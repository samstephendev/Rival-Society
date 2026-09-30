<?php
// Razorpay Credentials and SDK Initializer Helper

function get_razorpay_config(): array {
    static $keys = null;
    if ($keys !== null) {
        return $keys;
    }

    $keys = [
        'key_id'         => getenv('RAZORPAY_KEY_ID') ?: '',
        'key_secret'     => getenv('RAZORPAY_KEY_SECRET') ?: '',
        'webhook_secret' => getenv('RAZORPAY_WEBHOOK_SECRET') ?: '',
    ];

    $envPath = __DIR__ . '/../payment/key.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");
                if ($k === 'RAZORPAY_KEY_ID') {
                    $keys['key_id'] = $v;
                } elseif ($k === 'RAZORPAY_KEY_SECRET') {
                    $keys['key_secret'] = $v;
                } elseif ($k === 'RAZORPAY_WEBHOOK_SECRET') {
                    $keys['webhook_secret'] = $v;
                }
            }
        }
    }

    return $keys;
}
