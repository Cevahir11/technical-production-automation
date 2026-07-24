<?php

require_once __DIR__ . "/helpers.php";

// ---------------------------------------------------------
// ÜRÜN KATALOĞU
// Python PRODUCT_CATALOG karşılığı
// ---------------------------------------------------------

function get_product_catalog()
{
    return [

        // -------------------------------------------------
        // ÇİZİLECEK ANA TAVAN / MODÜL ÜRÜNLERİ
        // -------------------------------------------------
        [
            "code" => 100,
            "keywords" => ["BIOKLIMATIK", "PERGOLA"],
            "category" => "module",
            "area" => "BİOKLİMATİK PERGOLA",
            "drawing_type" => "roof",
        ],
        [
            "code" => 101,
            "keywords" => ["BIYOKLIMATIK", "PERGOLA"],
            "category" => "module",
            "area" => "BİOKLİMATİK PERGOLA",
            "drawing_type" => "roof",
        ],
        [
            "code" => 102,
            "keywords" => ["PERGOLA OTOMATIK TAVAN SISTEMI"],
            "category" => "module",
            "area" => "PERGOLA TAVAN",
            "drawing_type" => "roof",
        ],
        [
            "code" => 103,
            "keywords" => ["LED DAHIL PERGOLA"],
            "category" => "module",
            "area" => "PERGOLA TAVAN",
            "drawing_type" => "roof",
        ],
        [
            "code" => 104,
            "keywords" => ["OTOMATIK TAVAN SISTEMI"],
            "category" => "module",
            "area" => "OTOMATİK TAVAN",
            "drawing_type" => "roof",
        ],

        // -------------------------------------------------
        // ÇİZİLECEK CEPHE / CAM ÜRÜNLERİ
        // -------------------------------------------------
        [
            "code" => 200,
            "keywords" => ["SURME CAM"],
            "category" => "glass",
            "area" => "SÜRME CAM",
            "drawing_type" => "sliding_glass",
        ],
        [
            "code" => 201,
            "keywords" => ["GIYOTIN CAM"],
            "category" => "glass",
            "area" => "GİYOTİN CAM",
            "drawing_type" => "guillotine_glass",
        ],
        [
            "code" => 202,
            "keywords" => ["SABIT CAM"],
            "category" => "glass",
            "area" => "SABİT CAM",
            "drawing_type" => "fixed_glass",
        ],
        [
            "code" => 203,
            "keywords" => ["SABIT CAM DOGRAMA"],
            "category" => "glass",
            "area" => "SABİT CAM DOĞRAMA",
            "drawing_type" => "fixed_glass",
        ],

        // -------------------------------------------------
        // ÇİZİME GİRMEYECEK OPSİYON / HİZMET KALEMLERİ
        // -------------------------------------------------
        [
            "code" => 900,
            "keywords" => ["LOJISTIK"],
            "category" => "ignore",
            "area" => "LOJİSTİK",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 901,
            "keywords" => ["SAHA ORGANIZASYON"],
            "category" => "ignore",
            "area" => "SAHA ORGANİZASYON",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 902,
            "keywords" => ["GARANTI"],
            "category" => "ignore",
            "area" => "GARANTİ",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 903,
            "keywords" => ["FUME CAM YUKSELTME"],
            "category" => "ignore",
            "area" => "FÜME CAM OPSİYONU",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 904,
            "keywords" => ["KARKAS SISTEMI"],
            "category" => "ignore",
            "area" => "KARKAS",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 905,
            "keywords" => ["SOMFY MOTORLU OTOMASYON"],
            "category" => "option",
            "area" => "SOMFY OTOMASYON",
            "drawing_type" => "option",
        ],
        [
            "code" => 906,
            "keywords" => ["4 MEVSIM KONFOR PAKETI"],
            "category" => "option",
            "area" => "4 MEVSİM KONFOR",
            "drawing_type" => "option",
        ],

        // -------------------------------------------------
        // ESKİ KATALOG KAYITLARI
        // -------------------------------------------------
        [
            "code" => 1,
            "keywords" => ["CIFT HAREKET", "BIOKLIMATIK PERGOLA"],
            "category" => "module",
            "area" => "BİOKLİMATİK PERGOLA",
            "drawing_type" => "roof",
        ],
        [
            "code" => 2,
            "keywords" => ["4 MEVSIM KONFOR PAKETI"],
            "category" => "option",
            "area" => "KONFOR PAKETİ",
            "drawing_type" => "option",
        ],
        [
            "code" => 3,
            "keywords" => ["GIYOTIN CAM"],
            "category" => "glass",
            "area" => "GİYOTİN CAM",
            "drawing_type" => "glass_front",
        ],
        [
            "code" => 4,
            "keywords" => ["SURME CAM"],
            "category" => "glass",
            "area" => "SÜRME CAM",
            "drawing_type" => "glass_front",
        ],
        [
            "code" => 5,
            "keywords" => ["KATLANIR CAM"],
            "category" => "glass",
            "area" => "KATLANIR CAM",
            "drawing_type" => "glass_front",
        ],
        [
            "code" => 6,
            "keywords" => ["SABIT CAM"],
            "category" => "glass",
            "area" => "SABİT CAM",
            "drawing_type" => "glass_front",
        ],
        [
            "code" => 7,
            "keywords" => ["UCGEN CAM"],
            "category" => "glass",
            "area" => "ÜÇGEN CAM",
            "drawing_type" => "glass_front",
        ],
        [
            "code" => 10,
            "keywords" => ["ZIP PERDE"],
            "category" => "screen",
            "area" => "ZIP PERDE",
            "drawing_type" => "screen",
        ],
        [
            "code" => 11,
            "keywords" => ["CIFT PANELLI KAPI"],
            "category" => "door",
            "area" => "ÇİFT PANELLİ KAPI",
            "drawing_type" => "door",
        ],
        [
            "code" => 12,
            "keywords" => ["KAPI"],
            "category" => "door",
            "area" => "KAPI",
            "drawing_type" => "door",
        ],
        [
            "code" => 13,
            "keywords" => ["FOTOSELLI KAPI"],
            "category" => "door",
            "area" => "FOTOSELLİ KAPI",
            "drawing_type" => "door",
        ],
        [
            "code" => 14,
            "keywords" => ["KOMPOZIT CEPHE KAPAMA"],
            "category" => "ignore",
            "area" => "KOMPOZİT CEPHE",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 15,
            "keywords" => ["KOMPOZIT CEPHE KAPAMA XL"],
            "category" => "ignore",
            "area" => "KOMPOZİT CEPHE XL",
            "drawing_type" => "ignore",
        ],
        [
            "code" => 20,
            "keywords" => ["SANDVIC PANEL", "CEPHE KAPAMA"],
            "category" => "panel",
            "area" => "SANDVİÇ PANEL CEPHE KAPAMA",
            "drawing_type" => "wall_panel",
        ],
        [
            "code" => 21,
            "keywords" => ["SABIT TAVAN"],
            "category" => "module",
            "area" => "SABİT TAVAN",
            "drawing_type" => "roof",
        ],
    ];
}


