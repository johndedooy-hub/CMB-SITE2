<?php
/**
 * CHAZZO MUSIC BOX (CMB) - MASTER FRONTEND STANDALONE VERIFIED DEF
 * Bestandsnaam: index.php
 */
error_reporting(0);
require_once dirname(__DIR__) . '/cmb-core/cmb-lib.php';
// Fast path: reuse the most recent complete live response for at most two seconds.
// Private theme previews bypass this shared cache.
if (isset($_GET['api_live_data']) && !isset($_GET['theme_preview'])) {
    $cmbFastFile = cmb_cache_dir() . 'live-sync-public.json';
    if (is_file($cmbFastFile) && time() - (int)@filemtime($cmbFastFile) <= 2) {
        $cmbCached = json_decode((string)@file_get_contents($cmbFastFile), true);
        if (is_array($cmbCached) && !empty($cmbCached['sync_hash'])) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
            $cmbCached['server_ms'] = (int)round(microtime(true) * 1000);
            $cmbCached['tz_offset'] = (int)date('Z');
            if (isset($_GET['h']) && hash_equals($cmbCached['sync_hash'], (string)$_GET['h'])) {
                echo json_encode(['same'=>1,'sync_hash'=>$cmbCached['sync_hash'],'server_ms'=>$cmbCached['server_ms'],'tz_offset'=>$cmbCached['tz_offset']]);
            } else echo json_encode($cmbCached, JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}

define('CMB_PART', true);
require_once __DIR__ . '/cmb-header-func.php';

$dataDir = cmb_data_dir();
$cacheDir = cmb_cache_dir();
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

$settingsFile = $dataDir . 'cmb-settings.json';
$slidesFile = $dataDir . 'cmb-slides.json';

$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];
$slides = file_exists($slidesFile) ? json_decode(file_get_contents($slidesFile), true) ?: [] : [];

// Thema: gepland of standaard; ?theme_preview=... toont (alleen ingelogd) een thema als voorbeeld
$themePreview = '';
if (isset($_GET['theme_preview']) && preg_match('/^(original|preset:[a-z]+|own:[a-z0-9_-]+)$/', (string)$_GET['theme_preview'])) {
    if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
    if (!empty($_SESSION['cmb_auth'])) $themePreview = (string)$_GET['theme_preview'];
    session_write_close();
}
$settings = cmb_theme_apply($settings, $themePreview !== '' ? $themePreview : null);
$designHash = md5(json_encode($settings));


$bgGlobal = $settings['bg_studio_global'] ?? $settings['bg_global'] ?? [];
$bgPlayer = $settings['bg_studio_player'] ?? $settings['bg_player'] ?? [];
$brandMode = $settings['brand_mode'] ?? 'text';
$brandMediaUrl = htmlspecialchars($settings['brand_media_url'] ?? '', ENT_QUOTES, 'UTF-8');
$logoUrl = htmlspecialchars($settings['logo_url'] ?? '', ENT_QUOTES, 'UTF-8');

$stationTitle = htmlspecialchars($settings['station_title'] ?? '', ENT_QUOTES, 'UTF-8');
$stationSub = htmlspecialchars($settings['station_sub'] ?? 'RETRO • [VISUAL]-RADIO • DANCE', ENT_QUOTES, 'UTF-8');
$stationSubNow = htmlspecialchars($settings['station_sub_now'] ?? '', ENT_QUOTES, 'UTF-8');
$studioMail = htmlspecialchars($settings['studio_mail'] ?? 'mail de studio: mail@chazzo.com', ENT_QUOTES, 'UTF-8');
$plMail = htmlspecialchars(cmb_pl_mail($settings), ENT_QUOTES, 'UTF-8');
$slideCornerBadge = htmlspecialchars($settings['slide_corner_badge'] ?? 'CMB', ENT_QUOTES, 'UTF-8');

$lblWeather = htmlspecialchars($settings['lbl_weather_text'] ?? 'WEER', ENT_QUOTES, 'UTF-8');
$lblTime = htmlspecialchars($settings['lbl_time_text'] ?? 'TIJD', ENT_QUOTES, 'UTF-8');
$weatherCityDisplay = htmlspecialchars($settings['weather_city_display'] ?? 'Steenbergen', ENT_QUOTES, 'UTF-8');
$weatherLat = htmlspecialchars($settings['weather_city_lat'] ?? '51.58', ENT_QUOTES, 'UTF-8');
$weatherLon = htmlspecialchars($settings['weather_city_lon'] ?? '4.32', ENT_QUOTES, 'UTF-8');
$colorValTime = htmlspecialchars($settings['color_val_time'] ?? '#ffffff', ENT_QUOTES, 'UTF-8');
$colorValWeather = htmlspecialchars($settings['color_val_weather'] ?? '#00e676', ENT_QUOTES, 'UTF-8');


$bottomLeft = htmlspecialchars($settings['bottom_left_text'] ?? 'CHAZZOMUSICBOX', ENT_QUOTES, 'UTF-8');
$bottomRight = htmlspecialchars($settings['bottom_right_text'] ?? '', ENT_QUOTES, 'UTF-8');
$trackFbRaw = trim((string)($settings['track_fallback_title'] ?? 'CMB Live Uitzending'));
$trackFbParts = strpos($trackFbRaw, ' - ') !== false ? explode(' - ', $trackFbRaw, 2) : ['', $trackFbRaw];
$trackFallbackArtist = htmlspecialchars(trim($trackFbParts[0]), ENT_QUOTES, 'UTF-8');
$trackFallback = htmlspecialchars(trim($trackFbParts[1]), ENT_QUOTES, 'UTF-8');

require __DIR__ . '/cmb-header-data.php';


$volShape = $settings['vol_shape'] ?? 'blocks';
// Rechterkant: weergave Volledig of Compact, wisselen van plek en het eigen tekstframe
// Bovenste regel: elk onderdeel een plek 1-5 van links naar rechts (0 = uit). Zonder keuze blijft alles zoals het was.
$topEls = ['logo', 'brand', 'weather', 'time', 'mail', 'traffic'];
$topSlots = cmb_top_slots($settings);
$topCustom = $topSlots !== null;
$topOrder = [];
if ($topCustom) {
    foreach ($topEls as $ti => $te) if ($topSlots[$te] > 0) $topOrder[] = [$topSlots[$te], $ti, $te];
    usort($topOrder, function ($x, $y) { return [$x[0], $x[1]] <=> [$y[0], $y[1]]; });
    $topOrder = array_map(function ($x) { return $x[2]; }, $topOrder);
}
$layoutCompact = (($settings['layout_mode'] ?? 'full') === 'compact');
// Slide-weergave links: 'overzicht' (tegels, zoals info.php) of 'slideshow'. Het tv-adres (tv.php) toont altijd de slideshow.
$slideView = (!defined('CMB_TV') && (($settings['slide_view'] ?? 'slideshow') === 'overzicht')) ? 'overzicht' : 'slideshow';
$ovCols = max(1, min(4, intval($settings['ov_cols'] ?? 3)));
$ovSize = in_array(($settings['ov_size'] ?? 'm'), ['s', 'm', 'l'], true) ? $settings['ov_size'] : 'm';
$cmbSelf = defined('CMB_SELF') ? CMB_SELF : 'index.php';
$ovBg = in_array(($settings['ov_bg'] ?? 'tile'), ['tile', 'both', 'frame', 'none'], true) ? $settings['ov_bg'] : 'tile'; // achtergrond van het overzicht: per tegel, één voor het hele frame, of geen
$ovDark = max(0, min(95, intval($settings['ov_dark'] ?? 45)));
$ovAccent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($settings['ov_accent'] ?? '')) ? $settings['ov_accent'] : '#63f6ff'; // kleur van de kopjes, onderregel en rand in info en tegels
$ovBlur = max(0, min(25, intval($settings['ov_blur'] ?? 6)));
$rightSwap = !empty($settings['right_swap']);
$compactH = max(130, min(320, intval($settings['compact_height'] ?? 200)));
// Eigen tekstframe: tot 6 beelden/teksten die om de zoveel seconden wisselen (zonder lijst: de oude losse tekst)
$ownSec = max(3, min(600, intval($settings['own_sec'] ?? 10)));
$ownRaw = (isset($settings['own_items']) && is_array($settings['own_items'])) ? $settings['own_items'] : [[
    'text' => (string)($settings['own_text'] ?? ''), 'style' => $settings['style_own_text'] ?? [], 'media' => $settings['own_media'] ?? '',
    'blur' => $settings['own_blur'] ?? 0, 'darken' => $settings['own_darken'] ?? 35,
]];
$ownItems = [];
foreach (array_slice(array_values($ownRaw), 0, 6) as $oi) {
    if (!is_array($oi)) continue;
    $om = trim((string)($oi['media'] ?? ''));
    if ($om !== '' && !preg_match('~^(https://|cmb-uploads/|cmb-assets/|/)[^\s"\'<>]*$~i', $om)) $om = '';
    $ot = (string)($oi['text'] ?? '');
    if ($om === '' && trim($ot) === '') continue;
    $ownItems[] = [
        'text' => $ot, 'style' => (is_array($oi['style'] ?? null) && $oi['style']) ? $oi['style'] : ['size' => '22px', 'color' => '#ffffff'],
        'media' => $om, 'video' => $om !== '' && preg_match('/\.(mp4|webm|m4v|mov|ogv)(\?|#|$)/i', $om),
        'blur' => max(0, min(25, intval($oi['blur'] ?? 0))), 'darken' => max(0, min(95, intval($oi['darken'] ?? 35))),
    ];
}
$cornerPosition = $settings['corner_btn_pos'] ?? 'top';
$artEffect = htmlspecialchars($settings['art_effect'] ?? 'aurora', ENT_QUOTES, 'UTF-8');

