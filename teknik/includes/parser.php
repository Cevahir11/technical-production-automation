<?php

require_once __DIR__ . "/helpers.php";
require_once __DIR__ . "/product_catalog.php";

function normalize_pdf_lines($text)
{
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $lines = explode("\n", $text);
    $out = [];

    foreach ($lines as $line) {
        $line = clean_text($line);
        if ($line !== "") {
            $out[] = $line;
        }
    }

    return $out;
}

function normalize_offer_area($area)
{
    $area = clean_text((string)$area);

    if (preg_match('/^\d+\s*\(([^)]+)\)/u', $area, $m)) {
        $area = clean_text($m[1]);
    }

    $ascii = strtoupper(tr_to_ascii($area));

    if (strpos($ascii, "SOL") !== false && strpos($ascii, "CEPHE") !== false) return "SOL CEPHE";
    if ((strpos($ascii, "SAG") !== false || strpos($ascii, "SAĞ") !== false) && strpos($ascii, "CEPHE") !== false) return "SAĞ CEPHE";
    if (strpos($ascii, "ON") !== false && strpos($ascii, "CEPHE") !== false) return "ÖN CEPHE";
    if (strpos($ascii, "ARKA") !== false && strpos($ascii, "CEPHE") !== false) return "ARKA CEPHE";
    if (strpos($ascii, "TAVAN") !== false) return "TAVAN";

    return normalize_area_name($area);
}


function detect_offer_format($text)
{
    $norm = strtoupper(tr_to_ascii((string)$text));

    if (strpos($norm, "FIYAT TEKLIFI VE ODEME TABLOSU") !== false) {
        return "cam_dograma_odeme";
    }

    return "default";
}

function parse_cam_dograma_number($value)
{
    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return null;
    }

    $value = str_replace(["₺", "TL", "m²", "m2", "M²", "M2", "m", " "], "", $value);

    // Amerikan para/ondalık formatı: 7,000.00 / 121,800.00 / 5.80
    if (preg_match('/^[0-9,]+\.[0-9]+$/', $value)) {
        return floatval(str_replace(",", "", $value));
    }

    // Türk formatı: 7.000,00 / 121.800,00 / 5,80
    if (preg_match('/^[0-9.]+,[0-9]+$/', $value)) {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);
        return floatval($value);
    }

    return floatval($value);
}

function format_cam_dograma_measure($value)
{
    $n = parse_cam_dograma_number($value);

    if ($n === null) {
        return "-";
    }

    return number_format($n, 2, ",", ".");
}

function format_cam_dograma_money($value)
{
    $n = parse_cam_dograma_number($value);

    if ($n === null) {
        return "-";
    }

    return number_format($n, 2, ",", ".");
}

function parse_cam_dograma_teklif_rows($text)
{
    $plain = clean_text($text);
    $plain = str_replace(["\r", "\n", "\t"], " ", $plain);
    $plain = preg_replace('/\s+/u', ' ', $plain);

    $areaPattern = '(SOL\s+CEPHE|SAĞ\s+CEPHE|SAG\s+CEPHE|ORTA|ÖN\s+CEPHE|ON\s+CEPHE)';

    $pattern = '/' .
        $areaPattern .
        '\s+' .
        '(.+?)' .
        '\s+' .
        '([0-9]+[,.][0-9]{2})' .       // en
        '\s+' .
        '([0-9]+[,.][0-9]{2})' .       // yükseklik
        '\s+' .
        '([0-9]+[,.][0-9]{2})' .       // alan
        '\s+' .
        '([0-9]+)' .                   // adet
        '\s+' .
        '(m2|M2|m²|M²|AD|ad)' .        // birim
        '\s+' .
        '([0-9]+[,.][0-9]{2})' .       // toplam alan
        '\s+' .
        '₺\s*([0-9.,]+)' .             // birim fiyat
        '\s+' .
        '₺\s*([0-9.,]+)' .             // toplam
        '/iu';

    $rows = [];

    if (!preg_match_all($pattern, $plain, $matches, PREG_SET_ORDER)) {
        return [];
    }

    foreach ($matches as $m) {
        $area = normalize_offer_area($m[1]);
        $description = clean_text($m[2]);

        $unit = strtoupper(str_replace(["m²", "M²", "m2"], "M2", $m[7]));
        if ($unit === "AD") {
            $unit = "AD";
        }

        $rows[] = [
            "area" => $area,
            "description" => $description,
            "width" => format_cam_dograma_measure($m[3]),
            "depth" => "-",
            "height" => format_cam_dograma_measure($m[4]),
            "area_m2" => format_cam_dograma_measure($m[5]),
            "quantity" => clean_text($m[6]),
            "unit" => $unit,
            "total_m2" => format_cam_dograma_measure($m[8]),
            "unit_price" => format_cam_dograma_money($m[9]),
            "total_price" => format_cam_dograma_money($m[10]),
        ];
    }

    return $rows;
}


function clean_price_text_local($value)
{
    $value = clean_text((string)$value);
    $value = str_replace(["₺", "TL"], "", $value);
    $value = trim($value);

    if ($value === "") {
        return "-";
    }

    return $value . " TL";
}

