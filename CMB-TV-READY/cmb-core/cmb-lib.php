<?php
/**
 * CHAZZO MUSIC BOX (CMB) - GEDEELDE HULPFUNCTIES
 * Bestandsnaam: cmb-lib.php
 * Wordt geladen door alle CMB-pagina's (speler, beheer, studio, back-up, assistent).
 */

// Hoofdmap van deze CMB-installatie (de map met index.php)
if (!defined('CMB_ROOT')) define('CMB_ROOT', dirname(__DIR__));

/* ---------------- Config buiten public_html (inlog, sleutels, datapaden) ----------------
 * Zet cmb-config.php in je thuismap (bv. /home/u123456789/cmb-config.php), dus BUITEN public_html.
 * CMB zoekt hem zelf: eerst de omgevingsvariabele CMB_CONFIG, dan elke bovenliggende map vanaf deze installatie.
 * Zo vinden zowel chazzo.nl/cmb3 als een subdomein hetzelfde bestand. Geen config = standaardwaarden.
 */
function cmb_config_path() {
    static $path = false;
    if ($path !== false) return $path;
    $path = '';
    $env = getenv('CMB_CONFIG');
    if ($env && @is_file($env)) return $path = $env;
    $dir = CMB_ROOT;
    for ($i = 0; $i < 8; $i++) {
        $f = $dir . '/cmb-config.php';
        if (@is_file($f)) return $path = $f;
        $up = dirname($dir);
        if ($up === $dir) break;
        $dir = $up;
    }
    return $path;
}

/** Alle config-waarden, met aanvullingen per (sub)domein ('per_host') en per map ('per_folder'). */
function cmb_config() {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = [];
    $p = cmb_config_path();
    if ($p !== '') {
        $c = include $p;
        if (is_array($c)) $cfg = $c;
    }
    $host = strtolower(preg_replace('/:\d+$/', '', (string)($_SERVER['HTTP_HOST'] ?? '')));
    if ($host !== '' && isset($cfg['per_host'][$host]) && is_array($cfg['per_host'][$host])) $cfg = array_merge($cfg, $cfg['per_host'][$host]);
    $folder = basename(CMB_ROOT);
    if (isset($cfg['per_folder'][$folder]) && is_array($cfg['per_folder'][$folder])) $cfg = array_merge($cfg, $cfg['per_folder'][$folder]);
    return $cfg;
}

/** NS-sleutel: eerst cmb-config.php (ns_key), anders de instellingen van het beheer. */
function cmb_ns_key($settings = null) {
    $k = trim((string)cmb_cfg('ns_key', ''));
    if ($k !== '') return $k;
    if ($settings === null) $settings = function_exists('cmb_load_settings_safe') ? cmb_load_settings_safe() : [];
    return trim((string)($settings['ov_key'] ?? ''));
}

function cmb_cfg($key, $default = null) {
    $c = cmb_config();
    return (isset($c[$key]) && $c[$key] !== '') ? $c[$key] : $default;
}

/** Mappen (altijd met / aan het eind). Standaard binnen deze installatie; via de config ergens anders (bv. voor een subdomein). */
function cmb_dir_from_cfg($key, $sub) {
    $d = (string)cmb_cfg($key, CMB_ROOT . '/' . $sub);
    return rtrim($d, '/\\') . '/';
}
/** Pulse: meldt aan alle open spelers dat er iets is gewijzigd (slides, achtergrond, ticker, ontwerp, streams).
 *  Elke bewaaractie in beheer roept dit aan; cmb-events.php geeft het door, de speler pakt het dan direct over. */
function cmb_pulse($part = 'all') {
    $dir = cmb_cache_dir();
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $fh = @fopen($dir . 'pulse.json', 'c+');
    if (!$fh) return;
    if (flock($fh, LOCK_EX)) {
        $o = json_decode((string)stream_get_contents($fh), true);
        $n = (is_array($o) ? intval($o['n'] ?? 0) : 0) + 1;
        ftruncate($fh, 0); rewind($fh);
        fwrite($fh, json_encode(['n' => $n, 'part' => (string)$part, 't' => time()]));
        fflush($fh); flock($fh, LOCK_UN);
    }
    fclose($fh);
}

function cmb_data_dir()   { return cmb_dir_from_cfg('data_dir', 'cmb-data'); }
function cmb_upload_dir() { return cmb_dir_from_cfg('uploads_dir', 'cmb-uploads'); }
function cmb_cache_dir()  { return cmb_dir_from_cfg('cache_dir', 'cmb-cache'); }

