<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
require __DIR__ . '/../vendor/autoload.php';
$ManagePanel = new ManagePanel();
$setting = select("setting", "*");
$textbotlang = languagechange();
$month_date_time_start = time() - 86400;
$month_date_time_start = date('Y/m/d H:i:s',$month_date_time_start);
$stmt = $pdo->prepare("SELECT * FROM Payment_report WHERE time < '$month_date_time_start' AND payment_Status = 'Unpaid'");
$stmt->execute();

while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $status_var = [
        'cart to cart' =>  $textbotlang['textbot']['carttocart'],
        'aqayepardakht' => $textbotlang['textbot']['aqayepardakht'],
        'zarinpal' => $textbotlang['textbot']['zarinpal'],
        'plisio' => $textbotlang['textbot']['textnowpayment'],
        'arze digital offline' => $textbotlang['textbot']['textnowpaymenttron'],
        'Currency Rial 1' => $textbotlang['textbot']['iranpay2'],
        'Currency Rial 2' => $textbotlang['textbot']['iranpay3'],
        'Currency Rial 3' => $textbotlang['textbot']['iranpay1'],
        'Currency Rial tow' => "پرداخت ارزی ریالی",
        'Currency Rial gateway3' => "پرداخت ارزی ریالی دوم",
        'perfect' => "پرفکت مانی",
        'paymentnotverify' => $textbotlang['textbot']['textpaymentnotverify'],
        'Star Telegram' => $textbotlang['textbot']['text_star_telegram'],
        'nowpayment' => $textbotlang['textbot']['textsnowpayment']
        
    ][$result['Payment_Method']];
    $textexpire = "⭕️ کاربر گرامی ، فاکتور زیر به دلیل عدم پرداخت در مدت زمان مشخص شده منقضی شد .
❗️لطفاً به هیچ عنوان وجهی بابت این فاکتور  پرداخت نکنید و مجدداً فاکتور ایجاد نمایید ‌‌.

🛒 روش پرداختی شما : $status_var
📌 کد فاکتور : <code>{$result['id_order']}</code>
🪙 مبلغ فاکتور :  {$result['price']} تومان";
// sendmessage($result['id_user'], $textexpire, null, 'html');
deletemessage($result['id_user'], $result['message_id']);
update("Payment_report","payment_Status","expire","id_order",$result['id_order']);
}