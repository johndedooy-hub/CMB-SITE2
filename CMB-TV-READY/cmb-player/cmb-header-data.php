<?php
/** CMB speler: ticker, slides, clipslides en live-sync endpoint. Geladen door header.php. */
if (!defined('CMB_PART')) { http_response_code(403); exit; }
// Ticker Pool
$tickerAnimMode = $settings['ticker_animation_mode'] ?? 'fade';
$tickerPool = [];
if (!empty($settings['ticker_advanced']) && is_array($settings['ticker_advanced'])) {
    foreach ($settings['ticker_advanced'] as $item) {
        if (!empty($item['val'])) {
            $tickerPool[] = [
                'val' => $item['val'],
                'sec' => isset($item['sec']) ? intval($item['sec']) : 8,
                'style' => $item['style'] ?? []
            ];
        }
    }
}
if (empty($tickerPool)) {
    $rawTicker = $settings['ticker_text'] ?? "CHAZZO MUSIC BOX • 24/7 DANCE & HITS • STEENBERGEN LIVE || NON-STOP DE BESTE VISUAL RETRO CLASSICS";
    foreach (array_map('trim', explode('||', $rawTicker)) as $ri) {
        if ($ri !== '') $tickerPool[] = ['val' => $ri, 'sec' => 8, 'style' => []];
    }
}


// Optioneel: Voeg weerpraatje toe aan de bestaande ticker zonder conflict
if (!empty($slides['weather']) && is_array($slides['weather'])) {
    foreach ($slides['weather'] as $sw) {
        if (!empty($sw['active']) && !empty($sw['ticker_active']) && !empty($sw['praatje'])) {
            $cityName = !empty($sw['city']) ? htmlspecialchars($sw['city'], ENT_QUOTES, 'UTF-8') : 'WEER';
            $tickerPool[] = [
                'val' => '⛅ ' . strtoupper($cityName) . ': ' . htmlspecialchars($sw['praatje'], ENT_QUOTES, 'UTF-8'),
                'sec' => max(6, intval($sw['sec'] ?? 8)),
                'style' => [] // leeg = volgt de opmaak van ticker regel 1
            ];
        }
    }
}

