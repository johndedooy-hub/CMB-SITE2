<?php
/** CMB Beheer: tab "Logo, icoon & zoekmachine". Geladen door cmb-admin.php (tab=site). */
if (!defined('CMB_PART')) { http_response_code(403); exit; }
$siteV = function ($k, $d = '') use ($settings) { return htmlspecialchars((string)($settings[$k] ?? $d), ENT_QUOTES, 'UTF-8'); };
?>
    <div class="card">
        <h2>🖼️ Logo & merk (linksboven in de speler)</h2>
        <div class="cmb-helpbox" style="margin-bottom:12px; font-size:12px; color:#a8aac8; line-height:1.5;">
            Het logo staat in het vierkantje vóór de naam (leeg = het woord CMB). Daarnaast staat de stationsnaam als tekst, of een eigen afbeelding (stationslogo/banner). Upload een bestand of plak een link (png, jpg, webp, gif).
        </div>
        <div class="form-row">
            <div class="field" style="min-width:280px;">
                <label>Logo (vierkant, bv. 200×200 px)</label>
                <input type="text" id="site_logo_url" name="logo_url" value="<?= $siteV('logo_url') ?>" placeholder="cmb-uploads/logo.png of https://...">
                <input type="file" name="upload_site_logo" accept="image/*" style="margin-top:6px;">
            </div>
            <div class="field" style="max-width:300px;">
                <label>Naast het logo tonen</label>
                <select name="brand_mode">
                    <option value="text" <?= ($settings['brand_mode'] ?? 'text') !== 'media' ? 'selected' : '' ?>>Tekst (stationsnaam + regel eronder)</option>
                    <option value="media" <?= ($settings['brand_mode'] ?? '') === 'media' ? 'selected' : '' ?>>Eigen afbeelding (stationslogo/banner)</option>
                </select>
            </div>
            <div class="field" style="min-width:280px;">
                <label>Stationslogo / banner (hoogte wordt 48 px)</label>
                <input type="text" id="site_brand_url" name="brand_media_url" value="<?= $siteV('brand_media_url') ?>" placeholder="cmb-uploads/stationslogo.png of https://...">
                <input type="file" name="upload_site_brand" accept="image/*" style="margin-top:6px;">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>⭐ Icoon in het browsertabblad (favicon)</h2>
        <div class="form-row">
            <div class="field" style="min-width:280px;">
                <label>Icoon (png, vierkant, minimaal 192×192 px; ook .ico of .jpg kan)</label>
                <input type="text" id="site_favicon_url" name="favicon_url" value="<?= $siteV('favicon_url') ?>" placeholder="cmb-uploads/icoon.png of https://...">
                <input type="file" name="upload_site_favicon" accept="image/*,.ico" style="margin-top:6px;">
            </div>
            <div class="field" style="max-width:200px;">
                <label>Themakleur (adresbalk telefoon)</label>
                <input type="color" name="seo_theme_color" value="<?= $siteV('seo_theme_color', '#0c0e1a') ?>">
            </div>
        </div>
    </div>

    <div class="card">
        <h2>🔎 Zoekmachine & delen (Google, WhatsApp, Facebook)</h2>
        <div class="cmb-helpbox" style="margin-bottom:12px; font-size:12px; color:#a8aac8; line-height:1.5;">
            Dit komt in de kop van de pagina. Google gebruikt titel en omschrijving voor het zoekresultaat; het deelplaatje verschijnt als iemand de link deelt. Het kan dagen tot weken duren voor Google het oppakt; meld je site in Google Search Console voor sneller resultaat.
        </div>
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label>Paginatitel (ca. 50–60 tekens; leeg = stationsnaam)</label>
                <input type="text" name="seo_title" maxlength="120" value="<?= $siteV('seo_title') ?>" placeholder="Bijv. Naam Radio – live luisteren | Chazzo Music Box">
            </div>
            <div class="field" style="flex:2;">
                <label>Website-adres van deze speler (https://…, voor Google en delen)</label>
                <input type="text" name="seo_site_url" maxlength="200" value="<?= $siteV('seo_site_url') ?>" placeholder="https://jouwdomein.nl/cmb4/">
            </div>
        </div>
        <div class="form-row">
            <div class="field" style="flex:1;">
                <label>Omschrijving (ca. 140–160 tekens)</label>
                <textarea name="seo_description" rows="3" maxlength="320" placeholder="Luister live naar … 24/7 muziek, nieuws en weer vanuit …"><?= $siteV('seo_description') ?></textarea>
            </div>
        </div>
        <div class="form-row">
            <div class="field" style="flex:2;">
                <label>Zoekwoorden (komma-gescheiden; weegt bij Google nauwelijks mee)</label>
                <input type="text" name="seo_keywords" maxlength="300" value="<?= $siteV('seo_keywords') ?>" placeholder="radio, live radio, internetradio, streek, muziek">
            </div>
            <div class="field" style="min-width:280px;">
                <label>Deelplaatje (ca. 1200×630 px)</label>
                <input type="text" id="site_og_url" name="seo_image_url" value="<?= $siteV('seo_image_url') ?>" placeholder="cmb-uploads/deelplaatje.jpg of https://...">
                <input type="file" name="upload_site_og" accept="image/*" style="margin-top:6px;">
            </div>
            <div class="field" style="max-width:260px;">
                <label>Zichtbaar voor zoekmachines</label>
                <select name="seo_robots">
                    <option value="index" <?= ($settings['seo_robots'] ?? 'index') !== 'noindex' ? 'selected' : '' ?>>Ja (mag gevonden worden)</option>
                    <option value="noindex" <?= ($settings['seo_robots'] ?? '') === 'noindex' ? 'selected' : '' ?>>Nee (testversie, niet tonen)</option>
                </select>
            </div>
        </div>
    </div>
