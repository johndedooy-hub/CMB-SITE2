<?php
/**
 * CMB verkeersinfo: files en ongelukken in heel Nederland uit NDW open data (actueel beeld, DATEX II).
 * Het resultaat staat in cmb-data/cmb-verkeer.json; de speler leest alleen dat kleine bestand.
 */
const CMB_VERKEER_URL = 'https://opendata.ndw.nu/actueel_beeld.xml.gz';

function cmb_verkeer_file() { return cmb_data_dir() . 'cmb-verkeer.json'; }

function cmb_verkeer_download($url) {
    if (!preg_match('#^https://#i', $url)) return '';
    $raw = '';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'CMB-Verkeer/1.0']);
        $raw = (string)curl_exec($ch);
        curl_close($ch);
    }
    if ($raw === '') {
        $ctx = stream_context_create(['http' => ['timeout' => 25, 'header' => "User-Agent: CMB-Verkeer/1.0\r\n"]]);
        $raw = (string)@file_get_contents($url, false, $ctx);
    }
    if (strlen($raw) > 2 && substr($raw, 0, 2) === "\x1f\x8b") { $d = @gzdecode($raw); $raw = $d === false ? '' : $d; }
    return $raw;
}

/** Leest de situaties uit DATEX II (v2.3 of v3) en telt files en ongelukken. */
function cmb_verkeer_parse($xml) {
    $out = ['files' => 0, 'ongelukken' => 0, 'km' => 0.0, 'items' => [], 'types' => [], 'sample' => '', 'sample_file' => ''];
    if ($xml === '' || !class_exists('XMLReader')) return $out;
    $r = new XMLReader();
    if (!@$r->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT)) return $out;
    $now = time(); $seen = [];
    while (@$r->read()) {
        if ($r->nodeType !== XMLReader::ELEMENT || $r->localName !== 'situationRecord') continue;
        $node = @$r->expand();
        if (!$node) continue;
        $doc = new DOMDocument();
        $doc->appendChild($doc->importNode($node, true));
        $xp = new DOMXPath($doc);
        $el = $doc->documentElement;
        $type = $el->getAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'type') ?: $el->getAttribute('xsi:type');
        $type = preg_replace('/^.*:/', '', (string)$type);
        $out['types'][$type] = ($out['types'][$type] ?? 0) + 1;
        if ($type !== 'Accident' && $type !== 'AbnormalTraffic') continue;
        if ($type === 'AbnormalTraffic' && $out['sample_file'] === '') $out['sample_file'] = mb_substr(preg_replace('/\s+/', ' ', (string)$doc->saveXML($el)), 0, 3500);
        if ($out['sample'] === '') $out['sample'] = mb_substr(preg_replace('/\s+/', ' ', (string)$doc->saveXML($el)), 0, 2500);
        $first = function ($name) use ($xp) { $n = $xp->query(".//*[local-name()='$name']"); return $n->length ? trim($n->item(0)->textContent) : ''; };
        if (strtolower($first('validityStatus')) === 'suspended') continue;
        $end = $first('overallEndTime'); if ($end !== '' && ($t = strtotime($end)) && $t < $now) continue;
        if ($type === 'AbnormalTraffic') { $at = strtolower($first('abnormalTrafficType')); if ($at === 'heavytraffic' || $at === 'slowtraffic') continue; }
        // Tekst: eerst de opmerkingen, anders alle losse waarden
        $texts = [];
        foreach ($xp->query(".//*[local-name()='generalPublicComment']//*[local-name()='value'] | .//*[local-name()='nonGeneralPublicComment']//*[local-name()='value']") as $n) { $v = trim($n->textContent); if ($v !== '') $texts[] = $v; }
        $text = $texts ? preg_replace('/\s+/', ' ', $texts[0]) : '';
        $road = '';
        foreach ($xp->query(".//*[local-name()='roadNumber']") as $n) { $v = strtoupper(preg_replace('/\s+/', '', trim($n->textContent))); if (preg_match('/^[ANE]\d{1,3}$/', $v)) { $road = $v; break; } }
        if ($road === '' && preg_match('/\b([ANE])\s?(\d{1,3})\b/', implode(' ', $texts), $m)) $road = $m[1] . $m[2];
        $m1 = (float)$first('queueLength'); if ($m1 <= 0) $m1 = (float)$first('lengthAffected');
        $km = $m1 > 0 ? round($m1 / 1000, 1) : 0;
        $dl = (float)$first('delayTimeValue'); $delay = $dl > 0 ? max(1, (int)round($dl / 60)) : 0;
        $lats = $xp->query(".//*[local-name()='latitude']"); $lons = $xp->query(".//*[local-name()='longitude']");
        $lat = $lats->length ? trim($lats->item(0)->textContent) : ''; $lon = $lons->length ? trim($lons->item(0)->textContent) : '';
        $lat2 = $lats->length > 1 ? trim($lats->item($lats->length - 1)->textContent) : ''; $lon2 = $lons->length > 1 ? trim($lons->item($lons->length - 1)->textContent) : '';
        if ($lat === '' || $lon === '') {   // files: lijn met coördinaten (posList, 'breedte lengte breedte lengte ...')
            $pls = $xp->query(".//*[local-name()='posList']");
            if ($pls->length) {
                $n1 = preg_split('/\s+/', trim($pls->item(0)->textContent));
                if (count($n1) >= 2) { $lat = $n1[0]; $lon = $n1[1]; }
                $n2 = preg_split('/\s+/', trim($pls->item($pls->length - 1)->textContent)); $c2 = count($n2);
                if ($c2 >= 4) { $lat2 = $n2[$c2 - 2]; $lon2 = $n2[$c2 - 1]; }
            }
        }
        $codes = []; foreach ($xp->query(".//*[local-name()='specificLocation']") as $n) { $v = trim($n->textContent); if (ctype_digit($v) && $v !== '0') $codes[] = (int)$v; }
        $cn = $xp->query(".//*[local-name()='causeDescription']//*[local-name()='value']"); $cause = $cn->length ? mb_substr(trim($cn->item(0)->textContent), 0, 80) : '';
        $dirc = strtolower($first('alertCDirectionCoded'));
        $key = $el->getAttribute('id') ?: ($type . '|' . $road . '|' . $text . '|' . $lat . '|' . $lon);
        if (isset($seen[$key])) continue; $seen[$key] = 1;
        $out['items'][] = ['t' => $type === 'Accident' ? 'ongeluk' : 'file', 'road' => $road, 'text' => mb_substr($text, 0, 160), 'km' => $km, 'lat' => $lat, 'lon' => $lon, 'lat2' => $lat2, 'lon2' => $lon2, 'delay' => $delay, 'c1' => $codes[0] ?? 0, 'c2' => count($codes) > 1 ? $codes[count($codes) - 1] : 0, 'dc' => $dirc, 'cause' => $cause];
        if ($type === 'Accident') $out['ongelukken']++; else { $out['files']++; $out['km'] += $km; }
    }
    $r->close();
    usort($out['items'], function ($a, $b) { return [$a['t'] === 'file' ? 0 : 1, -$a['km']] <=> [$b['t'] === 'file' ? 0 : 1, -$b['km']]; });
    $out['km'] = round($out['km'], 1);
    return $out;
}

