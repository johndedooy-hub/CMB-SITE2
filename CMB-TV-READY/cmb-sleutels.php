<?php
/**
 * CMB SLEUTELS: alle API-sleutels op één plek. Een nieuwe sleutel wordt opgeslagen in cmb-config.php (buiten de openbare map)
 * als dat bestand beschreven kan worden, anders in de instellingen (cmb-data). Sleutels worden nooit helemaal getoond.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-nav.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
$isApi = isset($_GET['action']);
if (empty($_SESSION['cmb_auth'])) {
    if ($isApi) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Niet ingelogd. Log eerst in via cmb-admin.php.']); }
    else header('Location: cmb-admin.php');
    exit;
}
if (empty($_SESSION['cmb_keys_csrf'])) $_SESSION['cmb_keys_csrf'] = bin2hex(random_bytes(16));

/** id => [naam, config-sleutel, instelling-sleutel (of ''), patroon, uitleg] */
function cmb_keys_defs() {
    return [
        'ns'         => ['NS (treinstoringen)', 'ns_key', 'ov_key', '/^[A-Za-z0-9]{16,64}$/', 'Gratis sleutel van het NS-ontwikkelaarsportaal'],
        'openai'     => ['OpenAI (Stemstudio)', 'openai_key', 'openai_key', '/^sk-[A-Za-z0-9_\-]{20,250}$/', 'Begint met sk-. Van platform.openai.com'],
        'elevenlabs' => ['ElevenLabs (stem en jingles)', 'elevenlabs_key', 'elevenlabs_key', '/^[A-Za-z0-9_\-]{20,120}$/', 'Van elevenlabs.io (profiel, API Keys)'],
        'anthropic'  => ['Claude (Anthropic)', 'anthropic_key', '', '/^sk-ant-[A-Za-z0-9_\-]{20,250}$/', 'Begint met sk-ant-'],
        'gemini'     => ['Gemini (Google)', 'gemini_key', '', '/^[A-Za-z0-9_\-]{20,80}$/', 'Gratis, via aistudio.google.com'],
        'openrouter' => ['OpenRouter', 'openrouter_key', '', '/^sk-or-[A-Za-z0-9_\-]{20,250}$/', 'Begint met sk-or-. Van openrouter.ai/keys'],
    ];
}
function cmb_keys_json($a, $code = 200) { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }
function cmb_keys_mask($k) { $k = (string)$k; return $k === '' ? '' : (strlen($k) <= 8 ? '••••' : substr($k, 0, 3) . '••••••' . substr($k, -4)); }
/** Waar staat de sleutel nu? [waarde, bron] */
function cmb_keys_current($id) {
    $d = cmb_keys_defs()[$id]; $s = cmb_load_settings_safe();
    $c = trim((string)cmb_cfg($d[1], ''));
    if ($c !== '') return [$c, 'cmb-config.php'];
    if ($d[2] !== '' && trim((string)($s[$d[2]] ?? '')) !== '') return [trim((string)$s[$d[2]]), 'instellingen'];
    $f = cmb_data_dir() . ($id === 'openai' ? 'openai-key.php' : $id . '-key.php');
    if (is_file($f)) { $r = @include $f; if (is_string($r) && trim($r) !== '') return [trim($r), 'bestand in cmb-data']; }
    return ['', ''];
}
/** Schrijft 'sleutel' => 'waarde' in cmb-config.php (met back-up en controle). */
function cmb_keys_write_config($ckey, $val, &$why) {
    $p = cmb_config_path();
    if ($p === '' || !is_file($p) || !is_writable($p) || !is_writable(dirname($p))) { $why = 'cmb-config.php is niet beschrijfbaar'; return false; }
    $src = (string)file_get_contents($p);
    $lit = var_export((string)$val, true);
    $re = "/^(\\s*'" . preg_quote($ckey, '/') . "'\\s*=>\\s*)'(?:[^'\\\\]|\\\\.)*'/m";
    if (preg_match($re, $src)) $new = preg_replace_callback($re, function ($m) use ($lit) { return $m[1] . $lit; }, $src, 1);
    else {
        $i = strrpos($src, '];');
        if ($i === false) { $why = 'indeling van cmb-config.php niet herkend'; return false; }
        $new = substr($src, 0, $i) . "    '" . $ckey . "' => " . $lit . ",\n" . substr($src, $i);
    }
    // eerst een proefbestand controleren
    $tmp = dirname($p) . '/cmb-config.tmp-' . bin2hex(random_bytes(4)) . '.php';
    if (@file_put_contents($tmp, $new) === false) { $why = 'proefbestand schrijven mislukt'; return false; }
    $ok = false;
    try { $r = (function ($f) { return include $f; })($tmp); $ok = is_array($r) && ($r[$ckey] ?? null) === (string)$val; } catch (\Throwable $e) { $ok = false; }
    @unlink($tmp);
    if (!$ok) { $why = 'controle van het nieuwe bestand mislukte (niets gewijzigd)'; return false; }
    @copy($p, $p . '.bak-' . date('dmHi'));
    if (@file_put_contents($p, $new, LOCK_EX) === false) { $why = 'opslaan mislukt'; return false; }
    if (function_exists('opcache_invalidate')) @opcache_invalidate($p, true);
    return true;
}
function cmb_keys_http($url, $headers) {
    $ch = curl_init($url); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'CMB-Sleutels/1.0', CURLOPT_ENCODING => '']);
    curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $e = curl_error($ch); curl_close($ch); return [$c, $e];
}

