<?php
/** Dedicated view: admin section 5, cover effects and player controls. */
?>
    <div class="card">
        <h2>Alle <?= count($effectsList) ?> Cover Effecten & Speler Vorm</h2>
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label>Cover Effect Achter Artwork (Kies uit alle <?= count($effectsList) ?>)</label>
                <select name="art_effect">
                    <?php foreach ($effectsList as $k => $label): ?>
                        <option value="<?= $k ?>" <?= ($settings['art_effect'] ?? 'aurora') === $k ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field" style="max-width:190px;">
                <label>Automatisch wisselen (min.)</label>
                <input type="number" name="art_rotate_min" min="0" max="240" value="<?= intval($settings['art_rotate_min'] ?? 0) ?>" title="0 = uit. Anders wisselt het cover effect elke zoveel minuten willekeurig.">
            </div>
            <div class="field" style="max-width:260px; align-self:flex-end;">
                <label class="cmb-check" style="margin:0 0 10px;"><input type="checkbox" name="art_overflow" value="1" <?= !empty($settings['art_overflow']) ? 'checked' : '' ?>> Effecten mogen buiten het speler-frame uitlopen</label>
            </div>
            <div class="field" style="max-width:230px;">
                <label>Draairichting draaiende effecten</label>
                <select name="art_spin">
                    <option value="" <?= ($settings['art_spin'] ?? '') === '' ? 'selected' : '' ?>>Zoals ontworpen (mix)</option>
                    <option value="cw" <?= ($settings['art_spin'] ?? '') === 'cw' ? 'selected' : '' ?>>Alles met de klok mee ↻</option>
                    <option value="ccw" <?= ($settings['art_spin'] ?? '') === 'ccw' ? 'selected' : '' ?>>Alles tegen de klok in ↺</option>
                </select>
            </div>
            <div class="field" style="max-width:150px; align-self:flex-end;">
                <button type="button" style="width:100%; height:38px; background:#2a2e52; color:#fff; border:none; border-radius:8px; font-weight:800; cursor:pointer;" onclick="cmbRandomEffect()">🎲 Willekeurig</button>
            </div>
            <div class="field">
                <label>Positie Play & Volume</label>
                <select name="corner_btn_pos">
                    <option value="top" <?= ($settings['corner_btn_pos'] ?? 'top') === 'top' ? 'selected' : '' ?>>Bovenhoeken</option>
                    <option value="bottom" <?= ($settings['corner_btn_pos'] ?? '') === 'bottom' ? 'selected' : '' ?>>Onderhoeken</option>
                </select>
            </div>
            <div class="field">
                <label>Volumebalk Icoontjes</label>
                <select name="vol_shape">
                    <option value="blocks" <?= ($settings['vol_shape'] ?? 'blocks') === 'blocks' ? 'selected' : '' ?>>■ Blokjes</option>
                    <option value="dots" <?= ($settings['vol_shape'] ?? '') === 'dots' ? 'selected' : '' ?>>● Rondjes</option>
                    <option value="stars" <?= ($settings['vol_shape'] ?? '') === 'stars' ? 'selected' : '' ?>>★ Sterretjes</option>
                    <option value="hearts" <?= ($settings['vol_shape'] ?? '') === 'hearts' ? 'selected' : '' ?>>♥ Hartjes</option>
                </select>
            </div>
        </div>
        <div class="form-row" id="artBgRow" style="margin-top:6px; align-items:center;">
            <div id="artBgPrev" style="width:150px; height:84px; border-radius:10px; border:1px dashed rgba(255,255,255,.2); background:#0a0c1c center/cover no-repeat; display:flex; align-items:center; justify-content:center; color:#8f92b5; font-size:11px; text-align:center;">Geen nieuwe achtergrond</div>
            <div class="field" style="flex:1;">
                <input type="hidden" name="art_bg_url" id="art_bg_url" value="">
                <label class="cmb-check" style="margin:0 0 4px;"><input type="checkbox" name="art_bg_keep" value="1" id="art_bg_keep"> Deze achtergrond bewaren als Spelerframe-achtergrond (alleen achter de cover)</label>
                <small style="color:#8f92b5;">Komt na 🎲 Verras me. Zonder vinkje is het alleen een voorbeeld en blijft je huidige achtergrond staan.</small>
            </div>
            <div class="field" style="max-width:150px;"><button type="button" id="artBgClear" style="width:100%; height:34px; background:#2a2e52; color:#fff; border:none; border-radius:8px; font-weight:700; cursor:pointer;">✕ Wissen</button></div>
        </div>
    </div>

<script>
window.CMB_COVER_BGS = <?php
    $cmbCv = [];
    foreach ((glob(CMB_ROOT . '/cmb-assets/background-covers/*.svg') ?: []) as $cf) $cmbCv[] = 'cmb-assets/background-covers/' . basename($cf);
    echo json_encode($cmbCv, JSON_UNESCAPED_SLASHES);
?>;
function cmbSetArtBg(url) {
    var h = document.getElementById('art_bg_url'), pv = document.getElementById('artBgPrev');
    if (!h || !pv) return;
    h.value = url || '';
    var isVid = /\.(mp4|webm)$/i.test(url || '');
    pv.style.backgroundImage = (url && !isVid) ? 'url("' + url + '")' : 'none';
    pv.textContent = url ? (isVid ? 'video' : '') : 'Geen nieuwe achtergrond';
    var k = document.getElementById('art_bg_keep'); if (k && !url) k.checked = false;
}
document.addEventListener('click', function (e) { if (e.target && e.target.id === 'artBgClear') cmbSetArtBg(''); });
function cmbRandomEffect() {
    var sel = document.querySelector('select[name="art_effect"]');
    if (!sel || !sel.options.length) return;
    sel.selectedIndex = Math.floor(Math.random() * sel.options.length);
    sel.dispatchEvent(new Event('change', { bubbles: true }));
}
</script>
