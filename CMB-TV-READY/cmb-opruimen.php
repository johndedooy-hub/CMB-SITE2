<?php
/**
 * CHAZZO MUSIC BOX (CMB) - EENMALIG OPRUIMEN NA DE NIEUWE MAPPENINDELING
 * Bestandsnaam: cmb-opruimen.php
 * Sinds ronde 9 staan de onderdelen in mappen (cmb-core, cmb-player, cmb-admin-parts, cmb-studio-parts, cmb-assets).
 * Na het uitpakken over een oude versie blijven de oude losse bestanden in de hoofdmap staan.
 * Dit script verwijdert ALLEEN die oude bestanden, en alleen als de nieuwe versie in de map aanwezig is.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }
if (empty($_SESSION['cmb_clean_csrf'])) $_SESSION['cmb_clean_csrf'] = bin2hex(random_bytes(16));

$map = [
    'cmb-core' => ['cmb-lib.php', 'cmb-fonts.php', 'cmb-effects-list.php', 'cmb-nav.php', 'cmb-claude-func.php'],
    'cmb-player' => ['header.php', 'footer.php', 'cmb-header-func.php', 'cmb-header-data.php', 'cmb-header-css.php'],
    'cmb-admin-parts' => ['cmb-admin-func.php', 'cmb-admin-frames.php', 'cmb-admin-lighting.php', 'cmb-admin-topmenu.php', 'cmb-admin-effects.php', 'cmb-admin-identity.php'],
    'cmb-studio-parts' => ['cmb-studio-save.php', 'cmb-studio-slides.php', 'cmb-studio-clipslides.php', 'cmb-studio-widgets.php', 'cmb-studio-streams.php'],
    'cmb-assets' => ['cmb-effects.css', 'cmb-player.js', 'cmb-admin-ui.js', 'cmb-admin.css', 'cmb-studio.js'],
];
$extraOld = ['cmb-studio-clips.php']; // al eerder vervangen
$rows = [];
foreach ($map as $dir => $files) {
    foreach ($files as $f) $rows[] = ['old' => $f, 'new' => $dir . '/' . $f];
}
foreach ($extraOld as $f) $rows[] = ['old' => $f, 'new' => ''];

$done = [];
$selfDeleted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['cmb_clean_csrf'], (string)($_POST['csrf'] ?? ''))) {
    if (isset($_POST['clean'])) {
        foreach ($rows as $r) {
            $old = CMB_ROOT . '/' . $r['old'];
            if (!is_file($old)) continue;
            if ($r['new'] !== '' && !is_file(CMB_ROOT . '/' . $r['new'])) { $done[] = [$r['old'], 'overgeslagen: nieuwe versie ontbreekt']; continue; }
            $done[] = [$r['old'], @unlink($old) ? 'verwijderd' : 'kon niet verwijderen (rechten?)'];
        }
    }
    if (isset($_POST['self'])) $selfDeleted = @unlink(__FILE__);
}
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Opruimen</title>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:20px; }
    .wrap { max-width:800px; margin:0 auto; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:16px; margin-bottom:16px; }
    h1 { color:#ffd166; font-size:20px; } table { width:100%; border-collapse:collapse; font-size:13px; }
    td { padding:5px 6px; border-bottom:1px solid rgba(255,255,255,.06); }
    button { cursor:pointer; border:none; border-radius:8px; font-weight:800; padding:10px 14px; margin-right:8px; }
    .go { background:#ff5bc8; color:#070812; } .self { background:#2a2e52; color:#fff; }
    .ok { color:#00e676; } .muted { color:#a8aac8; }
</style>
</head>
<body>
<div class="wrap">
    <h1>🧹 Eenmalig opruimen</h1>
    <?php if ($selfDeleted): ?>
        <div class="card ok">Klaar. Dit opruimscript is verwijderd. <a href="cmb-admin.php" style="color:#63f6ff;">Terug naar beheer</a></div>
    <?php else: ?>
    <div class="card">
        <p>Oude losse bestanden in de hoofdmap die nu in een map staan. Alleen bestanden waarvan de nieuwe versie aanwezig is worden verwijderd. Je instellingen (cmb-data), uploads en cmb-custom.css worden niet aangeraakt.</p>
        <table>
            <?php $any = false; foreach ($rows as $r): $exists = is_file(CMB_ROOT . '/' . $r['old']); $any = $any || $exists; ?>
            <tr><td><?= $h($r['old']) ?></td><td class="muted"><?= $r['new'] ? '→ ' . $h($r['new']) : 'vervallen' ?></td><td><?= $exists ? 'nog aanwezig' : '<span class="ok">✓ weg</span>' ?></td></tr>
            <?php endforeach; ?>
        </table>
        <?php if ($done): ?><p><?php foreach ($done as $d) echo $h($d[0]) . ': ' . $h($d[1]) . '<br>'; ?></p><?php endif; ?>
        <form method="POST" style="margin-top:12px;">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['cmb_clean_csrf']) ?>">
            <?php if ($any): ?><button class="go" name="clean" value="1" onclick="return confirm('Oude bestanden verwijderen?')">Oude bestanden verwijderen</button><?php endif; ?>
            <button class="self" name="self" value="1">Klaar: dit script verwijderen</button>
        </form>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
