<?php /** CMB Studio > Slides (nood, nieuws, weer, vrije slides). Geladen door cmb-studio.php. */ if (!defined('CMB_PART')) { http_response_code(403); exit; } ?>
<!-- TAB 1: SLIDE STUDIO (UPGRADED MET PREVIEWS, KRUISJES & UITKLAPTIJDEN) -->
<div id="tab-slides-studio" class="tab-pane active">
    <!-- FILMROL: wat de speler NU laat zien (live opgehaald, zoals de speler hem ook krijgt) -->
    <div class="cmb-filmroll-card">
        <div class="cmb-fr-head">
            <strong>🎞️ Filmrol: dit draait nu in de speler</strong>
            <span id="cmbFrSum" class="cmb-fr-sum">laden…</span>
            <button type="button" class="btn-mini-save" onclick="cmbLoadFilmroll()" style="margin-left:auto;">↻ Ververs</button>
            <a class="btn-mini-save" href="index.php?debug=1" target="_blank" style="text-decoration:none;" title="Speler met een venstertje: welke slide staat er en waarom">🔎 Speler met uitleg</a>
        </div>
        <div id="cmbFilmroll" class="cmb-fr-strip"></div>
        <div id="cmbFrExtra" class="cmb-fr-extra"></div>
    </div>
    <style>
        .cmb-filmroll-card { background:#0b0d1c; border:1px solid rgba(255,209,102,.35); border-radius:12px; padding:10px 12px; margin-bottom:14px; }
        .cmb-fr-head { display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:8px; font-size:13px; }
        .cmb-fr-sum { font-size:12px; color:#a8aac8; }
        .cmb-fr-strip { display:flex; gap:8px; overflow-x:auto; padding:6px 2px 8px; scroll-snap-type:x mandatory;
            background:repeating-linear-gradient(90deg, #000 0 10px, transparent 10px 18px) top/100% 5px no-repeat, repeating-linear-gradient(90deg, #000 0 10px, transparent 10px 18px) bottom/100% 5px no-repeat; }
        .cmb-fr-item { flex:0 0 150px; scroll-snap-align:start; background:#121427; border:1px solid rgba(255,255,255,.1); border-radius:8px; overflow:hidden; cursor:pointer; font-size:11px; position:relative; }
        .cmb-fr-item:hover { border-color:#63f6ff; }
        .cmb-fr-thumb { height:72px; background:#05060d center/cover no-repeat; position:relative; display:flex; align-items:center; justify-content:center; color:#a8aac8; font-size:22px; }
        .cmb-fr-thumb video { width:100%; height:100%; object-fit:cover; }
        .cmb-fr-num { position:absolute; top:4px; left:4px; background:rgba(0,0,0,.7); color:#fff; font-weight:900; border-radius:4px; padding:0 5px; font-size:10px; }
        .cmb-fr-type { position:absolute; top:4px; right:4px; border-radius:4px; padding:0 5px; font-size:9px; font-weight:900; color:#070812; }
        .cmb-fr-body { padding:5px 7px 7px; }
        .cmb-fr-title { font-weight:800; color:#fff; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .cmb-fr-meta { color:#a8aac8; margin-top:2px; }
        .cmb-fr-warn { color:#ffd166; font-weight:800; }
        .cmb-fr-extra { font-size:12px; color:#a8aac8; line-height:1.6; }
        .cmb-fr-extra b { color:#ffd166; }
    </style>
    <script>
    function cmbLoadFilmroll() {
        var strip = document.getElementById('cmbFilmroll'), sum = document.getElementById('cmbFrSum'), ex = document.getElementById('cmbFrExtra');
        var T = { emergency: ['NOOD', '#ff1744', 'emer'], news: ['NIEUWS', '#00e5ff', 'news'], weather: ['WEER', '#ffd166', 'weather'], custom: ['VRIJ', '#b388ff', 'custom'], widget: ['WIDGET', '#69f0ae', ''] };
        var esc = function (t) { var d = document.createElement('div'); d.textContent = t == null ? '' : String(t); return d.innerHTML; };
        var plain = function (h) { var d = document.createElement('div'); d.innerHTML = h || ''; return (d.textContent || '').trim(); };
        var thumb = function (u) {
            u = String(u || '');
            if (!u) return '';
            var y = u.match(/(?:youtu\.be\/|[?&]v=|embed\/|shorts\/)([A-Za-z0-9_-]{11})/);
            if (y) return 'style="background-image:url(https://img.youtube.com/vi/' + y[1] + '/mqdefault.jpg)"';
            if (/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(\?|#|$)/i.test(u)) return 'data-video="' + esc(u) + '"';
            return 'style="background-image:url(&quot;' + esc(u) + '&quot;)"';
        };
        fetch('index.php?api_live_data=1&v=' + Date.now(), { cache: 'no-store' }).then(function (r) { return r.json(); }).then(function (d) {
            var sl = Array.isArray(d.slides) ? d.slides.slice() : [];
            var total = 0, html = '';
            sl.forEach(function (s, i) {
                var t = T[s.type] || [String(s.type || '?').toUpperCase(), '#a8aac8', ''];
                var sec = parseInt(s.sec, 10) || 8, flags = [];
                if (s.track_only) flags.push('🎵 alleen bij: ' + esc(s.track_match || '?'));
                else if (s.track_match) flags.push('🎵 + bij: ' + esc(s.track_match));
                if (!s.track_only) total += sec;
                if (sec >= 60) flags.push('<span class="cmb-fr-warn">⚠ ' + sec + ' s: blijft lang staan</span>');
                if (s.track_match && String(s.track_match).trim().length < 3) flags.push('<span class="cmb-fr-warn">⚠ koppeltekst te kort, wordt genegeerd</span>');
                var th = thumb(s.media);
                html += '<div class="cmb-fr-item" onclick="' + (t[2] ? "filterSlideSection('" + t[2] + "')" : "location.href='cmb-studio.php?tab=widgets'") + '">' +
                    '<div class="cmb-fr-thumb" ' + th + '>' + (th ? '' : '🖼') + '<span class="cmb-fr-num">' + (i + 1) + '</span><span class="cmb-fr-type" style="background:' + t[1] + '">' + t[0] + '</span></div>' +
                    '<div class="cmb-fr-body"><div class="cmb-fr-title">' + esc(plain(s.title) || plain(s.sub) || '(zonder titel)') + '</div>' +
                    '<div class="cmb-fr-meta">⏱ ' + sec + ' s' + (flags.length ? '<br>' + flags.join('<br>') : '') + '</div></div></div>';
            });
            strip.innerHTML = html || '<div class="cmb-fr-meta">Er staan nu geen slides in de rotatie.</div>';
            strip.querySelectorAll('[data-video]').forEach(function (el) { el.innerHTML += '<video muted preload="metadata" src="' + el.getAttribute('data-video') + '#t=0.5"></video>'; });
            var mm = Math.floor(total / 60), ss = total % 60;
            sum.textContent = sl.length + ' slides · één ronde duurt ± ' + mm + ':' + (ss < 10 ? '0' : '') + ss;
            var extra = [];
            (d.timed_slides || []).forEach(function (ts) {
                var tt = (ts.times || []).map(function (x) { var m = Math.floor(x / 60), s2 = x % 60; return ':' + (m < 10 ? '0' : '') + m + ':' + (s2 < 10 ? '0' : '') + s2; }).join(' ');
                extra.push('⏱ <b>Op tijd</b>: ' + esc(plain(ts.slide && ts.slide.title) || ts.id) + ' om ' + tt + ' (' + ts.sec + ' s' + (ts.hours && ts.hours.length && ts.hours.length < 24 ? ', uren ' + ts.hours.join(',') : '') + ')');
            });
            (d.clipslides || []).forEach(function (c) {
                extra.push('🎬 <b>Clip</b> bij "' + esc(c.match) + '"' + (c.kind === 'page' ? ' <span class="cmb-fr-warn">⚠ webpagina: blijft tot het volgende nummer staan</span>' : ''));
            });
            ex.innerHTML = extra.join('<br>');
        }).catch(function () { sum.textContent = 'Kon de speler niet bereiken.'; });
    }
    document.addEventListener('DOMContentLoaded', cmbLoadFilmroll);
    setInterval(function () { if (!document.hidden) cmbLoadFilmroll(); }, 30000);
    </script>

    <!-- SUB-NAVIGATIE SLIDE STUDIO -->
    <div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; background:#121427; padding:10px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.08);">
        <button type="button" class="btn-mini-save" style="background:#222544; color:#63f6ff;" onclick="filterSlideSection('all')">👁️ Toon Alles</button>
        <button type="button" class="btn-mini-save" style="background:#ff1744; color:#fff;" onclick="filterSlideSection('emer')">🚨 Noodbericht</button>
        <button type="button" class="btn-mini-save" style="background:#00e5ff; color:#070812; font-weight:bold;" onclick="filterSlideSection('news')">📰 Nieuws Studio (5)</button>
        <button type="button" class="btn-mini-save" style="background:#ffd166; color:#070812; font-weight:bold;" onclick="filterSlideSection('weather')">⛅ Weer & Verwachting (5)</button>
        <button type="button" class="btn-mini-save" style="background:#b388ff; color:#070812; font-weight:bold;" onclick="filterSlideSection('custom')">🎨 Vrije Slides (15)</button>
        <button type="button" class="btn-mini-save" style="background:#ff7ad9; color:#070812; font-weight:bold;" onclick="filterSlideSection('clips')">🎥 Clipslides (15)</button>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; background:#121427; padding:12px 18px; border-radius:10px; margin-bottom:18px; border:1px solid rgba(255,255,255,0.08); flex-wrap:wrap; gap:10px;">
        <span style="font-size:13px; font-weight:bold; color:#63f6ff;">👁️ Weergavefilter:</span>
        <label style="font-size:12px; cursor:pointer; color:#ffd166; font-weight:bold; display:flex; align-items:center; gap:6px;">
            <input type="checkbox" id="chk_hide_inactive" onchange="toggleHideInactiveSlides(this.checked)"> Verberg ongebruikte / inactieve slides
        </label>
    </div>


    <div id="sec_slide_emer"><!-- 0. NOODSLIDE -->
    <?php $em = $slides['emergency'] ?? ['active'=>false, 'title_big'=>'', 'title_sub'=>'', 'body'=>'', 'media_url'=>'', 'blur'=>0, 'darken'=>40]; 
          $emMedia = htmlspecialchars($em['media_url'] ?? '');
    ?>
    <div class="card emer-card" id="card_slide_emer">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <h2 style="color:#ff1744; margin:0; border:none; padding:0;">🚨 Noodslide / Breek-in Slide</h2>
                <button type="button" class="btn-mini-save" onclick="saveSingleSlide('emer', 0)" style="background:#ff1744; color:#fff;">💾 Noodslide Opslaan</button>
            </div>
            <label style="background:#ff1744; color:#fff; padding:6px 14px; border-radius:6px; font-weight:900; cursor:pointer;">
                <input type="checkbox" name="emer_active" id="emer_active" value="1" <?= !empty($em['active']) ? 'checked' : '' ?>> NOODSLIDE ACTIEF
            </label>
        </div>
        <?= renderStyleSuite('emer_tbig', 'Grote Kop (Noodbericht)', $em['title_big'], $em['style_title_big'] ?? ['size'=>'24px','color'=>'#ff1744']) ?>
        <?= renderStyleSuite('emer_tsub', 'Kleine Kop / Tijdstip', $em['title_sub'], $em['style_title_sub'] ?? ['size'=>'14px','color'=>'#ffd166']) ?>
        <?= renderStyleSuite('emer_body', 'Berichttekst (Volledige mededeling)', $em['body'], $em['style_body'] ?? ['size'=>'13px','color'=>'#ffffff'], true) ?>
        
        <div class="form-row" style="margin-top:10px; align-items:center;">
            <div class="media-thumb-preview" id="thumb_slide_emer">
                <?php if ($emMedia): 
                    $isV = preg_match('/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:[?#].*)?$/i', $emMedia);
                    echo $isV ? "<video src='$emMedia' autoplay loop muted playsinline></video>" : "<img src='$emMedia'>";
                ?>
                    <div class="del-btn" onclick="clearSlideMedia('emer', 0)">✕</div>
                <?php else: ?>
                    <span class="empty-txt">Geen media</span>
                <?php endif; ?>
            </div>
            <div class="field" style="flex:2;"><label>Bestand Kiezen</label><input type="file" name="upload_slide_emergency"></div>
            <div class="field" style="flex:2;"><label>Of Media Pad</label><input type="text" name="emer_media_url" id="emer_media_url" value="<?= $emMedia ?>" placeholder="cmb-uploads/..."></div>
            <div class="field" style="max-width:70px;"><label>Blur (px)</label><input type="number" name="emer_blur" id="emer_blur" value="<?= $em['blur'] ?>" min="0" max="25"></div>
            <div class="field" style="max-width:70px;"><label>Donker (%)</label><input type="number" name="emer_darken" id="emer_darken" value="<?= $em['darken'] ?>" min="0" max="95"></div>
        </div>
    </div>

    </div><!-- 1. 5x RSS NIEUWS SLIDES -->
<div id="sec_slide_news">
    <div class="card">
        <h2>📰 5x RSS Nieuws Slides (Nu.nl & BN DeStem)</h2>
        <?php 
        $defRss = [
            1 => ['feed'=>'https://www.nu.nl/rss/Algemeen', 'title'=>'NU.NL ALGEMEEN NIEUWS'],
            2 => ['feed'=>'https://www.bndestem.nl/regio/rss.xml', 'title'=>'BN DESTEM REGIO NIEUWS'],
            3 => ['feed'=>'', 'title'=>'EXTRA NIEUWS FEED #3'],
            4 => ['feed'=>'', 'title'=>'EXTRA NIEUWS FEED #4'],
            5 => ['feed'=>'', 'title'=>'EXTRA NIEUWS FEED #5']
        ];
        for ($ni = 1; $ni <= 5; $ni++): 
            $sn = $slides['news'][$ni] ?? ['active'=>($ni<=2), 'order'=>$ni, 'sec'=>10, 'feed_url'=>$defRss[$ni]['feed'], 'koptitel'=>$defRss[$ni]['title'], 'media_url'=>'', 'blur'=>0, 'darken'=>30];
            $snMedia = htmlspecialchars($sn['media_url'] ?? '');
        ?>
        <div class="slide-block" id="card_slide_n_<?= $ni ?>">
            <div class="form-row" style="align-items:center; justify-content:space-between;">
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="background:rgba(255,255,255,0.08); padding:6px 12px; border-radius:6px; cursor:pointer;">
                        <input type="checkbox" name="sn_<?= $ni ?>_active" id="sn_<?= $ni ?>_active" value="1" <?= !empty($sn['active'])?'checked':'' ?>> Nieuws #<?= $ni ?> Actief
                    </label>
                    <button type="button" class="btn-mini-save" onclick="saveSingleSlide('news', <?= $ni ?>)">💾 Dit Nieuws Opslaan</button>
                </div>
                <div style="display:flex; gap:8px;">
                    <div class="field" style="max-width:90px;"><label>Positie</label><input type="number" name="sn_<?= $ni ?>_order" id="sn_<?= $ni ?>_order" value="<?= $sn['order'] ?? $ni ?>" min="1" max="99"></div>
                    <div class="field" style="max-width:90px;"><label>Seconden</label><input type="number" name="sn_<?= $ni ?>_sec" id="sn_<?= $ni ?>_sec" value="<?= $sn['sec'] ?>" min="2" max="360"></div>
                </div>
            </div>
            
            <div class="form-row" style="margin-top:6px;">
                <div class="field" style="flex:2;"><label>RSS XML Feed URL</label><input type="text" name="sn_<?= $ni ?>_feed" id="sn_<?= $ni ?>_feed" value="<?= htmlspecialchars($sn['feed_url']) ?>" placeholder="https://..."></div>
            </div>

            <?= renderStyleSuite("sn_{$ni}_title", "Koptitel Nieuws #{$ni}", $sn['koptitel'], $sn['style_koptitel'] ?? ['size'=>'18px','color'=>'#ffd166']) ?>
            <?= renderStyleSuite("sn_{$ni}_body", "Opmaak Inhoud / Nieuwstekst #{$ni} (Font, Grootte & Kleur)", "[Tekst wordt automatisch geladen vanuit de RSS Feed]", $sn['style_body'] ?? ['size'=>'13px','color'=>'#f7f7ff'], false) ?>

            <div class="form-row" style="align-items:center;">
                <div class="media-thumb-preview" id="thumb_slide_n_<?= $ni ?>">
                    <?php if ($snMedia): 
                        $isV = preg_match('/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:[?#].*)?$/i', $snMedia);
                        echo $isV ? "<video src='$snMedia' autoplay loop muted playsinline></video>" : "<img src='$snMedia'>";
                    ?>
                        <div class="del-btn" onclick="clearSlideMedia('news', <?= $ni ?>)">✕</div>
                    <?php else: ?>
                        <span class="empty-txt">Feed foto</span>
                    <?php endif; ?>
                </div>
                <div class="field"><label>Eigen Achtergrond Media</label><input type="file" name="upload_slide_n_<?= $ni ?>"></div>
                <div class="field"><label>Of Pad</label><input type="text" name="sn_<?= $ni ?>_media_url" id="sn_<?= $ni ?>_media_url" value="<?= $snMedia ?>" placeholder="cmb-uploads/..."></div>
                <div class="field" style="max-width:70px;"><label>Blur (px)</label><input type="number" name="sn_<?= $ni ?>_blur" id="sn_<?= $ni ?>_blur" value="<?= $sn['blur'] ?>" min="0" max="25"></div>
                <div class="field" style="max-width:70px;"><label>Donker (%)</label><input type="number" name="sn_<?= $ni ?>_darken" id="sn_<?= $ni ?>_darken" value="<?= $sn['darken'] ?>" min="0" max="95"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    </div><!-- 2. 5x WEERSLIDES -->
<div id="sec_slide_weather">
    <div class="card">
        <h2>⛅ 5x Weerslides (Open-Meteo Verwachting)</h2>
        <?php for ($wi = 1; $wi <= 5; $wi++): 
            $defW = [
            1 => ['city'=>'Steenbergen', 'lat'=>'51.58', 'lon'=>'4.32', 'title'=>'VERWACHTING STEENBERGEN', 'active'=>true],
            2 => ['city'=>'Putte', 'lat'=>'51.36', 'lon'=>'4.40', 'title'=>'VERWACHTING PUTTE', 'active'=>true],
            3 => ['city'=>'Bergen op Zoom', 'lat'=>'51.50', 'lon'=>'4.29', 'title'=>'VERWACHTING BERGEN OP ZOOM', 'active'=>true],
            4 => ['city'=>'Rotterdam', 'lat'=>'51.92', 'lon'=>'4.47', 'title'=>'VERWACHTING ROTTERDAM', 'active'=>false],
            5 => ['city'=>'Antwerpen', 'lat'=>'51.22', 'lon'=>'4.40', 'title'=>'VERWACHTING ANTWERPEN', 'active'=>false]
        ];
        $sw = $slides['weather'][$wi] ?? ['active'=>!empty($defW[$wi]['active']), 'order'=>($wi+5), 'sec'=>8, 'city'=>$defW[$wi]['city'], 'lat'=>$defW[$wi]['lat'], 'lon'=>$defW[$wi]['lon'], 'koptitel'=>$defW[$wi]['title'], 'media_url'=>'', 'blur'=>0, 'darken'=>20];
            $swMedia = htmlspecialchars($sw['media_url'] ?? '');
        ?>
        <div class="slide-block" id="card_slide_w_<?= $wi ?>">
            <div class="form-row" style="align-items:center; justify-content:space-between;">
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="background:rgba(255,255,255,0.08); padding:6px 12px; border-radius:6px; cursor:pointer;">
                        <input type="checkbox" name="sw_<?= $wi ?>_active" id="sw_<?= $wi ?>_active" value="1" <?= !empty($sw['active'])?'checked':'' ?>> Weer #<?= $wi ?> Actief
                    </label>
                    <button type="button" class="btn-mini-save" onclick="saveSingleSlide('weather', <?= $wi ?>)">💾 Dit Weer Opslaan</button>
                </div>
                <div style="display:flex; gap:8px;">
                    <div class="field" style="max-width:90px;"><label>Positie</label><input type="number" name="sw_<?= $wi ?>_order" id="sw_<?= $wi ?>_order" value="<?= $sw['order'] ?? ($wi+5) ?>" min="1" max="99"></div>
                    <div class="field" style="max-width:90px;"><label>Seconden</label><input type="number" name="sw_<?= $wi ?>_sec" id="sw_<?= $wi ?>_sec" value="<?= $sw['sec'] ?>" min="2" max="360"></div>
                </div>
            </div>

            <!-- LOCATIE ZOEKEN & GIMMICK NAAM -->
            <div class="form-row" style="margin-top:8px; align-items:flex-end;">
                <div class="field" style="flex:2;">
                    <label>🔍 Zoek Plaatsnaam / Regio (Automatische Coördinaten)</label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" id="geo_search_<?= $wi ?>" placeholder="Typ bv. Steenbergen, Breda, Dinteloord, Ibiza...">
                        <button type="button" class="btn-mini-save" style="background:#63f6ff; color:#070812; font-weight:bold; white-space:nowrap;" onclick="searchGeoCity(<?= $wi ?>)">🔍 Zoek Coördinaten</button>
                    </div>
                </div>
                <div class="field" style="flex:2;"><label>Plaatsnaam (Gimmick / Vrij aanpasbaar)</label><input type="text" name="sw_<?= $wi ?>_city" id="sw_<?= $wi ?>_city" value="<?= htmlspecialchars($sw['city']) ?>" placeholder="bv. Zonnig Steenbergen"></div>
                <div class="field" style="max-width:90px;"><label>Lat</label><input type="text" name="sw_<?= $wi ?>_lat" id="sw_<?= $wi ?>_lat" value="<?= htmlspecialchars($sw['lat']) ?>"></div>
                <div class="field" style="max-width:90px;"><label>Lon</label><input type="text" name="sw_<?= $wi ?>_lon" id="sw_<?= $wi ?>_lon" value="<?= htmlspecialchars($sw['lon']) ?>"></div>
            </div>

            <!-- KORT WEERPRAATJE & TICKER KOPPELING -->
            <div style="background:rgba(5,6,14,0.6); padding:10px; border-radius:8px; margin:10px 0; border:1px solid rgba(255,255,255,0.06);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <label style="font-size:11px; color:#ffd166; font-weight:bold; text-transform:uppercase; margin:0;">Kort Weerpraatje / 3-Daagse Verwachting</label>
                    <button type="button" class="btn-mini-save" style="background:#ffd166; color:#070812; font-weight:bold; padding:3px 8px; font-size:11px;" onclick="fetchLiveForecastForSlide(<?= $wi ?>)">🔄 Haal Live Verwachting Op</button>
                </div>
                <textarea name="sw_<?= $wi ?>_praatje" id="sw_<?= $wi ?>_praatje" rows="2" placeholder="bv. Vandaag volop zon en 22°C. Vanavond zacht met een briesje..." style="width:100%; resize:vertical; background:#05060d; color:#fff; border:1px solid rgba(255,255,255,0.15); border-radius:6px; padding:8px; font-size:13px;"><?= htmlspecialchars($sw['praatje'] ?? '') ?></textarea>
                <div style="margin-top:6px;">
                    <label style="font-size:12px; color:#63f6ff; font-weight:bold; cursor:pointer;">
                        <input type="checkbox" name="sw_<?= $wi ?>_ticker_active" id="sw_<?= $wi ?>_ticker_active" value="1" <?= !empty($sw['ticker_active'])?'checked':'' ?>> 📢 Toon dit weerpraatje ook in de lopende ticker onderin (zonder conflict met overige ticker items)
                    </label>
                </div>
            </div>

            <?= renderStyleSuite("sw_{$wi}_title", "Koptitel Weer #{$wi}", $sw['koptitel'], $sw['style_koptitel'] ?? ['size'=>'18px','color'=>'#63f6ff']) ?>
            <?= renderStyleSuite("sw_{$wi}_body", "Opmaak Inhoud / Weertekst #{$wi} (Font, Grootte & Kleur)", "[Tekst wordt automatisch geladen uit weerpraatje/verwachting]", $sw['style_body'] ?? ['size'=>'13px','color'=>'#a8aac8'], false) ?>

            <div class="form-row" style="align-items:center;">
                <div class="media-thumb-preview" id="thumb_slide_w_<?= $wi ?>">
                    <?php if ($swMedia): 
                        $isV = preg_match('/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:[?#].*)?$/i', $swMedia);
                        echo $isV ? "<video src='$swMedia' autoplay loop muted playsinline></video>" : "<img src='$swMedia'>";
                    ?>
                        <div class="del-btn" onclick="clearSlideMedia('weather', <?= $wi ?>)">✕</div>
                    <?php else: ?>
                        <span class="empty-txt">Geen media</span>
                    <?php endif; ?>
                </div>
                <div class="field"><label>Achtergrond Media</label><input type="file" name="upload_slide_w_<?= $wi ?>"></div>
                <div class="field"><label>Of Pad</label><input type="text" name="sw_<?= $wi ?>_media_url" id="sw_<?= $wi ?>_media_url" value="<?= $swMedia ?>" placeholder="cmb-uploads/..."></div>
                <div class="field" style="max-width:70px;"><label>Blur (px)</label><input type="number" name="sw_<?= $wi ?>_blur" id="sw_<?= $wi ?>_blur" value="<?= $sw['blur'] ?>" min="0" max="25"></div>
                <div class="field" style="max-width:70px;"><label>Donker (%)</label><input type="number" name="sw_<?= $wi ?>_darken" id="sw_<?= $wi ?>_darken" value="<?= $sw['darken'] ?>" min="0" max="95"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    </div><!-- 3. 15x EIGEN VRIJE SLIDES -->
<div id="sec_slide_custom">
    <div class="card">
        <h2>🎨 15x Eigen Vrije Slides (Foto's, Posters, Tekst & Planning)</h2>
        <?php
        $tmDelay = floatval($slides['timing']['delay'] ?? 0);
        $tmList = [];
        foreach (($slides['custom'] ?? []) as $tmI => $tmS) {
            if (empty($tmS['active']) || empty($tmS['precise']) || empty($tmS['sched_mins'])) continue;
            $tmT = [];
            foreach (array_values($tmS['sched_mins']) as $tmK => $tmM) $tmT[] = sprintf(':%02d:%02d', intval($tmM), intval($tmS['sched_secs'][$tmK] ?? 0));
            $tmH = $tmS['sched_hours'] ?? [];
            $tmList[] = ['n' => $tmI, 'title' => strip_tags((string)($tmS['title_big'] ?? '')) ?: ('Vrije slide ' . $tmI), 'times' => implode(' ', $tmT),
                'hours' => (!$tmH || count($tmH) === 24) ? 'elk uur' : implode(', ', array_map(function ($x) { return sprintf('%02d', $x); }, $tmH)) . ' uur',
                'sec' => max(2, intval($tmS['sec'] ?? 8))];
        }
        ?>
        <div class="cmb-helpbox" style="margin-bottom:14px; border:1px solid rgba(255,209,102,.35);">
            <strong>⏱️ Op tijd geplande slides</strong> (vinkje "Precies op tijd starten" bij een vrije slide)
            <div class="form-row" style="align-items:flex-end; margin-top:8px;">
                <div class="field" style="max-width:220px;"><label>Stream-vertraging (seconden)</label>
                    <input type="text" name="slides_stream_delay" id="slides_stream_delay" value="<?= htmlspecialchars((string)$tmDelay) ?>" placeholder="bv. 12 of 12.5" style="text-align:center;"></div>
                <div class="field" style="flex:2; font-size:12px; color:#a8aac8; line-height:1.5;">
                    Servertijd nu: <strong id="cmbSrvClock" style="color:#fff;" data-ms="<?= (int)round(microtime(true) * 1000) ?>" data-tz="<?= intval(date('Z')) ?>">--:--:--</strong><br>
                    Luisteraars horen de stream zoveel seconden later dan hij wordt uitgezonden. Een slide op <strong>:59:00</strong> verschijnt daarom om <strong id="cmbSrvExample">:59:<?= sprintf('%04.1f', $tmDelay) ?></strong> servertijd, precies als de luisteraar :59:00 hoort.
                    <br>Tip: vertraging meten = tik op de klok van een jingle of het nieuws en kijk hoeveel seconden later de speler hem laat horen.
                </div>
            </div>
            <?php if ($tmList): ?>
            <table style="width:100%; font-size:12px; margin-top:8px; border-collapse:collapse;">
                <tr style="color:#a8aac8; text-align:left;"><th style="padding:3px 6px;">Slide</th><th style="padding:3px 6px;">Start</th><th style="padding:3px 6px;">Uren</th><th style="padding:3px 6px;">Duur</th></tr>
                <?php foreach ($tmList as $tm): ?>
                <tr><td style="padding:3px 6px;"><?= htmlspecialchars($tm['title']) ?> <small style="color:#a8aac8;">(#<?= $tm['n'] ?>)</small></td><td style="padding:3px 6px; color:#ffd166;"><?= $tm['times'] ?></td><td style="padding:3px 6px;"><?= htmlspecialchars($tm['hours']) ?></td><td style="padding:3px 6px;"><?= $tm['sec'] ?> s</td></tr>
                <?php endforeach; ?>
            </table>
            <?php else: ?><div style="font-size:12px; color:#a8aac8; margin-top:6px;">Nog geen slides op tijd gepland.</div><?php endif; ?>
        </div>
        <script>
        (function () {
            var c = document.getElementById('cmbSrvClock'); if (!c) return;
            var off = parseInt(c.dataset.ms, 10) - Date.now(), tz = parseInt(c.dataset.tz, 10) * 1000;
            var p = function (n) { return (n < 10 ? '0' : '') + n; };
            setInterval(function () {
                var t = new Date(Date.now() + off + tz);
                c.textContent = p(t.getUTCHours()) + ':' + p(t.getUTCMinutes()) + ':' + p(t.getUTCSeconds());
            }, 250);
            var d = document.getElementById('slides_stream_delay'), ex = document.getElementById('cmbSrvExample');
            if (d && ex) d.addEventListener('input', function () {
                var v = Math.max(0, Math.min(120, parseFloat(String(d.value).replace(',', '.')) || 0));
                var m = 59 + Math.floor(v / 60), s = v % 60;
                ex.textContent = ':' + p(m % 60) + ':' + (s < 10 ? '0' : '') + s.toFixed(1);
            });
        })();
        </script>
        <?php for ($fi = 1; $fi <= 15; $fi++): 
            $sf = $slides['custom'][$fi] ?? [
                'active'=>false, 'order'=>($fi+10), 'sec'=>8, 'title_big'=>'', 'title_sub'=>'', 'body'=>'',
                'media_url'=>'', 'blur'=>0, 'darken'=>30, 'sched_days'=>['ma','di','wo','do','vr','za','zo'],
                'sched_hours'=>range(0,23), 'sched_mins'=>[] // leeg: geen minuten voorgevuld, alleen het voorbeeld in grijs
            ];
            $sfMedia = htmlspecialchars($sf['media_url'] ?? '');
        ?>
        <div class="slide-block" id="card_slide_f_<?= $fi ?>">
            <div class="form-row" style="align-items:center; justify-content:space-between;">
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <label style="background:rgba(99,246,255,0.15); border:1px solid #63f6ff; color:#63f6ff; padding:6px 14px; border-radius:6px; font-weight:bold; cursor:pointer;">
                        <input type="checkbox" name="sf_<?= $fi ?>_active" id="sf_<?= $fi ?>_active" value="1" <?= !empty($sf['active'])?'checked':'' ?>> Vrije Slide #<?= $fi ?> Actief
                    </label>
                    <button type="button" class="btn-mini-save" onclick="saveSingleSlide('custom', <?= $fi ?>)">💾 Deze Slide Opslaan</button>
                    <button type="button" class="btn-mini-save" style="background:#ff1744; color:#fff;" onclick="resetSingleSlide(<?= $fi ?>)">🗑️ Slide Vrijmaken</button>
                </div>
                <div style="display:flex; gap:8px;">
                    <div class="field" style="max-width:90px;"><label>Positie</label><input type="number" name="sf_<?= $fi ?>_order" id="sf_<?= $fi ?>_order" value="<?= $sf['order'] ?? ($fi+10) ?>" min="1" max="99"></div>
                    <div class="field" style="max-width:90px;"><label>Seconden</label><input type="number" name="sf_<?= $fi ?>_sec" id="sf_<?= $fi ?>_sec" value="<?= $sf['sec'] ?>" min="2" max="360"></div>
                </div>
            </div>

            <?= renderStyleSuite("sf_{$fi}_tbig", "Grote Kop (Optioneel)", $sf['title_big'], $sf['style_title_big'] ?? ['size'=>'22px','color'=>'#63f6ff']) ?>
            <?= renderStyleSuite("sf_{$fi}_tsub", "Kleine Kop (Optioneel)", $sf['title_sub'], $sf['style_title_sub'] ?? ['size'=>'14px','color'=>'#ffd166']) ?>
            <div class="form-row" style="margin-top:8px;"><div class="field" style="flex:1;"><label>Onderregel op de tegel (optioneel, alleen in het Overzicht)</label><input type="text" name="sf_<?= $fi ?>_foot" id="sf_<?= $fi ?>_foot" value="<?= htmlspecialchars($sf['foot'] ?? '') ?>" maxlength="80" placeholder="Bijvoorbeeld: elke vrijdag 20:00"></div></div>
            <?= renderStyleSuite("sf_{$fi}_body", "Berichttekst (Optioneel)", $sf['body'], $sf['style_body'] ?? ['size'=>'13px','color'=>'#ffffff'], true) ?>
            <div class="form-row" style="margin-top:8px;"><div class="field" style="flex:1;"><label>Lichtgewicht achtergrondcover (optioneel)</label><select name="sf_<?= $fi ?>_background_preset" id="sf_<?= $fi ?>_background_preset"><option value="">Geen preset / gebruik eigen media</option><?php for ($bgI=1; $bgI<=50; $bgI++): $bgV=sprintf('cover-%02d',$bgI); ?><option value="<?= $bgV ?>" <?= (($sf['background_preset'] ?? '') === $bgV) ? 'selected' : '' ?>>Achtergrond <?= $bgI ?></option><?php endfor; ?></select><small style="color:#a8aac8;">50 lichte SVG-achtergronden. Een eigen geüploade afbeelding of URL heeft voorrang.</small></div></div>

            <!-- TRACK TRIGGER KOPPELING -->
            <div style="background:rgba(99,246,255,0.06); border:1px solid rgba(99,246,255,0.25); border-radius:8px; padding:10px 14px; margin-top:10px;">
                <div style="font-size:12px; font-weight:900; color:#63f6ff; margin-bottom:6px;">
                    🎵 Track Trigger Koppeling (Start direct bij specifieke plaat)
                </div>
                <div class="form-row" style="align-items:flex-end; margin-bottom:4px;">
                    <div class="field" style="flex:2;">
                        <label>Titel / Artiest Match (bv: Blof - De Zeeuwse Kust of deels: Aan De Kust)</label>
                        <input type="text" name="sf_<?= $fi ?>_track_match" id="sf_<?= $fi ?>_track_match" value="<?= htmlspecialchars($sf['track_match'] ?? '') ?>" placeholder="Exacte titel of artiest...">
                    </div>
                    <div class="field" style="flex:1;">
                        <label style="cursor:pointer; color:#ffd166;">
                            <input type="checkbox" name="sf_<?= $fi ?>_track_only" value="1" <?= !empty($sf['track_only'])?'checked':'' ?>> Alleen tonen bij deze track
                        </label>
                    </div>
                </div>
            </div>

            <!-- UITKLAPBARE TIJDSPLANNING & SCHEDULER -->
            <div style="background:rgba(5,6,14,0.7); border:1px solid rgba(255,255,255,0.08); border-radius:8px; margin-top:10px; overflow:hidden;">
                <div class="style-header" onclick="toggleStyleDrawer('sched_drawer_<?= $fi ?>')" style="padding:10px 14px; background:rgba(255,209,102,0.08);">
                    <span style="font-size:12px; font-weight:bold; color:#ffd166;">🗓️ Uitzend-Planning & Tijden (Dagen, Uren & Minuten)</span>
                    <span class="style-tag">Uitklappen ▸</span>
                </div>
                <div id="sched_drawer_<?= $fi ?>" class="style-drawer" style="padding:14px; margin-top:0;">
                    <strong style="font-size:11px; color:#a8aac8; display:block; margin-bottom:6px;">TIJDELIJKE SLIDE (leeg = altijd zichtbaar):</strong>
                    <div class="form-row" style="margin-bottom:12px; flex-wrap:wrap;">
                        <div class="field" style="max-width:230px;"><label>Zichtbaar vanaf</label><input type="datetime-local" name="sf_<?= $fi ?>_from" value="<?= htmlspecialchars($sf['show_from'] ?? '') ?>"></div>
                        <div class="field" style="max-width:230px;"><label>Zichtbaar t/m</label><input type="datetime-local" name="sf_<?= $fi ?>_until" value="<?= htmlspecialchars($sf['show_until'] ?? '') ?>"></div>
                        <?php if (!empty($sf['show_until']) && strtotime($sf['show_until']) < time()): ?><div class="field" style="align-self:flex-end; color:#ff8aa0; font-size:11px;">⌛ Verlopen: wordt niet meer getoond</div><?php endif; ?>
                    </div>

                    <strong style="font-size:11px; color:#a8aac8; display:block; margin-bottom:6px;">DAGEN VAN DE WEEK:</strong>
                    <div style="display:flex; gap:10px; margin-bottom:12px; flex-wrap:wrap;">
                        <?php 
                        $dNames = ['ma'=>'Maandag', 'di'=>'Dinsdag', 'wo'=>'Woensdag', 'do'=>'Donderdag', 'vr'=>'Vrijdag', 'za'=>'Zaterdag', 'zo'=>'Zondag'];
                        $curDays = $sf['sched_days'] ?? array_keys($dNames);
                        foreach ($dNames as $dk => $dLabel): 
                        ?>
                        <label style="font-size:11px; cursor:pointer;"><input type="checkbox" name="sf_<?= $fi ?>_day_<?= $dk ?>" value="1" <?= in_array($dk, $curDays)?'checked':'' ?>> <?= $dLabel ?></label>
                        <?php endforeach; ?>
                    </div>
                    
                    <strong style="font-size:11px; color:#a8aac8; display:block; margin-bottom:6px;">SPECIFIEKE MINUTEN + SECONDEN (Leeg = continu in gekozen uren):</strong>
                    <div class="form-row" style="margin-bottom:8px; flex-wrap:wrap;">
                        <?php $mPh = ['12', '33', '43', '59']; for ($mi = 1; $mi <= 4; $mi++): ?>
                        <div class="field" style="max-width:150px;"><label>Minuut <?= $mi ?> : seconde</label>
                            <div style="display:flex; gap:4px; align-items:center;">
                                <input type="text" name="sf_<?= $fi ?>_m<?= $mi ?>" id="sf_<?= $fi ?>_m<?= $mi ?>" value="<?= htmlspecialchars($sf['sched_mins'][$mi - 1] ?? '') ?>" placeholder="bv. <?= $mPh[$mi - 1] ?>" style="width:62px; text-align:center;">
                                <span style="color:#a8aac8;">:</span>
                                <input type="number" min="0" max="59" name="sf_<?= $fi ?>_s<?= $mi ?>" id="sf_<?= $fi ?>_s<?= $mi ?>" value="<?= htmlspecialchars((string)($sf['sched_secs'][$mi - 1] ?? 0)) ?>" style="width:62px; text-align:center;" title="Seconde (0-59)">
                            </div></div>
                        <?php endfor; ?>
                    </div>
                    <label style="font-size:12px; cursor:pointer; display:block; margin-bottom:12px; color:#ffd166;">
                        <input type="checkbox" name="sf_<?= $fi ?>_precise" value="1" <?= !empty($sf['precise']) ? 'checked' : '' ?>>
                        ⏱️ Precies op tijd starten: verschijnt op de ingestelde minuut:seconde, gaat vóór alle andere slides en blijft "Seconden" lang staan (stream-vertraging wordt meegerekend)
                    </label>

                    <strong style="font-size:11px; color:#a8aac8; display:block; margin-bottom:6px;">ACTIEVE UREN (24-uurs):</strong>
                    <div class="sched-hours-grid" id="hours_grid_<?= $fi ?>">
                        <?php 
                        $curHours = $sf['sched_hours'] ?? range(0,23);
                        for ($h = 0; $h <= 23; $h++): 
                        ?>
                        <label class="sched-cell"><input type="checkbox" name="sf_<?= $fi ?>_h_<?= $h ?>" value="1" <?= in_array($h, $curHours)?'checked':'' ?>> <?= sprintf('%02d:00', $h) ?></label>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- MEDIA UPLOAD MET THUMB VOORBEELD EN KRUISJE -->
            <div class="form-row" style="margin-top:12px; align-items:center;">
                <div class="media-thumb-preview" id="thumb_slide_f_<?= $fi ?>">
                    <?php if ($sfMedia): 
                        $isV = preg_match('/\.(mp4|webm|m4v|mov|ogv|mkv|m3u8)(?:[?#].*)?$/i', $sfMedia);
                        echo $isV ? "<video src='$sfMedia' autoplay loop muted playsinline></video>" : "<img src='$sfMedia'>";
                    ?>
                        <div class="del-btn" onclick="clearSlideMedia('custom', <?= $fi ?>)">✕</div>
                    <?php else: ?>
                        <span class="empty-txt">Geen media</span>
                    <?php endif; ?>
                </div>
                <div class="field"><label>Achtergrond Media</label><input type="file" name="upload_slide_f_<?= $fi ?>"></div>
                <div class="field"><label>Of Pad</label><input type="text" name="sf_<?= $fi ?>_media_url" id="sf_<?= $fi ?>_media_url" value="<?= $sfMedia ?>" placeholder="cmb-uploads/..."></div>
                <div class="field" style="max-width:70px;"><label>Blur (px)</label><input type="number" name="sf_<?= $fi ?>_blur" id="sf_<?= $fi ?>_blur" value="<?= $sf['blur'] ?>" min="0" max="25"></div>
                <div class="field" style="max-width:70px;"><label>Donker (%)</label><input type="number" name="sf_<?= $fi ?>_darken" id="sf_<?= $fi ?>_darken" value="<?= $sf['darken'] ?>" min="0" max="95"></div>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>

<?php include CMB_ROOT . '/cmb-studio-parts/cmb-studio-clipslides.php'; ?>
</div>

<script>
// Betrouwbare fallback: de oude knoppen riepen functies aan die niet waren gedefinieerd.
window.saveSingleSlide = window.saveSingleSlide || function(type, index) {
  var form = document.getElementById('adminForm'); if (!form) return;
  var tab = form.querySelector('input[name="active_tab_context"]'); if (tab) tab.value = 'slides';
  var save = form.querySelector('button[name="save_studio"]');
  if (save) { save.click(); } else { var h=document.createElement('input'); h.type='hidden'; h.name='save_studio'; h.value='1'; form.appendChild(h); form.submit(); }
};
window.resetSingleSlide = window.resetSingleSlide || function(index) {
  if (!confirm('Vrije slide #' + index + ' leegmaken? De wijziging wordt opgeslagen.')) return;
  var card=document.getElementById('card_slide_f_'+index); if (!card) return;
  card.querySelectorAll('input[type="text"],input[type="url"],textarea').forEach(function(el){el.value='';});
  card.querySelectorAll('input[type="checkbox"]').forEach(function(el){el.checked=false;});
  card.querySelectorAll('select').forEach(function(el){el.selectedIndex=0;});
  var form=document.getElementById('adminForm'); var save=form && form.querySelector('button[name="save_studio"]'); if(save) save.click();
};
</script>
