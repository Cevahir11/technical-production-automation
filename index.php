<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vertu Otomasyon</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Arial, Helvetica, sans-serif;
            color: #fff;
            background:
                linear-gradient(rgba(10, 15, 25, 0.68), rgba(10, 15, 25, 0.72)),
                url('assets/bg.jpg') center center / cover no-repeat fixed;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 30px;
        }

        .portal {
            width: 100%;
            max-width: 1180px;
        }

        .brand {
            text-align: center;
            margin-bottom: 38px;
        }

        .brand-name {
            color: #d6b87c;
            font-size: 32px;
            font-weight: 900;
            letter-spacing: 9px;
            margin-bottom: 14px;
            text-shadow: 0 3px 12px rgba(0, 0, 0, 0.45);
        }
        .brand-name::after {
            content: "";
            display: block;
            width: 90px;
            height: 3px;
            margin: 10px auto 0;
            background: #d6b87c;
            border-radius: 3px;
        }

        h1 {
            margin: 0;
            font-size: 44px;
            font-weight: 900;
        }

        .description {
            margin: 15px auto 0;
            max-width: 760px;
            color: #e5e7eb;
            font-size: 17px;
            line-height: 1.7;
        }

        .module-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }

        .module-card {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 24px;
            overflow: hidden;
            text-decoration: none;
            color: #111827;
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.28);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: block;
        }

        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 28px 55px rgba(0, 0, 0, 0.36);
        }

        .card-image {
            height: 230px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .card-image::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.40), rgba(0,0,0,0.10));
        }

        .technical-image {
            background-image: url('assets/teknik.jpg');
        }

        .manufacturing-image {
            background-image: url('assets/imalat.jpg');
        }

        .card-badge {
            position: absolute;
            left: 18px;
            top: 18px;
            z-index: 2;
            padding: 10px 16px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 800;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .technical-badge {
            background: rgba(214, 184, 124, 0.92);
            color: #111;
        }

        .manufacturing-badge {
            background: rgba(37, 99, 235, 0.92);
        }

        .card-content {
            padding: 28px 26px 30px;
        }

        .card-content h2 {
            margin: 0 0 14px;
            font-size: 28px;
            font-weight: 900;
        }

        .card-content p {
            margin: 0;
            color: #4b5563;
            font-size: 15.5px;
            line-height: 1.7;
        }

        .card-footer {
            margin-top: 22px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 900;
            font-size: 14px;
        }

        .technical-text {
            color: #9a741f;
        }

        .manufacturing-text {
            color: #2563eb;
        }

        @media (max-width: 850px) {
            .module-grid {
                grid-template-columns: 1fr;
            }

            h1 {
                font-size: 34px;
            }

            .card-image {
                height: 210px;
            }
        }
    </style>
</head>
<body>

<main class="portal">
    <header class="brand">
        <div class="brand-name">VERTU</div>
        <h1>Üretim ve Teknik Otomasyon</h1>
        <p class="description">
            Kullanmak istediğiniz modülü seçin. Teknik çizim, sözleşme oluşturma ve
            imalat onay süreçlerini tek ekrandan yönetin.
        </p>
    </header>

    <section class="module-grid">
        <a href="teknik/" class="module-card">
            <div class="card-image technical-image">
                <div class="card-badge technical-badge">TEKNİK MODÜL</div>
            </div>

            <div class="card-content">
                <h2>Teknik Çizim ve Sözleşme</h2>
                <p>
                    Teklif veya sözleşme PDF dosyasını yükleyin, proje verilerini otomatik okuyun,
                    teknik çizim üretin ve PDF / Word sözleşme çıktısı oluşturun.
                </p>

                <div class="card-footer technical-text">
                    UYGULAMAYI AÇ →
                </div>
            </div>
        </a>

        <a href="imalat/" class="module-card">
            <div class="card-image manufacturing-image">
                <div class="card-badge manufacturing-badge">İMALAT MODÜLÜ</div>
            </div>

            <div class="card-content">
                <h2>İmalat Onay Formu</h2>
                <p>
                    Ürün ve ölçü bilgilerini girin, otomatik teknik görünüş oluşturun,
                    mühendis müdahalesi yapın ve imalat onay formunu hazırlayın.
                </p>

                <div class="card-footer manufacturing-text">
                    UYGULAMAYI AÇ →
                </div>
            </div>
        </a>
    </section>
</main>

</body>
</html>