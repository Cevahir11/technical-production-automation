<?php

require_once __DIR__ . "/includes/product_catalog.php";

$tests = [
    "Bioklimatik Pergola Tavan Sistemi",
    "Sürme Cam Cephe",
    "Giyotin Cam",
    "Sabit Cam Doğrama",
    "Sandviç Panel Cephe Kapama",
    "Katlanır Cam",
    "Lojistik Hizmeti",
    "Kompozit Cephe Kapama",
];

foreach ($tests as $test) {
    $result = classify_product($test);

    echo $test . PHP_EOL;
    echo "  category: " . $result["category"] . PHP_EOL;
    echo "  area: " . $result["area"] . PHP_EOL;
    echo "  drawing_type: " . $result["drawing_type"] . PHP_EOL;
    echo PHP_EOL;
}