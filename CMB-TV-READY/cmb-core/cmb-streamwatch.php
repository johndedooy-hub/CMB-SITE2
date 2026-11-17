<?php
/**
 * CMB - STREAMWATCH: mail naar de dj/beheerder als een stream uitvalt (en als hij terugkomt).
 *
 * - Het mailadres staat in Studio > Streams (instelling alert_mail). Leeg = geen mails.
 * - Elke minuut wordt gecontroleerd (zolang er iemand luistert via get_track.php, of via cmb-streamwatch.php
 *   als je daar een cronjob op zet). Eerst 2 keer achter elkaar mislukt, dan pas een mail (geen storm bij haperingen).
 * - Per storing maar 1 mail, daarna 1 mail "weer in de lucht".
 * - Status staat in cmb-data/streamwatch.json.
 */

function cmb_alert_mail(array $settings) {
    $m = trim((string)($settings['alert_mail'] ?? ''));
    return preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $m) ? $m : '';
}

/** Probeert een audiostream te openen en een stukje audio te lezen. true = de stream leeft. */
function cmb_stream_probe($url) {
    $url = trim((string)$url);
    if (!preg_match('#^https?://#i', $url)) return false;
    $got = 0;
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (CMB StreamWatch)',
            CURLOPT_HTTPHEADER => ['Icy-MetaData: 0'],
            CURLOPT_WRITEFUNCTION => function ($c, $d) use (&$got) { $got += strlen($d); return $got >= 4096 ? 0 : strlen($d); },
        ]);
        @curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $got >= 1024 && in_array($code, [0, 200, 206], true);
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 8, 'header' => "User-Agent: Mozilla/5.0 (CMB StreamWatch)\r\nIcy-MetaData: 0\r\n"],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
    ]);
    $fp = @fopen($url, 'rb', false, $ctx);
    if (!$fp) return false;
    stream_set_timeout($fp, 8);
    $d = @fread($fp, 4096);
    fclose($fp);
    return is_string($d) && strlen($d) >= 1024;
}

function cmb_streamwatch_urls(array $s) {
    $clean = function ($a) { return array_values(array_filter(array_map('trim', $a), function ($u) { return preg_match('#^https?://#i', $u); })); };
    return [
        $clean([(string)($s['streamUrl'] ?? '') ?: 'https://host10.onlineradyotv.net:7048/stream/43', (string)($s['streamUrlAlt'] ?? '')]),
        $clean([(string)($s['fallbackUrl'] ?? ''), (string)($s['fallbackUrlAlt'] ?? '')]),
    ];
}

