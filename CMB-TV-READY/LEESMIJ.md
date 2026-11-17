# CMB - NEW SPLIT (wat is er nieuw)

## Installeren
Zet alle bestanden in dezelfde map als de bestaande speler (overschrijven). De mappen cmb-data, cmb-uploads en cmb-cache blijven staan; je instellingen worden gewoon overgenomen.
Nieuw: cmb-nav.php (gedeeld menu), cmb-admin.css, cmb-admin-frames.php, cmb-admin-topmenu.php, cmb-studio-clipslides.php, cmb-studio-widgets.php.
Verwijderd: cmb-studio-clips.php (vervangen door clipslides).

## Menu
Elke beheerpagina (frames, topmenu, teksten & stijl, achtergronden, ticker, cover effecten, slides, clipslides, widgets, streams, AI-assistent, back-up) toont hetzelfde menu bovenaan, maar blijft een apart PHP-bestand.

## Frames
Per zijde (links/rechts) en per rand (boven, rechts, onder, links) los in te stellen; hoeken houden hun eigen dikte/rondheid. Snelheid = seconden. Kleurverloop-kiezer + live voorbeeld. Zijden kopiëren met één knop.

## Topmenu
Eigen blok op dezelfde plek, 6 knoppen met eigen tekst/link/kleur, oogje voor de kijker blijft.

## Stijl & lettertype
Per tekst één duidelijk vak ("Stijl & lettertype") met voorbeeld, overal in beheer hetzelfde.

## Clipslides (max. 15)
Studio > Slides > Clipslides. Koppel "Artiest - Titel" aan een clip (pc/Android-upload tot 15 MB, of mp4/YouTube/Vimeo-link). De clip neemt het scherm over, speelt één keer, titel/artiest blijven zichtbaar. Een "vertraging" (sec.) stemt de clip af op de stream. Let op: server-instelling upload_max_filesize moet >= 15 MB zijn.

## Widgets & RSS (max. 6)
Studio > Widgets: RSS-feed (gecachet, 15 min), website/embed (https) of eigen code (afgeschermde sandbox). Komen in de slide-rotatie met eigen volgorde en seconden.

## Streams
Stream 1 en stream 2 zijn twee volledige infrastructuren (audio, stats-URL, backup-API, speler-JS, hoes-zoeker). Valt stream 1 uit, dan neemt stream 2 geluid én titelinfo over; de speler test stream 1 elke X seconden (instelbaar) en schakelt terug. Uitklap voor handmatig kiezen blijft bestaan.

## Effecten
100 cover effecten (optioneel buiten het frame schieten), seizoensdecor (sneeuw, blaadjes, kerst, Valentijn, ...) los aan/uit te zetten, 9 letter-animaties bij trackwissel + willekeurig.

## AI
cmb-claude.php: Claude-assistent (Anthropic API-sleutel nodig, zie cmb-data/anthropic-key.example.php). Gratis alternatief: Gemini-sleutel in cmb-ai-helper.php.

## Tussenronde (fixes)
- Opslaan op grote pagina's (Slides: >1300 velden) werkte op servers met max_input_vars=1000 niet: velden werden stilletjes afgekapt. Formulieren worden nu als één pakketje verstuurd (cmb_payload).
- Weerkleur uit "Teksten & stijl" werd in de speler niet gebruikt; nu wel.
- Schaduw aan/uit bij elke tekststijl (vinkje "aan" naast de schaduwkleur).
- Topmenu: strakker, nieuw standaardlettertype Inter (ook Manrope en Outfit in de lijst).
- Mini-voorbeeld met groen vinkje en ✕ bij alle mediavelden (slides, achtergronden, carrousel, clipslides).
- Speler: scrollen op telefoons (overflow op html én body gaf een dubbel scrollgebied).

## Ronde 3
- Slides: tekst schaalt mee met het slide-paneel (tv/groot scherm groter, telefoon kleiner), bij te stellen onder Teksten & stijl (% computer/tv, % mobiel, max. regels).
- Weerslide in de lightbox toont nu de weertegels; widget-slides ook.
- Cover-effecten: instelling "Draairichting" (mix, alles met de klok mee, alles tegen de klok in) werkt voor alle draaiende effecten.
- Widgets: eigen achtergrondmedia (upload of link, blur, donker).
- Last.fm als extra cover-bron: sleutel invullen bij Streams > Hoes. Staat in cmb-cover.php, antwoorden worden gecachet.
- Streams: "Mijn zenders" (bewaren is alleen op knop, verbergen met vinkje), stream 1 en 2 worden daaruit gevuld; het keuzepaneel voor luisteraars is verwijderd.

## Ronde 4
- Achtergrond (pagina en speler): video bleef niet doorlopen en verdween na de carrouseltijd. Nu laadt een nieuw beeld eerst onzichtbaar en verschijnt het pas als het klaar is (zachte overgang). Bij één item blijft de video gewoon loopen. Video's met ?v=... achter de bestandsnaam worden herkend. Oude carrouseltimers worden gestopt, en de eerste live-check bouwt de achtergrond niet meer opnieuw op.

### Bijwerken zonder je instellingen te verliezen
Deze zip bevat vanaf nu GEEN instellingen meer (map cmb-data bevat alleen het sleutelvoorbeeld). Pak hem gewoon uit over je site heen: je eigen cmb-settings.json, cmb-slides.json, stream-presets.json en de map cmb-uploads blijven staan. Bij een nieuwe installatie begint de speler met standaardwaarden en maakt hij die bestanden zelf aan bij het eerste opslaan.

## Ronde 5
- Teksten & stijl (tabblad Identiteit): een stijl wordt alleen nog opgeslagen als hij al bestond of je hem echt hebt aangepast. Eerder schreef elke keer opslaan voor alle teksten tegelijk "Montserrat 15px" weg, waardoor lettertype en grootte boven en onder in de speler veranderden.

## Ronde 6: grote bestanden geknipt
Werking en uiterlijk blijven precies hetzelfde; de grote bestanden zijn opgedeeld in kleinere stukken.
- header.php -> + cmb-header-func.php (functies), cmb-header-data.php (ticker/slides/live-sync), cmb-header-css.php (CSS).
- footer.php -> speler-JS staat nu in cmb-player.js (browser/tv kan hem bewaren, sneller laden; ververst automatisch na een update).
- cmb-studio.php -> + cmb-studio-save.php (opslaan), cmb-studio-slides.php (tab Slides), cmb-studio.js.
- cmb-admin.php -> + cmb-admin-func.php (functies), cmb-admin-identity.php (tab Identiteit).
- cmb-claude.php -> + cmb-claude-func.php (hulpfuncties).
Alle nieuwe bestanden horen in dezelfde map. Rechtstreeks openen via de browser geeft een lege pagina (403), ze werken alleen via het hoofdbestand.

