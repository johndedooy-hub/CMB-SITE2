<?php
/**
 * CHAZZO MUSIC BOX (CMB) - REALTIME STREAM METADATA ENGINE
 * Bestandsnaam: get_track.php
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/cmb-core/cmb-lib.php';
$settingsFile = cmb_data_dir() . 'cmb-settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];

$statsUrl = trim($settings['statsJsonUrl'] ?? '');
$backupApi = trim($settings['apiBackupUrl'] ?? '');
$streamUrl = trim($settings['streamUrl'] ?? '');
$fallbackUrl = trim($settings['fallbackUrl'] ?? '');

$songtitle = '';
$artist = '';
$song = '';
$cover = '';

/**
 * Gedeelde metadata-cache: meerdere bezoekers gebruiken hetzelfde resultaat.
 * Cache-key bevat de volledige streamconfiguratie, zodat wijzigingen in Studio
 * onmiddellijk een nieuwe cache opleveren. Bij tijdelijke providerproblemen
 * mag een recent resultaat kort worden hergebruikt.
 */
function cmb_cached_read_meta($statsUrl, $backupApi, $jsSrc, $sourceKey) {
    $cacheDir = cmb_cache_dir();
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cacheFile = rtrim($cacheDir, '/\\') . DIRECTORY_SEPARATOR . 'track-meta-' . hash('sha256', $sourceKey . "\n" . $statsUrl . "\n" . $backupApi . "\n" . $jsSrc) . '.json';
    $now = time();
    $cached = is_file($cacheFile) ? json_decode((string)@file_get_contents($cacheFile), true) : null;
    if (is_array($cached) && isset($cached['fetched_at'], $cached['data']) && is_array($cached['data']) && ($now - (int)$cached['fetched_at']) < 8) {
        return $cached['data'];
    }

    // Non-blocking lock voorkomt dat een grote groep bezoekers tegelijk dezelfde API bevraagt.
    $lock = @fopen($cacheFile . '.lock', 'c');
    $locked = $lock && @flock($lock, LOCK_EX | LOCK_NB);
    if (!$locked) {
        if (is_resource($lock)) @fclose($lock);
        if (is_array($cached['data'] ?? null) && ($now - (int)($cached['fetched_at'] ?? 0)) < 45) return $cached['data'];
        return cmb_read_meta($statsUrl, $backupApi, $jsSrc);
    }

    // Na het verkrijgen van de lock kan een andere bezoeker de cache al hebben gevuld.
    $cached = is_file($cacheFile) ? json_decode((string)@file_get_contents($cacheFile), true) : null;
    if (is_array($cached) && isset($cached['fetched_at'], $cached['data']) && is_array($cached['data']) && ($now - (int)$cached['fetched_at']) < 8) {
        @flock($lock, LOCK_UN); @fclose($lock);
        return $cached['data'];
    }

    $fresh = cmb_read_meta($statsUrl, $backupApi, $jsSrc);
    if (!empty($fresh['songtitle'])) {
        $payload = json_encode(['fetched_at' => $now, 'data' => $fresh], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($payload !== false) @file_put_contents($cacheFile, $payload, LOCK_EX);
        @flock($lock, LOCK_UN); @fclose($lock);
        return $fresh;
    }

    // Bij een haperende metadata-provider tonen we maximaal 45 seconden de laatste bekende track.
    if (is_array($cached['data'] ?? null) && ($now - (int)($cached['fetched_at'] ?? 0)) < 45) $fresh = $cached['data'];
    @flock($lock, LOCK_UN); @fclose($lock);
    return is_array($fresh) ? $fresh : ['songtitle' => '', 'artist' => '', 'song' => '', 'cover' => ''];
}

function fetchRemoteUrl($url, $timeout = 3) {
    if (empty($url)) return false;
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (CMB Visual Radio Metadata Engine)');
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) return $data;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => $timeout, 'header' => "User-Agent: Mozilla/5.0 (CMB Visual Radio)\r\n"],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);
    return @file_get_contents($url, false, $ctx);
}