function find_basic_info($text)
{
    $cleaned = clean_text($text);
    $lines = normalize_pdf_lines($text);

    $customer = null;
    $offer_no = null;
    $approval_no = null;
    $contract_price = null;
    $subtotal_price = null;
    $vat_price = null;
    $grand_total_price = null;

    $address = null;
    $phone = null;
    $email = null;
    $project_name = null;
    $work_title = null;



    $seller = "Monshiny Alüminyum";
    $brand = "Vertu Bioklimatik Kış Bahçesi";
    $date_range = null;

    // TEKLİF NO / REF
    $offer_patterns = [
        '/TEKL[İI]F\s*NO\s*:?\s*((?:REF|MS)[\-\s]*\d+(?:[\-\s]*\d+)*)/iu',
        '/TEKL[İI]F\s*NO\s*:?\s*([0-9]{6,})/iu',
        '/\b((?:REF|MS)[\-\s]*\d+(?:[\-\s]*\d+)*)\b/iu',
    ];

    foreach ($offer_patterns as $pattern) {
        if (preg_match($pattern, $cleaned, $m)) {
            $offer_no = preg_replace('/\s+/', '', trim($m[1]));
            $offer_no = trim($offer_no, "-");
            break;
        }
    }

    $approval_no = $offer_no;

    if (preg_match('/İmalat\s+Onay\s+No\s+([A-Z0-9\-]+)/iu', $cleaned, $m)) {
        $approval_no = trim($m[1]);
    }

    // Tarih
    $date_patterns = [
        '/TAR[İI]H\s*[:\-]?\s*(\d{2}[\.\/-]\d{2}[\.\/-]\d{4})/iu',
        '/Teklif\s+Tarihi\s*[:\-]?\s*(\d{2}[\.\/-]\d{2}[\.\/-]\d{4})/iu',
        '/Sözleşme\s+Tarihi\s*[:\-]?\s*(\d{2}[\.\/-]\d{2}[\.\/-]\d{4})/iu',
        '/Tahmini\s+imalat\s+ve\s+montaj\s+tarihi\s+(\d{2}[\.\/-]\d{2}[\.\/-]\d{4})\s+ile\s+(\d{2}[\.\/-]\d{2}[\.\/-]\d{4})/iu',
    ];

    foreach ($date_patterns as $pattern) {
        if (preg_match($pattern, $cleaned, $m)) {
            if (isset($m[2])) {
                $date_range = str_replace(["/", "-"], ".", $m[1]) . " - " . str_replace(["/", "-"], ".", $m[2]);
            } else {
                $date_range = str_replace(["/", "-"], ".", $m[1]);
            }
            break;
        }
    }

    // Müşteri
    // Müşteri
    $customer = null;

    // 1) Satır bazlı okuma: Vertu tekliflerinde PDF tabloyu karıştırıyor.
    // Bu Mehmet EKE dosyası için isim genelde FİRMA: satırından sonraki ilk değer olarak geliyor.
    for ($i = 0; $i < count($lines); $i++) {
        $line = clean_text($lines[$i]);
        $asciiLine = strtoupper(tr_to_ascii($line));

        if (preg_match('/^(FIRMA|FİRMA)\s*:?\s*$/iu', $line)) {
            for ($j = $i + 1; $j < min($i + 6, count($lines)); $j++) {
                $candidate = clean_text($lines[$j]);
                $asciiCandidate = strtoupper(tr_to_ascii($candidate));

                // Etiketleri geç
                if (preg_match('/^(ILGILI KISI|İLGİLİ KİŞİ|ADRES|TELEFON|E-MAIL|E-MAİL|PROJE ADI|YAPILACAK IS|YAPILACAK İŞ|TEKLIF NO|TEKLİF NO)\s*:?\s*$/iu', $candidate)) {
                    continue;
                }

                // Telefon / adres gibi satırları alma
                if (preg_match('/\d{3,}/u', $candidate)) {
                    continue;
                }

                if (preg_match('/\b(ISTANBUL|İSTANBUL|ANKARA|IZMIR|İZMİR|MAH|CAD|SOK|NO)\b/iu', $candidate)) {
                    continue;
                }

                // Firma unvanı alma
                if (preg_match('/\b(LTD|ŞTİ|STI|SANAYI|SANAYİ|TICARET|TİCARET|ANONIM|ANONİM|A\.Ş|AS)\b/iu', $candidate)) {
                    continue;
                }

                // İsim gibi görünen 2-4 kelimelik satırı al
                if (preg_match('/^[A-ZÇĞİÖŞÜa-zçğıöşü]+(?:\s+[A-ZÇĞİÖŞÜa-zçğıöşü]+){1,3}$/u', $candidate)) {
                    $customer = mb_convert_case($candidate, MB_CASE_TITLE, "UTF-8");
                    break 2;
                }
            }
        }
    }

    // 2) Eğer yukarıdaki bulamazsa eski regex mantığı devam etsin
    if (!$customer) {
        $customer_patterns = [
            '/Müşteri\s*:?\s*(.+?)\s+Montaj\s*\/\s*Uygulama\s+Adresi/iu',
            '/Müşteri\s+Ad[ıi]\s*:?\s*(.+?)\s+Montaj\s*\/\s*Uygulama\s+Adresi/iu',
            '/Müşteri\s*:?\s*(.+?)\s+Uygulama\s+Adresi/iu',
            '/Müşteri\s*:?\s*(.+?)\s+Montaj\s+Adresi/iu',
            '/İLGİLİ\s+KİŞİ\s*:?\s*([A-ZÇĞİÖŞÜa-zçğıöşü\s\.]+?)\s+ADRES\s*:?/iu',
            '/F[İI]RMA\s*:?\s*([^\n]+?)\s+İLGİLİ\s+KİŞİ/iu',
        ];

        foreach ($customer_patterns as $pattern) {
            if (preg_match($pattern, $cleaned, $m)) {
                $candidate = clean_text($m[1]);
                $candidate = preg_replace('/\s+/', ' ', $candidate);
                $candidate = trim($candidate, " :-\t\n\r\0\x0B");

                if ($candidate !== "" && mb_strlen($candidate, "UTF-8") < 80) {
                    $customer = mb_convert_case($candidate, MB_CASE_TITLE, "UTF-8");
                    break;
                }
            }
        }
    }

    // Yeni Vertu formatında isim bazen İLGİLİ KİŞİ satırından sonra gelir.
    if (!$customer && preg_match('/İLGİLİ\s+KİŞİ\s*:?\s*([A-ZÇĞİÖŞÜa-zçğıöşü]+(?:\s+[A-ZÇĞİÖŞÜa-zçğıöşü]+){0,3})\s+ADRES/iu', $cleaned, $m)) {
        $customer = mb_convert_case(clean_text($m[1]), MB_CASE_TITLE, "UTF-8");
    }


    // ADRES / TELEFON / E-MAIL / PROJE ADI / YAPILACAK İŞ
    if (preg_match('/ADRES\s*:?\s*(.+?)\s+TELEFON\s*:?/iu', $cleaned, $m)) {
        $address = clean_text($m[1]);
    }

    if (preg_match('/TELEFON\s*:?\s*([0-9\s\+\(\)]+)\s+E-?MA[İI]L\s*:?/iu', $cleaned, $m)) {
        $phone = clean_text($m[1]);
    }

    if (preg_match('/E-?MA[İI]L\s*:?\s*(.*?)\s+PROJE\s+ADI\s*:?/iu', $cleaned, $m)) {
        $email = clean_text($m[1]);
    }

    if (preg_match('/PROJE\s+ADI\s*:?\s*(.*?)\s+YAPILACAK\s+[İI]Ş\s*:?/iu', $cleaned, $m)) {
        $project_name = clean_text($m[1]);
    }

    if (preg_match('/YAPILACAK\s+[İI]Ş\s*:?\s*(.*?)\s+TEKL[İI]F\s+NO\s*:?/iu', $cleaned, $m)) {
        $work_title = clean_text($m[1]);
    }

    // Para alanları
    $subtotal_matches = [];
    preg_match_all('/(?:ARA\s+TOPLAM|KDV\s*HAR[İI]Ç\s+TOPLAM|TUTAR)\s+₺?\s*([\d\.\,]+)\s*(?:TL)?/iu', $cleaned, $subtotal_matches);
    if (!empty($subtotal_matches[1])) {
        $subtotal_price = clean_price_text_local(end($subtotal_matches[1]));
    }

    $vat_matches = [];
    preg_match_all('/KDV\s*(?:\(?%?20\)?|%20)?\s+₺?\s*([\d\.\,]+)\s*(?:TL)?/iu', $cleaned, $vat_matches);
    if (!empty($vat_matches[1])) {
        $vat_price = clean_price_text_local(end($vat_matches[1]));
    }

    $grand_matches = [];
    preg_match_all('/(?:GENEL\s+TOPLAM|\bTOPLAM)\s+₺?\s*([\d\.\,]+)\s*(?:TL)?/iu', $cleaned, $grand_matches);
    if (!empty($grand_matches[1])) {
        $grand_total_price = clean_price_text_local(end($grand_matches[1]));
    }

    if ($grand_total_price) {
        $contract_price = $grand_total_price;
    }

    if (preg_match('/Satıcı\/Yüklenici\s+(.+?)\s+Marka/iu', $cleaned, $m)) {
        $seller = clean_text($m[1]);
    }

    if (preg_match('/Marka\s+(.+?)\s+Müşteri/iu', $cleaned, $m)) {
        $brand = clean_text($m[1]);
    }

    return [
        "customer" => $customer,
        "offer_no" => $offer_no,
        "approval_no" => $approval_no,
        "contract_price" => $contract_price,
        "subtotal_price" => $subtotal_price,
        "vat_price" => $vat_price,
        "grand_total_price" => $grand_total_price,
        "seller" => $seller,
        "brand" => $brand,
        "date_range" => $date_range,
        "address" => $address,
        "phone" => $phone,
        "email" => $email,
        "project_name" => $project_name,
        "work_title" => $work_title,
    ];
}

