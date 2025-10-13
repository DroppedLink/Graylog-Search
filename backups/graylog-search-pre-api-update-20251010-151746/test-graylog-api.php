<?php
/**
 * Graylog API Diagnostic Script
 * Run this standalone to test Graylog API connectivity
 * 
 * Usage: php test-graylog-api.php
 */

// Configuration - UPDATE THESE VALUES
$api_url = 'http://logs:9000';  // Your Graylog URL (without /api)
$api_token = 'YOUR_API_TOKEN_HERE';  // Your API token
$username = 'admin';  // Your Graylog username (if using username/password)

// Test query
$query = '*';  // Match all
$time_range = 3600;  // Last hour
$limit = 10;

echo "=== Graylog API Diagnostic Tool ===\n\n";

// Test 1: Universal/Relative endpoint (OLD)
echo "Test 1: /api/search/universal/relative (Current Plugin Method)\n";
echo str_repeat('-', 60) . "\n";

$endpoint1 = rtrim($api_url, '/') . '/api/search/universal/relative';
$url1 = $endpoint1 . '?' . http_build_query([
    'query' => $query,
    'range' => $time_range,
    'limit' => $limit,
    'sort' => 'timestamp:desc'
]);

echo "URL: $url1\n";
echo "Auth: Token format (token:token)\n\n";

$ch1 = curl_init($url1);
curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($api_token . ':token'),
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true
]);

$response1 = curl_exec($ch1);
$status1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
$error1 = curl_error($ch1);
curl_close($ch1);

echo "Status Code: $status1\n";
if ($error1) {
    echo "cURL Error: $error1\n";
}

if ($status1 == 200) {
    echo "✓ SUCCESS\n";
    $data1 = json_decode($response1, true);
    echo "Messages returned: " . count($data1['messages'] ?? []) . "\n";
} else {
    echo "✗ FAILED\n";
    echo "Response: " . substr($response1, 0, 500) . "\n";
}

echo "\n\n";

// Test 2: Messages endpoint with token auth (NEW)
echo "Test 2: /api/search/messages with Token Auth\n";
echo str_repeat('-', 60) . "\n";

$endpoint2 = rtrim($api_url, '/') . '/api/search/messages';
$url2 = $endpoint2 . '?' . http_build_query([
    'query' => $query,
    'fields' => 'timestamp,source,message'
]);

echo "URL: $url2\n";
echo "Auth: Token format (token:token)\n\n";

$ch2 = curl_init($url2);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($api_token . ':token'),
        'Accept: application/json',
        'X-Requested-By: cli'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response2 = curl_exec($ch2);
$status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2 = curl_error($ch2);
curl_close($ch2);

echo "Status Code: $status2\n";
if ($error2) {
    echo "cURL Error: $error2\n";
}

if ($status2 == 200) {
    echo "✓ SUCCESS\n";
    $data2 = json_decode($response2, true);
    echo "Messages returned: " . (isset($data2['datarows']) ? count($data2['datarows']) : 'N/A') . "\n";
} else {
    echo "✗ FAILED\n";
    echo "Response: " . substr($response2, 0, 500) . "\n";
}

echo "\n\n";

// Test 3: Messages endpoint with username/password (NEW - Alternative)
echo "Test 3: /api/search/messages with Username/Password Auth\n";
echo str_repeat('-', 60) . "\n";

$endpoint3 = rtrim($api_url, '/') . '/api/search/messages';
$url3 = $endpoint3 . '?' . http_build_query([
    'query' => $query,
    'fields' => 'timestamp,source,message'
]);

echo "URL: $url3\n";
echo "Auth: Username/Password format\n\n";

$ch3 = curl_init($url3);
curl_setopt_array($ch3, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($username . ':' . $api_token),
        'Accept: application/json',
        'X-Requested-By: cli'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response3 = curl_exec($ch3);
$status3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
$error3 = curl_error($ch3);
curl_close($ch3);

echo "Status Code: $status3\n";
if ($error3) {
    echo "cURL Error: $error3\n";
}

if ($status3 == 200) {
    echo "✓ SUCCESS\n";
    $data3 = json_decode($response3, true);
    echo "Messages returned: " . (isset($data3['datarows']) ? count($data3['datarows']) : 'N/A') . "\n";
} else {
    echo "✗ FAILED\n";
    echo "Response: " . substr($response3, 0, 500) . "\n";
}

echo "\n\n";

// Test 4: POST to messages endpoint (RECOMMENDED for Graylog 6.1)
echo "Test 4: POST /api/search/messages with JSON body (Graylog 6.1 Recommended)\n";
echo str_repeat('-', 60) . "\n";

$endpoint4 = rtrim($api_url, '/') . '/api/search/messages';
$postData = json_encode([
    'query' => $query,
    'fields' => ['timestamp', 'source', 'message'],
    'size' => $limit,
    'timerange' => [
        'type' => 'relative',
        'range' => $time_range
    ],
    'sort' => 'timestamp',
    'sort_order' => 'desc'
]);

echo "URL: $endpoint4\n";
echo "Method: POST\n";
echo "Auth: Token format\n";
echo "Body: $postData\n\n";

$ch4 = curl_init($endpoint4);
curl_setopt_array($ch4, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . base64_encode($api_token . ':token'),
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Requested-By: cli'
    ],
    CURLOPT_TIMEOUT => 30
]);

$response4 = curl_exec($ch4);
$status4 = curl_getinfo($ch4, CURLINFO_HTTP_CODE);
$error4 = curl_error($ch4);
curl_close($ch4);

echo "Status Code: $status4\n";
if ($error4) {
    echo "cURL Error: $error4\n";
}

if ($status4 == 200) {
    echo "✓ SUCCESS\n";
    $data4 = json_decode($response4, true);
    echo "Messages returned: " . (isset($data4['datarows']) ? count($data4['datarows']) : 'N/A') . "\n";
} else {
    echo "✗ FAILED\n";
    echo "Response: " . substr($response4, 0, 500) . "\n";
}

echo "\n\n";
echo "=== Summary ===\n";
echo "Test 1 (universal/relative + token:token): " . ($status1 == 200 ? '✓ PASS' : '✗ FAIL') . "\n";
echo "Test 2 (messages GET + token:token): " . ($status2 == 200 ? '✓ PASS' : '✗ FAIL') . "\n";
echo "Test 3 (messages GET + username:token): " . ($status3 == 200 ? '✓ PASS' : '✗ FAIL') . "\n";
echo "Test 4 (messages POST + JSON): " . ($status4 == 200 ? '✓ PASS' : '✗ FAIL') . "\n";

echo "\n";
echo "Recommendation:\n";
if ($status1 == 200) {
    echo "- Current plugin method (Test 1) still works. No immediate changes needed.\n";
} elseif ($status2 == 200 || $status3 == 200) {
    echo "- Update plugin to use /api/search/messages endpoint.\n";
} elseif ($status4 == 200) {
    echo "- Update plugin to use POST /api/search/messages with JSON body.\n";
} else {
    echo "- All tests failed. Check:\n";
    echo "  1. Graylog URL is correct\n";
    echo "  2. API token is valid\n";
    echo "  3. User has necessary permissions\n";
    echo "  4. Graylog service is running\n";
}

