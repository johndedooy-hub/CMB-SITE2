<?php
/**
 * CHAZZO MUSIC BOX (CMB) - THEMA'S
 * Bestandsnaam: cmb-core/cmb-themes.php
 *
 * - 20 vaste kleurthema's (kleuren in één klik) + "Willekeurig"
 * - Eigen thema's: een volledige kopie van de opmaak in cmb-data/themes/<id>.json
 *   met per thema de keuze "alleen kleuren" (rest blijft je eigen opmaak) of "volledig" (alles van het thema)
 * - Planning: datum + tijd (bv. 31-10 vanaf 18:00), weekdagen + tijd (bv. elke zaterdag), of dagdeel (elke dag 06:00-10:00)
 *   Volgorde bij overlap: datum > weekdag > dagdeel. Niets gepland = de "standaard" (originele opmaak, een vast thema of willekeurig).
 * Het thema wordt alleen in de SPELER toegepast; het beheer toont en bewaart altijd de originele opmaak
 * (behalve als je een thema bewerkt, zie cmb-themes.php).
 */

/** 20 vaste kleurthema's: [naam, hoofdkleur, tweede kleur, accent, accent 2] */
function cmb_theme_presets() {
    return [
        'neon'       => ['Neon (origineel)', '#63f6ff', '#ffd166', '#ff5bc8', '#9d6cff'],
        'groen'      => ['Groen', '#00e676', '#b9f6ca', '#64dd17', '#1de9b6'],
        'blauw'      => ['Blauw', '#2f80ff', '#8ec5ff', '#00b0ff', '#536dfe'],
        'rood'       => ['Rood', '#ff1744', '#ff8a80', '#ff5252', '#d50000'],
        'oranje'     => ['Oranje', '#ff9100', '#ffd180', '#ff6d00', '#ffab40'],
        'paars'      => ['Paars', '#b388ff', '#e1bee7', '#7c4dff', '#ea80fc'],
        'roze'       => ['Roze', '#ff4fa3', '#ffc1e3', '#ff80ab', '#f50057'],
        'goud'       => ['Goud', '#ffd166', '#fff1c1', '#ffb300', '#c99a2e'],
        'ijs'        => ['IJs', '#b3e5fc', '#e1f5fe', '#81d4fa', '#4fc3f7'],
        'turquoise'  => ['Turquoise', '#1de9b6', '#a7ffeb', '#00bfa5', '#64ffda'],
        'mint'       => ['Mint', '#69f0ae', '#e0f2f1', '#b9f6ca', '#00c853'],
        'lime'       => ['Lime', '#c6ff00', '#f4ff81', '#aeea00', '#76ff03'],
        'zonsondergang' => ['Zonsondergang', '#ff7043', '#ffd54f', '#ff4081', '#ab47bc'],
        'oceaan'     => ['Oceaan', '#00b8d4', '#80deea', '#2979ff', '#00e5ff'],
        'bos'        => ['Bos', '#66bb6a', '#c5e1a5', '#a1887f', '#43a047'],
        'lava'       => ['Lava', '#ff3d00', '#ffab00', '#dd2c00', '#ff6e40'],
        'kerst'      => ['Kerst', '#e53935', '#ffd54f', '#43a047', '#ef9a9a'],
        'halloween'  => ['Halloween', '#ff7518', '#b388ff', '#76ff03', '#ff9100'],
        'disco'      => ['Disco', '#ff00ff', '#00ffff', '#ffff00', '#ff1493'],
        'valentijn'  => ['Valentijn', '#ff4d6d', '#ffb3c1', '#ff758f', '#c9184a'],
    ];
}

/* ---------------- Opslag ---------------- */

function cmb_themes_file() { return cmb_data_dir() . 'cmb-themes.json'; }
function cmb_theme_dir()   { return cmb_data_dir() . 'themes/'; }
function cmb_theme_file($id) { return cmb_theme_dir() . preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$id)) . '.json'; }

function cmb_themes_load() {
    $f = cmb_themes_file();
    $d = is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    $d += ['themes' => [], 'fallback' => 'original', 'random_pick' => ''];
    if (!is_array($d['themes'])) $d['themes'] = [];
    return $d;
}

