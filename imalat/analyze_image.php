<?php
header('Content-Type: application/json; charset=utf-8');

function json_error_response($message, $details = null) {
    echo json_encode([
        'success' => false,
        'message' => $message,
        'details' => $details
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error_response('Sadece POST isteği kabul edilir.');
}

if (!isset($_FILES['drawing_image'])) {
    json_error_response('Fotoğraf gelmedi.');
}

$file = $_FILES['drawing_image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    json_error_response('Fotoğraf yüklenirken hata oluştu.', $file['error']);
}

$tmpPath = $file['tmp_name'];
$mimeType = mime_content_type($tmpPath);

$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($mimeType, $allowedTypes, true)) {
    json_error_response('Sadece JPG, PNG veya WEBP yükleyebilirsin.', $mimeType);
}

$apiKey = getenv('GEMINI_API_KEY');

if (!$apiKey) {
    json_error_response('GEMINI_API_KEY bulunamadı. Önce Gemini API key tanımlanmalı.');
}

$imageData = file_get_contents($tmpPath);

if ($imageData === false) {
    json_error_response('Fotoğraf okunamadı.');
}

$base64 = base64_encode($imageData);

$prompt = <<<PROMPT
Sen bir imalat onay formu için usta tarafından elle çizilmiş teknik krokileri okuyorsun.

Bu görseller genelde kış bahçesi / bioklimatik / cephe / tavan krokisidir.
Amaç: fotoğraftaki ölçüleri okuyup imalat çizimine çevrilecek temiz JSON üretmek.

ÇOK ÖNEMLİ:
- Ölçüleri uydurma.
- Sadece fotoğrafta gördüğün veya çok net anladığın ölçüleri yaz.
- 5.18, 5,18 veya 518 yazıları genelde 518 cm anlamına gelir.
- 300 yazısı genelde 300 cm anlamına gelir.
- 450 yazısı genelde çoğu zaman DERİNLİK anlamına gelir.
- Fotoğrafta açıkça eğimli/çapraz bir cephe yoksa "eğimli kenar" yazma.
- 450 sayısını otomatik olarak eğim kabul etme.
- Eğer çizimde 450 yandan/derinlik yönünde yazılmışsa bunu "Derinlik" olarak kaydet.
- Eğer 518 yatay genişlik gibi görünüyorsa "Genişlik" olarak kaydet.
- Eğer 300 dikey yükseklik gibi görünüyorsa "Yükseklik" olarak kaydet.
- Fotoğraftaki "m" yazısı çoğu zaman metre değil, el yazısı karışıklığı olabilir. 300, 450, 518 gibi ölçülerde unit genelde "cm" olmalı.
- Notları düzeltmeye çalış ama emin değilsen raw_notes içine aynen yaz.
- Çıktı SADECE geçerli JSON olsun. Markdown, açıklama, kod bloğu yazma.

JSON formatı kesin böyle olsun:

{
  "source": "image",
  "type": "cephe",
  "unit": "cm",
  "summary": "El çiziminden okunan ölçüler",
  "dimensions": {
    "width": {
      "label": "Genişlik",
      "length": 518,
      "unit": "cm",
      "confidence": "orta"
    },
    "height": {
      "label": "Yükseklik",
      "length": 300,
      "unit": "cm",
      "confidence": "orta"
    },
    "depth": {
      "label": "Derinlik",
      "length": 450,
      "unit": "cm",
      "confidence": "orta"
    }
  },
  "segments": [
    {
      "id": "width",
      "label": "Genişlik",
      "length": 518,
      "unit": "cm",
      "position": "front",
      "orientation": "horizontal",
      "confidence": "orta"
    },
    {
      "id": "height",
      "label": "Yükseklik",
      "length": 300,
      "unit": "cm",
      "position": "front",
      "orientation": "vertical",
      "confidence": "orta"
    },
    {
      "id": "depth",
      "label": "Derinlik",
      "length": 450,
      "unit": "cm",
      "position": "side",
      "orientation": "depth",
      "confidence": "orta"
    }
  ],
  "notes_clean": [
    "Arka yükseklik 300 cm",
    "Duvar yüksekliği 300 cm"
  ],
  "raw_notes": [],
  "warnings": [
    "El yazısı olduğu için ölçüler kullanıcı tarafından kontrol edilmeli"
  ]
}

Eğer gerçekten fotoğrafta eğimli/çapraz cephe çizgisi varsa, sadece o zaman şu segmenti ekle:
{
  "id": "sloped_edge",
  "label": "Eğimli kenar",
  "length": 0,
  "unit": "cm",
  "position": "right",
  "orientation": "sloped",
  "confidence": "dusuk"
}

Ama çizim sadece genişlik-yükseklik-derinlik anlatıyorsa sloped_edge ekleme.

Eski formatta items kullanma. Yeni formatta dimensions ve segments kullan.
PROMPT;

$payload = [
    'contents' => [
        [
            'role' => 'user',
            'parts' => [
                [
                    'text' => $prompt
                ],
                [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $base64
                    ]
                ]
            ]
        ]
    ],
    'generationConfig' => [
        'temperature' => 0.1,
        'responseMimeType' => 'application/json'
    ]
];