/** Inloggers die in beheer zijn aangemaakt of van wachtwoord veranderd (cmb-data/cmb-users.json, alleen hashes). */
function cmb_users_file() { return cmb_data_dir() . 'cmb-users.json'; }
function cmb_users_load() {
    $d = is_file(cmb_users_file()) ? json_decode((string)@file_get_contents(cmb_users_file()), true) : [];
    return (is_array($d) && is_array($d['users'] ?? null)) ? $d['users'] : [];
}
function cmb_users_save($users) {
    $dir = cmb_data_dir(); if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return @file_put_contents(cmb_users_file(), json_encode(['users' => $users], JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

/** Inlog controleren: eerst de inloggers uit beheer, anders gebruiker + wachtwoord-hash uit cmb-config.php. Zonder config de oude standaard. */
function cmb_check_login($user, $pass) {
    $user = (string)$user; $pass = (string)$pass;
    if ($user === '' || $pass === '') return false;
    $users = cmb_users_load();
    if (isset($users[$user]) && is_string($users[$user]) && $users[$user] !== '') return password_verify($pass, $users[$user]);
    $hasCfg = cmb_config_path() !== '';
    $cu = (string)cmb_cfg('admin_user', $hasCfg ? '' : 'chazzo');
    if ($cu === '' || !hash_equals($cu, $user)) return false;
    $hash = (string)cmb_cfg('admin_pass_hash', '');
    if ($hash !== '') return password_verify($pass, $hash);
    $plain = (string)cmb_cfg('admin_pass', $hasCfg ? '' : 'musicbox'); // liever admin_pass_hash gebruiken
    return $plain !== '' && hash_equals($plain, $pass);
}

/** Rand als plaatje: namen uit cmb-frames/ (alleen echte, veilige bestandsnamen), maximaal 6. */
function cmb_ring_list() {
    $o = [];
    foreach (@glob(CMB_ROOT . '/cmb-frames/*') ?: [] as $f) {
        $b = basename($f);
        if (is_file($f) && preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|webp)$/i', $b)) $o[] = $b;
    }
    sort($o);
    return $o;
}
/** Bovenste regel: plek 1-5 per onderdeel (0 = uit), of null voor de standaard indeling. Oude links/midden/rechts-keuze wordt omgezet. */
function cmb_top_slots(array $settings) {
    $els = ['logo', 'brand', 'weather', 'time', 'mail'];
    if (is_array($settings['top_slots'] ?? null)) {
        $o = [];
        foreach ($els as $i => $e) $o[$e] = max(0, min(7, intval($settings['top_slots'][$e] ?? ($i + 1))));
        // Nieuw: verkeersknop (-1 = naast de middenregel, zoals eerst) en WhatsApp-knop (standaard uit)
        $o['traffic'] = isset($settings['top_slots']['traffic']) ? max(-1, min(7, intval($settings['top_slots']['traffic']))) : -1;
        if ($o['traffic'] === -1) $o['traffic'] = $o['mail'] > 0 ? $o['mail'] : 7;   // oude keuze 'naast de middenregel': nu een eigen blok direct erachter
        $o['whatsapp'] = max(0, min(7, intval($settings['top_slots']['whatsapp'] ?? 0)));
        return $o;
    }
    if (is_array($settings['top_pos'] ?? null)) {
        $def = ['brand' => 'left', 'weather' => 'right', 'time' => 'right', 'mail' => 'mid'];
        $old = $settings['top_pos'] + $def;
        if ($old === $def) return null;
        $rank = ['left' => 0, 'mid' => 1, 'right' => 2];
        $tmp = [];
        foreach ($els as $i => $e) $tmp[$e] = [$rank[(string)$old[$e === 'logo' ? 'brand' : $e]] ?? 0, $i];
        uasort($tmp, function ($x, $y) { return $x <=> $y; });
        $n = 0; $o = [];
        foreach ($tmp as $e => $_) $o[$e] = ++$n;
        return $o;
    }
    return null;
}
/** Beheer op een subdomein: voorbeelden van uploads via de echte uploads-URL tonen (config 'uploads_url'). */
function cmb_admin_media_urls() {
    $base = (string)cmb_cfg('uploads_url', '');
    if ($base === '' || !preg_match('#^https?://#i', $base)) return;
    $base = rtrim($base, '/') . '/';
    ob_start(function ($html) use ($base) {
        $html = preg_replace('#\b(src|poster)=(["\'])cmb-uploads/#i', '$1=$2' . $base, $html);
        $js = '<script>window.CMB_UPLOADS_URL = ' . json_encode($base, JSON_UNESCAPED_SLASHES) . ';</script>';
        $pos = stripos($html, '</head>');
        return $pos === false ? $html : substr_replace($html, $js . '</head>', $pos, 7);
    });
}

// Emoji en andere 4-byte tekens: altijd UTF-8, ook als de server een andere standaard heeft
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) mb_internal_encoding('UTF-8');

/* ---------------- Fonts ---------------- */

function cmb_font_catalog() {
    static $cat = null;
    if ($cat === null) {
        $cat = require __DIR__ . '/cmb-fonts.php';
        if (!is_array($cat)) $cat = [];
    }
    return $cat;
}

/** name => "1. Naam (Omschrijving)" voor keuzelijsten */
function cmb_font_options() {
    $o = [];
    $i = 1;
    foreach (cmb_font_catalog() as $name => $d) {
        $o[$name] = $i . '. ' . $name . ' (' . $d[0] . ')';
        $i++;
    }
    return $o;
}

/** Zoekt recursief alle 'font' => '...' waarden in settings/slides */
function cmb_collect_fonts($data, &$found) {
    if (!is_array($data)) return;
    foreach ($data as $k => $v) {
        if ($k === 'font' && is_string($v) && $v !== '') {
            $found[$v] = true;
        } elseif (is_array($v)) {
            cmb_collect_fonts($v, $found);
        }
    }
}

function cmb_fonts_url($names) {
    $cat = cmb_font_catalog();
    $parts = [];
    foreach ($names as $n) {
        if (!isset($cat[$n]) || $cat[$n][1] === null) continue;
        $fam = 'family=' . str_replace(' ', '+', $n);
        if ($cat[$n][1] !== '') $fam .= ':wght@' . $cat[$n][1];
        $parts[] = $fam;
    }
    if (!$parts) return '';
    return 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap';
}

/** URL met alleen de lettertypen die in settings of slides echt gebruikt worden (+ basisfont) */
function cmb_used_fonts_url($settings, $slides) {
    $found = ['Montserrat' => true];
    cmb_collect_fonts($settings, $found);
    cmb_collect_fonts($slides, $found);
    return cmb_fonts_url(array_keys($found));
}

/* ---------------- Cover effecten ---------------- */

function cmb_effects_list() {
    static $l = null;
    if ($l === null) {
        $l = require __DIR__ . '/cmb-effects-list.php';
        if (!is_array($l)) $l = [];
    }
    return $l;
}

/* ---------------- Uploads ---------------- */

/**
 * Slaat een geüpload bestand op in cmb-uploads/ (alleen afbeeldingen en video's).
 * $maxBytes > 0: weigert grotere bestanden; $err krijgt dan een Nederlandse melding.
 * $prefix: voorvoegsel voor de bestandsnaam (voorkomt overschrijven tussen items).
 */
function cmb_upload($fileInputName, $uploadDir, $maxBytes = 0, &$err = null, $prefix = '') {
    $err = null;
    if (empty($_FILES[$fileInputName]['name'])) return null;
    $code = intval($_FILES[$fileInputName]['error'] ?? 0);
    if ($code === UPLOAD_ERR_NO_FILE) return null;
    if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
        $err = 'Bestand is groter dan de server toestaat (' . ini_get('upload_max_filesize') . '). Kies een kleiner bestand of vraag de hoster de limiet te verhogen.';
        return null;
    }
    if ($code !== UPLOAD_ERR_OK) { $err = 'Upload mislukt (foutcode ' . $code . ').'; return null; }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'ico', 'mp4', 'webm', 'ogv', 'm4v', 'mov', 'mkv'];
    $orig = basename($_FILES[$fileInputName]['name']);
    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) { $err = 'Bestandstype .' . $ext . ' is niet toegestaan (alleen afbeeldingen en video).'; return null; }
    if ($maxBytes > 0 && intval($_FILES[$fileInputName]['size'] ?? 0) > $maxBytes) {
        $err = 'Bestand is te groot (' . round(intval($_FILES[$fileInputName]['size']) / 1048576, 1) . ' MB, maximaal ' . round($maxBytes / 1048576) . ' MB).';
        return null;
    }
    $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($orig, PATHINFO_FILENAME));
    if ($base === '' || $base === null) $base = 'upload';
    $fn = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix) . $base . '.' . $ext;
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
    $ht = rtrim($uploadDir, '/') . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "# Geen scripts uitvoeren in uploads\n<FilesMatch \"\\.(php|phtml|phar|php[0-9])$\">\n    Require all denied\n</FilesMatch>\nOptions -ExecCGI\n");
    }
    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], rtrim($uploadDir, '/') . '/' . $fn)) {
        return 'cmb-uploads/' . $fn;
    }
    $err = 'Bestand kon niet worden opgeslagen (rechten van de map cmb-uploads?).';
    return null;
}

