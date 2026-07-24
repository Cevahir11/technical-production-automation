<?php

function safe_text($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function product_title($type) {
    $titles = [
        'bioklimatik' => 'BİOKLİMATİK',
        'bioklimatik_sabit' => 'BİOKLİMATİK SABİT TAVAN',
        'pergola_tente' => 'PERGOLA / TENTE',
        'sandvic_panel_tavan' => 'SANDVİÇ PANEL TAVAN',
        'cam_tavan' => 'CAM TAVAN',
        'kompozit_tavan' => 'KOMPOZİT TAVAN',

        'surme_cam' => 'SÜRME CAM',
        'giyotin_cam' => 'GİYOTİN CAM',
        'sabit_cam' => 'SABİT CAM',
        'katlanir_cam' => 'KATLANIR CAM',
        'zip_perde' => 'ZİP PERDE',
        'sandvic_panel' => 'SANDVİÇ PANEL',
        'kompozit_kapama' => 'KOMPOZİT KAPAMA',

        'surme_kapi' => 'SÜRME KAPI',
        'katlanir_kapi' => 'KATLANIR KAPI',
        'tek_kanat_kapi' => 'TEK KANAT KAPI',
        'cift_kanat_kapi' => 'ÇİFT KANAT KAPI',
        'ozel' => 'ÖZEL ÜRÜN'
    ];

    return $titles[$type] ?? 'ÜRÜN';
}

function product_category_title($category) {
    $titles = [
        'tavan' => 'TAVAN',
        'cephe' => 'CEPHE',
        'kapi' => 'KAPI'
    ];

    return $titles[$category] ?? 'ÜRÜN';
}

function is_tavan_type($type) {
    return in_array($type, [
        'bioklimatik',
        'bioklimatik_sabit',
        'pergola_tente',
        'sandvic_panel_tavan',
        'cam_tavan',
        'kompozit_tavan'
    ], true);
}

function is_cam_type($type) {
    return in_array($type, [
        'surme_cam',
        'giyotin_cam',
        'sabit_cam',
        'katlanir_cam'
    ], true);
}

function is_kapi_type($type) {
    return in_array($type, [
        'surme_kapi',
        'katlanir_kapi',
        'tek_kanat_kapi',
        'cift_kanat_kapi'
    ], true);
}

function num_value($value) {
    if ($value === null || $value === '') {
        return '';
    }

    return preg_replace('/[^0-9.,]/', '', (string)$value);
}

function qty_value($product) {
    $qty = $product['quantity'] ?? 1;
    if ($qty === '' || $qty === null) {
        return 1;
    }

    return $qty;
}

function mm_to_number($value) {
    $value = num_value($value);

    if ($value === '') {
        return 0;
    }

    $value = str_replace('.', '', $value);
    $value = str_replace(',', '.', $value);

    return (float)$value;
}

function auto_glass_panel_count($widthMm) {
    if ($widthMm <= 2000) return 2;
    if ($widthMm <= 3000) return 3;
    if ($widthMm <= 4000) return 4;
    if ($widthMm <= 6500) return 6; // 3+3
    if ($widthMm <= 8000) return 8; // 4+4
    return 8;
}
function system_count_for_drawing($product) {
    $qty = (int) num_value($product['quantity'] ?? 1);

    if ($qty <= 0) {
        $qty = 1;
    }

    return min($qty, 8);
}

function dimension_label($product) {
    $type = $product['type'] ?? '';
    $w = num_value($product['width'] ?? '');
    $h = num_value($product['height'] ?? '');
    $d = num_value($product['depth'] ?? '');

    if (is_tavan_type($type)) {
        $parts = [];
        if ($w !== '') $parts[] = $w;
        if ($d !== '') $parts[] = $d;
        if ($h !== '') $parts[] = $h;
        return implode(' x ', $parts) . (count($parts) ? ' mm' : '');
    }

    $parts = [];
    if ($w !== '') $parts[] = $w;
    if ($h !== '') $parts[] = $h;
    return implode(' x ', $parts) . (count($parts) ? ' mm' : '');
}

function measure_top($x, $y, $w, $text, $color = '#ef4444') {
    if ($text === '' || $text === null) return '';

    $yy = $y - 14;
    $textY = $yy - 5;

    $s = '';
    $s .= '<line x1="'.$x.'" y1="'.$yy.'" x2="'.($x + $w).'" y2="'.$yy.'" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<line x1="'.$x.'" y1="'.($yy - 7).'" x2="'.$x.'" y2="'.($yy + 7).'" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<line x1="'.($x + $w).'" y1="'.($yy - 7).'" x2="'.($x + $w).'" y2="'.($yy + 7).'" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<text x="'.($x + $w / 2).'" y="'.$textY.'" text-anchor="middle" font-size="14" fill="'.$color.'" font-weight="900">'.safe_text($text).'</text>';

    return $s;
}

function measure_left($x, $y, $h, $text, $color = '#ef4444') {
    if ($text === '' || $text === null) return '';

    $xx = $x - 10;
    $textX = $xx - 7;
    $cy = $y + $h / 2;

    $s = '';
    $s .= '<line x1="'.$xx.'" y1="'.$y.'" x2="'.$xx.'" y2="'.($y + $h).'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<line x1="'.($xx - 5).'" y1="'.$y.'" x2="'.($xx + 5).'" y2="'.$y.'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<line x1="'.($xx - 5).'" y1="'.($y + $h).'" x2="'.($xx + 5).'" y2="'.($y + $h).'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<text x="'.$textX.'" y="'.$cy.'" text-anchor="middle" font-size="10" fill="'.$color.'" font-weight="900" transform="rotate(-90 '.$textX.' '.$cy.')">'.safe_text($text).'</text>';

    return $s;
}
function measure_right($x, $y, $h, $text, $color = '#f97316') {
    if ($text === '' || $text === null) return '';

    $xx = $x + 10;
    $textX = $xx + 7;
    $cy = $y + $h / 2;

    $s = '';
    $s .= '<line x1="'.$xx.'" y1="'.$y.'" x2="'.$xx.'" y2="'.($y + $h).'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<line x1="'.($xx - 5).'" y1="'.$y.'" x2="'.($xx + 5).'" y2="'.$y.'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<line x1="'.($xx - 5).'" y1="'.($y + $h).'" x2="'.($xx + 5).'" y2="'.($y + $h).'" stroke="'.$color.'" stroke-width="1.2"/>';
    $s .= '<text x="'.$textX.'" y="'.$cy.'" text-anchor="middle" font-size="10" fill="'.$color.'" font-weight="900" transform="rotate(90 '.$textX.' '.$cy.')">'.safe_text($text).'</text>';

    return $s;
}

function draw_arrow($x1, $y1, $x2, $y2, $color = '#ef4444') {
    $angle = atan2($y2 - $y1, $x2 - $x1);
    $head = 11;

    $p1x = $x2 - $head * cos($angle - pi() / 6);
    $p1y = $y2 - $head * sin($angle - pi() / 6);
    $p2x = $x2 - $head * cos($angle + pi() / 6);
    $p2y = $y2 - $head * sin($angle + pi() / 6);

    $s = '';
    $s .= '<line x1="'.$x1.'" y1="'.$y1.'" x2="'.$x2.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<polyline points="'.$p1x.','.$p1y.' '.$x2.','.$y2.' '.$p2x.','.$p2y.'" fill="none" stroke="'.$color.'" stroke-width="1.6"/>';

    return $s;
}

function draw_double_arrow_vertical($x, $y1, $y2, $color = '#ef4444') {
    $mid = ($y1 + $y2) / 2;
    $s = '';
    $s .= '<line x1="'.$x.'" y1="'.$y1.'" x2="'.$x.'" y2="'.$y2.'" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<polyline points="'.($x - 8).','.($mid - 18).' '.$x.','.($mid - 30).' '.($x + 8).','.($mid - 18).'" fill="none" stroke="'.$color.'" stroke-width="1.6"/>';
    $s .= '<polyline points="'.($x - 8).','.($mid + 18).' '.$x.','.($mid + 30).' '.($x + 8).','.($mid + 18).'" fill="none" stroke="'.$color.'" stroke-width="1.6"/>';

    return $s;
}

function draw_cut_list($cutList, $x, $y, $w, $h) {
    $s = '';
    $s .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#fff" stroke="#cbd5e1" stroke-width="1"/>';
    $s .= '<text x="'.($x + 8).'" y="'.($y + 16).'" font-size="11" font-weight="900" fill="#111827">KESİM / AYAR ÖZETİ</text>';

    $lineY = $y + 32;
    $maxLines = max(2, (int)floor(($h - 36) / 14));
    $count = 0;

    foreach ($cutList as $line) {
        if ($count >= $maxLines) {
            $s .= '<text x="'.($x + 8).'" y="'.$lineY.'" font-size="10" fill="#ef4444">Devamı ürün bilgilerinde...</text>';
            break;
        }

        $s .= '<text x="'.($x + 8).'" y="'.$lineY.'" font-size="10" fill="#111827">'.safe_text($line).'</text>';
        $lineY += 14;
        $count++;
    }

    return $s;
}

function cut_list_for_product($product) {
    $type = $product['type'] ?? '';
    $w = num_value($product['width'] ?? '');
    $h = num_value($product['height'] ?? '');
    $d = num_value($product['depth'] ?? '');
    $qty = qty_value($product);
    $caseRal = $product['case_ral'] ?? '';
    $color = $product['color'] ?? '';
    $glassType = $product['glass_type'] ?? '';

    $cuts = [];

    if (is_tavan_type($type)) {
        if ($w !== '') {
            $cuts[] = 'Ön kiriş: '.$w.' mm x 1';
            $cuts[] = 'Arka kiriş: '.$w.' mm x 1';
        }

        if ($d !== '') {
            $cuts[] = 'Yan kiriş: '.$d.' mm x 2';
        }

        $legHeight = num_value($product['leg_height'] ?? '');

        if ($legHeight !== '' && !empty($product['leg_count'])) {
            $cuts[] = 'Ayak: '.$legHeight.' mm x '.safe_text($product['leg_count']);
        } elseif ($legHeight !== '') {
            $cuts[] = 'Ayak yüksekliği: '.$legHeight.' mm';
        } elseif ($h !== '') {
            $cuts[] = 'Yükseklik: '.$h.' mm';
        }
        if (($product['middle_record'] ?? '') === 'Var') {
            $cuts[] = 'Orta kayıt: Var';
        }

        if (($product['has_frame'] ?? '') === 'Var') {
            $cuts[] = 'Karkas: Var';
        }

        if ($caseRal !== '') {
            $cuts[] = 'RAL: '.$caseRal;
        }

        return $cuts;
    }

    if ($type === 'zip_perde') {
        if ($w !== '') $cuts[] = 'Üst kutu: '.$w.' mm x 1';
        if ($h !== '') $cuts[] = 'Yan kılavuz: '.$h.' mm x 2';
        if ($w !== '' && $h !== '') $cuts[] = 'Kumaş: '.$w.' x '.$h.' mm';
        $cuts[] = 'Adet: '.$qty;
        if ($caseRal !== '') $cuts[] = 'Kasa RAL: '.$caseRal;
        if ($color !== '') $cuts[] = 'Kumaş/Renk: '.$color;

        return $cuts;
    }

    if (is_cam_type($type) || is_kapi_type($type) || in_array($type, ['sandvic_panel', 'kompozit_kapama'], true)) {
        if ($w !== '') {
            $cuts[] = 'Üst kasa: '.$w.' mm x 1';
            $cuts[] = 'Alt kasa/eşik: '.$w.' mm x 1';
        }

        if ($h !== '') {
            $cuts[] = 'Yan dikme: '.$h.' mm x 2';
        }

        if ($type === 'surme_cam' || $type === 'surme_kapi') {
            $cuts[] = 'Sürme kanat/modül: '.$qty.' adet';
        } elseif ($type === 'giyotin_cam') {
            $cuts[] = 'Giyotin modül: '.$qty.' adet';
        } elseif ($type === 'sabit_cam') {
            $cuts[] = 'Sabit cam: '.$qty.' adet';
        } elseif ($type === 'katlanir_cam' || $type === 'katlanir_kapi') {
            $cuts[] = 'Katlanır kanat: '.$qty.' adet';
        } else {
            $cuts[] = 'Adet: '.$qty;
        }

        if ($caseRal !== '') $cuts[] = 'Kasa RAL: '.$caseRal;
        if ($glassType !== '') $cuts[] = 'Cam: '.$glassType;
        if ($color !== '') $cuts[] = 'Renk: '.$color;

        return $cuts;
    }

    $cuts[] = 'Adet: '.$qty;
    return $cuts;
}

function draw_tavan_scheme($product, $x, $y, $w, $h) {
    $type = $product['type'] ?? '';
    $width = num_value($product['width'] ?? '');
    $depth = num_value($product['depth'] ?? '');
    $middle = $product['middle_record'] ?? '';
    
    $systemCount = system_count_for_drawing($product);

    $sx = $x + 38;
    $sy = $y + 28;
    $sw = max(80, $w - 76);
    $sh = max(50, $h - 56);

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $s .= measure_left($sx, $sy, $sh, $depth);
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$sh.'" fill="#f8fafc" stroke="#111827" stroke-width="2"/>';

    if ($type === 'pergola_tente') {
        for ($i = 1; $i < 7; $i++) {
            $yy = $sy + $i * $sh / 7;
            $s .= '<line x1="'.$sx.'" y1="'.$yy.'" x2="'.($sx + $sw).'" y2="'.$yy.'" stroke="#94a3b8" stroke-width="1"/>';
        }
        $s .= draw_arrow($sx + $sw / 2, $sy + 10, $sx + $sw / 2, $sy + $sh - 12);
        $s .= '<text x="'.($sx + $sw / 2).'" y="'.($sy + $sh / 2).'" text-anchor="middle" font-size="11" fill="#111827">PERGOLA / TENTE</text>';
    } else {
        $lamelCount = 8;
            for ($i = 1; $i < $lamelCount; $i++) {
                $yy = $sy + $i * $sh / $lamelCount;
                $s .= '<line x1="'.$sx.'" y1="'.$yy.'" x2="'.($sx + $sw).'" y2="'.$yy.'" stroke="#94a3b8" stroke-width="1.1"/>';
            }

        if ($middle === 'Var') {
            $mx = $sx + $sw / 2;
            $s .= '<line x1="'.$mx.'" y1="'.$sy.'" x2="'.$mx.'" y2="'.($sy + $sh).'" stroke="#ef4444" stroke-dasharray="5,4" stroke-width="1.5"/>';
            $s .= '<text x="'.($mx + 5).'" y="'.($sy + $sh - 6).'" font-size="9" fill="#ef4444">ORTA KAYIT</text>';
        }

        $leg = 7;
        $points = [
            [$sx, $sy],
            [$sx + $sw, $sy],
            [$sx, $sy + $sh],
            [$sx + $sw, $sy + $sh]
        ];

        foreach ($points as $p) {
            $s .= '<rect x="'.($p[0] - $leg / 2).'" y="'.($p[1] - $leg / 2).'" width="'.$leg.'" height="'.$leg.'" fill="#facc15" stroke="#111827" stroke-width="1"/>';
        }
    }
        // Adet / sistem bölmeleri
    if ($systemCount > 1) {
        for ($i = 1; $i < $systemCount; $i++) {
            $mx = $sx + ($i * $sw / $systemCount);
            $s .= '<line x1="'.$mx.'" y1="'.$sy.'" x2="'.$mx.'" y2="'.($sy + $sh).'" stroke="#111827" stroke-width="2.4"/>';
        }

        for ($i = 0; $i < $systemCount; $i++) {
            $tx = $sx + ($i * $sw / $systemCount) + ($sw / $systemCount / 2);
            $s .= '<text x="'.$tx.'" y="'.($sy + $sh - 10).'" text-anchor="middle" font-size="11" fill="#111827" font-weight="900">'.($i + 1).'.SİSTEM</text>';
        }
    }

    return $s;
}

function draw_surme_scheme($product, $x, $y, $w, $h) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');

    $widthMm = mm_to_number($width);
    $panels = auto_glass_panel_count($widthMm);

    $sx = $x + 24;
    $sy = $y + 22;
    $sw = max(80, $w - 48);
    $sh = max(50, $h - 42);
    $beamH = 12;

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);

    $topY = $sy;
    $clearTopY = $sy + $beamH;   // net yükseklik üst kasanın altından başlasın
    $bottomY = $sy + $sh;

    // SOL: YÜKSEKLİK
    $leftX = $sx - 14;
    $leftTextX = $leftX - 8;
    $leftMidY = ($clearTopY + $bottomY) / 2;

    $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="9" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">YÜKSEKLİK: '.safe_text($height).'</text>';

    // SAĞ: AYAK YÜKSEKLİĞİ
    if ($legHeight !== '') {
        $rightX = $sx + $sw + 14;
        $rightTextX = $rightX + 8;
        $rightMidY = ($topY + $bottomY) / 2;

        $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="9" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeight).'</text>';
    }

    // üst gri kasa
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$beamH.'" fill="#4b5563" stroke="#111827" stroke-width="1.2"/>';

    $bodyY = $sy + $beamH;
    $bodyH = $sh - $beamH;

    // dış çerçeve
    $s .= '<rect x="'.$sx.'" y="'.$bodyY.'" width="'.$sw.'" height="'.$bodyH.'" fill="#f8fafc" stroke="#111827" stroke-width="1.6"/>';

    // panel çizgileri
    for ($i = 1; $i < $panels; $i++) {
        $px = $sx + ($i * $sw / $panels);

        // 3+3 veya 4+4 orta ayrımı daha kalın olsun
        $strokeW = 1.1;
        if (($panels === 6 || $panels === 8) && $i === ($panels / 2)) {
            $strokeW = 2.4;
        }

        $s .= '<line x1="'.$px.'" y1="'.$bodyY.'" x2="'.$px.'" y2="'.($bodyY + $bodyH).'" stroke="#64748b" stroke-width="'.$strokeW.'"/>';
    }

    // hareket okları
    for ($i = 0; $i < $panels; $i++) {
        $cx = $sx + ($i * $sw / $panels) + ($sw / $panels / 2);
        $cy = $bodyY + ($bodyH / 2);

        // sola bakan küçük ok
        $s .= '<polyline points="'.($cx + 10).','.($cy - 10).' '.($cx - 8).','.$cy.' '.($cx + 10).','.($cy + 10).'" fill="none" stroke="#f87171" stroke-width="1.2"/>';
    }

    return $s;
}