$model = 'gemini-2.5-flash-lite';
$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . urlencode($apiKey);

$ch = curl_init($url);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT => 90
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false) {
    json_error_response('Gemini bağlantı hatası.', $curlError);
}

$decoded = json_decode($response, true);

if ($httpCode < 200 || $httpCode >= 300) {
    json_error_response('Gemini API hata döndürdü.', $decoded ?: $response);
}

$outputText = '';

if (isset($decoded['candidates'][0]['content']['parts'])) {
    foreach ($decoded['candidates'][0]['content']['parts'] as $part) {
        if (isset($part['text'])) {
            $outputText .= $part['text'];
        }
    }
}

$outputText = trim($outputText);
$outputText = preg_replace('/^```json\s*/u', '', $outputText);
$outputText = preg_replace('/^```\s*/u', '', $outputText);
$outputText = preg_replace('/\s*```$/u', '', $outputText);
$outputText = trim($outputText);

$resultJson = json_decode($outputText, true);

if (!is_array($resultJson)) {
    json_error_response('Gemini modelinden geçerli JSON alınamadı.', [
        'raw_text' => $outputText,
        'raw_response' => $decoded
    ]);
}

if (!isset($resultJson['source'])) {
    $resultJson['source'] = 'image';
}
// Gemini bazen derinliği yanlışlıkla eğimli kenar olarak da yazıyor.
// Eğer depth ile sloped_edge aynı ölçüyse, sloped_edge'i siliyoruz.
if (
    isset($resultJson['dimensions']['depth']['length']) &&
    isset($resultJson['segments']) &&
    is_array($resultJson['segments'])
) {
    $depthLength = (float) $resultJson['dimensions']['depth']['length'];

    $cleanSegments = [];

    foreach ($resultJson['segments'] as $segment) {
        $id = $segment['id'] ?? '';
        $orientation = $segment['orientation'] ?? '';
        $length = isset($segment['length']) ? (float) $segment['length'] : 0;

        $isWrongSlopedDepth =
            ($id === 'sloped_edge' || $orientation === 'sloped') &&
            $depthLength > 0 &&
            abs($length - $depthLength) < 0.01;

        if (!$isWrongSlopedDepth) {
            $cleanSegments[] = $segment;
        }
    }

    $resultJson['segments'] = $cleanSegments;
}
// Notes içinde de yanlış "eğimli kenar" notu kalırsa temizle.
// Bizim bu kroki mantığında 450 derinliktir, eğim değildir.
if (isset($resultJson['notes_clean']) && is_array($resultJson['notes_clean'])) {
    $cleanNotes = [];

    foreach ($resultJson['notes_clean'] as $note) {
        $noteLower = mb_strtolower($note, 'UTF-8');

        $hasWrongSlopeNote =
            str_contains($noteLower, 'eğim') ||
            str_contains($noteLower, 'egim') ||
            str_contains($noteLower, 'eğimli') ||
            str_contains($noteLower, 'egimli');

        if (!$hasWrongSlopeNote) {
            $cleanNotes[] = $note;
        }
    }

    $resultJson['notes_clean'] = $cleanNotes;
}
echo json_encode([
    'success' => true,
    'data' => $resultJson
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);