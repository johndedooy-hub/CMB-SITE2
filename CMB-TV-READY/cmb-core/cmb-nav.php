<?php
/**
 * CHAZZO MUSIC BOX (CMB) - GEDEELD MENU (één overzicht voor alle beheerpagina's)
 * Bestandsnaam: cmb-nav.php
 *
 * Gebruik: echo cmb_nav('frames', 'cmb-admin.php?logout=1');
 * Alle pagina's blijven aparte PHP-bestanden, maar tonen hetzelfde menu: een korte balk met vijf groepen;
 * de onderdelen van de groep waar je zit staan eronder. cmb-start.php toont dezelfde indeling als tegels.
 * Een nieuwe pagina toevoegen = één regel in cmb_nav_groups() hieronder.
 */

/** Groepen: sleutel => [kort label, [onderdeel-sleutel => [label, link, nieuw tabblad?, uitleg voor de tegel]]] */
function cmb_nav_groups() {
    return [
        'uiterlijk' => ['🖼️ Uiterlijk', [
            'frames'     => ['🔲 Alle frames', 'cmb-admin.php?tab=frames', false, 'Rand per frame (lijnen of plaatjes), eigen tekstframe en weergave Volledig of Compact'],
            'topmenu'    => ['🔝 Topmenu & knoppen', 'cmb-admin.php?tab=topmenu', false, 'De knoppen bovenaan en hun links'],
            'identiteit' => ['🏷️ Teksten & stijl', 'cmb-admin.php?tab=identiteit', false, 'Naam, tagline en lettertypes'],
            'media'      => ['🌄 Achtergronden', 'cmb-admin.php?tab=media', false, 'Achtergronden en de carrousels per uur'],
            'ticker'     => ['📜 Ticker', 'cmb-admin.php?tab=ticker', false, 'De lopende tekst onderin'],
            'effecten'   => ['✨ Cover effecten', 'cmb-admin.php?tab=effecten', false, 'Het effect rond de cover'],
            'site'       => ['🌐 Logo, icoon & zoekmachine', 'cmb-admin.php?tab=site', false, 'Logo, icoon en hoe de site gevonden wordt'],
            'themas'     => ['🎨 Thema\'s & planning', 'cmb-themes.php', false, 'Een complete look, ook per datum ingepland'],
        ]],
        'inhoud' => ['🎬 Inhoud', [
            'slides'     => ['🎞️ Slides', 'cmb-studio.php?tab=slides', false, 'Nood, nieuws, weer en vrije slides'],
            'clipslides' => ['🎥 Clipslides', 'cmb-studio.php?tab=slides#clipslides', false, 'Slides met een videoclip'],
            'widgets'    => ['🧩 Widgets & RSS', 'cmb-studio.php?tab=widgets', false, 'Nieuwsfeeds en eigen blokken'],
            'streams'    => ['📻 Streams', 'cmb-studio.php?tab=streams', false, 'De radiostreams en reserve-stream'],
        ]],
        'regio' => ['📅 Regioagenda', [
            'regio'      => ['📅 Regioagenda', 'cmb-regioagenda.php', false, 'Invoer, slides en mp3 van de regioagenda'],
        ]],
        'stem' => ['🎙️ Stem', [
            'stemstudio' => ['🎙️ Stemstudio', 'cmb-stemstudio.php', false, 'Tekst inspreken met een AI-stem (bumper, slogan, voicetrack), met effect en opslaan als mp3'],
        ]],
        'afbeeldingen' => ['🎨 Afbeeldingen', [
            'afbeelding' => ['🎨 Afbeelding maken', 'cmb-afbeelding.php', false, 'Tekst en logo op een achtergrond, opslaan als plaatje'],
            'extra1'     => ['🖼️ Extra 1', '../cmb-extra-1/', true, 'Map met plaatjes (nieuw tabblad)'],
            'extra2'     => ['🖼️ Extra 2', '../cmb-extra-2/', true, 'Map met plaatjes (nieuw tabblad)'],
            'extra3'     => ['🖼️ Extra 3', '../cmb-extra-3/', true, 'Map met plaatjes (nieuw tabblad)'],
        ]],
        'hulp' => ['🛠️ Hulp', [
            'claude'     => ['🤖 AI-assistent', 'cmb-claude.php', false, 'Laat de AI iets in de speler aanpassen'],
            'herstel'    => ['🔧 AI-wijzigingen herstellen', 'cmb-herstel-ai.php', false, 'Zet wijzigingen van de AI-assistent terug, bijvoorbeeld na een update'],
            'sleutels'   => ['🔐 Sleutels', 'cmb-sleutels.php', false, 'Alle API-sleutels (NS, OpenAI, Claude, Gemini, OpenRouter) op één plek, met testknop'],
            'backup'     => ['💾 Back-up', 'cmb-backup.php', false, 'Bewaren en terugzetten van alle instellingen'],
            'inlog'      => ['🔑 Inlog & gebruikers', 'cmb-wachtwoord.php', false, 'Je wachtwoord en gebruikers beheren'],
            'tvcheck'    => ['📺 TV-browser check', 'cmb-tv-check.php', false, 'Test of de tv alles aankan'],
        ]],
    ];
}

function cmb_nav_color($gk) {
    $c = ['uiterlijk'=>'#ff9f43','inhoud'=>'#54a0ff','regio'=>'#1dd1a1','stem'=>'#ff6b81','afbeeldingen'=>'#a55eea','hulp'=>'#8395a7'];
    return $c[$gk] ?? '#63f6ff';
}

