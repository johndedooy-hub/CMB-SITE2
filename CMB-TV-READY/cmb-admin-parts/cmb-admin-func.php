<?php
/** CMB Beheer: hulpfuncties (upload, randen, achtergronden, stijlen). Geladen door cmb-admin.php. */
if (!defined('CMB_PART')) { http_response_code(403); exit; }
function handleUpload($fileInputName, $uploadDir) {
    // Alleen afbeeldingen en video's; scripts worden geweigerd (zie cmb-lib.php).
    return cmb_upload($fileInputName, $uploadDir);
}

function renderBorderSuite($prefix, $label, $current = []) {
    $b = array_merge(['enabled' => true, 'color' => '#63f6ff', 'width' => 1], $current);
    $chk = !empty($b['enabled']) ? 'checked' : '';
    $html = '<div class="border-box">';
    $html .= '<div style="display:flex; justify-content:space-between; align-items:center;">';
    $html .= '<label style="color:#63f6ff; font-weight:bold;"><input type="checkbox" id="' . $prefix . '_b_on" name="' . $prefix . '_b_on" value="1" ' . $chk . '> Rand voor ' . htmlspecialchars($label) . '</label>';
    $html .= '</div>';
    $html .= '<div class="form-row" style="margin-top:6px;">';
    $html .= '<div class="field" style="max-width:110px;"><label>Randkleur</label><input type="color" id="' . $prefix . '_b_col" name="' . $prefix . '_b_col" value="' . htmlspecialchars($b['color']) . '"></div>';
    $html .= '<div class="field" style="max-width:110px;"><label>Dikte (px)</label><input type="number" id="' . $prefix . '_b_w" name="' . $prefix . '_b_w" value="' . intval($b['width']) . '" min="1" max="6"></div>';
    $html .= '</div></div>';
    return $html;
}

function extractBorderSuite($prefix) {
    return [
        'enabled' => !empty($_POST[$prefix . '_b_on']),
        'color' => $_POST[$prefix . '_b_col'] ?? '#63f6ff',
        'width' => max(1, min(6, intval($_POST[$prefix . '_b_w'] ?? 1)))
    ];
}

function renderBgStudioSuite($key, $label, $current = []) {
    $c = array_merge([
        'mode' => 'single', 'sec' => 8, 'blur' => 0, 'darken' => 0,
        'single_url' => '', 'items' => []
    ], $current);
    
    $html = '<div class="bg-studio-box" id="box_' . $key . '">';
    $html .= '<strong style="color:#ffd166; font-size:15px; display:block; margin-bottom:8px;">' . htmlspecialchars($label) . '</strong>';
    $html .= '<div class="form-row">';
    $html .= '<div class="field"><label>Achtergrond Modus</label><select id="' . $key . '_mode" name="' . $key . '_mode">';
    $html .= '<option value="single" ' . ($c['mode']==='single'?'selected':'') . '>Enkele Vaste Achtergrond (Foto of Videoloop)</option>';
    $html .= '<option value="carousel" ' . ($c['mode']==='carousel'?'selected':'') . '>Carrousel Slideshow (Tot 24 items)</option>';
    $html .= '</select></div>';
    $secTot = max(2, min(3600, intval($c['sec'])));
    $html .= '<div class="field" style="max-width:80px;"><label>Wisseltijd min (0-60)</label><input type="number" id="' . $key . '_min" name="' . $key . '_min" value="' . intdiv($secTot, 60) . '" min="0" max="60" style="text-align:center;"></div>';
    $html .= '<div class="field" style="max-width:80px;"><label>sec (0-59)</label><input type="number" id="' . $key . '_sec" name="' . $key . '_sec" value="' . ($secTot % 60) . '" min="0" max="59" style="text-align:center;"></div>';
    $html .= '<div class="field" style="max-width:70px;"><label>Blur (px)</label><input type="number" id="' . $key . '_blur" name="' . $key . '_blur" min="0" max="25" value="' . intval($c['blur'] ?? 0) . '" style="text-align:center;"></div>';
    $html .= '<div class="field" style="max-width:70px;"><label>Donker (%)</label><input type="number" id="' . $key . '_darken" name="' . $key . '_darken" min="0" max="95" value="' . intval($c['darken'] ?? 0) . '" style="text-align:center;"></div>';
    $html .= '</div>';

    $singleUrl = htmlspecialchars($c['single_url']);
    $html .= '<div class="sub-bg-section">';
    $html .= '<div style="font-size:12px; font-weight:bold; color:#63f6ff; margin-bottom:8px;">Vaste Enkele Media</div>';
    $html .= '<div style="display:flex; gap:12px; align-items:center;">';
    $html .= '<div class="field" style="flex:2;"><input type="text" id="' . $key . '_single_url" name="' . $key . '_single_url" value="' . $singleUrl . '" placeholder="URL naar MP4/afbeelding..."></div>';
    $html .= '<div class="field" style="max-width:200px;"><input type="file" name="upload_' . $key . '_single"></div>';
    $html .= '</div></div>';

    $html .= '<div class="sub-bg-section" style="margin-top:10px;">';
    $html .= '<div style="font-size:12px; font-weight:bold; color:#d500f9; margin-bottom:8px;">Carrousel Vakken (Max 24 slides)</div>';
    $html .= '<div class="carousel-grid">';
    for ($i = 1; $i <= 24; $i++) {
        $curItem = htmlspecialchars($c['items'][$i] ?? '');
        $html .= '<div class="carousel-cell" id="cell_' . $key . '_' . $i . '">';
        $html .= '<span class="cell-num">#' . $i . '</span>';
        $html .= '<input type="text" class="cell-url-input" id="' . $key . '_item_' . $i . '" name="' . $key . '_item_' . $i . '" value="' . $curItem . '" placeholder="URL">';
        $html .= '<input type="file" class="cell-file-input" name="upload_' . $key . '_' . $i . '">';
        $html .= '</div>';
    }
    $html .= '</div></div></div>';
    return $html;
}

function extractBgStudio($key, $uploadDir) {
    $items = [];
    for ($i = 1; $i <= 24; $i++) {
        $uFile = handleUpload("upload_{$key}_{$i}", $uploadDir);
        $val = $uFile ?: trim($_POST["{$key}_item_{$i}"] ?? '');
        if ($val !== '') $items[$i] = $val;
    }
    $uSingle = handleUpload("upload_{$key}_single", $uploadDir);
    return [
        'mode' => $_POST["{$key}_mode"] ?? 'single',
        'sec' => max(2, min(3600, max(0, min(60, intval($_POST["{$key}_min"] ?? 0))) * 60 + max(0, min(59, intval($_POST["{$key}_sec"] ?? 8))))),
        'blur' => max(0, min(25, intval($_POST["{$key}_blur"] ?? 0))),
        'darken' => max(0, min(95, intval($_POST["{$key}_darken"] ?? 0))),
        'single_url' => $uSingle ?: trim($_POST["{$key}_single_url"] ?? ''),
        'items' => $items
    ];
}

// Een tekststijl wordt alleen bewaard als hij al bestond of echt is aangepast.
// Anders zou het formulier (Montserrat 15px) de eigen standaardopmaak van de speler overschrijven.
function cmb_keep_style(&$settings, $key, $prefix) {
    if (!empty($settings[$key]) || !empty($_POST[$prefix . '_touched'])) {
        $settings[$key] = extractStyleSuite($prefix);
    }
}