if ($isApi) {
    $act = $_GET['action']; $defs = cmb_keys_defs();
    $id = (string)($_POST['id'] ?? $_GET['id'] ?? '');
    if (!isset($defs[$id])) cmb_keys_json(['error' => 'Onbekende sleutel'], 400);
    if ($act === 'test') {
        [$k, $src] = cmb_keys_current($id);
        if ($k === '') cmb_keys_json(['ok' => false, 'msg' => 'Nog geen sleutel ingevuld.']);
        if (!function_exists('curl_init')) cmb_keys_json(['ok' => false, 'msg' => 'cURL ontbreekt op deze server.']);
        switch ($id) {
            case 'ns': [$c, $e] = cmb_keys_http('https://gateway.apiportal.ns.nl/disruptions/v3?isActive=true', ['Ocp-Apim-Subscription-Key: ' . $k]); break;
            case 'openai': [$c, $e] = cmb_keys_http('https://api.openai.com/v1/models', ['Authorization: Bearer ' . $k]); break;
            case 'elevenlabs':
                $ch = curl_init('https://api.elevenlabs.io/v1/user'); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_HTTPHEADER => ['xi-api-key: ' . $k]]);
                $b = (string)curl_exec($ch); $c = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $e = curl_error($ch); curl_close($ch);
                if ($c === 401 && stripos($b, 'missing_permissions') !== false) cmb_keys_json(['ok' => true, 'msg' => 'Sleutel is goed (hij mist alleen het recht om je account te lezen, dat is prima).']);
                break;
            case 'anthropic': [$c, $e] = cmb_keys_http('https://api.anthropic.com/v1/models', ['x-api-key: ' . $k, 'anthropic-version: 2023-06-01']); break;
            case 'gemini': [$c, $e] = cmb_keys_http('https://generativelanguage.googleapis.com/v1beta/models?key=' . rawurlencode($k), []); break;
            default: [$c, $e] = cmb_keys_http('https://openrouter.ai/api/v1/auth/key', ['Authorization: Bearer ' . $k]);
        }
        if ($c === 200) cmb_keys_json(['ok' => true, 'msg' => 'Werkt.']);
        if ($c === 401 || $c === 403) cmb_keys_json(['ok' => false, 'msg' => 'Sleutel wordt geweigerd (' . $c . '): verlopen of niet goed.']);
        if ($c === 429) cmb_keys_json(['ok' => false, 'msg' => 'Sleutel is goed, maar het tegoed of de limiet is op (429).']);
        cmb_keys_json(['ok' => false, 'msg' => 'Geen antwoord: ' . ($e ?: 'HTTP ' . $c)]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['cmb_keys_csrf'], (string)($_POST['csrf'] ?? ''))) cmb_keys_json(['error' => 'Sessie verlopen, ververs de pagina.'], 403);
    $d = $defs[$id];
    if ($act === 'save') {
        $v = trim((string)($_POST['key'] ?? ''));
        if (!preg_match($d[3], $v)) cmb_keys_json(['error' => 'Dit lijkt niet op een sleutel van ' . $d[0] . ' (' . $d[4] . ').'], 400);
        $why = '';
        if (cmb_keys_write_config($d[1], $v, $why)) {
            // oude kopie in de instellingen opruimen
            if ($d[2] !== '') { $s = cmb_load_settings_safe(); if (!empty($s[$d[2]])) { $s[$d[2]] = ''; @file_put_contents(cmb_data_dir() . 'cmb-settings.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX); } }
            cmb_keys_json(['ok' => true, 'where' => 'cmb-config.php (buiten de openbare map)']);
        }
        if ($d[2] === '') cmb_keys_json(['error' => 'Opslaan in cmb-config.php lukte niet: ' . $why . '.'], 500);
        $s = cmb_load_settings_safe(); $s[$d[2]] = $v;
        @file_put_contents(cmb_data_dir() . 'cmb-settings.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        cmb_keys_json(['ok' => true, 'where' => 'instellingen (cmb-config.php was niet beschrijfbaar: ' . $why . ')']);
    }
    if ($act === 'clear') {
        $why = ''; $done = false;
        if (trim((string)cmb_cfg($d[1], '')) !== '') $done = cmb_keys_write_config($d[1], '', $why);
        if ($d[2] !== '') { $s = cmb_load_settings_safe(); if (!empty($s[$d[2]])) { $s[$d[2]] = ''; @file_put_contents(cmb_data_dir() . 'cmb-settings.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX); $done = true; } }
        cmb_keys_json(['ok' => true]);
    }
    if ($act === 'move') {
        if ($d[2] === '') cmb_keys_json(['error' => 'Niets te verplaatsen'], 400);
        $s = cmb_load_settings_safe(); $v = trim((string)($s[$d[2]] ?? ''));
        if ($v === '') cmb_keys_json(['error' => 'Er staat geen sleutel in de instellingen.'], 400);
        $why = '';
        if (!cmb_keys_write_config($d[1], $v, $why)) cmb_keys_json(['error' => 'Verplaatsen lukte niet: ' . $why . '.'], 500);
        $s[$d[2]] = ''; @file_put_contents(cmb_data_dir() . 'cmb-settings.json', json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
        cmb_keys_json(['ok' => true]);
    }
    cmb_keys_json(['error' => 'Onbekend'], 400);
}
$rows = [];
foreach (cmb_keys_defs() as $id => $d) { [$k, $src] = cmb_keys_current($id); $rows[$id] = ['d' => $d, 'mask' => cmb_keys_mask($k), 'src' => $src]; }
$cfgPath = cmb_config_path(); $cfgOk = $cfgPath !== '' && is_writable($cfgPath) && is_writable(dirname($cfgPath));
$h = function ($x) { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); };
?><!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>CMB Sleutels</title>
<style>
*{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
body{background:#070812;color:#f7f7ff;margin:0;padding:20px}
.wrap{max-width:900px;margin:0 auto}
h1{margin:6px 0 4px;font-size:22px}.sub{color:#a8aac8;margin:0 0 14px;font-size:14px}
.card{background:#121427;border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:14px 16px;margin-bottom:12px}
.card h2{margin:0;font-size:15px;color:#ffd166}
.st{font-size:13px;margin:6px 0 8px;color:#a8aac8}
.st b{color:#fff}.good{color:#7dffb2}.bad{color:#ff7a90}
.row{display:flex;gap:8px;flex-wrap:wrap}
input[type=text]{flex:1;min-width:200px;background:#0b0d1c;border:1px solid rgba(255,255,255,.15);border-radius:9px;color:#fff;padding:10px;font-size:14px}
button{background:#63f6ff;color:#070812;border:0;border-radius:9px;padding:10px 14px;font-weight:800;font-size:13px;cursor:pointer}
button.sec{background:#1c203d;color:#fff;border:1px solid rgba(255,255,255,.2)}
.msg{font-size:13px;margin-top:6px;min-height:16px}
.note{background:#0d0f1e;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:10px 12px;font-size:13px;color:#c9cbe0;margin-bottom:14px}
</style></head><body><div class="wrap">
<?= cmb_nav('sleutels', 'cmb-admin.php?logout=1') ?>
<h1>🔐 Sleutels</h1>
<p class="sub">Alle API-sleutels op één plek. Een nieuwe sleutel vervangt de oude. Sleutels worden nooit helemaal getoond.</p>
<div class="note">Opslagplaats: <b><?= $cfgOk ? 'cmb-config.php (buiten de openbare map), beschrijfbaar ✓' : ($cfgPath !== '' ? 'cmb-config.php gevonden maar niet beschrijfbaar; sleutels voor NS en OpenAI komen dan in de instellingen' : 'geen cmb-config.php gevonden; sleutels voor NS en OpenAI komen in de instellingen') ?></b>. De sleutel geldt voor alle sites die dezelfde cmb-config.php gebruiken.</div>
<?php foreach ($rows as $id => $r): ?>
<div class="card" id="c-<?= $h($id) ?>">
    <h2><?= $h($r['d'][0]) ?></h2>
    <div class="st"><?= $r['mask'] !== '' ? 'Ingevuld: <b>' . $h($r['mask']) . '</b> · staat in ' . $h($r['src']) : 'Nog niet ingevuld' ?> · <?= $h($r['d'][4]) ?></div>
    <div class="row">
        <input type="text" autocomplete="off" placeholder="Plak hier een nieuwe sleutel" id="k-<?= $h($id) ?>">
        <button type="button" onclick="act('<?= $h($id) ?>','save')">Opslaan</button>
        <button type="button" class="sec" onclick="act('<?= $h($id) ?>','test')">Testen</button>
        <?php if ($r['src'] === 'instellingen' && $r['d'][2] !== '' && $cfgOk): ?><button type="button" class="sec" onclick="act('<?= $h($id) ?>','move')">Verplaats naar cmb-config.php</button><?php endif; ?>
        <?php if ($r['mask'] !== ''): ?><button type="button" class="sec" onclick="if(confirm('Sleutel wissen?'))act('<?= $h($id) ?>','clear')">Wissen</button><?php endif; ?>
    </div>
    <div class="msg" id="m-<?= $h($id) ?>"></div>
</div>
<?php endforeach; ?>
</div>
<script>
var CSRF=<?= json_encode($_SESSION['cmb_keys_csrf']) ?>;
function act(id,a){var m=document.getElementById('m-'+id);m.className='msg';m.textContent='Even geduld…';var f=new FormData();f.append('id',id);f.append('csrf',CSRF);
 if(a==='save')f.append('key',document.getElementById('k-'+id).value.trim());
 fetch('?action='+a+(a==='test'?'&id='+id:''),{method:a==='test'?'GET':'POST',body:a==='test'?null:f}).then(function(r){return r.json()}).then(function(d){
  if(a==='test'){m.className='msg '+(d.ok?'good':'bad');m.textContent=d.msg||d.error;return}
  if(d.error){m.className='msg bad';m.textContent=d.error;return}
  m.className='msg good';m.textContent=a==='save'?'Opgeslagen in '+d.where+'. Een ogenblik, dan ververs ik…':'Gedaan.';setTimeout(function(){location.reload()},1400)
 }).catch(function(){m.className='msg bad';m.textContent='Mislukt.'})}
</script></body></html>
