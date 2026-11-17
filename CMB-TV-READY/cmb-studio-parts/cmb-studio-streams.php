<?php
/** CMB Studio > Streams. Wordt ingeladen door cmb-studio.php (tab "streams"). */
$hiddenPresets = (isset($settings['hidden_presets']) && is_array($settings['hidden_presets'])) ? $settings['hidden_presets'] : [];
$stationList = [];
foreach ($presets as $pName => $pData) {
    if (!is_array($pData)) continue;
    $stationList[] = [
        'name' => (string)$pName,
        'hidden' => in_array($pName, $hiddenPresets, true),
        'data' => [
            'streamUrl' => $pData['streamUrl'] ?? '', 'fallbackUrl' => $pData['fallbackUrl'] ?? '', 'statsJsonUrl' => $pData['statsJsonUrl'] ?? '',
            'apiBackupUrl' => $pData['apiBackupUrl'] ?? '', 'jsSrc' => $pData['jsSrc'] ?? '',
            'jsPort' => $pData['jsPort'] ?? '', 'coverApi' => $pData['coverApi'] ?? 'itunes',
        ],
    ];
}
?>
<div id="tab-streams" class="tab-pane active" style="display:block;">
    <div class="cmb-helpbox" style="margin-bottom:16px;">
        <strong>Zo werkt het:</strong> stream 1 en stream 2 zijn twee <u>volledige, losse</u> zenders (geluid, titelinfo en hoes).
        Normaal speelt stream 1. Maakt stream 1 geen contact, dan neemt stream 2 het <strong>helemaal</strong> over, en zodra stream 1 terug is schakelt de speler vanzelf terug.
        Laat stream 2 leeg als je geen reserve hebt.
    </div>

    <div class="card" style="border:1px solid var(--cyan);">
        <h2>📚 Mijn zenders (snel invullen)</h2>
        <p style="font-size:12px; color:#a8aac8; margin-top:-6px;">
            Een zender bewaren is optioneel. <strong>Een zender bewaren slaat op:</strong> geluids-URL, titel-URL, backup-API, speler-JS, poort en hoes-zoeker van de stream die je kiest.
            Ingevulde zenders veranderen <u>nooit</u> vanzelf: alleen de knop "Bewaar" schrijft naar deze lijst. Vink <strong>Verberg</strong> aan om een zender uit het zicht te halen (hij wordt niet verwijderd).
        </p>
        <table class="cmb-station-table" id="stationTable">
            <thead><tr><th>Zender</th><th>Invullen in</th><th>Verberg</th></tr></thead>
            <tbody id="stationBody">
            <?php foreach ($stationList as $i => $st): ?>
                <tr class="<?= $st['hidden'] ? 'is-hidden' : '' ?>" data-station="<?= htmlspecialchars(json_encode($st['data']), ENT_QUOTES) ?>">
                    <td><strong><?= htmlspecialchars($st['name']) ?></strong>
                        <small><?= htmlspecialchars((string)(parse_url($st['data']['streamUrl'], PHP_URL_HOST) ?: '')) ?></small></td>
                    <td>
                        <button type="button" class="btn-mini-save" onclick="cmbLoadStation(1, this)">→ Stream 1</button>
                        <button type="button" class="btn-mini-save" style="background:#ffd166;" onclick="cmbLoadStation(2, this)">→ Stream 2</button>
                    </td>
                    <td><label style="text-transform:none; margin:0;"><input type="checkbox" name="hp_<?= $i ?>" value="<?= htmlspecialchars($st['name'], ENT_QUOTES) ?>" <?= $st['hidden'] ? 'checked' : '' ?> onchange="this.closest('tr').classList.toggle('is-hidden', this.checked)"> verberg</label></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$stationList): ?><tr id="stationEmpty"><td colspan="3" style="color:#a8aac8;">Nog geen zenders bewaard.</td></tr><?php endif; ?>
            </tbody>
        </table>
        <div class="form-row" style="align-items:flex-end; margin-top:12px;">
            <div class="field" style="flex:2;"><label>Bewaar onder de naam</label>
                <input type="text" id="new_preset_name" placeholder="bv. DD Fresh Turkye"></div>
            <div class="field" style="max-width:170px;"><button type="button" class="btn-mini-save" style="width:100%; height:38px;" onclick="cmbSaveStation(1)">+ Bewaar stream 1</button></div>
            <div class="field" style="max-width:170px;"><button type="button" class="btn-mini-save" style="width:100%; height:38px; background:#ffd166;" onclick="cmbSaveStation(2)">+ Bewaar stream 2</button></div>
        </div>
    </div>

    <div class="card" style="border:1px solid #00e676;">
        <h2>🥇 Stream 1 — hoofdzender</h2>
        <div class="form-row"><div class="field"><label>Naam (voor jezelf)</label>
            <input type="text" name="stream1_name" value="<?= htmlspecialchars($settings['stream1_name'] ?? '') ?>" placeholder="bv. DD Fresh Turkye"></div></div>
        <div class="form-row"><div class="field"><label>Audio stream URL (streamUrl)</label>
            <input type="text" name="streamUrl" id="streamUrl" value="<?= htmlspecialchars($settings['streamUrl'] ?? '') ?>" placeholder="https://host:poort/stream"></div></div>
        <div class="form-row"><div class="field"><label>Tweede audio-adres van dezelfde zender (optioneel)</label>
            <input type="text" name="streamUrlAlt" id="streamUrlAlt" value="<?= htmlspecialchars($settings['streamUrlAlt'] ?? '') ?>" placeholder="bv. https://host:poort/stream (wordt geprobeerd vóór stream 2)"></div></div>
        <div class="form-row"><div class="field"><label>Titelinfo: stats JSON URL (statsJsonUrl)</label>
            <input type="text" name="statsJsonUrl" id="statsJsonUrl" value="<?= htmlspecialchars($settings['statsJsonUrl'] ?? '') ?>" placeholder="https://host:poort/stats?json=1&sid=43"></div></div>
        <div class="form-row"><div class="field"><label>Titelinfo reserve: backup API (apiBackupUrl)</label>
            <input type="text" name="apiBackupUrl" id="apiBackupUrl" value="<?= htmlspecialchars($settings['apiBackupUrl'] ?? '') ?>" placeholder="https://host:poort/rpc/…/streaminfo.get"></div></div>
        <div class="form-row">
            <div class="field" style="flex:2;"><label>JavaScript speler bron (jsSrc)</label>
                <input type="text" name="jsSrc" id="jsSrc" value="<?= htmlspecialchars($settings['jsSrc'] ?? '') ?>" placeholder="https://host:poort/system/player.js"></div>
            <div class="field" style="max-width:180px;"><label>Poort / mount (jsPort)</label>
                <input type="text" name="jsPort" id="jsPort" value="<?= htmlspecialchars($settings['jsPort'] ?? '') ?>" placeholder="4433 of /stream" style="text-align:center;"></div>
            <div class="field" style="max-width:200px;"><label>Hoes-zoeker (stream 1)</label>
                <select name="coverApi" id="coverApi">
                    <option value="itunes" <?= ($settings['coverApi'] ?? 'itunes') === 'itunes' ? 'selected' : '' ?>>Apple iTunes</option>
                    <option value="deezer" <?= ($settings['coverApi'] ?? '') === 'deezer' ? 'selected' : '' ?>>Deezer</option>
                    <option value="lastfm" <?= ($settings['coverApi'] ?? '') === 'lastfm' ? 'selected' : '' ?>>Last.fm</option>
                </select></div>
        </div>
    </div>

    <div class="card" style="border:1px solid #ffd166;">
        <h2>🥈 Stream 2 — reservezender (neemt alles over als stream 1 uitvalt)</h2>
        <div class="form-row"><div class="field"><label>Naam (voor jezelf)</label>
            <input type="text" name="stream2_name" value="<?= htmlspecialchars($settings['stream2_name'] ?? '') ?>" placeholder="bv. DD Fresh Epic"></div></div>
        <div class="form-row"><div class="field"><label>Audio stream URL (stream 2)</label>
            <input type="text" name="fallbackUrl" id="fallbackUrl" value="<?= htmlspecialchars($settings['fallbackUrl'] ?? '') ?>" placeholder="https://backup-host:poort/stream"></div></div>
        <div class="form-row"><div class="field"><label>Tweede audio-adres van dezelfde zender (stream 2, optioneel)</label>
            <input type="text" name="fallbackUrlAlt" id="fallbackUrlAlt" value="<?= htmlspecialchars($settings['fallbackUrlAlt'] ?? '') ?>" placeholder="bv. https://backup-host:poort/stream"></div></div>
        <div class="form-row"><div class="field"><label>Titelinfo: stats JSON URL (stream 2)</label>
            <input type="text" name="statsJsonUrl2" id="statsJsonUrl2" value="<?= htmlspecialchars($settings['statsJsonUrl2'] ?? '') ?>" placeholder="https://backup-host:poort/stats?json=1&sid=1"></div></div>
        <div class="form-row"><div class="field"><label>Titelinfo reserve: backup API (stream 2)</label>
            <input type="text" name="apiBackupUrl2" id="apiBackupUrl2" value="<?= htmlspecialchars($settings['apiBackupUrl2'] ?? '') ?>" placeholder="https://backup-host:poort/rpc/…/streaminfo.get"></div></div>
        <div class="form-row">
            <div class="field" style="flex:2;"><label>JavaScript speler bron (stream 2)</label>
                <input type="text" name="jsSrc2" id="jsSrc2" value="<?= htmlspecialchars($settings['jsSrc2'] ?? '') ?>"></div>
            <div class="field" style="max-width:180px;"><label>Poort / mount (stream 2)</label>
                <input type="text" name="jsPort2" id="jsPort2" value="<?= htmlspecialchars($settings['jsPort2'] ?? '') ?>" style="text-align:center;"></div>
            <div class="field" style="max-width:200px;"><label>Hoes-zoeker (stream 2)</label>
                <select name="coverApi2" id="coverApi2">
                    <option value="" <?= ($settings['coverApi2'] ?? '') === '' ? 'selected' : '' ?>>Zelfde als stream 1</option>
                    <option value="itunes" <?= ($settings['coverApi2'] ?? '') === 'itunes' ? 'selected' : '' ?>>Apple iTunes</option>
                    <option value="deezer" <?= ($settings['coverApi2'] ?? '') === 'deezer' ? 'selected' : '' ?>>Deezer</option>
                    <option value="lastfm" <?= ($settings['coverApi2'] ?? '') === 'lastfm' ? 'selected' : '' ?>>Last.fm</option>
                </select></div>
        </div>
        <div class="form-row" style="margin-top:6px;"><div class="field" style="max-width:300px;">
            <label>Terugschakelen naar stream 1: controleer elke (seconden)</label>
            <input type="number" name="stream_recheck_sec" min="20" max="600" value="<?= max(20, intval($settings['stream_recheck_sec'] ?? 60)) ?>"></div></div>
    </div>

    <div class="card">
        <h2>📧 Melding bij uitval van een stream</h2>
        <div class="form-row">
            <div class="field" style="flex:2;"><label>Mailadres voor de melding (leeg = geen mails)</label>
                <input type="email" name="alert_mail" value="<?= htmlspecialchars($settings['alert_mail'] ?? '') ?>" placeholder="bv. studio@jouwstation.nl" autocomplete="off"></div>
            <div class="field" style="margin-top:24px;"><label style="cursor:pointer;"><input type="checkbox" name="alert_test" value="1"> Stuur nu een testmail bij opslaan</label></div>
        </div>
        <small style="color:#a8aac8; font-size:11px;">Je krijgt één mail als stream 1 of stream 2 uitvalt (met erbij of de reserve het overneemt) en één mail als hij weer terug is. Controle elke minuut, zolang er iemand luistert. Wil je ook gemeld worden als niemand luistert? Zet dan een cronjob op <code>cmb-streamwatch.php</code> (elke minuut).</small>
    </div>

    <div class="card">
        <h2>🎨 Hoes (albumcover)</h2>
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label>Last.fm API-sleutel (gratis; optioneel)</label>
                <input type="text" name="lastfm_key" value="<?= htmlspecialchars($settings['lastfm_key'] ?? '') ?>" placeholder="32 tekens, aan te vragen op last.fm/api/account/create" autocomplete="off" spellcheck="false">
                <small style="color:#a8aac8; font-size:11px;">Met een sleutel is Last.fm altijd een extra vangnet als de gekozen bron geen hoes vindt. De sleutel blijft op de server.</small>
            </div>
            <div class="field" style="margin-top:24px;">
                <label style="cursor:pointer;"><input type="checkbox" name="cover_fallback_centova" value="1" <?= !empty($settings['cover_fallback_centova']) ? 'checked' : '' ?>> 🖼️ Albumhoes van de stream als laatste reserve</label>
            </div>
        </div>
    </div>
