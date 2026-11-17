<?php
/**
 * CMB TV-ADRES: de speler met de slideshow links, ook als de gewone speler op "Overzicht" staat.
 * Gebruik dit adres voor tv, OBS of een scherm in de studio: jouwsite.nl/cmb/tv
 * Alles werkt hetzelfde als index.php; alleen de weergave links is altijd de slideshow.
 */
define('CMB_TV', true);
define('CMB_SELF', 'tv.php');
require __DIR__ . '/index.php';