function cmb_themes_save($d) {
    $dir = cmb_data_dir();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $f = cmb_themes_file();
    if (function_exists('cmb_snapshot')) cmb_snapshot($f);
    $okT = file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    if ($okT) cmb_pulse('design');
    return $okT;
}

function cmb_theme_snapshot_load($id) {
    $f = cmb_theme_file($id);
    return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : null;
}

/* ---------------- Kleuren ---------------- */

/** Is dit een kleurwaarde (#hex, rgb/rgba, hsl)? */
function cmb_is_color($v) {
    return is_string($v) && preg_match('/^\s*(#[0-9a-f]{3,8}|rgba?\([^)]*\)|hsla?\([^)]*\))\s*$/i', $v);
}

/** Kleur -> [r, g, b, a] (0-255, alpha 0-1); null als het geen kleur is */
function cmb_color_rgba($v) {
    $v = trim((string)$v);
    if (preg_match('/^#([0-9a-f]{3,8})$/i', $v, $m)) {
        $h = $m[1];
        if (strlen($h) === 3 || strlen($h) === 4) $h = preg_replace('/(.)/', '$1$1', $h);
        if (strlen($h) !== 6 && strlen($h) !== 8) return null;
        $a = strlen($h) === 8 ? hexdec(substr($h, 6, 2)) / 255 : 1;
        return [hexdec(substr($h, 0, 2)), hexdec(substr($h, 2, 2)), hexdec(substr($h, 4, 2)), $a];
    }
    if (preg_match('/^rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)\s*(?:,\s*([\d.]+)\s*)?\)$/i', $v, $m)) {
        return [floatval($m[1]), floatval($m[2]), floatval($m[3]), isset($m[4]) && $m[4] !== '' ? floatval($m[4]) : 1];
    }
    return null;
}

/** [tint 0-360, verzadiging 0-1, lichtheid 0-1] */
function cmb_color_hsl($rgb) {
    $r = $rgb[0] / 255; $g = $rgb[1] / 255; $b = $rgb[2] / 255;
    $max = max($r, $g, $b); $min = min($r, $g, $b); $l = ($max + $min) / 2;
    if ($max == $min) return [0, 0, $l];
    $d = $max - $min;
    $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
    if ($max == $r) $h = (($g - $b) / $d + ($g < $b ? 6 : 0));
    elseif ($max == $g) $h = (($b - $r) / $d + 2);
    else $h = (($r - $g) / $d + 4);
    return [$h * 60, $s, $l];
}

/**
 * Kleurt alle kleuren in de opmaak om naar een palet [hoofd, tweede, accent, accent2].
 * Elke (duidelijke) kleur krijgt de paletkleur die bij zijn "rol" hoort: cyaan/groen -> hoofdkleur,
 * goud/geel/oranje -> tweede kleur, roze/rood -> accent, paars/blauw -> accent 2.
 * Wit, grijs en heel donker blijven staan (achtergronden en leesbare tekst).
 */
function cmb_recolor($val, $pal) {
    if (is_array($val)) {
        foreach ($val as $k => $v) $val[$k] = cmb_recolor($v, $pal);
        return $val;
    }
    if (!cmb_is_color($val)) return $val;
    $c = cmb_color_rgba($val);
    if (!$c) return $val;
    list($h, $s, $l) = cmb_color_hsl($c);
    if ($s < 0.25 || $l > 0.93 || $l < 0.12) return $val;
    // rollen op tint: [tint, paletindex]
    $roles = [[184, 0], [150, 0], [42, 1], [60, 1], [25, 1], [318, 2], [350, 2], [0, 2], [263, 3], [230, 3]];
    $best = 0; $bd = 999;
    foreach ($roles as $r) {
        $d = abs($h - $r[0]); $d = min($d, 360 - $d);
        if ($d < $bd) { $bd = $d; $best = $r[1]; }
    }
    $t = cmb_color_rgba($pal[$best]);
    if (!$t) return $val;
    if ($c[3] < 1) return 'rgba(' . intval($t[0]) . ',' . intval($t[1]) . ',' . intval($t[2]) . ',' . round($c[3], 3) . ')';
    return sprintf('#%02x%02x%02x', $t[0], $t[1], $t[2]);
}

