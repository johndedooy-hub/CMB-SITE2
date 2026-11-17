# CMB TV-READY — zelfstandige nieuwe serververtakking

Dit is een aparte, schone installatievertakking van de bestaande CMB-code. De bestaande installatie is niet aangepast. De inhoud is bedoeld voor een lege webroot; kopieer deze mapinhoud naar de nieuwe server.

## Wat blijft behouden
- Alle bestaande PHP-pagina's, beheer- en Studio-modules.
- Alle bestaande CSS-effecten, coveranimaties, trackwissel-effecten, slides, widgets, clips en video's.
- De huidige visuele timing en media-inhoud; er zijn geen effecten of videokwaliteit uitgezet.

## TV-browser / belasting
- Live-track-, weer- en live-configuratiepolls voorkomen nu overlappende verzoeken als een tv-browser traag antwoordt. Dit verandert de inhoud niet en voorkomt dat achterstallige netwerkverzoeken zich opstapelen.
- `cmb-tv-check.php` toont de browser/user-agent en codec-signalen zonder sleutels of instellingen te tonen. Open deze pagina op de doel-tv.
- Video's worden niet getranscodeerd en effecten zijn niet uitgezet.

## H.265 / HEVC-links
- Directe MP4/MOV-links met H.265/HEVC worden als videobron behandeld; H.265 is de codec ín het bestand, niet een aparte URL-soort.
- Directe MKV- en HLS `.m3u8`-links zijn ook herkenbaar gemaakt. Native HLS- en MKV-ondersteuning verschilt sterk per tv-browser.
- De browser/decoder moet HEVC ondersteunen. `canPlayType()` geeft alleen een aanwijzing; test de echte video op de beoogde tv. Bij gebrek aan HEVC-ondersteuning is een H.264/AAC fallback nodig. Alleen de URL veranderen kan een bestaand bestand niet naar H.265 omzetten.
- Voor uploads is `.mkv` toegelaten, maar afspelen hangt af van de browser. Upload alleen vertrouwde media.

## Eerste installatie op lege server
1. Upload de volledige inhoud van deze map naar de nieuwe lege webroot.
2. Controleer dat PHP 8.x en Apache/.htaccess beschikbaar zijn; als de host Nginx gebruikt, zet MIME-types en cacheheaders in de Nginx-configuratie.
3. Maak een kopie van `cmb-config.example.php` volgens de bestaande installatie-instructies en stel de paden in.
4. Geef alleen `cmb-data`, `cmb-cache` en `cmb-uploads` schrijfrechten die nodig zijn. Plaats echte API-sleutels nooit in openbare voorbeeldbestanden.
5. Open `cmb-tv-check.php` op de doel-tv en test daarna audio, streamfailover, cover, vrije slide, clipslide, achtergrondvideo en beheeropslag.

## Opmerking over HLS/HEVC
Een `.m3u8`-bestand kan H.265-segmenten verwijzen, maar dit project voegt geen HLS-transcoder of hls.js-bibliotheek toe. Oudere tv-browsers kunnen HLS/HEVC mogelijk niet afspelen. De checkpagina geeft capability-signalen; daadwerkelijke afspeeltest blijft noodzakelijk.
