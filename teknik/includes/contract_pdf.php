<?php

require_once __DIR__ . "/helpers.php";

function contract_escape($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8");
}

function money_display($value)
{
    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return "-";
    }

    $value = str_replace("₺", "", $value);
    $value = str_replace("TL", "", $value);
    $value = trim($value);

    return $value . " TL";
}

function build_contract_pdf_html($project_data)
{
    $basic = $project_data["basic_info"] ?? [];
    $rows = $project_data["contract_rows"] ?? [];

    $customer = contract_escape($basic["customer"] ?? "-");
    $seller = contract_escape($basic["seller"] ?? "Monshiny Alüminyum");
    $brand = contract_escape($basic["brand"] ?? "Vertu Bioklimatik Kış Bahçesi");
    $date = contract_escape($basic["date_range"] ?? "-");
    $offer_no = contract_escape($basic["offer_no"] ?? "-");
    $approval_no = contract_escape($basic["approval_no"] ?? $offer_no);

    $subtotal = money_display($basic["subtotal_price"] ?? "-");
    $vat = money_display($basic["vat_price"] ?? "-");
    $grand_total = money_display($basic["grand_total_price"] ?? ($basic["contract_price"] ?? "-"));

    $row_html = "";

    if (!$rows) {
        $row_html .= '
            <tr>
                <td colspan="9" style="text-align:center;">Ürün satırı bulunamadı.</td>
            </tr>
        ';
    } else {
        foreach ($rows as $i => $row) {
            $area = contract_escape($row["area"] ?? "-");
            $desc = contract_escape($row["description"] ?? "-");

            $width = format_meter($row["width"] ?? 0);
            $depth = format_meter($row["depth"] ?? 0);
            $height = format_meter($row["height"] ?? 0);

            if ($depth === "0,00") {
                $depth = "-";
            }

            $qty = contract_escape($row["quantity"] ?? "1");
            $unit = contract_escape($row["unit"] ?? "-");
            $unit_price = money_display($row["unit_price"] ?? "-");
            $total_price = money_display($row["total_price"] ?? "-");

            $row_html .= '
                <tr>
                    <td>' . ($i + 1) . '</td>
                    <td>' . $area . '</td>
                    <td>' . $desc . '</td>
                    <td>' . $width . '</td>
                    <td>' . $depth . '</td>
                    <td>' . $height . '</td>
                    <td>' . $qty . ' ' . $unit . '</td>
                    <td>' . $unit_price . '</td>
                    <td>' . $total_price . '</td>
                </tr>
            ';
        }
    }

    return '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 25px;
        size: A4 portrait;
    }

    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        font-size: 10px;
        color: #111;
        margin: 0;
        padding: 0;
    }

    .header {
        background: #111;
        color: #d6b87c;
        padding: 16px 20px;
        text-align: center;
        border-bottom: 4px solid #d6b87c;
    }

    .header h1 {
        margin: 0;
        font-size: 22px;
        letter-spacing: 1px;
    }

    .header div {
        margin-top: 4px;
        color: #fff;
        font-size: 12px;
    }

    .info-grid {
        width: 100%;
        border-collapse: collapse;
        margin-top: 18px;
        margin-bottom: 18px;
    }

    .info-grid td {
        border: 1px solid #999;
        padding: 8px;
        vertical-align: top;
    }

    .info-label {
        font-weight: bold;
        background: #f2f2f2;
        width: 18%;
    }

    h2 {
        font-size: 15px;
        border-bottom: 2px solid #111;
        padding-bottom: 5px;
        margin-top: 18px;
        margin-bottom: 8px;
    }

    .contract-text {
        line-height: 1.55;
        font-size: 10px;
        text-align: justify;
    }

    .product-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
    }

    .product-table th {
        background: #111;
        color: #fff;
        padding: 6px 4px;
        border: 1px solid #111;
        font-size: 9px;
    }

    .product-table td {
        border: 1px solid #999;
        padding: 5px 4px;
        font-size: 8.5px;
        vertical-align: top;
    }

    .total-table {
        width: 45%;
        margin-left: auto;
        border-collapse: collapse;
        margin-top: 12px;
    }

    .total-table td {
        border: 1px solid #999;
        padding: 7px;
        font-size: 10px;
    }

    .total-table .label {
        font-weight: bold;
        background: #f2f2f2;
    }

    .total-table .grand {
        background: #111;
        color: #d6b87c;
        font-weight: bold;
    }

    .signatures {
        width: 100%;
        margin-top: 45px;
        border-collapse: collapse;
    }

    .signatures td {
        width: 50%;
        text-align: center;
        padding-top: 35px;
        border-top: 1px solid #111;
        font-weight: bold;
    }

    .small-note {
        margin-top: 12px;
        font-size: 9px;
        line-height: 1.4;
    }
