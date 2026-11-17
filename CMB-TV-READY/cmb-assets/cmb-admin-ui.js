/**
 * CHAZZO MUSIC BOX (CMB) - BEHEER GEBRUIKSGEMAK
 * Bestandsnaam: cmb-admin-ui.js
 * - waarschuwt bij niet-opgeslagen wijzigingen
 * - zwevende opslaan-knop + Ctrl/Cmd+S
 * - meldingen verdwijnen vanzelf
 * - snelle thema's voor de frame-verlichting (alleen als die velden op de pagina staan)
 */
(function () {
    'use strict';
    var form = document.getElementById('adminForm');
    var saveBtn = form ? form.querySelector('.btn-save') : null;
    var dirty = false;
    var submitting = false;

    var css = document.createElement('style');
    css.textContent =
        '.cmb-float-save{position:fixed;right:18px;bottom:18px;z-index:9999;background:linear-gradient(135deg,#ffd166,#ff5bc8);color:#070812;border:none;border-radius:999px;padding:12px 20px;font-weight:900;font-size:14px;cursor:pointer;box-shadow:0 8px 28px rgba(0,0,0,.6);display:none}' +
        '.cmb-float-save.show{display:block}' +
        '.cmb-dirty-note{position:fixed;left:18px;bottom:22px;z-index:9999;background:#2a2e52;color:#ffd166;border-radius:8px;padding:8px 12px;font-size:12px;font-weight:700;display:none}' +
        '.cmb-dirty-note.show{display:block}' +
        '.cmb-theme-bar{display:flex;flex-wrap:wrap;gap:8px;margin-top:8px}' +
        '.cmb-theme-bar button{background:#1d2142;color:#fff;border:1px solid rgba(255,255,255,.18);border-radius:999px;padding:8px 14px;font-weight:800;font-size:12px;cursor:pointer}' +
        '.cmb-theme-bar button:hover{border-color:#ffd166;color:#ffd166}';
    document.head.appendChild(css);

    function markDirty() {
        if (dirty || submitting) return;
        dirty = true;
        if (floatBtn) floatBtn.classList.add('show');
        if (note) note.classList.add('show');
    }

    var floatBtn = null, note = null;
    if (form && saveBtn) {
        floatBtn = document.createElement('button');
        floatBtn.type = 'button';
        floatBtn.className = 'cmb-float-save';
        floatBtn.textContent = '💾 Opslaan (Ctrl+S)';
        floatBtn.addEventListener('click', function () { saveBtn.click(); });
        document.body.appendChild(floatBtn);

        note = document.createElement('div');
        note.className = 'cmb-dirty-note';
        note.textContent = 'Er zijn niet-opgeslagen wijzigingen';
        document.body.appendChild(note);

        form.addEventListener('input', markDirty);
        form.addEventListener('change', markDirty);
        form.addEventListener('submit', function () {
            submitting = true; dirty = false;
            try { sessionStorage.setItem('cmb_scroll_' + location.pathname + location.search, String(window.scrollY)); } catch (e) {}
        });
        // Terug op dezelfde hoogte na opslaan (alleen direct na een opslag)
        try {
            var key = 'cmb_scroll_' + location.pathname + location.search, y = sessionStorage.getItem(key);
            if (y !== null && document.querySelector('.msg')) {
                sessionStorage.removeItem(key);
                window.addEventListener('load', function () { window.scrollTo(0, parseInt(y, 10) || 0); });
            }
        } catch (e) {}
    }

    // Alle tekstvelden in één pakketje versturen (omzeilt de serverlimiet max_input_vars van 1000 velden)
    if (form && window.FormData && 'onformdata' in form) {
        form.addEventListener('formdata', function (e) {
            var fd = e.formData, payload = {}, keys = [];
            fd.forEach(function (v, k) {
                if (typeof v === 'string' && k !== 'save_admin' && k !== 'save_studio' && k !== 'active_tab_context') {
                    payload[k] = v; keys.push(k);
                }
            });
            keys.forEach(function (k) { fd.delete(k); });
            fd.set('cmb_payload', JSON.stringify(payload));
        });
    }

    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S') && saveBtn) {
            e.preventDefault();
            saveBtn.click();
        }
    });

    // Statusmelding laten verdwijnen
    var msgEl = document.querySelector('.msg');
    if (msgEl) {
        setTimeout(function () {
            msgEl.style.transition = 'opacity .6s';
            msgEl.style.opacity = '0';
            setTimeout(function () { msgEl.style.display = 'none'; }, 700);
        }, 6000);
    }

    // Een knop "markeer als gewijzigd" voor scripts die velden vullen
    window.cmbMarkDirty = markDirty;

    /* ---- Snelle thema's voor frame-verlichting ---- */
    var THEMES = [
        { label: '🎄 Kerst', mode: 'snow_edge', theme: 'cyan' },
        { label: '🎃 Halloween', mode: 'fire_edge', theme: 'fire' },
        { label: '🎉 Feest / Nieuwjaar', mode: 'confetti_edge', theme: 'rainbow' },
        { label: '🧡 Oranje', mode: 'chaser', theme: 'custom', col: '#ff7a00' },
        { label: '💖 Retro Neon', mode: 'chaser', theme: 'magenta' },
        { label: '🏆 Goud Gala', mode: 'gold_shimmer', theme: 'gold' },
        { label: '🌈 Regenboog', mode: 'rainbow_run', theme: 'rainbow' },
        { label: '⚡ Elektrisch', mode: 'electric_pulse', theme: 'cyan' }
    ];

    function setVal(id, v) {
        var el = document.getElementById(id);
        if (!el) return;
        el.value = v;
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function applyTheme(t, keys) {
        keys.forEach(function (key) {
            setVal(key + '_light_mode', t.mode);
            setVal(key + '_light_theme', t.theme);
            if (t.col) setVal(key + '_custom_col', t.col);
            if (typeof window.cmbMarkFrameTiles === 'function') window.cmbMarkFrameTiles(key);
        });
        markDirty();
    }

    var left = document.getElementById('fl_left_light_mode');
    var right = document.getElementById('fl_right_light_mode');
    if (left || right) {
        var keys = [];
        if (left) keys.push('fl_left');
        if (right) keys.push('fl_right');
        if (document.getElementById('fl_own_light_mode')) keys.push('fl_own');
        var host = document.querySelector('.lighting-box');
        if (host && host.parentNode) {
            var wrap = document.createElement('div');
            wrap.style.cssText = 'margin-bottom:14px;padding:12px;border:1px solid rgba(255,209,102,.3);border-radius:10px;background:rgba(5,6,14,.7)';
            var h = document.createElement('div');
            h.style.cssText = 'font-size:12px;color:#ffd166;font-weight:900;text-transform:uppercase;letter-spacing:.05em';
            h.textContent = '🎨 Snelle thema\'s (vult beide frames in; daarna nog opslaan)';
            var bar = document.createElement('div');
            bar.className = 'cmb-theme-bar';
            THEMES.forEach(function (t) {
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = t.label;
                b.addEventListener('click', function () { applyTheme(t, keys); });
                bar.appendChild(b);
            });
            wrap.appendChild(h);
            wrap.appendChild(bar);
            host.parentNode.insertBefore(wrap, host);
        }
    }
})();

