<?php
/**
 * CHAZZO MUSIC BOX (CMB) - AI HELPER & KNOWLEDGE BASE
 * Bestandsnaam: cmb-ai-helper.php
 */
error_reporting(0);
session_start();

if (empty($_SESSION['cmb_auth'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Niet ingelogd']);
    exit;
}

require_once __DIR__ . '/cmb-core/cmb-lib.php';
$settingsFile = cmb_data_dir() . 'cmb-settings.json';
$settings = file_exists($settingsFile) ? json_decode(file_get_contents($settingsFile), true) ?: [] : [];

// Kennisbank van het CMB Kiosk Systeem
$knowledgeBase = "
CHAZZO MUSIC BOX (CMB) KIOSK PLAYER ARCHITECTUUR:
1. Bestanden:
   - index.php: Hoofdweergave van de speler (Linker slide-frame, Rechter spelerframe, Topbar, Dachmenu, Ticker, PL bar).
   - header.php: PHP data-initialisatie (+ cmb-header-func.php, cmb-header-data.php, cmb-header-css.php).
   - cmb-effects.css: Alle 100 Cover Effecten, Frame Lichteffecten en Seizoensdecor.
   - footer.php + cmb-player.js: CMBEngine JS (Live sync, track polling, weer, klok, slide rotatie, audio buffering).
   - get_track.php: Realtime metadata-uitlezing van Shoutcast/Icecast/Centova streams.
   - cmb-admin.php: Beheerpaneel (Frame verlichting per zijde, 100 cover effecten, identiteit, achtergronden, ticker, knoppen).
   - cmb-studio.php: Slide Studio (Noodslide, 5x Nieuws RSS, 5x Weer Open-Meteo, 15x Vrije Slides met Track Trigger, 15x Clipslides, 6x Widgets/RSS, Streams 1+2 met automatische overschakeling).
   - cmb-nav.php: gedeeld beheermenu.
   - stream-presets.json: Audio stream configuraties.
2. Belangrijke functies:
   - Track Trigger Slide: Vrije slides kunnen worden gekoppeld aan specifieke tracktitels (bijv. 'Blof - Aan De Kust'). Zodra die titel draait, start direct de bijbehorende slide/video.
   - Live-Sync: Wijzigingen in beheer/studio worden zonder paginarefresh live doorgevoerd.
   - Cache-busting: Via filemtime() op cmb-effects.css ververst CSS direct zonder browser cache haperingen.
";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['ask_ai'])) {
    header('Content-Type: application/json');
    $raw = file_get_contents('php://input');
    $req = json_decode($raw, true);
    $userQuestion = trim($req['question'] ?? '');
    $apiKey = trim($settings['gemini_api_key'] ?? '');

    if (empty($userQuestion)) {
        echo json_encode(['reply' => 'Stel a.u.b. een vraag over CMB.']);
        exit;
    }

    if (empty($apiKey)) {
        // Standalone offline antwoorden op basis van de ingebouwde kennisbank:
        $q = strtolower($userQuestion);
        $reply = "🤖 **CMB Assistent (Offline Modus):**\n\n";
        if (strpos($q, 'track') !== false || strpos($q, 'nummer') !== false || strpos($q, 'lied') !== false) {
            $reply .= "Om een slide aan een specifiek nummer te koppelen: ga naar **CMB Studio -> Vrije Slides**. Vul bij de slide in het veld **Track Trigger Koppeling** de titel of artiest in (bv. 'Blof' of 'De Zeeuwse Kust'). Vink optioneel 'Alleen tonen bij deze track' aan. Zodra de stream dit nummer draait, start de slide direct met jouw media!";
        } elseif (strpos($q, 'weer') !== false || strpos($q, 'plaats') !== false) {
            $reply .= "Weerlocatie instellen: ga naar **CMB Studio -> Weer & Verwachting**. Typ de gewenste plaatsnaam in bij het zoekveld en klik op 'Zoek Coördinaten'. De latitude en longitude worden direct ingevuld en de verwachting start automatisch.";
        } elseif (strpos($q, 'effect') !== false || strpos($q, 'cover') !== false) {
            $reply .= "Cover effecten aanpassen: ga naar **CMB Admin -> Tab 5 (Alle 92 Cover Effecten)**. Kies een effect uit de lijst (zoals Disc, Fire Ring, Vinyl Gold of Holo Foil) en sla op. De speler neemt dit direct live over via de Live-Sync.";
        } elseif (strpos($q, 'stream') !== false || strpos($q, 'audio') !== false) {
            $reply .= "Streams beheren: ga naar **CMB Studio -> Tab 2 (Audio Streams & Fallback)**. Je kunt kiezen uit bestaande presets of handmatig een streamUrl, fallbackUrl en statsJsonUrl invoeren.";
        } else {
            $reply .= "Ik beantwoord al je vragen over de werking van CMB (slides, effecten, streams, ticker en styling). Wil je volledige AI antwoorden? Voeg dan een gratis Google Gemini API key toe in cmb-admin.php!";
        }
        echo json_encode(['reply' => $reply]);
        exit;
    }

    // Indien Gemini API key aanwezig is: online live call naar Gemini API
    $gm = getenv('CMB_GEMINI_MODEL') ?: 'gemini-flash-latest'; // gemini-1.5-flash bestaat niet meer
    $apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/" . rawurlencode($gm) . ":generateContent?key=" . urlencode($apiKey);
    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => "Systeemhandleiding CMB:\n" . $knowledgeBase . "\n\nVraag van de beheerder:\n" . $userQuestion]
                ]
            ]
        ]
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $res = curl_exec($ch);
    curl_close($ch);

    $d = json_decode($res, true);
    $aiText = $d['candidates'][0]['content']['parts'][0]['text'] ?? 'Geen antwoord van Gemini API.';
    echo json_encode(['reply' => $aiText]);
    exit;
}
?>