</style>
</head>

<body>

<div class="header">
    <h1>KIŞ BAHÇESİ SATIŞ SÖZLEŞMESİ</h1>
    <div>VERTU BİOKLİMATİK</div>
</div>

<table class="info-grid">
    <tr>
        <td class="info-label">Müşteri</td>
        <td>' . $customer . '</td>
        <td class="info-label">Tarih</td>
        <td>' . $date . '</td>
    </tr>
    <tr>
        <td class="info-label">Teklif / Ref No</td>
        <td>' . $offer_no . '</td>
        <td class="info-label">İmalat / Ref No</td>
        <td>' . $approval_no . '</td>
    </tr>
    <tr>
        <td class="info-label">Satıcı</td>
        <td>' . $seller . '</td>
        <td class="info-label">Marka</td>
        <td>' . $brand . '</td>
    </tr>
</table>

<h2>1. Sözleşmenin Konusu</h2>
<div class="contract-text">
    İşbu sözleşme, müşteriye ait projede kullanılacak kış bahçesi, bioklimatik tavan, cam cephe ve yardımcı sistemlerin
    üretim, tedarik ve montaj işlerine ilişkin şartları düzenlemek amacıyla hazırlanmıştır.
</div>

<h2>2. Ürün ve Hizmet Listesi</h2>

<table class="product-table">
    <thead>
        <tr>
            <th>No</th>
            <th>Alan</th>
            <th>Açıklama</th>
            <th>En</th>
            <th>Boy</th>
            <th>Yük.</th>
            <th>Adet/Birim</th>
            <th>Birim Fiyat</th>
            <th>Toplam</th>
        </tr>
    </thead>
    <tbody>
        ' . $row_html . '
    </tbody>
</table>

<table class="total-table">
    <tr>
        <td class="label">KDV Hariç Toplam</td>
        <td>' . $subtotal . '</td>
    </tr>
    <tr>
        <td class="label">KDV %20</td>
        <td>' . $vat . '</td>
    </tr>
    <tr>
        <td class="grand">KDV Dahil Toplam</td>
        <td class="grand">' . $grand_total . '</td>
    </tr>
</table>

<h2>3. Garanti ve Uygulama Şartları</h2>
<div class="contract-text">
    Ürünler üretici garanti şartları kapsamında teslim edilir. Kullanıcı hatası, darbeye bağlı hasarlar, doğal afetler,
    üçüncü kişiler tarafından yapılan müdahaleler ve uygunsuz kullanım garanti kapsamı dışındadır.
</div>

<h2>4. Ödeme ve Teslimat</h2>
<div class="contract-text">
    Ödeme planı ve teslimat süresi taraflar arasında mutabık kalınan teklif şartlarına göre uygulanır.
    Vinç, ulaşım, konaklama ve resmi izin bedelleri ayrıca belirtilmediği sürece müşteriye aittir.
</div>

<div class="small-note">
    Bu sözleşme, yukarıda belirtilen teklif bilgileri ve ürün kalemleri esas alınarak otomatik oluşturulmuştur.
</div>

<table class="signatures">
    <tr>
        <td>Satıcı Yetkili İmza</td>
        <td>Müşteri İmza</td>
    </tr>
</table>

</body>
</html>
';
}

function generate_contract_pdf_file($project_data, $output_path)
{
    $html = build_contract_pdf_html($project_data);

    $dompdf = new Dompdf\Dompdf();
    $dompdf->setPaper("A4", "portrait");
    $dompdf->loadHtml($html, "UTF-8");
    $dompdf->render();

    $dir = dirname($output_path);

    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($output_path, $dompdf->output());

    return $output_path;
}