<?php
require_once 'config.php';
require_once 'request.php';
ini_set('error_log', 'error_log');

function pg_get_api_key($code_panel)
{
    $panel = select("marzban_panel", "*", "code_panel", $code_panel, "select");
    if ($panel === null) {
        return null;
    }
    // Pasarguard uses API_KEY from .env file, stored in password_panel field
    return $panel['password_panel'];
}

function pg_add_user($location, $data_limit, $username, $expire, $note = '', $data_limit_reset = 'no_reset', $name_product = false)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Pasarguard uses PUT /user/sync with User protobuf
    $url = rtrim($panel['url_panel'], '/') . '/user/sync';

    // Build User protobuf structure (simplified JSON representation)
    $user_data = array(
        'email' => $username,
        'proxies' => array(
            'type' => 'XRAY',
            'config' => json_encode(array(
                'data_limit' => $data_limit,
                'expire' => $expire,
                'note' => $note
            ))
        ),
        'inbounds' => array()
    );

    // Add inbounds if configured
    if (!empty($panel['inbounds']) && $panel['inbounds'] != "null") {
        $inbounds = json_decode($panel['inbounds'], true);
        if (is_array($inbounds)) {
            $user_data['inbounds'] = $inbounds;
        }
    }

    // Try JSON first
    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $response = $req->put(json_encode($user_data));
    if (isset($response['status']) && in_array($response['status'], [400, 415])) {
        // retry with protobuf content type (payload kept JSON-like until full encoder is needed)
        $req = new CurlRequest($url);
        $req->setHeaders(array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/x-protobuf'
        ));
        $response = $req->put(json_encode($user_data));
    }

    // Return success response with subscription URL
    if (empty($response['error']) && (!isset($response['status']) || $response['status'] == 200)) {
        $subscription_url = rtrim($panel['url_panel'], '/') . '/sub/' . $username;
        return array(
            'body' => json_encode(array(
                'username' => $username,
                'subscription_url' => $subscription_url,
                'links' => array($subscription_url)
            ))
        );
    }

    return $response;
}

function pg_get_user($username, $location)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Pasarguard doesn't have direct user retrieval, use stats instead
    $url = rtrim($panel['url_panel'], '/') . '/stats';

    // Build StatRequest for user stats
    $stat_request = array(
        'name' => $username,
        'type' => 'UserStat', // 5 = UserStat
        'reset' => false
    );

    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $response = $req->getWithBody(json_encode($stat_request));
    if (isset($response['status']) && in_array($response['status'], [400, 415])) {
        $req = new CurlRequest($url);
        $req->setHeaders(array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/x-protobuf'
        ));
        $response = $req->getWithBody(json_encode($stat_request));
    }

    // Parse stats to compute used_traffic
    if (empty($response['error']) && (!isset($response['status']) || $response['status'] == 200)) {
        $used_traffic = 0;
        $body = json_decode($response['body'], true);
        if (isset($body['stats']) && is_array($body['stats'])) {
            foreach ($body['stats'] as $stat) {
                if (isset($stat['name']) && isset($stat['value'])) {
                    // Sum all values for the user (bytes)
                    $used_traffic += intval($stat['value']);
                }
            }
        }
        $subscription_url = rtrim($panel['url_panel'], '/') . '/sub/' . $username;
        return array(
            'body' => json_encode(array(
                'username' => $username,
                'status' => 'active',
                'data_limit' => 0,
                'expire' => 0,
                'online_at' => null,
                'used_traffic' => $used_traffic,
                'subscription_url' => $subscription_url,
                'links' => array($subscription_url)
            ))
        );
    }

    return $response;
}

function pg_modify_user($location, $username, array $data)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Pasarguard uses PUT /user/sync to update user
    $url = rtrim($panel['url_panel'], '/') . '/user/sync';

    // Build updated User structure; encode extend/volume/time in config
    $user_data = array(
        'email' => $username,
        'proxies' => array(
            'type' => 'XRAY',
            'config' => json_encode(array(
                'data_limit' => isset($data['data_limit']) ? $data['data_limit'] : null,
                'expire' => isset($data['expire']) ? $data['expire'] : null,
                'extend_strategy' => isset($data['extend_strategy']) ? $data['extend_strategy'] : null
            ))
        ),
        'inbounds' => array()
    );

    // Add inbounds if configured
    if (!empty($panel['inbounds']) && $panel['inbounds'] != "null") {
        $inbounds = json_decode($panel['inbounds'], true);
        if (is_array($inbounds)) {
            $user_data['inbounds'] = $inbounds;
        }
    }

    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $res = $req->put(json_encode($user_data));
    if (isset($res['status']) && in_array($res['status'], [400, 415])) {
        $req = new CurlRequest($url);
        $req->setHeaders(array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/x-protobuf'
        ));
        $res = $req->put(json_encode($user_data));
    }
    return $res;
}

function pg_reset_usage($username, $location)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Pasarguard uses GET /stats with reset=true to reset user stats
    $url = rtrim($panel['url_panel'], '/') . '/stats';

    $stat_request = array(
        'name' => $username,
        'type' => 'UserStat', // 5 = UserStat
        'reset' => true
    );

    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $res = $req->getWithBody(json_encode($stat_request));
    if (isset($res['status']) && in_array($res['status'], [400, 415])) {
        $req = new CurlRequest($url);
        $req->setHeaders(array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/x-protobuf'
        ));
        $res = $req->getWithBody(json_encode($stat_request));
    }
    return $res;
}

function pg_revoke_sub($username, $location)
{
    // Pasarguard doesn't have explicit revoke_sub, regenerate user config instead
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Update user with new config to effectively revoke old subscription
    $url = rtrim($panel['url_panel'], '/') . '/user/sync';

    $user_data = array(
        'email' => $username,
        'proxies' => array(
            'type' => 'XRAY',
            'config' => json_encode(array(
                'revoked_at' => time(),
                'regenerated' => true
            ))
        ),
        'inbounds' => array()
    );

    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    $res = $req->put(json_encode($user_data));
    if (isset($res['status']) && in_array($res['status'], [400, 415])) {
        $req = new CurlRequest($url);
        $req->setHeaders(array(
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/x-protobuf'
        ));
        $res = $req->put(json_encode($user_data));
    }
    return $res;
}

function pg_remove_user($location, $username)
{
    $panel = select("marzban_panel", "*", "name_panel", $location, "select");
    $api_key = pg_get_api_key($panel['code_panel']);
    if (!$api_key) {
        return array("error" => "API key not found");
    }

    // Pasarguard removes user by sending empty inbounds array
    $url = rtrim($panel['url_panel'], '/') . '/user/sync';

    $user_data = array(
        'email' => $username,
        'proxies' => array(
            'type' => 'XRAY',
            'config' => json_encode(array())
        ),
        'inbounds' => array() // Empty inbounds = remove user
    );

    $headers = array(
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/x-protobuf'
    );

    $req = new CurlRequest($url);
    $req->setHeaders($headers);
    return $req->put(json_encode($user_data));
}