function draw_giyotin_scheme($product, $x, $y, $w, $h) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');
    $systemCount = system_count_for_drawing($product);

    if ($systemCount < 1) $systemCount = 1;

    $sx = $x + 24;
    $sy = $y + 22;
    $sw = max(80, $w - 48);
    $sh = max(50, $h - 42);
    $beamH = 12;

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $topY = $sy;
    $clearTopY = $sy + $beamH;   // net yükseklik üst kasanın altından başlasın
    $bottomY = $sy + $sh;

    // SOL: YÜKSEKLİK
    $leftX = $sx - 14;
    $leftTextX = $leftX - 8;
    $leftMidY = ($clearTopY + $bottomY) / 2;

    $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="9" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">YÜKSEKLİK: '.safe_text($height).'</text>';

    // SAĞ: AYAK YÜKSEKLİĞİ
    if ($legHeight !== '') {
        $rightX = $sx + $sw + 14;
        $rightTextX = $rightX + 8;
        $rightMidY = ($topY + $bottomY) / 2;

        $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="9" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeight).'</text>';
    }
    // üst gri hatıl
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$beamH.'" fill="#4b5563" stroke="#111827" stroke-width="1.2"/>';

    $moduleW = $sw / $systemCount;

    for ($i = 0; $i < $systemCount; $i++) {
        $mx = $sx + ($i * $moduleW);
        $postW = 6;

        $innerX = $mx + $postW;
        $innerY = $sy + $beamH + 4;
        $innerW = $moduleW - ($postW * 2);
        $innerH = $sh - $beamH - 8;

        // yan gri dikmeler
        $s .= '<rect x="'.$mx.'" y="'.($sy + $beamH).'" width="'.$postW.'" height="'.($sh - $beamH).'" fill="#9ca3af" stroke="#111827" stroke-width="0.8"/>';
        $s .= '<rect x="'.($mx + $moduleW - $postW).'" y="'.($sy + $beamH).'" width="'.$postW.'" height="'.($sh - $beamH).'" fill="#9ca3af" stroke="#111827" stroke-width="0.8"/>';

        // iç cam alanı
        $s .= '<rect x="'.$innerX.'" y="'.$innerY.'" width="'.$innerW.'" height="'.$innerH.'" fill="#f8fafc" stroke="#111827" stroke-width="0.9"/>';

        // 3 yatay bölme
        $line1 = $innerY + ($innerH / 3);
        $line2 = $innerY + (2 * $innerH / 3);
        $s .= '<line x1="'.$innerX.'" y1="'.$line1.'" x2="'.($innerX + $innerW).'" y2="'.$line1.'" stroke="#94a3b8" stroke-width="1"/>';
        $s .= '<line x1="'.$innerX.'" y1="'.$line2.'" x2="'.($innerX + $innerW).'" y2="'.$line2.'" stroke="#94a3b8" stroke-width="1"/>';

        // aşağı ok
        $cx = $innerX + ($innerW / 2);
        $topY = $innerY + 10;
        $bottomY = $innerY + $innerH - 14;

        $s .= '<line x1="'.$cx.'" y1="'.$topY.'" x2="'.$cx.'" y2="'.$bottomY.'" stroke="#f87171" stroke-width="1.2"/>';
        $s .= '<line x1="'.($cx - 8).'" y1="'.($bottomY - 10).'" x2="'.$cx.'" y2="'.$bottomY.'" stroke="#f87171" stroke-width="1.2"/>';
        $s .= '<line x1="'.($cx + 8).'" y1="'.($bottomY - 10).'" x2="'.$cx.'" y2="'.$bottomY.'" stroke="#f87171" stroke-width="1.2"/>';
    }

    return $s;
}