## Ronde 7: streams (o.a. Hostrix 4433)
- Een zender in "Mijn zenders" heeft vaak twee audio-adressen (bv. Hostrix: /radio/4433/stream én :4433/stream). Het tweede adres werd genegeerd en bij "Bewaar" zelfs gewist. Nu: nieuw veld "Tweede audio-adres van dezelfde zender" bij stream 1 en stream 2. Een zender invullen vult dit veld mee; bewaren houdt het vast.
- Speler: lukt het eerste adres niet, dan probeert hij eerst het tweede adres van dezelfde zender, pas daarna stream 2. Terugschakelen test om en om beide adressen van stream 1.
- Titelinfo (get_track.php): leest nu ook een stats-adres in het JS-veld (Shoutcast /stats, XML of JSON), Sonic Panel get_info (titel + hoes "art"), Icecast status-json, en zet &amp; / &#039; om naar gewone tekens.
- Na uitpakken: open Studio > Streams, klik bij je zender op "→ Stream 1" (of 2) en sla op, zodat het tweede adres wordt ingevuld.
- Hostrix (volgens het origineel): API Backup = https://radio.hostrix.biz/cp/api/nowplaying/4433, JS = https://radio.hostrix.biz/cp/widgets.js. Gecorrigeerd in stream-presets.json (hoofdmap). get_track.php leest nu ook het nowplaying-formaat (now_playing.song: titel, artiest, hoes).
- Mijn zenders: "Focus Hostrix (4433)" heeft de juiste gegevens. Vanaf ronde 8 zit cmb-data/stream-presets.json NIET meer in de zip (jouw eigen lijst blijft staan); de kopie in de hoofdmap is bijgewerkt.

## Ronde 8: emoji, slides op de seconde, AI-hulp voor slides
- Emoji: pagina's en opslaan zijn altijd UTF-8 (ook als de server iets anders als standaard heeft). Tv's zonder emoji-lettertype krijgen Noto Color Emoji (alleen de gebruikte emoji worden geladen), dus geen blokjes meer. De watertekst knipte emoji doormidden; nu niet meer (ook 🇳🇱 en 👨‍👩‍👧 blijven heel).
- Vrije slides > Planning: per minuut een seconden-vakje en het vinkje "Precies op tijd starten". Zo'n slide verschijnt op de seconde (bv. elk uur :59:00), gaat vóór alle andere slides en blijft "Seconden" lang staan; daarna gaat de rotatie verder waar hij was.
- Bovenaan Vrije slides: "Stream-vertraging (seconden)" met de servertijd erbij en een overzicht van alle op tijd geplande slides. De speler rekent met de serverklok (een verkeerd ingestelde tv-klok maakt niet uit) en trekt de vertraging eraf, zodat de slide samenvalt met wat de luisteraar hoort.
- Tijdelijke slides: "Zichtbaar vanaf" en "Zichtbaar t/m" bij elke vrije slide. Verlopen slides worden niet meer getoond (en zijn in Studio gemarkeerd).
- AI-assistent (cmb-claude.php): kan nu zelf slides maken en aanpassen (RSS-nieuws, weer, vrije en tijdelijke slides), met back-up. Gratis alternatief: kies "Gemini (Google, gratis)" bovenaan; sleutel gratis via aistudio.google.com, zet hem in cmb-data/gemini-key.php (zie gemini-key.example.php). Ander model: omgevingsvariabele CMB_GEMINI_MODEL (standaard gemini-flash-latest). Gratis laag = beperkt aantal vragen per minuut/dag.

## Ronde 9: mappen, inlog buiten public_html, klaar voor een subdomein
### Nieuwe indeling
- Hoofdmap = alleen de pagina's die je opent: index.php, cmb-admin.php, cmb-studio.php, cmb-claude.php, cmb-backup.php, cmb-wachtwoord.php, get_track.php, cmb-cover.php (+ cmb-custom.css, je eigen CSS, blijft hier).
- cmb-core/ (gedeelde functies), cmb-player/ (onderdelen van de speler), cmb-admin-parts/ en cmb-studio-parts/ (tabbladen van het beheer), cmb-assets/ (css en js).
- cmb-core, cmb-player, cmb-admin-parts, cmb-studio-parts, cmb-data en cmb-cache zijn niet meer via de browser te openen (.htaccess). cmb-uploads en cmb-assets wel (nodig voor de speler).

### Installeren (eenmalig)
1. Pak de zip uit in public_html/cmb3 (over de bestaande bestanden heen). Je data in cmb-data en cmb-uploads blijft staan.
2. Log in en open cmb-opruimen.php: verwijdert de oude losse bestanden uit de hoofdmap (alleen als de nieuwe versie in de map staat). Klik daarna op "Klaar: dit script verwijderen".
3. Inlog buiten public_html: hernoem cmb-config.example.php naar cmb-config.php en zet hem in je thuismap, één niveau boven "domains" (dus niet in domains/chazzo.nl, daar staat DO_NOT_UPLOAD_HERE). Verwijder het voorbeeld daarna uit cmb3.
4. Beheer > Hulp & beheer > Inlog & config: laat zien welke config gevonden is en maakt een veilige wachtwoord-hash. Plak die regels in cmb-config.php. Zonder config blijft de oude inlog werken.

### Subdomein (later)
- Zet dezelfde versie in domains/<subdomein>/public_html. Hij vindt cmb-config.php in je thuismap vanzelf.
- Laat het subdomein werken met de data van cmb3 via 'per_host' in cmb-config.php (data_dir, uploads_dir, cache_dir en uploads_url; voorbeeld staat in het bestand). Opslaan op het subdomein komt dan direct in de speler op chazzo.nl/cmb3.
- API-sleutels (Claude/Gemini) kunnen nu ook in cmb-config.php in plaats van in cmb-data.

## Ronde 9b
- Speler: de teksten links- en rechtsonder staan nu vast boven de PL-balk. Klap je de balk open, dan schuift alleen de balk; de teksten blijven staan (rustiger op een groot scherm).

