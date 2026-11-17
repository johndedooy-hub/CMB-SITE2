<?php
/**
 * CHAZZO MUSIC BOX (CMB) - REGIOAGENDA (kern)
 * Bestandsnaam: cmb-core/cmb-regio.php
 *
 * Eén bron voor twee pagina's: het beheer-menu "Regioagenda" (cmb-regioagenda.php)
 * en de losse invoerpagina (regioagenda-tekst.php). Beide lezen en schrijven dezelfde data.
 *
 * Opzet: 6 onderwerpen = 2 edities van 3 (editie 1 = onderwerp 1-3, editie 2 = onderwerp 4-6).
 * Per editie wordt een tekstbestand gemaakt: vaste intro + de onderwerpen + vaste outro.
 * Dezelfde onderwerpen kunnen (aan/uit per onderwerp) als slide in de speler. De slide leest
 * de getypte tekst alleen; wat naar de spraak gaat verandert er niet door.
 *
 * Optionele instellingen in cmb-config.php (alles heeft een werkende standaard):
 *   'regio_txt_dir'    => '/pad/naar/map/waar/ff-regioagenda-1.txt/komt'   (standaard: map van CMB)
 *   'regio_txt_url'    => 'https://www.focusfantastique.nl/'                (alleen voor de weergave)
 *   'regio_repo'       => 'johndedooy-hub/focus-nieuws-tts'
 *   'regio_workflow'   => 'regio-editie-tts.yml'
 *   'regio_token_file' => '/pad/naar/github-token.php'                    (bestand dat de token teruggeeft)
 */
if (!defined('CMB_ROOT')) define('CMB_ROOT', dirname(__DIR__));
require_once __DIR__ . '/cmb-lib.php';

const CMB_REGIO_ITEMS = 6;
const CMB_REGIO_PER_ED = 3;
const CMB_REGIO_TING = '---TING---'; // markering in de tekst: hier komt de pingel (regio-ting.mp3)

function cmb_regio_file() { return cmb_data_dir() . 'cmb-regioagenda.json'; }

function cmb_regio_defaults() {
    $items = [];
    for ($i = 0; $i < CMB_REGIO_ITEMS; $i++) $items[] = ['text' => '', 'kop' => '', 'media' => '', 'blur' => null, 'darken' => null, 'slide' => true];
    return [
        'intro'  => "Dit is de Focus Fantastique regioagenda.\nIk ben Fenna de Bruijn!",
        'outro'  => "Tot zover de Regio agenda!\nHeeft u informatie voor de regioagenda?\n\nMail deze dan naar: studio@focusfantastique.nl",
        'items'  => $items,
        'sec'    => 12,
        'darken' => 35,
        'media'  => '',
        'style_kop'  => ['font' => 'Montserrat', 'size' => '20px', 'color' => '#63f6ff', 'grad_mode' => 'none', 'grad_col2' => '#63f6ff', 'shadow' => '#000000'],
        'style_body' => ['font' => 'Montserrat', 'size' => '15px', 'color' => '#ffffff', 'grad_mode' => 'none', 'grad_col2' => '#63f6ff', 'shadow' => '#000000'],
        'updated' => 0,
    ];
}

/** Tekst opschonen: UTF-8, geen stuurtekens, geen rare lege regels. */
function cmb_regio_clean_text($s, $max = 2000) {
    $s = str_replace(["\r\n", "\r"], "\n", (string)$s);
    $c = preg_replace('/[^\P{C}\n]+/u', '', $s);
    if ($c !== null) $s = $c;
    $s = preg_replace("/[ \t]+\n/", "\n", $s);
    $s = preg_replace("/\n{3,}/", "\n\n", $s);
    return mb_substr(trim($s), 0, $max);
}

