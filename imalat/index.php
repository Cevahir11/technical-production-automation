<?php
// index.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>İmalat Onay Formu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="page">
    <h1>İmalat Onay Formu</h1>
    <p class="desc">
        Ürün bilgilerini gir, çizim yöntemini seç, montaj malzemelerini ekle ve imalat onay formunu oluştur.
    </p>

    <form action="generate_pdf.php" method="post" id="imalatForm" enctype="multipart/form-data">

        <datalist id="ralOptions">
            <option value="7016">RAL 7016 - Antrasit Gri</option>
            <option value="7006">RAL 7006 - Bej Gri</option>
            <option value="7047">RAL 7047 - Telegri 4</option>
            <option value="7035">RAL 7035 - Açık Gri</option>
            <option value="7021">RAL 7021 - Siyah Gri</option>
            <option value="7012">RAL 7012 - Bazalt Gri</option>
            <option value="9005">RAL 9005 - Siyah</option>
            <option value="9006">RAL 9006 - Beyaz Alüminyum</option>
            <option value="9007">RAL 9007 - Gri Alüminyum</option>
            <option value="9016">RAL 9016 - Trafik Beyazı</option>
            <option value="8019">RAL 8019 - Gri Kahve</option>
            <option value="1013">RAL 1013 - İnci Beyazı</option>
        </datalist>

        <datalist id="glassTypeOptions">
            <option value="Konfor Isı Cam"></option>
            <option value="Isıcam"></option>
            <option value="Temperli Cam"></option>
            <option value="Lamine Cam"></option>
            <option value="Tek Cam"></option>
        </datalist>

        <section class="card">
            <h2>Genel Bilgiler</h2>

            <div class="grid">
                <label>
                    Müşteri 
                     <input type="text" name="customer_name" placeholder="Örn: XXXX">
                </label>

                <label>
                    Sipariş No
                    <input type="text" name="order_no" placeholder="Örn: 15052601">
                </label>

                <label>
                    Tarih
                    <input type="date" name="date">
                </label>

                <label>
                    Çizen
                    <input type="text" name="drawn_by" placeholder="Örn:Müh. Nesim Bey">
                </label>

                <label>
                    Onaylayan
                    <input type="text" name="approved_by" placeholder="Örn: CENKER ERDEN">
                </label>

                <label>
                    Lokasyon
                    <input type="text" name="location" placeholder="PENDİK/ İSTANBUL ">
                </label>
            </div>
        </section>

        <section class="card">
            <h2>Çizim Yöntemi</h2>

            <div class="drawing-mode-grid">
                <label class="mode-card">
                    <input type="radio" name="drawing_mode" value="auto" checked onchange="toggleDrawingMode()">
                    <span>
                        <strong>Otomatik Çizim</strong>
                        <small>Eklenen ürünlerden sistem otomatik teknik görünüş oluşturur.</small>
                    </span>
                </label>

                <label class="mode-card">
                    <input type="radio" name="drawing_mode" value="image" onchange="toggleDrawingMode()">
                    <span>
                        <strong>Görselden Oku</strong>
                        <small>Ustanın verdiği kağıt çizimi yükle, sistem ölçüleri okuyup çizime çevirsin.</small>
                    </span>
                </label>

                <label class="mode-card">
                    <input type="radio" name="drawing_mode" value="manual" onchange="toggleDrawingMode()">
                    <span>
                        <strong>Mühendis Çizimi</strong>    
                        <small>CAD tarzı çizim alanında çizimi sen oluşturursun.</small>
                    </span>
                </label>
            </div>
                        <div style="margin-top:14px; display:flex; justify-content:flex-end;">
                <button type="button" id="autoToCadBtn" style="padding:12px 18px; font-weight:800;">
                    Otomatik Çizimi Oluştur ve Düzenle
                </button>
            </div>

            <input type="hidden" name="manual_drawing_data" id="manual_drawing_data">

            <div id="manualDrawingBox" class="manual-drawing-box" style="display:none;">
                <div class="drawing-toolbar cad-toolbar">
                    <button type="button" class="tool-btn active" data-tool="select">Seç / Taşı</button>
                    <button type="button" class="tool-btn" data-tool="line">Çizgi</button>
                    <button type="button" class="tool-btn" data-tool="rect">Dikdörtgen</button>
                    <button type="button" class="tool-btn" data-tool="parallelogram">Paralelkenar</button>
                    <button type="button" class="tool-btn" data-tool="triangle">Üçgen</button>
                    <button type="button" class="tool-btn" data-tool="square">Kare</button>
                    <button type="button" class="tool-btn" data-tool="circle">Daire</button>
                    <button type="button" class="tool-btn" data-tool="dimension">Ölçü</button>
                    <button type="button" class="tool-btn" data-tool="arrow">Ok</button>
                    <button type="button" class="tool-btn" data-tool="text">Yazı</button>
                    <button type="button" class="tool-btn" data-tool="delete">Sil</button>

                    <div class="toolbar-separator"></div>

                    <label class="cad-color-tool">
                        Renk
                        <input type="color" id="cadColor" value="#111827">
                    </label>

                    <button type="button" id="toggleGridBtn" class="toggle-btn active">Grid Açık</button>
                    <button type="button" id="toggleOrthoBtn" class="toggle-btn">Ortho Kapalı</button>
                    <button type="button" id="undoBtn">Geri Al</button>
                    <button type="button" id="clearCanvasBtn" class="danger-btn">Temizle</button>
                </div>

                <div class="canvas-wrap cad-canvas-wrap">
                    <canvas id="manualCanvas" width="1000" height="650"></canvas>
                </div>

                <p class="canvas-help">
                    Teknik çizim modu: ölçü aracı artık her açıyla çalışır. Listeden silmek için Sil aracını kullan.
                </p>
            </div>



            <input type="hidden" name="image_drawing_data" id="image_drawing_data">

                <div id="imageDrawingBox" class="manual-drawing-box" style="display:none;">
                    <div class="drawing-toolbar cad-toolbar">
                        <strong style="color:white;">Görselden Oku</strong>
                    </div>

                    <div style="padding:16px;">
                        <label>
                            Usta Çizimi Fotoğrafı
                            <input type="file" name="drawing_image" id="drawingImageInput" accept="image/*">
                        </label>

                        <button type="button" onclick="readDrawingImage()">Görseli Oku</button>

                        <p class="canvas-help">
                            Fotoğraf yüklendikten sonra sistem ölçüleri okumaya çalışacak. Çıkan bilgiyi kontrol edip düzeltebileceğiz.
                        </p>

                        <label>
                            Okunan Ölçü Bilgisi
                            <textarea id="imageDrawingResult" rows="8" placeholder="Görsel okunduktan sonra bilgiler burada görünecek..."></textarea>
                        </label>
                    </div>
                </div>
        </section>

        <section class="card category-card">
            <div class="section-head">
                <div>
                    <h2>Tavan Sistemleri</h2>
                    <p>Bioklimatik, bioklimatik sabit tavan, pergola/tente, sandviç panel tavan ve cam tavan ürünleri.</p>
                </div>
                <button type="button" onclick="addProduct('tavan')">Tavan Ürünü Ekle</button>
            </div>

            <div id="products_tavan"></div>
        </section>

        <section class="card category-card">
            <div class="section-head">
                <div>
                    <h2>Cephe Sistemleri</h2>
                    <p>Sürme cam, giyotin cam, sabit cam, katlanır cam, zip perde ve cephe kaplamaları.</p>
                </div>
                <button type="button" onclick="addProduct('cephe')">Cephe Ürünü Ekle</button>
            </div>

            <div id="products_cephe"></div>
        </section>

        <section class="card category-card">
            <div class="section-head">
                <div>
                    <h2>Kapı Sistemleri</h2>
                    <p>Sürme kapı, katlanır kapı, tek kanat / çift kanat kapı ürünleri.</p>
                </div>
                <button type="button" onclick="addProduct('kapi')">Kapı Ürünü Ekle</button>
            </div>

            <div id="products_kapi"></div>
        </section>

        <section class="card">
            <h2>Montaj Malzemeleri</h2>

            <p class="section-desc">
                Malzeme türünü seç, ölçü/tip bilgisini yaz, miktar ve birim girerek listeye ekle.
                Listeden silmek için eklenen satıra tıkla.
            </p>

            <input type="hidden" name="materials" id="materialsInput">

            <div class="material-add-box">
                <label>
                    Malzeme
                    <select id="materialType">
                        <option value="">Seçiniz</option>
                        <option value="Köşebent">Köşebent</option>
                        <option value="Profil">Profil</option>
                        <option value="Kompozit / plaka kompozit">Kompozit / plaka kompozit</option>
                        <option value="Alüminyum profil">Alüminyum profil</option>
                        <option value="Demir profil">Demir profil</option>
                        <option value="Demir karkas">Demir karkas</option>
                        <option value="Yer flanşı / U yer flanşı">Yer flanşı / U yer flanşı</option>
                        <option value="Kablo">Kablo</option>
                        <option value="L ankraj">L ankraj</option>
                        <option value="Tij">Tij</option>
                        <option value="Somun">Somun</option>
                        <option value="Silikon">Silikon</option>
                        <option value="Sprey boya">Sprey boya</option>
                        <option value="Epoxy">Epoxy</option>
                    </select>
                </label>

                <label>
                    Ölçü / Tip
                    <input type="text" id="materialSize" placeholder="Örn: 20x20 / 40x80 / 3x1,5 / şeffaf">
                </label>

                <label>
                    Birim
                    <select id="materialUnit">
                        <option value="adet">adet</option>
                        <option value="metre">metre</option>
                        <option value="cm">cm</option>
                        <option value="mm">mm</option>
                        <option value="m²">m²</option>
                        <option value="tüp">tüp</option>
                        <option value="kutu">kutu</option>
                        <option value="kg">kg</option>
                        <option value="">birimsiz</option>
                    </select>
                </label>

                <label>
                    Adet
                    <input type="text" id="materialQty" placeholder="Örn: 1 / 2 / 5">
                </label>

                <button type="button" id="addMaterialBtn">Ekle</button>
            </div>

            <div class="selected-material-box">
                <strong>Seçilen Malzemeler</strong>
                <ul id="selectedMaterialList">
                    <li class="empty-material">Henüz malzeme eklenmedi.</li>
                </ul>
            </div>
        </section>

        <section class="card">
            <h2>Notlar</h2>
            <textarea name="notes" rows="5" placeholder="Ek notları buraya yaz..."></textarea>
        </section>

        <div class="actions">
            <button type="submit">Önizleme / PDF Oluştur</button>
        </div>
    </form>