## Ronde 10: titel/artiest-opmaak, clip-melding, minder flitsen, thema's
- Teksten & stijl: "Artiest (in de speler)" en "Titel (in de speler)" hebben nu elk een eigen Stijl & lettertype (lettertype, grootte, kleur, verloop, schaduw). De opmaak van de "Track Fallback Titel" wordt nu ook echt gebruikt als de reservetitel in beeld staat.
- Clipslides: plak je een link naar een webpagina (bv. Pixabay), dan krijg je meteen een melding met de oplossing (downloaden en uploaden, of een link die op .mp4 eindigt). Ook na opslaan.
- Minder flitsen: de frame-randen worden alleen nog bijgewerkt als ze echt veranderd zijn; een nieuwe hoes verschijnt pas als hij geladen is.
- Titelinfo volgt de stream die je hoort: speelt stream 1, dan alleen info van stream 1 (geen korte titel van de reservezender meer). Valt de titelinfo even weg, dan blijft de laatste titel staan tot de uitval ± 24 seconden duurt.
- Thema's (Uiterlijk > Thema's & planning, cmb-themes.php):
  - 20 vaste kleurthema's: bekijken met 👁 (alleen jij ziet het voorbeeld) of als startpunt voor een eigen thema.
  - Eigen thema: kopie van je opmaak (eventueel meteen in een kleurthema). "Opmaak bewerken" opent het gewone beheer met een oranje balk: alles wat je dan opslaat gaat naar het thema, je originele opmaak blijft ongemoeid.
  - Per thema: "Alleen kleuren" (rest = je originele opmaak) of "Volledige thema-opmaak" (ook letters, achtergronden, effecten en teksten zoals de stationsnaam).
  - Planning: op datum (bv. 31-10 18:00 tot 01-11 03:00), op weekdagen (bv. zaterdag 20:00-02:00) of dagdeel (elke dag 06:00-10:00). Volgorde: datum > weekdag > dagdeel.
  - Standaard als er niets gepland is: originele opmaak, een vast thema, of Willekeurig (blijft hetzelfde tot je op "Ander willekeurig thema" klikt).
  - De speler wisselt vanzelf op het juiste moment, zonder herladen. Thema's staan in cmb-data/cmb-themes.json en cmb-data/themes/.

## Ronde 11: filmrol, hangende slide, frame-hoeken, widgets, verras me
- Studio > Slides: bovenaan een **filmrol** met alles wat de speler nu laat zien (zoals de speler het ook krijgt): nummer, soort (Nood/Nieuws/Weer/Vrij/Widget), plaatje, seconden, en waarschuwingen (slide staat erg lang, koppeltekst te kort, clip die een webpagina is). Daaronder de op tijd geplande slides en de clipslides. Klik op een kaartje = naar dat onderdeel. Ververst elke 30 s.
- **Hangende slide opsporen:** open de speler met `index.php?debug=1` (knop "🔎 Speler met uitleg" in de filmrol). Linksonder zie je welke slide er staat, waarom (rotatie / gekoppeld aan nummer / op tijd) en hoe lang nog, en of er een clip over het beeld ligt. Bekende oorzaken: een clipslide met een webpagina (blijft tot het volgende nummer), een vrije slide met track trigger (blijft het hele nummer), of heel veel seconden. Een koppeltekst korter dan 3 tekens wordt nu genegeerd (die paste op bijna elk nummer).
- **Frame-randen:** het effect had vierkante hoeken; links viel dat weg, rechts staken ze uit zodra het frame open stond voor cover-effecten. Nu volgen alle frame-effecten de ronde hoeken.
- **Widgets:** klik op een widget (ook een ingesloten pagina) opent de lightbox. RSS laat meteen de vorige versie zien en ververst daarna op de achtergrond (de speler wacht niet meer). Een ingesloten pagina wordt één slide van tevoren al geladen.
- **Achtergrond van slides:** video via een link (ook met ?... erachter), YouTube en Vimeo spelen stil en herhalend als achtergrond, ook in de lightbox. Dezelfde achtergrond start niet opnieuw bij de volgende slide.
- **Beheer:** strakker menu (compacter, op de telefoon een veegbare rij). Na opslaan blijf je in hetzelfde onderdeel (bv. Slides > Weer), op dezelfde hoogte, en bij Frames op dezelfde zijde.
- **🎲 Verras me:** bij elk frame, bij Cover effecten en bij beide achtergronden (gebruikt je eigen uploads). Alleen het formulier verandert; "↩ Terug" zet alles terug, Opslaan zet het in de speler. Bij Thema's: **🎲 Verras me helemaal** maakt een nieuw (uitgeschakeld) thema met passende kleuren, frames en cover-effect, dat je meteen kunt bekijken.
- **Back-up:** de download bevat nu ook je thema's, en importeren zet ze terug.

## Ronde 7: lichtere live-sync (haperen op tv / N4120)
- index.php?api_live_data was elke 4 s een volledig antwoord dat de speler telkens helemaal doorrekende. Nu stuurt de speler de laatste "vingerafdruk" mee; is er niets gewijzigd, dan antwoordt de server met een paar bytes en verwerkt de speler niets.
- Interval: 5 s (computer/telefoon), 12 s op tv's en zwakke apparaten (herkend aan tv-browser, ≤4 kernen of ≤4 GB). Zelfregelend: rustiger bij niets nieuws of zware verwerking (max. 10 s / 20 s), direct weer snel zodra er iets verandert.
- Tekstschaal van slides wordt alleen nog opnieuw berekend als de waarde echt verandert.
- Titel-poll (get_track.php) blijft 8 s.


## Aanvullingen in deze build
- 50 lichtgewicht SVG-achtergrondcovers in `cmb-assets/background-covers/`, selecteerbaar bij iedere Vrije Slide. Eigen media heeft voorrang op de preset.
- Opmaak van de klokwaarde en weerwaarde toegevoegd aan Beheer > Identiteit.
- Trackwisselletters uitgebreid met instelbare grootte, letterafstand, basiskleur en gloed, plus vijf extra animaties.
- Herstelde `saveSingleSlide()` / `resetSingleSlide()`-fallback voor de Vrije Slides-knoppen; deze slaan via het bestaande Studio-formulier op.