function cmb_regio_load() {
    $d = cmb_regio_defaults();
    $f = cmb_regio_file();
    $j = is_file($f) ? json_decode((string)@file_get_contents($f), true) : null;
    if (!is_array($j)) return $d;
    foreach (['intro', 'outro', 'media'] as $k) if (isset($j[$k]) && is_string($j[$k])) $d[$k] = $j[$k];
    foreach (['sec', 'darken', 'updated'] as $k) if (isset($j[$k])) $d[$k] = intval($j[$k]);
    foreach (['style_kop', 'style_body'] as $k) if (!empty($j[$k]) && is_array($j[$k])) $d[$k] = array_merge($d[$k], $j[$k]);
    if (!empty($j['items']) && is_array($j['items'])) {
        $j['items'] = array_values($j['items']);
        for ($i = 0; $i < CMB_REGIO_ITEMS; $i++) {
            if (!isset($j['items'][$i]) || !is_array($j['items'][$i])) continue;
            $d['items'][$i] = [
                'text'  => cmb_regio_clean_text($j['items'][$i]['text'] ?? ''),
                'kop'   => cmb_regio_clean_text($j['items'][$i]['kop'] ?? '', 120),
                'media' => cmb_regio_media_url($j['items'][$i]['media'] ?? ''),
                'blur'   => cmb_regio_num($j['items'][$i]['blur'] ?? null, 0, 30),
                'darken' => cmb_regio_num($j['items'][$i]['darken'] ?? null, 0, 90),
                'slide' => !empty($j['items'][$i]['slide']),
            ];
        }
    }
    return $d;
}

/** Formulier (POST) omzetten naar data. Velden die niet in het formulier zitten blijven zoals ze waren. */
function cmb_regio_from_post(array $post, array $old) {
    $d = $old;
    if (isset($post['intro'])) $d['intro'] = cmb_regio_clean_text($post['intro'], 1000);
    if (isset($post['outro'])) $d['outro'] = cmb_regio_clean_text($post['outro'], 1000);
    for ($i = 0; $i < CMB_REGIO_ITEMS; $i++) {
        $d['items'][$i] = [
            'text'  => cmb_regio_clean_text($post['item_text'][$i] ?? ''),
            'kop'   => str_replace("\n", ' ', cmb_regio_clean_text($post['item_kop'][$i] ?? ($old['items'][$i]['kop'] ?? ''), 120)),
            'media' => cmb_regio_media_url($post['item_media'][$i] ?? ($old['items'][$i]['media'] ?? '')),
            'blur'   => cmb_regio_num($post['item_blur'][$i] ?? null, 0, 30),
            'darken' => cmb_regio_num($post['item_darken'][$i] ?? null, 0, 90),
            'slide' => !empty($post['item_slide'][$i]),
        ];
    }
    // Opmaak van koptitel en slidetekst (alleen op de beheerpagina aanwezig)
    if (isset($post['rg_kop_font']))  $d['style_kop']  = extractStyleSuite('rg_kop');
    if (isset($post['rg_body_font'])) $d['style_body'] = extractStyleSuite('rg_body');
    if (isset($post['sec']))    $d['sec'] = max(3, min(120, intval($post['sec'])));
    if (isset($post['darken'])) $d['darken'] = max(0, min(90, intval($post['darken'])));
    if (isset($post['media']))  $d['media'] = cmb_regio_media_url($post['media']);
    return $d;
}

/** Getal binnen grenzen, of null (= standaard) als leeg. */
function cmb_regio_num($v, $min, $max) {
    if ($v === null || $v === '' || !is_numeric($v)) return null;
    return max($min, min($max, intval($v)));
}

/** Uploads per onderwerp (veldnamen item_file_0 .. item_file_5). Geeft foutmeldingen terug. */
function cmb_regio_uploads(array &$d) {
    $errs = [];
    for ($i = 0; $i < CMB_REGIO_ITEMS; $i++) {
        $e = null;
        $p = cmb_upload('item_file_' . $i, cmb_upload_dir(), 8 * 1048576, $e, 'regio' . ($i + 1) . '_');
        if ($e) $errs[] = 'Onderwerp ' . ($i + 1) . ': ' . $e;
        elseif ($p) $d['items'][$i]['media'] = $p;
    }
    return $errs;
}

