<?php
/**
 * CMB OV-info: treinstoringen en werkzaamheden uit de NS API (Disruptions).
 * Het resultaat staat in cmb-data/cmb-ov.json; de speler leest alleen dat kleine bestand. De sleutel verlaat de server nooit.
 */
const CMB_OV_URL = 'https://gateway.apiportal.ns.nl/disruptions/v3?isActive=true';

function cmb_ov_file() { return cmb_data_dir() . 'cmb-ov.json'; }

function cmb_ov_download($key, &$code, &$err) {
    $code = 0; $err = '';
    if ($key === '' || !function_exists('curl_init')) { $err = $key === '' ? 'Geen sleutel ingevuld' : 'cURL ontbreekt'; return ''; }
    $ch = curl_init(CMB_OV_URL);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true, CURLOPT_USERAGENT => 'CMB-OV/1.0', CURLOPT_ENCODING => '',
        CURLOPT_HTTPHEADER => ['Ocp-Apim-Subscription-Key: ' . $key, 'Accept: application/json']]);
    $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    if ($code !== 200) { if ($err === '') $err = 'HTTP ' . $code . ($code === 401 || $code === 403 ? ' (sleutel afgewezen)' : ''); return ''; }
    if (strlen($body) > 2 && substr($body, 0, 2) === "\x1f\x8b") { $d = @gzdecode($body); if ($d !== false) $body = $d; }
    return $body;
}

function cmb_ov_lbl($v) { if (is_array($v)) { foreach (['label', 'description', 'value', 'shortLabel'] as $k) if (isset($v[$k]) && is_string($v[$k])) return trim($v[$k]); return ''; } return is_string($v) ? trim($v) : ''; }

/** Zet het NS-antwoord om naar een korte lijst. */
function cmb_ov_parse($json) {
    $out = ['storingen' => 0, 'werk' => 0, 'items' => [], 'sample' => ''];
    $j = json_decode($json, true);
    if (!is_array($j)) return $out;
    $out['raw'] = mb_convert_encoding(mb_substr($json, 0, 1500), 'UTF-8', 'UTF-8');
    // Zoek de lijst met storingen, waar die ook in het antwoord staat
    $find = function ($v, $depth) use (&$find) {
        if (!is_array($v) || $depth > 4) return null;
        if ($v && array_keys($v) === range(0, count($v) - 1) && is_array($v[0]) && (isset($v[0]['title']) || isset($v[0]['type']) || isset($v[0]['topic']))) return $v;
        foreach (['disruptions', 'payload', 'data', 'result'] as $k) if (isset($v[$k])) { $r = $find($v[$k], $depth + 1); if ($r !== null) return $r; }
        foreach ($v as $x) { $r = $find($x, $depth + 1); if ($r !== null) return $r; }
        return null;
    };
    $j = $find($j, 0) ?? [];
    $out['sample'] = mb_substr(json_encode($j[0] ?? [], JSON_UNESCAPED_UNICODE), 0, 3000);
    foreach ($j as $d) {
        if (!is_array($d)) continue;
        if (isset($d['isActive']) && !$d['isActive']) continue;
        $type = strtoupper((string)($d['type'] ?? ''));
        if ($type === 'EVENT') continue;
        $maint = ($type === 'MAINTENANCE');
        $title = cmb_ov_lbl($d['title'] ?? '');
        if ($title === '') $title = cmb_ov_lbl($d['topic'] ?? '');
        if ($title === '') continue;
        $title = rtrim($title, " .");
        $ts = (isset($d['timespans'][0]) && is_array($d['timespans'][0])) ? $d['timespans'][0] : [];
        $sit = cmb_ov_lbl($ts['situation'] ?? ($d['situation'] ?? ''));
        $cause = cmb_ov_lbl($ts['cause'] ?? ($d['cause'] ?? ''));
        $alt = cmb_ov_lbl($ts['alternativeTransport'] ?? '');
        $dur = cmb_ov_lbl($d['expectedDuration'] ?? '');
        $add = cmb_ov_lbl($d['summaryAdditionalTravelTime'] ?? '');
        $chip = $add !== '' ? $add : '';
        $out['items'][] = ['t' => $maint ? 'werk' : 'storing', 'title' => mb_substr($title, 0, 120), 'sit' => mb_substr($sit, 0, 200), 'cause' => mb_substr($cause, 0, 100), 'alt' => mb_substr($alt, 0, 100), 'dur' => mb_substr($dur, 0, 100), 'chip' => mb_substr($chip, 0, 40), 'start' => (string)($d['start'] ?? ($ts['start'] ?? '')), 'end' => (string)($d['end'] ?? ($ts['end'] ?? ''))];
        if ($maint) $out['werk']++; else $out['storingen']++;
    }
    usort($out['items'], function ($a, $b) { return ($a['t'] === 'storing' ? 0 : 1) <=> ($b['t'] === 'storing' ? 0 : 1); });
    return $out;
}