</div>
<style>
.cmb-station-table { width:100%; border-collapse:collapse; font-size:13px; }
.cmb-station-table th { text-align:left; font-size:11px; text-transform:uppercase; color:#a8aac8; padding:6px 8px; border-bottom:1px solid rgba(255,255,255,.1); }
.cmb-station-table td { padding:8px; border-bottom:1px solid rgba(255,255,255,.06); vertical-align:middle; }
.cmb-station-table td small { display:block; color:#a8aac8; font-size:11px; }
.cmb-station-table tr.is-hidden td { opacity:.45; }
.cmb-station-table tr.is-hidden strong { text-decoration:line-through; }
</style>
<script>
(function () {
    var MAP1 = { streamUrl: 'streamUrl', fallbackUrl: 'streamUrlAlt', statsJsonUrl: 'statsJsonUrl', apiBackupUrl: 'apiBackupUrl', jsSrc: 'jsSrc', jsPort: 'jsPort', coverApi: 'coverApi' };
    var MAP2 = { streamUrl: 'fallbackUrl', fallbackUrl: 'fallbackUrlAlt', statsJsonUrl: 'statsJsonUrl2', apiBackupUrl: 'apiBackupUrl2', jsSrc: 'jsSrc2', jsPort: 'jsPort2', coverApi: 'coverApi2' };
    function el(id) { return document.getElementById(id); }
    window.cmbLoadStation = function (n, btn) {
        var d = {}; try { d = JSON.parse(btn.closest('tr').getAttribute('data-station')); } catch (e) {}
        var map = n === 1 ? MAP1 : MAP2;
        Object.keys(map).forEach(function (k) {
            var f = el(map[k]); if (f && d[k] !== undefined) { f.value = d[k]; f.dispatchEvent(new Event('input', { bubbles: true })); }
        });
        var name = btn.closest('tr').querySelector('strong').textContent;
        var nm = document.querySelector('[name=' + (n === 1 ? 'stream1_name' : 'stream2_name') + ']');
        if (nm) nm.value = name;
    };
    window.cmbSaveStation = async function (n) {
        var name = (el('new_preset_name').value || '').trim();
        if (!name) { alert('Vul eerst een naam in bij "Bewaar onder de naam".'); return; }
        var map = n === 1 ? MAP1 : MAP2, p = { name: name };
        Object.keys(map).forEach(function (k) { var f = el(map[k]); p[k] = f ? f.value.trim() : ''; });
        if (!p.coverApi) p.coverApi = (el('coverApi') || {}).value || 'itunes';
        if (!p.streamUrl) { alert('Stream ' + n + ' heeft nog geen audio-URL, dus er valt niets te bewaren.'); return; }
        try {
            var r = await fetch('cmb-studio.php?ajax_save_preset=1', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(p) });
            var d = await r.json();
            if (d.status !== 'ok') { alert('Bewaren is niet gelukt.'); return; }
            // Lijst direct bijwerken zonder de pagina te herladen (je andere wijzigingen blijven staan)
            var empty = el('stationEmpty'); if (empty) empty.remove();
            var body = el('stationBody'), row = null;
            body.querySelectorAll('tr').forEach(function (tr) { if (tr.querySelector('strong').textContent === name) row = tr; });
            var data = { streamUrl: p.streamUrl, fallbackUrl: p.fallbackUrl, statsJsonUrl: p.statsJsonUrl, apiBackupUrl: p.apiBackupUrl, jsSrc: p.jsSrc, jsPort: p.jsPort, coverApi: p.coverApi };
            if (row) { row.setAttribute('data-station', JSON.stringify(data)); }
            else {
                row = document.createElement('tr'); row.setAttribute('data-station', JSON.stringify(data));
                row.innerHTML = '<td><strong></strong><small></small></td><td>' +
                    '<button type="button" class="btn-mini-save" onclick="cmbLoadStation(1, this)">→ Stream 1</button> ' +
                    '<button type="button" class="btn-mini-save" style="background:#ffd166;" onclick="cmbLoadStation(2, this)">→ Stream 2</button></td>' +
                    '<td><small>(na opslaan ook te verbergen)</small></td>';
                row.querySelector('strong').textContent = name;
                try { row.querySelector('small').textContent = new URL(p.streamUrl).hostname; } catch (e) {}
                body.appendChild(row);
            }
            el('new_preset_name').value = '';
            alert('✓ "' + name + '" bewaard in Mijn zenders.');
        } catch (e) { alert('Netwerkfout bij bewaren.'); }
    };
})();
</script>
