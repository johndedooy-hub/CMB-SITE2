<?php
/**
 * CHAZZO MUSIC BOX (CMB) - ADMIN TAB: TOPMENU & KNOPPEN
 * Bestandsnaam: cmb-admin-topmenu.php
 * Het topmenu is een eigen blok bovenaan het midden van de speler (tot 6 knoppen + het "oogje"
 * waarmee de kijker het beeld kan vergroten of verkleinen). De knoppen kunnen later ook
 * als menu voor andere pagina's gebruikt worden: vul een link in en de knop werkt.
 */
$tmEnabled = !isset($settings['topmenu_enabled']) || !empty($settings['topmenu_enabled']);
$tmEye = !isset($settings['topmenu_eye']) || !empty($settings['topmenu_eye']);
$tmBg = $settings['topmenu_bg'] ?? '#0c0e1a';
$tmBgA = isset($settings['topmenu_bar_alpha']) ? intval($settings['topmenu_bar_alpha']) : 25;
$tmLineOnA = !isset($settings['topmenu_line_on']) || !empty($settings['topmenu_line_on']);
$tmLineA = $settings['topmenu_line'] ?? '#63f6ff'; if (!preg_match('/^#[0-9a-fA-F]{6}$/', (string)$tmLineA)) $tmLineA = '#63f6ff';
$defLinks = ['1' => 'CMB 1', '2' => 'CMB 2', '3' => 'CMB 3', '4' => 'CMB 4', '5' => '', '6' => ''];
?>
<div class="cmb-helpbox">
    <strong>Zo werkt het:</strong> het topmenu staat bovenaan in het midden van de speler. Zet knoppen aan, geef ze een tekst en
    (als je wilt) een link. Zonder link doet de knop niets. Het <strong>oogje</strong> rechts van het menu laat de kijker het beeld
    groter of kleiner maken, zodat alles op zijn scherm past.<br>
    <strong>Info-overzicht:</strong> vul bij een knop als link <code>#info</code> (of <code>info.php</code>) in. Die knop opent dan een glaslaag met alle rollende slides (de muziek loopt door).
</div>

<div class="card">
    <h2>🔝 Topmenu-blok</h2>
    <div class="form-row" style="align-items:flex-end;">
        <label class="cmb-check"><input type="checkbox" name="topmenu_enabled" value="1" <?= $tmEnabled ? 'checked' : '' ?>> Topmenu tonen</label>
        <label class="cmb-check"><input type="checkbox" name="topmenu_eye" value="1" <?= $tmEye ? 'checked' : '' ?>> 👁️ Oogje voor de kijker tonen (beeld vergroten/verkleinen)</label>
        <div class="field" style="max-width:110px;"><label>Achtergrond balk</label><input type="color" name="topmenu_bg" value="<?= htmlspecialchars($tmBg) ?>"></div>
        <div class="field" style="max-width:150px;"><label>Balk zichtbaar (%, 0 = geen)</label><input type="number" name="topmenu_bar_alpha" value="<?= $tmBgA ?>" min="0" max="100"></div>
        <label class="cmb-check"><input type="checkbox" name="topmenu_line_on" value="1" <?= $tmLineOnA ? 'checked' : '' ?>> Dunne lijn onder de balk</label>
        <div class="field" style="max-width:110px;"><label>Kleur lijn en |</label><input type="color" name="topmenu_line" value="<?= htmlspecialchars($tmLineA) ?>"></div>
    </div>
</div>

<div class="card">
    <h2>🔗 Knoppen in het topmenu (max. 6)</h2>
    <?php for ($li = 1; $li <= 6; $li++):
        $lbl = $settings["top_menu_link_{$li}"] ?? $defLinks[(string)$li];
        $on = array_key_exists("top_menu_on_{$li}", $settings) ? !empty($settings["top_menu_on_{$li}"]) : (trim((string)$lbl) !== '');
        $url = $settings["top_menu_url_{$li}"] ?? '';
        $newTab = !empty($settings["top_menu_new_{$li}"]);
    ?>
        <div class="cmb-top-card">
            <div class="cmb-top-card-head">
                <strong>Knop <?= $li ?></strong>
                <label class="cmb-check" style="margin:0;"><input type="checkbox" name="top_menu_on_<?= $li ?>" value="1" <?= $on ? 'checked' : '' ?>> Tonen</label>
            </div>
            <div class="form-row">
                <div class="field" style="flex:2;"><label>Link (https://… of een pagina in je site)</label>
                    <input type="text" name="top_menu_url_<?= $li ?>" value="<?= htmlspecialchars($url) ?>" placeholder="https://voorbeeld.nl of leeg laten">
                </div>
                <label class="cmb-check" style="align-self:flex-end; margin-bottom:12px;"><input type="checkbox" name="top_menu_new_<?= $li ?>" value="1" <?= $newTab ? 'checked' : '' ?>> Openen in nieuw tabblad</label>
            </div>
            <?= renderStyleSuite("top_link_{$li}", "Tekst van knop {$li}", $lbl, array_merge(['font' => 'Inter', 'size' => '11px', 'color' => '#63f6ff'], $settings["style_top_menu_link_{$li}"] ?? []), false) ?>
        </div>
    <?php endfor; ?>
</div>

<div class="card">
    <h2>🔘 Randjes van knoppen in de speler</h2>
    <?= renderBorderSuite('btn_play', 'Play / Pause knop', $settings['border_btn_play'] ?? []) ?>
    <?= renderBorderSuite('btn_vol', 'Volume knop', $settings['border_btn_vol'] ?? []) ?>
    <?= renderBorderSuite('btn_logo', 'Logo-container', $settings['border_btn_logo'] ?? []) ?>
    <?= renderBorderSuite('btn_pl', 'Platform-balk (iconen)', $settings['border_btn_pl'] ?? []) ?>
</div>
