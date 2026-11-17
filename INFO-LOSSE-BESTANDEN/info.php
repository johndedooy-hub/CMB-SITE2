<?php
/**
 * INFO-OVERZICHT (losse pagina)
 * Zet dit bestand in public_html (naast de map /cmb), dan is het adres jouwsite.nl/info.php
 * Staat de CMB-speler in een andere map dan /cmb? Pas dan hieronder $CMB_MAP aan.
 * Staat dit bestand in de CMB-map zelf, dan werkt het ook zonder aanpassing.
 */
$CMB_MAP = 'cmb';

$CMB_MAP = preg_replace('~[^A-Za-z0-9_./-]~', '', (string)$CMB_MAP);
$base = is_file(__DIR__ . '/cmb-core/cmb-lib.php') ? '' : trim($CMB_MAP, '/') . '/';
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Info</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;800&display=swap" rel="stylesheet">
<style>
 :root{--gold:#ffd166;--cyan:#63f6ff}
 *{box-sizing:border-box;font-family:'Montserrat','Noto Color Emoji',sans-serif}
 body{margin:0;background:radial-gradient(circle at 20% 0%,#16193a,#070812 60%);color:#f7f7ff;min-height:100vh;padding:16px}
 .wrap{max-width:1000px;margin:0 auto}
 .top{display:flex;align-items:center;gap:14px;flex-wrap:wrap;background:rgba(12,14,26,.6);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:12px 14px;backdrop-filter:blur(14px);-webkit-backdrop-filter:blur(14px)}
 #pp{width:46px;height:46px;border-radius:12px;border:1px solid var(--cyan);background:rgba(5,6,14,.7);color:var(--cyan);font-size:18px;cursor:pointer;flex:none}
 .now{flex:1;min-width:160px}
 .l1{color:var(--gold);font-weight:800;font-size:14px;letter-spacing:.04em}
 .l2{color:#fff;font-weight:600;font-size:14px;margin-top:3px}
 .go{color:#070812;background:var(--cyan);font-weight:800;font-size:13px;text-decoration:none;border-radius:10px;padding:11px 14px;white-space:nowrap}
 .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:12px;margin-top:16px}
 .cat{grid-column:1/-1;font-size:11px;font-weight:800;letter-spacing:.18em;color:var(--acc,var(--cyan));opacity:.85;margin:8px 2px -4px}
 .card{cursor:pointer;min-height:96px;border-radius:14px;padding:12px;background:rgba(12,14,26,.65) center/cover;border:1px solid rgba(255,255,255,.14);display:flex;flex-direction:column;justify-content:flex-end;transition:transform .2s,border-color .2s}
 .card:hover{transform:translateY(-3px);border-color:var(--acc,var(--cyan))}
 .ct{color:#fff;font-weight:800;font-size:14px;line-height:1.25;text-shadow:0 1px 4px #000}
 .cs{color:var(--acc,var(--cyan));font-weight:600;font-size:11px;margin-top:3px;text-shadow:0 1px 3px #000}
 .empty{color:#a8aac8;text-align:center;margin-top:30px;font-size:14px}
 .lb{position:fixed;inset:0;z-index:50;background:rgba(5,6,14,.88);backdrop-filter:blur(22px);-webkit-backdrop-filter:blur(22px);display:none;align-items:center;justify-content:center;padding:16px;cursor:pointer}
 .lb.open{display:flex}
 .modal{background:#090a16;border:1px solid rgba(99,246,255,.45);border-radius:20px;max-width:820px;width:100%;min-height:300px;max-height:88vh;position:relative;overflow:hidden;display:flex;flex-direction:column;justify-content:center}
 .mw{position:absolute;inset:-20px;background-size:cover;background-position:center}
 .dk{position:absolute;inset:0}
 .lc{position:relative;padding:30px 26px;overflow-y:auto;max-height:84vh}
 .lc h3{color:var(--gold);font-size:clamp(20px,4vw,26px);margin:0 0 8px;font-weight:800;text-shadow:0 2px 10px #000}
 .lc h4{color:var(--cyan);font-size:clamp(14px,2.5vw,17px);margin:0 0 14px;font-weight:600;text-shadow:0 2px 8px #000}
 .lb-body{color:#fff;font-size:clamp(13px,2.2vw,16px);line-height:1.6;white-space:pre-line;text-shadow:0 2px 8px #000}
 .lb-body *{white-space:normal}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <button id="pp" aria-label="Afspelen of pauzeren">▶</button>
    <div class="now">
      <div class="l1" id="l1">Nu op …</div>
      <div class="l2" id="l2"></div>
    </div>
    <a class="go" id="go" href="<?= htmlspecialchars($base, ENT_QUOTES) ?>index.php?play=1">▶ Naar de speler<br><small style="font-weight:600;font-size:10px">muziek herstart vanzelf</small></a>
  </div>
  <div class="grid" id="grid"></div>
  <div class="empty" id="empty" style="display:none">Er is nu niets om te laten zien.</div>
</div>

<div class="lb" id="lb" onclick="this.classList.remove('open')">
  <div class="modal">
    <div class="mw" id="mw"></div><div class="dk" id="dk"></div>
    <div class="lc"><h3 id="lt"></h3><h4 id="ls"></h4><div class="lb-body" id="lbody"></div></div>
  </div>
</div>

<script>
(function () {
  var BASE = <?= json_encode($base) ?>;
  var station = '', streamUrl = '', fallbackUrl = '', slides = [], playing = false, triedFallback = false;
  var audio = new Audio();
  var $ = function (id) { return document.getElementById(id); };
  function plain(h) { try { return (new DOMParser().parseFromString(String(h || ''), 'text/html').body.textContent || '').trim(); } catch (e) { return ''; } }
  function isImg(u) { return /\.(jpe?g|png|gif|webp|svg|avif)(\?|#|$)/i.test(u || ''); }

  function loadData() {
    fetch(BASE + 'index.php?api_live_data=1&v=' + Date.now(), { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d || d.same) return;
      if (d.ov_accent && /^#[0-9a-f]{6}$/i.test(d.ov_accent)) document.documentElement.style.setProperty('--acc', d.ov_accent); // accentkleur uit het beheer (alleen kopjes, tegel-onderregel en rand)
      station = plain(d.station_title) || 'de radio';
      $('l1').textContent = 'Nu op ' + station;
      document.title = 'Info - ' + station;
      streamUrl = d.stream_url || ''; fallbackUrl = d.fallback_url || '';
      slides = (d.slides || []).filter(function (s) { return s && s.ov && s.type !== 'emergency'; });
      draw();
    }).catch(function () {});
  }
  function draw() {
    var g = $('grid'); g.innerHTML = '';
    $('empty').style.display = slides.length ? 'none' : 'block';
    var co = ['nieuws', 'weer', 'regio', 'overig'], lc = '';
    slides.sort(function (a, b) { return co.indexOf(a.cat || 'overig') - co.indexOf(b.cat || 'overig'); });
    slides.forEach(function (s) {
      var ct = co.indexOf(s.cat) > -1 ? s.cat : 'overig';
      if (ct !== lc) { lc = ct; var h = document.createElement('div'); h.className = 'cat'; h.textContent = ct.toUpperCase(); g.appendChild(h); }
      var c = document.createElement('div'); c.className = 'card';
      if (s.media && isImg(s.media)) c.style.backgroundImage = 'linear-gradient(rgba(5,6,14,.55),rgba(5,6,14,.8)),url("' + String(s.media).replace(/"/g, '%22') + '")';
      var t = document.createElement('div'); t.className = 'ct'; t.textContent = plain(s.title) || plain(s.sub) || '…'; c.appendChild(t);
      if (plain(s.title) && plain(s.sub)) { var u = document.createElement('div'); u.className = 'cs'; u.textContent = plain(s.sub); c.appendChild(u); }
      c.onclick = function () { openLb(s); };
      g.appendChild(c);
    });
  }
  function openLb(s) {
    var m = s.media || '';
    if (m && isImg(m)) { $('mw').style.display = 'block'; $('mw').style.backgroundImage = 'url("' + String(m).replace(/"/g, '%22') + '")'; $('mw').style.filter = 'blur(' + (parseInt(s.blur) || 0) + 'px)'; $('dk').style.background = 'rgba(0,0,0,' + ((s.darken !== undefined ? parseInt(s.darken) : 40) / 100) + ')'; }
    else { $('mw').style.display = 'none'; $('dk').style.background = 'transparent'; }
    $('lt').textContent = plain(s.title); $('ls').textContent = plain(s.sub);
    var b = $('lbody');
    if (s.type === 'widget') { b.innerHTML = s.body || ''; }
    else if (s.type === 'weather') {
      b.textContent = s.body || 'Weergegevens inladen...';
      if (!s.body && s.lat && s.lon) weatherText(s.lat, s.lon, b);
    } else { b.textContent = s.body || ''; }
    $('lb').classList.add('open');
  }
  function weatherText(lat, lon, el) {
    fetch('https://api.open-meteo.com/v1/forecast?latitude=' + encodeURIComponent(lat) + '&longitude=' + encodeURIComponent(lon) + '&daily=weathercode,temperature_2m_max,temperature_2m_min&timezone=auto').then(function (r) { return r.json(); }).then(function (d) {
      var dag = ['Vandaag', 'Morgen', 'Overmorgen'], out = [];
      for (var i = 0; i < Math.min(3, d.daily.time.length); i++) out.push(dag[i] + ': ' + Math.round(d.daily.temperature_2m_max[i]) + '°C (min ' + Math.round(d.daily.temperature_2m_min[i]) + '°C)');
      el.textContent = out.join('\n');
    }).catch(function () { el.textContent = 'Weergegevens zijn nu niet beschikbaar.'; });
  }
  function loadTrack() {
    fetch(BASE + 'get_track.php?s=1&v=' + Date.now(), { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
      var a = d.artist || '', s = d.song || d.songtitle || '';
      $('l2').textContent = [a, s].filter(Boolean).join(' - ');
    }).catch(function () {});
  }
  $('pp').onclick = function () {
    if (!playing) {
      if (!streamUrl) return;
      playing = true; triedFallback = false;
      audio.src = streamUrl; audio.play().catch(function () {});
      $('pp').textContent = '❚❚';
    } else {
      playing = false; audio.pause(); audio.removeAttribute('src'); audio.load();
      $('pp').textContent = '▶';
    }
  };
  audio.addEventListener('error', function () {
    if (!playing) return;
    if (!triedFallback && fallbackUrl) { triedFallback = true; audio.src = fallbackUrl; audio.play().catch(function () {}); }
    else { playing = false; $('pp').textContent = '▶'; }
  });
  loadData(); loadTrack();
  setInterval(loadData, 60000); setInterval(loadTrack, 10000);
})();
</script>
</body>
</html>
