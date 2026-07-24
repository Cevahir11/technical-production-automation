<?php

// ---------------------------------------------------------
// GENEL YARDIMCI FONKSİYONLAR
// Python helpers karşılığı
// ---------------------------------------------------------

function clean_text($text)
{
    if ($text === null) {
        return "";
    }

    $text = str_replace("\n", " ", (string)$text);
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim($text);
}


function safe_filename($filename, $default = "upload.pdf")
{
    $filename = basename($filename ?: $default);

    // Türkçe karakterlere izin ver, diğer riskli karakterleri _ yap
    $filename = preg_replace('/[^A-Za-z0-9ÇĞİÖŞÜçğıöşü_.-]/u', '_', $filename);

    if (!$filename) {
        $filename = $default;
    }

    return $filename;
}


function safe_name_part($value, $default = "dosya")
{
    $value = trim((string)($value ?: $default));
    $value = str_replace(" ", "_", $value);
    $value = preg_replace('/[^A-Za-z0-9ÇĞİÖŞÜçğıöşü_-]/u', '_', $value);
    $value = preg_replace('/_+/u', '_', $value);
    $value = trim($value, "_");

    if (!$value) {
        $value = $default;
    }

    return $value;
}


function to_float($value)
{
    $value = trim((string)$value);

    $value = str_replace("₺", "", $value);
    $value = str_replace("TL", "", $value);
    $value = str_replace("tl", "", $value);
    $value = trim($value);

    if ($value === "" || $value === "-") {
        return 0;
    }

    // 1.234,56 formatı
    if (strpos($value, ",") !== false && strpos($value, ".") !== false) {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);
    } else {
        $value = str_replace(",", ".", $value);
    }

    if (!is_numeric($value)) {
        return 0;
    }

    return (float)$value;
}


function tr_number_to_float($value)
{
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return null;
    }

    $value = str_replace("TL", "", $value);
    $value = str_replace("₺", "", $value);
    $value = str_replace("m²", "", $value);
    $value = str_replace("m2", "", $value);
    $value = str_replace("M²", "", $value);
    $value = str_replace("M2", "", $value);
    $value = str_replace("m", "", $value);
    $value = str_replace(" ", "", $value);
    $value = trim($value);

    if ($value === "") {
        return null;
    }

    try {
        if (strpos($value, ",") !== false) {
            $value = str_replace(".", "", $value);
            $value = str_replace(",", ".", $value);
            return (float)$value;
        }

        if (strpos($value, ".") !== false) {
            $parts = explode(".", $value);

            // 65.880 gibi ise nokta binliktir
            if (strlen(end($parts)) === 3) {
                $value = str_replace(".", "", $value);
            }
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (float)$value;
    } catch (Exception $e) {
        return null;
    }
}


function money_to_float($value)
{
    return tr_number_to_float($value);
}


function format_tr_number($value)
{
    if ($value === null) {
        return "-";
    }

    return number_format((float)$value, 2, ",", ".");
}


function format_tl($value)
{
    if ($value === null) {
        return "-";
    }

    return format_tr_number($value) . " TL";
}


function parse_measure_cell($value)
{
    if ($value === null) {
        return 0;
    }

    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return 0;
    }

    if (preg_match('/(\d+[,.]\d+)/u', $value, $match)) {
        return to_float($match[1]);
    }

    if (preg_match('/(\d+)/u', $value, $match)) {
        return to_float($match[1]);
    }

    return 0;
}


function format_meter($value)
{
    return str_replace(".", ",", number_format((float)$value, 2, ".", ""));
}


function clamp_value($value, $min_value, $max_value)
{
    return max($min_value, min($value, $max_value));
}


function tr_to_ascii($text)
{
    if ($text === null) {
        return "";
    }

    $text = (string)$text;

    $replacements = [
        "ç" => "c", "Ç" => "C",
        "ğ" => "g", "Ğ" => "G",
        "ı" => "i", "İ" => "I",
        "ö" => "o", "Ö" => "O",
        "ş" => "s", "Ş" => "S",
        "ü" => "u", "Ü" => "U",
    ];

    return strtr($text, $replacements);
}


function normalize_text($text)
{
    return strtoupper(tr_to_ascii((string)$text));
}


function normalize_area_name($area)
{
    $area_ascii = strtoupper(trim(tr_to_ascii($area)));

    if (strpos($area_ascii, "HAVUZ") !== false && strpos($area_ascii, "ONU") !== false) {
        return "HAVUZ ÖNÜ";
    }

    if ($area_ascii === "HAVUZ") {
        return "HAVUZ ÖNÜ";
    }

    if (strpos($area_ascii, "UST") !== false && strpos($area_ascii, "TERAS") !== false) {
        return "ÜST TERAS";
    }

    if (strpos($area_ascii, "TERAS") !== false) {
        return "TERAS";
    }

    if (strpos($area_ascii, "BAHCE") !== false) {
        return "BAHÇE";
    }

    if (strpos($area_ascii, "ARKA") !== false) {
        return "ARKA";
    }

    if (strpos($area_ascii, "YAN") !== false) {
        return "YAN";
    }

    if ($area_ascii === "ON") {
        return "ÖN";
    }

    if ($area_ascii === "UST") {
        return "ÜST";
    }

    return strtoupper(trim((string)$area));
}


function clean_product_name($value)
{
    $value = clean_text((string)$value);
    $value = str_replace("\n", " ", $value);
    $value = preg_replace('/\s+/u', ' ', $value);

    return trim($value);
}


function normalize_unit($value)
{
    $value = strtoupper(trim((string)$value));

    $value = str_replace("²", "2", $value);
    $value = str_replace("M 2", "M2", $value);
    $value = str_replace("M²", "M2", $value);
    $value = str_replace("m²", "M2", $value);
    $value = str_replace("m2", "M2", $value);

    if (in_array($value, ["M", "M2", "M²"], true)) {
        return "M2";
    }

    if (strpos($value, "M2") !== false) {
        return "M2";
    }

    if (in_array($value, ["AD", "ADET"], true)) {
        return "AD";
    }

    return $value;
}


function clean_price_text($value, $add_tl = false)
{
    if ($value === null) {
        return "-";
    }

    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return "-";
    }

    $value = str_replace("₺", "", $value);
    $value = str_replace("TL", "", $value);
    $value = str_replace("\n", " ", $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    $value = trim($value);

    if ($add_tl) {
        return $value . " TL";
    }

    return $value;
}


function format_table_money($value)
{
    $num = money_to_float($value);

    if ($num !== null) {
        return format_tl($num);
    }

    return clean_text($value);
}


function json_response($data)
{
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}