/* CMB speler: CMBEngine (live sync, track polling, weer, klok, slides, audio). Geladen door footer.php. */
class CMBEngine {
    constructor(cfg = {}) {
        this.streamUrl = cfg.streamUrl || '';
        this.fallbackUrl = cfg.fallbackUrl || '';
        this.lat = cfg.lat || '51.58';
        this.lon = cfg.lon || '4.32';
        this.audio = document.getElementById('cmb-core-audio');
        this.isPlaying = false;
        this.currentVol = 3;
        this.lastSong = '';
        this.volTimer = null;
        
        this.slides = Array.isArray(cfg.slides) ? cfg.slides : [];
        this.slideIdx = 0;
        this.slideView = cfg.slideView === 'overzicht' ? 'overzicht' : 'slideshow'; // links: tegeloverzicht of slideshow (tv.php = altijd slideshow)
        this.selfUrl = cfg.selfUrl || 'index.php';
        this.themeQs = cfg.themePreview ? '&theme_preview=' + encodeURIComponent(cfg.themePreview) : ''; // voorbeeld van een thema
        // Op tijd geplande slides (precies op de seconde, rekening houdend met stream-vertraging)
        this.timedSlides = Array.isArray(cfg.timedSlides) ? cfg.timedSlides : [];
        this.streamDelay = parseFloat(cfg.streamDelay) || 0;
        this.clockOffset = cfg.serverMs ? (cfg.serverMs - Date.now()) : 0; // verschil tv-klok en serverklok
        this.tzOffset = parseInt(cfg.tzOffset, 10) || 0;
        this.timedSlide = null; this.timedUntil = 0; this.timedFired = {};
        this.slideTimer = null;

        this.tickerPool = Array.isArray(cfg.tickerPool) ? cfg.tickerPool : [];
        this.tickerIdx = 0;
        this.tickerTimer = null;
        this.tickerAnimMode = cfg.tickerAnimMode || 'fade';
        this.tickerSpeed = cfg.tickerSpeed || 80;

        this.bgGlobal = cfg.bgGlobal || {};
        this.bgPlayer = cfg.bgPlayer || {};
        this.bgHash = cfg.bgHash || '';

        this.coverProvider = cfg.coverProvider || 'itunes';
        this.lastfmOn = !!cfg.lastfmOn;
        this.slideScale = cfg.slideScale || 100; this.slideScaleM = cfg.slideScaleM || 100;
        this.slideLines = cfg.slideLines || 9; this.slideLinesM = cfg.slideLinesM || 4;
        this.coverProvider1 = this.coverProvider;
        this.coverProvider2 = cfg.coverProvider2 || this.coverProvider;
        this.onBackup = false;
        this.streamAlt = cfg.streamAlt || '';     // tweede adres van stream 1 (zelfde zender)
        this.fallbackAlt = cfg.fallbackAlt || ''; // tweede adres van stream 2 (zelfde zender)
        this.altTried = false;
        this.probeFlip = false;
        this.recheckSec = Math.max(20, cfg.recheckSec || 60);
        this.recoverTimer = null;
        this.coverFallbackCentova = !!cfg.coverFallbackCentova;
        this.waterText = cfg.waterText || '';
        this.fallbackArtist = cfg.fallbackArtist || 'CHAZZO MUSIC BOX';
        this.waterPalette = cfg.waterPalette || ['#63f6ff', '#ff5bc8', '#ffd166', '#9d6cff'];
        this.waterAnim = cfg.waterAnim || 'bubble';
        this.waterFontSize = Math.max(8, Math.min(96, parseInt(cfg.waterFontSize || 15, 10)));
        this.waterLetterSpacing = Math.max(0, Math.min(20, parseInt(cfg.waterLetterSpacing ?? 2, 10)));
        this.waterColor = /^#[0-9a-f]{6}$/i.test(cfg.waterColor || '') ? cfg.waterColor : '#63f6ff';
        this.waterGlow = Math.max(0, Math.min(30, parseInt(cfg.waterGlow ?? 8, 10)));
        this.waterFont = /^[\w \-]{1,60}$/.test(cfg.waterFont || '') ? cfg.waterFont : 'Arial';
        this.waterColor2 = /^#[0-9a-f]{6}$/i.test(cfg.waterColor2 || '') ? cfg.waterColor2 : '';
        this.waterShadow = /^#[0-9a-f]{6}$/i.test(cfg.waterShadow || '') ? cfg.waterShadow : '';
        this.waterRandomColors = !!cfg.waterRandomColors;
        this.waterCustomColors = Array.isArray(cfg.waterCustomColors) && cfg.waterCustomColors.length ? cfg.waterCustomColors.filter(c => /^#[0-9a-f]{6}$/i.test(c)) : this.waterPalette;

        
        this.streamChoices = Array.isArray(cfg.streamChoices) ? cfg.streamChoices : [];
        this.streamFlyoutOn = cfg.streamFlyout !== false;
        this.streamWatchdog = null;
        this.artRotateMin = parseInt(cfg.artRotateMin || 0, 10);
        this.artEffectKeys = Array.isArray(cfg.artEffectKeys) ? cfg.artEffectKeys : [];
        this.artRotTimer = null;
        this.lastArtSetting = cfg.artEffect || '';
        this.lastDesignHash = cfg.designHash || '';
        this.fontsUrl = cfg.fontsUrl || '';
        this.clipSlides = Array.isArray(cfg.clipSlides) ? cfg.clipSlides : [];
        this.clipStop = null;
        this.clipOverlay = null;
        this.clipStartTimer = null;
        this.clipDucked = false;
        this.sseOn = cfg.sse !== false && typeof EventSource !== 'undefined';
        this._sseOk = false;

        this.lastLightLeftHash = cfg.lightLeftHash || "";
        this.lastLightRightHash = cfg.lightRightHash || "";
        this.lastLightOwnHash = cfg.lightOwnHash || "";
        // TV-ready branch: serialize network polling on slow embedded browsers.
        this._trackPollBusy = false;
        this._weatherPollBusy = false;
        this._liveSyncBusy = false;
        this.init();
    }

    async init() {
        this.setupBackgrounds();
        this.setupAudioListeners();
        this.setupControls();
        this.pollTrack();
        setInterval(() => this.pollTrack(), 8000);
        this.startClock();
        this.fetchLiveWeather();
        setInterval(() => this.fetchLiveWeather(), 600000);
        this.setupZoom();
        this.setupSlideScale();
        this.renderWaterText(this.waterText);
        this.startSlidesEngine();
        this.startTimedSlides();
        this.startDebugBox();
        this.startTickerEngine();
        this.startLiveSyncEngine();
        this.setupStreamFlyout();
        this.startArtRotation();
        // Vanaf de info-pagina: de speler start meteen (?play=1)
        if (/[?&]play=1/.test(location.search)) setTimeout(() => { if (!this.desiredPlaying) this.togglePlay(); }, 900);
    }

    setupBackgrounds() {
        this.mountBgContainer('cmb-global-bg', this.bgGlobal);
        this.mountBgContainer('cmb-player-bg', this.bgPlayer);
    }

    // Achtergronden: behoud alle opties, maar geef afgedankte videodecoders expliciet vrij.
    releaseBgMedia(node) {
        if (!node) return;
        const media = node.tagName === 'VIDEO' ? [node] : Array.from(node.querySelectorAll ? node.querySelectorAll('video') : []);
        media.forEach(v => {
            try {
                v.pause();
                v.removeAttribute('src');
                v.querySelectorAll('source').forEach(source => source.removeAttribute('src'));
                v.load();
            } catch (e) {}
        });
        if (node.parentNode) node.parentNode.removeChild(node);
    }

    mountBgContainer(elementId, bgData) {
        const el = document.getElementById(elementId);
        if (!el || !bgData) return;
        this.bgTimers = this.bgTimers || {};
        this.bgStates = this.bgStates || {};
        const previous = this.bgStates[elementId];
        if (previous) {
            previous.closed = true;
            previous.timers.forEach(t => clearTimeout(t));
        }
        if (this.bgTimers[elementId]) { clearInterval(this.bgTimers[elementId]); this.bgTimers[elementId] = null; }
        // innerHTML alleen legen na het stoppen van de bestaande videodecoders.
        Array.from(el.children).forEach(node => this.releaseBgMedia(node));
        const state = { closed: false, loading: false, timers: [], active: null };
        this.bgStates[elementId] = state;

        const mode = bgData.mode || 'single';
        const blur = parseInt(bgData.blur || 0, 10);
        const darken = parseInt(bgData.darken || 0, 10);
        const sec = Math.max(2, parseInt(bgData.sec || 8, 10));
        if (darken > 0) {
            const overlay = document.createElement('div');
            overlay.className = 'bg-dark-overlay';
            overlay.style.opacity = (darken / 100);
            el.appendChild(overlay);
        }
        const mediaWrap = document.createElement('div');
        mediaWrap.style.width = '100%';
        mediaWrap.style.height = '100%';
        mediaWrap.style.position = 'relative';
        if (blur > 0) mediaWrap.style.filter = `blur(${blur}px)`;
        el.appendChild(mediaWrap);

        const isVid = url => /\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:[?#].*)?$/i.test(url) || /video\/(?:mp4|webm|x-matroska|quicktime)/i.test(url);
        const makeNode = url => {
            let n;
            if (isVid(url)) {
                n = document.createElement('video');
                n.muted = true; n.loop = true; n.autoplay = true; n.playsInline = true;
                n.preload = 'metadata';
                n.setAttribute('muted', ''); n.setAttribute('playsinline', '');
                n.src = url;
            } else {
                n = document.createElement('img');
                n.decoding = 'async';
                n.src = url;
            }
            n.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 1.6s ease-in-out;';
            return n;
        };
        const later = (fn, delay) => {
            const t = setTimeout(() => {
                state.timers = state.timers.filter(v => v !== t);
                if (!state.closed) fn();
            }, delay);
            state.timers.push(t);
        };
        const show = url => {
            if (state.closed || state.loading) return;
            if (!url) {
                Array.from(mediaWrap.children).forEach(node => this.releaseBgMedia(node));
                state.active = null;
                return;
            }
            // Nooit meerdere nog ladende achtergrondvideo's laten opstapelen.
            state.loading = true;
            const n = makeNode(url);
            let done = false;
            const reveal = () => {
                if (done || state.closed) return;
                done = true;
                state.loading = false;
                n.style.opacity = '1';
                if (n.tagName === 'VIDEO') {
                    const pr = n.play();
                    if (pr && pr.catch) pr.catch(() => {});
                }
                const olds = Array.from(mediaWrap.children).filter(child => child !== n);
                state.active = n;
                later(() => olds.forEach(child => this.releaseBgMedia(child)), 1800);
            };
            n.addEventListener(n.tagName === 'VIDEO' ? 'loadeddata' : 'load', reveal, { once: true });
            n.addEventListener('error', () => {
                if (done) return;
                done = true;
                state.loading = false;
                this.releaseBgMedia(n); // oude beeld behouden als nieuwe video faalt
            }, { once: true });
            mediaWrap.appendChild(n);
            later(() => { if (!done && n.parentNode) reveal(); }, 8000);
        };
        if (mode === 'single' && bgData.single_url) {
            show(bgData.single_url);
        } else if (mode === 'carousel' && bgData.items) {
            const validItems = Object.values(bgData.items).filter(v => String(v).trim() !== '');
            if (validItems.length) {
                let idx = 0;
                show(validItems[idx]);
                if (validItems.length > 1) {
                    this.bgTimers[elementId] = setInterval(() => {
                        if (state.closed || state.loading || document.hidden) return;
                        idx = (idx + 1) % validItems.length;
                        show(validItems[idx]);
                    }, sec * 1000);
                }
            }
        }
    }

    setupSlideLightbox() {
        const stage = document.querySelector('.cmb-stage-left');
        if (!stage) return;
        stage.addEventListener('click', (e) => {
            if (this.slideView === 'overzicht') return;   // in het overzicht opent een tegel zijn eigen slide
            if (e.target.closest('.cmb-frame-ticker') || e.target.closest('.cmb-slide-dots') || e.target.closest('.cmb-corner-badge')) return;
            this.openCurrentSlideLightbox();
        });
    }

    // Rand als plaatje: bij meerdere plaatjes loopt een lichte, zachte wissel (alleen opacity)
    startRings() {
        (this._ringT || []).forEach(clearInterval);
        this._ringT = [];
        document.querySelectorAll('.cmb-ring').forEach((r) => {
            const L = Array.from(r.querySelectorAll('.cmb-ring-layer'));
            if (L.length < 2) return;
            let i = 0;
            const sec = Math.max(2, parseInt(r.dataset.sec, 10) || 12);
            L.forEach((e) => { e.style.transitionDuration = Math.min(1.6, sec * 0.7) + 's'; }); // bij korte tijden loopt de overgang vloeiend door
            this._ringT.push(setInterval(() => { L[i].classList.remove('on'); i = (i + 1) % L.length; L[i].classList.add('on'); }, sec * 1000));
        });
    }

    // Eigen tekstframe: wisselt rustig tussen de ingevulde beelden/teksten
    setupOwnFrame() {
        clearInterval(this._ownT);
        const f = document.getElementById('cmb-own-frame');
        const items = f ? Array.from(f.querySelectorAll('.cmb-own-item')) : [];
        if (items.length < 2) return;
        let i = 0;
        items.forEach((e, k) => e.classList.toggle('on', k === 0));
        const sec = Math.max(2, parseInt(f.dataset.sec, 10) || 10);
        this._ownT = setInterval(() => { items[i].classList.remove('on'); i = (i + 1) % items.length; items[i].classList.add('on'); }, sec * 1000);
    }

    openCurrentSlideLightbox() {
        if (!this.slides || this.slides.length === 0) return;
        let slide = this.currentSlide || this.slides[(this.slideIdx - 1 + this.slides.length) % this.slides.length];
        if (!slide) return;
        this.openSlideLightbox(slide);
    }

    // Info-overzicht: glaslaag over de speler met alle rollende slides; de muziek loopt gewoon door
    openInfoOverlay() {
        const ov = document.getElementById('cmb-info-overlay');
        const grid = document.getElementById('cmb-info-grid');
        if (!ov || !grid) return;
        const plain = (h) => { try { return (new DOMParser().parseFromString(String(h || ''), 'text/html').body.textContent || '').trim(); } catch (e) { return ''; } };
        const list = (this.slides || []).filter(s => s && s.ov && s.type !== 'emergency');
        grid.innerHTML = '';
        const catOrder = ['nieuws', 'weer', 'regio', 'overig'];
        list.sort((a, b) => catOrder.indexOf(a.cat || 'overig') - catOrder.indexOf(b.cat || 'overig'));
        let lastCat = '';
        list.forEach((s) => {
            const cat = catOrder.includes(s.cat) ? s.cat : 'overig';
            if (cat !== lastCat) { lastCat = cat; const h = document.createElement('div'); h.className = 'cmb-info-cat'; h.textContent = cat.toUpperCase(); grid.appendChild(h); }
            const card = document.createElement('div');
            card.className = 'cmb-info-card';
            const url = s.media || '';
            if (url && this.mediaKind(url) === 'image') card.style.backgroundImage = `linear-gradient(rgba(5,6,14,.55), rgba(5,6,14,.8)), url("${url}")`;
            const t = document.createElement('div');
            t.className = 'cmb-info-card-t';
            t.textContent = plain(s.title) || plain(s.sub) || '…';
            card.appendChild(t);
            if (plain(s.title) && plain(s.sub)) { const u = document.createElement('div'); u.className = 'cmb-info-card-s'; u.textContent = plain(s.sub); card.appendChild(u); }
            card.onclick = () => this.openSlideLightbox(s);
            grid.appendChild(card);
        });
        const refresh = () => {
            const st = document.querySelector('.cmb-brand-text h1');
            document.getElementById('cmb-info-station').textContent = 'Nu op ' + ((st && st.textContent.trim()) || 'de radio');
            const a = (document.getElementById('cmb-track-artist') || {}).innerText || '';
            const so = (document.getElementById('cmb-track-song') || {}).innerText || '';
            document.getElementById('cmb-info-track').textContent = [a, so].filter(Boolean).join(' - ');
        };
        refresh();
        clearInterval(this._infoTimer);
        this._infoTimer = setInterval(refresh, 4000);
        ov.classList.add('open');
    }

    closeInfoOverlay() {
        clearInterval(this._infoTimer);
        const ov = document.getElementById('cmb-info-overlay');
        if (ov) ov.classList.remove('open');
    }

    openSlideLightbox(slide) {
        let mediaUrl = slide.media || '';
        let isVideo = !!mediaUrl && this.mediaKind(mediaUrl) !== 'image';

        const modal = document.getElementById('cmb-slide-lightbox');
        const mediaWrap = document.getElementById('lb-media-wrap');
        const darkEl = document.getElementById('lb-darken');
        const tEl = document.getElementById('lb-title');
        const sEl = document.getElementById('lb-sub');
        const bEl = document.getElementById('lb-body');

        if (mediaUrl) {
            let blur = (slide.blur !== undefined) ? parseInt(slide.blur) : 0;
            mediaWrap.style.filter = `blur(${blur}px)`;
            if (isVideo) {
                mediaWrap.style.backgroundImage = 'none';
                mediaWrap.innerHTML = this.mediaHtml(mediaUrl);
            } else {
                mediaWrap.innerHTML = '';
                mediaWrap.style.backgroundImage = `url("${mediaUrl}")`;
            }
            mediaWrap.style.display = 'block';

            let dVal = (slide.darken !== undefined) ? parseInt(slide.darken) : 40;
            darkEl.style.backgroundColor = `rgba(0,0,0,${dVal / 100})`;
            darkEl.style.display = 'block';
        } else {
            mediaWrap.style.display = 'none';
            mediaWrap.innerHTML = '';
            darkEl.style.display = 'none';
        }

        tEl.innerText = slide.title || '';
        sEl.innerText = slide.sub || '';
        if (slide.type === 'weather' && slide.lat && slide.lon) {
            // Weerslide: de tegels worden apart opgehaald, dus ook hier laden (anders blijft de lightbox leeg)
            bEl.innerHTML = '⛅ Weersverwachting inladen...' ;
            this.getWeatherForecastForSlide(slide.lat, slide.lon).then((w) => {
                if (!modal.classList.contains('open')) return;
                bEl.innerHTML = (w && w.html) ? w.html : 'Weergegevens zijn nu niet beschikbaar.';
                if (slide.body) { const n = document.createElement('div'); n.className = 'lb-weather-note'; n.textContent = slide.body; bEl.appendChild(n); }
            });
        } else if (slide.type === 'widget') {
            bEl.innerHTML = slide.body || '';   // widget-HTML komt van de server (RSS is al ontsmet, eigen code zit in een sandbox)
        } else {
            bEl.innerText = slide.body || '';
        }

        modal.classList.add('open');
    }

    
    
    async getWeatherForecastForSlide(lat, lon, variant = 1) {
        if (!lat || !lon) return { text: '', html: '' };
        let key = `${lat}_${lon}_${variant}`;
        if (this.weatherCache && this.weatherCache[key] && (Date.now() - this.weatherCache[key].time < 1800000)) {
            return this.weatherCache[key];
        }
        try {
            let res = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&daily=weathercode,temperature_2m_max,temperature_2m_min,precipitation_probability_max,windspeed_10m_max&current_weather=true&timezone=auto`);
            if (!res.ok) return { text: '', html: '' };
            let d = await res.json();
            const codes = {
                0: { icon: '☀️', desc: 'Zonnig' },
                1: { icon: '🌤️', desc: 'Licht bewolkt' },
                2: { icon: '⛅', desc: 'Half bewolkt' },
                3: { icon: '☁️', desc: 'Bewolkt' },
                45: { icon: '🌫️', desc: 'Mist' },
                51: { icon: '🌦️', desc: 'Motregen' },
                61: { icon: '🌧️', desc: 'Regen' },
                71: { icon: '🌨️', desc: 'Sneeuw' },
                80: { icon: '🌧️', desc: 'Buien' },
                95: { icon: '⛈️', desc: 'Onweer' }
            };
            const days = ['Vandaag', 'Morgen', 'Overmorgen'];
            let textParts = [];
            let tilesHtml = '<div class="cmb-weather-tiles-wrap">';

            for (let i = 0; i < Math.min(3, d.daily.time.length); i++) {
                let maxT = Math.round(d.daily.temperature_2m_max[i]);
                let minT = Math.round(d.daily.temperature_2m_min[i]);
                let cInfo = codes[d.daily.weathercode ? d.daily.weathercode[i] : 2] || { icon: '⛅', desc: 'Wisselvallig' };
                let rain = (d.daily.precipitation_probability_max && d.daily.precipitation_probability_max[i] > 15) ? `${d.daily.precipitation_probability_max[i]}%` : '0%';
                
                textParts.push(`${days[i]}: ${cInfo.icon} ${cInfo.desc} ${maxT}°C (min ${minT}°C)`);
                tilesHtml += `
                    <div class="cmb-w-tile">
                        <div class="cmb-w-tile-day">${days[i]}</div>
                        <div class="cmb-w-tile-icon">${cInfo.icon}</div>
                        <div class="cmb-w-tile-temp">${maxT}°<span class="cmb-w-tile-min">/ ${minT}°</span></div>
                        <div class="cmb-w-tile-desc">${cInfo.desc}</div>
                        <div class="cmb-w-tile-rain">💧 ${rain}</div>
                    </div>`;
            }
            tilesHtml += '</div>';

            let dataObj = { text: textParts.join(' • '), html: tilesHtml, time: Date.now() };
            if (!this.weatherCache) this.weatherCache = {};
            this.weatherCache[key] = dataObj;
            return dataObj;
        } catch(e) {
            return { text: '', html: '' };
        }
    }

    
    startBufferProgress() {
        const flyout = document.getElementById('cmb-buffer-flyout');
        const txt = document.getElementById('cmb-buffer-txt');
        if (!flyout || !txt) return;

        clearInterval(this.bufTimer);
        flyout.classList.add('active');
        const items = [
            document.getElementById('buf-step-1'),
            document.getElementById('buf-step-2'),
            document.getElementById('buf-step-3'),
            document.getElementById('buf-step-4')
        ];
        items.forEach(it => { if (it) it.className = 'cmb-buffer-item'; });
        if (items[0]) items[0].classList.add('lit');

        let remaining = 3;
        txt.innerText = `STARTEN ${remaining}s`;

        let step = 1;
        this.bufTimer = setInterval(() => {
            step++;
            if (step <= 4 && items[step - 1]) items[step - 1].classList.add('lit');
            if (remaining > 1) {
                remaining--;
                txt.innerText = `STARTEN ${remaining}s`;
            } else {
                txt.innerText = 'VERBINDEN...';
            }
        }, 800);
    }

    finishBufferProgress() {
        clearInterval(this.bufTimer);
        const flyout = document.getElementById('cmb-buffer-flyout');
        const txt = document.getElementById('cmb-buffer-txt');
        if (!flyout) return;
        if (txt) txt.innerText = 'LIVE ON AIR';
        for (let i = 1; i <= 4; i++) {
            let el = document.getElementById('buf-step-' + i);
            if (el) el.className = 'cmb-buffer-item ready';
        }
        setTimeout(() => {
            if (flyout) flyout.classList.remove('active');
        }, 400);
    }

    stopBufferProgress() {
        clearInterval(this.bufTimer);
        const flyout = document.getElementById('cmb-buffer-flyout');
        if (flyout) flyout.classList.remove('active');
    }

    /* ---------- STREAM UITKLAP BIJ UITVAL ---------- */
    setupStreamFlyout() {
        const closeBtn = document.getElementById('cmb-stream-flyout-close');
        if (closeBtn) closeBtn.onclick = (e) => { e.stopPropagation(); this.hideStreamFlyout(); };
        this.renderStreamChoices();
    }

    renderStreamChoices() {
        const list = document.getElementById('cmb-stream-flyout-list');
        if (!list) return;
        list.innerHTML = '';
        const cur = this.audio && this.audio.src ? this.audio.src : '';
        this.streamChoices.forEach((c) => {
            const b = document.createElement('button');
            b.type = 'button';
            b.className = 'cmb-stream-item' + (cur && cur === c.url ? ' active' : '');
            b.dataset.url = c.url;
            const n = document.createElement('span');
            n.textContent = c.name;
            const s = document.createElement('small');
            try { s.textContent = new URL(c.url).hostname; } catch (e) { s.textContent = ''; }
            b.appendChild(n);
            b.appendChild(s);
            b.onclick = (e) => { e.stopPropagation(); this.playStreamUrl(c.url); };
            list.appendChild(b);
        });
    }

    markActiveStream() {
        const cur = this.audio && this.audio.src ? this.audio.src : '';
        document.querySelectorAll('.cmb-stream-item').forEach((b) => {
            b.classList.toggle('active', !!cur && b.dataset.url === cur);
        });
    }

    showStreamFlyout() {
        const fly = document.getElementById('cmb-stream-flyout');
        if (!fly || !this.streamFlyoutOn || this.streamChoices.length < 2) return;
        this.markActiveStream();
        fly.classList.add('open');
    }

    hideStreamFlyout() {
        const fly = document.getElementById('cmb-stream-flyout');
        if (fly) fly.classList.remove('open');
    }

    onStreamPlaying() {
        clearTimeout(this.streamWatchdog);
        this.markActiveStream();
        this.hideStreamFlyout();
    }

    armStreamWatchdog() {
        clearTimeout(this.streamWatchdog);
        if (!this.desiredPlaying) return;
        this.streamWatchdog = setTimeout(() => {
            if (this.desiredPlaying && (this.audio.paused || this.audio.readyState < 3)) this.onStreamFailure('timeout');
        }, 12000);
    }

    onStreamFailure() {
        if (!this.desiredPlaying) return;
        // Eerst het tweede adres van dezelfde zender proberen (bv. Hostrix :4433/stream)
        const alt = this.onBackup ? this.fallbackAlt : this.streamAlt;
        if (alt && !this.altTried) {
            this.altTried = true;
            this.audio.src = alt;
            this.audio.load();
            this.audio.play().catch(() => {});
            this.armStreamWatchdog();
            return;
        }
        // Stream 1 geen contact? Dan neemt stream 2 het volledig over (geluid én titelinfo).
        if (!this.onBackup && this.fallbackUrl && this.audio.src !== this.fallbackUrl) {
            this.switchToBackup();
            return;
        }
        this.showStreamFlyout();
    }

    switchToBackup() {
        this.onBackup = true;
        this.altTried = false;
        this.audio.src = this.fallbackUrl;
        this.audio.load();
        this.audio.play().catch(() => {});
        this.armStreamWatchdog();
        this.startRecoverWatch();
    }

    /* Terug naar stream 1 zodra die weer bereikbaar is (rustig testen met een stille proef-audio) */
    startRecoverWatch() {
        clearInterval(this.recoverTimer);
        this.recoverTimer = setInterval(() => {
            if (!this.onBackup || !this.desiredPlaying || !this.streamUrl) return;
            // Om en om het hoofdadres en het tweede adres van stream 1 testen
            this.probeFlip = !this.probeFlip;
            const probeUrl = (this.probeFlip || !this.streamAlt) ? this.streamUrl : this.streamAlt;
            const probe = new Audio();
            probe.muted = true; probe.preload = 'auto';
            const done = (ok) => {
                probe.onplaying = probe.oncanplay = probe.onerror = null;
                try { probe.pause(); probe.removeAttribute('src'); probe.load(); } catch(e) {}
                if (ok && this.onBackup && this.desiredPlaying) {
                    this.onBackup = false;
                    this.altTried = (probeUrl !== this.streamUrl);
                    clearInterval(this.recoverTimer);
                    this.audio.src = probeUrl;
                    this.audio.load();
                    this.audio.play().catch(() => {});
                    this.armStreamWatchdog();
                }
            };
            probe.oncanplay = () => done(true);
            probe.onerror = () => done(false);
            setTimeout(() => { if (probe.src) done(false); }, 8000);
            probe.src = probeUrl;
            probe.play().catch(() => {});
        }, this.recheckSec * 1000);
    }

    playStreamUrl(url) {
        if (!url) return;
        this.desiredPlaying = true;
        const pIcon = document.getElementById('cmb-play-icon');
        const sIcon = document.getElementById('cmb-pause-icon');
        if (pIcon) pIcon.style.display = 'none';
        if (sIcon) sIcon.style.display = 'block';
        this.startBufferProgress();
        this.onBackup = !!((url === this.fallbackUrl || (this.fallbackAlt && url === this.fallbackAlt)) && url !== this.streamUrl);
        this.altTried = false;
        clearInterval(this.recoverTimer);
        if (this.onBackup) this.startRecoverWatch();
        this.audio.src = url;
        this.audio.volume = (this.currentVol + 1) * 0.2;
        this.audio.load();
        this.audio.play().catch(() => {});
        this.armStreamWatchdog();
        this.markActiveStream();
    }

    /* ---------- AUTOMATISCH WISSELEN VAN COVER EFFECT ---------- */
    startArtRotation() {
        clearInterval(this.artRotTimer);
        if (!(this.artRotateMin > 0) || this.artEffectKeys.length < 2) return;
        this.artRotTimer = setInterval(() => {
            const st = document.querySelector('.cmb-art-stage');
            if (!st) return;
            const cur = Array.from(st.classList).find((c) => c.indexOf('fx-') === 0);
            let key;
            let guard = 0;
            do {
                key = this.artEffectKeys[Math.floor(Math.random() * this.artEffectKeys.length)];
                guard++;
            } while (('fx-' + key) === cur && guard < 10);
            if (cur) st.classList.remove(cur);
            st.classList.add('fx-' + key);
        }, this.artRotateMin * 60000);
    }

    /* ---------- CLIPSLIDES: een clip neemt het scherm over bij een bepaald nummer ---------- */
    normTrack(s) {
        return String(s || '').toLowerCase()
            .normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/&/g, ' en ')
            .replace(/\([^)]*\)|\[[^\]]*\]/g, ' ')
            .replace(/[^a-z0-9 ]+/g, ' ')
            .replace(/\s+/g, ' ').trim();
    }

    findClipFor(fullTitle, artist, title) {
        if (!Array.isArray(this.clipSlides) || !this.clipSlides.length) return null;
        const hay = this.normTrack(fullTitle + ' ' + artist + ' ' + title);
        for (const c of this.clipSlides) {
            const parts = String(c.match || '').split(/\s+[-–—]\s+/).map((p) => this.normTrack(p)).filter(Boolean);
            if (parts.length && parts.every((p) => hay.indexOf(p) !== -1)) return c;
        }
        return null;
    }

    handleClipSlides(fullTitle, artist, title) {
        // Nieuw nummer: een lopende clip stopt direct (de clip hoort bij zijn eigen nummer)
        this.stopClipTakeover(true);
        const c = this.findClipFor(fullTitle, artist, title);
        if (!c) return;
        const wait = Math.max(0, parseFloat(c.delay || 0)) * 1000;
        this.clipStartTimer = setTimeout(() => this.playClipTakeover(c), wait);
    }

    playClipTakeover(c) {
        // Nooit over het hele scherm: de clip komt in frame 3 (compact) of anders in het linker frame
        const own = document.getElementById('cmb-own-frame');
        const ownOn = !!(own && own.offsetWidth > 0 && own.offsetHeight > 0);
        const host = ownOn ? own : document.querySelector('.cmb-stage-left');
        if (!host) return;
        this.stopClipTakeover(true);
        const ov = document.createElement('div');
        ov.className = 'cmb-clip-takeover is-frame' + (ownOn ? ' in-own' : '');
        const media = document.createElement('div');
        media.className = 'cmb-clip-media fit-' + (c.fit === 'cover' ? 'cover' : 'contain');
        ov.appendChild(media);
        if (c.title) {
            const bar = document.createElement('div');
            bar.className = 'cmb-clip-title';
            const a = document.createElement('div');
            a.className = 'cmb-clip-artist';
            a.textContent = (document.getElementById('cmb-track-artist') || {}).innerText || '';
            const sg = document.createElement('div');
            sg.className = 'cmb-clip-song';
            sg.textContent = (document.getElementById('cmb-track-song') || {}).innerText || '';
            bar.appendChild(a);
            bar.appendChild(sg);
            ov.appendChild(bar);
        }
        host.appendChild(ov);
        this.clipOverlay = ov;
        requestAnimationFrame(() => ov.classList.add('show'));

        this.clipStop = this.playClipInto(media, c, () => this.stopClipTakeover(false));
    }

    stopClipTakeover(immediate) {
        clearTimeout(this.clipStartTimer);
        if (this.clipStop) {
            try { this.clipStop(); } catch (e) {}
            this.clipStop = null;
        }
        const ov = this.clipOverlay;
        this.clipOverlay = null;
        if (ov) {
            if (immediate) {
                ov.remove();
            } else {
                ov.classList.remove('show');
                setTimeout(() => ov.remove(), 600);
            }
        }
        if (this.clipDucked) {
            this.clipDucked = false;
            if (this.audio) this.audio.volume = (this.currentVol + 1) * 0.2;
        }
    }

    /** Speelt een clip (bestand, YouTube, Vimeo of webpagina) in een container, één keer. Geeft een stop-functie terug. */
    playClipInto(container, spec, onEnd) {
        let done = false;
        const cleanups = [];
        const finish = () => {
            if (done) return;
            done = true;
            onEnd();
        };
        const stop = () => { done = true; cleanups.forEach((fn) => { try { fn(); } catch (e) {} }); };
        const sound = false; // een clip speelt nooit geluid: de stream blijft het enige geluid

        // Veiligheidsgrens: na 2 uur altijd afsluiten
        const capT = setTimeout(finish, 7200 * 1000);
        cleanups.push(() => clearTimeout(capT));
        container.innerHTML = '';

        const kind = spec.kind;
        if (kind === 'file') {
            const v = document.createElement('video');
            v.setAttribute('playsinline', '');
            v.preload = 'auto';
            v.controls = false;
            v.loop = false;
            v.muted = !sound;
            v.src = spec.url;
            v.addEventListener('ended', finish);
            v.addEventListener('error', () => setTimeout(finish, 1200));
            container.appendChild(v);
            const p = v.play();
            if (p && p.catch) {
                p.catch(() => {
                    v.muted = true;
                    v.play().catch(() => setTimeout(finish, 1200));
                });
            }
            cleanups.push(() => { v.pause(); v.removeAttribute('src'); v.load(); });
        } else if (kind === 'youtube' && spec.id) {
            const fr = document.createElement('iframe');
            fr.allow = 'autoplay; encrypted-media; picture-in-picture';
            fr.setAttribute('allowfullscreen', '');
            fr.referrerPolicy = 'strict-origin-when-cross-origin';
            fr.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(spec.id) +
                '?autoplay=1&controls=0&rel=0&modestbranding=1&playsinline=1&enablejsapi=1&loop=0&mute=' + (sound ? 0 : 1) +
                '&origin=' + encodeURIComponent(location.origin);
            const onMsg = (ev) => {
                if (ev.source !== fr.contentWindow) return;
                let d = ev.data;
                if (typeof d === 'string') { try { d = JSON.parse(d); } catch (e) { return; } }
                if (!d) return;
                if (d.event === 'onStateChange' && d.info === 0) finish();
                else if (d.event === 'infoDelivery' && d.info && d.info.playerState === 0) finish();
                else if (d.event === 'onError') setTimeout(finish, 1200);
            };
            window.addEventListener('message', onMsg);
            cleanups.push(() => window.removeEventListener('message', onMsg));
            fr.addEventListener('load', () => {
                try {
                    fr.contentWindow.postMessage(JSON.stringify({ event: 'listening', id: 1, channel: 'widget' }), '*');
                    fr.contentWindow.postMessage(JSON.stringify({ event: 'command', func: 'addEventListener', args: ['onStateChange'] }), '*');
                } catch (e) {}
            });
            container.appendChild(fr);
            cleanups.push(() => { fr.src = 'about:blank'; });
        } else if (kind === 'vimeo' && spec.id) {
            const fr = document.createElement('iframe');
            fr.allow = 'autoplay; fullscreen; picture-in-picture';
            fr.src = 'https://player.vimeo.com/video/' + encodeURIComponent(spec.id) +
                '?autoplay=1&muted=' + (sound ? 0 : 1) + '&controls=0&title=0&byline=0&portrait=0&playsinline=1&loop=0&api=1';
            const post = (obj) => { try { fr.contentWindow.postMessage(JSON.stringify(obj), '*'); } catch (e) {} };
            const onMsg = (ev) => {
                if (ev.source !== fr.contentWindow) return;
                let d = ev.data;
                if (typeof d === 'string') { try { d = JSON.parse(d); } catch (e) { return; } }
                if (!d) return;
                if (d.event === 'ready') post({ method: 'addEventListener', value: 'finish' });
                else if (d.event === 'finish') finish();
            };
            window.addEventListener('message', onMsg);
            cleanups.push(() => window.removeEventListener('message', onMsg));
            fr.addEventListener('load', () => post({ method: 'addEventListener', value: 'finish' }));
            container.appendChild(fr);
            cleanups.push(() => { fr.src = 'about:blank'; });
        } else if (kind === 'page' && /^https?:\/\//i.test(spec.url || '')) {
            // Webpagina: zolang als het nummer duurt (stopt bij het volgende nummer), max. 15 minuten
            const fr = document.createElement('iframe');
            fr.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-forms allow-presentation');
            fr.referrerPolicy = 'no-referrer';
            fr.src = spec.url;
            container.appendChild(fr);
            const pageT = setTimeout(finish, 900 * 1000);
            cleanups.push(() => { clearTimeout(pageT); fr.src = 'about:blank'; });
        } else {
            setTimeout(finish, 800);
        }
        return stop;
    }

    /* ---------- LIVE DESIGN-UPDATE NA OPSLAAN ---------- */
    async applyDesignUpdate() {
        // Niet aan het luisteren: een volledige herlaad is het meest betrouwbaar
        if (!this.desiredPlaying && !this.isPlaying) { location.reload(); return; }
        try {
            const r = await fetch(this.selfUrl + '?v=' + Date.now() + (this.themeQs || ''), { cache: 'no-store' });
            if (!r.ok) return;
            const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
            // Weergave links omgezet (slideshow <-> overzicht): de pagina opnieuw laden is het veiligst
            const slNew = doc.querySelector('.cmb-stage-left');
            if (slNew && slNew.classList.contains('is-overview') !== (this.slideView === 'overzicht')) { location.reload(); return; }
            const ovN = doc.getElementById('cmb-overview'), ovC = document.getElementById('cmb-overview');
            if (ovN && ovC && ['bg', 'dark', 'blur'].some((k) => ovN.dataset[k] !== ovC.dataset[k])) { location.reload(); return; }
            const tbN = doc.querySelector('.cmb-topbar'), tbC = document.querySelector('.cmb-topbar');
            if (tbN && tbC && (tbN.getAttribute('data-sig') || '') !== (tbC.getAttribute('data-sig') || '')) { this._topbarPending = true; }
            const nS = doc.querySelectorAll('style');
            const cS = document.querySelectorAll('style');
            if (nS.length !== cS.length) return;
            nS.forEach((s, i) => { if (cS[i].textContent !== s.textContent) cS[i].textContent = s.textContent; });

            const swapPrefixed = (sel, prefix) => {
                const a = doc.querySelector(sel);
                const b = document.querySelector(sel);
                if (!a || !b) return;
                const nv = Array.from(a.classList).find((c) => c.indexOf(prefix) === 0);
                Array.from(b.classList).filter((c) => c.indexOf(prefix) === 0).forEach((c) => b.classList.remove(c));
                if (nv) b.classList.add(nv);
            };
            swapPrefixed('.cmb-stage-right', 'pos-');
            const ovNew = doc.querySelector('.cmb-stage-right');
            const ovCur = document.querySelector('.cmb-stage-right');
            if (ovNew && ovCur) {
                ovCur.classList.toggle('art-overflow', ovNew.classList.contains('art-overflow'));
                ['art-dir-cw', 'art-dir-ccw'].forEach((c) => ovCur.classList.toggle(c, ovNew.classList.contains(c)));
            }
            // Rechterkant: weergave (volledig/compact), wisselen van plek en het eigen tekstframe
            const gNew = doc.getElementById('cmb-grid'), gCur = document.getElementById('cmb-grid');
            if (gNew && gCur) gCur.classList.toggle('is-hswap', gNew.classList.contains('is-hswap'));
            const rcNew = doc.getElementById('cmb-right-col'), rcCur = document.getElementById('cmb-right-col');
            if (rcNew && rcCur) {
                ['is-compact', 'is-swap'].forEach((c) => rcCur.classList.toggle(c, rcNew.classList.contains(c)));
                const ofNew = doc.getElementById('cmb-own-frame'), ofCur = document.getElementById('cmb-own-frame');
                if (ofNew && ofCur) {
                    const itemsHtml = (el) => Array.from(el.querySelectorAll('.cmb-own-item')).map((e) => e.outerHTML).join('');
                    if (itemsHtml(ofNew) !== itemsHtml(ofCur) || ofNew.dataset.sec !== ofCur.dataset.sec) {
                        ofCur.querySelectorAll('.cmb-own-item').forEach((e) => e.remove());
                        ofNew.querySelectorAll('.cmb-own-item').forEach((e) => ofCur.appendChild(e.cloneNode(true)));
                        ofCur.dataset.sec = ofNew.dataset.sec; this.setupOwnFrame();
                    }
                }
            }
            const roofNew = doc.getElementById('cmb-roof-links');
            const roofCur = document.getElementById('cmb-roof-links');
            if (roofNew && roofCur && roofNew.innerHTML !== roofCur.innerHTML) roofCur.innerHTML = roofNew.innerHTML;
            swapPrefixed('.cmb-vol-flyout', 'shape-');

            ['.cmb-brand', '.cmb-onair', '.cmb-track-sub', '.cmb-art-fallback'].forEach((sel) => {
                const a = doc.querySelector(sel);
                const b = document.querySelector(sel);
                if (a && b && a.innerHTML !== b.innerHTML) b.innerHTML = a.innerHTML;
            });
            // Weer-, tijd- en datumwaarde: alleen de opmaak overnemen, de tekst (temperatuur/klok) blijft staan
            ['val-weather', 'val-time', 'val-date'].forEach((id) => {
                const a = doc.getElementById(id), b = document.getElementById(id);
                if (!a || !b) return;
                const st = a.getAttribute('style') || '';
                if (st !== (b.getAttribute('style') || '')) b.setAttribute('style', st);
            });
            const aw = doc.querySelectorAll('.cmb-widget-item small, .cmb-widget-item .cmb-widget-sub:not(#val-date)');
            const bw = document.querySelectorAll('.cmb-widget-item small, .cmb-widget-item .cmb-widget-sub:not(#val-date)');
            if (aw.length === bw.length) {
                aw.forEach((el, i) => {
                    if (el.innerHTML !== bw[i].innerHTML) bw[i].innerHTML = el.innerHTML;
                    const st = el.getAttribute('style') || '';
                    if (st !== (bw[i].getAttribute('style') || '')) bw[i].setAttribute('style', st);
                });
            }
        } catch (e) {}
    }

    startSlidesEngine() {
        if (this.slideView === 'overzicht') { this.startOverview(); return; }
        const mount = document.getElementById('cmb-stage-content');
        const bgMount = document.getElementById('slide-bg-mount');
        const darkMount = document.getElementById('slide-darken-mount');
        if (!mount || !this.slides || this.slides.length === 0) return;

        const runSlide = () => {
            if (this.timedSlide && Date.now() >= this.timedUntil - 50) this.timedSlide = null;
            const timed = this.timedSlide;
            let slide = this.slides[this.slideIdx % this.slides.length];
            let guard = 0;
            while (slide && slide.track_only && !this.activeTrackSlide && guard < this.slides.length) {
                this.slideIdx++;
                slide = this.slides[this.slideIdx % this.slides.length];
                guard++;
            }
            if (this.activeTrackSlide) slide = this.activeTrackSlide;
            if (timed) slide = timed;
            this.currentSlide = slide;
            this.slideReason = timed ? 'op tijd gepland' : (this.activeTrackSlide ? 'gekoppeld aan dit nummer' : 'rotatie');
            this.slideShownAt = Date.now();
            this.slideShownFor = timed ? Math.max(0, this.timedUntil - Date.now()) : ((slide.sec > 0 ? slide.sec : 8) * 1000);
            mount.style.opacity = '0';

            setTimeout(() => {
                if (bgMount && darkMount) {
                    let darken = parseInt(slide.darken || 0);
                    darkMount.style.opacity = (darken / 100);

                    let blur = parseInt(slide.blur || 0);
                    bgMount.style.filter = blur > 0 ? `blur(${blur}px)` : 'none';

                    if (slide.media) {
                        if (bgMount.dataset.src !== slide.media) { bgMount.innerHTML = this.mediaHtml(slide.media); bgMount.dataset.src = slide.media; }
                    } else {
                        bgMount.dataset.src = '';
                        bgMount.innerHTML = '';
                    }
                }

                let html = '';
                if (slide.title) html += `<div class="slide-big-title" style="${this.styleToCSS(slide.style_title, '#63f6ff')}">${slide.title}</div>`;
                if (slide.sub) html += `<div class="slide-sub-title" style="${this.styleToCSS(slide.style_sub, '#ffd166')}">${slide.sub}</div>`;
                // Widget & Embed check
                let isWidget = false;
                if (slide.body && (slide.body.includes('<iframe') || slide.body.includes('<div') || slide.body.includes('widget'))) {
                    isWidget = true;
                    // Klik-laag over de widget: zo werkt de lightbox ook bij een ingesloten pagina
                    html += `<div class="cmb-widget-embed-frame">${slide.body}<div class="cmb-widget-click" title="Groot bekijken"></div></div>`;
                }

                // Weer tiles weergave (met vakjes en icoontjes)
                if (!isWidget && slide.type === 'weather' && slide.lat && slide.lon) {
                    let key = `${slide.lat}_${slide.lon}_1`;
                    let cached = this.weatherCache && this.weatherCache[key];
                    if (cached && cached.html) {
                        html += cached.html;
                    } else {
                        html += `<div class="slide-body-txt" id="weather-tiles-mount">⛅ Weersverwachting inladen...</div>`;
                        this.getWeatherForecastForSlide(slide.lat, slide.lon).then(wObj => {
                            let mEl = mount.querySelector('#weather-tiles-mount');
                            if (mEl && wObj.html) {
                                mEl.outerHTML = wObj.html;
                            }
                        });
                    }
                } else if (!isWidget && slide.body) {
                    let bodyCSS = this.styleToCSS(slide.style_body, '#ffffff');
                    html += `<div class="slide-body-txt" style="${bodyCSS}">${slide.body}</div>`;
                }
                mount.innerHTML = html;
                
                this.updateDots();
                mount.style.opacity = '1';

                let delay;
                if (timed) {
                    delay = Math.max(500, this.timedUntil - Date.now()); // rotatie gaat daarna verder waar hij was
                } else {
                    this.slideIdx++;
                    delay = (slide.sec > 0 ? slide.sec : 8) * 1000;
                }
                clearTimeout(this.slideTimer);
                this.slideTimer = setTimeout(runSlide, delay);
                this.preloadNextWidget();
            }, 300);
        };

        this._runSlide = runSlide;
        runSlide();
    }

    /* ---------- TEGELOVERZICHT (linker frame) EN SLIDE IN FRAME 3 ----------
       Slide-weergave "Overzicht": links staan alle gewone slides als tegels (zoals info.php).
       Slides die niet in het overzicht horen (op tijd, tijdelijk, nood, bij een nummer) staan zolang ze actief zijn in frame 3. */
    startOverview() {
        this.renderOverview();
        this.syncTakeover();
    }

    // Wat staat er in frame 3? Nood gaat voor, dan een slide bij een nummer, dan een getimede (loopt zelf af), dan slides met een tijdvenster (dag, uur, minuut of periode)
    syncTakeover() {
        const em = (this.slides && this.slides[0] && this.slides[0].type === 'emergency') ? this.slides[0] : null;
        const keep = (sl) => { const sg = JSON.stringify([sl.title, sl.sub, sl.body, sl.media]); if (this._tk && this._tkKind === 'persist' && this._tkSig === sg) return; this.showTakeover(sl, 0, 'persist'); this._tkSig = sg; };
        if (em) { keep(em); return; }
        if (this.activeTrackSlide) { keep(this.activeTrackSlide); return; }
        if (this._tkKind === 'timed') return;
        const w = (this.slides || []).filter(s => s && !s.ov && s.type !== 'emergency' && !s.track_only && !(s.track_match && String(s.track_match).trim()));
        if (!w.length) { if (this._tk) this.hideTakeover(); return; }
        const sig = JSON.stringify(w.map(s => [s.title, s.sub, s.body, s.media]));
        if (this._tkKind === 'window' && this._wSig === sig) return;
        this._wSig = sig; this._wI = 0;
        this.runWindow(w);
    }

    runWindow(w) {
        const s = w[this._wI % w.length];
        this._wI++;
        this.showTakeover(s, 0, 'window');
        if (w.length > 1) this._wT = setTimeout(() => { if (this._tkKind === 'window') this.runWindow(w); }, Math.max(3, s.sec || 8) * 1000);
    }

    // Hoekbadgetje links boven: de eerste kop schuift er naast op en de tegels beginnen er direct onder
    fitOverviewBadge() {
        const grid = document.getElementById('cmb-overview');
        if (!grid) return;
        const badge = document.querySelector('.cmb-stage-left .cmb-corner-badge');
        let bx = 0, bh = 0;
        if (badge && badge.offsetWidth > 0 && badge.offsetHeight > 0) {
            const g = grid.getBoundingClientRect(), b = badge.getBoundingClientRect();
            bx = Math.max(0, Math.round(b.right - g.left + 8));
            bh = Math.max(0, Math.round(b.bottom - g.top));
        }
        grid.style.setProperty('--ov-bx', bx + 'px');
        grid.style.setProperty('--ov-bh', bh + 'px');
        if (!this._ovResize) { this._ovResize = true; window.addEventListener('resize', () => this.fitOverviewBadge()); }
    }

    // Eén achtergrond achter het hele overzicht: de achtergronden van de slides wisselen rustig (donker en blur instelbaar)
    startOverviewBg() {
        const grid = document.getElementById('cmb-overview');
        const bgMount = document.getElementById('slide-bg-mount'), dk = document.getElementById('slide-darken-mount');
        if (!grid || !bgMount || !dk) return;
        if (!['frame', 'both'].includes(grid.dataset.bg || 'tile')) { clearTimeout(this._ovBgT); this._ovBgSig = ''; bgMount.innerHTML = ''; bgMount.dataset.src = ''; dk.style.opacity = 0; return; }
        const seen = {}, list = [];
        (this.slides || []).forEach((s) => { if (s && s.media && s.type !== 'emergency' && !s.track_only && !seen[s.media]) { seen[s.media] = 1; list.push({ url: s.media, sec: s.sec || 8 }); } });
        const blur = parseInt(grid.dataset.blur || 6, 10) || 0, dark = (parseInt(grid.dataset.dark || 45, 10) || 0) / 100;
        const sig = JSON.stringify([list.map(x => x.url), blur, dark]);
        if (sig === this._ovBgSig) return;
        this._ovBgSig = sig;
        clearTimeout(this._ovBgT);
        bgMount.style.filter = blur > 0 ? `blur(${blur}px)` : 'none';
        dk.style.opacity = dark;
        if (!list.length) { bgMount.innerHTML = ''; bgMount.dataset.src = ''; return; }
        let i = 0;
        const show = () => {
            const m = list[i % list.length]; i++;
            bgMount.style.opacity = '0';
            setTimeout(() => {
                if (bgMount.dataset.src !== m.url) { bgMount.innerHTML = this.mediaHtml(m.url); bgMount.dataset.src = m.url; }
                bgMount.style.opacity = '1';
            }, 450);
            if (list.length > 1) this._ovBgT = setTimeout(show, Math.max(4, m.sec) * 1000);
        };
        show();
    }

    renderOverview() {
        const grid = document.getElementById('cmb-overview');
        if (!grid) return;
        const plain = (h) => { try { return (new DOMParser().parseFromString(String(h || ''), 'text/html').body.textContent || '').trim(); } catch (e) { return ''; } };
        const list = (this.slides || []).filter(s => s && s.ov && s.type !== 'emergency');
        const bgMode = grid.dataset.bg || 'tile';
        const sig = JSON.stringify([bgMode, list.map(s => [s.title, s.sub, s.foot, s.media, s.cat, s.darken])]);
        this.startOverviewBg();
        if (grid.dataset.sig === sig) return;
        grid.dataset.sig = sig;
        grid.innerHTML = '';
        if (!list.length) { const e = document.createElement('div'); e.className = 'cmb-ov-empty'; e.textContent = 'Nog geen slides'; grid.appendChild(e); return; }
        const catOrder = ['nieuws', 'weer', 'regio', 'overig'];
        list.sort((a, b) => catOrder.indexOf(a.cat || 'overig') - catOrder.indexOf(b.cat || 'overig'));
        let lastCat = '';
        list.forEach((s) => {
            const cat = catOrder.includes(s.cat) ? s.cat : 'overig';
            if (cat !== lastCat) { lastCat = cat; const h = document.createElement('div'); h.className = 'cmb-ov-cat'; h.textContent = cat.toUpperCase(); grid.appendChild(h); }
            const card = document.createElement('div');
            card.className = 'cmb-ov-card';
            const url = s.media || '';
            if ((bgMode === 'tile' || bgMode === 'both') && url && this.mediaKind(url) === 'image') card.style.backgroundImage = `linear-gradient(rgba(5,6,14,.55), rgba(5,6,14,.8)), url("${url}")`;
            const t = document.createElement('div');
            t.className = 'cmb-info-card-t';
            t.textContent = plain(s.title) || plain(s.sub) || '…';
            card.appendChild(t);
            if (plain(s.title) && plain(s.sub)) { const u = document.createElement('div'); u.className = 'cmb-info-card-s'; u.textContent = plain(s.sub); card.appendChild(u); }
            if (plain(s.foot)) { const f = document.createElement('div'); f.className = 'cmb-info-card-f'; f.textContent = plain(s.foot); card.appendChild(f); }
            card.onclick = () => this.openSlideLightbox(s);
            grid.appendChild(card);
        });
        this.fitOverviewBadge();
    }

    slideTextHtml(slide) {
        let html = '';
        if (slide.title) html += `<div class="slide-big-title" style="${this.styleToCSS(slide.style_title, '#63f6ff')}">${slide.title}</div>`;
        if (slide.sub) html += `<div class="slide-sub-title" style="${this.styleToCSS(slide.style_sub, '#ffd166')}">${slide.sub}</div>`;
        if (slide.body) html += `<div class="slide-body-txt" style="${this.styleToCSS(slide.style_body, '#ffffff')}">${slide.body}</div>`;
        return html;
    }

    // De slide neemt de plek van frame 3 over (is frame 3 niet zichtbaar, dan ligt hij over het linker frame)
    showTakeover(slide, ms, kind) {
        if (!slide) return;
        this.hideTakeover(true);
        const own = document.getElementById('cmb-own-frame');
        const ownOn = !!(own && own.offsetWidth > 0 && own.offsetHeight > 0);
        const host = ownOn ? own : document.querySelector('.cmb-stage-left');
        if (!host) return;
        const el = document.createElement('div');
        el.className = 'cmb-takeover' + (ownOn ? ' in-own' : '');
        let h = '';
        if (slide.media) {
            const blur = parseInt(slide.blur || 0), dk = parseInt(slide.darken || 0);
            h += `<div class="slide-bg-layer tk-bg" style="${blur > 0 ? 'filter:blur(' + blur + 'px);' : ''}">${this.mediaHtml(slide.media)}</div><div class="slide-bg-darken tk-dk" style="opacity:${dk / 100}"></div>`;
        }
        h += '<div class="tk-txt">' + this.slideTextHtml(slide) + '</div>';
        el.innerHTML = h;
        host.appendChild(el);
        void el.offsetWidth;
        el.classList.add('on');
        this._tk = el;
        this._tkKind = kind || (ms > 0 ? 'timed' : 'persist');
        clearTimeout(this._tkT);
        if (ms > 0) this._tkT = setTimeout(() => { this.hideTakeover(); this.syncTakeover(); }, ms);
    }

    hideTakeover(now) {
        clearTimeout(this._tkT); clearTimeout(this._wT);
        const el = this._tk;
        this._tk = null; this._tkKind = '';
        if (!el) return;
        if (now) { el.remove(); return; }
        el.classList.remove('on');
        setTimeout(() => el.remove(), 350);
    }

    /* ---------- OP TIJD GEPLANDE SLIDES ----------
       Servertijd (niet de tv-klok) min de stream-vertraging: zo valt de slide samen met wat de luisteraar hoort. */
    startTimedSlides() {
        clearInterval(this.timedTimer);
        const dayKeys = ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'];
        this.timedTimer = setInterval(() => {
            if (!this.timedSlides || this.timedSlides.length === 0) return;
            if (this.slides && this.slides[0] && this.slides[0].type === 'emergency') return;
            const lead = 300; // de overgang duurt 0,3 s; zo staat de slide er precies op tijd
            const t = new Date(Date.now() + this.clockOffset + this.tzOffset * 1000 - this.streamDelay * 1000 + lead);
            const dk = dayKeys[t.getUTCDay()], h = t.getUTCHours();
            const sOfH = t.getUTCMinutes() * 60 + t.getUTCSeconds() + t.getUTCMilliseconds() / 1000;
            for (const ts of this.timedSlides) {
                if (Array.isArray(ts.days) && ts.days.length && !ts.days.includes(dk)) continue;
                if (Array.isArray(ts.hours) && ts.hours.length && !ts.hours.includes(h)) continue;
                for (const tt of (ts.times || [])) {
                    const diff = sOfH - tt;
                    if (diff < 0 || diff >= 3) continue;
                    const key = ts.id + '_' + t.getUTCDate() + '_' + h + '_' + tt;
                    if (this.timedFired[key]) continue;
                    if (Object.keys(this.timedFired).length > 300) this.timedFired = {};
                    this.timedFired[key] = 1;
                    this.fireTimedSlide(ts, diff);
                }
            }
        }, 200);
    }

    fireTimedSlide(ts, lateSec) {
        if (!ts || !ts.slide) return;
        if (this.slideView === 'overzicht') {   // overzicht: de getimede slide staat zolang hij duurt in frame 3
            this.showTakeover(ts.slide, Math.max(2000, Math.max(2, ts.sec || 8) * 1000 - Math.max(0, (lateSec || 0) - 0.3) * 1000));
            return;
        }
        this.timedSlide = ts.slide;
        this.timedUntil = Date.now() + Math.max(2, ts.sec || 8) * 1000 - Math.max(0, (lateSec || 0) - 0.3) * 1000;
        clearTimeout(this.slideTimer);
        if (this._runSlide) this._runSlide();
    }

    /* Soort media: video-bestand (ook met ?...), YouTube, Vimeo of afbeelding */
    mediaKind(url) {
        url = String(url || '');
        if (/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:\?[^#]*)?(?:#.*)?$/i.test(url) || /video\/(?:mp4|webm|x-matroska|quicktime|vnd\.apple\.mpegurl)/i.test(url)) return 'video';
        if (/(youtu\.be\/|youtube\.com\/(watch|embed|shorts|live)|youtube-nocookie\.com\/embed)/i.test(url)) return 'youtube';
        if (/vimeo\.com\/(video\/)?\d{5,12}/i.test(url)) return 'vimeo';
        return 'image';
    }

    /* HTML voor een achtergrond: video, YouTube en Vimeo spelen stil en herhalend, afbeelding vult het vlak */
    mediaHtml(url) {
        url = String(url || '').trim();
        if (!url) return '';
        const q = (s) => s.replace(/"/g, '&quot;');
        const k = this.mediaKind(url);
        if (k === 'video') return `<video src="${q(url)}" autoplay loop muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>`;
        if (k === 'youtube') {
            const m = url.match(/(?:youtu\.be\/|[?&]v=|embed\/|shorts\/|live\/)([A-Za-z0-9_-]{11})/);
            if (m) return `<iframe class="cmb-bg-frame" src="https://www.youtube-nocookie.com/embed/${m[1]}?autoplay=1&mute=1&controls=0&loop=1&playlist=${m[1]}&modestbranding=1&playsinline=1&rel=0&disablekb=1" allow="autoplay; encrypted-media" tabindex="-1" referrerpolicy="strict-origin-when-cross-origin"></iframe>`;
        }
        if (k === 'vimeo') {
            const m = url.match(/vimeo\.com\/(?:video\/)?(\d{5,12})/);
            if (m) return `<iframe class="cmb-bg-frame" src="https://player.vimeo.com/video/${m[1]}?background=1&autoplay=1&muted=1&loop=1" allow="autoplay" tabindex="-1"></iframe>`;
        }
        return `<img src="${q(url)}" style="width:100%;height:100%;object-fit:cover;" alt="">`;
    }

    /* Volgende widget met een ingesloten pagina alvast stil laden, zodat hij meteen klaarstaat */
    preloadNextWidget() {
        try {
            if (!this.slides || this.slides.length < 2) return;
            const nxt = this.slides[this.slideIdx % this.slides.length];
            if (!nxt || nxt.type !== 'widget' || !nxt.body) return;
            const m = nxt.body.match(/<iframe[^>]+src="([^"]+)"/i);
            if (!m || m[1] === this.preloadedSrc) return;
            this.preloadedSrc = m[1];
            let box = document.getElementById('cmb-preload-box');
            if (!box) { box = document.createElement('div'); box.id = 'cmb-preload-box'; box.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;opacity:0;pointer-events:none;left:-9999px;'; document.body.appendChild(box); }
            box.innerHTML = `<iframe src="${m[1]}" loading="eager" tabindex="-1" sandbox="allow-scripts allow-same-origin"></iframe>`;
        } catch (e) {}
    }

    /* ?debug=1 achter het adres: klein venster met wat er nu in beeld staat en waarom */
    startDebugBox() {
        if (!/[?&]debug=1/.test(location.search)) return;
        const box = document.createElement('div');
        box.style.cssText = 'position:fixed;left:10px;bottom:10px;z-index:99999;background:rgba(0,0,0,.85);color:#0f0;font:12px/1.45 monospace;padding:8px 10px;border-radius:8px;max-width:440px;pointer-events:none;white-space:pre-wrap;';
        document.body.appendChild(box);
        const typeName = { emergency: 'NOOD', news: 'NIEUWS', weather: 'WEER', custom: 'VRIJ', widget: 'WIDGET' };
        setInterval(() => {
            const s = this.currentSlide || {};
            const idx = this.slides ? this.slides.indexOf(s) : -1;
            const left = this.slideShownFor ? Math.max(0, Math.round((this.slideShownAt + this.slideShownFor - Date.now()) / 1000)) + ' s' : 'tot volgend nummer';
            const tit = String(s.title || '').replace(/<[^>]*>/g, '').slice(0, 40);
            box.textContent = `SLIDE ${idx >= 0 ? (idx + 1) + '/' + this.slides.length : '-'}  ${typeName[s.type] || s.type || '-'}  "${tit}"\n` +
                `reden: ${this.slideReason || '-'}  ·  nog: ${left}\n` +
                `clip in beeld: ${this.clipOverlay ? 'ja' : 'nee'}  ·  stream: ${this.onBackup ? '2 (reserve)' : '1'}  ·  nummer: ${this.lastSong || '-'}`;
        }, 500);
    }

    styleToCSS(st, defCol) {
        if (!st) return `color:${defCol};`;
        let css = '';
        if (st.font) css += `font-family:'${st.font}','Noto Color Emoji',sans-serif;`;
        if (st.size) css += `font-size:calc(${st.size} * var(--cmb-ts, 1));`;
        if (st.shadow) css += `text-shadow:0 2px 4px ${st.shadow};`;
        let col = st.color || defCol;
        let col2 = st.grad_col2 || '#63f6ff';
        let gm = st.grad_mode || 'none';
        if (gm === 'tb') {
            css += `background:linear-gradient(180deg, ${col}, ${col2}); -webkit-background-clip:text; -webkit-text-fill-color:transparent;`;
        } else if (gm === 'lr') {
            css += `background:linear-gradient(90deg, ${col}, ${col2}); -webkit-background-clip:text; -webkit-text-fill-color:transparent;`;
        } else if (gm === 'diag1') {
            css += `background:linear-gradient(45deg, ${col}, ${col2}); -webkit-background-clip:text; -webkit-text-fill-color:transparent;`;
        } else if (gm === 'diag2') {
            css += `background:linear-gradient(135deg, ${col}, ${col2}); -webkit-background-clip:text; -webkit-text-fill-color:transparent;`;
        } else {
            css += `color:${col};`;
        }
        return css;
    }

    updateDots() {
        const dotsWrap = document.getElementById('cmb-dots-mount');
        if (!dotsWrap) return;
        dotsWrap.innerHTML = '';
        let count = Math.min(this.slides.length, 5);
        let cur = (this.slideIdx - 1 + this.slides.length) % this.slides.length;
        for (let i = 0; i < count; i++) {
            let d = document.createElement('div');
            d.className = 'cmb-dot' + (i === cur ? ' active' : '');
            dotsWrap.appendChild(d);
        }
    }


    startLiveSyncEngine() {
        let lastSyncStr = '';
        let lastBgHash = this.bgHash || '';
        let lastSyncHash = '';
        // Interval: normaal 5 s; zwakke apparaten (tv, N4120-laptop, weinig geheugen/kernen) 12 s.
        // Daarna regelt de speler zichzelf: kostte een ronde veel tijd of was er niets nieuw, dan wordt het rustiger; bij een wijziging weer snel.
        const ua = navigator.userAgent || '';
        const weak = /SMART-?TV|SmartTV|Tizen|Web0S|WebOS|NetCast|HbbTV|BRAVIA|VIDAA|Hisense|AFT|Roku|CrKey|Android TV|GoogleTV/i.test(ua)
            || (navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 4)
            || (navigator.deviceMemory && navigator.deviceMemory <= 4);
        const minGap = weak ? 12000 : 5000;
        const maxGap = weak ? 20000 : 10000;
        let gap = minGap;
        const tick = async () => {
            if (this._liveSyncBusy) return;
            this._liveSyncBusy = true;
            let busyMs = 0, changed = false;
            try {
                if (document.hidden) return;
                const tReq = Date.now();
                let res = await fetch(this.selfUrl + '?api_live_data=1&v=' + Date.now() + (lastSyncHash ? '&h=' + lastSyncHash : '') + (this.themeQs || ''), { cache: 'no-store' });
                if (!res.ok) return;
                let data = await res.json();
                if (data.server_ms) this.clockOffset = data.server_ms - Math.round((tReq + Date.now()) / 2);
                if (data.tz_offset !== undefined) this.tzOffset = parseInt(data.tz_offset, 10) || 0;
                if (data.same) return;   // niets gewijzigd: niets verwerken
                changed = true;
                if (data.sync_hash) lastSyncHash = data.sync_hash;
                const tWork = performance.now();
                // Op tijd geplande slides bijwerken (klok is al gelijkgezet)
                if (data.stream_delay !== undefined) this.streamDelay = parseFloat(data.stream_delay) || 0;
                if (Array.isArray(data.timed_slides)) this.timedSlides = data.timed_slides;
                let syncStr = JSON.stringify(data.slides) + JSON.stringify(data.ticker);

                // Live updates van slides & ticker
                if (lastSyncStr && lastSyncStr !== syncStr) {
                    let wasEmer = this.slides && this.slides[0] && this.slides[0].type === 'emergency';
                    let nowEmer = data.slides && data.slides[0] && data.slides[0].type === 'emergency';

                    this.slides = data.slides;
                    this.tickerPool = data.ticker;
                    if (this.slideView === 'overzicht') { this.renderOverview(); this.syncTakeover(); }

                    if (nowEmer !== wasEmer) {
                        this.slideIdx = 0;
                        clearTimeout(this.slideTimer);
                        this.startSlidesEngine();
                    }
                }
                lastSyncStr = syncStr;

                // Live updates van Achtergronden (zonder F5)
                if (data.bg_hash && data.bg_hash !== lastBgHash) {
                    lastBgHash = data.bg_hash;
                    if (data.bg_global) this.mountBgContainer('cmb-global-bg', data.bg_global);
                    if (data.bg_player) this.mountBgContainer('cmb-player-bg', data.bg_player);
                }

                // Live update van Cover Effecten (zonder F5)
                if (data.art_effect && data.art_effect !== this.lastArtSetting) {
                    this.lastArtSetting = data.art_effect;
                    let artStage = document.querySelector('.cmb-art-stage');
                    if (artStage) artStage.className = 'cmb-art-stage fx-' + data.art_effect;
                }
                if (data.slide_scale !== undefined) {
                    const scKey = data.slide_scale + '|' + data.slide_scale_m + '|' + data.slide_lines + '|' + data.slide_lines_m;
                    this.slideScale = data.slide_scale; this.slideScaleM = data.slide_scale_m;
                    this.slideLines = data.slide_lines; this.slideLinesM = data.slide_lines_m;
                    if (this.applySlideScale && scKey !== this._scKey) { this._scKey = scKey; this.applySlideScale(); }
                }
                if (data.art_rotate_min !== undefined && parseInt(data.art_rotate_min, 10) !== this.artRotateMin) {
                    this.artRotateMin = parseInt(data.art_rotate_min, 10) || 0;
                    this.startArtRotation();
                }

                if (Array.isArray(data.clipslides)) this.clipSlides = data.clipslides;

                // Lettertypen die nieuw in gebruik zijn
                if (data.fonts_url && data.fonts_url !== this.fontsUrl) {
                    this.fontsUrl = data.fonts_url;
                    let fl = document.getElementById('cmb-fonts-link');
                    if (fl) fl.href = data.fonts_url;
                }

                // Streams (hoofd, failover en uitklaplijst)
                if (data.stream_url !== undefined) {
                    this.fallbackUrl = data.fallback_url || '';
                    this.streamAlt = data.stream_alt || '';
                    this.fallbackAlt = data.fallback_alt || '';
                    if (data.stream_recheck_sec) this.recheckSec = data.stream_recheck_sec;
                    this.streamFlyoutOn = data.stream_flyout !== false;
                    if (Array.isArray(data.stream_choices) && JSON.stringify(data.stream_choices) !== JSON.stringify(this.streamChoices)) {
                        this.streamChoices = data.stream_choices;
                        this.renderStreamChoices();
                    }
                    if (data.stream_url && data.stream_url !== this.streamUrl) {
                        this.streamUrl = data.stream_url;
                        if (this.desiredPlaying) this.playStreamUrl(this.streamUrl);
                    }
                }

                // Alle overige opmaak: pas aan zodra er is opgeslagen
                if (data.design_hash && data.design_hash !== this.lastDesignHash) {
                    this.lastDesignHash = data.design_hash;
                    this.applyDesignUpdate();
                }

                // Live update van Frame Randen & Hoeken (veilig zonder flex/layout te wissen)
                if (data.border_left_css && data.border_left_css !== this.lastBorderLeft) {
                    this.lastBorderLeft = data.border_left_css;
                    let sLeft = document.querySelector('.cmb-panel.cmb-stage-left');
                    if (sLeft) {
                        let curCss = sLeft.getAttribute('style') || '';
                        let cleaned = curCss.replace(/border[^;]+;?/gi, '').replace(/box-shadow[^;]+;?/gi, '');
                        sLeft.style.cssText = cleaned + '; ' + data.border_left_css;
                    }
                }
                if (data.border_right_css && data.border_right_css !== this.lastBorderRight) {
                    this.lastBorderRight = data.border_right_css;
                    let sRight = document.querySelector('.cmb-panel.cmb-stage-right');
                    if (sRight) {
                        let curCss = sRight.getAttribute('style') || '';
                        let cleaned = curCss.replace(/border[^;]+;?/gi, '').replace(/box-shadow[^;]+;?/gi, '');
                        sRight.style.cssText = cleaned + '; ' + data.border_right_css;
                    }
                }
                if (data.water_anim) {
                    this.waterAnim = data.water_anim;
                }

                // Live update van Titels en Middenregel
                if (data.station_title) {
                    let h1 = document.querySelector('.cmb-brand-text h1');
                    if (h1 && h1.textContent !== data.station_title) h1.textContent = data.station_title;
                }
                if (data.station_sub) {
                    let subEl = document.querySelector('.cmb-brand-text span');
                    if (subEl && subEl.textContent !== data.station_sub) subEl.textContent = data.station_sub;
                }
                if (data.studio_mail) {
                    let mailEl = document.querySelector('.cmb-mid-txt') || document.querySelector('.cmb-mid-notify');
                    if (mailEl && mailEl.textContent !== data.studio_mail) mailEl.textContent = data.studio_mail;
                }
                if (data.water_text && this.lastWaterText !== data.water_text) {
                    this.lastWaterText = data.water_text;
                    this.renderWaterText(data.water_text);
                }
                if (data.light_left_hash && data.light_left_hash !== this.lastLightLeftHash) {
                    this.lastLightLeftHash = data.light_left_hash;
                    let mL = document.getElementById('cmb-light-mount-left');
                    if (mL && typeof data.light_left_html === 'string') { mL.innerHTML = data.light_left_html; this.startRings(); }
                }
                if (data.light_right_hash && data.light_right_hash !== this.lastLightRightHash) {
                    this.lastLightRightHash = data.light_right_hash;
                    let mR = document.getElementById('cmb-light-mount-right');
                    if (mR && typeof data.light_right_html === 'string') { mR.innerHTML = data.light_right_html; this.startRings(); }
                }
                if (data.light_own_hash && data.light_own_hash !== this.lastLightOwnHash) {
                    this.lastLightOwnHash = data.light_own_hash;
                    let mO = document.getElementById('cmb-light-mount-own');
                    if (mO && typeof data.light_own_html === 'string') { mO.innerHTML = data.light_own_html; this.startRings(); }
                }
                busyMs = performance.now() - tWork;
            } catch(e) {
            } finally {
                this._liveSyncBusy = false;
                // Rustiger bij zware verwerking of niets nieuws; snel terug naar normaal bij een wijziging
                if (changed) gap = (busyMs > 60) ? Math.min(maxGap, gap * 1.5) : minGap;
                else gap = Math.min(maxGap, Math.round(gap * 1.15));
                // Met werkende pulse (SSE): alleen nog een veiligheidscontrole per 5 min; zonder pulse de zelfregelende poll
                clearTimeout(this._syncTimer);
                this._syncTimer = setTimeout(tick, this._sseOk ? 300000 : gap);
            }
        };
        this._syncTimer = setTimeout(tick, minGap);

        // PULSE: beheer meldt elke wijziging (cmb_pulse) via cmb-events.php; de speler pakt het dan meteen over.
        // Lukt dat niet (oude tv-browser, host met buffering, uitgezet via config 'sse' => false), dan blijft de poll gewoon werken.
        if (this.sseOn && !weak) {
            let es = null, fails = 0, helloT = null, pend = null, lastSig = null;
            const nowTick = () => { clearTimeout(pend); pend = setTimeout(() => { clearTimeout(this._syncTimer); tick(); }, 250); };
            const giveUp = () => {
                if (es) { es.close(); es = null; }
                clearTimeout(helloT);
                this.sseOn = false; this._sseOk = false;
                clearTimeout(this._syncTimer); this._syncTimer = setTimeout(tick, gap);
            };
            const open = () => {
                if (es || document.hidden || !this.sseOn) return;
                try { es = new EventSource('cmb-events.php'); } catch (e) { giveUp(); return; }
                clearTimeout(helloT);
                helloT = setTimeout(() => { if (!this._sseOk) giveUp(); }, 12000);   // geen levensteken: ga door met de poll
                es.addEventListener('hello', (e) => {
                    fails = 0; this._sseOk = true; clearTimeout(helloT);
                    if (lastSig !== null && e.data !== lastSig) nowTick();   // intussen iets gemist (verbinding even weg)
                    lastSig = e.data;
                });
                es.addEventListener('pulse', (e) => { lastSig = e.data; nowTick(); });
                es.onerror = () => {
                    fails++;
                    if (es && es.readyState === 2) { giveUp(); return; }   // server weigert (uit of niet beschikbaar)
                    if (fails >= 6) { giveUp(); return; }
                    if (fails >= 2 && this._sseOk) {   // verbinding écht weg: terug naar de poll tot hij terug is
                        this._sseOk = false;
                        clearTimeout(this._syncTimer); this._syncTimer = setTimeout(tick, minGap);
                    }
                };
            };
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) { if (es) { es.close(); es = null; } this._sseOk = false; clearTimeout(helloT); }
                else if (this.sseOn) { open(); clearTimeout(this._syncTimer); this._syncTimer = setTimeout(tick, 300); }
            });
            open();
        }
    }

    startTickerEngine() {
        const mount = document.getElementById('cmb-ticker-mount');
        if (!mount || !this.tickerPool || this.tickerPool.length === 0) return;
        const mode = this.tickerAnimMode || 'fade';
        const pool = this.tickerPool;

        // Lopende band: alle tickerregels achter elkaar, gescheiden door een bol, doorlopend zonder wachten
        if (mode === 'marquee') {
            mount.style.cssText = '';
            const icon = (it) => (it.style && it.style.icon ? it.style.icon + ' ' : '');
            const sep = ' &nbsp;&bull;&nbsp; ';
            mount.innerHTML = pool.map(it => `<span style="${this.tickerCSS(it).replace(/"/g, '&quot;')}">${icon(it)}${it.val}</span>`).join(sep) + sep;
            mount.classList.remove('ticker-marquee', 'ticker-marquee-once');
            mount.style.cssText += ';color:' + ((pool[0].style && pool[0].style.color) || '#ffd166') + ';display:inline-block;white-space:nowrap;overflow:visible;width:auto;max-width:none;-webkit-line-clamp:unset;transition:none;opacity:1;';
            const speed = Math.max(10, parseFloat(this.tickerSpeed) || 80);
            const run = () => {
                const cw = (mount.parentElement ? mount.parentElement.clientWidth : 800) || 800;
                const tw = mount.scrollWidth || mount.offsetWidth || 1000;
                if (this._tickerAnim) { try { this._tickerAnim.cancel(); } catch (e) {} }
                if (!mount.animate) return;
                this._tickerAnim = mount.animate(
                    [{ transform: `translateX(${cw}px)` }, { transform: `translateX(-${tw}px)` }],
                    { duration: ((cw + tw) / speed) * 1000, iterations: Infinity, easing: 'linear' }
                );
            };
            run();
            window.addEventListener('resize', run);
            return;
        }
        if (pool[0].sec === 0) { this.applyTickerItem(mount, pool[0]); return; }

        // Wegfaden + vervangen: fade (op zijn plek), omhoog, omlaag, links, rechts
        const away = { fade: '', up: 'translateY(-60%)', down: 'translateY(60%)', left: 'translateX(-40%)', right: 'translateX(40%)' };
        const from = { fade: '', up: 'translateY(60%)', down: 'translateY(-60%)', left: 'translateX(40%)', right: 'translateX(-40%)' };
        const outT = away[mode] !== undefined ? away[mode] : '';
        const inT = from[mode] !== undefined ? from[mode] : '';
        const dur = 350;
        mount.style.transition = `opacity ${dur}ms ease, transform ${dur}ms ease`;

        const runTickerStep = () => {
            const currentItem = pool[this.tickerIdx % pool.length];
            mount.style.opacity = '0';
            mount.style.transform = outT;

            setTimeout(() => {
                this.applyTickerItem(mount, currentItem);
                // nieuwe tekst eerst aan de tegenoverliggende kant zetten (zonder animatie), dan naar de plek laten glijden
                const tr = mount.style.transition;
                mount.style.transition = 'none';
                mount.style.transform = inT;
                void mount.offsetWidth;
                mount.style.transition = tr;
                mount.style.opacity = '1';
                mount.style.transform = 'none';

                this.tickerIdx = (this.tickerIdx + 1) % pool.length;
                let nextDelay = (currentItem.sec > 0 ? currentItem.sec : 8) * 1000;
                this.tickerTimer = setTimeout(runTickerStep, nextDelay);
            }, dur);
        };

        runTickerStep();
    }

