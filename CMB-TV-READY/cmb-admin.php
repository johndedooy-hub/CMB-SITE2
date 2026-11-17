<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
cmb_unpack_payload();
cmb_admin_media_urls();
require_once CMB_ROOT . '/cmb-core/cmb-nav.php';
require_once CMB_ROOT . '/cmb-admin-parts/cmb-admin-frames.php';

// Inlog staat in cmb-config.php (buiten public_html), zie cmb-config.example.php

if (isset($_GET['logout'])) {
    unset($_SESSION['cmb_auth']);
    session_destroy();
    header('Location: cmb-admin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $u = trim($_POST['login_user'] ?? '');
    $p = trim($_POST['login_pass'] ?? '');
    if (cmb_check_login($u, $p)) {
        $_SESSION['cmb_auth'] = true;
        $_SESSION['cmb_user'] = $u;
        header('Location: cmb-start.php');
        exit;
    } else {
        $login_error = "Onjuiste inloggegevens!";
    }
}

if (empty($_SESSION['cmb_auth'])) {
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - CMB Admin</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #070812; color: #f7f7ff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 16px; }
        .login-box { background: #121427; border: 1px solid rgba(99, 246, 255, 0.3); border-radius: 16px; padding: 32px; width: 100%; max-width: 360px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); text-align: center; }
        h2 { color: #63f6ff; margin-top: 0; margin-bottom: 20px; font-size: 20px; }
        .field { margin-bottom: 14px; text-align: left; }
        label { display: block; font-size: 11px; text-transform: uppercase; color: #a8aac8; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; background: #05060d; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px; border-radius: 8px; font-size: 14px; }
        input:focus { border-color: #63f6ff; outline: none; }
        button { width: 100%; background: linear-gradient(135deg, #00e676, #00b0ff); border: none; color: #070812; padding: 12px; border-radius: 8px; font-weight: 900; font-size: 14px; cursor: pointer; margin-top: 10px; }
        .err { background: rgba(255, 23, 68, 0.15); border: 1px solid #ff1744; color: #ff1744; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔒 CMB Inloggen</h2>
        <?php if (!empty($login_error)): ?><div class="err"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Gebruikersnaam</label>
                <input type="text" name="login_user" autofocus required>
            </div>
            <div class="field">
                <label>Wachtwoord</label>
                <input type="password" name="login_pass" required>
            </div>
            <button type="submit" name="login_submit">INLOGGEN</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// DATA INITIALISATIE
$dataDir = cmb_data_dir();
$uploadDir = cmb_upload_dir();
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$settingsFile = $dataDir . 'cmb-settings.json';
// Bewerk je een eigen thema (zie cmb-themes.php)? Dan werkt het beheer op de kopie van dat thema.
$themeEdit = null;
if (!empty($_SESSION['cmb_theme_edit'])) {
    $tf = cmb_theme_file($_SESSION['cmb_theme_edit']);
    if (is_file($tf)) { $settingsFile = $tf; $themeEdit = cmb_theme_label('own:' . $_SESSION['cmb_theme_edit']); }
    else unset($_SESSION['cmb_theme_edit']);
}
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];
$msg = '';

$fonts = cmb_font_options();

$effectsList = cmb_effects_list();

define('CMB_PART', true);
require_once CMB_ROOT . '/cmb-admin-parts/cmb-admin-func.php';

// OPSLAAN HANDLER (ALLEEN INSTANTIES VAN DIT SCHEMA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_admin'])) {
    $curTab = $_POST['active_tab_context'] ?? 'frames';

    if ($curTab === 'verlichting') $curTab = 'frames';

    if ($curTab === 'frames') {
        $settings['lighting_left_frame'] = extractFrameLighting('fl_left');
        $settings['lighting_right_frame'] = extractFrameLighting('fl_right');
        if (isset($_POST['fl_own_light_mode'])) $settings['lighting_own_frame'] = extractFrameLighting('fl_own');
        // Rand als plaatje: eigen plaatje toevoegen en per frame de keuze bewaren
        foreach (['left', 'right', 'own'] as $rUp) {
            $rf = $_FILES['upload_ring_' . $rUp] ?? null;
            if (!$rf || empty($rf['name']) || !is_uploaded_file($rf['tmp_name'] ?? '')) continue;
            $rInfo = @getimagesize($rf['tmp_name']);
            $rExt = ($rInfo && $rInfo[2] === IMAGETYPE_JPEG) ? 'jpg' : (($rInfo && $rInfo[2] === IMAGETYPE_PNG) ? 'png' : (($rInfo && $rInfo[2] === IMAGETYPE_WEBP) ? 'webp' : ''));
            if ($rExt === '' || $rf['size'] > 2 * 1048576) continue;
            $rBase = preg_replace('/[^A-Za-z0-9_-]/', '_', pathinfo($rf['name'], PATHINFO_FILENAME)) ?: 'rand';
            if (!is_dir(CMB_ROOT . '/cmb-frames')) @mkdir(CMB_ROOT . '/cmb-frames', 0755, true);
            @move_uploaded_file($rf['tmp_name'], CMB_ROOT . '/cmb-frames/eigen-' . $rBase . '.' . $rExt);
        }
        $ringAll = cmb_ring_list();
        foreach (['left' => 'lighting_left_frame', 'right' => 'lighting_right_frame', 'own' => 'lighting_own_frame'] as $rk => $rKey) {
            if (!isset($_POST['ring_' . $rk . '_mode'])) continue;
            $rMode = in_array($_POST['ring_' . $rk . '_mode'], ['image', 'carousel'], true) ? $_POST['ring_' . $rk . '_mode'] : 'lines';
            $rImgs = [];
            foreach ($ringAll as $rfi => $rfn) if (isset($_POST['ring_' . $rk . '_i_' . $rfi]) && $_POST['ring_' . $rk . '_i_' . $rfi] === $rfn) $rImgs[] = $rfn;
            if (!is_array($settings[$rKey] ?? null)) $settings[$rKey] = ($rk === 'own' && is_array($settings['lighting_right_frame'] ?? null)) ? $settings['lighting_right_frame'] : [];
            if (!$rImgs) $rMode = 'lines';
            $settings[$rKey]['ring_mode'] = $rMode;
            $settings[$rKey]['ring_imgs'] = array_slice($rImgs, 0, 6);
            $settings[$rKey]['ring_px'] = max(1, min(3, intval($_POST['ring_' . $rk . '_px'] ?? 2)));
            $settings[$rKey]['ring_sec'] = max(2, min(600, intval($_POST['ring_' . $rk . '_sec'] ?? 12)));
        }
        // Rechterkant: weergave, wisselen van plek en het eigen tekstframe
        $settings['layout_mode'] = (($_POST['layout_mode'] ?? '') === 'compact') ? 'compact' : 'full';
        $settings['compact_height'] = max(130, min(320, intval($_POST['compact_height'] ?? 200)));
        $settings['right_swap'] = !empty($_POST['right_swap']);
        $settings['h_swap'] = !empty($_POST['h_swap']);
        // Linkerkant: slideshow of tegeloverzicht
        $settings['slide_view'] = (($_POST['slide_view'] ?? '') === 'overzicht') ? 'overzicht' : 'slideshow';
        $settings['ov_cols'] = max(1, min(4, intval($_POST['ov_cols'] ?? 3)));
        $settings['ov_size'] = in_array(($_POST['ov_size'] ?? 'm'), ['s', 'm', 'l'], true) ? $_POST['ov_size'] : 'm';
        $settings['ov_bg'] = in_array(($_POST['ov_bg'] ?? 'tile'), ['tile', 'both', 'frame', 'none'], true) ? $_POST['ov_bg'] : 'tile';
        $settings['ov_dark'] = max(0, min(95, intval($_POST['ov_dark'] ?? 45)));
        $settings['ov_blur'] = max(0, min(25, intval($_POST['ov_blur'] ?? 6)));
        $settings['ov_accent'] = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($_POST['ov_accent'] ?? '')) ? $_POST['ov_accent'] : '#63f6ff';
        // Eigen tekstframe: 6 plekken met elk tekst, opmaak en achtergrond
        $oldOwn = (isset($settings['own_items']) && is_array($settings['own_items'])) ? array_values($settings['own_items']) : [[
            'text' => $settings['own_text'] ?? '', 'style' => $settings['style_own_text'] ?? [], 'media' => $settings['own_media'] ?? '', 'blur' => $settings['own_blur'] ?? 0, 'darken' => $settings['own_darken'] ?? 35,
        ]];
        $newOwn = [];
        for ($oi = 0; $oi < 6; $oi++) {
            $pf = 'own_i' . $oi;
            $old = (isset($oldOwn[$oi]) && is_array($oldOwn[$oi])) ? $oldOwn[$oi] : [];
            $st = !empty($_POST[$pf . '_touched']) ? extractStyleSuite($pf) : ($old['style'] ?? []);
            $om = trim((string)($_POST[$pf . '_media'] ?? ''));
            $up = handleUpload('upload_' . $pf, $uploadDir);
            if ($up) $om = $up;
            if (!empty($_POST[$pf . '_del'])) $om = '';
            if ($om !== '' && !preg_match('~^(https://|cmb-uploads/|cmb-assets/|/)[^\s"\'<>]*$~i', $om)) $om = '';
            $newOwn[] = [
                'text' => mb_substr(trim((string)($_POST[$pf . '_val'] ?? '')), 0, 600), 'style' => $st, 'media' => $om,
                'blur' => max(0, min(25, intval($_POST[$pf . '_blur'] ?? 0))), 'darken' => max(0, min(95, intval($_POST[$pf . '_darken'] ?? 35))),
            ];
        }
        $settings['own_items'] = $newOwn;
        $settings['own_sec'] = max(2, min(600, intval($_POST['own_sec'] ?? 10)));
        // Eén vaste achtergrond voor alle slides (behalve nood)
        $so = trim((string)($_POST['slide_one_media'] ?? ''));
        $sup = handleUpload('upload_slide_one', $uploadDir);
        if ($sup) $so = $sup;
        if (!empty($_POST['slide_one_del'])) $so = '';
        if ($so !== '' && !preg_match('~^(https://|cmb-uploads/|cmb-assets/|/)[^\s"\'<>]*$~i', $so)) $so = '';
        $settings['slide_one_media'] = $so;
        $settings['slide_one_on'] = !empty($_POST['slide_one_on']) && $so !== '';
        $settings['slide_one_blur'] = max(0, min(25, intval($_POST['slide_one_blur'] ?? 0)));
        $settings['slide_one_darken'] = max(0, min(95, intval($_POST['slide_one_darken'] ?? 35)));
    } elseif ($curTab === 'topmenu') {
        $settings['topmenu_enabled'] = !empty($_POST['topmenu_enabled']);
        $settings['topmenu_eye'] = !empty($_POST['topmenu_eye']);
        $tmBg = trim($_POST['topmenu_bg'] ?? '#0c0e1a');
        $settings['topmenu_bg'] = preg_match('/^#[0-9a-fA-F]{6}$/', $tmBg) ? $tmBg : '#0c0e1a';
        $settings['topmenu_bar_alpha'] = max(0, min(100, intval($_POST['topmenu_bar_alpha'] ?? 25)));
        $settings['topmenu_line_on'] = !empty($_POST['topmenu_line_on']);
        $tmLn = trim($_POST['topmenu_line'] ?? '#63f6ff');
        $settings['topmenu_line'] = preg_match('/^#[0-9a-fA-F]{6}$/', $tmLn) ? $tmLn : '#63f6ff';
        for ($mi = 1; $mi <= 6; $mi++) {
            $settings["top_menu_link_{$mi}"] = trim($_POST["top_link_{$mi}_val"] ?? '');
            $settings["top_menu_on_{$mi}"] = !empty($_POST["top_menu_on_{$mi}"]);
            $settings["top_menu_url_{$mi}"] = trim($_POST["top_menu_url_{$mi}"] ?? '');
            $settings["top_menu_new_{$mi}"] = !empty($_POST["top_menu_new_{$mi}"]);
            $settings["style_top_menu_link_{$mi}"] = extractStyleSuite("top_link_{$mi}");
        }
        $settings['border_top_menu'] = extractBorderSuite('btn_roof');
        $settings['border_btn_play'] = extractBorderSuite('btn_play');
        $settings['border_btn_vol'] = extractBorderSuite('btn_vol');
        $settings['border_btn_logo'] = extractBorderSuite('btn_logo');
        $settings['border_btn_pl'] = extractBorderSuite('btn_pl');
    } elseif ($curTab === 'identiteit') {
        if (isset($_POST['top_slot_logo'])) {
            if (!empty($_POST['top_default'])) { unset($settings['top_slots'], $settings['top_pos']); }
            else {
                $tsNew = [];
                foreach (['logo', 'brand', 'weather', 'time', 'mail', 'traffic'] as $tsk) $tsNew[$tsk] = max(0, min(7, intval($_POST['top_slot_' . $tsk] ?? 0)));
                $settings['top_slots'] = $tsNew; unset($settings['top_pos']);
            }
        }
        if (isset($_POST['top_slot_logo'])) { $settings['tw_swap'] = !empty($_POST['tw_swap']); $settings['logo_scale'] = max(100, min(200, intval($_POST['logo_scale'] ?? 100))); $settings['tw_sec'] = max(2, min(60, intval($_POST['tw_sec'] ?? 6))); }
        $plMail = trim((string)($_POST['pl_mail'] ?? ''));
        $settings['pl_mail'] = preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $plMail) ? $plMail : '';
        $plOld = is_array($settings['pl_blocks'] ?? null) ? $settings['pl_blocks'] : [];
        $plNew = [];
        for ($i = 0; $i < 10; $i++) {
            $img = trim((string)($_POST["pl_img_$i"] ?? ''));
            $up = handleUpload("pl_file_$i", $uploadDir);
            if ($up) $img = $up;
            if (!empty($_POST["pl_del_$i"])) $img = '';
            if ($img !== '' && !preg_match('~^(https://|cmb-uploads/|cmb-assets/|/)[^\s"\'<>]*$~i', $img)) $img = '';
            $url = trim((string)($_POST["pl_url_$i"] ?? ''));
            if (!preg_match('~^https?://[^\s"\'<>]+$~i', $url)) $url = '';
            $plNew[$i] = ['img' => $img, 'text' => mb_substr(trim(strip_tags((string)($_POST["pl_text_$i"] ?? ''))), 0, 24), 'url' => $url];
        }
        $settings['pl_blocks'] = $plNew;
        $settings['station_title'] = trim($_POST['st_title_val'] ?? '');
        cmb_keep_style($settings, 'style_station_title', 'st_title');
        $settings['station_sub'] = trim($_POST['st_sub_val'] ?? 'RETRO • [VISUAL]-RADIO • DANCE');
        cmb_keep_style($settings, 'style_station_sub', 'st_sub');
        $settings['studio_mail'] = trim($_POST['mail_val'] ?? 'mail de studio: mail@chazzo.com');
        cmb_keep_style($settings, 'style_studio_mail', 'mail');
        $waN = preg_replace('/\D/', '', (string)($_POST['wa_number'] ?? ''));
        if (strpos($waN, '00') === 0) $waN = substr($waN, 2); elseif (strlen($waN) === 10 && $waN[0] === '0') $waN = '31' . substr($waN, 1);
        $settings['wa_number'] = substr($waN, 0, 15);
        $settings['wa_on'] = !empty($_POST['wa_on']);
        $settings['wa_text'] = mb_substr(trim(strip_tags((string)($_POST['wa_text'] ?? ''))), 0, 200);
        $settings['ov_on'] = !empty($_POST['ov_on']);
        $settings['bus_on'] = !empty($_POST['bus_on']);
        $settings['pwa_on'] = !empty($_POST['pwa_on']);
        $settings['pwa_name'] = mb_substr(trim(strip_tags((string)($_POST['pwa_name'] ?? ''))), 0, 24);
        $ovk = trim((string)($_POST['ov_key'] ?? '')); if ($ovk !== '' && preg_match('/^[A-Za-z0-9]{16,64}$/', $ovk)) $settings['ov_key'] = $ovk;
        if (!empty($_POST['ov_key_clear'])) $settings['ov_key'] = '';
        $settings['verkeer_on'] = !empty($_POST['verkeer_on']);
        $settings['verkeer_max2'] = max(1, min(200, intval($_POST['verkeer_max2'] ?? 100)));
        $vu = trim((string)($_POST['verkeer_url'] ?? '')); $settings['verkeer_url'] = preg_match('~^https://[^\s"\'<>]+$~i', $vu) ? $vu : '';

        $settings['lbl_weather_text'] = trim($_POST['lbl_w_val'] ?? 'WEER');
        cmb_keep_style($settings, 'style_lbl_weather', 'lbl_w');
        cmb_keep_style($settings, 'style_weather_value', 'weather_value');
        $settings['weather_city_display'] = trim($_POST['city_val'] ?? 'Steenbergen');
        cmb_keep_style($settings, 'style_weather_city', 'city');
        $settings['lbl_time_text'] = trim($_POST['lbl_t_val'] ?? 'TIJD');
        cmb_keep_style($settings, 'style_lbl_time', 'lbl_t');
        cmb_keep_style($settings, 'style_time_value', 'time_value');
        cmb_keep_style($settings, 'style_date_line', 'date_line');
        $settings['color_val_time'] = $_POST['color_val_time'] ?? '#ffffff';
        $settings['slide_text_scale'] = max(50, min(250, intval($_POST['slide_text_scale'] ?? 100)));
        $settings['slide_text_scale_m'] = max(50, min(250, intval($_POST['slide_text_scale_m'] ?? 100)));
        $settings['slide_lines'] = max(2, min(20, intval($_POST['slide_lines'] ?? 9)));
        $settings['slide_lines_m'] = max(2, min(12, intval($_POST['slide_lines_m'] ?? 4)));

        $settings['color_val_weather'] = $_POST['color_val_weather'] ?? '#00e676';
        $settings['date_line_val'] = trim($_POST['date_line_val'] ?? '');
        $settings['slide_corner_badge'] = trim($_POST['badge_val'] ?? 'CMB');
        cmb_keep_style($settings, 'style_corner_badge', 'badge');
        $settings['text_above_cover'] = trim($_POST['txt_above_val'] ?? '● ON AIR');
        cmb_keep_style($settings, 'style_text_above_cover', 'txt_above');
        $settings['text_behind_cover'] = trim($_POST['txt_behind_val'] ?? 'CMB');
        cmb_keep_style($settings, 'style_text_behind_cover', 'txt_behind');
        $settings['text_behind_sub'] = trim($_POST['txt_behind_sub_val'] ?? 'CHAZZO MUSIC BOX');
        cmb_keep_style($settings, 'style_text_behind_sub', 'txt_behind_sub');
        $settings['station_sub_now'] = trim($_POST['st_subnow_val'] ?? '');
        cmb_keep_style($settings, 'style_station_sub_now', 'st_subnow');
        $settings['track_fallback_title'] = trim($_POST['trk_fb_val'] ?? 'CMB Live Uitzending');
        cmb_keep_style($settings, 'style_track_fallback', 'trk_fb');
        cmb_keep_style($settings, 'style_track_artist', 'trk_artist');
        cmb_keep_style($settings, 'style_track_song', 'trk_song');

        $settings['bottom_left_text'] = trim($_POST['bot_l_val'] ?? 'CHAZZOMUSICBOX');
        cmb_keep_style($settings, 'style_bottom_left', 'bot_l');
        $settings['bottom_right_text'] = trim($_POST['bot_r_val'] ?? '');
        cmb_keep_style($settings, 'style_bottom_right', 'bot_r');
        $settings['water_text'] = trim($_POST['water_st_val'] ?? ($_POST['water_text'] ?? ''));
        $settings['water_anim_mode'] = $_POST['water_anim_mode'] ?? 'bubble';
        $settings['water_letter_spacing'] = max(0, min(20, intval($_POST['water_letter_spacing'] ?? 2)));
        $settings['water_glow'] = max(0, min(30, intval($_POST['water_glow'] ?? 8)));
        // Lettertype, grootte, kleur, verloop en schaduw: zelfde stijlvak als alle andere teksten
        cmb_keep_style($settings, 'style_water', 'water_st');
        if (!empty($settings['style_water'])) {
            $settings['water_font'] = $settings['style_water']['font'];
            $settings['water_font_size'] = max(8, min(96, intval($settings['style_water']['size'])));
            $settings['water_color'] = cmb_clean_color($settings['style_water']['color'], '#63f6ff');
        }
        $settings['water_random_colors'] = !empty($_POST['water_random_colors']);
        $rawWaterColors = preg_split('/[,;\s]+/', (string)($_POST['water_custom_colors'] ?? ''));
        $safeWaterColors = []; foreach ($rawWaterColors as $wc) { if (preg_match('/^#[0-9a-fA-F]{6}$/', trim($wc))) $safeWaterColors[] = strtolower(trim($wc)); }
        $settings['water_custom_colors'] = implode(',', array_slice(array_unique($safeWaterColors), 0, 16)) ?: '#63f6ff,#ff5bc8,#ffd166,#9d6cff';
    } elseif ($curTab === 'media') {
        $settings['bg_studio_global'] = extractBgStudio('bg_global', $uploadDir);
        $settings['bg_studio_player'] = extractBgStudio('bg_player', $uploadDir);
    } elseif ($curTab === 'ticker') {
        $tickerEngine = [];
        for ($i = 1; $i <= 15; $i++) {
            $val = trim($_POST["tk_free_{$i}_val"] ?? '');
            $sec = max(0, min(60, intval($_POST["tk_free_{$i}_sec"] ?? 8)));
            if ($val !== '') {
                $tickerEngine[] = [
                    'type' => 'free',
                    'index' => $i,
                    'val' => $val,
                    'sec' => ($i === 1 && $sec === 0) ? 0 : max(1, $sec),
                    'style' => extractStyleSuite("tk_free_{$i}")
                ];
            }
        }
        $settings['ticker_advanced'] = $tickerEngine;
        $tkMode = (string)($_POST['ticker_animation_mode'] ?? 'fade');
        $settings['ticker_animation_mode'] = in_array($tkMode, ['fade','up','down','left','right','marquee'], true) ? $tkMode : 'fade';
        $settings['ticker_speed'] = max(10, min(600, (int)($_POST['ticker_speed'] ?? 80)));
    } elseif ($curTab === 'site') {
        $cleanUrl = function ($v) {
            $v = trim((string)$v);
            if ($v === '' || preg_match('#^\s*(javascript|data|vbscript):#i', $v)) return '';
            return preg_match('#^(https?://|cmb-uploads/|/)?[A-Za-z0-9_@%:./?=&+~,;-]+$#', $v) ? $v : '';
        };
        $settings['logo_url'] = handleUpload('upload_site_logo', $uploadDir) ?: $cleanUrl($_POST['logo_url'] ?? '');
        $settings['brand_mode'] = (($_POST['brand_mode'] ?? '') === 'media') ? 'media' : 'text';
        $settings['brand_media_url'] = handleUpload('upload_site_brand', $uploadDir) ?: $cleanUrl($_POST['brand_media_url'] ?? '');
        $settings['favicon_url'] = handleUpload('upload_site_favicon', $uploadDir) ?: $cleanUrl($_POST['favicon_url'] ?? '');
        $settings['seo_image_url'] = handleUpload('upload_site_og', $uploadDir) ?: $cleanUrl($_POST['seo_image_url'] ?? '');
        $settings['seo_theme_color'] = cmb_clean_color($_POST['seo_theme_color'] ?? '', '#0c0e1a');
        $settings['seo_title'] = mb_substr(trim(strip_tags((string)($_POST['seo_title'] ?? ''))), 0, 120);
        $settings['seo_description'] = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($_POST['seo_description'] ?? '')))), 0, 320);
        $settings['seo_keywords'] = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags((string)($_POST['seo_keywords'] ?? '')))), 0, 300);
        $siteUrl = trim((string)($_POST['seo_site_url'] ?? ''));
        $settings['seo_site_url'] = preg_match('#^https?://[A-Za-z0-9._~:/?\#@!$&\'()*+,;=%-]+$#', $siteUrl) ? $siteUrl : '';
        $settings['seo_robots'] = (($_POST['seo_robots'] ?? '') === 'noindex') ? 'noindex' : 'index';
    } elseif ($curTab === 'effecten') {
        $settings['art_effect'] = $_POST['art_effect'] ?? 'aurora';
        $settings['art_overflow'] = !empty($_POST['art_overflow']);
        $settings['art_spin'] = in_array($_POST['art_spin'] ?? '', ['cw', 'ccw'], true) ? $_POST['art_spin'] : '';
        $settings['art_rotate_min'] = max(0, min(240, intval($_POST['art_rotate_min'] ?? 0)));
        $settings['vol_shape'] = $_POST['vol_shape'] ?? 'blocks';
        $settings['corner_btn_pos'] = $_POST['corner_btn_pos'] ?? 'top';
        // Verras me: nieuwe achtergrond achter de cover, alleen bewaren als dat is aangevinkt
        $artBg = trim((string)($_POST['art_bg_url'] ?? ''));
        if (!empty($_POST['art_bg_keep']) && preg_match('#^cmb-(uploads|assets/background-covers)/[A-Za-z0-9._-]+$#', $artBg)) {
            $old = is_array($settings['bg_studio_player'] ?? null) ? $settings['bg_studio_player'] : [];
            $settings['bg_studio_player'] = array_merge(['sec' => 8, 'blur' => 0, 'darken' => 30, 'items' => []], $old, ['mode' => 'single', 'single_url' => $artBg]);
        }
    }

    cmb_snapshot($settingsFile); file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); cmb_pulse('design');
    $msg = "✓ Instellingen voor " . strtoupper($curTab) . " succesvol opgeslagen!";
}

