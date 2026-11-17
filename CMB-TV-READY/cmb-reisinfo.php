<?php
$embed = isset($_GET['embed']);
/* Losse glaspagina: kies Auto, Trein of OV (bus, tram, metro). Files staan direct in beeld als ze er zijn.
   Kopkleur komt uit dezelfde instelling als het info-overzicht (ov_accent). */
require_once __DIR__ . '/cmb-core/cmb-lib.php';
require_once __DIR__ . '/cmb-core/cmb-verkeer.php';
$s = cmb_load_settings_safe();
$acc = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($s['ov_accent'] ?? '')) ? $s['ov_accent'] : '#63f6ff';
$on = ['auto' => !empty($s['verkeer_on']), 'trein' => !empty($s['ov_on']) && cmb_ns_key($s) !== '', 'ov' => !empty($s['bus_on'])];
?><!doctype html>
<html lang="nl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reisinfo</title>
<style>
:root{color-scheme:dark;--gold:#ffd166;--acc:<?= $acc ?>;--muted:#c9cbe0}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;font-family:'Montserrat',system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#fff;background:radial-gradient(circle at 15% 10%,#3b1d6e 0,transparent 45%),radial-gradient(circle at 85% 80%,#0e5a6e 0,transparent 45%),#0b0b18;padding:18px 16px 30px}
.wrap{max-width:900px;margin:0 auto}
h1{margin:0;font-size:15px;letter-spacing:.04em;text-align:center;color:var(--gold);font-weight:800}
.sum{text-align:center;font-weight:600;font-size:14px;margin:4px 0 16px}
.tiles{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;margin-bottom:18px}
.tile{position:relative;cursor:pointer;min-height:84px;border-radius:14px;padding:12px;background:rgba(12,14,26,.65);border:1px solid rgba(255,255,255,.14);display:flex;flex-direction:column;justify-content:flex-end;color:#fff;font:inherit;text-align:left;transition:transform .15s,border-color .15s}
.tile:hover{transform:translateY(-3px);border-color:var(--acc)}
.tile.on{border-color:var(--acc);box-shadow:0 0 0 1px var(--acc) inset}
.tile .ic{font-size:26px;line-height:1;margin-bottom:6px}
.tile .nm{font-weight:800;font-size:14px}
.tile .ct{font-size:12px;color:var(--muted);margin-top:2px}
.tile .bd{position:absolute;top:8px;right:8px;min-width:22px;height:22px;border-radius:11px;background:#000;color:var(--acc);border:1px solid var(--acc);font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;padding:0 6px}
.tile .bd[hidden]{display:none}
.row{display:flex;gap:10px;align-items:flex-start;background:rgba(12,14,26,.65);border:1px solid rgba(255,255,255,.1);border-left:4px solid #ff5b7a;border-radius:12px;padding:10px 12px;margin-bottom:8px;backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px)}
.row.f{border-left-color:#ffb300}
.row .t{flex:1;min-width:0}
.row b{display:block;font-size:15px}
.row span{display:block;color:var(--muted);font-size:13px;margin-top:2px}
.road{min-width:46px;text-align:center;font-weight:800;padding:3px 6px;border-radius:7px;background:#d32f2f;border:2px solid #f3c9c9}
.road.n{background:#e8a200;border-color:#ffe6a3;color:#222}
.delay{font-weight:700;color:var(--gold);background:rgba(255,179,0,.18);border-radius:9px;padding:3px 8px;white-space:nowrap}
.h{font-size:11px;font-weight:800;letter-spacing:.18em;text-transform:uppercase;color:var(--acc);opacity:.9;margin:14px 2px 8px}
.empty{color:var(--muted);padding:8px 2px;text-align:center}
.src{font-size:11px;color:#9a9cb8;margin-top:10px;text-align:center}
<?php if ($embed): ?>:root{color-scheme:normal}html,body{background:transparent !important}body{padding:6px 12px 30px}.x{position:fixed;top:4px;right:6px}<?php endif; ?>
.badge-bd{}
</style></head><body><div class="wrap">
<h1>REISINFO</h1><div class="sum" id="sum"></div>
<div class="tiles" id="tiles">
<?php if ($on['auto']): ?><button class="tile" data-k="auto"><span class="bd" id="b-auto" hidden></span><span class="ic">🚗</span><span class="nm">Auto</span><span class="ct" id="c-auto"></span></button><?php endif; ?>
<?php if ($on['trein']): ?><button class="tile" data-k="trein"><span class="bd" id="b-trein" hidden></span><span class="ic">🚆</span><span class="nm">Trein</span><span class="ct" id="c-trein"></span></button><?php endif; ?>
<?php if ($on['ov']): ?><button class="tile" data-k="ov"><span class="bd" id="b-ov" hidden></span><span class="ic">🚌</span><span class="nm">OV</span><span class="ct" id="c-ov"></span></button><?php endif; ?>
</div>
<div id="list"></div><div class="src" id="src"></div>
</div>
<script>
var ON=<?= json_encode($on) ?>, D={auto:null,trein:null,ov:null}, cur='', chosen=false;
var SRC={auto:'Bron: NDW',trein:'Bron: NS',ov:'Bron: OpenOV'};
function el(t,c,x){var e=document.createElement(t);if(c)e.className=c;if(x)e.textContent=x;return e}
function hd(l,x){l.appendChild(el('div','h',x))}
function fmt(n){return String(n).replace('.',',')}
function viewAuto(l){var d=D.auto;if(!d||!d.items.length){l.appendChild(el('div','empty','Geen files of ongelukken gemeld.'));return}
 d.items.forEach(function(it){var r=el('div','row'+(it.t==='file'?' f':''));if(it.road)r.appendChild(el('div','road '+it.road.charAt(0).toLowerCase(),it.road));
  var t=el('div','t');t.appendChild(el('b','',(it.t==='file'?'🚗 File':'⚠️ Ongeluk')+(it.route?'':(it.dir?' richting '+it.dir:''))));
  if(it.route)t.appendChild(el('span','',it.route));
  var w=it.where||'';if(it.km>0)w+=(w?' · ':'')+fmt(it.km)+' km';if(w)t.appendChild(el('span','',w));
  if(it.cause)t.appendChild(el('span','','Oorzaak: '+it.cause));
  r.appendChild(t);if(it.delay>0)r.appendChild(el('div','delay','+'+it.delay+' min'));l.appendChild(r)})}
function viewTrein(l){var d=D.trein;if(!d||!d.items.length){l.appendChild(el('div','empty','Geen storingen of werkzaamheden gemeld.'));return}
 var last='';d.items.forEach(function(it){if(it.t!==last){last=it.t;hd(l,it.t==='storing'?'Storingen':'Werkzaamheden')}
  var r=el('div','row'+(it.t==='storing'?' f':''));var t=el('div','t');t.appendChild(el('b','',(it.t==='storing'?'🚆 ':'🛠️ ')+it.title));
  if(it.sit)t.appendChild(el('span','',it.sit));if(it.cause)t.appendChild(el('span','','Oorzaak: '+it.cause));if(it.alt)t.appendChild(el('span','',it.alt));else if(it.dur)t.appendChild(el('span','',it.dur));if(it.chip)t.appendChild(el('span','',it.chip));
  r.appendChild(t);l.appendChild(r)})}
function viewOv(l){var d=D.ov;if(!d||!d.bus.length){l.appendChild(el('div','empty','Geen meldingen voor bus, tram of metro.'));return}
 d.bus.forEach(function(it){var r=el('div','row f');var t=el('div','t');var nm=it.kind==='tram'?'Tram':it.kind==='metro'?'Metro':'Bus';var who=[it.op,it.region&&it.region!==it.op?it.region:''].filter(Boolean).join(' · ');var hd=(it.line?nm+' '+it.line:'')+(it.line&&who?' · ':'')+who;if(hd)t.appendChild(el('span','',hd));t.appendChild(el('b','',(it.kind==='tram'?'🚋 ':it.kind==='metro'?'🚇 ':'🚌 ')+it.title));if(it.text)t.appendChild(el('span','',it.text));r.appendChild(t);l.appendChild(r)})}
function counts(){
 var a=D.auto,t=D.trein,o=D.ov;
 function set(k,n,txt){var b=document.getElementById('b-'+k),c=document.getElementById('c-'+k);if(c)c.textContent=txt;if(b){b.textContent=n;b.hidden=!(n>0)}}
 if(a)set('auto',a.files+a.ongelukken,a.files+' files · '+a.ongelukken+' ongelukken');
 if(t)set('trein',t.storingen,t.storingen+' storingen · '+t.werk+' werk');
 if(o)set('ov',o.bus.length,o.bus.length+' meldingen')}
function pick(){
 if(chosen&&ON[cur])return;
 var a=D.auto,t=D.trein,o=D.ov;
 if(ON.auto&&a&&(a.files+a.ongelukken)>0)cur='auto';else if(ON.trein&&t&&t.storingen>0)cur='trein';else if(ON.ov&&o&&o.bus.length>0)cur='ov';else cur=ON.auto?'auto':ON.trein?'trein':'ov'}
function draw(){counts();pick();
 [].forEach.call(document.querySelectorAll('.tile'),function(b){b.classList.toggle('on',b.dataset.k===cur)});
 var l=document.getElementById('list');l.innerHTML='';
 if(cur==='auto')viewAuto(l);else if(cur==='trein')viewTrein(l);else if(cur==='ov')viewOv(l);
 var s=document.getElementById('sum'),a=D.auto,t=D.trein,o=D.ov;
 s.textContent=cur==='auto'&&a?'Heel Nederland · '+a.files+' files'+(a.km>0?' ('+fmt(a.km)+' km)':'')+' · '+a.ongelukken+' ongelukken':cur==='trein'&&t?t.storingen+' storingen · '+t.werk+' werkzaamheden':cur==='ov'&&o?o.bus.length+' meldingen':'';
 document.getElementById('src').textContent=(SRC[cur]||'')+' · bijgewerkt '+new Date().toLocaleTimeString('nl-NL',{hour:'2-digit',minute:'2-digit'})}
document.getElementById('tiles').addEventListener('click',function(e){var b=e.target.closest('.tile');if(!b)return;cur=b.dataset.k;chosen=true;draw()});
function load(){var p=[];
 if(ON.auto)p.push(fetch('cmb-verkeer-data.php',{cache:'no-store'}).then(function(r){return r.json()}).then(function(d){D.auto=d&&d.on?d:null}).catch(function(){}));
 if(ON.trein||ON.ov)p.push(fetch('cmb-ov-data.php',{cache:'no-store'}).then(function(r){return r.json()}).then(function(d){if(d&&d.on){if(ON.trein&&d.train_on)D.trein=d;if(ON.ov&&d.bus_on)D.ov=d}}).catch(function(){}));
 Promise.all(p).then(draw)}
load();setInterval(load,300000);
if(window.parent!==window){
 var closeIt=function(){parent.postMessage('cmb-reisinfo-close','*')};
 document.addEventListener('click',function(e){if(!e.target.closest('.tile,.tiles,.row,.h,.sum,h1,.empty,.src'))closeIt()});
 document.addEventListener('keydown',function(e){if(e.key==='Escape')closeIt()});
}
</script></body></html>
