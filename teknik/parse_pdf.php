<?php


ob_start();

ini_set("display_errors", "0");
ini_set("log_errors", "1");
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED);

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/includes/parser.php";

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
    file_put_contents(__DIR__ . "/uploads/last_text.txt", $text);

    $projectData = build_project_data($text);

    if (ob_get_length()) {
        ob_clean();
    }

    json_response([
        "success" => true,
        "file" => [
            "original_name" => $originalName,
            "saved_name" => $savedName,
            "saved_path" => $savedPath,
        ],
        "text_preview" => mb_substr($text, 0, 1500, "UTF-8"),
        "project_data" => $projectData,
    ]);

} catch (Exception $e) {
    if (ob_get_length()) {
        ob_clean();
    }

    json_response([
        "success" => false,
        "error" => "PDF okunurken hata oluştu: " . $e->getMessage()
    ]);
}