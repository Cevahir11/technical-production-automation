<?php

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/includes/parser.php";

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;

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
    $rows = contract_get_rows($projectData, $text);

    $ref = $basic["approval_no"] ?? ($basic["offer_no"] ?? "sozlesme");
    $customer = $basic["customer"] ?? "musteri";

    $safeRef = function_exists("safe_name_part")
        ? safe_name_part($ref, "sozlesme")
        : contract_safe_part($ref, "sozlesme");

    $safeCustomer = function_exists("safe_name_part")
        ? safe_name_part($customer, "musteri")
        : contract_safe_part($customer, "musteri");

    $outputDir = __DIR__ . "/outputs/contracts_docx";

    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $fileName = $safeRef . "_" . $safeCustomer . "_sozlesme_" . time() . ".docx";
    $outputPath = $outputDir . DIRECTORY_SEPARATOR . $fileName;

    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName("Arial");
    $phpWord->setDefaultFontSize(9);

    $section = $phpWord->addSection([
        "marginTop" => 760,
        "marginBottom" => 760,
        "marginLeft" => 860,
        "marginRight" => 860,
        "pageSizeW" => 11906,
        "pageSizeH" => 16838,
    ]);

    $footer = $section->addFooter();
    $footer->addPreserveText("Sayfa {PAGE}", ["size" => 7], ["alignment" => Jc::RIGHT]);

    $styles = contract_styles();

    // ============================================================
    // SAYFA 1
    // ============================================================

    $section->addText("ÖZEL İMALAT SATIŞ VE MONTAJ SÖZLEŞMESİ", $styles["title"], $styles["center"]);
    $section->addText("VERTU PREMIUM KIŞ BAHÇELERİ", $styles["subtitle"], $styles["center"]);
    $section->addTextBreak(1);

    contract_add_info_table($section, $basic, $styles);

    contract_add_article($section, "MADDE 1 - TARAFLAR", [
        "1.1. Satıcı/Yüklenici: Monshiny Alüminyum, Vertu Bioklimatik Kış Bahçesi markası ile faaliyet göstermekte olup adresi Plevne Cd. No:55/D Pendik/İstanbul, telefonu 444 34 69’dur. Vergi dairesi/vergi no: Kartal VD 622 412668. Yetkili kişi: Melek Erden.",
        "1.2. Alıcı/Müşteri: " . contract_value($basic["customer"] ?? "") . ". Adresi: " . contract_value($basic["address"] ?? "…………") . ". Telefon: " . contract_value($basic["phone"] ?? "…………") . ". T.C. kimlik no/vergi no: …………. Açık uygulama adresi ile bina/kat/daire bilgileri imza öncesinde veya imalat onay formunda doldurulacaktır.",
        "1.3. Satıcı/Yüklenici ve Müşteri ayrı ayrı “Taraf”, birlikte “Taraflar” olarak anılacaktır."
    ], $styles);

    contract_add_article($section, "MADDE 2 - SÖZLEŞMENİN KONUSU", [
        "2.1. Bu sözleşmenin konusu, Müşteri’nin uygulama adresinde kullanılmak üzere özel ölçüye göre üretilecek Vertu Premium Kış Bahçeleri kapsamında bioklimatik pergola sistemleri, tavan sistemleri, konfor paketleri, panel cephe kapamaları, sürme cam, giyotin cam ve benzeri sistemlerin satış, imalat ve montaj koşullarının belirlenmesidir.",
        "2.2. Sözleşme kapsamındaki ürün ve iş kalemleri; bu sözleşmenin Madde 6 bölümünde ve Ek-1 teklif tablosunda belirtilen ölçü, adet, birim ve bedeller esas alınarak düzenlenmiştir.",
        "2.3. Teklifte veya imalat onay formunda açıkça yazılı olmayan ilave işler, altyapı hazırlıkları, elektrik hattı çekimi, boya/sıva/tadilat işleri, drenaj, ruhsat/izin işlemleri, yönetim izinleri, vinç giderleri ve saha hazırlıkları sözleşme bedeline dahil değildir."
    ], $styles);

    contract_add_article($section, "MADDE 3 - TEKLİF, ÖLÇÜ VE İMALAT ONAYI", [
        "3.1. Bu sözleşme, " . contract_value($basic["offer_no"] ?? $basic["approval_no"] ?? "…………") . " numaralı teklif esas alınarak düzenlenmiştir. İmalat onay süreci için referans numarası " . contract_value($basic["approval_no"] ?? $basic["offer_no"] ?? "…………") . "’dır.",
        "3.2. Sözleşme konusu ürünler özel ölçüye göre üretilecektir. Nihai imalat ölçüsü alındığında Satıcı/Yüklenici tarafından Müşteri’ye İmalat Onay Formu gönderilecektir.",
        "3.3. İmalat Onay Formu; ölçü, teknik detay, ürün kapsamı, renk/kaplama, aksesuar, cam ve panel detayları, montaj yeri ve varsa revize bedel bilgilerini içerir. Müşteri’nin yazılı, ıslak imzalı veya WhatsApp/e-posta gibi kalıcı veri saklayıcısı üzerinden vereceği onaydan sonra imalat süreci başlatılır.",
        "3.4. İmalat Onay Formu ile bu sözleşme arasında farklılık bulunması halinde, yalnızca Müşteri’nin açıkça onayladığı son tarihli imalat onay formu ilgili teknik detay bakımından esas alınır.",
        "3.5. Nihai ölçü veya saha koşullarının ilk teklife göre farklılık göstermesi halinde bedel, süre ve teknik kapsamda değişiklik yapılması gerekebilir. Bu tür değişiklikler Tarafların yazılı onayı ile geçerli olur."
    ], $styles);

    $grandTotal = contract_money($basic["grand_total_price"] ?? $basic["contract_price"] ?? "-");
    $vatTotal = contract_money($basic["vat_price"] ?? "-");

    contract_add_article($section, "MADDE 4 - SÖZLEŞME BEDELİ VE ÖDEME PLANI", [
        "4.1. Sözleşme konusu işlerin KDV dahil toplam sözleşme bedeli " . $grandTotal . "’dir. Bu bedel içerisinde %20 KDV tutarı " . $vatTotal . " olarak yer almaktadır.",
        "4.2. Müşteri, sözleşme bedelini aşağıdaki ödeme planına göre ödemeyi kabul eder:"
    ], $styles);

    contract_add_payment_table($section, $basic, $styles);

    contract_add_article($section, "", [
        "4.3. Ödemeler Monshiny Alüminyum adına kayıtlı TR15 0013 4000 0015 7185 2000 06 IBAN numaralı banka hesabına veya Tarafların yazılı olarak mutabık kaldığı başka bir ödeme yöntemiyle yapılacaktır. Dekontlar ödeme belgesi niteliğindedir.",
        "4.4. Ödeme planındaki gecikmeler, imalat ve montaj takvimini aynı oranda veya gecikmenin iş programına etkisi ölçüsünde uzatabilir.",
        "4.5. Vinç bedeli ve vinçle bağlantılı operatör, yol/park/işgal, bina yönetimi veya belediye izin giderleri Müşteri’ye aittir. Satıcı/Yüklenici’nin bu hizmetleri organize etmesi, ilgili giderlerin Satıcı/Yüklenici tarafından üstlenildiği anlamına gelmez."
    ], $styles);

    $section->addPageBreak();

    // ============================================================
    // SAYFA 2
    // ============================================================

    contract_add_article($section, "MADDE 5 - TESLİM, MONTAJ VE İŞ PROGRAMI", [
        "5.1. İmalat ve montaj süreci; imalat onay formunun Müşteri tarafından onaylanması, ödeme planındaki muaccel ödemelerin yapılması, vinç/izin organizasyonunun tamamlanması ve uygulama alanının montaja hazır hale getirilmesi şartlarına bağlıdır.",
        "5.2. Tahmini imalat ve montaj tarihi ………… ile ………… tarihleri arasıdır. Bu süre; saha koşulları, hava muhalefeti, vinç/izin organizasyonu, tedarik süreci ve Müşteri’den kaynaklı gecikmeler nedeniyle değişebilir.",
        "5.3. Montaj/Uygulama açık adresi: ………… . Bina, kat, daire veya konum bilgileri imza öncesinde veya imalat onay formunda netleştirilecektir.",
        "5.4. Müşteri; montaj alanının boş, güvenli, erişilebilir ve çalışmaya uygun olmasını; bina/yönetim/komşu izinlerinin alınmasını; gerekli elektrik, erişim, otopark ve çalışma ortamının sağlanmasını temin eder.",
        "5.5. Mevcut yapı, taşıyıcı zemin, cephe, drenaj, elektrik altyapısı veya gizli ayıplardan kaynaklanan ve Satıcı/Yüklenici’nin kusurundan doğmayan sorunlardan Müşteri sorumludur. Bu nedenle oluşacak ek iş ve bekleme süreleri ayrıca fiyatlandırılabilir."
    ], $styles);

    $section->addText("MADDE 6 - ÜRÜN KAPSAMI VE TEKNİK DOKÜMANLAR", $styles["article"], $styles["articleParagraph"]);
    $section->addText(
        "6.1. Sözleşme kapsamındaki iş kalemleri aşağıdaki tabloda belirtilmiştir. Nihai teknik kapsam; teklif, imalat onay formu, teknik çizim, renk seçimi, aksesuar listesi ve Tarafların yazılı onayları ile belirlenir.",
        $styles["text"],
        $styles["textParagraph"]
    );

    contract_add_product_table($section, $rows, $basic, $styles);

    contract_add_article($section, "", [
        "6.2. Teklif tablosunda yer alan Vertu Premium sistemlerin yüksek ses ve ısı izolasyonuna sahip olduğu belirtilmiştir. Bu ifade, teknik kapsam ve performans beklentisi bakımından imalat onay formunda yer alacak detaylarla birlikte değerlendirilir.",
        "6.3. Müşteri’nin imalat onayından sonra talep edeceği ölçü, renk, aksesuar, motor, aydınlatma, izolasyon, ilave panel, cam veya benzeri değişiklikler; teknik uygunluk, tedarik durumu, bedel ve süre etkisi bakımından Satıcı/Yüklenici tarafından değerlendirilir. Yazılı onay olmadan değişiklik zorunluluğu doğmaz.",
        "6.4. Ürünler özel ölçüye göre imal edileceğinden imalat onayı sonrası müşteri kaynaklı değişiklik veya iptal taleplerinde o tarihe kadar yapılan masraf, malzeme, kesim, üretim, işçilik, tedarik ve organizasyon bedelleri Müşteri’ye yansıtılabilir. Müşteri’nin kanundan doğan hakları saklıdır."
    ], $styles);

    $section->addPageBreak();

    // ============================================================
    // SAYFA 3
    // ============================================================

    contract_add_article($section, "MADDE 7 - TESLİM, KONTROL VE KABUL", [
        "7.1. Montaj tamamlandığında Taraflarca teslim/kabul tutanağı düzenlenir. Müşteri, ürünü makul süre içinde inceleyerek görünür eksiklik veya uygunsuzlukları tutanağa yazdırmalıdır.",
        "7.2. Kullanıma engel olmayan küçük eksiklikler veya rötuş gerektiren hususlar, ürünün teslimini tek başına engellemez; Satıcı/Yüklenici makul süre içinde giderim planı oluşturur.",
        "7.3. Müşteri’nin tutanağı imzadan kaçınması veya haklı sebep olmaksızın teslimi almaması halinde, Satıcı/Yüklenici durumu yazılı olarak bildirir. Bildirimden sonra ürünün fiilen kullanılması halinde teslim gerçekleşmiş kabul edilir; Müşteri’nin yasal hakları saklıdır."
    ], $styles);

    contract_add_article($section, "MADDE 8 - GARANTİ, AYIP VE SERVİS", [
        "8.1. Satıcı/Yüklenici, ürünlerin onaylanan teknik kapsam ve sözleşmeye uygun olarak imal edilmesinden ve kendi montaj işçiliğinden sorumludur.",
        "8.2. Garanti süresi 5 yıldır. Garanti başlangıcı, ürünlerin montajının tamamlandığı ve teslim edildiği tarihtir. Garanti kapsamında hizmet şartları, üretici/marka garanti belgesi ve servis prosedürleri ile birlikte uygulanır.",
        "8.3. Yetkisiz müdahale, bakım eksikliği, üçüncü kişilerin verdiği zararlar, doğal afetler, olağan dışı hava koşulları, bina hareketleri/zemin sorunları, elektrik dalgalanmaları, müşteri tarafından yaptırılan ek işlemler ve Satıcı/Yüklenici’nin kusurundan kaynaklanmayan haller garanti kapsamı dışındadır.",
        "8.4. Bu sözleşmedeki hiçbir hüküm, Müşteri’nin tüketici sıfatı varsa 6502 sayılı Tüketicinin Korunması Hakkında Kanun ve ilgili mevzuattan doğan zorunlu haklarını ortadan kaldıracak veya sınırlayacak şekilde yorumlanamaz."
    ], $styles);

    contract_add_article($section, "MADDE 9 - CAYMA, İPTAL VE SÖZLEŞMEDEN DÖNME", [
        "9.1. Bu sözleşme özel ölçüye göre imalat içeren bir satış ve montaj sözleşmesidir. İmalat onayından sonra müşteri kaynaklı iptal veya kapsam değişikliği talepleri, Tarafların yazılı mutabakatı ve o ana kadar oluşan maliyetlerin karşılanması şartıyla değerlendirilebilir.",
        "9.2. Tarafların kanundan doğan sözleşmeden dönme, ayıp, temerrüt ve tazminat hakları saklıdır."
    ], $styles);

    contract_add_article($section, "MADDE 10 - MÜCBİR SEBEP VE BEKLENMEYEN HALLER", [
        "10.1. Deprem, sel, yangın, fırtına, savaş, salgın, grev, idari kararlar, ithalat/tedarik kısıtları, üretici kaynaklı gecikmeler, ulaşım engelleri, olağan dışı hava koşulları ve Tarafların kontrolü dışında gelişen benzeri haller mücbir sebep sayılır.",
        "10.2. Mücbir sebep halinde etkilenen Taraf diğer Tarafı makul sürede bilgilendirir. Mücbir sebep süresi kadar ifa süreleri uzar; mücbir sebebin uzun süre devam etmesi halinde Taraflar iyi niyetle çözüm görüşmesi yapar."
    ], $styles);

    contract_add_article($section, "MADDE 11 - KİŞİSEL VERİLER VE GİZLİLİK", [
        "11.1. Müşteri’ye ait kimlik, iletişim ve adres bilgileri; sözleşmenin kurulması, ürünün imalatı, montaj, servis, muhasebe ve yasal yükümlülüklerin yerine getirilmesi amacıyla işlenir.",
        "11.2. Taraflar, sözleşme kapsamında öğrendikleri ticari ve kişisel bilgileri, yasal zorunluluklar ve hizmetin ifası için gerekli üçüncü kişi paylaşımları dışında gizli tutmayı kabul eder."
    ], $styles);

    contract_add_article($section, "MADDE 12 - BİLDİRİMLER", [
        "12.1. Tarafların bu sözleşmede belirtilen adres, telefon ve e-posta bilgileri tebligata ve bildirimlere esas kabul edilir. Adres değişikliği yazılı olarak bildirilmedikçe eski adrese yapılan bildirim geçerli sayılır.",
        "12.2. E-posta, SMS, WhatsApp veya benzeri kalıcı veri saklayıcısı niteliğindeki yazışmalar; imalat onayı, ödeme teyidi, montaj planı ve teknik mutabakat gibi konularda delil niteliğinde değerlendirilebilir."
    ], $styles);

    contract_add_article($section, "MADDE 13 - UYUŞMAZLIKLAR VE YETKİ", [
        "13.1. Bu sözleşmeye Türk hukuku uygulanır.",
        "13.2. Müşteri’nin tüketici sıfatını taşıdığı işlemlerde, yürürlükteki parasal sınırlar çerçevesinde yetkili Tüketici Hakem Heyetleri ve Tüketici Mahkemeleri görevlidir.",
        "13.3. İşlemin tüketici işlemi sayılmadığı hallerde İstanbul Anadolu Mahkemeleri ve İcra Daireleri yetkilidir."
    ], $styles);

    contract_add_article($section, "MADDE 14 - EKLER VE YÜRÜRLÜK", [
        "14.1. Aşağıdaki belgeler bu sözleşmenin ayrılmaz eki niteliğindedir: Ek-1 " . contract_value($basic["offer_no"] ?? "…………") . " numaralı teklif tablosu, Ek-2 " . contract_value($basic["approval_no"] ?? $basic["offer_no"] ?? "…………") . " numaralı imalat onay formu, Ek-3 teknik çizim, ölçü, renk/kaplama, cam/panel detayları ve aksesuar listesi.",
        "14.2. Bu sözleşme 4 sayfa ve 2 nüsha olarak düzenlenmiş olup Taraflarca imzalandığı tarihte yürürlüğe girer.",
        "14.3. Taraflar, sözleşmenin tüm maddelerini okuyup anladıklarını, serbest iradeleriyle kabul ettiklerini beyan eder."
    ], $styles);

    $section->addPageBreak();

    // ============================================================
    // SAYFA 4
    // ============================================================

    contract_add_signature_page($section, $basic, $styles);

    $writer = IOFactory::createWriter($phpWord, "Word2007");
    $writer->save($outputPath);

    json_response([
        "success" => true,
        "docx_file" => $fileName,
        "docx_url" => "outputs/contracts_docx/" . $fileName,
    ]);

} catch (Exception $e) {
    json_response([
        "success" => false,
        "error" => "Word sözleşme oluşturulurken hata oluştu: " . $e->getMessage()
    ]);
}