function is_offer_area_line($line)
{
    $ascii = strtoupper(tr_to_ascii(clean_text($line)));

    $valid = [
        "HAVUZ", "HAVUZ ONU", "HAVUZ ÖNÜ", "UST", "ÜST", "UST TERAS", "ÜST TERAS",
        "TERAS", "BAHCE", "BAHÇE", "ARKA", "YAN", "ON", "ÖN", "CAM", "BIOKLIMATIK",
        "BİOKLİMATİK", "TAVAN", "SOL CEPHE", "SAG CEPHE", "SAĞ CEPHE", "ON CEPHE", "ÖN CEPHE", "ARKA CEPHE"
    ];

    foreach ($valid as $v) {
        if ($ascii === strtoupper(tr_to_ascii($v))) {
            return true;
        }
    }

    if (preg_match('/^\d+\s*\((TAVAN|SOL CEPHE|SAG CEPHE|SAĞ CEPHE|ON CEPHE|ÖN CEPHE|ARKA CEPHE)\)$/iu', $line)) {
        return true;
    }

    return false;
}

function is_measure_price_line($line)
{
    $line = clean_text($line);
    $ascii = strtoupper(tr_to_ascii($line));

    if (preg_match('/\d+[,.]\d+\s*m\b/iu', $line)) {
        return true;
    }

    if (preg_match('/\d+[,.]\d+\s+\d+[,.]\d+/', $line) && preg_match('/\b(AD|M2|M²|M\^2|ADET)\b/u', $ascii)) {
        return true;
    }

    if (preg_match('/\d+[,.]\d+\s+\d+[,.]\d+\s+\d+[,.]\d+/', $line)) {
        return true;
    }

    return false;
}

function parse_measure_price_line($line)
{
    $line = clean_text($line);
    $line = str_replace("₺", " ₺ ", $line);
    $ascii = strtoupper(tr_to_ascii($line));

    // m'li yeni format: 3.45 m 3.50 m 2.80 m 12.08 1 m²
    if (preg_match('/(\d+[,.]\d+)\s*m\s+(-|\d+[,.]\d+\s*m)\s+(\d+[,.]\d+)\s*m\s+(\d+[,.]\d+)\s+(\d+)\s*(?:m²|m2|M2|M²)/iu', $line, $m)) {
        $depth = trim(str_replace("m", "", $m[2]));
        return [
            "width" => $m[1],
            "depth" => $depth,
            "height" => $m[3],
            "area_m2" => $m[4],
            "quantity" => $m[5],
            "unit" => "m²",
            "unit_price" => "-",
            "total_price" => "-",
        ];
    }

    $unit = "-";
    if (preg_match('/\b(AD|M2|M²|M\^2|ADET)\b/iu', $line, $unit_match)) {
        $unit = $unit_match[1];
    }

    $numbers = [];
    if (preg_match_all('/\d+[,.]\d+|\d+/', $line, $matches)) {
        $numbers = $matches[0];
    }

    $width = "-";
    $depth = "-";
    $height = "-";
    $area_m2 = "-";
    $quantity = "1";
    $unit_price = "-";
    $total_price = "-";

    // Unit öncesindeki sayıları bulmak daha doğru.
    $before_unit = $numbers;
    if (preg_match('/^(.*?)(?:\bAD\b|\bM2\b|M²|ADET)/iu', $line, $bu)) {
        $before_unit = [];
        if (preg_match_all('/\d+[,.]\d+|\d+/', $bu[1], $bm)) {
            $before_unit = $bm[0];
        }
    }

    $c = count($before_unit);

    if ($c >= 5) {
        $width = $before_unit[0];
        $depth = $before_unit[1];
        $height = $before_unit[2];
        $area_m2 = $before_unit[3];
        $quantity = $before_unit[4];
    } elseif ($c === 4) {
        $width = $before_unit[0];
        $depth = "-";
        $height = $before_unit[1];
        $area_m2 = $before_unit[2];
        $quantity = $before_unit[3];
    } elseif ($c === 3) {
        $width = "-";
        $depth = "-";
        $height = $before_unit[0];
        $area_m2 = $before_unit[1];
        $quantity = $before_unit[2];
    } elseif ($c === 1) {
        $quantity = $before_unit[0];
    }

    return [
        "width" => $width,
        "depth" => $depth,
        "height" => $height,
        "area_m2" => $area_m2,
        "quantity" => $quantity,
        "unit" => $unit,
        "unit_price" => $unit_price,
        "total_price" => $total_price,
    ];
}


