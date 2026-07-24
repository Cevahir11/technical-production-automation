<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/includes/parser.php";
require_once __DIR__ . "/includes/drawing_svg.php";

use Smalot\PdfParser\Parser;
use Dompdf\Dompdf;
use Dompdf\Options;


if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    json_response([
        "success" => false,
        "error" => "Sadece POST isteği kabul edilir."
    ]);
}

if (!isset($_FILES["file"])) {
    json_response([
        "success" => false,
        "error" => "PDF dosyası gelmedi."
    ]);
}

$file = $_FILES["file"];

if ($file["error"] !== UPLOAD_ERR_OK) {
    json_response([
        "success" => false,
        "error" => "Dosya yükleme hatası: " . $file["error"]
    ]);
}

$originalName = safe_filename($file["name"]);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

if ($extension !== "pdf") {
    json_response([
        "success" => false,
        "error" => "Sadece PDF dosyası yüklenebilir."
    ]);
}

$uploadDir = __DIR__ . "/uploads";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$savedName = time() . "_" . $originalName;
$savedPath = $uploadDir . DIRECTORY_SEPARATOR . $savedName;

if (!move_uploaded_file($file["tmp_name"], $savedPath)) {
    json_response([
        "success" => false,
        "error" => "Dosya uploads klasörüne taşınamadı."
    ]);
}

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($savedPath);
    $text = $pdf->getText();

    $projectData = build_project_data($text);

    $basic = $projectData["basic_info"] ?? [];
    $ref = $basic["approval_no"] ?? ($basic["offer_no"] ?? "teknik_cizim");
    $customer = $basic["customer"] ?? "musteri";

    $safeRef = safe_name_part($ref, "teknik_cizim");
    $safeCustomer = safe_name_part($customer, "musteri");

    $svgDir = __DIR__ . "/outputs/drawings";
    $pdfDir = __DIR__ . "/outputs/drawings_pdf";

    if (!is_dir($svgDir)) {
        mkdir($svgDir, 0777, true);
    }

    if (!is_dir($pdfDir)) {
        mkdir($pdfDir, 0777, true);
    }

    $baseName = $safeRef . "_" . $safeCustomer . "_" . time();

    $svgFileName = $baseName . ".svg";
    $svgPath = $svgDir . DIRECTORY_SEPARATOR . $svgFileName;

    $pdfFileName = $baseName . ".pdf";
    $pdfPath = $pdfDir . DIRECTORY_SEPARATOR . $pdfFileName;

    generate_technical_svg($projectData, $svgPath);

    $htmlPath = str_replace(".svg", ".html", $svgPath);
    $svgContent = file_get_contents($svgPath);

    $html = '<!doctype html>
    <html>
    <head>
    <meta charset="utf-8">
    <title>Teknik Çizim</title>
    <style>
        @page {
            margin: 0;
            size: A3 landscape;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            background: #111111;
            overflow: hidden;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            position: relative;
        }

        svg {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: block;
            background: #111111;
        }
    </style>
    </head>
    <body>
    ' . $svgContent . '
    </body>
    </html>';

    file_put_contents($htmlPath, $html);

    $chromePaths = [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
        'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
    ];

    $browserPath = null;

    foreach ($chromePaths as $path) {
        if (file_exists($path)) {
            $browserPath = $path;
            break;
        }
    }

    if (!$browserPath) {
        throw new Exception("Chrome veya Edge bulunamadı. PDF çizim için Chrome/Edge gerekiyor.");
    }

    $htmlFileUrl = 'file:///' . str_replace('\\', '/', realpath($htmlPath));
    $pdfRealPath = str_replace('\\', '/', $pdfPath);

    $cmd = '"' . $browserPath . '"'
        . ' --headless'
        . ' --disable-gpu'
        . ' --no-sandbox'
        . ' --print-to-pdf="' . $pdfRealPath . '"'
        . ' "' . $htmlFileUrl . '"';

    exec($cmd, $output, $returnCode);

    if ($returnCode !== 0 || !file_exists($pdfPath)) {
        throw new Exception("Chrome ile PDF oluşturulamadı.");
    }

    

    json_response([
        "success" => true,
        "pdf_file" => $pdfFileName,
        "pdf_url" => "outputs/drawings_pdf/" . $pdfFileName,
        "svg_file" => $svgFileName,
        "svg_url" => "outputs/drawings/" . $svgFileName,
    ]);

} catch (Exception $e) {
    json_response([
        "success" => false,
        "error" => "PDF çizim oluşturulurken hata oluştu: " . $e->getMessage()
    ]);
}