// ============================================================================
// YARDIMCI FONKSİYONLAR
// ============================================================================

function contract_styles()
{
    return [
        "title" => ["bold" => true, "size" => 12, "color" => "000000"],
        "subtitle" => ["bold" => true, "size" => 10.5, "color" => "000000"],

        "article" => ["bold" => true, "size" => 9.2, "color" => "000000"],
        "text" => ["size" => 8.2, "color" => "000000"],
        "numberBold" => ["bold" => true, "size" => 8.8, "color" => "000000"],
        "small" => ["size" => 7.2, "color" => "000000"],

        "tableHead" => ["bold" => true, "size" => 8.5, "color" => "000000"],
        "table" => ["size" => 8.0, "color" => "000000"],
        "tableBold" => ["bold" => true, "size" => 8.2, "color" => "000000"],

        "productHead" => ["bold" => true, "size" => 7.4, "color" => "000000"],
        "productText" => ["size" => 6.6, "color" => "000000"],
        "productBold" => ["bold" => true, "size" => 6.8, "color" => "000000"],

        "center" => ["alignment" => Jc::CENTER, "spaceAfter" => 20, "spaceBefore" => 0],
        "right" => ["alignment" => Jc::RIGHT, "spaceAfter" => 0, "spaceBefore" => 0],

        "articleParagraph" => ["spaceBefore" => 180, "spaceAfter" => 45],
        "textParagraph" => ["spaceBefore" => 0, "spaceAfter" => 42], 

        "p" => ["spaceAfter" => 18, "spaceBefore" => 0],
        "pTight" => ["spaceAfter" => 4, "spaceBefore" => 0],
    ];
}