/* ==========================================================================
   FRAME-PANELEN: live voorbeeld, snelle hulpknoppen, kopiëren, wisselen
   ========================================================================== */
(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }
    function val(id, d) { var e = $(id); return e ? e.value : d; }
    function num(id, d) { var n = parseFloat(val(id, '')); return isNaN(n) ? d : n; }
    function fire(el) {
        if (!el) return;
        el.dispatchEvent(new Event('input', { bubbles: true }));
        el.dispatchEvent(new Event('change', { bubbles: true }));
    }
    function mix(a, b, t) {
        function p(h) { return [1, 3, 5].map(function (i) { return parseInt(h.substr(i, 2), 16); }); }
        var x = p(a), y = p(b);
        return '#' + x.map(function (v, i) {
            var m = Math.round(v + (y[i] - v) * t).toString(16);
            return m.length < 2 ? '0' + m : m;
        }).join('');
    }
    var DEG = { diag1: '45deg', diag2: '135deg', tb: '180deg', lr: '90deg' };

    function updatePreview(key) {
        var box = $(key + '_pv');
        if (!box) return;
        var sides = ['top', 'right', 'bottom', 'left'];
        sides.forEach(function (s) {
            var w = num(key + '_side_' + s + '_w', 0);
            var cap = s.charAt(0).toUpperCase() + s.slice(1);
            box.style['border' + cap] = w > 0 ? (w + 'px ' + val(key + '_side_' + s + '_s', 'solid') + ' ' + val(key + '_side_' + s + '_c', '#63f6ff')) : 'none';
        });
        var all = Math.min(60, Math.max(0, num(key + '_corner_size', 24)));
        function r(c) {
            var on = $(key + '_corner_' + c + '_on');
            if (on && !on.checked) return 0;
            var own = val(key + '_corner_' + c + '_r', '');
            return Math.min(60, own === '' ? all : Math.max(0, parseFloat(own) || 0));
        }
        box.style.borderRadius = [r('tl'), r('tr'), r('br'), r('bl')].map(function (v) { return v + 'px'; }).join(' ');
        var shadow = [];
        var glow = $(key + '_corner_glow');
        var col = val(key + '_corner_col', '#63f6ff');
        if (val(key + '_corner_mode', 'sync') === 'custom') {
            var cw = Math.max(0, num(key + '_corner_w', 2));
            if (cw > 0) shadow.push('inset 0 0 0 ' + cw + 'px ' + col);
            if (glow && glow.checked) shadow.push('0 0 14px ' + col);
        } else if (glow && glow.checked) {
            shadow.push('0 0 14px ' + val(key + '_side_top_c', '#63f6ff'));
        }
        box.style.boxShadow = shadow.join(', ');

        var anim = $(key + '_pv_anim');
        if (anim) {
            var mode = val(key + '_light_mode', 'none');
            var tile = document.querySelector('.cmb-frame-tile-grid[data-key="' + key + '"] .cmb-frame-tile[data-mode="' + mode + '"]');
            var html = tile ? tile.innerHTML : '';
            if (anim.getAttribute('data-mode') !== mode) {
                anim.innerHTML = html;
                anim.setAttribute('data-mode', mode);
            }
        }
        var smp = $(key + '_grad_sample');
        if (smp) {
            smp.style.background = 'linear-gradient(' + (DEG[val(key + '_grad_angle', 'diag2')] || '135deg') + ', ' + val(key + '_grad_col1', '#63f6ff') + ', ' + val(key + '_grad_col2', '#ff5bc8') + ')';
        }
    }

    window.cmbSidesSame = function (key) {
        var c = val(key + '_side_top_c', '#63f6ff'), w = val(key + '_side_top_w', '2'), s = val(key + '_side_top_s', 'solid');
        ['right', 'bottom', 'left'].forEach(function (side) {
            if ($(key + '_side_' + side + '_c')) $(key + '_side_' + side + '_c').value = c;
            if ($(key + '_side_' + side + '_w')) $(key + '_side_' + side + '_w').value = w;
            if ($(key + '_side_' + side + '_s')) $(key + '_side_' + side + '_s').value = s;
        });
        fire($(key + '_side_top_c'));
    };
    window.cmbSidesGradient = function (key) {
        var c1 = val(key + '_grad_col1', '#63f6ff'), c2 = val(key + '_grad_col2', '#ff5bc8');
        var map = { top: c1, right: mix(c1, c2, 1 / 3), bottom: mix(c1, c2, 2 / 3), left: c2 };
        Object.keys(map).forEach(function (s) { if ($(key + '_side_' + s + '_c')) $(key + '_side_' + s + '_c').value = map[s]; });
        fire($(key + '_side_top_c'));
    };
    window.cmbSidesOff = function (key) {
        ['top', 'right', 'bottom', 'left'].forEach(function (s) { if ($(key + '_side_' + s + '_w')) $(key + '_side_' + s + '_w').value = 0; });
        fire($(key + '_side_top_w'));
    };
    window.cmbCopyFrame = function (key) {
        var other = key === 'fl_left' ? 'fl_right' : (key === 'fl_own' ? 'fl_right' : 'fl_left');
        var name = other === 'fl_left' ? 'het linker frame' : 'het rechter frame';
        if (!confirm('Alle instellingen van dit frame kopiëren naar ' + name + '? (Daarna nog opslaan.)')) return;
        var src = $('box_light_' + key), dst = $('box_light_' + other);
        if (!src || !dst) return;
        src.querySelectorAll('input[id], select[id]').forEach(function (el) {
            if (el.id.indexOf(key + '_') !== 0) return;
            var t = $(other + el.id.substr(key.length));
            if (!t) return;
            if (el.type === 'checkbox') t.checked = el.checked; else t.value = el.value;
        });
        if (typeof window.cmbMarkFrameTiles === 'function') window.cmbMarkFrameTiles(other);
        updatePreview(other);
        if (typeof window.cmbMarkDirty === 'function') window.cmbMarkDirty();
    };
    window.cmbShowFrame = function (key) {
        try { sessionStorage.setItem('cmb_frame_side', key); } catch (e) {}
        document.querySelectorAll('.cmb-frame-panel').forEach(function (p) {
            p.classList.toggle('cmb-hidden', p.getAttribute('data-key') !== key);
        });
        document.querySelectorAll('.cmb-switch button[data-frame]').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-frame') === key);
        });
        updatePreview(key);
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.cmb-frame-panel').forEach(function (panel) {
            var key = panel.getAttribute('data-key');
            var h = function () { updatePreview(key); };
            panel.addEventListener('input', h);
            panel.addEventListener('change', h);
            panel.addEventListener('click', function (e) {
                if (e.target.closest && e.target.closest('.cmb-frame-tile')) setTimeout(h, 0);
            });
            updatePreview(key);
        });
        var first = document.querySelector('.cmb-switch button[data-frame]'), saved = null;
        try { saved = sessionStorage.getItem('cmb_frame_side'); } catch (e) {}
        if (saved && document.getElementById('box_light_' + saved)) window.cmbShowFrame(saved);
        else if (first) window.cmbShowFrame(first.getAttribute('data-frame'));
    });
})();

