<?php
/** CMB Studio: data laden, presets en opslaan. Geladen door cmb-studio.php (na inloggen). */
if (!defined('CMB_PART')) { http_response_code(403); exit; }

$dataDir = cmb_data_dir();
$uploadDir = cmb_upload_dir();
if (!is_dir($dataDir)) @mkdir($dataDir, 0755, true);
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

$slidesFile = $dataDir . 'cmb-slides.json';
$settingsFile = $dataDir . 'cmb-settings.json';
$presetsFile = $dataDir . 'stream-presets.json';
// Verse installatie: zet de meegeleverde zenderlijst (hoofdmap) eenmalig in cmb-data, zodat "Mijn zenders" gevuld is
if (!is_file($presetsFile) && is_file(CMB_ROOT . '/stream-presets.json')) @copy(CMB_ROOT . '/stream-presets.json', $presetsFile);

$slides = file_exists($slidesFile) ? json_decode(file_get_contents($slidesFile), true) ?: [] : [];
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];
$presets = file_exists($presetsFile) ? json_decode(file_get_contents($presetsFile), true) ?: [] : [];
$msg = '';
if (isset($_GET['ajax_save_preset'])) {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $pData = json_decode($raw, true);
    if ($pData && !empty($pData['name'])) {
        $pName = trim($pData['name']);
        $presets[$pName] = [
            'streamUrl' => $pData['streamUrl'] ?? '',
            'fallbackUrl' => $pData['fallbackUrl'] ?? '',
            'apiBackupUrl' => $pData['apiBackupUrl'] ?? '',
            'jsSrc' => $pData['jsSrc'] ?? '',
            'jsPort' => $pData['jsPort'] ?? '',
            'statsJsonUrl' => $pData['statsJsonUrl'] ?? '',
            'coverApi' => $pData['coverApi'] ?? 'itunes'
        ];
        file_put_contents($presetsFile, json_encode($presets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo json_encode(['status' => 'ok']);
        exit;
    }
    echo json_encode(['status' => 'error']);
    exit;
}


$fonts = cmb_font_options();

function handleUpload($fileInputName, $uploadDir) {
    // Alleen afbeeldingen en video's; scripts worden geweigerd (zie cmb-lib.php).
    return cmb_upload($fileInputName, $uploadDir);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_studio'])) {
    $curTab = $_POST['active_tab_context'] ?? 'slides';
    $clipWarnW = [];

    if ($curTab === 'slides') {
        $uEmer = handleUpload('upload_slide_emergency', $uploadDir);
        $slides['emergency'] = [
            'active' => !empty($_POST['emer_active']),
            'title_big' => trim($_POST['emer_tbig_val'] ?? ''),
            'style_title_big' => extractStyleSuite('emer_tbig'),
            'title_sub' => trim($_POST['emer_tsub_val'] ?? ''),
            'style_title_sub' => extractStyleSuite('emer_tsub'),
            'body' => trim($_POST['emer_body_val'] ?? ''),
            'style_body' => extractStyleSuite('emer_body'),
            'media_url' => $uEmer ?: trim($_POST['emer_media_url'] ?? ''),
            'blur' => max(0, min(25, intval($_POST['emer_blur'] ?? 0))),
            'darken' => max(0, min(95, intval($_POST['emer_darken'] ?? 40)))
        ];

        for ($ni = 1; $ni <= 5; $ni++) {
            $uSlideN = handleUpload("upload_slide_n_{$ni}", $uploadDir);
            $slides['news'][$ni] = [
                'active' => !empty($_POST["sn_{$ni}_active"]),
                'order' => intval($_POST["sn_{$ni}_order"] ?? $ni),
                'sec' => max(2, intval($_POST["sn_{$ni}_sec"] ?? 10)),
                'feed_url' => trim($_POST["sn_{$ni}_feed"] ?? ''),
                'koptitel' => trim($_POST["sn_{$ni}_title_val"] ?? 'NIEUWS'),
                'style_koptitel' => extractStyleSuite("sn_{$ni}_title"),
                'style_body' => extractStyleSuite("sn_{$ni}_body"),
                'media_url' => $uSlideN ?: trim($_POST["sn_{$ni}_media_url"] ?? ''),
                'blur' => max(0, min(25, intval($_POST["sn_{$ni}_blur"] ?? 0))),
                'darken' => max(0, min(95, intval($_POST["sn_{$ni}_darken"] ?? 30)))
            ];
        }

        for ($wi = 1; $wi <= 5; $wi++) {
            $uSlideW = handleUpload("upload_slide_w_{$wi}", $uploadDir);
            $slides['weather'][$wi] = [
                'active' => !empty($_POST["sw_{$wi}_active"]),
                'order' => intval($_POST["sw_{$wi}_order"] ?? ($wi + 5)),
                'sec' => max(2, intval($_POST["sw_{$wi}_sec"] ?? 8)),
                'city' => trim($_POST["sw_{$wi}_city"] ?? 'Steenbergen'),
                'lat' => trim($_POST["sw_{$wi}_lat"] ?? '51.58'),
                'lon' => trim($_POST["sw_{$wi}_lon"] ?? '4.32'),
                'koptitel' => trim($_POST["sw_{$wi}_title_val"] ?? 'VERWACHTING'),
                'praatje' => trim($_POST["sw_{$wi}_praatje"] ?? ''),
                'ticker_active' => !empty($_POST["sw_{$wi}_ticker_active"]),
                'style_koptitel' => extractStyleSuite("sw_{$wi}_title"),
                'style_body' => extractStyleSuite("sw_{$wi}_body"),
                'media_url' => $uSlideW ?: trim($_POST["sw_{$wi}_media_url"] ?? ''),
                'blur' => max(0, min(25, intval($_POST["sw_{$wi}_blur"] ?? 0))),
                'darken' => max(0, min(95, intval($_POST["sw_{$wi}_darken"] ?? 20)))
            ];
        }

        for ($fi = 1; $fi <= 15; $fi++) {
            $uSlideF = handleUpload("upload_slide_f_{$fi}", $uploadDir);
            $schedHours = [];
            for ($h = 0; $h <= 23; $h++) {
                if (!empty($_POST["sf_{$fi}_h_{$h}"])) $schedHours[] = $h;
            }
            $schedDays = [];
            foreach (['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'] as $dk) {
                if (!empty($_POST["sf_{$fi}_day_{$dk}"])) $schedDays[] = $dk;
            }
            // Minuten (+ seconden per minuut, voor precies op tijd starten)
            $schedMins = []; $schedSecs = [];
            for ($mi = 1; $mi <= 4; $mi++) {
                $mv = trim($_POST["sf_{$fi}_m{$mi}"] ?? '');
                if ($mv === '' || !is_numeric($mv)) continue;
                $schedMins[] = (string)max(0, min(59, intval($mv)));
                $schedSecs[] = max(0, min(59, intval($_POST["sf_{$fi}_s{$mi}"] ?? 0)));
            }

            $slides['custom'][$fi] = [
                'active' => !empty($_POST["sf_{$fi}_active"]),
                'order' => intval($_POST["sf_{$fi}_order"] ?? ($fi + 10)),
                'sec' => max(2, intval($_POST["sf_{$fi}_sec"] ?? 8)),
                'title_big' => trim($_POST["sf_{$fi}_tbig_val"] ?? ''),
                'style_title_big' => extractStyleSuite("sf_{$fi}_tbig"),
                'title_sub' => trim($_POST["sf_{$fi}_tsub_val"] ?? ''),
                'style_title_sub' => extractStyleSuite("sf_{$fi}_tsub"),
                'foot' => mb_substr(trim($_POST["sf_{$fi}_foot"] ?? ''), 0, 80),
                'body' => trim($_POST["sf_{$fi}_body_val"] ?? ''),
                'style_body' => extractStyleSuite("sf_{$fi}_body"),
                'media_url' => $uSlideF ?: trim($_POST["sf_{$fi}_media_url"] ?? ''),
                'background_preset' => preg_match('/^cover-(?:0[1-9]|[1-4][0-9]|50)$/', $_POST["sf_{$fi}_background_preset"] ?? '') ? $_POST["sf_{$fi}_background_preset"] : '',
                'blur' => max(0, min(25, intval($_POST["sf_{$fi}_blur"] ?? 0))),
                'darken' => max(0, min(95, intval($_POST["sf_{$fi}_darken"] ?? 30))),
                'track_match' => trim($_POST["sf_{$fi}_track_match"] ?? ''),
                'track_only' => !empty($_POST["sf_{$fi}_track_only"]),
                'sched_days' => $schedDays,
                'sched_hours' => $schedHours,
                'sched_mins' => array_values($schedMins),
                'sched_secs' => $schedSecs,
                'precise' => !empty($_POST["sf_{$fi}_precise"]),
                // Tijdelijke slide: alleen zichtbaar tussen deze momenten (leeg = altijd)
                'show_from' => cmb_clean_dt($_POST["sf_{$fi}_from"] ?? ''),
                'show_until' => cmb_clean_dt($_POST["sf_{$fi}_until"] ?? '')
            ];
        }
        // Stream-vertraging voor op tijd geplande slides (seconden, mag met decimaal)
        $slides['timing'] = ['delay' => max(0, min(120, round(floatval(str_replace(',', '.', $_POST['slides_stream_delay'] ?? 0)), 1)))];
        // Clipslides (max. 15): een clip die het scherm overneemt bij een bepaald nummer
        $oldClipSlides = (isset($slides['clipslides']) && is_array($slides['clipslides'])) ? array_values($slides['clipslides']) : [];
        $newClipSlides = [];
        $clipWarn = [];
        for ($ci = 1; $ci <= 15; $ci++) {
            $csErr = null;
            $uCs = cmb_upload("upload_cs_{$ci}", $uploadDir, 15 * 1024 * 1024, $csErr, "clip{$ci}_");
            if ($csErr) $clipWarn[] = "Clip {$ci}: " . $csErr;
            $csUrl = $uCs ?: trim($_POST["cs_{$ci}_url"] ?? '');
            $csMatch = trim($_POST["cs_{$ci}_match"] ?? '');
            if ($csUrl === '' && $csMatch === '') continue;
            if ($csUrl !== '' && cmb_clip_info($csUrl)['kind'] === 'page') {
                $clipWarn[] = "Clip {$ci}: dit is een link naar een webpagina, niet naar het videobestand. Veel sites (zoals Pixabay) laten zich niet in een venster tonen, dan blijft het scherm leeg. Download de video en upload hem hier, of gebruik een directe link die op .mp4 eindigt.";
            }
            if (cmb_clip_info($csUrl)['kind'] === 'invalid' && $csUrl !== '') {
                $clipWarn[] = "Clip {$ci}: de link wordt niet herkend (gebruik https://… of een geüpload bestand).";
                $csUrl = '';
            }
            $newClipSlides[] = [
                'active' => !empty($_POST["cs_{$ci}_active"]),
                'match' => $csMatch,
                'url' => $csUrl,
                'delay' => max(0, min(60, floatval($_POST["cs_{$ci}_delay"] ?? 0))),
                'show_title' => !empty($_POST["cs_{$ci}_show_title"]),
                'sound' => !empty($_POST["cs_{$ci}_sound"]),
                'fit' => (($_POST["cs_{$ci}_fit"] ?? 'contain') === 'cover') ? 'cover' : 'contain',
                'scope' => (($_POST["cs_{$ci}_scope"] ?? 'frame') === 'screen') ? 'screen' : 'frame',
            ];
        }
        $slides['clipslides'] = $newClipSlides;
        unset($slides['clips']);

        cmb_snapshot($slidesFile); file_put_contents($slidesFile, json_encode($slides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); cmb_pulse('slides');
        $msg = "✓ Alle slides succesvol opgeslagen!" . ($clipWarn ? ' ⚠ ' . implode(' ', $clipWarn) : '');
    } elseif ($curTab === 'widgets') {
        $newWidgets = [];
        for ($wgi = 1; $wgi <= 6; $wgi++) {
            $kind = $_POST["wg_{$wgi}_kind"] ?? 'rss';
            if (!in_array($kind, ['rss', 'embed', 'html'], true)) $kind = 'rss';
            $wgUrl = trim($_POST["wg_{$wgi}_url"] ?? '');
            $wgHtml = (string)($_POST["wg_{$wgi}_html"] ?? '');
            $wgTitle = trim($_POST["wg_{$wgi}_title_val"] ?? '');
            if ($wgUrl === '' && trim($wgHtml) === '' && $wgTitle === '' && trim($_POST["wg_{$wgi}_media_url"] ?? '') === '') continue;
            if ($wgUrl !== '' && !preg_match('~^https?://~i', $wgUrl)) { $clipWarnW[] = "Widget {$wgi}: de link moet met https:// beginnen."; $wgUrl = ''; }
            if ($kind === 'embed' && $wgUrl !== '' && !preg_match('~^https://~i', $wgUrl)) { $clipWarnW[] = "Widget {$wgi}: een embed vraagt een https-link."; $wgUrl = ''; }
            $newWidgets[] = [
                'active' => !empty($_POST["wg_{$wgi}_active"]),
                'kind' => $kind,
                'title' => $wgTitle,
                'style_title' => extractStyleSuite("wg_{$wgi}_title"),
                'sub' => trim($_POST["wg_{$wgi}_sub"] ?? ''),
                'url' => $wgUrl,
                'html' => $wgHtml,
                'max' => max(1, min(8, intval($_POST["wg_{$wgi}_max"] ?? 4))),
                'show_desc' => !empty($_POST["wg_{$wgi}_show_desc"]),
                'sec' => max(3, min(120, intval($_POST["wg_{$wgi}_sec"] ?? 12))),
                'order' => intval($_POST["wg_{$wgi}_order"] ?? (20 + $wgi)),
                'media_url' => handleUpload("upload_wg_{$wgi}", $uploadDir) ?: trim($_POST["wg_{$wgi}_media_url"] ?? ''),
                'blur' => max(0, min(25, intval($_POST["wg_{$wgi}_blur"] ?? 0))),
                'darken' => max(0, min(95, intval($_POST["wg_{$wgi}_darken"] ?? 35))),
            ];
        }
        $slides['widgets'] = $newWidgets;
        cmb_snapshot($slidesFile); file_put_contents($slidesFile, json_encode($slides, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); cmb_pulse('slides');
        $msg = "✓ Widgets opgeslagen!" . (!empty($clipWarnW) ? ' ⚠ ' . implode(' ', $clipWarnW) : '');
    } elseif ($curTab === 'streams') {
        $settings['streamUrl'] = trim($_POST['streamUrl'] ?? '');
        $settings['fallbackUrl'] = trim($_POST['fallbackUrl'] ?? '');
        $settings['statsJsonUrl'] = trim($_POST['statsJsonUrl'] ?? '');
        $settings['apiBackupUrl'] = trim($_POST['apiBackupUrl'] ?? '');
        $settings['jsSrc'] = trim($_POST['jsSrc'] ?? '');
        $settings['jsPort'] = trim($_POST['jsPort'] ?? '');
        $settings['coverApi'] = in_array($_POST['coverApi'] ?? '', ['itunes', 'deezer', 'lastfm'], true) ? $_POST['coverApi'] : 'itunes';
        $settings['cover_search_provider'] = $settings['coverApi'];
        $settings['cover_fallback_centova'] = !empty($_POST['cover_fallback_centova']);
        foreach (['streamUrlAlt', 'fallbackUrlAlt'] as $k2) {
            $v2 = trim($_POST[$k2] ?? '');
            $settings[$k2] = preg_match('#^https?://#i', $v2) ? $v2 : '';
        }
        foreach (['stream1_name','stream2_name','statsJsonUrl2','apiBackupUrl2','jsSrc2','jsPort2'] as $k2) $settings[$k2] = trim($_POST[$k2] ?? '');
        $settings['coverApi2'] = in_array($_POST['coverApi2'] ?? '', ['itunes', 'deezer', 'lastfm'], true) ? $_POST['coverApi2'] : '';
        $settings['stream_recheck_sec'] = max(20, min(600, intval($_POST['stream_recheck_sec'] ?? 60)));
        $am = trim($_POST['alert_mail'] ?? '');
        $settings['alert_mail'] = preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $am) ? $am : '';
        if ($am !== '' && $settings['alert_mail'] === '') $clipWarnW[] = 'Het mailadres voor de uitvalmelding is niet geldig en is niet bewaard.';
        if (!empty($_POST['alert_test']) && $settings['alert_mail'] !== '') {
            $clipWarnW[] = cmb_streamwatch_test($settings) ? 'Testmail is verstuurd naar ' . $settings['alert_mail'] . ' (kijk ook in de spam).' : 'De testmail kon niet verstuurd worden (de host weigert mail).';
        }
        $lfm = trim($_POST['lastfm_key'] ?? '');
        $settings['lastfm_key'] = preg_match('/^[a-fA-F0-9]{32}$/', $lfm) ? $lfm : '';
        if ($lfm !== '' && $settings['lastfm_key'] === '') $clipWarnW[] = 'De Last.fm-sleutel moet uit 32 letters/cijfers (0-9, a-f) bestaan en is niet bewaard.';
        // De uitklap voor luisteraars is vervallen: stream 2 neemt automatisch over
        $settings['stream_flyout_enabled'] = false;
        // Verborgen zenders in "Mijn zenders" (niet verwijderd, alleen uit het zicht)
        $hidden = [];
        foreach ($_POST as $pk => $pv) { if (strpos($pk, 'hp_') === 0 && is_string($pv) && $pv !== '') $hidden[] = $pv; }
        $settings['hidden_presets'] = array_values(array_unique($hidden));
        $msg = "✓ Streams opgeslagen!" . ($clipWarnW ? ' ⚠ ' . implode(' ', $clipWarnW) : '');
cmb_snapshot($settingsFile); file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); cmb_pulse('design');
    }
}

$activeTab = $_GET['tab'] ?? ($_POST['active_tab_context'] ?? 'slides');
if ($activeTab === 'clips' || $activeTab === 'clipslides') $activeTab = 'slides';
$allowedTabs = ['slides', 'widgets', 'streams'];
if (!in_array($activeTab, $allowedTabs)) $activeTab = 'slides';
