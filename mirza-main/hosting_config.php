<?php
/**
 * Mirza Pro - Hosting Configuration Helper
 * Automatically detects hosting environment and configures settings
 */

class HostingConfig {
    private $config = [];
    private $hosting_type = 'unknown';

    public function __construct() {
        $this->detectHosting();
        $this->generateConfig();
    }

    private function detectHosting() {
        $server_software = $_SERVER['SERVER_SOFTWARE'] ?? '';
        $document_root = $_SERVER['DOCUMENT_ROOT'] ?? '';
        $server_name = $_SERVER['SERVER_NAME'] ?? '';

        // Detect hosting type
        if (strpos($server_software, 'Apache') !== false) {
            $this->hosting_type = 'apache';
        } elseif (strpos($server_software, 'nginx') !== false) {
            $this->hosting_type = 'nginx';
        } elseif (strpos($server_name, 'cpanel') !== false || strpos($document_root, 'public_html') !== false) {
            $this->hosting_type = 'cpanel';
        } elseif (strpos($server_name, 'plesk') !== false) {
            $this->hosting_type = 'plesk';
        }

        // Detect shared hosting
        if (strpos($document_root, 'public_html') !== false ||
            strpos($document_root, 'www') !== false ||
            strpos($document_root, 'htdocs') !== false) {
            $this->hosting_type = 'shared';
        }
    }

    private function generateConfig() {
        $this->config = [
            'hosting_type' => $this->hosting_type,
            'php_version' => PHP_VERSION,
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => $this->getRequiredExtensions(),
            'permissions' => $this->checkPermissions(),
            'recommendations' => $this->getRecommendations()
        ];
    }

    private function getRequiredExtensions() {
        $required = ['pdo', 'pdo_mysql', 'curl', 'json', 'openssl', 'gd', 'mbstring'];
        $available = [];

        foreach ($required as $ext) {
            $available[$ext] = extension_loaded($ext);
        }

        return $available;
    }

    private function checkPermissions() {
        $paths = [
            '.',
            'webpanel/',
            'logs/',
            'backups/',
            'webpanel/assets/'
        ];

        $permissions = [];
        foreach ($paths as $path) {
            if (file_exists($path)) {
                $permissions[$path] = [
                    'readable' => is_readable($path),
                    'writable' => is_writable($path),
                    'executable' => is_executable($path)
                ];
            }
        }

        return $permissions;
    }

    private function getRecommendations() {
        $recommendations = [];

        switch ($this->hosting_type) {
            case 'shared':
                $recommendations[] = 'استفاده از هاست اشتراکی - تنظیمات محدود';
                $recommendations[] = 'اطمینان از فعال بودن PHP 7.4+';
                $recommendations[] = 'بررسی دسترسی به MySQL/MariaDB';
                break;

            case 'cpanel':
                $recommendations[] = 'استفاده از cPanel - دسترسی کامل';
                $recommendations[] = 'تنظیم PHP از طریق cPanel';
                $recommendations[] = 'ایجاد پایگاه داده از طریق MySQL Databases';
                break;

            case 'plesk':
                $recommendations[] = 'استفاده از Plesk - دسترسی کامل';
                $recommendations[] = 'تنظیم PHP از طریق Plesk';
                $recommendations[] = 'ایجاد پایگاه داده از طریق Databases';
                break;

            default:
                $recommendations[] = 'نوع هاست نامشخص - بررسی دستی تنظیمات';
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $recommendations[] = 'ارتقاء PHP به نسخه 7.4 یا بالاتر';
        }

        // Check memory limit
        $memory_limit = ini_get('memory_limit');
        if ($memory_limit !== '-1' && intval($memory_limit) < 256) {
            $recommendations[] = 'افزایش memory_limit به حداقل 256M';
        }

        return $recommendations;
    }

    public function getConfig() {
        return $this->config;
    }

    public function generateHtaccess() {
        $htaccess_content = "# Mirza Pro - Apache Configuration\n";
        $htaccess_content .= "# Generated for hosting type: " . $this->hosting_type . "\n\n";

        // Basic security
        $htaccess_content .= "# Security Headers\n";
        $htaccess_content .= "<IfModule mod_headers.c>\n";
        $htaccess_content .= "    Header always set X-Frame-Options \"SAMEORIGIN\"\n";
        $htaccess_content .= "    Header always set X-Content-Type-Options \"nosniff\"\n";
        $htaccess_content .= "    Header always set X-XSS-Protection \"1; mode=block\"\n";
        $htaccess_content .= "</IfModule>\n\n";

        // Protect sensitive files
        $htaccess_content .= "# Protect sensitive files\n";
        $htaccess_content .= "<Files \"config.php\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</Files>\n\n";

        $htaccess_content .= "<Files \"*.log\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Deny from all\n";
        $htaccess_content .= "</Files>\n\n";

        // URL rewriting
        $htaccess_content .= "# URL Rewriting\n";
        $htaccess_content .= "<IfModule mod_rewrite.c>\n";
        $htaccess_content .= "    RewriteEngine On\n";
        $htaccess_content .= "    \n";
        $htaccess_content .= "    # Redirect to webpanel if accessing root\n";
        $htaccess_content .= "    RewriteCond %{REQUEST_URI} ^/$\n";
        $htaccess_content .= "    RewriteRule ^(.*)$ /webpanel/ [R=301,L]\n";
        $htaccess_content .= "    \n";
        $htaccess_content .= "    # Handle webpanel routes\n";
        $htaccess_content .= "    RewriteCond %{REQUEST_FILENAME} !-f\n";
        $htaccess_content .= "    RewriteCond %{REQUEST_FILENAME} !-d\n";
        $htaccess_content .= "    RewriteRule ^webpanel/(.*)$ /webpanel/index.php [QSA,L]\n";
        $htaccess_content .= "</IfModule>\n\n";

        // PHP settings
        $htaccess_content .= "# PHP Settings\n";
        $htaccess_content .= "<IfModule mod_php7.c>\n";
        $htaccess_content .= "    php_value memory_limit 512M\n";
        $htaccess_content .= "    php_value max_execution_time 300\n";
        $htaccess_content .= "    php_value upload_max_filesize 50M\n";
        $htaccess_content .= "    php_value post_max_size 50M\n";
        $htaccess_content .= "</IfModule>\n\n";

        $htaccess_content .= "<IfModule mod_php8.c>\n";
        $htaccess_content .= "    php_value memory_limit 512M\n";
        $htaccess_content .= "    php_value max_execution_time 300\n";
        $htaccess_content .= "    php_value upload_max_filesize 50M\n";
        $htaccess_content .= "    php_value post_max_size 50M\n";
        $htaccess_content .= "</IfModule>\n";

        return $htaccess_content;
    }