    // Eigen opmaak van een tickerregel (lettertype, grootte, kleur, verloop, schaduw); leeg = opmaak van regel 1
    tickerCSS(item) {
        const master = (this.tickerPool && this.tickerPool[0] && this.tickerPool[0].style) ? this.tickerPool[0].style : {};
        const own = (item && item.style && Object.keys(item.style).length) ? item.style : master;
        let css = this.styleToCSS(own, '#ffd166');
        css = css.replace(/calc\(([^ ]+) \* var\(--cmb-ts, 1\)\)/, '$1');
        css = css.replace(/text-shadow:[^;]*;/, '') + (own.shadow ? `text-shadow:0 2px 4px ${own.shadow};` : 'text-shadow:none;');
        return css;
    }

    applyTickerItem(el, item) {
        const iconTxt = item.style && item.style.icon ? item.style.icon + ' ' : '';
        el.innerHTML = iconTxt + item.val;
        const keep = { o: el.style.opacity, t: el.style.transform, tr: el.style.transition };
        el.style.cssText = this.tickerCSS(item);
        el.style.opacity = keep.o; el.style.transform = keep.t; el.style.transition = keep.tr;
    }

    async fetchLiveWeather() {
        if (this._weatherPollBusy) return;
        this._weatherPollBusy = true;
        try {
            let r = await fetch(`https://api.open-meteo.com/v1/forecast?latitude=${this.lat}&longitude=${this.lon}&current_weather=true&timezone=auto`);
            if (!r.ok) throw new Error('weer');
            let d = await r.json();
            let curTemp = Math.round(d.current_weather.temperature);
            let code = d.current_weather.weathercode;
            let icon = '⛅';
            if (code === 0) icon = '☀️';
            else if ([1, 2].includes(code)) icon = '🌤️';
            else if (code === 3) icon = '☁️';
            else if ([51, 61, 80].includes(code)) icon = '🌧️';

            let topW = document.getElementById('val-weather');
            this._weatherText = `${icon} ${curTemp}°C`;
            if (topW) topW.innerText = this._weatherText;
            clearTimeout(this._weatherRetry);
        } catch(e) {
            // Mislukt (trage tv, netwerk): laatste waarde terugzetten en over 20 s opnieuw proberen, niet pas na 10 minuten
            const w = document.getElementById('val-weather');
            if (w && this._weatherText && w.innerText !== this._weatherText) w.innerText = this._weatherText;
            clearTimeout(this._weatherRetry);
            this._weatherRetry = setTimeout(() => this.fetchLiveWeather(), 20000);
        } finally { this._weatherPollBusy = false; }
    }