function contract_add_info_table($section, $basic, $s)
{
    $table = $section->addTable([
        "borderSize" => 10,
        "borderColor" => "000000",
        "cellMargin" => 55,
        "width" => 100 * 50,
        "unit" => TblWidth::PERCENT,
        "alignment" => Jc::CENTER,
    ]);

    $labelCell = ["bgColor" => "D9D9D9"];
    $normalCell = [];

    $rows = [
        [
            "Sözleşme Tarihi",
            $basic["date_range"] ?? date("d.m.Y"),
            "Teklif No",
            $basic["offer_no"] ?? "-"
        ],
        [
            "İmalat Onay No",
            $basic["approval_no"] ?? ($basic["offer_no"] ?? "-"),
            "Sözleşme Bedeli",
            contract_money($basic["grand_total_price"] ?? $basic["contract_price"] ?? "-") . " KDV Dahil"
        ],
        [
            "Satıcı/Yüklenici",
            $basic["seller"] ?? "Monshiny Alüminyum",
            "Marka",
            $basic["brand"] ?? "Vertu Bioklimatik Kış Bahçesi"
        ],
        [
            "Müşteri",
            $basic["customer"] ?? "-",
            "Montaj/Uygulama Adresi",
            $basic["address"] ?? "…………"
        ],
    ];

    foreach ($rows as $row) {
        $table->addRow(330);

        $table->addCell(1900, $labelCell)->addText(
            $row[0],
            $s["tableHead"],
            $s["pTight"]
        );

        $table->addCell(3200, $normalCell)->addText(
            $row[1],
            $s["table"],
            $s["pTight"]
        );

        $table->addCell(1900, $labelCell)->addText(
            $row[2],
            $s["tableHead"],
            $s["pTight"]
        );

        $table->addCell(3300, $normalCell)->addText(
            $row[3],
            $s["table"],
            $s["pTight"]
        );
    }

    $section->addTextBreak(2);
}   