function cmb_nav($active, $logoutHref = 'cmb-admin.php?logout=1') {
    $groups = cmb_nav_groups();
    $activeGroup = '';
    foreach ($groups as $gk => $g) if (isset($g[1][$active])) { $activeGroup = $gk; break; }
    if ($activeGroup === '' && $active !== 'start') $activeGroup = 'uiterlijk';
    $uid = 'n' . substr(md5(uniqid('', true)), 0, 6);

    ob_start();
    ?>
    <style>
        .cmb-nav { background:#0d0f1e; border:1px solid rgba(255,255,255,.1); border-radius:12px; padding:8px 10px; margin-bottom:16px; }
        .cmb-nav-top { display:flex; justify-content:space-between; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px; }
        .cmb-nav-brand { color:#63f6ff; font-weight:900; font-size:15px; letter-spacing:.02em; text-decoration:none; }
        .cmb-nav-actions { display:flex; gap:6px; flex-wrap:wrap; }
        .cmb-nav-actions a { padding:5px 10px; border-radius:7px; font-size:11px; font-weight:800; text-decoration:none; color:#fff; background:#222; border:1px solid #444; }
        .cmb-nav-actions a.play { background:linear-gradient(135deg,#00e676,#00b0ff); color:#070812; border:none; }
        .cmb-nav-actions a.out { background:#3a1220; border-color:#ff1744; color:#ff8aa0; }
        .cmb-nav-main { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }
        .cmb-nav-main a { display:flex; align-items:center; justify-content:center; text-align:center; min-height:46px; padding:6px 4px; border-radius:10px; font-size:13px; font-weight:900; line-height:1.2; text-decoration:none; color:#fff; background:color-mix(in srgb, var(--c,#63f6ff) 22%, #0d0f1e); border:2px solid color-mix(in srgb, var(--c,#63f6ff) 55%, transparent); cursor:pointer; }
        .cmb-nav-main a:hover { border-color:var(--c,#63f6ff); }
        .cmb-nav-main a.on { background:var(--c,#63f6ff); color:#070812; border-color:var(--c,#63f6ff); }
        .cmb-nav-sub { display:none; grid-template-columns:repeat(3,1fr); gap:6px; margin-top:8px; padding-top:8px; border-top:1px solid rgba(255,255,255,.08); }
        .cmb-nav-sub.on { display:grid; }
        .cmb-nav-sub a { display:flex; align-items:center; justify-content:center; text-align:center; min-height:42px; padding:5px 4px; border-radius:9px; font-size:12px; font-weight:800; line-height:1.25; text-decoration:none; color:#fff; background:color-mix(in srgb, var(--c,#63f6ff) 16%, #121427); border:1px solid color-mix(in srgb, var(--c,#63f6ff) 50%, transparent); }
        .cmb-nav-sub a:hover { border-color:var(--c,#63f6ff); }
        .cmb-nav-sub a.active { background:var(--c,#63f6ff); color:#070812; border-color:var(--c,#63f6ff); }
        @media (min-width: 700px) {
            .cmb-nav-main { grid-template-columns:repeat(6,1fr); }
            .cmb-nav-sub { grid-template-columns:repeat(4,1fr); }
        }
    </style>
    <nav class="cmb-nav" id="<?= $uid ?>" aria-label="Beheermenu">
        <div class="cmb-nav-top">
            <a class="cmb-nav-brand" href="cmb-start.php">🎛️ CMB Beheer</a>
            <div class="cmb-nav-actions">
                <a href="cmb-start.php">🏠 Start</a>
                <a class="play" href="index.php" target="_blank" rel="noopener">▶ Speler openen</a>
                <a class="out" href="<?= htmlspecialchars($logoutHref) ?>">🔒 Uitloggen</a>
            </div>
        </div>
        <div class="cmb-nav-main">
            <?php foreach ($groups as $gk => $g):
                $items = $g[1];
                if (count($items) === 1) { $only = reset($items); ?>
                    <a href="<?= htmlspecialchars($only[1]) ?>" style="--c:<?= cmb_nav_color($gk) ?>" class="<?= $gk === $activeGroup ? 'on' : '' ?>"><?= htmlspecialchars($g[0]) ?></a>
                <?php } else { ?>
                    <a data-g="<?= $gk ?>" style="--c:<?= cmb_nav_color($gk) ?>" class="<?= $gk === $activeGroup ? 'on' : '' ?>"><?= htmlspecialchars($g[0]) ?></a>
                <?php } ?>
            <?php endforeach; ?>
        </div>
        <?php foreach ($groups as $gk => $g): if (count($g[1]) === 1) continue; ?>
            <div class="cmb-nav-sub<?= $gk === $activeGroup ? ' on' : '' ?>" data-sub="<?= $gk ?>" style="--c:<?= cmb_nav_color($gk) ?>">
                <?php foreach ($g[1] as $key => $it): ?>
                    <a href="<?= htmlspecialchars($it[1]) ?>" class="<?= $key === $active ? 'active' : '' ?>"<?= !empty($it[2]) ? ' target="_blank" rel="noopener"' : '' ?>><?= htmlspecialchars($it[0]) ?></a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    <script>
    (function () {
        var nav = document.getElementById('<?= $uid ?>'); if (!nav) return;
        nav.querySelectorAll('.cmb-nav-main a[data-g]').forEach(function (a) {
            a.addEventListener('click', function () {
                var g = a.getAttribute('data-g');
                nav.querySelectorAll('.cmb-nav-main a').forEach(function (x) { x.classList.toggle('on', x === a); });
                nav.querySelectorAll('.cmb-nav-sub').forEach(function (s) { s.classList.toggle('on', s.getAttribute('data-sub') === g); });
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}
