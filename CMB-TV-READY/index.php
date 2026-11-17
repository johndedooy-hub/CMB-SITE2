<?php
/**
 * CHAZZO MUSIC BOX (CMB) - MODULAIRE SPELER
 * Bestandsnaam: index.php
 */
require_once __DIR__ . '/cmb-player/header.php';
?>
<div class="cmb-app">
<?php
    ob_start(); ?>
            <div class="cmb-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?= $logoUrl ?>" alt="Logo">
                <?php else: ?>
                    CMB
                <?php endif; ?>
            </div>
<?php $tbLogo = ob_get_clean(); ob_start(); ?>
            <div class="cmb-brand-content">
                <?php if ($brandMode === 'media' && $brandMediaUrl): ?>
                    <img src="<?= $brandMediaUrl ?>" alt="Brand" class="cmb-brand-banner">
                <?php else: ?>
                    <div class="cmb-brand-text">
                        <h1><?= $stationTitle ?></h1>
                        <span><?= $stationSub ?></span>
                    </div>
                <?php endif; ?>
            </div>
<?php $tbBrand = ob_get_clean(); ob_start(); ?>
            <div class="cmb-widget-item cmb-w-weather">
                <small style="<?= getStyleCSS($settings['style_lbl_weather'] ?? []) ?>"><?= $lblWeather ?></small>
                <span id="val-weather" style="color:<?= $colorValWeather ?>; <?= getStyleCSS($settings['style_weather_value'] ?? []) ?>">--°C</span>
                <div class="cmb-widget-sub" style="<?= getStyleCSS($settings['style_weather_city'] ?? []) ?>"><?= $weatherCityDisplay ?></div>
            </div>
<?php $tbWeather = ob_get_clean(); ob_start(); ?>
            <div class="cmb-widget-item cmb-w-time">
                <small style="<?= getStyleCSS($settings['style_lbl_time'] ?? []) ?>"><?= $lblTime ?></small>
                <span id="val-time" style="color:<?= $colorValTime ?>; <?= getStyleCSS($settings['style_time_value'] ?? []) ?>">--:--:--</span>
                <div class="cmb-widget-sub" id="val-date" style="<?= getStyleCSS($settings['style_date_line'] ?? ['color'=>'#a8aac8','size'=>'13px']) ?>">--</div>
            </div>