function cmb_verkeer_refresh() {
    @set_time_limit(60);
    $s = cmb_load_settings_safe();
    $url = trim((string)($s['verkeer_url'] ?? '')) ?: CMB_VERKEER_URL;
    $f = cmb_verkeer_file();
    $old = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $xml = cmb_verkeer_download($url);
    if ($xml === '') { $old['tried'] = time(); $old['err'] = 'Ophalen mislukt'; @file_put_contents($f, json_encode($old), LOCK_EX); return $old; }
    $p = cmb_verkeer_parse($xml);
    $GLOBALS['cmb_geo_dbg'] = ['ok' => 0, 'fail' => 0, 'last' => ''];
    $p['items'] = cmb_verkeer_places($p['items']);
    $p['geo'] = $GLOBALS['cmb_geo_dbg']; $p['vild'] = $GLOBALS['cmb_vild_dbg'] ?? [];
    $p['t'] = time(); $p['tried'] = time(); $p['err'] = '';
    @file_put_contents($f, json_encode($p, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $p;
}

function cmb_load_settings_safe() {
    $f = cmb_data_dir() . 'cmb-settings.json';
    return is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
}


/** Weg en plaats opzoeken bij een coördinaat (OpenStreetMap Nominatim); onthouden in cmb-verkeer-geo.json. */
function cmb_verkeer_geo_one($lat, $lon, array &$geo, &$new, &$changed) {
    if ($lat === '' || $lon === '' || !is_numeric($lat) || !is_numeric($lon)) return null;
    $k = round((float)$lat, 3) . ',' . round((float)$lon, 3);
    if (isset($geo[$k]) && (($geo[$k]['v'] ?? 1) >= 2 || $geo[$k]['road'] !== '')) return $geo[$k];
    if ($new >= 40 || !function_exists('curl_init')) return null;
    if ($new > 0) usleep(1050000);   // Nominatim: maximaal 1 aanvraag per seconde
    $new++;
    $ch = curl_init('https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=16&addressdetails=1&extratags=1&accept-language=nl&lat=' . urlencode((string)$lat) . '&lon=' . urlencode((string)$lon));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_USERAGENT => 'CMB-Verkeer/1.0 (radio player; ' . (cmb_pl_mail(cmb_load_settings_safe()) ?: 'contact onbekend') . ')']);
    $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    $j = json_decode($body, true);
    if (!is_array($j) || empty($j['address'])) { $GLOBALS['cmb_geo_dbg']['fail']++; $GLOBALS['cmb_geo_dbg']['last'] = 'HTTP ' . $code . ' ' . $err; return null; }
    $a = $j['address']; $road = '';
    foreach ([$j['extratags']['ref'] ?? '', $j['extratags']['nat_ref'] ?? '', $a['road'] ?? '', $j['name'] ?? ''] as $c) if (preg_match('/\b([ANE])\s?(\d{1,3})\b/', (string)$c, $m)) { $road = $m[1] . $m[2]; break; }
    $geo[$k] = ['road' => $road, 'place' => (string)($a['village'] ?? $a['town'] ?? $a['city'] ?? $a['suburb'] ?? $a['municipality'] ?? ''), 't' => time(), 'v' => 2];
    $GLOBALS['cmb_geo_dbg']['ok']++; $changed = true;
    return $geo[$k];
}

