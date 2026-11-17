/* CMB Studio: hulpfuncties voor de beheerpagina. Geladen door cmb-studio.php. */
function toggleStyleDrawer(id) {
    var el = document.getElementById(id);
    if (el) el.classList.toggle('open');
}

function filterSlideSection(sec) {
    try { sessionStorage.setItem('cmb_slide_sec', sec); } catch (e) {}
    var sections = ['sec_slide_emer', 'sec_slide_news', 'sec_slide_weather', 'sec_slide_custom', 'sec_slide_clips'];
    if (sec === 'all') {
        sections.forEach(function(s) { var el = document.getElementById(s); if (el) el.style.display = 'block'; });
    } else {
        sections.forEach(function(s) {
            var el = document.getElementById(s);
            if (el) el.style.display = (s === 'sec_slide_' + sec) ? 'block' : 'none';
        });
        var cur = document.getElementById('sec_slide_' + sec);
        if (cur && window.cmbOpenCardsIn) window.cmbOpenCardsIn(cur);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (location.hash === '#clipslides' && document.getElementById('sec_slide_clips')) {
        filterSlideSection('clips');
        document.getElementById('sec_slide_clips').scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }
    // Na opslaan: terug naar het onderdeel waar je was (Nieuws, Weer, Vrij, Clips ...)
    var sec = null;
    try { sec = sessionStorage.getItem('cmb_slide_sec'); } catch (e) {}
    if (sec && document.getElementById('sec_slide_emer')) filterSlideSection(sec);
});

function toggleHideInactiveSlides(hide) {
    document.querySelectorAll('.slide-block, .emer-card').forEach(function(card) {
        var chk = card.querySelector('input[type="checkbox"]');
        if (chk && !chk.checked) {
            card.style.display = hide ? 'none' : 'block';
        } else {
            card.style.display = 'block';
        }
    });
}

function applyStreamPreset(val) {
    if (val === 'custom') return;
    const sel = document.getElementById('active_stream');
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    if (!opt) return;

    if (opt.dataset.streamurl !== undefined) document.getElementById('streamUrl').value = opt.dataset.streamurl;
    if (opt.dataset.fallbackurl !== undefined) document.getElementById('fallbackUrl').value = opt.dataset.fallbackurl;
    if (opt.dataset.statsjsonurl !== undefined) document.getElementById('statsJsonUrl').value = opt.dataset.statsjsonurl;
    if (opt.dataset.apibackupurl !== undefined) document.getElementById('apiBackupUrl').value = opt.dataset.apibackupurl;
    if (opt.dataset.jssrc !== undefined) document.getElementById('jsSrc').value = opt.dataset.jssrc;
    if (opt.dataset.jsport !== undefined) document.getElementById('jsPort').value = opt.dataset.jsport;
    if (opt.dataset.coverapi !== undefined) document.getElementById('coverApi').value = opt.dataset.coverapi;
}