function proforma_row_area_from_description($description)
{
    $desc_ascii = strtoupper(tr_to_ascii($description));

    if (strpos($desc_ascii, "CEPHE KAPAMA") !== false || strpos($desc_ascii, "KOMPOZIT") !== false) {
        return "CEPHE";
    }

    if (
        strpos($desc_ascii, "TAVAN") !== false ||
        strpos($desc_ascii, "PERGOLA") !== false ||
        strpos($desc_ascii, "BIOKLIMATIK") !== false ||
        strpos($desc_ascii, "BIYOKLIMATIK") !== false ||
        strpos($desc_ascii, "SANDVIC") !== false
    ) {
        return "TAVAN";
    }

    if (
        strpos($desc_ascii, "GIYOTIN") !== false ||
        strpos($desc_ascii, "SURME CAM") !== false ||
        strpos($desc_ascii, "SABIT CAM") !== false ||
        strpos($desc_ascii, "CAM SISTEMI") !== false
    ) {
        return "ÖN CEPHE";
    }

    return "GENEL";
}

function clean_proforma_description($description)
{
    $description = clean_text($description);

    // Fiyat, link, başlık kalıntılarını açıklamadan temizle
    $description = preg_replace('/https?:\/\/\S+/iu', ' ', $description);
    $description = preg_replace('/\b\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\s*TL\b/iu', ' ', $description);
    $description = preg_replace('/\bTL\s*\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\b/iu', ' ', $description);
    $description = preg_replace('/\b(FIYAT|FİYAT|TEKLIFI|TEKLİFİ|PROFORMA|URUN|ÜRÜN|ACIKLAMASI|AÇIKLAMASI)\b/iu', ' ', $description);
    $description = clean_text($description);

    return $description;
}

function parse_proforma_rows_general($text)
{
    $rows = [];
    $norm_all = strtoupper(tr_to_ascii((string)$text));

    if (
        strpos($norm_all, "FIYAT TEKLIFI / PROFORMA") === false &&
        strpos($norm_all, "URUN ACIKLAMASI") === false
    ) {
        return [];
    }

    $seen = [];

    $add_row = function ($description, $width, $depth, $height, $area_m2, $quantity, $unit) use (&$rows, &$seen) {
        $description = clean_proforma_description($description);
        $desc_ascii = strtoupper(tr_to_ascii($description));

        if ($description === "") {
            return;
        }

        if (
            strpos($desc_ascii, "TUTAR") !== false ||
            strpos($desc_ascii, "KDV") !== false ||
            strpos($desc_ascii, "TOPLAM") !== false ||
            strpos($desc_ascii, "PROJE DETAYLARI") !== false ||
            strpos($desc_ascii, "FIRMA BILGILERI") !== false
        ) {
            return;
        }

        $key = strtoupper(tr_to_ascii($description)) . "|" . clean_text($width) . "|" . clean_text($height) . "|" . clean_text($quantity);
        if (isset($seen[$key])) {
            return;
        }
        $seen[$key] = true;

        $depth = str_replace("m", "", clean_text($depth));
        $depth = trim($depth);

        $rows[] = [
            "area" => normalize_offer_area(proforma_row_area_from_description($description)),
            "description" => $description,
            "width" => clean_text($width),
            "depth" => $depth,
            "height" => clean_text($height),
            "area_m2" => clean_text($area_m2),
            "quantity" => clean_text($quantity),
            "unit" => clean_text($unit ?: "m²"),
            "unit_price" => "",
            "total_price" => "",
        ];
    };

    // 1) Layout metin: ürün ilk satırda ölçülerle gelir, açıklama devamı alt satırlardadır.
    $lines = normalize_pdf_lines($text);
    $line_count = count($lines);

    $line_pattern = '/^\s*(\d{1,3})\s+(.+?)\s+(\d+[,.]\d+)\s*m\s+(-|\d+[,.]\d+\s*m)\s+(\d+[,.]\d+)\s*m\s+(\d+[,.]\d+)\s+(\d+)\s*(m²|m2|M2|M²)\b/iu';

    for ($i = 0; $i < $line_count; $i++) {
        $line = clean_text($lines[$i]);

        if (!preg_match($line_pattern, $line, $m)) {
            continue;
        }

        $desc_parts = [clean_text($m[2])];
        $j = $i + 1;

        while ($j < $line_count) {
            $next = clean_text($lines[$j]);
            $next_ascii = strtoupper(tr_to_ascii($next));

            if ($next === "") {
                $j++;
                continue;
            }

            if (preg_match($line_pattern, $next)) {
                break;
            }

            if (
                strpos($next_ascii, "PROJE DETAYLARI") !== false ||
                strpos($next_ascii, "TUTAR") !== false ||
                strpos($next_ascii, "KDV") !== false ||
                strpos($next_ascii, "TOPLAM") !== false ||
                strpos($next_ascii, "FIRMA BILGILERI") !== false ||
                strpos($next_ascii, "TEKLIF SARTLARI") !== false ||
                strpos($next_ascii, "HTTPS") !== false
            ) {
                break;
            }

            // devam açıklaması: ölçü/fiyat satırı değilse ekle
            if (
                strpos($next_ascii, "TL") === false &&
                !preg_match('/\d+[,.]\d+\s*m/iu', $next) &&
                !preg_match('/^(NO|CEPHE|ACILIM|AÇILIM|YUKSEKLIK|YÜKSEKLIK|ALAN|ADET|BIRIM|BİRİM)\b/iu', $next)
            ) {
                $desc_parts[] = $next;
            }

            $j++;

            if (count($desc_parts) > 12) {
                break;
            }
        }

        $add_row(implode(" ", $desc_parts), $m[3], $m[4], $m[5], $m[6], $m[7], $m[8]);
    }

    if (!empty($rows)) {
        return $rows;
    }

    // 2) Smalot düz metin: ürün açıklaması ölçülerden önce tek parça gelmiş olabilir.
    $flat = str_replace(["\r", "\n", "\t"], " ", (string)$text);
    $flat = preg_replace('/\s+/u', ' ', $flat);
    $flat = clean_text($flat);

    $flat_pattern = '/(?:^|\s)(\d{1,3})\s+(.+?)\s+(\d+[,.]\d+)\s*m\s+(-|\d+[,.]\d+\s*m)\s+(\d+[,.]\d+)\s*m\s+(\d+[,.]\d+)\s+(\d+)\s*(m²|m2|M2|M²)\b(?:\s+\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\s*TL)?(?:\s+\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\s*TL)?/iu';

    if (preg_match_all($flat_pattern, $flat, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $m) {
            $description = clean_text($m[2]);

            // Bir önceki satır fiyatı açıklamaya yapıştıysa son ürün numarasından sonrasını al.
            $description = preg_replace('/^.*?\b\d{1,3}(?:[.,]\d{3})*(?:[.,]\d{2})?\s*TL\s*/iu', '', $description);
            $description = preg_replace('/^\d+\s*/u', '', $description);
            $description = clean_text($description);

            $add_row($description, $m[3], $m[4], $m[5], $m[6], $m[7], $m[8]);
        }
    }

    return $rows;
}

