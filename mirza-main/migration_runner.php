<?php
/**
 * Mirza Pro - Migration Runner
 * Command-line and web-based migration execution
 */

// Check if running from command line
$is_cli = php_sapi_name() === 'cli';

if ($is_cli) {
    // Command line usage
    echo "Mirza Pro - Migration Runner\n";
    echo "============================\n\n";

    if ($argc < 2) {
        echo "Usage: php migration_runner.php <action>\n";
        echo "Actions:\n";
        echo "  run     - Run all pending migrations\n";
        echo "  status  - Show migration status\n";
        echo "  reset   - Reset migration table (DANGEROUS)\n";
        exit(1);
    }

    $action = $argv[1];
} else {
    // Web interface
    session_start();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: webpanel/login.php');
        exit;
    }

    $action = $_GET['action'] ?? 'status';
}

// Load configuration
if (file_exists('config.php')) {
    require_once 'config.php';
} else {
    if ($is_cli) {
        echo "Error: config.php not found. Please run installation first.\n";
        exit(1);
    } else {
        die('Configuration file not found. Please complete installation first.');
    }
}

// Load migration system
require_once 'table.php';

class MigrationRunner {
    private $pdo;
    private $connect;

    public function __construct($pdo, $connect = null) {
        $this->pdo = $pdo;
        $this->connect = $connect;
    }

    public function runMigrations() {
        try {
            $migrator = new DatabaseMigrator($this->pdo, $this->connect);
            $migrator->runMigrations();

            return [
                'success' => true,
                'message' => 'Migrations completed successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage()
            ];
        }
    }

    public function getStatus() {
        try {
            $migrator = new DatabaseMigrator($this->pdo, $this->connect);
            $current_version = $migrator->getCurrentVersion();

            // Get all migrations
            $stmt = $this->pdo->query("SELECT * FROM migrations ORDER BY executed_at DESC");
            $migrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'current_version' => $current_version,
                'migrations' => $migrations
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to get status: ' . $e->getMessage()
            ];
        }
    }

    public function resetMigrations() {
        try {
            $this->pdo->exec("DROP TABLE IF EXISTS migrations");
            return [
                'success' => true,
                'message' => 'Migration table reset successfully'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Reset failed: ' . $e->getMessage()
            ];
        }
    }
}

// Initialize runner
$runner = new MigrationRunner($pdo, $connect ?? null);

// Execute action
switch ($action) {
    case 'run':
        $result = $runner->runMigrations();
        if ($is_cli) {
            echo $result['success'] ? "✓ " : "✗ ";
            echo $result['message'] . "\n";
        } else {
            echo json_encode($result);
        }
        break;

    case 'status':
        $result = $runner->getStatus();
        if ($is_cli) {
            if ($result['success']) {
                echo "Current Version: " . $result['current_version'] . "\n\n";
                echo "Migration History:\n";
                echo "==================\n";
                foreach ($result['migrations'] as $migration) {
                    echo sprintf("%-20s %-30s %s\n",
                        $migration['version'],
                        $migration['migration_name'],
                        $migration['executed_at']
                    );
                }
            } else {
                echo "✗ " . $result['message'] . "\n";
            }
        } else {
            echo json_encode($result);
        }
        break;

    case 'reset':
        if ($is_cli) {
            echo "WARNING: This will reset the migration table!\n";
            echo "Are you sure? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            fclose($handle);

            if (trim($line) === 'yes') {
                $result = $runner->resetMigrations();
                echo $result['success'] ? "✓ " : "✗ ";
                echo $result['message'] . "\n";
            } else {
                echo "Reset cancelled.\n";
            }
        } else {
            $result = $runner->resetMigrations();
            echo json_encode($result);
        }
        break;

    default:
        if ($is_cli) {
            echo "Unknown action: $action\n";
            exit(1);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
}