    public function generateNginxConfig() {
        $nginx_config = "# Mirza Pro - Nginx Configuration\n";
        $nginx_config .= "# Generated for hosting type: " . $this->hosting_type . "\n\n";

        $nginx_config .= "server {\n";
        $nginx_config .= "    listen 80;\n";
        $nginx_config .= "    server_name _;\n";
        $nginx_config .= "    root " . ($_SERVER['DOCUMENT_ROOT'] ?? '/var/www/html') . ";\n";
        $nginx_config .= "    index index.php index.html;\n\n";

        $nginx_config .= "    # Security headers\n";
        $nginx_config .= "    add_header X-Frame-Options \"SAMEORIGIN\" always;\n";
        $nginx_config .= "    add_header X-Content-Type-Options \"nosniff\" always;\n";
        $nginx_config .= "    add_header X-XSS-Protection \"1; mode=block\" always;\n\n";

        $nginx_config .= "    # Protect sensitive files\n";
        $nginx_config .= "    location ~ /\\. {\n";
        $nginx_config .= "        deny all;\n";
        $nginx_config .= "    }\n\n";

        $nginx_config .= "    location ~ \\.log$ {\n";
        $nginx_config .= "        deny all;\n";
        $nginx_config .= "    }\n\n";

        $nginx_config .= "    location ~ config\\.php$ {\n";
        $nginx_config .= "        deny all;\n";
        $nginx_config .= "    }\n\n";

        $nginx_config .= "    # Main location\n";
        $nginx_config .= "    location / {\n";
        $nginx_config .= "        try_files \$uri \$uri/ /index.php?\$query_string;\n";
        $nginx_config .= "    }\n\n";

        $nginx_config .= "    # PHP handling\n";
        $nginx_config .= "    location ~ \\.php$ {\n";
        $nginx_config .= "        include snippets/fastcgi-php.conf;\n";
        $nginx_config .= "        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;\n";
        $nginx_config .= "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n";
        $nginx_config .= "        include fastcgi_params;\n";
        $nginx_config .= "    }\n\n";

        $nginx_config .= "    # Web panel\n";
        $nginx_config .= "    location /webpanel {\n";
        $nginx_config .= "        try_files \$uri \$uri/ /webpanel/index.php?\$query_string;\n";
        $nginx_config .= "    }\n";
        $nginx_config .= "}\n";

        return $nginx_config;
    }

    public function saveConfigFiles() {
        $results = [];

        // Save .htaccess for Apache
        if ($this->hosting_type === 'apache' || $this->hosting_type === 'shared' || $this->hosting_type === 'cpanel') {
            $htaccess_content = $this->generateHtaccess();
            if (file_put_contents('.htaccess', $htaccess_content)) {
                $results['htaccess'] = 'success';
            } else {
                $results['htaccess'] = 'failed';
            }
        }

        // Save nginx config
        if ($this->hosting_type === 'nginx') {
            $nginx_content = $this->generateNginxConfig();
            if (file_put_contents('nginx.conf', $nginx_content)) {
                $results['nginx'] = 'success';
            } else {
                $results['nginx'] = 'failed';
            }
        }

        return $results;
    }
}

// Usage example
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $hosting_config = new HostingConfig();
    $config = $hosting_config->getConfig();

    echo "<pre>";
    echo "Hosting Configuration Report\n";
    echo "============================\n\n";
    echo "Hosting Type: " . $config['hosting_type'] . "\n";
    echo "PHP Version: " . $config['php_version'] . "\n";
    echo "Document Root: " . $config['document_root'] . "\n";
    echo "Server Software: " . $config['server_software'] . "\n\n";

    echo "Extensions Status:\n";
    foreach ($config['extensions'] as $ext => $status) {
        echo "  $ext: " . ($status ? '✓' : '✗') . "\n";
    }

    echo "\nRecommendations:\n";
    foreach ($config['recommendations'] as $rec) {
        echo "  • $rec\n";
    }

    echo "</pre>";
}
