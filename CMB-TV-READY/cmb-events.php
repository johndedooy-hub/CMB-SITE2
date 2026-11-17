<?php
/**
 * CHAZZO MUSIC BOX (CMB) - PULSE (Server-Sent Events)
 * Houdt per open speler een lichte verbinding en meldt zodra er iets is gewijzigd
 * (beheer roept cmb_pulse() aan; daarnaast wordt de wijzigingstijd van de data-bestanden bewaakt).
 * Uitzetten kan met 'sse' => false in cmb-config.php: de speler gebruikt dan alleen de poll.
 */
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (cmb_cfg('sse', true) === false) { http_response_code(204); exit; }

@set_time_limit(0);
ignore_user_abort(false);
if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
while (ob_get_level() > 0) @ob_end_clean();
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('X-Accel-Buffering: no');
header('Connection: keep-alive');
ob_implicit_flush(true);

$dataDir = cmb_data_dir();
$pulseFile = cmb_cache_dir() . 'pulse.json';
$sig = function () use ($dataDir, $pulseFile) {
    static $lastScan = 0, $m = 0;
    clearstatcache();
    if (time() - $lastScan >= 6) {
        $m = 0;
        foreach (glob($dataDir . '*.json') ?: [] as $f) { $t = @filemtime($f); if ($t > $m) $m = $t; }
        $lastScan = time();
    }
    $p = is_file($pulseFile) ? (string)@file_get_contents($pulseFile) : '';
    return $m . '-' . substr(md5($p), 0, 10);
};

$send = function ($chunk) { echo $chunk; @flush(); };
$last = $sig();
$send("retry: 1500\n");
$send("event: hello\ndata: " . $last . "\n\n");
$end = time() + 55;           // netjes sluiten; de browser verbindt zelf weer (spaart PHP-processen)
$lastKa = time();
while (time() < $end) {
    usleep(1500000);
    if (connection_aborted()) exit;
    $now = $sig();
    if ($now !== $last) { $last = $now; $send("event: pulse\ndata: " . $now . "\n\n"); $lastKa = time(); }
    elseif (time() - $lastKa >= 15) { $send(": ka\n\n"); $lastKa = time(); }
}
