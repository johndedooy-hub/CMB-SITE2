<?php
/** CMB speler: hulpfuncties (RSS, stijl-CSS, frames, seizoensdecor). Geladen door header.php. */
if (!defined('CMB_PART')) { http_response_code(403); exit; }
// RSS Parser Helper met cURL / stream fallback en foto-extractie
function getRssFeedItem($feedUrl, $cacheDir) {
    if (empty($feedUrl)) return null;
    $cFile = $cacheDir . 'rss_' . md5($feedUrl) . '.json';
    $slidesMtime = file_exists($slidesFile) ? filemtime($slidesFile) : 0;
    if (file_exists($cFile) && (time() - filemtime($cFile) < 180) && (filemtime($cFile) >= $slidesMtime)) {
        return json_decode(file_get_contents($cFile), true);
    }

    $rawXml = '';
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $feedUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $rawXml = curl_exec($ch);
        curl_close($ch);
    }
    if (!$rawXml) {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => 4,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)
Accept: application/rss+xml, application/xml, text/xml
"
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
        ]);
        $rawXml = @file_get_contents($feedUrl, false, $ctx);
    }

    if (!$rawXml) return null;
    $xml = @simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA);
    if (!$xml || !isset($xml->channel->item[0])) return null;

    $item = $xml->channel->item[0];
    $title = trim(strip_tags((string)$item->title));
    $desc = trim(strip_tags((string)$item->description));
    $img = '';

    if (isset($item->enclosure['url'])) {
        $img = (string)$item->enclosure['url'];
    } else {
        $media = $item->children('media', true);
        if (isset($media->content)) {
            $img = (string)$media->content->attributes()->url;
        }
    }

    $res = ['title' => $title, 'desc' => $desc, 'img' => $img];
    $oldC = file_exists($cFile) ? @file_get_contents($cFile) : '';
    $newC = json_encode($res);
    @file_put_contents($cFile, $newC);
    if ($oldC !== $newC) cmb_pulse('ticker');
    return $res;
}

function getStyleCSS($styleArr) {
    if (empty($styleArr) || !is_array($styleArr)) return '';
    $css = '';
    if (!empty($styleArr['font'])) $css .= "font-family: '{$styleArr['font']}', 'Noto Color Emoji', sans-serif;";
    if (!empty($styleArr['size'])) $css .= "font-size: {$styleArr['size']};";
    if (!empty($styleArr['shadow'])) $css .= "text-shadow: 0 2px 4px {$styleArr['shadow']}, 0 0 4px #000;";
    $col = $styleArr['color'] ?? '#ffffff';
    $col2 = $styleArr['grad_col2'] ?? '#63f6ff';
    $gm = $styleArr['grad_mode'] ?? 'none';
    if ($gm === 'tb') {
        $css .= "background: linear-gradient(180deg, {$col}, {$col2}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;";
    } elseif ($gm === 'lr') {
        $css .= "background: linear-gradient(90deg, {$col}, {$col2}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;";
    } elseif ($gm === 'diag1') {
        $css .= "background: linear-gradient(45deg, {$col}, {$col2}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;";
    } elseif ($gm === 'diag2') {
        $css .= "background: linear-gradient(135deg, {$col}, {$col2}); -webkit-background-clip: text; -webkit-text-fill-color: transparent;";
    } else {
        $css .= "color: {$col};";
    }
    return $css;
}

function getBorderCSS($borderArr, $defaultColor = '#63f6ff') {
    if (empty($borderArr) || empty($borderArr['enabled'])) return 'border: none;';
    $w = intval($borderArr['width'] ?? 1);
    $c = htmlspecialchars($borderArr['color'] ?? $defaultColor);
    return "border: {$w}px solid {$c} !important;";
}