function cmb_ov_refresh($withRaw = false) {
    @set_time_limit(40);
    require_once __DIR__ . '/cmb-verkeer.php';
    $s = cmb_load_settings_safe();
    $f = cmb_ov_file();
    $old = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $body = cmb_ov_download(cmb_ns_key($s), $code, $err);
    if ($body === '') { $old['tried'] = time(); $old['err'] = $err ?: 'Ophalen mislukt'; $old['http'] = $code; @file_put_contents($f, json_encode($old, JSON_UNESCAPED_UNICODE), LOCK_EX); return $old; }
    $p = cmb_ov_parse($body); if (empty($p['items'])) $p['rawfirst'] = mb_convert_encoding(mb_substr($body, 0, 1500), 'UTF-8', 'UTF-8');
    if (isset($p['raw']) && !empty($p['items'])) unset($p['raw']);
    $p['t'] = time(); $p['tried'] = time(); $p['err'] = ''; $p['http'] = $code; $p['bytes'] = strlen($body);
    @file_put_contents($f, json_encode($p, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $p;
}


/* ---------- Bus, tram en metro: GTFS-realtime meldingen (OpenOV, protobuf) ---------- */
const CMB_OV_ALERTS_URL = 'https://gtfs.openov.nl/gtfs-rt/alerts.pb';

function cmb_pb_varint($b, &$i) { $r = 0; $sh = 0; $n = strlen($b); while ($i < $n) { $c = ord($b[$i++]); $r |= ($c & 0x7f) << $sh; if (!($c & 0x80)) return $r; $sh += 7; if ($sh > 63) break; } return 0; }

/** Leest een protobuf-bericht als lijst [veldnr => [waarden]]; lengtevelden blijven ruwe tekst. */
function cmb_pb_fields($b) {
    $i = 0; $n = strlen($b); $f = [];
    while ($i < $n) {
        $key = cmb_pb_varint($b, $i); $no = $key >> 3; $wt = $key & 7;
        if ($wt === 0) $v = cmb_pb_varint($b, $i);
        elseif ($wt === 2) { $l = cmb_pb_varint($b, $i); $v = substr($b, $i, $l); $i += $l; }
        elseif ($wt === 1) { $v = substr($b, $i, 8); $i += 8; }
        elseif ($wt === 5) { $v = substr($b, $i, 4); $i += 4; }
        else break;
        $f[$no][] = $v;
    }
    return $f;
}

function cmb_pb_text($ts) {
    $f = cmb_pb_fields($ts); $best = '';
    foreach ($f[1] ?? [] as $tr) { $t = cmb_pb_fields($tr); $txt = $t[1][0] ?? ''; $lang = $t[2][0] ?? ''; if ($txt !== '' && ($best === '' || stripos($lang, 'nl') === 0)) $best = $txt; }
    return trim(preg_replace('/\s+/', ' ', $best));
}

/** Vervoerder-id (agency_id uit OpenOV) => [naam, plaats of regio]. Onbekende id's blijven leeg. */
function cmb_ov_operator($id) {
    static $m = [
        'GVB' => ['GVB', 'Amsterdam'], 'RET' => ['RET', 'Rotterdam'], 'HTM' => ['HTM', 'Den Haag'],
        'U-OV' => ['U-OV', 'Utrecht'], 'UOV' => ['U-OV', 'Utrecht'], 'QBUZZ' => ['Qbuzz', 'Groningen/Drenthe/Utrecht'],
        'ARR' => ['Arriva', ''], 'ARRIVA' => ['Arriva', ''], 'CXX' => ['Connexxion', ''], 'CONNEXXION' => ['Connexxion', ''],
        'EBS' => ['EBS', 'Zuid-Holland'], 'KEOLIS' => ['Keolis', ''], 'BRENG' => ['Breng', 'Arnhem/Nijmegen'],
        'SYNTUS' => ['Syntus', 'Gelderland/Overijssel'], 'OVERAL' => ['OV Regio IJsselmond', 'Overijssel'], 'DOEKSEN' => ['Doeksen', 'Waddeneilanden'],
        'TESO' => ['TESO', 'Texel'], 'TEXELHOPPER' => ['Texelhopper', 'Texel'], 'WESTFLEX' => ['Westflex', 'West-Friesland'],
        'HERMES' => ['Hermes', 'Brabant'], 'DRECHTSTEDEN' => ['Drechtsteden', 'Dordrecht'], 'TWENTS' => ['Twents', 'Twente'],
        'GOVI' => ['Govi', ''], 'OVPAY' => ['OVpay', ''],
    ];
    $id = strtoupper(trim((string)$id)); if ($id === '') return null;
    if (isset($m[$id])) return $m[$id];
    foreach ($m as $k => $v) if (strlen($k) >= 3 && strpos($id, $k) !== false) return $v;
    return null;
}

/** De feed geeft geen vervoerder-id mee: herken de vervoerder aan de tekst van de melding. */
function cmb_ov_operator_text($s) {
    static $m = [
        ['/\bgvb(\.nl)?\b/iu', 'GVB', 'Amsterdam'], ['/\bRET\b|\bret\.nl\b/u', 'RET', 'Rotterdam'], ['/\bhtm(\.nl)?\b/iu', 'HTM', 'Den Haag'],
        ['/qbuzz|stadsbuzz|streekbuzz|buurtbuzz/iu', 'Qbuzz', 'Groningen/Drenthe/Friesland'], ['/rrreis/iu', 'RRReis', 'Overijssel/Gelderland'],
        ['/\bbreng\b/iu', 'Breng', 'Arnhem/Nijmegen'], ['/arriva/iu', 'Arriva', ''], ['/u-?ov\b/iu', 'U-OV', 'Utrecht'],
        ['/connexxion|transdev/iu', 'Connexxion', ''], ['/\bebs\b/iu', 'EBS', ''], ['/keolis/iu', 'Keolis', ''], ['/syntus/iu', 'Syntus', 'Gelderland/Overijssel'],
    ];
    foreach ($m as $r) if (preg_match($r[0], $s)) return [$r[1], $r[2]];
    return null;
}

function cmb_ov_alerts_parse($bin) {
    $out = ['items' => [], 'n' => 0];
    $now = time(); $seen = [];
    foreach (cmb_pb_fields($bin)[2] ?? [] as $ent) {
        $e = cmb_pb_fields($ent);
        if (!empty($e[2][0])) continue;   // is_deleted
        if (empty($e[5][0])) continue;
        $out['n']++;
        $a = cmb_pb_fields($e[5][0]);
        $active = false; $noPeriod = empty($a[1]);
        foreach ($a[1] ?? [] as $pr) { $p = cmb_pb_fields($pr); $st = (int)($p[1][0] ?? 0); $en = (int)($p[2][0] ?? 0); if (($st === 0 || $st <= $now) && ($en === 0 || $en >= $now)) $active = true; }
        if (!$active && !$noPeriod) continue;
        $modes = []; $ags = []; $rts = [];
        foreach ($a[5] ?? [] as $es) { $x = cmb_pb_fields($es); if (isset($x[3][0])) $modes[(int)$x[3][0]] = 1; if (isset($x[1][0])) $ags[strtoupper(trim((string)$x[1][0]))] = 1; if (isset($x[2][0])) $rts[trim((string)$x[2][0])] = 1; }
        if ($modes && !array_diff_key($modes, [2 => 1, 100 => 1, 101 => 1, 102 => 1, 103 => 1, 105 => 1])) continue;   // alleen trein: dat komt van NS
        $head = cmb_pb_text($a[10][0] ?? ''); $desc = cmb_pb_text($a[11][0] ?? '');
        if ($head === '' && $desc === '') continue;
        if ($head === '') { $head = mb_substr($desc, 0, 80); $desc = ''; }
        $k = md5($head . $desc); if (isset($seen[$k])) continue; $seen[$k] = 1;
        $kind = isset($modes[1]) ? 'metro' : (isset($modes[0]) ? 'tram' : (isset($modes[3]) ? 'bus' : ''));
        $op = ''; $reg = ''; $agIds = array_keys($ags);
        foreach ($agIds as $ai) { $m = cmb_ov_operator($ai); if ($m) { $op = $m[0]; $reg = $m[1]; break; } }
        $hay = $head . ' ' . $desc;
        if ($op === '') { $m = cmb_ov_operator_text($hay); if ($m) { $op = $m[0]; $reg = $m[1]; } }
        if ($kind === '') $kind = preg_match('/\btram\b/iu', $hay) ? 'tram' : (preg_match('/\bmetro\b/iu', $hay) ? 'metro' : 'bus');
        $line = '';
        if (preg_match('/\b(?:tram|bus|metro|buslijn|tramlijn|lijn|lijnen)\s*(\d{1,3}[A-Za-z]?)\b/iu', $head . ' ' . $desc, $mm)) $line = strtoupper($mm[1]);
        elseif ($kind === 'metro' && preg_match('/\blijn\s+([A-E])\b/u', $hay, $mm)) $line = $mm[1];
        $out['items'][] = ['kind' => $kind, 'title' => mb_substr(rtrim($head, ' .'), 0, 140), 'text' => mb_substr($desc, 0, 260), 'op' => $op, 'region' => $reg, 'line' => $line, 'ag' => array_slice($agIds, 0, 3), 'rt' => array_slice(array_keys($rts), 0, 3)];
    }
    return $out;
}

function cmb_ov_alerts_refresh() {
    @set_time_limit(40);
    $f = cmb_data_dir() . 'cmb-ov-bus.json';
    $old = is_file($f) ? (json_decode((string)@file_get_contents($f), true) ?: []) : [];
    $code = 0; $err = ''; $body = '';
    if (function_exists('curl_init')) {
        $ch = curl_init(CMB_OV_ALERTS_URL);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 8, CURLOPT_FOLLOWLOCATION => true, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => 'CMB-OV/1.0']);
        $body = (string)curl_exec($ch); $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); $err = curl_error($ch); curl_close($ch);
    }
    if ($code !== 200 || $body === '') { $old['tried'] = time(); $old['err'] = $err ?: ('HTTP ' . $code); $old['http'] = $code; @file_put_contents($f, json_encode($old, JSON_UNESCAPED_UNICODE), LOCK_EX); return $old; }
    if (strlen($body) > 2 && substr($body, 0, 2) === "\x1f\x8b") { $d = @gzdecode($body); if ($d !== false) $body = $d; }
    $p = cmb_ov_alerts_parse($body);
    $p['t'] = time(); $p['tried'] = time(); $p['err'] = ''; $p['http'] = $code; $p['bytes'] = strlen($body);
    @file_put_contents($f, json_encode($p, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $p;
}