function draw_sabit_scheme($product, $x, $y, $w, $h) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');
    $systemCount = system_count_for_drawing($product);

    if ($systemCount < 1) $systemCount = 1;

    $sx = $x + 24;
    $sy = $y + 22;
    $sw = max(80, $w - 48);
    $sh = max(50, $h - 42);
    $beamH = 12;

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $topY = $sy;
    $clearTopY = $sy + $beamH;   // net yükseklik üst kasanın altından başlasın
    $bottomY = $sy + $sh;

    // SOL: YÜKSEKLİK
    $leftX = $sx - 14;
    $leftTextX = $leftX - 8;
    $leftMidY = ($clearTopY + $bottomY) / 2;

    $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="9" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">YÜKSEKLİK: '.safe_text($height).'</text>';

    // SAĞ: AYAK YÜKSEKLİĞİ
    if ($legHeight !== '') {
        $rightX = $sx + $sw + 14;
        $rightTextX = $rightX + 8;
        $rightMidY = ($topY + $bottomY) / 2;

        $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="9" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeight).'</text>';
    }

    // üst gri hatıl
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$beamH.'" fill="#4b5563" stroke="#111827" stroke-width="1.2"/>';

    $moduleW = $sw / $systemCount;

    for ($i = 0; $i < $systemCount; $i++) {
        $mx = $sx + ($i * $moduleW);
        $postW = 6;

        $innerX = $mx + $postW;
        $innerY = $sy + $beamH + 4;
        $innerW = $moduleW - ($postW * 2);
        $innerH = $sh - $beamH - 8;

        // yan gri dikmeler
        $s .= '<rect x="'.$mx.'" y="'.($sy + $beamH).'" width="'.$postW.'" height="'.($sh - $beamH).'" fill="#9ca3af" stroke="#111827" stroke-width="0.8"/>';
        $s .= '<rect x="'.($mx + $moduleW - $postW).'" y="'.($sy + $beamH).'" width="'.$postW.'" height="'.($sh - $beamH).'" fill="#9ca3af" stroke="#111827" stroke-width="0.8"/>';

        // iç cam
        $s .= '<rect x="'.$innerX.'" y="'.$innerY.'" width="'.$innerW.'" height="'.$innerH.'" fill="#f8fafc" stroke="#111827" stroke-width="0.9"/>';

        // sabit işareti
        $s .= '<line x1="'.$innerX.'" y1="'.$innerY.'" x2="'.($innerX + $innerW).'" y2="'.($innerY + $innerH).'" stroke="#cbd5e1" stroke-width="1.1"/>';
    }

    return $s;
}

function draw_zip_scheme($product, $x, $y, $w, $h) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');

    $sx = $x + 38;
    $sy = $y + 22;
    $sw = max(80, $w - 76);
    $sh = max(50, $h - 48);
    $boxH = max(14, $sh * 0.18);

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $topY = $sy;
    $clearTopY = $sy + $boxH;   // zip perdede net yükseklik kutunun altından başlasın
    $bottomY = $sy + $sh;

    // SOL: YÜKSEKLİK
    $leftX = $sx - 14;
    $leftTextX = $leftX - 8;
    $leftMidY = ($clearTopY + $bottomY) / 2;

    $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="9" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">YÜKSEKLİK: '.safe_text($height).'</text>';

    // SAĞ: AYAK YÜKSEKLİĞİ
    if ($legHeight !== '') {
        $rightX = $sx + $sw + 14;
        $rightTextX = $rightX + 8;
        $rightMidY = ($topY + $bottomY) / 2;

        $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="9" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeight).'</text>';
    }
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$boxH.'" fill="#e5e7eb" stroke="#111827" stroke-width="1.6"/>';
    $s .= '<rect x="'.$sx.'" y="'.($sy + $boxH).'" width="'.$sw.'" height="'.($sh - $boxH).'" fill="#fff" stroke="#111827" stroke-width="1.6"/>';
    $s .= '<line x1="'.($sx + 8).'" y1="'.($sy + $boxH).'" x2="'.($sx + 8).'" y2="'.($sy + $sh).'" stroke="#111827" stroke-width="1.8"/>';
    $s .= '<line x1="'.($sx + $sw - 8).'" y1="'.($sy + $boxH).'" x2="'.($sx + $sw - 8).'" y2="'.($sy + $sh).'" stroke="#111827" stroke-width="1.8"/>';
    $s .= draw_arrow($sx + $sw / 2, $sy + $boxH + 12, $sx + $sw / 2, $sy + $sh - 12);

    return $s;
}

function draw_kapi_scheme($product, $x, $y, $w, $h) {
    $type = $product['type'] ?? '';
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');

    if ($type === 'surme_kapi') {
        return draw_surme_scheme($product, $x, $y, $w, $h);
    }

    if ($type === 'katlanir_kapi') {
        $sx = $x + 32;
        $sy = $y + 24;
        $sw = max(80, $w - 64);
        $sh = max(50, $h - 48);
        $beamH = 12;

        $s = '';
        $s .= measure_top($sx, $sy, $sw, $width);

        $topY = $sy;
        $clearTopY = $sy + $beamH;
        $bottomY = $sy + $sh;

        // SOL: YÜKSEKLİK
        $leftX = $sx - 14;
        $leftTextX = $leftX - 8;
        $leftMidY = ($clearTopY + $bottomY) / 2;

        $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
        $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
        $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
        $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="11" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">Y: '.safe_text($height).'</text>';

        // SAĞ: AYAK YÜKSEKLİĞİ
        if ($legHeight !== '') {
            $rightX = $sx + $sw + 14;
            $rightTextX = $rightX + 8;
            $rightMidY = ($topY + $bottomY) / 2;

            $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
            $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
            $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
            $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="11" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK: '.safe_text($legHeight).'</text>';
        }

        // Üst kasa
        $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$beamH.'" fill="#4b5563" stroke="#111827" stroke-width="1.2"/>';

        $bodyY = $sy + $beamH;
        $bodyH = $sh - $beamH;

        $s .= '<rect x="'.$sx.'" y="'.$bodyY.'" width="'.$sw.'" height="'.$bodyH.'" fill="#fff" stroke="#111827" stroke-width="2"/>';

        $widthMm = mm_to_number($width);
        $panels = auto_glass_panel_count($widthMm);

        for ($i = 1; $i < $panels; $i++) {
            $px = $sx + $i * $sw / $panels;

            $strokeW = 1.1;
            if (($panels === 6 || $panels === 8) && $i === ($panels / 2)) {
                $strokeW = 2.4;
            }

            $s .= '<line x1="'.$px.'" y1="'.$bodyY.'" x2="'.$px.'" y2="'.($bodyY + $bodyH).'" stroke="#64748b" stroke-width="'.$strokeW.'"/>';
        }

        $points = [];
        for ($i = 0; $i <= $panels; $i++) {
            $px = $sx + $i * $sw / $panels;
            $py = ($i % 2 === 0) ? $bodyY + 12 : $bodyY + $bodyH - 12;
            $points[] = $px.','.$py;
        }

        $s .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#ef4444" stroke-width="1.5"/>';

        return $s;
    }

    $sx = $x + 38;
    $sy = $y + 28;
    $sw = max(80, $w - 76);
    $sh = max(50, $h - 56);

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $s .= measure_left($sx, $sy, $sh, $height);
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$sh.'" fill="#fff" stroke="#111827" stroke-width="2"/>';

    if ($type === 'cift_kanat_kapi') {
        $s .= '<line x1="'.($sx + $sw / 2).'" y1="'.$sy.'" x2="'.($sx + $sw / 2).'" y2="'.($sy + $sh).'" stroke="#111827" stroke-width="1.4"/>';
        $s .= '<path d="M '.($sx + $sw / 2).' '.($sy + $sh).' Q '.($sx + $sw * 0.78).' '.($sy + $sh * 0.70).' '.($sx + $sw - 8).' '.($sy + $sh).'" fill="none" stroke="#ef4444" stroke-width="1.4"/>';
        $s .= '<path d="M '.($sx + $sw / 2).' '.($sy + $sh).' Q '.($sx + $sw * 0.22).' '.($sy + $sh * 0.70).' '.($sx + 8).' '.($sy + $sh).'" fill="none" stroke="#ef4444" stroke-width="1.4"/>';
    } else {
        $s .= '<path d="M '.$sx.' '.($sy + $sh).' Q '.($sx + $sw * 0.55).' '.($sy + $sh * 0.65).' '.($sx + $sw - 8).' '.($sy + $sh).'" fill="none" stroke="#ef4444" stroke-width="1.4"/>';
    }

    return $s;
}

