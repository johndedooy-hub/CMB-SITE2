<?php
/**
 * CHAZZO MUSIC BOX (CMB) - BACK-UP, EXPORT, IMPORT & HERSTEL
 * Bestandsnaam: cmb-backup.php
 *
 * - Download alle instellingen, slides en stream-presets als één bestand
 * - Importeer zo'n bestand weer (met automatische back-up van wat er nu staat)
 * - Herstel een van de automatische back-ups die bij elke opslag worden gemaakt
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';

if (empty($_SESSION['cmb_auth'])) {
    header('Location: cmb-admin.php');
    exit;
}
if (empty($_SESSION['cmb_backup_csrf'])) {
    $_SESSION['cmb_backup_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['cmb_backup_csrf'];

$dataDir = cmb_data_dir();
$snapDir = cmb_cache_dir() . 'snapshots/';
$settingsFile = $dataDir . 'cmb-settings.json';
$slidesFile = $dataDir . 'cmb-slides.json';
$presetsFile = $dataDir . 'stream-presets.json';

function read_json_file($f) {
    if (!is_file($f)) return [];
    $d = json_decode(file_get_contents($f), true);
    return is_array($d) ? $d : [];
}

/* ---- Download ---- */
if (isset($_GET['download'])) {
    $bundle = [
        'cmb_backup' => 1,
        'created' => date('c'),
        'settings' => read_json_file($settingsFile),
        'slides' => read_json_file($slidesFile),
        'presets' => read_json_file($presetsFile),
        'themes' => cmb_themes_load(),
        'theme_files' => (function () {
            $o = [];
            foreach (cmb_themes_load()['themes'] as $t) {
                if (!empty($t['id']) && is_array($snap = cmb_theme_snapshot_load($t['id']))) $o[$t['id']] = $snap;
            }
            return $o;
        })(),
    ];
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="cmb-backup-' . date('Ymd-His') . '.json"');
    echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
        $err = 'Ongeldige sessie-token; ververs de pagina en probeer opnieuw.';
    } elseif (($_POST['action'] ?? '') === 'import') {
        if (empty($_FILES['bundle']['tmp_name']) || !is_uploaded_file($_FILES['bundle']['tmp_name'])) {
            $err = 'Kies eerst een back-upbestand.';
        } else {
            $b = json_decode(file_get_contents($_FILES['bundle']['tmp_name']), true);
            if (!is_array($b) || empty($b['cmb_backup']) || !isset($b['settings']) || !is_array($b['settings'])) {
                $err = 'Dit is geen geldig CMB back-upbestand.';
            } else {
                cmb_snapshot($settingsFile);
                cmb_snapshot($slidesFile);
                file_put_contents($settingsFile, json_encode($b['settings'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
                if (isset($b['slides']) && is_array($b['slides'])) {
                    file_put_contents($slidesFile, json_encode($b['slides'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
                }
                if (isset($b['presets']) && is_array($b['presets'])) {
                    file_put_contents($presetsFile, json_encode($b['presets'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
                }
                // Thema's (sinds ronde 11 in de back-up)
                if (isset($b['themes']) && is_array($b['themes']) && isset($b['themes']['themes'])) {
                    if (!is_dir(cmb_theme_dir())) @mkdir(cmb_theme_dir(), 0755, true);
                    foreach ((array)($b['theme_files'] ?? []) as $tid => $snap) {
                        $tid = preg_replace('/[^a-z0-9_-]/', '', strtolower((string)$tid));
                        if ($tid !== '' && is_array($snap)) file_put_contents(cmb_theme_file($tid), json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
                    }
                    cmb_themes_save($b['themes']);
                }
                cmb_pulse('all');
                $msg = '✓ Back-up geïmporteerd. De speler past zich binnen enkele seconden aan.';
            }
        }
    } elseif (($_POST['action'] ?? '') === 'restore') {
        $name = basename((string)($_POST['snapshot'] ?? ''));
        if (!preg_match('/^(cmb-settings|cmb-slides)__\d{8}-\d{6}\.json$/', $name) || !is_file($snapDir . $name)) {
            $err = 'Back-up niet gevonden.';
        } else {
            $content = json_decode(file_get_contents($snapDir . $name), true);
            if (!is_array($content)) {
                $err = 'Deze back-up is beschadigd.';
            } else {
                $target = (strpos($name, 'cmb-settings__') === 0) ? $settingsFile : $slidesFile;
                cmb_snapshot($target);
                copy($snapDir . $name, $target);
                $msg = '✓ Hersteld: ' . $name;
            }
        }
    }
}

$snaps = glob($snapDir . '*.json') ?: [];
rsort($snaps);
$snaps = array_slice($snaps, 0, 40);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Back-up & Herstel</title>
<style>
    * { box-sizing: border-box; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    body { background:#070812; color:#f7f7ff; margin:0; padding:20px; }
    .wrap { max-width:860px; margin:0 auto; }
    h1 { color:#ffd166; font-size:20px; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:16px; margin-bottom:16px; }
    h2 { margin:0 0 10px; font-size:16px; color:#63f6ff; }
    a.back { color:#63f6ff; font-size:12px; text-decoration:none; margin-right:12px; }
    .btn { display:inline-block; cursor:pointer; border:none; border-radius:8px; font-weight:800; padding:10px 14px; background:linear-gradient(135deg,#00e676,#00b0ff); color:#070812; text-decoration:none; font-size:13px; }
    .btn.warn { background:#ff9100; }
    .ok { background:rgba(0,230,118,.15); border:1px solid #00e676; color:#00e676; padding:10px; border-radius:8px; margin-bottom:12px; }
    .bad { background:rgba(255,23,68,.15); border:1px solid #ff1744; color:#ff8aa0; padding:10px; border-radius:8px; margin-bottom:12px; }
    .snap { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid rgba(255,255,255,.06); font-size:13px; }
    .snap span { flex:1; overflow-wrap:anywhere; }
    input[type=file] { color:#fff; }
</style>
</head>
<body>
<div class="wrap">
    <?php require_once CMB_ROOT . '/cmb-core/cmb-nav.php'; echo cmb_nav('backup', 'cmb-admin.php?logout=1'); ?>
    <h1>💾 Back-up, export &amp; herstel</h1>
    <?php if ($msg): ?><div class="ok"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bad"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="card">
        <h2>Alles downloaden</h2>
        <p style="font-size:13px; color:#a8aac8; margin-top:0;">Eén bestand met alle instellingen, slides, clips en stream-presets. Geüploade afbeeldingen en video's (map cmb-uploads) zitten er niet in.</p>
        <a class="btn" href="cmb-backup.php?download=1">⬇ Download back-up</a>
    </div>

    <div class="card">
        <h2>Back-up importeren</h2>
        <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Huidige instellingen worden vervangen (er wordt eerst automatisch een back-up gemaakt). Doorgaan?');">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="import">
            <input type="file" name="bundle" accept=".json,application/json"><br><br>
            <button type="submit" class="btn warn">⬆ Importeer</button>
        </form>
    </div>

    <div class="card">
        <h2>Automatische back-ups (laatste 40)</h2>
        <p style="font-size:13px; color:#a8aac8; margin-top:0;">Bij elke opslag in admin of studio wordt de vorige versie bewaard (maximaal 15 per bestand).</p>
        <?php if (!$snaps): ?>
            <div style="font-size:13px; color:#a8aac8;">Nog geen back-ups.</div>
        <?php endif; ?>
        <?php foreach ($snaps as $sf): $bn = basename($sf); ?>
            <form method="POST" class="snap" onsubmit="return confirm('Deze versie terugzetten?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="restore">
                <input type="hidden" name="snapshot" value="<?= htmlspecialchars($bn) ?>">
                <span><?= htmlspecialchars($bn) ?> <small style="color:#a8aac8;">(<?= round(filesize($sf) / 1024, 1) ?> KB)</small></span>
                <button type="submit" class="btn warn">Terugzetten</button>
            </form>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>