function cmb_send_alert($to, $subject, $body, $host) {
    $host = preg_replace('/^www\./i', '', preg_replace('/[^A-Za-z0-9.-]/', '', (string)$host));
    if ($host === '' || strpos($host, '.') === false) $host = 'localhost';
    $headers = "From: CMB Streamwatch <cmb@{$host}>\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\nX-Mailer: CMB";
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function cmb_streamwatch_time($ts) {
    try { $d = new DateTime('@' . (int)$ts); $d->setTimezone(new DateTimeZone('Europe/Amsterdam')); return $d->format('d-m-Y H:i'); }
    catch (Exception $e) { return date('d-m-Y H:i', (int)$ts); }
}

function cmb_streamwatch_duration($sec) {
    $sec = max(0, (int)$sec);
    if ($sec < 90) return $sec . ' seconden';
    if ($sec < 5400) return round($sec / 60) . ' minuten';
    return floor($sec / 3600) . ' uur en ' . round(($sec % 3600) / 60) . ' minuten';
}

/** Testmail vanuit Studio (knop "stuur testmail"). */
function cmb_streamwatch_test(array $settings) {
    $to = cmb_alert_mail($settings);
    if ($to === '') return false;
    $st = [];
    $f = cmb_data_dir() . 'streamwatch.json';
    if (is_file($f)) $st = json_decode((string)@file_get_contents($f), true) ?: [];
    $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($st['host'] ?? '');
    return cmb_send_alert($to, 'CMB testmail: streammelding werkt', "Dit is een testmail van de Chazzo Music Box" . ($host ? " ({$host})" : '') . ".\nAls je dit leest, krijg je voortaan een mail als een stream uitvalt en als hij terug is.\n", $host);
}

/**
 * Controleert stream 1 en 2 (hooguit 1x per minuut) en mailt bij een wisseling.
 * $force = ook als de laatste controle minder dan 55 sec geleden was (minimaal 20 sec).
 */
function cmb_streamwatch_tick(array $s, $force = false) {
    $to = cmb_alert_mail($s);
    if ($to === '') return ['skipped' => 'geen mailadres'];
    $dir = cmb_data_dir();
    if (!is_dir($dir)) return ['skipped' => 'geen datamap'];
    $lock = @fopen($dir . 'streamwatch.lock', 'c');
    if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) return ['skipped' => 'bezig'];
    $file = $dir . 'streamwatch.json';
    $st = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
    $now = time();
    if ($now - (int)($st['checked'] ?? 0) < ($force ? 20 : 55)) { flock($lock, LOCK_UN); fclose($lock); return ['skipped' => 'te kort geleden']; }
    if (!empty($_SERVER['HTTP_HOST'])) $st['host'] = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']);
    $host = $st['host'] ?? '';

    list($u1, $u2) = cmb_streamwatch_urls($s);
    $list = ['s1' => $u1, 's2' => $u2];
    $names = ['s1' => trim((string)($s['stream1_name'] ?? '')) ?: 'Stream 1', 's2' => trim((string)($s['stream2_name'] ?? '')) ?: 'Stream 2 (reserve)'];

    // 1. meten
    $alive = [];
    foreach ($list as $k => $urls) {
        if (!$urls) { $alive[$k] = null; continue; }   // niet ingesteld
        $ok = false;
        foreach ($urls as $u) { if (cmb_stream_probe($u)) { $ok = true; break; } }
        $alive[$k] = $ok;
    }

    // 2. status bijwerken en bepalen wat er gemaild moet worden
    $events = [];
    foreach ($alive as $k => $ok) {
        if ($ok === null) { unset($st[$k]); continue; }
        $cur = $st[$k] ?? ['status' => 'ok', 'fails' => 0, 'since' => 0];
        if ($ok) {
            if (($cur['status'] ?? '') === 'down') $events[] = ['up', $k, (int)($cur['since'] ?? $now)];
            $cur = ['status' => 'ok', 'fails' => 0, 'since' => 0];
        } else {
            $cur['fails'] = (int)($cur['fails'] ?? 0) + 1;
            if ($cur['fails'] >= 2 && ($cur['status'] ?? '') !== 'down') {
                $cur['status'] = 'down'; $cur['since'] = $now - 60; $events[] = ['down', $k, $now];
            }
        }
        $st[$k] = $cur;
    }
    $st['checked'] = $now;
    @file_put_contents($file, json_encode($st, JSON_PRETTY_PRINT));

    // 3. mailen
    $s1down = ($st['s1']['status'] ?? 'ok') === 'down';
    $s2down = isset($st['s2']) && ($st['s2']['status'] ?? 'ok') === 'down';
    $has2 = $alive['s2'] !== null;
    foreach ($events as $ev) {
        list($type, $k, $t) = $ev;
        $n = $names[$k]; $other = $k === 's1' ? 's2' : 's1';
        $site = $host ? " ({$host})" : '';
        if ($type === 'down') {
            $subject = "⚠ {$n} is uitgevallen";
            $body = "{$n} geeft sinds ongeveer " . cmb_streamwatch_time($t - 60) . " geen geluid meer{$site}.\n\n";
            if ($k === 's1') {
                if (!$has2) $body .= "Er is geen reservestream ingesteld, luisteraars horen dus niets.\n";
                elseif ($s2down) $body .= "LET OP: stream 2 (reserve) ligt er ook uit. Op dit moment hoort niemand iets.\n";
                else $body .= "Stream 2 (reserve) neemt automatisch over, luisteraars horen gewoon geluid.\n";
            } else {
                if ($s1down) $body .= "LET OP: stream 1 ligt er ook uit. Op dit moment hoort niemand iets.\n";
                else $body .= "Stream 1 draait nog gewoon; alleen de reserve is niet beschikbaar.\n";
            }
            $body .= "\nJe krijgt een nieuwe mail zodra de stream weer in de lucht is.\n";
        } else {
            $subject = "✓ {$n} is weer in de lucht";
            $body = "{$n} geeft weer geluid{$site}.\nDe storing duurde ongeveer " . cmb_streamwatch_duration($now - $t) . " (vanaf " . cmb_streamwatch_time($t) . ").\n";
            if ($k === 's1' && $has2) $body .= "De speler schakelt zelf terug naar stream 1.\n";
        }
        cmb_send_alert($to, $subject, $body, $host);
    }

    flock($lock, LOCK_UN); fclose($lock);
    return ['s1' => $alive['s1'], 's2' => $alive['s2'], 'mails' => count($events)];
}