function contract_add_article($section, $title, $paragraphs, $s)
{
    if (trim((string)$title) !== "") {
        $section->addText($title, $s["article"], $s["articleParagraph"]);
    }

    foreach ($paragraphs as $p) {
        $p = (string)$p;

        if (preg_match('/^(\d+\.\d+\.)\s*(.*)$/u', $p, $m)) {
            $run = $section->addTextRun($s["textParagraph"]);
            $run->addText($m[1] . " ", $s["numberBold"]);
            $run->addText($m[2], $s["text"]);
        } else {
            $section->addText($p, $s["text"], $s["textParagraph"]);
        }
    }
}

function contract_add_payment_table($section, $basic, $s)
{
    $grandRaw = contract_clean_money_number($basic["grand_total_price"] ?? $basic["contract_price"] ?? "0");
    $advance = $grandRaw > 0 ? $grandRaw * 0.50 : 0;
    $balance = $grandRaw > 0 ? $grandRaw * 0.50 : 0;

    $table = $section->addTable([
        "borderSize" => 10,
        "borderColor" => "000000",
        "cellMargin" => 70,
        "width" => 86 * 50,
        "unit" => TblWidth::PERCENT,
        "alignment" => Jc::CENTER,
    ]);

    $table->addRow(340);
    $table->addCell(2300, ["bgColor" => "D9D9D9"])->addText("Ödeme Tarihi", $s["tableHead"], $s["pTight"]);
    $table->addCell(2600, ["bgColor" => "D9D9D9"])->addText("Tutar", $s["tableHead"], $s["pTight"]);
    $table->addCell(5000, ["bgColor" => "D9D9D9"])->addText("Açıklama", $s["tableHead"], $s["pTight"]);

    $table->addRow(380);
    $table->addCell(2300)->addText("Sözleşme imza tarihinde", $s["table"], $s["pTight"]);
    $table->addCell(2600)->addText($advance > 0 ? "%50 - " . contract_money(number_format($advance, 2, ",", ".")) : "-", $s["table"], $s["pTight"]);
    $table->addCell(5000)->addText("Sözleşmede / sipariş ve süreç başlangıç ödemesi", $s["table"], $s["pTight"]);

    $table->addRow(380);
    $table->addCell(2300)->addText("İş tesliminde", $s["table"], $s["pTight"]);
    $table->addCell(2600)->addText($balance > 0 ? "%50 - " . contract_money(number_format($balance, 2, ",", ".")) : "-", $s["table"], $s["pTight"]);
    $table->addCell(5000)->addText("Montaj ve iş teslimi sırasında kalan ödeme", $s["table"], $s["pTight"]);

    $section->addTextBreak(1);
}

