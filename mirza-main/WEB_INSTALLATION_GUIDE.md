# Mirza Pro - Web-based Installation System

## Overview

Mirza Pro now includes a complete web-based installation and management system that works with any hosting provider, eliminating the need for terminal access.

## Features

### 🌐 Web-based Installation
- **Complete GUI Installation**: No terminal access required
- **Step-by-step Wizard**: Guided installation process
- **System Requirements Check**: Automatic validation
- **Database Setup**: Automated table creation and migration
- **Bot Configuration**: Easy Telegram bot setup

### 🗄️ Database Management
- **Migration System**: Version-controlled database updates
- **Automatic Setup**: Creates all required tables
- **Backup System**: One-click database backups
- **Optimization**: Database maintenance tools
- **Web Interface**: Manage database through web panel

### 🏠 Hosting Compatibility
- **Auto-detection**: Detects hosting type automatically
- **Shared Hosting**: Optimized for cPanel/Plesk
- **VPS/Dedicated**: Works with Apache/Nginx
- **Configuration Files**: Auto-generates .htaccess/nginx.conf
- **PHP Settings**: Optimizes PHP configuration

## Installation Guide

### Method 1: Web Installation (Recommended)

1. **Upload Files**
   ```bash
   # Upload all files to your web directory
   # Example: /public_html/ or /www/
   ```

2. **Access Installation**
   ```
   http://yourdomain.com/install.php
   ```

3. **Follow the Wizard**
   - Step 1: System requirements check
   - Step 2: Database configuration
   - Step 3: Database setup
   - Step 4: Bot configuration
   - Step 5: Final setup

4. **Complete Setup**
   - Access web panel: `http://yourdomain.com/webpanel/`
   - Login with admin credentials
   - Configure your bot settings

### Method 2: Manual Database Setup

If you prefer to set up the database manually:

1. **Create Database**
   ```sql
   CREATE DATABASE mirza_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Run Database Setup**
   ```php
   // Access: http://yourdomain.com/table.php
   // This will create all required tables
   ```

3. **Configure Bot**
   ```php
   // Edit config.php with your database and bot details
   ```

## File Structure

```
mirza_pro/
├── install.php              # Web installation wizard
├── table.php                # Database setup and migrations
├── hosting_config.php       # Hosting environment detection
├── migration_runner.php     # Migration management tool
├── webpanel/
│   ├── database_manager.php # Database management interface
│   └── ...
├── config.php              # Main configuration (auto-generated)
└── backups/                # Database backups directory
```

## Database Migration System

### Automatic Migrations
The system automatically runs migrations during installation and updates:

```php
// Migrations are version-controlled
'1.0.0' => 'initial_setup',
'1.1.0' => 'add_webpanel_tables',
'1.2.0' => 'add_lottery_system',
'1.3.0' => 'add_notification_system',
'1.4.0' => 'add_backup_system'
```

### Manual Migration Control
```bash
# Check migration status
php migration_runner.php status

# Run pending migrations
php migration_runner.php run

# Reset migrations (DANGEROUS)
php migration_runner.php reset
```

## Hosting Configuration

### Supported Hosting Types
- **Shared Hosting**: cPanel, Plesk, DirectAdmin
- **VPS/Dedicated**: Apache, Nginx
- **Cloud Hosting**: AWS, DigitalOcean, etc.

### Auto-generated Files
- `.htaccess` - Apache configuration
- `nginx.conf` - Nginx configuration
- `config.php` - Main application config

### PHP Requirements
- PHP 7.4 or higher
- PDO MySQL extension
- cURL extension
- JSON extension
- OpenSSL extension
- GD extension
- MBString extension

## Web Panel Features

### Database Management
- View database statistics
- Run migrations
- Create backups
- Optimize tables
- Monitor performance

### System Monitoring
- Hosting type detection
- PHP configuration check
- Extension status
- Permission validation

## Troubleshooting

### Common Issues

1. **Permission Errors**
   ```bash
   # Ensure web directory is writable
   chmod 755 /path/to/mirza_pro
   chmod 775 /path/to/mirza_pro/webpanel
   chmod 775 /path/to/mirza_pro/backups
   ```

2. **Database Connection Failed**
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists

3. **Migration Errors**
   - Check database permissions
   - Verify PDO MySQL extension
   - Review error logs

### Error Logs
- Installation logs: Check browser console
- Database errors: `error_log` files
- PHP errors: Server error logs

## Security Features

### File Protection
- `config.php` - Protected from direct access
- Log files - Denied access
- Backup files - Secure storage

### Security Headers
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection: 1; mode=block

## API Integration

### Webhook Setup
```php
// Automatic webhook configuration
telegram('setwebhook', [
    'url' => "https://{$domainhosts}/index.php"
]);
```

### Database API
```php
// Access database manager via API
GET /webpanel/database_manager.php?action=status
POST /webpanel/database_manager.php (with action parameter)
```

## Migration from Terminal Installation

If you have an existing terminal-based installation:

1. **Backup Current Installation**
   ```bash
   cp -r /var/www/mirza_pro /var/www/mirza_pro_backup
   ```

2. **Update Files**
   ```bash
   # Replace with new web-based files
   # Keep your existing config.php and database
   ```

3. **Run Migrations**
   ```bash
   php migration_runner.php run
   ```

4. **Verify Installation**
   - Check web panel access
   - Verify bot functionality
   - Test database operations

## Support

### Documentation
- Web Panel Guide: `WEBPANEL_COMPLETE_GUIDE.md`
- Bot Integration: `WEBPANEL_BOT_INTEGRATION_GUIDE.md`
- Installation Guide: This file

### Getting Help
- Check system requirements
- Review error logs
- Verify hosting compatibility
- Test database connectivity

## Changelog

### Version 5.10.77
- ✅ Web-based installation system
- ✅ Database migration system
- ✅ Hosting auto-detection
- ✅ Web panel database management
- ✅ Automatic configuration generation
- ✅ Security enhancements
- ✅ Backup system integration

---

**Note**: This web-based installation system replaces the need for terminal access and provides a complete GUI-based setup experience suitable for any hosting environment.