## TV-READY vertakking en H.265 (nieuwe lege server)
Deze zelfstandige vertakking bevat `cmb-tv-check.php`, basis-MIME-typen en netwerkpolling die niet overlapt op trage tv-browsers. Bestaande effecten, video-inhoud en visuele timing blijven behouden. Zie `TV-READY-INSTALL.md`. H.265 kan alleen worden afgespeeld wanneer de daadwerkelijke tv-browser/decoder HEVC ondersteunt; een URL kan de codec van een bestand niet wijzigen.


## Controle links + waterval-letters (CLA)
- Gecontroleerd: alle PHP-bestanden (syntax), alle include-paden, het beheermenu, alle links/scripts/stijlen in speler, beheer en studio. Geen gebroken links gevonden; wel opgelost: de meegeleverde zenderlijst (stream-presets.json in de hoofdmap) werd op een lege installatie niet gebruikt, waardoor "Mijn zenders" leeg bleef. Nu wordt hij bij de eerste keer openen van Studio > Streams in cmb-data gezet en dient hij tot dan als reserve.
- Beheermenu: nieuwe knop "TV-browser check" (cmb-tv-check.php) onder Hulp & beheer.
- Waterval-letters (Teksten & stijl): nu hetzelfde vak "Stijl & lettertype" als alle andere teksten: volledige lettertypelijst, grootte, kleur, kleurverloop (loopt over de letters van kleur 1 naar kleur 2) en schaduw. Letterafstand, gloed, willekeurige kleuren en animatie blijven eronder. Bestaande instellingen blijven gelijk tot je het vak aanpast.

## Weer en klok na opmaakwijziging (CLA)
- Live opmaak-update nam font/grootte/kleur van de weerwaarde, klokwaarde en datum niet mee; nu wel (de tekst zelf blijft staan).
- Weer: mislukt het ophalen, dan wordt de laatste waarde teruggezet en na 20 seconden opnieuw geprobeerd (was: pas na 10 minuten, tot die tijd "--°C").
- Klok: altijd 24-uurs notatie (uu:mm:ss), los van de taalinstelling van de tv/browser.

## Nummering cover-effecten (CLA)
- In de lijst Cover effecten stonden 101-115 tussen 92 en 93; de lijst staat nu in volgorde 1 t/m 115. Bij de trackwissel-letters staat "10. Random" nu tussen 9 en 11.

## Kleine tekst achter cover / fallback bij uitval (CLA)
- "Kleine Tekst Achter Cover" (Teksten & stijl) werkt nu echt, inclusief stijl (stond vast op CHAZZO MUSIC BOX). Een stream zonder artiest toont die tekst ook op de artiestregel.
- Bij uitval: "Track Fallback" kan nu "ARTIEST - TITEL" zijn en wordt gesplitst zoals de stream (artiestregel + titelregel). Zonder " - " staat er alleen een titel en blijft de artiestregel leeg.
- DELTADANCE komt in deze versie nergens meer als standaardtekst voor (titel, voettekst, watertekst, ticker, onder de cover, reserveslide). Vul zelf in wat je wilt zien. Opgeslagen instellingen blijven zoals ze zijn. Alleen de zendernamen in de voorbeeldlijst stream-presets.json (bv. "DD Fresh ...") zijn niet aangepast.

## Standaardzoom computer/laptop (CLA)
- Op computer/laptop (muis, breder dan 820 px, geen tv-browser) start de speler op maximaal 110%, zo groot als past in het venster (nooit kleiner dan 100%). Tv en telefoon/tablet blijven op 100%. Zoomt iemand zelf met +/-, dan blijft dat zo. Ook geen scrollbalk meer door de zoom (de minimumhoogte werd meegezoomd).

## Logo, icoon & zoekmachine (CLA)
- Beheer > Uiterlijk > "Logo, icoon & zoekmachine" (cmb-admin.php?tab=site):
  - Logo (vierkantje vóór de naam), naast het logo tekst óf een eigen stationslogo/banner, icoon voor het browsertabblad (favicon, ook als apple-touch-icon) en themakleur voor de adresbalk. Upload of link, met mini-voorbeeld.
  - Zoekmachine: paginatitel, omschrijving, zoekwoorden, website-adres (canonical), deelplaatje (Open Graph/Twitter, voor WhatsApp/Facebook), "zichtbaar voor zoekmachines" ja/nee (nee = noindex voor testversies) en automatisch schema.org-gegevens (RadioStation).
  - Zonder titel gebruikt de speler de stationsnaam, anders "Chazzo Music Box". Thema's overschrijven deze velden niet.
- Uploads: .ico is nu ook toegestaan.

## Ronde 9 (19 okt)
- Schuifbalk in de speler verborgen (scrollen werkt nog wel, balk is onzichtbaar).
- Nieuw letter-effect "16. 💥 Floep groot in beeld" (Teksten & stijl > waterval-animatie). Alleen transform/opacity, dus licht. Zit bewust niet in de willekeurige mix.
- Cover effecten: Verras me geeft nu ook een nieuwe achtergrond achter de cover (voorbeeldtegel). Aanvinken "bewaren" = wordt Spelerframe-achtergrond; anders blijft je huidige staan.
- 34 effectnamen aangepast aan wat ze tonen (alleen de naam; sleutels en nummers ongewijzigd).
- Beheer en Studio: elk onderdeel standaard ingeklapt, met samenvatting en aan/uit-label, even/oneven kleurnuance, Alles openen/sluiten. Open/dicht wordt onthouden per pagina (sessie). "Voorbeeld Aa" ongewijzigd.

- AI-assistent: OpenRouter als derde keuze naast Claude en Gemini. Sleutel in cmb-data/openrouter-key.php (voorbeeld: openrouter-key.example.php), OPENROUTER_API_KEY of 'openrouter_key' in cmb-config.php. Model: CMB_OPENROUTER_MODEL of 'openrouter_model' (standaard openrouter/auto).

