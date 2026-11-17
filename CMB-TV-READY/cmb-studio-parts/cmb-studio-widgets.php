<?php
/**
 * CHAZZO MUSIC BOX (CMB) - STUDIO: WIDGETS & RSS
 * Bestandsnaam: cmb-studio-widgets.php (wordt ingeladen door cmb-studio.php, tab "widgets")
 *
 * Een widget is een slide met live inhoud. Drie soorten:
 *   - RSS-feed:     plak de link van een nieuwssite (bv. NOS, Omroep Brabant); de laatste koppen worden getoond.
 *   - Website/embed: plak een https-link van een widget (weer, koersen, klok); wordt in een kader getoond.
 *   - Eigen code:   plak HTML/JS van een widget-aanbieder; draait afgeschermd in een sandbox.
 * Opslaan gebeurt in cmb-studio.php. Widgets hebben hun eigen plek in de slide-rotatie (volgorde + seconden).
 */
const CMB_WIDGET_MAX = 6;
$wList = (isset($slides['widgets']) && is_array($slides['widgets'])) ? array_values($slides['widgets']) : [];
$rssExamples = [
    'NOS Nieuws' => 'https://feeds.nos.nl/nosnieuwsalgemeen',
    'NU.nl Algemeen' => 'https://www.nu.nl/rss/Algemeen',
    'Omroep Brabant' => 'https://www.omroepbrabant.nl/rss/nieuws',
];
?>
<div class="card" style="border:1px solid #00e5ff;">
    <h2>🧩 Widgets & RSS (max. <?= CMB_WIDGET_MAX ?>)</h2>
    <div class="cmb-helpbox">
        <strong>Zo werkt het:</strong> kies een soort, vul een link of code in en zet de widget aan. Hij komt dan tussen de andere slides in de rotatie.<br>
        <strong>RSS-voorbeelden</strong> (klik om in te vullen in het actieve veld):
        <?php foreach ($rssExamples as $n => $u): ?>
            <a href="#" onclick="cmbWidgetExample('<?= htmlspecialchars($u) ?>');return false;" style="color:#63f6ff;"><?= htmlspecialchars($n) ?></a>
        <?php endforeach; ?><br>
        <strong>Veilig:</strong> eigen code en embeds draaien afgeschermd en kunnen de speler of het beheer niet bereiken. RSS-links werken alleen naar openbare websites.
    </div>

    <?php for ($i = 1; $i <= CMB_WIDGET_MAX; $i++):
        $w = array_merge([
            'active' => false, 'kind' => 'rss', 'title' => '', 'sub' => '', 'url' => '', 'html' => '',
            'max' => 4, 'show_desc' => true, 'sec' => 12, 'order' => 20 + $i, 'media_url' => '', 'blur' => 0, 'darken' => 35,
            'style_title' => [], 'style_body' => [],
        ], (isset($wList[$i - 1]) && is_array($wList[$i - 1])) ? $wList[$i - 1] : []);
        $kindLabel = ['rss' => 'RSS', 'embed' => 'Embed', 'html' => 'Eigen code'][$w['kind']] ?? 'RSS';
    ?>
    <details class="slide-block" <?= $w['active'] ? 'open' : '' ?>>
        <summary style="cursor:pointer; font-weight:900; color:#ffd166;">
            Widget <?= $i ?> — <?= htmlspecialchars($w['title'] !== '' ? $w['title'] : 'leeg') ?>
            <span style="font-size:11px; color:#a8aac8;">(<?= $kindLabel ?><?= $w['active'] ? ', aan' : ', uit' ?>)</span>
        </summary>
        <div class="form-row" style="margin-top:12px;">
            <div class="field" style="max-width:110px;"><label>Status</label>
                <label style="text-transform:none;"><input type="checkbox" name="wg_<?= $i ?>_active" value="1" <?= $w['active'] ? 'checked' : '' ?>> Aan</label></div>
            <div class="field" style="max-width:170px;"><label>Soort</label>
                <select name="wg_<?= $i ?>_kind" onchange="cmbWidgetKind(<?= $i ?>, this.value)">
                    <option value="rss" <?= $w['kind']==='rss'?'selected':'' ?>>📰 RSS-feed</option>
                    <option value="embed" <?= $w['kind']==='embed'?'selected':'' ?>>🌐 Website / embed</option>
                    <option value="html" <?= $w['kind']==='html'?'selected':'' ?>>🧪 Eigen code</option>
                </select></div>
            <div class="field"><label>Subtitel</label><input type="text" name="wg_<?= $i ?>_sub" value="<?= htmlspecialchars($w['sub']) ?>"></div>
            <div class="field" style="max-width:80px;"><label>Seconden</label><input type="number" name="wg_<?= $i ?>_sec" value="<?= intval($w['sec']) ?>" min="3" max="120"></div>
            <div class="field" style="max-width:80px;"><label>Volgorde</label><input type="number" name="wg_<?= $i ?>_order" value="<?= intval($w['order']) ?>"></div>
        </div>
        <div id="wg_<?= $i ?>_pane_url" style="<?= $w['kind']==='html' ? 'display:none;' : '' ?>">
            <div class="form-row">
                <div class="field" style="flex:3;"><label>Link (RSS-feed of https-adres)</label>
                    <input type="text" class="wg-url" name="wg_<?= $i ?>_url" value="<?= htmlspecialchars($w['url']) ?>" placeholder="https://…" onfocus="window.cmbWgLast=this"></div>
                <div class="field" style="max-width:90px;"><label>Aantal items</label><input type="number" name="wg_<?= $i ?>_max" value="<?= intval($w['max']) ?>" min="1" max="8"></div>
                <div class="field" style="max-width:150px;"><label>Samenvatting</label>
                    <label style="text-transform:none;"><input type="checkbox" name="wg_<?= $i ?>_show_desc" value="1" <?= $w['show_desc'] ? 'checked' : '' ?>> Tonen</label></div>
            </div>
        </div>
        <div id="wg_<?= $i ?>_pane_html" style="<?= $w['kind']==='html' ? '' : 'display:none;' ?>">
            <div class="form-row"><div class="field"><label>Widget-code (HTML / script van de aanbieder)</label>
                <textarea name="wg_<?= $i ?>_html" rows="5" style="font-family:monospace;" placeholder="<div>…</div>"><?= htmlspecialchars($w['html']) ?></textarea></div></div>
        </div>
        <div class="form-row" style="align-items:flex-end;">
            <div class="field"><label>Achtergrond media (foto of video, van pc of Android)</label><input type="file" name="upload_wg_<?= $i ?>" accept="image/*,video/mp4,video/webm"></div>
            <div class="field"><label>Of pad / link</label><input type="text" id="wg_<?= $i ?>_media_url" name="wg_<?= $i ?>_media_url" value="<?= htmlspecialchars($w['media_url']) ?>" placeholder="cmb-uploads/..."></div>
            <div class="field" style="max-width:80px;"><label>Blur (px)</label><input type="number" name="wg_<?= $i ?>_blur" value="<?= intval($w['blur']) ?>" min="0" max="25"></div>
            <div class="field" style="max-width:90px;"><label>Donker (%)</label><input type="number" name="wg_<?= $i ?>_darken" value="<?= intval($w['darken']) ?>" min="0" max="95"></div>
        </div>
        <?php if (function_exists('renderStyleSuite')) {
            echo renderStyleSuite("wg_{$i}_title", 'Titel (groot) met stijl', $w['title'], $w['style_title'], false);
        } ?>
    </details>
    <?php endfor; ?>
</div>
<script>
function cmbWidgetKind(i, kind) {
    document.getElementById('wg_' + i + '_pane_url').style.display = kind === 'html' ? 'none' : '';
    document.getElementById('wg_' + i + '_pane_html').style.display = kind === 'html' ? '' : 'none';
}
function cmbWidgetExample(u) {
    var el = window.cmbWgLast || document.querySelector('.wg-url');
    if (el) { el.value = u; el.focus(); }
}
</script>