// Slide Pool
$activeSlides = [];
$timedSlidesJs = []; // vrije slides die precies op tijd starten (zie hieronder)
if (!empty($slides['emergency']['active'])) {
    $em = $slides['emergency'];
    $activeSlides[] = [
        'type' => 'emergency',
        'title' => $em['title_big'] ?: 'NOODBERICHT',
        'sub' => $em['title_sub'] ?: '',
        'body' => $em['body'] ?: '',
        'style_title' => $em['style_title_big'] ?? ['size' => '24px', 'color' => '#ff1744'],
        'style_sub' => $em['style_title_sub'] ?? ['size' => '14px', 'color' => '#ffd166'],
        'style_body' => $em['style_body'] ?? ['size' => '13px', 'color' => '#ffffff'],
        'media' => $em['media_url'] ?? '',
        'blur' => intval($em['blur'] ?? 0),
        'darken' => intval($em['darken'] ?? 40),
        'sec' => 9999
    ];
} else {
    // 1. Nieuws Slides
    if (!empty($slides['news']) && is_array($slides['news'])) {
        foreach ($slides['news'] as $idx => $sn) {
            if (!empty($sn['active']) && !empty($sn['feed_url'])) {
                $fUrl = trim($sn['feed_url']);
                if ($fUrl === 'https://nu.nl/rss' || $fUrl === 'http://nu.nl/rss') {
                    $fUrl = 'https://www.nu.nl/rss/Algemeen';
                }
                $rssData = getRssFeedItem($fUrl, $cacheDir);
                $finalMedia = !empty($sn['media_url']) ? $sn['media_url'] : ($rssData['img'] ?? '');

                $activeSlides[] = [
                    'type' => 'news',
                    'order' => intval($sn['order'] ?? $idx),
                    'title' => $sn['koptitel'] ?: 'NIEUWS',
                    'sub' => $rssData['title'] ?? 'Laatste nieuwsbulletin',
                    'body' => $rssData['desc'] ?? '',
                    'style_title' => $sn['style_koptitel'] ?? ['size' => '18px', 'color' => '#ffd166'],
                    'style_sub' => ['size' => '14px', 'color' => '#ffffff'],
                    'style_body' => ['size' => '12px', 'color' => '#f7f7ff'],
                    'media' => $finalMedia,
                    'blur' => intval($sn['blur'] ?? 0),
                    'darken' => intval($sn['darken'] ?? 30),
                    'sec' => max(2, intval($sn['sec'] ?: 10))
                ];
            }
        }
    }

    // 2. Weer Slides
    if (!empty($slides['weather']) && is_array($slides['weather'])) {
        foreach ($slides['weather'] as $widx => $sw) {
            if (!empty($sw['active'])) {
                $activeSlides[] = [
                    'type' => 'weather',
                    'order' => intval($sw['order'] ?? ($widx + 5)),
                    'lat' => trim($sw['lat'] ?? '51.58'),
                    'lon' => trim($sw['lon'] ?? '4.32'),
                    'city' => $sw['city'] ?: 'Steenbergen',
                    'title' => $sw['koptitel'] ?: 'VERWACHTING',
                    'sub' => ($sw['city'] ?: 'Steenbergen') . ' • Actueel',
                    'body' => trim($sw['praatje'] ?? ''),
                    'style_title' => $sw['style_koptitel'] ?? ['size' => '18px', 'color' => '#63f6ff'],
                    'style_sub' => ['size' => '14px', 'color' => '#ffd166'],
                    'style_body' => ['size' => '12px', 'color' => '#a8aac8'],
                    'media' => $sw['media_url'] ?? '',
                    'blur' => intval($sw['blur'] ?? 0),
                    'darken' => intval($sw['darken'] ?? 20),
                    'sec' => max(2, intval($sw['sec'] ?: 8))
                ];
            }
        }
    }

    // 3. Vrije Slides
    $timedSlidesJs = [];
    $curDay = strtolower(date('D'));
    $dayMap = ['mon'=>'ma','tue'=>'di','wed'=>'wo','thu'=>'do','fri'=>'vr','sat'=>'za','sun'=>'zo'];
    $curDk = $dayMap[$curDay] ?? 'ma';
    $curH = intval(date('G'));
    $curM = intval(date('i'));

    $cList = $slides['custom'] ?? ($slides['vrij'] ?? []);
    if (!empty($cList) && is_array($cList)) {
        foreach ($cList as $cidx => $sf) {
            if (!is_array($sf) || !cmb_slide_in_window($sf)) continue; // tijdelijke slide buiten zijn periode
            if (!empty($sf['active']) && !empty($sf['precise']) && !empty($sf['sched_mins'])) {
                // Precies op tijd: de speler start deze slide zelf op de seconde (los van de rotatie)
                $tTimes = [];
                foreach (array_values($sf['sched_mins']) as $ti => $tm) {
                    if ($tm === '' || !is_numeric($tm)) continue;
                    $tTimes[] = max(0, min(59, intval($tm))) * 60 + max(0, min(59, intval($sf['sched_secs'][$ti] ?? 0)));
                }
                if ($tTimes) $timedSlidesJs[] = [
                    'id' => 'f' . $cidx,
                    'days' => array_values($sf['sched_days'] ?? []),
                    'hours' => array_map('intval', array_values($sf['sched_hours'] ?? [])),
                    'times' => $tTimes,
                    'sec' => max(2, intval($sf['sec'] ?: 8)),
                    'slide' => [
                        'type' => 'custom', 'timed' => true,
                        'title' => $sf['title_big'] ?: '', 'sub' => $sf['title_sub'] ?: '', 'body' => $sf['body'] ?: '',
                        'style_title' => $sf['style_title_big'] ?? ['size' => '22px', 'color' => '#63f6ff'],
                        'style_sub' => $sf['style_title_sub'] ?? ['size' => '14px', 'color' => '#ffd166'],
                        'style_body' => $sf['style_body'] ?? ['size' => '13px', 'color' => '#ffffff'],
                        'media' => !empty($sf['media_url']) ? $sf['media_url'] : (!empty($sf['background_preset']) ? 'cmb-assets/background-covers/' . basename($sf['background_preset']) . '.svg' : ''), 'blur' => intval($sf['blur'] ?? 0), 'darken' => intval($sf['darken'] ?? 30),
                        'sec' => max(2, intval($sf['sec'] ?: 8)),
                    ],
                ];
                continue;
            }
            if (!empty($sf['active'])) {
                $schedDays = $sf['sched_days'] ?? [];
                if (!empty($schedDays) && !in_array($curDk, $schedDays)) continue;

                $schedHours = $sf['sched_hours'] ?? [];
                if (!empty($schedHours) && !in_array($curH, $schedHours)) continue;

                $schedMins = $sf['sched_mins'] ?? [];
                if (!empty($schedMins)) {
                    $matchedMin = false;
                    foreach ($schedMins as $sm) {
                        if ($sm !== '' && abs($curM - intval($sm)) <= 2) {
                            $matchedMin = true;
                            break;
                        }
                    }
                    if (!$matchedMin) continue;
                }

                $ovMins = array_filter((array)($sf['sched_mins'] ?? []), function ($v) { return $v !== ''; });
                $activeSlides[] = [
                    'type' => 'custom',
                    'ov' => ((count(array_unique((array)($sf['sched_days'] ?? []))) >= 7 || empty($sf['sched_days'])) && (count(array_unique((array)($sf['sched_hours'] ?? []))) >= 24 || empty($sf['sched_hours'])) && !$ovMins && empty($sf['precise']) && trim((string)($sf['track_match'] ?? '')) === '' && empty($sf['track_only']) && empty($sf['show_from']) && empty($sf['show_until'])) ? 1 : 0, // 1 = hoort in het info-overzicht
                    'order' => intval($sf['order'] ?? ($cidx + 10)),
                    'title' => $sf['title_big'] ?: '',
                    'sub' => $sf['title_sub'] ?: '',
                    'foot' => trim((string)($sf['foot'] ?? '')),
                    'body' => $sf['body'] ?: '',
                    'style_title' => $sf['style_title_big'] ?? ['size' => '22px', 'color' => '#63f6ff'],
                    'style_sub' => $sf['style_title_sub'] ?? ['size' => '14px', 'color' => '#ffd166'],
                    'style_body' => $sf['style_body'] ?? ['size' => '13px', 'color' => '#ffffff'],
                    'media' => !empty($sf['media_url']) ? $sf['media_url'] : (!empty($sf['background_preset']) ? 'cmb-assets/background-covers/' . basename($sf['background_preset']) . '.svg' : ''),
                    'blur' => intval($sf['blur'] ?? 0),
                    'darken' => intval($sf['darken'] ?? 30),
                    'track_match' => trim($sf['track_match'] ?? ''),
                    'track_only' => !empty($sf['track_only']),
                    'sec' => max(2, intval($sf['sec'] ?: 8))
                ];
            }
        }
    }

    // Widgets (RSS / embed / eigen code)
    if (!empty($slides['widgets']) && is_array($slides['widgets'])) {
        foreach (array_slice($slides['widgets'], 0, 6) as $wi => $wg) {
            if (!is_array($wg) || empty($wg['active'])) continue;
            $wHtml = cmb_widget_html($wg, rtrim(cmb_cache_dir(), '/'));
            if ($wHtml === '') continue;
            $activeSlides[] = [
                'type' => 'widget',
                'order' => intval($wg['order'] ?? (20 + $wi)),
                'title' => $wg['title'] ?? '',
                'sub' => $wg['sub'] ?? '',
                'body' => '<div class="cmb-widget-wrap-outer">' . $wHtml . '</div>',
                'style_title' => $wg['style_title'] ?? ['size' => '22px', 'color' => '#63f6ff'],
                'style_sub' => ['size' => '14px', 'color' => '#ffd166'],
                'style_body' => ['size' => '13px', 'color' => '#ffffff'],
                'media' => $wg['media_url'] ?? '', 'blur' => intval($wg['blur'] ?? 0), 'darken' => intval($wg['darken'] ?? 35),
                'sec' => max(3, intval($wg['sec'] ?? 12))
            ];
        }
    }

    // Regioagenda-slides (beheer > Regioagenda); een fout hier mag de speler nooit raken
    try {
        if (is_file(CMB_ROOT . '/cmb-core/cmb-regio.php')) {
            require_once CMB_ROOT . '/cmb-core/cmb-regio.php';
            $rgData = cmb_regio_load();
            $rgSlides = cmb_regio_slides($rgData);
            foreach ($rgSlides as $rs) { $rs['cat'] = 'regio'; $activeSlides[] = $rs; }
            if ($rgSlides) $slides['_regio_fonts'] = [$rgData['style_kop'], $rgData['style_body']]; // alleen in geheugen: zo wordt het lettertype geladen
        }
    } catch (Throwable $e) { /* regio-slides overslaan */ }

    usort($activeSlides, function($a, $b) {
        return ($a['order'] ?? 10) <=> ($b['order'] ?? 10);
    });
}

