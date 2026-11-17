<?php
/**
 * CHAZZO MUSIC BOX (CMB) - INLOG & CONFIG
 * Bestandsnaam: cmb-wachtwoord.php
 * Laat zien welke cmb-config.php wordt gebruikt en maakt een veilige wachtwoord-hash om daarin te plakken.
 * Er wordt hier niets opgeslagen: jij plakt de regel zelf in cmb-config.php (buiten public_html).
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }

$cfgPath = cmb_config_path();
$home = '';
if (preg_match('#^(.*?)/domains/#', CMB_ROOT, $m)) $home = $m[1];
elseif (preg_match('#^(.*?)/public_html#', CMB_ROOT, $m)) $home = $m[1];
$suggest = ($home !== '' ? $home : dirname(CMB_ROOT)) . '/cmb-config.php';
$inPublic = $cfgPath !== '' && strpos($cfgPath, '/public_html') !== false;

$accMsg = ''; $accErr = '';
if (empty($_SESSION['cmb_acc_csrf'])) $_SESSION['cmb_acc_csrf'] = bin2hex(random_bytes(16));
$me = (string)($_SESSION['cmb_user'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acc_action'])) {
    if (!hash_equals($_SESSION['cmb_acc_csrf'], (string)($_POST['csrf'] ?? ''))) { $accErr = 'Sessie verlopen, probeer opnieuw.'; }
    else {
        $act = $_POST['acc_action']; $users = cmb_users_load();
        if ($act === 'change') {
            $cur = (string)($_POST['cur'] ?? ''); $n1 = (string)($_POST['n1'] ?? ''); $n2 = (string)($_POST['n2'] ?? '');
            if ($me === '') $accErr = 'Log opnieuw in en probeer nog eens.';
            elseif (!cmb_check_login($me, $cur)) $accErr = 'Je huidige wachtwoord klopt niet.';
            elseif ($n1 !== $n2) $accErr = 'De twee nieuwe wachtwoorden zijn niet gelijk.';
            elseif (strlen($n1) < 8) $accErr = 'Kies een wachtwoord van minstens 8 tekens.';
            else { $users[$me] = password_hash($n1, PASSWORD_DEFAULT); $accMsg = cmb_users_save($users) ? 'Je wachtwoord is veranderd. Gebruik het de volgende keer dat je inlogt.' : 'Opslaan lukt niet (map cmb-data niet beschrijfbaar).'; if (strpos($accMsg, 'lukt niet') !== false) { $accErr = $accMsg; $accMsg = ''; } }
        } elseif ($act === 'add') {
            $nu = trim((string)($_POST['nu'] ?? '')); $np = (string)($_POST['np'] ?? '');
            if (!preg_match('/^[A-Za-z0-9._-]{3,30}$/', $nu)) $accErr = 'Gebruikersnaam: 3-30 tekens, letters, cijfers, punt, streepje.';
            elseif (isset($users[$nu]) || $nu === (string)cmb_cfg('admin_user', '')) $accErr = 'Deze gebruiker bestaat al.';
            elseif (strlen($np) < 8) $accErr = 'Kies een wachtwoord van minstens 8 tekens.';
            else { $users[$nu] = password_hash($np, PASSWORD_DEFAULT); if (cmb_users_save($users)) $accMsg = 'Gebruiker ' . $nu . ' toegevoegd. Hij kan nu inloggen en zijn eigen wachtwoord veranderen.'; else $accErr = 'Opslaan lukt niet (map cmb-data niet beschrijfbaar).'; }
        } elseif ($act === 'del') {
            $du = (string)($_POST['du'] ?? '');
            if ($du === $me) $accErr = 'Je kunt jezelf niet verwijderen.';
            elseif (isset($users[$du])) { unset($users[$du]); cmb_users_save($users); $accMsg = 'Gebruiker ' . $du . ' verwijderd.'; }
        }
    }
}
$accUsers = array_keys(cmb_users_load());
$snippet = ''; $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim((string)($_POST['u'] ?? ''));
    $p1 = (string)($_POST['p1'] ?? ''); $p2 = (string)($_POST['p2'] ?? '');
    if ($u === '' || $p1 === '') $err = 'Vul een gebruikersnaam en wachtwoord in.';
    elseif ($p1 !== $p2) $err = 'De twee wachtwoorden zijn niet gelijk.';
    elseif (strlen($p1) < 8) $err = 'Kies een wachtwoord van minstens 8 tekens.';
    else $snippet = "    'admin_user'      => " . var_export($u, true) . ",\n    'admin_pass_hash' => " . var_export(password_hash($p1, PASSWORD_DEFAULT), true) . ",";
}
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Inlog &amp; config</title>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:20px; }
    .wrap { max-width:900px; margin:0 auto; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:16px; margin-bottom:16px; }
    h1 { color:#ffd166; font-size:20px; margin:0 0 10px; } h2 { font-size:15px; color:#63f6ff; margin:0 0 10px; }
    code, pre { background:#05060d; border:1px solid rgba(255,255,255,.12); border-radius:6px; padding:2px 6px; font-size:12px; overflow-wrap:anywhere; }
    pre { padding:10px; white-space:pre-wrap; }
    label { display:block; font-size:12px; color:#a8aac8; margin:8px 0 4px; }
    input { width:100%; max-width:360px; background:#05060d; color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:8px; padding:9px; font-size:14px; }
    button { margin-top:12px; cursor:pointer; border:none; border-radius:8px; font-weight:800; padding:10px 14px; background:linear-gradient(135deg,#ffd166,#ff5bc8); color:#070812; }
    .ok { color:#00e676; } .warn { color:#ffd166; } .err { color:#ff8aa0; }
    p, li { font-size:13px; line-height:1.55; }
</style>
</head>
<body>
<div class="wrap">
    <?php require_once CMB_ROOT . '/cmb-core/cmb-nav.php'; echo cmb_nav('inlog', 'cmb-admin.php?logout=1'); ?>
    <h1>🔑 Inlog &amp; config</h1>
    <div class="card">
        <h2>Mijn wachtwoord veranderen</h2>
        <?php if ($accMsg): ?><p class="ok"><?= $h($accMsg) ?></p><?php endif; ?>
        <?php if ($accErr): ?><p class="err"><?= $h($accErr) ?></p><?php endif; ?>
        <p>Ingelogd als <strong><?= $h($me !== '' ? $me : '?') ?></strong>.</p>
        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['cmb_acc_csrf']) ?>"><input type="hidden" name="acc_action" value="change">
            <label>Huidig wachtwoord</label><input type="password" name="cur" required>
            <label>Nieuw wachtwoord (minstens 8 tekens)</label><input type="password" name="n1" required>
            <label>Nieuw wachtwoord nog een keer</label><input type="password" name="n2" required>
            <button type="submit">Wachtwoord opslaan</button>
        </form>
    </div>
    <div class="card">
        <h2>Gebruikers</h2>
        <p>Iedereen die hier staat kan inloggen met zijn eigen wachtwoord en dat zelf veranderen. Iedereen heeft dezelfde rechten.</p>
        <?php foreach ($accUsers as $au): ?>
            <form method="POST" style="display:flex; gap:10px; align-items:center; margin:4px 0;"><input type="hidden" name="csrf" value="<?= $h($_SESSION['cmb_acc_csrf']) ?>"><input type="hidden" name="acc_action" value="del"><input type="hidden" name="du" value="<?= $h($au) ?>">
            <span><?= $h($au) ?><?= $au === $me ? ' (jij)' : '' ?></span><?php if ($au !== $me): ?><button type="submit" style="margin:0; padding:5px 10px; background:#ff5b7a;" onclick="return confirm('Verwijderen?')">Verwijder</button><?php endif; ?></form>
        <?php endforeach; ?>
        <form method="POST" autocomplete="off" style="margin-top:10px;">
            <input type="hidden" name="csrf" value="<?= $h($_SESSION['cmb_acc_csrf']) ?>"><input type="hidden" name="acc_action" value="add">
            <label>Nieuwe gebruiker (bijvoorbeeld anton)</label><input type="text" name="nu" required>
            <label>Tijdelijk wachtwoord (minstens 8 tekens, hij verandert het zelf)</label><input type="password" name="np" required>
            <button type="submit">Gebruiker toevoegen</button>
        </form>
    </div>
    <div class="card">
        <h2>Welke config wordt gebruikt?</h2>
        <?php if ($cfgPath === ''): ?>
            <p class="warn">Er is nog geen <code>cmb-config.php</code> gevonden. De oude standaard-inlog is nog actief.</p>
            <p>Zet <code>cmb-config.example.php</code> (uit deze map) hernoemd als <code>cmb-config.php</code> hier neer:<br><code><?= $h($suggest) ?></code></p>
        <?php else: ?>
            <p class="ok">Gevonden: <code><?= $h($cfgPath) ?></code></p>
            <?php if ($inPublic): ?><p class="err">Let op: dit bestand staat binnen public_html. Zet het liever in <code><?= $h($suggest) ?></code>.</p><?php endif; ?>
            <?php if (cmb_cfg('admin_pass_hash', '') === ''): ?><p class="warn">Er staat nog geen <code>admin_pass_hash</code> in. Maak hieronder een hash en plak hem erin.</p><?php endif; ?>
        <?php endif; ?>
        <p>Data van deze installatie: <code><?= $h(cmb_data_dir()) ?></code><br>Uploads: <code><?= $h(cmb_upload_dir()) ?></code></p>
    </div>
    <div class="card">
        <h2>Nieuw wachtwoord maken</h2>
        <form method="POST" autocomplete="off">
            <label>Gebruikersnaam</label><input name="u" value="<?= $h($_POST['u'] ?? cmb_cfg('admin_user', 'chazzo')) ?>">
            <label>Nieuw wachtwoord (minstens 8 tekens)</label><input type="password" name="p1">
            <label>Nog een keer</label><input type="password" name="p2">
            <button type="submit">Maak regels voor cmb-config.php</button>
        </form>
        <?php if ($err): ?><p class="err"><?= $h($err) ?></p><?php endif; ?>
        <?php if ($snippet): ?>
            <p class="ok">Vervang in <code>cmb-config.php</code> de regels <code>admin_user</code> en <code>admin_pass_hash</code> door:</p>
            <pre><?= $h($snippet) ?></pre>
            <p>Het wachtwoord zelf wordt nergens bewaard; alleen deze hash. Log daarna uit en weer in om te testen.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
