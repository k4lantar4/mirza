<?php
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../botapi.php';
require_once __DIR__ . '/../panels.php';
require_once __DIR__ . '/../function.php';
$ManagePanel = new ManagePanel();


$stmt = $pdo->prepare("SELECT id FROM user WHERE checkstatus = '1' ORDER BY RAND() LIMIT 10");
$stmt->execute();
while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmts = $pdo->prepare("SELECT * FROM invoice WHERE id_user = '{$result['id']}' AND Status = 'disablebyadmin'  ORDER BY RAND() LIMIT 10");
        $stmts->execute();
        $selectinvoice = $stmts->fetchAll();
        if($stmts->rowCount() == 0){
            update("user","checkstatus","0","id",$result['id']);
            continue;
            }
        foreach ($selectinvoice as $invoice){
        $get_username_Check = $ManagePanel->DataUser($invoice['Service_location'],$invoice['username']);
        if($get_username_Check['status'] == "disabled"){
        $userchengestatus = $ManagePanel->Change_status($invoice['username'],$invoice['Service_location']);
        }
        update("invoice","Status","active","id_invoice",$invoice['id_invoice']);
        }
    }