// ---------------------------------------------------------
// ÜRÜN SINIFLANDIRMA
// Python classify_product karşılığı
// ---------------------------------------------------------

function classify_product($product_text)
{
    $product_ascii = strtoupper(tr_to_ascii($product_text));

    // Bizde katlanır cam yok, tamamen yok say
    if (strpos($product_ascii, "KATLANIR CAM") !== false) {
        return [
            "code" => -1,
            "keywords" => [],
            "category" => "ignore",
            "area" => "YOK SAY",
            "drawing_type" => "ignore",
        ];
    }

    // Üçgen cam çizime girmesin
    if (
        strpos($product_ascii, "UCGEN CAM") !== false ||
        strpos(strtoupper((string)$product_text), "ÜÇGEN CAM") !== false
    ) {
        return [
            "code" => -1,
            "keywords" => [],
            "category" => "ignore",
            "area" => "YOK SAY",
            "drawing_type" => "ignore",
        ];
    }

    // Çizime girmeyecek hizmet / opsiyon kalemleri
    $ignore_keywords = [
        "LOJISTIK",
        "SAHA ORGANIZASYON",
        "GARANTI",
        "FUME CAM YUKSELTME",
        "KARKAS SISTEMI",
        "NAKLIYE",
        "MONTAJ OPERASYON",
        "KOMPOZIT CEPHE KAPAMA",
        "KOMPOZIT",
    ];

    foreach ($ignore_keywords as $keyword) {
        if (strpos($product_ascii, $keyword) !== false) {
            return [
                "code" => -1,
                "keywords" => [],
                "category" => "ignore",
                "area" => "YOK SAY",
                "drawing_type" => "ignore",
            ];
        }
    }

    $catalog = get_product_catalog();

    // Daha özel keywordler önce yakalansın.
    usort($catalog, function ($a, $b) {
        $a_len = strlen(implode(" ", $a["keywords"] ?? []));
        $b_len = strlen(implode(" ", $b["keywords"] ?? []));
        return $b_len <=> $a_len;
    });

    foreach ($catalog as $item) {
        if (!isset($item["keywords"]) || !is_array($item["keywords"])) {
            continue;
        }

        $all_match = true;

        foreach ($item["keywords"] as $keyword) {
            if (strpos($product_ascii, $keyword) === false) {
                $all_match = false;
                break;
            }
        }

        if ($all_match) {
            return $item;
        }
    }

    return [
        "code" => 0,
        "keywords" => [],
        "category" => "other",
        "area" => "DİĞER",
        "drawing_type" => "other",
    ];
}