/** Neemt alleen de kleuren (en lichtthema-namen) uit $snap over in $base, op dezelfde plek. */
function cmb_copy_colors($base, $snap) {
    if (!is_array($snap)) return $base;
    foreach ($snap as $k => $v) {
        if (is_array($v)) {
            $allColors = $v && count(array_filter($v, 'cmb_is_color')) === count($v);
            if ($allColors) { $base[$k] = $v; continue; } // bv. water_palette
            $base[$k] = cmb_copy_colors(isset($base[$k]) && is_array($base[$k]) ? $base[$k] : [], $v);
        } elseif (cmb_is_color($v) || ($k === 'theme' && is_string($v))) {
            $base[$k] = $v;
        }
    }
    return $base;
}

/** Sleutels die bij de inhoud/techniek horen en NOOIT door een thema worden overschreven. */
function cmb_theme_skip_key($k) {
    return (bool)preg_match('/^(stream|fallbackUrl|statsJsonUrl|apiBackupUrl|jsSrc|jsPort|coverApi|cover_search|cover_fallback|lastfm|hidden_presets|gemini|anthropic|weather_city|ticker_advanced|extra_streams|logo_url|brand_|favicon_url|seo_|_theme)/i', (string)$k);
}

/* ---------------- Welk thema is nu actief? ---------------- */

function cmb_hm($s) { // "18:30" -> minuten
    if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim((string)$s), $m)) return null;
    return min(23, intval($m[1])) * 60 + min(59, intval($m[2]));
}
function cmb_in_timerange($from, $until, $nowMin) {
    $f = cmb_hm($from); $u = cmb_hm($until);
    if ($f === null && $u === null) return true;
    if ($f === null) $f = 0;
    if ($u === null) $u = 24 * 60;
    return $f <= $u ? ($nowMin >= $f && $nowMin < $u) : ($nowMin >= $f || $nowMin < $u); // over middernacht
}

/** Geeft het actieve thema: ['key' => 'preset:groen' | 'own:<id>', 'name', 'reason'] of null (originele opmaak). */
function cmb_theme_active($now = null, $data = null) {
    $now = $now ?? time();
    $d = $data ?? cmb_themes_load();
    $nowMin = intval(date('G', $now)) * 60 + intval(date('i', $now));
    $dk = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'][intval(date('w', $now))];
    $best = null; $bestPrio = 0;
    foreach ($d['themes'] as $t) {
        if (empty($t['id']) || empty($t['enabled'])) continue;
        $sc = $t['schedule'] ?? [];
        $type = $sc['type'] ?? 'none';
        $prio = 0; $why = '';
        if ($type === 'date') {
            $f = !empty($sc['date_from']) ? strtotime($sc['date_from']) : false;
            $u = !empty($sc['date_until']) ? strtotime($sc['date_until']) : false;
            if (($f === false || $now >= $f) && ($u === false || $now < $u) && ($f !== false || $u !== false)) { $prio = 3; $why = 'gepland op datum'; }
        } elseif ($type === 'weekday') {
            $days = (array)($sc['days'] ?? []);
            if ($days && in_array($dk, $days, true) && cmb_in_timerange($sc['time_from'] ?? '', $sc['time_until'] ?? '', $nowMin)) { $prio = 2; $why = 'gepland op ' . implode('/', $days); }
        } elseif ($type === 'daypart') {
            if (cmb_in_timerange($sc['time_from'] ?? '', $sc['time_until'] ?? '', $nowMin)) { $prio = 1; $why = 'dagdeel ' . ($sc['time_from'] ?? '') . '-' . ($sc['time_until'] ?? ''); }
        }
        if ($prio > $bestPrio) { $bestPrio = $prio; $best = ['key' => 'own:' . $t['id'], 'name' => $t['name'] ?? $t['id'], 'reason' => $why]; }
    }
    if ($best) return $best;
    $fb = (string)($d['fallback'] ?? 'original');
    if ($fb === 'random') {
        $pick = (string)($d['random_pick'] ?? '');
        if ($pick === '') return null;
        return ['key' => $pick, 'name' => cmb_theme_label($pick, $d), 'reason' => 'willekeurig gekozen'];
    }
    if ($fb !== '' && $fb !== 'original') return ['key' => $fb, 'name' => cmb_theme_label($fb, $d), 'reason' => 'standaard'];
    return null;
}