/* ==========================================================================
   STIJL & LETTERTYPE-VAKKEN: levend voorbeeld; MAX BESTANDSGROOTTE
   ========================================================================== */
(function () {
    'use strict';
    var DEG = { none: '', tb: '180deg', lr: '90deg', diag1: '45deg', diag2: '135deg' };

    function sampleFor(card) {
        var p = card.getAttribute('data-prefix');
        var smp = card.querySelector('.cmb-style-sample');
        if (!p || !smp) return;
        function g(s) { var e = document.getElementById(p + '_' + s); return e ? e.value : ''; }
        var txt = document.getElementById(p + '_val');
        smp.textContent = (txt && txt.value.trim() !== '') ? txt.value.replace(/<[^>]*>/g, '').slice(0, 40) : 'Voorbeeld Aa';
        smp.style.fontFamily = "'" + g('font') + "', sans-serif";
        smp.style.fontSize = Math.min(26, Math.max(10, parseInt(g('size'), 10) || 14)) + 'px';
        var so = document.getElementById(p + '_shadow_on'); smp.style.textShadow = (so && !so.checked) ? 'none' : '0 1px 3px ' + (g('shadow') || '#000');
        var mode = g('grad_mode');
        if (mode && mode !== 'none' && DEG[mode]) {
            smp.style.background = 'linear-gradient(' + DEG[mode] + ', ' + g('color') + ', ' + g('grad_col2') + ')';
            smp.style.webkitBackgroundClip = 'text';
            smp.style.backgroundClip = 'text';
            smp.style.webkitTextFillColor = 'transparent';
            smp.style.color = 'transparent';
        } else {
            smp.style.background = '#05060d';
            smp.style.webkitBackgroundClip = 'border-box';
            smp.style.backgroundClip = 'border-box';
            smp.style.webkitTextFillColor = g('color') || '#fff';
            smp.style.color = g('color') || '#fff';
        }
    }

    function loadFont(name) {
        if (!name || document.getElementById('cmb-font-' + name.replace(/\W+/g, '_'))) return;
        var l = document.createElement('link');
        l.id = 'cmb-font-' + name.replace(/\W+/g, '_');
        l.rel = 'stylesheet';
        l.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name).replace(/%20/g, '+') + '&display=swap';
        document.head.appendChild(l);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.cmb-style-card').forEach(function (card) {
            var h = function (e) {
                var f = card.querySelector('select[id$="_font"]');
                if (f) loadFont(f.value);
                sampleFor(card);
                // Alleen een wijziging in het stijlpaneel telt (niet het typen van de tekst zelf)
                if (e && e.target && e.target.closest && e.target.closest('.cmb-style-panel')) {
                    var t = card.querySelector('input[name$="_touched"]');
                    if (t) t.value = '1';
                }
            };
            card.addEventListener('input', h);
            card.addEventListener('change', h);
            sampleFor(card);
        });

        // Bestandsgrootte-controle (max. 15 MB voor clips)
        document.querySelectorAll('input[type="file"][data-maxmb]').forEach(function (inp) {
            var max = parseFloat(inp.getAttribute('data-maxmb')) * 1024 * 1024;
            var note = document.createElement('span');
            note.className = 'cmb-size-note';
            inp.insertAdjacentElement('afterend', note);
            inp.addEventListener('change', function () {
                var f = inp.files && inp.files[0];
                if (!f) { note.textContent = ''; return; }
                var mb = (f.size / 1048576).toFixed(1);
                if (f.size > max) {
                    note.className = 'cmb-size-note cmb-size-bad';
                    note.textContent = '✗ ' + mb + ' MB is te groot (max ' + inp.getAttribute('data-maxmb') + ' MB). Kies een kleinere clip.';
                    inp.value = '';
                } else {
                    note.className = 'cmb-size-note cmb-size-ok';
                    note.textContent = '✓ ' + mb + ' MB';
                }
            });
        });
    });
})();