function extract_offer_rows_from_plain_text($text)
{
    $rows = [];
    if (detect_offer_format($text) === "cam_dograma_odeme") {
        return parse_cam_dograma_teklif_rows($text);
    }

    $cleaned_all = clean_text($text);

    $proforma_rows = parse_proforma_rows_general($text);
    if (!empty($proforma_rows)) {
        return $proforma_rows;
    }

    // -------------------------------------------------
    // 1) PIPE TABLE FORMAT
    // Eski Python mantığı:
    // cells[0] = alan
    // cells[1] = ürün açıklaması
    // cells[2] = cephe/en
    // cells[3] = açılım/boy
    // cells[4] = yükseklik
    // cells[5] = alan
    // cells[6] = adet
    // -------------------------------------------------
    foreach (explode("\n", $text) as $line) {
        if (strpos($line, "|") === false) {
            continue;
        }

        $cells = array_map("clean_text", explode("|", $line));

        if (count($cells) < 7) {
            continue;
        }

        $joined = strtoupper(tr_to_ascii(implode(" ", $cells)));

        if (
            strpos($joined, "URUN ACIKLAMASI") !== false ||
            strpos($joined, "BIRIM FIYAT") !== false ||
            strpos($joined, "TOPLAM") === 0 ||
            strpos($joined, "KDV") === 0
        ) {
            continue;
        }

        if (ctype_digit(trim($cells[0]))) {
            $area = "GENEL";
            $description = clean_text($cells[1] ?? "");
            $width = $cells[2] ?? "";
            $depth = $cells[3] ?? "";
            $height = $cells[4] ?? "";
            $area_m2 = $cells[5] ?? "";
            $quantity = $cells[6] ?? "1";
        } else {
            $area = clean_text($cells[0] ?? "");
            $description = clean_text($cells[1] ?? "");
            $width = $cells[2] ?? "";
            $depth = $cells[3] ?? "";
            $height = $cells[4] ?? "";
            $area_m2 = $cells[5] ?? "";
            $quantity = $cells[6] ?? "1";
        }

        if ($description === "") {
            continue;
        }

        // Bu tip bozuk satırlar ürün satırı değil, başlık/opsiyon satırı gibi geliyor
        $area_ascii = strtoupper(tr_to_ascii($area));
        $desc_ascii = strtoupper(tr_to_ascii($description));

        if (
            $area_ascii === "BIOKLIMATIK" ||
            $area_ascii === "BIYOKLIMATIK" ||
            $area_ascii === "CAM"
        ) {
            // Burayı komple çöpe atmıyoruz; ama modül üretmesin diye rows'a eklemiyoruz.
            continue;
        }

        if (parse_measure_cell($width) <= 0) {
            continue;
        }

        $rows[] = [
            "area" => normalize_offer_area($area),
            "description" => $description,
            "width" => $width,
            "depth" => $depth,
            "height" => $height,
            "area_m2" => $area_m2,
            "quantity" => $quantity,
            "unit" => "",
            "unit_price" => "",
            "total_price" => "",
        ];
    }

    // -------------------------------------------------
    // 2) YENİ VERTU FORMAT
    // Örnek:
    // 1 (Tavan) Vertu Elit ... 3.45 m 3.50 m 2.80 m 12.08 1 m²
    // -------------------------------------------------
    $pattern_new = '/\b(\d+)\s*\(([^)]+)\)\s+(.*?)\s+(\d+[,.]\d+)\s*m\s+(-|\d+[,.]\d+\s*m)\s+(\d+[,.]\d+)\s*m\s+(\d+[,.]\d+)\s+(\d+)\s*(?:m²|m2|M2|M²)/iu';

    if (preg_match_all($pattern_new, $cleaned_all, $matches_new, PREG_SET_ORDER)) {
        foreach ($matches_new as $m) {
            $depth_value = clean_text($m[5]);
            $depth_value = str_replace("m", "", $depth_value);
            $depth_value = trim($depth_value);

            $rows[] = [
                "area" => normalize_offer_area($m[2]),
                "description" => clean_text($m[3]),
                "width" => clean_text($m[4]),
                "depth" => $depth_value,
                "height" => clean_text($m[6]),
                "area_m2" => clean_text($m[7]),
                "quantity" => clean_text($m[8]),
                "unit" => "m²",
                "unit_price" => "",
                "total_price" => "",
            ];
        }
    }


    // -------------------------------------------------
    // -------------------------------------------------
    // 2B) SATIR SATIR PDF TABLO FORMAT
    // Bu tekliflerde PDF tabloyu alt alta okuyor:
    // 1
    // SANDVİÇ PANELLİ
    // SABİT TAVAN
    // 3.92 m
    // 1.40 m
    // 2.45 m
    // 5.49
    // 1
    // m²
    // -------------------------------------------------
    if (
        stripos(tr_to_ascii($text), "FIYAT TEKLIFI / PROFORMA") !== false &&
        stripos(tr_to_ascii($text), "URUN ACIKLAMASI") !== false
    ) {
        $lines = normalize_pdf_lines($text);
        $line_count = count($lines);

        for ($i = 0; $i < $line_count; $i++) {
            if (!preg_match('/^\d+$/u', $lines[$i])) {
                continue;
            }

            $row_no = intval($lines[$i]);

            if ($row_no <= 0 || $row_no > 50) {
                continue;
            }

            $j = $i + 1;
            $desc_parts = [];

            while (
                $j < $line_count &&
                !preg_match('/^\d+[,.]\d+\s*m$/iu', $lines[$j])
            ) {
                $part = clean_text($lines[$j]);
                $part_ascii = strtoupper(tr_to_ascii($part));

                if (
                    $part_ascii === "TUTAR" ||
                    strpos($part_ascii, "KDV") !== false ||
                    $part_ascii === "TOPLAM" ||
                    strpos($part_ascii, "PROJE DETAYLARI") !== false ||
                    strpos($part_ascii, "FIRMA BILGILERI") !== false
                ) {
                    break;
                }

                $desc_parts[] = $part;
                $j++;

                if (count($desc_parts) > 15) {
                    break;
                }
            }

            if (empty($desc_parts)) {
                continue;
            }

            $width = $lines[$j] ?? "";
            $depth = $lines[$j + 1] ?? "";
            $height = $lines[$j + 2] ?? "";
            $area_m2 = $lines[$j + 3] ?? "";
            $quantity = $lines[$j + 4] ?? "";
            $unit = $lines[$j + 5] ?? "";

            if (!preg_match('/^\d+[,.]\d+\s*m$/iu', $width)) {
                continue;
            }

            if (!preg_match('/^(-|\d+[,.]\d+\s*m)$/iu', $depth)) {
                continue;
            }

            if (!preg_match('/^\d+[,.]\d+\s*m$/iu', $height)) {
                continue;
            }

            if (!preg_match('/^\d+[,.]?\d*$/u', $area_m2)) {
                continue;
            }

            if (!preg_match('/^\d+$/u', $quantity)) {
                continue;
            }

            if (stripos($unit, "m") === false) {
                continue;
            }

            $description = clean_text(implode(" ", $desc_parts));
            $desc_ascii = strtoupper(tr_to_ascii($description));

            if (
                strpos($desc_ascii, "URUN ACIKLAMASI") !== false ||
                strpos($desc_ascii, "BIRIM FIYAT") !== false ||
                strpos($desc_ascii, "FIYAT") !== false ||
                strpos($desc_ascii, "TOPLAM") !== false
            ) {
                continue;
            }

            $area = "GENEL";

            if (
                strpos($desc_ascii, "CEPHE KAPAMA") !== false ||
                strpos($desc_ascii, "KOMPOZIT") !== false
            ) {
                $area = "CEPHE";
            } elseif (
                strpos($desc_ascii, "TAVAN") !== false ||
                strpos($desc_ascii, "PERGOLA") !== false ||
                strpos($desc_ascii, "BIOKLIMATIK") !== false ||
                strpos($desc_ascii, "BIYOKLIMATIK") !== false ||
                strpos($desc_ascii, "SANDVIC") !== false
            ) {
                $area = "TAVAN";
            } elseif (
                strpos($desc_ascii, "GIYOTIN") !== false ||
                strpos($desc_ascii, "SURME CAM") !== false ||
                strpos($desc_ascii, "SABIT CAM") !== false ||
                strpos($desc_ascii, "CAM SISTEMI") !== false
            ) {
                $area = "ÖN CEPHE";
            }

            $width = str_replace("m", "", $width);
            $depth = str_replace("m", "", $depth);
            $height = str_replace("m", "", $height);

            $rows[] = [
                "area" => normalize_offer_area($area),
                "description" => $description,
                "width" => clean_text($width),
                "depth" => clean_text($depth),
                "height" => clean_text($height),
                "area_m2" => clean_text($area_m2),
                "quantity" => clean_text($quantity),
                "unit" => "m²",
                "unit_price" => "",
                "total_price" => "",
            ];
        }
    }

    // -------------------------------------------------
    // 3) ESKİ DÜZ METİN FORMAT - 5 ölçülü
    // Örnek:
    // HAVUZ ÖNÜ ... PERGOLA 5,00 2,00 2,90 10,00 1 AD
    // -------------------------------------------------
    $area_pattern = '(HAVUZ\s+ÖNÜ|HAVUZ\s+ONU|ÜST\s+TERAS|UST\s+TERAS|ARKA\s+CEPHE|ÖN\s+CEPHE|ON\s+CEPHE|SOL\s+CEPHE|SAĞ\s+CEPHE|SAG\s+CEPHE|YAN\s+CEPHE|ARKA|ÜST|UST|ÖN|ON|TAVAN)';

    $pattern_old_module = '/' .
        $area_pattern .
        '\s+' .
        '(.+?)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+)' .
        '\s+' .
        '(?:AD|M2|M²|m2|m²)' .
        '/iu';

    if (preg_match_all($pattern_old_module, $cleaned_all, $matches_old, PREG_SET_ORDER)) {
        foreach ($matches_old as $m) {
            $area = normalize_offer_area($m[1]);
            $description = clean_text($m[2]);
            $description = preg_replace('/^TL\s*[\d.,]+\s*TL\s*/iu', '', $description);
            $description = preg_replace('/^\d+\s*/u', '', $description);
            $description = clean_text($description);

            $desc_ascii = strtoupper(tr_to_ascii($description));

            // Başlık/opsiyon satırlarını alma
            if (
                strpos($desc_ascii, "KONFOR PAKETI") !== false ||
                strpos($desc_ascii, "4 MEVSIM") !== false
            ) {
                continue;
            }

            $rows[] = [
                "area" => $area,
                "description" => $description,
                "width" => clean_text($m[3]),
                "depth" => clean_text($m[4]),
                "height" => clean_text($m[5]),
                "area_m2" => clean_text($m[6]),
                "quantity" => clean_text($m[7]),
                "unit" => "AD",
                "unit_price" => "",
                "total_price" => "",
            ];
        }
    }

    // -------------------------------------------------
    // 4) ESKİ DÜZ METİN FORMAT - 4 ölçülü cam/panel
    // Örnek:
    // ÜST VERTU ELİT SÜRME CAM SİSTEMİ Temperli Güvenlik Camı 5,30 2,90 15,37 2 M2
    // ARKA POLİÜRETAN SANDVİÇ PANEL CEPHE KAPAMA 4,15 2,90 12,04 2 M2
    // Burada depth yok; height ikinci ölçü.
    // -------------------------------------------------
    $pattern_old_facade = '/' .
        $area_pattern .
        '\s+' .
        '(.+?)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+[,.]\d+)' .
        '\s+' .
        '(\d+)' .
        '\s+' .
        '(?:M2|M²|m2|m²)' .
        '/iu';

    if (preg_match_all($pattern_old_facade, $cleaned_all, $matches_facade, PREG_SET_ORDER)) {
        foreach ($matches_facade as $m) {
            $area = normalize_offer_area($m[1]);
            $description = clean_text($m[2]);

            $desc_ascii = strtoupper(tr_to_ascii($description));

            // Modül satırları zaten 5 ölçülü formatta yakalandı; burada tekrar alma
            if (
                strpos($desc_ascii, "BIOKLIMATIK") !== false ||
                strpos($desc_ascii, "BIYOKLIMATIK") !== false ||
                strpos($desc_ascii, "PERGOLA") !== false
            ) {
                continue;
            }

            $rows[] = [
                "area" => $area,
                "description" => $description,
                "width" => clean_text($m[3]),
                "depth" => "0",
                "height" => clean_text($m[4]),
                "area_m2" => clean_text($m[5]),
                "quantity" => clean_text($m[6]),
                "unit" => "M2",
                "unit_price" => "",
                "total_price" => "",
            ];
        }
    }

    // -------------------------------------------------
    // TEKRAR TEMİZLE
    // -------------------------------------------------
    $unique = [];
    $clean_rows = [];

    foreach ($rows as $row) {
        $key = strtoupper(tr_to_ascii(
            ($row["area"] ?? "") . "|" .
            ($row["description"] ?? "") . "|" .
            ($row["width"] ?? "") . "|" .
            ($row["depth"] ?? "") . "|" .
            ($row["height"] ?? "") . "|" .
            ($row["quantity"] ?? "")
        ));

        if (isset($unique[$key])) {
            continue;
        }

        $unique[$key] = true;
        $clean_rows[] = $row;
    }

    return $clean_rows;
}




