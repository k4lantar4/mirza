<?php
require_once 'config.php';
require_once 'request.php';
ini_set('error_log', 'error_log');
date_default_timezone_set('Asia/Tehran');

#-----------------------------#
function pg_token_panel($code_panel, $verify = true)
{
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
    if ($panel === null) {
        return array("error" => "Panel not found");
    }

    // Check cached token (valid for 1 hour)
    if ($panel['datelogin'] != null && $verify) {
        $date = json_decode($panel['datelogin'], true);
        if (isset($date['time'])) {
            $timecurrent = time();
            $start_date = time() - strtotime($date['time']);
            if ($start_date <= 3600) {
                return $date;
            }
        }
    }

    // Get new token
    $url_get_token = $panel['url_panel'] . '/api/admin/token';
    $username_panel = $panel['username_panel'];
    $password_panel = $panel['password_panel'];

    $data_token = array(
        'grant_type' => 'password',
        'username' => $username_panel,
        'password' => $password_panel,
        'scope' => '',
        'client_id' => '',
        'client_secret' => ''
    );

    $headers = array(
        'Content-Type: application/x-www-form-urlencoded',
        'accept: application/json'
    );

    $req = new CurlRequest($url_get_token);
    $req->setHeaders($headers);
    $response = $req->post($data_token);

    if (!empty($response['error'])) {
        return array("error" => $response['error']);
    }

    $body = json_decode($response['body'], true);
    if (isset($body['access_token'])) {
        $time = date('Y/m/d H:i:s');
        $data = json_encode(array(
            'time' => $time,
            'access_token' => $body['access_token']
        ));
        update("marzban_panel", "datelogin", $data, 'name_panel', $panel['name_panel']);
    }

    return $body;
}
#-----------------------------#

function pg_add_user($location, $data_limit, $username, $expire, $note = '', $data_limit_reset = 'no_reset', $name_product = false)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses POST /api/user like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user';

    // Build user data following Pasarguard API spec
    $user_data = array(
        'username' => $username,
        'data_limit' => $data_limit,
        'data_limit_reset_strategy' => $data_limit_reset,
        'note' => $note,
        'status' => 'active'
    );

    // Handle expire timestamp - convert to ISO 8601 format if not 0
    if ($expire == 0) {
        $user_data['expire'] = 0;
    } else {
        $user_data['expire'] = date('c', $expire);
    }

    // Handle proxy_settings (NOT proxies) - Pasarguard uses proxy_settings object
    $proxy_settings = array();
    if (!empty($panel['proxies']) && $panel['proxies'] != "null") {
        $proxies = json_decode($panel['proxies'], true);
        if (is_array($proxies) && !empty($proxies)) {
            $proxy_settings = $proxies;
        }
    }

    // Handle product-specific proxy_settings if provided
    if ($name_product && $name_product != "usertest") {
        global $pdo;
        $product = select("product", "*", "name_product", $name_product, "select");
        if ($product && !empty($product['proxies']) && $product['proxies'] != "null") {
            $product_proxies = json_decode($product['proxies'], true);
            if (is_array($product_proxies) && !empty($product_proxies)) {
                $proxy_settings = $product_proxies;
            }
        }
    }

    // Only add proxy_settings if not empty
    if (!empty($proxy_settings)) {
        $user_data['proxy_settings'] = $proxy_settings;
    }

    // Handle group_ids - Pasarguard uses group_ids array of integers
    // Use group_ids column from database (not inbounds) for Pasarguard
    $group_ids_array = null;

    // Helper function to parse group_ids value to array
    $parse_group_ids = function($group_ids_value) {
        if (empty($group_ids_value) || $group_ids_value === null || $group_ids_value === "null" || $group_ids_value === "") {
            return null;
        }

        // Try JSON decode first
        $decoded = json_decode($group_ids_value, true);

        // If JSON decode failed or returned non-array, try other formats
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            // Maybe it's comma-separated: "1,2,3"
            if (preg_match('/^[\d,\s]+$/', $group_ids_value)) {
                $decoded = array_map('trim', explode(',', $group_ids_value));
            }
            // Maybe it's a single number: "1"
            elseif (is_numeric($group_ids_value)) {
                $decoded = array(intval($group_ids_value));
            }
        }

        // Validate and convert to integer array
        if (is_array($decoded) && !empty($decoded)) {
            // Filter out empty values and convert to integers
            $ids = array_filter(array_map('intval', $decoded), function($val) {
                return $val > 0; // Only positive integers
            });
            return !empty($ids) ? array_values($ids) : null; // Re-index array
        } elseif (is_numeric($group_ids_value)) {
            // Single integer value
            $id = intval($group_ids_value);
            return $id > 0 ? array($id) : null;
        }

        return null;
    };

    // First, try to get group_ids from panel's group_ids column (NEW column for Pasarguard)
    if (!empty($panel['group_ids']) && $panel['group_ids'] !== "null") {
        $group_ids_array = $parse_group_ids($panel['group_ids']);
    }
    // Fallback to inbounds if group_ids is not set (backward compatibility)
    elseif (!empty($panel['inbounds']) && $panel['inbounds'] !== "null") {
        $group_ids_array = $parse_group_ids($panel['inbounds']);
    }

    // Override with product-specific group_ids if provided (product takes priority)
    if ($name_product && $name_product != "usertest") {
        global $pdo;
        $product = select("product", "*", "name_product", $name_product, "select");
        if ($product) {
            // Try product's group_ids first (if it exists), then fallback to inbounds
            if (!empty($product['group_ids']) && $product['group_ids'] !== "null") {
                $product_group_ids = $parse_group_ids($product['group_ids']);
                if ($product_group_ids !== null) {
                    $group_ids_array = $product_group_ids;
                }
            } elseif (!empty($product['inbounds']) && $product['inbounds'] !== "null") {
                $product_group_ids = $parse_group_ids($product['inbounds']);
                if ($product_group_ids !== null) {
                    $group_ids_array = $product_group_ids;
                }
            }
        }
    }

    // Add group_ids to user_data if we have valid group_ids
    if ($group_ids_array !== null && !empty($group_ids_array)) {
        $user_data['group_ids'] = $group_ids_array;
    }

    $headers = array(
        'Content-Type: application/json',
        'accept: application/json'
    );

    // Debug: Log what we're sending (remove in production if needed)
    // Uncomment the next lines for debugging:
    // error_log("Pasarguard create user - URL: " . $url);
    // error_log("Pasarguard create user - Data: " . json_encode($user_data, JSON_PRETTY_PRINT));
    // error_log("Pasarguard create user - group_ids: " . (isset($user_data['group_ids']) ? json_encode($user_data['group_ids']) : 'NOT SET'));

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->post(json_encode($user_data));

    // Debug: Log response (remove in production if needed)
    // error_log("Pasarguard create user - Response: " . json_encode($response));

    return $response;
}

