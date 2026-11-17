<?php
/**
 * CHAZZO MUSIC BOX (CMB) - BEHEER STARTPAGINA (tegels)
 * Bestandsnaam: cmb-start.php
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }
require_once CMB_ROOT . '/cmb-core/cmb-nav.php';
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Beheer - Start</title>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:16px; }
    .wrap { max-width:1100px; margin:0 auto; }
    h1 { color:#ffd166; font-size:22px; margin:6px 0 4px; }
    .sub { color:#a8aac8; font-size:13px; margin:0 0 14px; }
    h2 { font-size:12px; text-transform:uppercase; letter-spacing:.08em; color:#a8aac8; margin:20px 0 8px; }
    .tiles { display:grid; grid-template-columns:repeat(auto-fill,minmax(210px,1fr)); gap:10px; }
    .tile { display:flex; flex-direction:column; gap:4px; padding:14px; border-radius:14px; text-decoration:none; color:#fff; background:#121427; border:1px solid rgba(255,255,255,.1); min-height:92px; transition:transform .15s, border-color .15s; }
    .tile:hover { transform:translateY(-2px); border-color:#63f6ff; }
    .tile b { font-size:15px; } .tile span { font-size:12px; color:#a8aac8; line-height:1.4; }
    .tile.big { background:linear-gradient(135deg,#0f2a3a,#1a1d38); border-color:#63f6ff55; }
</style>
</head>
<body>
<div class="wrap">
    <?= cmb_nav('start', 'cmb-admin.php?logout=1') ?>
    <h1>Waar wil je naartoe?</h1>
    <p class="sub">Kies een onderdeel. Het menu bovenaan blijft op elke pagina staan.</p>
    <div class="tiles">
        <a class="tile big" href="index.php" target="_blank" rel="noopener"><b>▶ Speler openen</b><span>Bekijk de speler zoals bezoekers hem zien</span></a>
    </div>
    <?php foreach (cmb_nav_groups() as $g): ?>
        <h2><?= $h($g[0]) ?></h2>
        <div class="tiles">
            <?php foreach ($g[1] as $it): ?>
                <a class="tile" href="<?= $h($it[1]) ?>"<?= !empty($it[2]) ? ' target="_blank" rel="noopener"' : '' ?>><b><?= $h($it[0]) ?></b><span><?= $h($it[3] ?? '') ?></span></a>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