## Ronde 9: Regioagenda (nieuw menu "Regioagenda")
Beheer > Regioagenda (cmb-regioagenda.php): 2 edities x 3 onderwerpen. Editie 1 = onderwerp 1-3, editie 2 = onderwerp 4-6. Per onderwerp een vinkje "Op slide in de speler" (uit = alleen in de mp3). Vaste intro (Fenna) en outro staan ingeklapt bovenaan. De getypte tekst gaat ongewijzigd naar de spraak; de slide toont dezelfde tekst (bij meerdere regels is de eerste regel de kop).
- Opslaan schrijft ff-regioagenda-1.txt, ff-regioagenda-2.txt en (voor de oude route) ff-regioagenda.txt (alles samen) in de map van CMB. Staat de website-root ergens anders: 'regio_txt_dir' in cmb-config.php.
- "Opslaan & mp3 editie 1/2/beide" start de GitHub-workflow en toont daarna per editie de status; de downloadknop wordt actief zodra de nieuwe mp3 klaar is.
- Slides: max. 6 (tekst, seconden, achtergrond onder "Slides in de speler"). Lange tekst wordt op de slide afgekapt (zie Teksten & stijl > max. regels); de mp3 blijft volledig.
- Installeren: (1) REGIO-LOSSE-BESTANDEN/regio-editie-tts.yml in de GitHub-repo zetten als .github/workflows/regio-editie-tts.yml (de oude workflow blijft ongemoeid werken); (2) GitHub-token: bestand github-token-tts.php (geeft de token terug, zoals nu) naast cmb-config.php laten staan, of 'regio_token_file' instellen; de token moet Actions lezen én schrijven mogen; (3) optioneel 'regio_repo', 'regio_workflow', 'regio_txt_url' in cmb-config.php.
- De mp3's heten Focus-Regio-News-1.mp3 en Focus-Regio-News-2.mp3 (oude Focus-Regio-News.mp3 blijft bij de oude workflow).
- REGIO-LOSSE-BESTANDEN/regioagenda-tekst.php: eenvoudige invoerpagina voor de dj met dezelfde 2x3 velden, zelfde inlog en dezelfde gegevens als het beheer. Zet hem op de website; staat CMB in een submap, pas dan $CMB_DIR bovenin aan.
- Intro en outro zijn aanpasbaar onder "Vaste begin- en eindtekst" (bij andere stationnaam, e-mail of domein). Het adres van het tekstbestand wordt vanuit het beheer aan de workflow meegegeven ('regio_txt_url'), dus een ander domein hoeft de yml niet te wijzigen. Gebruik wel de nieuwste yml uit deze zip.
- Slides (aangepast): per onderwerp een eigen "Koptitel voor de slide" (komt niet in de mp3, mag leeg; leeg = vaste titel "Regioagenda"). De gesproken tekst staat klein onder de koptitel. Opmaak (lettertype, grootte, kleur, verloop, schaduw) van koptitel en slidetekst staat onder "Slides in de speler" en geldt voor alle 6 slides.
- De GitHub-token wordt gezocht vanaf de CMB-map omhoog (public_html, domains/<site>, thuismap), net als cmb-config.php.
- Pingel: in de tekstbestanden van editie 1 en 2 staat de markering ---TING---. De workflow zet op die plek regio-ting.mp3 (moet in de hoofdmap van de GitHub-repo staan): na de opening en tussen de onderwerpen, niet na het laatste onderwerp. De pingel zit in de stem, het bedje loopt eroverheen. Ontbreekt regio-ting.mp3, dan wordt de mp3 zonder pingels gemaakt (waarschuwing in het GitHub-logboek). Gebruik de nieuwste yml uit deze zip.
- Achtergrond per slide: bij elk onderwerp klapt "Achtergrond van deze slide" open: plaatje kiezen (uit cmb-uploads of een https-link), of een nieuw plaatje uploaden (max. 8 MB, of lager als de server dat bepaalt), met eigen blur (0-30) en donker (0-90). Leeg = de standaardachtergrond onder "Slides in de speler". Uploads komen in cmb-uploads (voorvoegsel regioN_).

## Herstel na een update
- cmb-custom.css zit niet meer in de zip: dat is het eigen bestand van de AI-assistent (covers en eigen stijlen) en werd bij elke update overschreven door een leeg voorbeeld.
- Nieuw: cmb-herstel-ai.php. Open die pagina in je browser (ingelogd). Ze toont alle wijzigingen die je ooit in de AI-assistent hebt goedgekeurd, welke er niet meer in staan en zet ze met één klik terug (met back-up). Werkt alleen als de map cmb-cache (claude-pending) nog bestaat.
- Update je een site waar de AI wijzigingen aan code- of css-bestanden heeft gedaan, open dan na de update eerst deze pagina.

## Ticker: wegfaden en lopende band
Beheer > Ticker > Animatiemodus: fade, omhoog, omlaag, links of rechts wegfaden (nieuwe tekst vervangt de oude), of lopende band.
Lopende band: alle tickerregels lopen doorlopend achter elkaar, gescheiden door een bol (•), zonder te wachten. De snelheid stel je in als richtsnelheid (pixels per seconde).

## PL-balk
Beheer > Teksten & stijl > PL-balk: vast 🔒 en ✉️, daarna 8 smalle en 2 brede vrije blokjes (plaatje uploaden of link, eventueel een klik-link; de brede mogen ook een korte tekst). Zonder plaatje is een blokje niet zichtbaar. Het mailadres van ✉️ staat los van de middenregel en komt ook in de privacy-tekst.

## Info-overzicht
- In de speler: vul bij een topmenu-knop (Beheer > Topmenu & knoppen) als link `#info` in en geef de knop de tekst "info". De knop opent een glaslaag met alle rollende slides (noodbericht, tijdgebonden, tijdelijke en getriggerde slides doen niet mee). Klik op een kaartje voor de lightbox. De muziek loopt door.
- Losse pagina: zet INFO-LOSSE-BESTANDEN/info.php in public_html (naast de map cmb), adres: jouwsite.nl/info.php. Staat de speler niet in /cmb, pas dan $CMB_MAP bovenin aan. Bovenaan staat "Nu op [station]" met artiest - nummer en een play/pauze-knop. De knop "Naar de speler" opent de speler en start de muziek (?play=1).
- Het ticker-snelheidsveld werkt nu echt (er stond een dubbele regel in footer.php).

## Verborgen inlog
Klik op de laatste twee cijfers van de klok (de seconden) bovenin de speler: dat opent cmb-admin.php (de inlog). Er is niets van te zien.

## Afbeeldingenmappen in het beheermenu
Het beheermenu heeft een blok "Afbeeldingen" met Extra 1, 2 en 3. Ze openen in een nieuw tabblad de mappen ../cmb-extra-1/, ../cmb-extra-2/ en ../cmb-extra-3/ (naast de CMB-map, dus bij /cmb is dat /cmb-extra-1/).

## Nieuwe vrije slide: geen voorgevulde minuten meer
Een nieuwe vrije slide had de minuten 00, 15, 30 en 45 voorgevuld. Daardoor verscheen hij alleen rond die minuten. Nu zijn de minuten leeg (alleen een grijs voorbeeld); leeg betekent: de slide rouleert gewoon het hele uur.