function contract_add_product_table($section, $rows, $basic, $s)
{
    $table = $section->addTable([
        "borderSize" => 10,
        "borderColor" => "000000",
        "cellMargin" => 38,
        "width" => 100 * 50,
        "unit" => TblWidth::PERCENT,
    ]);

    $headers = [
        "No",
        "Ürün / Açıklama",
        "En",
        "Boy",
        "Yük.",
        "Alan M2",
        "Adet",
        "Birim",
        "Birim Fiyat",
        "Tutar"
    ];

    $widths = [700, 2500, 460, 460, 460, 560, 360, 420, 1350, 1320];

    $table->addRow(520);

    foreach ($headers as $i => $h) {
        $table->addCell($widths[$i], [
            "bgColor" => "D9D9D9",
            "valign" => "center"
        ])->addText($h, $s["productHead"], $s["pTight"]);
    }

    if (!$rows) {
        $table->addRow(700);
        $table->addCell(array_sum($widths), ["gridSpan" => 10])->addText(
            "Ürün satırı bulunamadı.",
            $s["productText"],
            $s["pTight"]
        );
    } else {
        foreach ($rows as $row) {
            $desc = (string)($row["description"] ?? $row["product"] ?? "-");

            $heightRow = 760;

            if (mb_strlen($desc, "UTF-8") > 80) {
                $heightRow = 1100;
            }

            if (mb_strlen($desc, "UTF-8") > 150) {
                $heightRow = 1350;
            }

            $table->addRow($heightRow);

            $areaName = (string)($row["area"] ?? "-");

            $width = contract_fmt_m($row["width"] ?? 0);
            if ($width === "0,00") {
                $width = "-";
            }

            $depth = contract_fmt_m($row["depth"] ?? 0);
            if ($depth === "0,00") {
                $depth = "-";
            }

            $height = contract_fmt_m($row["height"] ?? 0);
            if ($height === "0,00") {
                $height = "-";
            }

            $qty = (string)($row["quantity"] ?? "1");
            if ($qty === "0" || $qty === "") {
                $qty = "-";
            }

            $cells = [
                $areaName,
                $desc,
                $width,
                $depth,
                $height,
                contract_area($row["area_m2"] ?? $row["area_value"] ?? "-"),
                $qty,
                (string)($row["unit"] ?? "-"),
                contract_money($row["unit_price"] ?? "-"),
                contract_money($row["total_price"] ?? "-"),
            ];

            foreach ($cells as $ci => $value) {
                $table->addCell($widths[$ci], [
                    "valign" => "top"
                ])->addText(
                    (string)$value,
                    $s["productText"],
                    $s["pTight"]
                );
            }
        }
    }

    $table->addRow(580);
    $table->addCell(5920, ["gridSpan" => 8])->addText("", $s["productText"], $s["pTight"]);
    $table->addCell(1350, ["bgColor" => "D9D9D9", "valign" => "center"])->addText("KDV HARİÇ\nTOPLAM", $s["productBold"], $s["pTight"]);
    $table->addCell(1320, ["valign" => "center"])->addText("-", $s["productBold"], $s["pTight"]);

    $table->addRow(580);
    $table->addCell(5920, ["gridSpan" => 8])->addText("", $s["productText"], $s["pTight"]);
    $table->addCell(1350, ["bgColor" => "D9D9D9", "valign" => "center"])->addText("KDV %20", $s["productBold"], $s["pTight"]);
    $table->addCell(1320, ["valign" => "center"])->addText(contract_money($basic["vat_price"] ?? "-"), $s["productBold"], $s["pTight"]);

    $table->addRow(580);
    $table->addCell(5920, ["gridSpan" => 8])->addText("", $s["productText"], $s["pTight"]);
    $table->addCell(1350, ["bgColor" => "D9D9D9", "valign" => "center"])->addText("KDV DAHİL\nTOPLAM", $s["productBold"], $s["pTight"]);
    $table->addCell(1320, ["valign" => "center"])->addText(contract_money($basic["grand_total_price"] ?? $basic["contract_price"] ?? "-"), $s["productBold"], $s["pTight"]);

    $section->addTextBreak(1);
}