/** Maakt van een speler-JS/stats-veld een bruikbare stats-URL (Shoutcast /stats, Sonic Panel get_info), anders leeg. */
function cmb_stats_from_js($js) {
    $js = trim((string)$js);
    if (!preg_match('#^https?://#i', $js)) return '';
    if (!preg_match('#/(stats|currentsong|7\.html)|get_info\.php|status-json#i', $js)) return '';
    if (preg_match('#/stats\??#i', $js) && stripos($js, 'json=') === false) {
        $js = rtrim($js, '?&') . (strpos($js, '?') !== false && substr($js, -1) !== '?' ? '&' : '?') . 'json=1';
        if (stripos($js, 'sid=') === false) $js .= '&sid=1';
    }
    return $js;
}

/** Leest titel/artiest/hoes uit de stats-URL, de backup-API en (indien een stats-adres) het JS-veld van één stream. */
function cmb_read_meta($statsUrl, $backupApi, $jsSrc = '') {
    $out = ['songtitle' => '', 'artist' => '', 'song' => '', 'cover' => ''];
    $dec = function ($v) { return trim(html_entity_decode(trim((string)$v), ENT_QUOTES | ENT_HTML5, 'UTF-8')); };
    foreach ([$statsUrl, $backupApi, cmb_stats_from_js($jsSrc)] as $u) {
        if (!$u || $out['songtitle']) continue;
        $raw = fetchRemoteUrl($u, 4);
        if (!$raw) continue;
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', trim($raw));
        $d = json_decode($raw, true);
        if (!is_array($d)) {
            // Shoutcast XML (<SONGTITLE>) of 7.html / currentsong als platte tekst
            if (preg_match('#<SONGTITLE[^>]*>(.*?)</SONGTITLE>#is', $raw, $mm)) $out['songtitle'] = $dec($mm[1]);
            elseif (preg_match('#<body>(.*?)</body>#is', $raw, $mm)) { $parts = explode(',', strip_tags($mm[1]), 7); if (count($parts) === 7) $out['songtitle'] = $dec($parts[6]); }
            elseif (strlen($raw) < 300 && strpos($raw, '<') === false && strpos($raw, '{') === false) $out['songtitle'] = $dec($raw);
            continue;
        }
        // Een lijst van zenders (nowplaying zonder nummer): neem de eerste
        if (isset($d[0]) && is_array($d[0])) $d = $d[0];
        // Nowplaying-API (bv. https://radio.hostrix.biz/cp/api/nowplaying/4433): now_playing.song
        if (!empty($d['now_playing']['song']) && is_array($d['now_playing']['song'])) {
            $np = $d['now_playing']['song'];
            $out['artist'] = $dec($np['artist'] ?? '');
            $out['song'] = $dec($np['title'] ?? '');
            $out['songtitle'] = $dec($np['text'] ?? '') ?: ($out['artist'] ? "{$out['artist']} - {$out['song']}" : $out['song']);
            if (!empty($np['art']) && is_string($np['art'])) $d['art'] = $np['art'];
        }
        // Icecast status-json
        if (!$out['songtitle'] && !empty($d['icestats']['source'])) {
            $src = $d['icestats']['source'];
            if (isset($src[0])) $src = $src[0];
            if (!empty($src['title'])) $out['songtitle'] = $dec((!empty($src['artist']) ? $src['artist'] . ' - ' : '') . $src['title']);
        }
        foreach (['songtitle', 'title', 'song', 'current_song'] as $k) {
            if ($out['songtitle']) break;
            if (!empty($d[$k]) && is_string($d[$k])) $out['songtitle'] = $dec($d[$k]);
        }
        if (!$out['songtitle'] && !empty($d['data'][0]['song'])) $out['songtitle'] = $dec($d['data'][0]['song']);
        if (!$out['songtitle'] && !empty($d['data'][0]['track']['title'])) {
            $out['artist'] = $dec($d['data'][0]['track']['artist'] ?? '');
            $out['song'] = $dec($d['data'][0]['track']['title'] ?? '');
            $out['songtitle'] = $out['artist'] ? "{$out['artist']} - {$out['song']}" : $out['song'];
        }
        // Hoes: Centova (imageurl) of Sonic Panel (art); standaard "geen hoes"-plaatjes overslaan
        foreach ([$d['data'][0]['track']['imageurl'] ?? '', $d['art'] ?? '', $d['artwork'] ?? '', $d['cover'] ?? ''] as $c) {
            $c = is_string($c) ? trim($c) : '';
            if ($c !== '' && preg_match('#^https?://#i', $c) && !preg_match('#no[-_]?cover|noimage|default#i', $c)) { $out['cover'] = $c; break; }
        }
    }
    return $out;
}

