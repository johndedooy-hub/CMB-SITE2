<?php
/**
 * CHAZZO MUSIC BOX (CMB) - HERSTEL WIJZIGINGEN VAN DE AI-ASSISTENT
 * Bestandsnaam: cmb-herstel-ai.php
 *
 * Een update-zip overschrijft bestanden (bv. cmb-custom.css, cmb-effects.css) waarin de AI-assistent wijzigingen had gezet.
 * De assistent bewaart elke goedgekeurde wijziging in cmb-cache/claude-pending/. Deze pagina laat zien welke wijzigingen
 * ontbreken en zet ze met één klik terug (met eerst een back-up van het bestand zoals het nu is).
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }
define('CMB_PART', true);
require_once CMB_ROOT . '/cmb-core/cmb-claude-func.php';
require_once CMB_ROOT . '/cmb-core/cmb-nav.php';

$pendDir = cmb_cache_dir() . 'claude-pending/';
$bakDir  = cmb_cache_dir() . 'claude-backups/';
if (!is_dir($bakDir)) @mkdir($bakDir, 0755, true);
if (empty($_SESSION['cmb_herstel_csrf'])) $_SESSION['cmb_herstel_csrf'] = bin2hex(random_bytes(16));
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };

/** Alleen gewone projectbestanden; nooit sleutels, config of de assistent zelf. */
function herstel_pad_ok($rel) {
    $rel = ltrim(str_replace('\\', '/', (string)$rel), '/');
    if ($rel === '' || strpos($rel, '..') !== false) return '';
    if (!preg_match('/\.(css|js|php|json)$/i', $rel)) return '';
    if (preg_match('/(cmb-config|key|token|cmb-claude|cmb-ai-helper|cmb-wachtwoord|cmb-herstel)/i', $rel)) return '';
    return $rel;
}

// Alle goedgekeurde (toegepaste) wijzigingen, oudste eerst
$recs = [];
foreach (glob($pendDir . '*.json') ?: [] as $f) {
    $r = json_decode((string)@file_get_contents($f), true);
    if (is_array($r) && ($r['status'] ?? '') === 'applied' && herstel_pad_ok($r['path'] ?? '') !== '') $recs[] = $r;
}
usort($recs, function ($a, $b) { return strcmp($a['applied'] ?? $a['created'] ?? '', $b['applied'] ?? $b['created'] ?? ''); });

/** Staat deze wijziging nog in het bestand? */
function herstel_status($r) {
    $rel = herstel_pad_ok($r['path']);
    $f = cmb_claude_abs($rel);
    $cur = is_file($f) ? (string)file_get_contents($f) : '';
    $new = (string)($r['new'] ?? '');
    if ($new !== '' && strpos($cur, $new) !== false) return 'aanwezig';
    if ($new === '') return 'aanwezig'; // een verwijdering: niets terug te zetten
    list($res, $err) = apply_edit_to_text($cur, (string)($r['old'] ?? ''), $new);
    return $res === null ? 'handwerk' : 'ontbreekt';
}

