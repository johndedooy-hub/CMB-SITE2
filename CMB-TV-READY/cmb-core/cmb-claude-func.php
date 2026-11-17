<?php
/** CMB Claude-assistent: hulpfuncties en tools. Geladen door cmb-claude.php. */
if (!defined('CMB_PART')) { http_response_code(403); exit; }
/* ---------- Hulpfuncties ---------- */
function out($arr, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function allowed_path($rel, $list) {
    $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
    if (in_array($rel, $list, true)) return $rel;
    // Alleen een bestandsnaam (zonder map) opgegeven? Zoek hem op als hij uniek is.
    $hits = array_values(array_filter($list, function ($x) use ($rel) { return basename($x) === basename($rel); }));
    return count($hits) === 1 ? $hits[0] : null;
}

/** Projectpad -> echt pad op de server (cmb-data kan via cmb-config.php ergens anders staan). */
function cmb_claude_abs($rel) {
    if (strpos($rel, 'cmb-data/') === 0) return cmb_data_dir() . substr($rel, 9);
    return CMB_ROOT . '/' . $rel;
}

function make_backup($rel, $bakDir) {
    $src = cmb_claude_abs($rel);
    if (!is_file($src)) return '';
    $name = date('Ymd-His') . '-' . bin2hex(random_bytes(2)) . '__' . str_replace('/', '__', $rel);
    return copy($src, $bakDir . $name) ? $name : '';
}

function sensitive_key($k) {
    return (bool)preg_match('/key|pass|secret|token|wachtwoord/i', (string)$k);
}

function strip_sensitive($arr) {
    if (!is_array($arr)) return $arr;
    $o = [];
    foreach ($arr as $k => $v) {
        if (sensitive_key($k)) continue;
        $o[$k] = strip_sensitive($v);
    }
    return $o;
}

function is_assoc($a) {
    return is_array($a) && $a !== [] && array_keys($a) !== range(0, count($a) - 1);
}

function merge_patch($base, $patch, &$changed, $path = '') {
    foreach ($patch as $k => $v) {
        if (sensitive_key($k)) continue;
        $p = $path === '' ? (string)$k : $path . '.' . $k;
        if (is_assoc($v) && isset($base[$k]) && is_assoc($base[$k])) {
            $base[$k] = merge_patch($base[$k], $v, $changed, $p);
        } else {
            $base[$k] = $v;
            $changed[] = $p;
        }
    }
    return $base;
}

function load_pending($id, $pendDir) {
    if (!preg_match('/^[a-f0-9]{8}$/', (string)$id)) return null;
    $f = $pendDir . $id . '.json';
    if (!is_file($f)) return null;
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : null;
}

function save_pending($rec, $pendDir) {
    file_put_contents($pendDir . $rec['id'] . '.json', json_encode($rec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), LOCK_EX);
}

function exec_available() {
    if (!function_exists('exec')) return false;
    $dis = array_map('trim', explode(',', (string)ini_get('disable_functions')));
    return !in_array('exec', $dis, true);
}

/** Geeft [bool ok, string melding, bool gecontroleerd] */
function lint_php($code) {
    if (!exec_available()) return [true, 'PHP-controle niet beschikbaar op deze server', false];
    $tmp = tempnam(sys_get_temp_dir(), 'cmb') . '.php';
    file_put_contents($tmp, $code);
    $outLines = [];
    $rc = 0;
    @exec('php -l ' . escapeshellarg($tmp) . ' 2>&1', $outLines, $rc);
    @unlink($tmp);
    if ($rc === 127 || $rc === 126) return [true, 'php-commando niet gevonden; controle overgeslagen', false];
    if ($rc !== 0) return [false, implode("\n", $outLines), true];
    return [true, 'Syntaxis OK', true];
}

/** Past een voorstel toe op de tekst. Geeft [nieuwe tekst|null, foutmelding] */
function apply_edit_to_text($current, $old, $new) {
    if ($old === '') {
        return [rtrim($current, "\n") . ($current === '' ? '' : "\n") . $new . "\n", ''];
    }
    $count = substr_count($current, $old);
    if ($count === 0) return [null, 'De te vervangen tekst komt niet (meer) voor in het bestand.'];
    if ($count > 1)  return [null, 'De te vervangen tekst komt ' . $count . 'x voor; voorstel is niet uniek.'];
    return [str_replace($old, $new, $current), ''];
}

/* ---------- Tools voor Claude ---------- */
$tools = [
    [
        'name' => 'get_settings',
        'description' => 'Geeft de huidige CMB-instellingen (cmb-settings.json) terug, zonder gevoelige velden.',
        'input_schema' => ['type' => 'object', 'properties' => new stdClass()],
    ],
    [
        'name' => 'update_settings',
        'description' => 'Voert direct een wijziging door in cmb-settings.json (met automatische back-up). Geef alleen de velden die veranderen; geneste objecten worden samengevoegd. Gebruik dit voor instellingen zoals lighting_left_frame, art_effect, kleuren en teksten. Velden met key/pass/secret/token in de naam worden genegeerd.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['patch' => ['type' => 'object', 'description' => 'JSON-patch met de te wijzigen instellingen']],
            'required' => ['patch'],
        ],
    ],
    [
        'name' => 'get_slides',
        'description' => 'Geeft een overzicht van alle slides uit cmb-slides.json: news (RSS-nieuws, nr 1-5), weather (weer, nr 1-5), custom (vrije slides, nr 1-15) en de stream-vertraging. Lees dit altijd voordat je update_slides gebruikt.',
        'input_schema' => ['type' => 'object', 'properties' => new stdClass()],
    ],
    [
        'name' => 'update_slides',
        'description' => 'Wijzigt slides direct in cmb-slides.json (met back-up). Geef alleen wat verandert, per groep en nummer, bv. {"custom":{"3":{"active":true,"title_big":"Kermis","body":"Vanavond live","sec":10,"show_until":"2026-10-20T23:59"}}}. '
            . 'Groepen: news 1-5 (velden: active, feed_url, koptitel, sec, order, media_url, blur, darken), '
            . 'weather 1-5 (active, city, lat, lon, koptitel, praatje, ticker_active, sec, order, media_url, blur, darken), '
            . 'custom 1-15 (active, title_big, title_sub, body, sec, order, media_url, blur, darken, track_match, track_only, '
            . 'sched_days [ma,di,wo,do,vr,za,zo], sched_hours [0-23], sched_mins ["59"], sched_secs [0], precise (true = precies op minuut:seconde starten), '
            . 'show_from en show_until (tijdelijke slide, formaat JJJJ-MM-DDTUU:MM, leeg = altijd)). '
            . 'Een vrije slide die leeg is (active false, geen titel) mag je gebruiken voor iets nieuws; overschrijf geen bestaande slide zonder dat de gebruiker dat vraagt.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['patch' => ['type' => 'object', 'description' => 'Wijzigingen per groep (news/weather/custom) en nummer']],
            'required' => ['patch'],
        ],
    ],
    [
        'name' => 'read_file',
        'description' => 'Leest een projectbestand (whitelist). Gebruik start_line/end_line voor grote bestanden. Zonder bereik worden de eerste 40000 tekens teruggegeven.',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string'],
                'start_line' => ['type' => 'integer'],
                'end_line' => ['type' => 'integer'],
            ],
            'required' => ['path'],
        ],
    ],
    [
        'name' => 'search_files',
        'description' => 'Zoekt (hoofdletterongevoelig) een tekst in alle leesbare projectbestanden en geeft bestand:regel terug.',
        'input_schema' => [
            'type' => 'object',
            'properties' => ['query' => ['type' => 'string']],
            'required' => ['query'],
        ],
    ],
    [
        'name' => 'propose_file_edit',
        'description' => 'Stelt een wijziging in een projectbestand voor. Wordt NIET direct doorgevoerd: de gebruiker moet het voorstel goedkeuren. old_str moet exact en uniek in het bestand voorkomen (lees het bestand eerst). Voor cmb-custom.css (nieuw of bestaand): laat old_str leeg om new_str onderaan toe te voegen.',
        'input_schema' => [
            'type' => 'object',
            'properties' => [
                'path' => ['type' => 'string'],
                'old_str' => ['type' => 'string'],
                'new_str' => ['type' => 'string'],
                'explanation' => ['type' => 'string', 'description' => 'Korte uitleg in het Nederlands van wat dit doet'],
            ],
            'required' => ['path', 'old_str', 'new_str', 'explanation'],
        ],
    ],
];