function draw_panel_scheme($product, $x, $y, $w, $h) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');

    $sx = $x + 38;
    $sy = $y + 28;
    $sw = max(80, $w - 76);
    $sh = max(50, $h - 56);

    $s = '';
    $s .= measure_top($sx, $sy, $sw, $width);
    $topY = $sy;
    $clearTopY = $sy;   // panelde üst kasa olmadığı için net yükseklik en üstten başlasın
    $bottomY = $sy + $sh;

    // SOL: YÜKSEKLİK
    $leftX = $sx - 14;
    $leftTextX = $leftX - 8;
    $leftMidY = ($clearTopY + $bottomY) / 2;

    $s .= '<line x1="'.$leftX.'" y1="'.$clearTopY.'" x2="'.$leftX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$clearTopY.'" x2="'.($leftX + 5).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<line x1="'.($leftX - 5).'" y1="'.$bottomY.'" x2="'.($leftX + 5).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="1.2"/>';
    $s .= '<text x="'.$leftTextX.'" y="'.$leftMidY.'" text-anchor="middle" font-size="11" fill="#ef4444" font-weight="900" transform="rotate(-90 '.$leftTextX.' '.$leftMidY.')">YÜKSEKLİK: '.safe_text($height).'</text>';

    // SAĞ: AYAK YÜKSEKLİĞİ
    if ($legHeight !== '') {
        $rightX = $sx + $sw + 14;
        $rightTextX = $rightX + 8;
        $rightMidY = ($topY + $bottomY) / 2;

        $s .= '<line x1="'.$rightX.'" y1="'.$topY.'" x2="'.$rightX.'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$topY.'" x2="'.($rightX + 5).'" y2="'.$topY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<line x1="'.($rightX - 5).'" y1="'.$bottomY.'" x2="'.($rightX + 5).'" y2="'.$bottomY.'" stroke="#f97316" stroke-width="1.2"/>';
        $s .= '<text x="'.$rightTextX.'" y="'.$rightMidY.'" text-anchor="middle" font-size="11" fill="#f97316" font-weight="900" transform="rotate(90 '.$rightTextX.' '.$rightMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeight).'</text>';
    }
    $s .= '<rect x="'.$sx.'" y="'.$sy.'" width="'.$sw.'" height="'.$sh.'" fill="#f8fafc" stroke="#111827" stroke-width="2"/>';

    for ($i = 1; $i < 6; $i++) {
        $yy = $sy + $i * $sh / 6;
        $s .= '<line x1="'.$sx.'" y1="'.$yy.'" x2="'.($sx + $sw).'" y2="'.$yy.'" stroke="#94a3b8" stroke-width="1"/>';
    }

    return $s;
}

function draw_product_scheme($product, $x, $y, $w, $h) {
    $type = $product['type'] ?? '';

    if (is_tavan_type($type)) {
        return draw_tavan_scheme($product, $x, $y, $w, $h);
    }

    if ($type === 'surme_cam') {
        return draw_surme_scheme($product, $x, $y, $w, $h);
    }

    if ($type === 'giyotin_cam') {
        return draw_giyotin_scheme($product, $x, $y, $w, $h);
    }

    if ($type === 'sabit_cam') {
        return draw_sabit_scheme($product, $x, $y, $w, $h);
    }

    if ($type === 'katlanir_cam') {
        return draw_kapi_scheme(['type' => 'katlanir_kapi'] + $product, $x, $y, $w, $h);
    }

    if ($type === 'zip_perde') {
        return draw_zip_scheme($product, $x, $y, $w, $h);
    }

    if (is_kapi_type($type)) {
        return draw_kapi_scheme($product, $x, $y, $w, $h);
    }

    if (in_array($type, ['sandvic_panel', 'kompozit_kapama'], true)) {
        return draw_panel_scheme($product, $x, $y, $w, $h);
    }

    $s = '';
    $s .= '<rect x="'.($x + 35).'" y="'.($y + 25).'" width="'.($w - 70).'" height="'.($h - 50).'" fill="#fff" stroke="#111827" stroke-width="2"/>';
    $s .= '<text x="'.($x + $w / 2).'" y="'.($y + $h / 2).'" text-anchor="middle" font-size="13" fill="#111827">'.safe_text(product_title($type)).'</text>';
    

    return $s;
}

function draw_product_block($product, $x, $y, $w, $h) {
    $title = product_title($product['type'] ?? '');
    $category = product_category_title($product['category'] ?? '');
    $side = $product['side'] ?? '';
    $qty = qty_value($product);
    $dim = dimension_label($product);
    $headerH = 34;  
    $schemeTop = 58;
    $schemeH = $h - $schemeTop - 8;

    $s = '';
    $s .= '<g transform="translate('.$x.','.$y.')">';
    $s .= '<rect x="0" y="0" width="'.$w.'" height="'.$h.'" fill="#f8fafc" stroke="#111827" stroke-width="1.4"/>';
    $s .= '<rect x="0" y="0" width="'.$w.'" height="'.$headerH.'" fill="#111827" stroke="#111827" stroke-width="1"/>';

    $headerTitle = $title;
    if ($side !== '') {
        $headerTitle .= ' / '.$side;
    }

    $s .= '<text x="10" y="22" font-size="13" fill="#fff" font-weight="900">'.safe_text($headerTitle).'</text>';
    $s .= '<text x="'.($w - 10).'" y="22" text-anchor="end" font-size="10" fill="#e5e7eb">'.safe_text($category).'</text>';

    $meta = trim(($dim !== '' ? $dim : '') . ($qty !== '' ? ' / '.$qty.' adet' : ''));
    if ($meta !== '') {
        $s .= '<text x="10" y="48" font-size="10" fill="#111827" font-weight="900">'.safe_text($meta).'</text>';
    }

    $s .= draw_product_scheme($product, 8, $schemeTop, $w - 16, $schemeH);

    $s .= '</g>';

    return $s;
}

function expand_products_for_drawing($products) {
    $expanded = [];

    foreach ($products as $product) {
        if (empty($product['type'])) {
            continue;
        }

        if (!empty($product['items']) && is_array($product['items'])) {
            foreach ($product['items'] as $item) {
                if (empty($item['width']) || empty($item['height'])) {
                    continue;
                }

                $newProduct = $product;
                unset($newProduct['items']);

                $newProduct['width'] = $item['width'] ?? '';
                $newProduct['height'] = $item['height'] ?? '';
                $newProduct['leg_height'] = $item['leg_height'] ?? '';
                $newProduct['quantity'] = $item['quantity'] ?? 1;

                $expanded[] = $newProduct;
            }
        } else {
            $expanded[] = $product;
        }
    }

    return $expanded;
}