$lightLeft = $settings['lighting_left_frame'] ?? ['mode'=>'chaser', 'speed'=>'4s', 'dir'=>'cw', 'theme'=>'cyan'];
$lightRight = $settings['lighting_right_frame'] ?? ['mode'=>'chaser', 'speed'=>'4s', 'dir'=>'ccw', 'theme'=>'cyan'];

$lightOwn = $settings['lighting_own_frame'] ?? $lightRight; // eigen frame: zolang niet apart ingesteld dezelfde lijn als de speler

$streamUrl = htmlspecialchars($settings['streamUrl'] ?? 'https://host10.onlineradyotv.net:7048/stream/43', ENT_QUOTES, 'UTF-8');
$fallbackUrl = htmlspecialchars($settings['fallbackUrl'] ?? '', ENT_QUOTES, 'UTF-8');
// ---- Topmenu (eigen blok, tot 6 knoppen + oogje) ----
$topMenuEnabled = !isset($settings['topmenu_enabled']) || !empty($settings['topmenu_enabled']);
$topMenuEye = !isset($settings['topmenu_eye']) || !empty($settings['topmenu_eye']);
$topMenuItems = [];
for ($tmi = 1; $tmi <= 6; $tmi++) {
    $tmLbl = trim((string)($settings["top_menu_link_{$tmi}"] ?? ($tmi <= 4 ? "CMB {$tmi}" : '')));
    $tmOn = array_key_exists("top_menu_on_{$tmi}", $settings) ? !empty($settings["top_menu_on_{$tmi}"]) : ($tmLbl !== '');
    if (!$tmOn || $tmLbl === '') continue;
    $tmUrl = trim((string)($settings["top_menu_url_{$tmi}"] ?? ''));
    if ($tmUrl === '' || !preg_match('#^(https?://|mailto:|/|\#|[A-Za-z0-9_][A-Za-z0-9_./?=&%-]*)#i', $tmUrl) || preg_match('#^\s*(javascript|data|vbscript):#i', $tmUrl)) $tmUrl = '#';
    $topMenuItems[] = ['n' => $tmi, 'label' => $tmLbl, 'url' => $tmUrl, 'new' => !empty($settings["top_menu_new_{$tmi}"])];
}
$tmBgHex = cmb_clean_color($settings['topmenu_bg'] ?? '', '#0c0e1a');
$tmBgAlpha = max(0, min(100, intval($settings['topmenu_bar_alpha'] ?? 25))) / 100;
$tmRgb = sscanf($tmBgHex, '#%02x%02x%02x');
$topMenuBg = 'rgba(' . implode(',', $tmRgb) . ',' . $tmBgAlpha . ')';
$tmLineOn = !isset($settings['topmenu_line_on']) || !empty($settings['topmenu_line_on']);
$tmLineHex = cmb_clean_color($settings['topmenu_line'] ?? '', '#63f6ff');
$tmLr = sscanf($tmLineHex, '#%02x%02x%02x');
$topMenuLine = 'rgba(' . implode(',', $tmLr) . ',.45)';
$topMenuSep = 'rgba(' . implode(',', $tmLr) . ',.4)';
$streamChoices = cmb_stream_choices($settings, $dataDir);
$streamFlyoutOn = false; // keuzepaneel voor luisteraars vervallen
$artRotateMin = max(0, intval($settings['art_rotate_min'] ?? 0));
$artOverflow = !empty($settings['art_overflow']);
$artSpin = in_array($settings['art_spin'] ?? '', ['cw', 'ccw'], true) ? $settings['art_spin'] : '';
$artEffectKeys = array_keys(cmb_effects_list());