async function saveCurrentAsPreset() {
    const nameInput = document.getElementById('new_preset_name');
    const name = nameInput ? nameInput.value.trim() : '';
    if (!name) {
        alert('Vul a.u.b. een naam in voor de nieuwe preset!');
        return;
    }
    const payload = {
        name: name,
        streamUrl: document.getElementById('streamUrl').value.trim(),
        fallbackUrl: document.getElementById('fallbackUrl').value.trim(),
        statsJsonUrl: document.getElementById('statsJsonUrl').value.trim(),
        apiBackupUrl: document.getElementById('apiBackupUrl').value.trim(),
        jsSrc: document.getElementById('jsSrc').value.trim(),
        jsPort: document.getElementById('jsPort').value.trim(),
        coverApi: document.getElementById('coverApi').value
    };

    try {
        const res = await fetch('cmb-studio.php?ajax_save_preset=1', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await res.json();
        if (d.status === 'ok') {
            alert('✓ Preset "' + name + '" succesvol opgeslagen!');
            window.location.reload();
        } else {
            alert('Fout bij opslaan van preset.');
        }
    } catch(e) {
        alert('Netwerkfout bij opslaan.');
    }
}
window.cmbRefreshUploadTicks = function () {
    document.querySelectorAll('input[type="file"]').forEach(function (inp) {
        if (inp.getAttribute('data-tick') === '1') return;
        inp.setAttribute('data-tick', '1');
        var tick = document.createElement('span');
        tick.className = 'cmb-upload-tick';
        inp.insertAdjacentElement('afterend', tick);
        var row = inp.closest('.form-row');
        var pathInp = row ? row.querySelector('input[type="text"][id$="_media_url"], input[type="text"][name$="_url"]') : null;
        function refresh() {
            if (inp.files && inp.files.length) {
                tick.textContent = '✓ Gekozen: ' + inp.files[0].name + ' (wordt bij opslaan geüpload)';
                tick.classList.add('on');
            } else if (pathInp && pathInp.value.trim() !== '') {
                tick.textContent = '✓ Media ingesteld';
                tick.classList.add('on');
            } else {
                tick.classList.remove('on');
            }
        }
        inp.addEventListener('change', refresh);
        if (pathInp) pathInp.addEventListener('input', refresh);
        refresh();
    });
};
document.addEventListener('DOMContentLoaded', window.cmbRefreshUploadTicks);

/* Clipslides: waarschuw meteen als een link naar een webpagina wijst in plaats van naar een video */
function cmbClipLinkHint(inp) {
    var v = (inp.value || '').trim(), hint = inp.parentElement.querySelector('.cmb-cliplink-hint');
    if (!hint) return;
    var isPage = /^https?:\/\//i.test(v) && !/\.(mp4|webm|ogv|m4v|mov|mkv|m3u8)(\?.*)?$/i.test(v) && !/(youtu\.be|youtube\.com|youtube-nocookie\.com|vimeo\.com)/i.test(v);
    hint.style.display = isPage ? '' : 'none';
}



/** Weer-slide: vult het weerpraatje met de live 3-daagse verwachting (knop "Haal Live Verwachting Op"). */
async function fetchLiveForecastForSlide(n) {
    const lat = (document.getElementById('sw_' + n + '_lat') || {}).value;
    const lon = (document.getElementById('sw_' + n + '_lon') || {}).value;
    const box = document.getElementById('sw_' + n + '_praatje');
    if (!box) return;
    if (!lat || !lon) { alert('Vul eerst Lat en Lon in bij deze weerslide.'); return; }
    const old = box.value;
    box.value = 'Verwachting ophalen...';
    try {
        const r = await fetch('https://api.open-meteo.com/v1/forecast?latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lon) + '&daily=weathercode,temperature_2m_max,temperature_2m_min&timezone=auto');
        if (!r.ok) throw new Error('weer');
        const d = await r.json();
        const code = (c) => c === 0 ? '☀️ Zonnig' : c <= 2 ? '🌤️ Wisselend bewolkt' : c === 3 ? '☁️ Bewolkt' : c <= 48 ? '🌫️ Mist' : c <= 57 ? '🌦️ Motregen' : c <= 67 ? '🌧️ Regen' : c <= 77 ? '🌨️ Sneeuw' : c <= 82 ? '🌧️ Buien' : '⛈️ Onweer';
        const dag = ['Vandaag', 'Morgen', 'Overmorgen'];
        const parts = [];
        for (let i = 0; i < Math.min(3, d.daily.time.length); i++) {
            parts.push(dag[i] + ': ' + code(d.daily.weathercode[i]) + ' ' + Math.round(d.daily.temperature_2m_max[i]) + '°C (min ' + Math.round(d.daily.temperature_2m_min[i]) + '°C)');
        }
        box.value = parts.join(' • ');
    } catch (e) {
        box.value = old;
        alert('Verwachting ophalen is niet gelukt. Controleer Lat en Lon en probeer opnieuw.');
    }
}
