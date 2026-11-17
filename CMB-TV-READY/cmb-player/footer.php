<script src="cmb-assets/cmb-player.js?v=<?= file_exists(CMB_ROOT . '/cmb-assets/cmb-player.js') ? filemtime(CMB_ROOT . '/cmb-assets/cmb-player.js') : time() ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.cmbPlayer = new CMBEngine({
                slideView: <?= json_encode($slideView) ?>,
                selfUrl: <?= json_encode($cmbSelf) ?>,
                lightLeftHash: <?= json_encode(md5(json_encode($lightLeft))) ?>,
                lightRightHash: <?= json_encode(md5(json_encode($lightRight))) ?>,
                lightOwnHash: <?= json_encode(md5(json_encode($lightOwn))) ?>,
                streamUrl: <?= json_encode(html_entity_decode($streamUrl, ENT_QUOTES, 'UTF-8')) ?>,
                fallbackUrl: <?= json_encode(html_entity_decode($fallbackUrl, ENT_QUOTES, 'UTF-8')) ?>,
                streamAlt: <?= json_encode(trim((string)($settings['streamUrlAlt'] ?? ''))) ?>,
                fallbackAlt: <?= json_encode(trim((string)($settings['fallbackUrlAlt'] ?? ''))) ?>,
                streamChoices: <?= json_encode($streamChoices) ?>,
                streamFlyout: <?= $streamFlyoutOn ? 'true' : 'false' ?>,
                artRotateMin: <?= intval($artRotateMin) ?>,
                clipSlides: <?= json_encode($clipSlidesJs) ?>,
                timedSlides: <?= json_encode($timedSlidesJs ?? []) ?>,
                streamDelay: <?= json_encode(floatval($slides['timing']['delay'] ?? 0)) ?>,
                serverMs: <?= (int)round(microtime(true) * 1000) ?>,
                tzOffset: <?= intval(date('Z')) ?>,
                artEffectKeys: <?= json_encode($artEffectKeys) ?>,
                artEffect: <?= json_encode($artEffect) ?>,
                sse: <?= cmb_cfg('sse', true) === false ? 'false' : 'true' ?>,
                designHash: <?= json_encode($designHash) ?>,
                themePreview: <?= json_encode($themePreview) ?>,
                fontsUrl: <?= json_encode($fontsUrl) ?>,
                lat: <?= json_encode((string)($weatherLat), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                lon: <?= json_encode((string)($weatherLon), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                bgGlobal: <?= json_encode($bgGlobal ?? []) ?>,
                bgPlayer: <?= json_encode($bgPlayer ?? []) ?>,
                bgHash: <?= json_encode(md5(json_encode($bgGlobal ?? []) . json_encode($bgPlayer ?? []))) ?>,
                slides: <?= json_encode($activeSlides) ?>,
                tickerPool: <?= json_encode($tickerPool) ?>,
                tickerAnimMode: <?= json_encode((string)($tickerAnimMode), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                tickerSpeed: <?= (int)($settings['ticker_speed'] ?? 80) ?>,
                fallbackArtist: <?= json_encode((string)($settings['text_behind_sub'] ?? 'CHAZZO MUSIC BOX')) ?>,
                waterText: <?= json_encode((string)($settings['water_text'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                coverProvider: <?= json_encode((string)($settings['coverApi'] ?? $settings['cover_search_provider'] ?? 'itunes'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                coverProvider2: <?= json_encode((string)(($settings['coverApi2'] ?? '') ?: ($settings['coverApi'] ?? 'itunes')), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                slideScale: <?= max(50, min(250, intval($settings['slide_text_scale'] ?? 100))) ?>,
                slideScaleM: <?= max(50, min(250, intval($settings['slide_text_scale_m'] ?? 100))) ?>,
                slideLines: <?= max(2, min(20, intval($settings['slide_lines'] ?? 9))) ?>,
                slideLinesM: <?= max(2, min(12, intval($settings['slide_lines_m'] ?? 4))) ?>,
                lastfmOn: <?= trim((string)($settings['lastfm_key'] ?? '')) !== '' ? 'true' : 'false' ?>,
                recheckSec: <?= max(20, intval($settings['stream_recheck_sec'] ?? 60)) ?>,
                coverFallbackCentova: <?= !empty($settings['cover_fallback_centova']) ? 'true' : 'false' ?>,
                waterPalette: <?= json_encode($settings['water_palette'] ?? ['#63f6ff', '#ff5bc8', '#ffd166', '#9d6cff']) ?>,
                waterAnim: <?= json_encode((string)($settings['water_anim_mode'] ?? 'bubble'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                waterFontSize: <?= max(8, min(96, intval($settings['style_water']['size'] ?? $settings['water_font_size'] ?? 15))) ?>,
                waterLetterSpacing: <?= max(0, min(20, intval($settings['water_letter_spacing'] ?? 2))) ?>,
                waterColor: <?= json_encode((string)($settings['style_water']['color'] ?? $settings['water_color'] ?? '#63f6ff'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
                waterColor2: <?= json_encode(($settings['style_water']['grad_mode'] ?? 'none') !== 'none' ? ($settings['style_water']['grad_col2'] ?? '') : '') ?>,
                waterShadow: <?= json_encode($settings['style_water']['shadow'] ?? '') ?>,
                waterGlow: <?= max(0, min(30, intval($settings['water_glow'] ?? 8))) ?>,
                waterFont: <?= json_encode($settings['style_water']['font'] ?? $settings['water_font'] ?? 'Arial') ?>,
                waterRandomColors: <?= !empty($settings['water_random_colors']) ? 'true' : 'false' ?>,
                waterCustomColors: <?= json_encode(array_values(array_filter(preg_split('/[,;\s]+/', (string)($settings['water_custom_colors'] ?? '#63f6ff,#ff5bc8,#ffd166,#9d6cff')), static fn($c) => preg_match('/^#[0-9a-fA-F]{6}$/', $c)))) ?>
            });
        });
    </script>



<!-- LIGHTBOX SLIDE (Matglas, achtergrond achter de tekst, elke klik sluit) -->
<div id="cmb-slide-lightbox" class="cmb-lightbox-overlay" onclick="closeSlideLightbox()">
    <div class="cmb-lightbox-modal">
        <button class="cmb-lightbox-close" onclick="closeSlideLightbox()">✕</button>
        <div class="cmb-lb-media-wrap" id="lb-media-wrap"></div>
        <div class="cmb-lb-darken" id="lb-darken"></div>
        <div class="cmb-lb-content">
            <h3 id="lb-title"></h3>
            <h4 id="lb-sub"></h4>
            <div id="lb-body" class="lb-body"></div>
        </div>
    </div>
</div>

<!-- INFO-OVERZICHT: glaslaag met alle rollende slides -->
<div id="cmb-info-overlay" class="cmb-info-overlay" onclick="if(event.target===this)closeInfoOverlay()">
    <div class="cmb-info-panel">
        <button class="cmb-lightbox-close" onclick="closeInfoOverlay()">✕</button>
        <div class="cmb-info-head">
            <div id="cmb-info-station" class="cmb-info-l1"></div>
            <div id="cmb-info-track" class="cmb-info-l2"></div>
        </div>
        <div id="cmb-info-grid" class="cmb-info-grid"></div>
    </div>
</div>

<!-- OV: treinstoringen en werkzaamheden (glaslaag) -->
<div id="cmb-ov-overlay" class="cmb-info-overlay" onclick="if(event.target===this)closeOvOverlay()">
    <div class="cmb-info-panel cmb-traffic-panel">
        <button class="cmb-lightbox-close" onclick="closeOvOverlay()">✕</button>
        <div class="cmb-info-head">
            <div class="cmb-info-l1">OV</div>
            <div id="cmb-ov-sum" class="cmb-info-l2"></div>
        </div>
        <div id="cmb-ov-list" class="cmb-traffic-list"></div>
        <div class="cmb-traffic-src">Bron: NS</div>
    </div>
</div>

<?php if (!empty($settings['pwa_on'])): ?>
<!-- APP INSTALLEREN (PWA) -->
<div id="cmb-pwa-banner" class="cmb-pwa-banner" hidden>
    <button class="cmb-pwa-x" type="button" aria-label="Sluiten" onclick="cmbPwaDismiss()">✕</button>
    <img class="cmb-pwa-ic" src="cmb-pwa-icon.php?s=192" alt="" width="64" height="64">
    <div class="cmb-pwa-tx">
        <b>Zet <?= htmlspecialchars(mb_substr(trim((string)($settings['pwa_name'] ?? '')) ?: $seoTitleRaw, 0, 24)) ?> op je beginscherm</b>
        <span id="cmb-pwa-sub">Luister met één tik, direct vanaf je beginscherm.</span>
    </div>
    <button class="cmb-pwa-go" id="cmb-pwa-go" type="button" onclick="cmbPwaInstall()">⬇ Installeren</button>
</div>
<script>window.CMB_PWA = true;</script>
<?php endif; ?>

<!-- REISINFO: auto, trein en OV in één glaslaag (ook te openen via een link naar cmb-reisinfo.php) -->
<div id="cmb-reisinfo-overlay" class="cmb-info-overlay" onclick="if(event.target===this)closeReisinfoOverlay()">
    <div class="cmb-info-panel cmb-reisinfo-panel">
        <button class="cmb-lightbox-close" onclick="closeReisinfoOverlay()" style="z-index:2">✕</button>
        <iframe id="cmb-reisinfo-frame" title="Reisinfo" allowtransparency="true"></iframe>
    </div>
</div>

<!-- BUS, TRAM, METRO (glaslaag) -->
<div id="cmb-bus-overlay" class="cmb-info-overlay" onclick="if(event.target===this)closeBusOverlay()">
    <div class="cmb-info-panel cmb-traffic-panel">
        <button class="cmb-lightbox-close" onclick="closeBusOverlay()">✕</button>
        <div class="cmb-info-head">
            <div class="cmb-info-l1">BUS, TRAM EN METRO</div>
            <div id="cmb-bus-sum" class="cmb-info-l2"></div>
        </div>
        <div id="cmb-bus-list" class="cmb-traffic-list"></div>
        <div class="cmb-traffic-src">Bron: OpenOV</div>
    </div>
</div>

<!-- VERKEER: files en ongelukken (glaslaag, zelfde stijl als het info-overzicht) -->
<div id="cmb-traffic-overlay" class="cmb-info-overlay" onclick="if(event.target===this)closeTrafficOverlay()">
    <div class="cmb-info-panel cmb-traffic-panel">
        <button class="cmb-lightbox-close" onclick="closeTrafficOverlay()">✕</button>
        <div class="cmb-info-head">
            <div class="cmb-info-l1">VERKEER</div>
            <div id="cmb-traffic-sum" class="cmb-info-l2"></div>
        </div>
        <div id="cmb-traffic-list" class="cmb-traffic-list"></div>
        <div class="cmb-traffic-src">Bron: NDW (Nationale Databank Wegverkeersgegevens)</div>
    </div>
</div>

<!-- PRIVACY & SECURITY LIGHTBOX -->
<div id="cmb-privacy-lightbox" class="cmb-lightbox-overlay" onclick="closePrivacyLightbox(event)">
    <div class="cmb-lightbox-modal" onclick="event.stopPropagation()">
        <button class="cmb-lightbox-close" onclick="closePrivacyLightbox()">✕</button>
        <div class="cmb-lightbox-text-wrap" style="padding:26px;">
            <h2 style="color:var(--cyan); margin-bottom:12px; font-family:'Montserrat',sans-serif;">🔒 Veiligheid & Privacy</h2>
            <p style="color:#f7f7ff; line-height:1.6; font-size:13px; margin-bottom:12px;">Chazzo Music Box (CMB) respecteert uw privacy. Deze visual-radio player verwerkt uitsluitend de technische streams en publieke visual data (weer & nieuwsfeeds).</p>
            <p style="color:#a8aac8; line-height:1.5; font-size:12px; margin-bottom:14px;">Er worden geen trackingcookies van derden geplaatst en er worden geen persoonlijke luistergegevens opgeslagen. De beveiliging voldoet aan actuele SSL/HTTPS webstandaarden.</p>
            <div style="font-size:12px; color:var(--gold); border-top:1px solid rgba(255,255,255,0.08); padding-top:10px;">
                Direct studio contact: <span style="color:var(--cyan);"><?= $plMail ?></span>
            </div>
        </div>
    </div>
</div>

</body>
</html>
