<?php
/**
 * CHAZZO MUSIC BOX (CMB) - COVER OPZOEKEN VIA LAST.FM
 * Bestandsnaam: cmb-cover.php
 *
 * De speler vraagt hier om een albumhoes (cmb-cover.php?artist=...&title=...). De Last.fm API-sleutel
 * blijft op de server (nooit zichtbaar in de speler) en antwoorden worden bewaard in cmb-cache,
 * zodat Last.fm niet bij elk nummer opnieuw wordt gevraagd.
 * Antwoord: {"url": "https://..."} of {"url": ""} als er niets is gevonden.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300');

require_once __DIR__ . '/cmb-core/cmb-lib.php';
$settingsFile = cmb_data_dir() . 'cmb-settings.json';
$settings = file_exists($settingsFile) ? (json_decode(file_get_contents($settingsFile), true) ?: []) : [];
$key = trim((string)($settings['lastfm_key'] ?? ''));
$artist = trim((string)($_GET['artist'] ?? ''));
$title = trim((string)($_GET['title'] ?? ''));
$out = ['url' => ''];

if ($key !== '' && preg_match('/^[a-f0-9]{32}$/i', $key) && $artist !== '' && $title !== ''
    && mb_strlen($artist) < 120 && mb_strlen($title) < 160) {
    $cacheDir = cmb_cache_dir();
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    $cFile = $cacheDir . 'lfm_' . md5(mb_strtolower($artist . '|' . $title)) . '.json';
    $ttl = 7 * 86400;
    if (is_file($cFile)) {
        $c = json_decode((string)@file_get_contents($cFile), true);
        if (is_array($c) && isset($c['url']) && (time() - filemtime($cFile)) < (($c['url'] === '') ? 86400 : $ttl)) {
            echo json_encode($c, JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    function lfm_get($params) {
        $url = 'https://ws.audioscrobbler.com/2.0/?' . http_build_query($params + ['format' => 'json']);
        $ctx = stream_context_create(['http' => ['timeout' => 4, 'user_agent' => 'CMB-Cover/1.0']]);
        $raw = @file_get_contents($url, false, $ctx);
        $d = $raw ? json_decode($raw, true) : null;
        return is_array($d) ? $d : [];
    }
    function lfm_pick($images) {
        // Grootste beschikbare afbeelding; Last.fm's "geen hoes"-standaardplaatje slaan we over
        $best = '';
        foreach ((array)$images as $im) {
            $u = trim((string)($im['#text'] ?? ''));
            if ($u === '' || strpos($u, '2a96cbd8b46e442fc41c2b86b821562f') !== false) continue;
            $best = $u;
        }
        return $best;
    }

    $answered = false;
    $d = lfm_get(['method' => 'track.getInfo', 'api_key' => $key, 'artist' => $artist, 'track' => $title, 'autocorrect' => 1]);
    if ($d) $answered = true;
    $url = lfm_pick($d['track']['album']['image'] ?? []);
    if ($url === '') {
        $d = lfm_get(['method' => 'track.search', 'api_key' => $key, 'artist' => $artist, 'track' => $title, 'limit' => 1]);
        if ($d) $answered = true;
        $m = $d['results']['trackmatches']['track'][0] ?? null;
        if (is_array($m)) $url = lfm_pick($m['image'] ?? []);
    }
    if (preg_match('#^https://#i', $url)) $out['url'] = $url;
    // Alleen onthouden als Last.fm echt antwoordde (een haperende verbinding mag niet een dag 'niets gevonden' bewaren)
    if ($answered) @file_put_contents($cFile, json_encode($out, JSON_UNESCAPED_SLASHES));
}
echo json_encode($out, JSON_UNESCAPED_SLASHES);