function renderLightingLayer($l) {
    if (empty($l) || !is_array($l)) return '';
    $mode = $l['mode'] ?? 'chaser';
    if ($mode === 'none') return '';
    
    $speedNum = floatval($l['speed'] ?? 4);
    $speed = (($speedNum >= 0.3 && $speedNum <= 120) ? rtrim(rtrim(number_format($speedNum, 1, '.', ''), '0'), '.') : '4') . 's';
    $dir = ($l['dir'] ?? 'cw') === 'ccw' ? 'reverse' : 'normal';
    $theme = $l['theme'] ?? 'cyan';
    $cw = max(1, min(12, intval($l['chaser_w'] ?? 3)));
    
    // Theme kleuren mapping
    $primary = 'var(--cyan)';
    $secondary = 'var(--magenta)';
    if ($theme === 'gold') { $primary = '#ffd166'; $secondary = '#ff9100'; }
    elseif ($theme === 'magenta') { $primary = '#ff007f'; $secondary = '#7928ca'; }
    elseif ($theme === 'fire') { $primary = '#ff1744'; $secondary = '#ff9100'; }
    elseif ($theme === 'emerald') { $primary = '#00e676'; $secondary = '#1de9b6'; }
    elseif ($theme === 'rainbow') { $primary = '#00ffff'; $secondary = '#ff00ff'; }
    elseif ($theme === 'custom' && !empty($l['custom_col'])) { $primary = cmb_clean_color($l['custom_col'], '#00ffff'); $secondary = $primary; }
    elseif ($theme === 'gradient') { $primary = cmb_clean_color($l['grad_col1'] ?? '', '#63f6ff'); $secondary = cmb_clean_color($l['grad_col2'] ?? '', '#ff5bc8'); }
    $ang = cmb_gradient_deg($l['grad_angle'] ?? 'diag2');

    if ($mode === 'chaser') {
        $grad = 'conic-gradient(from 0deg, var(--cyan) 0deg 90deg, transparent 100deg 360deg)';
        if ($theme === 'gold') $grad = 'conic-gradient(from 0deg, #ffd166 0deg 90deg, transparent 100deg 360deg)';
        elseif ($theme === 'rainbow') $grad = 'conic-gradient(red, orange, yellow, green, cyan, blue, magenta, red)';
        elseif ($theme === 'magenta') $grad = 'conic-gradient(from 0deg, #ff007f 0deg 90deg, transparent 100deg 360deg)';
        elseif ($theme === 'fire') $grad = 'conic-gradient(from 0deg, #ff1744 0deg 90deg, #ff9100 180deg, transparent 200deg 360deg)';
        elseif ($theme === 'emerald') $grad = 'conic-gradient(from 0deg, #00e676 0deg 90deg, #1de9b6 180deg, transparent 200deg 360deg)';
        elseif ($theme === 'custom') $grad = 'conic-gradient(from 0deg, ' . $primary . ' 0deg 90deg, transparent 100deg 360deg)';
        elseif ($theme === 'gradient') $grad = 'conic-gradient(from 0deg, ' . $primary . ' 0deg 60deg, ' . $secondary . ' 110deg 150deg, transparent 160deg 360deg)';

        return '<div class="frame-chaser-layer chaser-spin-layer" style="padding:' . $cw . 'px;">
            <div style="position:absolute; inset:-50%; width:200%; height:200%; animation: chaserSpin ' . $speed . ' linear infinite ' . $dir . '; background: ' . $grad . ';"></div>
        </div>';
    } elseif ($mode === 'rain_curtain') {
        $rainGrad = ($theme === 'rainbow') 
            ? 'repeating-linear-gradient(180deg, red 0px, yellow 15px, green 30px, cyan 45px, blue 60px, magenta 75px, transparent 90px)'
            : 'repeating-linear-gradient(180deg, ' . $primary . ' 0px, transparent 15px, ' . $secondary . ' 30px, transparent 45px)';
        return '<div class="frame-rain-curtain" style="padding:' . $cw . 'px; animation: rainCurtainFall ' . $speed . ' linear infinite ' . $dir . '; background: ' . $rainGrad . '; background-size: 100% 90px; filter: drop-shadow(0 0 6px ' . $primary . ');"></div>';
    } elseif ($mode === 'pulse_glow') {
        $pulseGrad = ($theme === 'rainbow')
            ? 'conic-gradient(red, yellow, lime, cyan, blue, magenta, red)'
            : 'linear-gradient(' . $ang . ', ' . $primary . ', ' . $secondary . ')';
        return '<div class="frame-pulse-glow" style="padding:' . $cw . 'px; animation: pulseGlowBreathe ' . $speed . ' ease-in-out infinite; background: ' . $pulseGrad . '; filter: drop-shadow(0 0 8px ' . $primary . ');"></div>';
    } elseif ($mode === 'music_disco') {
        return '<div class="frame-music-disco" style="padding:' . $cw . 'px; animation: discoSpectrumCycle ' . $speed . ' linear infinite ' . $dir . ', pulseGlowBreathe 1.2s infinite; background: conic-gradient(' . $primary . ', ' . $secondary . ', var(--gold), ' . $primary . ');"></div>';
    } elseif ($mode === 'random_spec') {
        return '<div class="frame-random-spec" style="padding:' . $cw . 'px; animation: discoSpectrumCycle ' . $speed . ' linear infinite ' . $dir . '; background: conic-gradient(red, yellow, lime, cyan, blue, magenta, red);"></div>';
    } elseif ($mode === 'golden_sparks') {
        // Fonkelende Vonken met gekozen themakleur en vonk-gloed
        $sparkGrad = 'conic-gradient(from 45deg, ' . $primary . ', ' . $secondary . ', #fff, ' . $primary . ')';
        return '<div class="frame-golden-sparks" style="padding:' . $cw . 'px; background:' . $sparkGrad . '; filter:drop-shadow(0 0 8px ' . $primary . ');">
            <div style="position:absolute; inset:-50%; width:200%; height:200%; animation: fxGoldenSparks ' . $speed . ' ease-in-out infinite ' . $dir . ', chaserSpin 5s linear infinite; background:' . $sparkGrad . ';"></div>
        </div>';
    } elseif ($mode === 'snow_dots') {
        return '<div class="frame-snow-dots" style="padding:' . $cw . 'px; animation: fxSnowFall ' . $speed . ' linear infinite ' . $dir . '; filter: drop-shadow(0 0 4px ' . $primary . ');"></div>';
    } elseif ($mode === 'neon_waterfall') {
        $wfGrad = 'linear-gradient(180deg, transparent 0%, ' . $primary . ' 60%, ' . $secondary . ' 100%)';
        return '<div class="frame-neon-waterfall" style="padding:' . $cw . 'px; animation: fxWaterfallRun ' . $speed . ' linear infinite ' . $dir . '; background:' . $wfGrad . '; background-size:100% 180px; filter: drop-shadow(0 0 6px ' . $primary . ');"></div>';
    } elseif ($mode === 'laser_radar') {
        $lrGrad = 'linear-gradient(90deg, transparent 0%, ' . $primary . ' 50%, transparent 100%)';
        return '<div class="frame-laser-radar" style="padding:' . $cw . 'px; animation: fxLaserSweep ' . $speed . ' ease-in-out infinite ' . $dir . '; background:' . $lrGrad . '; background-size:200% 200%; filter: drop-shadow(0 0 8px ' . $primary . ');"></div>';
    } elseif ($mode === 'breathing_aurora') {
        $auroraGrad = ($theme === 'rainbow') ? 'conic-gradient(from 180deg, red, yellow, lime, cyan, blue, magenta, red)' : 'conic-gradient(from 180deg, ' . $primary . ', ' . $secondary . ', ' . $primary . ')';
        return '<div class="frame-breathing-aurora" style="padding:' . $cw . 'px; animation: fxAuroraBreathe ' . $speed . ' ease-in-out infinite ' . $dir . ';">
            <div class="frame-aurora-spin" style="background:' . $auroraGrad . '; animation: chaserSpin 10s linear infinite ' . $dir . ';"></div>
        </div>';
    } elseif ($mode === 'snow_edge') {
        $band = max(10, $cw * 4);
        return '<div class="frame-snow-edge" style="padding:' . $band . 'px; animation-duration:' . $speed . '; animation-direction:' . $dir . ';"></div>';
    } elseif ($mode === 'confetti_edge') {
        $band = max(10, $cw * 4);
        return '<div class="frame-confetti-edge" style="padding:' . $band . 'px; animation-duration:' . $speed . '; animation-direction:' . $dir . ';"></div>';
    } elseif ($mode === 'fire_edge') {
        $fa = ($theme === 'cyan') ? '#ff1744' : $primary;
        $fb = ($theme === 'cyan') ? '#ff9100' : $secondary;
        $fireGrad = 'repeating-linear-gradient(0deg, ' . $fa . ' 0px, ' . $fb . ' 14px, #ffea00 26px, ' . $fb . ' 38px, ' . $fa . ' 52px)';
        return '<div class="frame-fire-edge" style="padding:' . $cw . 'px; background:' . $fireGrad . '; background-size:100% 52px; animation-duration: calc(' . $speed . ' / 3), .25s; animation-direction:' . $dir . ', alternate;"></div>';
    } elseif ($mode === 'rainbow_run') {
        return '<div class="frame-rainbow-run" style="padding:' . $cw . 'px; animation-duration:' . $speed . '; animation-direction:' . $dir . ';"></div>';
    } elseif ($mode === 'gold_shimmer') {
        $ga = ($theme === 'cyan') ? '#ffd166' : $primary;
        $gb = ($theme === 'cyan') ? '#7a5a00' : $secondary;
        $goldGrad = 'linear-gradient(110deg, ' . $gb . ' 0%, ' . $ga . ' 25%, #fff6c8 40%, ' . $ga . ' 55%, ' . $gb . ' 100%)';
        return '<div class="frame-gold-shimmer" style="padding:' . $cw . 'px; background:' . $goldGrad . '; background-size:250% 100%; animation-duration:' . $speed . '; animation-direction:' . $dir . ';"></div>';
    } elseif ($mode === 'electric_pulse') {
        $elGrad = 'repeating-linear-gradient(45deg, ' . $primary . ' 0px, ' . $primary . ' 10px, transparent 10px, transparent 20px, ' . $secondary . ' 20px, ' . $secondary . ' 30px, transparent 30px, transparent 40px)';
        return '<div class="frame-electric-pulse" style="padding:' . $cw . 'px; background:' . $elGrad . '; animation-duration: calc(' . $speed . ' / 4), 1.4s; animation-direction:' . $dir . ', normal; filter: drop-shadow(0 0 6px ' . $primary . ');"></div>';
    } elseif ($mode === 'cyber_matrix') {
        return '<div class="frame-cyber-matrix" style="padding:' . $cw . 'px; animation: fxMatrixCode ' . $speed . ' linear infinite ' . $dir . '; filter: drop-shadow(0 0 6px ' . $primary . ');"></div>';
    }
    return '';
}