// De speler zegt welke stream je hoort (?s=1 of ?s=2). De titel komt dan alleen van die stream,
// zodat je nooit even de titel van de reservezender ziet terwijl stream 1 speelt.
$want = intval($_GET['s'] ?? 0);
$has2 = !empty($settings['statsJsonUrl2']) || !empty($settings['apiBackupUrl2']) || !empty($settings['jsSrc2']);
$source = 1;
if ($want === 2 && $has2) {
    $m = cmb_cached_read_meta(trim($settings['statsJsonUrl2'] ?? ''), trim($settings['apiBackupUrl2'] ?? ''), $settings['jsSrc2'] ?? '', 'stream-2');
    $source = 2;
} else {
    // 1. Stream 1 (hoofdinfrastructuur): eigen stats-URL + eigen backup-API
    $m = cmb_cached_read_meta($statsUrl, $backupApi, $settings['jsSrc'] ?? '', 'stream-1');
    // 2. Oude spelers (zonder ?s=): geeft stream 1 niets, dan neemt stream 2 het over
    if (!$m['songtitle'] && $want === 0 && $has2) {
        $m2 = cmb_cached_read_meta(trim($settings['statsJsonUrl2'] ?? ''), trim($settings['apiBackupUrl2'] ?? ''), $settings['jsSrc2'] ?? '', 'stream-2');
        if ($m2['songtitle']) { $m = $m2; $source = 2; }
    }
}
$songtitle = $m['songtitle']; $artist = $m['artist']; $song = $m['song']; $cover = $m['cover'];

// 3. Splits songtitle netjes op in Artiest en Titel
if ($songtitle && (!$artist || !$song)) {
    if (strpos($songtitle, ' - ') !== false) {
        $parts = explode(' - ', $songtitle, 2);
        $artist = trim($parts[0]);
        $song = trim($parts[1]);
    } else {
        $artist = trim($settings['text_behind_sub'] ?? 'CHAZZO MUSIC BOX');
        $song = $songtitle;
    }
}

// 4. Fallback indien de stream niets uitzendt of offline is
$isFallback = !$songtitle;
if (!$songtitle) {
    $fallbackTitle = trim($settings['track_fallback_title'] ?? 'CMB Live Uitzending');
    // Reservetitel: "ARTIEST - TITEL" wordt net als bij de stream gesplitst; zonder " - " alleen een titel
    if (strpos($fallbackTitle, ' - ') !== false) {
        $fp = explode(' - ', $fallbackTitle, 2);
        $artist = trim($fp[0]);
        $song = trim($fp[1]);
    } else {
        $artist = '';
        $song = $fallbackTitle;
    }
    $songtitle = $fallbackTitle;
}

echo json_encode([
    'artist' => $artist,
    'song' => $song,
    'songtitle' => $songtitle,
    'cover' => $cover,
    'source' => $source,
    'fallback' => $isFallback
], JSON_UNESCAPED_SLASHES);

// Streamwatch: zolang er iemand luistert controleert dit hooguit 1x per minuut of stream 1/2 nog leven
// (mail bij uitval naar het adres in Studio > Streams). De luisteraar wacht hier niet op.
if (function_exists('cmb_alert_mail') && cmb_alert_mail($settings) !== '') {
    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request(); else { @ob_end_flush(); @flush(); }
    @ignore_user_abort(true);
    cmb_streamwatch_tick($settings);
}