function find_modules($text)
{
    $modules = [];
    $rows = extract_offer_rows_from_plain_text($text);

    foreach ($rows as $row) {
        $description = clean_text($row["description"] ?? "");
        $area = clean_text($row["area"] ?? "");

        if ($description === "") {
            continue;
        }

        $desc_ascii = strtoupper(tr_to_ascii($description));
        $area_ascii = strtoupper(tr_to_ascii($area));

        // Bunlar gerçek tavan/modül ürünleri
        $is_bioklimatik =
            strpos($desc_ascii, "BIOKLIMATIK") !== false ||
            strpos($desc_ascii, "BIYOKLIMATIK") !== false ||
            strpos($desc_ascii, "PERGOLA") !== false;

        $is_fixed_roof =
            (
                strpos($desc_ascii, "SANDVIC") !== false &&
                strpos($desc_ascii, "TAVAN") !== false
            ) ||
            (
                strpos($desc_ascii, "SABIT") !== false &&
                strpos($desc_ascii, "TAVAN") !== false
            );

        // Bunlar kesinlikle modül değil
        $is_not_module =
            strpos($desc_ascii, "SURME CAM") !== false ||
            strpos($desc_ascii, "ISICAMLI SURME") !== false ||
            strpos($desc_ascii, "SABIT CAM") !== false ||
            strpos($desc_ascii, "GIYOTIN CAM") !== false ||
            strpos($desc_ascii, "KARKAS") !== false ||
            strpos($desc_ascii, "KONFOR PAKETI") !== false ||
            strpos($desc_ascii, "4 MEVSIM") !== false ||
            $area_ascii === "BIOKLIMATIK" ||
            $area_ascii === "BIYOKLIMATIK" ||
            $area_ascii === "CAM";

        if ((!$is_bioklimatik && !$is_fixed_roof) || $is_not_module) {
            continue;
        }

        $width = parse_measure_cell($row["width"] ?? 0);
        $depth = parse_measure_cell($row["depth"] ?? 0);
        $height = parse_measure_cell($row["height"] ?? 0);

        if ($width <= 0 || $height <= 0) {
            continue;
        }

        $quantity = intval($row["quantity"] ?? 1);

        if ($quantity <= 0) {
            $quantity = 1;
        }

        $clean_area = normalize_offer_area($area);

        if ($clean_area === "" || is_numeric($clean_area)) {
            $clean_area = "GENEL";
        }

        $module_id = count($modules) + 1;

        $modules[] = [
            "id" => $module_id,
            "name" => "MODÜL " . $module_id,
            "area" => $clean_area,
            "width" => $width,
            "depth" => $depth,
            "height" => $height,
            "quantity" => $quantity,
            "system" => $description,
            "drawing_type" => ($is_fixed_roof ? "fixed_roof" : "roof"),
            "raw_product" => $description,
        ];
    }

    return $modules;
}

