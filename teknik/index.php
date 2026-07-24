<?php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Teknik Çizim Otomasyonu</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f2f2;
        }

        .header {
            background: #111;
            color: #d6b87c;
            padding: 25px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 34px;
            letter-spacing: 2px;
        }

        .header p {
            color: #f0d28c;
            font-size: 17px;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            background: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.12);
        }

        .box {
            border: 2px dashed #999;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            background: #fafafa;
        }

        input[type="file"] {
            margin: 20px 0;
            font-size: 16px;
        }

        button {
            background: #111;
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 6px;
        }

        button:hover {
            background: #333;
        }

        .secondary-btn {
            background: #d6b87c;
            color: #111;
        }

        .secondary-btn:hover {
            background: #c7a86e;
        }

        .loading {
            margin-top: 18px;
            color: #333;
            font-weight: bold;
            display: none;
        }

        .data-panel {
            display: none;
            margin-top: 30px;
            background: #f8f8f8;
            padding: 24px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        .data-panel h3 {
            margin-top: 0;
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }

        .info-card {
            background: white;
            padding: 14px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .info-card strong {
            display: block;
            margin-bottom: 6px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            margin-bottom: 25px;
            background: white;
        }

        th {
            background: #111;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            border: 1px solid #ddd;
            padding: 9px;
        }

        tr:nth-child(even) {
            background: #f3f3f3;
        }

        .result {
            margin-top: 25px;
            padding: 20px;
            background: #f6f6f6;
            border-radius: 8px;
            display: none;
        }

        .download-link {
            display: inline-block;
            margin-top: 15px;
            margin-right: 12px;
            padding: 12px 20px;
            background: #d6b87c;
            color: #111;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .preview {
            margin-top: 30px;
            width: 100%;
            height: 720px;
            border: 1px solid #ccc;
            display: none;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>TEKNİK KEŞİF ÇİZİMİ OTOMASYONU</h1>
        <p>PDF teklif/sözleşmeden otomatik veri çıkarma + SVG/PDF teknik çizim üretme</p>
    </div>

    <div class="container">
        <div class="box">
            <h2>PDF Teklif / Sözleşme Yükle</h2>
            <p>Önce PDF dosyasını seç, sonra verileri oku. Kontrol ettikten sonra çizimi oluştur.</p>

            <input type="file" id="pdfFile" accept="application/pdf">
            <br>

            <button class="secondary-btn" onclick="readData()">1. Verileri Oku</button>
            <button onclick="generateDrawing()">2. Çizim Oluştur</button>
            <button onclick="generateContract()">3. Sözleşme Oluştur</button>

            <div id="contractDownloadArea" style="display:none; margin-top:20px;">
                <button onclick="downloadContractPdf()">PDF Sözleşme İndir</button>
                <button onclick="downloadContractWord()">Word Sözleşme İndir</button>
            </div>

            <div class="loading" id="loadingText">İşlem yapılıyor, lütfen bekle...</div>
        </div>

        <div class="data-panel" id="dataPanel">
            <h3>PDF'den Çıkarılan Veriler</h3>

            <div class="info-grid">
                <div class="info-card"><strong>Müşteri</strong><span id="customerText">-</span></div>
                <div class="info-card"><strong>Teklif / Ref No</strong><span id="offerText">-</span></div>
                <div class="info-card"><strong>İmalat / Ref No</strong><span id="approvalText">-</span></div>
                <div class="info-card"><strong>Satıcı</strong><span id="sellerText">-</span></div>
                <div class="info-card"><strong>Marka</strong><span id="brandText">-</span></div>
                <div class="info-card"><strong>Tarih</strong><span id="dateText">-</span></div>
            </div>

            <h3>Modül Bilgileri</h3>
            <table>
                <thead>
                    <tr>
                        <th>Modül</th>
                        <th>Alan</th>
                        <th>En</th>
                        <th>Boy</th>
                        <th>Yükseklik</th>
                        <th>Adet</th>
                        <th>Sistem</th>
                    </tr>
                </thead>
                <tbody id="modulesTable"></tbody>
            </table>

            <h3>Cam Sistemleri</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Alan</th>
                        <th>En</th>
                        <th>Yükseklik</th>
                        <th>Adet</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody id="glassTable"></tbody>
            </table>

            <h3>Cephe Kapama Bilgileri</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Alan</th>
                        <th>En</th>
                        <th>Yükseklik</th>
                        <th>Adet</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody id="panelTable"></tbody>
            </table>
        </div>

        <div class="result" id="resultBox">
            <h3>Çizim oluşturuldu ✅</h3>

            <a id="downloadLink" class="download-link" href="#" download style="display:none;">
                SVG Çizimi İndir
            </a>

            <a id="pdfDownloadLink" class="download-link" href="#" download style="display:none;">
                PDF Çizim İndir
            </a>

            <a id="contractDownloadLink" class="download-link" href="#" download style="display:none;">
                Sözleşmeyi İndir
            </a>
        </div>

        <iframe id="previewFrame" class="preview"></iframe>
    </div>

    <script>
        function getFile() {
            const fileInput = document.getElementById("pdfFile");

            if (!fileInput.files.length) {
                alert("Lütfen bir PDF dosyası seç.");
                return null;
            }

            return fileInput.files[0];
        }

        function meter(value) {
            if (value === null || value === undefined || value === "-" || value === "") {
                return "-";
            }

            const n = Number(value);

            if (isNaN(n)) {
                return "-";
            }

            return n.toFixed(2).replace(".", ",") + " m";
        }

        async function readData() {
            const file = getFile();

            if (!file) {
                return;
            }

            const loadingText = document.getElementById("loadingText");
            loadingText.style.display = "block";
            loadingText.innerText = "PDF okunuyor ve veriler çıkarılıyor...";

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("parse_pdf.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                loadingText.style.display = "none";

                if (!result.success) {
                    alert(result.error || "PDF verileri okunurken hata oluştu.");
                    return;
                }

                const data = result.project_data || {};
                const basic = data.basic_info || {};

                document.getElementById("customerText").innerText = basic.customer || "-";
                document.getElementById("offerText").innerText = basic.offer_no || basic.approval_no || "-";
                document.getElementById("approvalText").innerText = basic.approval_no || basic.offer_no || "-";
                document.getElementById("sellerText").innerText = basic.seller || "-";
                document.getElementById("brandText").innerText = basic.brand || "-";
                document.getElementById("dateText").innerText = basic.date_range || "-";

                const modulesTable = document.getElementById("modulesTable");
                modulesTable.innerHTML = "";

                if (data.modules && data.modules.length > 0) {
                    data.modules.forEach(function(item) {
                        modulesTable.innerHTML += `
                            <tr>
                                <td>${item.name || "-"}</td>
                                <td>${item.area || "-"}</td>
                                <td>${meter(item.width)}</td>
                                <td>${meter(item.depth)}</td>
                                <td>${meter(item.height)}</td>
                                <td>${item.quantity || "-"}</td>
                                <td>${item.system || "-"}</td>
                            </tr>
                        `;
                    });
                } else {
                    modulesTable.innerHTML = `<tr><td colspan="7">Modül verisi bulunamadı.</td></tr>`;
                }

                const glassTable = document.getElementById("glassTable");
                glassTable.innerHTML = "";

                if (data.sliding_glass && data.sliding_glass.length > 0) {
                    data.sliding_glass.forEach(function(item) {
                        glassTable.innerHTML += `
                            <tr>
                                <td>${item.id || "-"}</td>
                                <td>${item.area || "-"}</td>
                                <td>${meter(item.width)}</td>
                                <td>${meter(item.height)}</td>
                                <td>${item.quantity || "-"}</td>
                                <td>${item.description || "-"}</td>
                            </tr>
                        `;
                    });
                } else {
                    glassTable.innerHTML = `<tr><td colspan="6">Cam verisi bulunamadı.</td></tr>`;
                }

                const panelTable = document.getElementById("panelTable");
                panelTable.innerHTML = "";

                if (data.panels && data.panels.length > 0) {
                    data.panels.forEach(function(item) {
                        panelTable.innerHTML += `
                            <tr>
                                <td>${item.id || "-"}</td>
                                <td>${item.area || "-"}</td>
                                <td>${meter(item.width)}</td>
                                <td>${meter(item.height)}</td>
                                <td>${item.quantity || "-"}</td>
                                <td>${item.description || "-"}</td>
                            </tr>
                        `;
                    });
                } else {
                    panelTable.innerHTML = `<tr><td colspan="6">Cephe kapama verisi bulunamadı.</td></tr>`;
                }

                document.getElementById("dataPanel").style.display = "block";

            } catch (error) {
                loadingText.style.display = "none";
                alert("Beklenmeyen hata: " + error.message);
            }
        }

        async function generateDrawing() {
            const file = getFile();

            if (!file) {
                return;
            }

            const loadingText = document.getElementById("loadingText");
            const resultBox = document.getElementById("resultBox");

            loadingText.style.display = "block";
            loadingText.innerText = "Çizim hazırlanıyor, lütfen bekle...";
            resultBox.style.display = "none";

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("generate_drawing.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                loadingText.style.display = "none";

                if (!result.success) {
                    alert(result.error || "Çizim oluşturulamadı.");
                    return;
                }

                const svgUrl = result.svg_url;

                const downloadLink = document.getElementById("downloadLink");
                downloadLink.href = svgUrl;
                downloadLink.download = result.svg_file || "teknik_cizim.svg";
                downloadLink.style.display = "inline-block";

                const pdfDownloadLink = document.getElementById("pdfDownloadLink");
                pdfDownloadLink.href = "#";
                pdfDownloadLink.download = "teknik_cizim.pdf";
                pdfDownloadLink.style.display = "inline-block";
                pdfDownloadLink.onclick = function(event) {
                    event.preventDefault();
                    generateDrawingPdf();
                };

                const contractDownloadLink = document.getElementById("contractDownloadLink");
                contractDownloadLink.style.display = "none";

                const previewFrame = document.getElementById("previewFrame");
                previewFrame.src = svgUrl;
                previewFrame.style.display = "block";

                resultBox.style.display = "block";

            } catch (error) {
                loadingText.style.display = "none";
                alert("Beklenmeyen hata: " + error.message);
            }
        }

        async function generateDrawingPdf() {
            const file = getFile();

            if (!file) {
                return;
            }

            const loadingText = document.getElementById("loadingText");

            loadingText.style.display = "block";
            loadingText.innerText = "PDF çizim hazırlanıyor, lütfen bekle...";

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("generate_drawing_pdf.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                loadingText.style.display = "none";

                if (!result.success) {
                    alert(result.error || "PDF çizim oluşturulamadı.");
                    return;
                }

                const pdfDownloadLink = document.getElementById("pdfDownloadLink");
                pdfDownloadLink.href = result.pdf_url;
                pdfDownloadLink.download = result.pdf_file || "teknik_cizim.pdf";
                pdfDownloadLink.style.display = "inline-block";

                window.open(result.pdf_url, "_blank");

            } catch (error) {
                loadingText.style.display = "none";
                alert("Beklenmeyen hata: " + error.message);
            }
        }

        function generateContract() {
            const file = getFile();

            if (!file) {
                return;
            }

            const area = document.getElementById("contractDownloadArea");

            if (area) {
                area.style.display = "block";
            }
        }

        async function downloadContractPdf() {
            const file = getFile();

            if (!file) {
                return;
            }

            const loadingText = document.getElementById("loadingText");

            loadingText.style.display = "block";
            loadingText.innerText = "PDF sözleşme hazırlanıyor, lütfen bekle...";

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("generate_contract_pdf.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                loadingText.style.display = "none";

                if (!result.success) {
                    alert(result.error || "PDF sözleşme oluşturulamadı.");
                    return;
                }

                window.open(result.pdf_url, "_blank");

            } catch (error) {
                loadingText.style.display = "none";
                alert("Beklenmeyen hata: " + error.message);
            }
        }

        async function downloadContractWord() {
            const file = getFile();

            if (!file) {
                return;
            }

            const loadingText = document.getElementById("loadingText");

            loadingText.style.display = "block";
            loadingText.innerText = "Word sözleşme hazırlanıyor, lütfen bekle...";

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("generate_contract_docx.php", {
                    method: "POST",
                    body: formData
                });

                const result = await response.json();

                loadingText.style.display = "none";

                if (!result.success) {
                    alert(result.error || "Word sözleşme oluşturulamadı.");
                    return;
                }

                window.open(result.docx_url, "_blank");

            } catch (error) {
                loadingText.style.display = "none";
                alert("Beklenmeyen hata: " + error.message);
            }
        }
    </script>
</body>
</html>