function pg_get_user($username, $location)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses GET /api/user/{username} like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user/' . $username;

    $headers = array(
        'accept: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $response = $req->get();

    return $response;
}

function pg_modify_user($location, $username, array $data)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses PUT /api/user/{username} like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user/' . $username;

    // Build update data following Pasarguard API spec
    $user_data = array();

    // Handle data_limit
    if (isset($data['data_limit'])) {
        $user_data['data_limit'] = $data['data_limit'];
    }

    // Handle expire - convert timestamp to ISO 8601 if needed
    if (isset($data['expire'])) {
        if ($data['expire'] == 0) {
            $user_data['expire'] = 0;
        } elseif (is_numeric($data['expire'])) {
            $user_data['expire'] = date('c', $data['expire']);
        } else {
            $user_data['expire'] = $data['expire'];
        }
    }

    // Handle status
    if (isset($data['status'])) {
        $user_data['status'] = $data['status'];
    }

    // Handle data_limit_reset_strategy
    if (isset($data['data_limit_reset_strategy'])) {
        $user_data['data_limit_reset_strategy'] = $data['data_limit_reset_strategy'];
    }

    // Handle note
    if (isset($data['note'])) {
        $user_data['note'] = $data['note'];
    }

    // Handle proxy_settings (NOT proxies) - Pasarguard uses proxy_settings object
    if (isset($data['proxy_settings'])) {
        $user_data['proxy_settings'] = $data['proxy_settings'];
    } elseif (isset($data['proxies'])) {
        // Map proxies to proxy_settings for backward compatibility
        $user_data['proxy_settings'] = $data['proxies'];
    }

    // Handle group_ids (NOT inbounds) - Pasarguard uses group_ids array of integers
    if (isset($data['group_ids'])) {
        // Ensure all values are integers
        $user_data['group_ids'] = array_map('intval', $data['group_ids']);
    } elseif (isset($data['inbounds'])) {
        // Map inbounds to group_ids for backward compatibility
        $user_data['group_ids'] = array_map('intval', $data['inbounds']);
    }

    $headers = array(
        'Content-Type: application/json',
        'accept: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $res = $req->put(json_encode($user_data));

    return $res;
}

function pg_reset_usage($username, $location)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses POST /api/user/{username}/reset like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user/' . $username . '/reset';

    $headers = array(
        'accept: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $res = $req->post(array());

    return $res;
}

function pg_revoke_sub($username, $location)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses POST /api/user/{username}/revoke_sub like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user/' . $username . '/revoke_sub';

    $headers = array(
        'accept: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $res = $req->post(array());

    return $res;
}

function pg_remove_user($location, $username)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $Check_token = pg_token_panel($panel['code_panel']);
    if (!empty($Check_token['error'])) {
        return $Check_token;
    }
    if (!isset($Check_token['access_token'])) {
        return array("error" => "Token not found");
    }

    // Pasarguard uses DELETE /api/user/{username} like Marzban
    $url = rtrim($panel['url_panel'], '/') . '/api/user/' . $username;

    $headers = array(
        'accept: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $req->setBearerToken($Check_token['access_token']);
    $res = $req->delete();

    return $res;
}