## Stabiliteit (22-10)
- Info-overzicht: een vrije slide met alle dagen en alle uren aangevinkt (en zonder minuten, trigger of tijdelijke periode) telt nu mee. Slides met echte tijden, een nummer-trigger, "precies op tijd" of een tijdelijke periode doen nog steeds niet mee.
- Achtergrond-carrousels wisselen zachter (1,6 s overvloeien).
- De ticker staat vast onderin (iets lager) en de slidetekst heeft vaste ruimte, dus de tekst springt niet meer omhoog bij een ticker van twee regels.

## Achtergrond-carrousels (website en speler)
Nu 24 plekken per carrousel (was 12). De wisseltijd stel je in met minuten (0-60) én seconden (0-59), samen minimaal 2 seconden en maximaal 1 uur. Een bestaande wisseltijd in seconden wordt vanzelf omgerekend (150 s wordt 2 min 30 s).

## Compact weergave (rechterkant)
Beheer > Frames: kies bij "Weergave" Volledig of Compact. Compact = lage speler met daaronder een eigen tekstframe (eigen tekst, plaatje/video erachter, blur en donkerte). Met het vinkje ↑↓ wissel je de twee frames van plek. Volledig blijft ongewijzigd. Bezoekers kunnen dit niet omzetten.

## Eigen tekstframe (6 plekken) en inloggers
Beheer > Frames: bij Compact tot 6 plekken met elk een eigen tekst, opmaak en achtergrond; ze wisselen na het ingestelde aantal seconden. Beheer > Inlog & config: eigen wachtwoord veranderen en gebruikers toevoegen of verwijderen (opgeslagen als hash in cmb-data/cmb-users.json; verwijder dat bestand om terug te gaan naar de inlog uit cmb-config.php).

## Afbeelding maken
Beheer > Afbeelding maken (cmb-afbeelding.php): achtergrond kiezen of uploaden, tot twee teksten (lettertype, kleur, plek) en een doorzichtig logo, met direct voorbeeld. Opslaan geeft een jpg/png in cmb-uploads (afb-...); de link plak je waar een plaatje of link kan.

## Afbeelding maken: bronnen, link en download · lijnen van het eigen frame
Achtergrond en logo komen uit je uploads en uit ../cmb-extra-1, -2 en -3 (losse plaatjes). Een geplakte https-link wordt door de server opgehaald en als kopie in cmb-uploads gezet. Het resultaat kun je opslaan, downloaden of (op telefoon) delen. Het eigen tekstframe heeft bij Compact eigen lijnen (Beheer > Frames > Eigen tekstframe); zolang je daar niets instelt, is het dezelfde lijn als de speler.

## Nieuw beheermenu
Na het inloggen kom je op cmb-start.php (tegels). Op elke beheerpagina staat bovenaan een korte balk met vijf groepen (Uiterlijk, Inhoud, Regioagenda, Afbeeldingen, Hulp); de onderdelen van de gekozen groep staan eronder. De indeling staat op één plek: cmb_nav_groups() in cmb-core/cmb-nav.php.

## Plek in de bovenste regel
Beheer > Teksten & stijl > "Plek in de bovenste regel": per onderdeel (logo en logotekst, weer, tijd, middenregel) links, midden of rechts. Standaard blijft alles zoals het was. Een wijziging van de plekken ziet de speler bij de eerstvolgende keer laden.

## Rand als plaatje (lichtgewicht)
Beheer > Alle frames, per frame bovenaan "Animatie van de rand": kiezen tussen bewegende lijnen (zoals altijd) of één plaatje of een carrousel (maximaal 6, zacht overlopen). Van het plaatje zie je alleen het randje van 1-3 px; de binnenkant blijft doorzichtig, dus je achtergrond zit niet in de weg. De plaatjes staan in de map cmb-frames (12 meegeleverd, eigen jpg/png/webp toevoegen kan in Beheer). Zet ook de map cmb-frames mee op de host.

Menu: "Frames links & rechts" heet nu "Alle frames"; onder Hulp staat "AI-wijzigingen herstellen" (cmb-herstel-ai.php). Bij Licht (plaatjes) verdwijnen de zware lijninstellingen; de rand-voorbeelden staan ingeklapt en kleiner.

Bovenste regel (nieuw): Beheer > Teksten & stijl: elk onderdeel (CMB-blokje, naam/banner, weer, tijd, middenregel) een plek 1-5 van links naar rechts, of Uit. De oude links/midden/rechts-keuze wordt automatisch omgezet. Op een telefoon: naam bovenaan, weer en tijd naast elkaar, dan de middenregel.

## Linkerkant: slideshow of tegeloverzicht (+ tv-adres)
- Beheer > Frames > "Linkerkant: slideshow of tegeloverzicht": kies Slideshow (zoals altijd) of Tegeloverzicht, het aantal kolommen (1-4) en de tegelgrootte.
- Tegeloverzicht: alle gewone slides staan als tegels in het linker frame (zoals info.php). Een klik opent die slide. De ticker onderin blijft.
- Slides die op een vast moment komen, tijdelijk zijn, een noodbericht of bij een nummer starten staan zolang ze actief zijn in frame 3 (Weergave: Compact). Is frame 3 niet zichtbaar, dan komen ze over het linker frame.
- Onderregel op een tegel: Studio > vrije slide > "Onderregel op de tegel".
- TV-adres: `tv.php` (of `/cmb/tv`) toont altijd de slideshow, ook als de gewone speler op tegels staat. Gebruik dat voor tv en OBS.

## Randplaatjes met open stukken (png met doorzichtige delen)
- In cmb-frames staan 6 losse plaatjes met een open stuk (`open-…`) en 5 reeksen van 6 (`sneeuw-1..6`, `waterval-1..6`, `komeet-1..6`, `gat-wandelt-1..6`, `vonken-1..6`).
- Een reeks gebruik je door bij de rand van een frame "Licht: plaatjes die zacht wisselen" te kiezen en alle 6 aan te vinken; zet de seconden op 2 of 3 voor de mooiste beweging.
- Zelf maken: png of webp van 512 x 256 met doorzichtige delen. Alleen de buitenste rand (ca. 7 pixels) van het plaatje telt mee; waar die doorzichtig is, is de rand van het frame open.