$report = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['cmb_herstel_csrf'], (string)($_POST['csrf'] ?? ''))) {
    $sel = array_map('strval', (array)($_POST['herstel'] ?? []));
    foreach ($recs as $r) {
        if (!in_array($r['id'], $sel, true)) continue;
        $rel = herstel_pad_ok($r['path']);
        $f = cmb_claude_abs($rel);
        $cur = is_file($f) ? (string)file_get_contents($f) : '';
        $new = (string)($r['new'] ?? '');
        if ($new !== '' && strpos($cur, $new) !== false) { $report[] = [$rel, 'stond er al in, overgeslagen']; continue; }
        list($res, $err) = apply_edit_to_text($cur, (string)($r['old'] ?? ''), $new);
        if ($res === null) { $report[] = [$rel, 'niet teruggezet: ' . $err]; continue; }
        if (substr($rel, -4) === '.php') {
            list($okLint, $msg) = lint_php($res);
            if (!$okLint) { $report[] = [$rel, 'niet teruggezet (PHP-fout): ' . $msg]; continue; }
        }
        make_backup($rel, $bakDir);
        if (!is_dir(dirname($f))) @mkdir(dirname($f), 0755, true);
        if (file_put_contents($f, $res, LOCK_EX) === false) { $report[] = [$rel, 'niet teruggezet: bestand niet beschrijfbaar']; continue; }
        $report[] = [$rel, 'teruggezet ✔'];
    }
    cmb_pulse('all');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB herstel AI-wijzigingen</title>
<style>
 *{box-sizing:border-box;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif}
 body{background:#070812;color:#f7f7ff;margin:0;padding:20px}
 .wrap{max-width:900px;margin:0 auto}
 h1{color:#ffd166;font-size:20px;margin:0 0 8px}
 .card{background:#121427;border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:12px 14px;margin-bottom:10px}
 .b{font-size:11px;font-weight:900;border-radius:6px;padding:2px 8px;color:#070812}
 .ok{background:#00e676}.mis{background:#ffd166}.hand{background:#ff8aa0}
 p{font-size:13px;line-height:1.55;color:#c9cbe6}
 .meta{font-size:12px;color:#a8aac8}
 button{cursor:pointer;border:none;border-radius:9px;padding:12px 18px;font-weight:900;font-size:14px;background:linear-gradient(135deg,#00e676,#00b0ff);color:#070812}
 .rep{background:rgba(0,230,118,.1);border:1px solid #00e676;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px}
 pre{white-space:pre-wrap;background:#05060d;border-radius:8px;padding:8px;font-size:11px;color:#c9cbe6;max-height:160px;overflow:auto}
</style>
</head>
<body>
<div class="wrap">
<?= cmb_nav('herstel', 'cmb-admin.php?logout=1') ?>
<h1>🛟 Herstel wijzigingen van de AI-assistent</h1>
<p>Na een update kan het zijn dat iets wat de AI-assistent voor je maakte (bijvoorbeeld een cover of eigen stijl) weg is. Hieronder staan alle wijzigingen die je hebt goedgekeurd. Wat ontbreekt kun je met één klik terugzetten. Van elk bestand wordt eerst een back-up gemaakt.</p>
<?php if ($report): ?><div class="rep"><strong>Resultaat:</strong><br><?php foreach ($report as $x): ?><?= $h($x[0]) ?>: <?= $h($x[1]) ?><br><?php endforeach; ?></div><?php endif; ?>
<?php if (!$recs): ?>
  <div class="card">Er zijn geen goedgekeurde wijzigingen gevonden in <code>cmb-cache/claude-pending</code> van deze installatie. Zat de map <code>cmb-cache</code> niet in je kopie, dan kan ik niets terugzetten. Controleer dan of de map nog bestaat op de plek waar je de AI gebruikte.</div>
<?php else: ?>
<form method="POST">
<input type="hidden" name="csrf" value="<?= $h($_SESSION['cmb_herstel_csrf']) ?>">
<?php foreach ($recs as $r): $st = herstel_status($r); ?>
  <div class="card">
    <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer">
      <input type="checkbox" name="herstel[]" value="<?= $h($r['id']) ?>" <?= $st === 'ontbreekt' ? 'checked' : 'disabled' ?>>
      <span>
        <span class="b <?= $st === 'aanwezig' ? 'ok' : ($st === 'ontbreekt' ? 'mis' : 'hand') ?>"><?= $st === 'aanwezig' ? '✔ staat er nog in' : ($st === 'ontbreekt' ? '✖ ontbreekt, terug te zetten' : '⚠ past niet meer, handwerk') ?></span>
        <strong><?= $h($r['path']) ?></strong>
        <div class="meta"><?= $h(substr((string)($r['applied'] ?? $r['created'] ?? ''), 0, 16)) ?> · <?= $h($r['explanation'] ?? '') ?></div>
      </span>
    </label>
    <?php if ($st === 'handwerk'): ?><details><summary class="meta">Toon de wijziging (voor handmatig terugzetten of om aan de AI te geven)</summary><pre><?= $h($r['new'] ?? '') ?></pre></details><?php endif; ?>
  </div>
<?php endforeach; ?>
<button type="submit">🛟 Zet aangevinkte wijzigingen terug</button>
</form>
<?php endif; ?>
</div>
</body>
</html>
