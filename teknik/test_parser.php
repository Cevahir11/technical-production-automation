<?php

require_once __DIR__ . "/includes/parser.php";

$text = "
TEKLİF NO: REF-090626-0678-002
TARİH: 09.06.2026
Müşteri Mehmet Eke Montaj/Uygulama Adresi İstanbul

HAVUZ ÖNÜ | Bioklimatik Pergola Tavan Sistemi | 4,00 m | 3,00 m | 2,80 m | 12,00 | 1 | M2 | 3.000 TL | 36.000 TL
HAVUZ ÖNÜ | Sürme Cam Cephe | 4,00 m | - | 2,50 m | 10,00 | 1 | M2 | 2.000 TL | 20.000 TL
YAN | Sandviç Panel Cephe Kapama | 3,00 m | - | 2,50 m | 7,50 | 1 | M2 | 1.000 TL | 7.500 TL

KDV HARİÇ TOPLAM 63.500 TL
KDV %20 12.700 TL
GENEL TOPLAM 76.200 TL
";

$data = build_project_data($text);

echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);