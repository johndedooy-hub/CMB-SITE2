<?php /** CMB speler: hoofd-CSS (met instellingen). Geladen door header.php. */ if (!defined('CMB_PART')) { http_response_code(403); exit; } ?>
    <style>

        :root {
            --bg1: #05060d;
            --panel-bg: rgba(18, 20, 39, 0.85);
            --border-color: rgba(255, 255, 255, 0.08);
            --cyan: #63f6ff;
            --ov-acc: <?= $ovAccent ?>;
            --magenta: #ff5bc8;
            --violet: #9d6cff;
            --gold: #ffd166;
            --white: #f7f7ff;
            --muted: #a8aac8;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            width: 100%; min-height: 100vh; background: #05060d; color: var(--white);
            font-family: 'Segoe UI', system-ui, 'Noto Color Emoji', sans-serif;
        }
        /* Maar één scrollende laag: overflow-x hidden op html én body maakt van body een eigen scrollgebied,
           wat op telefoons het scrollen laat terugspringen. clip knipt zijwaarts af zonder zo'n gebied. */
        /* Geen zichtbare schuifbalken: dit is een speler, geen site om te scrollen (scrollen met wiel/aanraking blijft werken) */
        html, body, * { scrollbar-width: none; -ms-overflow-style: none; }
        html::-webkit-scrollbar, body::-webkit-scrollbar, *::-webkit-scrollbar { width: 0; height: 0; display: none; }
        html { overflow-x: hidden; overflow-y: auto; -webkit-text-size-adjust: 100%; }
        body { overflow-x: clip; overscroll-behavior-y: none; }

        body {
            background: radial-gradient(circle at 10% 20%, #12333b 0, transparent 35%),
                        radial-gradient(circle at 90% 80%, #32142f 0, transparent 35%),
                        linear-gradient(135deg, #05060d, #120d27, #070812);
            display: flex; justify-content: center; align-items: flex-start;
            padding: 4px 12px 12px 12px; position: relative;
        }

        #cmb-global-bg { position: fixed; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; }
        #cmb-global-bg img, #cmb-global-bg video { width: 100%; height: 100%; object-fit: cover; }
        .bg-dark-overlay { position: absolute; inset: 0; background: #000; pointer-events: none; z-index: 1; }

        #cmb-player-bg { position: absolute; inset: 0; z-index: 0; overflow: hidden; pointer-events: none; border-radius: inherit; }
        #cmb-player-bg img, #cmb-player-bg video { width: 100%; height: 100%; object-fit: cover; }

        /* Slide Achtergrond Lagen */
        .slide-bg-layer { position: absolute; inset: 0; z-index: 1; overflow: hidden; border-radius: inherit; pointer-events: none; }
        .slide-bg-layer img, .slide-bg-layer video { width: 100%; height: 100%; object-fit: cover; }
        .slide-bg-darken { position: absolute; inset: 0; background: #000; z-index: 2; pointer-events: none; }

        /* DAKMENU: smalle balk over de hele breedte, met een dunne lijn eronder en | tussen de items */
        .cmb-roof {
            position: fixed; top: 0; left: 0; right: 0; z-index: 9999; height: 24px; box-sizing: border-box;
            display: flex; gap: 0; align-items: center; justify-content: center;
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
        }
        .cmb-roof-links { display: flex; align-items: center; justify-content: center; }
        .cmb-roof-link {
            position: relative; background: none; border: 0; border-radius: 0;
            padding: 0 14px; font-family: 'Inter', 'Segoe UI', 'Noto Color Emoji', sans-serif; font-size: 10.5px; font-weight: 600; color: var(--cyan);
            text-decoration: none; text-transform: uppercase; letter-spacing: 0.14em; transition: 0.2s;
            height: 22px; display: inline-flex; align-items: center; white-space: nowrap;
        }
        .cmb-roof-link span { line-height: 1; }
        .cmb-roof-link:hover { filter: brightness(1.35); }
        .cmb-roof-link + .cmb-roof-link::before, .cmb-zoom-drawer::before {
            content: ''; position: absolute; left: 0; top: 6px; bottom: 6px; width: 1px; background: var(--roof-sep, rgba(99,246,255,.35));
        }

        .cmb-zoom-drawer { position: relative; display: flex; align-items: center; height: 22px; }
        .cmb-zoom-box { display: none; position: absolute; top: 23px; right: 0; background: rgba(5,6,14,0.92); backdrop-filter: blur(10px); border: 1px solid var(--cyan); border-radius: 8px; padding: 4px 8px; gap: 6px; }
        .cmb-zoom-drawer.open .cmb-zoom-box { display: flex; }
        .cmb-zoom-box button { background: transparent; border: 1px solid var(--cyan); color: var(--cyan); width: 22px; height: 22px; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .cmb-zoom-tab {
            background: none; border: 0; padding: 0 14px; cursor: pointer;
            font-size: 13px; color: var(--cyan); height: 22px; display: inline-flex; align-items: center;
        }

        .cmb-app {
            width: 100%; max-width: 1380px; display: flex; flex-direction: column; gap: 10px;
            position: relative; z-index: 2; padding-top: 26px;
        }

        .cmb-topbar { width: 100%; display: flex; justify-content: space-between; align-items: center; position: relative; padding: 0 4px; }
        .cmb-brand { display: flex; align-items: center; gap: 14px; z-index: 3; }
        .cmb-logo {
            width: 48px; height: 48px; border-radius: 14px; background: #0c0e1a;
            display: flex; align-items: center; justify-content: center; font-weight: 900; color: var(--cyan);
            flex-shrink: 0; overflow: hidden;
            <?= getBorderCSS($settings['border_btn_logo'] ?? [], 'rgba(255,255,255,0.14)') ?>
        }
        .cmb-logo img { width: 100%; height: 100%; object-fit: cover; }
        <?php $logoScale = max(100, min(200, intval($settings['logo_scale'] ?? 100))); if ($logoScale > 100): ?>
        @media (min-width: 821px) and (orientation: landscape) {
            .cmb-logo { width: min(<?= round(48 * $logoScale / 100, 1) ?>px, var(--cmb-logo-cap, 60px)); height: min(<?= round(48 * $logoScale / 100, 1) ?>px, var(--cmb-logo-cap, 60px)); }
            .cmb-brand-banner { max-height: min(<?= round(48 * $logoScale / 100, 1) ?>px, var(--cmb-logo-cap, 60px)); }
        }
        <?php endif; ?>
        .cmb-brand-content { display: flex; align-items: center; }
        .cmb-brand-banner { max-height: 48px; width: auto; object-fit: contain; border-radius: 8px; }
        .cmb-brand-text h1 { font-size: clamp(18px, 4vw, 22px); font-weight: 900; line-height: 1.1; letter-spacing: 0.05em; <?= getStyleCSS($settings['style_station_title'] ?? []) ?> }
        .cmb-brand-text span { font-size: clamp(10px, 2.5vw, 12px); letter-spacing: 0.15em; text-transform: uppercase; <?= getStyleCSS($settings['style_station_sub'] ?? []) ?> }

        .cmb-ring { position: absolute; inset: 0; border-radius: inherit; padding: var(--rp, 2px); pointer-events: none; box-sizing: border-box; overflow: hidden;
            -webkit-mask-image: linear-gradient(#fff 0 0), linear-gradient(#fff 0 0); -webkit-mask-origin: border-box, content-box; -webkit-mask-clip: border-box, content-box; -webkit-mask-composite: xor;
            mask-image: linear-gradient(#fff 0 0), linear-gradient(#fff 0 0); mask-origin: border-box, content-box; mask-clip: border-box, content-box; mask-composite: exclude; }
        .cmb-ring-layer { position: absolute; inset: 0; background-size: 100% 100%; background-repeat: no-repeat; opacity: 0; transition: opacity 1.6s ease; }
        .cmb-ring-layer.on { opacity: 1; }
        .cmb-topbar.is-slots { display: flex; justify-content: space-between; align-items: center; gap: 18px; }
        .cmb-topbar.is-slots > * { min-width: 0; z-index: 3; }
        .cmb-topbar.is-slots .cmb-mid-notify { position: static; pointer-events: auto; text-align: center; }
        .cmb-topbar.is-slots > .cmb-widget-item { align-items: center; text-align: center; }
        .cmb-topbar.is-slots > .cmb-widget-item:first-child { align-items: flex-start; text-align: left; }
        .cmb-topbar.is-slots > .cmb-widget-item:last-child { align-items: flex-end; text-align: right; }
        .cmb-mid-notify { position: absolute; left: 0; right: 0; text-align: center; font-size: 13px; pointer-events: none; z-index: 2; <?= getStyleCSS($settings['style_studio_mail'] ?? []) ?> }

        .cmb-widget-item.cmb-w-swap { display: grid; }
        .cmb-w-swap > .cmb-widget-item { grid-area: 1 / 1; width: 100%; align-items: inherit; text-align: inherit; transition: opacity .9s ease; }
        .cmb-w-swap > .cmb-sw-b { opacity: 0; pointer-events: none; }
        .cmb-w-swap.is-b > .cmb-sw-a { opacity: 0; pointer-events: none; }
        .cmb-w-swap.is-b > .cmb-sw-b { opacity: 1; pointer-events: auto; }
        .cmb-top-widgets { display: flex; gap: 24px; align-items: flex-start; text-align: right; margin-left: auto; z-index: 3; }
        .cmb-widget-item { display: flex; flex-direction: column; align-items: flex-end; justify-content: flex-start; }
        .cmb-widget-item small { display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 2px; }
        .cmb-widget-item span { font-size: clamp(20px, 4.5vw, 25px); font-weight: 800; line-height: 1.1; }
        .cmb-widget-sub { font-size: 12px; margin-top: 3px; font-weight: 600; white-space: nowrap; text-transform: capitalize; }

        /* Frame-effecten: de laag volgt de ronde hoeken van het frame (ook als het frame "open" staat voor cover-effecten) */
        #cmb-light-mount-left, #cmb-light-mount-right, #cmb-light-mount-own { position: absolute; inset: 0; border-radius: inherit; pointer-events: none; z-index: 15; }
        /* YouTube/Vimeo als achtergrond: vult het vlak, niet aanklikbaar */
        #slide-bg-mount, .cmb-lb-media-wrap { overflow: hidden; }
        .cmb-bg-frame { position: absolute; top: 50%; left: 50%; width: 178%; height: 178%; transform: translate(-50%, -50%); border: 0; pointer-events: none; }
        /* Klik-laag boven een ingesloten widget, zodat de lightbox opent */
        .cmb-widget-embed-frame { position: relative; }
        .cmb-widget-click { position: absolute; inset: 0; z-index: 3; cursor: zoom-in; background: transparent; }
        .cmb-grid { display: flex; gap: 16px; align-items: stretch; width: 100%; }
        .cmb-grid.is-hswap { flex-direction: row-reverse; }
        .cmb-panel {
            background: var(--panel-bg); border-radius: 24px; backdrop-filter: blur(15px);
            overflow: hidden; position: relative; box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            height: 438px;
        }

        
        /* LINKER FRAME - MET VASTE STRUCTUUR VOOR SLIDE EN TICKER */
        .cmb-stage-left {
            flex: 1 1 58%; min-width: 0; display: flex; flex-direction: column;
            justify-content: space-between; padding: 22px; position: relative;
            <?= getStaticFrameBorderCSS($lightLeft) ?>
        }
        
        .cmb-corner-badge {
            position: absolute; top: 16px; left: 18px; z-index: 40;
            background: rgba(5, 6, 14, 0.6); backdrop-filter: blur(8px);
            border-radius: 8px; padding: 4px 10px; font-weight: 900; letter-spacing: 0.1em;
            <?= getStyleCSS($settings['style_corner_badge'] ?? []) ?>
        }

        .cmb-slide-dots { position: absolute; top: 18px; right: 20px; display: flex; gap: 5px; z-index: 40; }
        .cmb-dot { width: 6px; height: 6px; border-radius: 50%; background: rgba(255,255,255,0.25); transition: 0.3s; }
        .cmb-dot.active { background: var(--ov-acc); box-shadow: 0 0 8px var(--ov-acc); }

        .cmb-stage-content {
            flex: 1; display: flex; flex-direction: column; justify-content: center;
            align-items: center; text-align: center; position: relative; z-index: 5;
            transition: opacity 0.3s ease; width: 100%; padding: 0 16px 52px; /* vaste ruimte onderaan: de slidetekst blijft stil staan, ook bij een ticker van twee regels */
        }

        .slide-big-title { font-size: calc(22px * var(--cmb-ts, 1)); font-weight: 900; line-height: 1.15; margin-bottom: 8px; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; text-shadow: 0 2px 4px rgba(0,0,0,0.9); }
        .slide-sub-title { font-size: calc(14px * var(--cmb-ts, 1)); font-weight: 700; margin-bottom: 8px; text-shadow: 0 2px 4px rgba(0,0,0,0.9); }
        .slide-body-txt { white-space: pre-line;  font-size: calc(13px * var(--cmb-ts, 1)); line-height: 1.45; color: #f7f7ff; max-width: 90%; text-shadow: 0 2px 4px rgba(0,0,0,0.9);
            display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: var(--cmb-lines, 9); overflow: hidden; }

        .cmb-slide-bottom-gradient {
            position: absolute; bottom: 0; left: 0; right: 0; height: 90px;
            background: linear-gradient(to bottom, transparent 0%, rgba(5, 6, 14, 0.85) 70%, rgba(5, 6, 14, 0.98) 100%);
            pointer-events: none; z-index: 4;
        }

        .cmb-frame-ticker {
            position: absolute; left: 22px; bottom: 14px; width: calc((100% - 44px) * 0.94); z-index: 6;
            background: transparent !important; border: none !important;
            padding: 4px 0 2px 2px; display: flex; align-items: center; overflow: hidden;
            min-height: 44px;
        }
        .cmb-ticker-inner { width: 100%; overflow: hidden; white-space: normal; }
        .cmb-ticker-text {
            display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
            overflow: hidden; font-weight: 800; line-height: 1.35;
            text-shadow: 0 2px 4px rgba(0,0,0,0.95), 0 0 3px #000;
            transition: opacity 0.35s ease; white-space: normal;
        }
        .ticker-marquee-once { animation: marqueeScroll 18s linear 1; }
        .ticker-marquee { animation: marqueeScroll 18s linear infinite; }
        @keyframes marqueeScroll { 0% { transform: translateX(100%); } 100% { transform: translateX(-100%); } }

        /* RECHTER SPELER FRAME */
        .cmb-stage-right {
            flex: 1 1 42%; min-width: 320px; display: flex; flex-direction: column;
            align-items: center; justify-content: space-between; padding: 18px 22px 14px 22px;
            text-align: center; position: relative;
            <?= getStaticFrameBorderCSS($lightRight) ?>
        }

        .pos-top .cmb-corner-play-btn { top: 16px; left: 18px; bottom: auto; }
        .pos-top .cmb-corner-vol-btn { top: 16px; right: 18px; bottom: auto; }
        .pos-top .cmb-vol-flyout { top: 16px; right: 68px; bottom: auto; }

        .pos-bottom .cmb-corner-play-btn { bottom: 16px; left: 18px; top: auto; }
        .pos-bottom .cmb-corner-vol-btn { bottom: 16px; right: 18px; top: auto; }
        .pos-bottom .cmb-vol-flyout { bottom: 16px; right: 68px; top: auto; }

        .cmb-corner-play-btn, .cmb-corner-vol-btn { touch-action: manipulation;
            position: absolute; z-index: 99 !important;
            pointer-events: auto !important; cursor: pointer !important;
            width: 42px; height: 42px; border-radius: 12px;
            background: rgba(12, 14, 26, 0.75); backdrop-filter: blur(10px);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 0 15px rgba(99, 246, 255, 0.25); transition: transform 0.15s, background 0.2s;
        }
        .cmb-corner-play-btn { <?= getBorderCSS($settings['border_btn_play'] ?? [], 'var(--cyan)') ?> }
        .cmb-corner-vol-btn { <?= getBorderCSS($settings['border_btn_vol'] ?? [], 'var(--cyan)') ?> }
        .cmb-corner-play-btn:active, .cmb-corner-vol-btn:active { transform: scale(0.92); }

        .cmb-vol-flyout {
            position: absolute; z-index: 100;
            background: rgba(12, 14, 26, 0.9); backdrop-filter: blur(12px);
            border-radius: 12px; padding: 8px 12px; display: none; align-items: center; gap: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
            animation: fadeInFlyout 0.25s ease;
            <?= getBorderCSS($settings['border_btn_vol'] ?? [], 'var(--cyan)') ?>
        }
        @keyframes fadeInFlyout { from { opacity: 0; transform: translateX(10px); } to { opacity: 1; transform: translateX(0); } }
        .cmb-vol-flyout.open { display: flex; }
        
        .cmb-vol-btn-mini {
            width: 24px; height: 24px; background: rgba(255,255,255,0.08);
            border: 1px solid var(--border-color); color: var(--white);
            border-radius: 6px; cursor: pointer; font-size: 11px;
            display: flex; align-items: center; justify-content: center;
        }
        .cmb-vol-track { display: flex; gap: 6px; align-items: center; }
        
        .cmb-vol-item {
            width: 14px; height: 14px; cursor: pointer; display: flex;
            align-items: center; justify-content: center; font-size: 10px;
            color: rgba(255,255,255,0.25); transition: 0.2s;
        }
        .shape-blocks .cmb-vol-item { border: 1px solid var(--cyan); border-radius: 2px; }
        .shape-blocks .cmb-vol-item.active { background: var(--cyan); box-shadow: 0 0 6px var(--cyan); }
        .shape-dots .cmb-vol-item { border: 1px solid var(--cyan); border-radius: 50%; }
        .shape-dots .cmb-vol-item.active { background: var(--cyan); box-shadow: 0 0 6px var(--cyan); }
        .shape-stars .cmb-vol-item::before { content: "★"; font-size: 12px; }
        .shape-stars .cmb-vol-item.active { color: var(--gold); text-shadow: 0 0 6px var(--gold); }
        .shape-hearts .cmb-vol-item::before { content: "♥"; font-size: 12px; }
        .shape-hearts .cmb-vol-item.active { color: var(--magenta); text-shadow: 0 0 6px var(--magenta); }

        .cmb-speler-header-bar {
            position: absolute; top: 16px; left: 66px; right: 66px;
            height: 42px; display: flex; align-items: center; justify-content: center;
            z-index: 20; pointer-events: none;
        }
        .cmb-onair {
            font-size: 10px; letter-spacing: 0.18em; font-weight: 700;
            background: rgba(5,6,14,0.5); backdrop-filter: blur(6px);
            padding: 6px 14px; border-radius: 20px; border: 1px solid rgba(255,255,255,0.06);
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;
            <?= getStyleCSS($settings['style_text_above_cover'] ?? []) ?>
        }

                .cmb-art-stage {
            width: min(186px, 48%); aspect-ratio: 1; position: relative;
            display: flex; align-items: center; justify-content: center;
            margin-top: 64px; margin-bottom: 0;
            z-index: 2;
        }
        .cmb-art-stage::before, .cmb-art-stage::after {
            content: ""; position: absolute; pointer-events: none !important; z-index: 0;
            border-radius: 50%;
        }

.cmb-art-wrap { width: 100%; height: 100%; border-radius: 18px; background: #000; overflow: hidden; position: relative; z-index: 2; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
        .cmb-art-img { width: 100%; height: 100%; object-fit: cover; display: none; }
        .cmb-art-fallback { width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: radial-gradient(circle, #1a153a, #05060d); }
        .cmb-art-fallback h2 { font-size: 26px; font-weight: 900; <?= getStyleCSS($settings['style_text_behind_cover'] ?? []) ?> }
        .cmb-art-fallback p { font-size: 9px; color: var(--muted); letter-spacing: 0.15em; margin-top: 4px; }

        .cmb-player-bottom-group {
            width: 100%; display: flex; flex-direction: column; align-items: center;
            margin-top: auto; padding-top: 10px; position: relative; z-index: 2;
        }

        .cmb-track-info { width: 100%; margin-bottom: 4px; }
        .cmb-artist { font-size: 19px; font-weight: 800; color: var(--cyan); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; letter-spacing: 0.02em; }
        .cmb-title { font-size: 15px; color: var(--white); margin-top: 3px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cmb-track-sub { font-size: 10px; letter-spacing: 0.15em; margin-top: 9px; margin-bottom: 4px; font-weight: 700; text-transform: uppercase; <?= getStyleCSS($settings['style_station_sub_now'] ?? []) ?> }

        .cmb-water-wrap { min-height: 32px; margin-top: 8px; display: flex; flex-wrap: wrap; justify-content: center; align-items: center; }
        .cmb-water-br { flex-basis: 100%; height: 0; }
        .cmb-mid-txt { white-space: pre-line; }
        .cmb-water-letter { font-weight: 900; font-size: 15px; display: inline-block; white-space: pre; letter-spacing: 0.08em; will-change: transform; transform: translateZ(0); }
        
        /* EXTRA TRACKWISSEL LETTER EFFECTEN */

        @keyframes partyOrbit { 0%{transform:rotateY(0) translateY(0) scale(.4);opacity:0} 45%{transform:rotateY(360deg) translateY(-18px) scale(1.35);opacity:1} 100%{transform:rotateY(720deg) translateY(0) scale(1);opacity:1} }
        @keyframes partyTypewriter { 0%{transform:translateY(18px);opacity:0;filter:blur(5px)} 60%{transform:translateY(0);opacity:1;filter:blur(0)} 100%{transform:translateY(0);opacity:1} }
        @keyframes partyPulse { 0%,100%{transform:scale(.8);filter:brightness(.7)} 50%{transform:scale(1.45);filter:brightness(2.1)} }
        @keyframes partyWaveRise { 0%{transform:translateY(45px) rotate(12deg);opacity:0} 60%{transform:translateY(-10px) rotate(-5deg);opacity:1} 100%{transform:translateY(0) rotate(0);opacity:1} }
        @keyframes partyScatter { 0%{transform:translate(var(--scatter-x, -35px),var(--scatter-y, 45px)) rotate(180deg) scale(.2);opacity:0} 65%{transform:translate(0,-5px) rotate(-10deg) scale(1.3);opacity:1} 100%{transform:translate(0,0) rotate(0) scale(1);opacity:1} }
        @keyframes partyRainbowWave {
            0% { transform: translateY(0) scale(1); filter: hue-rotate(0deg); }
            25% { transform: translateY(-16px) scale(1.7); filter: hue-rotate(90deg) drop-shadow(0 0 12px #ff5bc8); }
            50% { transform: translateY(0) scale(1.3); filter: hue-rotate(180deg) drop-shadow(0 0 12px #63f6ff); }
            75% { transform: translateY(-8px) scale(1.5); filter: hue-rotate(270deg) drop-shadow(0 0 12px #ffd166); }
            100% { transform: translateY(0) scale(1); filter: hue-rotate(360deg); }
        }
        @keyframes partyNeonFlicker {
            0% { opacity: .85; filter: none; transform: scale(1); }
            8% { opacity: .2; transform: scale(1.35); filter: drop-shadow(0 0 16px #fff); }
            14% { opacity: 1; filter: drop-shadow(0 0 18px var(--cyan)) brightness(1.8); transform: scale(1.8); }
            22% { opacity: .3; transform: scale(1.5); }
            30% { opacity: 1; filter: drop-shadow(0 0 22px var(--magenta)) brightness(2); transform: scale(2); }
            45% { opacity: .55; transform: scale(1.5); }
            60% { opacity: 1; filter: drop-shadow(0 0 14px var(--gold)); transform: scale(1.3); }
            100% { opacity: .85; filter: none; transform: scale(1); }
        }
        @keyframes partyDropBounce {
            0% { transform: translateY(-60px) scale(2.2); opacity: 0; }
            35% { transform: translateY(0) scale(1.4, .7); opacity: 1; }
            50% { transform: translateY(-22px) scale(.9, 1.3); }
            68% { transform: translateY(0) scale(1.2, .85); }
            82% { transform: translateY(-7px) scale(1); }
            100% { transform: translateY(0) scale(1); opacity: .85; }
        }
        @keyframes partyFlipCoin {
            0% { transform: perspective(500px) rotateY(0deg) scale(1); }
            40% { transform: perspective(500px) rotateY(540deg) scale(2.1) translateY(-14px); filter: drop-shadow(0 0 16px var(--gold)); }
            100% { transform: perspective(500px) rotateY(720deg) scale(1); }
        }
        @keyframes partyFireworks {
            0% { transform: scale(.4) rotate(0deg); filter: blur(4px) brightness(3); opacity: 0; }
            20% { transform: scale(2.8) rotate(25deg); filter: blur(0) brightness(2) drop-shadow(0 0 20px #ff5bc8) drop-shadow(0 0 30px #ffd166); opacity: 1; }
            45% { transform: scale(2.2) rotate(-20deg) translateY(-8px); filter: hue-rotate(120deg) drop-shadow(0 0 20px #63f6ff); }
            70% { transform: scale(1.5) rotate(10deg); filter: hue-rotate(240deg) drop-shadow(0 0 14px #00e676); }
            100% { transform: scale(1) rotate(0deg); filter: none; opacity: .85; }
        }

        @keyframes partySupernova {
            0% { transform: scale3d(1, 1, 1) translate3d(0, 0, 0); opacity: 0.8; }
            20% { transform: scale3d(3.4, 3.4, 1) translate3d(0, -32px, 0) rotate(-14deg); opacity: 1; filter: drop-shadow(0 0 25px var(--cyan)) drop-shadow(0 0 35px #fff); z-index: 50; }
            45% { transform: scale3d(2.6, 2.6, 1) translate3d(0, -18px, 0) rotate(10deg); opacity: 1; filter: drop-shadow(0 0 20px var(--magenta)); z-index: 50; }
            70% { transform: scale3d(1.8, 1.8, 1) translate3d(0, -6px, 0) rotate(-4deg); opacity: 1; filter: drop-shadow(0 0 12px var(--gold)); }
            100% { transform: scale3d(1, 1, 1) translate3d(0, 0, 0) rotate(0deg); opacity: 0.85; }
        }

        @keyframes partyHyperdrive {
            0% { transform: perspective(400px) rotateX(0deg) rotateY(0deg) scale(1); }
            30% { transform: perspective(400px) rotateX(360deg) rotateY(180deg) scale(2.8) translate3d(0, -20px, 40px); filter: drop-shadow(0 0 20px var(--cyan)); }
            60% { transform: perspective(400px) rotateX(720deg) rotateY(360deg) scale(1.6) translate3d(0, -5px, 20px); filter: drop-shadow(0 0 15px var(--gold)); }
            100% { transform: perspective(400px) rotateX(720deg) rotateY(360deg) scale(1); }
        }

        @keyframes partyShockwave {
            0% { transform: scale(1); filter: brightness(1); }
            15% { transform: scale(2.4) translate3d(-6px, -12px, 0); filter: brightness(2.5) drop-shadow(0 0 25px #fff) drop-shadow(0 0 40px var(--magenta)); }
            30% { transform: scale(1.9) translate3d(6px, -6px, 0); filter: hue-rotate(90deg) brightness(2); }
            50% { transform: scale(1.4) translate3d(-3px, -2px, 0); filter: hue-rotate(180deg); }
            75% { transform: scale(1.1) translate3d(2px, 0, 0); filter: hue-rotate(270deg); }
            100% { transform: scale(1); filter: brightness(1) hue-rotate(360deg); }
        }
@keyframes partyFlashIn {
            0% { transform: scale(.15); opacity: 0; }
            16% { transform: scale(1.18); opacity: 1; }
            30% { transform: scale(1); opacity: 1; }
            72% { transform: scale(1); opacity: 1; }
            100% { transform: scale(.55); opacity: 0; }
        }
        #cmb-flash-layer { position: fixed; inset: 0; z-index: 9500; pointer-events: none; display: flex; align-items: center; justify-content: center; flex-wrap: nowrap; overflow: hidden; }
        #cmb-flash-layer span { display: inline-block; white-space: pre; font-weight: 900; will-change: transform, opacity; }
@keyframes partyPopAnim {
            0% { transform: scale3d(1, 1, 1) translate3d(0, 0, 0) rotate(0deg); opacity: 0.8; }
            20% { transform: scale3d(2.4, 2.4, 1) translate3d(0, -12px, 0) rotate(-8deg); opacity: 1; filter: drop-shadow(0 0 10px var(--cyan)); }
            45% { transform: scale3d(2.1, 2.1, 1) translate3d(0, -5px, 0) rotate(8deg); opacity: 1; filter: drop-shadow(0 0 12px var(--magenta)); }
            70% { transform: scale3d(1.7, 1.7, 1) translate3d(0, -6px, 0) rotate(-4deg); opacity: 1; filter: drop-shadow(0 0 8px var(--gold)); }
            85% { transform: scale3d(1.2, 1.2, 1) translate3d(0, -2px, 0) rotate(2deg); opacity: 0.95; }
            100% { transform: scale3d(1, 1, 1) translate3d(0, 0, 0) rotate(0deg); opacity: 0.85; }
        }

        .cmb-pl-center-wrap { display: flex; flex-direction: column; align-items: center; margin-top: 6px; position: relative; z-index: 10; width: 100%; }
        .cmb-pl-toggle-btn { width: 22px; height: 22px; background: rgba(5,6,14,0.8); border: 1px solid var(--ov-acc); transform: rotate(45deg); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .cmb-pl-toggle-btn span { transform: rotate(-45deg); font-size: 11px; color: var(--ov-acc); }

        .cmb-platform-bar {
            display: none; gap: 8px; margin-top: 10px; background: transparent; padding: 4px;
            align-items: center; justify-content: center; flex-wrap: wrap; max-width: 100%;
        }
        .cmb-platform-bar.open { display: flex; }
        .cmb-pl-item {
            width: 42px; height: 42px; border-radius: 12px; background: rgba(12, 14, 26, 0.7);
            display: flex; align-items: center; justify-content: center; color: #fff; text-decoration: none; font-size: 16px;
            transition: transform 0.2s, border-color 0.2s;
            <?= getBorderCSS($settings['border_btn_pl'] ?? [], 'rgba(255,255,255,0.1)') ?>
        }
        .cmb-pl-item:hover { transform: translateY(-3px); border-color: var(--cyan); }
        .cmb-pl-item { position: relative; overflow: hidden; }
        .cmb-pl-item img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .cmb-pl-item .cmb-pl-txt { position: relative; z-index: 1; text-shadow: 0 1px 3px #000, 0 0 2px #000; padding: 0 4px; text-align: center; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
        .cmb-pl-item.wide { width: 78px; font-size: 10px; font-weight: 900; letter-spacing: 0.08em; }

        .cmb-sub-footer {
            display: flex; justify-content: space-between; align-items: center;
            width: 100%; padding: 4px 6px; font-size: 11px; color: var(--muted);
            letter-spacing: 0.1em; margin-top: 4px; transition: transform 0.35s ease;
        }
        .cmb-sub-footer.shifted { transform: none; } /* teksten blijven staan als de PL-balk opengaat */
        #cmb-bottom-left { flex: 0 0 50%; max-width: 50%; text-align: left; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= getStyleCSS($settings['style_bottom_left'] ?? []) ?> }
        #cmb-bottom-right { flex: 0 0 50%; max-width: 50%; text-align: right; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; <?= getStyleCSS($settings['style_bottom_right'] ?? []) ?> }

        @media (max-width: 820px) {
            body { padding: 4px 6px; align-items: flex-start !important; }
            .cmb-roof { position: absolute !important; top: 0 !important; overflow: hidden; }
            .cmb-roof-link, .cmb-zoom-tab { padding: 0 9px; letter-spacing: 0.08em; }
            .cmb-app { width: 100% !important; padding-top: 32px !important; gap: 8px; }
            .cmb-grid { flex-direction: column !important; gap: 12px; width: 100% !important; }
            .cmb-stage-left {
                height: 255px !important; min-height: 255px !important; max-height: 255px !important;
                padding: 14px 16px 8px 16px !important; overflow: hidden !important;
            }
            .cmb-stage-content {
                max-height: 233px !important; overflow: hidden !important; width: 100%;
            }
            .cmb-frame-ticker { left: 16px !important; bottom: 4px !important; width: calc((100% - 32px) * 0.94) !important; }
            .slide-title-txt {
                font-size: clamp(14px, 4.2vw, 18px) !important;
                margin-bottom: 4px !important;
            }
            .slide-sub-txt {
                font-size: clamp(11px, 3.2vw, 13px) !important;
                margin-bottom: 6px !important;
            }
            .slide-body-txt {
                white-space: pre-line;
                line-height: 1.4 !important;
                display: -webkit-box !important;
                -webkit-line-clamp: var(--cmb-lines, 4) !important;
                -webkit-box-orient: vertical !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
            }
            .cmb-stage-right { height: auto !important; min-height: 400px !important; padding: 20px 14px !important; }
            .cmb-topbar { display: flex !important; flex-direction: column !important; align-items: stretch !important; width: 100% !important; gap: 6px; }
            .cmb-topbar.is-slots { flex-direction: row !important; flex-wrap: wrap !important; align-items: center !important; justify-content: space-between !important; gap: 8px 12px; }
            .cmb-topbar.is-slots .cmb-logo { order: 1; }
            .cmb-topbar.is-slots .cmb-brand-content { order: 1; flex: 1 1 calc(100% - 66px); }
            .cmb-topbar.is-slots > .cmb-widget-item { order: 2; flex: 1 1 40%; }
            .cmb-topbar.is-slots > .cmb-widget-item { align-items: flex-start !important; text-align: left !important; }
            .cmb-topbar.is-slots > .cmb-widget-item ~ .cmb-widget-item { align-items: flex-end !important; text-align: right !important; }
            .cmb-topbar.is-slots .cmb-mid-notify { order: 5; flex: 1 1 100%; width: 100% !important; }
            .cmb-topbar.is-slots .cmb-w-weather { order: 2; }
            .cmb-topbar.is-slots .cmb-top-blok { order: 3; flex: 0 0 auto; }
            .cmb-topbar.is-slots .cmb-w-time { order: 4; }
            .cmb-topbar.is-slots.has-blok > .cmb-widget-item { flex: 0 1 auto; min-width: min-content; }
            .cmb-topbar.is-slots.has-blok > .cmb-w-weather { margin-right: auto; }
            .cmb-topbar.is-slots.has-blok > .cmb-w-time { margin-left: auto; }
            .cmb-topbar.is-slots.has-blok > .cmb-w-time { align-items: flex-end !important; text-align: right !important; }
            .cmb-top-blok .cmb-traffic-btn, .cmb-top-blok .cmb-wa-btn, .cmb-mid-notify .cmb-traffic-btn, .cmb-mid-notify .cmb-wa-btn { width: 28px; height: 26px; margin-left: 6px; }
            .cmb-top-blok { gap: 6px; }
            .cmb-brand { order: 1 !important; width: 100% !important; justify-content: flex-start !important; }
            .cmb-top-widgets { order: 2 !important; width: 100% !important; display: flex !important; justify-content: flex-end !important; text-align: right !important; margin-left: 0 !important; }
            .cmb-mid-notify { order: 5 !important; position: static !important; width: 100% !important; text-align: center !important; margin-top: 2px !important; pointer-events: auto !important; }
            .cmb-platform-bar { gap: 6px !important; }
            .cmb-pl-item { width: 36px !important; height: 36px !important; font-size: 14px !important; }
            .cmb-pl-item.wide { width: 68px !important; font-size: 9px !important; }
            .pos-top .cmb-vol-flyout { top: 62px; right: 18px; }
            .pos-bottom .cmb-vol-flyout { bottom: 62px; right: 18px; }
        }
    
        
        /* RECHTERKANT: volledige speler of compacte speler + eigen tekstframe */
        :root { --cmb-ch: <?= intval($compactH) ?>px; }
        .cmb-right-col { flex: 1 1 42%; min-width: 320px; display: flex; flex-direction: column; gap: 16px; height: 438px; }
        .cmb-right-col .cmb-stage-right { flex: 1 1 auto; min-width: 0; width: 100%; }
        .cmb-own-frame { display: none; flex: 1 1 auto; min-height: 0; height: auto; align-items: center; justify-content: center; text-align: center; padding: 14px 18px; <?= getStaticFrameBorderCSS($lightRight) ?> }
        .cmb-own-item { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; text-align: center; padding: 14px 18px; opacity: 0; transition: opacity 1.2s ease; z-index: 0; pointer-events: none; }
        .cmb-own-item.on { opacity: 1; z-index: 1; }
        .cmb-own-bg { position: absolute; inset: -20px; z-index: 0; overflow: hidden; }
        .cmb-own-bg img, .cmb-own-bg video { width: 100%; height: 100%; object-fit: cover; }
        .cmb-own-dark { position: absolute; inset: 0; z-index: 1; pointer-events: none; }
        .cmb-own-txt { position: relative; z-index: 2; white-space: pre-line; max-width: 100%; line-height: 1.3; font-weight: 800; text-shadow: 0 2px 6px rgba(0,0,0,0.8); overflow: hidden; }
        .cmb-right-col.is-swap .cmb-stage-right { order: 2; }
        .cmb-right-col.is-swap .cmb-own-frame { order: 1; }
        .cmb-right-col.is-compact .cmb-own-frame { display: flex; }
        .cmb-right-col.is-compact .cmb-stage-right { --cmb-tx: calc(62px + var(--cmb-ch) - 70px + 16px); flex: 0 0 var(--cmb-ch); height: var(--cmb-ch) !important; min-height: 0 !important; padding: 0 !important; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-corner-play-btn { top: 12px !important; left: 12px !important; bottom: auto !important; right: auto !important; width: 38px; height: 38px; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-corner-vol-btn { bottom: 12px !important; left: 12px !important; top: auto !important; right: auto !important; width: 38px; height: 38px; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-vol-flyout { bottom: 12px !important; left: 58px !important; top: auto !important; right: auto !important; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-buffer-flyout { top: 12px !important; left: 58px !important; bottom: auto !important; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-art-stage { position: absolute; left: 62px; top: 50%; transform: translateY(-50%); margin: 0; width: calc(var(--cmb-ch) - 70px); }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-speler-header-bar { top: 10px; left: 64px; right: 12px; width: auto !important; height: auto; justify-content: flex-end; text-align: right; z-index: 3; }
        .cmb-right-col.is-compact .cmb-stage-right .cmb-player-bottom-group { position: absolute; left: var(--cmb-tx); right: 12px; top: 44px; bottom: 8px; width: auto !important; margin: 0; padding: 0; justify-content: space-between; }
        .cmb-right-col.is-compact .cmb-track-info { margin: auto 0; }
        .cmb-right-col.is-compact .cmb-track-sub { display: none; }
        .cmb-right-col.is-compact .cmb-water-wrap { margin-top: 0; height: auto; min-height: 26px; flex-wrap: wrap; justify-content: flex-end; margin-left: calc(64px - var(--cmb-tx)); width: calc(100% + var(--cmb-tx) - 64px); max-width: none; position: relative; z-index: 3; }
        .cmb-right-col.is-compact .cmb-water-letter { font-size: 13px; }
        .cmb-right-col.is-compact .cmb-artist { font-size: 17px; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .cmb-right-col.is-compact .cmb-title { font-size: 14px; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        @media (max-width: 820px) {
            .cmb-right-col { height: auto !important; min-width: 0; }
            .cmb-right-col.is-compact .cmb-own-frame { min-height: 150px; }
        }

        /* BLOKJES (verkeer en WhatsApp) */
        .cmb-wa-btn { display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; margin-left: 10px; width: 34px; height: 30px; border-radius: 9px; background: rgba(12,14,26,.7); border: 1px solid var(--ov-acc, #63f6ff); color: var(--ov-acc, #63f6ff); cursor: pointer; pointer-events: auto; text-decoration: none; }
        .cmb-top-blok { display: flex; align-items: center; justify-content: center; pointer-events: auto; gap: 8px; padding: 6px 10px; border-radius: 16px; background: rgba(0,0,0,.22); }
        .cmb-brand .cmb-top-blok { margin-left: 14px; }
        .cmb-top-blok .cmb-traffic-btn, .cmb-top-blok .cmb-wa-btn { margin-left: 0; }
        /* VERKEER */
        .cmb-traffic-btn { position: relative; display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; margin-left: 10px; width: 34px; height: 30px; border-radius: 9px; background: rgba(12,14,26,.7); border: 1px solid var(--ov-acc, #63f6ff); color: var(--ov-acc, #63f6ff); cursor: pointer; pointer-events: auto; padding: 0; }
        .cmb-traffic-btn[hidden] { display: none; }
        .cmb-traffic-badge { position: absolute; top: -8px; right: -8px; min-width: 18px; height: 18px; border-radius: 9px; padding: 0 4px; background: #000; color: var(--ov-acc); border: 1px solid var(--ov-acc); font: 800 11px/16px 'Montserrat', sans-serif; text-align: center; }
        .cmb-traffic-badge[hidden] { display: none; }
        .cmb-pwa-banner { position: fixed; left: 12px; right: 12px; bottom: 12px; z-index: 99995; max-width: 520px; margin: 0 auto; display: grid; grid-template-columns: 64px 1fr; gap: 12px 14px; align-items: center; padding: 14px; border-radius: 18px; background: rgba(8,28,30,.92); border: 1px solid var(--ov-acc); box-shadow: 0 8px 30px rgba(0,0,0,.5); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); font-family: 'Montserrat', sans-serif; }
        .cmb-pwa-banner[hidden] { display: none; }
        .cmb-pwa-ic { width: 64px; height: 64px; border-radius: 14px; border: 2px solid var(--gold); }
        .cmb-pwa-tx b { display: block; font-size: 16px; color: #fff; }
        .cmb-pwa-tx span { display: block; font-size: 13px; color: var(--muted); margin-top: 3px; }
        .cmb-pwa-go { grid-column: 1 / -1; border: 0; border-radius: 12px; padding: 12px; font: 800 15px 'Montserrat', sans-serif; background: var(--ov-acc); color: #04201e; cursor: pointer; }
        .cmb-pwa-go[hidden] { display: none; }
        .cmb-pwa-x { position: absolute; top: 8px; right: 8px; width: 30px; height: 30px; border-radius: 50%; border: 0; background: rgba(255,255,255,.12); color: #fff; cursor: pointer; }
        .cmb-info-panel.cmb-traffic-panel { max-width: 640px; }
        .cmb-info-panel.cmb-reisinfo-panel { max-width: 940px; padding: 18px 0 10px; }
        .cmb-reisinfo-panel iframe { width: 100%; height: calc(100vh - 60px); border: 0; background: transparent; display: block; }
        .cmb-traffic-list { display: flex; flex-direction: column; gap: 10px; }
        .cmb-traffic-row { display: flex; gap: 12px; align-items: center; padding: 10px 12px; border-radius: 12px; background: rgba(12,14,26,.65); border-left: 4px solid #ff5470; color: #fff; }
        .cmb-traffic-row.is-file { border-left-color: #ffb300; }
        .cmb-road { flex: 0 0 auto; min-width: 52px; text-align: center; font: 800 14px/1 'Montserrat', sans-serif; padding: 8px 6px; border-radius: 8px; background: #c62828; color: #fff; border: 2px solid rgba(255,255,255,.8); }
        .cmb-road.n { background: #ffd600; color: #111; border-color: #111; }
        .cmb-road.e { background: #2e7d32; }
        .cmb-traffic-txt { font-size: 13px; line-height: 1.35; }
        .cmb-traffic-txt { flex: 1 1 auto; min-width: 0; }
        .cmb-traffic-txt b { display: block; font-size: 15px; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; }
        .cmb-traffic-sub { display: block; color: #c9cbe0; margin-top: 2px; }
        .cmb-delay { flex: 0 0 auto; padding: 7px 10px; border-radius: 8px; background: rgba(255,179,0,.16); color: #ffc83d; font: 800 14px/1 'Montserrat', sans-serif; white-space: nowrap; }
        .cmb-traffic-empty { text-align: center; color: #a8aac8; padding: 20px 0; font-size: 14px; }
        .cmb-traffic-src { margin: 14px 4px 0; font-size: 11px; color: #8a8ea8; }
        /* INFO-OVERZICHT (glaslaag); de lightbox van een slide ligt er bovenop */
        .cmb-info-overlay { position: fixed; inset: 0; z-index: 99990; display: none; background: rgba(5,6,14,.55); backdrop-filter: blur(18px); -webkit-backdrop-filter: blur(18px); padding: 16px; overflow-y: auto; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; }
        .cmb-info-overlay.open { display: block; animation: lbFadeIn .2s ease; }
        .cmb-info-panel { position: relative; max-width: 1000px; margin: 0 auto; padding: 18px 8px 30px; }
        .cmb-info-panel .cmb-lightbox-close { position: absolute; top: 4px; right: 4px; }
        .cmb-info-head { text-align: center; margin: 6px 40px 18px; }
        .cmb-info-l1 { color: var(--gold); font-weight: 800; font-size: 14px; letter-spacing: .04em; }
        .cmb-info-l2 { color: #fff; font-weight: 600; font-size: 14px; margin-top: 4px; }
        .cmb-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 12px; }
        .cmb-info-cat { grid-column: 1 / -1; font-size: 11px; font-weight: 800; letter-spacing: .18em; color: var(--ov-acc); opacity: .85; margin: 8px 2px -4px; }
        .cmb-info-card { cursor: pointer; min-height: 96px; border-radius: 14px; padding: 12px; background: rgba(12,14,26,.65) center/cover; border: 1px solid rgba(255,255,255,.14); display: flex; flex-direction: column; justify-content: flex-end; transition: transform .2s, border-color .2s; }
        .cmb-info-card:hover { transform: translateY(-3px); border-color: var(--ov-acc); }
        .cmb-info-card-t { color: #fff; font-weight: 800; font-size: 14px; line-height: 1.25; text-shadow: 0 1px 4px #000; }
        .cmb-info-card-s { color: var(--ov-acc); font-weight: 600; font-size: 11px; margin-top: 3px; text-shadow: 0 1px 3px #000; }
        .cmb-info-card-f { color: rgba(255,255,255,.72); font-weight: 500; font-size: 10px; margin-top: 4px; text-shadow: 0 1px 3px #000; }

        /* TEGELOVERZICHT in het linker frame (Slide-weergave: Overzicht). Het tv-adres (tv.php) houdt de slideshow. */
        .cmb-stage-left.is-overview { cursor: default; }
        .cmb-stage-left.is-overview #cmb-stage-content, .cmb-stage-left.is-overview .cmb-slide-dots, .cmb-stage-left.is-overview .slide-bg-layer:not(.tk-bg), .cmb-stage-left.is-overview .slide-bg-darken:not(.tk-dk) { display: none !important; }
        .cmb-overview { --ov-cols: <?= $ovCols ?>; --ov-h: <?= ['s' => 64, 'm' => 92, 'l' => 128][$ovSize] ?>px; --ov-t: <?= ['s' => 9, 'm' => 11, 'l' => 13][$ovSize] ?>px; --ov-s: <?= ['s' => 8, 'm' => 10, 'l' => 11][$ovSize] ?>px; --ov-f: <?= ['s' => 7.5, 'm' => 9, 'l' => 10][$ovSize] ?>px;
            position: absolute; left: 0; right: 0; top: 0; bottom: 50px; z-index: 6; padding: 12px 16px 6px; overflow-y: auto; scrollbar-width: none;
            display: grid; grid-template-columns: repeat(var(--ov-cols), minmax(0, 1fr)); gap: 10px; align-content: start; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; }
        .cmb-overview::-webkit-scrollbar { display: none; }
        .cmb-ov-cat { grid-column: 1 / -1; font-size: 10px; font-weight: 800; letter-spacing: .18em; color: var(--ov-acc); opacity: .85; margin: 4px 2px -4px; }
        /* De eerste kop schuift naast het hoekbadgetje op, en de tegels beginnen er direct onder (maat wordt door de speler gemeten) */
        .cmb-ov-cat:first-child { margin-left: var(--ov-bx, 0px); min-height: max(0px, calc(var(--ov-bh, 0px) - 16px)); display: flex; align-items: center; }
        /* Achtergrond van het overzicht: één achtergrond achter alles (donker en blur instelbaar) of geen */
        .cmb-stage-left.is-overview .slide-bg-layer { transition: opacity .45s ease; }
        .cmb-stage-left.is-overview.ov-bg-frame .slide-bg-layer:not(.tk-bg), .cmb-stage-left.is-overview.ov-bg-frame .slide-bg-darken:not(.tk-dk), .cmb-stage-left.is-overview.ov-bg-both .slide-bg-layer:not(.tk-bg), .cmb-stage-left.is-overview.ov-bg-both .slide-bg-darken:not(.tk-dk) { display: block !important; }
        .cmb-stage-left.is-overview.ov-bg-frame .slide-bg-layer:not(.tk-bg) > *, .cmb-stage-left.is-overview.ov-bg-both .slide-bg-layer:not(.tk-bg) > * { transform: scale(1.12); }
        .cmb-stage-left.is-overview.ov-bg-frame .cmb-ov-card, .cmb-stage-left.is-overview.ov-bg-none .cmb-ov-card { background-image: none !important; background-color: rgba(8,10,22,.5); -webkit-backdrop-filter: blur(6px); backdrop-filter: blur(6px); }
        .cmb-ov-card { cursor: pointer; min-height: var(--ov-h); border-radius: 12px; padding: 10px; background: rgba(12,14,26,.65) center/cover; border: 1px solid rgba(255,255,255,.14); display: flex; flex-direction: column; justify-content: flex-end; min-width: 0; overflow: hidden; transition: transform .2s, border-color .2s; }
        .cmb-ov-card:hover { transform: translateY(-2px); border-color: var(--ov-acc); }
        /* Tekst in een tegel: standaard klein en rustig (de grote kop van de slide wordt hier een kleine kop) */
        .cmb-ov-card .cmb-info-card-t { font-size: var(--ov-t); font-weight: 700; line-height: 1.25; letter-spacing: .01em; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 3; overflow: hidden; }
        .cmb-ov-card .cmb-info-card-s { font-size: var(--ov-s); font-weight: 600; line-height: 1.25; display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: 2; overflow: hidden; }
        .cmb-ov-card .cmb-info-card-f { font-size: var(--ov-f); }
        .cmb-ov-empty { grid-column: 1 / -1; color: #a8aac8; text-align: center; font-size: 13px; padding: 20px 0; }
        @media (max-width: 768px) { .cmb-overview { grid-template-columns: repeat(min(var(--ov-cols), 2), minmax(0, 1fr)); } }

        /* Slide die tijdelijk de plek van frame 3 overneemt (getimed, tijdelijk, nood of bij een nummer) */
        .cmb-takeover { position: absolute; inset: 0; z-index: 30; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; padding: 10px 16px 46px; background: #080a16; overflow: hidden; opacity: 0; transition: opacity .3s ease; border-radius: inherit; }
        .cmb-takeover.on { opacity: 1; }
        .cmb-takeover.in-own { padding: 10px 16px; }
        .cmb-takeover .tk-txt { position: relative; z-index: 5; width: 100%; }
        .cmb-takeover.in-own .slide-big-title { font-size: clamp(14px, 3.2vw, 20px) !important; margin-bottom: 4px; }
        .cmb-takeover.in-own .slide-sub-title { font-size: clamp(11px, 2.4vw, 14px) !important; margin-bottom: 4px; }
        .cmb-takeover.in-own .slide-body-txt { font-size: clamp(10px, 2vw, 12px) !important; -webkit-line-clamp: 3; margin: 0 auto; }

        /* LIGHTBOX & MATGLAS MODALS MET FRAME-EFFECTEN */
        .cmb-lightbox-overlay {
            position: fixed; inset: 0; z-index: 99999;
            background: rgba(5, 6, 14, 0.88); backdrop-filter: blur(22px);
            display: none; align-items: center; justify-content: center;
            padding: 16px; animation: lbFadeIn 0.2s ease; cursor: pointer;
        }
        .cmb-lightbox-overlay.open { display: flex; }
        @keyframes lbFadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .cmb-lightbox-modal {
            background: #090a16; border: 1px solid rgba(99, 246, 255, 0.45);
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.95), 0 0 35px rgba(99, 246, 255, 0.25);
            border-radius: 20px; max-width: 820px; width: 100%; min-height: 400px; max-height: 88vh;
            position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center;
            cursor: pointer;
        }
        .cmb-lb-media-wrap {
            position: absolute; inset: -20px; z-index: 1; overflow: hidden;
            background-size: cover; background-position: center; transition: filter 0.3s;
        }
        .cmb-lb-media-wrap video {
            width: 100%; height: 100%; object-fit: cover;
        }
        .cmb-lb-darken {
            position: absolute; inset: 0; z-index: 2; pointer-events: none;
        }
        .cmb-lb-content {
            position: relative; z-index: 3; padding: 32px 28px; overflow-y: auto; max-height: 82vh;
            pointer-events: none;
        }
        .cmb-lb-content h3 { color: var(--gold); font-size: clamp(20px, 4vw, 26px); margin-bottom: 8px; font-weight: 800; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; text-shadow: 0 2px 10px rgba(0,0,0,0.95), 0 0 20px rgba(0,0,0,0.8); }
        .cmb-lb-content h4 { color: var(--cyan); font-size: clamp(14px, 2.5vw, 17px); margin-bottom: 14px; font-weight: 700; text-shadow: 0 2px 8px rgba(0,0,0,0.95); }
        .cmb-lb-content .lb-body { color: #ffffff; font-size: clamp(13px, 2.2vw, 16px); line-height: 1.6; white-space: pre-line; text-shadow: 0 2px 8px rgba(0,0,0,0.95), 0 1px 3px #000; }
        .cmb-lb-content .lb-body .cmb-weather-tiles-wrap, .cmb-lb-content .lb-body .cmb-rss-list { white-space: normal; }
        .lb-weather-note { margin-top: 12px; white-space: pre-line; }
        .cmb-lb-content p { color: #ffffff; font-size: clamp(13px, 2.2vw, 16px); line-height: 1.6; white-space: pre-line; text-shadow: 0 2px 8px rgba(0,0,0,0.95), 0 1px 3px #000; }

        .cmb-lightbox-close {
            position: absolute; top: 12px; right: 14px; z-index: 10;
            width: 36px; height: 36px; border-radius: 50%;
            background: rgba(255, 23, 68, 0.88); border: 2px solid rgba(255,255,255,0.4); color: #fff;
            font-size: 18px; font-weight: bold; cursor: pointer; display: flex;
            align-items: center; justify-content: center; transition: 0.2s; box-shadow: 0 4px 15px rgba(0,0,0,0.6);
        }
        .cmb-lightbox-close:hover { background: #ff1744; transform: scale(1.1); }
        .cmb-stage-left { cursor: pointer; }



        /* BUFFERING COUNTDOWN FLYOUT (In identieke stijl als volumebalk) */
        .pos-top .cmb-buffer-flyout { top: 16px; left: 68px; bottom: auto; }
        .pos-bottom .cmb-buffer-flyout { bottom: 16px; left: 68px; top: auto; }
        .cmb-buffer-flyout {
            position: absolute; z-index: 99; display: none; align-items: center; gap: 8px;
            background: rgba(12, 14, 26, 0.85); backdrop-filter: blur(12px);
            border: 1px solid rgba(99, 246, 255, 0.35); border-radius: 12px;
            padding: 6px 12px; box-shadow: 0 0 15px rgba(99, 246, 255, 0.2);
            animation: fadeInFlyout 0.2s ease; pointer-events: none;
        }
        .cmb-buffer-flyout.active { display: flex; }
        .cmb-buffer-txt {
            font-size: 11px; font-weight: 800; color: var(--gold);
            font-family: 'Montserrat', 'Noto Color Emoji', sans-serif; letter-spacing: 0.5px;
        }
        .cmb-buffer-track { display: flex; gap: 5px; align-items: center; }
        .cmb-buffer-item {
            width: 12px; height: 12px; border: 1px solid var(--cyan);
            border-radius: 2px; background: transparent; transition: all 0.25s ease;
        }
        .cmb-buffer-item.lit {
            background: var(--cyan); box-shadow: 0 0 8px var(--cyan);
        }
        .cmb-buffer-item.ready {
            background: #00e676; border-color: #00e676; box-shadow: 0 0 10px #00e676;
        }

        /* WEER TILES & WIDGET CARDS */
        .cmb-weather-tiles-wrap {
            width: 100%; display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
            margin-top: 10px;
        }
        .cmb-w-tile {
            background: rgba(18, 20, 39, 0.72); backdrop-filter: blur(12px);
            border: 1px solid rgba(99, 246, 255, 0.3); border-radius: 12px;
            padding: 10px 6px; text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5), inset 0 0 10px rgba(99, 246, 255, 0.05);
            transition: transform 0.2s;
        }
        .cmb-w-tile:hover { transform: translateY(-2px); border-color: var(--cyan); }
        .cmb-w-tile-day {
            font-size: 11px; font-weight: 800; color: var(--gold);
            text-transform: uppercase; margin-bottom: 2px; font-family: 'Montserrat', 'Noto Color Emoji', sans-serif;
        }
        .cmb-w-tile-icon {
            font-size: 26px; line-height: 1.2; margin: 4px 0;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.6));
        }
        .cmb-w-tile-temp {
            font-size: 15px; font-weight: 900; color: #ffffff;
        }
        .cmb-w-tile-min {
            font-size: 11px; color: var(--cyan); font-weight: 600; margin-left: 2px;
        }
        .cmb-w-tile-desc {
            font-size: 10px; color: #a8aac8; margin-top: 3px;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .cmb-w-tile-rain {
            font-size: 10px; color: var(--cyan); font-weight: bold; margin-top: 2px;
        }

        /* RSS-widget */
        .cmb-rss-list { display:flex; flex-direction:column; gap:8px; text-align:left; width:100%; padding:0 6px; }
        .cmb-rss-item { background:rgba(0,0,0,.35); border-left:3px solid var(--cyan); border-radius:8px; padding:7px 10px; }
        .cmb-rss-title { font-weight:800; font-size:calc(13px * var(--cmb-ts, 1)); color:#fff; line-height:1.25; }
        .cmb-rss-desc { font-size:calc(11px * var(--cmb-ts, 1)); color:#c9cbe6; margin-top:3px; line-height:1.3; }
        .cmb-widget-wrap { width:100%; height:100%; min-height:220px; }
        /* WIDGET & EMBED CONTAINER */
        .cmb-widget-embed-frame {
            width: 100%; height: 100%; min-height: 220px; border: none; border-radius: 14px;
            overflow: hidden; background: transparent;
        }



</style>
