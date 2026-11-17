<?php
/**
 * CHAZZO MUSIC BOX (CMB) - CLAUDE ONTWERP-ASSISTENT
 * Bestandsnaam: cmb-claude.php
 *
 * Chat met Claude (Anthropic API) die binnen de CMB-bestandsstructuur kan meedenken:
 *  - instellingen (cmb-settings.json) direct aanpassen, met automatische back-up
 *  - bestanden lezen en doorzoeken (alleen een vaste whitelist)
 *  - code-wijzigingen VOORSTELLEN; die worden pas doorgevoerd als jij op "Toepassen" klikt
 *    (met back-up, PHP-syntaxcontrole indien mogelijk, en een Ongedaan-maken knop)
 *
 * API-sleutel: zet ANTHROPIC_API_KEY als omgevingsvariabele, of maak
 * cmb-data/anthropic-key.php aan (zie cmb-data/anthropic-key.example.php).
 * Alternatief: OpenRouter (OPENROUTER_API_KEY of cmb-data/openrouter-key.php, zie openrouter-key.example.php; model via CMB_OPENROUTER_MODEL).
 * Gratis alternatief: Google Gemini. Zet GEMINI_API_KEY of maak cmb-data/gemini-key.php aan
 * (zie cmb-data/gemini-key.example.php). Model wijzigen: omgevingsvariabele CMB_GEMINI_MODEL.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';

$isApi = isset($_GET['action']);

if (empty($_SESSION['cmb_auth'])) {
    if ($isApi) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Niet ingelogd. Log eerst in via cmb-admin.php.']);
    } else {
        header('Location: cmb-admin.php');
    }
    exit;
}

if (empty($_SESSION['cmb_claude_csrf'])) {
    $_SESSION['cmb_claude_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['cmb_claude_csrf'];

$root      = __DIR__;
$dataDir   = cmb_data_dir();
$cacheDir  = cmb_cache_dir();
$bakDir    = $cacheDir . 'claude-backups/';
$pendDir   = $cacheDir . 'claude-pending/';
$settingsFile = $dataDir . 'cmb-settings.json';

foreach ([$cacheDir, $bakDir, $pendDir] as $d) {
    if (!is_dir($d)) @mkdir($d, 0755, true);
}

/* ---------- Configuratie ---------- */
function cmb_api_key() {
    $k = getenv('ANTHROPIC_API_KEY') ?: cmb_cfg('anthropic_key', '');
    if ($k) return trim($k);
    $f = cmb_data_dir() . 'anthropic-key.php';
    if (is_file($f)) {
        $v = include $f;
        if (is_string($v)) return trim($v);
    }
    return '';
}
$model = getenv('CMB_CLAUDE_MODEL') ?: 'claude-sonnet-5-5';

/** Gratis alternatief: Google Gemini-sleutel (omgevingsvariabele, sleutelbestand of oude instelling). */
function cmb_gemini_key() {
    $k = getenv('GEMINI_API_KEY') ?: cmb_cfg('gemini_key', '');
    if ($k) return trim($k);
    $f = cmb_data_dir() . 'gemini-key.php';
    if (is_file($f)) {
        $v = include $f;
        if (is_string($v) && strpos($v, '...') === false) return trim($v);
    }
    $sf = cmb_data_dir() . 'cmb-settings.json';
    $s = is_file($sf) ? (json_decode(file_get_contents($sf), true) ?: []) : [];
    return trim((string)($s['gemini_api_key'] ?? ''));
}
$geminiModel = getenv('CMB_GEMINI_MODEL') ?: cmb_cfg('gemini_model', 'gemini-flash-latest');

/** OpenRouter-sleutel (omgevingsvariabele, cmb-config.php of cmb-data/openrouter-key.php). */
function cmb_openrouter_key() {
    $k = getenv('OPENROUTER_API_KEY') ?: cmb_cfg('openrouter_key', '');
    if ($k) return trim($k);
    $f = cmb_data_dir() . 'openrouter-key.php';
    if (is_file($f)) {
        $v = include $f;
        if (is_string($v) && strpos($v, '...') === false) return trim($v);
    }
    return '';
}
$openrouterModel = getenv('CMB_OPENROUTER_MODEL') ?: cmb_cfg('openrouter_model', 'openrouter/auto');