/* ---------------- Snapshots (automatische back-up bij opslaan) ---------------- */

function cmb_snapshot($file, $keep = 15) {
    if (!is_file($file)) return;
    $dir = cmb_cache_dir() . 'snapshots/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    $name = pathinfo($file, PATHINFO_FILENAME);
    @copy($file, $dir . $name . '__' . date('Ymd-His') . '.json');
    $all = glob($dir . $name . '__*.json') ?: [];
    sort($all);
    while (count($all) > $keep) {
        @unlink(array_shift($all));
    }
}

/* ---------------- Clips ---------------- */

/** Geeft ['kind' => file|youtube|vimeo|page|invalid, 'id' => string] */
function cmb_clip_info($url) {
    $url = trim((string)$url);
    if ($url === '') return ['kind' => 'invalid', 'id' => ''];
    if (preg_match('#(?:youtu\.be/|youtube\.com/(?:watch\?(?:[^\s]*&)?v=|embed/|shorts/|live/)|youtube-nocookie\.com/embed/)([A-Za-z0-9_-]{11})#i', $url, $m)) {
        return ['kind' => 'youtube', 'id' => $m[1]];
    }
    if (preg_match('#vimeo\.com/(?:video/)?(\d{5,12})#i', $url, $m)) {
        return ['kind' => 'vimeo', 'id' => $m[1]];
    }
    $isRelative = (strpos($url, 'cmb-uploads/') === 0);
    $isHttp = (bool)preg_match('#^https?://#i', $url);
    if (!$isRelative && !$isHttp) return ['kind' => 'invalid', 'id' => ''];
    if (preg_match('#\.(mp4|webm|ogv|m4v|mov|mkv|m3u8)(?:[?#].*)?$#i', $url)) return ['kind' => 'file', 'id' => ''];
    if ($isRelative) return ['kind' => 'invalid', 'id' => ''];
    return ['kind' => 'page', 'id' => ''];
}

