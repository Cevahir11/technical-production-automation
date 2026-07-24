<?php

require_once __DIR__ . "/includes/helpers.php";

echo "clean_text: " . clean_text("Merhaba     dünya") . PHP_EOL;
echo "to_float: " . to_float("1.234,56 TL") . PHP_EOL;
echo "format_meter: " . format_meter(3.5) . PHP_EOL;
echo "tr_to_ascii: " . tr_to_ascii("Sürme Cam Çatı Üst Görünüm") . PHP_EOL;
echo "normalize_area_name: " . normalize_area_name("havuz onu") . PHP_EOL;
echo "safe_filename: " . safe_filename("teklif dosyası 1.pdf") . PHP_EOL;