// Bestanden die Claude mag lezen en (na jouw goedkeuring) mag wijzigen.
$editable = [
    'cmb-assets/cmb-effects.css', 'cmb-custom.css', 'cmb-assets/cmb-admin-ui.js', 'cmb-assets/cmb-admin.css', 'cmb-assets/cmb-player.js', 'cmb-assets/cmb-studio.js',
    'index.php', 'default.php', 'get_track.php', 'cmb-admin.php', 'cmb-studio.php',
    'cmb-player/header.php', 'cmb-player/footer.php', 'cmb-player/cmb-header-func.php', 'cmb-player/cmb-header-data.php', 'cmb-player/cmb-header-css.php',
    'cmb-admin-parts/cmb-admin-effects.php', 'cmb-admin-parts/cmb-admin-lighting.php', 'cmb-admin-parts/cmb-admin-frames.php', 'cmb-admin-parts/cmb-admin-topmenu.php',
    'cmb-admin-parts/cmb-admin-func.php', 'cmb-admin-parts/cmb-admin-identity.php',
    'cmb-studio-parts/cmb-studio-streams.php', 'cmb-studio-parts/cmb-studio-clipslides.php', 'cmb-studio-parts/cmb-studio-widgets.php',
    'cmb-studio-parts/cmb-studio-save.php', 'cmb-studio-parts/cmb-studio-slides.php',
    'cmb-core/cmb-effects-list.php', 'cmb-core/cmb-fonts.php', 'cmb-core/cmb-nav.php',
    'cmb-data/stream-presets.json', 'cmb-data/cmb-slides.json', 'stream-presets.json',
];
$readonlyExtra = ['cmb-core/cmb-lib.php', 'cmb-core/cmb-themes.php', 'cmb-backup.php']; // alleen lezen (bevat beveiligingsregels)
$readable = array_merge($editable, $readonlyExtra);
// cmb-claude.php, cmb-core/cmb-claude-func.php, cmb-ai-helper.php, cmb-config.php en de sleutels zijn bewust NIET opgenomen.

define('CMB_PART', true);
require_once CMB_ROOT . '/cmb-core/cmb-claude-func.php';

$ctx = [
    'settingsFile' => $settingsFile, 'slidesFile' => $dataDir . 'cmb-slides.json', 'bakDir' => $bakDir, 'pendDir' => $pendDir,
    'readable' => $readable, 'editable' => $editable,
];

$systemPrompt = <<<TXT
Je bent de ontwerp-assistent van de Chazzo Music Box (CMB), een radio-kiosk speler in PHP/CSS/JS. Antwoord altijd in het Nederlands, kort en praktisch.