function find_sliding_glass($text)
{
    $items = [];
    $rows = extract_offer_rows_from_plain_text($text);

    foreach ($rows as $row) {
        $description = clean_text($row["description"] ?? "");
        $area = clean_text($row["area"] ?? "");

        if ($description === "") continue;

        $desc_ascii = strtoupper(tr_to_ascii($description));

        $is_sliding =
            strpos($desc_ascii, "SURME CAM") !== false ||
            strpos($desc_ascii, "ISICAMLI SURME") !== false;

        $is_fixed =
            strpos($desc_ascii, "SABIT CAM") !== false ||
            strpos($desc_ascii, "SABIT CAM DOGRAMA") !== false;

        $is_guillotine = strpos($desc_ascii, "GIYOTIN CAM") !== false;

        $is_door =
            strpos($desc_ascii, "KAPI DOGRAMA") !== false ||
            strpos($desc_ascii, "ALUMINYUM KAPI") !== false;

        if (!$is_sliding && !$is_fixed && !$is_guillotine && !$is_door) continue;

        $width = parse_measure_cell($row["width"] ?? 0);
        $height = parse_measure_cell($row["height"] ?? 0);

        if ($width <= 0 || $height <= 0) continue;

        $quantity = intval($row["quantity"] ?? 1);
        if ($quantity <= 0) $quantity = 1;

        if ($is_sliding) {
            $drawing_type = "sliding_glass";
        } elseif ($is_fixed) {
            $drawing_type = "fixed_glass";
        } elseif ($is_guillotine) {
            $drawing_type = "guillotine_glass";
        } else {
            $drawing_type = "read_only";
        }

        $items[] = [
            "id" => count($items) + 1,
            "area" => normalize_offer_area($area),
            "width" => $width,
            "depth" => parse_measure_cell($row["depth"] ?? 0),
            "height" => $height,
            "quantity" => $quantity,
            "description" => $description,
            "drawing_type" => $drawing_type,
        ];
    }

    return $items;
}