        setupAudioListeners() {
        const pIcon = document.getElementById('cmb-play-icon');
        const sIcon = document.getElementById('cmb-pause-icon');

        this.audio.addEventListener('stalled', () => this.armStreamWatchdog());
        this.audio.addEventListener('waiting', () => this.armStreamWatchdog());
        this.audio.addEventListener('ended', () => this.onStreamFailure('ended'));

        this.audio.addEventListener('playing', () => {
            this.isPlaying = true;
            this.onStreamPlaying();
            pIcon.style.display = 'none';
            sIcon.style.display = 'block';
            this.finishBufferProgress();
        });

        this.audio.addEventListener('pause', () => {
            this.isPlaying = false;
            pIcon.style.display = 'block';
            sIcon.style.display = 'none';
        });

        this.audio.addEventListener('error', () => {
            if (this.desiredPlaying) this.onStreamFailure('error');
            else this.audio.removeAttribute('src');
        });
    }

    setupControls() {
        const pBtn = document.getElementById('cmb-play-btn');
        const volToggle = document.getElementById('cmb-vol-toggle-btn');
        const volFlyout = document.getElementById('cmb-vol-flyout');
        const vDown = document.getElementById('cmb-vol-down');
        const vUp = document.getElementById('cmb-vol-up');
        const vBlocks = document.querySelectorAll('.cmb-vol-item');

        this.desiredPlaying = false;
        let actionBusy = false;

        const handleDirectPlayToggle = (e) => {
            if (e) { e.preventDefault(); e.stopPropagation(); }
            if (actionBusy) return;
            actionBusy = true;
            setTimeout(() => { actionBusy = false; }, 300);

            this.desiredPlaying = !this.desiredPlaying;
            const pIcon = document.getElementById('cmb-play-icon');
            const sIcon = document.getElementById('cmb-pause-icon');

            if (this.desiredPlaying) {
                pIcon.style.display = 'none';
                sIcon.style.display = 'block';
                
                this.startBufferProgress();
                this.onBackup = false; this.altTried = false; clearInterval(this.recoverTimer);
                this.audio.src = this.streamUrl;
                this.audio.volume = (this.currentVol + 1) * 0.2;
                this.audio.load();
                let playPromise = this.audio.play();
                this.armStreamWatchdog();
                if (playPromise !== undefined) {
                    playPromise.catch(err => {
                        if (!this.desiredPlaying) return;
                        this.onStreamFailure('play');
                    });
                }
            } else {
                pIcon.style.display = 'block';
                sIcon.style.display = 'none';

                clearTimeout(this.streamWatchdog);
                this.hideStreamFlyout();
                this.stopBufferProgress();
                this.audio.pause();
                this.audio.removeAttribute('src');
                this.audio.load();
                this.isPlaying = false;
            }
        };

        pBtn.onclick = handleDirectPlayToggle;

        const resetVolTimer = () => {
            clearTimeout(this.volTimer);
            this.volTimer = setTimeout(() => volFlyout.classList.remove('open'), 4000);
        };

        volToggle.addEventListener('pointerup', (e) => {
            e.stopPropagation();
            volFlyout.classList.toggle('open');
            if (volFlyout.classList.contains('open')) resetVolTimer();
        });

        vDown.onclick = (e) => { e.stopPropagation(); this.setVolume(this.currentVol - 1); resetVolTimer(); };
        vUp.onclick = (e) => { e.stopPropagation(); this.setVolume(this.currentVol + 1); resetVolTimer(); };
        vBlocks.forEach((b, i) => {
            b.onclick = (e) => { e.stopPropagation(); this.setVolume(i); resetVolTimer(); };
        });

        this.setVolume(this.currentVol);
        this.setupSlideLightbox();
        this.setupOwnFrame();
        this.startRings();
    }