PROJECTSTRUCTUUR (mappen: cmb-core = gedeelde functies, cmb-player = onderdelen van de speler, cmb-admin-parts en cmb-studio-parts = tabbladen van het beheer, cmb-assets = css/js; gebruik bij read_file het pad met map, bv. cmb-assets/cmb-effects.css):
- index.php: hoofdweergave (linker slide-frame, rechter speler-frame, topbar, ticker).
- header.php: instellingen laden en <head>/body-begin; laadt cmb-header-func.php (functies zoals renderLightingLayer() en getStaticFrameBorderCSS()), cmb-header-data.php (ticker, slides, clipslides, live-sync endpoint) en cmb-header-css.php (hoofd-CSS).
- cmb-effects.css: 100 cover-effecten (.fx-*), frame-effecten (.frame-*), seizoensdecor, stream-uitklap en clip-overname.
- cmb-effects-list.php: lijst van alle cover-effecten (sleutel => naam). Een nieuw effect = regel hier + .fx-<sleutel> in cmb-effects.css.
- cmb-fonts.php: lettertypecatalogus (naam => [omschrijving, Google-gewichten]); de speler laadt alleen gebruikte fonts.
- cmb-studio-clipslides.php: clipslides (max. 15): een clip gekoppeld aan 'Artiest - Titel' neemt het scherm over zolang hij speelt (eenmalig, max. 15 MB bij upload). cmb-studio-widgets.php: RSS-, embed- en eigen-code-widgets (max. 6). cmb-nav.php: gedeeld beheermenu. cmb-admin-frames.php: frame-instellingen per zijde/rand.
- cmb-admin-ui.js: gebruiksgemak in beheer (niet-opgeslagen waarschuwing, Ctrl+S, snelle thema's).
- footer.php: start de speler met de instellingen; de CMBEngine JS (live sync, track polling, weer, klok, slide-rotatie, audio) staat in cmb-player.js.
- cmb-admin.php (+ cmb-admin-func.php, cmb-admin-identity.php, cmb-admin-lighting.php, cmb-admin-effects.php): beheer van frame-verlichting, cover-effecten, identiteit, ticker, knoppen.
- cmb-studio.php (+ cmb-studio-save.php opslaan, cmb-studio-slides.php, cmb-studio.js, cmb-studio-streams.php): slides, weer, vrije slides met track trigger, streams. Stream 1 en stream 2 zijn twee volledige infrastructuren (audio + titelinfo + hoes); valt 1 uit dan neemt 2 alles over en schakelt de speler automatisch terug.
- cmb-data/cmb-settings.json: alle instellingen; cmb-data/cmb-slides.json: slides, clipslides (sleutel 'clipslides') en widgets (sleutel 'widgets'); stream-presets.json: stream-presets.

WERKWIJZE:
- Instellingen wijzigen: gebruik update_settings (direct, met back-up). Lees eerst get_settings.
- Slides toevoegen of wijzigen (RSS-nieuws, weer, vrije of tijdelijke slides): lees eerst get_slides en gebruik dan update_slides. Kies voor iets nieuws een lege of uitgeschakelde vrije slide en zeg welk nummer je gebruikt. Tijdelijke slide = show_from/show_until. Weer: zoek zelf geen coördinaten op als je ze niet zeker weet; vraag ze of laat de gebruiker de plaats zoeken in Studio > Weer.
- Code, CSS of layout wijzigen: lees eerst het relevante deel met read_file/search_files en gebruik dan propose_file_edit met een kleine, unieke old_str. De gebruiker keurt elk voorstel zelf goed. Doe liever meerdere kleine voorstellen dan één groot.
- Voor eigen stijlen: voeg CSS toe aan cmb-custom.css (old_str leeg) in plaats van bestaande bestanden om te bouwen. Dat bestand wordt na cmb-effects.css geladen.
- Frame-effecten zijn holle randen: gebruik de bestaande mask-techniek (padding = randdikte, mask-composite: exclude) en GEEN clip-path of polygon; hoeken lopen via border-radius.
- Raak nooit inloggegevens of API-sleutels aan en doe geen voorstellen die authenticatie omzeilen.
- Wees eerlijk over wat je niet kunt controleren: je ziet de pagina niet en kunt geen code uitvoeren.
TXT;

/* ---------- API-acties ---------- */
if ($isApi) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'Alleen POST'], 405);
    $tok = $_SERVER['HTTP_X_CMB_TOKEN'] ?? '';
    if (!hash_equals($csrf, $tok)) out(['error' => 'Ongeldige sessie-token; ververs de pagina.'], 403);

    $req = json_decode(file_get_contents('php://input'), true);
    if (!is_array($req)) $req = [];
    $action = $_GET['action'];

    if ($action === 'list') {
        $items = [];
        foreach (glob($pendDir . '*.json') ?: [] as $f) {
            $r = json_decode(file_get_contents($f), true);
            if (is_array($r)) $items[] = $r;
        }
        usort($items, function ($a, $b) { return strcmp($b['created'] ?? '', $a['created'] ?? ''); });
        out(['items' => array_slice($items, 0, 40)]);
    }

    if ($action === 'reject' || $action === 'apply' || $action === 'undo') {
        $rec = load_pending($req['id'] ?? '', $pendDir);
        if (!$rec) out(['error' => 'Voorstel niet gevonden.'], 404);
        $rel = allowed_path($rec['path'], $editable);
        if (!$rel) out(['error' => 'Bestand niet toegestaan.'], 400);
        $f = cmb_claude_abs($rel);

        if ($action === 'reject') {
            if ($rec['status'] !== 'pending') out(['error' => 'Voorstel is al afgehandeld.'], 400);
            $rec['status'] = 'rejected';
            save_pending($rec, $pendDir);
            out(['ok' => true, 'item' => $rec]);
        }

        if ($action === 'apply') {
            if ($rec['status'] !== 'pending') out(['error' => 'Voorstel is al afgehandeld.'], 400);
            $cur = is_file($f) ? file_get_contents($f) : '';
            list($res, $err) = apply_edit_to_text($cur, $rec['old'], $rec['new']);
            if ($res === null) out(['error' => $err . ' Vraag Claude om een nieuw voorstel.'], 409);
            $lintNote = '';
            if (substr($rel, -4) === '.php') {
                list($okLint, $msg) = lint_php($res);
                if (!$okLint) out(['error' => 'PHP-fout in voorstel, niet toegepast: ' . $msg], 422);
                $lintNote = $msg;
            }
            $bak = make_backup($rel, $bakDir);
            if (file_put_contents($f, $res, LOCK_EX) === false) out(['error' => 'Kon bestand niet schrijven.'], 500);
            $rec['status'] = 'applied';
            $rec['backup'] = $bak;
            $rec['existed'] = $bak !== '';
            $rec['applied'] = date('c');
            $rec['note'] = $lintNote;
            save_pending($rec, $pendDir);
            out(['ok' => true, 'item' => $rec]);
        }

        if ($action === 'undo') {
            if ($rec['status'] !== 'applied') out(['error' => 'Alleen toegepaste wijzigingen kunnen ongedaan worden gemaakt.'], 400);
            if (!empty($rec['backup']) && is_file($bakDir . basename($rec['backup']))) {
                copy($bakDir . basename($rec['backup']), $f);
            } elseif (empty($rec['existed'])) {
                @unlink($f); // bestand was nieuw
            } else {
                out(['error' => 'Back-up niet gevonden.'], 404);
            }
            $rec['status'] = 'undone';
            save_pending($rec, $pendDir);
            out(['ok' => true, 'item' => $rec]);
        }
    }

    if ($action === 'chat') {
        $provider = in_array($req['provider'] ?? '', ['gemini', 'openrouter'], true) ? $req['provider'] : 'claude';
        if ($provider === 'claude' && cmb_api_key() === '') {
            if (cmb_gemini_key() !== '') $provider = 'gemini';
            elseif (cmb_openrouter_key() !== '') $provider = 'openrouter';
        }
        $apiKey = $provider === 'gemini' ? cmb_gemini_key() : ($provider === 'openrouter' ? cmb_openrouter_key() : cmb_api_key());
        if ($apiKey === '') {
            out(['error' => $provider === 'openrouter'
                ? 'Geen OpenRouter-sleutel gevonden. Maak een sleutel op openrouter.ai/keys en zet hem in cmb-data/openrouter-key.php (zie cmb-data/openrouter-key.example.php).'
                : ($provider === 'gemini'
                ? 'Geen Gemini-sleutel gevonden. Maak gratis een sleutel op aistudio.google.com en zet hem in cmb-data/gemini-key.php (zie cmb-data/gemini-key.example.php).'
                : 'Geen API-sleutel gevonden. Zet ANTHROPIC_API_KEY of maak cmb-data/anthropic-key.php aan (zie cmb-data/anthropic-key.example.php). Alternatieven: Gemini (cmb-data/gemini-key.example.php) of OpenRouter (cmb-data/openrouter-key.example.php).')], 400);
        }
        $messages = [];
        foreach (array_slice((array)($req['messages'] ?? []), -20) as $m) {
            $role = ($m['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $txt = trim((string)($m['content'] ?? ''));
            if ($txt === '') continue;
            $messages[] = ['role' => $role, 'content' => mb_substr($txt, 0, 8000)];
        }
        if (!$messages || $messages[count($messages) - 1]['role'] !== 'user') out(['error' => 'Geen vraag ontvangen.'], 400);
        while ($messages && $messages[0]['role'] !== 'user') array_shift($messages);

        $GLOBALS['cmb_new_proposals'] = [];
        $GLOBALS['cmb_settings_changed'] = [];
        $finalText = '';

        if ($provider === 'openrouter') {
            // ---- OpenRouter: OpenAI-formaat met dezelfde tools ----
            $oTools = [];
            foreach ($tools as $t) $oTools[] = ['type' => 'function', 'function' => ['name' => $t['name'], 'description' => $t['description'] ?? '', 'parameters' => $t['input_schema'] ?? ['type' => 'object', 'properties' => new stdClass()]]];
            $oMsgs = [['role' => 'system', 'content' => $systemPrompt]];
            foreach ($messages as $m) $oMsgs[] = ['role' => $m['role'], 'content' => $m['content']];
            for ($round = 0; $round < 8; $round++) {
                $payload = ['model' => $openrouterModel, 'messages' => $oMsgs, 'tools' => $oTools, 'max_tokens' => 4096];
                $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    CURLOPT_HTTPHEADER => ['content-type: application/json', 'Authorization: Bearer ' . $apiKey, 'X-Title: Chazzo Music Box'],
                    CURLOPT_TIMEOUT => 120,
                ]);
                $raw = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $cerr = curl_error($ch);
                curl_close($ch);
                if ($raw === false) out(['error' => 'Verbinding met OpenRouter mislukt: ' . $cerr], 502);
                $resp = json_decode($raw, true);
                if ($http !== 200 || !is_array($resp) || isset($resp['error'])) {
                    $msg = $resp['error']['message'] ?? ('HTTP ' . $http);
                    if ($http === 401) $msg .= ' (sleutel ongeldig)';
                    if ($http === 402) $msg .= ' (geen tegoed meer op OpenRouter)';
                    if ($http === 404) $msg .= ' (model "' . $openrouterModel . '" niet beschikbaar of zonder tool-ondersteuning; zet een ander model in CMB_OPENROUTER_MODEL)';
                    out(['error' => 'OpenRouter-fout: ' . $msg], 502);
                }
                $msg = $resp['choices'][0]['message'] ?? [];
                if (!empty($msg['content']) && is_string($msg['content'])) $finalText .= $msg['content'];
                $calls = $msg['tool_calls'] ?? [];
                if (!$calls) break;
                $oMsgs[] = ['role' => 'assistant', 'content' => $msg['content'] ?? '', 'tool_calls' => $calls];
                foreach ($calls as $tc) {
                    $nm = (string)($tc['function']['name'] ?? '');
                    $args = json_decode((string)($tc['function']['arguments'] ?? '{}'), true);
                    $res = run_tool($nm, is_array($args) ? $args : [], $ctx);
                    $oMsgs[] = ['role' => 'tool', 'tool_call_id' => $tc['id'] ?? '', 'content' => (string)$res];
                }
                $finalText .= "\n";
            }
        } elseif ($provider === 'gemini') {
            // ---- Gemini: zelfde tools, ander berichtformaat ----
            $contents = [];
            foreach ($messages as $m) $contents[] = ['role' => $m['role'] === 'assistant' ? 'model' : 'user', 'parts' => [['text' => $m['content']]]];
            $gTools = gemini_tools($tools);
            for ($round = 0; $round < 8; $round++) {
                $payload = [
                    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents' => $contents,
                    'tools' => $gTools,
                    'generationConfig' => ['maxOutputTokens' => 4096],
                ];
                $ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($geminiModel) . ':generateContent');
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    CURLOPT_HTTPHEADER => ['content-type: application/json', 'x-goog-api-key: ' . $apiKey],
                    CURLOPT_TIMEOUT => 120,
                ]);
                $raw = curl_exec($ch);
                $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $cerr = curl_error($ch);
                curl_close($ch);
                if ($raw === false) out(['error' => 'Verbinding met Gemini mislukt: ' . $cerr], 502);
                $resp = json_decode($raw, true);
                if ($http !== 200 || !is_array($resp)) {
                    $msg = $resp['error']['message'] ?? ('HTTP ' . $http);
                    if ($http === 429) $msg .= ' (gratis limiet bereikt; probeer het over een minuut opnieuw)';
                    if ($http === 404) $msg .= ' (model "' . $geminiModel . '" niet gevonden; zet een ander model in CMB_GEMINI_MODEL)';
                    out(['error' => 'Gemini API-fout: ' . $msg], 502);
                }
                $parts = $resp['candidates'][0]['content']['parts'] ?? [];
                $calls = [];
                foreach ($parts as $pt) {
                    if (isset($pt['text']) && empty($pt['thought'])) $finalText .= $pt['text'];
                    if (isset($pt['functionCall'])) $calls[] = $pt['functionCall'];
                }
                if (!$calls) break;
                $contents[] = ['role' => 'model', 'parts' => $parts]; // ongewijzigd terug (bevat ook de denk-handtekening)
                $resParts = [];
                foreach ($calls as $fc) {
                    $nm = (string)($fc['name'] ?? '');
                    $res = run_tool($nm, gemini_args($tools, $nm, $fc['args'] ?? []), $ctx);
                    $fr = ['name' => $nm, 'response' => ['result' => (string)$res]];
                    if (isset($fc['id'])) $fr['id'] = $fc['id'];
                    $resParts[] = ['functionResponse' => $fr];
                }
                $contents[] = ['role' => 'user', 'parts' => $resParts];
                $finalText .= "\n";
            }
        } else
        for ($round = 0; $round < 8; $round++) {
            $payload = [
                'model' => $model,
                'max_tokens' => 4096,
                'system' => $systemPrompt,
                'tools' => $tools,
                'messages' => $messages,
            ];
            $ch = curl_init('https://api.anthropic.com/v1/messages');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                CURLOPT_HTTPHEADER => [
                    'content-type: application/json',
                    'x-api-key: ' . $apiKey,
                    'anthropic-version: 2023-06-01',
                ],
                CURLOPT_TIMEOUT => 120,
            ]);
            $raw = curl_exec($ch);
            $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($raw === false) out(['error' => 'Verbinding met Anthropic mislukt: ' . $cerr], 502);
            $resp = json_decode($raw, true);
            if ($http !== 200 || !is_array($resp)) {
                $msg = $resp['error']['message'] ?? ('HTTP ' . $http);
                out(['error' => 'Anthropic API-fout: ' . $msg], 502);
            }

            $content = $resp['content'] ?? [];
            foreach ($content as $b) {
                if (($b['type'] ?? '') === 'text') $finalText .= $b['text'];
            }
            if (($resp['stop_reason'] ?? '') !== 'tool_use') break;

            $messages[] = ['role' => 'assistant', 'content' => $content];
            $results = [];
            foreach ($content as $b) {
                if (($b['type'] ?? '') !== 'tool_use') continue;
                $res = run_tool($b['name'], is_array($b['input'] ?? null) ? $b['input'] : [], $ctx);
                $results[] = ['type' => 'tool_result', 'tool_use_id' => $b['id'], 'content' => (string)$res];
            }
            $messages[] = ['role' => 'user', 'content' => $results];
            $finalText .= "\n";
        }

        $props = [];
        foreach ($GLOBALS['cmb_new_proposals'] as $pid) {
            $r = load_pending($pid, $pendDir);
            if ($r) $props[] = $r;
        }
        out([
            'provider' => $provider,
            'reply' => trim($finalText) !== '' ? trim($finalText) : '(Geen tekstantwoord ontvangen.)',
            'proposals' => $props,
            'settings_changed' => array_values(array_unique($GLOBALS['cmb_settings_changed'])),
        ]);
    }

    out(['error' => 'Onbekende actie'], 400);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Claude Assistent</title>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:20px; }
    .wrap { max-width:1100px; margin:0 auto; display:grid; grid-template-columns:1.4fr 1fr; gap:16px; }
    @media (max-width:900px){ .wrap{ grid-template-columns:1fr; } }
    h1 { color:#ffd166; font-size:20px; margin:0 0 4px; }
    .sub { color:#a8aac8; font-size:12px; margin-bottom:14px; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:14px; }
    #chat { height:60vh; overflow-y:auto; display:flex; flex-direction:column; gap:10px; padding-right:4px; }
    .msg { padding:10px 12px; border-radius:10px; font-size:14px; line-height:1.45; white-space:pre-wrap; overflow-wrap:anywhere; }
    .msg.user { background:#1d2142; align-self:flex-end; max-width:90%; }
    .msg.ai { background:#0b1020; border:1px solid rgba(99,246,255,.2); }
    .msg.err { background:rgba(255,23,68,.15); border:1px solid #ff1744; color:#ff8aa0; }
    .row { display:flex; gap:8px; margin-top:10px; }
    textarea { flex:1; background:#05060d; color:#fff; border:1px solid rgba(255,255,255,.15); border-radius:8px; padding:10px; font-size:14px; min-height:64px; resize:vertical; }
    button { cursor:pointer; border:none; border-radius:8px; font-weight:800; padding:10px 14px; }
    .send { background:linear-gradient(135deg,#ffd166,#ff5bc8); color:#070812; }
    .ok { background:#00e676; color:#070812; } .no { background:#2a2e52; color:#fff; } .undo { background:#ff9100; color:#070812; }
    .prop { border:1px solid rgba(255,209,102,.3); border-radius:10px; padding:10px; margin-bottom:10px; font-size:12px; }
    .prop b { color:#ffd166; } .prop small { color:#a8aac8; }
    pre { margin:6px 0; padding:8px; border-radius:6px; max-height:160px; overflow:auto; font-size:11px; white-space:pre-wrap; overflow-wrap:anywhere; }
    pre.old { background:rgba(255,23,68,.12); } pre.new { background:rgba(0,230,118,.12); }
    .status { font-size:11px; font-weight:800; text-transform:uppercase; }
    a.back { color:#63f6ff; font-size:12px; text-decoration:none; margin-right:12px; }
</style>
</head>
<body>
<div style="max-width:1100px;margin:0 auto 12px;">
    <?php require_once CMB_ROOT . '/cmb-core/cmb-nav.php'; echo cmb_nav('claude', 'cmb-admin.php?logout=1'); ?>
    <h1>🤖 CMB Claude Assistent</h1>
    <?php $hasClaude = cmb_api_key() !== ''; $hasGemini = cmb_gemini_key() !== ''; $hasOR = cmb_openrouter_key() !== ''; ?>
    <div style="margin:6px 0 8px; font-size:12px;">
        <label style="color:#a8aac8;">Assistent:
            <select id="provider" style="background:#05060d; color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:6px; padding:4px 6px;">
                <option value="claude" <?= $hasClaude ? 'selected' : '' ?>>Claude (Anthropic, betaald)<?= $hasClaude ? '' : ' - geen sleutel' ?></option>
                <option value="gemini" <?= !$hasClaude && $hasGemini ? 'selected' : '' ?>>Gemini (Google, gratis)<?= $hasGemini ? '' : ' - geen sleutel' ?></option>
                <option value="openrouter" <?= !$hasClaude && !$hasGemini && $hasOR ? 'selected' : '' ?>>OpenRouter (veel modellen)<?= $hasOR ? '' : ' - geen sleutel' ?></option>
            </select>
        </label>
    </div>
    <div class="sub">Vraag Claude om iets te ontwerpen of te veranderen. Instellingen worden direct (met back-up) aangepast; code-wijzigingen komen rechts als voorstel dat jij goedkeurt.</div>
</div>
<div class="wrap">
    <div class="card">
        <div id="chat"><div class="msg ai">Hoi! Vertel wat je wilt: bijvoorbeeld "maak een nieuw gouden frame-effect", "zet het linker frame op aurora in emerald", "maak een RSS-slide met het NOS-nieuws" of "maak een tijdelijke slide voor de kermis t/m zondag".</div></div>
        <div class="row">
            <textarea id="q" placeholder="Wat wil je ontwerpen of aanpassen?"></textarea>
            <button class="send" id="sendBtn" onclick="sendMsg()">Verstuur</button>
        </div>
    </div>
    <div class="card">
        <strong style="color:#ffd166;">Voorstellen &amp; geschiedenis</strong>
        <div id="props" style="margin-top:10px;"></div>
    </div>
</div>
<script>
var TOKEN = <?= json_encode($csrf) ?>;
var history_ = [];

function api(action, body) {
    return fetch('cmb-claude.php?action=' + action, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CMB-Token': TOKEN },
        body: JSON.stringify(body || {})
    }).then(function (r) { return r.json(); });
}
function addMsg(cls, text) {
    var d = document.createElement('div');
    d.className = 'msg ' + cls;
    d.textContent = text;
    var c = document.getElementById('chat');
    c.appendChild(d); c.scrollTop = c.scrollHeight;
    return d;
}
function sendMsg() {
    var q = document.getElementById('q');
    var text = q.value.trim();
    if (!text) return;
    q.value = '';
    addMsg('user', text);
    history_.push({ role: 'user', content: text });
    var btn = document.getElementById('sendBtn');
    btn.disabled = true;
    var prov = (document.getElementById('provider') || {}).value || 'claude';
    var wait = addMsg('ai', (prov === 'gemini' ? 'Gemini' : prov === 'openrouter' ? 'OpenRouter' : 'Claude') + ' denkt na…');
    api('chat', { messages: history_, provider: prov }).then(function (d) {
        wait.remove();
        if (d.error) { addMsg('err', d.error); history_.pop(); return; }
        addMsg('ai', d.reply);
        history_.push({ role: 'assistant', content: d.reply });
        if (d.settings_changed && d.settings_changed.length) {
            addMsg('ai', '✓ Instellingen gewijzigd: ' + d.settings_changed.join(', ') + '. Herlaad de speler om het resultaat te zien.');
        }
        loadProps();
    }).catch(function () {
        wait.remove(); addMsg('err', 'Netwerkfout.'); history_.pop();
    }).finally(function () { btn.disabled = false; });
}
document.getElementById('q').addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) sendMsg();
});

function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
var LABELS = { pending: 'Wacht op goedkeuring', applied: 'Toegepast', rejected: 'Afgewezen', undone: 'Ongedaan gemaakt' };
function loadProps() {
    api('list').then(function (d) {
        var box = document.getElementById('props');
        if (!d.items || !d.items.length) { box.innerHTML = '<small style="color:#a8aac8">Nog geen voorstellen.</small>'; return; }
        box.innerHTML = d.items.map(function (p) {
            var btns = '';
            if (p.status === 'pending') btns = '<button class="ok" onclick="act(\'apply\',\'' + p.id + '\')">Toepassen</button> <button class="no" onclick="act(\'reject\',\'' + p.id + '\')">Afwijzen</button>';
            if (p.status === 'applied') btns = '<button class="undo" onclick="act(\'undo\',\'' + p.id + '\')">Ongedaan maken</button>';
            return '<div class="prop"><b>' + esc(p.path) + '</b> <span class="status">· ' + (LABELS[p.status] || p.status) + '</span><br>' +
                '<small>' + esc(p.explanation) + (p.note ? ' (' + esc(p.note) + ')' : '') + '</small>' +
                (p.old ? '<pre class="old">' + esc(p.old) + '</pre>' : '') +
                '<pre class="new">' + esc(p.new) + '</pre>' + btns + '</div>';
        }).join('');
    });
}
function act(action, id) {
    api(action, { id: id }).then(function (d) {
        if (d.error) alert(d.error);
        loadProps();
    });
}
loadProps();
</script>
</body>
</html>
