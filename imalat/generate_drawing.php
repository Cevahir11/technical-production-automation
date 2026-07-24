<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/includes/drawing_svg.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Sadece POST isteği kabul edilir.'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$products = $_POST['products'] ?? [];

try {
    $svg = build_full_svg($products);

    echo json_encode([
        'success' => true,
        'svg' => $svg
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Otomatik çizim oluşturulamadı.',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