/* ---------------- Streams (keuzelijst bij uitval) ---------------- */

function cmb_stream_choices($settings, $dataDir) {
    $choices = [];
    $add = function ($name, $url) use (&$choices) {
        $url = trim((string)$url);
        if ($url === '' || !preg_match('#^https?://#i', $url)) return;
        foreach ($choices as $c) {
            if ($c['url'] === $url) return;
        }
        $choices[] = ['name' => (string)$name, 'url' => $url];
    };
    $add(trim((string)($settings['stream1_name'] ?? '')) ?: 'Stream 1 (hoofd)', $settings['streamUrl'] ?? 'https://host10.onlineradyotv.net:7048/stream/43');
    $add(trim((string)($settings['stream2_name'] ?? '')) ?: 'Stream 2 (reserve)', $settings['fallbackUrl'] ?? '');
    foreach (preg_split('/\r\n|\r|\n/', (string)($settings['extra_streams'] ?? '')) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $p = explode('|', $line, 2);
        if (count($p) === 2) $add(trim($p[0]), $p[1]);
        else $add('Extra stream', $p[0]);
    }
    $pf = rtrim($dataDir, '/') . '/stream-presets.json';
    if (!is_file($pf) && is_file(CMB_ROOT . '/stream-presets.json')) $pf = CMB_ROOT . '/stream-presets.json';
    if (is_file($pf)) {
        $pr = json_decode(file_get_contents($pf), true);
        if (is_array($pr)) {
            foreach ($pr as $pn => $pd) {
                if (is_array($pd)) $add($pn, $pd['streamUrl'] ?? '');
            }
        }
    }
    return array_slice($choices, 0, 12);
}

/* ---------------- Frame-instellingen: gedeelde lijsten ---------------- */

