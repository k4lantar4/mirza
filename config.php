<?php
$dbname = 'bb'; 
$usernamedb = 'bb'; 
$passworddb = 'nbPAOQbkqSQMNJYw1vDXTCbeu'; 
$connect = mysqli_connect("localhost", $usernamedb, $passworddb, $dbname);
if ($connect->connect_error) { die("error" . $connect->connect_error); }
mysqli_set_charset($connect, "utf8mb4");
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
];
$dsn = "mysql:host=localhost;dbname=$dbname;charset=utf8mb4";
try { $pdo = new PDO($dsn, $usernamedb, $passworddb, $options); } catch (\PDOException $e) { error_log("Database connection failed: " . $e->getMessage()); }
$APIKEY = '8444337751:AAEF29Qxejphr7dpRMJ3kw9n-AmIZkMO9c4'; 
$adminnumber = '1713374557'; 
$domainhosts = 'bb.farsbazar.com';
$usernamebot = 'capitanvpn_adminbot'; 
$new_marzban = true;
?>
