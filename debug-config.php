<?php
/**
 * Debug Configuration for WordPress Plugin Installer
 * Add these lines to your wp-config.php file if you're experiencing issues
 */

// Enable WordPress debugging
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Increase memory and execution time limits
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
ini_set('max_input_time', 300);

// Increase upload limits
ini_set('upload_max_filesize', '64M');
ini_set('post_max_size', '64M');

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', ABSPATH . 'wp-content/debug.log');

// Filesystem method (if needed)
// define('FS_METHOD', 'direct');

// FTP credentials (if needed for filesystem access)
// define('FTP_HOST', 'your-ftp-host');
// define('FTP_USER', 'your-ftp-username');
// define('FTP_PASS', 'your-ftp-password');
// define('FTP_SSL', false);

/**
 * Common hosting-specific configurations:
 */

// For shared hosting with limited permissions
// define('DISALLOW_FILE_EDIT', false);
// define('DISALLOW_FILE_MODS', false);

// For cPanel hosting
// define('FS_METHOD', 'ftpext');

// For VPS/Dedicated servers
// define('FS_CHMOD_DIR', (0755 & ~ umask()));
// define('FS_CHMOD_FILE', (0644 & ~ umask()));

/**
 * Instructions:
 * 1. Copy the lines you need from this file
 * 2. Add them to your wp-config.php file BEFORE the line: require_once ABSPATH . 'wp-settings.php';
 * 3. Save the file and test the plugin again
 */