/** Animatiemodi voor frames: sleutel => [volledige naam, korte naam, voorbeeld-HTML voor de tegel] */
function cmb_frame_modes() {
    $t = function ($cls, $pad = 3, $inner = '') {
        return '<div class="' . $cls . '" style="padding:' . $pad . 'px;">' . $inner . '</div>';
    };
    return [
        'chaser' => ['🐍 Draaiende Lichtslang (Neon Chaser)', 'Chaser',
            '<div class="frame-chaser-layer chaser-spin-layer" style="padding:3px;"><div style="position:absolute; inset:-50%; width:200%; height:200%; animation: chaserSpin 3s linear infinite; background: conic-gradient(from 0deg, var(--cyan) 0deg 90deg, transparent 100deg 360deg);"></div></div>'],
        'rain_curtain'   => ['🌧️ Neon Regengordijn (vallende drips)', 'Regengordijn', $t('frame-rain-curtain')],
        'snow_dots'      => ['❄️ Vallende Sneeuwpuntjes & Winter Glow', 'Sneeuw', $t('frame-snow-dots')],
        'golden_sparks'  => ['✨ Dansende Gouden Vonken (Sparkler)', 'Vonken', $t('frame-golden-sparks')],
        'neon_waterfall' => ['🌊 Neon Waterval Sweep (boven naar beneden)', 'Waterval', $t('frame-neon-waterfall')],
        'laser_radar'    => ['🎯 Laser Sweeper Scan (rand scanner)', 'Laser', $t('frame-laser-radar')],
        'breathing_aurora' => ['🌌 Ademend Noorderlicht (Aurora)', 'Aurora', $t('frame-breathing-aurora', 3, '<div class="frame-aurora-spin"></div>')],
        'cyber_matrix'   => ['🟩 Cyber Matrix Code Run (digitaal)', 'Matrix', $t('frame-cyber-matrix')],
        'pulse_glow'     => ['💫 Pulserend Neon (Breathe Pulse)', 'Pulse', $t('frame-pulse-glow')],
        'music_disco'    => ['🎵 Muziek Beat Disco (realtime pulse)', 'Disco', $t('frame-music-disco')],
        'random_spec'    => ['🎲 Random Kleuren Spectrum', 'Spectrum', $t('frame-random-spec')],
        'snow_edge'      => ['❄️ Sneeuwrand (vallende sneeuw langs de rand)', 'Sneeuwrand', $t('frame-snow-edge', 8)],
        'fire_edge'      => ['🔥 Vuurrand (oplaaiende vlammen)', 'Vuurrand', $t('frame-fire-edge', 4)],
        'rainbow_run'    => ['🌈 Regenboog Run (lopend kleurenspectrum)', 'Regenboog', $t('frame-rainbow-run')],
        'confetti_edge'  => ['🎊 Confetti Rand (feest)', 'Confetti', $t('frame-confetti-edge', 8)],
        'gold_shimmer'   => ['🏆 Gouden Glans (glinsterende lijst)', 'Gouden glans', $t('frame-gold-shimmer')],
        'electric_pulse' => ['⚡ Elektrische Puls (knipperende stroom)', 'Elektrisch', $t('frame-electric-pulse')],
        'none'           => ['❌ Geen geanimeerd effect (alleen vaste randen)', 'Geen', ''],
    ];
}

function cmb_frame_themes() {
    return [
        'cyan' => 'Neon Cyan / Blauw', 'gold' => 'Goud & Amber', 'magenta' => 'Cyber Magenta',
        'fire' => 'Vuur Rood & Oranje', 'emerald' => 'Emerald Groen & Mint', 'rainbow' => 'Regenboog Spectrum',
        'custom' => 'Eén eigen kleur', 'gradient' => '🎨 Eigen kleurverloop (twee kleuren)',
    ];
}

function cmb_gradient_angles() {
    return ['diag1' => 'Diagonaal ↗ (45°)', 'diag2' => 'Diagonaal ↘ (135°)', 'tb' => 'Boven → beneden', 'lr' => 'Links → rechts'];
}

function cmb_gradient_deg($mode) {
    return ['diag1' => '45deg', 'diag2' => '135deg', 'tb' => '180deg', 'lr' => '90deg'][$mode] ?? '135deg';
}

function cmb_side_styles() {
    return ['solid' => 'Doorlopend', 'dashed' => 'Streepjes', 'dotted' => 'Stippen', 'double' => 'Dubbel'];
}

/** Seizoensdecor: sleutel => [naam, emoji] */
function cmb_season_modes() {
    return [
        'none'      => ['Geen seizoensdecor', '—'],
        'auto'      => ['🗓️ Automatisch op datum', '🗓️'],
        'snow'      => ['❄️ Sneeuwlaagje & vlokken (winter)', '❄️'],
        'xmas'      => ['🎄 Kerstverlichting & ballen', '🎄'],
        'leaves'    => ['🍂 Herfstbladeren', '🍂'],
        'halloween' => ['🎃 Halloween pompoenen & vleermuizen', '🎃'],
        'valentine' => ['💖 Valentijn hartjes', '💖'],
        'spring'    => ['🌸 Lente bloesem', '🌸'],
        'easter'    => ['🐣 Pasen eitjes & kuikens', '🐣'],
        'orange'    => ['🧡 Oranje feest (vlaggetjes)', '🧡'],
    ];
}

function cmb_season_positions() {
    return ['bottom' => 'Onderkant', 'top' => 'Bovenkant', 'sides' => 'Zijkanten', 'all' => 'Rondom'];
}

/** Welk seizoen hoort bij de datum? (voor "Automatisch") */
function cmb_auto_season($ts = null) {
    $ts = $ts ?: time();
    $md = (int)date('md', $ts);
    if ($md >= 1201 || $md <= 106) return 'xmas';
    if ($md >= 1025 && $md <= 1101) return 'halloween';
    if ($md >= 1002 && $md < 1025) return 'leaves';
    if ($md >= 1102 && $md < 1201) return 'leaves';
    if ($md >= 207 && $md <= 214) return 'valentine';
    if ($md >= 420 && $md <= 427) return 'orange';
    if ($md >= 321 && $md < 420) return 'spring';
    if ($md >= 107 && $md <= 206) return 'snow';
    return 'none';
}

/* ---------------- Tekststijl-vakken (gedeeld door admin en studio) ---------------- */

