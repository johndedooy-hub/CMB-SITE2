<?php
/**
 * CHAZZO MUSIC BOX (CMB) - STUDIO: CLIPSLIDES (onderdeel van het slides-menu)
 * Bestandsnaam: cmb-studio-clipslides.php
 *
 * Een clipslide is een videoclip die het scherm overneemt zodra een bepaald nummer speelt.
 *   - Koppeling: typ "Artiest - Titel" zoals hij in de stream staat (hoofdletters en leestekens maken niet uit).
 *   - Bron: kies een bestand (pc of Android, max. 15 MB) of plak een link (mp4/webm, YouTube, Vimeo of webpagina).
 *   - De clip speelt één keer (geen herhaling) en verdwijnt zodra hij afgelopen is of het nummer wisselt.
 *   - De muziek komt gewoon uit de stream; titel en artiest kunnen over de clip getoond worden.
 * Opslaan gebeurt in cmb-studio.php (tab slides). Maximaal 15 clipslides.
 */
const CMB_CLIPSLIDE_MAX = 15;
const CMB_CLIPSLIDE_MAX_MB = 15;

$csList = (isset($slides['clipslides']) && is_array($slides['clipslides'])) ? array_values($slides['clipslides']) : [];
// Overnemen van de eerdere "clips"-lijst, zolang er nog geen clipslides zijn opgeslagen
if (!$csList && isset($slides['clips']) && is_array($slides['clips'])) {
    foreach (array_slice(array_values($slides['clips']), 0, CMB_CLIPSLIDE_MAX) as $oldClip) {
        if (!is_array($oldClip)) continue;
        $csList[] = [
            'active' => !empty($oldClip['active']), 'title' => $oldClip['title'] ?? '', 'match' => $oldClip['track_match'] ?? '',
            'url' => $oldClip['url'] ?? '', 'delay' => 0, 'show_title' => true, 'sound' => !empty($oldClip['sound']),
            'fit' => 'contain', 'scope' => 'frame',
        ];
    }
}

$csUsed = 0;
foreach ($csList as $c) { if (is_array($c) && trim((string)($c['url'] ?? '')) !== '') $csUsed++; }