/** True als een formulier te groot was voor de server (dan is $_POST leeg). */
function cmb_regio_post_too_big() {
    return $_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && intval($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
}

function cmb_regio_media_url($u) {
    $u = trim((string)$u);
    if ($u === '' || strlen($u) > 600) return '';
    return preg_match('~^(https://|cmb-uploads/|cmb-assets/)~i', $u) ? $u : '';
}

function cmb_regio_save(array $d) {
    $d['updated'] = time();
    $f = cmb_regio_file();
    if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
    cmb_snapshot($f);
    $ok = @file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
    if ($ok) cmb_pulse('slides');
    return $ok;
}

/** Gevulde onderwerpen van een editie (1 of 2): [nummer => tekst] */
function cmb_regio_edition_items(array $d, $ed) {
    $out = [];
    $start = ($ed - 1) * CMB_REGIO_PER_ED;
    for ($i = $start; $i < $start + CMB_REGIO_PER_ED; $i++) {
        $t = trim((string)($d['items'][$i]['text'] ?? ''));
        if ($t !== '') $out[$i + 1] = $t;
    }
    return $out;
}

/** De volledige tekst voor de spraak: intro + onderwerpen + outro, gescheiden door een lege regel. */
function cmb_regio_script(array $d, $ed = 0) {
    $items = [];
    if ($ed === 0) { foreach ([1, 2] as $e) $items += cmb_regio_edition_items($d, $e); }
    else $items = cmb_regio_edition_items($d, $ed);
    if (!$items) return '';
    $parts = [];
    if (trim($d['intro']) !== '') $parts[] = trim($d['intro']);
    foreach ($items as $t) $parts[] = $t;
    // Per editie: markering voor de pingel na de opening en tussen de onderwerpen (niet na het laatste onderwerp).
    // De oude gecombineerde tekst (ed 0) krijgt geen markeringen.
    $glue = $ed === 0 ? "\n\n" : "\n\n" . CMB_REGIO_TING . "\n\n";
    $txt = implode($glue, $parts);
    if (trim($d['outro']) !== '') $txt .= "\n\n" . trim($d['outro']);
    return $txt . "\n";
}

function cmb_regio_txt_dir() {
    return rtrim((string)cmb_cfg('regio_txt_dir', CMB_ROOT), '/\\') . '/';
}
function cmb_regio_txt_name($ed) { return $ed === 0 ? 'ff-regioagenda.txt' : 'ff-regioagenda-' . $ed . '.txt'; }

function cmb_regio_txt_url($ed) {
    $base = (string)cmb_cfg('regio_txt_url', '');
    if ($base === '') {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
        $base = ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
    }
    return rtrim($base, '/') . '/' . cmb_regio_txt_name($ed);
}

/** Schrijft ff-regioagenda-1.txt, ff-regioagenda-2.txt en (voor de oude route) ff-regioagenda.txt. Geeft foutmeldingen terug. */
function cmb_regio_write_txt(array $d) {
    $err = [];
    $dir = cmb_regio_txt_dir();
    if (!is_dir($dir) || !is_writable($dir)) return ["Map niet beschrijfbaar: $dir"];
    foreach ([1, 2, 0] as $ed) {
        $f = $dir . cmb_regio_txt_name($ed);
        $tmp = $f . '.tmp' . getmypid();
        // Een lege editie geeft een leeg bestand: de workflow stopt dan met een duidelijke fout in plaats van oude tekst te herhalen.
        if (@file_put_contents($tmp, cmb_regio_script($d, $ed), LOCK_EX) === false || !@rename($tmp, $f)) {
            @unlink($tmp);
            $err[] = 'Schrijven mislukt: ' . cmb_regio_txt_name($ed);
        }
    }
    return $err;
}

/** Slides voor de speler (zelfde vorm als de vrije slides). */
function cmb_regio_slides(array $d) {
    $out = [];
    $media = $d['media'] ?? '';
    $esc = function ($s) { return str_replace(['<', '>'], ['&lt;', '&gt;'], (string)$s); };
    for ($i = 0; $i < CMB_REGIO_ITEMS; $i++) {
        $it = $d['items'][$i] ?? [];
        $t = trim((string)($it['text'] ?? ''));
        if ($t === '' || empty($it['slide'])) continue;
        // Koptitel is een eigen veld (niet in de mp3); zonder kop komt er een vaste titel. De tekst staat klein eronder.
        $kop = trim((string)($it['kop'] ?? ''));
        $own = (string)($it['media'] ?? '');
        $title = $kop !== '' ? $kop : '📅 Regioagenda'; $sub = ''; $body = $t;
        $out[] = [
            'type' => 'custom',
            'order' => 40 + $i,
            'title' => $esc($title), 'sub' => $esc($sub), 'body' => $esc($body),
            'style_title' => $d['style_kop'],
            'style_sub'   => ['size' => '14px', 'color' => '#ffd166'],
            'style_body'  => $d['style_body'],
            'media' => $own !== '' ? $own : $media,
            'blur' => intval($it['blur'] ?? 0),
            'darken' => $it['darken'] !== null && isset($it['darken']) ? intval($it['darken']) : intval($d['darken'] ?? 35),
            'track_match' => '', 'track_only' => false,
            'sec' => max(3, intval($d['sec'] ?? 12)),
        ];
    }
    return $out;
}

/* ---------------- GitHub (workflow starten, status, downloads) ---------------- */

function cmb_regio_repo()     { return (string)cmb_cfg('regio_repo', 'johndedooy-hub/focus-nieuws-tts'); }
function cmb_regio_workflow() { return (string)cmb_cfg('regio_workflow', 'regio-editie-tts.yml'); }
function cmb_regio_mp3_name($ed) { return 'Focus-Regio-News-' . intval($ed) . '.mp3'; }
function cmb_regio_mp3_url($ed) {
    return 'https://github.com/' . cmb_regio_repo() . '/raw/refs/heads/main/' . cmb_regio_mp3_name($ed);
}

function cmb_regio_token() {
    $cands = [(string)cmb_cfg('regio_token_file', '')];
    if (cmb_config_path() !== '') { $cands[] = dirname(cmb_config_path()) . '/github-token.php'; $cands[] = dirname(cmb_config_path()) . '/github-token-tts.php'; }
    $dir = CMB_ROOT;   // daarna omhoog zoeken, net als cmb-config.php (thuismap, domains/<site>, public_html)
    for ($i = 0; $i < 8; $i++) {
        $cands[] = $dir . '/github-token.php';
        $cands[] = $dir . '/github-token-tts.php';   // reserve, voor als de oude nog ergens staat
        $up = dirname($dir);
        if ($up === $dir) break;
        $dir = $up;
    }
    foreach ($cands as $f) {
        if ($f !== '' && @is_file($f)) {
            $t = @include $f;
            if (is_string($t) && trim($t) !== '') return trim($t);
        }
    }
    return '';
}

/** Eén GitHub-aanroep. Geeft [httpcode, body] terug. */
function cmb_regio_gh($method, $path, $payload = null) {
    $token = cmb_regio_token();
    if ($token === '') return [0, 'GitHub-token niet gevonden'];
    $ch = curl_init('https://api.github.com' . $path);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.github+json',
            'Content-Type: application/json',
            'User-Agent: CMB-Regioagenda',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
    ]);
    if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $body = curl_exec($ch);
    $code = intval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
    if ($body === false) $body = curl_error($ch);
    curl_close($ch);
    return [$code, (string)$body];
}