function contract_add_signature_page($section, $basic, $s)
{
    $table = $section->addTable([
        "borderSize" => 10,
        "borderColor" => "000000",
        "cellMargin" => 100,
        "width" => 100 * 50,
        "unit" => TblWidth::PERCENT,
    ]);

    $table->addRow(460);
    $table->addCell(5000, ["bgColor" => "D9D9D9"])->addText("SATICI/YÜKLENİCİ", $s["tableHead"], $s["pTight"]);
    $table->addCell(5000, ["bgColor" => "D9D9D9"])->addText("MÜŞTERİ", $s["tableHead"], $s["pTight"]);

    $table->addRow(1700);

    $left = $table->addCell(5000);
    $left->addText("Monshiny Alüminyum", $s["table"], $s["pTight"]);
    $left->addText("Yetkili: Melek Erden", $s["table"], $s["pTight"]);
    $left->addText("", $s["table"], $s["pTight"]);
    $left->addText("Tarih: ____ / ____ / 2026", $s["table"], $s["pTight"]);
    $left->addText("", $s["table"], $s["pTight"]);
    $left->addText("İmza / Kaşe:", $s["table"], $s["pTight"]);

    $right = $table->addCell(5000);
    $right->addText($basic["customer"] ?? "-", $s["table"], $s["pTight"]);
    $right->addText("T.C. Kimlik No: …………", $s["table"], $s["pTight"]);
    $right->addText("", $s["table"], $s["pTight"]);
    $right->addText("Tarih: ____ / ____ / 2026", $s["table"], $s["pTight"]);
    $right->addText("", $s["table"], $s["pTight"]);
    $right->addText("İmza:", $s["table"], $s["pTight"]);

    $table->addRow(460);
    $table->addCell(5000)->addText("Telefon: 444 34 69", $s["table"], $s["pTight"]);
    $table->addCell(5000)->addText("Telefon: " . ($basic["phone"] ?? "…………"), $s["table"], $s["pTight"]);

    $section->addTextBreak(2);

    $section->addText(
        "Not: Bu belge, kullanıcı tarafından sağlanan teklif tablosundaki bilgiler esas alınarak hazırlanmış taslak sözleşmedir. İmza öncesinde boş alanların doldurulması ve şirket hukuk/mali müşaviri tarafından kontrol edilmesi önerilir.",
        $s["small"],
        $s["p"]
    );
}

function contract_get_rows($projectData, $text = "")
{
    $rows = [];

    if (!empty($projectData["contract_rows"])) {
        foreach ($projectData["contract_rows"] as $r) {
            $rows[] = contract_normalize_row_for_contract($r);
        }
    }

    if (!$rows && !empty($projectData["rows"])) {
        foreach ($projectData["rows"] as $r) {
            $rows[] = contract_normalize_row_for_contract($r);
        }
    }

    if (!$rows) {
        foreach ($projectData["modules"] ?? [] as $m) {
            $rows[] = contract_normalize_row_for_contract([
                "area" => $m["area"] ?? "",
                "description" => $m["system"] ?? $m["description"] ?? "",
                "width" => $m["width"] ?? 0,
                "depth" => $m["depth"] ?? 0,
                "height" => $m["height"] ?? 0,
                "area_m2" => $m["area_m2"] ?? "-",
                "quantity" => $m["quantity"] ?? 1,
                "unit" => "AD",
            ]);
        }

        foreach ($projectData["sliding_glass"] ?? [] as $g) {
            $rows[] = contract_normalize_row_for_contract([
                "area" => $g["area"] ?? "",
                "description" => $g["description"] ?? "",
                "width" => $g["width"] ?? 0,
                "depth" => 0,
                "height" => $g["height"] ?? 0,
                "area_m2" => $g["area_m2"] ?? "-",
                "quantity" => $g["quantity"] ?? 1,
                "unit" => "M2",
            ]);
        }

        foreach ($projectData["panels"] ?? [] as $p) {
            $rows[] = contract_normalize_row_for_contract([
                "area" => $p["area"] ?? "",
                "description" => $p["description"] ?? "",
                "width" => $p["width"] ?? 0,
                "depth" => 0,
                "height" => $p["height"] ?? 0,
                "area_m2" => $p["area_m2"] ?? "-",
                "quantity" => $p["quantity"] ?? 1,
                "unit" => "M2",
            ]);
        }
    }

    $rows = contract_remove_duplicate_rows($rows);

    $rows = contract_add_missing_contract_rows($rows, $text);

    $rows = contract_remove_duplicate_rows($rows);

    usort($rows, function ($a, $b) {
        return contract_row_sort_weight($a) <=> contract_row_sort_weight($b);
    });

    $rows = contract_apply_prices_from_pdf_text($rows, $text);

    return $rows;
}

function contract_normalize_row_for_contract($r)
{
    return [
        "area" => normalize_offer_area($r["area"] ?? "-"),
        "description" => clean_text($r["description"] ?? ($r["product"] ?? "-")),
        "width" => $r["width"] ?? "-",
        "depth" => $r["depth"] ?? "-",
        "height" => $r["height"] ?? "-",
        "area_m2" => $r["area_m2"] ?? ($r["area_value"] ?? "-"),
        "quantity" => $r["quantity"] ?? "1",
        "unit" => strtoupper(str_replace(["m²", "M²", "m2"], "M2", (string)($r["unit"] ?? "-"))),
        "unit_price" => $r["unit_price"] ?? "-",
        "total_price" => $r["total_price"] ?? "-",
    ];
}