// ---- Clipslides: een clip die het scherm overneemt zodra een bepaald nummer speelt ----
$clipSlidesJs = [];
$csList = (isset($slides['clipslides']) && is_array($slides['clipslides'])) ? $slides['clipslides'] : [];
foreach (array_slice($csList, 0, 15) as $csItem) {
    if (!is_array($csItem) || empty($csItem['active'])) continue;
    $csUrl = trim((string)($csItem['url'] ?? ''));
    $csMatch = trim((string)($csItem['match'] ?? ''));
    $csInfo = cmb_clip_info($csUrl);
    if ($csMatch === '' || $csInfo['kind'] === 'invalid') continue;
    $clipSlidesJs[] = [
        'match' => $csMatch,
        'url' => $csUrl,
        'kind' => $csInfo['kind'],
        'id' => $csInfo['id'],
        'delay' => max(0, min(60, floatval($csItem['delay'] ?? 0))),
        'title' => !empty($csItem['show_title']),
        'sound' => !empty($csItem['sound']),
        'fit' => (($csItem['fit'] ?? 'contain') === 'cover') ? 'cover' : 'contain',
        'scope' => (($csItem['scope'] ?? 'frame') === 'screen') ? 'screen' : 'frame',
    ];
}

if (empty($activeSlides)) {
    $activeSlides[] = [
        'type' => 'custom',
        'title' => '',
        'sub' => 'Steenbergen Live',
        'body' => 'Non-stop de beste Dance & Retro Classics',
        'style_title' => ['size' => '22px', 'color' => '#63f6ff'],
        'style_sub' => ['size' => '14px', 'color' => '#ffd166'],
        'style_body' => ['size' => '13px', 'color' => '#ffffff'],
        'media' => '',
        'blur' => 0,
        'darken' => 0,
        'ov' => 0,
        'sec' => 8
    ];
}
// Info-overzicht: elke slide die gewoon rouleert telt mee (noodbericht, tijdgebonden, tijdelijke en getriggerde slides niet)
foreach ($activeSlides as $ovI => $ovS) if (!isset($ovS['cat'])) $activeSlides[$ovI]['cat'] = ['news' => 'nieuws', 'weather' => 'weer'][$ovS['type'] ?? ''] ?? 'overig';
foreach ($activeSlides as $ovI => $ovS) if (!isset($ovS['ov'])) $activeSlides[$ovI]['ov'] = (($ovS['type'] ?? '') === 'emergency') ? 0 : 1;