/**
 * Een tekstveld met daaronder een duidelijk vak "Stijl & lettertype" (uitklapbaar, met levend voorbeeld).
 * De veldnamen blijven {prefix}_val, _font, _size, _color, _grad_mode, _grad_col2 en _shadow.
 */
function renderStyleSuite($prefix, $label, $currentVal, $currentStyle = [], $isMultiline = true, $withText = true) {
    $fonts = cmb_font_options();
    $s = array_merge([
        'font' => 'Montserrat', 'size' => '15px', 'color' => '#ffffff',
        'grad_mode' => 'none', 'grad_col2' => '#63f6ff', 'shadow' => '#000000'
    ], is_array($currentStyle) ? $currentStyle : []);
    $p = htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8');
    $rows = $isMultiline ? '2' : '1';
    $numSize = intval(preg_replace('/[^0-9]/', '', (string)($s['size'] ?? '15'))) ?: 15;
    $modes = ['none' => 'Geen (effen kleur)', 'tb' => 'Boven → beneden', 'lr' => 'Links → rechts', 'diag1' => 'Diagonaal ↗ (45°)', 'diag2' => 'Diagonaal ↘ (135°)'];

    ob_start();
    ?>
    <div class="cmb-style-card" data-prefix="<?= $p ?>">
        <div class="cmb-style-card-label"><?= htmlspecialchars($label) ?></div>
        <?php if ($withText): ?><textarea id="<?= $p ?>_val" name="<?= $p ?>_val" rows="<?= $rows ?>" placeholder="Tekst invoeren..."><?= htmlspecialchars((string)$currentVal) ?></textarea>
        <?php else: ?><div style="font-size:11px; color:#a8aac8; margin:2px 0 6px;"><?= htmlspecialchars((string)$currentVal) ?></div><?php endif; ?>
        <details class="cmb-style-panel">
            <summary>🎨 Stijl &amp; lettertype <span class="cmb-style-sample">Voorbeeld Aa</span></summary>
            <input type="hidden" name="<?= $p ?>_touched" value="0">
            <div class="cmb-style-grid">
                <div class="field wide"><label>Lettertype</label>
                    <select id="<?= $p ?>_font" name="<?= $p ?>_font">
                        <?php foreach ($fonts as $fk => $fv): ?>
                            <option value="<?= htmlspecialchars($fk, ENT_QUOTES) ?>" <?= $s['font'] === $fk ? 'selected' : '' ?>><?= htmlspecialchars($fv) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Grootte (px)</label><input type="number" id="<?= $p ?>_size" name="<?= $p ?>_size" value="<?= $numSize ?>" min="8" max="96"></div>
                <div class="field"><label>Kleur</label><input type="color" id="<?= $p ?>_color" name="<?= $p ?>_color" value="<?= htmlspecialchars($s['color']) ?>"></div>
                <div class="field wide"><label>Kleurverloop</label>
                    <select id="<?= $p ?>_grad_mode" name="<?= $p ?>_grad_mode">
                        <?php foreach ($modes as $mk => $mv): ?>
                            <option value="<?= $mk ?>" <?= $s['grad_mode'] === $mk ? 'selected' : '' ?>><?= $mv ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field"><label>Kleur 2 (verloop)</label><input type="color" id="<?= $p ?>_grad_col2" name="<?= $p ?>_grad_col2" value="<?= htmlspecialchars($s['grad_col2']) ?>"></div>
                <div class="field"><label>Schaduw</label>
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="color" id="<?= $p ?>_shadow" name="<?= $p ?>_shadow" value="<?= htmlspecialchars($s['shadow'] !== '' ? $s['shadow'] : '#000000') ?>">
                        <label style="text-transform:none; margin:0; display:flex; gap:4px; align-items:center; white-space:nowrap;"><input type="checkbox" id="<?= $p ?>_shadow_on" name="<?= $p ?>_shadow_on" value="1" <?= $s['shadow'] !== '' ? 'checked' : '' ?>> aan</label>
                    </div></div>
            </div>
        </details>
    </div>
    <?php
    return ob_get_clean();
}

function extractStyleSuite($prefix) {
    $rawSize = intval(preg_replace('/[^0-9]/', '', $_POST[$prefix . '_size'] ?? '15')) ?: 15;
    return [
        'font' => $_POST[$prefix . '_font'] ?? 'Montserrat',
        'size' => $rawSize . 'px',
        'color' => $_POST[$prefix . '_color'] ?? '#ffffff',
        'grad_mode' => $_POST[$prefix . '_grad_mode'] ?? 'none',
        'grad_col2' => $_POST[$prefix . '_grad_col2'] ?? '#63f6ff',
        // Schaduw uit = lege waarde; alle weergavecode slaat een lege schaduw over
        'shadow' => !empty($_POST[$prefix . '_shadow_on']) ? cmb_clean_color($_POST[$prefix . '_shadow'] ?? '', '#000000') : ''
    ];
}

