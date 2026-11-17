<?php
/* Geeft de OV-info (treinstoringen en werkzaamheden) als JSON aan de speler. Ververst zelf op de achtergrond. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
require_once __DIR__ . '/cmb-core/cmb-ov.php';
$s = cmb_load_settings_safe();
$trainOn = !empty($s['ov_on']); $busOn = !empty($s['bus_on']);
if (!$trainOn && !$busOn) { echo json_encode(['on' => false]); exit; }
$GLOBALS['cmb_train_on'] = $trainOn; $GLOBALS['cmb_bus_on'] = $busOn;
$f = cmb_ov_file();
$d = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
$test = isset($_GET['test']);
$bf = cmb_data_dir() . 'cmb-ov-bus.json';
$bd = is_file($bf) ? (json_decode((string)@file_get_contents($bf), true) ?: []) : [];
$stale = ($trainOn && (empty($d['tried']) || time() - intval($d['tried']) > (empty($d['err']) ? 300 : 120))) || ($busOn && (empty($bd['tried']) || time() - intval($bd['tried']) > (empty($bd['err']) ? 300 : 120)));
$refresh = function () use ($f) {
    $lk = @fopen($f . '.lock', 'c');
    if ($lk && flock($lk, LOCK_EX | LOCK_NB)) { if (!empty($GLOBALS['cmb_train_on'])) cmb_ov_refresh(); if (!empty($GLOBALS['cmb_bus_on'])) cmb_ov_alerts_refresh(); flock($lk, LOCK_UN); }
    if ($lk) fclose($lk);
};
if ($test || ($trainOn && empty($d['t'])) || ($busOn && empty($bd['t']))) { $refresh(); $d = json_decode((string)@file_get_contents($f), true) ?: []; $bd = json_decode((string)@file_get_contents($bf), true) ?: []; $stale = false; }
$items = $trainOn ? ($d['items'] ?? []) : []; $nw = 0; $ns = 0;
foreach ($items as $it) { if (($it['t'] ?? '') === 'werk') $nw++; else $ns++; }
$out = ['train_on' => $trainOn, 'bus_on' => $busOn, 'bus' => $busOn ? array_slice($bd['items'] ?? [], 0, 40) : [], 'bus_err' => (string)($bd['err'] ?? ''), 'on' => true, 'storingen' => $ns, 'werk' => $nw, 'items' => array_slice($items, 0, 60), 'total' => count($items), 't' => intval($d['t'] ?? 0), 'err' => (string)($d['err'] ?? '')];
if ($test) { $out['http'] = $d['http'] ?? 0; $out['key_set'] = cmb_ns_key($s) !== ''; $out['sample'] = $d['sample'] ?? ''; $out['bytes'] = $d['bytes'] ?? 0; $out['bus_http'] = $bd['http'] ?? 0; $out['bus_total'] = count($bd['items'] ?? []); $out['bus_alerts_in_feed'] = $bd['n'] ?? 0; $ag = []; foreach ($bd['items'] ?? [] as $it) foreach ($it['ag'] ?? ['-'] as $x) $ag[$x] = ($ag[$x] ?? 0) + 1; $out['bus_agencies'] = $ag; $out['bus_sample'] = array_slice($bd['items'] ?? [], 0, 6); $out['raw'] = $d['raw'] ?? ($d['rawfirst'] ?? ''); }
echo json_encode($out, JSON_UNESCAPED_UNICODE);
if ($stale) {
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request(); elseif (function_exists('litespeed_finish_request')) litespeed_finish_request(); else { @ob_flush(); @flush(); }
    ignore_user_abort(true);
    $refresh();
}