// Eén vaste achtergrond voor alle slides (behalve het noodbericht); geldt voor slideshow, tegels en tv
if (!empty($settings['slide_one_on']) && trim((string)($settings['slide_one_media'] ?? '')) !== '') {
    $so1 = trim((string)$settings['slide_one_media']); $so1b = intval($settings['slide_one_blur'] ?? 0); $so1d = intval($settings['slide_one_darken'] ?? 35);
    $soApply = function ($s) use ($so1, $so1b, $so1d) { if (is_array($s) && ($s['type'] ?? '') !== 'emergency') { $s['media'] = $so1; $s['blur'] = $so1b; $s['darken'] = $so1d; } return $s; };
    foreach ($activeSlides as $soI => $soS) $activeSlides[$soI] = $soApply($soS);
    foreach ($timedSlidesJs as $soI => $soS) if (isset($soS['slide'])) $timedSlidesJs[$soI]['slide'] = $soApply($soS['slide']);
}

// Thema met een kleurpalet: ook de kleuren van slides en ticker meekleuren
if (!empty($settings['_theme']['palette'])) {
    $thPal = $settings['_theme']['palette'];
    foreach ($activeSlides as $si => $sl) foreach (['style_title', 'style_sub', 'style_body'] as $sk) if (isset($sl[$sk])) $activeSlides[$si][$sk] = cmb_recolor($sl[$sk], $thPal);
    foreach ($timedSlidesJs as $si => $ts) foreach (['style_title', 'style_sub', 'style_body'] as $sk) if (isset($ts['slide'][$sk])) $timedSlidesJs[$si]['slide'][$sk] = cmb_recolor($ts['slide'][$sk], $thPal);
    foreach ($tickerPool as $ti => $tk) if (isset($tk['style'])) $tickerPool[$ti]['style'] = cmb_recolor($tk['style'], $thPal);
}