    togglePlay() {
        const pBtn = document.getElementById('cmb-play-btn');
        if (pBtn && pBtn.onclick) pBtn.onclick();
    }

setVolume(step) {
        this.currentVol = Math.max(0, Math.min(4, step));
        if (this.audio) this.audio.volume = (this.currentVol + 1) * 0.2;
        document.querySelectorAll('.cmb-vol-item').forEach((b, i) => {
            if (i <= this.currentVol) b.classList.add('active');
            else b.classList.remove('active');
        });
    }

    renderWaterText(str) {
        const wrap = document.getElementById('cmb-water');
        if (!wrap) return;
        wrap.innerHTML = '';
        const pal = this.waterPalette;
        const chars = this.splitChars(String(str || ''));
        for (let i = 0; i < chars.length; i++) {
            if (chars[i] === '\n' || chars[i] === '\r\n') { const br = document.createElement('span'); br.className = 'cmb-water-br'; wrap.appendChild(br); continue; }   // nieuwe regel
            if (chars[i] === '\r') continue;
            const span = document.createElement('span');
            span.className = 'cmb-water-letter';
            let letterColor = this.waterRandomColors ? this.waterCustomColors[Math.floor(Math.random() * this.waterCustomColors.length)] : (this.waterColor || pal[i % pal.length]);
            // Kleurverloop uit het stijlvak: kleur 1 -> kleur 2 over de letters
            if (!this.waterRandomColors && this.waterColor2 && chars.length > 1) letterColor = this.mixHex(this.waterColor, this.waterColor2, i / (chars.length - 1));
            span.style.color = letterColor;
            span.style.fontFamily = "'" + this.waterFont + "', 'Noto Color Emoji', sans-serif";
            span.style.fontSize = (this.waterFontSize || 15) + 'px';
            span.style.letterSpacing = (this.waterLetterSpacing ?? 2) + 'px';
            const glowCol = this.waterShadow || letterColor;
            span.style.textShadow = (this.waterGlow || 0) > 0 ? `0 0 ${this.waterGlow}px ${glowCol}` : (this.waterShadow ? `0 2px 4px ${this.waterShadow}` : 'none');
            span.textContent = chars[i];
            wrap.appendChild(span);
        }
    }

