<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/includes/parser.php";
require_once __DIR__ . "/includes/drawing_svg.php";

use Smalot\PdfParser\Parser;

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

    $outputDir = __DIR__ . "/outputs/drawings";

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $svgFileName = $safeRef . "_" . $safeCustomer . "_" . time() . ".svg";
    $svgPath = $outputDir . DIRECTORY_SEPARATOR . $svgFileName;

    generate_technical_svg($projectData, $svgPath);

    json_response([
        "success" => true,
        "svg_file" => $svgFileName,
        "svg_url" => "outputs/drawings/" . $svgFileName,
        "project_data" => $projectData,
    ]);

} catch (Exception $e) {
    json_response([
        "success" => false,
        "error" => "Çizim oluşturulurken hata oluştu: " . $e->getMessage()
    ]);
}