/** Start de workflow voor één editie. [ok, melding] */
function cmb_regio_dispatch($ed) {
    [$code, $body] = cmb_regio_gh('POST', '/repos/' . cmb_regio_repo() . '/actions/workflows/' . rawurlencode(cmb_regio_workflow()) . '/dispatches',
        ['ref' => 'main', 'inputs' => ['edition' => (string)intval($ed), 'txt_url' => cmb_regio_txt_url(intval($ed))]]);
    if ($code === 204) return [true, 'gestart'];
    $m = json_decode($body, true);
    return [false, 'GitHub antwoordde ' . $code . (is_array($m) && !empty($m['message']) ? ': ' . $m['message'] : ($code === 0 ? ': ' . $body : ''))];
}

/** Laatste runs per editie: [1 => ['status'=>..., 'conclusion'=>..., 'created'=>ts], 2 => ...] */
function cmb_regio_status() {
    [$code, $body] = cmb_regio_gh('GET', '/repos/' . cmb_regio_repo() . '/actions/workflows/' . rawurlencode(cmb_regio_workflow()) . '/runs?per_page=10');
    if ($code !== 200) return ['ok' => false, 'msg' => 'status niet beschikbaar (' . $code . ')'];
    $j = json_decode($body, true);
    $out = ['ok' => true, 'eds' => []];
    foreach (($j['workflow_runs'] ?? []) as $r) {
        if (!preg_match('/editie (\d)/i', (string)($r['display_title'] ?? $r['name'] ?? ''), $m)) continue;
        $ed = intval($m[1]);
        if (isset($out['eds'][$ed])) continue; // nieuwste eerst
        $out['eds'][$ed] = [
            'status' => (string)($r['status'] ?? ''),
            'conclusion' => (string)($r['conclusion'] ?? ''),
            'created' => strtotime((string)($r['created_at'] ?? '')) ?: 0,
            'url' => (string)($r['html_url'] ?? ''),
        ];
    }
    return $out;
}

