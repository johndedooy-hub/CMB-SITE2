<?php
// OpenRouter: één sleutel voor veel modellen (Claude, GPT, Gemini, Llama, ook gratis modellen).
// 1. Maak een sleutel op https://openrouter.ai/keys
// 2. Hernoem dit bestand naar openrouter-key.php en vul je sleutel in.
// Model kiezen: omgevingsvariabele CMB_OPENROUTER_MODEL of 'openrouter_model' in cmb-config.php
//   (bijv. 'anthropic/claude-sonnet-4.5'; standaard 'openrouter/auto'). Kies een model dat "tools" ondersteunt.
// Alternatief (veiliger): zet de omgevingsvariabele OPENROUTER_API_KEY op de server.
return 'sk-or-...';