    mixHex(a, b, t) {
        const pa = parseInt(a.slice(1), 16), pb = parseInt(b.slice(1), 16);
        const ch = (sh) => Math.round(((pa >> sh) & 255) * (1 - t) + ((pb >> sh) & 255) * t);
        return '#' + ((1 << 24) | (ch(16) << 16) | (ch(8) << 8) | ch(0)).toString(16).slice(1);
    }

    /* Splitst tekst in echte tekens: een emoji (ook 👨‍👩‍👧 of 🇳🇱) blijft één geheel */
    splitChars(s) {
        try {
            if (typeof Intl !== 'undefined' && Intl.Segmenter) {
                return Array.from(new Intl.Segmenter('nl', { granularity: 'grapheme' }).segment(s), (x) => x.segment);
            }
        } catch (e) {}
        return Array.from(s);
    }

    async pollTrack() {
        if (this._trackPollBusy) return;
        this._trackPollBusy = true;
        try {
            let r = await fetch('get_track.php?s=' + (this.onBackup ? 2 : 1) + '&v=' + Date.now());
            if (!r.ok) return;
            let d = await r.json();
            let song = d.songtitle || '';

            // Even geen titelinfo? Pas na 3 keer achter elkaar (± 24 s) de reservetitel tonen, anders flitst het beeld
            if (d.fallback && this.lastSong && !this.lastWasFallback) {
                this.fallbackHits = (this.fallbackHits || 0) + 1;
                if (this.fallbackHits < 3) return;
            } else if (!d.fallback) {
                this.fallbackHits = 0;
            }

            if (song && song !== this.lastSong) {
                this.lastSong = song;
                this.lastWasFallback = !!d.fallback;
                document.getElementById('cmb-track-artist').innerText = d.fallback ? (d.artist || '') : (d.artist || this.fallbackArtist);
                const songEl = document.getElementById('cmb-track-song');
                songEl.innerText = d.song || song;
                songEl.classList.toggle('is-fallback', !!d.fallback);
                this.partyLetterCelebration();
                this.coverProvider = (d.source === 2) ? this.coverProvider2 : this.coverProvider1;
                this.resolveArtwork(song, d.cover || '', d.artist || '', d.song || '');
                this.checkTrackTriggeredSlide(song, d.artist || '', d.song || '');
                this.handleClipSlides(song, d.artist || '', d.song || '');
            }
        } catch(e) {} finally { this._trackPollBusy = false; }
    }