</div>

<script>
    let productIndex = 0;

    const categoryCounters = {
        tavan: 0,
        cephe: 0,
        kapi: 0
    };

    const categoryNames = {
        tavan: 'Tavan',
        cephe: 'Cephe',
        kapi: 'Kapı'
    };

    function toggleDrawingMode() {
        const selected = document.querySelector('input[name="drawing_mode"]:checked');
        const manualBox = document.getElementById('manualDrawingBox');
        const imageBox = document.getElementById('imageDrawingBox');

        if (!selected) return;

        if (manualBox) {
            manualBox.style.display = selected.value === 'manual' ? 'block' : 'none';
        }

        if (imageBox) {
            imageBox.style.display = selected.value === 'image' ? 'block' : 'none';
        }

        if (selected.value === 'manual' && window.resizeManualCanvas) {
            setTimeout(window.resizeManualCanvas, 100);
        }
    }
    function readDrawingImage() {
        const fileInput = document.getElementById('drawingImageInput');
        const resultBox = document.getElementById('imageDrawingResult');
        const hiddenInput = document.getElementById('image_drawing_data');

        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            alert('Önce usta çizimi fotoğrafını seç.');
            return;
        }

        const formData = new FormData();
        formData.append('drawing_image', fileInput.files[0]);

        if (resultBox) {
            resultBox.value = 'Görsel okunuyor, bekle...';
        }

        fetch('analyze_image.php', {
            method: 'POST',
            body: formData
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (result) {
                if (!result.success) {
                    const message = result.message || 'Görsel okunamadı.';
                    if (resultBox) {
                        resultBox.value = JSON.stringify(result, null, 2);
                    }
                    alert(message + "\n\nDetay için alttaki Okunan Ölçü Bilgisi kutusuna bak.");
                    return;
                }

                const jsonText = JSON.stringify(result.data, null, 2);

                if (resultBox) {
                    resultBox.value = jsonText;
                }

                if (hiddenInput) {
                    hiddenInput.value = jsonText;
                }

                alert('Görsel okundu. Ölçüleri kontrol et.');
            })
            .catch(function (error) {
                if (resultBox) {
                    resultBox.value = String(error);
                }

                alert('Görsel okuma sırasında hata oluştu.');
            });
    }

    function addProduct(category) {
        const container = document.getElementById('products_' + category);
        if (!container) return;

        const index = productIndex++;
        categoryCounters[category]++;

        const visibleNumber = categoryCounters[category];
        const productTitle = categoryNames[category] + ' Ürünü ' + visibleNumber;

        const div = document.createElement('div');
        div.className = 'product-card';
        div.dataset.index = index;

        div.innerHTML = `
            <div class="product-top">
                <h3>${productTitle}</h3>
                <button type="button" onclick="this.closest('.product-card').remove()">Sil</button>
            </div>

            <input type="hidden" name="products[${index}][category]" value="${category}">

            <label>Ürün Tipi
                <select name="products[${index}][type]" onchange="changeProductFields(this, ${index}, '${category}')">
                    ${getOptionsByCategory(category)}
                </select>
            </label>

            <div id="product_fields_${index}"></div>
        `;

        container.appendChild(div);
    }

    function getOptionsByCategory(category) {
        if (category === 'tavan') {
            return `
                <option value="">Seçiniz</option>
                <option value="bioklimatik">Bioklimatik</option>
                <option value="bioklimatik_sabit">Bioklimatik Sabit Tavan</option>
                <option value="pergola_tente">Pergola / Tente</option>
                <option value="sandvic_panel_tavan">Sandviç Panel Tavan</option>
                <option value="cam_tavan">Cam Tavan</option>
                <option value="kompozit_tavan">Kompozit Tavan Kapama</option>
                <option value="ozel">Özel Tavan Ürünü</option>
            `;
        }

        if (category === 'cephe') {
            return `
                <option value="">Seçiniz</option>
                <option value="surme_cam">Sürme Cam</option>
                <option value="giyotin_cam">Giyotin Cam</option>
                <option value="sabit_cam">Sabit Cam</option>
                <option value="katlanir_cam">Katlanır Cam</option>
                <option value="zip_perde">Zip Perde</option>
                <option value="sandvic_panel">Sandviç Panel Cephe Kapama</option>
                <option value="kompozit_kapama">Kompozit Cephe Kapama</option>
                <option value="ozel">Özel Cephe Ürünü</option>
            `;
        }

        if (category === 'kapi') {
            return `
                <option value="">Seçiniz</option>
                <option value="surme_kapi">Sürme Kapı</option>
                <option value="katlanir_kapi">Katlanır Kapı</option>
                <option value="tek_kanat_kapi">Tek Kanat Kapı</option>
                <option value="cift_kanat_kapi">Çift Kanat Kapı</option>
                <option value="ozel">Özel Kapı Ürünü</option>
            `;
        }

        return '<option value="">Seçiniz</option>';
    }

    function changeProductFields(select, index, category) {
        const type = select.value;
        const box = document.getElementById('product_fields_' + index);
        if (!box) return;

        if (!type) {
            box.innerHTML = '';
            return;
        }

        if (category === 'tavan') {
            box.innerHTML = getTavanFields(index, type);
            return;
        }

        if (category === 'cephe') {
            box.innerHTML = getCepheFields(index, type);
            addMeasureRow(index);
            return;
        }

        if (category === 'kapi') {
            box.innerHTML = getKapiFields(index, type);
            addMeasureRow(index);
            return;
        }
    }

    function getTavanFields(index, type) {
        let extraRalField = '';

        if (type === 'bioklimatik_sabit' || type === 'sandvic_panel_tavan') {
            extraRalField = `<label>Panel RAL <input type="text" list="ralOptions" name="products[${index}][panel_ral]" placeholder="7016"></label>`;
        }

        if (type === 'pergola_tente') {
            extraRalField = `<label>Kumaş RAL <input type="text" list="ralOptions" name="products[${index}][panel_ral]" placeholder="7016"></label>`;
        }

        if (type === 'cam_tavan') {
            extraRalField = `<label>Cam Rengi <input type="text" name="products[${index}][panel_ral]" placeholder="Şeffaf / Füme / Bronz"></label>`;
        }

        if (type === 'kompozit_tavan') {
            extraRalField = `<label>Kompozit RAL <input type="text" list="ralOptions" name="products[${index}][panel_ral]" placeholder="7016"></label>`;
        }

        return `
            <div class="grid">
                <label>Genişlik mm <input type="number" name="products[${index}][width]" placeholder="6000"></label>
                <label>Derinlik mm <input type="number" name="products[${index}][depth]" placeholder="3000"></label>
                <label>Yükseklik mm <input type="number" name="products[${index}][height]" placeholder="2500"></label>
                <label>Ayak Yüksekliği mm <input type="number" name="products[${index}][leg_height]" placeholder="3000"></label>
                <label>Adet <input type="number" name="products[${index}][quantity]" value="1"></label>

                <label>Sistem Tipi
                    <select name="products[${index}][system_type]">
                        <option value="">Seçiniz</option>
                        <option value="Havuz Tipi">Havuz Tipi</option>
                        <option value="Duvar Tipi">Duvar Tipi</option>
                        <option value="Tilt Duvar Tipi">Tilt Duvar Tipi</option>
                        <option value="Sabit Sistem">Sabit Sistem</option>
                    </select>
                </label>

                <label>Orta Kayıt
                    <select name="products[${index}][middle_record]">
                        <option value="">Seçiniz</option>
                        <option value="Var">Var</option>
                        <option value="Yok">Yok</option>
                    </select>
                </label>

                <label>Karkas
                    <select name="products[${index}][has_frame]">
                        <option value="">Seçiniz</option>
                        <option value="Var">Var</option>
                        <option value="Yok">Yok</option>
                    </select>
                </label>

                <label>Kasa RAL <input type="text" list="ralOptions" name="products[${index}][case_ral]" placeholder="7016"></label>
                ${extraRalField}
                <label>LED <input type="text" name="products[${index}][led]" placeholder="Var / Yok / RGB"></label>
                <label>Ayak Sayısı <input type="text" name="products[${index}][leg_count]" placeholder="4 adet"></label>
            </div>

            <label>Ürün Notu
                <textarea name="products[${index}][note]" rows="3" placeholder="Özel tavan notu..."></textarea>
            </label>
        `;
    }

    function getCepheFields(index, type) {
        let camTuruField = '';
        let renkLabel = 'Cam / Kumaş / Panel Rengi';

        if (
            type === 'surme_cam' ||
            type === 'giyotin_cam' ||
            type === 'sabit_cam' ||
            type === 'katlanir_cam'
        ) {
            camTuruField = `<label>Cam Türü <input type="text" list="glassTypeOptions" name="products[${index}][glass_type]" placeholder="Konfor Isı Cam"></label>`;
            renkLabel = 'Cam Rengi';
        }

        if (type === 'zip_perde') {
            renkLabel = 'Kumaş Rengi';
        }

        if (
            type === 'kompozit_cephe' ||
            type === 'sandvic_panel_cephe'
        ) {
            renkLabel = 'Panel Rengi';
        }

        return `
            <div class="multi-measure-box">
                <div class="multi-head">
                    <strong>Ölçüler</strong>
                    <button type="button" onclick="addMeasureRow(${index})">Ölçü Satırı Ekle</button>
                </div>
                <div id="measure_rows_${index}"></div>
            </div>

            <div class="grid">
                <label>Cephe
                    <select name="products[${index}][side]">
                        <option value="">Seçiniz</option>
                        <option value="Ön Cephe">Ön Cephe</option>
                        <option value="Sağ Cephe">Sağ Cephe</option>
                        <option value="Sol Cephe">Sol Cephe</option>
                        <option value="Arka Cephe">Arka Cephe</option>
                    </select>
                </label>

                <label>Kasa RAL <input type="text" list="ralOptions" name="products[${index}][case_ral]" placeholder="7016"></label>
                ${camTuruField}
                <label>${renkLabel} <input type="text" name="products[${index}][color]" placeholder="Şeffaf / Füme / 7016"></label>
            </div>

            <label>Ürün Notu
                <textarea name="products[${index}][note]" rows="3" placeholder="Özel cephe notu..."></textarea>
            </label>
        `;
    }

    function getKapiFields(index, type) {
        return `
            <div class="multi-measure-box">
                <div class="multi-head">
                    <strong>Ölçüler</strong>
                    <button type="button" onclick="addMeasureRow(${index})">Ölçü Satırı Ekle</button>
                </div>
                <div id="measure_rows_${index}"></div>
            </div>

            <div class="grid">

                <label>Açılım Yönü
                    <select name="products[${index}][opening_direction]">
                        <option value="">Seçiniz</option>
                        <option value="Sağa Açılır">Sağa Açılır</option>
                        <option value="Sola Açılır">Sola Açılır</option>
                        <option value="İçeri Açılır">İçeri Açılır</option>
                        <option value="Dışarı Açılır">Dışarı Açılır</option>
                    </select>
                </label>

                <label>Kasa RAL <input type="text" list="ralOptions" name="products[${index}][case_ral]" placeholder="7016"></label>
                <label>Cam Türü <input type="text" list="glassTypeOptions" name="products[${index}][glass_type]" placeholder="Konfor Isı Cam"></label>
                <label>Kilit / Kol Notu <input type="text" name="products[${index}][lock_note]" placeholder="Kilitli / Kolsuz / Özel"></label>
            </div>

            <label>Ürün Notu
                <textarea name="products[${index}][note]" rows="3" placeholder="Özel kapı notu..."></textarea>
            </label>
        `;
    }

    function addMeasureRow(productIndex) {
        const container = document.getElementById('measure_rows_' + productIndex);
        if (!container) return;

        let rowIndex = parseInt(container.dataset.nextIndex || '0', 10);
        container.dataset.nextIndex = rowIndex + 1;

        const row = document.createElement('div');
        row.className = 'measure-row';

        const deleteButton = rowIndex === 0
            ? ''
            : `<button type="button" onclick="this.closest('.measure-row').remove()">Sil</button>`;

        row.innerHTML = `
            <label>Genişlik mm
                <input type="number" name="products[${productIndex}][items][${rowIndex}][width]" placeholder="3000">
            </label>

            <label>Yükseklik mm
                <input type="number" name="products[${productIndex}][items][${rowIndex}][height]" placeholder="2500">
            </label>

            <label>Ayak Yüksekliği mm
                <input type="number" name="products[${productIndex}][items][${rowIndex}][leg_height]" placeholder="3000">
            </label>

            <label>Adet
                <input type="number" name="products[${productIndex}][items][${rowIndex}][quantity]" value="1">
            </label>

            ${deleteButton}
        `;

        container.appendChild(row);
    }

    const selectedMaterials = [];

    function updateSelectedMaterials() {
        const list = document.getElementById('selectedMaterialList');
        const input = document.getElementById('materialsInput');

        if (!list || !input) return;

        list.innerHTML = '';

        if (selectedMaterials.length === 0) {
            list.innerHTML = '<li class="empty-material">Henüz malzeme eklenmedi.</li>';
            input.value = '';
            return;
        }

        selectedMaterials.forEach(function (item, index) {
            const li = document.createElement('li');
            li.className = 'selected-material-item';
            li.textContent = item;
            li.title = 'Silmek için tıkla';

            li.addEventListener('click', function () {
                selectedMaterials.splice(index, 1);
                updateSelectedMaterials();
            });

            list.appendChild(li);
        });

        input.value = selectedMaterials.join("\n");
    }

    function addMaterialToList() {
        const typeEl = document.getElementById('materialType');
        const sizeEl = document.getElementById('materialSize');
        const unitEl = document.getElementById('materialUnit');
        const qtyEl = document.getElementById('materialQty');

        const type = typeEl ? typeEl.value.trim() : '';
        const size = sizeEl ? sizeEl.value.trim() : '';
        const unit = unitEl ? unitEl.value.trim() : '';
        const qty = qtyEl ? qtyEl.value.trim() : '';

        if (!type) {
            alert('Önce malzeme seç.');
            return;
        }

        let finalText = type;

        // Profil 20x20 m
        if (size) {
            finalText += ' ' + size;

            if (unit) {
                finalText += ' ' + unit;
            }
        }

        // 3 adet
        if (qty) {
            finalText += ' - ' + qty + ' adet';
        }

        selectedMaterials.push(finalText);
        updateSelectedMaterials();

        if (typeEl) typeEl.value = '';
        if (sizeEl) sizeEl.value = '';
        if (unitEl) unitEl.value = '';
        if (qtyEl) qtyEl.value = '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const addBtn = document.getElementById('addMaterialBtn');

        if (addBtn) {
            addBtn.addEventListener('click', addMaterialToList);
        }

        updateSelectedMaterials();
    });

</script>

<script src="assets/draw.js"></script>

</body>
</html>


