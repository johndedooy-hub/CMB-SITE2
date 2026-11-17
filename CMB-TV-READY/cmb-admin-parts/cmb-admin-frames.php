<?php
/**
 * CHAZZO MUSIC BOX (CMB) - FRAME-INSTELLINGEN (formulier + opslaan)
 * Bestandsnaam: cmb-admin-frames.php
 * Wordt geladen door cmb-admin.php. Elk frame (links en rechts) heeft vier stappen:
 *   1. Animatie   2. Vaste rand per zijde   3. Hoeken   4. Seizoensdecor
 * Elke stap schrijft alleen zijn eigen velden, zodat instellingen elkaar niet in de weg zitten.
 */

function cmb_frame_defaults() {
    return [
        'mode' => 'chaser', 'theme' => 'cyan', 'custom_col' => '#00ffff',
        'grad_col1' => '#63f6ff', 'grad_col2' => '#ff5bc8', 'grad_angle' => 'diag2',
        'speed' => '4s', 'dir' => 'cw', 'chaser_w' => 3,
        'top_col' => '#63f6ff', 'top_w' => 2, 'top_style' => 'solid',
        'right_col' => '#ff5bc8', 'right_w' => 2, 'right_style' => 'solid',
        'bottom_col' => '#ffd166', 'bottom_w' => 2, 'bottom_style' => 'solid',
        'left_col' => '#63f6ff', 'left_w' => 2, 'left_style' => 'solid',
        'corner_mode' => 'sync', 'corner_col' => '#63f6ff', 'corner_size' => 24, 'corner_w' => 2, 'corner_glow' => false,
        'corner_tl_on' => true, 'corner_tr_on' => true, 'corner_br_on' => true, 'corner_bl_on' => true,
        'corner_tl_r' => '', 'corner_tr_r' => '', 'corner_br_r' => '', 'corner_bl_r' => '',
        'season_mode' => 'none', 'season_pos' => 'bottom', 'season_exclusive' => false,
    ];
}

function cmb_frame_speed_seconds($speed) {
    $v = floatval($speed);
    if ($v <= 0) $v = 4;
    return max(0.3, min(120, $v));
}

function extractFrameLighting($key) {
    $d = cmb_frame_defaults();
    $modes = cmb_frame_modes();
    $themes = cmb_frame_themes();
    $styles = cmb_side_styles();
    $angles = cmb_gradient_angles();
    $seasons = cmb_season_modes();
    $pos = cmb_season_positions();

    $sec = cmb_frame_speed_seconds($_POST["{$key}_speed_s"] ?? 4);
    $out = [
        'mode' => isset($modes[$_POST["{$key}_light_mode"] ?? '']) ? $_POST["{$key}_light_mode"] : 'chaser',
        'theme' => isset($themes[$_POST["{$key}_light_theme"] ?? '']) ? $_POST["{$key}_light_theme"] : 'cyan',
        'custom_col' => cmb_clean_color($_POST["{$key}_custom_col"] ?? '', $d['custom_col']),
        'grad_col1' => cmb_clean_color($_POST["{$key}_grad_col1"] ?? '', $d['grad_col1']),
        'grad_col2' => cmb_clean_color($_POST["{$key}_grad_col2"] ?? '', $d['grad_col2']),
        'grad_angle' => isset($angles[$_POST["{$key}_grad_angle"] ?? '']) ? $_POST["{$key}_grad_angle"] : 'diag2',
        'speed' => rtrim(rtrim(number_format($sec, 1, '.', ''), '0'), '.') . 's',
        'dir' => (($_POST["{$key}_light_dir"] ?? 'cw') === 'ccw') ? 'ccw' : 'cw',
        'chaser_w' => max(1, min(12, intval($_POST["{$key}_chaser_w"] ?? 3))),
        'corner_mode' => (($_POST["{$key}_corner_mode"] ?? 'sync') === 'custom') ? 'custom' : 'sync',
        'corner_col' => cmb_clean_color($_POST["{$key}_corner_col"] ?? '', $d['corner_col']),
        'corner_size' => max(0, min(60, intval($_POST["{$key}_corner_size"] ?? 24))),
        'corner_w' => max(0, min(10, intval($_POST["{$key}_corner_w"] ?? 2))),
        'corner_glow' => !empty($_POST["{$key}_corner_glow"]),
        'season_mode' => isset($seasons[$_POST["{$key}_season_mode"] ?? '']) ? $_POST["{$key}_season_mode"] : 'none',
        'season_pos' => isset($pos[$_POST["{$key}_season_pos"] ?? '']) ? $_POST["{$key}_season_pos"] : 'bottom',
        'season_exclusive' => !empty($_POST["{$key}_season_exclusive"]),
    ];
    foreach (['top', 'right', 'bottom', 'left'] as $side) {
        $out["{$side}_col"] = cmb_clean_color($_POST["{$key}_side_{$side}_c"] ?? '', $d["{$side}_col"]);
        $out["{$side}_w"] = max(0, min(20, intval($_POST["{$key}_side_{$side}_w"] ?? 2)));
        $out["{$side}_style"] = isset($styles[$_POST["{$key}_side_{$side}_s"] ?? '']) ? $_POST["{$key}_side_{$side}_s"] : 'solid';
    }
    foreach (['tl', 'tr', 'br', 'bl'] as $c) {
        $out["corner_{$c}_on"] = !empty($_POST["{$key}_corner_{$c}_on"]);
        $r = trim((string)($_POST["{$key}_corner_{$c}_r"] ?? ''));
        $out["corner_{$c}_r"] = ($r === '') ? '' : max(0, min(60, intval($r)));
    }
    return $out;
}

