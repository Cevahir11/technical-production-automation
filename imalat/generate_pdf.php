<?php
require_once __DIR__ . '/includes/drawing_svg.php';

$customerName = $_POST['customer_name'] ?? '';

function make_file_name($text) {
    $text = trim($text);

    $tr = ['ş','Ş','ı','İ','ğ','Ğ','ü','Ü','ö','Ö','ç','Ç'];
    $en = ['s','S','i','I','g','G','u','U','o','O','c','C'];

    $text = str_replace($tr, $en, $text);
    $text = preg_replace('/[^A-Za-z0-9]+/', '_', $text);
    $text = trim($text, '_');

    if ($text === '') {
        $text = 'Imalat_Onay_Formu';
    }

    return $text;
}

$pdfFileName = make_file_name($customerName) . '_Imalat_Onay_Formu';

$location = $_POST['location'] ?? '';
$orderNo = $_POST['order_no'] ?? '';
$date = $_POST['date'] ?? '';
$drawnBy = $_POST['drawn_by'] ?? '';
$approvedBy = $_POST['approved_by'] ?? '';
$products = $_POST['products'] ?? [];
$materials = $_POST['materials'] ?? '';
$notes = $_POST['notes'] ?? '';

$drawingMode = $_POST['drawing_mode'] ?? 'auto';
$manualDrawingData = $_POST['manual_drawing_data'] ?? '';
$imageDrawingData = $_POST['image_drawing_data'] ?? '';

if ($drawingMode === 'image' && !empty(trim($imageDrawingData))) {
    $svg = draw_image_based_svg($imageDrawingData);
} else {
    $svg = build_full_svg($products);
}
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function format_date_tr($date) {
    if (!$date) return '';
    $time = strtotime($date);
    if (!$time) return $date;
    return date('d.m.Y', $time);
}

function type_label($type) {
    $labels = [
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
        'sandvic_panel' => 'SANDVİÇ PANEL CEPHE KAPAMA',
        'kompozit_kapama' => 'KOMPOZİT CEPHE KAPAMA',

        'surme_kapi' => 'SÜRME KAPI',
        'katlanir_kapi' => 'KATLANIR KAPI',
        'tek_kanat_kapi' => 'TEK KANAT KAPI',
        'cift_kanat_kapi' => 'ÇİFT KANAT KAPI',
        'servis_kapisi' => 'SERVİS KAPISI',

        'ozel' => 'ÖZEL ÜRÜN'
    ];

    return $labels[$type] ?? strtoupper((string)$type);
}

function category_label($category) {
    $labels = [
        'tavan' => 'TAVAN SİSTEMLERİ',
        'cephe' => 'CEPHE SİSTEMLERİ',
        'kapi' => 'KAPI SİSTEMLERİ'
    ];

    return $labels[$category] ?? 'DİĞER ÜRÜNLER';
}

function group_products($products) {
    $groups = [
        'tavan' => [],
        'cephe' => [],
        'kapi' => [],
        'diger' => []
    ];

    foreach ($products as $product) {
        if (empty($product['type'])) {
            continue;
        }

        $category = $product['category'] ?? 'diger';

        if (!isset($groups[$category])) {
            $category = 'diger';
        }

        $groups[$category][] = $product;
    }

    return $groups;
}

$groupedProducts = group_products($products);

$totalProductCount = 0;
$detailWeight = 0;

foreach ($groupedProducts as $items) {
    foreach ($items as $product) {
        $totalProductCount++;
        $detailWeight++;

        if (!empty($product['items']) && is_array($product['items'])) {
            $detailWeight += count($product['items']);
        }

        foreach (['system_type','middle_record','has_frame','frame_note','case_ral','panel_ral','glass_type','color','led','leg_height','leg_count','opening_direction','lock_note','note'] as $key) {
            if (!empty($product[$key])) {
                $detailWeight++;
            }
        }
    }
}