/** Welk seizoensdecor is nu actief voor dit frame? ('' = geen) */
function cmb_active_season($l) {
    $m = is_array($l) ? ($l['season_mode'] ?? 'none') : 'none';
    if ($m === 'auto') $m = cmb_auto_season();
    return array_key_exists($m, cmb_season_modes()) && $m !== 'none' && $m !== 'auto' ? $m : '';
}

/** Losse laag met seizoensdecor (sneeuwlaagje, bladeren, kerstverlichting, enz.) */
function renderSeasonLayer($l) {
    $mode = cmb_active_season($l);
    if ($mode === '') return '';
    $pos = $l['season_pos'] ?? 'bottom';
    if (!array_key_exists($pos, cmb_season_positions())) $pos = 'bottom';

    $cfg = [
        'snow'      => ['fall' => ['❄', '❅', '❆', '❄'], 'edge' => 'snowcap'],
        'xmas'      => ['edge' => 'lights', 'static' => ['🎄', '🎁', '⭐', '🔔', '🎄']],
        'leaves'    => ['fall' => ['🍂', '🍁', '🍃', '🍂']],
        'halloween' => ['static' => ['🎃', '🕸️', '🎃'], 'fly' => '🦇'],
        'valentine' => ['rise' => ['💖', '💕', '💗', '💘']],
        'spring'    => ['fall' => ['🌸', '🌼', '🌷', '🌸']],
        'easter'    => ['static' => ['🥚', '🐣', '🐰', '🥚', '🐣'], 'hop' => true],
        'orange'    => ['edge' => 'bunting', 'fall' => ['🧡']],
    ][$mode] ?? [];

    $edges = ['bottom' => ['bottom'], 'top' => ['top'], 'sides' => ['left', 'right'], 'all' => ['top', 'right', 'bottom', 'left']][$pos];
    $h = '<div class="cmb-season cmb-season-' . $mode . ' cmb-pos-' . $pos . '">';

    if (!empty($cfg['edge'])) {
        foreach ($edges as $e) {
            $h .= '<div class="cmb-s-edge cmb-edge-' . $e . ' cmb-edge-' . $cfg['edge'] . '"></div>';
        }
    }

    $falling = $cfg['fall'] ?? ($cfg['rise'] ?? []);
    if ($falling) {
        $zones = ($pos === 'sides') ? ['left', 'right'] : [$pos];
        foreach ($zones as $zi => $z) {
            $h .= '<div class="cmb-s-zone cmb-zone-' . $z . '">';
            for ($i = 0; $i < 7; $i++) {
                $left = ($i * 14 + $zi * 7 + 4) % 90;
                $dur = 7 + (($i * 5 + $zi * 3) % 7);
                $delay = -(($i * 13 + $zi * 5) % 11);
                $size = 11 + (($i * 3) % 6);
                $cls = isset($cfg['rise']) ? 'cmb-s-rise' : 'cmb-s-fall';
                $h .= '<span class="cmb-s-item ' . $cls . '" style="left:' . $left . '%; font-size:' . $size . 'px; animation-duration:' . $dur . 's; animation-delay:' . $delay . 's;">' . $falling[$i % count($falling)] . '</span>';
            }
            $h .= '</div>';
        }
    }

    if (!empty($cfg['static'])) {
        $n = count($cfg['static']);
        foreach ($edges as $ei => $e) {
            foreach ($cfg['static'] as $i => $emo) {
                $at = (int)round(8 + $i * (84 / max(1, $n - 1)));
                $style = ($e === 'bottom') ? "left:{$at}%; bottom:1px;" : (($e === 'top') ? "left:{$at}%; top:1px;" : (($e === 'left') ? "top:{$at}%; left:1px;" : "top:{$at}%; right:1px;"));
                $hop = !empty($cfg['hop']) ? ' cmb-s-hop' : ' cmb-s-glow';
                $h .= '<span class="cmb-s-static' . $hop . '" style="' . $style . ' animation-delay:' . (-($i * 0.37 + $ei * 0.2)) . 's;">' . $emo . '</span>';
            }
        }
    }

    if (!empty($cfg['fly'])) {
        for ($i = 0; $i < 3; $i++) {
            $h .= '<span class="cmb-s-fly" style="top:' . (6 + $i * 11) . '%; animation-duration:' . (9 + $i * 4) . 's; animation-delay:' . (-($i * 4)) . 's;">' . $cfg['fly'] . '</span>';
        }
    }
    return $h . '</div>';
}

