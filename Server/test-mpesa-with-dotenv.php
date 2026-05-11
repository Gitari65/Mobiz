<?php
require "vendor/autoload.php";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$consumer_key = getenv("MPESA_CONSUMER_KEY");
$consumer_secret = getenv("MPESA_CONSUMER_SECRET");
$base_url = getenv("MPESA_BASE_URL");

echo "=== M-Pesa Token Test ===\n";
echo "Base URL: {$base_url}\n";
echo "Consumer Key: " . substr($consumer_key, 0, 10) . "...\n";
echo "Consumer Secret: " . substr($consumer_secret, 0, 10) . "...\n\n";

$credentials = base64_encode($consumer_key . ":" . $consumer_secret);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$base_url}/oauth/v1/generate?grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Basic {$credentials}",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

curl_close($ch);

echo "HTTP Status: {$http_code}\n";
if ($curl_error) {
    echo "CURL Error: {$curl_error}\n";
}
echo "Response:\n";
echo $response . "\n";

$json = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
    if (isset($json["access_token"])) {
        echo "\nToken generated successfully!\n";
        echo "Token: " . substr($json["access_token"], 0, 20) . "...\n";
    } else {
        echo "\nNo access token in response\n";
        print_r($json);
    }
}
?>
