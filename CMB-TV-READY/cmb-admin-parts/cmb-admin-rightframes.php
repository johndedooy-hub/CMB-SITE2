<?php /** CMB Beheer > Frames: rechterkant (speler volledig of compact + eigen tekstframe). Geladen door cmb-admin.php. */ if (!defined('CMB_PART')) { http_response_code(403); exit; }
$rfCompact = (($settings['layout_mode'] ?? 'full') === 'compact');
?>
<div class="card">
    <h2>🪟 Rechterkant: speler Volledig of Compact</h2>
    <div class="cmb-helpbox">
        <strong>Volledig:</strong> de speler zoals hij was. <strong>Compact:</strong> een lage speler (play, cover, artiest, nummer en je regel) met daaronder of daarboven een eigen tekstframe met een plaatje erachter.
        Handig als de speler naast een camera of andere beelden in OBS staat.
    </div>
    <div class="form-row">
        <div class="field"><label>Weergave</label>
            <select name="layout_mode">
                <option value="full" <?= !$rfCompact ? 'selected' : '' ?>>Volledig (zoals nu)</option>
                <option value="compact" <?= $rfCompact ? 'selected' : '' ?>>Compact (speler + eigen tekstframe)</option>
            </select>
        </div>
        <div class="field" style="max-width:190px;"><label>Hoogte compacte speler (px, 130-320)</label><input type="number" name="compact_height" min="130" max="320" value="<?= max(130, min(320, intval($settings['compact_height'] ?? 200))) ?>" style="text-align:center;"></div>
        <label class="cmb-check" style="align-self:flex-end; margin-bottom:12px;"><input type="checkbox" name="h_swap" value="1" <?= !empty($settings['h_swap']) ? 'checked' : '' ?>> ↔ Linker en rechter kant wisselen (speler links)</label>
        <label class="cmb-check" style="align-self:flex-end; margin-bottom:12px;"><input type="checkbox" name="right_swap" value="1" <?= !empty($settings['right_swap']) ? 'checked' : '' ?>> ↑↓ Eigen frame bovenaan (wissel de plek met de speler)</label>
    </div>
</div>