<?php $tbTime = ob_get_clean(); $trafficBtn = !empty($settings['verkeer_on']) ? '<button type="button" class="cmb-traffic-btn" id="cmb-traffic-btn" aria-label="Verkeer: files en ongelukken" onclick="openTrafficOverlay()" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 6c-.2-.6-.8-1-1.4-1h-11c-.7 0-1.2.4-1.4 1L3 12v8c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-1h12v1c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-8l-2.1-6zM6.5 16c-.8 0-1.5-.7-1.5-1.5S5.7 13 6.5 13s1.5.7 1.5 1.5S7.3 16 6.5 16zm11 0c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/></svg><span class="cmb-traffic-badge" id="cmb-traffic-badge" hidden></span></button>' : '';
$ovBtn = (!empty($settings['ov_on']) && cmb_ns_key($settings) !== '') ? '<button type="button" class="cmb-traffic-btn" id="cmb-ov-btn" aria-label="OV: treinstoringen" onclick="openOvOverlay()" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8 2 4 2.5 4 6v9.5C4 17.4 5.6 19 7.5 19L6 20.5V21h2l2-2h4l2 2h2v-.5L16.5 19c1.9 0 3.5-1.6 3.5-3.5V6c0-3.5-4-4-8-4zM7.5 16c-.8 0-1.5-.7-1.5-1.5S6.7 13 7.5 13s1.5.7 1.5 1.5S8.3 16 7.5 16zm3.5-5H6V6h5v5zm2 0V6h5v5h-5zm3.5 5c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5z"/></svg><span class="cmb-traffic-badge" id="cmb-ov-badge" hidden></span></button>' : '';
$busBtn = !empty($settings['bus_on']) ? '<button type="button" class="cmb-traffic-btn" id="cmb-bus-btn" aria-label="Bus, tram en metro: meldingen" onclick="openBusOverlay()" hidden><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M4 16c0 .9.4 1.7 1 2.2V20c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-1h8v1c0 .6.4 1 1 1h1c.6 0 1-.4 1-1v-1.8c.6-.5 1-1.3 1-2.2V6c0-3.5-3.6-4-8-4S4 2.5 4 6v10zm3.5 1c-.8 0-1.5-.7-1.5-1.5S6.7 14 7.5 14s1.5.7 1.5 1.5S8.3 17 7.5 17zm9 0c-.8 0-1.5-.7-1.5-1.5s.7-1.5 1.5-1.5 1.5.7 1.5 1.5-.7 1.5-1.5 1.5zM6 11V6h12v5H6z"/></svg><span class="cmb-traffic-badge" id="cmb-bus-badge" hidden></span></button>' : '';
$waNum = preg_replace('/\D/', '', (string)($settings['wa_number'] ?? ''));
$waTxt = trim((string)($settings['wa_text'] ?? ''));
$waBtn = (!empty($settings['wa_on']) && $waNum !== '') ? '<a class="cmb-wa-btn" href="https://wa.me/' . $waNum . ($waTxt !== '' ? '?text=' . rawurlencode($waTxt) : '') . '" target="_blank" rel="noopener noreferrer" aria-label="Stuur een WhatsApp-bericht"><svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21c5.46 0 9.91-4.45 9.91-9.91S17.5 2 12.04 2zm0 18.15c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.25-8.24 4.54 0 8.24 3.7 8.24 8.24s-3.7 8.24-8.24 8.24zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.17.25-.64.81-.78.97-.14.17-.29.19-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.17-.25.25-.41.08-.17.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.42h-.48c-.17 0-.43.06-.66.31-.23.25-.87.85-.87 2.07s.89 2.4 1.01 2.56c.12.17 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.14-1.18-.06-.1-.23-.16-.48-.29z"/></svg></a>' : '';
$topSl = $topCustom ? $topSlots : null;
// Tijd en weer op één plek, om en om (zacht in beeld): optie in het beheer
$twSwap = !empty($settings['tw_swap']);
$twSec = max(2, min(60, intval($settings['tw_sec'] ?? 6)));
$tbSwap = '<div class="cmb-widget-item cmb-w-swap cmb-w-time" data-sec="' . $twSec . '">' . str_replace('cmb-widget-item cmb-w-time', 'cmb-widget-item cmb-w-time cmb-sw-a', $tbTime) . str_replace('cmb-widget-item cmb-w-weather', 'cmb-widget-item cmb-w-weather cmb-sw-b', $tbWeather) . '</div>';
// De vier knoppen (auto, trein, bus/tram/metro, WhatsApp) staan samen in één blok; de middentekst staat los daarvan.
$blokInner = $trafficBtn . $ovBtn . $busBtn . $waBtn;
$tbBlok = $blokInner !== '' ? '<div class="cmb-top-blok">' . $blokInner . '</div>' : '';
$tbMail = '<div class="cmb-mid-notify"><span class="cmb-mid-txt">' . $studioMail . '</span></div>';
?>
    <?php if (!$topCustom): ?>
    <header class="cmb-topbar">
        <div class="cmb-brand"><?= $tbLogo . $tbBrand . $tbBlok ?></div>
        <div class="cmb-top-widgets"><?= $twSwap ? $tbSwap : $tbWeather . $tbTime ?></div>
        <?= $tbMail ?>
    </header>
    <?php else:
        $parts = ['logo' => $tbLogo, 'brand' => $tbBrand, 'weather' => $tbWeather, 'time' => $tbTime, 'mail' => $tbMail, 'traffic' => $tbBlok]; ?>
    <header class="cmb-topbar is-slots<?= in_array('traffic', $topOrder, true) ? ' has-blok' : '' ?>" data-sig="<?= htmlspecialchars(json_encode($topOrder), ENT_QUOTES) ?>">
        <?php if ($twSwap) { $parts['time'] = $tbSwap; $parts['weather'] = $tbSwap; if (in_array('time', $topOrder, true)) $topOrder = array_values(array_diff($topOrder, ['weather'])); else { $wi = array_search('weather', $topOrder, true); } } ?>
        <?php foreach ($topOrder as $te): ?><?= $parts[$te] ?><?php endforeach; ?>
    </header>
    <?php endif; ?>

    <main class="cmb-grid<?= !empty($settings['h_swap']) ? ' is-hswap' : '' ?>" id="cmb-grid">
        <!-- LINKER FRAME MET SLIDES EN TICKER -->
        <section class="cmb-panel cmb-stage-left<?= $slideView === 'overzicht' ? ' is-overview ov-bg-' . $ovBg : '' ?>">
            <div class="slide-bg-layer" id="slide-bg-mount"></div>
            <div class="slide-bg-darken" id="slide-darken-mount"></div>

            <div id="cmb-light-mount-left"><?= renderFrameLayers($lightLeft) ?></div>
            
            <div class="cmb-corner-badge"><?= $slideCornerBadge ?></div>
            <div class="cmb-slide-dots" id="cmb-dots-mount"><div class="cmb-dot active"></div></div>

            <div class="cmb-stage-content" id="cmb-stage-content">
                <div class="slide-big-title"></div>
                <div class="slide-sub-title">Steenbergen Live</div>
                <div class="slide-body-txt">Non-stop de beste Dance & Retro Classics</div>
            </div>

            <?php if ($slideView === 'overzicht'): ?><div class="cmb-overview" id="cmb-overview" data-cols="<?= $ovCols ?>" data-size="<?= $ovSize ?>" data-bg="<?= $ovBg ?>" data-dark="<?= $ovDark ?>" data-blur="<?= $ovBlur ?>"></div><?php endif; ?>

            <div class="cmb-slide-bottom-gradient"></div>

            <div class="cmb-frame-ticker">
                <div class="cmb-ticker-inner">
                    <div class="cmb-ticker-text " id="cmb-ticker-mount">
                        <?= htmlspecialchars($tickerPool[0]['val'] ?? '') ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- RECHTERKANT: speler (volledig of compact) en, bij compact, een eigen tekstframe -->
        <div class="cmb-right-col<?= $layoutCompact ? ' is-compact' : '' ?><?= $rightSwap ? ' is-swap' : '' ?>" id="cmb-right-col">
        <!-- RECHTER SPELER FRAME -->
        <section class="cmb-panel cmb-stage-right <?= 'pos-' . $cornerPosition ?><?= $artOverflow ? ' art-overflow' : '' ?><?= $artSpin ? ' art-dir-' . $artSpin : '' ?>">
            <div id="cmb-player-bg"></div>
            
            <div id="cmb-light-mount-right"><?= renderFrameLayers($lightRight) ?></div>
            
            <button class="cmb-corner-play-btn" id="cmb-play-btn" aria-label="Play / Pause">
                <svg id="cmb-play-icon" width="18" height="18" viewBox="0 0 24 24" fill="var(--cyan)"><polygon points="5,3 19,12 5,21"/></svg>
                <svg id="cmb-pause-icon" width="18" height="18" viewBox="0 0 24 24" fill="var(--cyan)" style="display:none;"><rect x="5" y="4" width="4" height="16"/><rect x="15" y="4" width="4" height="16"/></svg>
            </button>

            <div class="cmb-buffer-flyout" id="cmb-buffer-flyout">
                <span class="cmb-buffer-txt" id="cmb-buffer-txt">STARTEN 3s</span>
                <div class="cmb-buffer-track">
                    <div class="cmb-buffer-item lit" id="buf-step-1"></div>
                    <div class="cmb-buffer-item" id="buf-step-2"></div>
                    <div class="cmb-buffer-item" id="buf-step-3"></div>
                    <div class="cmb-buffer-item" id="buf-step-4"></div>
                </div>
            </div>

            <button class="cmb-corner-vol-btn" id="cmb-vol-toggle-btn" aria-label="Volume">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="var(--cyan)"><polygon points="11,5 6,9 2,9 2,15 6,15 11,19"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
            </button>

            <div class="cmb-vol-flyout <?= 'shape-' . $volShape ?>" id="cmb-vol-flyout">
                <button class="cmb-vol-btn-mini" id="cmb-vol-down">-</button>
                <div class="cmb-vol-track">
                    <div class="cmb-vol-item"></div>
                    <div class="cmb-vol-item"></div>
                    <div class="cmb-vol-item"></div>
                    <div class="cmb-vol-item"></div>
                    <div class="cmb-vol-item"></div>
                </div>
                <button class="cmb-vol-btn-mini" id="cmb-vol-up">+</button>
            </div>

            <div class="cmb-speler-header-bar">
                <div class="cmb-onair"><?= htmlspecialchars($settings['text_above_cover'] ?? '● ON AIR') ?></div>
            </div>

            <div class="cmb-art-stage <?= 'fx-' . $artEffect ?>">
                <div class="cmb-art-wrap">
                    <img id="cmb-art-img" class="cmb-art-img" alt="Artwork">
                    <div id="cmb-art-fallback" class="cmb-art-fallback">
                        <h2><?= htmlspecialchars($settings['text_behind_cover'] ?? 'CMB') ?></h2>
                        <p style="<?= getStyleCSS($settings['style_text_behind_sub'] ?? []) ?>"><?= htmlspecialchars($settings['text_behind_sub'] ?? 'CHAZZO MUSIC BOX') ?></p>
                    </div>
                </div>
            </div>

            <div class="cmb-player-bottom-group">
                <div class="cmb-track-info">
                    <div class="cmb-artist" id="cmb-track-artist"><?= $trackFallbackArtist ?></div>
                    <div class="cmb-title is-fallback" id="cmb-track-song"><?= $trackFallback ?></div>
                    <div class="cmb-track-sub"><?= $stationSubNow ?></div>
                </div>
                <div class="cmb-water-wrap" id="cmb-water"></div>
            </div>
        </section>

        <!-- EIGEN TEKSTFRAME (alleen zichtbaar bij Weergave: Compact) -->
        <section class="cmb-panel cmb-own-frame" id="cmb-own-frame" data-sec="<?= $ownSec ?>">
            <div id="cmb-light-mount-own"><?= renderFrameLayers($lightOwn) ?></div>
            <?php foreach ($ownItems as $oIdx => $oi): ?>
            <div class="cmb-own-item<?= $oIdx === 0 ? ' on' : '' ?>">
                <?php if ($oi['media'] !== ''): ?>
                <div class="cmb-own-bg" style="filter:blur(<?= $oi['blur'] ?>px);">
                    <?php if ($oi['video']): ?><video src="<?= htmlspecialchars($oi['media']) ?>" autoplay muted loop playsinline></video>
                    <?php else: ?><img src="<?= htmlspecialchars($oi['media']) ?>" alt=""><?php endif; ?>
                </div>
                <div class="cmb-own-dark" style="background:rgba(0,0,0,<?= $oi['darken'] / 100 ?>);"></div>
                <?php endif; ?>
                <div class="cmb-own-txt" style="<?= getStyleCSS($oi['style']) ?>"><?= nl2br(htmlspecialchars($oi['text'])) ?></div>
            </div>
            <?php endforeach; ?>
        </section>
        </div>
    </main>

    <!-- Teksten links/rechtsonder staan vast boven de PL-balk; alleen de balk klapt open -->
    <footer class="cmb-sub-footer" id="cmb-sub-footer">
        <div id="cmb-bottom-left"><?= $bottomLeft ?></div>
        <div id="cmb-bottom-right"><?= $bottomRight ?></div>
    </footer>

    <div class="cmb-pl-center-wrap">
        <div class="cmb-pl-toggle-btn" onclick="togglePlBar()"><span>⤢</span></div>
        <div class="cmb-platform-bar" id="cmb-pl-bar">
            <a href="javascript:void(0)" onclick="openPrivacyLightbox()" class="cmb-pl-item" title="Veiligheid & Privacy">🔒</a>
            <a href="mailto:<?= htmlspecialchars(cmb_pl_mail($settings)) ?>" class="cmb-pl-item" title="Mail de Studio">✉️</a>
            <?php foreach (cmb_pl_blocks($settings) as $plb): ?>
                <?php $plIn = ($plb['img'] !== '' ? '<img src="' . htmlspecialchars($plb['img']) . '" alt="" loading="lazy">' : '') . ($plb['text'] !== '' ? '<span class="cmb-pl-txt">' . htmlspecialchars($plb['text']) . '</span>' : ''); ?>
                <?php if ($plb['url'] !== ''): ?>
                <a href="<?= htmlspecialchars($plb['url']) ?>" target="_blank" rel="noopener noreferrer" class="cmb-pl-item<?= $plb['wide'] ? ' wide' : '' ?>"><?= $plIn ?></a>
                <?php else: ?>
                <div class="cmb-pl-item<?= $plb['wide'] ? ' wide' : '' ?>"><?= $plIn ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

</div>



    
<?php
require_once CMB_ROOT . '/cmb-player/footer.php';
?>