// LIVE SYNC ENDPOINT (Zonder audio-onderbreking)
if (isset($_GET['api_live_data'])) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    $curSettings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];
    $curSettings = cmb_theme_apply($curSettings, $themePreview !== '' ? $themePreview : null); // thema volgt de planning, ook live
    $curBgGlobal = $curSettings['bg_studio_global'] ?? $curSettings['bg_global'] ?? [];
    $curBgPlayer = $curSettings['bg_studio_player'] ?? $curSettings['bg_player'] ?? [];
    $curLightLeft = $curSettings['lighting_left_frame'] ?? ['mode'=>'chaser', 'speed'=>'4s', 'dir'=>'cw', 'theme'=>'cyan'];
    $curLightRight = $curSettings['lighting_right_frame'] ?? ['mode'=>'chaser', 'speed'=>'4s', 'dir'=>'ccw', 'theme'=>'cyan'];
    $curLightOwn = $curSettings['lighting_own_frame'] ?? $curLightRight;
    
    $liveOut = [
        'slides' => $activeSlides,
        'ticker' => $tickerPool,
        'ov_accent' => preg_match('/^#[0-9a-fA-F]{6}$/', (string)($curSettings['ov_accent'] ?? '')) ? $curSettings['ov_accent'] : '#63f6ff',
        'station_title' => (string)($curSettings['station_title'] ?? ''),
        'station_sub' => (string)($curSettings['station_sub'] ?? 'RETRO • [VISUAL]-RADIO • DANCE'),
        'studio_mail' => (string)($curSettings['studio_mail'] ?? 'mail de studio: mail@chazzo.com'),
        'art_effect' => htmlspecialchars($curSettings['art_effect'] ?? 'aurora', ENT_QUOTES, 'UTF-8'),
        'water_text' => (string)($curSettings['water_text'] ?? ''),
        'water_anim' => htmlspecialchars($curSettings['water_anim_mode'] ?? 'bubble', ENT_QUOTES, 'UTF-8'),
        'light_left_hash' => md5(json_encode($curLightLeft)),
        'light_right_hash' => md5(json_encode($curLightRight)),
        'light_own_hash' => md5(json_encode($curLightOwn)),
        'light_own_html' => renderFrameLayers($curLightOwn),
        'light_left_html' => renderFrameLayers($curLightLeft),
        'light_right_html' => renderFrameLayers($curLightRight),
        'border_left_css' => getStaticFrameBorderCSS($curLightLeft),
        'border_right_css' => getStaticFrameBorderCSS($curLightRight),
        'bg_global' => $curBgGlobal,
        'bg_player' => $curBgPlayer,
        'bg_hash' => md5(json_encode($curBgGlobal) . json_encode($curBgPlayer)),
        'design_hash' => md5(json_encode($curSettings)),
        'fonts_url' => cmb_used_fonts_url($curSettings, $slides),
        'stream_url' => $curSettings['streamUrl'] ?? 'https://host10.onlineradyotv.net:7048/stream/43',
        'fallback_url' => $curSettings['fallbackUrl'] ?? '',
        'stream_alt' => trim((string)($curSettings['streamUrlAlt'] ?? '')),
        'fallback_alt' => trim((string)($curSettings['fallbackUrlAlt'] ?? '')),
        'stream_recheck_sec' => max(20, intval($curSettings['stream_recheck_sec'] ?? 60)),
        'stream_choices' => cmb_stream_choices($curSettings, $dataDir),
        'stream_flyout' => false,
        'art_rotate_min' => max(0, intval($curSettings['art_rotate_min'] ?? 0)),
        'slide_scale' => max(50, min(250, intval($curSettings['slide_text_scale'] ?? 100))),
        'slide_scale_m' => max(50, min(250, intval($curSettings['slide_text_scale_m'] ?? 100))),
        'slide_lines' => max(2, min(20, intval($curSettings['slide_lines'] ?? 9))),
        'slide_lines_m' => max(2, min(12, intval($curSettings['slide_lines_m'] ?? 4))),
        'clipslides' => $clipSlidesJs,
        'timed_slides' => $timedSlidesJs ?? [],
        'stream_delay' => floatval($slides['timing']['delay'] ?? 0),
        'server_ms' => (int)round(microtime(true) * 1000),
        'tz_offset' => intval(date('Z')),
        'slides_mtime' => file_exists($slidesFile) ? filemtime($slidesFile) : 0,
        'settings_mtime' => file_exists($settingsFile) ? filemtime($settingsFile) : 0
    ];
    // Niets veranderd sinds de vorige keer? Dan alleen een klein antwoord: de speler hoeft niets te verwerken (zacht voor tv's en zwakke laptops)
    $liveSrvMs = $liveOut['server_ms']; $liveTz = $liveOut['tz_offset'];
    unset($liveOut['server_ms']);
    $liveHash = md5(json_encode($liveOut));
    // Cache only public, complete responses. Preserve scheduled-theme logic on refresh.
    if ($themePreview === '') {
        $cmbCacheTarget = $cacheDir . 'live-sync-public.json';
        $cmbCacheTemp = @tempnam($cacheDir, 'cmb-live-');
        if ($cmbCacheTemp !== false) {
            if (@file_put_contents($cmbCacheTemp, json_encode(array_merge($liveOut, ['server_ms'=>$liveSrvMs, 'sync_hash'=>$liveHash]), JSON_UNESCAPED_SLASHES)) !== false) {
                @rename($cmbCacheTemp, $cmbCacheTarget);
            } else @unlink($cmbCacheTemp);
        }
    }
    if (isset($_GET['h']) && hash_equals($liveHash, (string)$_GET['h'])) {
        echo json_encode(['same' => 1, 'sync_hash' => $liveHash, 'server_ms' => $liveSrvMs, 'tz_offset' => $liveTz]);
        exit;
    }
    $liveOut['server_ms'] = $liveSrvMs;
    $liveOut['sync_hash'] = $liveHash;
    echo json_encode($liveOut, JSON_UNESCAPED_SLASHES);
    exit;
}