function cmb_clean_color($v, $default) {
    $v = trim((string)$v);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $v) ? $v : $default;
}

/**
 * Widgets & RSS (item 8)
 * cmb_public_url: alleen http(s)-links naar het openbare internet (geen localhost / intern netwerk).
 */
function cmb_public_url($url) {
    $url = trim((string)$url);
    if (!preg_match('~^https?://~i', $url)) return false;
    $p = parse_url($url);
    if (empty($p['host'])) return false;
    $host = strtolower($p['host']);
    if ($host === 'localhost' || substr($host, -6) === '.local' || substr($host, -9) === '.internal') return false;
    $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : (@gethostbynamel($host) ?: []);
    if (!$ips) return false;
    foreach ($ips as $ip) {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return false;
    }
    return true;
}

/** Haalt een RSS/Atom-feed op (met cache) en geeft [ ['title'=>, 'desc'=>, 'link'=>, 'date'=>], ... ] terug. */
function cmb_rss_items($url, $cacheDir, $max = 5, $ttl = 900) {
    if (!function_exists('simplexml_load_string') || !cmb_public_url($url)) return [];
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cFile = rtrim($cacheDir, '/') . '/rss_' . md5($url) . '.json';
    if (file_exists($cFile) && empty($GLOBALS['cmb_rss_refreshing'])) {
        $c = json_decode((string)@file_get_contents($cFile), true);
        if (is_array($c) && $c) {
            if ((time() - filemtime($cFile)) < $ttl) return array_slice($c, 0, $max);
            // Verlopen: meteen de vorige versie tonen (de speler wacht niet) en pas NA het antwoord vernieuwen
            static $queued = [];
            if (empty($queued[$url])) {
                $queued[$url] = true;
                @touch($cFile); // voorkomt dat andere bezoekers tegelijk gaan vernieuwen
                register_shutdown_function(function () use ($url, $cacheDir, $max, $ttl, $cFile) {
                    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
                    $GLOBALS['cmb_rss_refreshing'] = true;
                    @touch($cFile, time() - $ttl - 1);
                    cmb_rss_items($url, $cacheDir, $max, $ttl);
                });
            }
            return array_slice($c, 0, $max);
        }
    }
    $raw = false;
    $ctx = stream_context_create(['http' => ['timeout' => 4, 'user_agent' => 'CMB-RSS/1.0', 'follow_location' => 0], 'ssl' => ['verify_peer' => true]]);
    $raw = @file_get_contents($url, false, $ctx, 0, 1500000);
    $items = [];
    if ($raw) {
        $prev = libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_use_internal_errors($prev);
        if ($xml) {
            $nodes = [];
            if (isset($xml->channel->item)) { foreach ($xml->channel->item as $n) $nodes[] = $n; }
            elseif (isset($xml->entry)) { foreach ($xml->entry as $n) $nodes[] = $n; }
            foreach (array_slice($nodes, 0, 15) as $n) {
                $title = trim(html_entity_decode(strip_tags((string)$n->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $desc = (string)($n->description ?? $n->summary ?? '');
                $desc = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($desc), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
                if (mb_strlen($desc) > 220) $desc = mb_substr($desc, 0, 217) . '…';
                if ($title !== '') $items[] = ['title' => $title, 'desc' => $desc, 'link' => '', 'date' => (string)($n->pubDate ?? $n->updated ?? '')];
            }
        }
    }
    if ($items) { $oldC = file_exists($cFile) ? @file_get_contents($cFile) : ''; $newC = json_encode($items); @file_put_contents($cFile, $newC); if ($oldC !== $newC) cmb_pulse('ticker'); return array_slice($items, 0, $max); }
    // Mislukt? Geef de oude cache terug zodat de speler niet leeg raakt.
    if (file_exists($cFile)) {
        @touch($cFile, time() - $ttl + 120); // feed even onbereikbaar: over 2 minuten opnieuw proberen
        $c = json_decode((string)@file_get_contents($cFile), true);
        if (is_array($c)) return array_slice($c, 0, $max);
    }
    return [];
}

/** Bouwt de HTML van een widget-slide. Alles wat van buiten komt wordt geëscaped of in een sandbox-iframe gezet. */
function cmb_widget_html($w, $cacheDir) {
    $kind = $w['kind'] ?? 'rss';
    if ($kind === 'rss') {
        $items = cmb_rss_items($w['url'] ?? '', $cacheDir, max(1, min(8, intval($w['max'] ?? 4))));
        if (!$items) return '<div class="cmb-rss-list"><div class="cmb-rss-item">Geen nieuws beschikbaar.</div></div>';
        $h = '<div class="cmb-rss-list">';
        foreach ($items as $it) {
            $h .= '<div class="cmb-rss-item"><div class="cmb-rss-title">' . htmlspecialchars($it['title']) . '</div>';
            if (!empty($w['show_desc']) && $it['desc'] !== '') $h .= '<div class="cmb-rss-desc">' . htmlspecialchars($it['desc']) . '</div>';
            $h .= '</div>';
        }
        return $h . '</div>';
    }
    if ($kind === 'embed') {
        $u = trim((string)($w['url'] ?? ''));
        if (!preg_match('~^https://~i', $u)) return '';
        return '<div class="cmb-widget-wrap"><iframe src="' . htmlspecialchars($u) . '" style="width:100%;height:100%;min-height:220px;border:0;border-radius:12px;" loading="lazy" referrerpolicy="no-referrer" sandbox="allow-scripts allow-same-origin allow-popups"></iframe></div>';
    }
    if ($kind === 'html') {
        $code = (string)($w['html'] ?? '');
        if (trim($code) === '') return '';
        // Eigen code draait afgeschermd (geen toegang tot de speler of beheer-cookies)
        return '<div class="cmb-widget-wrap"><iframe srcdoc="' . htmlspecialchars($code, ENT_QUOTES) . '" style="width:100%;height:100%;min-height:220px;border:0;border-radius:12px;" sandbox="allow-scripts"></iframe></div>';
    }
    return '';
}


/**
 * Grote formulieren (zoals Slides, ruim 1300 velden) worden door de browser als één pakketje (cmb_payload)
 * verstuurd. Veel servers kappen anders alles boven 1000 velden (max_input_vars) stilletjes af, waardoor
 * wijzigingen "niet werken". Hier pakken we dat pakketje uit zodat de rest van de code gewoon $_POST gebruikt.
 */
/** Datum-tijd uit een formulier (JJJJ-MM-DDTUU:MM) netjes maken; ongeldig = leeg. */
function cmb_clean_dt($v) {
    $v = trim((string)$v);
    if ($v === '' || !strtotime($v)) return '';
    return date('Y-m-d\TH:i', strtotime($v));
}

/** Is een (tijdelijke) slide nu zichtbaar? show_from/show_until leeg = geen grens. */
function cmb_slide_in_window($s, $now = null) {
    $now = $now ?? time();
    if (!empty($s['show_from']) && ($t = strtotime($s['show_from'])) && $now < $t) return false;
    if (!empty($s['show_until']) && ($t = strtotime($s['show_until'])) && $now > $t) return false;
    return true;
}

function cmb_unpack_payload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['cmb_payload'])) return;
    $data = json_decode((string)$_POST['cmb_payload'], true);
    unset($_POST['cmb_payload']);
    if (!is_array($data)) return;
    foreach ($data as $k => $v) {
        if (is_string($k) && (is_string($v) || is_numeric($v))) $_POST[$k] = (string)$v;
    }
}

