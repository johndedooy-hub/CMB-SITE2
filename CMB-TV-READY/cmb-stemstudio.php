<?php
/**
 * CMB STEMSTUDIO: tekst inspreken met een AI-stem (OpenAI), met stijl, tempo en een effect, opslaan als mp3.
 * Sleutel: omgevingsvariabele OPENAI_API_KEY, of cmb-data/openai-key.php (return 'sk-...';), of invullen op deze pagina.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-nav.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';   // cmb_load_settings_safe()
$isApi = isset($_GET['action']);
if (empty($_SESSION['cmb_auth'])) {
    if ($isApi) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error' => 'Niet ingelogd. Log eerst in via cmb-admin.php.']); }
    else header('Location: cmb-admin.php');
    exit;
}

const CMB_STEM_MODEL = 'gpt-4o-mini-tts';
const CMB_STEM_VOICES = ['coral' => 'Coral (warm, vrouw)', 'nova' => 'Nova (helder, vrouw)', 'shimmer' => 'Shimmer (zacht, vrouw)', 'sage' => 'Sage (rustig, neutraal)', 'alloy' => 'Alloy (neutraal)', 'ash' => 'Ash (man, rauw)', 'echo' => 'Echo (man)', 'onyx' => 'Onyx (diep, man)', 'fable' => 'Fable (verhalend)', 'ballad' => 'Ballad (expressief)', 'verse' => 'Verse (levendig)'];

function cmb_stem_key() {
    $k = trim((string)getenv('OPENAI_API_KEY'));
    if ($k !== '') return $k;
    $c = trim((string)cmb_cfg('openai_key', '')); if ($c !== '') return $c;
    $f = cmb_data_dir() . 'openai-key.php';
    if (is_file($f)) { $r = @include $f; if (is_string($r) && trim($r) !== '') return trim($r); }
    $s = cmb_load_settings_safe();
    return trim((string)($s['openai_key'] ?? ''));
}
function cmb_el_key() {
    $k = trim((string)getenv('ELEVENLABS_API_KEY'));
    if ($k !== '') return $k;
    $c = trim((string)cmb_cfg('elevenlabs_key', '')); if ($c !== '') return $c;
    $s = cmb_load_settings_safe();
    return trim((string)($s['elevenlabs_key'] ?? ''));
}
/** Aanroep naar ElevenLabs. Geeft [http, body, fout]. */
function cmb_el_call($method, $path, $key, $payload = null, $timeout = 90) {
    $ch = curl_init('https://api.elevenlabs.io' . $path);
    $o = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_HTTPHEADER => ['xi-api-key: ' . $key, 'Content-Type: application/json', 'Accept: */*']];
    if ($method === 'POST') { $o[CURLOPT_POST] = true; $o[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE); }
    curl_setopt_array($ch, $o);
    $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    return [$code, $body, $err];
}
function cmb_el_error($code, $body, $err) {
    $j = json_decode($body, true); $d = $j['detail'] ?? null;
    $m = is_array($d) ? ($d['message'] ?? ($d['status'] ?? '')) : (is_string($d) ? $d : '');
    if ($code === 401) $m = 'De ElevenLabs-sleutel wordt geweigerd of mist rechten (401). ' . $m;
    elseif ($code === 402 || $code === 403) $m = 'Dit mag niet met het huidige ElevenLabs-abonnement (' . $code . '). ' . $m;
    elseif ($code === 429) $m = 'Te veel verzoeken of tegoed op (429). ' . $m;
    return 'ElevenLabs: ' . trim($m ?: ($err ?: 'onbekende fout')) . ' (HTTP ' . $code . ')';
}
function cmb_stem_dir() { $d = rtrim(cmb_upload_dir(), '/\\') . '/stem/'; if (!is_dir($d)) @mkdir($d, 0755, true); return $d; }
function cmb_stem_json($a, $code = 200) { http_response_code($code); header('Content-Type: application/json; charset=utf-8'); echo json_encode($a, JSON_UNESCAPED_UNICODE); exit; }

