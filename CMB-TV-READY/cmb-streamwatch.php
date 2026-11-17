<?php
/**
 * CMB - STREAMWATCH (aanroepbaar voor een cronjob)
 * Controleert stream 1 en 2 en mailt bij uitval naar het adres uit Studio > Streams.
 *
 * Cronjob op de host, elke minuut, één van deze twee:
 *   php /pad/naar/cmb-streamwatch.php
 *   curl -s https://jouwsite.nl/cmb/cmb-streamwatch.php > /dev/null
 *
 * Zonder cronjob werkt het ook: zolang er iemand luistert, controleert de speler zelf elke minuut.
 */
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/cmb-core/cmb-lib.php';
$settingsFile = cmb_data_dir() . 'cmb-settings.json';
$settings = file_exists($settingsFile) ? (json_decode(file_get_contents($settingsFile), true) ?: []) : [];
$r = cmb_streamwatch_tick($settings, true);
if (isset($r['skipped'])) { echo 'overgeslagen: ' . $r['skipped'] . "\n"; exit; }
echo 'stream1=' . ($r['s1'] === null ? '-' : ($r['s1'] ? 'ok' : 'UIT')) . ' stream2=' . ($r['s2'] === null ? '-' : ($r['s2'] ? 'ok' : 'UIT')) . ' mails=' . $r['mails'] . "\n";
