<?php
/**
 * Mirza Pro - Setup Verification
 * Checks installation status and system health
 */

// Prevent direct access if not in web context
if (php_sapi_name() === 'cli' && !isset($argv[1])) {
    echo "This script should be run from web browser or with --web flag\n";
    exit(1);
}

$is_web = isset($_SERVER['HTTP_HOST']) || (php_sapi_name() === 'cli' && isset($argv[1]) && $argv[1] === '--web');

if ($is_web) {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>بررسی وضعیت نصب - Mirza Pro</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .status { padding: 15px; margin: 10px 0; border-radius: 8px; }
            .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
            .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
            .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
            .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
            h1 { color: #333; text-align: center; }
            h2 { color: #667eea; margin-top: 30px; }
            .check-item { display: flex; justify-content: space-between; align-items: center; padding: 10px; margin: 5px 0; background: #f8f9fa; border-radius: 5px; }
            .check-item .status-icon { font-size: 1.2em; }
            .btn { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; }
            .btn:hover { background: #5a6fd8; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🔍 بررسی وضعیت نصب Mirza Pro</h1>
    <?php
}

class SetupVerifier {
    private $checks = [];
    private $overall_status = true;

    public function runChecks() {
        $this->checkFiles();
        $this->checkDatabase();
        $this->checkPermissions();
        $this->checkExtensions();
        $this->checkConfiguration();

        return [
            'overall_status' => $this->overall_status,
            'checks' => $this->checks
        ];
    }

    private function checkFiles() {
        $required_files = [
            'config.php' => 'فایل پیکربندی اصلی',
            'table.php' => 'سیستم مدیریت پایگاه داده',
            'function.php' => 'توابع اصلی سیستم',
            'botapi.php' => 'API ربات تلگرام',
            'webpanel/' => 'پنل وب',
            'install.php' => 'سیستم نصب وب‌محور'
        ];

        foreach ($required_files as $file => $description) {
            $exists = file_exists($file);
            $this->addCheck("فایل $description", $exists, $exists ? 'موجود است' : 'یافت نشد');
        }
    }

    private function checkDatabase() {
        if (file_exists('config.php')) {
            try {
                require_once 'config.php';

                if (isset($pdo) && $pdo instanceof PDO) {
                    // Test connection
                    $pdo->query("SELECT 1");
                    $this->addCheck('اتصال به پایگاه داده', true, 'موفق');

                    // Check required tables
                    $required_tables = ['user', 'setting', 'admin', 'marzban_panel', 'product', 'invoice'];
                    $existing_tables = [];

                    $stmt = $pdo->query("SHOW TABLES");
                    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                        $existing_tables[] = $row[0];
                    }

                    foreach ($required_tables as $table) {
                        $exists = in_array($table, $existing_tables);
                        $this->addCheck("جدول $table", $exists, $exists ? 'موجود است' : 'یافت نشد');
                    }
                } else {
                    $this->addCheck('اتصال به پایگاه داده', false, 'PDO اتصال موجود نیست');
                }
            } catch (Exception $e) {
                $this->addCheck('اتصال به پایگاه داده', false, 'خطا: ' . $e->getMessage());
            }
        } else {
            $this->addCheck('اتصال به پایگاه داده', false, 'فایل config.php موجود نیست');
        }
    }

    private function checkPermissions() {
        $directories = [
            '.' => 'دایرکتوری اصلی',
            'webpanel/' => 'پنل وب',
            'logs/' => 'لاگ‌ها',
            'backups/' => 'پشتیبان‌ها'
        ];

        foreach ($directories as $dir => $description) {
            if (file_exists($dir)) {
                $writable = is_writable($dir);
                $this->addCheck("دسترسی نوشتن $description", $writable, $writable ? 'قابل نوشتن' : 'غیرقابل نوشتن');
            }
        }
    }

    private function checkExtensions() {
        $required_extensions = [
            'pdo' => 'PDO',
            'pdo_mysql' => 'PDO MySQL',
            'curl' => 'cURL',
            'json' => 'JSON',
            'openssl' => 'OpenSSL',
            'gd' => 'GD',
            'mbstring' => 'MBString'
        ];

        foreach ($required_extensions as $ext => $name) {
            $loaded = extension_loaded($ext);
            $this->addCheck("اکستنشن $name", $loaded, $loaded ? 'فعال' : 'غیرفعال');
        }
    }

    private function checkConfiguration() {
        if (file_exists('config.php')) {
            try {
                require_once 'config.php';

                $config_checks = [
                    'bot_token' => isset($bot_token) && !empty($bot_token),
                    'admin_ids' => isset($admin_ids) && is_array($admin_ids) && !empty($admin_ids),
                    'domainhosts' => isset($domainhosts) && !empty($domainhosts)
                ];

                foreach ($config_checks as $config => $status) {
                    $this->addCheck("تنظیم $config", $status, $status ? 'تنظیم شده' : 'تنظیم نشده');
                }
            } catch (Exception $e) {
                $this->addCheck('بررسی تنظیمات', false, 'خطا در بارگذاری config.php');
            }
        }
    }

    private function addCheck($name, $status, $message) {
        $this->checks[] = [
            'name' => $name,
            'status' => $status,
            'message' => $message
        ];

        if (!$status) {
            $this->overall_status = false;
        }
    }

    public function renderResults($results) {
        if ($results['overall_status']) {
            echo '<div class="status success">';
            echo '<strong>✅ وضعیت کلی:</strong> سیستم به درستی نصب و پیکربندی شده است.';
            echo '</div>';
        } else {
            echo '<div class="status error">';
            echo '<strong>❌ وضعیت کلی:</strong> برخی مشکلات در نصب یا پیکربندی وجود دارد.';
            echo '</div>';
        }

        echo '<h2>جزئیات بررسی</h2>';

        foreach ($results['checks'] as $check) {
            $icon = $check['status'] ? '✅' : '❌';
            $class = $check['status'] ? 'success' : 'error';

            echo '<div class="check-item">';
            echo '<span><span class="status-icon">' . $icon . '</span> ' . $check['name'] . '</span>';
            echo '<span>' . $check['message'] . '</span>';
            echo '</div>';
        }

        echo '<div style="text-align: center; margin-top: 30px;">';
        if (!$results['overall_status']) {
            echo '<a href="install.php" class="btn">شروع نصب مجدد</a>';
        }
        echo '<a href="webpanel/" class="btn">دسترسی به پنل وب</a>';
        echo '<a href="database_manager.php" class="btn">مدیریت پایگاه داده</a>';
        echo '</div>';
    }
}

// Run verification
$verifier = new SetupVerifier();
$results = $verifier->runChecks();

if ($is_web) {
    $verifier->renderResults($results);
    echo '</div></body></html>';
} else {
    // CLI output
    echo "Mirza Pro Setup Verification\n";
    echo "============================\n\n";

    if ($results['overall_status']) {
        echo "✅ Overall Status: System is properly installed and configured\n\n";
    } else {
        echo "❌ Overall Status: Issues found in installation or configuration\n\n";
    }

    echo "Check Details:\n";
    echo "==============\n";

    foreach ($results['checks'] as $check) {
        $icon = $check['status'] ? '✅' : '❌';
        echo sprintf("%s %-30s %s\n", $icon, $check['name'], $check['message']);
    }

    echo "\n";
    if (!$results['overall_status']) {
        echo "Recommendation: Run web installation at http://yourdomain.com/install.php\n";
    } else {
        echo "System is ready! Access web panel at http://yourdomain.com/webpanel/\n";
    }
}