function run_tool($name, $in, $ctx) {
    $root = CMB_ROOT;
    switch ($name) {
        case 'get_settings':
            $s = is_file($ctx['settingsFile']) ? json_decode(file_get_contents($ctx['settingsFile']), true) : [];
            return json_encode(strip_sensitive($s ?: []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        case 'update_settings':
            $patch = $in['patch'] ?? null;
            if (!is_array($patch) || !$patch) return 'Fout: lege of ongeldige patch.';
            $cur = is_file($ctx['settingsFile']) ? (json_decode(file_get_contents($ctx['settingsFile']), true) ?: []) : [];
            make_backup('cmb-data/cmb-settings.json', $ctx['bakDir']);
            $changed = [];
            $new = merge_patch($cur, $patch, $changed);
            if (!$changed) return 'Niets gewijzigd (geblokkeerde of lege velden).';
            $ok = file_put_contents($ctx['settingsFile'], json_encode($new, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
            if ($ok === false) return 'Fout: kon cmb-settings.json niet schrijven.';
            cmb_pulse('design');
            $GLOBALS['cmb_settings_changed'] = array_merge($GLOBALS['cmb_settings_changed'] ?? [], $changed);
            return 'Gewijzigd: ' . implode(', ', $changed);

        case 'get_slides':
            $sl = is_file($ctx['slidesFile']) ? (json_decode(file_get_contents($ctx['slidesFile']), true) ?: []) : [];
            $view = ['stream_delay_sec' => $sl['timing']['delay'] ?? 0];
            foreach (['news' => 5, 'weather' => 5, 'custom' => 15] as $grp => $max) {
                for ($i = 1; $i <= $max; $i++) {
                    $it = $sl[$grp][$i] ?? null;
                    if (!is_array($it)) { $view[$grp][$i] = '(leeg)'; continue; }
                    foreach ($it as $k => $v) if (strpos((string)$k, 'style_') === 0) unset($it[$k]); // opmaak weglaten (scheelt veel tekst)
                    $view[$grp][$i] = $it;
                }
            }
            return json_encode($view, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        case 'update_slides':
            $patch = $in['patch'] ?? null;
            if (!is_array($patch) || !$patch) return 'Fout: lege of ongeldige patch.';
            $limits = ['news' => 5, 'weather' => 5, 'custom' => 15];
            $fields = [
                'news' => ['active', 'feed_url', 'koptitel', 'sec', 'order', 'media_url', 'blur', 'darken'],
                'weather' => ['active', 'city', 'lat', 'lon', 'koptitel', 'praatje', 'ticker_active', 'sec', 'order', 'media_url', 'blur', 'darken'],
                'custom' => ['active', 'title_big', 'title_sub', 'foot', 'body', 'sec', 'order', 'media_url', 'blur', 'darken', 'track_match', 'track_only',
                             'sched_days', 'sched_hours', 'sched_mins', 'sched_secs', 'precise', 'show_from', 'show_until'],
            ];
            $sl = is_file($ctx['slidesFile']) ? (json_decode(file_get_contents($ctx['slidesFile']), true) ?: []) : [];
            $changed = [];
            foreach ($patch as $grp => $items) {
                if (!isset($limits[$grp])) return "Fout: groep '{$grp}' kan niet via de assistent (alleen news, weather, custom).";
                if (!is_array($items)) return "Fout: {$grp} moet een object met nummers zijn.";
                foreach ($items as $nr => $vals) {
                    $nr = intval($nr);
                    if ($nr < 1 || $nr > $limits[$grp]) return "Fout: {$grp} {$nr} bestaat niet (1-{$limits[$grp]}).";
                    if (!is_array($vals)) return "Fout: {$grp} {$nr} moet een object zijn.";
                    $cur = (isset($sl[$grp][$nr]) && is_array($sl[$grp][$nr])) ? $sl[$grp][$nr] : [];
                    // Ontbrekende velden aanvullen, zodat Studio de slide netjes kan tonen
                    $defs = [
                        'news' => ['active' => false, 'order' => $nr, 'sec' => 10, 'feed_url' => '', 'koptitel' => 'NIEUWS', 'media_url' => '', 'blur' => 0, 'darken' => 30],
                        'weather' => ['active' => false, 'order' => $nr + 5, 'sec' => 8, 'city' => '', 'lat' => '', 'lon' => '', 'koptitel' => 'VERWACHTING', 'praatje' => '', 'ticker_active' => false, 'media_url' => '', 'blur' => 0, 'darken' => 20],
                        'custom' => ['active' => false, 'order' => $nr + 10, 'sec' => 8, 'title_big' => '', 'title_sub' => '', 'body' => '', 'media_url' => '', 'blur' => 0, 'darken' => 30,
                                     'track_match' => '', 'track_only' => false, 'sched_days' => ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo'], 'sched_hours' => range(0, 23),
                                     'sched_mins' => [], 'sched_secs' => [], 'precise' => false, 'show_from' => '', 'show_until' => ''],
                    ];
                    $cur = array_merge($defs[$grp], $cur);
                    foreach ($vals as $k => $v) {
                        if (!in_array($k, $fields[$grp], true)) continue;
                        if (in_array($k, ['active', 'track_only', 'precise', 'ticker_active'], true)) $v = !empty($v);
                        elseif (in_array($k, ['sec', 'order', 'blur', 'darken'], true)) $v = intval($v);
                        elseif (in_array($k, ['sched_days', 'sched_hours', 'sched_mins', 'sched_secs'], true)) $v = array_values((array)$v);
                        elseif (in_array($k, ['show_from', 'show_until'], true)) $v = ($v && strtotime((string)$v)) ? date('Y-m-d\TH:i', strtotime((string)$v)) : '';
                        elseif (in_array($k, ['media_url', 'feed_url'], true)) $v = preg_match('#^(https?://|cmb-uploads/)#i', (string)$v) ? (string)$v : '';
                        else $v = (string)$v;
                        $cur[$k] = $v;
                        $changed[] = "{$grp} {$nr}.{$k}";
                    }
                    if ($grp === 'custom' && isset($cur['sched_hours'])) $cur['sched_hours'] = array_values(array_filter(array_map('intval', $cur['sched_hours']), function ($h) { return $h >= 0 && $h <= 23; }));
                    $sl[$grp][$nr] = $cur;
                }
            }
            if (!$changed) return 'Niets gewijzigd (onbekende velden).';
            make_backup('cmb-data/cmb-slides.json', $ctx['bakDir']);
            foreach (array_keys($limits) as $grp) if (isset($sl[$grp]) && is_array($sl[$grp])) ksort($sl[$grp]);
            if (file_put_contents($ctx['slidesFile'], json_encode($sl, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) === false) return 'Fout: kon cmb-slides.json niet schrijven.';
            cmb_pulse('slides');
            $GLOBALS['cmb_settings_changed'] = array_merge($GLOBALS['cmb_settings_changed'] ?? [], $changed);
            return 'Slides gewijzigd: ' . implode(', ', $changed) . '. De speler neemt dit vanzelf over.';

        case 'read_file':
            $rel = allowed_path($in['path'] ?? '', $ctx['readable']);
            if (!$rel) return 'Fout: bestand niet toegestaan. Toegestaan: ' . implode(', ', $ctx['readable']);
            $f = cmb_claude_abs($rel);
            if (!is_file($f)) return 'Bestand bestaat nog niet.';
            $txt = file_get_contents($f);
            $lines = explode("\n", $txt);
            if (isset($in['start_line']) || isset($in['end_line'])) {
                $s = max(1, (int)($in['start_line'] ?? 1));
                $e = min(count($lines), (int)($in['end_line'] ?? ($s + 199)));
                $slice = array_slice($lines, $s - 1, $e - $s + 1);
                $o = [];
                foreach ($slice as $i => $l) $o[] = ($s + $i) . "\t" . $l;
                return "[{$rel}, regels {$s}-{$e} van " . count($lines) . "]\n" . implode("\n", $o);
            }
            if (strlen($txt) > 40000) {
                return "[{$rel}: eerste 40000 van " . strlen($txt) . " tekens, " . count($lines) . " regels; gebruik start_line/end_line voor de rest]\n" . substr($txt, 0, 40000);
            }
            return $txt;

        case 'search_files':
            $q = (string)($in['query'] ?? '');
            if (strlen($q) < 2) return 'Fout: zoekterm te kort.';
            $hits = [];
            foreach ($ctx['readable'] as $rel) {
                $f = cmb_claude_abs($rel);
                if (!is_file($f)) continue;
                foreach (explode("\n", file_get_contents($f)) as $i => $l) {
                    if (stripos($l, $q) !== false) {
                        $hits[] = $rel . ':' . ($i + 1) . ': ' . mb_substr(trim($l), 0, 160);
                        if (count($hits) >= 40) break 2;
                    }
                }
            }
            return $hits ? implode("\n", $hits) : 'Geen resultaten.';

        case 'propose_file_edit':
            $rel = allowed_path($in['path'] ?? '', $ctx['editable']);
            if (!$rel) return 'Fout: bestand niet wijzigbaar. Toegestaan: ' . implode(', ', $ctx['editable']);
            $old = (string)($in['old_str'] ?? '');
            $new = (string)($in['new_str'] ?? '');
            $f = cmb_claude_abs($rel);
            $exists = is_file($f);
            if (!$exists && !($rel === 'cmb-custom.css' && $old === '')) {
                return 'Fout: bestand bestaat niet; alleen cmb-custom.css mag nieuw worden aangemaakt (laat old_str leeg).';
            }
            if ($old === '' && $rel !== 'cmb-custom.css') {
                return 'Fout: old_str mag alleen leeg zijn voor cmb-custom.css.';
            }
            $cur = $exists ? file_get_contents($f) : '';
            list($res, $err) = apply_edit_to_text($cur, $old, $new);
            if ($res === null) return 'Fout: ' . $err . ' Lees het bestand opnieuw en probeer een unieke fragment.';
            $id = bin2hex(random_bytes(4));
            save_pending([
                'id' => $id, 'path' => $rel, 'old' => $old, 'new' => $new,
                'explanation' => (string)($in['explanation'] ?? ''),
                'status' => 'pending', 'created' => date('c'),
            ], $ctx['pendDir']);
            $GLOBALS['cmb_new_proposals'][] = $id;
            return "Voorstel {$id} voor {$rel} is klaargezet; de gebruiker moet het nog goedkeuren.";
    }
    return 'Onbekende tool.';
}

/* ---------- Gemini (gratis alternatief via Google AI Studio) ---------- */
/** Zet de Claude-tooldefinities om naar Gemini functionDeclarations. Objecten zonder vaste velden gaan als JSON-tekst. */
function gemini_tools($tools) {
    $decl = [];
    foreach ($tools as $t) {
        $props = (array)($t['input_schema']['properties'] ?? []);
        $d = ['name' => $t['name'], 'description' => $t['description']];
        if ($props) {
            $p = [];
            foreach ($props as $k => $v) {
                $v = (array)$v;
                if (($v['type'] ?? '') === 'object') {
                    $p[$k] = ['type' => 'string', 'description' => trim(($v['description'] ?? '') . ' (als JSON-tekst)')];
                } else {
                    $p[$k] = $v;
                }
            }
            $d['parameters'] = ['type' => 'object', 'properties' => $p, 'required' => $t['input_schema']['required'] ?? []];
        }
        $decl[] = $d;
    }
    return [['functionDeclarations' => $decl]];
}

/** Gemini-argumenten terugzetten: velden die als JSON-tekst kwamen weer als object. */
function gemini_args($tools, $name, $args) {
    $args = is_array($args) ? $args : [];
    foreach ($tools as $t) {
        if ($t['name'] !== $name) continue;
        foreach ((array)($t['input_schema']['properties'] ?? []) as $k => $v) {
            $v = (array)$v;
            if (($v['type'] ?? '') === 'object' && isset($args[$k]) && is_string($args[$k])) {
                $j = json_decode($args[$k], true);
                if (is_array($j)) $args[$k] = $j;
            }
        }
    }
    return $args;
}