// ---------- Mini-voorbeeld bij elk mediaveld (pc/Android upload of pad/link) ----------
// Groen vinkje = media ingesteld. ✕ = wissen (alleen bij dit veld; andere slides houden hun eigen media).
// Een nieuwe upload vervangt automatisch het oude pad.
(function () {
    'use strict';
    function ytId(u) { var m = /(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([\w-]{11})/.exec(u || ''); return m ? m[1] : ''; }
    function kindOf(src, file) {
        if (file) return file.type.indexOf('video') === 0 ? 'video' : (file.type.indexOf('image') === 0 ? 'image' : 'file');
        if (/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(\?|#|$)/i.test(src)) return 'video';
        if (/\.(jpe?g|png|gif|webp|avif|svg)(\?|#|$)/i.test(src)) return 'image';
        if (ytId(src)) return 'youtube';
        if (/vimeo\.com/i.test(src)) return 'vimeo';
        if (/^https?:/i.test(src)) return 'link';
        return 'image';
    }
    function pathFieldFor(inp) {
        var node = inp.parentElement;
        for (var i = 0; i < 4 && node; i++, node = node.parentElement) {
            if (node.querySelectorAll('input[type="file"]').length !== 1) break;
            var f = node.querySelector('input[type="text"].cell-url-input, input[type="text"][id$="_media_url"], input[type="text"][id$="_single_url"], input[type="text"][name$="_url"]');
            if (f) return { field: f, box: node };
        }
        return null;
    }
    function build(inp) {
        if (inp.getAttribute('data-mp') || inp.name === 'bundle') return;
        var found = pathFieldFor(inp);
        if (!found) return;
        inp.setAttribute('data-mp', '1');
        var path = found.field, box = found.box;
        var small = !!inp.closest('.carousel-cell');
        // Oude, losse voorbeeldjes in dezelfde rij vervangen door dit ene voorbeeld
        box.querySelectorAll('.media-thumb-preview').forEach(function (el) { if (!el.closest('summary')) el.remove(); });

        var wrap = document.createElement('div');
        wrap.className = 'cmb-mp' + (small ? ' small' : '');
        wrap.innerHTML = '<div class="cmb-mp-thumb"></div><span class="cmb-mp-ok" title="Media ingesteld">✓</span>' +
                         '<button type="button" class="cmb-mp-x" title="Media verwijderen" aria-label="Media verwijderen">✕</button>';
        var status = document.createElement('div');
        status.className = 'cmb-mp-status';
        var thumb = wrap.querySelector('.cmb-mp-thumb');
        var anchor = inp.closest('.field') || inp;
        if (small) { anchor.parentElement.insertBefore(wrap, anchor); anchor.parentElement.insertBefore(status, anchor); }
        else { anchor.parentElement.insertBefore(wrap, anchor); anchor.appendChild(status); }
        var objUrl = null;

        function render() {
            var file = inp.files && inp.files[0];
            var src = (path.value || '').trim();
            if (objUrl) { URL.revokeObjectURL(objUrl); objUrl = null; }
            var has = !!(file || src);
            wrap.classList.toggle('on', has);
            if (!has) { thumb.innerHTML = '<span class="cmb-mp-empty">Geen media</span>'; status.textContent = ''; status.className = 'cmb-mp-status'; return; }
            var k = kindOf(src, file), url = src;
            // Beheer op een subdomein: uploads staan bij de speler (zie uploads_url in cmb-config.php)
            if (window.CMB_UPLOADS_URL && src.indexOf('cmb-uploads/') === 0) url = window.CMB_UPLOADS_URL + src.slice(12);
            if (file) { objUrl = URL.createObjectURL(file); url = objUrl; }
            var h;
            if (k === 'video') h = '<video src="' + url + (file ? '' : '#t=0.5') + '" muted playsinline preload="metadata"></video>';
            else if (k === 'image') h = '<img alt="" src="' + url.replace(/"/g, '&quot;') + '">';
            else if (k === 'youtube') h = '<img alt="" src="https://img.youtube.com/vi/' + ytId(src) + '/mqdefault.jpg">';
            else h = '<span class="cmb-mp-empty">' + (k === 'vimeo' ? 'Vimeo' : '🔗 Link') + '</span>';
            thumb.innerHTML = h;
            status.className = 'cmb-mp-status on';
            status.textContent = file ? '✓ Nieuw: ' + file.name + ' (wordt bij opslaan geüpload)' : '✓ Media ingesteld';
        }
        inp.addEventListener('change', function () {
            if (inp.files && inp.files[0]) { path.value = ''; }   // nieuwe upload vervangt het oude pad
            render();
        });
        path.addEventListener('input', render);
        wrap.querySelector('.cmb-mp-x').addEventListener('click', function () {
            inp.value = ''; path.value = '';
            path.dispatchEvent(new Event('input', { bubbles: true }));
            var note = box.querySelector('.cmb-size-note'); if (note) note.remove();
            render();
        });
        render();
    }
    function init() { document.querySelectorAll('input[type="file"]').forEach(build); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

/* ==========================================================================
   🎲 VERRAS ME (random++): verzint binnen veilige grenzen een combinatie.
   Alleen het formulier verandert (met voorbeeld); pas bij Opslaan gaat het naar de speler.
   "↩ Terug" zet alles terug zoals het stond vóór de eerste 🎲.
   ========================================================================== */
(function () {
    var PALETTES = [
        ['#63f6ff', '#ffd166', '#ff5bc8', '#9d6cff'], ['#00e676', '#b9f6ca', '#64dd17', '#1de9b6'], ['#2f80ff', '#8ec5ff', '#00b0ff', '#536dfe'],
        ['#ff1744', '#ff8a80', '#ff5252', '#d50000'], ['#ff9100', '#ffd180', '#ff6d00', '#ffab40'], ['#b388ff', '#e1bee7', '#7c4dff', '#ea80fc'],
        ['#ff4fa3', '#ffc1e3', '#ff80ab', '#f50057'], ['#ffd166', '#fff1c1', '#ffb300', '#c99a2e'], ['#b3e5fc', '#e1f5fe', '#81d4fa', '#4fc3f7'],
        ['#1de9b6', '#a7ffeb', '#00bfa5', '#64ffda'], ['#69f0ae', '#e0f2f1', '#b9f6ca', '#00c853'], ['#c6ff00', '#f4ff81', '#aeea00', '#76ff03'],
        ['#ff7043', '#ffd54f', '#ff4081', '#ab47bc'], ['#00b8d4', '#80deea', '#2979ff', '#00e5ff'], ['#66bb6a', '#c5e1a5', '#a1887f', '#43a047'],
        ['#ff3d00', '#ffab00', '#dd2c00', '#ff6e40'], ['#e53935', '#ffd54f', '#43a047', '#ef9a9a'], ['#ff7518', '#b388ff', '#76ff03', '#ff9100'],
        ['#ff00ff', '#00ffff', '#ffff00', '#ff1493'], ['#ff4d6d', '#ffb3c1', '#ff758f', '#c9184a']
    ];
    var pick = function (a) { return a[Math.floor(Math.random() * a.length)]; };
    var rnd = function (min, max) { return Math.floor(min + Math.random() * (max - min + 1)); };
    var fire = function (el) { el.dispatchEvent(new Event('input', { bubbles: true })); el.dispatchEvent(new Event('change', { bubbles: true })); };
    var setVal = function (el, v) { if (!el) return; if (el.type === 'checkbox') el.checked = !!v; else el.value = v; fire(el); };
    var randomOption = function (sel, skip) {
        if (!sel || !sel.options.length) return;
        var opts = Array.prototype.filter.call(sel.options, function (o) { return !o.disabled && (!skip || !skip.test(o.value)); });
        if (opts.length) setVal(sel, pick(opts).value);
    };
    var snapshot = function (scope) {
        if (scope.__cmbUndo) return;
        var snap = [];
        scope.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.type === 'file' || el.type === 'hidden' && !/_light_mode$/.test(el.id)) return;
            snap.push([el, el.type === 'checkbox' ? el.checked : el.value]);
        });
        scope.__cmbUndo = snap;
    };
    var undo = function (scope) {
        if (!scope.__cmbUndo) return;
        scope.__cmbUndo.forEach(function (p) { if (p[0].type === 'checkbox') p[0].checked = p[1]; else p[0].value = p[1]; fire(p[0]); });
        scope.__cmbUndo = null;
        if (scope.__afterUndo) scope.__afterUndo();
    };
    var addButtons = function (where, scope, onRandom, label) {
        if (!where || !scope) return;
        var wrap = document.createElement('span');
        wrap.className = 'cmb-surprise-btns';
        wrap.style.cssText = 'display:inline-flex; gap:6px; margin-left:6px; vertical-align:middle;';
        var b = document.createElement('button'); b.type = 'button'; b.className = 'cmb-mini'; b.textContent = '🎲 ' + (label || 'Verras me');
        b.style.cssText = 'background:linear-gradient(135deg,#ffd166,#ff5bc8); color:#070812; font-weight:900;';
        var u = document.createElement('button'); u.type = 'button'; u.className = 'cmb-mini'; u.textContent = '↩ Terug'; u.style.display = 'none';
        b.onclick = function () { snapshot(scope); onRandom(); u.style.display = ''; if (typeof window.cmbMarkDirty === 'function') window.cmbMarkDirty(); };
        u.onclick = function () { undo(scope); u.style.display = 'none'; };
        wrap.appendChild(b); wrap.appendChild(u);
        where.appendChild(wrap);
    };

    document.addEventListener('DOMContentLoaded', function () {
        // ---- Frames (links en rechts los) ----
        document.querySelectorAll('.cmb-frame-panel').forEach(function (panel) {
            var key = panel.getAttribute('data-key'), q = function (n) { return document.getElementById(key + '_' + n) || panel.querySelector('[name="' + key + '_' + n + '"]'); };
            panel.__afterUndo = function () { if (typeof window.cmbMarkFrameTiles === 'function') window.cmbMarkFrameTiles(key); };
            addButtons(panel.querySelector('.cmb-fp-tools'), panel, function () {
                var pal = pick(PALETTES);
                var modeSel = q('light_mode');
                if (modeSel) {
                    randomOption(modeSel, /^none$/);
                    if (typeof window.cmbMarkFrameTiles === 'function') window.cmbMarkFrameTiles(key);
                }
                var th = q('light_theme');
                if (th) { setVal(th, Array.prototype.some.call(th.options, function (o) { return o.value === 'gradient'; }) && Math.random() < 0.6 ? 'gradient' : (Math.random() < 0.5 ? 'custom' : th.value)); if (Math.random() < 0.3) randomOption(th); }
                setVal(q('grad_col1'), pal[0]); setVal(q('grad_col2'), pal[2]); setVal(q('custom_col'), pal[0]);
                var ga = q('grad_angle'); if (ga) randomOption(ga);
                setVal(q('speed_s'), pick([2, 3, 4, 5, 6, 8, 10]));
                var dir = q('light_dir'); if (dir) setVal(dir, Math.random() < 0.5 ? 'cw' : 'ccw');
                setVal(q('chaser_w'), rnd(2, 6));
                var w = rnd(1, 4), sides = { top: pal[0], right: pal[2], bottom: pal[1], left: pal[0] };
                Object.keys(sides).forEach(function (s) {
                    setVal(q('side_' + s + '_c'), sides[s]);
                    setVal(q('side_' + s + '_w'), w);
                });
                setVal(q('corner_col'), pal[0]);
                setVal(q('corner_size'), pick([8, 12, 16, 20, 24, 28, 32]));
                var cg = q('corner_glow'); if (cg) setVal(cg, Math.random() < 0.5);
            }, 'Verras me');
        });

        // ---- Cover effecten ----
        var eff = document.querySelector('select[name="art_effect"]');
        if (eff) {
            var card = eff.closest('.card') || eff.parentElement;
            addButtons(card.querySelector('h2') || eff.parentElement, card, function () {
                randomOption(eff);
                var sp = document.querySelector('select[name="art_spin"]'); if (sp) randomOption(sp);
                var ov = document.querySelector('input[name="art_overflow"]'); if (ov) setVal(ov, Math.random() < 0.35);
                // Nieuwe achtergrond alleen achter de cover (voorbeeld; bewaren is optioneel)
                var pool = (Array.isArray(window.CMB_COVER_BGS) ? window.CMB_COVER_BGS : []).concat((Array.isArray(window.CMB_UPLOAD_MEDIA) ? window.CMB_UPLOAD_MEDIA : []).filter(function (u) { return !/\.(mp4|webm)$/i.test(u); }));
                if (pool.length && window.cmbSetArtBg) window.cmbSetArtBg(pick(pool));
            }, 'Verras me');
        }

        // ---- Achtergronden (website en speler) ----
        var uploads = Array.isArray(window.CMB_UPLOAD_MEDIA) ? window.CMB_UPLOAD_MEDIA : [];
        document.querySelectorAll('.bg-studio-box').forEach(function (box) {
            var key = (box.id || '').replace(/^box_/, '');
            var q = function (n) { return document.getElementById(key + '_' + n); };
            addButtons(box.querySelector('strong') || box, box, function () {
                setVal(q('blur'), pick([0, 0, 2, 4, 6, 8]));
                setVal(q('darken'), pick([20, 30, 40, 50, 60]));
                if (uploads.length) {
                    if (uploads.length > 2 && Math.random() < 0.5) {
                        setVal(q('mode'), 'carousel');
                        var pool = uploads.slice().sort(function () { return Math.random() - 0.5; }).slice(0, Math.min(5, uploads.length));
                        for (var i = 1; i <= 24; i++) setVal(document.querySelector('[name="' + key + '_item_' + i + '"]'), pool[i - 1] || '');
                        setVal(q('min'), 0);
                        setVal(q('sec'), pick([8, 10, 12, 15, 20]));
                    } else {
                        setVal(q('mode'), 'single');
                        setVal(q('single_url'), pick(uploads));
                    }
                }
            }, uploads.length ? 'Verras me' : 'Verras me (blur/donker)');
        });
    });
})();

// ---------- Ingeklapte onderdelen (rustiger beheer) ----------
// Elke kaart (kop) en elk slide-blok is standaard dicht, met een korte samenvatting (de tekst die erin staat).
// Open/dicht blijft onthouden per pagina (ook na opslaan). Even/oneven onderdelen krijgen een licht verschil in kleur.
(function () {
    'use strict';
    var folds = [];
    var storeKey = 'cmb_fold:' + location.pathname.replace(/^.*\//, '') + location.search;

    function txt(el) { return (el && (el.value !== undefined ? el.value : el.textContent) || '').replace(/\s+/g, ' ').trim(); }

    function preview(block) {
        var f = block.querySelector('textarea[name$="_tbig_val"], textarea[name$="_title_val"], input[type="text"][name$="_title"], textarea[name$="_val"]');
        var t = txt(f);
        if (!t) { f = block.querySelector('textarea[name$="_body_val"], textarea[name$="_tsub_val"]'); t = txt(f); }
        return t.length > 70 ? t.slice(0, 70) + '…' : t;
    }

    function blockTitle(block, idx) {
        var chk = block.querySelector('input[type="checkbox"][name$="_active"]');
        var lab = chk && chk.closest('label');
        var t = lab ? txt(lab).replace(/\s*Actief\s*$/i, '') : '';
        return { title: t || ('Onderdeel ' + (idx + 1)), chk: chk };
    }

    function setOpen(f, open) {
        f.open = open;
        f.el.classList.toggle('cmb-fold-open', open);
        f.head.setAttribute('aria-expanded', open ? 'true' : 'false');
        f.body.style.display = open ? '' : 'none';
    }

    function save() {
        try { sessionStorage.setItem(storeKey, JSON.stringify(folds.map(function (f, i) { return f.open ? i : -1; }).filter(function (i) { return i >= 0; }))); } catch (e) {}
    }

    function makeFold(el, head, bodyNodes, kind, idx) {
        var body = document.createElement('div');
        body.className = 'cmb-fold-body';
        bodyNodes.forEach(function (n) { body.appendChild(n); });
        el.appendChild(body);
        el.classList.add('cmb-fold', kind, (idx % 2 === 0) ? 'cmb-fold-odd' : 'cmb-fold-even');
        head.classList.add('cmb-fold-head');
        head.setAttribute('role', 'button');
        head.setAttribute('tabindex', '0');
        var chev = document.createElement('span');
        chev.className = 'cmb-fold-chev';
        chev.textContent = '▸';
        head.insertBefore(chev, head.firstChild);
        var f = { el: el, head: head, body: body, open: false, sum: null };
        var toggle = function (e) {
            if (e && e.target && e.target.closest && e.target.closest('input, button:not(.cmb-fold-head), select, textarea, a, label')) return;
            setOpen(f, !f.open); save();
        };
        head.addEventListener('click', toggle);
        head.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(e); } });
        folds.push(f);
        return f;
    }

    function init() {
        var scope = document.querySelector('form') || document.body;
        var kaarten = Array.prototype.filter.call(scope.querySelectorAll('.card'), function (c) {
            var h = c.firstElementChild;
            return h && h.tagName === 'H2' && !c.closest('.cmb-fold-body');
        });
        var idxCard = 0;
        kaarten.forEach(function (c) {
            var h2 = c.firstElementChild;
            var rest = Array.prototype.slice.call(c.children, 1);
            if (!rest.length) return;
            makeFold(c, h2, rest, 'cmb-fold-card', idxCard++);
        });
        // Slide-blokken (nieuws, weer, vrij) en de noodslide-kaart zonder eigen kop
        var blokken = Array.prototype.slice.call(scope.querySelectorAll('div.slide-block'));
        blokken.forEach(function (b, i) {
            if (b.classList.contains('cmb-fold')) return;
            var bt = blockTitle(b, i);
            var head = document.createElement('div');
            var title = document.createElement('span'); title.className = 'cmb-fold-title'; title.textContent = bt.title;
            var sum = document.createElement('span'); sum.className = 'cmb-fold-sum';
            var state = document.createElement('span'); state.className = 'cmb-fold-state';
            head.appendChild(title); head.appendChild(sum); head.appendChild(state);
            var nodes = Array.prototype.slice.call(b.children);
            var f = makeFold(b, head, nodes, 'cmb-fold-block', i);
            b.insertBefore(head, b.firstChild);
            var refresh = function () {
                sum.textContent = preview(b);
                state.textContent = bt.chk ? (bt.chk.checked ? 'aan' : 'uit') : '';
                state.className = 'cmb-fold-state ' + (bt.chk && bt.chk.checked ? 'on' : 'off');
            };
            refresh();
            b.addEventListener('input', refresh);
            b.addEventListener('change', refresh);
        });
        if (!folds.length) return;
        // Alles open / dicht
        var first = folds[0].el;
        var bar = document.createElement('div');
        bar.className = 'cmb-fold-bar';
        bar.innerHTML = '<button type="button" data-a="open">▾ Alles openen</button><button type="button" data-a="close">▸ Alles sluiten</button>';
        bar.addEventListener('click', function (e) {
            var a = e.target && e.target.getAttribute && e.target.getAttribute('data-a');
            if (!a) return;
            folds.forEach(function (f) { setOpen(f, a === 'open'); });
            save();
        });
        first.parentNode.insertBefore(bar, first);
        // Opgeslagen toestand terugzetten; zonder opslag alles dicht (bij één onderdeel: open)
        var saved = null;
        try { saved = JSON.parse(sessionStorage.getItem(storeKey) || 'null'); } catch (e) {}
        folds.forEach(function (f, i) { setOpen(f, saved ? saved.indexOf(i) >= 0 : folds.length === 1); });
        openForHash();
        var sec = null;
        try { sec = sessionStorage.getItem('cmb_slide_sec'); } catch (e) {}
        if (location.hash === '#clipslides') sec = 'clips';
        var se = sec && sec !== 'all' && document.getElementById('sec_slide_' + sec);
        if (se && window.cmbOpenCardsIn) window.cmbOpenCardsIn(se);
    }

    function openFor(node) {
        var changed = false;
        folds.forEach(function (f) { if (!f.open && f.el.contains(node)) { setOpen(f, true); changed = true; } });
        if (changed) save();
        return changed;
    }
    function openForHash() {
        if (!location.hash) return;
        var t = null;
        try { t = document.getElementById(location.hash.slice(1)); } catch (e) {}
        if (t && openFor(t)) t.scrollIntoView({ block: 'start' });
    }

    // Een ongeldig veld in een dichte kaart zou het opslaan stil blokkeren: open de kaart dan.
    document.addEventListener('invalid', function (e) {
        var t = e.target;
        if (openFor(t)) setTimeout(function () { try { t.scrollIntoView({ block: 'center' }); t.focus(); t.reportValidity(); } catch (x) {} }, 30);
    }, true);
    window.addEventListener('hashchange', openForHash);
    // Sprong naar een onderdeel (menu "Clipslides", filter op slide-soort): open het
    window.cmbOpenFoldFor = function (node) { if (node) openFor(node); };
    // Eén onderdeel gekozen (filterknop Nieuws/Weer/Vrij/Clips): open de kaarten daarin, de blokken blijven dicht
    window.cmbOpenCardsIn = function (root) {
        var ch = false;
        folds.forEach(function (f) { if (!f.open && f.el.classList.contains('cmb-fold-card') && root.contains(f.el)) { setOpen(f, true); ch = true; } });
        if (ch) save();
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();


/* Per frame: zware bewegende lijnen of lichte plaatjes. Bij licht verdwijnen de zware instellingen. */
(function () {
    'use strict';
    window.cmbRingToggle = function (key) {
        var sel = document.getElementById(key + '_ring_mode'); if (!sel) return;
        var light = sel.value !== 'lines';
        var heavy = document.getElementById(key + '_ring_heavy'), pick = document.getElementById(key + '_ring_light');
        if (heavy) heavy.style.display = light ? 'none' : '';
        if (pick) pick.style.display = light ? '' : 'none';
        if (typeof window.cmbMarkDirty === 'function') window.cmbMarkDirty();
    };
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select[id$="_ring_mode"]').forEach(function (s) {
            var key = s.id.replace(/_ring_mode$/, '');
            var light = s.value !== 'lines';
            var heavy = document.getElementById(key + '_ring_heavy'), pick = document.getElementById(key + '_ring_light');
            if (heavy) heavy.style.display = light ? 'none' : '';
            if (pick) pick.style.display = light ? '' : 'none';
        });
    });
})();
