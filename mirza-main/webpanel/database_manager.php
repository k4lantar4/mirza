<?php
/**
 * Mirza Pro - Database Management Panel
 * Web-based database maintenance and migration system
 */

require_once 'config.php';
require_once 'table.php';

// Check admin access
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: webpanel/login.php');
    exit;
}

$action = $_GET['action'] ?? 'status';
$message = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'run_migrations':
            try {
                $migrator = new DatabaseMigrator($pdo, $connect);
                $migrator->runMigrations();
                $message = 'Migrations executed successfully';
            } catch (Exception $e) {
                $error = 'Migration failed: ' . $e->getMessage();
            }
            break;

        case 'backup_database':
            try {
                $backup_result = backupDatabase();
                if ($backup_result['success']) {
                    $message = 'Database backup created: ' . $backup_result['filename'];
                } else {
                    $error = 'Backup failed: ' . $backup_result['message'];
                }
            } catch (Exception $e) {
                $error = 'Backup failed: ' . $e->getMessage();
            }
            break;

        case 'optimize_tables':
            try {
                optimizeTables();
                $message = 'Database tables optimized successfully';
            } catch (Exception $e) {
                $error = 'Optimization failed: ' . $e->getMessage();
            }
            break;
    }
}

function getDatabaseStatus() {
    global $pdo;

    try {
        // Get database size
        $stmt = $pdo->query("SELECT
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB'
            FROM information_schema.tables
            WHERE table_schema = DATABASE()");
        $db_size = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get table count
        $stmt = $pdo->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = DATABASE()");
        $table_count = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get migration status
        $migrator = new DatabaseMigrator($pdo);
        $current_version = $migrator->getCurrentVersion();

        return [
            'size' => $db_size['DB Size in MB'] ?? 0,
            'tables' => $table_count['table_count'] ?? 0,
            'version' => $current_version
        ];
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function getTableInfo() {
    global $pdo;

    try {
        $stmt = $pdo->query("SELECT
            table_name,
            table_rows,
            ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size in MB',
            table_collation
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
            ORDER BY (data_length + index_length) DESC");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return ['error' => $e->getMessage()];
    }
}

function backupDatabase() {
    global $db_host, $db_name, $db_user, $db_password;

    $backup_dir = 'backups/';
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }

    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;

    // Use mysqldump if available
    $command = "mysqldump -h{$db_host} -u{$db_user} -p{$db_password} {$db_name} > {$filepath}";

    $output = [];
    $return_code = 0;
    exec($command, $output, $return_code);

    if ($return_code === 0 && file_exists($filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    } else {
        return ['success' => false, 'message' => 'mysqldump command failed'];
    }
}

function optimizeTables() {
    global $pdo;

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $pdo->exec("OPTIMIZE TABLE `{$table}`");
    }
}

$db_status = getDatabaseStatus();
$table_info = getTableInfo();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت پایگاه داده - Mirza Pro</title>
    <link rel="stylesheet" href="webpanel/assets/css/style.css">
    <style>
        .db-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container th,
        .table-container td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }

        .table-container th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .migration-status {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'webpanel/includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="topbar">
                <h1>مدیریت پایگاه داده</h1>
            </div>

            <div class="content-area">
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <strong>موفق:</strong> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <strong>خطا:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Database Statistics -->
                <div class="db-stats">
                    <div class="stat-card">
                        <h3>حجم پایگاه داده</h3>
                        <div class="value"><?php echo $db_status['size'] ?? 'N/A'; ?> MB</div>
                    </div>
                    <div class="stat-card">
                        <h3>تعداد جداول</h3>
                        <div class="value"><?php echo $db_status['tables'] ?? 'N/A'; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>نسخه فعلی</h3>
                        <div class="value"><?php echo $db_status['version'] ?? 'N/A'; ?></div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="run_migrations">
                        <button type="submit" class="btn btn-primary">اجرای Migration ها</button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="backup_database">
                        <button type="submit" class="btn btn-success">پشتیبان‌گیری</button>
                    </form>

                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="optimize_tables">
                        <button type="submit" class="btn btn-warning">بهینه‌سازی جداول</button>
                    </form>
                </div>

                <!-- Migration Status -->
                <div class="migration-status">
                    <h2>وضعیت Migration ها</h2>
                    <p>نسخه فعلی پایگاه داده: <strong><?php echo $db_status['version'] ?? 'نامشخص'; ?></strong></p>
                    <p>Migration ها به صورت خودکار اجرا می‌شوند. در صورت نیاز می‌توانید آن‌ها را مجدداً اجرا کنید.</p>
                </div>

                <!-- Table Information -->
                <div class="table-container">
                    <h2 style="padding: 20px; margin: 0; background: #f8f9fa;">اطلاعات جداول</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>نام جدول</th>
                                <th>تعداد رکوردها</th>
                                <th>حجم (MB)</th>
                                <th>Collation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($table_info['error'])): ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; color: #dc3545;">
                                        خطا در دریافت اطلاعات: <?php echo htmlspecialchars($table_info['error']); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($table_info as $table): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table['table_name']); ?></td>
                                        <td><?php echo number_format($table['table_rows']); ?></td>
                                        <td><?php echo $table['Size in MB']; ?></td>
                                        <td><?php echo htmlspecialchars($table['table_collation']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