        checkTrackTriggeredSlide(fullTitle, artist, title) {
        if (!this.slides || this.slides.length === 0) return;
        let sLower = (fullTitle + ' ' + artist + ' ' + title).toLowerCase();
        let matched = null;
        for (let s of this.slides) {
            if (s.track_match && s.track_match.trim().length >= 3) {
                if (sLower.includes(s.track_match.trim().toLowerCase())) {
                    matched = s;
                    break;
                }
            }
        }
        if (matched) {
            this.activeTrackSlide = matched;
            this.showDirectSlide(matched);
        } else if (this.activeTrackSlide) {
            this.activeTrackSlide = null;
            clearTimeout(this.slideTimer);
            this.startSlidesEngine();
        }
    }

    showDirectSlide(slide) {
        if (this.slideView === 'overzicht') { this.currentSlide = slide; this.showTakeover(slide, 0); return; } // blijft tot het volgende nummer
        clearTimeout(this.slideTimer);
        this.currentSlide = slide;
        this.slideReason = 'gekoppeld aan dit nummer (blijft tot het volgende nummer)';
        this.slideShownAt = Date.now(); this.slideShownFor = 0;
        const mount = document.getElementById('cmb-stage-content');
        const bgMount = document.getElementById('slide-bg-mount');
        const darkMount = document.getElementById('slide-darken-mount');
        if (!mount) return;

        if (bgMount && darkMount) {
            darkMount.style.opacity = (parseInt(slide.darken || 0) / 100);
            let b = parseInt(slide.blur || 0);
            bgMount.style.filter = b > 0 ? `blur(${b}px)` : 'none';
            if (slide.media) {
                if (bgMount.dataset.src !== slide.media) { bgMount.innerHTML = this.mediaHtml(slide.media); bgMount.dataset.src = slide.media; }
            } else {
                bgMount.dataset.src = '';
                bgMount.innerHTML = '';
            }
        }

        let html = '';
        if (slide.title) html += `<div class="slide-big-title" style="${this.styleToCSS(slide.style_title, '#63f6ff')}">${slide.title}</div>`;
        if (slide.sub) html += `<div class="slide-sub-title" style="${this.styleToCSS(slide.style_sub, '#ffd166')}">${slide.sub}</div>`;
        if (slide.body) html += `<div class="slide-body-txt" style="${this.styleToCSS(slide.style_body, '#ffffff')}">${slide.body}</div>`;
        mount.innerHTML = html;
    }

