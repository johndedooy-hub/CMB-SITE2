<?php
/* Web-app-manifest: laat de speler als app op het beginscherm installeren (PWA). Alleen actief als het in het beheer aanstaat. */
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
$s = cmb_load_settings_safe();
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache');
if (empty($s['pwa_on'])) { http_response_code(404); echo '{}'; exit; }
$name = trim((string)($s['seo_title'] ?? '')) ?: (trim(html_entity_decode((string)($s['station_title'] ?? ''), ENT_QUOTES, 'UTF-8')) ?: 'Chazzo Music Box');
$short = mb_substr(trim((string)($s['pwa_name'] ?? '')) ?: $name, 0, 24);
$col = cmb_clean_color($s['seo_theme_color'] ?? '', '#0c0e1a');
echo json_encode([
    'name' => $name, 'short_name' => $short, 'description' => trim((string)($s['seo_description'] ?? '')),
    'start_url' => './', 'scope' => './', 'id' => './', 'display' => 'standalone', 'orientation' => 'any',
    'background_color' => $col, 'theme_color' => $col, 'lang' => 'nl',
    'icons' => [
        ['src' => 'cmb-pwa-icon.php?s=192', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'cmb-pwa-icon.php?s=512', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
        ['src' => 'cmb-pwa-icon.php?s=512&m=1', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
