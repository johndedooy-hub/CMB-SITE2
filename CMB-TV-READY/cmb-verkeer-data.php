<?php
/* Geeft de verkeersinfo (files en ongelukken) als JSON aan de speler. Ververst zelf op de achtergrond, een cronjob is niet nodig. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
$s = cmb_load_settings_safe();
if (empty($s['verkeer_on'])) { echo json_encode(['on' => false]); exit; }
$max = max(1, min(200, intval($s['verkeer_max2'] ?? 100)));
$f = cmb_verkeer_file();
$d = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
$test = isset($_GET['test']);
// Alleen meldingen met wegnummer of plaats tonen; de rest wacht tot de plaats is opgezocht
$all = $d['items'] ?? []; $shown = []; $pending = 0;
foreach ($all as $it) { if (($it['road'] ?? '') !== '' || !in_array($it['text'] ?? '', ['File', 'Ongeluk', ''], true)) $shown[] = $it; else $pending++; }
$nf = 0; $no = 0; $km = 0.0;
foreach ($shown as $it) { if (($it['t'] ?? '') === 'file') { $nf++; $km += floatval($it['km'] ?? 0); } else $no++; }
$stale = empty($d['tried']) || time() - intval($d['tried']) > ($d && empty($d['err']) && $pending === 0 ? 300 : 90);
$lock = null;
$refresh = function () use ($f) {
    $lk = @fopen($f . '.lock', 'c');
    if ($lk && flock($lk, LOCK_EX | LOCK_NB)) { cmb_verkeer_refresh(); flock($lk, LOCK_UN); }
    if ($lk) fclose($lk);
};
if ($test || empty($d['t'])) { $refresh(); $d = json_decode((string)@file_get_contents($f), true) ?: []; $stale = false; }
$out = [
    'on' => true, 'files' => $nf, 'ongelukken' => $no, 'km' => round($km, 1), 'pending' => $pending,
    'items' => array_slice($shown, 0, $max), 'total' => count($shown), 't' => intval($d['t'] ?? 0), 'err' => (string)($d['err'] ?? ''),
];
if ($test) { $out['types'] = $d['types'] ?? []; $out['sample'] = $d['sample'] ?? ''; $out['sample_file'] = $d['sample_file'] ?? ''; $out['geo'] = $d['geo'] ?? []; $out['vild'] = $d['vild'] ?? []; }
echo json_encode($out, JSON_UNESCAPED_UNICODE);
if ($stale) {
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); elseif (function_exists('litespeed_finish_request')) litespeed_finish_request(); else { @ob_flush(); @flush(); }
    ignore_user_abort(true);
    $refresh();
}