function get_havuz_tipi_leg_points($x, $y, $w, $h, $legCount) {
    $count = (int) num_value($legCount);

    if ($count <= 0) {
        $count = 4;
    }

    $count = max(2, min(8, $count));

    $frontLeft   = [$x, $y + $h];
    $frontMiddle = [$x + ($w / 2), $y + $h];
    $frontRight  = [$x + $w, $y + $h];

    $backLeft    = [$x, $y];
    $backMiddle  = [$x + ($w / 2), $y];
    $backRight   = [$x + $w, $y];

    $leftMiddle  = [$x, $y + ($h / 2)];
    $rightMiddle = [$x + $w, $y + ($h / 2)];

    if ($count === 2) {
        return [$frontLeft, $frontRight];
    }

    if ($count === 3) {
        return [$frontLeft, $frontMiddle, $frontRight];
    }

    if ($count === 4) {
        return [$backLeft, $backRight, $frontLeft, $frontRight];
    }

    if ($count === 5) {
        return [$backLeft, $backRight, $frontLeft, $frontMiddle, $frontRight];
    }

    if ($count === 6) {
        return [$backLeft, $backMiddle, $backRight, $frontLeft, $frontMiddle, $frontRight];
    }

    if ($count === 7) {
        return [$backLeft, $backMiddle, $backRight, $frontLeft, $frontMiddle, $frontRight, $leftMiddle];
    }

    return [$backLeft, $backMiddle, $backRight, $frontLeft, $frontMiddle, $frontRight, $leftMiddle, $rightMiddle];
}
function draw_auto_tavan_plan_svg($product) {
    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $depth = num_value($product['depth'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');
    $legCount = $product['leg_count'] ?? '';
    $type = $product['type'] ?? '';
    $middleRecord = $product['middle_record'] ?? '';
    $systemType = $product['system_type'] ?? '';

    $systemCount = system_count_for_drawing($product);

    $widthText = $width !== '' ? $width . ' mm' : '-';
    $heightText = $height !== '' ? $height . ' mm' : '-';
    $depthText = $depth !== '' ? $depth . ' mm' : '-';
    $legHeightText = $legHeight !== '' ? $legHeight . ' mm' : '-';

    $title = product_title($type);

    $svg = '';
    $svg .= '<svg width="1000" height="700" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="1000" height="700" fill="#ffffff"/>';

    // Grid
    for ($gx = 0; $gx <= 1000; $gx += 25) {
        $stroke = ($gx % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="'.$gx.'" y1="0" x2="'.$gx.'" y2="700" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    for ($gy = 0; $gy <= 700; $gy += 25) {
        $stroke = ($gy % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="0" y1="'.$gy.'" x2="1000" y2="'.$gy.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    $svg .= '<rect x="22" y="22" width="956" height="656" fill="none" stroke="#111827" stroke-width="2"/>';
    $svg .= '<text x="500" y="58" text-anchor="middle" font-size="28" fill="#111827" font-weight="900">OTOMATİK TAVAN PLANI</text>';

    // Tavan üst görünüş alanı
    $x = 150;
    $y = 120;
    $w = 620;
    $h = 360;

    $svg .= '<text x="'.($x + $w / 2).'" y="'.($y - 34).'" text-anchor="middle" font-size="20" fill="#111827" font-weight="900">ÜST GÖRÜNÜŞ / TAVAN</text>';

    // Ana tavan dikdörtgeni
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#f8fafc" stroke="#111827" stroke-width="4"/>';
    // Adet / sistem bölmeleri
    if ($systemCount > 1) {
        for ($i = 1; $i < $systemCount; $i++) {
            $mx = $x + ($i * $w / $systemCount);
            $svg .= '<line x1="'.$mx.'" y1="'.$y.'" x2="'.$mx.'" y2="'.($y + $h).'" stroke="#111827" stroke-width="4"/>';
        }

        for ($i = 0; $i < $systemCount; $i++) {
            $tx = $x + ($i * $w / $systemCount) + ($w / $systemCount / 2);
            $svg .= '<text x="'.$tx.'" y="'.($y + $h - 24).'" text-anchor="middle" font-size="18" fill="#111827" font-weight="900">'.($i + 1).'.SİSTEM</text>';
        }
    }

    // Orta kayıt sadece kullanıcı "Var" seçerse çizilir
    if ($middleRecord === 'Var') {
        $middleX = $x + ($w / 2);

        $svg .= '<line x1="'.$middleX.'" y1="'.$y.'" x2="'.$middleX.'" y2="'.($y + $h).'" stroke="#111827" stroke-width="5"/>';
        $svg .= '<text x="'.($middleX + 12).'" y="'.($y + $h - 16).'" font-size="14" fill="#111827" font-weight="900">ORTA KAYIT</text>';
    }

    // Önden arkaya yön okları - daha kısa ve daha soft pembe
    $arrowLeftX = $x + ($w * 0.25);
    $arrowRightX = $x + ($w * 0.75);
    $arrowStartY = $y + $h - 90;
    $arrowEndY = $y + 70;
    $arrowColor = '#f9a8d4'; // soft pembe

    foreach ([$arrowLeftX, $arrowRightX] as $arrowX) {
        $svg .= '<line x1="'.$arrowX.'" y1="'.$arrowStartY.'" x2="'.$arrowX.'" y2="'.$arrowEndY.'" stroke="'.$arrowColor.'" stroke-width="2"/>';

        $svg .= '<polygon points="'
            .$arrowX.','.($arrowEndY - 8).' '
            .($arrowX - 7).','.($arrowEndY + 7).' '
            .($arrowX + 7).','.($arrowEndY + 7).'"
            fill="'.$arrowColor.'"/>';
    }

    // Köşe ayak noktaları
    $leg = 12;

    if ($systemType === 'Havuz Tipi') {
        $points = get_havuz_tipi_leg_points($x, $y, $w, $h, $legCount);
    } else {
        // Duvar tipi / tilt / sabit için şimdilik eski 4 köşe mantığı
        $points = [
            [$x, $y],
            [$x + $w, $y],
            [$x, $y + $h],
            [$x + $w, $y + $h]
        ];
    }

    foreach ($points as $p) {
        $svg .= '<rect x="'.($p[0] - $leg / 2).'" y="'.($p[1] - $leg / 2).'" width="'.$leg.'" height="'.$leg.'" fill="#facc15" stroke="#111827" stroke-width="1.5"/>';
    }

    // Genişlik ölçüsü
    $measureY = $y + $h + 52;
    $svg .= '<line x1="'.$x.'" y1="'.$measureY.'" x2="'.($x + $w).'" y2="'.$measureY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.$x.'" y1="'.($measureY - 10).'" x2="'.$x.'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($x + $w).'" y1="'.($measureY - 10).'" x2="'.($x + $w).'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($x + $w / 2).'" y="'.($measureY + 30).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900">GENİŞLİK: '.safe_text($widthText).'</text>';

    // Derinlik ölçüsü
    $measureX = $x - 56;
    $svg .= '<line x1="'.$measureX.'" y1="'.$y.'" x2="'.$measureX.'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.$y.'" x2="'.($measureX + 10).'" y2="'.$y.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.($y + $h).'" x2="'.($measureX + 10).'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($measureX - 26).'" y="'.($y + $h / 2).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900" transform="rotate(-90 '.($measureX - 26).' '.($y + $h / 2).')">DERİNLİK: '.safe_text($depthText).'</text>';

    
    // Alt bilgi kutusu
    $svg .= '<rect x="70" y="575" width="860" height="72" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>';
    $svg .= '<text x="92" y="603" font-size="18" fill="#111827" font-weight="900">GİRİLEN TAVAN ÖLÇÜLERİ</text>';
    $svg .= '<text x="92" y="630" font-size="15" fill="#111827">'
        .'Ürün: '.safe_text($title)
        .'   |   Genişlik: '.safe_text($widthText)
        .'   |   Sistem: '.safe_text($systemType ?: '-')
        .'   |   Derinlik: '.safe_text($depthText)
        .'   |   Yükseklik: '.safe_text($heightText)
        .'</text>';

    $svg .= '<text x="92" y="650" font-size="14" fill="#475569">'
        .'Ayak Yüksekliği: '.safe_text($legHeightText)
        .'   |   Ayak Sayısı: '.safe_text($legCount ?: '-')
        .'   |   Orta Kayıt: '.safe_text($middleRecord ?: '-')
        .'</text>';

    $svg .= '</svg>';

    return $svg;
}
function draw_auto_cephe_svg($product) {
    $type = $product['type'] ?? '';
    $side = $product['side'] ?? '';

    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $legHeight = num_value($product['leg_height'] ?? '');
    $qty = qty_value($product);

    $widthMm = mm_to_number($width);
    $panelCount = auto_glass_panel_count($widthMm);

    $systemCount = system_count_for_drawing($product);
    if ($systemCount < 1) {
        $systemCount = 1;
    }
    $caseRal = $product['case_ral'] ?? '';
    $glassType = $product['glass_type'] ?? '';
    $color = $product['color'] ?? '';
    $note = $product['note'] ?? '';

    $widthText = $width !== '' ? $width . ' mm' : '-';
    $heightText = $height !== '' ? $height . ' mm' : '-';
    $legHeightText = $legHeight !== '' ? $legHeight . ' mm' : '-';
    $title = product_title($type);

    $svg = '';
    $svg .= '<svg width="1000" height="700" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="1000" height="700" fill="#ffffff"/>';

    // Grid
    for ($gx = 0; $gx <= 1000; $gx += 25) {
        $stroke = ($gx % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="'.$gx.'" y1="0" x2="'.$gx.'" y2="700" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    for ($gy = 0; $gy <= 700; $gy += 25) {
        $stroke = ($gy % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="0" y1="'.$gy.'" x2="1000" y2="'.$gy.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    $svg .= '<rect x="22" y="22" width="956" height="656" fill="none" stroke="#111827" stroke-width="2"/>';
    $svg .= '<text x="500" y="58" text-anchor="middle" font-size="28" fill="#111827" font-weight="900">OTOMATİK CEPHE ÇİZİMİ</text>';

    $x = 145;
    $y = 125;
    $w = 620;
    $h = 340;

    $topProfileH = 18;

    // Tüm cephelerde normal yükseklik: yerden tavana kadar
    $clearTopY = $y + $topProfileH;

    // Ayak yüksekliği: en alttan en üste toplam ölçü
    $totalTopY = $y;

    $bottomY = $y + $h;

    $subTitle = 'ÖN GÖRÜNÜŞ / CEPHE';
    if ($side !== '') {
        $subTitle = strtoupper($side) . ' / CEPHE';
    }

    $svg .= '<text x="'.($x + $w / 2).'" y="'.($y - 34).'" text-anchor="middle" font-size="20" fill="#111827" font-weight="900">'.safe_text($subTitle).'</text>';

    // Ana cephe çerçevesi
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#ffffff" stroke="#111827" stroke-width="4"/>';

    // Ürün tipine göre cephe çizimi
    // Ürün tipine göre cephe çizimi
    if ($type === 'surme_cam' || $type === 'katlanir_cam') {
        $beamH = 18;
        $bodyY = $y + $beamH;
        $bodyH = $h - $beamH - 34;

        // Üst hatıl / kasa
        $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$beamH.'" fill="#3f3f46" stroke="#111827" stroke-width="1.5"/>';

        // Ana cam alanı
        $svg .= '<rect x="'.$x.'" y="'.$bodyY.'" width="'.$w.'" height="'.$bodyH.'" fill="#ffffff" stroke="#111827" stroke-width="2"/>';

        // Cam bölmeleri: normal cam araları ince, sadece 3+3 / 4+4 ortası kalın
        for ($i = 1; $i < $panelCount; $i++) {
            $px = $x + ($i * $w / $panelCount);

            $isGroupMiddle = (($panelCount === 6 || $panelCount === 8) && $i === ($panelCount / 2));

            $strokeW = $isGroupMiddle ? 5 : 1.2;
            $strokeColor = $isGroupMiddle ? '#111827' : '#94a3b8';

            $svg .= '<line x1="'.$px.'" y1="'.$bodyY.'" x2="'.$px.'" y2="'.($bodyY + $bodyH).'" stroke="'.$strokeColor.'" stroke-width="'.$strokeW.'"/>';
        }

        if ($panelCount === 6) {
            $panelLabel = '3+3 CAM';
        } elseif ($panelCount === 8) {
            $panelLabel = '4+4 CAM';
        } else {
            $panelLabel = $panelCount.' CAM';
        }

        // Sürme cam hareket oku
        if ($type === 'surme_cam') {
            $groupCount = ($panelCount === 6 || $panelCount === 8) ? 2 : 1;

            for ($g = 0; $g < $groupCount; $g++) {
                $groupW = $w / $groupCount;
                $cx = $x + ($g * $groupW) + ($groupW / 2);
                $cy = $bodyY + ($bodyH / 2);

                $arrowColor = '#ef4444';

                $svg .= '<line x1="'.($cx - 32).'" y1="'.$cy.'" x2="'.($cx + 32).'" y2="'.$cy.'" stroke="'.$arrowColor.'" stroke-width="2"/>';

                // sol ok ucu
                $svg .= '<line x1="'.($cx - 32).'" y1="'.$cy.'" x2="'.($cx - 18).'" y2="'.($cy - 11).'" stroke="'.$arrowColor.'" stroke-width="2"/>';
                $svg .= '<line x1="'.($cx - 32).'" y1="'.$cy.'" x2="'.($cx - 18).'" y2="'.($cy + 11).'" stroke="'.$arrowColor.'" stroke-width="2"/>';

                // sağ ok ucu
                $svg .= '<line x1="'.($cx + 32).'" y1="'.$cy.'" x2="'.($cx + 18).'" y2="'.($cy - 11).'" stroke="'.$arrowColor.'" stroke-width="2"/>';
                $svg .= '<line x1="'.($cx + 32).'" y1="'.$cy.'" x2="'.($cx + 18).'" y2="'.($cy + 11).'" stroke="'.$arrowColor.'" stroke-width="2"/>';
            }
        }

        // Katlanır cam zigzag
        // Katlanır cam görünümü: büyük zigzag yok, menteşe ve toplanma oku var
        if ($type === 'katlanir_cam') {
            $panelW = $w / $panelCount;

            // panel birleşimlerinde küçük menteşe noktaları
            for ($i = 1; $i < $panelCount; $i++) {
                $px = $x + ($i * $panelW);

                $svg .= '<circle cx="'.$px.'" cy="'.($bodyY + 18).'" r="2" fill="#111827"/>';
                $svg .= '<circle cx="'.$px.'" cy="'.($bodyY + $bodyH - 18).'" r="2" fill="#111827"/>';

                // küçük kat izi, sadece minicik
                if ($i % 2 === 1) {
                    $svg .= '<line x1="'.($px - 8).'" y1="'.($bodyY + 34).'" x2="'.($px + 8).'" y2="'.($bodyY + 50).'" stroke="#ef4444" stroke-width="1.2"/>';
                } else {
                    $svg .= '<line x1="'.($px + 8).'" y1="'.($bodyY + 34).'" x2="'.($px - 8).'" y2="'.($bodyY + 50).'" stroke="#ef4444" stroke-width="1.2"/>';
                }
            }

            // sağa toplanır oku
            $arrowY = $bodyY + ($bodyH / 2);
            $arrowStartX = $x + $w - 75;
            $arrowEndX = $x + $w - 28;

            $svg .= '<line x1="'.$arrowStartX.'" y1="'.$arrowY.'" x2="'.$arrowEndX.'" y2="'.$arrowY.'" stroke="#ef4444" stroke-width="2"/>';
            $svg .= '<line x1="'.($arrowEndX - 10).'" y1="'.($arrowY - 8).'" x2="'.$arrowEndX.'" y2="'.$arrowY.'" stroke="#ef4444" stroke-width="2"/>';
            $svg .= '<line x1="'.($arrowEndX - 10).'" y1="'.($arrowY + 8).'" x2="'.$arrowEndX.'" y2="'.$arrowY.'" stroke="#ef4444" stroke-width="2"/>';
        }

        $svg .= '<text x="'.($x + $w / 2).'" y="'.($y + $h - 12).'" text-anchor="middle" font-size="18" fill="#111827" font-weight="900">'.safe_text($panelLabel).'</text>';

    } elseif ($type === 'giyotin_cam' || $type === 'sabit_cam') {
        $moduleW = $w / $systemCount;
        $beamH = 18;

        // Üst hatıl / kasa
        $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$beamH.'" fill="#3f3f46" stroke="#111827" stroke-width="1.5"/>';

        for ($i = 0; $i < $systemCount; $i++) {
            $mx = $x + ($i * $moduleW);

            $postW = 8;
            $innerX = $mx + $postW;
            $innerY = $y + $beamH + 6;
            $innerW = $moduleW - ($postW * 2);
            $innerH = $h - $beamH - 45;

            // Sol dikme
            $svg .= '<rect x="'.$mx.'" y="'.($y + $beamH).'" width="'.$postW.'" height="'.($h - $beamH).'" fill="#52525b" stroke="#111827" stroke-width="1"/>';

            // Sağ dikme
            $svg .= '<rect x="'.($mx + $moduleW - $postW).'" y="'.($y + $beamH).'" width="'.$postW.'" height="'.($h - $beamH).'" fill="#52525b" stroke="#111827" stroke-width="1"/>';

            // İç cam alanı
            $svg .= '<rect x="'.$innerX.'" y="'.$innerY.'" width="'.$innerW.'" height="'.$innerH.'" fill="#ffffff" stroke="#111827" stroke-width="1.2"/>';

            if ($type === 'giyotin_cam') {
                $line1 = $innerY + ($innerH / 3);
                $line2 = $innerY + (2 * $innerH / 3);

                $svg .= '<line x1="'.$innerX.'" y1="'.$line1.'" x2="'.($innerX + $innerW).'" y2="'.$line1.'" stroke="#111827" stroke-width="1.4"/>';
                $svg .= '<line x1="'.$innerX.'" y1="'.$line2.'" x2="'.($innerX + $innerW).'" y2="'.$line2.'" stroke="#111827" stroke-width="1.4"/>';

                $arrowColor = '#ef4444';
                $cx = $mx + ($moduleW / 2);
                $arrowTop = $innerY + 38;
                $arrowBottom = $innerY + $innerH - 38;

                $svg .= '<line x1="'.$cx.'" y1="'.$arrowTop.'" x2="'.$cx.'" y2="'.$arrowBottom.'" stroke="'.$arrowColor.'" stroke-width="2"/>';
                $svg .= '<line x1="'.($cx - 14).'" y1="'.($arrowBottom - 16).'" x2="'.$cx.'" y2="'.$arrowBottom.'" stroke="'.$arrowColor.'" stroke-width="2"/>';
                $svg .= '<line x1="'.($cx + 14).'" y1="'.($arrowBottom - 16).'" x2="'.$cx.'" y2="'.$arrowBottom.'" stroke="'.$arrowColor.'" stroke-width="2"/>';

            } elseif ($type === 'sabit_cam') {
                $svg .= '<line x1="'.$innerX.'" y1="'.$innerY.'" x2="'.($innerX + $innerW).'" y2="'.($innerY + $innerH).'" stroke="#cbd5e1" stroke-width="2"/>';
            }

            $cxText = $mx + ($moduleW / 2);
            $svg .= '<text x="'.$cxText.'" y="'.($y + $h - 12).'" text-anchor="middle" font-size="18" fill="#111827" font-weight="500">'.($i + 1).'.SİSTEM</text>';
        }

    } elseif ($type === 'zip_perde') {
        $boxH = 55;

        $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$boxH.'" fill="#e5e7eb" stroke="#111827" stroke-width="2"/>';
        $svg .= '<line x1="'.($x + 18).'" y1="'.($y + $boxH).'" x2="'.($x + 18).'" y2="'.($y + $h).'" stroke="#111827" stroke-width="3"/>';
        $svg .= '<line x1="'.($x + $w - 18).'" y1="'.($y + $boxH).'" x2="'.($x + $w - 18).'" y2="'.($y + $h).'" stroke="#111827" stroke-width="3"/>';

        $arrowColor = '#f9a8d4';
        $arrowX = $x + $w / 2;
        $arrowStartY = $y + $boxH + 55;
        $arrowEndY = $y + $h - 55;

        $svg .= '<line x1="'.$arrowX.'" y1="'.$arrowStartY.'" x2="'.$arrowX.'" y2="'.$arrowEndY.'" stroke="'.$arrowColor.'" stroke-width="2"/>';
        $svg .= '<polygon points="'.$arrowX.','.($arrowEndY + 10).' '.($arrowX - 7).','.($arrowEndY - 6).' '.($arrowX + 7).','.($arrowEndY - 6).'" fill="'.$arrowColor.'"/>';
        $svg .= '<text x="'.($x + $w / 2).'" y="'.($y + 35).'" text-anchor="middle" font-size="16" fill="#111827" font-weight="900">ZİP PERDE KUTUSU</text>';
    } elseif ($type === 'sandvic_panel' || $type === 'kompozit_kapama') {
        for ($i = 1; $i < 7; $i++) {
            $py = $y + ($i * $h / 7);
            $svg .= '<line x1="'.$x.'" y1="'.$py.'" x2="'.($x + $w).'" y2="'.$py.'" stroke="#94a3b8" stroke-width="1.5"/>';
        }

        $svg .= '<text x="'.($x + $w / 2).'" y="'.($y + $h / 2 + 6).'" text-anchor="middle" font-size="22" fill="#111827" font-weight="900">'.safe_text($title).'</text>';
    } else {
        $svg .= '<text x="'.($x + $w / 2).'" y="'.($y + $h / 2 + 6).'" text-anchor="middle" font-size="22" fill="#111827" font-weight="900">'.safe_text($title).'</text>';
    }

    // Köşe noktaları
    $corner = 12;
    $points = [
        [$x, $y],
        [$x + $w, $y],
        [$x, $y + $h],
        [$x + $w, $y + $h]
    ];

    foreach ($points as $p) {
        $svg .= '<rect x="'.($p[0] - $corner / 2).'" y="'.($p[1] - $corner / 2).'" width="'.$corner.'" height="'.$corner.'" fill="#111827"/>';
    }

    // Genişlik ölçüsü
    $measureY = $y + $h + 52;
    $svg .= '<line x1="'.$x.'" y1="'.$measureY.'" x2="'.($x + $w).'" y2="'.$measureY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.$x.'" y1="'.($measureY - 10).'" x2="'.$x.'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($x + $w).'" y1="'.($measureY - 10).'" x2="'.($x + $w).'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($x + $w / 2).'" y="'.($measureY + 30).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900">GENİŞLİK: '.safe_text($widthText).'</text>';

    // Net yükseklik ölçüsü: yerden tavana kadar
    $measureX = $x - 56;
    $heightMidY = ($clearTopY + $bottomY) / 2;

    $svg .= '<line x1="'.$measureX.'" y1="'.$clearTopY.'" x2="'.$measureX.'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.$clearTopY.'" x2="'.($measureX + 10).'" y2="'.$clearTopY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.$bottomY.'" x2="'.($measureX + 10).'" y2="'.$bottomY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($measureX - 26).'" y="'.$heightMidY.'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900" transform="rotate(-90 '.($measureX - 26).' '.$heightMidY.')">YÜKSEKLİK: '.safe_text($heightText).'</text>';

    // Ayak yüksekliği ölçüsü: en alttan en üste
    if ($legHeight !== '') {
        $legMeasureX = $x + $w + 42;
        $legTextX = $legMeasureX + 22;
        $legMidY = ($totalTopY + $bottomY) / 2;

        $svg .= '<line x1="'.$legMeasureX.'" y1="'.$totalTopY.'" x2="'.$legMeasureX.'" y2="'.$bottomY.'" stroke="#f59e0b" stroke-width="2"/>';
        $svg .= '<line x1="'.($legMeasureX - 8).'" y1="'.$totalTopY.'" x2="'.($legMeasureX + 8).'" y2="'.$totalTopY.'" stroke="#f59e0b" stroke-width="2"/>';
        $svg .= '<line x1="'.($legMeasureX - 8).'" y1="'.$bottomY.'" x2="'.($legMeasureX + 8).'" y2="'.$bottomY.'" stroke="#f59e0b" stroke-width="2"/>';
        $svg .= '<text x="'.$legTextX.'" y="'.$legMidY.'" text-anchor="middle" font-size="14" fill="#f59e0b" font-weight="900" transform="rotate(90 '.$legTextX.' '.$legMidY.')">AYAK YÜKSEKLİĞİ: '.safe_text($legHeightText).'</text>';
    }
        

    

    $svg .= '</svg>';

    return $svg;
}
function draw_auto_kapi_svg($product) {
    $type = $product['type'] ?? '';

    $width = num_value($product['width'] ?? '');
    $height = num_value($product['height'] ?? '');
    $qty = qty_value($product);

    $caseRal = $product['case_ral'] ?? '';
    $glassType = $product['glass_type'] ?? '';
    $color = $product['color'] ?? '';
    $note = $product['note'] ?? '';

    $widthText = $width !== '' ? $width . ' mm' : '-';
    $heightText = $height !== '' ? $height . ' mm' : '-';
    $title = product_title($type);

    $svg = '';
    $svg .= '<svg width="1000" height="700" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="1000" height="700" fill="#ffffff"/>';

    // Grid
    for ($gx = 0; $gx <= 1000; $gx += 25) {
        $stroke = ($gx % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="'.$gx.'" y1="0" x2="'.$gx.'" y2="700" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    for ($gy = 0; $gy <= 700; $gy += 25) {
        $stroke = ($gy % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="0" y1="'.$gy.'" x2="1000" y2="'.$gy.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    $svg .= '<rect x="22" y="22" width="956" height="656" fill="none" stroke="#111827" stroke-width="2"/>';
    $svg .= '<text x="500" y="58" text-anchor="middle" font-size="28" fill="#111827" font-weight="900">OTOMATİK KAPI ÇİZİMİ</text>';

    $x = 145;
    $y = 125;
    $w = 620;
    $h = 340;

    $svg .= '<text x="'.($x + $w / 2).'" y="'.($y - 34).'" text-anchor="middle" font-size="20" fill="#111827" font-weight="900">ÖN GÖRÜNÜŞ / KAPI</text>';

    // Ana kapı çerçevesi
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#ffffff" stroke="#111827" stroke-width="4"/>';

    // Tiplere göre çizim
    if ($type === 'surme_kapi') {
        $panels = 4;

        if ((float)$width >= 6000) {
            $panels = 6;
        } elseif ((float)$width < 3000 && (float)$width > 0) {
            $panels = 2;
        }

        for ($i = 1; $i < $panels; $i++) {
            $px = $x + ($i * $w / $panels);
            $svg .= '<line x1="'.$px.'" y1="'.$y.'" x2="'.$px.'" y2="'.($y + $h).'" stroke="#111827" stroke-width="2"/>';
        }

        $arrowColor = '#f9a8d4';
        for ($i = 0; $i < $panels; $i++) {
            $cx = $x + ($i * $w / $panels) + ($w / $panels / 2);
            $cy = $y + ($h / 2);

            $svg .= '<line x1="'.($cx - 22).'" y1="'.$cy.'" x2="'.($cx + 22).'" y2="'.$cy.'" stroke="'.$arrowColor.'" stroke-width="2"/>';
            $svg .= '<polygon points="'.($cx - 28).','.$cy.' '.($cx - 16).','.($cy - 7).' '.($cx - 16).','.($cy + 7).'" fill="'.$arrowColor.'"/>';
            $svg .= '<polygon points="'.($cx + 28).','.$cy.' '.($cx + 16).','.($cy - 7).' '.($cx + 16).','.($cy + 7).'" fill="'.$arrowColor.'"/>';
        }

    } elseif ($type === 'katlanir_kapi') {
        $panels = 5;

        for ($i = 1; $i < $panels; $i++) {
            $px = $x + ($i * $w / $panels);
            $svg .= '<line x1="'.$px.'" y1="'.$y.'" x2="'.$px.'" y2="'.($y + $h).'" stroke="#111827" stroke-width="2"/>';
        }

        $points = [];
        for ($i = 0; $i <= $panels; $i++) {
            $px = $x + ($i * $w / $panels);
            $py = ($i % 2 === 0) ? $y + 55 : $y + $h - 55;
            $points[] = $px . ',' . $py;
        }

        $svg .= '<polyline points="'.implode(' ', $points).'" fill="none" stroke="#f9a8d4" stroke-width="2.2"/>';

    } elseif ($type === 'cift_kanat_kapi') {
        $centerX = $x + $w / 2;

        $svg .= '<line x1="'.$centerX.'" y1="'.$y.'" x2="'.$centerX.'" y2="'.($y + $h).'" stroke="#111827" stroke-width="2.5"/>';

        $svg .= '<path d="M '.$centerX.' '.($y + $h).' Q '.($x + $w * 0.78).' '.($y + $h * 0.68).' '.($x + $w - 14).' '.($y + $h).'" fill="none" stroke="#f9a8d4" stroke-width="2.2"/>';
        $svg .= '<path d="M '.$centerX.' '.($y + $h).' Q '.($x + $w * 0.22).' '.($y + $h * 0.68).' '.($x + 14).' '.($y + $h).'" fill="none" stroke="#f9a8d4" stroke-width="2.2"/>';

    } else {
        // tek kanat kapı
        $svg .= '<path d="M '.$x.' '.($y + $h).' Q '.($x + $w * 0.55).' '.($y + $h * 0.67).' '.($x + $w - 14).' '.($y + $h).'" fill="none" stroke="#f9a8d4" stroke-width="2.2"/>';
    }

    // Köşe noktaları
    $corner = 12;
    $points = [
        [$x, $y],
        [$x + $w, $y],
        [$x, $y + $h],
        [$x + $w, $y + $h]
    ];

    foreach ($points as $p) {
        $svg .= '<rect x="'.($p[0] - $corner / 2).'" y="'.($p[1] - $corner / 2).'" width="'.$corner.'" height="'.$corner.'" fill="#111827"/>';
    }

    // Genişlik ölçüsü
    $measureY = $y + $h + 52;
    $svg .= '<line x1="'.$x.'" y1="'.$measureY.'" x2="'.($x + $w).'" y2="'.$measureY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.$x.'" y1="'.($measureY - 10).'" x2="'.$x.'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($x + $w).'" y1="'.($measureY - 10).'" x2="'.($x + $w).'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($x + $w / 2).'" y="'.($measureY + 30).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900">GENİŞLİK: '.safe_text($widthText).'</text>';

    // Yükseklik ölçüsü
    $measureX = $x - 56;
    $svg .= '<line x1="'.$measureX.'" y1="'.$y.'" x2="'.$measureX.'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.$y.'" x2="'.($measureX + 10).'" y2="'.$y.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.($y + $h).'" x2="'.($measureX + 10).'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($measureX - 26).'" y="'.($y + $h / 2).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900" transform="rotate(-90 '.($measureX - 26).' '.($y + $h / 2).')">YÜKSEKLİK: '.safe_text($heightText).'</text>';

    // Sağ bilgi kutusu
    $infoX = 805;
    $infoY = 125;
    $svg .= '<rect x="'.$infoX.'" y="'.$infoY.'" width="155" height="280" rx="6" fill="#ffffff" stroke="#cbd5e1" stroke-width="2"/>';
    $svg .= '<text x="'.($infoX + 77).'" y="'.($infoY + 30).'" text-anchor="middle" font-size="16" fill="#111827" font-weight="900">KESİM BİLGİSİ</text>';

    $lineY = $infoY + 65;

    if ($width !== '') {
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">Üst kasa:</text>';
        $lineY += 20;
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">'.safe_text($widthText).' x 1</text>';

        $lineY += 30;
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">Alt eşik:</text>';
        $lineY += 20;
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">'.safe_text($widthText).' x 1</text>';
    }

    if ($height !== '') {
        $lineY += 30;
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">Yan dikme:</text>';
        $lineY += 20;
        $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">'.safe_text($heightText).' x 2</text>';
    }

    $lineY += 30;
    $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">Adet:</text>';
    $lineY += 20;
    $svg .= '<text x="'.($infoX + 14).'" y="'.$lineY.'" font-size="14" fill="#111827">'.safe_text($qty).' adet</text>';

    // Alt bilgi kutusu
    $svg .= '<rect x="70" y="575" width="860" height="72" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>';
    $svg .= '<text x="92" y="603" font-size="18" fill="#111827" font-weight="900">GİRİLEN KAPI ÖLÇÜLERİ</text>';

    $svg .= '<text x="92" y="630" font-size="15" fill="#111827">'
        .'Ürün: '.safe_text($title)
        .'   |   Genişlik: '.safe_text($widthText)
        .'   |   Yükseklik: '.safe_text($heightText)
        .'</text>';

    $svg .= '<text x="92" y="650" font-size="13" fill="#475569">'
        .'Kasa RAL: '.safe_text($caseRal ?: '-')
        .'   |   Cam: '.safe_text($glassType ?: '-')
        .'   |   Renk: '.safe_text($color ?: '-')
        .'</text>';

    if ($note !== '') {
        $svg .= '<text x="92" y="668" font-size="12" fill="#475569">Not: '.safe_text($note).'</text>';
    }

    $svg .= '</svg>';

    return $svg;
}
function drawing_float_value($value) {
    $n = num_value($value);

    if ($n === '') {
        return 0;
    }

    $n = str_replace('.', '', $n);
    $n = str_replace(',', '.', $n);

    return (float)$n;
}

function drawing_side_key($value) {
    $v = trim((string)$value);

    if ($v === '') {
        return 'genel';
    }

    $v = str_replace(
        ['Ö','ö','Ğ','ğ','Ü','ü','Ş','ş','İ','ı','Ç','ç'],
        ['o','o','g','g','u','u','s','s','i','i','c','c'],
        $v
    );

    return strtolower($v);
}

function is_mergeable_cephe_type($type) {
    return in_array($type, [
        'surme_cam',
        'giyotin_cam',
        'sabit_cam',
        'katlanir_cam',
        'zip_perde',
        'sandvic_panel',
        'kompozit_kapama'
    ], true);
}

function merge_same_cephe_products_for_drawing($products) {
    $merged = [];
    $map = [];

    foreach ($products as $product) {
        $type = $product['type'] ?? '';

        if (!is_mergeable_cephe_type($type)) {
            $merged[] = $product;
            continue;
        }

        $sideKey = drawing_side_key($product['side'] ?? '');
        $key = $type . '|' . $sideKey;

        if (!isset($map[$key])) {
            $map[$key] = count($merged);

            $qty = (int) num_value($product['quantity'] ?? 1);
            if ($qty <= 0) {
                $qty = 1;
            }

            $product['quantity'] = $qty;
            $merged[] = $product;
            continue;
        }

        $idx = $map[$key];
        $old = $merged[$idx];

        $oldWidth = drawing_float_value($old['width'] ?? '');
        $newWidth = drawing_float_value($product['width'] ?? '');

        if ($oldWidth > 0 || $newWidth > 0) {
            $old['width'] = (string)($oldWidth + $newWidth);
        }

        $oldHeight = drawing_float_value($old['height'] ?? '');
        $newHeight = drawing_float_value($product['height'] ?? '');

        if ($newHeight > $oldHeight) {
            $old['height'] = (string)$newHeight;
        }

        $oldLegHeight = drawing_float_value($old['leg_height'] ?? '');
        $newLegHeight = drawing_float_value($product['leg_height'] ?? '');

        if ($newLegHeight > $oldLegHeight) {
            $old['leg_height'] = (string)$newLegHeight;
        }

        $oldQty = (int) num_value($old['quantity'] ?? 1);
        $newQty = (int) num_value($product['quantity'] ?? 1);

        if ($oldQty <= 0) $oldQty = 1;
        if ($newQty <= 0) $newQty = 1;

        $old['quantity'] = $oldQty + $newQty;

        $merged[$idx] = $old;
    }

    return $merged;
}
function build_full_svg($products) {
    $pageW = 1000;
    $pageH = 700;

    $svg = '<svg width="'.$pageW.'" height="'.$pageH.'" viewBox="0 0 '.$pageW.' '.$pageH.'" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="'.$pageW.'" height="'.$pageH.'" fill="#ffffff"/>';

    for ($gx = 0; $gx <= $pageW; $gx += 25) {
        $stroke = ($gx % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="'.$gx.'" y1="0" x2="'.$gx.'" y2="'.$pageH.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    for ($gy = 0; $gy <= $pageH; $gy += 25) {
        $stroke = ($gy % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="0" y1="'.$gy.'" x2="'.$pageW.'" y2="'.$gy.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    $expandedProducts = expand_products_for_drawing($products);
    $expandedProducts = merge_same_cephe_products_for_drawing($expandedProducts);
    $count = count($expandedProducts);

    if ($count === 1 && is_tavan_type($expandedProducts[0]['type'] ?? '')) {
        return draw_auto_tavan_plan_svg($expandedProducts[0]);
    }
    if (
        $count === 1 &&
        in_array(($expandedProducts[0]['type'] ?? ''), [
            'surme_cam',
            'giyotin_cam',
            'sabit_cam',
            'katlanir_cam',
            'zip_perde',
            'sandvic_panel',
            'kompozit_kapama'
        ], true)
    ) {
        return draw_auto_cephe_svg($expandedProducts[0]);
    }
    if ($count === 1 && is_kapi_type($expandedProducts[0]['type'] ?? '')) {
        return draw_auto_kapi_svg($expandedProducts[0]);
    }

    if ($count === 0) {
        $svg .= '<text x="500" y="350" text-anchor="middle" font-size="24" fill="#94a3b8" font-weight="900">OTOMATİK ÇİZİM İÇİN ÜRÜN EKLEYİN</text>';
        $svg .= '</svg>';
        return $svg;
    }

    if ($count === 1) {
        $cols = 1;
        $rows = 1;
    } elseif ($count === 2) {
        $cols = 2;
        $rows = 1;
    } else {
        $cols = 2;
        $rows = (int)ceil($count / 2);
    }

    $rows = max(1, min($rows, 4));
    $gapX = 18;
    $gapY = 18;
    $margin = 22;

    $boxW = ($pageW - ($margin * 2) - (($cols - 1) * $gapX)) / $cols;

    $startY = $margin;

    if ($count === 2) {
        $boxH = 280;   // istersen 280 de yapabiliriz
        $startY = 70;  // yukarı-aşağı ortalasın diye
    } else {
        $boxH = ($pageH - ($margin * 2) - (($rows - 1) * $gapY)) / $rows;
    }

    $maxBlocks = $cols * $rows;

    foreach ($expandedProducts as $i => $product) {
        if ($i >= $maxBlocks) {
            $svg .= '<rect x="710" y="650" width="260" height="28" fill="#fff" stroke="#ef4444" stroke-width="1"/>';
            $svg .= '<text x="840" y="669" text-anchor="middle" font-size="12" fill="#ef4444" font-weight="900">FAZLA ÜRÜN: SAĞ LİSTEDE DEVAM EDER</text>';
            break;
        }

        $row = (int)floor($i / $cols);
        $col = $i % $cols;

        $x = $margin + ($col * ($boxW + $gapX));
        $y = $startY + ($row * ($boxH + $gapY));

        $svg .= draw_product_block($product, $x, $y, $boxW, $boxH);
    }

    $svg .= '</svg>';

    return $svg;
}
function draw_image_based_svg($imageDataJson) {
    if (is_string($imageDataJson)) {
        $data = json_decode($imageDataJson, true);
    } else {
        $data = $imageDataJson;
    }

    if (!is_array($data)) {
        return '<svg width="1000" height="700" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg">
            <rect x="0" y="0" width="1000" height="700" fill="#ffffff"/>
            <text x="500" y="350" text-anchor="middle" font-size="24" fill="#ef4444" font-weight="900">GÖRSEL VERİSİ OKUNAMADI</text>
        </svg>';
    }

    $width = $data['dimensions']['width']['length'] ?? '';
    $height = $data['dimensions']['height']['length'] ?? '';
    $depth = $data['dimensions']['depth']['length'] ?? '';

    $widthUnit = $data['dimensions']['width']['unit'] ?? 'cm';
    $heightUnit = $data['dimensions']['height']['unit'] ?? 'cm';
    $depthUnit = $data['dimensions']['depth']['unit'] ?? 'cm';

    $notes = $data['notes_clean'] ?? [];

    $widthText = $width !== '' ? $width . ' ' . $widthUnit : '-';
    $heightText = $height !== '' ? $height . ' ' . $heightUnit : '-';
    $depthText = $depth !== '' ? $depth . ' ' . $depthUnit : '-';

    $svg = '';
    $svg .= '<svg width="1000" height="700" viewBox="0 0 1000 700" xmlns="http://www.w3.org/2000/svg">';
    $svg .= '<rect x="0" y="0" width="1000" height="700" fill="#ffffff"/>';

    // Grid
    for ($gx = 0; $gx <= 1000; $gx += 25) {
        $stroke = ($gx % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="'.$gx.'" y1="0" x2="'.$gx.'" y2="700" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    for ($gy = 0; $gy <= 700; $gy += 25) {
        $stroke = ($gy % 125 === 0) ? '#e2e8f0' : '#f1f5f9';
        $svg .= '<line x1="0" y1="'.$gy.'" x2="1000" y2="'.$gy.'" stroke="'.$stroke.'" stroke-width="1"/>';
    }

    $svg .= '<rect x="22" y="22" width="956" height="656" fill="none" stroke="#111827" stroke-width="2"/>';
    $svg .= '<text x="500" y="58" text-anchor="middle" font-size="28" fill="#111827" font-weight="900">GÖRSELDEN OKUNAN TAVAN PLANI</text>';

    // Tavan üst görünüş alanı
    $x = 150;
    $y = 120;
    $w = 620;
    $h = 360;

    $svg .= '<text x="'.($x + $w / 2).'" y="'.($y - 34).'" text-anchor="middle" font-size="20" fill="#111827" font-weight="900">ÜST GÖRÜNÜŞ / TAVAN</text>';

    // Ana tavan dikdörtgeni: genişlik x derinlik
    $svg .= '<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="#f8fafc" stroke="#111827" stroke-width="4"/>';

  

    #// Orta eksen
    #$svg .= '<line x1="'.($x + $w / 2).'" y1="'.$y.'" x2="'.($x + $w / 2).'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="1.5" stroke-dasharray="8 6"/>';
    #$svg .= '<text x="'.($x + $w / 2 + 8).'" y="'.($y + $h - 12).'" font-size="13" fill="#ef4444" font-weight="900">ORTA EKSEN</text>';

    // Önden arkaya yön okları - solda ve sağda 1'er tane
    $arrowLeftX = $x + ($w * 0.25);
    $arrowRightX = $x + ($w * 0.75);
    $arrowStartY = $y + $h - 28;
    $arrowEndY = $y + 28;

    foreach ([$arrowLeftX, $arrowRightX] as $arrowX) {
        $svg .= '<line x1="'.$arrowX.'" y1="'.$arrowStartY.'" x2="'.$arrowX.'" y2="'.$arrowEndY.'" stroke="#ef4444" stroke-width="3"/>';

        $svg .= '<polygon points="'
            .$arrowX.','.($arrowEndY - 12).' '
            .($arrowX - 10).','.($arrowEndY + 10).' '
            .($arrowX + 10).','.($arrowEndY + 10).'"
            fill="#ef4444"/>';
    }
    // Ayak / köşe noktaları
    $leg = 12;
    $points = [
        [$x, $y],
        [$x + $w, $y],
        [$x, $y + $h],
        [$x + $w, $y + $h]
    ];

    foreach ($points as $p) {
        $svg .= '<rect x="'.($p[0] - $leg / 2).'" y="'.($p[1] - $leg / 2).'" width="'.$leg.'" height="'.$leg.'" fill="#111827"/>';
    }

    // Genişlik ölçüsü
    $measureY = $y + $h + 52;
    $svg .= '<line x1="'.$x.'" y1="'.$measureY.'" x2="'.($x + $w).'" y2="'.$measureY.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.$x.'" y1="'.($measureY - 10).'" x2="'.$x.'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($x + $w).'" y1="'.($measureY - 10).'" x2="'.($x + $w).'" y2="'.($measureY + 10).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($x + $w / 2).'" y="'.($measureY + 30).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900">GENİŞLİK: '.safe_text($widthText).'</text>';

    // Derinlik ölçüsü
    $measureX = $x - 56;
    $svg .= '<line x1="'.$measureX.'" y1="'.$y.'" x2="'.$measureX.'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.$y.'" x2="'.($measureX + 10).'" y2="'.$y.'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<line x1="'.($measureX - 10).'" y1="'.($y + $h).'" x2="'.($measureX + 10).'" y2="'.($y + $h).'" stroke="#ef4444" stroke-width="2"/>';
    $svg .= '<text x="'.($measureX - 26).'" y="'.($y + $h / 2).'" text-anchor="middle" font-size="22" fill="#ef4444" font-weight="900" transform="rotate(-90 '.($measureX - 26).' '.($y + $h / 2).')">DERİNLİK: '.safe_text($depthText).'</text>';

    // Sağ bilgi kutusu
    $infoX = 805;
    $infoY = 135;
    $svg .= '<rect x="'.$infoX.'" y="'.$infoY.'" width="140" height="210" rx="6" fill="#ffffff" stroke="#cbd5e1" stroke-width="2"/>';
    $svg .= '<text x="'.($infoX + 70).'" y="'.($infoY + 30).'" text-anchor="middle" font-size="16" fill="#111827" font-weight="900">KESİM BİLGİSİ</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 65).'" font-size="14" fill="#111827">Ön kiriş:</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 85).'" font-size="14" fill="#111827">'.safe_text($widthText).' x 1</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 115).'" font-size="14" fill="#111827">Arka kiriş:</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 135).'" font-size="14" fill="#111827">'.safe_text($widthText).' x 1</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 165).'" font-size="14" fill="#111827">Yan kiriş:</text>';
    $svg .= '<text x="'.($infoX + 14).'" y="'.($infoY + 185).'" font-size="14" fill="#111827">'.safe_text($depthText).' x 2</text>';

    // Alt bilgi kutusu
    $svg .= '<rect x="70" y="575" width="860" height="72" rx="8" fill="#f8fafc" stroke="#cbd5e1" stroke-width="2"/>';
    $svg .= '<text x="92" y="603" font-size="18" fill="#111827" font-weight="900">OKUNAN ÖLÇÜLER</text>';
    $svg .= '<text x="92" y="630" font-size="16" fill="#111827">Genişlik: '.safe_text($widthText).'   |   Derinlik: '.safe_text($depthText).'   |   Yükseklik: '.safe_text($heightText).'</text>';

    if (is_array($notes) && count($notes) > 0) {
        $noteText = implode(' / ', array_slice($notes, 0, 3));
        $svg .= '<text x="92" y="650" font-size="13" fill="#475569">Not: '.safe_text($noteText).'</text>';
    }

    $svg .= '</svg>';

    return $svg;
}