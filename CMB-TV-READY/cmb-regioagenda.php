<?php
/**
 * CHAZZO MUSIC BOX (CMB) - BEHEER: REGIOAGENDA
 * Bestandsnaam: cmb-regioagenda.php
 * Eigen menu-onderdeel "Regioagenda": 2 edities x 3 onderwerpen, per onderwerp aan/uit voor de slide,
 * één knop die de mp3('s) laat maken, en downloadknoppen. Kern: cmb-core/cmb-regio.php.
 */
ini_set('display_errors', 0);
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
cmb_unpack_payload();
require_once CMB_ROOT . '/cmb-core/cmb-regio.php';
require_once CMB_ROOT . '/cmb-core/cmb-nav.php';

cmb_regio_require_login('CMB Regioagenda', 'cmb-regioagenda.php');

// Status van de mp3-runs (voor de downloadknoppen), alleen voor ingelogde beheerders
if (isset($_GET['status'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode(cmb_regio_status());
    exit;
}

// Opslaan / mp3 laten maken
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['act']) || cmb_regio_post_too_big())) {
    $msgs = [];
    if (cmb_regio_post_too_big()) {
        $msgs[] = ['err', 'Niets opgeslagen: het plaatje is te groot voor de server (max. ' . ini_get('upload_max_filesize') . '). Kies een kleiner plaatje.'];
    } elseif (!cmb_regio_csrf_ok()) {
        $msgs[] = ['err', 'Sessie verlopen, probeer opnieuw.'];
    } else {
        $d = cmb_regio_from_post($_POST, cmb_regio_load());
        foreach (cmb_regio_uploads($d) as $e) $msgs[] = ['err', $e];
        if (!cmb_regio_save($d)) $msgs[] = ['err', 'Opslaan mislukt (cmb-data niet beschrijfbaar?).'];
        else $msgs[] = ['ok', 'Opgeslagen. Slides in de speler zijn bijgewerkt.'];
        foreach (cmb_regio_write_txt($d) as $e) $msgs[] = ['err', $e];

        $act = (string)$_POST['act'];
        $eds = $act === 'make1' ? [1] : ($act === 'make2' ? [2] : ($act === 'makeboth' ? [1, 2] : []));
        $started = [];
        foreach ($eds as $ed) {
            if (!cmb_regio_edition_items($d, $ed)) { $msgs[] = ['err', "Editie $ed heeft geen onderwerpen: niet gestart."]; continue; }
            [$ok, $m] = cmb_regio_dispatch($ed);
            if ($ok) { $started[$ed] = time(); $msgs[] = ['ok', "Editie $ed: mp3 wordt gemaakt."]; }
            else $msgs[] = ['err', "Editie $ed: $m"];
        }
        foreach ($started as $ed => $t) $_SESSION['cmb_regio_started'][$ed] = $t;
    }
    $_SESSION['cmb_regio_flash'] = $msgs;
    header('Location: cmb-regioagenda.php');
    exit;
}

