<?php
/** Admin tab "Frames": linker en rechter frame, per frame vier stappen. */
?>
<div class="cmb-helpbox">
    <strong>Zo werkt het:</strong> kies hieronder het <strong>linker</strong> of <strong>rechter</strong> frame en loop de stappen langs.
    Rechts ziet u steeds een voorbeeld. Daarna één keer op <strong>Opslaan</strong> (of Ctrl+S) drukken.
    Met <em>Kopieer naar het andere frame</em> krijgen beide frames dezelfde look.
</div>
<div class="cmb-switch">
    <button type="button" data-frame="fl_left" onclick="cmbShowFrame('fl_left')">◀ Linker frame (slides)</button>
    <button type="button" data-frame="fl_right" onclick="cmbShowFrame('fl_right')">Rechter frame (speler) ▶</button>
    <?php if (($settings['layout_mode'] ?? 'full') === 'compact'): ?><button type="button" data-frame="fl_own" onclick="cmbShowFrame('fl_own')">Eigen tekstframe ▼</button><?php endif; ?>
</div>
<?= renderFrameLightingSuite('fl_left', 'Linker frame: slides', $settings['lighting_left_frame'] ?? []) ?>
<?= renderFrameLightingSuite('fl_right', 'Rechter frame: speler', $settings['lighting_right_frame'] ?? []) ?>
<?php if (($settings['layout_mode'] ?? 'full') === 'compact'): ?>
<?= renderFrameLightingSuite('fl_own', 'Eigen tekstframe', $settings['lighting_own_frame'] ?? ($settings['lighting_right_frame'] ?? [])) ?>
<?php endif; ?>