/** Alle lagen van een frame: animatie (tenzij uitgezet door seizoensdecor) + seizoensdecor */
function cmb_ring_imgs($l) {
    if (!is_array($l) || !in_array($l['ring_mode'] ?? 'lines', ['image', 'carousel'], true)) return [];
    $out = [];
    foreach ((array)($l['ring_imgs'] ?? []) as $n) {
        $n = basename((string)$n);
        if (preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|webp)$/i', $n) && is_file(CMB_ROOT . '/cmb-frames/' . $n)) $out[] = $n;
    }
    $out = array_slice(array_values(array_unique($out)), 0, 6);
    if (($l['ring_mode'] ?? '') === 'image') $out = array_slice($out, 0, 1);
    return $out;
}
function renderRingLayers($l) {
    $imgs = cmb_ring_imgs($l);
    if (!$imgs) return '';
    $px = max(1, min(3, intval($l['ring_px'] ?? 2)));
    $sec = max(2, min(600, intval($l['ring_sec'] ?? 12)));
    $h = '<div class="cmb-ring" data-sec="' . $sec . '" style="--rp:' . $px . 'px;">';
    foreach ($imgs as $i => $n) $h .= '<div class="cmb-ring-layer' . ($i === 0 ? ' on' : '') . '" style="background-image:url(\'cmb-frames/' . htmlspecialchars($n, ENT_QUOTES) . '\');"></div>';
    return $h . '</div>';
}

