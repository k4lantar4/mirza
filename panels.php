<?php
ini_set('error_log', 'error_log');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Marzban.php';
require_once __DIR__ . '/function.php';
require_once __DIR__ . '/x-ui_single.php';
require_once __DIR__ . '/hiddify.php';
require_once __DIR__ . '/alireza.php';
require_once __DIR__ . '/marzneshin.php';
require_once __DIR__ . '/alireza_single.php';
require_once __DIR__ . '/WGDashboard.php';
require_once __DIR__ . '/s_ui.php';
require_once __DIR__ . '/ibsng.php';
require_once __DIR__ . '/mikrotik.php';
require_once __DIR__ . '/pasarguard.php';

class ManagePanel
{
    public $pdo, $domainhosts, $name_panel, $new_marzban;
    function createUser($name_panel, $code_product, $usernameC, array $Data_Config)
    {
        $Output = [];
        global $pdo, $domainhosts, $new_marzban;
        if (strlen($usernameC) < 3) {
            return array(
                "status" => "Unsuccessful",
                "msg" => "Username must be at least 3 characters long."
            );
        }
        // input time expire timestep use $Data_Config
        // input data_limit byte use $Data_Config
        // input username use $Data_Config
        // input from_id use $Data_Config
        // input type config use $Data_Config
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel == false) {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
            return $Output;
        }
        if ($Get_Data_Panel['subvip'] == "onsubvip") {
            $inoice = select("invoice", "*", "username", $usernameC, "select");
        } else {
            $inoice = false;
        }
        if (!in_array($code_product, ["usertest", "🛍 حجم دلخواه", "customvolume"])) {

            $stmt = $pdo->prepare("SELECT * FROM product WHERE (Location = :name_panel OR Location = '/all')  AND code_product = :code_product");
            $stmt->bindParam(':name_panel', $name_panel);
            $stmt->bindParam(':code_product', $code_product);
            $stmt->execute();
            $Get_Data_Product = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            if ($code_product == "usertest") {
                $Get_Data_Product['name_product'] = "usertest";
            } else {
                $Get_Data_Product['name_product'] = false;
            }
            $Get_Data_Product['data_limit_reset'] = "no_reset";
        }
        $expire = $Data_Config['expire'];
        $data_limit = $Data_Config['data_limit'];
        $note = "{$Data_Config['from_id']} | {$Data_Config['username']} | {$Data_Config['type']}";
        if ($Get_Data_Panel['type'] == "marzban") {
            //create user
            $ConnectToPanel = adduser($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire, $note, $Get_Data_Product['data_limit_reset'], $Get_Data_Product['name_product']);
            if (!empty($ConnectToPanel['status']) && $ConnectToPanel['status'] == 500) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['status']
                );
            }
            if (!empty($ConnectToPanel['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['error']
                );
            }
            $data_Output = json_decode($ConnectToPanel['body'], true);
            if (!empty($data_Output['detail']) && $data_Output['detail']) {
                $Output['status'] = 'Unsuccessful';
                if ($data_Output['detail']) {
                    $Output['msg'] = $data_Output['detail'];
                } else {
                    $Output['msg'] = '';
                }
            } else {
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $data_Output['subscription_url'])) {
                    $data_Output['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($data_Output['subscription_url'], "/");
                }
                if ($new_marzban) {
                    $out_put_link = outputlunk($data_Output['subscription_url']);
                    if (isBase64($out_put_link)) {
                        $data_Output['links'] = base64_decode(outputlunk($data_Output['subscription_url']));
                    }
                    $data_Output['links'] = explode("\n", $data_Output['links']);
                }
                if ($inoice != false) {
                    $data_Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $data_Output['subscription_url'];
                $Output['configs'] = $data_Output['links'];
            }
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            //create user
            $ConnectToPanel = adduserm($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire, $Get_Data_Product['name_product'], $note, $Get_Data_Product['data_limit_reset']);
            if (!empty($ConnectToPanel['status']) && $ConnectToPanel['status'] == 500) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['status']
                );
            }
            if (!empty($ConnectToPanel['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['error']
                );
            }
            $data_Output = json_decode($ConnectToPanel['body'], true);
            if (isset($data_Output['detail']) && $data_Output['detail']) {
                $Output['status'] = 'Unsuccessful';
                if ($data_Output['detail']) {
                    $Output['msg'] = $data_Output['detail'];
                } else {
                    $Output['msg'] = '';
                }
            } else {
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $data_Output['subscription_url'])) {
                    $data_Output['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($data_Output['subscription_url'], "/");
                }
                $data_Output['links'] = outputlunk($data_Output['subscription_url']);
                if (isBase64($data_Output['links'])) {
                    $data_Output['links'] = base64_decode($data_Output['links']);
                }
                $links_user = explode("\n", trim($data_Output['links']));
                $date = new DateTime($data_Output['expire']);
                if ($inoice != false) {
                    $data_Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
                $data_Output['expire'] = $date->getTimestamp();
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $data_Output['subscription_url'];
                $Output['configs'] = $links_user;
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $subId = bin2hex(random_bytes(8));
            if (isset($Get_Data_Product['inbounds']) and $Get_Data_Product['inbounds'] != null) {
                $inbounds = $Get_Data_Product['inbounds'];
            } else {
                $inbounds = $Get_Data_Panel['inboundid'];
            }
            $data_Output = addClient($Get_Data_Panel['name_panel'], $usernameC, $expire, $data_limit, generateUUID(), "", $subId, $inbounds, $Get_Data_Product['name_product'], $note);
            if (!empty($data_Output['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['error']
                );
            } elseif (!empty($data_Output['status']) && $data_Output['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['status']
                );
            } else {
                $data_Output = json_decode($data_Output['body'], true);
                if (!$data_Output['success']) {
                    $Output['status'] = 'Unsuccessful';
                    $Output['msg'] = $data_Output['msg'];
                } else {
                    $links_user = outputlunk($Get_Data_Panel['linksubx'] . "/{$subId}");
                    if (isBase64($links_user)) {
                        $links_user = base64_decode($links_user);
                    }
                    $links_user = explode("\n", trim($links_user));
                    $Output['status'] = 'successful';
                    $Output['username'] = $usernameC;
                    $Output['subscription_url'] = $Get_Data_Panel['linksubx'] . "/{$subId}";
                    $Output['configs'] = $links_user;
                    if ($inoice != false) {
                        $Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                    }
                }
            }
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            $subId = bin2hex(random_bytes(8));
            $Expireac = $expire * 1000;
            if (isset($Get_Data_Product['inbounds']) and $Get_Data_Product['inbounds'] != null) {
                $inbounds = $Get_Data_Product['inbounds'];
            } else {
                $inbounds = $Get_Data_Panel['inboundid'];
            }
            $data_Output = addClientalireza_singel($Get_Data_Panel['name_panel'], $usernameC, $Expireac, $data_limit, generateUUID(), "", $subId, $inbounds);
            if (!empty($data_Output['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['error']
                );
            } elseif (!empty($data_Output['status']) && $data_Output['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['status']
                );
            } else {
                $data_Output = json_decode($data_Output['body'], true);
                if (!$data_Output['success']) {
                    $Output['status'] = 'Unsuccessful';
                    $Output['msg'] = $data_Output['msg'];
                } else {
                    $Output['status'] = 'successful';
                    $Output['username'] = $usernameC;
                    $Output['subscription_url'] = $Get_Data_Panel['linksubx'] . "/{$subId}";
                    $Output['configs'] = [outputlunk($Output['subscription_url'])];
                    if ($inoice != false) {
                        $Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                    }
                }
            }
        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            if ($expire != 0) {
                $current_timestamp = time();
                $diff_seconds = $expire - $current_timestamp;
                $diff_days = ceil($diff_seconds / (60 * 60 * 24));
            } else {
                $diff_days = 111111;
            }
            $uuid = generateUUID();
            $data = array(
                "uuid" => $uuid,
                "name" => $usernameC,
                "added_by_uuid" => $Get_Data_Panel['secret_code'],
                "current_usage_GB" => "0",
                "usage_limit_GB" => $data_limit / pow(1024, 3),
                "package_days" => $diff_days,
                "comment" => $note,
            );
            $data_Output = adduserhi($Get_Data_Panel['name_panel'], $data);
            if (!empty($data_Output['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['error']
                );
            } elseif (!empty($data_Output['status']) && $data_Output['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['status']
                );
            }
            $data_Output = json_decode($data_Output['body'], true);
            if (isset($data_Output['message']) && $data_Output['message']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['message'];
            } else {
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = "{$Get_Data_Panel['linksubx']}/{$data_Output['uuid']}/";
                $Output['configs'] = [];
                if ($inoice != false) {
                    $Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
            }
        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            $statement = $pdo->prepare("SELECT * FROM manualsell WHERE codepanel = :code_panel AND status = 'active' AND codeproduct = '$code_product' ORDER BY RAND() LIMIT 1");
            $statement->execute(array(':code_panel' => $Get_Data_Panel['code_panel']));
            $configman = $statement->fetch(PDO::FETCH_ASSOC);
            $Output['status'] = 'successful';
            $Output['username'] = $usernameC;
            $Output['subscription_url'] = $configman['contentrecord'];
            $Output['configs'] = "";
            update("manualsell", "status", "selled", "id", $configman['id']);
            update("manualsell", "username", $usernameC, "id", $configman['id']);
        } elseif ($Get_Data_Panel['type'] == "WGDashboard") {
            $data_limit = round($data_limit / (1024 * 1024 * 1024), 2);
            $data_Output = addpear($Get_Data_Panel['name_panel'], $usernameC);
            if (!empty($data_Output['status']) && $data_Output['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['status']
                );
            }
            if (!empty($data_Output['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $data_Output['error']
                );
            }
            $data_Output = $data_Output['body'];
            $response = json_decode($data_Output['response'], true);
            if ($data_limit != 0) {
                setjob($Get_Data_Panel['name_panel'], "total_data", $data_limit, $data_Output['public_key']);
            }
            if ($expire != 0) {
                setjob($Get_Data_Panel['name_panel'], "date", date('Y-m-d H:i:s', $expire), $data_Output['public_key']);
            }
            update("invoice", "user_info", json_encode($data_Output), "username", $usernameC);
            if (!$response['status']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $download_config = downloadconfig($Get_Data_Panel['name_panel'], $data_Output['public_key']);
                if (!empty($download_config['status']) && $download_config['status'] != 200) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $download_config['status']
                    );
                }
                if (!empty($download_config['error'])) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $download_config['error']
                    );
                }
                $download_config = json_decode($download_config['body'], true)['data'];
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = strval($download_config['file']);
                $Output['configs'] = [];
            }
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            if ($Get_Data_Product['inbounds'] != null) {
                $Get_Data_Panel['inbounds'] = $Get_Data_Product['inbounds'];
            }
            $data_Output = addClientS_ui($Get_Data_Panel['name_panel'], $usernameC, $expire, $data_limit, json_decode($Get_Data_Panel['inbounds']), $note);
            if (!$data_Output['success']) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $setting_app = get_settig($Get_Data_Panel['name_panel']);
                $url = explode(":", $Get_Data_Panel['url_panel']);
                $url_sub = $url[0] . ":" . $url[1] . ":" . $setting_app['subPort'] . $setting_app['subPath'] . $usernameC;
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = $url_sub;
                $Output['configs'] = [outputlunk($url_sub)];
            }
        } elseif ($Get_Data_Panel['type'] == "ibsng") {
            $password = bin2hex(random_bytes(6));
            $name_group = $Get_Data_Panel['proxies'];
            if ($Get_Data_Product['inbounds'] != null) {
                $name_group = $Get_Data_Panel['inbounds'];
            } elseif ($code_product == "usertest") {
                $name_group = "usertest";
            }
            $data_Output = addUserIBsng($Get_Data_Panel['name_panel'], $usernameC, $password, $name_group);
            if (!$data_Output) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = $password;
                $Output['configs'] = [];
            }
        } elseif ($Get_Data_Panel['type'] == "mikrotik") {
            $password = bin2hex(random_bytes(6));
            $name_group = $Get_Data_Panel['proxies'];
            if ($Get_Data_Product['inbounds'] != null) {
                $name_group = $Get_Data_Product['inbounds'];
            } elseif ($code_product == "usertest") {
                $name_group = "usertest";
            }
            $data_Output = addUser_mikrotik($Get_Data_Panel['name_panel'], $usernameC, $password, $name_group);
            if (isset($data_Output['error'])) {
                $Output['status'] = 'Unsuccessful';
                $Output['msg'] = $data_Output['msg'];
            } else {
                $Output['status'] = 'successful';
                $Output['username'] = $usernameC;
                $Output['subscription_url'] = $password;
                $Output['configs'] = [];
            }
        } elseif ($Get_Data_Panel['type'] == "pasarguard") {
            //create user
            $ConnectToPanel = pg_add_user($Get_Data_Panel['name_panel'], $data_limit, $usernameC, $expire, $note, $Get_Data_Product['data_limit_reset'], $Get_Data_Product['name_product']);
            if (!empty($ConnectToPanel['status']) && $ConnectToPanel['status'] == 500) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['status']
                );
            }
            if (!empty($ConnectToPanel['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $ConnectToPanel['error']
                );
            }
            $data_Output = json_decode($ConnectToPanel['body'], true);
            if (!empty($data_Output['detail']) && $data_Output['detail']) {
                $Output['status'] = 'Unsuccessful';
                if ($data_Output['detail']) {
                    $Output['msg'] = $data_Output['detail'];
                } else {
                    $Output['msg'] = '';
                }
            } else {
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $data_Output['subscription_url'])) {
                    $data_Output['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($data_Output['subscription_url'], "/");
                }
                if ($new_marzban) {
                    $out_put_link = outputlunk($data_Output['subscription_url']);
                    if (isBase64($out_put_link)) {
                        $data_Output['links'] = base64_decode(outputlunk($data_Output['subscription_url']));
                    }
                    $data_Output['links'] = explode("\n", $data_Output['links']);
                }
                if ($inoice != false) {
                    $data_Output['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
                $Output['status'] = 'successful';
                $Output['username'] = $data_Output['username'];
                $Output['subscription_url'] = $data_Output['subscription_url'];
                $Output['configs'] = $data_Output['links'];
            }
        } else {
            $Output['status'] = 'Unsuccessful';
            $Output['msg'] = 'Panel Not Found';
        }
        return $Output;
    }
    function DataUser($name_panel, $username)
    {
        $Output = array();
        global $pdo, $domainhosts, $new_marzban;
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['subvip'] == "onsubvip") {
            $inoice = select("invoice", "*", "username", $username, "select");
        } else {
            $inoice = false;
        }
        if ($Get_Data_Panel['type'] == "marzban") {
            $UsernameData = getuser($username, $Get_Data_Panel['name_panel']);
            if (!empty($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            } elseif (!empty($UsernameData['status']) && $UsernameData['status'] == 500) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } else {
                $UsernameData = json_decode($UsernameData['body'], true);
                if (!empty($UsernameData['detail'])) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $UsernameData['detail']
                    );
                }
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $UsernameData['subscription_url'])) {
                    $UsernameData['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($UsernameData['subscription_url'], "/");
                }
                if ($new_marzban) {
                    $UsernameData['expire'] = strtotime($UsernameData['expire']);
                    $UsernameData['links'] = base64_decode(outputlunk($UsernameData['subscription_url']));
                    $UsernameData['links'] = explode("\n", $UsernameData['links']);
                    $sublist_update = get_list_update($name_panel, $username);
                    if (!empty($sublist_update['error'])) {
                        return array(
                            'status' => 'Unsuccessful',
                            'msg' => $sublist_update['error']
                        );
                    } elseif (!empty($sublist_update['status']) && $sublist_update['status'] == 500) {
                        return array(
                            'status' => 'Unsuccessful',
                            'msg' => $sublist_update['status']
                        );
                    }
                    $sublist_update = json_decode($sublist_update['body'], true)['updates'][0];
                    $UsernameData['sub_updated_at'] = $sublist_update['created_at'];
                    $UsernameData['sub_last_user_agent'] = $sublist_update['user_agent'];
                } else {
                    $UsernameData['expire'] = $UsernameData['expire'];
                }
                if ($inoice != false) {
                    $UsernameData['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
                if ($new_marzban) {
                    $UsernameData['proxies'] = $UsernameData['proxy_settings'];
                }
                $Output = array(
                    'status' => $UsernameData['status'],
                    'username' => $UsernameData['username'],
                    'data_limit' => $UsernameData['data_limit'],
                    'expire' => $UsernameData['expire'],
                    'online_at' => $UsernameData['online_at'],
                    'used_traffic' => $UsernameData['used_traffic'],
                    'links' => $UsernameData['links'],
                    'subscription_url' => $UsernameData['subscription_url'],
                    'sub_updated_at' => $UsernameData['sub_updated_at'],
                    'sub_last_user_agent' => $UsernameData['sub_last_user_agent'],
                    'uuid' => $UsernameData['proxies'],
                    'data_limit_reset' => $UsernameData['data_limit_reset_strategy']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $UsernameData = getuserm($username, $Get_Data_Panel['name_panel']);
            if (!empty($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            } elseif (!empty($UsernameData['status']) && $UsernameData['status'] == 500) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } else {
                $UsernameData = json_decode($UsernameData['body'], true);
                if (isset($UsernameData['detail']) && $UsernameData['detail']) {
                    $Output = array(
                        'status' => 'Unsuccessful',
                        'msg' => $UsernameData['detail']
                    );
                } elseif (!isset($UsernameData['username'])) {
                    $Output = array(
                        'status' => 'Unsuccessful',
                        'msg' => "Unsuccessful"
                    );
                } else {
                    if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $UsernameData['subscription_url'])) {
                        $UsernameData['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($UsernameData['subscription_url'], "/");
                    }
                    $UsernameData['status'] = "active";
                    if (!$UsernameData['enabled']) {
                        $UsernameData['status'] = "disabled";
                    }
                    if ($UsernameData['expire_strategy'] == "start_on_first_use") {
                        $UsernameData['status'] = "on_hold";
                    }
                    if ($UsernameData['expired']) {
                        $UsernameData['status'] = "expired";
                    }
                    if (($UsernameData['data_limit'] - $UsernameData['used_traffic'] <= 0) and $UsernameData['data_limit'] != null) {
                        $UsernameData['status'] = "limtied";
                    }
                    $UsernameData['links'] = outputlunk($UsernameData['subscription_url']);
                    if (isBase64($UsernameData['links'])) {
                        $UsernameData['links'] = base64_decode($UsernameData['links']);
                    }
                    $links_user = explode("\n", trim($UsernameData['links']));
                    if ($UsernameData['data_limit'] == null) {
                        $UsernameData['data_limit'] = 0;
                    }
                    if (isset($UsernameData['expire_date'])) {
                        $expiretime = strtotime(($UsernameData['expire_date']));
                    } else {
                        $expiretime = 0;
                    }
                    if ($inoice != false) {
                        $UsernameData['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                    }
                    $Output = array(
                        'status' => $UsernameData['status'],
                        'username' => $UsernameData['username'],
                        'data_limit' => $UsernameData['data_limit'],
                        'expire' => $expiretime,
                        'online_at' => $UsernameData['online_at'],
                        'used_traffic' => $UsernameData['used_traffic'],
                        'links' => $links_user,
                        'subscription_url' => $UsernameData['subscription_url'],
                        'sub_updated_at' => $UsernameData['sub_updated_at'],
                        'sub_last_user_agent' => $UsernameData['sub_last_user_agent'],
                        'uuid' => null
                    );
                }
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $user_data = get_clinets($username, $Get_Data_Panel['name_panel']);
            if (!empty($user_data['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $user_data['error']
                );
            } elseif (!empty($user_data['status']) && $user_data['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => json_encode($user_data)
                );
            }
            $user_data = json_decode($user_data['body'], true);

            if (!is_array($user_data)) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => 'object invalid'
                );
            }
            if (empty($user_data['obj'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => "User not found"
                );
            }
            $user_data = $user_data['obj'];
            $expire = $user_data['expiryTime'] / 1000;
            if ($user_data['enable']) {
                $user_data['enable'] = "active";
            } else {
                $user_data['enable'] = "disabled";
            }
            if ((intval($user_data['total'])) != 0) {
                if ((intval($user_data['total']) - ($user_data['up'] + $user_data['down'])) <= 0)
                    $user_data['enable'] = "limited";
            }
            if (intval($user_data['expiryTime']) != 0) {
                if ($expire - time() <= 0)
                    $user_data['enable'] = "expired";
            }
            if ($user_data['expiryTime'] < -10000) {
                $user_data['enable'] = "on_hold";
                $expire = 0;
            }
            $linksub = $Get_Data_Panel['linksubx'] . "/{$user_data['subId']}";
            $links_user = outputlunk($Get_Data_Panel['linksubx'] . "/{$user_data['subId']}");
            if (isBase64($links_user))
                $links_user = base64_decode($links_user);
            $links_user = explode("\n", trim($links_user));
            if ($inoice != false)
                $linksub = "https://$domainhosts/sub/" . $inoice['id_invoice'];
            $user_data['lastOnline'] = $user_data['lastOnline'] == 0 ? "offline" : date('Y-m-d H:i:s', $user_data['lastOnline'] / 1000);
            $Output = array(
                'status' => $user_data['enable'],
                'username' => $user_data['email'],
                'data_limit' => $user_data['total'],
                'expire' => $expire,
                'online_at' => $user_data['lastOnline'],
                'used_traffic' => $user_data['up'] + $user_data['down'],
                'links' => $links_user,
                'subscription_url' => $linksub,
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
            );

        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            $UsernameData = getdatauser($username, $Get_Data_Panel['name_panel']);
            if (!isset($UsernameData)) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => "Not Connected TO paonel"
                );
            } elseif (isset($UsernameData['message'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['message']
                );
            } else {
                if ($UsernameData['start_date'] == null) {
                    $date = 0;
                } else {
                    $current_date = time();
                    $start_date = strtotime($UsernameData['start_date']);
                    $end_date = $start_date + ($UsernameData['package_days'] * 86400);
                    $date = strtotime(date("Y-m-d H:i:s", $end_date));
                }
                $UsernameData['usage_limit_GB'] = $UsernameData['usage_limit_GB'] * pow(1024, 3);
                $UsernameData['current_usage_GB'] = $UsernameData['current_usage_GB'] * pow(1024, 3);
                $linksuburl = "{$Get_Data_Panel['linksubx']}/{$UsernameData['uuid']}/";
                $linksubconfig = $linksuburl . "sub";
                if ($UsernameData['last_online'] == "1-01-01 00:00:00") {
                    $UsernameData['last_online'] = null;
                }
                if ($UsernameData['usage_limit_GB'] - $UsernameData['current_usage_GB'] <= 0) {
                    $status = "limited";
                } elseif ($date - time() <= 0 and $date != 0) {
                    $status = "expired";
                } elseif ($UsernameData['start_date'] == null) {
                    $status = "on_hold";
                } else {
                    $status = "active";
                }
                if ($inoice != false) {
                    $linksuburl = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }
                $Output = array(
                    'status' => $status,
                    'username' => $UsernameData['name'],
                    'data_limit' => $UsernameData['usage_limit_GB'],
                    'expire' => $date,
                    'online_at' => $UsernameData['last_online'],
                    'used_traffic' => $UsernameData['current_usage_GB'],
                    'links' => [],
                    'subscription_url' => $linksuburl,
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            $stmt = $pdo->prepare("SELECT * FROM manualsell WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $configman = $stmt->fetch(PDO::FETCH_ASSOC);
            $service = select("invoice", "*", "username", $username, "select");
            $Output = array(
                'status' => $service['Status'],
                'username' => $service['username'],
                'data_limit' => null,
                'expire' => $service['time_sell'],
                'online_at' => null,
                'used_traffic' => null,
                'links' => [],
                'subscription_url' => $configman['contentrecord'],
                'sub_updated_at' => null,
                'sub_last_user_agent' => null,
                'uuid' => null
            );
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            $UsernameData2 = get_clinetsalireza($username, $Get_Data_Panel['name_panel']);
            if (!is_array($UsernameData2)) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => "user not found"
                );
            }
            $UsernameData = $UsernameData2[1];
            $UsernameData2 = $UsernameData2[0];
            $expire = $UsernameData['expiryTime'] / 1000;
            if (!$UsernameData['id']) {
                if (!isset($UsernameData['msg']))
                    $UsernameData['msg'] = null;
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                if ($UsernameData['enable']) {
                    $UsernameData['enable'] = "active";
                } else {
                    $UsernameData['enable'] = "deactivev";
                }
                $subId = $UsernameData2['subId'];
                $status_user = get_onlineclialireza($Get_Data_Panel['name_panel'], $username);
                if ((intval($UsernameData['total'])) != 0) {
                    if ((intval($UsernameData['total']) - ($UsernameData['up'] + $UsernameData['down'])) <= 0)
                        $UsernameData['enable'] = "limited";
                }
                if (intval($UsernameData['expiryTime']) != 0) {
                    if ($expire - time() <= 0)
                        $UsernameData['enable'] = "expired";
                }
                $Output = array(
                    'status' => $UsernameData['enable'],
                    'username' => $UsernameData['email'],
                    'data_limit' => $UsernameData['total'],
                    'expire' => $expire,
                    'online_at' => $status_user,
                    'used_traffic' => $UsernameData['up'] + $UsernameData['down'],
                    'links' => [outputlunk($Get_Data_Panel['linksubx'] . "/{$UsernameData2['subId']}")],
                    'subscription_url' => $Get_Data_Panel['linksubx'] . "/{$UsernameData2['subId']}",
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "WGDashboard") {
            $UsernameData = get_userwg($username, $Get_Data_Panel['name_panel']);
            $invoiceinfo = select("invoice", "*", "username", $username, "select");
            $infoconfig = isset($invoiceinfo['user_info']) ? json_decode($invoiceinfo['user_info'], true) : json_encode(array());
            if (!isset($UsernameData['id'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => isset($UsernameData['msg']) ? $UsernameData['msg'] : ''
                );
            } else {
                $jobtime = [];
                $jobvolume = [];
                foreach ($UsernameData['jobs'] as $job) {
                    if ($job['Field'] == "total_data") {
                        $jobvolume = $job;
                    } elseif ($job['Field'] == "date") {
                        $jobtime = $job;
                    }
                }
                if (intval($invoiceinfo['Service_time']) == 0) {
                    $expire = 0;
                } else {
                    if (isset($jobtime['Value'])) {
                        $expire = strtotime($jobtime['Value']);
                    } else {
                        $expire = 0;
                    }
                }
                $status = "active";
                if (!$UsernameData['configuration']['Status'])
                    $status = "disabled";
                if ($expire != 0 and $expire - time() < 0) {
                    $status = "expired";
                }
                $data_useage = ($UsernameData['total_data'] * pow(1024, 3)) + ($UsernameData['cumu_data'] * pow(1024, 3));
                if (($jobvolume['Value'] * pow(1024, 3)) < $data_useage) {
                    $status = "limited";
                }
                $download_config = downloadconfig($Get_Data_Panel['name_panel'], $UsernameData['id']);
                if (!empty($download_config['status']) && $download_config['status'] != 200) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $download_config['status']
                    );
                }
                if (!empty($download_config['error'])) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $download_config['error']
                    );
                }
                $download_config = json_decode($download_config['body'], true)['data'];
                $Output = array(
                    'status' => $status,
                    'username' => $UsernameData['name'],
                    'data_limit' => $jobvolume['Value'] * pow(1024, 3),
                    'expire' => $expire,
                    'online_at' => null,
                    'used_traffic' => $data_useage,
                    'links' => [],
                    'subscription_url' => strval($download_config['file']),
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            $UsernameData = GetClientsS_UI($username, $Get_Data_Panel['name_panel']);
            $onlinestatus = get_onlineclients_ui($Get_Data_Panel['name_panel'], $username);
            if (!isset($UsernameData['id'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $links = [];
                if (is_array($UsernameData['links'])) {
                    foreach ($UsernameData['links'] as $config) {
                        $links[] = $config['uri'];
                    }
                }
                $data_limit = $UsernameData['volume'];
                $useage = $UsernameData['up'] + $UsernameData['down'];
                $RemainingVolume = $data_limit - $useage;
                $expire = $UsernameData['expiry'];
                if ($UsernameData['enable']) {
                    $UsernameData['enable'] = "active";
                } elseif ($data_limit != 0 and $RemainingVolume < 0) {
                    $UsernameData['enable'] = "limited";
                } elseif ($expire - time() < 0 and $expire != 0) {
                    $UsernameData['enable'] = "expired";
                } else {
                    $UsernameData['enable'] = "disabled";
                }
                $setting_app = get_settig($Get_Data_Panel['name_panel']);
                $url = explode(":", $Get_Data_Panel['url_panel']);
                $url_sub = $url[0] . ":" . $url[1] . ":" . $setting_app['subPort'] . $setting_app['subPath'] . $username;
                $Output = array(
                    'status' => $UsernameData['enable'],
                    'username' => $UsernameData['name'],
                    'data_limit' => $data_limit,
                    'expire' => $expire,
                    'online_at' => $onlinestatus,
                    'used_traffic' => $useage,
                    'links' => $links,
                    'subscription_url' => $url_sub,
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "ibsng") {
            $UsernameData = GetUserIBsng($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $UsernameData = $UsernameData['data'];
                $data_limit = $UsernameData['data_limit'];
                $expire = strtotime($UsernameData['absolute_expire_date']);
                $UsernameData['enable'] = "active";
                $Output = array(
                    'status' => $UsernameData['enable'],
                    'username' => $UsernameData['username'],
                    'data_limit' => $data_limit,
                    'expire' => $expire,
                    'online_at' => strtolower($UsernameData['status']),
                    'used_traffic' => $UsernameData['used_traffic'],
                    'links' => [],
                    'subscription_url' => $UsernameData['password'],
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "mikrotik") {
            $UsernameData = GetUsermikrotik($Get_Data_Panel['name_panel'], $username)[0];
            if (isset($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $invocie = select("invoice", "*", "username", $username, "select");
                $traffic_get = GetUsermikrotik_volume($Get_Data_Panel['name_panel'], $UsernameData['.id']);
                $used_traffic = $traffic_get['total-upload'] + $traffic_get['total-download'];
                $data_limit = $invocie['Volume'] * pow(1024, 3);
                $expire = $invocie['time_sell'] + ($invocie['Service_time'] * 86400);
                $UsernameData['enable'] = "active";
                $Output = array(
                    'status' => $UsernameData['enable'],
                    'username' => $invocie['username'],
                    'data_limit' => $data_limit,
                    'expire' => $expire,
                    'online_at' => null,
                    'used_traffic' => $used_traffic,
                    'links' => [],
                    'subscription_url' => $UsernameData['password'],
                    'sub_updated_at' => null,
                    'sub_last_user_agent' => null,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "pasarguard") {
            $UsernameData = pg_get_user($username, $Get_Data_Panel['name_panel']);
            if (!empty($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            } elseif (!empty($UsernameData['status']) && $UsernameData['status'] == 500) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } else {
                $UsernameData = json_decode($UsernameData['body'], true);
                if (!empty($UsernameData['detail'])) {
                    return array(
                        'status' => 'Unsuccessful',
                        'msg' => $UsernameData['detail']
                    );
                }

                // Handle subscription_url formatting
                if (isset($UsernameData['subscription_url']) && !empty($UsernameData['subscription_url'])) {
                    if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $UsernameData['subscription_url'])) {
                        $UsernameData['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($UsernameData['subscription_url'], "/");
                    }
                } else {
                    // Generate subscription URL if not in response
                    $UsernameData['subscription_url'] = rtrim($Get_Data_Panel['url_panel'], '/') . '/sub/' . $username;
                }

                // Handle expire - Pasarguard returns ISO 8601 string or 0
                $expire_timestamp = 0;
                if (isset($UsernameData['expire'])) {
                    if ($UsernameData['expire'] == 0 || $UsernameData['expire'] == "0") {
                        $expire_timestamp = 0;
                    } elseif (is_string($UsernameData['expire'])) {
                        $expire_timestamp = strtotime($UsernameData['expire']);
                    } else {
                        $expire_timestamp = $UsernameData['expire'];
                    }
                }

                // Extract links from subscription_url if needed
                $links = array();
                $sub_updated_at = null;
                $sub_last_user_agent = null;

                if ($new_marzban && isset($UsernameData['subscription_url'])) {
                    $links_raw = outputlunk($UsernameData['subscription_url']);
                    if (isBase64($links_raw)) {
                        $links_raw = base64_decode($links_raw);
                    }
                    $links = explode("\n", trim($links_raw));

                    // Try to get sub_update info (if available)
                    $sublist_update = get_list_update($name_panel, $username);
                    if (!empty($sublist_update['error'])) {
                        // Ignore error, continue without sub_update info
                    } elseif (!empty($sublist_update['status']) && $sublist_update['status'] == 500) {
                        // Ignore error, continue without sub_update info
                    } else {
                        $sublist_update_data = json_decode($sublist_update['body'] ?? '{}', true);
                        if (isset($sublist_update_data['updates'][0])) {
                            $sub_updated_at = $sublist_update_data['updates'][0]['created_at'] ?? null;
                            $sub_last_user_agent = $sublist_update_data['updates'][0]['user_agent'] ?? null;
                        }
                    }
                }

                if ($inoice != false) {
                    $UsernameData['subscription_url'] = "https://$domainhosts/sub/" . $inoice['id_invoice'];
                }

                // Pasarguard uses proxy_settings (not proxies), map for consistency
                $proxies_value = null;
                if (isset($UsernameData['proxy_settings'])) {
                    $proxies_value = $UsernameData['proxy_settings'];
                } elseif (isset($UsernameData['proxies'])) {
                    $proxies_value = $UsernameData['proxies'];
                }

                $Output = array(
                    'status' => $UsernameData['status'] ?? 'active',
                    'username' => $UsernameData['username'],
                    'data_limit' => $UsernameData['data_limit'] ?? 0,
                    'expire' => $expire_timestamp,
                    'online_at' => $UsernameData['online_at'] ?? null,
                    'used_traffic' => $UsernameData['used_traffic'] ?? 0,
                    'links' => !empty($links) ? $links : array(),
                    'subscription_url' => $UsernameData['subscription_url'],
                    'sub_updated_at' => $sub_updated_at,
                    'sub_last_user_agent' => $sub_last_user_agent,
                    'uuid' => $proxies_value,
                    'data_limit_reset' => $UsernameData['data_limit_reset_strategy'] ?? 'no_reset'
                );
            }
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function Revoke_sub($name_panel, $username)
    {
        $Output = array();
        $ManagePanel = new ManagePanel();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $revoke_sub = revoke_sub($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $Data_User['subscription_url'])) {
                    $Data_User['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($Data_User['subscription_url'], "/");
                }
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
            }
        } else if ($Get_Data_Panel['type'] == "marzneshin") {
            $revoke_sub = revoke_subm($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                $Data_User['links'] = [base64_decode(outputlunk($Data_User['subscription_url']))];
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $subId = bin2hex(random_bytes(8));
            $config = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => generateUUID(),
                                "enable" => true,
                                "subId" => $subId,
                            )
                        ),
                    )
                )
            );
            $updateinbound = $ManagePanel->Modifyuser($username, $Get_Data_Panel['name_panel'], $config);
            if (!$updateinbound['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'configs' => [outputlunk($Get_Data_Panel['linksubx'] . "/{$subId}")],
                    'subscription_url' => $Get_Data_Panel['linksubx'] . "/{$subId}",
                );
            }
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            $subId = bin2hex(random_bytes(8));
            $config = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => generateUUID(),
                                "enable" => true,
                                "subId" => $subId,
                            )
                        ),
                    )
                )
            );
            $updateinbound = $ManagePanel->Modifyuser($username, $Get_Data_Panel['name_panel'], $config);
            if (!$updateinbound['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'configs' => [outputlunk($Get_Data_Panel['linksubx'] . "/{$subId}")],
                    'subscription_url' => $Get_Data_Panel['linksubx'] . "/{$subId}",
                );
            }
        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'panel not supported'
            );
        } elseif ($Get_Data_Panel['type'] == "pasarguard") {
            $revoke_sub = pg_revoke_sub($username, $name_panel);
            if (isset($revoke_sub['detail']) && $revoke_sub['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $revoke_sub['detail']
                );
            } else {
                $config = new ManagePanel();
                $Data_User = $config->DataUser($name_panel, $username);
                if (!preg_match('/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(:\d+)?((\/[^\s\/]+)+)?$/', $Data_User['subscription_url'])) {
                    $Data_User['subscription_url'] = $Get_Data_Panel['url_panel'] . "/" . ltrim($Data_User['subscription_url'], "/");
                }
                $Output = array(
                    'status' => 'successful',
                    'configs' => $Data_User['links'],
                    'subscription_url' => $Data_User['subscription_url']
                );
            }
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            $clients = GetClientsS_UI($username, $name_panel);
            $password = bin2hex(random_bytes(16));
            $usernameac = $username;
            $configpanel = array(
                "object" => 'clients',
                'action' => "edit",
                "data" => json_encode(array(
                    "id" => $clients['id'],
                    "enable" => $clients['enable'],
                    "name" => $usernameac,
                    "config" => array(
                        "mixed" => array(
                            "username" => $usernameac,
                            "password" => generateAuthStr()
                        ),
                        "socks" => array(
                            "username" => $usernameac,
                            "password" => generateAuthStr()
                        ),
                        "http" => array(
                            "username" => $usernameac,
                            "password" => generateAuthStr()
                        ),
                        "shadowsocks" => array(
                            "name" => $usernameac,
                            "password" => $password
                        ),
                        "shadowsocks16" => array(
                            "name" => $usernameac,
                            "password" => $password
                        ),
                        "shadowtls" => array(
                            "name" => $usernameac,
                            "password" => $password
                        ),
                        "vmess" => array(
                            "name" => $usernameac,
                            "uuid" => generateUUID(),
                            "alterId" => 0
                        ),
                        "vless" => array(
                            "name" => $usernameac,
                            "uuid" => generateUUID(),
                            "flow" => ""
                        ),
                        "trojan" => array(
                            "name" => $usernameac,
                            "password" => generateAuthStr()
                        ),
                        "naive" => array(
                            "username" => $usernameac,
                            "password" => generateAuthStr()
                        ),
                        "hysteria" => array(
                            "name" => $usernameac,
                            "auth_str" => generateAuthStr()
                        ),
                        "tuic" => array(
                            "name" => $usernameac,
                            "uuid" => generateUUID(),
                            "password" => generateAuthStr()
                        ),
                        "hysteria2" => array(
                            "name" => $usernameac,
                            "password" => generateAuthStr()
                        )
                    ),
                    "inbounds" => $clients['inbounds'],
                    "links" => [],
                    "volume" => $clients['volume'],
                    "expiry" => $clients['expiry'],
                    "desc" => $clients['desc']
                )),
            );
            $result = updateClientS_ui($Get_Data_Panel['name_panel'], $configpanel);
            if (!$result['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => 'Unsuccessful'
                );
            } else {
                $setting_app = get_settig($Get_Data_Panel['name_panel']);
                $url = explode(":", $Get_Data_Panel['url_panel']);
                $url_sub = $url[0] . ":" . $url[1] . ":" . $setting_app['subPort'] . $setting_app['subPath'] . $username;
                $Output = array(
                    'status' => 'successful',
                    'configs' => [outputlunk($url_sub)],
                    'subscription_url' => $url_sub,
                );
            }
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function RemoveUser($name_panel, $username)
    {
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            $UsernameData = removeuser($Get_Data_Panel['name_panel'], $username);
            if (!empty($UsernameData['status']) && $UsernameData['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } elseif (!empty($UsernameData['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            }
            $UsernameData = json_decode($UsernameData['body'], true);
            if ($UsernameData['detail'] != "User successfully deleted") {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $UsernameData = removeuserm($Get_Data_Panel['name_panel'], $username);
            if (isset($UsernameData['detail']) && $UsernameData['detail']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $UsernameData = removeClient($Get_Data_Panel['name_panel'], $username);
            if (!empty($UsernameData['status']) && $UsernameData['status'] != 200) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } elseif (!empty($UsernameData['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            }
            $UsernameData = json_decode($UsernameData['body'], true);
            if (!$UsernameData['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            $UsernameData = removeClientalireza_single($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            $data_user = getdatauser($username, $name_panel);
            removeuserhi($name_panel, $data_user['uuid']);
            $Output = array(
                'status' => 'successful',
                'msg' => ""
            );
        } elseif ($Get_Data_Panel['type'] == "Manualsale") {
            update("manualsell", "status", "delete", "username", $username);
            $Output = array(
                'status' => 'successful',
                'username' => $username,
            );
        } elseif ($Get_Data_Panel['type'] == "WGDashboard") {
            $UsernameData = remove_userwg($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            $UsernameData = removeClientS_ui($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['success']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "ibsng") {
            $UsernameData = deleteUserIBSng($Get_Data_Panel['name_panel'], $username);
            if (!$UsernameData['status']) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "mikrotik") {
            $UsernameData = GetUsermikrotik($Get_Data_Panel['name_panel'], $username)[0];
            if (isset($UsernameData['error'])) {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['msg']
                );
            } else {
                deleteUser_mikrotik($Get_Data_Panel['name_panel'], $UsernameData['.id']);
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } elseif ($Get_Data_Panel['type'] == "pasarguard") {
            $UsernameData = pg_remove_user($Get_Data_Panel['name_panel'], $username);
            if (!empty($UsernameData['status']) && $UsernameData['status'] != 200 && $UsernameData['status'] != 204) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['status']
                );
            } elseif (!empty($UsernameData['error'])) {
                return array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['error']
                );
            }
            $UsernameData = json_decode($UsernameData['body'] ?? '{}', true);
            if (isset($UsernameData['detail']) && $UsernameData['detail'] != "User successfully deleted") {
                $Output = array(
                    'status' => 'Unsuccessful',
                    'msg' => $UsernameData['detail'] ?? 'Unsuccessful'
                );
            } else {
                $Output = array(
                    'status' => 'successful',
                    'username' => $username,
                );
            }
        } else {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => 'Panel Not Found'
            );
        }
        return $Output;
    }
    function Modifyuser($username, $name_panel, $config = array())
    {
        global $new_marzban;
        $Output = array();
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($Get_Data_Panel['type'] == "marzban") {
            if ($new_marzban) {
                $result = getuser($username, $name_panel);
                $result = json_decode($result['body'], true);
                $config['proxy_settings'] = $result['proxy_settings'];
            }
            $modify = Modifyuser($name_panel, $username, $config);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] == 500) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modifycheck = json_decode($modify['body'], true);
            if (!empty($modifycheck['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $modifycheck['detail']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            $config['username'] = $username;
            $modify = Modifyuserm($name_panel, $username, $config);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] == 500) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modifycheck = json_decode($modify['body'], true);
            if (!empty($modifycheck['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $modifycheck['detail']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            $clients = get_clinets($username, $name_panel);
            if (!empty($clients['error'])) {
                return array(
                    'status' => false,
                    'msg' => $clients['error']
                );
            } elseif (!empty($clients['status']) && $clients['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => json_encode($clients)
                );
            }
            $clients = json_decode($clients['body'], true);
            if (!is_array($clients)) {
                return array(
                    'status' => false,
                    'msg' => 'object invalid'
                );
            }
            if (empty($clients['obj'])) {
                return array(
                    'status' => false,
                    'msg' => "User not found"
                );
            }
            $clients = $clients['obj'];
            $configs = array(
                'id' => intval($clients['inboundId']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => $clients['uuid'],
                                "flow" => "",
                                "email" => $clients['email'],
                                "totalGB" => $clients['total'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $clients['subId'],
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
            $configs['settings'] = json_encode(array_replace_recursive(json_decode($configs['settings'], true), json_decode($config['settings'], true)));
            $modify = updateClient($Get_Data_Panel['name_panel'], $clients['uuid'], $configs);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modify = json_decode($modify['body'], true);
            if (!$modify['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $modify['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            $clients = get_clinetsalireza($username, $name_panel)[0];
            $configs = array(
                'id' => intval($Get_Data_Panel['inboundid']),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "id" => $clients['id'],
                                "flow" => $clients['flow'],
                                "email" => $clients['email'],
                                "totalGB" => $clients['totalGB'],
                                "expiryTime" => $clients['expiryTime'],
                                "enable" => true,
                                "subId" => $clients['subId'],
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
            $configs['settings'] = json_encode(array_replace_recursive(json_decode($configs['settings'], true), json_decode($config['settings'], true)));
            $modify = updateClientalireza($Get_Data_Panel['name_panel'], $username, $configs);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modify = json_decode($modify['body'], true);
            if (!$modify['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $modify['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            $modify = updateuserhi($username, $name_panel, $config);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modify = json_decode($modify['body'], true);
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "WGDashboard") {
            $data_user = get_userwg($username, $name_panel);
            $configs = array(
                "DNS" => $data_user['DNS'],
                "allowed_ip" => $data_user['allowed_ip'],
                "endpoint_allowed_ip" => "0.0.0.0/0",
                "jobs" => $data_user['jobs'],
                "id" => $data_user['id'],
                "keepalive" => $data_user['keepalive'],
                "mtu" => $data_user['mtu'],
                "name" => $data_user['name'],
                "preshared_key" => $data_user['preshared_key'],
                "private_key" => $data_user['private_key']
            );
            $configs = array_merge($configs, $config);
            $modify = updatepear($Get_Data_Panel['name_panel'], $configs);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modify = json_decode($modify['body'], true);
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            $clients = GetClientsS_UI($username, $name_panel);
            if (!$clients)
                return [];
            $usernameac = $username;
            $configs = array(
                "object" => 'clients',
                'action' => "edit",
                "data" => array(
                    "id" => $clients['id'],
                    "enable" => $clients['enable'],
                    "name" => $usernameac,
                    "config" => $clients['config'],
                    "inbounds" => $clients['inbounds'],
                    "links" => $clients['links'],
                    "volume" => $clients['volume'],
                    "expiry" => $clients['expiry'],
                    "desc" => $clients['desc']
                ),
            );
            $configs['data'] = array_merge($configs['data'], $config);
            $configs['data'] = json_encode($configs['data'], true);
            $modify = updateClientS_ui($Get_Data_Panel['name_panel'], $configs);
            return array(
                'status' => true,
                'data' => $modify
            );
        } elseif ($Get_Data_Panel['type'] == "pasarguard") {
            $modify = pg_modify_user($name_panel, $username, $config);
            if (!empty($modify['error'])) {
                return array(
                    'status' => false,
                    'msg' => $modify['error']
                );
            } elseif (!empty($modify['status']) && $modify['status'] == 500) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $modify['status']
                );
            }
            $modifycheck = json_decode($modify['body'] ?? '{}', true);
            if (!empty($modifycheck['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $modifycheck['detail']
                );
            }
            return array(
                'status' => true,
                'data' => $modify
            );
        }
    }
    function Change_status($username, $name_panel)
    {
        $ManagePanel = new ManagePanel();
        $DataUserOut = $ManagePanel->DataUser($name_panel, $username);
        $Get_Data_Panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($DataUserOut['status'] == "Unsuccessful") {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => $DataUserOut['detail']
            );
            return;
        }
        if (!in_array($DataUserOut['status'], ["active", "disabled"])) {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => "status invalid"
            );
            return;
        }
        if ($Get_Data_Panel['type'] == "marzban") {
            if ($DataUserOut['status'] == "active") {
                $status = "disabled";
            } else {
                $status = "active";
            }
            $configs = array("status" => $status);
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "marzneshin") {
            if ($DataUserOut['status'] == "active") {
                disableduser($name_panel, $username);
            } else {
                enableuser($name_panel, $username);
            }
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "x-ui_single") {
            if ($DataUserOut['status'] == "active") {
                $status = false;
            } else {
                $status = true;
            }
            $configs = array(
                'settings' => json_encode(array(
                    'clients' => array(
                        array(
                            "enable" => $status,
                        )
                    ),
                )),
            );
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "alireza_single") {
            if ($DataUserOut['status'] == "active") {
                $status = false;
            } else {
                $status = true;
            }
            $configs = array(
                'settings' => json_encode(array(
                    'clients' => array(
                        array(
                            "enable" => $status,
                        )
                    ),
                )),
            );
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "hiddify") {
            $Output = array(
                'status' => 'Unsuccessful',
                'msg' => null
            );
        } elseif ($Get_Data_Panel['type'] == "s_ui") {
            if ($DataUserOut['status'] == "active") {
                $status = false;
            } else {
                $status = true;
            }
            $configs = array("enable" => $status);
            $ManagePanel->Modifyuser($username, $name_panel, $configs);
            $Output = array(
                'status' => 'successful',
                'msg' => null
            );
        }

        return $Output;
    }
    function ResetUserDataUsage($username, $name_panel)
    {
        $panel = select("marzban_panel", "*", "name_panel", $name_panel, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        if ($panel['type'] == "marzban") {
            $reset = ResetUserDataUsage($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!empty($reset['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $reset['detail']
                );
            }
            return array(
                'status' => true,
                'msg' => 'successful'
            );
        } elseif ($panel['type'] == "marzneshin") {
            $reset = ResetUserDataUsagem($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!empty($reset['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $reset['detail']
                );
            }
            return array(
                'status' => true,
                'msg' => 'successful'
            );
        } elseif ($panel['type'] == 'x-ui_single') {
            $reset = ResetUserDataUsagex_uisin($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!$reset['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $reset['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $reset
            );
        } elseif ($panel['type'] == 'alireza_single') {
            $reset = ResetUserDataUsagealirezasin($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            if (!$reset['success']) {
                return array(
                    'status' => false,
                    'msg' => 'error :' . $reset['msg']
                );
            }
            return array(
                'status' => true,
                'data' => $reset
            );
        } elseif ($panel['type'] == "WGDashboard") {
            allowAccessPeers($panel['name_panel'], $username);
            $datauser = get_userwg($username, $panel['name_panel']);
            $reset = ResetUserDataUsagewg($datauser['id'], $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'], true);
            return array(
                'status' => true,
                'data' => $reset
            );
        } elseif ($panel['type'] == "hiddify") {
            return array(
                'status' => true
            );
        } elseif ($panel['type'] == "s_ui") {
            ResetUserDataUsages_ui($username, $name_panel);
            return array(
                'status' => true
            );
        } elseif ($panel['type'] == "pasarguard") {
            $reset = pg_reset_usage($username, $panel['name_panel']);
            if (!empty($reset['status']) && $reset['status'] != 200) {
                return array(
                    'status' => false,
                    'msg' => 'error code : ' . $reset['status']
                );
            } elseif (!empty($reset['error'])) {
                return array(
                    'status' => false,
                    'msg' => 'error  : ' . $reset['error']
                );
            }
            $reset = json_decode($reset['body'] ?? '{}', true);
            if (!empty($reset['detail'])) {
                return array(
                    'status' => false,
                    'msg' => $reset['detail']
                );
            }
            return array(
                'status' => true,
                'msg' => 'successful'
            );
        }
    }
    function extend($Method_extend, $new_limit, $time_day, $username, $code_product, $name_panel)
    {
        $panel = select("marzban_panel", "*", "code_panel", $name_panel, "select");
        $product = select("product", "*", "code_product", $code_product, "select");
        $invoice = select("invoice", "*", "username", $username, "select");
        if ($code_product == "custom_volume")
            $product = true;
        if ($panel == false || $product == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $data_user = $this->DataUser($panel['name_panel'], $username);
        if ($data_user['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $data_user['msg']
            );
        }
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $data_limit_old = $data_user['data_limit'];
        $time_old = $data_user['expire'];
        $time_old = time() - $time_old > 0 ? time() : $time_old;
        $data_limit_new = $new_limit == 0 ? 0 : $new_limit * pow(1024, 3);
        $data_limit_new_add = $new_limit == 0 ? 0 : $data_limit_old + ($new_limit * pow(1024, 3));
        $time_new = $time_day == 0 ? 0 : time() + $time_day * 86400;
        $time_old = $time_old == 0 ? time() : $time_old;
        $time_new_add = $time_day == 0 ? 0 : $time_old + ($time_day * 86400);
        //inboud and proxies
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        $inbounds = $product['inbounds'] != null ? json_decode($product['inbounds']) : $inbounds;
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username);
        }
        update("invoice", 'uuid', null, "username", $username);
        update("invoice", 'Status', "active", "username", $username);
        if ($Method_extend == "ریست حجم و زمان") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
        } elseif ($Method_extend == "اضافه شدن زمان و حجم به ماه بعد") {
            $data_limit_new = $data_limit_new_add;
            $time_new = $time_new_add;
        } elseif ($Method_extend == "ریست زمان و اضافه کردن حجم قبلی") {
            $data_limit_new = $data_limit_new_add;
        } elseif ($Method_extend == "ریست شدن حجم و اضافه شدن زمان") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
        } elseif ($Method_extend == "اضافه شدن زمان و تبدیل حجم کل به حجم باقی مانده") {
            $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
            if ($reset['status'] == false) {
                return array(
                    'status' => false,
                    'msg' => 'error reset : ' . $reset['msg']
                );
            }
            $time_new = $time_new_add;
            $data_limit_last = $data_user['data_limit'] - $data_user['used_traffic'];
            $data_limit_last = $data_limit_last < 0 ? 0 : $data_limit_last;
            $data_limit_new = $data_limit_new + $data_limit_last;
        }
        if ($panel['type'] == "marzban") {
            $data = array(
                'data_limit' => $data_limit_new,
                'expire' => $time_new,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "marzneshin") {
            $expire_strotegy = $time_new == 0 ? "never" : "fixed_date";
            $time_new = date('c', $time_new);
            $data = array(
                'username' => $username,
                'expire_date' => $time_new,
                'expire_strategy' => $expire_strotegy,
                'data_limit' => $data_limit_new
            );
        } elseif ($panel['type'] == "x-ui_single") {
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $data_limit_new,
                                "expiryTime" => $time_new * 1000,
                                "enable" => true,
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
        } elseif ($panel['type'] == "alireza_single") {
            $data = array(
                'id' => intval($inbound_id),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $data_limit_new,
                                "expiryTime" => $time_new * 1000,
                                "enable" => true,
                            )
                        ),
                        'decryption' => 'none',
                        'fallbacks' => array(),
                    )
                ),
            );
        } elseif ($panel['type'] == "WGDashboard") {
            if ($data_user['status'] == "limited" || $data_user['status'] == "expired") {
                $reset = $this->ResetUserDataUsage($username, $panel['name_panel']);
                if ($reset['status'] == false) {
                    return array(
                        'status' => false,
                        'msg' => 'error reset : ' . $reset['msg']
                    );
                }
            }
            allowAccessPeers($panel['name_panel'], $username);
            $datauser = get_userwg($username, $panel['name_panel']);
            $count = 0;
            foreach ($datauser['jobs'] as $jobsvolume) {
                if ($jobsvolume['Field'] == "date") {
                    break;
                }
                $count += 1;
            }
            $datam = array(
                "Job" => $datauser['jobs'][$count],
            );
            deletejob($panel['name_panel'], $datam);
            $count = 0;
            foreach ($datauser['jobs'] as $jobsvolume) {
                if ($jobsvolume['Field'] == "total_data") {
                    break;
                }
                $count += 1;
            }
            $datam = array(
                "Job" => $datauser['jobs'][$count],
            );
            deletejob($panel['name_panel'], $datam);
            $time_new = date("Y-m-d H:i:s", $time_new);
            if ($time_day != 0) {
                setjob($panel['name_panel'], "date", $time_new, $datauser['id']);
            }
            if ($new_limit != 0) {
                setjob($panel['name_panel'], "total_data", $data_limit_new / pow(1024, 3), $datauser['id']);
            }
            return array(
                'status' => true
            );
        } elseif ($panel['type'] == "hiddify") {
            $day = $time_new - time();
            $data = array(
                "package_days" => $day / 86400,
                "usage_limit_GB" => $data_limit_new / pow(1024, 3),
                "start_date" => null
            );
            if (in_array($Method_extend, ["ریست حجم و زمان", "ریست شدن حجم و اضافه شدن زمان", "اضافه شدن زمان و تبدیل حجم کل به حجم باقی مانده"])) {
                $data['current_usage_GB'] = "0";
            }
        } elseif ($panel['type'] == "s_ui") {
            $data = array(
                "volume" => $data_limit_new,
                "expiry" => $time_new
            );
        } elseif ($panel['type'] == "pasarguard") {
            // Pasarguard uses group_ids (not inbounds) and needs proper format
            $group_ids = array();
            if (is_array($inbounds)) {
                $group_ids = array_map('intval', $inbounds);
            } elseif (!empty($inbounds)) {
                $group_ids = array_map('intval', json_decode($inbounds, true));
            }

            $data = array(
                'data_limit' => $data_limit_new,
                'expire' => $time_new,
            );

            // Only add group_ids if not empty
            if (!empty($group_ids)) {
                $data['group_ids'] = $group_ids;
            }
        }
        $extend = $this->Modifyuser($username, $panel['name_panel'], $data);
        if ($extend['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extend['msg']
            );
        }
        return $extend;
    }
    function extra_volume($username_account, $code_panel, $limit_volume_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => false,
            'time' => $notif_value['time'],
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_volume = $user_info['data_limit'];
        $new_limit = $limit_volume_new == 0 ? 0 : ($limit_volume_new * pow(1024, 3)) + $old_limit_volume;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username_account);
        }
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        if ($panel['type'] == "marzban") {
            $data = array(
                'data_limit' => $new_limit,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "marzneshin") {
            $data = array(
                'data_limit' => $new_limit,
            );
        } elseif ($panel['type'] == "x-ui_single") {
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        } elseif ($panel['type'] == "alireza_single") {
            $data = array(
                'id' => intval($inbound_id),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "totalGB" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        } elseif ($panel['type'] == "hiddify") {
            $data_limit = ($user_info['data_limit'] / pow(1024, 3)) + $limit_volume_new;
            $datauser = getdatauser($username_account, $panel['name_panel']);
            $data = array(
                "current_usage_GB" => $datauser['current_usage_GB'],
                "usage_limit_GB" => $new_limit / pow(1024, 3),
            );
        } elseif ($panel['type'] == "WGDashboard") {
            allowAccessPeers($panel['name_panel'], $username_account);
            $datauser = get_userwg($username_account, $panel['name_panel']);
            $count = 0;
            foreach ($datauser['jobs'] as $jobsvolume) {
                if ($jobsvolume['Field'] == "total_data") {
                    break;
                }
                $count += 1;
            }
            if (isset($datauser['jobs'][$count])) {
                $datam = array(
                    "Job" => $datauser['jobs'][$count],
                );
                deletejob($panel['name_panel'], $datam);
            } else {
                $this->ResetUserDataUsage($username_account, $panel['name_panel']);
            }
            $log = setjob($panel['name_panel'], "total_data", $new_limit / pow(1024, 3), $datauser['id']);
            return array(
                'status' => true,
                'data' => $log
            );
        } elseif ($panel['type'] == "s_ui") {
            $data = array(
                "volume" => $new_limit,
            );
        } elseif ($panel['type'] == "pasarguard") {
            // Pasarguard uses group_ids (not inbounds) and needs proper format
            $group_ids = array();
            if (is_array($inbounds)) {
                $group_ids = array_map('intval', $inbounds);
            } elseif (!empty($inbounds)) {
                $group_ids = array_map('intval', json_decode($inbounds, true));
            }

            $data = array(
                'data_limit' => $new_limit,
            );

            // Only add group_ids if not empty
            if (!empty($group_ids)) {
                $data['group_ids'] = $group_ids;
            }
        }
        $extra_volume = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_volume['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_volume['msg']
            );
        }
        return $extra_volume;
    }
    function extra_time($username_account, $code_panel, $limit_time_new)
    {
        $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
        $invoice = select("invoice", "*", "username", $username_account, "select");
        if ($panel == false) {
            return array(
                'status' => false,
                'msg' => 'data not found'
            );
        }
        $notif_value = json_decode($invoice['notifctions'], true);
        $notifctions = json_encode(array(
            'volume' => $notif_value['volume'],
            'time' => false,
        ));
        update("invoice", "notifctions", $notifctions, 'id_invoice', $invoice['id_invoice']);
        $user_info = $this->DataUser($panel['name_panel'], $username_account);
        if ($user_info['status'] == "Unsuccessful") {
            return array(
                'status' => false,
                'msg' => $user_info['msg']
            );
        }
        $old_limit_time = $user_info['expire'];
        $old_limit_time = time() - $old_limit_time > 0 ? time() : $old_limit_time;
        $new_limit = $limit_time_new == 0 ? 0 : $limit_time_new * 86400 + $old_limit_time;
        $inbound_id = isset($panel['inboundid']) ? $panel['inboundid'] : 1;
        $inbounds = is_string($panel['inbounds']) ? json_decode($panel['inbounds']) : "{}";
        if ($panel['type'] != "WGDashboard") {
            update("invoice", 'user_info', null, "username", $username_account);
        }
        update("invoice", 'uuid', null, "username", $username_account);
        update("invoice", 'Status', "active", "username", $username_account);
        if ($panel['type'] == "marzban") {
            $data = array(
                'expire' => $new_limit,
                'inbounds' => $inbounds,
            );
            if ($invoice != false && $invoice['uuid'] != null) {
                $data['proxies'] = json_decode($invoice['uuid'], true);
            }
        } elseif ($panel['type'] == "marzneshin") {
            $data = array(
                'expire_date' => $new_limit,
                'expire_strategy' => "fixed_date",

            );
        } elseif ($panel['type'] == "x-ui_single") {
            $new_limit = $new_limit * 1000;
            $data = array(
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "expiryTime" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        } elseif ($panel['type'] == "alireza_single") {
            $new_limit = $new_limit * 1000;
            $data = array(
                'id' => intval($inbound_id),
                'settings' => json_encode(
                    array(
                        'clients' => array(
                            array(
                                "expiryTime" => $new_limit,
                            )
                        ),
                    )
                ),
            );
        } elseif ($panel['type'] == "hiddify") {
            $new_limit = ($old_limit_time / pow(1024, 3)) + $limit_time_new;
            $datauser = getdatauser($username_account, $panel['name_panel']);
            $data = array(
                "current_usage_GB" => $datauser['current_usage_GB'],
                "usage_limit_GB" => $datauser['usage_limit_GB'],
                "package_days" => $new_limit,
                "start_date" => null
            );
        } elseif ($panel['type'] == "WGDashboard") {
            allowAccessPeers($panel['name_panel'], $username_account);
            $datauser = get_userwg($username_account, $panel['name_panel']);
            $count = 0;
            foreach ($datauser['jobs'] as $jobsvolume) {
                if ($jobsvolume['Field'] == "date") {
                    break;
                }
                $count += 1;
            }
            if (isset($datauser['jobs'][$count])) {
                $datam = array(
                    "Job" => $datauser['jobs'][$count],
                );
                deletejob($panel['name_panel'], $datam);
            }
            $log = setjob($panel['name_panel'], "date", date('Y-m-d H:i:s', $new_limit), $datauser['id']);
            return array(
                'status' => true,
                'data' => $log
            );
        } elseif ($panel['type'] == "s_ui") {
            $data = array(
                "expiry" => $new_limit,
            );
        } elseif ($panel['type'] == "pasarguard") {
            // Pasarguard uses group_ids (not inbounds) and needs proper format
            $group_ids = array();
            if (is_array($inbounds)) {
                $group_ids = array_map('intval', $inbounds);
            } elseif (!empty($inbounds)) {
                $group_ids = array_map('intval', json_decode($inbounds, true));
            }

            $data = array(
                'expire' => $new_limit,
            );

            // Only add group_ids if not empty
            if (!empty($group_ids)) {
                $data['group_ids'] = $group_ids;
            }
        }
        $extra_time = $this->Modifyuser($username_account, $panel['name_panel'], $data);
        if ($extra_time['status'] == false) {
            return array(
                'status' => false,
                'msg' => $extra_time['msg']
            );
        }
        return $extra_time;
    }
}
// $ManagePanel = new ManagePanel();
// $datac = array(
//         'desc' => 'mahdi12211',
//     );
// // $DataUserOut = $ManagePanel->createUser("test2","usertest","mahdi1221",$datac);
// // // $DataUserOut = $ManagePanel->RemoveUser("alireza","4b090d1f0d19");
// $DataUserOut = $ManagePanel->Modifyuser("mahdi12211","test2",$datac);
// // $DataUserOut = $ManagePanel->Revoke_sub("iran","ddddd");
// var_dump($DataUserOut);