// Bepaal actieve tab (via GET of standaard 'verlichting')
$activeTab = $_GET['tab'] ?? ($_POST['active_tab_context'] ?? 'frames');
if ($activeTab === 'verlichting') $activeTab = 'frames';
$allowedTabs = ['frames', 'topmenu', 'identiteit', 'media', 'ticker', 'effecten', 'site'];
if (!in_array($activeTab, $allowedTabs)) $activeTab = 'frames';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMB Beheer</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Dancing+Script:wght@700&family=Great+Vibes&family=Montserrat:wght@400;700;900&family=Orbitron:wght@700&family=Oswald:wght@400;700&family=Pacifico&family=Poppins:wght@600;800&family=Righteous&family=Russo+One&family=Satisfy&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="cmb-assets/cmb-admin.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin.css') : time() ?>">
    <link rel="stylesheet" href="cmb-assets/cmb-effects.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-effects.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-effects.css') : time() ?>">
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #070812; color: #f7f7ff; padding: 20px; margin: 0; }
        .wrap { max-width: 1280px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .top-bar h1 { color: #63f6ff; margin: 0; font-size: 22px; font-family: 'Montserrat', sans-serif; }
        .top-nav-links { display: flex; gap: 8px; align-items: center; }
        .nav-btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; }
        .btn-studio-link { background: linear-gradient(135deg, #ffd166, #ff5bc8); color: #070812; font-weight: 900; }
        .logout-btn { background: #ff1744; color: #fff; }

        .tab-menu { display: flex; gap: 8px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-btn { background: #121427; border: 1px solid rgba(255,255,255,0.1); color: #a8aac8; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 13px; transition: 0.2s; display: inline-block; }
        .tab-btn:hover { background: #1c203d; color: #fff; }
        .tab-btn.active { background: #63f6ff; color: #070812; border-color: #63f6ff; }

        .card { border-radius: 14px; padding: 22px; margin-bottom: 22px; border: 1px solid rgba(255,255,255,0.08); background: #121427; }
        .card h2 { margin-top: 0; margin-bottom: 18px; font-size: 17px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px; font-family: 'Montserrat', sans-serif; color: #63f6ff; }
        
        .style-box { background: rgba(5,6,14,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .style-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        .style-tag { font-size: 11px; color: #ffd166; font-weight: bold; }
        .style-drawer { display: none; margin-top: 10px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 10px; }
        .style-drawer.open { display: block; }

        .border-box { background: rgba(12,14,26,0.8); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .lighting-box { background: rgba(18,20,39,0.9); border: 1px solid rgba(99,246,255,0.25); border-radius: 10px; padding: 14px; margin-bottom: 14px; }
        .bg-studio-box { background: rgba(12,14,26,0.95); border: 1px solid rgba(255,209,102,0.2); border-radius: 12px; padding: 18px; margin-bottom: 18px; }
        .sub-bg-section { background: rgba(5,6,14,0.6); border: 1px solid rgba(255,255,255,0.06); border-radius: 8px; padding: 12px; }

        .carousel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 10px; margin-top: 8px; }
        .carousel-cell { background: rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.08); border-radius: 6px; padding: 6px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .cell-num { font-size: 10px; color: #a8aac8; font-weight: bold; }
        .cell-url-input { width: 100%; font-size: 10px; padding: 4px; border-radius: 4px; border: 1px solid rgba(255,255,255,0.1); background: #0c0e1a; color: #fff; }
        .cell-file-input { width: 100%; font-size: 9px; }

        .form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .field { flex: 1; min-width: 180px; display: flex; flex-direction: column; }
        label { font-size: 11px; text-transform: uppercase; color: #a8aac8; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea { background: #05060d !important; border: 1px solid rgba(255,255,255,0.15) !important; color: #fff !important; padding: 8px; border-radius: 6px; font-size: 13px; font-family: inherit; }
        textarea { resize: vertical; min-height: 48px; }
        input[type="color"] { padding: 1px; height: 35px; cursor: pointer; }

        .btn-save { background: linear-gradient(135deg, #00e676, #00b0ff); border: none; color: #070812; padding: 18px 36px; border-radius: 12px; font-weight: 900; font-size: 16px; cursor: pointer; width: 100%; margin-top: 20px; box-shadow: 0 4px 20px rgba(0,230,118,0.3); }
        .msg { background: rgba(0,230,118,0.15); border: 1px solid #00e676; color: #00e676; padding: 14px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; }
    </style>
</head>
<body>
<div class="wrap">
    <?= cmb_nav($activeTab, 'cmb-admin.php?logout=1') ?>
    <?php if ($themeEdit !== null): ?>
    <div style="background:linear-gradient(135deg,#ff7518,#b388ff); color:#070812; font-weight:800; border-radius:12px; padding:12px 16px; margin:-8px 0 18px; display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
        <span>🎨 Je bewerkt het thema "<?= htmlspecialchars($themeEdit) ?>". Opslaan gaat naar dit thema, je originele opmaak blijft ongemoeid.</span>
        <span><a href="index.php?theme_preview=<?= urlencode('own:' . $_SESSION['cmb_theme_edit']) ?>" target="_blank" style="color:#070812;">👁 Bekijk</a> · <a href="cmb-themes.php?stop_edit=1" style="color:#070812;">✓ Klaar, terug naar thema's</a></span>
    </div>
    <?php endif; ?>

    <?php if (!empty($msg)): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="adminForm" accept-charset="UTF-8">
        <input type="hidden" name="active_tab_context" value="<?= htmlspecialchars($activeTab) ?>">

<?php if ($activeTab === 'frames'): ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-rightframes.php'; ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-lighting.php'; ?>

<?php elseif ($activeTab === 'topmenu'): ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-topmenu.php'; ?>

<?php elseif ($activeTab === 'identiteit'): ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-identity.php'; ?>
<?php elseif ($activeTab === 'media'): ?>
    <div class="card">
        <h2>Achtergronden: Website & Spelerframe (Enkel of Carrousel tot 12)</h2>
        <?= renderBgStudioSuite('bg_global', 'Website Achtergrond (Volledige Pagina)', $settings['bg_studio_global'] ?? []) ?>
        <?= renderBgStudioSuite('bg_player', 'Spelerframe Achtergrond (Rechter Kiosk Frame)', $settings['bg_studio_player'] ?? []) ?>
    </div>

<?php elseif ($activeTab === 'ticker'): ?>
    <div class="card">
        <h2>Ticker Instellingen & 15 Vrije Regels</h2>
        <div class="form-row">
            <div class="field">
                <label>Animatiemodus</label>
                <select name="ticker_animation_mode">
                    <?php $tkm = $settings['ticker_animation_mode'] ?? 'fade'; foreach (['fade'=>'Vervagend vervangen (op zijn plek)','up'=>'Omhoog wegfaden, nieuwe vervangt','down'=>'Omlaag wegfaden, nieuwe vervangt','left'=>'Links wegfaden, nieuwe vervangt','right'=>'Rechts wegfaden, nieuwe vervangt','marquee'=>'Lopende band op 1 lijn (rechts naar links)'] as $k=>$l): ?>
                    <option value="<?= $k ?>" <?= $tkm===$k?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Snelheid lopende band (richtsnelheid, pixels per seconde; 80 is rustig)</label>
                <input type="number" name="ticker_speed" min="10" max="600" value="<?= (int)($settings['ticker_speed'] ?? 80) ?>">
            </div>
        </div>
        <?php 
        $advMap = [];
        if (!empty($settings['ticker_advanced'])) {
            foreach ($settings['ticker_advanced'] as $tk) {
                if ($tk['type'] === 'free') $advMap[$tk['index']] = $tk;
            }
        }
        for ($ti = 1; $ti <= 15; $ti++): 
            $tData = $advMap[$ti] ?? ['val' => '', 'sec' => ($ti === 1 ? 8 : 6), 'style' => []];
        ?>
        <div style="display:flex; gap:10px; align-items:flex-start;">
            <div style="flex:1;"><?= renderStyleSuite("tk_free_{$ti}", "Vrije Ticker Regel #{$ti}", $tData['val'], $tData['style']) ?></div>
            <div style="width:110px; margin-top:28px;"><label>Seconden (0-60)</label><input type="number" name="tk_free_<?= $ti ?>_sec" value="<?= $tData['sec'] ?>" min="0" max="60"></div>
        </div>
        <?php endfor; ?>
    </div>

<?php elseif ($activeTab === 'effecten'): ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-effects.php'; ?>

<?php elseif ($activeTab === 'site'): ?>
<?php include CMB_ROOT . '/cmb-admin-parts/cmb-admin-site.php'; ?>

<?php endif; ?>

        <button type="submit" name="save_admin" class="btn-save">💾 DEZE INSTELLINGEN OPSLAAN</button>
    </form>
</div>

<script>
function toggleStyleDrawer(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('open');
}
function cmbMarkFrameTiles(key) {
    var sel = document.getElementById(key + '_light_mode');
    var grid = document.querySelector('.cmb-frame-tile-grid[data-key="' + key + '"]');
    if (!sel || !grid) return;
    grid.querySelectorAll('.cmb-frame-tile').forEach(function (b) {
        b.classList.toggle('is-selected', b.getAttribute('data-mode') === sel.value);
    });
}
function cmbPickFrameMode(key, mode) {
    var sel = document.getElementById(key + '_light_mode');
    if (!sel) return;
    sel.value = mode;
    cmbMarkFrameTiles(key);
}
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('select[id$="_light_mode"]').forEach(function (sel) {
        var key = sel.id.replace(/_light_mode$/, '');
        sel.addEventListener('change', function () { cmbMarkFrameTiles(key); });
    });
});
</script>
<script>
// Voor 🎲 Verras me bij achtergronden: je eigen uploads (nieuwste eerst)
window.CMB_UPLOAD_MEDIA = <?php
    $cmbUp = [];
    foreach ((glob(cmb_upload_dir() . '*.{jpg,jpeg,png,gif,webp,mp4,webm}', GLOB_BRACE) ?: []) as $uf) $cmbUp[$uf] = @filemtime($uf);
    arsort($cmbUp);
    echo json_encode(array_map(function ($f) { return 'cmb-uploads/' . basename($f); }, array_slice(array_keys($cmbUp), 0, 60)), JSON_UNESCAPED_SLASHES);
?>;
</script>
<script src="cmb-assets/cmb-admin-ui.js?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') : time() ?>"></script>
<script src="cmb-assets/cmb-filepick.js?v=2"></script>
</body>
</html>