/* ---------------- Gedeelde stukken voor de pagina's ---------------- */

function cmb_regio_csrf() {
    if (empty($_SESSION['cmb_regio_csrf'])) $_SESSION['cmb_regio_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['cmb_regio_csrf'];
}
function cmb_regio_csrf_ok() {
    return !empty($_SESSION['cmb_regio_csrf']) && hash_equals($_SESSION['cmb_regio_csrf'], (string)($_POST['csrf'] ?? ''));
}

/** Inlogscherm (zelfde inlog als de rest van CMB). Geeft true terug als de bezoeker is ingelogd. */
function cmb_regio_require_login($title, $selfUrl) {
    if (isset($_GET['logout'])) { unset($_SESSION['cmb_auth']); header('Location: ' . $selfUrl); exit; }
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
        if (cmb_check_login(trim($_POST['login_user'] ?? ''), trim($_POST['login_pass'] ?? ''))) {
            $_SESSION['cmb_auth'] = true;
            header('Location: ' . $selfUrl); exit;
        }
        $error = 'Onjuiste inloggegevens!';
    }
    if (!empty($_SESSION['cmb_auth'])) return true;
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inloggen - <?= htmlspecialchars($title) ?></title>
<style>
 *{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
 body{background:#070812;color:#f7f7ff;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;padding:16px}
 .box{background:#121427;border:1px solid rgba(255,209,102,.3);border-radius:16px;padding:32px;width:100%;max-width:360px;text-align:center}
 h2{color:#ffd166;margin:0 0 20px;font-size:20px} label{display:block;text-align:left;font-size:11px;text-transform:uppercase;color:#a8aac8;margin-bottom:5px;font-weight:bold}
 input{width:100%;background:#05060d;border:1px solid rgba(255,255,255,.15);color:#fff;padding:10px;border-radius:8px;font-size:14px;margin-bottom:14px}
 button{width:100%;background:linear-gradient(135deg,#ffd166,#ff5bc8);border:none;color:#070812;padding:12px;border-radius:8px;font-weight:900;font-size:14px;cursor:pointer}
 .err{background:rgba(255,23,68,.15);border:1px solid #ff1744;color:#ff8aa0;padding:10px;border-radius:6px;font-size:12px;margin-bottom:15px;font-weight:bold}
</style></head><body><div class="box"><h2>🔒 <?= htmlspecialchars($title) ?></h2>
<?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST"><label>Gebruikersnaam</label><input type="text" name="login_user" autofocus required>
<label>Wachtwoord</label><input type="password" name="login_pass" required>
<button type="submit" name="login_submit">INLOGGEN</button></form></div></body></html>
<?php
    exit;
}

/** CSS voor het invoerformulier (gebruikt door beide pagina's). */
function cmb_regio_form_css() {
    return <<<CSS
.rg-ed{background:#121427;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:18px;margin-bottom:18px}
.rg-ed h2{margin:0 0 6px;font-size:17px;color:#ffd166}
.rg-hint{font-size:12px;color:#a8aac8;margin:0 0 12px;line-height:1.5}
.rg-item{background:#0b0d1c;border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:10px 12px;margin-bottom:10px}
.rg-item-head{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;font-weight:800;font-size:13px;color:#63f6ff}
.rg-item textarea,.rg-fixed textarea{width:100%;min-height:96px;background:#05060d;border:1px solid rgba(255,255,255,.15);color:#fff;padding:10px;border-radius:8px;font-size:14px;line-height:1.45;resize:vertical;font-family:inherit}
.rg-item textarea:focus,.rg-fixed textarea:focus{border-color:#ffd166;outline:none}
.rg-check{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#ffd166;cursor:pointer}
.rg-kop{width:100%;background:#05060d;border:1px solid rgba(99,246,255,.35);color:#63f6ff;padding:9px 10px;border-radius:8px;font-size:14px;font-weight:700;margin-bottom:8px}
.rg-kop:focus{border-color:#63f6ff;outline:none}
.rg-bg{margin-top:8px;background:#0b0d1c;border:1px dashed rgba(255,255,255,.15);border-radius:8px;padding:6px 10px}
.rg-bg summary{cursor:pointer;font-size:12px;font-weight:800;color:#ffd166}
.rg-bg-row{display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;margin-top:8px}
.rg-bg-f{display:flex;flex-direction:column;gap:4px}
.rg-bg-f label{font-size:10px;text-transform:uppercase;color:#a8aac8;font-weight:bold}
.rg-bg-f input[type=text],.rg-bg-f input[type=number]{background:#05060d;border:1px solid rgba(255,255,255,.15);color:#fff;padding:8px;border-radius:8px;font-size:13px}
.rg-bg-f input[type=file]{font-size:12px;color:#c9cbe6}
.rg-bg-thumb{height:56px;width:90px;object-fit:cover;border-radius:6px;border:1px solid rgba(255,255,255,.2)}
.rg-count{font-size:11px;color:#a8aac8;margin-top:4px}
.rg-count.warn{color:#ffd166;font-weight:800}
.rg-fixed label{display:block;font-size:11px;text-transform:uppercase;color:#a8aac8;margin:8px 0 4px;font-weight:bold}
.rg-fixed textarea{min-height:70px;color:#c9cbe6}
details.rg-fixed{background:#121427;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:12px 18px;margin-bottom:18px}
details.rg-fixed summary{cursor:pointer;font-weight:800;color:#ffd166}
CSS;
}

/** Het invoerformulier zelf: vaste intro/outro (ingeklapt) + 2 edities x 3 onderwerpen. */
function cmb_regio_form_fields(array $d) {
    $ups = [];
    foreach ((glob(cmb_upload_dir() . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE) ?: []) as $uf) $ups[$uf] = @filemtime($uf);
    arsort($ups);
    ob_start();
    ?>
    <datalist id="rgImgs"><?php foreach (array_slice(array_keys($ups), 0, 80) as $uf): ?><option value="cmb-uploads/<?= htmlspecialchars(basename($uf)) ?>"><?php endforeach; ?></datalist>
    <details class="rg-fixed">
        <summary>✏️ Vaste begin- en eindtekst (aanpasbaar, bv. bij andere stationnaam, e-mail of domein)</summary>
        <p class="rg-hint" style="margin-top:10px">Deze tekst komt automatisch voor en na de onderwerpen in beide mp3's. Pas aan en sla op; de volgende mp3 gebruikt de nieuwe tekst.</p>
        <label>Begin (intro)</label><textarea name="intro" rows="3"><?= htmlspecialchars($d['intro']) ?></textarea>
        <label>Einde (outro)</label><textarea name="outro" rows="5"><?= htmlspecialchars($d['outro']) ?></textarea>
    </details>
    <?php for ($ed = 1; $ed <= 2; $ed++): ?>
    <div class="rg-ed">
        <h2>🎙️ Editie <?= $ed ?> <span style="font-weight:400;color:#a8aac8;font-size:13px;">· onderwerp <?= ($ed - 1) * 3 + 1 ?> t/m <?= $ed * 3 ?> → mp3 <?= $ed ?></span></h2>
        <p class="rg-hint">Typ de tekst zoals je hem uitgesproken wilt hebben. De <strong>koptitel</strong> is alleen voor de slide en komt niet in de mp3.</p>
        <?php for ($k = 0; $k < 3; $k++): $i = ($ed - 1) * 3 + $k; $it = $d['items'][$i]; ?>
        <div class="rg-item">
            <div class="rg-item-head">
                <span>Onderwerp <?= $i + 1 ?></span>
                <label class="rg-check"><input type="checkbox" name="item_slide[<?= $i ?>]" value="1" <?= !empty($it['slide']) ? 'checked' : '' ?>> 🖥️ Op slide in de speler</label>
            </div>
            <input type="text" class="rg-kop" name="item_kop[<?= $i ?>]" maxlength="120" value="<?= htmlspecialchars($it['kop'] ?? '') ?>" placeholder="Koptitel voor de slide (niet in de mp3, mag leeg)">
            <textarea name="item_text[<?= $i ?>]" data-rg-count="rgc<?= $i ?>" placeholder="Leeg laten als er geen onderwerp is"><?= htmlspecialchars($it['text']) ?></textarea>
            <div class="rg-count" id="rgc<?= $i ?>"></div>
            <details class="rg-bg">
                <summary>🖼️ Achtergrond van deze slide <?= !empty($it['media']) ? '· eigen plaatje gekozen' : '· leeg = standaard' ?></summary>
                <div class="rg-bg-row">
                    <?php if (!empty($it['media'])): ?><img class="rg-bg-thumb" src="<?= htmlspecialchars($it['media']) ?>" alt="" onerror="this.style.display='none'"><?php endif; ?>
                    <div class="rg-bg-f" style="flex:2;min-width:220px"><label>Plaatje kiezen (bestaand of link)</label>
                        <input type="text" name="item_media[<?= $i ?>]" list="rgImgs" value="<?= htmlspecialchars($it['media'] ?? '') ?>" placeholder="cmb-uploads/plaatje.jpg of https://…"></div>
                    <div class="rg-bg-f" style="flex:2;min-width:220px"><label>Of een nieuw plaatje uploaden</label>
                        <input type="file" name="item_file_<?= $i ?>" accept="image/*"></div>
                    <div class="rg-bg-f"><label>Blur (0-30)</label><input type="number" name="item_blur[<?= $i ?>]" min="0" max="30" value="<?= $it['blur'] === null ? '' : intval($it['blur']) ?>" placeholder="0" style="width:80px"></div>
                    <div class="rg-bg-f"><label>Donker (0-90)</label><input type="number" name="item_darken[<?= $i ?>]" min="0" max="90" value="<?= $it['darken'] === null ? '' : intval($it['darken']) ?>" placeholder="std" style="width:80px"></div>
                </div>
            </details>
        </div>
        <?php endfor; ?>
    </div>
    <?php endfor; ?>
    <script>
    document.querySelectorAll('textarea[data-rg-count]').forEach(function (ta) {
        var out = document.getElementById(ta.getAttribute('data-rg-count'));
        function upd() {
            var n = ta.value.length, lines = ta.value.split('\n').filter(function (x) { return x.trim(); }).length;
            var long = n > 330 || lines > 8;
            out.className = 'rg-count' + (long ? ' warn' : '');
            out.textContent = n ? (n + ' tekens' + (long ? ' · lang voor een slide: de slide toont dan niet alles, de mp3 wel' : '')) : '';
        }
        ta.addEventListener('input', upd); upd();
    });
    </script>
    <?php
    return ob_get_clean();
}
