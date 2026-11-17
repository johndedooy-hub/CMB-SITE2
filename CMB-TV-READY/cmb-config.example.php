<?php
/**
 * CHAZZO MUSIC BOX (CMB) - CONFIG BUITEN PUBLIC_HTML
 *
 * 1. Hernoem dit bestand naar cmb-config.php.
 * 2. Zet het in je THUISMAP, één niveau boven "domains" (bv. /home/u123456789/cmb-config.php).
 *    Dus NIET in public_html en niet in domains/chazzo.nl (daar staat DO_NOT_UPLOAD_HERE).
 * 3. Maak je wachtwoord-hash in het beheer: Hulp & beheer > Inlog & config, en plak hem hieronder.
 * 4. Verwijder daarna dit voorbeeld uit public_html.
 *
 * CMB zoekt dit bestand zelf vanaf zijn eigen map omhoog. Zo gebruiken chazzo.nl/cmb3 en een subdomein
 * (domains/beheer.chazzo.nl/public_html) hetzelfde bestand. Staat er geen cmb-config.php, dan blijft de oude inlog werken.
 */
return [
    // ---- Inlog voor alle CMB-beheerpagina's ----
    'admin_user'      => 'chazzo',
    'admin_pass_hash' => '', // plak hier de hash uit "Inlog & config" (begint met $2y$...)

    // ---- Sleutels (optioneel; zo staan ze niet in public_html) ----
    'anthropic_key' => '',   // Claude (betaald)
    'gemini_key'    => '',   // Gemini (gratis, via aistudio.google.com)
    // 'gemini_model' => 'gemini-flash-latest',
    'openrouter_key' => '',  // OpenRouter (openrouter.ai/keys), veel modellen met één sleutel
    // 'openrouter_model' => 'openrouter/auto',

    // ---- Data op een andere plek (alleen nodig voor beheer op een subdomein) ----
    // Standaard gebruikt elke installatie zijn eigen cmb-data / cmb-uploads / cmb-cache.
    // Voorbeeld: beheer op beheer.chazzo.nl werkt met de data van chazzo.nl/cmb3:
    'per_host' => [
        // 'beheer.chazzo.nl' => [
        //     'data_dir'    => '/home/u123456789/domains/chazzo.nl/public_html/cmb3/cmb-data',
        //     'uploads_dir' => '/home/u123456789/domains/chazzo.nl/public_html/cmb3/cmb-uploads',
        //     'cache_dir'   => '/home/u123456789/domains/chazzo.nl/public_html/cmb3/cmb-cache',
        //     'uploads_url' => 'https://chazzo.nl/cmb3/cmb-uploads/',   // voor de voorbeeldplaatjes in het beheer
        // ],
    ],
    // Hetzelfde per map (de mapnaam van de installatie), bv. een eigen inlog voor cmb2:
    'per_folder' => [
        // 'cmb2' => ['admin_user' => 'test', 'admin_pass_hash' => '...'],
    ],
];