function cmb_verkeer_places(array $items) {
    @set_time_limit(120);
    $gf = cmb_data_dir() . 'cmb-verkeer-geo.json';
    $geo = is_file($gf) ? (json_decode((string)@file_get_contents($gf), true) ?: []) : [];
    $new = 0; $changed = false;
    $vild = cmb_vild_load(); $GLOBALS['cmb_vild_dbg'] = ['hit' => 0, 'miss' => 0, 'nocode' => 0, 'missed' => []];
    foreach ($items as &$it) {
        $vh = cmb_vild_apply($it, $vild);
        if ($vh) { unset($it['lat'], $it['lon'], $it['lat2'], $it['lon2'], $it['c1'], $it['c2'], $it['dc']); continue; }
        $a = cmb_verkeer_geo_one($it['lat'] ?? '', $it['lon'] ?? '', $geo, $new, $changed);
        $b = ($it['lat2'] ?? '') !== '' ? cmb_verkeer_geo_one($it['lat2'], $it['lon2'], $geo, $new, $changed) : null;
        if ($a && $it['road'] === '') $it['road'] = $a['road'] ?: (($b['road'] ?? '') ?: '');
        if ($it['text'] === '' && $a && $a['place'] !== '') {
            $label = $it['t'] === 'file' ? 'File' : 'Ongeluk';
            $it['where'] = ($b && $b['place'] !== '' && $b['place'] !== $a['place']) ? 'tussen ' . $a['place'] . ' en ' . $b['place'] : 'bij ' . $a['place'];
            $it['text'] = $label . ' ' . $it['where'];
        }
        if ($it['text'] === '') $it['text'] = $it['t'] === 'file' ? 'File' : 'Ongeluk';
        unset($it['lat'], $it['lon'], $it['lat2'], $it['lon2'], $it['c1'], $it['c2'], $it['dc']);
    }
    unset($it);
    if ($changed) { if (count($geo) > 600) $geo = array_slice($geo, -400, null, true); @file_put_contents($gf, json_encode($geo, JSON_UNESCAPED_UNICODE), LOCK_EX); }
    return $items;
}