    showBigFlash(letters) {
        if (!letters || !letters.length || !document.body) return;
        const old = document.getElementById('cmb-flash-layer');
        if (old) old.remove();
        const layer = document.createElement('div');
        layer.id = 'cmb-flash-layer';
        const n = letters.length;
        const size = Math.max(36, Math.min(220, Math.floor((window.innerWidth * 0.92) / (n * 0.72))));
        const maxDelay = Math.min(0.9, n * 0.05);
        letters.forEach((l, i) => {
            const s = document.createElement('span');
            s.textContent = l.textContent;
            s.style.color = l.style.color;
            s.style.fontFamily = l.style.fontFamily;
            s.style.fontSize = size + 'px';
            s.style.lineHeight = '1';
            s.style.letterSpacing = '0.04em';
            s.style.textShadow = l.style.textShadow && l.style.textShadow !== 'none' ? l.style.textShadow.replace(/\d+px/, Math.round(size / 6) + 'px') : '0 0 ' + Math.round(size / 6) + 'px ' + (l.style.color || '#fff');
            s.style.animation = 'partyFlashIn 2.1s cubic-bezier(0.2, 0.9, 0.3, 1.2) ' + (i * (maxDelay / n)).toFixed(3) + 's both';
            layer.appendChild(s);
        });
        document.body.appendChild(layer);
        clearTimeout(this._flashT);
        this._flashT = setTimeout(() => { const x = document.getElementById('cmb-flash-layer'); if (x) x.remove(); }, 3200);
    }

    partyLetterCelebration() {
        let letters = document.querySelectorAll('.cmb-water-letter');
        const modes = ['partyPopAnim', 'partySupernova', 'partyHyperdrive', 'partyShockwave', 'partyRainbowWave', 'partyNeonFlicker', 'partyDropBounce', 'partyFlipCoin', 'partyFireworks', 'partyOrbit', 'partyTypewriter', 'partyPulse', 'partyWaveRise', 'partyScatter'];
        const map = {
            bubble: 'partyPopAnim', supernova: 'partySupernova', hyperdrive: 'partyHyperdrive', shockwave: 'partyShockwave',
            rainbowwave: 'partyRainbowWave', neonflicker: 'partyNeonFlicker', dropbounce: 'partyDropBounce',
            flipcoin: 'partyFlipCoin', fireworks: 'partyFireworks', orbit: 'partyOrbit', typewriter: 'partyTypewriter', pulse: 'partyPulse', waverise: 'partyWaveRise', scatter: 'partyScatter'
        };
        let chosenAnim = map[this.waterAnim] || 'partyPopAnim';
        if (this.waterAnim === 'random') chosenAnim = modes[Math.floor(Math.random() * modes.length)];

        // "Floep": de letters knallen even groot midden in beeld (eigen laag, alleen transform/opacity) en de echte letters pulseren mee
        if (this.waterAnim === 'bigflash') {
            chosenAnim = 'partyPulse';
            this.showBigFlash(letters);
        }

        letters.forEach((l, i) => {
            l.style.animation = 'none';
            void l.offsetWidth;
            l.style.animation = `${chosenAnim} 3.2s cubic-bezier(0.175, 0.885, 0.32, 1.275) ${i * 0.08}s both`;
        });
    }

    async resolveArtwork(query, streamCoverUrl, artistName, titleName) {
        let clean = query.replace(/(&|feat\.?|ft\.?|remix|edit|radio)/gi, '').trim();
        let img = document.getElementById('cmb-art-img');
        let fb = document.getElementById('cmb-art-fallback');

        const show = (u) => {
            const pre = new Image();
            pre.onload = () => { img.src = u; img.style.display = 'block'; fb.style.display = 'none'; };
            pre.onerror = () => { img.src = ''; img.style.display = 'none'; fb.style.display = 'flex'; };
            pre.src = u;
        };
        const lookups = {
            itunes: async () => {
                let r = await fetch(`https://itunes.apple.com/search?term=${encodeURIComponent(clean)}&media=music&limit=1`);
                let d = await r.json();
                return (d.results && d.results.length > 0) ? d.results[0].artworkUrl100.replace('100x100bb', '600x600bb') : '';
            },
            deezer: async () => {
                let r = await fetch(`https://api.deezer.com/search?q=${encodeURIComponent(clean)}&limit=1`);
                let d = await r.json();
                return (d.data && d.data.length > 0 && d.data[0].album) ? (d.data[0].album.cover_big || d.data[0].album.cover_medium) : '';
            },
            lastfm: async () => {
                if (!artistName || !titleName) return '';
                let r = await fetch(`cmb-cover.php?artist=${encodeURIComponent(artistName)}&title=${encodeURIComponent(titleName)}`);
                let d = await r.json();
                return d && d.url ? d.url : '';
            }
        };
        // Gekozen bron eerst; Last.fm (als er een sleutel is ingesteld) is altijd een extra vangnet erachter
        const chain = [this.coverProvider];
        if (this.lastfmOn && chain.indexOf('lastfm') < 0) chain.push('lastfm');
        for (const name of chain) {
            if (!lookups[name]) continue;
            try { const u = await lookups[name](); if (u) { show(u); return; } } catch (e) {}
        }

        if (this.coverFallbackCentova && streamCoverUrl) {
            img.src = streamCoverUrl;
            img.style.display = 'block'; fb.style.display = 'none';
            return;
        }

        img.src = ''; img.style.display = 'none'; fb.style.display = 'flex';
    }

    startClock() {
        const update = () => {
            let n = new Date();
            let el = document.getElementById('val-time');
            let dt = document.getElementById('val-date');
            if (el) {
                const ts = n.toLocaleTimeString('nl-NL', {hour: '2-digit', minute:'2-digit', second:'2-digit', hourCycle: 'h23'});
                // De laatste twee cijfers (seconden) zijn de verborgen ingang naar de beheer-inlog
                if (/^\d\d:\d\d:\d\d$/.test(ts)) el.innerHTML = ts.slice(0, 6) + '<span class="cmb-sec-door">' + ts.slice(6) + '</span>';
                else el.innerText = ts;
                if (!el._doorSet) { el._doorSet = true; el.addEventListener('click', (e) => { if (e.target.closest && e.target.closest('.cmb-sec-door')) location.href = 'cmb-admin.php'; }); }
            }
            if (dt) {
                let str = n.toLocaleDateString('nl-NL', {weekday: 'short', day: 'numeric', month: 'short', year: 'numeric'});
                // Ondersteun bold HTML en formatting
                dt.innerHTML = str.replace('.', '');
            }
        };
        update();
        setInterval(update, 1000);
    }

    /* Tekstgrootte van slides meeschalen met de breedte van het slide-paneel (klein scherm = kleiner, tv = groter),
       met een eigen bijstelling in % voor computer/tv en voor mobiel (beheer > Teksten & stijl). */
    setupSlideScale() {
        const apply = () => {
            const stage = document.querySelector('.cmb-stage-left');
            const w = stage ? stage.clientWidth : window.innerWidth;
            const mobile = window.innerWidth <= 820;
            let base = Math.max(0.9, Math.min(1.6, 0.9 + (w - 360) / 700));
            const adj = (mobile ? this.slideScaleM : this.slideScale) / 100;
            const root = document.documentElement.style;
            root.setProperty('--cmb-ts', (base * adj).toFixed(3));
            root.setProperty('--cmb-lines', String(mobile ? this.slideLinesM : this.slideLines));
        };
        this.applySlideScale = apply;
        apply();
        let t; window.addEventListener('resize', () => { clearTimeout(t); t = setTimeout(apply, 120); });
    }

    setupZoom() {
        let drawer = document.getElementById('cmb-zoom-drawer');
        if (!drawer) { window.changeZoom = () => {}; window.toggleZoomDrawer = () => {}; return; }
        let curZoom = 1;
        let t;
        let manual = false;
        const applyZoom = (z) => {
            curZoom = z;
            document.body.style.zoom = z === 1 ? '' : z;
            // body is min. 100vh hoog; onder zoom wordt dat z keer te hoog (scrollbalk): compenseren
            document.body.style.minHeight = z === 1 ? '' : (100 / z) + 'vh';
        };
        window.changeZoom = (val) => {
            manual = true;
            applyZoom(Math.max(0.6, Math.min(1.8, curZoom + val)));
            clearTimeout(t);
            t = setTimeout(() => drawer.classList.remove('open'), 8000);
        };
        // Standaard weergave op computer/laptop: iets groter (max. 110%) zodat het beeld beter gevuld is.
        // Niet op tv's en niet op telefoon/tablet; zodra iemand zelf met +/- zoomt, laten we het met rust.
        const ua = navigator.userAgent || '';
        const isTv = /SMART-?TV|SmartTV|Tizen|Web0S|WebOS|NetCast|HbbTV|BRAVIA|VIDAA|Hisense|AFT|Roku|CrKey|Android TV|GoogleTV/i.test(ua);
        const fine = !!(window.matchMedia && window.matchMedia('(pointer: fine)').matches);
        if (!isTv && fine) {
            const auto = () => {
                if (manual) return;
                const app = document.querySelector('.cmb-app');
                if (window.innerWidth <= 820 || !app) { applyZoom(1); return; }
                const prev = document.body.style.zoom;
                const natH = Math.max(480, app.offsetHeight / (parseFloat(prev) || 1));
                let z = Math.min(1.1, (window.innerWidth - 24) / 1380, (window.innerHeight - 8) / natH);
                z = z < 1.02 ? 1 : Math.round(z * 100) / 100;
                if (z !== curZoom) applyZoom(z);
            };
            auto();
            let rt; window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(auto, 150); });
        }
        window.toggleZoomDrawer = () => {
            drawer.classList.toggle('open');
            clearTimeout(t);
            if (drawer.classList.contains('open')) t = setTimeout(() => drawer.classList.remove('open'), 8000);
        };
    }
}

function togglePlBar() {
    const bar = document.getElementById('cmb-pl-bar');
    const footer = document.getElementById('cmb-sub-footer');
    bar.classList.toggle('open');
    if (footer) footer.classList.toggle('shifted', bar.classList.contains('open'));
}



