<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
cmb_unpack_payload();
cmb_admin_media_urls();
require_once CMB_ROOT . '/cmb-core/cmb-nav.php';

// Inlog staat in cmb-config.php (buiten public_html), zie cmb-config.example.php

if (isset($_GET['logout'])) {
    unset($_SESSION['cmb_auth']);
    session_destroy();
    header('Location: cmb-studio.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $u = trim($_POST['login_user'] ?? '');
    $p = trim($_POST['login_pass'] ?? '');
    if (cmb_check_login($u, $p)) {
        $_SESSION['cmb_auth'] = true;
        header('Location: cmb-studio.php');
        exit;
    } else {
        $login_error = "Onjuiste inloggegevens!";
    }
}

if (empty($_SESSION['cmb_auth'])) {
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen - CMB Studio</title>
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #070812; color: #f7f7ff; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 16px; }
        .login-box { background: #121427; border: 1px solid rgba(255, 209, 102, 0.3); border-radius: 16px; padding: 32px; width: 100%; max-width: 360px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); text-align: center; }
        h2 { color: #ffd166; margin-top: 0; margin-bottom: 20px; font-size: 20px; }
        .field { margin-bottom: 14px; text-align: left; }
        label { display: block; font-size: 11px; text-transform: uppercase; color: #a8aac8; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; background: #05060d; border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 10px; border-radius: 8px; font-size: 14px; }
        input:focus { border-color: #ffd166; outline: none; }
        button { width: 100%; background: linear-gradient(135deg, #ffd166, #ff5bc8); border: none; color: #070812; padding: 12px; border-radius: 8px; font-weight: 900; font-size: 14px; cursor: pointer; margin-top: 10px; }
        .err { background: rgba(255, 23, 68, 0.15); border: 1px solid #ff1744; color: #ff1744; padding: 10px; border-radius: 6px; font-size: 12px; margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔒 CMB Studio Inloggen</h2>
        <?php if (!empty($login_error)): ?><div class="err"><?= htmlspecialchars($login_error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="field">
                <label>Gebruikersnaam</label>
                <input type="text" name="login_user" autofocus required>
            </div>
            <div class="field">
                <label>Wachtwoord</label>
                <input type="password" name="login_pass" required>
            </div>
            <button type="submit" name="login_submit">INLOGGEN</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}
define('CMB_PART', true);
require CMB_ROOT . '/cmb-studio-parts/cmb-studio-save.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CMB Studio</title>
    <link rel="stylesheet" href="cmb-assets/cmb-admin.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin.css') : time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@700&family=Dancing+Script:wght@700&family=Great+Vibes&family=Montserrat:wght@400;700;900&family=Orbitron:wght@700&family=Oswald:wght@400;700&family=Pacifico&family=Poppins:wght@600;800&family=Righteous&family=Russo+One&family=Satisfy&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #070812; color: #f7f7ff; padding: 20px; margin: 0; }
        .wrap { max-width: 1280px; margin: 0 auto; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 15px; flex-wrap: wrap; gap: 10px; }
        .top-bar h1 { color: #ffd166; margin: 0; font-size: 22px; font-family: 'Montserrat', sans-serif; }
        .top-nav-links { display: flex; gap: 8px; align-items: center; }
        .nav-btn { padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px; display: inline-flex; align-items: center; }
        .btn-admin-link { background: linear-gradient(135deg, #00e5ff, #00e676); color: #070812; font-weight: 900; }
        .logout-btn { background: #ff1744; color: #fff; }

        .tab-menu { display: flex; gap: 8px; margin-bottom: 25px; flex-wrap: wrap; }
        .tab-btn { background: #121427; border: 1px solid rgba(255,255,255,0.1); color: #a8aac8; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: bold; font-size: 13px; transition: 0.2s; display: inline-block; }
        .tab-btn:hover { background: #1c203d; color: #fff; }
        .tab-btn.active { background: #ffd166; color: #070812; border-color: #ffd166; }

        .card { border-radius: 14px; padding: 22px; margin-bottom: 22px; border: 1px solid rgba(255,255,255,0.08); background: #121427; }
        .card h2 { margin-top: 0; margin-bottom: 18px; font-size: 17px; border-bottom: 1px solid rgba(255,255,255,0.06); padding-bottom: 10px; font-family: 'Montserrat', sans-serif; color: #ffd166; }
        
        .style-box { background: rgba(5,6,14,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 8px; padding: 12px; margin-bottom: 12px; }
        .style-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; }
        .style-tag { font-size: 11px; color: #ffd166; font-weight: bold; }
        .style-drawer { display: none; margin-top: 10px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 10px; }
        .style-drawer.open { display: block; }

        .slide-block { background: rgba(5,6,14,0.6); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 16px; margin-bottom: 18px; }
        .emer-card { border: 2px solid #ff1744; background: rgba(255, 23, 68, 0.05); }

        .sched-hours-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 4px; margin-top: 4px; }
        .sched-cell { font-size: 10px; background: #05060d; padding: 4px; border-radius: 4px; text-align: center; border: 1px solid rgba(255,255,255,0.08); }

        .form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
        .field { flex: 1; min-width: 180px; display: flex; flex-direction: column; }
        label { font-size: 11px; text-transform: uppercase; color: #a8aac8; margin-bottom: 4px; font-weight: bold; }
        input, select, textarea { background: #05060d !important; border: 1px solid rgba(255,255,255,0.15) !important; color: #fff !important; padding: 8px; border-radius: 6px; font-size: 13px; font-family: inherit; }
        textarea { resize: vertical; min-height: 48px; }
        input[type="color"] { padding: 1px; height: 35px; cursor: pointer; }

        .btn-mini-save { background: #00e676; border: none; color: #070812; padding: 5px 12px; border-radius: 6px; font-size: 11px; font-weight: bold; cursor: pointer; }
        .btn-save { background: linear-gradient(135deg, #ffd166, #00e5ff); border: none; color: #070812; padding: 18px 36px; border-radius: 12px; font-weight: 900; font-size: 16px; cursor: pointer; width: 100%; margin-top: 20px; box-shadow: 0 4px 20px rgba(255,209,102,0.3); }
        .msg { background: rgba(0,230,118,0.15); border: 1px solid #00e676; color: #00e676; padding: 14px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; }
        /* Compacte media-previews (90 x 56 px) en uploadvinkjes */
        .media-thumb-preview { width:90px; height:56px; flex:0 0 90px; position:relative; overflow:hidden; box-sizing:border-box; border:1px solid rgba(255,255,255,.2); border-radius:8px; background:#05060d; display:flex; align-items:center; justify-content:center; }
        .media-thumb-preview img, .media-thumb-preview video { width:100%; height:100%; object-fit:cover; display:block; }
        .media-thumb-preview .empty-txt { font-size:10px; color:#a8aac8; text-align:center; padding:2px; }
        .media-thumb-preview .del-btn { position:absolute; top:2px; right:2px; width:16px; height:16px; line-height:16px; text-align:center; font-size:10px; border-radius:50%; background:rgba(255,23,68,.9); color:#fff; cursor:pointer; }
        .cmb-upload-tick { display:none; margin-top:4px; font-size:11px; font-weight:900; color:#00e676; }
        .cmb-upload-tick.on { display:block; }
    </style>
</head>
<body>
<div class="wrap">
    <?= cmb_nav($activeTab === 'slides' ? 'slides' : $activeTab, 'cmb-studio.php?logout=1') ?>

    <?php if (!empty($msg)): ?><div class="msg"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="adminForm" accept-charset="UTF-8">
        <input type="hidden" name="active_tab_context" value="<?= htmlspecialchars($activeTab) ?>">

<?php if ($activeTab === 'slides'): ?>
<?php include CMB_ROOT . '/cmb-studio-parts/cmb-studio-slides.php'; ?>
<?php elseif ($activeTab === 'widgets'): ?>
<?php include CMB_ROOT . '/cmb-studio-parts/cmb-studio-widgets.php'; ?>
<?php elseif ($activeTab === 'streams'): ?>
<!-- TAB 7: AUDIO STREAMS (EXACT CONFORM STREAM-PRESETS.JSON) -->
<?php include CMB_ROOT . '/cmb-studio-parts/cmb-studio-streams.php'; ?>
<?php endif; ?>

        <button type="submit" name="save_studio" class="btn-save">💾 DEZE INSTELLINGEN OPSLAAN</button>
    </form>
</div>

<script src="cmb-assets/cmb-studio.js?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-studio.js') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-studio.js') : time() ?>"></script>
<script src="cmb-assets/cmb-admin-ui.js?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin-ui.js') : time() ?>"></script>
<script src="cmb-assets/cmb-filepick.js?v=2"></script>
</body>
</html>