// ---- Zoekmachine, delen en icoon (beheer > Logo, icoon & zoekmachine) ----
$seoH = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$seoScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
$seoDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$seoSite = trim((string)($settings['seo_site_url'] ?? ''));
$seoBase = $seoSite !== '' ? rtrim($seoSite, '/') . '/' : $seoScheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $seoDir . '/';
$seoAbs = function ($u) use ($seoBase) {
    $u = trim((string)$u);
    if ($u === '') return '';
    if (preg_match('#^https?://#i', $u)) return $u;
    return $seoBase . ltrim($u, '/');
};
$seoTitleRaw = trim((string)($settings['seo_title'] ?? ''));
if ($seoTitleRaw === '') $seoTitleRaw = trim(html_entity_decode((string)$stationTitle, ENT_QUOTES, 'UTF-8'));
if ($seoTitleRaw === '') $seoTitleRaw = 'Chazzo Music Box';
$seoDesc = trim((string)($settings['seo_description'] ?? ''));
$seoKeys = trim((string)($settings['seo_keywords'] ?? ''));
$seoImg = $seoAbs($settings['seo_image_url'] ?? '');
$seoIcon = trim((string)($settings['favicon_url'] ?? ''));
$seoLogo = $seoAbs($settings['logo_url'] ?? '');
$seoNoIndex = ($settings['seo_robots'] ?? 'index') === 'noindex';
$seoColor = cmb_clean_color($settings['seo_theme_color'] ?? '', '#0c0e1a');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $seoH($seoTitleRaw) ?></title>
    <?php if ($seoDesc !== ''): ?><meta name="description" content="<?= $seoH($seoDesc) ?>"><?php endif; ?>
    <?php if ($seoKeys !== ''): ?><meta name="keywords" content="<?= $seoH($seoKeys) ?>"><?php endif; ?>
    <meta name="robots" content="<?= $seoNoIndex ? 'noindex, nofollow' : 'index, follow' ?>">
    <meta name="theme-color" content="<?= $seoH($seoColor) ?>">
    <?php if (!empty($settings['pwa_on'])): ?>
    <link rel="manifest" href="cmb-manifest.php">
    <link rel="apple-touch-icon" href="cmb-pwa-icon.php?s=180">
    <meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= $seoH(mb_substr(trim((string)($settings['pwa_name'] ?? '')) ?: $seoTitleRaw, 0, 24)) ?>">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <?php endif; ?>
    <?php if ($seoSite !== ''): ?><link rel="canonical" href="<?= $seoH($seoBase) ?>"><?php endif; ?>
    <?php if ($seoIcon !== ''): ?><link rel="icon" href="<?= $seoH($seoIcon) ?>"><link rel="apple-touch-icon" href="<?= $seoH($seoIcon) ?>"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= $seoH($seoTitleRaw) ?>">
    <meta property="og:title" content="<?= $seoH($seoTitleRaw) ?>">
    <?php if ($seoDesc !== ''): ?><meta property="og:description" content="<?= $seoH($seoDesc) ?>"><?php endif; ?>
    <?php if ($seoSite !== ''): ?><meta property="og:url" content="<?= $seoH($seoBase) ?>"><?php endif; ?>
    <?php if ($seoImg !== ''): ?><meta property="og:image" content="<?= $seoH($seoImg) ?>"><?php endif; ?>
    <meta name="twitter:card" content="<?= $seoImg !== '' ? 'summary_large_image' : 'summary' ?>">
    <meta name="twitter:title" content="<?= $seoH($seoTitleRaw) ?>">
    <?php if ($seoDesc !== ''): ?><meta name="twitter:description" content="<?= $seoH($seoDesc) ?>"><?php endif; ?>
    <?php if ($seoImg !== ''): ?><meta name="twitter:image" content="<?= $seoH($seoImg) ?>"><?php endif; ?>
    <?php if ($seoSite !== '' || $seoDesc !== ''): ?>
    <script type="application/ld+json"><?= json_encode(array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'RadioStation',
        'name' => $seoTitleRaw,
        'description' => $seoDesc,
        'url' => $seoSite !== '' ? $seoBase : '',
        'logo' => $seoLogo,
        'image' => $seoImg,
    ], function ($v) { return $v !== ''; }), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?></script>
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?php $fontsUrl = cmb_used_fonts_url($settings, $slides); ?>
    <link id="cmb-fonts-link" href="<?= htmlspecialchars($fontsUrl, ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Color+Emoji&display=swap" rel="stylesheet"><!-- emoji-vangnet voor tv's zonder emoji-lettertype; laadt alleen de emoji die echt gebruikt worden -->
    <link rel="stylesheet" href="cmb-assets/cmb-effects.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-effects.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-effects.css') : time() ?>">
    <?php if (file_exists(CMB_ROOT . '/cmb-custom.css')): ?><link rel="stylesheet" href="cmb-custom.css?v=<?= filemtime(CMB_ROOT . '/cmb-custom.css') ?>"><?php endif; ?>
    
    

