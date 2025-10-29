<?php
/**
 * Pasarguard API Test File
 *
 * This file tests Pasarguard API endpoints to verify correct implementation.
 *
 * Usage:
 * 1. Set environment variables before accessing this file:
 *    export PASARGUARD_URL="https://your-pasarguard-panel.com"
 *    export PASARGUARD_API_KEY="your-api-key-here"
 *
 * 2. Or set via GET parameters:
 *    ?url=https://your-pasarguard-panel.com&apikey=your-api-key
 *
 * 3. Access via browser: http://your-domain.com/test_pasarguard_api.php
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasarguard API Test</title>
    <style>
        body { font-family: Tahoma, Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .test-section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 5px; border-left: 4px solid #4CAF50; }
        .test-section h2 { margin-top: 0; color: #4CAF50; }
        .test-result { margin: 10px 0; padding: 10px; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 4px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Pasarguard API Test Tool</h1>

        <?php
        // Get configuration from environment or GET parameters
        $pasarguard_url = getenv('PASARGUARD_URL') ?: ($_GET['url'] ?? '');
        $pasarguard_api_key = getenv('PASARGUARD_API_KEY') ?: ($_GET['apikey'] ?? '');

        // Show form if credentials not provided
        if (empty($pasarguard_url) || empty($pasarguard_api_key)) {
        ?>
            <div class="warning">
                <strong>⚠️ توجه:</strong> برای تست API پاسارگارد، اطلاعات زیر را وارد کنید:
            </div>

            <form method="GET">
                <div class="form-group">
                    <label>Pasarguard Panel URL:</label>
                    <input type="text" name="url" value="<?php echo htmlspecialchars($pasarguard_url); ?>" placeholder="https://panel.example.com" required>
                </div>
                <div class="form-group">
                    <label>API Key:</label>
                    <input type="text" name="apikey" value="<?php echo htmlspecialchars($pasarguard_api_key); ?>" placeholder="your-api-key-here" required>
                </div>
                <button type="submit">شروع تست</button>
            </form>
        <?php
            exit;
        }

        // Helper function to make API requests
        function testPasarguardAPI($url, $api_key, $method = 'GET', $endpoint = '', $data = null) {
            $full_url = rtrim($url, '/') . $endpoint;
            $ch = curl_init($full_url);

            $headers = array(
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json',
                'Accept: application/json'
            );

            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_CUSTOMREQUEST => $method,
            ));

            if ($data !== null && in_array($method, ['POST', 'PUT'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            return array(
                'url' => $full_url,
                'method' => $method,
                'http_code' => $http_code,
                'response' => $response,
                'error' => $error,
                'parsed' => json_decode($response, true)
            );
        }

        // Display configuration
        echo '<div class="test-section">';
        echo '<h2>📋 تنظیمات</h2>';
        echo '<div class="test-result info">';
        echo '<strong>Panel URL:</strong> ' . htmlspecialchars($pasarguard_url) . '<br>';
        echo '<strong>API Key:</strong> ' . str_repeat('*', strlen($pasarguard_api_key) - 4) . substr($pasarguard_api_key, -4);
        echo '</div>';
        echo '</div>';

        // Test 1: Base endpoint check
        echo '<div class="test-section">';
        echo '<h2>✅ تست 1: بررسی دسترسی به پنل (GET /)</h2>';
        $test1 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'GET', '/');
        if ($test1['http_code'] == 200 || $test1['http_code'] == 404) {
            echo '<div class="test-result success">';
            echo '<strong>✓ موفق:</strong> پنل در دسترس است (HTTP ' . $test1['http_code'] . ')';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>✗ خطا:</strong> پنل پاسخ نمی‌دهد (HTTP ' . $test1['http_code'] . ')';
        }
        echo '</div>';
        echo '<pre>' . htmlspecialchars($test1['response']) . '</pre>';
        echo '</div>';

        // Test 2: Get admin info (to verify API key)
        echo '<div class="test-section">';
        echo '<h2>✅ تست 2: بررسی اعتبار API Key (GET /api/admin)</h2>';
        $test2 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'GET', '/api/admin');
        if ($test2['http_code'] == 200) {
            echo '<div class="test-result success">';
            echo '<strong>✓ موفق:</strong> API Key معتبر است';
            if (isset($test2['parsed']['username'])) {
                echo '<br><strong>Admin Username:</strong> ' . htmlspecialchars($test2['parsed']['username']);
            }
        } elseif ($test2['http_code'] == 401) {
            echo '<div class="test-result error">';
            echo '<strong>✗ خطا:</strong> API Key نامعتبر است (Unauthorized)';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>✗ خطا:</strong> HTTP ' . $test2['http_code'];
        }
        echo '</div>';
        echo '<pre>' . htmlspecialchars(json_encode($test2['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        echo '</div>';

        // Test 3: Create a test user
        $test_username = 'test_user_' . time();
        $test_user_data = array(
            'username' => $test_username,
            'data_limit' => 1073741824, // 1GB in bytes
            'expire' => 0, // Unlimited
            'proxies' => array(),
            'note' => 'Test user created by API test script'
        );

        echo '<div class="test-section">';
        echo '<h2>✅ تست 3: ایجاد کاربر تستی (POST /api/user)</h2>';
        echo '<div class="test-result info">';
        echo '<strong>Username:</strong> ' . htmlspecialchars($test_username);
        echo '</div>';
        $test3 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'POST', '/api/user', $test_user_data);
        if ($test3['http_code'] == 200 || $test3['http_code'] == 201) {
            echo '<div class="test-result success">';
            echo '<strong>✓ موفق:</strong> کاربر با موفقیت ایجاد شد';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>✗ خطا:</strong> HTTP ' . $test3['http_code'];
        }
        echo '</div>';
        echo '<pre>' . htmlspecialchars(json_encode($test3['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        echo '</div>';

        // Test 4: Get the test user
        if ($test3['http_code'] == 200 || $test3['http_code'] == 201) {
            echo '<div class="test-section">';
            echo '<h2>✅ تست 4: دریافت اطلاعات کاربر (GET /api/user/{username})</h2>';
            $test4 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'GET', '/api/user/' . $test_username);
            if ($test4['http_code'] == 200) {
                echo '<div class="test-result success">';
                echo '<strong>✓ موفق:</strong> اطلاعات کاربر دریافت شد';
                if (isset($test4['parsed']['subscription_url'])) {
                    echo '<br><strong>Subscription URL:</strong> ' . htmlspecialchars($test4['parsed']['subscription_url']);
                }
            } else {
                echo '<div class="test-result error">';
                echo '<strong>✗ خطا:</strong> HTTP ' . $test4['http_code'];
            }
            echo '</div>';
            echo '<pre>' . htmlspecialchars(json_encode($test4['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            echo '</div>';

            // Test 5: Modify user
            echo '<div class="test-section">';
            echo '<h2>✅ تست 5: ویرایش کاربر (PUT /api/user/{username})</h2>';
            $modify_data = array(
                'data_limit' => 2147483648, // 2GB
                'note' => 'Modified by API test script'
            );
            $test5 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'PUT', '/api/user/' . $test_username, $modify_data);
            if ($test5['http_code'] == 200) {
                echo '<div class="test-result success">';
                echo '<strong>✓ موفق:</strong> کاربر ویرایش شد';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>✗ خطا:</strong> HTTP ' . $test5['http_code'];
            }
            echo '</div>';
            echo '<pre>' . htmlspecialchars(json_encode($test5['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            echo '</div>';

            // Test 6: Reset user usage
            echo '<div class="test-section">';
            echo '<h2>✅ تست 6: ریست مصرف کاربر (POST /api/user/{username}/reset)</h2>';
            $test6 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'POST', '/api/user/' . $test_username . '/reset');
            if ($test6['http_code'] == 200) {
                echo '<div class="test-result success">';
                echo '<strong>✓ موفق:</strong> مصرف کاربر ریست شد';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>✗ خطا:</strong> HTTP ' . $test6['http_code'];
            }
            echo '</div>';
            echo '<pre>' . htmlspecialchars(json_encode($test6['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            echo '</div>';

            // Test 7: Revoke subscription
            echo '<div class="test-section">';
            echo '<h2>✅ تست 7: باطل کردن اشتراک (POST /api/user/{username}/revoke_sub)</h2>';
            $test7 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'POST', '/api/user/' . $test_username . '/revoke_sub');
            if ($test7['http_code'] == 200) {
                echo '<div class="test-result success">';
                echo '<strong>✓ موفق:</strong> اشتراک باطل شد';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>✗ خطا:</strong> HTTP ' . $test7['http_code'];
            }
            echo '</div>';
            echo '<pre>' . htmlspecialchars(json_encode($test7['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
            echo '</div>';

            // Test 8: Delete test user
            echo '<div class="test-section">';
            echo '<h2>✅ تست 8: حذف کاربر تستی (DELETE /api/user/{username})</h2>';
            $test8 = testPasarguardAPI($pasarguard_url, $pasarguard_api_key, 'DELETE', '/api/user/' . $test_username);
            if ($test8['http_code'] == 200 || $test8['http_code'] == 204) {
                echo '<div class="test-result success">';
                echo '<strong>✓ موفق:</strong> کاربر حذف شد';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>✗ خطا:</strong> HTTP ' . $test8['http_code'];
            }
            echo '</div>';
            echo '<pre>' . htmlspecialchars($test8['response']) . '</pre>';
            echo '</div>';
        }

        // Summary
        echo '<div class="test-section">';
        echo '<h2>📊 خلاصه نتایج</h2>';
        echo '<div class="test-result info">';
        echo '<strong>توجه:</strong> این تست‌ها نشان می‌دهند که آیا API پاسارگارد مطابق با مستندات OpenAPI کار می‌کند یا خیر.<br>';
        echo 'اگر تمام تست‌ها موفق باشند، می‌توانید کد pasarguard.php را به‌روزرسانی کنید.';
        echo '</div>';
        echo '</div>';
        ?>

        <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 4px;">
            <strong>💡 نکته:</strong> برای حذف این فایل تست پس از استفاده، آن را از سرور پاک کنید.
        </div>
    </div>
</body>
</html>

