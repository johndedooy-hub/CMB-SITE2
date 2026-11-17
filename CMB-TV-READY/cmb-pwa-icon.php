<?php
/* App-pictogram (PNG) uit het favicon of logo van de speler; zonder beeld een eenvoudige letter-tegel. ?s=192|512|180, &m=1 = met extra rand (maskable). */
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
$s = cmb_load_settings_safe();
$size = in_array((int)($_GET['s'] ?? 192), [180, 192, 512], true) ? (int)$_GET['s'] : 192;
$mask = !empty($_GET['m']);
$col = cmb_clean_color($s['seo_theme_color'] ?? '', '#0c0e1a');
$src = trim((string)($s['pwa_icon_url'] ?? '')) ?: (trim((string)($s['logo_url'] ?? '')) ?: trim((string)($s['favicon_url'] ?? '')));
$bytes = '';
if ($src !== '') {
    if (preg_match('#^https?://#i', $src)) {
        $ctx = stream_context_create(['http' => ['timeout' => 8, 'header' => "User-Agent: CMB-PWA/1.0\r\n"]]);
        $bytes = (string)@file_get_contents($src, false, $ctx);
    } else {
        $p = realpath(__DIR__ . '/' . ltrim(parse_url($src, PHP_URL_PATH) ?: '', '/'));
        if ($p && strpos($p, realpath(__DIR__)) === 0 && is_file($p)) $bytes = (string)@file_get_contents($p);
    }
}
header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');
if (!function_exists('imagecreatetruecolor')) { http_response_code(404); exit; }
$im = imagecreatetruecolor($size, $size);
$rgb = sscanf(ltrim($col, '#'), '%02x%02x%02x');
$bg = imagecolorallocate($im, $rgb[0] ?? 12, $rgb[1] ?? 14, $rgb[2] ?? 26);
imagefill($im, 0, 0, $bg);
$pad = (int)round($size * ($mask ? 0.2 : 0.1));
$src_im = $bytes !== '' ? @imagecreatefromstring($bytes) : false;
if ($src_im) {
    $w = imagesx($src_im); $h = imagesy($src_im); $box = $size - 2 * $pad;
    $r = min($box / $w, $box / $h); $nw = max(1, (int)round($w * $r)); $nh = max(1, (int)round($h * $r));
    imagealphablending($im, true);
    imagecopyresampled($im, $src_im, (int)(($size - $nw) / 2), (int)(($size - $nh) / 2), 0, 0, $nw, $nh, $w, $h);
} else {
    $white = imagecolorallocate($im, 255, 209, 102);
    $ring = (int)round($size * 0.62);
    imagefilledellipse($im, (int)($size / 2), (int)($size / 2), $ring, $ring, $white);
    imagefilledellipse($im, (int)($size / 2), (int)($size / 2), (int)($ring * 0.78), (int)($ring * 0.78), $bg);
    imagefilledrectangle($im, (int)($size * 0.46), (int)($size * 0.36), (int)($size * 0.54), (int)($size * 0.64), $white);
}
imagepng($im);
