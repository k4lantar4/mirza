<?php

function DirectPaymentbot($order_id,$image = 'images.jpg'){
    global $pdo,$ManagePanel,$textbotlang,$keyboardextendfnished,$keyboard,$Confirm_pay,$from_id,$message_id,$datatextbot;
    $setting = select("setting", "*");
    $Payment_report = select("Payment_report", "*", "id_order", $order_id,"select");
    $format_price_cart = number_format($Payment_report['price']);
    $Balance_id = select("user", "*", "id", $Payment_report['id_user'],"select");
    $Balance_id['Balance'] = json_decode(file_get_contents("data/{$Payment_report['id_user']}/{$Payment_report['id_user']}.json"),true)['Balance'];
    update("user","Processing_value","0", "id",$Balance_id['id']);
    update("user","Processing_value_one","0", "id",$Balance_id['id']);
    update("user","Processing_value_tow","0", "id",$Balance_id['id']);
    update("user","Processing_value_four","0", "id",$Balance_id['id']);
        $Balance_confrim = intval($Balance_id['Balance']) + intval($Payment_report['price']);
        $userbalance = json_decode(file_get_contents("data/{$Payment_report['id_user']}/{$Payment_report['id_user']}.json"),true);
        $userbalance['Balance'] = $Balance_confrim;
        file_put_contents("data/{$Payment_report['id_user']}/{$Payment_report['id_user']}.json",json_encode($userbalance));
        update("Payment_report","payment_Status","paid","id_order",$Payment_report['id_order']);
        $Payment_report['price'] = number_format($Payment_report['price'], 0);
        $format_price_cart = $Payment_report['price'];
        if($Payment_report['Payment_Method'] == "cart to cart" or   $Payment_report['Payment_Method'] == "arze digital offline"){
        $textconfrom = "⭕️ یک پرداخت جدید انجام شده است
        افزایش موجودی.
👤 شناسه کاربر: <code>{$Balance_id['id']}</code>
🛒 کد پیگیری پرداخت: {$Payment_report['id_order']}
⚜️ نام کاربری: @{$Balance_id['username']}
💸 مبلغ پرداختی: $format_price_cart تومان
✍️ توضیحات : {$Payment_report['dec_not_confirmed']}";
        Editmessagetext($from_id, $message_id, $textconfrom, $Confirm_pay);
        }
        sendmessage($Payment_report['id_user'], "💎 کاربر گرامی مبلغ {$Payment_report['price']} تومان به کیف پول شما واریز گردید با تشکراز پرداخت شما.

🛒 کد پیگیری شما: {$Payment_report['id_order']}", null, 'HTML');
}
function channel_check($id_channel){
    global $from_id;
        $channel_link = array();
         $response = telegram('getChatMember',[
                'chat_id' => $id_channel,
                'user_id' => $from_id
                ]);
            if($response['ok']){
        if(!in_array($response['result']['status'], ['member', 'creator', 'administrator'])){
                $channel_link[] = $id_channel;
            }
        }

        if(count($channel_link) == 0){
            return [];
        }else{
            return $channel_link;
        }
}
function getProductTableInfo($userbot, $ApiToken) {
    if ($userbot['agent'] == 'n2') {
        return [
            'table' => 'partner_product',
            'filter' => "bot_token = '$ApiToken'",
            'filter_param' => [':bot_token' => $ApiToken]
        ];
    } else {
        return [
            'table' => 'product',
            'filter' => "agent = '{$userbot['agent']}'",
            'filter_param' => [':agent' => $userbot['agent']]
        ];
    }
}
function getProductByCode($code_product, $userbot, $ApiToken) {
    $tableInfo = getProductTableInfo($userbot, $ApiToken);
    return select($tableInfo['table'], "*", "code_product", $code_product, "select");
}
function getJsonValue($json_string, $key, $default = null) {
    if (empty($json_string)) {
        return $default;
    }
    $decoded = json_decode($json_string, true);
    if (!is_array($decoded) || !isset($decoded[$key])) {
        return $default;
    }
    return $decoded[$key];
}