/** VILD (NDW locatietabel 6.14.A): wegnummer, afrit en richting bij een alertC-locatiecode. */
function cmb_vild_load() {
    static $v = null;
    if ($v !== null) return $v;
    $f = __DIR__ . '/cmb-vild.json';
    $v = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    return $v;
}

function cmb_vild_pname($n) { return trim(preg_replace('/^Knooppunt\s+/i', '', (string)$n)); }

function cmb_vild_label(array $pt) {
    // [wegnummer, naam, afritnummer, lijn, type]
    $name = cmb_vild_pname($pt[1]); $t = (string)($pt[4] ?? '');
    if ($pt[2] !== '' && $pt[2] !== '0' && strpos($t, 'P1.3') === 0) return $name . ' (' . $pt[2] . ')';
    if (strpos($t, 'P1.1') === 0 || strpos($t, 'P1.2') === 0) return 'knooppunt ' . $name;
    return $name;
}

/** Vult road/dir/where/text in; geeft true als de code in de tabel stond. */
function cmb_vild_apply(array &$it, array $vild) {
    $c1 = (int)($it['c1'] ?? 0); $c2 = (int)($it['c2'] ?? 0);
    if (!$c1 || empty($vild['p'])) { $GLOBALS['cmb_vild_dbg']['nocode']++; return false; }
    $p1 = $vild['p'][$c1] ?? null; $p2 = $c2 ? ($vild['p'][$c2] ?? null) : null;
    if (!$p1 && !$p2) { $GLOBALS['cmb_vild_dbg']['miss']++; if (count($GLOBALS['cmb_vild_dbg']['missed']) < 8) $GLOBALS['cmb_vild_dbg']['missed'][] = $c1; return false; }
    $GLOBALS['cmb_vild_dbg']['hit']++;
    $a = $p1 ?: $p2;
    $road = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', explode('/', (string)$a[0])[0]));
    $ln0 = $vild['l'][$a[3] ?? 0] ?? null;
    $cand = [$road, $ln0 ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', explode('/', (string)($ln0[2] ?? ''))[0])) : '', $p2 ? strtoupper(preg_replace('/[^A-Za-z0-9]/', '', explode('/', (string)$p2[0])[0])) : ''];
    foreach ($cand as $cr) if (preg_match('/^[ANE]\d{1,3}$/', $cr)) { $it['road'] = $cr; break; }
    // richting: positief = naar tweede naam van het wegvak, negatief = naar eerste naam
    $dir = '';
    $ln = $vild['l'][$a[3] ?? 0] ?? null;
    if ($ln && ($it['dc'] ?? '') !== '') {
        $dir = ($it['dc'] === 'negative') ? $ln[0] : (($it['dc'] === 'positive') ? $ln[1] : '');
        $dir = cmb_vild_pname($dir);
    }
    $it['dir'] = $dir;
    $it['route'] = ($ln && $dir !== '') ? (($it['dc'] ?? '') === 'negative' ? cmb_vild_pname($ln[1]) . ' → ' . $dir : cmb_vild_pname($ln[0]) . ' → ' . $dir) : '';
    $l1 = cmb_vild_label($a); $l2 = ($p1 && $p2 && $c2 !== $c1) ? cmb_vild_label($p2) : '';
    if ($it['t'] === 'file' && $l2 !== '' && $l2 !== $l1) $where = 'tussen ' . $l2 . ' en ' . $l1;   // in rijrichting: eerst het staartje, dan de kop van de file
    else $where = 'bij ' . $l1;
    $it['where'] = $where;
    $it['text'] = ($it['t'] === 'file' ? 'File' : 'Ongeluk') . ($dir !== '' ? ' richting ' . $dir : '') . ' ' . $where;
    return true;
}