## Tegeloverzicht: achtergrond en tijdslides
- Beheer > Frames > "Achtergrond van het overzicht": alleen per tegel het plaatje van de slide (zoals eerst), per tegel het plaatje én een achtergrond achter alles (de slide-plaatjes wisselen rustig; Donker % en Blur px instelbaar), alleen een achtergrond achter alles, of geen plaatjes.
- De eerste kop (bijvoorbeeld NIEUWS) staat naast het hoekbadgetje en de tegels beginnen er direct onder.
- Slides met een tijdvenster (bepaalde dagen of uren, minuten, of een periode) staan zolang het venster loopt in frame 3. Meerdere tegelijk wisselen af op hun eigen seconden. Een slide met "Precies op tijd" komt op de seconde en gaat daarna weer weg.

## Accentkleur van kopjes en tegels
- Beheer > Frames > "Kleur van kopjes (NIEUWS, WEER, REGIO, OVERIG), onderregel en rand": een kleurkiezer (standaard #63f6ff).
- Geldt alleen voor het tegeloverzicht, het info-venster in de speler en de losse info.php (daarvoor ook die nieuwe info.php uit INFO-LOSSE-BESTANDEN neerzetten). Slides en de rest blijven zoals ze waren.

## Melding bij uitval van een stream (nieuw)
- Studio > Streams > "Melding bij uitval": vul een mailadres in (leeg = geen mails). Met het vinkje "testmail" kun je meteen testen.
- Je krijgt 1 mail als stream 1 of 2 uitvalt (met erbij of de reserve overneemt) en 1 mail als hij weer terug is.
- Controle elke minuut zolang er iemand luistert. Ook melden als niemand luistert: cronjob elke minuut op `cmb-streamwatch.php`.
- Kleur van de slide-puntjes en de PL-knop = de kopjeskleur van het tegeloverzicht (Beheer > Linkerkant).

## Clipslides aangepast
- Een clip komt nooit meer over het hele scherm. Bij Compact speelt hij in het kleine derde venster, anders in het linker slide-frame.
- Een clip speelt nooit geluid meer; de stream blijft het enige geluid.

## .htaccess per host
De zip bevat geen `.htaccess` meer, zodat uploaden nooit uw werkende bestand overschrijft.
- `.htaccess.hostinger` (1,22 KB): met het verbergen van .php (voor Hostinger).
- `.htaccess.strato` (619 bytes): zonder dat blok (voor Strato, daar geeft het :443 en conflicten).
Nieuwe installatie: hernoem het juiste bestand naar `.htaccess`.

## Verkeer (files en ongelukken)
Nieuw: autoknop met cijfertje naast de middenregel. Klik opent een venster met files en ongelukken in heel Nederland (bron NDW, gratis). Aanzetten: beheer > Teksten & stijl > Verkeer. Nieuwe bestanden: cmb-verkeer-data.php en cmb-core/cmb-verkeer.php. De speler ververst zelf elke 5 minuten, een cronjob is niet nodig. Test: knop "Nu ophalen en testen" in het beheer.
- Verkeer: NDW geeft bij een ongeluk alleen coördinaten. Weg en plaats worden daarom eenmalig opgezocht via OpenStreetMap (Nominatim) en onthouden in cmb-data/cmb-verkeer-geo.json. Alle ongelukken worden nu apart getoond.

## Blokjes bovenin: verkeer en WhatsApp
- Elk onderdeel van de bovenste regel heeft nu een eigen plek (1 t/m 7). Nieuw: Verkeersknop en WhatsApp-knop. De middentekst blijft een eigen onderdeel.
- Verkeersknop: "Naast de middenregel" is de oude plek; kies een plek om hem los te zetten.
- WhatsApp: beheer > Teksten & stijl > WhatsApp-knop (nummer met landcode). De plek kiest u bij "plek van elk onderdeel".
- Mobiel: de blokjes staan klein tussen het weer en de tijd, de middentekst op de derde regel.

- Mobiel: weer, blokjes en tijd overlappen niet meer bij grote lettertypes.

- Verkeer: toont nu alle meldingen (geen 'en 8 meer'), de bronnaam NLRWS is geen tekst meer, bij files worden begin- en eindplaats opgezocht ('File tussen A en B'). 'Nu ophalen en testen' toont ook een voorbeeld van een file en of het opzoeken lukt.

- Verkeer: alleen meldingen met wegnummer of plaats worden getoond; de rest verschijnt zodra de plaats is opgezocht.

- Verkeer: files geven hun plek als lijn door; die wordt nu uitgelezen, zodat ook files een plaats krijgen. Het opzoeken gaat in porties van 40 per verversing, dus het duurt even voor alle files zichtbaar zijn.

- Verkeer: ook N-wegen krijgen hun wegnummer (extra veld bij OpenStreetMap). Meldingen zonder nummer worden opnieuw opgezocht.

- Verkeer: venster als bij Radio 1224: pictogram + File/Ongeluk, daaronder de plek en lengte, rechts de vertraging (+N min) als NDW die doorgeeft.

## Verkeer: VILD-locatietabel (nieuw)
`cmb-core/cmb-vild.json` (NDW VILD 6.14.A) geeft wegnummer, afrit en richting. OpenStreetMap blijft reserve als een code ontbreekt.
Test: open `cmb-verkeer-data.php?test=1` en kijk bij "vild": hit = gevonden, miss = niet in tabel.

## OV-knop (treinstoringen)
Beheer, tab identiteit: "OV (treinstoringen)": vink aan en plak de NS-sleutel. Test: `cmb-ov-data.php?test=1`.

## Reisinfo en bus/tram/metro
- `cmb-reisinfo.php`: losse glaspagina met files, ongelukken en OV samen (link of iframe).
- OV-venster toont nu ook bus/tram/metro-meldingen (OpenOV). Test: `cmb-ov-data.php?test=1`, kijk bij "bus_http" en "bus_total".

## Knoppenblok en reisinfo (update)
- Auto-, trein-, bus- en WhatsApp-knop staan samen in één blok; de middenregel staat los. Elke knop heeft zijn eigen aan/uit in het beheer. Eén plek voor het blok bij "plek van elk onderdeel".
- `cmb-reisinfo.php`: tegels Auto / Trein / OV; files staan direct in beeld als ze er zijn. Kopkleur = instelling `ov_accent` (zelfde als info-overzicht).

## Reisinfo als glaslaag
Een link naar `cmb-reisinfo.php` op de speler opent nu de glaslaag boven de speler; sluiten met ✕, een klik op een leeg veld of Escape. Ook als functie: `openReisinfoOverlay()`. De meldingsbolletjes zijn zwart met het getal in de opmaakkleur (`ov_accent`).

## App op het beginscherm (PWA)
Beheer, tab identiteit: "App op het beginscherm" aanvinken. Bestanden: `cmb-manifest.php`, `cmb-pwa-icon.php` (pictogram uit logo of favicon), `cmb-sw.js` (laat deze in de hoofdmap staan). Android/Chrome: venster "Installeren". iPhone: korte uitleg (Deel, Zet op beginscherm). Weg te klikken, komt na 14 dagen terug.

## Stemstudio (nieuw)
`cmb-stemstudio.php`, in het beheermenu onder "Stem". Tekst inspreken met een OpenAI-stem (gpt-4o-mini-tts), stijl in eigen woorden, tempo en effect (radio, zaal, echo, telefoon, megafoon, robot, dieper, hoger). Bewaard in `cmb-uploads/stem/` als mp3. Sleutel: invullen op de pagina, of `cmb-data/openai-key.php` (`<?php return 'sk-...';`), of omgevingsvariabele OPENAI_API_KEY.

## Stemstudio: toon per zin, pauzes, uitspraakhulp, drie stemmen
Tekst als: `[blij] Dit is Focus! [pauze 1] [fluisterend] Je maatjes in muziek.` Tonen: blij, serieus, fluisterend, enthousiast, dramatisch, lachend, verbaasd, rustig, snel, stoer (of eigen woord). `[pauze]` of `[pauze 1.5]` = stilte in seconden. Uitspraaklijst (`woord = klank`) staat in `cmb-data/stem-uitspraak.txt`. Vinkje "Drie stemmen tegelijk" maakt drie versies om te vergelijken.

## Tijd en weer op één plek
Beheer, tab identiteit, bij de plekken: vink "Tijd en weer wisselen op één plek" aan en kies het aantal seconden. Het blok neemt de plek van "Tijd en datum". Na het opslaan de speler even verversen.

## Fix: bubbeltekst met twee regels en aanhalingstekens in de middenregel
- Een regeleinde in de bubbeltekst (en emoji) liet de speler vastlopen: de instellingen worden nu veilig doorgegeven (json_encode). De bubbeltekst mag nu meerdere regels hebben.
- De middenregel, titel, subtitel en bubbeltekst worden bij het live bijwerken niet meer dubbel omgezet (&quot; verdween). De middenregel mag ook twee regels hebben.

## Bestand kiezen op Android
`cmb-assets/cmb-filepick.js` (in beheer, studio, regioagenda en afbeelding maken): op Android opent "Bestand kiezen" de bestandsbeheerder in plaats van Google Foto's. Op pc en iPhone verandert niets.

## Update 31-10 (avond)
- NIEUW cmb-sleutels.php (beheer > Hulp > Sleutels): NS, OpenAI, Claude, Gemini, OpenRouter op één plek, met Opslaan/Testen/Wissen/Verplaats. Opslaan gaat naar cmb-config.php (buiten de openbare map; back-up .bak wordt gemaakt). Alleen de laatste 4 tekens zichtbaar.
- Sites zonder deze update blijven werken: oude plekken worden nog gelezen.
- Logo-formaat ongewijzigd (48px).
- Bus/tram/metro-meldingen tonen lijn, vervoerder en plaats (bv. "Tram 13 · GVB · Amsterdam"). cmb-ov-data.php?test=1 toont bus_agencies en bus_sample.
- OV: de feed geeft geen vervoerder-id; vervoerder, soort (bus/tram/metro) en plaats worden nu uit de tekst herkend (GVB, RET, HTM, Qbuzz, RRReis, Breng, Arriva, ...).
- WhatsApp-knop heeft nu dezelfde kleuren als de andere knoppen; achter het knoppenblok een subtiele donkere achtergrond (zonder rand).
- Stemstudio: motor ElevenLabs (stemmen uit je account, expressie-schuif, toon per zin) en Jingle maken (ElevenLabs muziek, lengte en alleen-muziek-optie). Sleutel: pagina Sleutels.
- Topmenu: nieuwe smalle balk over de volle breedte met dunne lijn eronder en | tussen de items, oogje achteraan. Instellen bij beheer > Topmenu (achtergrond balk, zichtbaarheid, lijn aan/uit en kleur).
- Logo en banner groter (%) op computer/tv in liggende stand: beheer > Identiteit. Nooit hoger dan de bovenste regel zelf; mobiel ongewijzigd.
- Frame-wissel: ringplaatjes en eigen tekstframe kunnen op 2 seconden (de ring-invoer toonde eerder 3 bij een opgeslagen 2: opgelost).
- Beheer > Linkerkant: "Eén vaste achtergrond voor alle slides" (upload of link, blur, donker). Overrulet de slide-achtergronden, behalve het noodbericht. Geldt voor slideshow, tegels en tv.
- Reisinfo-pagina toont bij bus, tram en metro ook lijn, vervoerder en plaats/streek.


## CMB SITE2 — TV-video-optimalisatie (16 november 2026)
- Achtergrondvideo's worden bij wisselen en heropbouw nu expliciet gestopt en uit het geheugen vrijgegeven (in plaats van alleen het HTML-element te verwijderen).
- Een achtergrondcarrousel start geen nieuwe wissel zolang de vorige achtergrond nog laadt. Dit voorkomt opstapeling van videodecoders op langzamere TV-browsers.
- Wanneer de browserpagina niet zichtbaar is, slaat de achtergrondcarrousel een wisselbeurt over. De bestaande muziek en overige functies worden niet gewijzigd.
- Videovoorladen gebruikt metadata in plaats van automatisch het hele bestand te proberen voor te laden; de video wordt nog steeds gestart zodra deze in beeld komt.
- Alle achtergrondopties, overgangen, frames, slides, streams, regioagenda en instellingen blijven bestaan.
- Deze ronde wijzigt alleen cmb-assets/cmb-player.js en dit logboek. Geen configuratie- of datamigratie.
- Controle: JavaScript-syntax, PHP-syntax en ZIP-integriteit. Nog te testen op Zeus Browser / Android TV met de zware achtergronden.
- Let op: achtergrondvideo's van externe ingesloten platforms en de gelijktijdige videodecodering van twee zichtbare achtergronden kunnen nog steeds zwaar zijn.