// Thema's (kleurthema's, eigen thema's en planning)
require_once __DIR__ . '/cmb-themes.php';
// Mail bij uitval van een stream
require_once __DIR__ . '/cmb-streamwatch.php';


/** PL-balk: het mailadres voor het envelopje en de privacy-tekst (los van de middenregel). */
function cmb_pl_mail(array $settings) {
    $m = trim((string)($settings['pl_mail'] ?? ''));
    if ($m === '' && preg_match('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', (string)($settings['studio_mail'] ?? ''), $mm)) $m = $mm[0];
    if ($m === '') $m = 'mail@chazzo.com';
    return preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $m) ? $m : 'mail@chazzo.com';
}

/** PL-balk: de 10 vrije blokjes (0-7 smal, 8-9 breed), alleen de zichtbare, met schone waarden. */
function cmb_pl_blocks(array $settings) {
    $out = [];
    $raw = is_array($settings['pl_blocks'] ?? null) ? $settings['pl_blocks'] : [];
    for ($i = 0; $i < 10; $i++) {
        $b = is_array($raw[$i] ?? null) ? $raw[$i] : [];
        $wide = $i >= 8;
        $img = trim((string)($b['img'] ?? ''));
        if ($img !== '' && !preg_match('~^(https://|cmb-uploads/|cmb-assets/|/)[^\s"\'<>]*$~i', $img)) $img = '';
        $txt = $wide ? mb_substr(trim(strip_tags((string)($b['text'] ?? ''))), 0, 24) : '';
        if ($img === '' && $txt === '') continue; // geen plaatje (en bij breed ook geen tekst): geen zichtbare plaats
        $url = trim((string)($b['url'] ?? ''));
        if (!preg_match('~^https?://[^\s"\'<>]+$~i', $url)) $url = '';
        $out[] = ['wide' => $wide, 'img' => $img, 'text' => $txt, 'url' => $url];
    }
    return $out;
}
