<?php
// Load Laravel
require_once 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Test M-Pesa Service
$mpesa = app(\App\Services\MpesaService::class);

echo "=== M-Pesa Configuration Test ===\n\n";

$config = config('services.mpesa');
echo "Base URL: " . ($config['base_url'] ?? 'NOT SET') . "\n";
echo "Consumer Key: " . (isset($config['consumer_key']) ? substr($config['consumer_key'], 0, 10) . "..." : "NOT SET") . "\n";
echo "Consumer Secret: " . (isset($config['consumer_secret']) ? substr($config['consumer_secret'], 0, 10) . "..." : "NOT SET") . "\n";
echo "Shortcode: " . ($config['shortcode'] ?? 'NOT SET') . "\n";
echo "Passkey: " . (isset($config['passkey']) ? substr($config['passkey'], 0, 10) . "..." : "NOT SET") . "\n";
echo "Callback URL: " . ($config['callback_url'] ?? 'NOT SET') . "\n\n";

echo "Is Configured: " . ($mpesa->isConfigured() ? "YES ✓" : "NO ✗") . "\n\n";

// Try to get token
echo "=== Testing Token Generation ===\n";
try {
    $token = $mpesa->accessToken();
    echo "✓ Token generated successfully!\n";
    echo "Token: " . substr($token, 0, 30) . "...\n";
} catch (\Throwable $e) {
    echo "✗ Token generation failed:\n";
    echo "Error: " . $e->getMessage() . "\n";
}

?>