$d = cmb_regio_load();
$flash = $_SESSION['cmb_regio_flash'] ?? [];
unset($_SESSION['cmb_regio_flash']);
$started = $_SESSION['cmb_regio_started'] ?? [];
$tokenOk = cmb_regio_token() !== '';
$txtDirOk = is_dir(cmb_regio_txt_dir()) && is_writable(cmb_regio_txt_dir());
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMB Regioagenda</title>
    <link rel="stylesheet" href="cmb-assets/cmb-admin.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin.css') : time() ?>">
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #070812; color: #f7f7ff; padding: 20px; margin: 0; }
        .wrap { max-width: 980px; margin: 0 auto; }
        h1 { color: #ffd166; margin: 0 0 14px; font-size: 22px; }
        .rg-msg { padding: 10px 14px; border-radius: 8px; margin-bottom: 10px; font-size: 13px; font-weight: 700; }
        .rg-msg.ok { background: rgba(0,230,118,.12); border: 1px solid #00e676; color: #7dffb3; }
        .rg-msg.err { background: rgba(255,23,68,.15); border: 1px solid #ff1744; color: #ff8aa0; }
        .rg-msg.warn { background: rgba(255,209,102,.12); border: 1px solid #ffd166; color: #ffd166; }
        .rg-card { background: #121427; border: 1px solid rgba(255,255,255,.08); border-radius: 14px; padding: 18px; margin-bottom: 18px; }
        .rg-card h2 { margin: 0 0 10px; font-size: 17px; color: #ffd166; }
        .rg-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
        .rg-row .f { display: flex; flex-direction: column; gap: 4px; }
        .rg-row label.l { font-size: 11px; text-transform: uppercase; color: #a8aac8; font-weight: bold; }
        .rg-row input[type=number], .rg-row input[type=text] { background: #05060d; border: 1px solid rgba(255,255,255,.15); color: #fff; padding: 9px; border-radius: 8px; font-size: 14px; }
        .rg-actions { position: sticky; bottom: 0; z-index: 5; background: rgba(7,8,18,.95); border-top: 1px solid rgba(255,255,255,.1); padding: 12px 0; display: flex; gap: 8px; flex-wrap: wrap; }
        .rg-btn { border: none; border-radius: 9px; padding: 11px 16px; font-weight: 900; font-size: 13px; cursor: pointer; color: #070812; background: #ffd166; text-decoration: none; display: inline-block; }
        .rg-btn.go { background: linear-gradient(135deg, #00e676, #00b0ff); }
        .rg-btn.dl { background: #63f6ff; }
        .rg-btn.off { background: #2a2d4a; color: #7c7fa6; pointer-events: none; }
        .rg-dl { display: flex; gap: 14px; flex-wrap: wrap; }
        .rg-dl > div { flex: 1 1 260px; background: #0b0d1c; border: 1px solid rgba(255,255,255,.08); border-radius: 10px; padding: 12px; }
        .rg-state { font-size: 12px; color: #a8aac8; margin: 6px 0 10px; min-height: 18px; }
        pre.rg-pre { white-space: pre-wrap; background: #05060d; border-radius: 8px; padding: 10px; font-size: 12px; color: #c9cbe6; margin: 8px 0 0; }
        <?= cmb_regio_form_css() ?>
    </style>
</head>
<body>
<div class="wrap">
    <?= cmb_nav('regio', 'cmb-regioagenda.php?logout=1') ?>
    <h1>📅 Regioagenda</h1>

    <?php foreach ($flash as $m): ?><div class="rg-msg <?= $m[0] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($m[1]) ?></div><?php endforeach; ?>
    <?php if (!$txtDirOk): ?><div class="rg-msg warn">De map voor de tekstbestanden is niet beschrijfbaar (<?= htmlspecialchars(cmb_regio_txt_dir()) ?>). Stel <code>regio_txt_dir</code> in cmb-config.php in.</div><?php endif; ?>
    <?php if (!$tokenOk): ?><div class="rg-msg warn">GitHub-token niet gevonden: opslaan en slides werken, de knoppen "mp3 maken" nog niet. Zie de LEESMIJ (regel Regioagenda).</div><?php endif; ?>

    <form method="POST" action="cmb-regioagenda.php" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(cmb_regio_csrf()) ?>">

        <?= cmb_regio_form_fields($d) ?>

        <div class="rg-card">
            <h2>🖥️ Slides in de speler</h2>
            <p class="rg-hint">Elk onderwerp met het vinkje "Op slide" komt als eigen slide in de rotatie (max. 6). Vinkje uit = alleen in de mp3.</p>
            <div class="cmb-style-grid-wrap" style="margin-bottom:12px">
                <?= renderStyleSuite('rg_kop', 'Opmaak koptitel (geldt voor alle koptitels op de slides)', 'Lettertype, grootte, kleur, verloop en schaduw', $d['style_kop'], false, false) ?>
                <?= renderStyleSuite('rg_body', 'Opmaak slidetekst (de tekst onder de koptitel)', 'Houd deze klein zodat de tekst past', $d['style_body'], false, false) ?>
            </div>
            <div class="rg-row">
                <div class="f"><label class="l">Seconden per slide</label><input type="number" name="sec" value="<?= intval($d['sec']) ?>" min="3" max="120" style="width:110px"></div>
                <div class="f"><label class="l">Donker over achtergrond (%)</label><input type="number" name="darken" value="<?= intval($d['darken']) ?>" min="0" max="90" style="width:110px"></div>
                <div class="f" style="flex:1;min-width:240px"><label class="l">Standaardachtergrond (voor onderwerpen zonder eigen plaatje)</label><input type="text" name="media" value="<?= htmlspecialchars($d['media']) ?>" placeholder="https://…/afbeelding.jpg"></div>
            </div>
        </div>

        <div class="rg-actions">
            <button class="rg-btn" type="submit" name="act" value="save">💾 Opslaan</button>
            <button class="rg-btn go" type="submit" name="act" value="make1">🎙️ Opslaan &amp; mp3 editie 1</button>
            <button class="rg-btn go" type="submit" name="act" value="make2">🎙️ Opslaan &amp; mp3 editie 2</button>
            <button class="rg-btn go" type="submit" name="act" value="makeboth">🎙️ Opslaan &amp; mp3 beide edities</button>
        </div>
    </form>

    <div class="rg-card" style="margin-top:18px">
        <h2>⬇️ Downloads</h2>
        <p class="rg-hint">Na "mp3 maken" duurt het ± 3½ minuut. De knop wordt groen zodra de nieuwe mp3 klaar is. Controleer de mp3 en zet hem daarna in de radio-automatisering.</p>
        <div class="rg-dl">
            <?php for ($ed = 1; $ed <= 2; $ed++): ?>
            <div>
                <strong>Editie <?= $ed ?></strong>
                <div class="rg-state" id="rgState<?= $ed ?>"><?= isset($started[$ed]) ? '⏳ wordt gemaakt…' : 'Nog niet gestart in deze sessie.' ?></div>
                <a class="rg-btn <?= isset($started[$ed]) ? 'off' : 'dl' ?>" id="rgDl<?= $ed ?>" href="<?= htmlspecialchars(cmb_regio_mp3_url($ed)) ?>" target="_blank" rel="noopener" download>
                    <?= isset($started[$ed]) ? '⬇ mp3 editie ' . $ed . ' (even wachten)' : '⬇ vorige mp3 editie ' . $ed ?>
                </a>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <details class="rg-fixed">
        <summary>👁️ Tekst zoals die naar de spraak gaat (opgeslagen versie)</summary>
        <?php for ($ed = 1; $ed <= 2; $ed++): $s = cmb_regio_script($d, $ed); ?>
            <p class="rg-hint" style="margin-top:12px"><strong>Editie <?= $ed ?></strong> · <?= htmlspecialchars(cmb_regio_txt_url($ed)) ?></p>
            <pre class="rg-pre"><?= $s !== '' ? htmlspecialchars(str_replace(CMB_REGIO_TING, '🔔 pingel', $s)) : '(leeg: geen onderwerpen)' ?></pre>
        <?php endfor; ?>
    </details>
</div>

<script>
(function () {
    var started = <?= json_encode((object)$started) ?>;
    var eds = Object.keys(started);
    if (!eds.length) return;
    var t0 = Date.now(), fallbackMs = 3.5 * 60 * 1000, done = {};
    function ui(ed, ok, text, url) {
        var s = document.getElementById('rgState' + ed), a = document.getElementById('rgDl' + ed);
        s.textContent = text;
        if (ok) {
            a.className = 'rg-btn dl'; a.textContent = '⬇ mp3 editie ' + ed + ' downloaden';
            a.href = url + '?t=' + Date.now();
        }
    }
    function tick() {
        if (Date.now() - t0 > 15 * 60 * 1000) return;
        fetch('cmb-regioagenda.php?status=1', { cache: 'no-store', credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
            if (!j.ok) throw new Error('geen status');
            eds.forEach(function (ed) {
                if (done[ed]) return;
                var r = j.eds && j.eds[ed];
                if (!r || r.created < started[ed] - 10) { ui(ed, false, '⏳ wordt gestart…'); return; }
                if (r.status === 'completed' && r.conclusion === 'success') { done[ed] = 1; ui(ed, true, '✅ Klaar.', <?= json_encode(cmb_regio_mp3_url(1)) ?>.replace('-1.mp3', '-' + ed + '.mp3')); }
                else if (r.status === 'completed') { done[ed] = 1; ui(ed, false, '❌ Mislukt (' + r.conclusion + '). Probeer opnieuw; blijft het fout, kijk bij de run op GitHub.'); }
                else ui(ed, false, '⏳ wordt gemaakt…');
            });
            if (eds.some(function (e) { return !done[e]; })) setTimeout(tick, 8000);
        }).catch(function () {
            // Geen status beschikbaar: val terug op de vaste wachttijd
            var left = fallbackMs - (Date.now() - t0);
            eds.forEach(function (ed) {
                if (done[ed]) return;
                if (left <= 0) { done[ed] = 1; ui(ed, true, 'Waarschijnlijk klaar (status niet op te vragen).', <?= json_encode(cmb_regio_mp3_url(1)) ?>.replace('-1.mp3', '-' + ed + '.mp3')); }
                else { var m = Math.floor(left / 60000), s = Math.floor(left % 60000 / 1000); ui(ed, false, '⏳ nog ± ' + m + ':' + (s < 10 ? '0' : '') + s); }
            });
            if (eds.some(function (e) { return !done[e]; })) setTimeout(tick, 1000);
        });
    }
    tick();
})();
</script>
<script src="cmb-assets/cmb-admin-ui.js?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') : time() ?>"></script>
<script src="cmb-assets/cmb-filepick.js?v=2"></script>
</body>
</html>
