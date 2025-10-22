<?php
header('Content-Type: application/json; charset=utf-8');

$api_url = "https://api.bitpin.org/api/v1/mkt/tickers/";

// دریافت داده با cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$json = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

// بررسی پاسخ
if ($json === false || $httpcode != 200) {
    echo json_encode(['error' => "Connection failed ($httpcode): $error"], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$data = json_decode($json, true);

// بررسی اینکه داده آرایه هست یا نه
if (!is_array($data)) {
    echo json_encode(['error' => 'Unexpected data format', 'raw' => substr($json, 0, 200)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// مقداردهی اولیه
$usdt = $trx = $ton = 0;

// پیمایش در آرایه و پیدا کردن قیمت‌ها
foreach ($data as $coin) {
    if (!isset($coin['symbol']) || !isset($coin['price'])) continue;
    switch ($coin['symbol']) {
        case 'USDT_IRT':
            $usdt = floatval($coin['price']);
            break;
        case 'TRX_IRT':
            $trx = floatval($coin['price']);
            break;
        case 'TON_IRT':
            $ton = floatval($coin['price']);
            break;
    }
}

if ($usdt == 0 && $trx == 0 && $ton == 0) {
    echo json_encode(['error' => 'Symbols not found in response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// خروجی نهایی به ریال
echo json_encode([
    'usd' => $usdt,
    'tron' => $trx,
    'ton' => $ton
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