if ($isApi) {
    $act = $_GET['action'];
    if ($act === 'elvoices') {
        $key = cmb_el_key();
        if ($key === '') cmb_stem_json(['error' => 'Nog geen ElevenLabs-sleutel. Zet hem op de pagina Sleutels.'], 400);
        [$code, $body, $err] = cmb_el_call('GET', '/v1/voices', $key, null, 30);
        if ($code !== 200) cmb_stem_json(['error' => cmb_el_error($code, $body, $err)], 502);
        $o = []; foreach ((json_decode($body, true)['voices'] ?? []) as $v) { if (!empty($v['voice_id'])) $o[] = ['id' => $v['voice_id'], 'name' => (string)($v['name'] ?? $v['voice_id']), 'cat' => (string)($v['category'] ?? '')]; }
        cmb_stem_json(['voices' => $o]);
    }
    if ($act === 'elmusic' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $key = cmb_el_key();
        if ($key === '') cmb_stem_json(['error' => 'Nog geen ElevenLabs-sleutel. Zet hem op de pagina Sleutels.'], 400);
        $pr = trim((string)($_POST['prompt'] ?? ''));
        if ($pr === '' || mb_strlen($pr) > 1500) cmb_stem_json(['error' => 'Beschrijf de muziek (maximaal 1500 tekens).'], 400);
        $sec = max(3, min(60, (int)($_POST['sec'] ?? 15)));
        $payload = ['prompt' => $pr, 'music_length_ms' => $sec * 1000, 'model_id' => 'music_v1'];
        if (!empty($_POST['instr'])) $payload['force_instrumental'] = true;
        [$code, $body, $err] = cmb_el_call('POST', '/v1/music?output_format=mp3_44100_128', $key, $payload, 240);
        if ($code !== 200) cmb_stem_json(['error' => cmb_el_error($code, $body, $err)], 502);
        header('Content-Type: audio/mpeg'); header('Cache-Control: no-store'); echo $body; exit;
    }
    if ($act === 'speak' && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['engine'] ?? '') === 'eleven') {
        $key = cmb_el_key();
        if ($key === '') cmb_stem_json(['error' => 'Nog geen ElevenLabs-sleutel. Zet hem op de pagina Sleutels.'], 400);
        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '' || mb_strlen($text) > 2000) cmb_stem_json(['error' => 'Typ een tekst (maximaal 2000 tekens).'], 400);
        $vid = (string)($_POST['voice'] ?? '');
        if (!preg_match('/^[A-Za-z0-9]{10,40}$/', $vid)) cmb_stem_json(['error' => 'Kies eerst een ElevenLabs-stem.'], 400);
        $expr = max(0.0, min(1.0, (float)($_POST['expr'] ?? 0.4)));
        $speed = max(0.7, min(1.2, (float)($_POST['speed'] ?? 1)));
        $payload = ['text' => $text, 'model_id' => 'eleven_multilingual_v2', 'voice_settings' => ['stability' => round(0.8 - $expr * 0.55, 2), 'similarity_boost' => 0.75, 'style' => round($expr * 0.7, 2), 'use_speaker_boost' => true, 'speed' => $speed]];
        [$code, $body, $err] = cmb_el_call('POST', '/v1/text-to-speech/' . $vid . '?output_format=mp3_44100_128', $key, $payload);
        if ($code !== 200) cmb_stem_json(['error' => cmb_el_error($code, $body, $err)], 502);
        header('Content-Type: audio/mpeg'); header('Cache-Control: no-store'); echo $body; exit;
    }
    if ($act === 'speak' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $key = cmb_stem_key();
        if ($key === '') cmb_stem_json(['error' => 'Nog geen OpenAI-sleutel ingevuld.'], 400);
        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '' || mb_strlen($text) > 2000) cmb_stem_json(['error' => 'Typ een tekst (maximaal 2000 tekens).'], 400);
        $voice = isset(CMB_STEM_VOICES[$_POST['voice'] ?? '']) ? $_POST['voice'] : 'coral';
        $instr = mb_substr(trim((string)($_POST['instructions'] ?? '')), 0, 600);
        $speed = max(0.6, min(1.6, (float)($_POST['speed'] ?? 1)));
        $payload = ['model' => CMB_STEM_MODEL, 'input' => $text, 'voice' => $voice, 'response_format' => 'mp3', 'instructions' => 'Spreek Nederlands met een natuurlijke, duidelijke uitspraak. ' . $instr];
        if (abs($speed - 1.0) > 0.01) $payload['speed'] = $speed;
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 90, CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $key, 'Content-Type: application/json'], CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE)]);
        $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
        if ($code !== 200) {
            $j = json_decode($body, true); $m = $j['error']['message'] ?? ($err ?: 'Onbekende fout');
            if ($code === 401) $m = 'De sleutel is niet goed (401). Controleer de OpenAI-sleutel.';
            cmb_stem_json(['error' => 'OpenAI: ' . $m . ' (HTTP ' . $code . ')'], 502);
        }
        header('Content-Type: audio/mpeg'); header('Cache-Control: no-store'); echo $body; exit;
    }
    if ($act === 'savekey' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $k = trim((string)($_POST['key'] ?? ''));
        $f = cmb_data_dir() . 'cmb-settings.json';
        $s = cmb_load_settings_safe();
        if (!empty($_POST['clear'])) $s['openai_key'] = '';
        elseif (preg_match('/^sk-[A-Za-z0-9_\-]{20,200}$/', $k)) $s['openai_key'] = $k;
        else cmb_stem_json(['error' => 'Dit lijkt geen OpenAI-sleutel (begint met sk-).'], 400);
        @file_put_contents($f, json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        cmb_stem_json(['ok' => true, 'has' => !empty($s['openai_key'])]);
    }
    if ($act === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $f = $_FILES['audio'] ?? null;
        if (!$f || $f['error'] !== UPLOAD_ERR_OK || $f['size'] > 15 * 1048576) cmb_stem_json(['error' => 'Geen geldig geluidsbestand.'], 400);
        $raw = (string)file_get_contents($f['tmp_name']);
        $ext = '';
        if (substr($raw, 0, 4) === 'RIFF') $ext = 'wav';
        elseif (substr($raw, 0, 3) === 'ID3' || (strlen($raw) > 2 && ord($raw[0]) === 0xFF && (ord($raw[1]) & 0xE0) === 0xE0)) $ext = 'mp3';
        if ($ext === '') cmb_stem_json(['error' => 'Alleen mp3 of wav.'], 400);
        $name = strtolower(preg_replace('/[^a-z0-9]+/i', '-', (string)($_POST['name'] ?? ''))); $name = trim($name, '-') ?: 'stem';
        $fn = mb_substr($name, 0, 40) . '-' . (new DateTime('now', new DateTimeZone('Europe/Amsterdam')))->format('dm-Hi') . '.' . $ext;
        if (@file_put_contents(cmb_stem_dir() . $fn, $raw) === false) cmb_stem_json(['error' => 'Opslaan lukt niet (rechten van cmb-uploads/stem?).'], 500);
        cmb_stem_json(['ok' => true, 'file' => $fn]);
    }
    if ($act === 'getdict') { $f = cmb_data_dir() . 'stem-uitspraak.txt'; cmb_stem_json(['text' => is_file($f) ? (string)file_get_contents($f) : '']); }
    if ($act === 'savedict' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = mb_substr((string)($_POST['text'] ?? ''), 0, 6000);
        @file_put_contents(cmb_data_dir() . 'stem-uitspraak.txt', $t, LOCK_EX);
        cmb_stem_json(['ok' => true]);
    }
    if ($act === 'list') {
        $o = [];
        foreach (glob(cmb_stem_dir() . '*.{mp3,wav}', GLOB_BRACE) ?: [] as $p) $o[] = ['file' => basename($p), 'url' => 'cmb-uploads/stem/' . rawurlencode(basename($p)), 'kb' => (int)round(filesize($p) / 1024), 't' => filemtime($p)];
        usort($o, function ($a, $b) { return $b['t'] <=> $a['t']; });
        cmb_stem_json(['files' => $o]);
    }
    if ($act === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = basename((string)($_POST['file'] ?? ''));
        if (preg_match('/^[a-z0-9._-]+\.(mp3|wav)$/i', $b) && is_file(cmb_stem_dir() . $b)) @unlink(cmb_stem_dir() . $b);
        cmb_stem_json(['ok' => true]);
    }
    cmb_stem_json(['error' => 'Onbekend'], 400);
}
$hasKey = cmb_stem_key() !== '';
$h = function ($x) { return htmlspecialchars((string)$x, ENT_QUOTES, 'UTF-8'); };
$styles = [
    'Vlot en vrolijk, als een radiopresentator' => 'Vlot, vrolijk en energiek, als een radiopresentator die een bumper inspreekt. Levendige intonatie, een glimlach in de stem.',
    'Snel en enthousiast (reclamestem)' => 'Snel, enthousiast en overtuigend, als een reclamespot op de radio. Veel energie, duidelijke nadruk op de sleutelwoorden.',
    'Diepe, warme nachtstem' => 'Diep, warm en rustig, als een late avond radiopresentator. Zachte, gladde voordracht met korte pauzes.',
    'Stoer, filmtrailer' => 'Stoer, dramatisch en gedragen, als een filmtrailer. Lage stem, nadruk op elk belangrijk woord, korte pauzes.',
    'Warme voice-over (diep, glad)' => 'Diepe, warme en gladde voice-over, zelfverzekerd en uitnodigend, als een ervaren reclamestem. Rustig tempo, rond en vol, nadruk op de sleutelwoorden, korte pauzes na belangrijke zinsdelen.',
    'Late-night radio (intiem)' => 'Intiem en dichtbij, alsof je tegen één luisteraar praat laat op de avond. Zacht, laag en vertrouwd, met een kleine glimlach.',
    'Reclame met knipoog' => 'Speels en snel, met een knipoog en humor. Licht overdreven intonatie, een beetje ondeugend, als een slimme radiospot.',
    'Nieuwslezer (helder, zakelijk)' => 'Helder, zakelijk en betrouwbaar, als een nieuwslezer. Gelijkmatig tempo, duidelijke articulatie, neutrale toon.',
    'Rustig en dromerig' => 'Rustig, zacht en dromerig, langzaam en ontspannen.',
    'Feestelijk, party-omroeper' => 'Luid, feestelijk en opzwepend, als een omroeper op een groot dancefeest. Veel energie en opbouw.',
];
?><!DOCTYPE html>
<html lang="nl"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>CMB Stemstudio</title>
<style>
*{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
body{background:#070812;color:#f7f7ff;margin:0;padding:20px}
.wrap{max-width:900px;margin:0 auto}
h1{margin:6px 0 4px;font-size:22px}
.sub{color:#a8aac8;margin:0 0 16px;font-size:14px}
.card{background:#121427;border:1px solid rgba(255,255,255,.1);border-radius:14px;padding:16px;margin-bottom:14px}
.card h2{margin:0 0 10px;font-size:15px;color:#ffd166}
label{display:block;font-size:12px;color:#a8aac8;margin:10px 0 4px}
textarea,input[type=text],select{width:100%;background:#0b0d1c;border:1px solid rgba(255,255,255,.15);border-radius:9px;color:#fff;padding:10px;font-size:15px}
textarea{min-height:110px;resize:vertical}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
@media(max-width:640px){.row{grid-template-columns:1fr}}
button{background:#63f6ff;color:#070812;border:0;border-radius:10px;padding:12px 18px;font-weight:800;font-size:14px;cursor:pointer}
button.sec{background:#1c203d;color:#fff;border:1px solid rgba(255,255,255,.2)}
button:disabled{opacity:.5;cursor:wait}
.btns{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}
.msg{margin-top:10px;font-size:13px;min-height:18px}
.err{color:#ff7a90}.ok{color:#7dffb2}
.item{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:8px 0;border-top:1px solid rgba(255,255,255,.08)}
.item .n{flex:1;min-width:160px;font-size:13px;word-break:break-all}
.item audio{height:34px;max-width:260px}
.item a,.item button{font-size:12px;padding:6px 10px}
.item a{color:#63f6ff;text-decoration:none;border:1px solid rgba(99,246,255,.4);border-radius:8px}
.hint{font-size:12px;color:#a8aac8;margin-top:6px}
label.chk{display:flex;gap:8px;align-items:center;font-size:13px;color:#f7f7ff}
label.chk input{width:auto}
.res{padding:10px 0;border-top:1px solid rgba(255,255,255,.08)}
.res .vn{font-size:13px;color:#ffd166;margin-bottom:4px}
</style></head><body><div class="wrap">
<?= cmb_nav('stemstudio', 'cmb-admin.php?logout=1') ?>
<h1>🎙️ Stemstudio</h1>
<p class="sub">Typ een tekst, kies een stem en stijl, zet er een effect op en bewaar het als mp3.</p>

<div class="card" id="keycard" <?= $hasKey ? 'hidden' : '' ?>>
    <h2>OpenAI-sleutel</h2>
    <div class="hint">Nog geen sleutel. Plak hier uw OpenAI-sleutel (begint met sk-). Die blijft op de server. Heeft u er nog geen, maak er een op platform.openai.com (onder "API keys").</div>
    <input type="text" id="key" autocomplete="off" placeholder="sk-...">
    <div class="btns"><button type="button" onclick="saveKey()">Sleutel opslaan</button></div>
    <div class="msg" id="keymsg"></div>
</div>

<div class="card">
    <h2>Tekst</h2>
    <textarea id="text" maxlength="2000" placeholder="Bijvoorbeeld: [blij] Je luistert naar Focus Fantastique! [pauze] [fluisterend] Je maatjes in muziek."></textarea>
    <div class="hint">Toon per zin: zet een woord tussen blokhaken vóór de zin, bijvoorbeeld [blij], [serieus], [fluisterend], [enthousiast], [dramatisch], [lachend], [verbaasd], [rustig]. Pauze: [pauze] of [pauze 1.5] (seconden). Elke eigen toon mag ook, het woord wordt als aanwijzing gebruikt.</div>
    <div class="row">
        <div><label>Motor</label><select id="engine" onchange="engChange()"><option value="openai">OpenAI</option><option value="eleven">ElevenLabs</option></select></div>
        <div id="elbox" hidden><label>Expressie: <b id="exv">40</b>%</label><input type="range" id="expr" min="0" max="100" step="5" value="40" oninput="document.getElementById('exv').textContent=this.value"></div>
    </div>
    <div class="hint" id="elhint" hidden>Bij ElevenLabs geldt de stijllijst hieronder niet; gebruik Expressie en de toon per zin ([blij], [rustig], ...).</div>
    <div class="row">
        <div><label>Stem</label><select id="voice"><?php foreach (CMB_STEM_VOICES as $k => $l): ?><option value="<?= $h($k) ?>"><?= $h($l) ?></option><?php endforeach; ?></select></div>
        <div><label>Stijl</label><select id="style" onchange="document.getElementById('instr').value=this.value"><?php foreach ($styles as $l => $v): ?><option value="<?= $h($v) ?>"><?= $h($l) ?></option><?php endforeach; ?><option value="">Eigen stijl (hieronder typen)</option></select></div>
    </div>
    <label>Stijl in eigen woorden (mag aangepast)</label>
    <textarea id="instr" style="min-height:64px" maxlength="600"><?= $h(reset($styles)) ?></textarea>
    <div class="row">
        <div><label>Tempo: <b id="spv">1,0</b>×</label><input type="range" id="speed" min="0.8" max="1.4" step="0.05" value="1" oninput="document.getElementById('spv').textContent=this.value.replace('.',',')"></div>
        <div><label>Effect</label><select id="fx">
            <option value="none">Geen effect</option><option value="radio">Radio (warm, krachtig)</option><option value="hall">Zaal (galm)</option><option value="echo">Echo</option>
            <option value="phone">Telefoon</option><option value="mega">Megafoon</option><option value="robot">Robot</option><option value="deep">Dieper</option><option value="high">Hoger</option>
        </select></div>
    </div>
    <label class="chk"><input type="checkbox" id="three"> Drie stemmen tegelijk maken om te vergelijken</label>
    <label>Naam om te bewaren</label>
    <input type="text" id="name" placeholder="bijv. bumper-focus">
    <div class="btns"><button type="button" id="go" onclick="make()">🔊 Maken en beluisteren</button></div>
    <div class="msg" id="msg"></div>
    <div id="results"></div>
</div>

<div class="card">
    <h2>Jingle maken (ElevenLabs muziek)</h2>
    <div class="hint">Beschrijf de muziek, bijvoorbeeld: "Korte vrolijke radiojingle, dance, synths, 8 seconden, eindigt met een klap". Gebruikt tegoed van uw ElevenLabs-account.</div>
    <textarea id="mprompt" maxlength="1500" style="min-height:70px"></textarea>
    <div class="row">
        <div><label>Lengte</label><select id="msec"><option>5</option><option>8</option><option selected>10</option><option>15</option><option>20</option><option>30</option><option>45</option><option>60</option></select><div class="hint">seconden</div></div>
        <div><label class="chk" style="margin-top:28px"><input type="checkbox" id="minstr" checked> Alleen muziek, zonder zang</label></div>
    </div>
    <div class="btns"><button type="button" id="mgo" onclick="makeMusic()">🎵 Jingle maken</button></div>
    <div class="msg" id="mmsg"></div>
    <div id="mres"></div>
</div>

<div class="card">
    <h2>Uitspraakhulp</h2>
    <div class="hint">Eén regel per woord: <b>woord = zo uitspreken</b>. Bijvoorbeeld <b>Focus = Foh-kus</b> of <b>DJ = diejee</b>. Wordt automatisch toegepast.</div>
    <textarea id="dict" style="min-height:90px" placeholder="Focus = Foh-kus&#10;DJ = diejee"></textarea>
    <div class="btns"><button type="button" class="sec" onclick="saveDict()">Lijst opslaan</button></div>
    <div class="msg" id="dmsg"></div>
</div>

<div class="card">
    <h2>Bewaard</h2>
    <div id="lib"><div class="hint">Laden…</div></div>
</div>
</div>
<script>
var raw=null, outBlob=null, outExt='mp3';
function $(i){return document.getElementById(i)}
function say(t,c){var m=$('msg');m.textContent=t||'';m.className='msg '+(c||'')}
function saveKey(){var f=new FormData();f.append('key',$('key').value.trim());fetch('?action=savekey',{method:'POST',body:f}).then(r=>r.json()).then(function(d){var m=$('keymsg');if(d.error){m.textContent=d.error;m.className='msg err'}else{m.textContent='Opgeslagen.';m.className='msg ok';$('keycard').hidden=true}})}

/* ---------- effecten (in de browser, Web Audio) ---------- */
function impulse(ctx,sec,decay){var n=Math.floor(ctx.sampleRate*sec),b=ctx.createBuffer(2,n,ctx.sampleRate);for(var c=0;c<2;c++){var d=b.getChannelData(c);for(var i=0;i<n;i++)d[i]=(Math.random()*2-1)*Math.pow(1-i/n,decay)}return b}
function curve(k){var n=2048,c=new Float32Array(n);for(var i=0;i<n;i++){var x=i*2/n-1;c[i]=(1+k)*x/(1+k*Math.abs(x))}return c}
function render(buf,fx){
  var rate=fx==='deep'?0.86:fx==='high'?1.2:1, tail=(fx==='hall'||fx==='echo')?2.5:0.1;
  var len=Math.ceil(buf.length/rate+buf.sampleRate*tail);
  var ctx=new OfflineAudioContext(1,len,buf.sampleRate), src=ctx.createBufferSource();src.buffer=buf;src.playbackRate.value=rate;
  var out=ctx.createGain(), last=src;
  function chain(n){last.connect(n);last=n;return n}
  if(fx==='radio'){var hp=ctx.createBiquadFilter();hp.type='highpass';hp.frequency.value=90;chain(hp);var pk=ctx.createBiquadFilter();pk.type='peaking';pk.frequency.value=2800;pk.gain.value=4;pk.Q.value=0.9;chain(pk);var lo=ctx.createBiquadFilter();lo.type='lowshelf';lo.frequency.value=180;lo.gain.value=3;chain(lo);var cp=ctx.createDynamicsCompressor();cp.threshold.value=-26;cp.ratio.value=5;cp.attack.value=0.004;cp.release.value=0.18;chain(cp)}
  else if(fx==='phone'){var h2=ctx.createBiquadFilter();h2.type='highpass';h2.frequency.value=450;chain(h2);var l2=ctx.createBiquadFilter();l2.type='lowpass';l2.frequency.value=3000;chain(l2);var w=ctx.createWaveShaper();w.curve=curve(8);chain(w)}
  else if(fx==='mega'){var h3=ctx.createBiquadFilter();h3.type='highpass';h3.frequency.value=600;chain(h3);var l3=ctx.createBiquadFilter();l3.type='lowpass';l3.frequency.value=4500;chain(l3);var w3=ctx.createWaveShaper();w3.curve=curve(25);chain(w3);var c3=ctx.createDynamicsCompressor();c3.threshold.value=-30;c3.ratio.value=8;chain(c3)}
  else if(fx==='robot'){var rm=ctx.createGain();rm.gain.value=0;var osc=ctx.createOscillator();osc.frequency.value=55;osc.type='sine';osc.connect(rm.gain);chain(rm);osc.start()}
  if(fx==='echo'){var dry=ctx.createGain();dry.gain.value=1;var dl=ctx.createDelay(1);dl.delayTime.value=0.27;var fb=ctx.createGain();fb.gain.value=0.38;var wet=ctx.createGain();wet.gain.value=0.45;last.connect(dry);dry.connect(out);last.connect(dl);dl.connect(fb);fb.connect(dl);dl.connect(wet);wet.connect(out)}
  else if(fx==='hall'){var d4=ctx.createGain();d4.gain.value=0.9;var cv=ctx.createConvolver();cv.buffer=impulse(ctx,2.2,2.6);var w4=ctx.createGain();w4.gain.value=0.5;last.connect(d4);d4.connect(out);last.connect(cv);cv.connect(w4);w4.connect(out)}
  else{last.connect(out)}
  out.connect(ctx.destination);src.start();
  return ctx.startRendering();
}
function norm(buf){var d=buf.getChannelData(0),p=0,i;for(i=0;i<d.length;i++){var a=Math.abs(d[i]);if(a>p)p=a}var g=p>0?0.89/p:1;var e=d.length;while(e>0&&Math.abs(d[e-1])<0.002)e--;e=Math.min(d.length,e+Math.floor(buf.sampleRate*0.15));var pcm=new Int16Array(e);for(i=0;i<e;i++){var v=Math.max(-1,Math.min(1,d[i]*g));pcm[i]=v<0?v*32768:v*32767}return pcm}
function wav(pcm,rate){var b=new ArrayBuffer(44+pcm.length*2),v=new DataView(b);function s(o,t){for(var i=0;i<t.length;i++)v.setUint8(o+i,t.charCodeAt(i))}s(0,'RIFF');v.setUint32(4,36+pcm.length*2,true);s(8,'WAVE');s(12,'fmt ');v.setUint32(16,16,true);v.setUint16(20,1,true);v.setUint16(22,1,true);v.setUint32(24,rate,true);v.setUint32(28,rate*2,true);v.setUint16(32,2,true);v.setUint16(34,16,true);s(36,'data');v.setUint32(40,pcm.length*2,true);for(var i=0;i<pcm.length;i++)v.setInt16(44+i*2,pcm[i],true);return new Blob([b],{type:'audio/wav'})}
function loadLame(){return new Promise(function(res){if(window.lamejs)return res(true);var s=document.createElement('script');s.src='https://cdnjs.cloudflare.com/ajax/libs/lamejs/1.2.1/lame.min.js';s.onload=function(){res(!!window.lamejs)};s.onerror=function(){res(false)};document.head.appendChild(s)})}
function mp3(pcm,rate){var enc=new lamejs.Mp3Encoder(1,rate,128),out=[],i,k=1152;for(i=0;i<pcm.length;i+=k){var c=enc.encodeBuffer(pcm.subarray(i,i+k));if(c.length)out.push(new Uint8Array(c))}var f=enc.flush();if(f.length)out.push(new Uint8Array(f));return new Blob(out,{type:'audio/mpeg'})}

var TONES={blij:'Vrolijk en opgewekt, met een glimlach.',serieus:'Serieus, rustig en gedragen.',fluisterend:'Fluisterend, zacht en intiem.',enthousiast:'Heel enthousiast en energiek.',dramatisch:'Dramatisch en gedragen, met nadruk.',lachend:'Lachend, alsof je net iets grappigs zegt.',verbaasd:'Verbaasd en nieuwsgierig.',rustig:'Rustig, langzaam en ontspannen.',snel:'Snel en vlot.',stoer:'Stoer en zelfverzekerd.'};
function applyDict(t){var lines=$('dict').value.split(/\r?\n/);lines.forEach(function(l){var m=l.split('=');if(m.length<2)return;var w=m[0].trim(),v=m.slice(1).join('=').trim();if(!w||!v)return;var e=w.replace(/[.*+?^${}()|[\]\\]/g,'\\$&');t=t.replace(new RegExp('(^|[^\\p{L}\\p{N}])'+e+'(?=$|[^\\p{L}\\p{N}])','giu'),function(x,p){return p+v})});return t}
function parseText(t){var parts=[],re=/\[([^\]]+)\]/g,last=0,tone='',tg='',m;
  function push(txt){txt=txt.trim();if(txt)parts.push({type:'say',text:txt,tone:tone,tg:tg})}
  while((m=re.exec(t))){push(t.slice(last,m.index));last=re.lastIndex;var tag=m[1].trim().toLowerCase();var pm=tag.match(/^pauze\s*([0-9]+(?:[.,][0-9]+)?)?$/);if(pm){parts.push({type:'gap',sec:Math.min(5,parseFloat((pm[1]||'0.6').replace(',','.')))})}else{tone=TONES[tag]||('Toon: '+tag+'.');tg=tag}}
  push(t.slice(last));return parts}
var EXPR={'blij':.15,'enthousiast':.25,'dramatisch':.3,'lachend':.25,'verbaasd':.2,'fluisterend':-.2,'rustig':-.2,'serieus':-.15};
var openaiVoices=null;
function eng(){return $('engine').value}
function engChange(){var el=eng()==='eleven';$('elbox').hidden=!el;$('elhint').hidden=!el;var sel=$('voice');
  if(openaiVoices===null)openaiVoices=sel.innerHTML;
  if(!el){sel.innerHTML=openaiVoices;say('');return}
  say('ElevenLabs-stemmen worden opgehaald…');
  fetch('?action=elvoices').then(r=>r.json()).then(function(d){if(d.error){say(d.error,'err');$('engine').value='openai';engChange2();return}
    sel.innerHTML='';d.voices.forEach(function(v){var o=document.createElement('option');o.value=v.id;o.textContent=v.name+(v.cat?' ('+v.cat+')':'');sel.appendChild(o)});say('Stemmen geladen: '+d.voices.length,'ok')}).catch(function(){say('Ophalen mislukt','err')})}
function engChange2(){$('elbox').hidden=true;$('elhint').hidden=true;if(openaiVoices!==null)$('voice').innerHTML=openaiVoices}
function ask(text,voice,instr,speed,tg){var f=new FormData();f.append('text',text);f.append('voice',voice);f.append('instructions',instr);f.append('speed',speed);
  if(eng()==='eleven'){f.append('engine','eleven');f.append('expr',Math.max(0,Math.min(1,parseInt($('expr').value,10)/100+(EXPR[tg]||0))))}
  return fetch('?action=speak',{method:'POST',body:f}).then(function(r){if(!r.ok)return r.json().then(function(d){throw new Error(d.error||'Mislukt')});return r.arrayBuffer()})}
function synth(parts,voice,ac){var base=$('instr').value,speed=$('speed').value,bufs=[];
  return parts.reduce(function(p,pt){return p.then(function(){
    if(pt.type==='gap'){bufs.push({gap:pt.sec});return}
    var ins=(base+' '+(pt.tone||'')).trim();
    return ask(applyDict(pt.text),voice,ins,speed,pt.tg).then(function(ab){return ac.decodeAudioData(ab)}).then(function(b){bufs.push({buf:b})})})},Promise.resolve())
  .then(function(){var rate=ac.sampleRate,total=0,i;bufs.forEach(function(x){x.n=x.gap?Math.floor(x.gap*rate):x.buf.length;total+=x.n+(x.gap?0:Math.floor(rate*0.06))});
    var out=ac.createBuffer(1,Math.max(1,total),rate),d=out.getChannelData(0),pos=0;
    bufs.forEach(function(x){if(x.gap){pos+=x.n;return}var c=x.buf.getChannelData(0);if(x.buf.sampleRate!==rate){var rr=x.buf.sampleRate/rate;for(i=0;i<x.n&&pos+i<d.length;i++)d[pos+i]=c[Math.floor(i*rr)]||0}else{for(i=0;i<x.n&&pos+i<d.length;i++)d[pos+i]=c[i]}pos+=x.n+Math.floor(rate*0.06)});
    return out})}
function finish(buf){return render(buf,$('fx').value).then(function(rb){var pcm=norm(rb),rate=rb.sampleRate;return loadLame().then(function(ok){return ok?{blob:mp3(pcm,rate),ext:'mp3'}:{blob:wav(pcm,rate),ext:'wav'}})})}
var VSETS=['coral','onyx','ash','nova','sage','echo'];
function make(){
  var t=$('text').value.trim();if(!t){say('Typ eerst een tekst.','err');return}
  var parts=parseText(t);if(!parts.some(function(x){return x.type==='say'})){say('Er staat nog geen tekst om uit te spreken.','err');return}
  var v0=$('voice').value,voices=[v0];if($('three').checked){var pool=eng()==='eleven'?Array.prototype.map.call($('voice').options,function(o){return o.value}):VSETS;pool.forEach(function(v){if(voices.length<3&&voices.indexOf(v)<0)voices.push(v)})}
  $('go').disabled=true;$('results').innerHTML='';var AC=window.AudioContext||window.webkitAudioContext,ac=new AC(),n=0;
  voices.reduce(function(p,v){return p.then(function(){say('Stem '+(++n)+' van '+voices.length+' wordt gemaakt…');return synth(parts,v,ac).then(finish).then(function(r){addResult(v,r)})})},Promise.resolve())
  .then(function(){say('Klaar.'+(outExt==='wav'?' (Als wav; mp3 kon niet worden gemaakt.)':''),'ok')})
  .catch(function(e){say(e.message||'Mislukt','err')}).then(function(){ac.close();$('go').disabled=false})
}
function addResult(voice,r){outExt=r.ext;var box=document.createElement('div');box.className='res';
  var vn=document.createElement('div');vn.className='vn';vn.textContent='Stem: '+($('voice').querySelector('option[value="'+voice+'"]')||{textContent:voice}).textContent;box.appendChild(vn);
  var a=document.createElement('audio');a.controls=true;a.style.width='100%';a.src=URL.createObjectURL(r.blob);box.appendChild(a);
  var b=document.createElement('button');b.type='button';b.textContent='💾 Bewaren';b.style.marginTop='8px';b.onclick=function(){saveBlob(r.blob,r.ext,voice)};box.appendChild(b);
  $('results').appendChild(box);if($('results').children.length===1)a.play().catch(function(){})}
function saveBlob(blob,ext,voice){var f=new FormData();f.append('audio',blob,'x.'+ext);f.append('name',($('name').value||'stem')+'-'+voice);
  fetch('?action=save',{method:'POST',body:f}).then(r=>r.json()).then(function(d){if(d.error)say(d.error,'err');else{say('Bewaard als '+d.file,'ok');lib()}})}
function makeMusic(){var p=$('mprompt').value.trim(),m=$('mmsg');if(!p){m.textContent='Beschrijf eerst de muziek.';m.className='msg err';return}
  var f=new FormData();f.append('prompt',p);f.append('sec',$('msec').value);if($('minstr').checked)f.append('instr','1');
  $('mgo').disabled=true;m.textContent='De jingle wordt gemaakt (kan een minuut duren)…';m.className='msg';
  fetch('?action=elmusic',{method:'POST',body:f}).then(function(r){if(!r.ok)return r.json().then(function(d){throw new Error(d.error||'Mislukt')});return r.blob()}).then(function(b){
    var box=document.createElement('div');box.className='res';var a=document.createElement('audio');a.controls=true;a.style.width='100%';a.src=URL.createObjectURL(b);box.appendChild(a);
    var bt=document.createElement('button');bt.type='button';bt.textContent='💾 Bewaren';bt.style.marginTop='8px';bt.onclick=function(){var g=new FormData();g.append('audio',b,'x.mp3');g.append('name',($('name').value||'jingle')+'-jingle');fetch('?action=save',{method:'POST',body:g}).then(r=>r.json()).then(function(d){m.textContent=d.error||('Bewaard als '+d.file);m.className='msg '+(d.error?'err':'ok');if(!d.error)lib()})};box.appendChild(bt);
    $('mres').insertBefore(box,$('mres').firstChild);m.textContent='Klaar.';m.className='msg ok';a.play().catch(function(){})}).catch(function(e){m.textContent=e.message||'Mislukt';m.className='msg err'}).then(function(){$('mgo').disabled=false})}
function saveDict(){var f=new FormData();f.append('text',$('dict').value);fetch('?action=savedict',{method:'POST',body:f}).then(r=>r.json()).then(function(){var m=$('dmsg');m.textContent='Opgeslagen.';m.className='msg ok'})}
fetch('?action=getdict').then(r=>r.json()).then(function(d){$('dict').value=d.text||''});
function lib(){fetch('?action=list').then(r=>r.json()).then(function(d){var L=$('lib');L.innerHTML='';if(!d.files||!d.files.length){L.innerHTML='<div class="hint">Nog niets bewaard.</div>';return}
  d.files.forEach(function(x){var r=document.createElement('div');r.className='item';
   var n=document.createElement('div');n.className='n';n.textContent=x.file+' ('+x.kb+' kB)';r.appendChild(n);
   var a=document.createElement('audio');a.controls=true;a.preload='none';a.src=x.url;r.appendChild(a);
   var dl=document.createElement('a');dl.href=x.url;dl.download=x.file;dl.textContent='Downloaden';r.appendChild(dl);
   var del=document.createElement('button');del.className='sec';del.type='button';del.textContent='Verwijderen';del.onclick=function(){if(!confirm('Verwijderen: '+x.file+'?'))return;var f=new FormData();f.append('file',x.file);fetch('?action=delete',{method:'POST',body:f}).then(lib)};r.appendChild(del);
   L.appendChild(r)})})}
lib();
</script></body></html>