function contract_add_missing_contract_rows($rows, $text)
{
    $plain = (string)$text;
    $norm = strtoupper(contract_ascii($plain));

    // BİOKLİMATİK konfor paketi
    if (
        !contract_has_area_desc($rows, "BİOKLİMATİK", "KONFOR PAKETİ") &&
        strpos($norm, "BIOKLIMATIK") !== false &&
        strpos($norm, "KONFOR PAKETI") !== false
    ) {
        $rows[] = contract_normalize_row_for_contract([
            "area" => "BİOKLİMATİK",
            "description" => "VERTU PREMIUM 4 MEVSİM KONFOR PAKETİ Poliüretan Dolgu ile Yüksek Isı ve Ses İzolasyonu, Galvanizli Çelik Aksesuar ve Premium LED Aydınlatma Sistemi Somfy Motor",
            "width" => "-",
            "depth" => "-",
            "height" => "-",
            "area_m2" => "-",
            "quantity" => 1,
            "unit" => "M2",
        ]);
    }

    // ARKA sandviç panel
    if (
        !contract_has_area_desc($rows, "ARKA", "SANDVİÇ PANEL") &&
        preg_match('/ARKA\s+POL[İI]ÜRETAN\s+SANDV[İI]Ç\s+PANEL\s+CEPHE\s+KAPAMA\s+([\d,.]+)\s+([\d,.]+)\s+([\d,.]+)\s+(\d+)/iu', $plain, $m)
    ) {
        $w = contract_parse_measure($m[1]);
        $h = contract_parse_measure($m[3]);
        $qty = intval($m[4]);
        if ($qty <= 0) $qty = 1;

        $areaM2 = $w > 0 && $h > 0 ? $w * $h : 0;

        $rows[] = contract_normalize_row_for_contract([
            "area" => "ARKA",
            "description" => "POLİÜRETAN SANDVİÇ PANEL CEPHE KAPAMA",
            "width" => $w,
            "depth" => "-",
            "height" => $h,
            "area_m2" => $areaM2 > 0 ? number_format($areaM2, 2, ",", ".") : "-",
            "quantity" => $qty,
            "unit" => "M2",
        ]);
    }

    // CAM konfor paketi
    if (
        !contract_has_area_desc($rows, "CAM", "KONFOR") &&
        strpos($norm, "CAM") !== false &&
        (
            strpos($norm, "KONFOR ISICAM") !== false ||
            strpos($norm, "SOLAR LOW") !== false ||
            strpos($norm, "4+12+4") !== false
        )
    ) {
        $rows[] = contract_normalize_row_for_contract([
            "area" => "CAM",
            "description" => "VERTU PREMIUM 4 MEVSİM KONFOR PAKETİ 4+12+4 Temperli Konfor Isıcam ile Solar Low-E Kaplamalı Yüksek Isı ve Ses İzolasyonu",
            "width" => "-",
            "depth" => "-",
            "height" => "-",
            "area_m2" => "-",
            "quantity" => "-",
            "unit" => "M2",
        ]);
    }

    return $rows;
}

function contract_has_area_desc($rows, $areaNeedle, $descNeedle)
{
    $areaNeedle = strtoupper(contract_ascii($areaNeedle));
    $descNeedle = strtoupper(contract_ascii($descNeedle));

    foreach ($rows as $r) {
        $area = strtoupper(contract_ascii($r["area"] ?? ""));
        $desc = strtoupper(contract_ascii($r["description"] ?? ""));

        if ($area === $areaNeedle && strpos($desc, $descNeedle) !== false) {
            return true;
        }
    }

    return false;
}

function contract_apply_prices_from_pdf_text($rows, $text)
{
    $money = contract_extract_money_values_from_text($text);
    $rowCount = count($rows);

    if (!$money || $rowCount <= 0) {
        return $rows;
    }

    // En sondaki özet toplamları ayıkla: KDV hariç, KDV, KDV dahil
    if (count($money) >= $rowCount + 3) {
        $moneyWithoutTotals = array_slice($money, 0, count($money) - 3);
    } else {
        $moneyWithoutTotals = $money;
    }

    // Durum 1: PDF hem birim fiyat hem tutarı yakaladıysa
    // sıra: birim, tutar, birim, tutar...
    if (count($moneyWithoutTotals) >= $rowCount * 2) {
        $index = 0;

        foreach ($rows as $i => $row) {
            $rows[$i]["unit_price"] = contract_format_money($moneyWithoutTotals[$index] ?? null);
            $rows[$i]["total_price"] = contract_format_money($moneyWithoutTotals[$index + 1] ?? null);
            $index += 2;
        }

        return $rows;
    }

    // Durum 2: PDF sadece satır tutarlarını yakaladıysa
    // sıra: tutar, tutar, tutar...
    if (count($moneyWithoutTotals) >= $rowCount) {
        foreach ($rows as $i => $row) {
            $total = $moneyWithoutTotals[$i] ?? null;

            $rows[$i]["total_price"] = contract_format_money($total);
            $rows[$i]["unit_price"] = contract_calculate_unit_price_from_total($row, $total);
        }

        return $rows;
    }

    return $rows;
}

function contract_extract_money_values_from_text($text)
{
    $text = (string)$text;

    $norm = str_replace(["\r", "\n", "\t"], " ", $text);
    $norm = preg_replace('/\s+/u', ' ', $norm);

    // Ölçüler yakalanmasın diye para için ya ₺ işareti ya da binlik nokta arıyoruz.
    preg_match_all('/(?:₺\s*)?([0-9]{1,3}(?:\.[0-9]{3})+,[0-9]{2})/u', $norm, $m);

    $values = [];

    foreach ($m[1] ?? [] as $raw) {
        $float = contract_money_to_float($raw);

        if ($float <= 0) {
            continue;
        }

        $values[] = $float;
    }

    return $values;
}

