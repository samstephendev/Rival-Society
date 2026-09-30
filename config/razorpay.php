<?php
// Razorpay Credentials and SDK Initializer Helper

function load_env_file(string $path): array {
    $values = [];
    if (!file_exists($path)) {
        return $values;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($key !== '') {
            $values[$key] = $value;
        }
    }

    return $values;
}

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

    $envPaths = [
        __DIR__ . '/../.env',
        __DIR__ . '/../payment/key.env',
    ];

    foreach ($envPaths as $envPath) {
        $envValues = load_env_file($envPath);
        foreach ($envValues as $k => $v) {
            if ($k === 'RAZORPAY_KEY_ID' && empty($keys['key_id'])) {
                $keys['key_id'] = $v;
            } elseif ($k === 'RAZORPAY_KEY_SECRET' && empty($keys['key_secret'])) {
                $keys['key_secret'] = $v;
            } elseif ($k === 'RAZORPAY_WEBHOOK_SECRET' && empty($keys['webhook_secret'])) {
                $keys['webhook_secret'] = $v;
            }
        }
    }

    return $keys;
}