<?php $rfOv = (($settings['slide_view'] ?? 'slideshow') === 'overzicht'); ?>
<div class="card">
    <h2>🧩 Linkerkant: slideshow of tegeloverzicht</h2>
    <div class="cmb-helpbox">
        <strong>Slideshow:</strong> de slides wisselen één voor één, zoals nu.
        <strong>Tegeloverzicht:</strong> alle gewone slides staan als tegels naast elkaar (zoals de info-pagina); een klik op een tegel opent die slide. De ticker onderin blijft.
        Slides die op een vast moment komen, tijdelijk zijn, een noodbericht of bij een nummer starten, staan zolang ze actief zijn in <strong>frame 3</strong> (zet daarvoor Weergave op Compact; anders komen ze over het linker frame heen).
        <br><strong>TV-adres:</strong> <code>tv</code> naast je speler (bijvoorbeeld <code>jouwsite.nl/cmb/tv</code>) toont altijd de slideshow, ook als de gewone speler op tegels staat. Gebruik dat voor tv en OBS.
    </div>
    <div class="form-row">
        <div class="field"><label>Weergave links</label>
            <select name="slide_view">
                <option value="slideshow" <?= !$rfOv ? 'selected' : '' ?>>Slideshow (zoals nu)</option>
                <option value="overzicht" <?= $rfOv ? 'selected' : '' ?>>Tegeloverzicht</option>
            </select>
        </div>
        <div class="field" style="max-width:170px;"><label>Kolommen (1-4)</label><input type="number" name="ov_cols" min="1" max="4" value="<?= max(1, min(4, intval($settings['ov_cols'] ?? 3))) ?>"></div>
        <div class="field" style="max-width:190px;"><label>Tegelgrootte</label>
            <select name="ov_size">
                <?php $rfSz = $settings['ov_size'] ?? 'm'; foreach (['s' => 'Klein', 'm' => 'Middel', 'l' => 'Groot'] as $szk => $szl): ?>
                <option value="<?= $szk ?>" <?= $rfSz === $szk ? 'selected' : '' ?>><?= $szl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php $sOne = !empty($settings['slide_one_on']); $sOneM = (string)($settings['slide_one_media'] ?? ''); ?>
    <div style="margin:10px 0; padding:10px 12px; border:1px solid rgba(255,255,255,.12); border-radius:10px;">
        <label class="cmb-check"><input type="checkbox" name="slide_one_on" value="1" <?= $sOne ? 'checked' : '' ?>> <strong>Eén vaste achtergrond voor alle slides</strong> (overrulet de achtergrond van elke slide, behalve het noodbericht)</label>
        <div class="form-row">
            <div class="field"><label>Plaatje of video uploaden</label><input type="file" name="upload_slide_one" accept="image/*,video/mp4,video/webm"></div>
            <div class="field"><label>of link (https://)</label><input type="text" name="slide_one_media" value="<?= htmlspecialchars($sOneM) ?>"></div>
            <div class="field" style="max-width:90px;"><label>Blur (px)</label><input type="number" name="slide_one_blur" min="0" max="25" value="<?= intval($settings['slide_one_blur'] ?? 0) ?>" style="text-align:center;"></div>
            <div class="field" style="max-width:90px;"><label>Donker (%)</label><input type="number" name="slide_one_darken" min="0" max="95" value="<?= intval($settings['slide_one_darken'] ?? 35) ?>" style="text-align:center;"></div>
        </div>
        <?php if ($sOneM !== ''): ?><label style="font-size:12px;"><input type="checkbox" name="slide_one_del" value="1"> vaste achtergrond verwijderen</label><?php endif; ?>
    </div>
    <div class="form-row">
        <div class="field"><label>Achtergrond van het overzicht</label>
            <select name="ov_bg">
                <?php $rfBg = $settings['ov_bg'] ?? 'tile'; foreach (['tile' => 'Alleen per tegel het plaatje van de slide (zoals eerst)', 'both' => 'Per tegel het plaatje én een achtergrond achter alles', 'frame' => 'Alleen een achtergrond achter alles (rustige tegels)', 'none' => 'Geen plaatjes, rustige tegels'] as $bk => $bl): ?>
                <option value="<?= $bk ?>" <?= $rfBg === $bk ? 'selected' : '' ?>><?= $bl ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field" style="max-width:170px;"><label>Donker % (0-95)</label><input type="number" name="ov_dark" min="0" max="95" value="<?= max(0, min(95, intval($settings['ov_dark'] ?? 45))) ?>"></div>
        <div class="field" style="max-width:170px;"><label>Blur px (0-25)</label><input type="number" name="ov_blur" min="0" max="25" value="<?= max(0, min(25, intval($settings['ov_blur'] ?? 6))) ?>"></div>
    </div>
    <div class="form-row">
        <div class="field" style="max-width:260px;"><label>Kleur van kopjes (NIEUWS, WEER, REGIO, OVERIG), onderregel, rand, slide-puntjes en PL-knop</label>
            <input type="color" name="ov_accent" value="<?= preg_match('/^#[0-9a-fA-F]{6}$/', (string)($settings['ov_accent'] ?? '')) ? $settings['ov_accent'] : '#63f6ff' ?>" style="height:42px; width:100%;">
        </div>
        <div class="cmb-helpbox" style="flex:1; align-self:flex-end; margin:0;">Geldt voor het tegeloverzicht, het info-venster in de speler, de losse info-pagina, de slide-puntjes en de uitklapknop van de PL-balk. Standaard is het fel cyaan (#63f6ff). Slides en de rest veranderen niet.</div>
    </div>
    <div class="cmb-helpbox" style="margin-top:8px;">Donker en blur gelden voor de achtergrond achter alles (de plaatjes van de slides wisselen rustig).</div>
    <div class="cmb-helpbox" style="margin-top:8px;">De onderregel op een tegel vul je in bij Studio, per vrije slide (veld "Onderregel op de tegel").</div>
</div>

<?php
$rfItems = (isset($settings['own_items']) && is_array($settings['own_items'])) ? array_values($settings['own_items']) : [[
    'text' => $settings['own_text'] ?? '', 'style' => $settings['style_own_text'] ?? [], 'media' => $settings['own_media'] ?? '', 'blur' => $settings['own_blur'] ?? 0, 'darken' => $settings['own_darken'] ?? 35,
]];
?>
<div class="card">
    <h2>✍️ Eigen tekstframe (bij Compact)</h2>
    <div class="cmb-helpbox">Tot 6 plekken, elk met een eigen tekst, opmaak en achtergrond. Ze wisselen rustig na elkaar. Een lege plek wordt overgeslagen. Met maar één plek blijft die gewoon staan.</div>
    <div class="form-row"><div class="field" style="max-width:220px;"><label>Seconden per plek (2-600)</label><input type="number" name="own_sec" min="2" max="600" value="<?= max(2, min(600, intval($settings['own_sec'] ?? 10))) ?>" style="text-align:center;"></div></div>
    <?php for ($i = 0; $i < 6; $i++):
        $it = (isset($rfItems[$i]) && is_array($rfItems[$i])) ? $rfItems[$i] : [];
        $txt = (string)($it['text'] ?? ''); $med = (string)($it['media'] ?? '');
        $first = trim(mb_substr(preg_replace('/\s+/', ' ', $txt), 0, 32));
        $isOn = ($txt !== '' || $med !== '');
    ?>
    <details style="margin:8px 0; border:1px solid rgba(255,255,255,.12); border-radius:10px; padding:8px 12px;" <?= $i === 0 ? 'open' : '' ?>>
        <summary style="cursor:pointer; font-weight:800;">Plek <?= $i + 1 ?> <span style="font-weight:400; color:#a8aac8;"><?= $isOn ? '· ' . htmlspecialchars($first !== '' ? $first : 'alleen achtergrond') : '· leeg' ?></span></summary>
        <?= renderStyleSuite('own_i' . $i, 'Tekst', $txt, array_merge(['font' => 'Montserrat', 'size' => '22px', 'color' => '#ffffff'], is_array($it['style'] ?? null) ? $it['style'] : [])) ?>
        <div class="form-row">
            <div class="field"><label>Plaatje of video erachter uploaden</label><input type="file" name="upload_own_i<?= $i ?>" accept="image/*,video/mp4,video/webm"></div>
            <div class="field"><label>of link (https://)</label><input type="text" name="own_i<?= $i ?>_media" value="<?= htmlspecialchars($med) ?>"></div>
            <div class="field" style="max-width:90px;"><label>Blur (px)</label><input type="number" name="own_i<?= $i ?>_blur" min="0" max="25" value="<?= intval($it['blur'] ?? 0) ?>" style="text-align:center;"></div>
            <div class="field" style="max-width:90px;"><label>Donker (%)</label><input type="number" name="own_i<?= $i ?>_darken" min="0" max="95" value="<?= intval($it['darken'] ?? 35) ?>" style="text-align:center;"></div>
        </div>
        <?php if ($med !== ''): ?><label style="font-size:12px;"><input type="checkbox" name="own_i<?= $i ?>_del" value="1"> achtergrond van deze plek verwijderen</label><?php endif; ?>
    </details>
    <?php endfor; ?>
</div>

