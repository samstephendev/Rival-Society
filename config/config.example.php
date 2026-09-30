<?php
// Configuration Template for Rival Society
// Copy this file to config.local.php and update with your local environment settings.

return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'root',
        'name' => 'shop_db',
    ],
    'app' => [
        // Leave empty to auto-detect (subdirectory locally, '' at domain root).
        // Set explicitly only when auto-detect cannot see SCRIPT_NAME (rare).
        'base_url' => '',
    ],
];