function cmb_cs_thumb($url) {
    $info = cmb_clip_info($url);
    if ($info['kind'] === 'youtube') return '<img src="https://img.youtube.com/vi/' . htmlspecialchars($info['id']) . '/mqdefault.jpg" alt="">';
    if ($info['kind'] === 'file') return '<video src="' . htmlspecialchars($url) . '#t=0.5" preload="metadata" muted playsinline></video>';
    if ($info['kind'] === 'vimeo') return '<span class="empty-txt">Vimeo</span>';
    if ($info['kind'] === 'page') return '<span class="empty-txt">Webpagina</span>';
    return '<span class="empty-txt">Leeg</span>';
}
?>
<div class="card" id="sec_slide_clips" style="border:1px solid #b388ff;">
    <h2>🎥 Clipslides (max. <?= CMB_CLIPSLIDE_MAX ?>) <span style="font-size:12px; color:#a8aac8; font-weight:600;">— <?= $csUsed ?> van <?= CMB_CLIPSLIDE_MAX ?> in gebruik</span></h2>
    <div class="cmb-helpbox">
        <strong>Zo werkt het:</strong> maak een lijst met nummers. Staat er bij een nummer een clip, dan neemt die clip het scherm over zodra dat nummer speelt
        en verdwijnt hij weer als de clip is afgelopen. De clip speelt één keer, zonder herhaling. De muziek blijft uit de stream komen.<br>
        <strong>Voorbeeld:</strong> koppeling <code>Blof - De Kust</code> + bestand <code>Blof - De Kust.mp4</code>.<br>
        <strong>Let op:</strong> houd een videobestand onder <strong><?= CMB_CLIPSLIDE_MAX_MB ?> MB</strong> (zo blijft de speler soepel). Een link (YouTube, Vimeo, directe mp4) telt niet mee.
    </div>

    <?php for ($i = 1; $i <= CMB_CLIPSLIDE_MAX; $i++):
        $c = array_merge([
            'active' => true, 'title' => '', 'match' => '', 'url' => '', 'delay' => 0,
            'show_title' => true, 'sound' => false, 'fit' => 'contain', 'scope' => 'frame',
        ], (isset($csList[$i - 1]) && is_array($csList[$i - 1])) ? $csList[$i - 1] : []);
        $has = trim((string)$c['url']) !== '';
        $info = cmb_clip_info($c['url']);
        $kindLabel = ['file' => 'Bestand', 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'page' => 'Webpagina', 'invalid' => $has ? 'Ongeldige link' : 'Nog leeg'][$info['kind']];
        $name = trim((string)$c['match']) !== '' ? $c['match'] : 'Clip ' . $i;
    ?>
    <details class="cmb-cs-row" <?= $has ? '' : '' ?>>
        <summary class="cmb-cs-head" style="cursor:pointer;">
            <span class="cmb-cs-num"><?= $i ?></span>
            <div class="media-thumb-preview"><?= cmb_cs_thumb($c['url']) ?></div>
            <div style="flex:1; min-width:140px;">
                <div style="font-weight:900; color:#ffd166; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($name) ?></div>
                <div style="font-size:11px; color:#a8aac8;"><?= $kindLabel ?><?= $has && empty($c['active']) ? ' · uitgeschakeld' : '' ?></div>
            </div>
        </summary>

        <div style="margin-top:12px;">
            <div class="form-row" style="align-items:flex-end;">
                <div class="field" style="flex:2;">
                    <label>Bij welk nummer? (Artiest - Titel)</label>
                    <input type="text" id="cs_<?= $i ?>_match" name="cs_<?= $i ?>_match" value="<?= htmlspecialchars($c['match']) ?>" placeholder="Blof - De Kust">
                    <small class="hint">Zoals het nummer in de stream verschijnt. Gebruik " - " tussen artiest en titel.</small>
                </div>
                <label class="cmb-check" style="margin-bottom:22px;"><input type="checkbox" name="cs_<?= $i ?>_active" value="1" <?= !empty($c['active']) ? 'checked' : '' ?>> Actief</label>
            </div>

            <div class="form-row">
                <div class="field" style="flex:2;">
                    <label>Clip kiezen van je pc of Android (mp4 / webm, max. <?= CMB_CLIPSLIDE_MAX_MB ?> MB)</label>
                    <input type="file" name="upload_cs_<?= $i ?>" accept="video/mp4,video/webm,.mp4,.webm" data-maxmb="<?= CMB_CLIPSLIDE_MAX_MB ?>">
                </div>
                <div class="field" style="flex:2;">
                    <label>Of een weblink (mp4/webm, YouTube, Vimeo, webpagina)</label>
                    <input type="text" id="cs_<?= $i ?>_url" name="cs_<?= $i ?>_url" value="<?= htmlspecialchars($c['url']) ?>" placeholder="https://… of cmb-uploads/clip.mp4" oninput="cmbClipLinkHint(this)">
                    <small class="hint cmb-cliplink-hint" style="color:#ffd166;<?= ($c['url'] !== '' && cmb_clip_info($c['url'])['kind'] === 'page') ? '' : 'display:none;' ?>">⚠ Dit is een webpagina, geen videobestand. Werkt meestal niet (bv. Pixabay): download de video en upload hem hierboven, of plak een link die op .mp4 eindigt.</small>
                </div>
            </div>

            <div class="form-row" style="align-items:flex-end;">
                <div class="field" style="max-width:170px;">
                    <label>Start na (seconden)</label>
                    <input type="number" name="cs_<?= $i ?>_delay" value="<?= htmlspecialchars((string)$c['delay']) ?>" min="0" max="60" step="0.5">
                    <small class="hint">Stream loopt vaak even achter: stel hier de vertraging in zodat beeld en muziek gelijk lopen.</small>
                </div>
                <div class="field" style="max-width:190px;">
                    <label>Beeld</label>
                    <select name="cs_<?= $i ?>_fit">
                        <option value="contain" <?= $c['fit'] === 'contain' ? 'selected' : '' ?>>Hele clip zichtbaar</option>
                        <option value="cover" <?= $c['fit'] === 'cover' ? 'selected' : '' ?>>Beeld vullen (randen weg)</option>
                    </select>
                </div>
                <div class="field" style="max-width:260px;"><label>Waar tonen?</label><div class="cmb-help">Altijd in het slide-frame (bij compact in het kleine derde venster). Nooit over het hele scherm.</div></div>
            </div>
            <div class="form-row">
                <label class="cmb-check"><input type="checkbox" name="cs_<?= $i ?>_show_title" value="1" <?= !empty($c['show_title']) ? 'checked' : '' ?>> Artiest en titel over de clip tonen</label>
                <span class="cmb-help">Een clip speelt altijd zonder geluid; de stream blijft hoorbaar.</span>
            </div>
        </div>
    </details>
    <?php endfor; ?>
</div>