function contract_calculate_unit_price_from_total($row, $total)
{
    $total = floatval($total);

    if ($total <= 0) {
        return "-";
    }

    $area = strtoupper(contract_ascii($row["area"] ?? ""));
    $desc = strtoupper(contract_ascii($row["description"] ?? ""));
    $unit = strtoupper(contract_ascii($row["unit"] ?? ""));
    $qty = floatval(str_replace(",", ".", (string)($row["quantity"] ?? 1)));

    if ($qty <= 0) {
        $qty = 1;
    }

    // Konfor paketleri için eski Python çıktısındaki sabit birim fiyatlar
    if ($area === "BIOKLIMATIK" && strpos($desc, "KONFOR PAKETI") !== false) {
        return contract_format_money(540);
    }

    if ($area === "CAM" && strpos($desc, "KONFOR") !== false) {
        return contract_format_money(1500);
    }

    if ($unit === "AD") {
        return contract_format_money($total / $qty);
    }

    $areaM2 = contract_parse_measure($row["area_m2"] ?? 0);

    if ($unit === "M2" && $areaM2 > 0) {
        $unitPrice = $total / max($areaM2 * $qty, 1);
        return contract_format_money(round($unitPrice));
    }

    return "-";
}

function contract_money_to_float($value)
{
    $value = trim((string)$value);
    $value = str_replace(["₺", "TL", " "], "", $value);
    $value = str_replace(".", "", $value);
    $value = str_replace(",", ".", $value);

    return floatval($value);
}

function contract_format_money($value)
{
    if ($value === null || $value === "" || $value === "-") {
        return "-";
    }

    $value = floatval($value);

    if ($value <= 0) {
        return "-";
    }

    return number_format($value, 2, ",", ".") . " TL";
}

function contract_parse_measure($value)
{
    if (function_exists("parse_measure_cell")) {
        return parse_measure_cell($value);
    }

    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return 0;
    }

    $value = str_replace(".", "", $value);
    $value = str_replace(",", ".", $value);

    return floatval($value);
}

function contract_remove_duplicate_rows($rows)
{
    $seen = [];
    $clean = [];

    foreach ($rows as $row) {
        $key = strtoupper(contract_ascii(
            trim(
                ($row["area"] ?? "") . "|" .
                ($row["description"] ?? "") . "|" .
                ($row["width"] ?? "") . "|" .
                ($row["depth"] ?? "") . "|" .
                ($row["height"] ?? "") . "|" .
                ($row["area_m2"] ?? "") . "|" .
                ($row["quantity"] ?? "") . "|" .
                ($row["unit"] ?? "")
            )
        ));

        if (isset($seen[$key])) {
            continue;
        }

        $seen[$key] = true;
        $clean[] = $row;
    }

    return $clean;
}

function contract_row_sort_weight($r)
{
    $area = strtoupper(contract_ascii($r["area"] ?? ""));
    $desc = strtoupper(contract_ascii($r["description"] ?? ""));

    if (
        strpos($desc, "BIOKLIMATIK PERGOLA") !== false ||
        strpos($desc, "MAKASLI BIOKLIMATIK") !== false
    ) {
        return 10;
    }

    if (
        $area === "BIOKLIMATIK" ||
        (
            strpos($desc, "4 MEVSIM KONFOR PAKETI") !== false &&
            strpos($desc, "POLIURETAN") !== false
        )
    ) {
        return 20;
    }

    if (
        strpos($desc, "SANDVIC PANEL") !== false ||
        strpos($desc, "PANEL CEPHE") !== false
    ) {
        return 30;
    }

    if (
        strpos($desc, "SURME CAM") !== false ||
        strpos($desc, "GIYOTIN CAM") !== false ||
        strpos($desc, "SABIT CAM") !== false
    ) {
        return 40;
    }

    if (
        $area === "CAM" ||
        strpos($desc, "KONFOR ISICAM") !== false ||
        strpos($desc, "SOLAR LOW") !== false
    ) {
        return 50;
    }

    return 99;
}

function contract_value($value)
{
    $value = trim((string)$value);
    return $value !== "" ? $value : "…………";
}

function contract_money($value)
{
    $value = trim((string)$value);

    if ($value === "" || $value === "-") {
        return "-";
    }

    $value = str_replace(["₺", "TL"], "", $value);
    $value = trim($value);

    if ($value === "") {
        return "-";
    }

    return $value . " TL";
}

function contract_clean_money_number($value)
{
    $value = trim((string)$value);
    $value = str_replace(["₺", "TL", " "], "", $value);

    if (strpos($value, ",") !== false) {
        $value = str_replace(".", "", $value);
        $value = str_replace(",", ".", $value);
    }

    return floatval($value);
}

function contract_fmt_m($value)
{
    if (function_exists("format_meter")) {
        return format_meter($value);
    }

    $n = floatval(str_replace(",", ".", (string)$value));
    return number_format($n, 2, ",", ".");
}

function contract_area($value)
{
    if ($value === "" || $value === null || $value === "-") {
        return "-";
    }

    if (is_numeric($value)) {
        return number_format((float)$value, 2, ",", ".");
    }

    return (string)$value;
}

function contract_safe_part($value, $fallback)
{
    $value = trim((string)$value);

    if ($value === "") {
        return $fallback;
    }

    $value = preg_replace('/[^A-Za-z0-9_\-]+/u', "_", $value);
    $value = trim($value, "_");

    return $value !== "" ? $value : $fallback;
}

function contract_ascii($text)
{
    $text = (string)$text;

    $map = [
        "İ" => "I",
        "İ" => "I",
        "ı" => "I",
        "Ş" => "S",
        "ş" => "S",
        "Ğ" => "G",
        "ğ" => "G",
        "Ü" => "U",
        "ü" => "U",
        "Ö" => "O",
        "ö" => "O",
        "Ç" => "C",
        "ç" => "C",
    ];

    return strtr($text, $map);
}