<?php include __DIR__ . '/cmb-header-css.php'; ?>
    <style>
        :root {
            --app-bg: <?= htmlspecialchars($appBg) ?>;
            --font-main: '<?= htmlspecialchars($fontChoice) ?>', sans-serif;
            --brand-color: <?= htmlspecialchars($brandColor) ?>;
            <?= cmb_theme_vars_css($settings) ?>
        }
        .cmb-panel.cmb-stage-left { <?= getStaticFrameBorderCSS($lightLeft) ?> }
        .cmb-panel.cmb-stage-right { <?= getStaticFrameBorderCSS($lightRight) ?> }
        .cmb-panel.cmb-own-frame { <?= getStaticFrameBorderCSS($lightOwn) ?> }
        .cmb-brand-text h1 { <?= getStyleCSS($settings['style_header_title'] ?? [], 'var(--brand-color)') ?> }
        .cmb-brand-text .cmb-tagline { <?= getStyleCSS($settings['style_header_sub'] ?? [], 'var(--cyan)') ?> }
        .cmb-mid-notify { <?= getStyleCSS($settings['style_header_mail'] ?? [], 'var(--gold)') ?> }
        .cmb-roof { display: <?= $topMenuEnabled ? 'flex' : 'none' ?>; }
        .cmb-roof { background: <?= $topMenuBg ?>; <?= $tmLineOn ? 'border-bottom: 1px solid ' . $topMenuLine . ';' : '' ?> --roof-sep: <?= $topMenuSep ?>; }
        <?php foreach ($topMenuItems as $tmItem): ?>
        .cmb-topmenu-btn-<?= $tmItem['n'] ?> { <?= getStyleCSS($settings['style_top_menu_link_' . $tmItem['n']] ?? []) ?> }
        <?php endforeach; ?>
        .cmb-corner-play-btn { <?= getBorderCSS($settings['border_btn_play'] ?? [], 'var(--cyan)') ?> }
        .cmb-corner-vol-btn { <?= getBorderCSS($settings['border_btn_vol'] ?? [], 'var(--cyan)') ?> }
        .cmb-btn-fs { <?= getBorderCSS($settings['border_btn_fs'] ?? [], 'var(--cyan)') ?> }
        /* Titel en artiest: eigen opmaak (Teksten & stijl); de fallback-titel krijgt zijn eigen stijl */
        <?php if (!empty($settings['style_track_artist'])): ?>.cmb-artist { <?= getStyleCSS($settings['style_track_artist']) ?> }<?php endif; ?>
        <?php if (!empty($settings['style_track_song'])): ?>.cmb-title { <?= getStyleCSS($settings['style_track_song']) ?> }<?php endif; ?>
        <?php if (!empty($settings['style_track_fallback'])): ?>.cmb-title.is-fallback { <?= getStyleCSS($settings['style_track_fallback']) ?> }<?php endif; ?>
    </style>


