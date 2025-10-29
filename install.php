<?php
/**
 * Mirza Pro - Web-based Installation System
 * Complete installation and database management through web interface
 */

// Prevent direct access if already installed
if (file_exists('config.php') && !isset($_GET['force'])) {
    $config_exists = true;
    try {
        require_once 'config.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            header('Location: webpanel/');
            exit;
        }
    } catch (Exception $e) {
        $config_exists = false;
    }
}

// Set error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Installation steps
$steps = [
    1 => 'System Requirements Check',
    2 => 'Database Configuration',
    3 => 'Database Setup',
    4 => 'Bot Configuration',
    5 => 'Final Setup'
];

$current_step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$current_step = max(1, min(5, $current_step));

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($current_step) {
        case 2:
            handleDatabaseConfig();
            break;
        case 3:
            handleDatabaseSetup();
            break;
        case 4:
            handleBotConfig();
            break;
        case 5:
            handleFinalSetup();
            break;
    }
}

function handleDatabaseConfig() {
    global $current_step;

    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? 'mirza_pro';
    $db_user = $_POST['db_user'] ?? '';
    $db_password = $_POST['db_password'] ?? '';

    // Test database connection
    try {
        $pdo = new PDO("mysql:host=$db_host", $db_user, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Save credentials for next step
        $_SESSION['db_config'] = [
            'host' => $db_host,
            'name' => $db_name,
            'user' => $db_user,
            'password' => $db_password
        ];

        $current_step = 3;
        header('Location: ?step=3');
        exit;
    } catch (PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
    }
}

function handleDatabaseSetup() {
    global $current_step;

    if (!isset($_SESSION['db_config'])) {
        header('Location: ?step=2');
        exit;
    }

    $config = $_SESSION['db_config'];

    try {
        // Connect to MySQL server
        $pdo = new PDO("mysql:host={$config['host']}", $config['user'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create database if not exists
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Connect to the specific database
        $pdo = new PDO("mysql:host={$config['host']};dbname={$config['name']}", $config['user'], $config['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Run database setup
        require_once 'table.php';

        $current_step = 4;
        header('Location: ?step=4');
        exit;
    } catch (PDOException $e) {
        $error = "Database setup failed: " . $e->getMessage();
    }
}

function handleBotConfig() {
    global $current_step;

    $bot_token = $_POST['bot_token'] ?? '';
    $admin_id = $_POST['admin_id'] ?? '';
    $domain = $_POST['domain'] ?? '';

    if (empty($bot_token) || empty($admin_id)) {
        $error = "Bot token and admin ID are required";
        return;
    }

    // Save bot configuration
    $_SESSION['bot_config'] = [
        'bot_token' => $bot_token,
        'admin_id' => $admin_id,
        'domain' => $domain
    ];

    $current_step = 5;
    header('Location: ?step=5');
    exit;
}

function handleFinalSetup() {
    global $current_step;

    if (!isset($_SESSION['db_config']) || !isset($_SESSION['bot_config'])) {
        header('Location: ?step=2');
        exit;
    }

    $db_config = $_SESSION['db_config'];
    $bot_config = $_SESSION['bot_config'];

    try {
        // Create config.php
        $config_content = generateConfigFile($db_config, $bot_config);
        file_put_contents('config.php', $config_content);

        // Set up admin user
        require_once 'config.php';
        setupAdminUser($bot_config['admin_id']);

        // Configure hosting environment
        require_once 'hosting_config.php';
        $hosting_config = new HostingConfig();
        $hosting_results = $hosting_config->saveConfigFiles();

        // Create installation complete flag
        file_put_contents('webpanel/.installation_complete', date('Y-m-d H:i:s'));

        // Clean up session
        unset($_SESSION['db_config']);
        unset($_SESSION['bot_config']);

        header('Location: webpanel/');
        exit;
    } catch (Exception $e) {
        $error = "Final setup failed: " . $e->getMessage();
    }
}

function generateConfigFile($db_config, $bot_config) {
    $domain = $bot_config['domain'] ?: $_SERVER['HTTP_HOST'];

    return "<?php
// Mirza Pro Configuration
// Generated on " . date('Y-m-d H:i:s') . "

// Database Configuration
\$db_host = '{$db_config['host']}';
\$db_name = '{$db_config['name']}';
\$db_user = '{$db_config['user']}';
\$db_password = '{$db_config['password']}';

// Bot Configuration
\$bot_token = '{$bot_config['bot_token']}';
\$admin_ids = [{$bot_config['admin_id']}];
\$domainhosts = '$domain';

// PDO Connection
try {
    \$pdo = new PDO(\"mysql:host=\$db_host;dbname=\$db_name;charset=utf8mb4\", \$db_user, \$db_password);
    \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    \$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException \$e) {
    die('Database connection failed: ' . \$e->getMessage());
}

// Legacy MySQL connection for compatibility
\$connect = new mysqli(\$db_host, \$db_user, \$db_password, \$db_name);
if (\$connect->connect_error) {
    die('MySQL connection failed: ' . \$connect->connect_error);
}

// Admin number for compatibility
\$adminnumber = {$bot_config['admin_id']};

// Version
\$version = '5.10.77';
?>";
}

function setupAdminUser($admin_id) {
    global $pdo;

    // Insert admin user
    $stmt = $pdo->prepare("INSERT IGNORE INTO admin (id_admin, username, password, rule) VALUES (?, 'admin', ?, 'administrator')");
    $random_password = bin2hex(random_bytes(8));
    $stmt->execute([$admin_id, $random_password]);
}

function checkSystemRequirements() {
    require_once 'hosting_config.php';
    $hosting_config = new HostingConfig();
    $config = $hosting_config->getConfig();

    $requirements = [
        'PHP Version >= 7.4' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
        'cURL Extension' => extension_loaded('curl'),
        'JSON Extension' => extension_loaded('json'),
        'OpenSSL Extension' => extension_loaded('openssl'),
        'GD Extension' => extension_loaded('gd'),
        'MBString Extension' => extension_loaded('mbstring'),
        'Config File Writable' => is_writable('.'),
        'Webpanel Directory Writable' => is_writable('webpanel/'),
        'Hosting Type Detected' => $config['hosting_type'] !== 'unknown',
    ];

    return $requirements;
}

session_start();
$requirements = checkSystemRequirements();
$all_requirements_met = !in_array(false, $requirements);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب Mirza Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 1.1em;
        }

        .progress {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e9ecef;
        }

        .progress-bar {
            background: #e9ecef;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .progress-fill {
            background: linear-gradient(90deg, #667eea, #764ba2);
            height: 100%;
            width: <?php echo ($current_step / 5) * 100; ?>%;
            transition: width 0.3s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .step {
            display: flex;
            align-items: center;
            font-size: 0.9em;
            color: #6c757d;
        }

        .step.active {
            color: #667eea;
            font-weight: bold;
        }

        .step.completed {
            color: #28a745;
        }

        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            font-weight: bold;
        }

        .step.active .step-number {
            background: #667eea;
            color: white;
        }

        .step.completed .step-number {
            background: #28a745;
            color: white;
        }

        .content {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            margin-left: 15px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .requirements {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .requirement {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .requirement.pass {
            background: #d4edda;
            color: #155724;
        }

        .requirement.fail {
            background: #f8d7da;
            color: #721c24;
        }

        .requirement-icon {
            font-size: 1.5em;
            margin-left: 15px;
        }

        .text-center {
            text-align: center;
        }

        .mt-4 {
            margin-top: 2rem;
        }

        .mb-4 {
            margin-bottom: 2rem;
        }

        .help-text {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
        }

        .code {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Mirza Pro</h1>
            <p>سیستم نصب و راه‌اندازی وب‌محور</p>
        </div>

        <div class="progress">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            <div class="step-indicator">
                <?php foreach ($steps as $step_num => $step_name): ?>
                    <div class="step <?php echo $step_num < $current_step ? 'completed' : ($step_num == $current_step ? 'active' : ''); ?>">
                        <span><?php echo $step_name; ?></span>
                        <div class="step-number"><?php echo $step_num; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="content">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <strong>خطا:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($current_step == 1): ?>
                <h2>بررسی نیازمندی‌های سیستم</h2>
                <p class="mb-4">قبل از شروع نصب، سیستم شما بررسی می‌شود:</p>

                <div class="requirements">
                    <?php foreach ($requirements as $requirement => $status): ?>
                        <div class="requirement <?php echo $status ? 'pass' : 'fail'; ?>">
                            <div class="requirement-icon">
                                <?php echo $status ? '✅' : '❌'; ?>
                            </div>
                            <div>
                                <strong><?php echo $requirement; ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($all_requirements_met): ?>
                    <div class="alert alert-success">
                        <strong>عالی!</strong> تمام نیازمندی‌های سیستم برآورده شده است.
                    </div>
                    <div class="text-center">
                        <a href="?step=2" class="btn">ادامه نصب</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <strong>خطا:</strong> برخی از نیازمندی‌های سیستم برآورده نشده است. لطفاً با مدیر سرور تماس بگیرید.
                    </div>
                <?php endif; ?>

            <?php elseif ($current_step == 2): ?>
                <h2>پیکربندی پایگاه داده</h2>
                <p class="mb-4">اطلاعات اتصال به پایگاه داده MySQL را وارد کنید:</p>

                <form method="POST">
                    <div class="form-group">
                        <label for="db_host">آدرس سرور پایگاه داده:</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                        <div class="help-text">معمولاً localhost است</div>
                    </div>

                    <div class="form-group">
                        <label for="db_name">نام پایگاه داده:</label>
                        <input type="text" id="db_name" name="db_name" value="mirza_pro" required>
                        <div class="help-text">پایگاه داده جدید ایجاد خواهد شد</div>
                    </div>

                    <div class="form-group">
                        <label for="db_user">نام کاربری MySQL:</label>
                        <input type="text" id="db_user" name="db_user" required>
                        <div class="help-text">نام کاربری با دسترسی ایجاد پایگاه داده</div>
                    </div>

                    <div class="form-group">
                        <label for="db_password">رمز عبور MySQL:</label>
                        <input type="password" id="db_password" name="db_password" required>
                    </div>

                    <div class="text-center">
                        <a href="?step=1" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn">تست اتصال و ادامه</button>
                    </div>
                </form>

            <?php elseif ($current_step == 3): ?>
                <h2>راه‌اندازی پایگاه داده</h2>
                <p class="mb-4">جداول و داده‌های اولیه ایجاد می‌شود:</p>

                <div class="alert alert-info">
                    <strong>در حال ایجاد پایگاه داده...</strong><br>
                    این مرحله ممکن است چند دقیقه طول بکشد.
                </div>

                <form method="POST">
                    <div class="text-center">
                        <a href="?step=2" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn">ایجاد پایگاه داده</button>
                    </div>
                </form>

            <?php elseif ($current_step == 4): ?>
                <h2>پیکربندی ربات تلگرام</h2>
                <p class="mb-4">اطلاعات ربات تلگرام را وارد کنید:</p>

                <form method="POST">
                    <div class="form-group">
                        <label for="bot_token">توکن ربات تلگرام:</label>
                        <input type="text" id="bot_token" name="bot_token" placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" required>
                        <div class="help-text">توکن ربات را از <span class="code">@BotFather</span> دریافت کنید</div>
                    </div>

                    <div class="form-group">
                        <label for="admin_id">آیدی ادمین:</label>
                        <input type="number" id="admin_id" name="admin_id" placeholder="123456789" required>
                        <div class="help-text">آیدی عددی شما در تلگرام (از <span class="code">@userinfobot</span> دریافت کنید)</div>
                    </div>

                    <div class="form-group">
                        <label for="domain">دامنه سایت (اختیاری):</label>
                        <input type="text" id="domain" name="domain" placeholder="example.com" value="<?php echo $_SERVER['HTTP_HOST']; ?>">
                        <div class="help-text">برای webhook و دسترسی به پنل وب</div>
                    </div>

                    <div class="text-center">
                        <a href="?step=3" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn">ادامه</button>
                    </div>
                </form>

            <?php elseif ($current_step == 5): ?>
                <h2>تکمیل نصب</h2>
                <p class="mb-4">نصب در حال تکمیل است...</p>

                <div class="alert alert-info">
                    <strong>در حال تکمیل نصب...</strong><br>
                    فایل‌های پیکربندی ایجاد می‌شود و کاربر ادمین تنظیم می‌شود.
                </div>

                <form method="POST">
                    <div class="text-center">
                        <a href="?step=4" class="btn btn-secondary">بازگشت</a>
                        <button type="submit" class="btn">تکمیل نصب</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