function cmb_theme_label($key, $d = null) {
    $p = cmb_theme_presets();
    if (strpos($key, 'preset:') === 0) { $k = substr($key, 7); return isset($p[$k]) ? $p[$k][0] : $k; }
    if (strpos($key, 'own:') === 0) {
        $d = $d ?? cmb_themes_load();
        foreach ($d['themes'] as $t) if (($t['id'] ?? '') === substr($key, 4)) return $t['name'] ?? $t['id'];
    }
    return $key;
}

/** Nieuwe willekeurige keuze uit de 20 vaste thema's en je eigen thema's (niet dezelfde als nu). */
function cmb_theme_random_pick($d) {
    $keys = array_map(function ($k) { return 'preset:' . $k; }, array_keys(cmb_theme_presets()));
    foreach ($d['themes'] as $t) if (!empty($t['id'])) $keys[] = 'own:' . $t['id'];
    $keys = array_values(array_diff($keys, ['preset:neon', (string)($d['random_pick'] ?? '')]));
    return $keys ? $keys[array_rand($keys)] : '';
}

/**
 * Past een thema toe op de instellingen van de speler.
 * Geeft de nieuwe instellingen terug; '_theme' (sleutel + CSS-variabelen) zit erin zodat de live-sync de wissel ziet.
 */
function cmb_theme_apply($settings, $key = null) {
    $d = cmb_themes_load();
    if ($key === null) { $a = cmb_theme_active(null, $d); $key = $a ? $a['key'] : ''; }
    if ($key === '' || $key === 'original') return $settings;
    $vars = [];
    if (strpos($key, 'preset:') === 0) {
        $p = cmb_theme_presets(); $k = substr($key, 7);
        if (!isset($p[$k])) return $settings;
        $pal = array_slice($p[$k], 1, 4);
        // Frames zonder eigen randkleuren krijgen eerst de standaardkleuren, zodat ook die meekleuren
        $lightDef = ['top_col' => '#63f6ff', 'right_col' => '#ff5bc8', 'bottom_col' => '#ffd166', 'left_col' => '#63f6ff', 'corner_col' => '#63f6ff'];
        foreach (['lighting_left_frame' => 'cw', 'lighting_right_frame' => 'ccw'] as $lk => $dir) {
            $cur = (isset($settings[$lk]) && is_array($settings[$lk])) ? $settings[$lk] : ['mode' => 'chaser', 'speed' => '4s', 'dir' => $dir, 'theme' => 'cyan'];
            foreach ($lightDef as $ck => $cv) if (empty($cur[$ck])) $cur[$ck] = $cv;
            $settings[$lk] = $cur;
        }
        $settings = cmb_recolor($settings, $pal);
        $vars = ['cyan' => $pal[0], 'gold' => $pal[1], 'magenta' => $pal[2], 'violet' => $pal[3]];
    } elseif (strpos($key, 'own:') === 0) {
        $id = substr($key, 4); $meta = null;
        foreach ($d['themes'] as $t) if (($t['id'] ?? '') === $id) $meta = $t;
        $snap = cmb_theme_snapshot_load($id);
        if (!$meta || !is_array($snap)) return $settings;
        if (($meta['mode'] ?? 'colors') === 'full') {
            foreach ($snap as $sk => $sv) if (!cmb_theme_skip_key($sk)) $settings[$sk] = $sv;
        } else {
            $settings = cmb_copy_colors($settings, array_filter($snap, function ($sk) { return !cmb_theme_skip_key($sk); }, ARRAY_FILTER_USE_KEY));
        }
        if (!empty($meta['palette']) && is_array($meta['palette']) && count($meta['palette']) === 4) {
            $pal = array_values($meta['palette']);
            $vars = ['cyan' => $pal[0], 'gold' => $pal[1], 'magenta' => $pal[2], 'violet' => $pal[3]];
        }
    } else {
        return $settings;
    }
    $settings['_theme'] = ['key' => $key, 'vars' => $vars, 'palette' => isset($pal) ? $pal : []];
    return $settings;
}

/** CSS-variabelen van het actieve thema (voor :root in de speler). */
function cmb_theme_vars_css($settings) {
    $css = '';
    foreach ((array)($settings['_theme']['vars'] ?? []) as $k => $v) {
        if (preg_match('/^[a-z]+$/', $k) && cmb_is_color($v)) $css .= "--{$k}: {$v}; ";
    }
    return $css;
}