$compactClass = '';
if ($detailWeight >= 14 || $totalProductCount >= 4) {
    $compactClass = 'compact-products';
}
if ($detailWeight >= 24 || $totalProductCount >= 7) {
    $compactClass = 'very-compact-products';
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?= h($pdfFileName) ?></title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            background: #d1d5db;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
        }

        .top-actions {
            width: 297mm;
            max-width: 96%;
            margin: 18px auto;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .top-actions button {
            background: #111827;
            color: #fff;
            border: 0;
            border-radius: 6px;
            padding: 10px 16px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .top-actions button:last-child {
            background: #b8860b;
        }

        .page-wrap {
            width: 297mm;
            height: 210mm;
            margin: 0 auto 40px auto;
        }

        .sheet {
            width: 297mm;
            height: 210mm;
            background: #fff;
            border: 2px solid #111;
            box-shadow: 0 10px 28px rgba(0,0,0,0.20);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .sheet-header {
            display: grid;
            grid-template-columns: 38mm 1fr 55mm;
            height: 23mm;
            border-bottom: 2px solid #111;
            flex-shrink: 0;
        }

        .logo-box {
            border-right: 2px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-weight: bold;
        }

        .logo-main {
            font-size: 27px;
            color: #b8860b;
            letter-spacing: 1px;
            line-height: 1;
        }

        .logo-sub {
            margin-top: 6px;
            font-size: 9px;
            color: #333;
            letter-spacing: 1px;
        }

        .title-box {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8px 18px;
        }

        .main-title {
            font-size: 31px;
            font-weight: 900;
            letter-spacing: 2px;
        }

        .order-box {
            border-left: 2px solid #111;
            padding: 10px 13px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .order-label {
            font-size: 11px;
            font-weight: 900;
            color: #333;
        }

        .order-value {
            margin-top: 6px;
            font-size: 20px;
            font-weight: 900;
        }

        .main-grid {
            flex: 1;
            min-height: 0;
            display: grid;
            grid-template-columns: 55% 45%;
            border-bottom: 2px solid #111;
        }

        .drawing-section {
            border-right: 2px solid #111;
            padding: 3mm;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .drawing-title-bar {
            height: 8mm;
            border: 1.5px solid #111;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin-bottom: 2mm;
            flex-shrink: 0;
        }

        .drawing-frame {
            height: 93mm;
            border: 1.5px solid #111;
            overflow: hidden;
            flex-shrink: 0;
            background:
                linear-gradient(#e8eef6 1px, transparent 1px),
                linear-gradient(90deg, #e8eef6 1px, transparent 1px);
            background-size: 7mm 7mm;
        }

        .drawing-frame svg,
        .manual-drawing-preview,
        .manual-drawing-preview img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: contain;
        }

        .manual-drawing-preview {
            background: #fff;
        }

        .left-bottom-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3mm;
            margin-top: 2mm;
            flex: 1;
            min-height: 0;
            align-items: stretch;
        }

        .left-bottom-box {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            overflow: hidden;
        }

        .left-bottom-box .side-title {
            margin-bottom: 1.5mm;
            flex-shrink: 0;
        }

        .left-bottom-box .text-area {
            border: 1px solid #d1d5db;
            padding: 2mm;
            flex: 1;
            height: auto;
            max-height: none;
            min-height: 0;
            overflow: hidden;
            margin-bottom: 0;
            white-space: pre-line;
            font-size: 9px;
            line-height: 1.2;
        }

        .side-section {
            padding: 3mm 3.5mm;
            overflow: hidden;
            min-height: 0;
        }

        .side-title {
            background: #111827;
            color: white;
            font-size: 11px;
            font-weight: 900;
            padding: 1.5mm 2mm;
            margin-bottom: 2mm;
            letter-spacing: 0.3px;
        }

        .product-group {
            margin-bottom: 3mm;
        }

        .product-item {
            border: 1px solid #d1d5db;
            margin-bottom: 2mm;
            page-break-inside: avoid;
        }

        .product-head {
            background: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
            padding: 1.5mm 2mm;
            font-size: 11px;
            font-weight: 900;
        }

        .product-body {
            padding: 1.6mm 2mm;
            font-size: 10px;
            line-height: 1.22;
        }

        .info-line {
            display: grid;
            grid-template-columns: 28mm 1fr;
            gap: 2mm;
            border-bottom: 1px dotted #ccc;
            padding: 0.7mm 0;
        }

        .info-line span:first-child {
            color: #555;
            font-weight: bold;
        }

        .info-line span:last-child {
            text-align: right;
            font-weight: 700;
        }

        .measure-table {
            border: 1px solid #e5e7eb;
            margin-bottom: 1.5mm;
        }

        .measure-line {
            display: grid;
            grid-template-columns: 1fr 20mm;
            gap: 2mm;
            border-bottom: 1px dotted #ccc;
            padding: 1mm 1.5mm;
            font-size: 10px;
        }

        .measure-line:last-child {
            border-bottom: 0;
        }

        .measure-line span {
            font-weight: 700;
        }

        .measure-line strong {
            text-align: right;
            color: #111;
        }

        .product-note {
            margin-top: 1.5mm;
            padding-top: 1.2mm;
            border-top: 1px dotted #ccc;
            font-size: 9px;
        }

        .text-area {
            border: 1px solid #d1d5db;
            min-height: 18mm;
            padding: 2mm;
            font-size: 9.5px;
            white-space: pre-line;
            line-height: 1.25;
            margin-bottom: 3mm;
            background: #fff;
        }

        .empty-text {
            color: #999;
            font-style: italic;
        }

        .footer-row {
            display: grid;
            grid-template-columns: 2fr 1.3fr 1.3fr 1fr;
            height: 21mm;
            flex-shrink: 0;
        }

        .footer-cell {
            border-right: 1px solid #111;
            padding: 2mm 3mm;
            font-size: 11px;
        }

        .footer-cell:last-child {
            border-right: 0;
        }

        .footer-label {
            font-size: 9px;
            font-weight: 900;
            color: #555;
            margin-bottom: 5mm;
        }

        .footer-value {
            font-size: 13px;
            font-weight: 900;
            border-top: 1px solid #111;
            padding-top: 1.5mm;
            min-height: 7mm;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Urun coksa sag panel otomatik sıkısır */
        .main-grid.compact-products .side-section {
            padding: 2.4mm 3mm;
        }

        .main-grid.compact-products .side-title {
            font-size: 9.5px;
            padding: 1.1mm 1.6mm;
            margin-bottom: 1.4mm;
        }

        .main-grid.compact-products .product-group {
            margin-bottom: 2mm;
        }

        .main-grid.compact-products .product-item {
            margin-bottom: 1.3mm;
        }

        .main-grid.compact-products .product-head {
            font-size: 9.5px;
            padding: 1.1mm 1.5mm;
        }

        .main-grid.compact-products .product-body {
            font-size: 8.2px;
            line-height: 1.12;
            padding: 1.1mm 1.5mm;
        }

        .main-grid.compact-products .info-line {
            grid-template-columns: 25mm 1fr;
            padding: 0.45mm 0;
        }

        .main-grid.compact-products .measure-line {
            font-size: 8.2px;
            padding: 0.7mm 1.1mm;
        }

        .main-grid.compact-products .product-note {
            font-size: 7.6px;
            margin-top: 0.9mm;
            padding-top: 0.8mm;
        }

        .main-grid.very-compact-products .side-section {
            padding: 2mm 2.4mm;
        }

        .main-grid.very-compact-products .side-title {
            font-size: 8px;
            padding: 0.8mm 1.2mm;
            margin-bottom: 1mm;
        }

        .main-grid.very-compact-products .product-group {
            margin-bottom: 1.2mm;
        }

        .main-grid.very-compact-products .product-item {
            margin-bottom: 0.8mm;
        }

        .main-grid.very-compact-products .product-head {
            font-size: 8px;
            padding: 0.8mm 1.1mm;
        }

        .main-grid.very-compact-products .product-body {
            font-size: 7px;
            line-height: 1.05;
            padding: 0.8mm 1.1mm;
        }

        .main-grid.very-compact-products .info-line {
            grid-template-columns: 21mm 1fr;
            gap: 1mm;
            padding: 0.28mm 0;
        }

        .main-grid.very-compact-products .measure-line {
            grid-template-columns: 1fr 16mm;
            font-size: 7px;
            padding: 0.5mm 0.8mm;
        }

        .main-grid.very-compact-products .product-note {
            font-size: 6.5px;
            margin-top: 0.6mm;
            padding-top: 0.5mm;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        @media print {
            .no-print,
            .top-actions {
                display: none !important;
            }

            html,
            body {
                width: 297mm !important;
                height: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
                overflow: hidden !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .page-wrap {
                width: 297mm !important;
                height: 209mm !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }

            .sheet {
                width: 297mm !important;
                height: 209mm !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                overflow: hidden !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

<div class="top-actions no-print">
    <button type="button" onclick="window.history.back()" class="back-btn">Geri Dön</button>
    <button type="button" onclick="downloadPdf()">PDF İndir</button>
</div>

<div class="page-wrap">
    <div class="sheet">

        <div class="sheet-header">
            <div class="logo-box">
                <div class="logo-main">VERTU</div>
                <div class="logo-sub">BİOKLİMATİK</div>
            </div>

            <div class="title-box">
                <div class="main-title">İMALAT ONAY FORMU</div>
            </div>

            <div class="order-box">
                <div class="order-label">SIRA / SİPARİŞ NO</div>
                <div class="order-value"><?= h($orderNo) ?></div>
            </div>
        </div>

        <div class="main-grid <?= h($compactClass) ?>">
            <div class="drawing-section">
                <div class="drawing-title-bar">
                    <?php
                    if ($drawingMode === 'manual') {
                        echo 'MÜHENDİS ÇİZİMİ';
                    } elseif ($drawingMode === 'image') {
                        echo 'GÖRSELDEN OKUNAN TEKNİK ÇİZİM';
                    } else {
                        echo 'TEKNİK ÇİZİM / ÜRÜN ÖNİZLEME';
                    }
                    ?>
                </div>

                <div class="drawing-frame">
                    <?php if ($drawingMode === 'manual' && !empty($manualDrawingData)): ?>
                        <div class="manual-drawing-preview">
                            <img src="<?= h($manualDrawingData) ?>" alt="Mühendis çizimi">
                        </div>
                    <?php elseif ($drawingMode === 'image'): ?>
                        <?= $svg ?>
                    <?php else: ?>
                        <?= $svg ?>
                    <?php endif; ?>
                </div>

                <div class="left-bottom-info">
                    <div class="left-bottom-box">
                        <div class="side-title">MONTAJ MALZEMELERİ</div>
                        <div class="text-area">
                            <?= $materials ? h($materials) : '<span class="empty-text">Malzeme girilmedi.</span>' ?>
                        </div>
                    </div>

                    <div class="left-bottom-box">
                        <div class="side-title">NOTLAR</div>
                        <div class="text-area">
                            <?= $notes ? h($notes) : '<span class="empty-text">Not girilmedi.</span>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="side-section">
                <?php foreach ($groupedProducts as $category => $categoryProducts): ?>
                    <?php if (empty($categoryProducts)) continue; ?>

                    <div class="product-group">
                        <div class="side-title"><?= h(category_label($category)) ?></div>

                        <?php foreach ($categoryProducts as $product): ?>
                            <div class="product-item">
                                <div class="product-head">
                                    <?= h(type_label($product['type'] ?? '')) ?>
                                    <?php if (!empty($product['side'])): ?>
                                        / <?= h($product['side']) ?>
                                    <?php endif; ?>
                                </div>

                                <div class="product-body">
                                    <?php if (!empty($product['items']) && is_array($product['items'])): ?>
                                        <div class="measure-table">
                                            <?php foreach ($product['items'] as $item): ?>
                                                <?php
                                                    $itemWidth = $item['width'] ?? '';
                                                    $itemHeight = $item['height'] ?? '';
                                                    $itemQty = $item['quantity'] ?? '';
                                                ?>

                                                <?php if ($itemWidth || $itemHeight): ?>
                                                    <div class="measure-line">
                                                        <span><?= h($itemWidth) ?> x <?= h($itemHeight) ?> mm</span>
                                                        <strong><?= h($itemQty) ?> ADET</strong>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <?php if (!empty($product['width']) || !empty($product['height']) || !empty($product['depth'])): ?>
                                            <div class="info-line">
                                                <span>Ölçü</span>
                                                <span>
                                                    <?= h($product['width'] ?? '') ?>
                                                    <?php if (!empty($product['depth'])): ?>
                                                        x <?= h($product['depth']) ?>
                                                    <?php elseif (!empty($product['height'])): ?>
                                                        x <?= h($product['height']) ?>
                                                    <?php endif; ?>
                                                    mm
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($product['quantity'])): ?>
                                            <div class="info-line">
                                                <span>Adet</span>
                                                <span><?= h($product['quantity']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php
                                    $fields = [
                                        'system_type' => 'Sistem Tipi',
                                        'middle_record' => 'Orta Kayıt',
                                        'has_frame' => 'Karkas',
                                        'frame_note' => 'Karkas Notu',
                                        'case_ral' => 'Kasa RAL',
                                        'panel_ral' => 'Panel / Kumaş RAL',
                                        'glass_type' => 'Cam Türü',
                                        'color' => 'Renk / Cam',
                                        'led' => 'LED',
                                        'leg_height' => 'Ayak H',
                                        'leg_count' => 'Ayak Sayısı',
                                        'opening_direction' => 'Açılım Yönü',
                                        'lock_note' => 'Kol / Kilit'
                                    ];
                                    ?>

                                    <?php foreach ($fields as $fieldKey => $fieldLabel): ?>
                                        <?php if (!empty($product[$fieldKey])): ?>
                                            <div class="info-line">
                                                <span><?= h($fieldLabel) ?></span>
                                                <span>
                                                    <?= h($product[$fieldKey]) ?>
                                                    <?php if ($fieldKey === 'leg_height'): ?> mm<?php endif; ?>
                                                    <?php if ($fieldKey === 'leg_count'): ?> ADET<?php endif; ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if (!empty($product['note'])): ?>
                                        <div class="product-note">
                                            <strong>Not:</strong> <?= h($product['note']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="footer-row">
            <div class="footer-cell">
                <div class="footer-label">MÜŞTERİ / LOKASYON</div>
                <div class="footer-value">
                    <?= h($customerName) ?><?= $location ? ' - ' . h($location) : '' ?>
                </div>
            </div>

            <div class="footer-cell">
                <div class="footer-label">ÇİZEN</div>
                <div class="footer-value"><?= h($drawnBy) ?></div>
            </div>

            <div class="footer-cell">
                <div class="footer-label">ONAYLAYAN</div>
                <div class="footer-value"><?= h($approvedBy) ?></div>
            </div>

            <div class="footer-cell">
                <div class="footer-label">TARİH</div>
                <div class="footer-value"><?= h(format_date_tr($date)) ?></div>
            </div>
        </div>

    </div>
</div>

<script>
function downloadPdf() {
    document.title = <?= json_encode($pdfFileName, JSON_UNESCAPED_UNICODE) ?>;
    window.print();
}
</script>

</body>
</html>
