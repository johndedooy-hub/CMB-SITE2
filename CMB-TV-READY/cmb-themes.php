<?php
/**
 * CHAZZO MUSIC BOX (CMB) - THEMA'S & PLANNING
 * Bestandsnaam: cmb-themes.php
 *
 * - 20 vaste kleurthema's bekijken of als startpunt gebruiken
 * - Eigen thema's maken (kopie van je opmaak), bewerken in het gewone beheer, en plannen:
 *   datum + tijd, weekdagen + tijd, of dagdeel. Per thema: "alleen kleuren" of "volledige thema-opmaak".
 * - Standaard als er niets gepland is: originele opmaak, een vast thema, of willekeurig.
 */
session_start();
require_once __DIR__ . '/cmb-core/cmb-lib.php';
if (empty($_SESSION['cmb_auth'])) { header('Location: cmb-admin.php'); exit; }
if (empty($_SESSION['cmb_theme_csrf'])) $_SESSION['cmb_theme_csrf'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['cmb_theme_csrf'];

if (isset($_GET['stop_edit'])) { unset($_SESSION['cmb_theme_edit']); header('Location: cmb-themes.php'); exit; }

$presets = cmb_theme_presets();
$d = cmb_themes_load();
$msg = '';
$dayNames = ['ma' => 'ma', 'di' => 'di', 'wo' => 'wo', 'do' => 'do', 'vr' => 'vr', 'za' => 'za', 'zo' => 'zo'];

function cmb_theme_slug($name, $d) {
    $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name)), '-') ?: 'thema';
    $base = substr($base, 0, 30);
    $ids = array_column($d['themes'], 'id');
    $id = $base; $n = 2;
    while (in_array($id, $ids, true)) $id = $base . '-' . $n++;
    return $id;
}
function cmb_original_settings() {
    $f = cmb_data_dir() . 'cmb-settings.json';
    $s = is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    unset($s['_theme']);
    return $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($csrf, (string)($_POST['csrf'] ?? ''))) {
    $act = (string)($_POST['act'] ?? '');

    if ($act === 'surprise') {
        $keys = array_keys($presets);
        $pk = $keys[array_rand($keys)];
        $pal = array_slice($presets[$pk], 1, 4);
        $snap = cmb_recolor(cmb_original_settings(), $pal);
        $modes = array_values(array_diff(array_keys(cmb_frame_modes()), ['none']));
        $fx = array_keys(cmb_effects_list());
        foreach (['lighting_left_frame' => 'cw', 'lighting_right_frame' => 'ccw'] as $lk => $dir) {
            $fr = (isset($snap[$lk]) && is_array($snap[$lk])) ? $snap[$lk] : [];
            $fr['mode'] = $modes ? $modes[array_rand($modes)] : 'chaser';
            $fr['theme'] = 'gradient'; $fr['grad_col1'] = $pal[0]; $fr['grad_col2'] = $pal[2];
            $fr['speed'] = [2, 3, 4, 5, 6, 8][array_rand([2, 3, 4, 5, 6, 8])] . 's';
            $fr['dir'] = mt_rand(0, 1) ? 'cw' : 'ccw';
            foreach (['top' => 0, 'right' => 2, 'bottom' => 1, 'left' => 0] as $sd => $pi) { $fr["{$sd}_col"] = $pal[$pi]; }
            $fr['corner_col'] = $pal[0];
            $snap[$lk] = $fr;
        }
        if ($fx) $snap['art_effect'] = $fx[array_rand($fx)];
        $name = '🎲 Verrassing ' . date('d-m H:i');
        $id = cmb_theme_slug('verrassing-' . date('dm-Hi'), $d);
        if (!is_dir(cmb_theme_dir())) @mkdir(cmb_theme_dir(), 0755, true);
        file_put_contents(cmb_theme_file($id), json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $d['themes'][] = ['id' => $id, 'name' => $name, 'mode' => 'full', 'enabled' => false, 'schedule' => ['type' => 'none'], 'palette' => $pal];
        cmb_themes_save($d);
        $surpriseId = $id;
        $msg = '🎲 Nieuw verrassingsthema in ' . $presets[$pk][0] . '-kleuren, met eigen frames en cover-effect. Bekijk het; bevalt het niet, verwijder het of maak een nieuwe.';
    }

    if ($act === 'new') {
        $name = trim((string)($_POST['new_name'] ?? '')) ?: 'Mijn thema';
        $base = (string)($_POST['new_base'] ?? 'current');
        $id = cmb_theme_slug($name, $d);
        $snap = cmb_original_settings();
        $meta = ['id' => $id, 'name' => mb_substr($name, 0, 40), 'mode' => 'colors', 'enabled' => false, 'schedule' => ['type' => 'none']];
        if (strpos($base, 'preset:') === 0 && isset($presets[substr($base, 7)])) {
            $pal = array_slice($presets[substr($base, 7)], 1, 4);
            $snap = cmb_recolor($snap, $pal);
            $meta['palette'] = $pal;
        }
        if (!is_dir(cmb_theme_dir())) @mkdir(cmb_theme_dir(), 0755, true);
        file_put_contents(cmb_theme_file($id), json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $d['themes'][] = $meta;
        cmb_themes_save($d);
        $msg = '✓ Thema "' . $meta['name'] . '" gemaakt. Klik op "Opmaak bewerken" om het aan te passen en stel daarna de planning in.';
    }

    // Opslaan gebeurt ook bij Bewerken/Verwijderen/Willekeurig, zodat je wijzigingen in de planning niet verloren gaan
    if (in_array($act, ['save', 'edit', 'delete', 'random_new'], true) && isset($_POST['fallback'])) {
        $fb = (string)($_POST['fallback'] ?? 'original');
        $valid = ['original', 'random'];
        foreach (array_keys($presets) as $k) $valid[] = 'preset:' . $k;
        foreach ($d['themes'] as $t) $valid[] = 'own:' . $t['id'];
        $d['fallback'] = in_array($fb, $valid, true) ? $fb : 'original';
        if ($d['fallback'] === 'random' && empty($d['random_pick'])) $d['random_pick'] = cmb_theme_random_pick($d);
        foreach ($d['themes'] as $i => $t) {
            $p = 't_' . $t['id'] . '_';
            $d['themes'][$i]['name'] = mb_substr(trim((string)($_POST[$p . 'name'] ?? $t['name'])) ?: $t['name'], 0, 40);
            $d['themes'][$i]['mode'] = ($_POST[$p . 'mode'] ?? 'colors') === 'full' ? 'full' : 'colors';
            $d['themes'][$i]['enabled'] = !empty($_POST[$p . 'enabled']);
            $type = (string)($_POST[$p . 'type'] ?? 'none');
            if (!in_array($type, ['none', 'date', 'weekday', 'daypart'], true)) $type = 'none';
            $days = [];
            foreach (array_keys($dayNames) as $dk) if (!empty($_POST[$p . 'day_' . $dk])) $days[] = $dk;
            $hm = function ($v) { return preg_match('/^\d{1,2}:\d{2}$/', trim((string)$v)) ? trim((string)$v) : ''; };
            $d['themes'][$i]['schedule'] = [
                'type' => $type,
                'date_from' => cmb_clean_dt($_POST[$p . 'date_from'] ?? ''),
                'date_until' => cmb_clean_dt($_POST[$p . 'date_until'] ?? ''),
                'days' => $days,
                'time_from' => $hm($_POST[$p . 'time_from'] ?? ''),
                'time_until' => $hm($_POST[$p . 'time_until'] ?? ''),
            ];
        }
        cmb_themes_save($d);
        if ($act === 'save') $msg = '✓ Thema\'s en planning opgeslagen. De speler wisselt vanzelf op het juiste moment.';
    }

    if ($act === 'random_new') {
        $d['random_pick'] = cmb_theme_random_pick($d);
        $d['fallback'] = 'random';
        cmb_themes_save($d);
        $msg = '🎲 Nieuw willekeurig thema: ' . cmb_theme_label($d['random_pick'], $d) . '.';
    }

    $tid = preg_replace('/[^a-z0-9_-]/', '', (string)($_POST['id'] ?? ''));
    if ($tid !== '' && in_array($tid, array_column($d['themes'], 'id'), true)) {
        if ($act === 'edit') {
            $_SESSION['cmb_theme_edit'] = $tid;
            header('Location: cmb-admin.php?tab=identiteit');
            exit;
        }
        if ($act === 'delete') {
            $d['themes'] = array_values(array_filter($d['themes'], function ($t) use ($tid) { return $t['id'] !== $tid; }));
            if (($d['fallback'] ?? '') === 'own:' . $tid) $d['fallback'] = 'original';
            if (($d['random_pick'] ?? '') === 'own:' . $tid) $d['random_pick'] = '';
            @unlink(cmb_theme_file($tid));
            if (($_SESSION['cmb_theme_edit'] ?? '') === $tid) unset($_SESSION['cmb_theme_edit']);
            cmb_themes_save($d);
            $msg = '🗑 Thema verwijderd.';
        }
    }
    $d = cmb_themes_load();
}

$active = cmb_theme_active(null, $d);
$h = function ($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); };
$sw = function ($cols) use ($h) {
    $o = '';
    foreach ($cols as $c) $o .= '<i style="background:' . $h($c) . '"></i>';
    return '<span class="sw">' . $o . '</span>';
};
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMB Thema's &amp; planning</title>
<link rel="stylesheet" href="cmb-assets/cmb-admin.css?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-admin.css') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-admin.css') : time() ?>">
<style>
    body { background:#070812; color:#f7f7ff; margin:0; padding:20px; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif; }
    .wrap { max-width:1100px; margin:0 auto; }
    .card { background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:14px; padding:16px; margin-bottom:16px; }
    h1 { color:#ffd166; font-size:20px; margin:0 0 12px; } h2 { font-size:15px; color:#63f6ff; margin:0 0 10px; }
    .msg { background:rgba(0,230,118,.12); border:1px solid #00e676; border-radius:10px; padding:10px 14px; margin-bottom:14px; font-size:13px; }
    .grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(165px, 1fr)); gap:10px; }
    .tile { background:#0b0d1c; border:1px solid rgba(255,255,255,.1); border-radius:10px; padding:10px; font-size:13px; }
    .tile.on { border-color:#ffd166; box-shadow:0 0 0 1px #ffd166; }
    .sw { display:flex; gap:3px; margin:6px 0 8px; } .sw i { flex:1; height:16px; border-radius:4px; display:block; }
    .tile a, .tile button { font-size:11px; }
    button, .btn { cursor:pointer; border:none; border-radius:7px; font-weight:800; padding:7px 10px; background:#2a2e52; color:#fff; text-decoration:none; display:inline-block; }
    .btn-main { background:linear-gradient(135deg,#ffd166,#ff5bc8); color:#070812; padding:11px 16px; }
    .btn-del { background:rgba(255,23,68,.25); }
    label { font-size:12px; color:#a8aac8; }
    input[type=text], input[type=time], input[type=datetime-local], select { background:#05060d; color:#fff; border:1px solid rgba(255,255,255,.2); border-radius:7px; padding:7px; font-size:13px; }
    .own { border:1px solid rgba(255,255,255,.12); border-radius:12px; padding:12px; margin-bottom:12px; }
    .row { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; margin-bottom:8px; }
    .row > div { display:flex; flex-direction:column; gap:4px; }
    .muted { color:#a8aac8; font-size:12px; line-height:1.5; }
    .status { font-size:14px; } .status b { color:#ffd166; }
</style>
</head>
<body>
<div class="wrap">
    <?php require_once CMB_ROOT . '/cmb-core/cmb-nav.php'; echo cmb_nav('themas', 'cmb-admin.php?logout=1'); ?>
    <h1>🎨 Thema's &amp; planning</h1>
    <?php if ($msg): ?><div class="msg"><?= $h($msg) ?><?php if (!empty($surpriseId)): ?> · <a href="index.php?theme_preview=<?= urlencode('own:' . $surpriseId) ?>" target="_blank" style="color:#63f6ff;">👁 Bekijk nu</a><?php endif; ?></div><?php endif; ?>
    <form method="POST" class="card" style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
        <button type="submit" class="btn-main" name="act" value="surprise">🎲 Verras me helemaal</button>
        <span class="muted">Maakt een nieuw thema met kleuren, frames en cover-effect die bij elkaar passen. Je originele opmaak blijft ongemoeid; het thema staat uit tot jij het plant of als standaard kiest.</span>
    </form>

    <div class="card status">
        Nu in de speler: <b><?= $active ? $h($active['name']) : 'Originele opmaak' ?></b>
        <?php if ($active): ?><span class="muted">(<?= $h($active['reason']) ?>)</span><?php endif; ?>
        · <a href="index.php" target="_blank" style="color:#63f6ff;">speler openen</a>
        <div class="muted" style="margin-top:6px;">Volgorde als planningen overlappen: <b>datum</b> gaat voor <b>weekdag</b>, en weekdag gaat voor <b>dagdeel</b>. Is er niets gepland, dan geldt de standaard hieronder. Het beheer laat altijd je originele opmaak zien; thema's gelden alleen in de speler.</div>
    </div>

    <form method="POST">
    <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
    <div class="card">
        <h2>Standaard (als er niets gepland is)</h2>
        <div class="row">
            <div><label>Toon</label>
                <select name="fallback">
                    <option value="original" <?= $d['fallback'] === 'original' ? 'selected' : '' ?>>Originele opmaak</option>
                    <option value="random" <?= $d['fallback'] === 'random' ? 'selected' : '' ?>>🎲 Willekeurig<?= $d['random_pick'] ? ' (nu: ' . $h(cmb_theme_label($d['random_pick'], $d)) . ')' : '' ?></option>
                    <optgroup label="Vaste kleurthema's">
                        <?php foreach ($presets as $k => $p): ?><option value="preset:<?= $k ?>" <?= $d['fallback'] === 'preset:' . $k ? 'selected' : '' ?>><?= $h($p[0]) ?></option><?php endforeach; ?>
                    </optgroup>
                    <?php if ($d['themes']): ?><optgroup label="Mijn thema's">
                        <?php foreach ($d['themes'] as $t): ?><option value="own:<?= $h($t['id']) ?>" <?= $d['fallback'] === 'own:' . $t['id'] ? 'selected' : '' ?>><?= $h($t['name']) ?></option><?php endforeach; ?>
                    </optgroup><?php endif; ?>
                </select></div>
            <div><button type="submit" name="act" value="random_new">🎲 Ander willekeurig thema</button></div>
        </div>
        <div class="muted">Willekeurig kiest één thema en houdt dat vast tot jij op "Ander willekeurig thema" klikt of een gepland thema het overneemt.</div>
    </div>

    <div class="card">
        <h2>Mijn thema's</h2>
        <?php if (!$d['themes']): ?><div class="muted">Nog geen eigen thema's. Maak er hieronder een, van je huidige opmaak of vanuit een vast kleurthema.</div><?php endif; ?>
        <?php foreach ($d['themes'] as $t): $p = 't_' . $t['id'] . '_'; $sc = $t['schedule'] ?? []; $type = $sc['type'] ?? 'none'; ?>
        <div class="own">
            <div class="row">
                <div><label>Naam</label><input type="text" name="<?= $p ?>name" value="<?= $h($t['name']) ?>" maxlength="40"></div>
                <div><label>Wat neemt het thema over?</label>
                    <select name="<?= $p ?>mode">
                        <option value="colors" <?= ($t['mode'] ?? '') !== 'full' ? 'selected' : '' ?>>Alleen kleuren (rest = je originele opmaak)</option>
                        <option value="full" <?= ($t['mode'] ?? '') === 'full' ? 'selected' : '' ?>>Volledige thema-opmaak (kleuren, letters, achtergronden, effecten, teksten)</option>
                    </select></div>
                <div><label><input type="checkbox" name="<?= $p ?>enabled" value="1" <?= !empty($t['enabled']) ? 'checked' : '' ?>> planning aan</label></div>
                <?php if (!empty($t['palette'])) echo $sw($t['palette']); ?>
            </div>
            <div class="row">
                <div><label>Planning</label>
                    <select name="<?= $p ?>type" onchange="cmbSched(this)">
                        <option value="none" <?= $type === 'none' ? 'selected' : '' ?>>Niet gepland</option>
                        <option value="date" <?= $type === 'date' ? 'selected' : '' ?>>Op datum (van … tot …)</option>
                        <option value="weekday" <?= $type === 'weekday' ? 'selected' : '' ?>>Op weekdagen</option>
                        <option value="daypart" <?= $type === 'daypart' ? 'selected' : '' ?>>Dagdeel (elke dag)</option>
                    </select></div>
                <div class="s-date"><label>Vanaf</label><input type="datetime-local" name="<?= $p ?>date_from" value="<?= $h($sc['date_from'] ?? '') ?>"></div>
                <div class="s-date"><label>Tot</label><input type="datetime-local" name="<?= $p ?>date_until" value="<?= $h($sc['date_until'] ?? '') ?>"></div>
                <div class="s-week"><label>Dagen</label><span>
                    <?php foreach ($dayNames as $dk => $dl): ?><label style="margin-right:6px;"><input type="checkbox" name="<?= $p ?>day_<?= $dk ?>" value="1" <?= in_array($dk, (array)($sc['days'] ?? []), true) ? 'checked' : '' ?>> <?= $dl ?></label><?php endforeach; ?>
                </span></div>
                <div class="s-time"><label>Van (tijd)</label><input type="time" name="<?= $p ?>time_from" value="<?= $h($sc['time_from'] ?? '') ?>"></div>
                <div class="s-time"><label>Tot (tijd)</label><input type="time" name="<?= $p ?>time_until" value="<?= $h($sc['time_until'] ?? '') ?>"></div>
            </div>
            <div class="row">
                <button type="submit" name="act" value="edit" onclick="this.form.id.value='<?= $h($t['id']) ?>'">✏️ Opmaak bewerken</button>
                <a class="btn" href="index.php?theme_preview=<?= urlencode('own:' . $t['id']) ?>" target="_blank">👁 Bekijk</a>
                <button type="submit" class="btn-del" name="act" value="delete" onclick="if(!confirm('Thema verwijderen?'))return false; this.form.id.value='<?= $h($t['id']) ?>'">🗑 Verwijder</button>
            </div>
        </div>
        <?php endforeach; ?>
        <input type="hidden" name="id" value="">
        <button type="submit" class="btn-main" name="act" value="save">💾 Thema's &amp; planning opslaan</button>
    </div>
    </form>

    <form method="POST" class="card">
        <input type="hidden" name="csrf" value="<?= $h($csrf) ?>">
        <h2>Nieuw eigen thema</h2>
        <div class="row">
            <div><label>Naam</label><input type="text" name="new_name" placeholder="bv. Halloween, Disco, Ontbijt" maxlength="40"></div>
            <div><label>Begin met</label>
                <select name="new_base">
                    <option value="current">Mijn huidige opmaak</option>
                    <?php foreach ($presets as $k => $p): ?><option value="preset:<?= $k ?>">Huidige opmaak in kleurthema <?= $h($p[0]) ?></option><?php endforeach; ?>
                </select></div>
            <div><button type="submit" class="btn-main" name="act" value="new">＋ Maak thema</button></div>
        </div>
        <div class="muted">Een thema is een kopie van je opmaak. Met "Opmaak bewerken" pas je het aan in het gewone beheer; je originele opmaak blijft ongemoeid.</div>
    </form>

    <div class="card">
        <h2>20 vaste kleurthema's</h2>
        <div class="grid">
            <?php foreach ($presets as $k => $p): $on = $active && $active['key'] === 'preset:' . $k; ?>
            <div class="tile<?= $on ? ' on' : '' ?>">
                <strong><?= $h($p[0]) ?></strong><?= $sw(array_slice($p, 1, 4)) ?>
                <a class="btn" href="index.php?theme_preview=preset:<?= $k ?>" target="_blank">👁 Bekijk</a>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?= $h($csrf) ?>"><input type="hidden" name="new_name" value="<?= $h($p[0]) ?>"><input type="hidden" name="new_base" value="preset:<?= $k ?>">
                    <button type="submit" name="act" value="new">＋ Eigen thema</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<script>
function cmbSched(sel) {
    var box = sel.closest('.own'), t = sel.value;
    box.querySelectorAll('.s-date').forEach(function (e) { e.style.display = t === 'date' ? '' : 'none'; });
    box.querySelectorAll('.s-week').forEach(function (e) { e.style.display = t === 'weekday' ? '' : 'none'; });
    box.querySelectorAll('.s-time').forEach(function (e) { e.style.display = (t === 'weekday' || t === 'daypart') ? '' : 'none'; });
}
document.querySelectorAll('select[name$="_type"]').forEach(cmbSched);
</script>
</body>
</html>