function find_panels($text)
{
    $panels = [];
    $id = 1;

    $plain = (string)$text;
    $norm = strtoupper(tr_to_ascii($plain));

    // 1) BİOKLİMATİK KONFOR PAKETİ satırı
    if (
        strpos($norm, "BIOKLIMATIK") !== false &&
        strpos($norm, "KONFOR PAKETI") !== false
    ) {
        $panels[] = [
            "id" => $id++,
            "area" => "BİOKLİMATİK",
            "width" => 0,
            "height" => 0,
            "quantity" => 1,
            "description" => "VERTU PREMIUM 4 MEVSİM KONFOR PAKETİ Poliüretan Dolgu ile Yüksek Isı ve Ses İzolasyonu, Galvanizli Çelik Aksesuar ve Premium LED Aydınlatma Sistemi Somfy Motor",
        ];
    }

    // 2) ARKA POLİÜRETAN SANDVİÇ PANEL CEPHE KAPAMA satırı
    if (
        preg_match(
            '/ARKA\s+POL[İI]ÜRETAN\s+SANDV[İI]Ç\s+PANEL\s+CEPHE\s+KAPAMA\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)\s+(\d+)/iu',
            $plain,
            $m
        )
    ) {
        $panels[] = [
            "id" => $id++,
            "area" => "ARKA",
            "width" => parse_measure_cell($m[1]),
            "height" => parse_measure_cell($m[2]),
            "quantity" => intval($m[4]),
            "description" => "POLİÜRETAN SANDVİÇ PANEL CEPHE KAPAMA",
        ];
    } else {
        // Daha gevşek yedek yakalama
        if (preg_match('/ARKA.*?SANDV[İI]Ç.*?CEPHE\s+KAPAMA.*?([\d,.]+).*?([\d,.]+).*?(\d+)\s+M2/isu', $plain, $m)) {
            $panels[] = [
                "id" => $id++,
                "area" => "ARKA",
                "width" => parse_measure_cell($m[1]),
                "height" => parse_measure_cell($m[2]),
                "quantity" => intval($m[3]),
                "description" => "POLİÜRETAN SANDVİÇ PANEL CEPHE KAPAMA",
            ];
        }
    }

    // 3) CAM KONFOR PAKETİ satırı
    if (
        strpos($norm, "CAM") !== false &&
        strpos($norm, "KONFOR ISICAM") !== false
    ) {
        $panels[] = [
            "id" => $id++,
            "area" => "CAM",
            "width" => 0,
            "height" => 0,
            "quantity" => 1,
            "description" => "VERTU PREMIUM 4 MEVSİM KONFOR PAKETİ 4+12+4 Temperli Konfor Isıcam ile Solar Low-E Kaplamalı Yüksek Isı ve Ses İzolasyonu",
        ];
    }


    // 4) Genel teklif satırlarından panel / kompozit cephe kapama yakala
    $rows = extract_offer_rows_from_plain_text($text);

    foreach ($rows as $row) {
        $description = clean_text($row["description"] ?? "");

        if ($description === "") {
            continue;
        }

        $desc_ascii = strtoupper(tr_to_ascii($description));

        $is_panel =
            strpos($desc_ascii, "CEPHE KAPAMA") !== false ||
            strpos($desc_ascii, "KOMPOZIT") !== false ||
            (
                strpos($desc_ascii, "SANDVIC PANEL") !== false &&
                strpos($desc_ascii, "TAVAN") === false
            );

        if (!$is_panel) {
            continue;
        }

        $width = parse_measure_cell($row["width"] ?? 0);
        $height = parse_measure_cell($row["height"] ?? 0);
        $quantity = intval($row["quantity"] ?? 1);

        if ($width <= 0 || $height <= 0) {
            continue;
        }

        if ($quantity <= 0) {
            $quantity = 1;
        }

        $dup = false;
        foreach ($panels as $p) {
            if (
                round((float)($p["width"] ?? 0), 2) === round($width, 2) &&
                round((float)($p["height"] ?? 0), 2) === round($height, 2) &&
                strtoupper(tr_to_ascii($p["description"] ?? "")) === $desc_ascii
            ) {
                $dup = true;
                break;
            }
        }

        if ($dup) {
            continue;
        }

        $panels[] = [
            "id" => $id++,
            "area" => normalize_offer_area($row["area"] ?? "CEPHE"),
            "width" => $width,
            "height" => $height,
            "quantity" => $quantity,
            "description" => $description,
            "drawing_type" => "wall_panel",
            "category" => "panel",
        ];
    }

    return $panels;
}


function find_contract_offer_rows($text)
{
    $rows = extract_offer_rows_from_plain_text($text);
    $contract_rows = [];

    foreach ($rows as $row) {
        $contract_rows[] = [
            "area" => normalize_offer_area($row["area"] ?? "-"),
            "description" => clean_text($row["description"] ?? "-"),
            "width" => $row["width"] ?? "-",
            "depth" => $row["depth"] ?? "-",
            "height" => $row["height"] ?? "-",
            "area_m2" => $row["area_m2"] ?? "-",
            "quantity" => $row["quantity"] ?? "1",
            "unit" => $row["unit"] ?? "-",
            "unit_price" => $row["unit_price"] ?? "-",
            "total_price" => $row["total_price"] ?? "-",
        ];
    }

    return $contract_rows;
}

function get_technical_features($project_data)
{
    $features = [];

    $add = function ($text) use (&$features) {
        if (!in_array($text, $features, true)) {
            $features[] = $text;
        }
    };

    $parts = [];

    foreach ($project_data["modules"] ?? [] as $m) {
        $parts[] = $m["system"] ?? "";
        $parts[] = $m["raw_product"] ?? "";
        $parts[] = $m["area"] ?? "";
    }

    foreach ($project_data["sliding_glass"] ?? [] as $g) {
        $parts[] = $g["description"] ?? "";
        $parts[] = $g["area"] ?? "";
    }

    foreach ($project_data["panels"] ?? [] as $p) {
        $parts[] = $p["description"] ?? "";
        $parts[] = $p["area"] ?? "";
    }

    $full = strtoupper(tr_to_ascii(implode(" ", $parts)));

    if (strpos($full, "BIOKLIMATIK") !== false || strpos($full, "BIYOKLIMATIK") !== false) {
        $add("Çift hareket makaslı tavan sistemi");
        $add("Alüminyum lamel panel yapısı");
        $add("Su tahliye kanallı tavan sistemi");
        $add("LED aydınlatma sistemi");
        $add("Motorlu açılır kapanır mekanizma");
    }

    if (strpos($full, "SANDVIC") !== false) {
        $add("Sandviç panel kapama sistemi");
        $add("Poliüretan dolgulu panel yapısı");
    }

    if (
        strpos($full, "4 MEVSIM KONFOR") !== false ||
        strpos($full, "KONFOR ISICAM") !== false ||
        strpos($full, "SOLAR LOW-E") !== false ||
        strpos($full, "LOW-E") !== false
    ) {
        $add("4 mevsim konfor cam sistemi");
        $add("4+12+4 temperli konfor ısıcam");
        $add("Solar Low-E kaplamalı cam uygulaması");
        $add("Yüksek ısı ve ses izolasyonu");
    }
        

    if (strpos($full, "SURME CAM") !== false || strpos($full, "ISICAMLI SURME") !== false) {
        $add("Isıcamlı sürme cam sistemi");
        $add("Temperli güvenlik camı");
        $add("Alüminyum raylı hareket sistemi");
    }

    if (strpos($full, "SABIT CAM") !== false) {
        $add("Sabit cam doğrama sistemi");
        $add("4+12+4 ısıcam uygulaması");
    }

    if (!$features) {
        $features = [
            "Alüminyum taşıyıcı sistem",
            "Elektrostatik boyalı profil",
            "Yerinde ölçüye göre üretim",
            "Montaj dahil uygulama",
        ];
    }

    return array_slice($features, 0, 10);
}

function build_project_data($extracted_text)
{
    $basic_info = find_basic_info($extracted_text);
    $modules = find_modules($extracted_text);
    $sliding_glass = find_sliding_glass($extracted_text);
    $panels = find_panels($extracted_text);
    $contract_rows = find_contract_offer_rows($extracted_text);

    $project_data = [
        "basic_info" => $basic_info,
        "modules" => $modules,
        "module_count" => count($modules),
        "sliding_glass" => $sliding_glass,
        "sliding_glass_count" => count($sliding_glass),
        "panels" => $panels,
        "panel_count" => count($panels),
        "contract_rows" => $contract_rows,
    ];

    $project_data["technical_features"] = get_technical_features($project_data);

    return $project_data;
}
