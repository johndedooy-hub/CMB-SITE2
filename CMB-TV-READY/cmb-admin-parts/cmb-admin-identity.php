<?php /** CMB Beheer > Identiteit. Geladen door cmb-admin.php. */ if (!defined('CMB_PART')) { http_response_code(403); exit; } ?>
    <div class="card">
        <h2>↔ Bovenste regel: plek van elk onderdeel</h2>
        <div class="cmb-helpbox">Geef elk onderdeel een plek van <strong>1 (links) t/m 7 (rechts)</strong>, of zet het op <strong>Uit</strong>. De onderdelen staan in die volgorde gelijkmatig verdeeld over de regel. Op een telefoon staat de naam bovenaan, daaronder weer en tijd naast elkaar en dan de middenregel. Vink "Standaard indeling" aan om terug te gaan naar hoe het eerst was.</div>
        <?php
        $tpEls = ['logo' => 'CMB-blokje (logo)', 'brand' => 'Naam of banner', 'weather' => 'Weer', 'time' => 'Tijd en datum', 'mail' => 'Middenregel', 'traffic' => 'Knoppenblok (auto, trein, bus, WhatsApp)'];
        $topSlots = cmb_top_slots($settings); $topCustom = $topSlots !== null;
        $tpCur = [];
        $tpI = 0;
        foreach ($tpEls as $tk => $tl) { $tpI++; $tpCur[$tk] = isset($topSlots) && is_array($topSlots) ? intval($topSlots[$tk] ?? $tpI) : [1, 2, 4, 5, 3, 3][$tpI - 1]; if ($tk === 'traffic' && (!isset($topSlots) || !is_array($topSlots))) $tpCur[$tk] = 3; }
        ?>
        <div class="form-row">
            <?php foreach ($tpEls as $tk => $tl): ?>
            <div class="field"><label><?= $tl ?></label>
                <select name="top_slot_<?= $tk ?>">
                    <option value="0" <?= $tpCur[$tk] === 0 ? 'selected' : '' ?>>Uit</option>
                    
                    <?php for ($n = 1; $n <= 7; $n++): ?><option value="<?= $n ?>" <?= $tpCur[$tk] === $n ? 'selected' : '' ?>>Plek <?= $n ?><?= $n === 1 ? ' (links)' : ($n === 7 ? ' (rechts)' : '') ?></option><?php endfor; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
        <label class="cmb-check"><input type="checkbox" name="top_default" value="1" <?= empty($topCustom) ? 'checked' : '' ?>> Standaard indeling (zoals in het begin)</label>
        <label class="cmb-check" style="margin-top:10px"><input type="checkbox" name="tw_swap" value="1" <?= !empty($settings['tw_swap']) ? 'checked' : '' ?>> Tijd en weer wisselen op één plek (scheelt een plaats)</label>
        <div class="form-row"><div class="field" style="max-width:240px;"><label>Wisselen na ... seconden</label><input type="number" name="tw_sec" min="2" max="60" value="<?= intval($settings['tw_sec'] ?? 6) ?>"></div></div>
        <div class="cmb-helpbox" style="margin-top:6px">Het blok neemt de plek van "Tijd en datum"; de plek van "Weer" wordt dan niet gebruikt.</div>
        <div class="form-row" style="margin-top:10px"><div class="field" style="max-width:260px;"><label>Logo en banner groter (%) op computer en tv</label><input type="number" name="logo_scale" min="100" max="200" step="5" value="<?= max(100, min(200, intval($settings['logo_scale'] ?? 100))) ?>"></div></div>
        <div class="cmb-helpbox" style="margin-top:6px">100 = zoals nu. Alleen in liggende stand (computer, laptop, tv); op een telefoon verandert niets. Het logo wordt nooit hoger dan de bovenste regel zelf, dus er schuift niets op.</div>
    </div>
    <div class="card">
        <h2>Station Identiteit & Middenregel</h2>
        <?= renderStyleSuite('st_title', 'Station Hoofdtitel', $settings['station_title'] ?? '', $settings['style_station_title'] ?? []) ?>
        <?= renderStyleSuite('st_sub', 'Station Subtitel', $settings['station_sub'] ?? 'RETRO • [VISUAL]-RADIO • DANCE', $settings['style_station_sub'] ?? []) ?>
        <?= renderStyleSuite('mail', 'Middenregel (Hele zin)', $settings['studio_mail'] ?? 'mail de studio: mail@chazzo.com', $settings['style_studio_mail'] ?? []) ?>
    </div>
    <div class="card">
        <h2>🚗 Verkeer (files en ongelukken)</h2>
        <div class="cmb-helpbox">Toont naast de middenregel een autoknop met het aantal meldingen. Een klik opent een venster met files en ongelukken in heel Nederland (bron: NDW, gratis). De speler ververst zelf elke 5 minuten, een cronjob is niet nodig.</div>
        <label class="cmb-check"><input type="checkbox" name="verkeer_on" value="1" <?= !empty($settings['verkeer_on']) ? 'checked' : '' ?>> Verkeersknop tonen</label>
        <div class="form-row">
            <div class="field" style="max-width:200px;"><label>Max. meldingen in het venster (100 = alles)</label><input type="number" name="verkeer_max2" min="1" max="200" value="<?= intval($settings['verkeer_max2'] ?? 100) ?>"></div>
            <div class="field"><label>Bron-adres (leeg = NDW)</label><input type="text" name="verkeer_url" value="<?= htmlspecialchars($settings['verkeer_url'] ?? '') ?>" placeholder="https://opendata.ndw.nu/actueel_beeld.xml.gz"></div>
        </div>
        <h2 style="margin-top:18px">🚆 OV (treinstoringen)</h2>
        <div class="cmb-helpbox">Een treinknop naast de verkeersknop. Een klik opent een venster met treinstoringen en werkzaamheden (bron: NS). U heeft een gratis sleutel nodig van het NS-ontwikkelaarsportaal; plak die bij <a href="cmb-sleutels.php">Sleutels</a> (daar staan alle sleutels bij elkaar) of hieronder. Een opgeslagen sleutel blijft verborgen.</div>
        <label class="cmb-check"><input type="checkbox" name="ov_on" value="1" <?= !empty($settings['ov_on']) ? 'checked' : '' ?>> OV-knop tonen</label>
        <div class="form-row">
            <div class="field"><label>NS-sleutel <?= !empty($settings['ov_key']) ? '(is opgeslagen, leeg laten om te behouden)' : '(nog niet ingevuld)' ?></label><input type="text" name="ov_key" autocomplete="off" value="" placeholder="plak hier de sleutel"></div>
        </div>
        <label class="cmb-check"><input type="checkbox" name="ov_key_clear" value="1"> Opgeslagen sleutel wissen</label>
        <p><a href="cmb-ov-data.php?test=1" target="_blank" rel="noopener">Nu ophalen en testen</a> (ziet u bij "storingen" of "werk" cijfers en "err" is leeg, dan werkt het)</p>
        <h2 style="margin-top:18px">🚌 Bus, tram en metro</h2>
        <div class="cmb-helpbox">Een eigen busknop achter de treinknop met meldingen van bus, tram en metro (bron: OpenOV, geen sleutel nodig). De test staat bij OV hierboven.</div>
        <label class="cmb-check"><input type="checkbox" name="bus_on" value="1" <?= !empty($settings['bus_on']) ? 'checked' : '' ?>> Busknop tonen</label>
        <h2 style="margin-top:18px">📲 App op het beginscherm</h2>
        <div class="cmb-helpbox">Laat bezoekers de speler als app op hun telefoon zetten (zonder adresbalk). Er verschijnt onderin een venster "Installeren" (op iPhone een korte uitleg). Het pictogram komt van uw logo (of favicon). Test: <a href="cmb-manifest.php" target="_blank" rel="noopener">cmb-manifest.php</a> moet tekst tonen, en <a href="cmb-pwa-icon.php?s=192" target="_blank" rel="noopener">het pictogram</a> een plaatje.</div>
        <label class="cmb-check"><input type="checkbox" name="pwa_on" value="1" <?= !empty($settings['pwa_on']) ? 'checked' : '' ?>> App-installatie aanbieden</label>
        <div class="form-row"><div class="field" style="max-width:260px;"><label>Korte naam onder het pictogram (max. 24 tekens, leeg = titel)</label><input type="text" name="pwa_name" maxlength="24" value="<?= htmlspecialchars($settings['pwa_name'] ?? '') ?>"></div></div>
        <h2 style="margin-top:18px">💬 WhatsApp-knop</h2>
        <div class="cmb-helpbox">Een knop die direct WhatsApp opent (op een telefoon de app, op een pc WhatsApp Web). Geef het nummer met landcode, bijvoorbeeld 31612345678 (of 0612345678). De knoppen (auto, trein, bus, WhatsApp) staan samen in één blok; de plek van dat blok kiest u bovenaan bij "plek van elk onderdeel". De middenregel staat los daarvan.</div>
        <label class="cmb-check"><input type="checkbox" name="wa_on" value="1" <?= !empty($settings['wa_on']) ? 'checked' : '' ?>> WhatsApp-knop tonen</label>
        <div class="form-row">
            <div class="field" style="max-width:260px;"><label>Telefoonnummer</label><input type="text" name="wa_number" value="<?= htmlspecialchars($settings['wa_number'] ?? '') ?>" placeholder="31612345678"></div>
            <div class="field"><label>Eerste tekst in het bericht (mag leeg)</label><input type="text" name="wa_text" maxlength="200" value="<?= htmlspecialchars($settings['wa_text'] ?? '') ?>" placeholder="Hoi, ik luister mee!"></div>
        </div>
        <p><a href="cmb-verkeer-data.php?test=1" target="_blank" rel="noopener">Nu ophalen en testen</a> (opent het resultaat; ziet u daar bij "files" en "ongelukken" cijfers, dan werkt het)</p>
    </div>
    <div class="card">
        <h2>🔠 Tekstgrootte van de slides</h2>
        <div class="cmb-helpbox">
            De tekst in slides schaalt vanzelf mee met de breedte van het slide-paneel: groter op een tv of groot scherm, kleiner op een telefoon.
            Hieronder stel je dat bij. 100% = zoals de automatiek het kiest. Je lettergrootte per tekst (in px) blijft de basis.
            Het <strong>maximum aantal regels</strong> bepaalt hoeveel regels van de tekst getoond worden voordat hij wordt afgekapt (…).
        </div>
        <div class="form-row">
            <div class="field" style="max-width:200px;"><label>Computer / tv (%)</label><input type="number" name="slide_text_scale" min="50" max="250" value="<?= intval($settings['slide_text_scale'] ?? 100) ?>"></div>
            <div class="field" style="max-width:200px;"><label>Mobiel (%)</label><input type="number" name="slide_text_scale_m" min="50" max="250" value="<?= intval($settings['slide_text_scale_m'] ?? 100) ?>"></div>
            <div class="field" style="max-width:200px;"><label>Max. regels tekst (computer/tv)</label><input type="number" name="slide_lines" min="2" max="20" value="<?= intval($settings['slide_lines'] ?? 9) ?>"></div>
            <div class="field" style="max-width:200px;"><label>Max. regels tekst (mobiel)</label><input type="number" name="slide_lines_m" min="2" max="12" value="<?= intval($settings['slide_lines_m'] ?? 4) ?>"></div>
        </div>
    </div>
    <div class="card">
        <h2>Widgets: Weer & Klok / Datum</h2>
        <div class="form-row">
            <div class="field"><?= renderStyleSuite('lbl_w', 'Label Weer', $settings['lbl_weather_text'] ?? 'WEER', $settings['style_lbl_weather'] ?? []) ?></div>
            <div class="field"><?= renderStyleSuite('city', 'Plaatsnaam onder Weer', $settings['weather_city_display'] ?? 'Steenbergen', $settings['style_weather_city'] ?? []) ?></div>
            <div class="field" style="max-width:80px;"><label>Kleur Weer</label><input type="color" name="color_val_weather" value="<?= htmlspecialchars($settings['color_val_weather'] ?? '#00e676') ?>"></div>
        <div class="field"><?= renderStyleSuite('weather_value', 'Temperatuur / weerwaarde (font, grootte, kleur, verloop)', 'Weerwaarde', $settings['style_weather_value'] ?? ['font'=>'Montserrat','size'=>'25px','color'=>'#00e676'], false, false) ?></div>
        </div>
        <div class="form-row">
            <div class="field"><?= renderStyleSuite('lbl_t', 'Label Klok', $settings['lbl_time_text'] ?? 'TIJD', $settings['style_lbl_time'] ?? []) ?></div>
            <div class="field" style="max-width:80px;"><label>Kleur Tijd</label><input type="color" name="color_val_time" value="<?= htmlspecialchars($settings['color_val_time'] ?? '#ffffff') ?>"></div>
        <div class="field"><?= renderStyleSuite('time_value', 'Klokwaarde (font, grootte, kleur, verloop)', 'Tijdwaarde', $settings['style_time_value'] ?? ['font'=>'Montserrat','size'=>'25px','color'=>'#ffffff'], false, false) ?></div>
        </div>
        <?= renderStyleSuite('date_line', 'Datum Notering Regel (Ondersteunt <b> voor vetgedrukt)', $settings['date_line_val'] ?? '<b>zo 11 okt 2026</b>', $settings['style_date_line'] ?? ['size'=>'13px','color'=>'#a8aac8']) ?>
    </div>
    <div class="card">
        <h2>Cover Elementen & Vrije Fallback Tekst</h2>
        <?= renderStyleSuite('txt_above', 'Tekst Boven Cover (On-Air Balk)', $settings['text_above_cover'] ?? '● ON AIR', $settings['style_text_above_cover'] ?? []) ?>
        <?= renderStyleSuite('txt_behind', 'Grote Tekst Achter Cover (Koptekst)', $settings['text_behind_cover'] ?? 'CMB', $settings['style_text_behind_cover'] ?? []) ?>
        <?= renderStyleSuite('txt_behind_sub', 'Kleine Tekst Achter Cover (Subtekst)', $settings['text_behind_sub'] ?? 'CHAZZO MUSIC BOX', $settings['style_text_behind_sub'] ?? ['size'=>'10px','color'=>'#a8aac8']) ?>
        <?= renderStyleSuite('st_subnow', 'Subtitel onder Track', $settings['station_sub_now'] ?? '', $settings['style_station_sub_now'] ?? []) ?>
        <?= renderStyleSuite('trk_artist', 'Artiest (in de speler)', 'De artiestnaam die nu speelt; tekst komt van de stream.', $settings['style_track_artist'] ?? ['font' => 'Montserrat', 'size' => '19px', 'color' => '#63f6ff'], true, false) ?>
        <?= renderStyleSuite('trk_song', 'Titel (in de speler)', 'De titel van het nummer; tekst komt van de stream.', $settings['style_track_song'] ?? ['font' => 'Montserrat', 'size' => '15px', 'color' => '#f7f7ff'], true, false) ?>
        <?= renderStyleSuite('trk_fb', 'Track Fallback bij uitval: "ARTIEST - TITEL" (wordt gesplitst zoals de stream) of alleen een titel', $settings['track_fallback_title'] ?? 'CMB Live Uitzending', $settings['style_track_fallback'] ?? []) ?>
        <?= renderStyleSuite('badge', 'Hoekbadge Slideframe', $settings['slide_corner_badge'] ?? 'CMB', $settings['style_corner_badge'] ?? []) ?>
    </div>
    <div class="card">
        <h2>Voetteksten & Feestletters</h2>
        <div class="form-row">
            <div class="field"><?= renderStyleSuite('bot_l', 'Voettekst Links', $settings['bottom_left_text'] ?? 'CHAZZOMUSICBOX', $settings['style_bottom_left'] ?? []) ?></div>
            <div class="field"><?= renderStyleSuite('bot_r', 'Voettekst Rechts', $settings['bottom_right_text'] ?? '', $settings['style_bottom_right'] ?? []) ?></div>
        </div>
        <div class="form-row">
            <div class="field" style="flex:2;"><?= renderStyleSuite('water_st', 'Watertekst Letters (waterval bij trackwissel)', $settings['water_text'] ?? '', $settings['style_water'] ?? ['font' => $settings['water_font'] ?? 'Arial', 'size' => intval($settings['water_font_size'] ?? 15) . 'px', 'color' => $settings['water_color'] ?? '#63f6ff', 'grad_mode' => 'none', 'grad_col2' => '#ff5bc8', 'shadow' => ''], false, true) ?></div>
        </div>
        <div class="form-row">
            <div class="field"><label>Letterafstand (px)</label><input type="number" name="water_letter_spacing" min="0" max="20" value="<?= intval($settings['water_letter_spacing'] ?? 2) ?>"></div>
            <div class="field"><label><input type="checkbox" name="water_random_colors" value="1" <?= !empty($settings['water_random_colors'])?'checked':'' ?>> Elke letter willekeurige kleur</label></div>
            <div class="field"><label>Eigen kleuren (komma-gescheiden HEX)</label><input type="text" name="water_custom_colors" value="<?= htmlspecialchars($settings['water_custom_colors'] ?? '#63f6ff,#ff5bc8,#ffd166,#9d6cff') ?>" placeholder="#63f6ff,#ff5bc8,#ffd166"></div>
            <div class="field"><label>Gloed (px)</label><input type="number" name="water_glow" min="0" max="30" value="<?= intval($settings['water_glow'] ?? 8) ?>"></div>
            <div class="field" style="flex:2;">
                <label>Trackwissel Letter Effect (animatie bij nieuw nummer)</label>
                <select name="water_anim_mode">
                    <option value="bubble" <?= ($settings['water_anim_mode']??'bubble')==='bubble'?'selected':'' ?>>1. 🫧 Klassiek Bubbelen & Bouncen</option>
                    <option value="supernova" <?= ($settings['water_anim_mode']??'')==='supernova'?'selected':'' ?>>2. 💥 Supernova Blast (Schiet groot buiten frame)</option>
                    <option value="hyperdrive" <?= ($settings['water_anim_mode']??'')==='hyperdrive'?'selected':'' ?>>3. 🌀 Hyperdrive 3D Salto (Ruimtelijk 360°)</option>
                    <option value="shockwave" <?= ($settings['water_anim_mode']??'')==='shockwave'?'selected':'' ?>>4. ⚡ Neon Strobe Shockwave (Flits & Glow)</option>
                    <option value="rainbowwave" <?= ($settings['water_anim_mode']??'')==='rainbowwave'?'selected':'' ?>>5. 🌈 Regenboog Golf (kleuren rollen door de letters)</option>
                    <option value="neonflicker" <?= ($settings['water_anim_mode']??'')==='neonflicker'?'selected':'' ?>>6. 💡 Neon Reclame Flikker (knipperende gloed)</option>
                    <option value="dropbounce" <?= ($settings['water_anim_mode']??'')==='dropbounce'?'selected':'' ?>>7. ⬇️ Vallende Stuiter (letters vallen en stuiteren)</option>
                    <option value="flipcoin" <?= ($settings['water_anim_mode']??'')==='flipcoin'?'selected':'' ?>>8. 🪙 Muntflip (letters draaien als munten)</option>
                    <option value="fireworks" <?= ($settings['water_anim_mode']??'')==='fireworks'?'selected':'' ?>>9. 🎆 Vuurwerk Knal (kleurrijke explosie)</option>
                    <option value="bigflash" <?= ($settings['water_anim_mode']??'')==='bigflash'?'selected':'' ?>>16. 💥 Floep groot in beeld (letters knallen even midden in het scherm)</option>
                    <option value="random" <?= ($settings['water_anim_mode']??'')==='random'?'selected':'' ?>>10. 🎲 Random Party Mix (alle 9, wisselend per track)</option>
                    <option value="orbit" <?= ($settings['water_anim_mode']??'')==='orbit'?'selected':'' ?>>11. 🪐 Orbitale draai</option>
                    <option value="typewriter" <?= ($settings['water_anim_mode']??'')==='typewriter'?'selected':'' ?>>12. ✍️ Typemachine / zachte fade</option>
                    <option value="pulse" <?= ($settings['water_anim_mode']??'')==='pulse'?'selected':'' ?>>13. 💓 Neon puls</option>
                    <option value="waverise" <?= ($settings['water_anim_mode']??'')==='waverise'?'selected':'' ?>>14. 🌊 Golf omhoog</option>
                    <option value="scatter" <?= ($settings['water_anim_mode']??'')==='scatter'?'selected':'' ?>>15. ✨ Verspreiden en verzamelen</option>
                </select>
            </div>
        </div>
    </div>

    <div class="card">
        <h2>🔽 PL-balk (uitklapbalk onder de speler)</h2>
        <div class="cmb-helpbox">
            Vast, van links naar rechts: 🔒 privacy en ✉️ mail. Daarna 8 smalle en 2 brede vrije blokjes.
            Een blokje zonder plaatje is niet zichtbaar (bij de brede telt ook een korte tekst). Met een link wordt het blokje klikbaar. Randkleur en opmaak blijven zoals ze zijn.
        </div>
        <div class="form-row">
            <div class="field"><label>✉️ Mailadres (envelop en privacy-tekst)</label><input type="email" name="pl_mail" value="<?= htmlspecialchars(cmb_pl_mail($settings)) ?>"></div>
        </div>
        <?php $plRaw = is_array($settings['pl_blocks'] ?? null) ? $settings['pl_blocks'] : []; for ($i = 0; $i < 10; $i++): $pb = is_array($plRaw[$i] ?? null) ? $plRaw[$i] : []; ?>
        <div style="border-top:1px solid rgba(255,255,255,.08); padding:10px 0;">
            <strong><?= $i < 8 ? 'Smal blokje ' . ($i + 1) : 'Breed blokje ' . ($i - 7) ?></strong>
            <div class="form-row">
                <div class="field"><label>Plaatje uploaden</label><input type="file" name="pl_file_<?= $i ?>" accept="image/*"></div>
                <div class="field"><label>of plaatje-link (https://)</label><input type="text" name="pl_img_<?= $i ?>" value="<?= htmlspecialchars($pb['img'] ?? '') ?>"></div>
                <div class="field"><label>Link bij klik (https://, mag leeg)</label><input type="text" name="pl_url_<?= $i ?>" value="<?= htmlspecialchars($pb['url'] ?? '') ?>"></div>
                <?php if ($i >= 8): ?><div class="field" style="max-width:220px;"><label>Korte tekst (max 24 tekens)</label><input type="text" name="pl_text_<?= $i ?>" maxlength="24" value="<?= htmlspecialchars($pb['text'] ?? '') ?>"></div><?php endif; ?>
            </div>
            <?php if (!empty($pb['img'])): ?><label style="font-size:12px;"><img src="<?= htmlspecialchars($pb['img']) ?>" alt="" style="height:28px;vertical-align:middle;border-radius:6px;"> <input type="checkbox" name="pl_del_<?= $i ?>" value="1"> plaatje verwijderen</label><?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