function renderFrameLayers($l) {
    if (!is_array($l)) return '';
    if (cmb_ring_imgs($l)) return renderRingLayers($l) . renderSeasonLayer($l);
    $anim = renderLightingLayer($l);
    if (!empty($l['season_exclusive']) && cmb_active_season($l) !== '') $anim = '';
    return $anim . renderSeasonLayer($l);
}


function getStaticFrameBorderCSS($lightingArr) {
    if (empty($lightingArr) || !is_array($lightingArr)) return '';
    $css = '';
    $styles = cmb_side_styles();
    $defCols = ['top' => '#63f6ff', 'right' => '#ff5bc8', 'bottom' => '#ffd166', 'left' => '#63f6ff'];
    $ringOn = (bool)cmb_ring_imgs($lightingArr);
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        if ($ringOn) { $css .= "border-{$side}: none !important; "; continue; }
        $w = max(0, intval($lightingArr["{$side}_w"] ?? 2));
        $st = $lightingArr["{$side}_style"] ?? 'solid';
        if (!isset($styles[$st])) $st = 'solid';
        if ($st === 'double' && $w > 0 && $w < 3) $w = 3;
        $col = cmb_clean_color($lightingArr["{$side}_col"] ?? '', $defCols[$side]);
        $css .= "border-{$side}: " . ($w > 0 ? ($w . 'px ' . $st . ' ' . $col) : 'none') . ' !important; ';
    }

    // Hoeken: algemene ronding, per hoek eigen ronding of uit (scherp)
    $corner_mode = $lightingArr['corner_mode'] ?? 'sync';
    $corner_col = cmb_clean_color($lightingArr['corner_col'] ?? '', '#63f6ff');
    $corner_size = max(0, min(60, intval($lightingArr['corner_size'] ?? 24)));
    $corner_w = max(0, min(10, intval($lightingArr['corner_w'] ?? 2)));
    $corner_glow = !empty($lightingArr['corner_glow']);

    $radii = [];
    foreach (['tl', 'tr', 'br', 'bl'] as $c) {
        if (isset($lightingArr["corner_{$c}_on"]) && empty($lightingArr["corner_{$c}_on"])) { $radii[] = 0; continue; }
        $own = $lightingArr["corner_{$c}_r"] ?? '';
        $radii[] = ($own === '' || $own === null) ? $corner_size : max(0, min(60, intval($own)));
    }
    $css .= 'border-radius: ' . implode('px ', $radii) . 'px !important; ';
    if ($ringOn) return $css . 'box-shadow: none !important; ';

    if ($corner_mode === 'custom') {
        $shadows = [];
        if ($corner_w > 0) $shadows[] = 'inset 0 0 0 ' . $corner_w . 'px ' . $corner_col;
        if ($corner_glow) $shadows[] = '0 0 16px ' . $corner_col;
        if ($shadows) $css .= 'box-shadow: ' . implode(', ', $shadows) . '; ';
    } elseif ($corner_glow) {
        $css .= 'box-shadow: 0 0 14px ' . cmb_clean_color($lightingArr['top_col'] ?? '', '#63f6ff') . '; ';
    }
    return $css;
}
