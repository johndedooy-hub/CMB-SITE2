<?php
/**
 * CHAZZO MUSIC BOX (CMB) - AFBEELDING MAKEN
 * Bestandsnaam: cmb-afbeelding.php
 * Achtergrond + tekst + eventueel een logo samenvoegen tot één gewoon plaatje (jpg/png) in cmb-uploads.
 * Het plaatje wordt in de browser gemaakt; de server controleert en bewaart alleen het eindresultaat.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }
if (empty($_SESSION['cmb_img_csrf'])) $_SESSION['cmb_img_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['cmb_img_csrf'];
$upDir = cmb_upload_dir();
$imgExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
// Bronnen: je uploads en drie extra mappen naast de cmb-map (losse plaatjes)
$srcDirs = ['u' => ['Mijn uploads', $upDir]];
foreach ([1, 2, 3] as $xn) { $xd = dirname(CMB_ROOT) . '/cmb-extra-' . $xn . '/'; if (is_dir($xd)) $srcDirs['e' . $xn] = ['Extra ' . $xn, $xd]; }

function afb_list($upDir, $imgExt, $onlyMade = false) {
    $o = [];
    foreach (@glob(rtrim($upDir, '/') . '/*') ?: [] as $f) {
        $b = basename($f); $e = strtolower(pathinfo($b, PATHINFO_EXTENSION));
        if (!is_file($f) || !in_array($e, $imgExt, true)) continue;
        if ($onlyMade && strpos($b, 'afb-') !== 0) continue;
        $o[$b] = filemtime($f);
    }
    arsort($o);
    return array_keys($o);
}

// Een bestaand plaatje uit de uploadmap tonen (zo werkt het ook als de uploads elders staan)
if (isset($_GET['src'])) {
    $b = basename((string)$_GET['src']); $e = strtolower(pathinfo($b, PATHINFO_EXTENSION));
    $dk = (string)($_GET['d'] ?? 'u'); $dd = isset($srcDirs[$dk]) ? $srcDirs[$dk][1] : $upDir;
    $f = rtrim($dd, '/') . '/' . $b;
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $b) || !in_array($e, $imgExt, true) || !is_file($f)) { http_response_code(404); exit; }
    $mime = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'][$e];
    header('Content-Type: ' . $mime); header('Cache-Control: private, max-age=60'); readfile($f); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) { echo json_encode(['ok' => false, 'err' => 'Sessie verlopen, laad de pagina opnieuw.']); exit; }
    $act = (string)($_POST['act'] ?? '');
    if ($act === 'save') {
        $tmp = $_FILES['img']['tmp_name'] ?? '';
        if ($tmp === '' || !is_uploaded_file($tmp) || ($_FILES['img']['size'] ?? 0) > 12 * 1048576) { echo json_encode(['ok' => false, 'err' => 'Plaatje ontbreekt of is te groot.']); exit; }
        $info = @getimagesize($tmp);
        $ext = ($info && $info[2] === IMAGETYPE_PNG) ? 'png' : (($info && $info[2] === IMAGETYPE_JPEG) ? 'jpg' : '');
        if ($ext === '') { echo json_encode(['ok' => false, 'err' => 'Alleen jpg of png.']); exit; }
        if (!is_dir($upDir)) @mkdir($upDir, 0755, true);
        $name = 'afb-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4) . '.' . $ext;
        if (!move_uploaded_file($tmp, rtrim($upDir, '/') . '/' . $name)) { echo json_encode(['ok' => false, 'err' => 'Opslaan lukt niet (rechten van cmb-uploads?).']); exit; }
        echo json_encode(['ok' => true, 'name' => $name, 'url' => 'cmb-uploads/' . $name]); exit;
    }
    if ($act === 'upload') {
        $err = null; $u = cmb_upload('file', $upDir, 12 * 1048576, $err, 'bron-');
        $e = $u ? strtolower(pathinfo($u, PATHINFO_EXTENSION)) : '';
        if (!$u || !in_array($e, $imgExt, true)) { echo json_encode(['ok' => false, 'err' => $err ?: 'Alleen afbeeldingen (jpg, png, webp, gif).']); exit; }
        echo json_encode(['ok' => true, 'name' => basename($u), 'val' => 'u:' . basename($u)]); exit;
    }
    if ($act === 'fetch') {
        $url = trim((string)($_POST['url'] ?? ''));
        $pu = @parse_url($url);
        if (!$pu || strtolower($pu['scheme'] ?? '') !== 'https' || empty($pu['host'])) { echo json_encode(['ok' => false, 'err' => 'Plak een link die met https:// begint.']); exit; }
        $ips = @gethostbynamel($pu['host']) ?: [];
        if (!$ips) { echo json_encode(['ok' => false, 'err' => 'Die site is niet gevonden.']); exit; }
        foreach ($ips as $ip) if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) { echo json_encode(['ok' => false, 'err' => 'Die link is niet toegestaan.']); exit; }
        $data = false;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 6, CURLOPT_USERAGENT => 'Mozilla/5.0 CMB', CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_NOPROGRESS => false, CURLOPT_PROGRESSFUNCTION => function ($r, $dt, $dn) { return $dn > 12 * 1048576 ? 1 : 0; }]);
            $data = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($code >= 300) $data = false;
        } elseif (ini_get('allow_url_fopen')) {
            $data = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 15, 'follow_location' => 0, 'user_agent' => 'Mozilla/5.0 CMB']]), 0, 12 * 1048576 + 1);
        }
        if ($data === false || $data === '' || strlen($data) > 12 * 1048576) { echo json_encode(['ok' => false, 'err' => 'Ophalen mislukt (de site staat het niet toe of het plaatje is te groot). Download het plaatje en upload het hier.']); exit; }
        $info = @getimagesizefromstring($data);
        $map = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp'];
        $ext = $info ? ($map[$info[2]] ?? '') : '';
        if ($ext === '') { echo json_encode(['ok' => false, 'err' => 'Dat is geen plaatje (jpg, png, webp of gif).']); exit; }
        if (!is_dir($upDir)) @mkdir($upDir, 0755, true);
        $name = 'bron-' . date('Ymd-His') . '-' . substr(bin2hex(random_bytes(2)), 0, 4) . '.' . $ext;
        if (@file_put_contents(rtrim($upDir, '/') . '/' . $name, $data) === false) { echo json_encode(['ok' => false, 'err' => 'Opslaan lukt niet (rechten van cmb-uploads?).']); exit; }
        echo json_encode(['ok' => true, 'name' => $name, 'val' => 'u:' . $name]); exit;
    }
    if ($act === 'del') {
        $b = basename((string)($_POST['name'] ?? ''));
        if (strpos($b, 'afb-') === 0 && preg_match('/^[A-Za-z0-9._-]+$/', $b) && is_file(rtrim($upDir, '/') . '/' . $b)) @unlink(rtrim($upDir, '/') . '/' . $b);
        echo json_encode(['ok' => true]); exit;
    }
    echo json_encode(['ok' => false, 'err' => 'Onbekende actie.']); exit;
}

$sources = [];
foreach ($srcDirs as $sk => $sd) foreach (afb_list($sd[1], $imgExt) as $sf) $sources[$sk][] = $sf;
$made = afb_list($upDir, $imgExt, true);
$fontOpts = cmb_font_options();
$fontUrl = cmb_fonts_url(array_keys(cmb_font_catalog()));
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Afbeelding maken</title>
<?php if ($fontUrl): ?><link href="<?= $h($fontUrl) ?>" rel="stylesheet"><?php endif; ?>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:16px; }
    .wrap { max-width:1100px; margin:0 auto; }
    h1 { color:#ffd166; font-size:20px; margin:0 0 10px; } h2 { font-size:14px; color:#63f6ff; margin:0 0 8px; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:14px; margin-bottom:14px; }
    .grid { display:grid; grid-template-columns:minmax(0,1.4fr) minmax(0,1fr); gap:14px; align-items:start; }
    @media (max-width:820px) { .grid { grid-template-columns:1fr; } }
    label { display:block; font-size:11px; color:#a8aac8; margin:8px 0 3px; text-transform:uppercase; font-weight:700; }
    input[type=text], input[type=number], select, textarea { width:100%; background:#05060d; color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:8px; font-size:14px; }
    input[type=range] { width:100%; } input[type=color] { width:100%; height:36px; background:#05060d; border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:2px; }
    .row { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:8px; }
    button, .btn { cursor:pointer; border:none; border-radius:8px; font-weight:800; padding:10px 14px; background:linear-gradient(135deg,#00e676,#00b0ff); color:#070812; font-size:14px; }
    button.sec { background:#1d2040; color:#fff; border:1px solid rgba(255,255,255,.2); }
    @media (min-width:821px) { .stick { position:sticky; top:8px; } }
    #cv { width:100%; height:auto; border-radius:10px; border:1px solid rgba(255,255,255,.2); background:#05060d; display:block; }
    .msg { font-size:13px; margin-top:8px; } .ok { color:#00e676; } .err { color:#ff8aa0; }
    .made { display:flex; flex-wrap:wrap; gap:10px; } .made figure { margin:0; width:150px; font-size:11px; }
    .made img { width:150px; height:90px; object-fit:cover; border-radius:8px; border:1px solid rgba(255,255,255,.2); display:block; background:#05060d; }
    code { background:#05060d; border:1px solid rgba(255,255,255,.12); border-radius:6px; padding:2px 6px; font-size:12px; overflow-wrap:anywhere; }
    details { border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:6px 10px; margin:8px 0; } summary { cursor:pointer; font-weight:800; font-size:13px; }
    p { font-size:13px; line-height:1.5; }
</style>
</head>
<body>
<div class="wrap">
    <?php require_once CMB_ROOT . '/cmb-core/cmb-nav.php'; echo cmb_nav('afbeelding', 'cmb-admin.php?logout=1'); ?>
    <h1>🖼️ Afbeelding maken</h1>
    <p>Kies een achtergrond, zet er tekst op en eventueel een logo. Je ziet meteen het voorbeeld. Opslaan geeft een gewoon plaatje dat je overal kunt gebruiken waar een plaatje of link kan.</p>
    <div class="grid">
        <div class="card stick">
            <canvas id="cv" width="1280" height="720"></canvas>
            <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
                <button type="button" id="btnSave">💾 Opslaan als plaatje</button>
                <button type="button" class="sec" id="btnDl">⬇ Downloaden</button>
                <button type="button" class="sec" id="btnShare" style="display:none;">📤 Delen</button>
                <select id="fmt" style="width:auto;"><option value="jpg">JPG (klein)</option><option value="png">PNG (scherp, doorzichtig kan)</option></select>
            </div>
            <div class="msg" id="msg"></div>
        </div>
        <div>
            <div class="card">
                <h2>1. Achtergrond</h2>
                <label>Formaat</label>
                <select id="size"><option value="1280x720">Breed 1280×720</option><option value="1920x1080">Breed groot 1920×1080</option><option value="1080x1080">Vierkant 1080×1080</option><option value="1080x1920">Staand 1080×1920</option></select>
                <label>Kies uit je plaatjes</label>
                <select id="bg"><option value="">(effen kleur)</option><?php foreach ($sources as $sk => $list): ?><optgroup label="<?= $h($srcDirs[$sk][0]) ?>"><?php foreach ($list as $sf): ?><option value="<?= $h($sk . ':' . $sf) ?>"><?= $h($sf) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select>
                <label>of upload een achtergrond</label><input type="file" id="bgUp" accept="image/*">
                <label>of plak een link naar een foto</label><div style="display:flex; gap:6px;"><input type="text" id="bgLink" placeholder="https://..."><button type="button" class="sec" id="bgFetch" style="white-space:nowrap;">Ophalen</button></div>
                <div class="row"><div><label>Effen kleur</label><input type="color" id="bgCol" value="#101428"></div>
                <div><label>Donker (%)</label><input type="range" id="dark" min="0" max="90" value="30"></div>
                <div><label>Blur (px)</label><input type="range" id="blur" min="0" max="20" value="0"></div></div>
            </div>
            <div class="card">
                <h2>2. Logo (optioneel)</h2>
                <label>Kies uit je plaatjes</label>
                <select id="logo"><option value="">(geen logo)</option><?php foreach ($sources as $sk => $list): ?><optgroup label="<?= $h($srcDirs[$sk][0]) ?>"><?php foreach ($list as $sf): ?><option value="<?= $h($sk . ':' . $sf) ?>"><?= $h($sf) ?></option><?php endforeach; ?></optgroup><?php endforeach; ?></select>
                <label>of upload een logo (liefst png met doorzichtige achtergrond)</label><input type="file" id="logoUp" accept="image/*">
                <label>of plak een link naar een logo</label><div style="display:flex; gap:6px;"><input type="text" id="logoLink" placeholder="https://..."><button type="button" class="sec" id="logoFetch" style="white-space:nowrap;">Ophalen</button></div>
                <div class="row"><div><label>Grootte (%)</label><input type="range" id="lSize" min="5" max="80" value="25"></div>
                <div><label>Links ↔ rechts</label><input type="range" id="lX" min="0" max="100" value="85"></div>
                <div><label>Boven ↕ onder</label><input type="range" id="lY" min="0" max="100" value="15"></div>
                <div><label>Zichtbaar (%)</label><input type="range" id="lOp" min="10" max="100" value="100"></div></div>
            </div>
            <?php foreach ([1, 2] as $n): ?>
            <details <?= $n === 1 ? 'open' : '' ?>><summary>3. Tekst <?= $n ?><?= $n === 2 ? ' (optioneel)' : '' ?></summary>
                <textarea id="t<?= $n ?>" rows="2" placeholder="Tekst..."><?= $n === 1 ? 'Je tekst hier' : '' ?></textarea>
                <label>Lettertype</label>
                <select id="f<?= $n ?>"><?php foreach ($fontOpts as $fk => $fv): ?><option value="<?= $h($fk) ?>" <?= $fk === 'Montserrat' ? 'selected' : '' ?>><?= $h($fv) ?></option><?php endforeach; ?></select>
                <div class="row"><div><label>Grootte</label><input type="number" id="s<?= $n ?>" min="10" max="400" value="<?= $n === 1 ? 90 : 48 ?>"></div>
                <div><label>Kleur</label><input type="color" id="c<?= $n ?>" value="<?= $n === 1 ? '#ffffff' : '#ffd166' ?>"></div>
                <div><label>Uitlijnen</label><select id="a<?= $n ?>"><option value="left">Links</option><option value="center" selected>Midden</option><option value="right">Rechts</option></select></div>
                <div><label>Schaduw</label><select id="sh<?= $n ?>"><option value="1">Aan</option><option value="0">Uit</option></select></div></div>
                <div class="row"><div><label>Links ↔ rechts</label><input type="range" id="x<?= $n ?>" min="0" max="100" value="50"></div>
                <div><label>Boven ↕ onder</label><input type="range" id="y<?= $n ?>" min="0" max="100" value="<?= $n === 1 ? 45 : 65 ?>"></div></div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <h2>Mijn gemaakte plaatjes</h2>
        <div class="made" id="made">
            <?php foreach ($made as $m): ?>
            <figure data-n="<?= $h($m) ?>"><img src="cmb-afbeelding.php?src=<?= urlencode($m) ?>" alt=""><code>cmb-uploads/<?= $h($m) ?></code><br><button type="button" class="sec" style="padding:4px 8px; font-size:11px; margin-top:4px;" onclick="delMade(this)">Verwijder</button></figure>
            <?php endforeach; ?>
            <?php if (!$made): ?><span id="none" style="font-size:13px; color:#a8aac8;">Nog niets gemaakt.</span><?php endif; ?>
        </div>
    </div>
</div>
<script>
(function () {
    var CSRF = <?= json_encode($csrf) ?>;
    var $ = function (i) { return document.getElementById(i); };
    var cv = $('cv'), ctx = cv.getContext('2d');
    var imgs = { bg: null, logo: null };
    function loadImg(name, key) {
        if (!name) { imgs[key] = null; draw(); return; }
        var im = new Image();
        im.onload = function () { imgs[key] = im; draw(); };
        var pr = String(name).split(':');
        im.src = 'cmb-afbeelding.php?d=' + encodeURIComponent(pr[0]) + '&src=' + encodeURIComponent(pr.slice(1).join(':'));
    }
    function lineWrap(text, maxW) {
        var out = [];
        String(text).split('\n').forEach(function (para) {
            var words = para.split(' '), line = '';
            words.forEach(function (w) {
                var t = line ? line + ' ' + w : w;
                if (ctx.measureText(t).width > maxW && line) { out.push(line); line = w; } else line = t;
            });
            out.push(line);
        });
        return out;
    }
    function drawText(n) {
        var txt = $('t' + n).value; if (!txt.trim()) return;
        var W = cv.width, H = cv.height, scale = W / 1280;
        var size = (parseInt($('s' + n).value, 10) || 60) * scale, font = $('f' + n).value;
        ctx.font = '800 ' + size + 'px "' + font + '", sans-serif';
        ctx.fillStyle = $('c' + n).value; ctx.textBaseline = 'middle';
        var al = $('a' + n).value; ctx.textAlign = al;
        if ($('sh' + n).value === '1') { ctx.shadowColor = 'rgba(0,0,0,.75)'; ctx.shadowBlur = size * 0.18; ctx.shadowOffsetY = size * 0.05; } else { ctx.shadowColor = 'transparent'; ctx.shadowBlur = 0; ctx.shadowOffsetY = 0; }
        var x = W * $('x' + n).value / 100, y = H * $('y' + n).value / 100;
        var lines = lineWrap(txt, W * 0.9), lh = size * 1.25, y0 = y - (lines.length - 1) * lh / 2;
        lines.forEach(function (l, i) { ctx.fillText(l, x, y0 + i * lh); });
        ctx.shadowColor = 'transparent'; ctx.shadowBlur = 0; ctx.shadowOffsetY = 0;
    }
    function draw() {
        var p = $('size').value.split('x'); var W = parseInt(p[0], 10), H = parseInt(p[1], 10);
        if (cv.width !== W || cv.height !== H) { cv.width = W; cv.height = H; }
        ctx.clearRect(0, 0, W, H); ctx.fillStyle = $('bgCol').value; ctx.fillRect(0, 0, W, H);
        if (imgs.bg) {
            var im = imgs.bg, r = Math.max(W / im.width, H / im.height), w = im.width * r, h = im.height * r, b = parseInt($('blur').value, 10) || 0;
            ctx.save(); if (b) ctx.filter = 'blur(' + (b * W / 1280) + 'px)';
            var ex = b ? b * 2 : 0; ctx.drawImage(im, (W - w) / 2 - ex, (H - h) / 2 - ex, w + 2 * ex, h + 2 * ex); ctx.restore();
        }
        var d = parseInt($('dark').value, 10) || 0; if (d) { ctx.fillStyle = 'rgba(0,0,0,' + d / 100 + ')'; ctx.fillRect(0, 0, W, H); }
        if (imgs.logo) {
            var lg = imgs.logo, lw = W * $('lSize').value / 100, lh = lw * lg.height / lg.width;
            ctx.save(); ctx.globalAlpha = $('lOp').value / 100;
            ctx.drawImage(lg, W * $('lX').value / 100 - lw / 2, H * $('lY').value / 100 - lh / 2, lw, lh); ctx.restore();
        }
        drawText(1); drawText(2);
    }
    function fontReady() {
        var jobs = [1, 2].map(function (n) { return document.fonts && document.fonts.load ? document.fonts.load('800 40px "' + $('f' + n).value + '"') : Promise.resolve(); });
        Promise.all(jobs).then(draw, draw);
    }
    document.querySelectorAll('input,select,textarea').forEach(function (e) {
        if (e.type === 'file') return;
        e.addEventListener('input', function () { if (/^f[12]$/.test(e.id)) fontReady(); else draw(); });
        e.addEventListener('change', function () { if (e.id === 'bg') loadImg(e.value, 'bg'); else if (e.id === 'logo') loadImg(e.value, 'logo'); });
    });
    function say(t, ok) { $('msg').className = 'msg ' + (ok ? 'ok' : 'err'); $('msg').textContent = t; }
    function addSource(j) {
        ['bg', 'logo'].forEach(function (id) {
            var sel = $(id), g = sel.querySelector('optgroup[label="Mijn uploads"]');
            if (!g) { g = document.createElement('optgroup'); g.label = 'Mijn uploads'; sel.insertBefore(g, sel.children[1] || null); }
            var o = document.createElement('option'); o.value = j.val; o.textContent = j.name; g.insertBefore(o, g.firstChild);
        });
    }
    function fetchLink(inputId, btnId, selId, key) {
        $(btnId).addEventListener('click', function () {
            var u = $(inputId).value.trim(); if (!u) return;
            var fd = new FormData(); fd.append('csrf', CSRF); fd.append('act', 'fetch'); fd.append('url', u);
            say('Foto ophalen...', true);
            fetch('cmb-afbeelding.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { say(j.err || 'Ophalen mislukt.', false); return; }
                addSource(j); $(selId).value = j.val; loadImg(j.val, key); $(inputId).value = ''; say('Foto opgehaald en bewaard in je uploads.', true);
            }).catch(function () { say('Ophalen mislukt.', false); });
        });
    }
    function upload(inputId, selId, key) {
        $(inputId).addEventListener('change', function () {
            var f = this.files[0]; if (!f) return;
            var fd = new FormData(); fd.append('csrf', CSRF); fd.append('act', 'upload'); fd.append('file', f);
            say('Uploaden...', true);
            fetch('cmb-afbeelding.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { say(j.err || 'Upload mislukt.', false); return; }
                addSource(j); $(selId).value = j.val; loadImg(j.val, key); say('Geüpload.', true);
            }).catch(function () { say('Upload mislukt.', false); });
        });
    }
    upload('bgUp', 'bg', 'bg'); upload('logoUp', 'logo', 'logo');
    fetchLink('bgLink', 'bgFetch', 'bg', 'bg'); fetchLink('logoLink', 'logoFetch', 'logo', 'logo');
    function getBlob(cb) { var png = $('fmt').value === 'png'; cv.toBlob(function (b) { cb(b, png); }, png ? 'image/png' : 'image/jpeg', 0.92); }
    $('btnDl').addEventListener('click', function () {
        getBlob(function (blob, png) {
            if (!blob) { say('Maken lukt niet in deze browser.', false); return; }
            var a = document.createElement('a'); a.href = URL.createObjectURL(blob); a.download = 'afbeelding.' + (png ? 'png' : 'jpg');
            document.body.appendChild(a); a.click(); a.remove(); setTimeout(function () { URL.revokeObjectURL(a.href); }, 4000);
            say('Gedownload (kijk in je downloads).', true);
        });
    });
    try {
        var tf = new File([new Blob([''], { type: 'image/png' })], 't.png', { type: 'image/png' });
        if (navigator.share && navigator.canShare && navigator.canShare({ files: [tf] })) {
            $('btnShare').style.display = '';
            $('btnShare').addEventListener('click', function () {
                getBlob(function (blob, png) {
                    if (!blob) return;
                    var f = new File([blob], 'afbeelding.' + (png ? 'png' : 'jpg'), { type: blob.type });
                    navigator.share({ files: [f] }).catch(function () {});
                });
            });
        }
    } catch (e) {}
    $('btnSave').addEventListener('click', function () {
        var png = $('fmt').value === 'png';
        cv.toBlob(function (blob) {
            if (!blob) { say('Maken lukt niet in deze browser.', false); return; }
            var fd = new FormData(); fd.append('csrf', CSRF); fd.append('act', 'save'); fd.append('img', blob, 'afb.' + (png ? 'png' : 'jpg'));
            say('Opslaan...', true);
            fetch('cmb-afbeelding.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
                if (!j.ok) { say(j.err || 'Opslaan mislukt.', false); return; }
                say('Opgeslagen als ' + j.url + ' (plak deze link waar een plaatje kan).', true);
                var n = $('none'); if (n) n.remove();
                var f = document.createElement('figure'); f.setAttribute('data-n', j.name);
                f.innerHTML = '<img src="cmb-afbeelding.php?src=' + encodeURIComponent(j.name) + '" alt=""><code></code><br><button type="button" class="sec" style="padding:4px 8px; font-size:11px; margin-top:4px;" onclick="delMade(this)">Verwijder</button>';
                f.querySelector('code').textContent = j.url; $('made').insertBefore(f, $('made').firstChild);
            }).catch(function () { say('Opslaan mislukt.', false); });
        }, png ? 'image/png' : 'image/jpeg', 0.92);
    });
    window.delMade = function (btn) {
        var fig = btn.closest('figure'); if (!confirm('Dit plaatje verwijderen?')) return;
        var fd = new FormData(); fd.append('csrf', CSRF); fd.append('act', 'del'); fd.append('name', fig.getAttribute('data-n'));
        fetch('cmb-afbeelding.php', { method: 'POST', body: fd, credentials: 'same-origin' }).then(function () { fig.remove(); });
    };
    fontReady();
})();
</script>
<script src="cmb-assets/cmb-filepick.js?v=2"></script>
</body>
</html>