function cmb_fr_opts($list, $current) {
    $h = '';
    foreach ($list as $k => $v) {
        $label = is_array($v) ? $v[0] : $v;
        $h .= '<option value="' . htmlspecialchars($k) . '"' . ($k === $current ? ' selected' : '') . '>' . htmlspecialchars($label) . '</option>';
    }
    return $h;
}

function renderFrameModeTiles($key, $currentMode) {
    $out = '<div class="cmb-frame-tile-grid" data-key="' . htmlspecialchars($key) . '">';
    foreach (cmb_frame_modes() as $mode => $t) {
        $sel = ($currentMode === $mode) ? ' is-selected' : '';
        $out .= '<div class="cmb-frame-tile-wrap"><button type="button" class="cmb-frame-tile' . $sel . '" data-mode="' . $mode . '" onclick="cmbPickFrameMode(\'' . htmlspecialchars($key, ENT_QUOTES) . '\', \'' . $mode . '\')" title="' . htmlspecialchars($t[1]) . '">' . $t[2] . '</button>';
        $out .= '<div class="cmb-frame-tile-label">' . htmlspecialchars($t[1]) . '</div></div>';
    }
    return $out . '</div>';
}

function renderFrameLightingSuite($key, $label, $current = []) {
    $fl = array_merge(cmb_frame_defaults(), (array)$current);
    $k = htmlspecialchars($key);
    $speedS = cmb_frame_speed_seconds($fl['speed']);
    $uniformR = max(0, min(60, intval($fl['corner_size'])));

    ob_start();
    ?>
    <div class="lighting-box cmb-frame-panel" id="box_light_<?= $k ?>" data-key="<?= $k ?>">
        <div class="cmb-fp-head">
            <strong><?= htmlspecialchars($label) ?></strong>
            <div class="cmb-fp-tools">
                <button type="button" class="cmb-mini" onclick="cmbCopyFrame('<?= $k ?>')">📋 Kopieer naar het andere frame</button>
            </div>
        </div>

        <?php
        $rk = substr((string)$key, 3);
        $ringMode = in_array($fl['ring_mode'] ?? 'lines', ['image', 'carousel'], true) ? $fl['ring_mode'] : 'lines';
        $ringSel = array_map('strval', (array)($fl['ring_imgs'] ?? []));
        $ringFiles = function_exists('cmb_ring_list') ? cmb_ring_list() : [];
        ?>
        <div class="cmb-ring-choose" style="margin:8px 0 12px; padding:10px 12px; border:1px solid rgba(99,246,255,.3); border-radius:10px; background:rgba(5,6,14,.6);">
            <div class="form-row">
                <div class="field" style="flex:2;">
                    <label>Animatie van de rand</label>
                    <select id="<?= $k ?>_ring_mode" name="ring_<?= $rk ?>_mode" onchange="cmbRingToggle('<?= $k ?>')">
                        <option value="lines" <?= $ringMode === 'lines' ? 'selected' : '' ?>>Zwaar: bewegende lijnen (zoals altijd)</option>
                        <option value="image" <?= $ringMode === 'image' ? 'selected' : '' ?>>Licht: één plaatje als rand</option>
                        <option value="carousel" <?= $ringMode === 'carousel' ? 'selected' : '' ?>>Licht: plaatjes die zacht wisselen</option>
                    </select>
                </div>
            </div>
            <div class="cmb-ring-light" id="<?= $k ?>_ring_light">
                <div class="form-row">
                    <div class="field" style="max-width:130px;"><label>Dikte</label>
                        <select id="<?= $k ?>_ring_px" name="ring_<?= $rk ?>_px"><?php foreach ([1, 2, 3] as $rpx): ?><option value="<?= $rpx ?>" <?= intval($fl['ring_px'] ?? 2) === $rpx ? 'selected' : '' ?>><?= $rpx ?> px</option><?php endforeach; ?></select>
                    </div>
                    <div class="field" style="max-width:170px;"><label>Seconden per plaatje</label><input type="number" id="<?= $k ?>_ring_sec" name="ring_<?= $rk ?>_sec" min="2" max="600" value="<?= max(2, min(600, intval($fl['ring_sec'] ?? 12))) ?>" style="text-align:center;"></div>
                    <div class="field"><label>Eigen plaatje toevoegen (jpg, png, webp)</label><input type="file" name="upload_ring_<?= $rk ?>" accept="image/jpeg,image/png,image/webp"></div>
                </div>
                <label style="font-size:11px; color:#a8aac8; text-transform:uppercase; font-weight:700;">Kies plaatje(s): bij één plaatje telt het eerste, bij wisselen maximaal 6. Alleen het randje (1 tot 3 px) is te zien.</label>
                <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:6px;">
                    <?php foreach ($ringFiles as $rfi => $rf): ?>
                    <label style="display:block; width:84px; font-size:10px; text-align:center; cursor:pointer;">
                        <img src="cmb-frames/<?= htmlspecialchars($rf) ?>" alt="" style="width:84px; height:42px; object-fit:cover; border-radius:6px; border:1px solid rgba(255,255,255,.2); display:block;">
                        <input type="checkbox" id="<?= $k ?>_ring_i_<?= $rfi ?>" name="ring_<?= $rk ?>_i_<?= $rfi ?>" value="<?= htmlspecialchars($rf) ?>" <?= in_array($rf, $ringSel, true) ? 'checked' : '' ?>> <?= htmlspecialchars(preg_replace('/\.[a-z]+$/i', '', $rf)) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="cmb-fp-grid">
            <div class="cmb-fp-steps">
              <div class="cmb-ring-heavy" id="<?= $k ?>_ring_heavy">

                <!-- STAP 1: ANIMATIE -->
                <details class="cmb-step" open>
                    <summary><span class="cmb-step-no">1</span> Animatie op de rand</summary>
                    <div class="cmb-step-body">
                        <div class="form-row">
                            <div class="field" style="flex:2;">
                                <label>Welk effect?</label>
                                <select id="<?= $k ?>_light_mode" name="<?= $k ?>_light_mode"><?= cmb_fr_opts(cmb_frame_modes(), $fl['mode']) ?></select>
                            </div>
                            <div class="field" style="max-width:150px;">
                                <label>Snelheid (seconden)</label>
                                <input type="number" id="<?= $k ?>_speed_s" name="<?= $k ?>_speed_s" value="<?= htmlspecialchars((string)$speedS) ?>" min="0.3" max="120" step="0.1" title="Zoveel seconden duurt één rondje of golf. Lager = sneller.">
                                <small class="hint">Lager = sneller</small>
                            </div>
                            <div class="field" style="max-width:180px;">
                                <label>Richting</label>
                                <select id="<?= $k ?>_light_dir" name="<?= $k ?>_light_dir">
                                    <option value="cw" <?= $fl['dir'] === 'cw' ? 'selected' : '' ?>>Met de klok mee / omlaag</option>
                                    <option value="ccw" <?= $fl['dir'] === 'ccw' ? 'selected' : '' ?>>Tegen de klok in / omhoog</option>
                                </select>
                            </div>
                            <div class="field" style="max-width:130px;">
                                <label>Dikte effect (px)</label>
                                <input type="number" id="<?= $k ?>_chaser_w" name="<?= $k ?>_chaser_w" value="<?= intval($fl['chaser_w']) ?>" min="1" max="12">
                            </div>
                        </div>
                        <details class="cmb-tiles-fold"><summary style="cursor:pointer; font-size:12px; color:#a8aac8; margin-top:8px;">👁️ Visuele rand-voorbeelden</summary>
                        <?= renderFrameModeTiles($key, $fl['mode']) ?>
                        </details>

                        <div class="cmb-subbox">
                            <div class="cmb-subbox-title">🎨 Kleur van het effect</div>
                            <div class="form-row">
                                <div class="field" style="flex:2;">
                                    <label>Kleurthema</label>
                                    <select id="<?= $k ?>_light_theme" name="<?= $k ?>_light_theme"><?= cmb_fr_opts(cmb_frame_themes(), $fl['theme']) ?></select>
                                </div>
                                <div class="field" style="max-width:100px;">
                                    <label>Eigen kleur</label>
                                    <input type="color" id="<?= $k ?>_custom_col" name="<?= $k ?>_custom_col" value="<?= htmlspecialchars($fl['custom_col']) ?>">
                                </div>
                            </div>
                            <div class="form-row cmb-gradient-row">
                                <div class="field" style="max-width:100px;">
                                    <label>Verloop van</label>
                                    <input type="color" id="<?= $k ?>_grad_col1" name="<?= $k ?>_grad_col1" value="<?= htmlspecialchars($fl['grad_col1']) ?>">
                                </div>
                                <div class="field" style="max-width:100px;">
                                    <label>naar</label>
                                    <input type="color" id="<?= $k ?>_grad_col2" name="<?= $k ?>_grad_col2" value="<?= htmlspecialchars($fl['grad_col2']) ?>">
                                </div>
                                <div class="field" style="flex:1;">
                                    <label>Richting verloop</label>
                                    <select id="<?= $k ?>_grad_angle" name="<?= $k ?>_grad_angle"><?= cmb_fr_opts(cmb_gradient_angles(), $fl['grad_angle']) ?></select>
                                </div>
                                <div class="field" style="flex:1;">
                                    <label>Voorbeeld</label>
                                    <div class="cmb-grad-sample" id="<?= $k ?>_grad_sample"></div>
                                </div>
                            </div>
                            <small class="hint">Het kleurverloop wordt gebruikt zodra je bij Kleurthema "Eigen kleurverloop" kiest.</small>
                        </div>
                    </div>
                </details>

                <!-- STAP 2: VASTE RAND PER ZIJDE -->
                <details class="cmb-step" open>
                    <summary><span class="cmb-step-no">2</span> Vaste rand per zijde</summary>
                    <div class="cmb-step-body">
                        <table class="cmb-side-table">
                            <thead><tr><th>Zijde</th><th>Kleur</th><th>Dikte (px)</th><th>Lijnstijl</th></tr></thead>
                            <tbody>
                            <?php foreach (['top' => 'Boven', 'right' => 'Rechts', 'bottom' => 'Onder', 'left' => 'Links'] as $s => $sl): ?>
                                <tr>
                                    <td><?= $sl ?></td>
                                    <td><input type="color" id="<?= $k ?>_side_<?= $s ?>_c" name="<?= $k ?>_side_<?= $s ?>_c" value="<?= htmlspecialchars($fl["{$s}_col"]) ?>"></td>
                                    <td><input type="number" id="<?= $k ?>_side_<?= $s ?>_w" name="<?= $k ?>_side_<?= $s ?>_w" value="<?= intval($fl["{$s}_w"]) ?>" min="0" max="20" title="0 = geen rand aan deze zijde"></td>
                                    <td><select id="<?= $k ?>_side_<?= $s ?>_s" name="<?= $k ?>_side_<?= $s ?>_s"><?= cmb_fr_opts(cmb_side_styles(), $fl["{$s}_style"]) ?></select></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="cmb-quick-row">
                            <span class="hint">Snel invullen:</span>
                            <button type="button" class="cmb-mini" onclick="cmbSidesSame('<?= $k ?>')">Alle zijden gelijk aan Boven</button>
                            <button type="button" class="cmb-mini" onclick="cmbSidesGradient('<?= $k ?>')">Kleurverloop over de zijden (van → naar uit stap 1)</button>
                            <button type="button" class="cmb-mini" onclick="cmbSidesOff('<?= $k ?>')">Alle randen uit</button>
                        </div>
                        <small class="hint">0 px = geen vaste rand aan die zijde. Een open zijde is handig bij Waterval of Regengordijn.</small>
                    </div>
                </details>

                <!-- STAP 3: HOEKEN -->
                <details class="cmb-step" open>
                    <summary><span class="cmb-step-no">3</span> Hoeken</summary>
                    <div class="cmb-step-body">
                        <div class="form-row">
                            <div class="field" style="max-width:170px;">
                                <label>Ronding alle hoeken (px)</label>
                                <input type="number" id="<?= $k ?>_corner_size" name="<?= $k ?>_corner_size" value="<?= $uniformR ?>" min="0" max="60">
                            </div>
                            <div class="field" style="max-width:170px;">
                                <label>Dikte hoeklijn (px)</label>
                                <input type="number" id="<?= $k ?>_corner_w" name="<?= $k ?>_corner_w" value="<?= intval($fl['corner_w']) ?>" min="0" max="10" title="Dikte van de accentlijn om de hoeken (bij 'Eigen hoekkleur')">
                            </div>
                            <div class="field" style="flex:1.5;">
                                <label>Hoekkleur</label>
                                <select id="<?= $k ?>_corner_mode" name="<?= $k ?>_corner_mode">
                                    <option value="sync" <?= $fl['corner_mode'] === 'sync' ? 'selected' : '' ?>>Volgt de randkleuren (standaard)</option>
                                    <option value="custom" <?= $fl['corner_mode'] === 'custom' ? 'selected' : '' ?>>Eigen hoekkleur met accentlijn</option>
                                </select>
                            </div>
                            <div class="field" style="max-width:100px;">
                                <label>Hoekkleur</label>
                                <input type="color" id="<?= $k ?>_corner_col" name="<?= $k ?>_corner_col" value="<?= htmlspecialchars($fl['corner_col']) ?>">
                            </div>
                            <div class="field" style="max-width:130px; justify-content:flex-end; padding-bottom:8px;">
                                <label style="cursor:pointer;"><input type="checkbox" id="<?= $k ?>_corner_glow" name="<?= $k ?>_corner_glow" value="1" <?= !empty($fl['corner_glow']) ? 'checked' : '' ?>> ✨ Neon gloed</label>
                            </div>
                        </div>
                        <table class="cmb-side-table">
                            <thead><tr><th>Hoek</th><th>Aan</th><th>Eigen ronding (px)</th></tr></thead>
                            <tbody>
                            <?php foreach (['tl' => 'Linksboven', 'tr' => 'Rechtsboven', 'br' => 'Rechtsonder', 'bl' => 'Linksonder'] as $c => $cl):
                                $on = !isset($fl["corner_{$c}_on"]) || !empty($fl["corner_{$c}_on"]); ?>
                                <tr>
                                    <td><?= $cl ?></td>
                                    <td><input type="checkbox" id="<?= $k ?>_corner_<?= $c ?>_on" name="<?= $k ?>_corner_<?= $c ?>_on" value="1" <?= $on ? 'checked' : '' ?>></td>
                                    <td><input type="number" id="<?= $k ?>_corner_<?= $c ?>_r" name="<?= $k ?>_corner_<?= $c ?>_r" value="<?= htmlspecialchars((string)$fl["corner_{$c}_r"]) ?>" min="0" max="60" placeholder="= alle hoeken"></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <small class="hint">Hoek uit = scherpe hoek (0 px). Laat "Eigen ronding" leeg om de algemene ronding te volgen.</small>
                    </div>
                </details>

              </div><!-- /cmb-ring-heavy -->

                <!-- STAP 4: SEIZOENSDECOR -->
                <details class="cmb-step">
                    <summary><span class="cmb-step-no">4</span> Seizoensdecor (apart van de animatie)</summary>
                    <div class="cmb-step-body">
                        <div class="form-row">
                            <div class="field" style="flex:2;">
                                <label>Decor</label>
                                <select id="<?= $k ?>_season_mode" name="<?= $k ?>_season_mode"><?= cmb_fr_opts(cmb_season_modes(), $fl['season_mode']) ?></select>
                            </div>
                            <div class="field" style="flex:1;">
                                <label>Waar op het frame?</label>
                                <select id="<?= $k ?>_season_pos" name="<?= $k ?>_season_pos"><?= cmb_fr_opts(cmb_season_positions(), $fl['season_pos']) ?></select>
                            </div>
                        </div>
                        <label class="cmb-check"><input type="checkbox" id="<?= $k ?>_season_exclusive" name="<?= $k ?>_season_exclusive" value="1" <?= !empty($fl['season_exclusive']) ? 'checked' : '' ?>> Zet de animatie van stap 1 uit zolang er seizoensdecor aan staat</label>
                        <small class="hint">Seizoensdecor ligt als losse laag óver het frame en doet niets met je andere instellingen. Bij "Automatisch" kiest de speler zelf het seizoen op basis van de datum.</small>
                    </div>
                </details>
            </div>

            <!-- LIVE VOORBEELD -->
            <aside class="cmb-fp-preview">
                <div class="cmb-fp-preview-title">👁️ Voorbeeld</div>
                <div class="cmb-fp-box" id="<?= $k ?>_pv"><div class="cmb-fp-anim" id="<?= $k ?>_pv_anim"></div><span>Frame</span></div>
                <small class="hint">Het voorbeeld toont randen, hoeken en het gekozen effect. Kleuren van het effect kunnen in de speler iets afwijken.</small>
            </aside>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