</head>
<body>

<div id="cmb-global-bg"></div>

<audio id="cmb-core-audio" preload="none"></audio>

<!-- DAKMENU: eigen blok, tot 6 knoppen + oogje (beeld vergroten/verkleinen voor de kijker) -->
<div class="cmb-roof" id="cmb-roof">
    <div class="cmb-roof-links" id="cmb-roof-links">
        <?php foreach ($topMenuItems as $tmItem): ?>
            <a href="<?= htmlspecialchars($tmItem['url'], ENT_QUOTES, 'UTF-8') ?>" class="cmb-roof-link"<?= preg_match('~^(#info|(.*/)?info(\.php)?(\?.*)?)$~i', $tmItem['url']) ? ' onclick="openInfoOverlay();return false;"' : '' ?><?= $tmItem['new'] ? ' target="_blank" rel="noopener noreferrer"' : '' ?>><span class="cmb-topmenu-btn-<?= $tmItem['n'] ?>"><?= htmlspecialchars($tmItem['label'], ENT_QUOTES, 'UTF-8') ?></span></a>
        <?php endforeach; ?>
    </div>
    <?php if ($topMenuEye): ?>
    <div class="cmb-zoom-drawer" id="cmb-zoom-drawer">
        <div class="cmb-zoom-box">
            <button onclick="changeZoom(-0.1)" aria-label="Kleiner">-</button>
            <button onclick="changeZoom(0.1)" aria-label="Groter">+</button>
        </div>
        <div class="cmb-zoom-tab" onclick="toggleZoomDrawer()" title="Beeld groter of kleiner maken">👁️‍🗨️</div>
    </div>
    <?php endif; ?>
</div>

