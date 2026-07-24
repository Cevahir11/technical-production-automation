<?php

require_once __DIR__ . "/helpers.php";

// ---------------------------------------------------------
// SVG YARDIMCI FONKSIYONLAR
// ---------------------------------------------------------

function svg_escape($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function svg_text($x, $y, $text, $size = 14, $weight = "normal", $color = "#111111", $anchor = "start")
{
    $safe = svg_escape($text);

    return '<text x="' . $x . '" y="' . $y . '" '
        . 'font-family="DejaVu Sans, Arial, sans-serif" '
        . 'font-size="' . $size . '" '
        . 'font-weight="' . $weight . '" '
        . 'fill="' . $color . '" '
        . 'text-anchor="' . $anchor . '" '
        . 'letter-spacing="0.2">' . $safe . '</text>';
}

function svg_rect($x, $y, $w, $h, $fill = "#ffffff", $stroke = "#111111", $stroke_width = 2, $opacity = 1)
{
    return '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" '
        . 'fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="' . $stroke_width . '" opacity="' . $opacity . '" />';
}

function svg_line($x1, $y1, $x2, $y2, $color = "#111111", $width = 2, $opacity = 1)
{
    return '<line x1="' . $x1 . '" y1="' . $y1 . '" x2="' . $x2 . '" y2="' . $y2 . '" '
        . 'stroke="' . $color . '" stroke-width="' . $width . '" opacity="' . $opacity . '" />';
}

function svg_polygon($points, $fill = "#ffffff", $stroke = "#111111", $stroke_width = 2, $opacity = 1)
{
    $parts = [];

    foreach ($points as $p) {
        $parts[] = $p[0] . "," . $p[1];
    }

    return '<polygon points="' . implode(" ", $parts) . '" '
        . 'fill="' . $fill . '" stroke="' . $stroke . '" stroke-width="' . $stroke_width . '" opacity="' . $opacity . '" />';
}

function svg_image_stretch_php($path, $x, $y, $w, $h)
{
    if (!file_exists($path)) {
        return svg_rect($x, $y, $w, $h, "#eeeeee", "#999999", 1) .
            svg_text($x + ($w / 2), $y + ($h / 2), "BANNER YOK", 9, "bold", "#cc0000", "middle");
    }

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = $ext === "jpg" || $ext === "jpeg" ? "image/jpeg" : "image/png";
    $uri = "data:" . $mime . ";base64," . base64_encode(file_get_contents($path));

    return '<image href="' . $uri . '" x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" preserveAspectRatio="none"/>';
}

function draw_dimension_horizontal(&$svg, $x, $y, $w, $label, $size = 10)
{
    $svg[] = svg_line($x, $y, $x + $w, $y, "#111111", 1);
    $svg[] = svg_line($x, $y - 5, $x, $y + 5, "#111111", 1);
    $svg[] = svg_line($x + $w, $y - 5, $x + $w, $y + 5, "#111111", 1);
    $svg[] = svg_text($x + ($w / 2), $y - 7, $label, $size, "bold", "#111111", "middle");
}

function draw_dimension_vertical(&$svg, $x, $y, $h, $label, $size = 8)
{
    $svg[] = svg_line($x, $y, $x, $y + $h, "#777777", 1);
    $svg[] = svg_line($x - 5, $y, $x + 5, $y, "#777777", 1);
    $svg[] = svg_line($x - 5, $y + $h, $x + 5, $y + $h, "#777777", 1);

    $safe = svg_escape($label);
    $text_x = $x - 5;
    $text_y = $y + ($h / 2);

    $svg[] = '<text x="' . $text_x . '" y="' . $text_y . '" '
        . 'font-family="DejaVu Sans, Arial, sans-serif" '
        . 'font-size="' . $size . '" font-weight="bold" fill="#111111" text-anchor="middle" '
        . 'transform="rotate(-90 ' . $text_x . ' ' . $text_y . ')">' . $safe . '</text>';
}

function clamp_value_svg($value, $min_value, $max_value)
{
    return max($min_value, min($value, $max_value));
}

function fmt_m($value)
{
    return format_meter($value);
}

function get_num($item, $key, $default = 0)
{
    if (!isset($item[$key])) {
        return $default;
    }

    $v = $item[$key];

    if (is_numeric($v)) {
        return floatval($v);
    }

    if (function_exists('parse_measure_cell')) {
        return parse_measure_cell($v);
    }

    $v = str_replace(["₺", "TL", " "], "", (string)$v);
    $v = str_replace(".", "", $v);
    $v = str_replace(",", ".", $v);

    return is_numeric($v) ? floatval($v) : $default;
}

function get_qty($item)
{
    foreach (["quantity", "qty", "adet", "count"] as $key) {
        if (isset($item[$key]) && $item[$key] !== "" && $item[$key] !== null) {
            $raw = str_replace(",", ".", (string)$item[$key]);
            return max(1, intval(floatval($raw)));
        }
    }

    return 1;
}

function wrap_text_svg($text, $max_chars = 28, $max_lines = 3)
{
    $words = preg_split('/\s+/u', trim((string)$text));
    $lines = [];
    $current = "";

    foreach ($words as $word) {
        $test = $current === "" ? $word : $current . " " . $word;

        if (mb_strlen($test, "UTF-8") <= $max_chars) {
            $current = $test;
        } else {
            if ($current !== "") {
                $lines[] = $current;
            }
            $current = $word;
        }

        if (count($lines) >= $max_lines) {
            break;
        }
    }

    if ($current !== "" && count($lines) < $max_lines) {
        $lines[] = $current;
    }

    return $lines;
}

// ---------------------------------------------------------
// PROJE MODU / URUN TIPLERI
// ---------------------------------------------------------

function svg_norm($text)
{
    return strtoupper(tr_to_ascii((string)$text));
}

function get_real_panels($project_data)
{
    $result = [];

    foreach ($project_data["panels"] ?? [] as $p) {
        if (!in_array($p["category"] ?? "", ["ignore", "option"], true)) {
            $result[] = $p;
        }
    }

    return $result;
}

function project_mode_php($project_data)
{
    $has_roof = !empty($project_data["modules"] ?? []);
    $has_facade = !empty($project_data["sliding_glass"] ?? []) || !empty(get_real_panels($project_data));

    if ($has_roof && $has_facade) return "roof+facade";
    if ($has_roof) return "roof_only";
    if ($has_facade) return "facade_only";

    return "empty";
}

function get_roof_type_php($module)
{
    $text = svg_norm(implode(" ", [
        $module["roof_type"] ?? "",
        $module["system"] ?? "",
        $module["raw_product"] ?? "",
        $module["description"] ?? "",
        $module["area"] ?? "",
        $module["drawing_type"] ?? "",
    ]));

    if (strpos($text, "HAREKETLI CAM") !== false && strpos($text, "TAVAN") !== false) return "moving_glass_roof";
    if (strpos($text, "SABIT CAM") !== false && strpos($text, "TAVAN") !== false) return "fixed_glass_roof";
    if (strpos($text, "SABIT TAVAN") !== false) return "fixed_roof";
    if (strpos($text, "PENTE") !== false) return "pergola";
    if (strpos($text, "PERGOLA") !== false && strpos($text, "BIOKLIMATIK") === false && strpos($text, "BIYOKLIMATIK") === false) return "pergola";
    if (strpos($text, "OTOMATIK TAVAN") !== false) return "pergola";
    if (strpos($text, "BIOKLIMATIK") !== false || strpos($text, "BIYOKLIMATIK") !== false) return "bioklimatik";

    return "generic_roof";
}

function module_has_glass_php($module, $sliding_glass)
{
    if (!$sliding_glass) return false;

    $area = svg_norm($module["area"] ?? "");

    foreach ($sliding_glass as $item) {
        $glass_area = svg_norm($item["area"] ?? "");
        if ($glass_area && ($glass_area === $area || strpos($area, $glass_area) !== false || strpos($glass_area, $area) !== false)) {
            return true;
        }
    }

    return false;
}

function calculate_glass_leaf_groups_php($width_m)
{
    $width_m = floatval($width_m);

    if ($width_m <= 0) {
        return [3];
    }

    // 0 - 3 m arası
    if ($width_m < 3) {
        return [3];
    }

    // 3 - 4 m arası ve 4.00 m dahil
    if ($width_m <= 4) {
        return [4];
    }

    // 4.00 m üstü - 7 m arası
    if ($width_m < 7) {
        return [3, 3];
    }

    // 7 - 8 m arası
    if ($width_m < 8) {
        return [4, 4];
    }

    return [4, 4];
}  

function detect_section_type_php($text)
{
    $ascii = svg_norm($text);
    $raw = strtoupper((string)$text);

    if (strpos($ascii, "KATLANIR") !== false) return ["KATLANIR CAM KESİTİ", "folding"];
    if (strpos($ascii, "GIYOTIN") !== false) return ["GİYOTİN CAM KESİTİ", "guillotine"];
    if (strpos($ascii, "SURME") !== false) return ["SÜRME CAM KESİTİ", "sliding"];
    if (strpos($ascii, "SABIT CAM") !== false || strpos($ascii, "SABIT DOGRAMA") !== false) return ["SABİT CAM DOĞRAMA KESİTİ", "fixed_glass"];
    if (strpos($ascii, "ZIP") !== false || strpos($ascii, "PERDE") !== false) return ["ZİP PERDE KESİTİ", "zip"];
    if (strpos($ascii, "KOMPOZIT") !== false) return ["KOMPOZİT CEPHE KAPAMA KESİTİ", "composite"];
    if (strpos($ascii, "4 MEVSIM") !== false || strpos($ascii, "KONFOR PAKETI") !== false) return ["4 MEVSİM KONFOR PANEL KESİTİ", "comfort_panel"];
    if ((strpos($ascii, "SANDVIC") !== false || strpos($raw, "SANDVİÇ") !== false) && strpos($ascii, "CEPHE KAPAMA") !== false) return ["SANDVİÇ PANEL KAPAMA KESİTİ", "sandwich"];
    if (strpos($ascii, "PANEL") !== false) return ["PANEL KAPAMA KESİTİ", "sandwich"];

    return ["AÇIK CEPHE KESİTİ", "open"];
}

// ---------------------------------------------------------
// CIZIM PARCALARI
// ---------------------------------------------------------

function draw_bioklimatik_lamels_plan(&$svg, $x, $y, $w, $h)
{
    $margin = 8;
    $inner_x = $x + $margin;
    $inner_y = $y + $margin;
    $inner_w = max($w - ($margin * 2), 10);
    $inner_h = max($h - ($margin * 2), 10);

    $blade_count = max(6, min(14, intval($inner_w / 22)));
    $gap = 4;
    $blade_w = ($inner_w - ($gap * ($blade_count - 1))) / $blade_count;

    for ($i = 0; $i < $blade_count; $i++) {
        $bx = $inner_x + ($i * ($blade_w + $gap));
        $svg[] = svg_rect($bx, $inner_y, $blade_w, $inner_h, "#a9a9a9", "#666666", 0.8);
        $svg[] = svg_line($bx + ($blade_w / 2), $inner_y + 3, $bx + ($blade_w / 2), $inner_y + $inner_h - 3, "#d9d9d9", 0.5);
    }
}

function draw_bioklimatik_lamels_elevation(&$svg, $x, $y, $w, $roof_h = 18, $mode = "front")
{
    $margin = 6;
    $inner_x = $x + $margin;
    $inner_w = max($w - ($margin * 2), 10);

    if ($mode === "front") {
        $tooth_count = max(12, min(22, intval($inner_w / 13)));
        $gap = 3;
        $tooth_w = max(5, ($inner_w - ($gap * ($tooth_count - 1))) / $tooth_count);
        $tooth_h = min($roof_h, 16);
    } else {
        $tooth_count = max(6, min(12, intval($inner_w / 18)));
        $gap = 4;
        $tooth_w = max(9, ($inner_w - ($gap * ($tooth_count - 1))) / $tooth_count);
        $tooth_h = min($roof_h, 18);
    }

    for ($i = 0; $i < $tooth_count; $i++) {
        $tx = $inner_x + ($i * ($tooth_w + $gap));
        $points = [[$tx, $y], [$tx, $y + $tooth_h], [$tx + $tooth_w, $y]];
        $svg[] = svg_polygon($points, "#b7b7b7", "#666666", $mode === "front" ? 0.7 : 0.85);
        $svg[] = svg_line($tx + 1, $y + $tooth_h - 1, $tx + $tooth_w - 1, $y + 1, "#efefef", 0.5);
    }
}

function draw_roof_detail_pattern(&$svg, $x, $y, $w, $h)
{
    $svg[] = svg_rect($x, $y, $w, $h, "#c3c3c3", "#565656", 1);
    $step = 12;
    $current_x = $x + 4;
    while ($current_x < $x + $w - 8) {
        $svg[] = svg_line($current_x, $y + $h - 3, $current_x + ($step / 2), $y + 3, "#4a4a4a", 1);
        $svg[] = svg_line($current_x + ($step / 2), $y + 3, $current_x + $step, $y + $h - 3, "#555555", 1);
        $current_x += $step;
    }
}

function draw_bioklimatik_section_fill(&$svg, $x, $y, $w, $h)
{
    $svg[] = svg_rect($x, $y, $w, $h, "#d8d8d8", "#777777", 1);

    $inner_x = $x + 8;
    $inner_y = $y + 8;
    $inner_w = max($w - 16, 10);
    $inner_h = max($h - 16, 10);
    $row_count = max(4, min(7, intval($inner_h / 12)));
    $row_gap = 4;
    $row_h = ($inner_h - ($row_gap * ($row_count - 1))) / $row_count;

    for ($i = 0; $i < $row_count; $i++) {
        $ry = $inner_y + ($i * ($row_h + $row_gap));
        $svg[] = svg_rect($inner_x, $ry, $inner_w, $row_h, "#b8b8b8", "#666666", 0.6);
        $step = 16;
        $cx = $inner_x + 2;
        while ($cx < $inner_x + $inner_w - $step) {
            $mid = $cx + ($step / 2);
            $end = $cx + $step;
            $svg[] = svg_line($cx, $ry + $row_h - 2, $mid, $ry + 2, "#5f5f5f", 1);
            $svg[] = svg_line($mid, $ry + 2, $end, $ry + $row_h - 2, "#5f5f5f", 1);
            $cx += $step;
        }
    }
}

function draw_elevation_unit(
    &$svg,
    $x,
    $base_y,
    $w,
    $h,
    $title,
    $subtitle,
    $measure_w,
    $measure_h,
    $glass = false,
    $view_type = "pergola",
    $elevation_mode = "front",
    $glass_type = ""
) {
    $y = $base_y - $h;

    $frame_color = "#333333";
    $glass_color = "#dff3ff";

    // Ana gövde
    $svg[] = svg_rect(
        $x,
        $y,
        $w,
        $h,
        "#f8f8f8",
        "#111111",
        2
    );

    $roof_profile_h = 15;

    // Üst ana profil
    $svg[] = svg_rect(
        $x,
        $y,
        $w,
        $roof_profile_h,
        $frame_color,
        "#111111",
        1
    );

    // Sol dikme
    $svg[] = svg_rect(
        $x,
        $y,
        8,
        $h,
        $frame_color,
        "#111111",
        1
    );

    // Sağ dikme
    $svg[] = svg_rect(
        $x + $w - 8,
        $y,
        8,
        $h,
        $frame_color,
        "#111111",
        1
    );

    // -------------------------------------------------
    // TAVAN TİPİNE GÖRE ÜST DETAY
    // -------------------------------------------------

    if ($view_type === "fixed_glass_roof" || $view_type === "moving_glass_roof") {
        $roof_panel_h = 28;
        $panel_count = 4;
        $panel_w = ($w - 16) / $panel_count;

        for ($i = 0; $i < $panel_count; $i++) {
            $gx = $x + 8 + ($i * $panel_w);

            $svg[] = svg_rect(
                $gx,
                $y + $roof_profile_h,
                $panel_w,
                $roof_panel_h,
                $glass_color,
                "#6f8fa3",
                1,
                0.85
            );

            $svg[] = svg_line(
                $gx + 4,
                $y + $roof_profile_h + 4,
                $gx + $panel_w - 4,
                $y + $roof_profile_h + $roof_panel_h - 4,
                "#ffffff",
                0.6,
                0.45
            );
        }

        if ($view_type === "moving_glass_roof") {
            $arrow_y = $y + $roof_profile_h + ($roof_panel_h / 2);

            $svg[] = svg_line(
                $x + $w * 0.30,
                $arrow_y,
                $x + $w * 0.70,
                $arrow_y,
                "#222222",
                1
            );

            $svg[] = svg_line(
                $x + $w * 0.70,
                $arrow_y,
                $x + $w * 0.65,
                $arrow_y - 4,
                "#222222",
                1
            );

            $svg[] = svg_line(
                $x + $w * 0.70,
                $arrow_y,
                $x + $w * 0.65,
                $arrow_y + 4,
                "#222222",
                1
            );
        }
    } elseif ($view_type === "pergola") {
        for ($i = 1; $i < 7; $i++) {
            $lx = $x + ($i * $w / 7);

            $svg[] = svg_line(
                $lx,
                $y + $roof_profile_h,
                $lx - 18,
                $y + 42,
                "#888888",
                1
            );
        }
    } elseif ($view_type === "bioklimatik") {
        if ($elevation_mode === "side") {
            // Eski mantık: yan görünüşte bioklimatik üstü düz profil kalır
            $svg[] = svg_rect(
                $x + 8,
                $y + 2,
                $w - 16,
                $roof_profile_h - 4,
                $frame_color,
                "#111111",
                0.5
            );
        } else {
            draw_bioklimatik_lamels_elevation(
                $svg,
                $x + 8,
                $y + 2,
                $w - 16,
                $roof_profile_h - 4,
                $elevation_mode
            );
        }
    } elseif ($view_type === "fixed_roof") {
        for ($i = 1; $i < 7; $i++) {
            $lx = $x + ($i * $w / 7);

            $svg[] = svg_line(
                $lx,
                $y + $roof_profile_h,
                $lx,
                $y + 42,
                "#777777",
                1
            );
        }
    } else {
        for ($i = 1; $i < 9; $i++) {
            $lx = $x + ($i * $w / 9);

            $svg[] = svg_line(
                $lx,
                $y + $roof_profile_h,
                $lx,
                $y + 42,
                "#777777",
                1
            );
        }
    }

    // -------------------------------------------------
    // CEPHE CAMI VARSA ALT CAMLARI ÇİZ
    // Eski Python mantığı:
    // cam = tavan değildir.
    // cam sadece alt cephe bölümüne çizilir.
    // -------------------------------------------------
    if ($glass) {
        $real_width = parse_measure_cell($measure_w);

        if ($real_width <= 0) {
            $real_width = floatval(str_replace(",", ".", str_replace("m", "", (string)$measure_w)));
        }

        $groups = calculate_glass_leaf_groups_php($real_width);

        $glass_x = $x + 8;
        $glass_y = $y + 42;
        $glass_w = $w - 16;
        $glass_h = $h - 44;

        if ($glass_h < 20) {
            $glass_h = max(20, $h - 25);
        }

        $group_gap = count($groups) > 1 ? 10 : 0;
        $total_gap = $group_gap * max(count($groups) - 1, 0);
        $total_panels = array_sum($groups);

        if ($total_panels <= 0) {
            $total_panels = 3;
        }

        $usable_w = $glass_w - $total_gap;
        $panel_w = $usable_w / $total_panels;

        $current_x = $glass_x;

        foreach ($groups as $group_index => $group_count) {
            for ($panel_index = 0; $panel_index < $group_count; $panel_index++) {
                $gx = $current_x + ($panel_index * $panel_w);

                // Cam panel
                $svg[] = svg_rect(
                    $gx,
                    $glass_y,
                    $panel_w,
                    $glass_h,
                    $glass_color,
                    "#666666",
                    1
                );

                // Parlama çizgisi
                $svg[] = svg_line(
                    $gx + 4,
                    $glass_y + 4,
                    $gx + $panel_w - 4,
                    $glass_y + $glass_h - 4,
                    "#ffffff",
                    0.6,
                    0.45
                );

                // Sürme cam çift yönlü ok
                // Sadece sürme camda çift yönlü ok çiz
                $show_arrows = ($glass_type === "" || $glass_type === "sliding_glass");

                if ($show_arrows) {
                    $arrow_y = $glass_y + ($glass_h * 0.55);
                    $arrow_x1 = $gx + ($panel_w * 0.28);
                    $arrow_x2 = $gx + ($panel_w * 0.72);

                    $svg[] = svg_line(
                        $arrow_x1,
                        $arrow_y,
                        $arrow_x2,
                        $arrow_y,
                        "#222222",
                        1
                    );

                    // Sol ok ucu
                    $svg[] = svg_line(
                        $arrow_x1,
                        $arrow_y,
                        $arrow_x1 + 5,
                        $arrow_y - 3,
                        "#222222",
                        1
                    );

                    $svg[] = svg_line(
                        $arrow_x1,
                        $arrow_y,
                        $arrow_x1 + 5,
                        $arrow_y + 3,
                        "#222222",
                        1
                    );

                    // Sağ ok ucu
                    $svg[] = svg_line(
                        $arrow_x2,
                        $arrow_y,
                        $arrow_x2 - 5,
                        $arrow_y - 3,
                        "#222222",
                        1
                    );

                    $svg[] = svg_line(
                        $arrow_x2,
                        $arrow_y,
                        $arrow_x2 - 5,
                        $arrow_y + 3,
                        "#222222",
                        1
                    );
                }

            }

            $current_x += $group_count * $panel_w;

            // Gruplar arası kalın orta dikme
            if ($group_index < count($groups) - 1) {
                $svg[] = svg_rect(
                    $current_x,
                    $glass_y,
                    $group_gap,
                    $glass_h,
                    "#555555",
                    "#333333",
                    1
                );

                $current_x += $group_gap;
            }
        }
    }

    // -------------------------------------------------
    // ÖLÇÜLER
    // -------------------------------------------------
    $measure_w_text = (string)$measure_w;
    $measure_h_text = (string)$measure_h;

    if (strpos($measure_w_text, "m") === false) {
        $measure_w_text .= " m";
    }

    if (strpos($measure_h_text, "m") === false) {
        $measure_h_text .= " m";
    }

    draw_dimension_horizontal(
        $svg,
        $x,
        $y - 8,
        $w,
        $measure_w_text,
        8
    );

    draw_dimension_vertical(
        $svg,
        $x - 7,
        $y,
        $h,
        $measure_h_text,
        8
    );

    // -------------------------------------------------
    // YAZILAR
    // -------------------------------------------------
    if ($elevation_mode === "side") {
        if (trim((string)$title) !== "") {
            $svg[] = svg_text(
                $x + ($w / 2),
                $base_y + 13,
                $title,
                8,
                "bold",
                "#111111",
                "middle"
            );

            $svg[] = svg_text(
                $x + ($w / 2),
                $base_y + 25,
                $subtitle,
                8,
                "bold",
                "#111111",
                "middle"
            );
        } else {
            $svg[] = svg_text(
                $x + ($w / 2),
                $base_y + 20,
                $subtitle,
                8,
                "bold",
                "#111111",
                "middle"
            );
        }
    } else {
        $svg[] = svg_text(
            $x + ($w / 2),
            $base_y + 14,
            $title,
            9,
            "bold",
            "#111111",
            "middle"
        );

        $svg[] = svg_text(
            $x + ($w / 2),
            $base_y + 26,
            $subtitle,
            8,
            "bold",
            "#111111",
            "middle"
        );
    }    
}

// ---------------------------------------------------------
// 1. ON GORUNUSLER - ESKI PYTHON MANTIGI
// ---------------------------------------------------------
function module_has_front_glass_php($module, $project_data)
{
    $glass_items = $project_data["sliding_glass"] ?? [];

    if (empty($glass_items)) {
        return false;
    }

    $module_area = svg_norm($module["area"] ?? "");

    if ($module_area === "") {
        return false;
    }

    foreach ($glass_items as $glass) {
        $glass_area = svg_norm($glass["area"] ?? "");

        if ($glass_area === "") {
            continue;
        }

        // Eski Python mantığı:
        // Ölçüye bakma.
        // Sadece alan eşleşirse camı modüle bağla.
        //
        // Örn:
        // ÜST camı -> ÜST TERAS modülüne bağlanabilir.
        // ÜST camı -> HAVUZ ÖNÜ modülüne bağlanmaz.
        if (
            strpos($module_area, $glass_area) !== false ||
            strpos($glass_area, $module_area) !== false
        ) {
            return true;
        }
    }

    return false;
}

if (!function_exists("front_norm_php")) {
    function front_norm_php($text)
    {
        $text = (string)$text;

        if (function_exists("tr_to_ascii")) {
            $text = tr_to_ascii($text);
        } else {
            $search = ["ç","Ç","ğ","Ğ","ı","İ","ö","Ö","ş","Ş","ü","Ü"];
            $replace = ["c","C","g","G","i","I","o","O","s","S","u","U"];
            $text = str_replace($search, $replace, $text);
        }

        return strtoupper(trim($text));
    }
}

if (!function_exists("front_clamp_php")) {
    function front_clamp_php($value, $min, $max)
    {
        return max($min, min($value, $max));
    }
}

if (!function_exists("find_named_front_glass_php")) {
    function find_named_front_glass_php($sliding_glass)
    {
        foreach ($sliding_glass ?? [] as $item) {
            $area = front_norm_php($item["area"] ?? "");
            $desc = front_norm_php($item["description"] ?? "");
            $text = $area . " " . $desc;

            if (strpos($text, "ON CEPHE") !== false) {
                return $item;
            }
        }

        return null;
    }
}

if (!function_exists("module_has_glass_old_front_php")) {
    function module_has_glass_old_front_php($module, $sliding_glass)
    {
        if (empty($sliding_glass)) {
            return false;
        }

        $area = front_norm_php($module["area"] ?? "");

        if ($area === "") {
            return false;
        }

        foreach ($sliding_glass as $item) {
            $glass_area = front_norm_php($item["area"] ?? "");

            if ($glass_area === "") {
                continue;
            }

            // Eski Python mantığı:
            // ölçüye bakma, sadece alan eşleşmesi.
            if (
                strpos($area, $glass_area) !== false ||
                strpos($glass_area, $area) !== false
            ) {
                return true;
            }
        }

        return false;
    }
}

function draw_front_views_python_like(&$svg, $project_data)
{
    $modules = $project_data["modules"] ?? [];
    $sliding_glass = $project_data["sliding_glass"] ?? [];

    // Madde 2:
    // Ön görünüş sadece modules listesinden çizilir.
    if (empty($modules)) {
        return;
    }

    $front_base = 305;

    $inner_left = 55;
    $inner_right = 1205;
    $gap = 32;

    $available_w = $inner_right - $inner_left;

    $total_w = 0;

    foreach ($modules as $module) {
        $total_w += floatval($module["width"] ?? 0);
    }

    $module_count = count($modules);

            

    // Eski mantık:
    // Gerçek ölçü oran verir ama çizim kutusu kontrollü kalır.
    // Modül sayısı arttıkça satır topluca küçülür.
    if ($module_count == 1) {
        $max_scale = 58;
        $gap = 40;
    } elseif ($module_count == 2) {
        $max_scale = 50;
        $gap = 38;
    } elseif ($module_count == 3) {
        $max_scale = 44;
        $gap = 34;
    } elseif ($module_count <= 5) {
        $max_scale = 38;
        $gap = 30;
    } else {
        $max_scale = 34;
        $gap = 26;
    }

    $front_scale = min(
        ($available_w - $gap * max($module_count - 1, 0)) / max($total_w, 1),
        $max_scale
    );

    $front_items = [];

    foreach ($modules as $module) {
        $module_width = floatval($module["width"] ?? 0);
        $module_height = floatval($module["height"] ?? 0);

        // Eski Python mantığı:
        // Gerçek ölçü çizim oranına etki eder ama clamp ile sınırlandırılır.
        $w = front_clamp_php($module_width * $front_scale, 260, 380);
        $h = front_clamp_php($module_height * 40, 115, 145);

        // 3 modülde eski görünüm: çok ezmeden ama aynı satıra sığacak şekilde
        if ($module_count == 3) {
            $w = front_clamp_php($w, 245, 285);
        }

        // 4-5 modülde daha kompakt
        elseif ($module_count <= 5) {
            $w = front_clamp_php($w, 185, 235);
        }

        // 6 ve üzeri modülde standart küçük kutu
        else {
            $w = front_clamp_php($w, 135, 170);
        }

        // Ön cephede açıkça "ÖN CEPHE" cam varsa öncelik onda.
        $named_front_glass = find_named_front_glass_php($sliding_glass);

        if ($named_front_glass) {
            $glass = true;
        } else {
            // Yoksa eski alan eşleşmesi mantığı.
            $glass = module_has_glass_old_front_php($module, $sliding_glass);
        }

        $view_type = get_roof_type_php($module);

        $front_items[] = [
            "module" => $module,
            "w" => $w,
            "h" => $h,
            "glass" => $glass,
            "named_front_glass" => $named_front_glass,
            "view_type" => $view_type,
        ];
    }

    $total_row_w = 0;

    foreach ($front_items as $item) {
        $total_row_w += $item["w"];
    }

    $total_row_w += $gap * max(count($front_items) - 1, 0);

    // Sığmazsa tüm satırı beraber küçült.
    if ($total_row_w > $available_w && !empty($front_items)) {
        $sum_w = 0;

        foreach ($front_items as $item) {
            $sum_w += $item["w"];
        }

        $shrink_ratio = ($available_w - $gap * max(count($front_items) - 1, 0)) / max($sum_w, 1);

        foreach ($front_items as $idx => $item) {
            $front_items[$idx]["w"] = $item["w"] * $shrink_ratio;
        }

        $total_row_w = 0;

        foreach ($front_items as $item) {
            $total_row_w += $item["w"];
        }

        $total_row_w += $gap * max(count($front_items) - 1, 0);
    }

    $current_x = $inner_left + max(($available_w - $total_row_w) / 2, 0);

    foreach ($front_items as $item) {
        $module = $item["module"];

        $title = $module["name"] ?? "MODÜL";

        $qty = intval($module["quantity"] ?? 1);

        if ($qty > 1) {
            $title .= " x" . $qty;
        }

        $area_label = $module["area"] ?? "";
        $area_label = str_replace("ONU", "ÖNÜ", $area_label);
        $area_label = str_replace("UST", "ÜST", $area_label);
        $area_label = str_replace("SABIT", "SABİT", $area_label);

        $named_front_glass = $item["named_front_glass"];

        if ($named_front_glass) {
            $glass_width = floatval($named_front_glass["width"] ?? ($module["width"] ?? 0));
            $glass_height = floatval($named_front_glass["height"] ?? ($module["height"] ?? 0));
            $glass_qty = intval($named_front_glass["quantity"] ?? 1);

            if ($glass_qty <= 0) {
                $glass_qty = 1;
            }

            // Açık ön cephe camı varsa ölçü camdan gelir.
            $total_front_width = $glass_width * $glass_qty;

            $draw_measure_w = format_meter($total_front_width);
            $draw_measure_h = format_meter($glass_height);

            if ($glass_qty > 1) {
                $draw_subtitle =
                    ($named_front_glass["area"] ?? "ÖN CEPHE") .
                    " - " .
                    format_meter($glass_width) .
                    "x" .
                    format_meter($glass_height) .
                    " x" .
                    $glass_qty;
            } else {
                $draw_subtitle =
                    ($named_front_glass["area"] ?? "ÖN CEPHE") .
                    " - " .
                    format_meter($glass_width) .
                    "x" .
                    format_meter($glass_height);
            }
        } else {
            // Normal modül ön görünüşü: ölçü modülden gelir.
            $draw_measure_w = format_meter($module["width"] ?? 0);
            $draw_measure_h = format_meter($module["height"] ?? 0);
            $draw_subtitle = $area_label;
        }

        draw_elevation_unit(
        $svg,
        $current_x,
        $front_base,
        $item["w"],
        $item["h"],
        $title,
        $draw_subtitle,
        $draw_measure_w,
        $draw_measure_h,
        $item["glass"],
        $item["view_type"],
        "front",
        $named_front_glass["drawing_type"] ?? ""
    );

        $current_x += $item["w"] + $gap;
    }
}
// ---------------------------------------------------------
// 2. YAN GORUNUSLER - ESKI PYTHON MANTIGI
// ---------------------------------------------------------
if (!function_exists("glass_view_direction_php")) {
    function glass_view_direction_php($item)
    {
        $area = front_norm_php($item["area"] ?? "");
        $desc = front_norm_php($item["description"] ?? "");
        $text = $area . " " . $desc;

        if (strpos($text, "ARKA CEPHE") !== false) {
            return "back";
        }

        if (strpos($text, "ON CEPHE") !== false) {
            return "front";
        }

        if (strpos($text, "SOL CEPHE") !== false) {
            return "side";
        }

        if (strpos($text, "SAG CEPHE") !== false) {
            return "side";
        }

        if (strpos($text, "YAN CEPHE") !== false) {
            return "side";
        }

        return null;
    }
}

if (!function_exists("find_named_side_glasses_php")) {
    function find_named_side_glasses_php($sliding_glass)
    {
        $result = [];

        foreach ($sliding_glass ?? [] as $item) {
            if (glass_view_direction_php($item) !== "side") {
                continue;
            }

            $desc = front_norm_php($item["description"] ?? "");
            $area = front_norm_php($item["area"] ?? "");
            $text = $area . " " . $desc;

            if (
                strpos($text, "SURME CAM") === false &&
                strpos($text, "SABIT CAM") === false &&
                strpos($text, "SABIT CAM DOGRAMA") === false &&
                strpos($text, "GIYOTIN CAM") === false
            ) {
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }
}

function draw_side_views_python_like(&$svg, $project_data)
{
    $modules = $project_data["modules"] ?? [];
    $sliding_glass = $project_data["sliding_glass"] ?? [];

    if (!$modules && !$sliding_glass) {
        return;
    }

    $side_base = 540;
    $inner_left = 55;
    $inner_right = 1205;
    $available_w = $inner_right - $inner_left;

    $side_glasses = [];

    foreach ($sliding_glass as $glass) {
        $area_norm = front_norm_php($glass["area"] ?? "");
        $desc_norm = front_norm_php($glass["description"] ?? "");
        $text_norm = $area_norm . " " . $desc_norm;

        // Yan görünüşte sadece SOL / SAĞ / YAN CEPHE camları alınır.
        if (
            strpos($text_norm, "SOL CEPHE") === false &&
            strpos($text_norm, "SAG CEPHE") === false &&
            strpos($text_norm, "YAN CEPHE") === false
        ) {
            continue;
        }

        // Sadece cam sistemleri alınır.
        if (
            strpos($text_norm, "SURME CAM") === false &&
            strpos($text_norm, "SABIT CAM") === false &&
            strpos($text_norm, "GIYOTIN CAM") === false
        ) {
            continue;
        }

        $glass_w_m = get_num($glass, "width", 0);

        // 1 metre altı parçaları yan görünüşte çizme.
        if ($glass_w_m < 1.00) {
            continue;
        }

        $side_glasses[] = $glass;
    }

    $items = [];

    // SOL / SAĞ / YAN CEPHE camları varsa, yan görünüşü camlardan üret.
    if (!empty($side_glasses)) {
        $count = count($side_glasses);

        if ($count <= 3) {
            $gap = 46;
            $min_w = 230;
            $max_w = 340;
        } elseif ($count <= 6) {
            $gap = 38;
            $min_w = 150;
            $max_w = 230;
        } else {
            $gap = 30;
            $min_w = 95;
            $max_w = 155;
        }

        $total_width_m = 0;

        foreach ($side_glasses as $glass) {
            $glass_w_m = get_num($glass, "width", 0);

            // Toplam genişlik hesabında da 1 m altını sayma.
            if ($glass_w_m < 1.00) {
                continue;
            }

            $total_width_m += max($glass_w_m, 0.5);
        }

        $scale = ($available_w - ($gap * max($count - 1, 0))) / max($total_width_m, 1);

        foreach ($side_glasses as $glass) {
            $glass_w_m = get_num($glass, "width", 0);
            $glass_h_m = get_num($glass, "height", 0);

            // Ek güvenlik: 1 m altı çizilmez.
            if ($glass_w_m < 1.00) {
                continue;
            }

            if ($glass_h_m <= 0) {
                $glass_h_m = 1.4;
            }

            $w = clamp_value_svg($glass_w_m * $scale, $min_w, $max_w);
            $h = clamp_value_svg($glass_h_m * 80, 115, 145);

            $items[] = [
                "kind" => "glass",
                "glass" => $glass,
                "w" => $w,
                "h" => $h,
                "measure_w" => $glass_w_m,
                "measure_h" => $glass_h_m,
                "view_type" => "fixed_roof",
            ];
        }
    }

    // Eğer yan cephe camı yoksa, modül derinliklerinden üret.
    if (empty($items)) {
        $count = count($modules);

        if ($count <= 3) {
            $gap = 48;
            $min_w = 220;
            $max_w = 275;
        } elseif ($count <= 5) {
            $gap = 40;
            $min_w = 170;
            $max_w = 230;
        } else {
            $gap = 34;
            $min_w = 130;
            $max_w = 170;
        }

        $total_d = 0;

        foreach ($modules as $m) {
            $d = get_num($m, "depth", 0);

            if ($d <= 0) {
                $d = max(get_num($m, "width", 0) * 0.35, 1.2);
            }

            $total_d += $d;
        }

        $scale = ($available_w - ($gap * max($count - 1, 0))) / max($total_d, 1);

        foreach ($modules as $m) {
            $depth = get_num($m, "depth", 0);

            if ($depth <= 0) {
                $depth = max(get_num($m, "width", 0) * 0.35, 1.2);
            }

            $h = clamp_value_svg(get_num($m, "height", 0) * 40, 115, 145);
            $w = clamp_value_svg($depth * $scale, $min_w, $max_w);

            $module_glass = module_has_glass_old_front_php($m, $sliding_glass);

            $items[] = [
                "kind" => "module",
                "module" => $m,
                "w" => $w,
                "h" => $h,
                "measure_w" => $depth,
                "measure_h" => get_num($m, "height", 0),
                "glass" => $module_glass,
                "view_type" => get_roof_type_php($m),
            ];
        }
    }

    $total_row_w = array_sum(array_column($items, "w")) + ($gap * max(count($items) - 1, 0));

    if ($total_row_w > $available_w && $items) {
        $sum_w = array_sum(array_column($items, "w"));
        $ratio = ($available_w - ($gap * max(count($items) - 1, 0))) / max($sum_w, 1);

        foreach ($items as $i => $item) {
            $items[$i]["w"] = $item["w"] * $ratio;
        }

        $total_row_w = array_sum(array_column($items, "w")) + ($gap * max(count($items) - 1, 0));
    }

    $current_x = $inner_left + max(($available_w - $total_row_w) / 2, 0);

    foreach ($items as $item) {
        $title = "";

        if ($item["kind"] === "glass") {
            $glass = $item["glass"];

            $qty = get_qty($glass);

            if ($qty <= 0) {
                $qty = 1;
            }

            $desc_norm = front_norm_php($glass["description"] ?? "");
            $kind_label = "SÜRME CAM";

            if (strpos($desc_norm, "SABIT CAM") !== false) {
                $kind_label = "SABİT CAM";
            } elseif (strpos($desc_norm, "GIYOTIN CAM") !== false) {
                $kind_label = "GİYOTİN CAM";
            }

            if ($qty > 1) {
                $subtitle = ($glass["area"] ?? "YAN CEPHE") . " - " . $kind_label . " x" . $qty;
            } else {
                $subtitle = ($glass["area"] ?? "YAN CEPHE") . " - " . $kind_label;
            }

            $measure_w = fmt_m($item["measure_w"]);
            $measure_h = fmt_m($item["measure_h"]);

            draw_elevation_unit(
            $svg,
            $current_x,
            $side_base,
            $item["w"],
            $item["h"],
            $title,
            $subtitle,
            $measure_w,
            $measure_h,
            true,
            $item["view_type"],
            "side",
            $glass["drawing_type"] ?? ""
        );
        } else {
            $subtitle = "DERİNLİK: " . fmt_m($item["measure_w"]);

            draw_elevation_unit(
                $svg,
                $current_x,
                $side_base,
                $item["w"],
                $item["h"], 
                $title,
                $subtitle,
                fmt_m($item["measure_w"]),
                fmt_m($item["measure_h"]),
                $item["glass"],
                $item["view_type"],
                "side"
            );
        }

        $current_x += $item["w"] + $gap;
    }
}

// ---------------------------------------------------------
// 3. KESITLER
// ---------------------------------------------------------

function collect_unique_roof_sections_php($modules)
{
    $map = [];

    foreach ($modules ?? [] as $m) {
        $roof_type = get_roof_type_php($m);
        $key = $roof_type;
        $measure = fmt_m(get_num($m, "width", 0)) . "x" . fmt_m(get_num($m, "depth", 0));
        $qty = get_qty($m);
        if ($qty > 1) $measure .= " x" . $qty;

        if (!isset($map[$key])) {
            $map[$key] = ["roof_type" => $roof_type, "module" => $m, "measures" => []];
        }
        if (!in_array($measure, $map[$key]["measures"], true)) $map[$key]["measures"][] = $measure;
    }

    return array_values($map);
}

function collect_unique_facade_sections_php($project_data)
{
    $map = [];
    $sources = [];

    foreach ($project_data["sliding_glass"] ?? [] as $g) $sources[] = $g;
    foreach (get_real_panels($project_data) as $p) $sources[] = $p;

    foreach ($sources as $item) {
        [$title, $type] = detect_section_type_php(($item["description"] ?? "") . " " . ($item["area"] ?? ""));
        if ($type === "ignore") continue;

        $w = get_num($item, "width", 0);
        $h = get_num($item, "height", 0);
        if ($w <= 0 || $h <= 0) {
            continue;
        }        

        $qty = get_qty($item);
        $measure = fmt_m($w) . "x" . fmt_m($h);
        if ($qty > 1) $measure .= " x" . $qty;

        $key = $type;
        if (!isset($map[$key])) {
            $map[$key] = ["section_title" => $title, "section_type" => $type, "item" => $item, "width" => $w, "height" => $h, "measures" => []];
        }

        if ($w > $map[$key]["width"]) {
            $map[$key]["width"] = $w;
            $map[$key]["height"] = $h;
            $map[$key]["item"] = $item;
        }

        if (!in_array($measure, $map[$key]["measures"], true)) $map[$key]["measures"][] = $measure;
    }

    return array_values($map);
}

function draw_tavan_section(&$svg, $x, $y, $w, $h, $module)
{
    $roof_type = get_roof_type_php($module);
    $horizontal = get_num($module, "width", 0) ?: get_num($module, "depth", 0);
    $vertical = get_num($module, "depth", 0) ?: get_num($module, "height", 0);

    $original_x = $x;
    $original_y = $y;
    $original_w = $w;
    $original_h = $h;

    if ($vertical > $horizontal) {
        $draw_w = 115;
        $draw_h = $original_h;
    } else {
        $draw_w = $original_w;
        $draw_h = $original_h;
    }

    $x = $original_x + (($original_w - $draw_w) / 2);
    $y = $original_y + (($original_h - $draw_h) / 2);
    $w = $draw_w;
    $h = $draw_h;

    $title = "TAVAN KESİTİ";
    if ($roof_type === "moving_glass_roof") $title = "HAREKETLİ CAM TAVAN KESİTİ";
    elseif ($roof_type === "fixed_glass_roof") $title = "SABİT CAM TAVAN KESİTİ";
    elseif ($roof_type === "fixed_roof") $title = "SABİT TAVAN KESİTİ";
    elseif ($roof_type === "pergola") $title = "PERGOLA / PENTE TAVAN KESİTİ";
    elseif ($roof_type === "bioklimatik") $title = "BİOKLİMATİK TAVAN KESİTİ";

    $title_y = $vertical > $horizontal * 1.5 ? $y - 60 : $y - 14;
    $svg[] = svg_text($x + ($w / 2), $title_y, $title, 9, "bold", "#1a1a1a", "middle");
    $svg[] = svg_rect($x, $y, $w, $h, "#ffffff", "#4f4f4f", 1.5);

    $top_h = 22;
    $side_w = 8;
    $bottom_h = 10;

    if ($vertical > $horizontal * 1.5) {
        $extra_h = min(50, $h * 0.45);
        $y -= $extra_h;
        $h += $extra_h;
    }

    $profile = "#3d3d3d";
    $svg[] = svg_rect($x, $y, $w, $top_h, $profile, "#333333", 1);
    $svg[] = svg_rect($x, $y, $side_w, $h, $profile, "#333333", 1);
    $svg[] = svg_rect($x + $w - $side_w, $y, $side_w, $h, $profile, "#333333", 1);
    $svg[] = svg_rect($x, $y + $h - $bottom_h, $w, $bottom_h, $profile, "#333333", 1);

    $inner_x = $x + $side_w;
    $inner_y = $y + $top_h;
    $inner_w = $w - ($side_w * 2);
    $inner_h = $h - $top_h - $bottom_h;

    if ($roof_type === "bioklimatik") {
        draw_bioklimatik_section_fill($svg, $inner_x, $inner_y, $inner_w, $inner_h);
        $arrow_x = $inner_x + ($inner_w * 0.50);
        $arrow_y1 = $inner_y + ($inner_h * 0.18);
        $arrow_y2 = $inner_y + ($inner_h * 0.78);
        $svg[] = svg_line($arrow_x, $arrow_y1, $arrow_x, $arrow_y2, "#222222", 1.4);
        $svg[] = svg_line($arrow_x, $arrow_y2, $arrow_x - 5, $arrow_y2 - 7, "#222222", 1.4);
        $svg[] = svg_line($arrow_x, $arrow_y2, $arrow_x + 5, $arrow_y2 - 7, "#222222", 1.4);
        
    } elseif (in_array($roof_type, ["fixed_glass_roof", "moving_glass_roof"], true)) {
        $panel_w = $inner_w / 4;
        for ($i = 0; $i < 4; $i++) {
            $gx = $inner_x + ($i * $panel_w);
            $svg[] = svg_rect($gx, $inner_y, $panel_w, $inner_h, "#dff3ff", "#6f8fa3", 1, 0.85);
        }

    } elseif ($roof_type === "fixed_roof") {
        // SABİT TAVAN KESİTİ: yatay sandviç/panel çizgileri
        $svg[] = svg_rect($inner_x, $inner_y, $inner_w, $inner_h, "#d6d6d6", "#9a9a9a", 0.8);

        $line_count = 6;
        for ($i = 1; $i < $line_count; $i++) {
            $ly = $inner_y + ($inner_h * $i / $line_count);
            $svg[] = svg_line($inner_x, $ly, $inner_x + $inner_w, $ly, "#9a9a9a", 1);
        }

    } else {
        draw_roof_detail_pattern($svg, $inner_x, $inner_y, $inner_w, $inner_h);
    }

    draw_dimension_vertical($svg, $x - 7, $y, $h, $vertical ? fmt_m($vertical) : "AÇILIM", 8);
    draw_dimension_horizontal($svg, $x, $y + $h + 16, $w, $horizontal ? fmt_m($horizontal) : "CEPHE", 8);
}

function estimate_panel_count_php($section_type, $real_width)
{
    $real_width = floatval($real_width ?: 0);

    if (in_array($section_type, ["sliding", "folding"], true)) return array_sum(calculate_glass_leaf_groups_php($real_width));
    if ($section_type === "fixed_glass") {
        if ($real_width < 1.5) return 1;
        if ($real_width < 2.5) return 2;
        if ($real_width < 4.0) return 3;
        if ($real_width < 5.0) return 4;
        return max(1, intval(round($real_width / 0.95)));
    }
    if ($section_type === "guillotine") return 3;
    if (in_array($section_type, ["comfort_panel", "sandwich", "composite"], true)) {
        if ($real_width <= 3.0) return 3;
        if ($real_width <= 5.0) return 4;
        return 5;
    }

    return 1;
}

function draw_dynamic_section(&$svg, $x, $y, $w, $h, $title, $section_type, $real_width = 0, $real_height = 0, $show_top_detail = true)
{
    if ($section_type === "ignore") return;

    $panel_count = estimate_panel_count_php($section_type, $real_width);
    $profile_dark = "#3d3d3d";
    $profile_mid = "#555555";
    $glass_fill = "#cfdbe4";
    $panel_fill = "#c4c4c4";

    $svg[] = svg_text($x + ($w / 2), $y - 14, $title, 9, "bold", "#1a1a1a", "middle");
    $svg[] = svg_rect($x, $y, $w, $h, "#ffffff", "#4f4f4f", 1.5);

    $top_h = 20;
    $bottom_h = 10;
    $side_w = 8;

    $svg[] = svg_rect($x, $y, $w, $top_h, $profile_dark, "#333333", 1);
    draw_roof_detail_pattern($svg, $x + $side_w, $y + 3, $w - ($side_w * 2), $top_h - 6);
    $svg[] = svg_rect($x, $y, $side_w, $h, $profile_dark, "#333333", 1);
    $svg[] = svg_rect($x + $w - $side_w, $y, $side_w, $h, $profile_dark, "#333333", 1);
    $svg[] = svg_rect($x, $y + $h - $bottom_h, $w, $bottom_h, $profile_dark, "#333333", 1);

    $inner_x = $x + $side_w;
    $inner_y = $y + $top_h;
    $inner_w = $w - ($side_w * 2);
    $inner_h = $h - $top_h - $bottom_h;

    if (in_array($section_type, ["sliding", "folding"], true)) {
        $groups = calculate_glass_leaf_groups_php($real_width);
        $group_gap = count($groups) > 1 ? 10 : 0;
        $total_gap = $group_gap * (count($groups) - 1);
        $total_panels = array_sum($groups);
        $usable_w = $inner_w - $total_gap;
        $panel_w = $usable_w / max($total_panels, 1);
        $current_x = $inner_x;
        $arrow_y = $inner_y + ($inner_h * 0.62);

        foreach ($groups as $group_index => $group_count) {
            for ($panel_index = 0; $panel_index < $group_count; $panel_index++) {
                $gx = $current_x + ($panel_index * $panel_w);
                $svg[] = svg_rect($gx, $inner_y, $panel_w, $inner_h, $glass_fill, $profile_mid, 1);
                $svg[] = svg_line($gx + 4, $inner_y + 5, $gx + $panel_w - 5, $inner_y + $inner_h - 5, "#ffffff", 0.6, 0.45);
                $ax1 = ($panel_index % 2 === 0) ? $gx + ($panel_w * 0.25) : $gx + ($panel_w * 0.72);
                $ax2 = ($panel_index % 2 === 0) ? $gx + ($panel_w * 0.72) : $gx + ($panel_w * 0.25);
                $svg[] = svg_line($ax1, $arrow_y, $ax2, $arrow_y, "#222222", 1);
                if ($ax2 > $ax1) {
                    $svg[] = svg_line($ax2, $arrow_y, $ax2 - 5, $arrow_y - 3, "#222222", 1);
                    $svg[] = svg_line($ax2, $arrow_y, $ax2 - 5, $arrow_y + 3, "#222222", 1);
                } else {
                    $svg[] = svg_line($ax2, $arrow_y, $ax2 + 5, $arrow_y - 3, "#222222", 1);
                    $svg[] = svg_line($ax2, $arrow_y, $ax2 + 5, $arrow_y + 3, "#222222", 1);
                }
            }
            $current_x += $group_count * $panel_w;
            if ($group_index < count($groups) - 1) {
                $svg[] = svg_rect($current_x, $inner_y, $group_gap, $inner_h, $profile_mid, "#333333", 1);
                $current_x += $group_gap;
            }
        }
    } elseif ($section_type === "guillotine") {
        $panel_h = $inner_h / max($panel_count, 1);
        for ($i = 0; $i < $panel_count; $i++) {
            $gy = $inner_y + ($i * $panel_h);
            $svg[] = svg_rect($inner_x, $gy, $inner_w, $panel_h, $glass_fill, $profile_mid, 1);
        }
        $cx = $inner_x + ($inner_w / 2);
        $svg[] = svg_line($cx, $inner_y + 18, $cx, $inner_y + $inner_h - 18, "#222222", 1.3);
    } elseif ($section_type === "fixed_glass") {
        $panel_w = $inner_w / max($panel_count, 1);
        for ($i = 0; $i < $panel_count; $i++) {
            $gx = $inner_x + ($i * $panel_w);
            $svg[] = svg_rect($gx, $inner_y, $panel_w, $inner_h, $glass_fill, $profile_mid, 1);
            $svg[] = svg_line($gx + 5, $inner_y + 5, $gx + $panel_w - 5, $inner_y + $inner_h - 5, "#ffffff", 0.6, 0.45);
        }
    } elseif (in_array($section_type, ["zip", "composite", "comfort_panel", "sandwich"], true)) {
        $fill = $section_type === "composite" ? "#5b5b5b" : ($section_type === "zip" ? "#dddddd" : $panel_fill);
        $svg[] = svg_rect($inner_x, $inner_y, $inner_w, $inner_h, $fill, $profile_mid, 1);
        $line_count = $section_type === "zip" ? 7 : 6;
        for ($i = 1; $i < $line_count; $i++) {
            $ly = $inner_y + ($i * $inner_h / $line_count);
            $svg[] = svg_line($inner_x, $ly, $inner_x + $inner_w, $ly, "#8a8a8a", 1);
            if (in_array($section_type, ["comfort_panel", "sandwich"], true)) {
                $svg[] = svg_line($inner_x, $ly + 2, $inner_x + $inner_w, $ly + 2, "#eeeeee", 0.6);
            }
        }
    }

    $height_label = $real_height ? fmt_m($real_height) . " m" : "H";
    $bottom_label = $real_width ? fmt_m($real_width) . " m" : "MODÜL ÖLÇÜSÜ";
    draw_dimension_vertical($svg, $x - 7, $y, $h, $height_label, 8);
    draw_dimension_horizontal($svg, $x, $y + $h + 16, $w, $bottom_label, 8);
}

function draw_sections_python_like(&$svg, $project_data)
{
    $modules = $project_data["modules"] ?? [];
    $section_y = 690;
    $section_h = 115;

    $roof_sections = collect_unique_roof_sections_php($modules);
    $roof_start_x = 55;
    $roof_card_w = 280;
    $roof_card_h = $section_h;
    $roof_gap_x = 28;

    foreach (array_slice($roof_sections, 0, 2) as $i => $roof_sec) {
        $x = $roof_start_x + ($i * ($roof_card_w + $roof_gap_x));
        $y = $section_y;
        draw_tavan_section($svg, $x, $y, $roof_card_w, $roof_card_h, $roof_sec["module"]);
        $measure_line = implode(" / ", $roof_sec["measures"]);
        $svg[] = svg_text($x + ($roof_card_w / 2), $y + $roof_card_h + 30, $measure_line, 8.5, "bold", "#111111", "middle");
    }

    $facade_sections = collect_unique_facade_sections_php($project_data);
    $facade_start_x = 685;
    $facade_card_w = 260;
    $facade_card_h = $section_h;
    $facade_gap_x = 30;

    foreach (array_slice($facade_sections, 0, 2) as $i => $sec) {
        $x = $facade_start_x + ($i * ($facade_card_w + $facade_gap_x));
        $y = $section_y;
        draw_dynamic_section($svg, $x, $y, $facade_card_w, $facade_card_h, $sec["section_title"], $sec["section_type"], $sec["width"], $sec["height"], false);
        $measure_line = implode(" / ", $sec["measures"]);
        $svg[] = svg_text($x + ($facade_card_w / 2), $y + $facade_card_h + 30, $measure_line, 8.5, "bold", "#111111", "middle");
    }
}

// ---------------------------------------------------------
// 4. MODUL BILGILERI - KARTLI PYTHON MANTIGI
// ---------------------------------------------------------

function draw_module_info_cards(&$svg, $project_data)
{
    $modules = $project_data["modules"] ?? [];
    $info_x = 15;
    $info_y = 865;

    $svg[] = svg_rect($info_x, $info_y, 1230, 300, "#111111", "#111111", 2);
    $svg[] = svg_text($info_x + 20, $info_y + 35, "4. MODÜL BİLGİLERİ", 15, "bold", "#d6b87c");

    $card_x = $info_x + 30;
    $card_y = $info_y + 62;
    $count = count($modules);

    if ($count <= 3) {
        $card_w = 350; $card_gap = 45; $title_size = 12; $text_size = 11; $system_size = 10; $max_lines = 2;
    } elseif ($count <= 4) {
        $card_w = 270; $card_gap = 25; $title_size = 10; $text_size = 9; $system_size = 8; $max_lines = 2;
    } elseif ($count <= 6) {
        $card_w = 180; $card_gap = 20; $title_size = 8; $text_size = 8; $system_size = 7; $max_lines = 2;
    } else {
        $card_w = 150; $card_gap = 15; $title_size = 7; $text_size = 7; $system_size = 6; $max_lines = 1;
    }

    foreach ($modules as $idx => $m) {
        $x = $card_x + ($idx * ($card_w + $card_gap));
        $area = str_replace(["ONU", "UST", "SABIT"], ["ÖNÜ", "ÜST", "SABİT"], $m["area"] ?? "");
        $qty = get_qty($m);
        $qty_suffix = $qty > 1 ? " (x" . $qty . ")" : "";
        $circle_cx = $x + 4;
        $circle_cy = $card_y - 8;

        $svg[] = '<circle cx="' . $circle_cx . '" cy="' . $circle_cy . '" r="9" fill="#111111" stroke="#d6b87c" stroke-width="2"/>';
        $svg[] = svg_text($circle_cx, $circle_cy + 4, (string)($idx + 1), 10, "bold", "#d6b87c", "middle");
        $svg[] = svg_text($x + 22, $card_y, ($m["name"] ?? "MODÜL") . " - " . $area . $qty_suffix, $title_size, "bold", "#ffffff");
        $svg[] = svg_text($x, $card_y + 22, "Ölçü: " . fmt_m(get_num($m, "width", 0)) . " x " . fmt_m(get_num($m, "depth", 0)) . " m", $text_size, "normal", "#ffffff");
        $svg[] = svg_text($x, $card_y + 42, "Yükseklik: " . fmt_m(get_num($m, "height", 0)) . " m", $text_size, "normal", "#ffffff");
        $svg[] = svg_text($x, $card_y + 62, "Adet: " . $qty, $text_size, "normal", "#ffffff");

        $system_lines = wrap_text_svg("Sistem: " . ($m["system"] ?? ""), $count >= 5 ? 24 : 38, $max_lines);
        $sy = $card_y + 88;
        foreach ($system_lines as $line) {
            $svg[] = svg_text($x, $sy, $line, $system_size, "normal", "#ffffff");
            $sy += 18;
        }

        if ($idx < count($modules) - 1) {
            $sep_x = $x + $card_w + ($card_gap / 2);
            $svg[] = svg_line($sep_x, $info_y + 82, $sep_x, $info_y + 250, "#3f3f3f", 1);
        }
    }
}

// ---------------------------------------------------------
// 5. CEPHE DETAYLARI - DINAMIK GORSEL
// ---------------------------------------------------------

function image_to_base64_data_uri_php($image_path)
{
    $ext = strtolower(pathinfo($image_path, PATHINFO_EXTENSION));
    $mime = $ext === "jpg" || $ext === "jpeg" ? "image/jpeg" : ($ext === "webp" ? "image/webp" : "image/png");
    return "data:" . $mime . ";base64," . base64_encode(file_get_contents($image_path));
}

function svg_image_file_php($path, $x, $y, $w, $h, $mode = "meet")
{
    if (!file_exists($path)) {
        return svg_rect($x, $y, $w, $h, "#eeeeee", "#999999", 1) . svg_text($x + ($w / 2), $y + ($h / 2), "GÖRSEL YOK", 9, "bold", "#cc0000", "middle");
    }

    $uri = image_to_base64_data_uri_php($path);
    return '<image href="' . $uri . '" x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" preserveAspectRatio="xMidYMid ' . $mode . '"/>';
}

function get_facade_detail_assets_php()
{
    $base = __DIR__ . "/../assets/cephe_detaylari";
    return [
        "surme" => ["title" => "SÜRME CAM SİSTEMİ", "path" => $base . "/surme_cam.png"],
        "giyotin" => ["title" => "GİYOTİN CAM SİSTEMİ", "path" => $base . "/giyotin_cam.png"],
        "sabit" => ["title" => "SABİT CAM DOĞRAMA", "path" => $base . "/sabit_cam_dograma.png"],
        "sandvic" => ["title" => "SANDVİÇ PANEL KAPAMA", "path" => $base . "/sandvic_panel.png"],
        "bioklimatik" => ["title" => "BİOKLİMATİK PANEL", "path" => $base . "/bioklimatik_panel.png"],
    ];
}

function detect_facade_detail_types_php($project_data)
{
    $found = [];
    $add = function($key) use (&$found) { if (!in_array($key, $found, true)) $found[] = $key; };

    $scan = function($text) use (&$add) {
        $ascii = svg_norm($text);
        $raw = strtoupper((string)$text);
        if (strpos($ascii, "KATLANIR") !== false) return;
        if (strpos($ascii, "SURME") !== false) $add("surme");
        if (strpos($ascii, "GIYOTIN") !== false) $add("giyotin");
        if (strpos($ascii, "SABIT CAM") !== false || strpos($ascii, "SABIT DOGRAMA") !== false) $add("sabit");
        if (strpos($ascii, "SANDVIC") !== false || strpos($raw, "SANDVİÇ") !== false || strpos($ascii, "POLIURETAN") !== false) $add("sandvic");
        if (strpos($ascii, "BIOKLIMATIK") !== false || strpos($ascii, "BIYOKLIMATIK") !== false) $add("bioklimatik");
    };

    foreach ($project_data["modules"] ?? [] as $m) $scan(implode(" ", [$m["system"] ?? "", $m["raw_product"] ?? "", $m["area"] ?? "", $m["name"] ?? ""]));
    foreach ($project_data["sliding_glass"] ?? [] as $g) $scan(implode(" ", [$g["description"] ?? "", $g["area"] ?? "", $g["name"] ?? ""]));
    foreach ($project_data["panels"] ?? [] as $p) {
        $cat = $p["category"] ?? "";
        if ($cat === "option") continue;
        $scan(implode(" ", [$p["description"] ?? "", $p["area"] ?? "", $p["name"] ?? ""]));
    }

    return $found;
}

function draw_facade_details_python_like(&$svg, $project_data)
{
    $detail_x = 1260;
    $detail_y = 330;
    $detail_w = 405;
    $detail_h = 740;
    $assets = get_facade_detail_assets_php();
    $used = detect_facade_detail_types_php($project_data);
    $mode = project_mode_php($project_data);

    if ($mode === "facade_only") {
        $used = array_values(array_filter($used, fn($v) => $v !== "bioklimatik"));
    }

    $clean = [];
    foreach ($used as $key) if (!in_array($key, $clean, true)) $clean[] = $key;
    $used = array_slice($clean, 0, 3);
    if (!$used) return;

    $gap = 14;
    $layout_count = 3;
    $inner_x = $detail_x + 18;
    $inner_y = $detail_y + 42;
    $inner_w = $detail_w - 36;
    $inner_h = $detail_h - 62;
    $item_w = $inner_w;
    $item_h = ($inner_h - ($gap * ($layout_count - 1))) / $layout_count;

    foreach ($used as $i => $key) {
        if (!isset($assets[$key])) continue;
        $item = $assets[$key];
        $x = $inner_x;
        $y = $inner_y + ($i * ($item_h + $gap));
        $title = $item["title"];
        $svg[] = svg_text($x + ($item_w / 2), $y + 15, $title, 13, "bold", "#111111", "middle");
        $svg[] = svg_image_file_php($item["path"], $x, $y + 20, $item_w, $item_h - 20, "meet");
    }
}

// ---------------------------------------------------------
// 6. TEKNIK OZELLIKLER
// ---------------------------------------------------------

function draw_technical_features_python_like(&$svg, $project_data)
{
    $features = $project_data["technical_features"] ?? [];
    $tech_x = 1260;
    $tech_y = 110;
    $tech_w = 405;
    $tech_h = 205;

    $svg[] = svg_rect($tech_x, $tech_y, $tech_w, $tech_h, "#111111", "#111111", 2);
    $svg[] = svg_text($tech_x + 18, $tech_y + 35, "6. TEKNİK ÖZELLİKLER", 15, "bold", "#d6b87c");

    $ty = $tech_y + 62;
    foreach (array_slice($features, 0, 7) as $feature) {
        $svg[] = svg_text($tech_x + 18, $ty, "- " . $feature, 9, "normal", "#ffffff");
        $ty += 19;
    }
}

function section_box_svg(&$svg, $x, $y, $w, $h, $title, $scale_text = null, $dark = false)
{
    $fill = $dark ? "#111111" : "#ffffff";
    $text_color = $dark ? "#d6b87c" : "#111111";
    $svg[] = svg_rect($x, $y, $w, $h, $fill, "#111111", 2);
    $svg[] = svg_text($x + 14, $y + 25, $title, 15, "bold", $text_color);
    if ($scale_text) $svg[] = svg_text($x + 14, $y + 39, $scale_text, 10, "bold", $text_color);
}

// ---------------------------------------------------------
// ANA SVG URETIMI
// ---------------------------------------------------------

function generate_technical_svg($project_data, $output_path)
{
    $basic_info = $project_data["basic_info"] ?? [];
    $modules = $project_data["modules"] ?? [];
    $mode = project_mode_php($project_data);

    $width = 1680;
    $height = 1120;

    $approval_no = $basic_info["approval_no"] ?? ($basic_info["offer_no"] ?? "-");
    $approval_no = trim((string)$approval_no);
    $approval_no = preg_replace('/\s+/u', '', $approval_no);
    $approval_no = str_replace("REF-REF-", "REF-", $approval_no);
    $ref_text = ($approval_no && strpos($approval_no, "REF-") === 0) ? $approval_no : (($approval_no && $approval_no !== "-") ? "REF-" . $approval_no : "-");

    $date_range = $basic_info["date_range"] ?? "-";
    $customer_name = $basic_info["customer"] ?? "-";

    $svg = [];
    $svg[] = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg[] = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
    $svg[] = '<style>
        text {
            font-family: DejaVu Sans, Arial, sans-serif;
        }
    </style>';
    $svg[] = svg_rect(0, 0, $width, $height, "#111111", "#111111", 0);
    $svg[] = svg_rect(0, 0, $width, $height, "none", "#111111", 4);

    $header_h = 110;

    $svg[] = svg_rect(0, 0, $width, $header_h, "#111111", "#111111", 0);

    $bannerPath = __DIR__ . "/../assets/vertu_banner.png";

    $banner_x = 15;
    $banner_y = -30;
    $banner_draw_w = 675;
    $banner_logic_w = 620;
    $banner_h = 140;

    $svg[] = svg_image_stretch_php($bannerPath, $banner_x, $banner_y, $banner_draw_w, $banner_h);

    $right_info_w = 230;

    $title_left = $banner_x + $banner_logic_w;
    $title_right = $width - $right_info_w;
    $title_center_x = ($title_left + $title_right) / 2;

    $info_center_x = $width - ($right_info_w / 2);

    $svg[] = svg_text($title_center_x, 42, "TEKNİK KEŞİF ÇİZİMİ", 31, "bold", "#d6b87c", "middle");
    $svg[] = svg_text($title_center_x, 72, $ref_text, 21, "bold", "#ffffff", "middle");

    $svg[] = svg_text($info_center_x, 58, $date_range, 15, "bold", "#ffffff", "middle");
    $svg[] = svg_text($info_center_x, 80, $customer_name, 11, "bold", "#d6b87c", "middle");

    if ($mode === "roof_only") {
        $title_1 = "1. TAVAN ÖN GÖRÜNÜŞÜ";
        $title_2 = "2. TAVAN YAN GÖRÜNÜŞÜ";
        $title_3 = "3. TAVAN KESİTİ";
        $title_5 = "5. TAVAN DETAYLARI";
    } elseif ($mode === "roof+facade") {
        $title_1 = "1. ÖN GÖRÜNÜŞLER";
        $title_2 = "2. YAN GÖRÜNÜŞLER";
        $title_3 = "3. KESİTLER";
        $title_5 = "5. CEPHE DETAYLARI";
    } elseif ($mode === "facade_only") {
        $title_1 = "1. CEPHE ÖN GÖRÜNÜŞLERİ";
        $title_2 = "2. CEPHE YAN GÖRÜNÜŞLERİ";
        $title_3 = "3. CEPHE KESİTLERİ";
        $title_5 = "5. CEPHE DETAYLARI";
    } else {
        $title_1 = "1. ÖN GÖRÜNÜŞLER";
        $title_2 = "2. YAN GÖRÜNÜŞLER";
        $title_3 = "3. KESİTLER";
        $title_5 = "5. CEPHE DETAYLARI";
    }

    section_box_svg($svg, 15, 110, 1230, 225, $title_1, "ÖLÇEK: 1/100");
    section_box_svg($svg, 15, 345, 1230, 225, $title_2, "ÖLÇEK: 1/100");
    section_box_svg($svg, 15, 580, 1230, 270, $title_3, "ÖLÇEK: 1/100");
    section_box_svg($svg, 15, 865, 1230, 205, "4. MODÜL BİLGİLERİ", null, true);
    section_box_svg($svg, 1260, 110, 405, 205, "6. TEKNİK ÖZELLİKLER", null, true);
    section_box_svg($svg, 1260, 330, 405, 740, $title_5);

    draw_front_views_python_like($svg, $project_data);
    draw_side_views_python_like($svg, $project_data);
    draw_sections_python_like($svg, $project_data);
    draw_module_info_cards($svg, $project_data);
    draw_facade_details_python_like($svg, $project_data);
    draw_technical_features_python_like($svg, $project_data);

    $svg[] = svg_rect(0, 1085, $width, 35, "#111111", "#111111", 0);
    $svg[] = svg_text(35, 1108, "NOT: Tüm ölçüler keşif bilgilerine göre hazırlanmıştır. Uygulama öncesi yerinde kontrol edilmelidir.", 12, "normal", "#ffffff");
    $svg[] = svg_text(1420, 1108, "VERTU PREMIUM BIOKLIMATIK SİSTEMLER", 12, "normal", "#d6b87c");
    $svg[] = '</svg>';

    $content = implode("\n", $svg);
    if (!mb_check_encoding($content, "UTF-8")) {
        $content = mb_convert_encoding($content, "UTF-8", "auto");
    }


    $dir = dirname($output_path);
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    file_put_contents($output_path, $content);

    return $output_path;
}
