<?php
/**
 * FOCUS FANTASTIQUE - REGIOAGENDA INVOER (voor de dj)
 * Zet dit bestand op de website, naast ff-regioagenda.txt.
 * Zelfde inlog en zelfde gegevens als CMB > Regioagenda: wat hier wordt getypt staat ook in het beheer (en andersom).
 *
 * Staat CMB in een andere map dan dit bestand? Pas dan hieronder $CMB_DIR aan, bv. __DIR__ . '/cmb3'
 */
$CMB_DIR = __DIR__;

ini_set('display_errors', 0);
session_start();
define('CMB_ROOT', rtrim($CMB_DIR, '/\\'));
require_once CMB_ROOT . '/cmb-core/cmb-regio.php';

cmb_regio_require_login('Focus Fantastique regioagenda', basename(__FILE__));

$flash = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['act']) || cmb_regio_post_too_big())) {
    if (cmb_regio_post_too_big()) {
        $flash[] = ['err', 'Niets opgeslagen: het plaatje is te groot voor de server. Kies een kleiner plaatje.'];
    } elseif (!cmb_regio_csrf_ok()) {
        $flash[] = ['err', 'Sessie verlopen, probeer opnieuw.'];
    } else {
        $d = cmb_regio_from_post($_POST, cmb_regio_load());
        foreach (cmb_regio_uploads($d) as $e) $flash[] = ['err', $e];
        if (!cmb_regio_save($d)) $flash[] = ['err', 'Opslaan mislukt.'];
        else $flash[] = ['ok', 'Opgeslagen. De regioagenda-teksten staan klaar.'];
        foreach (cmb_regio_write_txt($d) as $e) $flash[] = ['err', $e];
    }
}
$d = cmb_regio_load();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Focus Fantastique regioagenda</title>
<style>
 *{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
 body{background:#070812;color:#f7f7ff;margin:0;padding:20px}
 .wrap{max-width:860px;margin:0 auto}
 h1{color:#ffd166;font-size:22px;margin:0 0 6px}
 .msg{padding:10px 14px;border-radius:8px;margin:10px 0;font-size:13px;font-weight:700}
 .msg.ok{background:rgba(0,230,118,.12);border:1px solid #00e676;color:#7dffb3}
 .msg.err{background:rgba(255,23,68,.15);border:1px solid #ff1744;color:#ff8aa0}
 .save{border:none;border-radius:9px;padding:13px 22px;font-weight:900;font-size:15px;cursor:pointer;color:#070812;background:#ffd166}
 .bar{position:sticky;bottom:0;background:rgba(7,8,18,.95);padding:12px 0;display:flex;gap:12px;align-items:center;flex-wrap:wrap}
 a{color:#63f6ff}
 <?= cmb_regio_form_css() ?>
</style>
</head>
<body>
<div class="wrap">
 <h1>📅 Focus Fantastique regioagenda</h1>
 <p class="rg-hint">Typ de onderwerpen. Begin en einde ("Dit is de Focus Fantastique regioagenda…" en "Tot zover…") worden er automatisch omheen gezet.</p>
 <?php foreach ($flash as $m): ?><div class="msg <?= $m[0] === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($m[1]) ?></div><?php endforeach; ?>
 <form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars(cmb_regio_csrf()) ?>">
  <?= cmb_regio_form_fields($d) ?>
  <div class="bar">
   <button class="save" type="submit" name="act" value="save">💾 Opslaan</button>
   <a href="?logout=1">Uitloggen</a>
  </div>
 </form>
</div>
</body>
</html>