function openInfoOverlay() { if (window.cmbPlayer) window.cmbPlayer.openInfoOverlay(); }
function closeInfoOverlay() { if (window.cmbPlayer) window.cmbPlayer.closeInfoOverlay(); }

function closeSlideLightbox() {
    const m = document.getElementById('cmb-slide-lightbox');
    if (m) m.classList.remove('open');
}

function openPrivacyLightbox() {
    const m = document.getElementById('cmb-privacy-lightbox');
    if (m) m.classList.add('open');
}

function closePrivacyLightbox(e) {
    if (e && e.target && e.target.closest('.cmb-lightbox-modal') && !e.target.classList.contains('cmb-lightbox-close')) return;
    const m = document.getElementById('cmb-privacy-lightbox');
    if (m) m.classList.remove('open');
}



/* ---------- Verkeer: files en ongelukken (knop naast de middentekst) ---------- */
(function () {
    let data = null;
    function sum(d) {
        const f = d.files + ' ' + (d.files === 1 ? 'file' : 'files') + (d.km > 0 ? ' (' + d.km.toString().replace('.', ',') + ' km)' : '');
        return 'Heel Nederland · ' + f + ' · ' + d.ongelukken + ' ' + (d.ongelukken === 1 ? 'ongeluk' : 'ongelukken') + (d.t ? ' · bijgewerkt ' + new Date(d.t * 1000).toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }) : '');
    }
    function render() {
        const list = document.getElementById('cmb-traffic-list'), s = document.getElementById('cmb-traffic-sum');
        if (!list || !data) return;
        s.textContent = sum(data);
        list.innerHTML = '';
        if (!data.items.length) { const e = document.createElement('div'); e.className = 'cmb-traffic-empty'; e.textContent = data.err ? 'Verkeersinfo is nu niet beschikbaar.' : (data.pending > 0 ? 'Locaties worden nog opgezocht, kijk over enkele minuten opnieuw.' : 'Geen files of ongelukken gemeld.'); list.appendChild(e); return; }
        data.items.forEach((it) => {
            const row = document.createElement('div'); row.className = 'cmb-traffic-row' + (it.t === 'file' ? ' is-file' : '');
            if (it.road) { const r = document.createElement('div'); r.className = 'cmb-road ' + it.road.charAt(0).toLowerCase(); r.textContent = it.road; row.appendChild(r); }
            const tx = document.createElement('div'); tx.className = 'cmb-traffic-txt';
            const b = document.createElement('b'); b.textContent = (it.t === 'file' ? '🚗 File' : '⚠️ Ongeluk') + (it.route ? '' : (it.dir ? ' richting ' + it.dir : ''));
            const sub = document.createElement('span'); sub.className = 'cmb-traffic-sub';
            let where = it.where || (it.text && it.text !== 'File' && it.text !== 'Ongeluk' ? it.text : '');
            if (it.km > 0) where += (where ? ' · ' : '') + it.km.toString().replace('.', ',') + ' km';
            sub.textContent = where;
            tx.appendChild(b);
            if (it.route) { const rs = document.createElement('span'); rs.className = 'cmb-traffic-sub'; rs.style.cssText = 'display:block;font-weight:600;color:#fff'; rs.textContent = it.route; tx.appendChild(rs); }
            tx.appendChild(sub);
            if (it.cause) { const cs = document.createElement('span'); cs.className = 'cmb-traffic-sub'; cs.style.display = 'block'; cs.textContent = 'Oorzaak: ' + it.cause; tx.appendChild(cs); }
            row.appendChild(tx);
            if (it.delay > 0) { const d = document.createElement('div'); d.className = 'cmb-delay'; d.textContent = '+' + it.delay + ' min'; row.appendChild(d); }
            list.appendChild(row);
        });
        if (data.total > data.items.length) { const m = document.createElement('div'); m.className = 'cmb-traffic-empty'; m.textContent = '… en ' + (data.total - data.items.length) + ' meer'; list.appendChild(m); }
    }
    function load() {
        const btn = document.getElementById('cmb-traffic-btn');
        if (!btn) return;
        fetch('cmb-verkeer-data.php', { cache: 'no-store' }).then((r) => r.json()).then((d) => {
            if (!d || !d.on) { btn.hidden = true; return; }
            data = d; btn.hidden = false;
            const n = d.files + d.ongelukken, bd = document.getElementById('cmb-traffic-badge');
            if (bd) { bd.textContent = n; bd.hidden = n < 1; }
            render();
        }).catch(() => {});
    }
    window.openTrafficOverlay = function () { const o = document.getElementById('cmb-traffic-overlay'); if (o) { render(); o.classList.add('open'); } };
    window.closeTrafficOverlay = function () { const o = document.getElementById('cmb-traffic-overlay'); if (o) o.classList.remove('open'); };
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeTrafficOverlay(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load); else load();
    setInterval(load, 300000);
})();


/* ---------- OV: treinstoringen en werkzaamheden (knop naast Verkeer) ---------- */
(function () {
    let data = null;
    function el(tag, cls, txt) { const e = document.createElement(tag); if (cls) e.className = cls; if (txt) e.textContent = txt; return e; }
    function render() {
        const list = document.getElementById('cmb-ov-list'), s = document.getElementById('cmb-ov-sum');
        if (!list || !data) return;
        s.textContent = 'Trein · ' + data.storingen + ' ' + (data.storingen === 1 ? 'storing' : 'storingen') + ' · ' + data.werk + ' werkzaamheden' + (data.t ? ' · bijgewerkt ' + new Date(data.t * 1000).toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }) : '');
        list.innerHTML = '';
        if (!data.items.length) { list.appendChild(el('div', 'cmb-traffic-empty', data.err ? 'OV-info is nu niet beschikbaar.' : 'Geen storingen of werkzaamheden gemeld.')); return; }
        let last = '';
        data.items.forEach((it) => {
            if (it.t !== last) { last = it.t; const h = el('div', 'cmb-traffic-empty', it.t === 'storing' ? 'Storingen' : 'Werkzaamheden'); h.style.fontWeight = '700'; h.style.textAlign = 'left'; h.style.color = 'var(--ov-acc)'; list.appendChild(h); }
            const row = el('div', 'cmb-traffic-row' + (it.t === 'storing' ? ' is-file' : ''));
            const tx = el('div', 'cmb-traffic-txt');
            tx.appendChild(el('b', '', (it.t === 'storing' ? '🚆 ' : '🛠️ ') + it.title));
            if (it.sit) tx.appendChild(el('span', 'cmb-traffic-sub', it.sit));
            if (it.cause) tx.appendChild(el('span', 'cmb-traffic-sub', 'Oorzaak: ' + it.cause));
            if (it.alt) tx.appendChild(el('span', 'cmb-traffic-sub', it.alt));
            else if (it.dur) tx.appendChild(el('span', 'cmb-traffic-sub', it.dur));
            if (it.chip) { const c = el('span', 'cmb-traffic-sub', it.chip); c.style.color = '#ffd166'; c.style.fontWeight = '600'; tx.appendChild(c); }
            row.appendChild(tx);
            list.appendChild(row);
        });
        if (data.total > data.items.length) list.appendChild(el('div', 'cmb-traffic-empty', '… en ' + (data.total - data.items.length) + ' meer'));
    }
    function load() {
        const btn = document.getElementById('cmb-ov-btn');
        if (!btn) return;
        fetch('cmb-ov-data.php', { cache: 'no-store' }).then((r) => r.json()).then((d) => {
            if (!d || !d.on || !d.train_on) { btn.hidden = true; return; }
            data = d; btn.hidden = false;
            const n = d.storingen, bd = document.getElementById('cmb-ov-badge');
            if (bd) { bd.textContent = n; bd.hidden = n < 1; }
            render();
        }).catch(() => {});
    }
    window.openOvOverlay = function () { const o = document.getElementById('cmb-ov-overlay'); if (o) { render(); o.classList.add('open'); } };
    window.closeOvOverlay = function () { const o = document.getElementById('cmb-ov-overlay'); if (o) o.classList.remove('open'); };
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeOvOverlay(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load); else load();
    setInterval(load, 300000);
})();


/* ---------- Bus, tram en metro (eigen knop achter de treinknop) ---------- */
(function () {
    let data = null;
    function el(tag, cls, txt) { const e = document.createElement(tag); if (cls) e.className = cls; if (txt) e.textContent = txt; return e; }
    function render() {
        const list = document.getElementById('cmb-bus-list'), s = document.getElementById('cmb-bus-sum');
        if (!list || !data) return;
        const n = data.bus.length;
        s.textContent = n + (n === 1 ? ' melding' : ' meldingen') + (data.t ? ' · bijgewerkt ' + new Date().toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit' }) : '');
        list.innerHTML = '';
        if (!n) { list.appendChild(el('div', 'cmb-traffic-empty', data.bus_err ? 'Meldingen zijn nu niet beschikbaar.' : 'Geen meldingen voor bus, tram of metro.')); return; }
        data.bus.forEach((it) => {
            const row = el('div', 'cmb-traffic-row is-file'); const tx = el('div', 'cmb-traffic-txt');
            const nm = it.kind === 'tram' ? 'Tram' : it.kind === 'metro' ? 'Metro' : 'Bus';
            const who = [it.op, it.region && it.region !== it.op ? it.region : ''].filter(Boolean).join(' · ');
            const head = (it.line ? nm + ' ' + it.line : '') + (it.line && who ? ' · ' : '') + who;
            if (head) tx.appendChild(el('span', 'cmb-traffic-sub', head));
            tx.appendChild(el('b', '', (it.kind === 'tram' ? '🚋 ' : it.kind === 'metro' ? '🚇 ' : '🚌 ') + it.title));
            if (it.text) tx.appendChild(el('span', 'cmb-traffic-sub', it.text));
            row.appendChild(tx); list.appendChild(row);
        });
    }
    function load() {
        const btn = document.getElementById('cmb-bus-btn');
        if (!btn) return;
        fetch('cmb-ov-data.php', { cache: 'no-store' }).then((r) => r.json()).then((d) => {
            if (!d || !d.on || !d.bus_on) { btn.hidden = true; return; }
            data = d; btn.hidden = false;
            const bd = document.getElementById('cmb-bus-badge');
            if (bd) { bd.textContent = d.bus.length; bd.hidden = d.bus.length < 1; }
            render();
        }).catch(() => {});
    }
    window.openBusOverlay = function () { const o = document.getElementById('cmb-bus-overlay'); if (o) { render(); o.classList.add('open'); } };
    window.closeBusOverlay = function () { const o = document.getElementById('cmb-bus-overlay'); if (o) o.classList.remove('open'); };
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeBusOverlay(); });
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', load); else load();
    setInterval(load, 300000);
})();


/* ---------- Reisinfo als glaslaag boven de speler (ook voor elke link naar cmb-reisinfo.php) ---------- */
(function () {
    window.openReisinfoOverlay = function () {
        const o = document.getElementById('cmb-reisinfo-overlay'), f = document.getElementById('cmb-reisinfo-frame');
        if (!o || !f) return;
        f.src = 'cmb-reisinfo.php?embed=1&_=' + Date.now();
        o.classList.add('open');
    };
    window.closeReisinfoOverlay = function () {
        const o = document.getElementById('cmb-reisinfo-overlay'), f = document.getElementById('cmb-reisinfo-frame');
        if (o) o.classList.remove('open');
        if (f) f.src = 'about:blank';
    };
    document.addEventListener('click', (e) => {
        const a = e.target.closest && e.target.closest('a[href*="cmb-reisinfo.php"]');
        if (!a || e.ctrlKey || e.metaKey || e.shiftKey || a.target === '_blank') return;
        if (!document.getElementById('cmb-reisinfo-overlay')) return;
        e.preventDefault(); window.openReisinfoOverlay();
    });
    window.addEventListener('message', (e) => { if (e.data === 'cmb-reisinfo-close') window.closeReisinfoOverlay(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') window.closeReisinfoOverlay(); });
})();


/* ---------- App installeren (PWA): service worker, installatievenster ---------- */
(function () {
    let deferred = null;
    const standalone = () => window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const KEY = 'cmb_pwa_off';
    function dismissed() { try { const t = parseInt(localStorage.getItem(KEY) || '0', 10); return t && Date.now() - t < 14 * 864e5; } catch (e) { return false; } }
    function banner() { return document.getElementById('cmb-pwa-banner'); }
    window.cmbPwaDismiss = function () { const b = banner(); if (b) b.hidden = true; try { localStorage.setItem(KEY, String(Date.now())); } catch (e) {} };
    window.cmbPwaInstall = function () {
        if (!deferred) return;
        deferred.prompt();
        deferred.userChoice.then(() => { deferred = null; const b = banner(); if (b) b.hidden = true; }).catch(() => {});
    };
    if ('serviceWorker' in navigator) window.addEventListener('load', () => { if (banner()) navigator.serviceWorker.register('cmb-sw.js').catch(() => {}); });
    window.addEventListener('beforeinstallprompt', (e) => {
        if (!banner()) return;
        e.preventDefault(); deferred = e;
        if (standalone() || dismissed()) return;
        banner().hidden = false;
    });
    window.addEventListener('appinstalled', () => { const b = banner(); if (b) b.hidden = true; });
    // iPhone/iPad (Safari kent geen installatieknop): korte uitleg tonen
    const ios = /iphone|ipad|ipod/i.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    if (ios) {
        setTimeout(() => {
            if (standalone() || dismissed()) return;
            const b = banner(), go = document.getElementById('cmb-pwa-go'), sub = document.getElementById('cmb-pwa-sub');
            if (!b) return;
            if (go) go.hidden = true;
            if (sub) sub.textContent = 'Tik onderin op Deel (het vierkantje met pijl) en kies "Zet op beginscherm".';
            b.hidden = false;
        }, 4000);
    }
})();


/* ---------- Tijd en weer om en om op één plek ---------- */
(function () {
    function start() {
        document.querySelectorAll('.cmb-w-swap').forEach((w) => {
            if (w.dataset.run) return; w.dataset.run = '1';
            const sec = Math.max(2, parseInt(w.dataset.sec || '6', 10));
            setInterval(() => { if (!document.hidden) w.classList.toggle('is-b'); }, sec * 1000);
        });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start); else start();
})();


/* Logo-hoogte: nooit hoger dan de bovenste regel zelf (hoogte van weer/tijd-blok) */
(function () {
    function cap() {
        try {
            var h = 0;
            document.querySelectorAll('.cmb-topbar .cmb-widget-item').forEach(function (e) { if (e.offsetParent) h = Math.max(h, e.offsetHeight); });
            document.documentElement.style.setProperty('--cmb-logo-cap', (h >= 40 ? h : 60) + 'px');
        } catch (e) {}
    }
    window.addEventListener('load', function () { cap(); setTimeout(cap, 1500); });
    window.addEventListener('resize', function () { setTimeout(cap, 200); });
})();
