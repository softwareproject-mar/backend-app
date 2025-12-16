<?php

// Simple API Test using file_get_contents

$baseUrl = 'http://127.0.0.1:8000/api';

echo "=== Simple API Test ===\n\n";

// Test 1: Register
echo "1. Testing Register...\n";
$registerData = json_encode([
    'name' => 'Test User',
    'email' => 'testuser' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
]);

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nAccept: application/json\r\n",
        'content' => $registerData,
        'ignore_errors' => true,
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($baseUrl . '/auth/register', false, $context);

if ($result === false) {
    echo "❌ Cannot connect to server. Make sure Laravel server is running on port 8000.\n";
    echo "   Run: php artisan serve\n\n";
    exit(1);
}

$response = json_decode($result, true);
echo "Response: " . substr($result, 0, 200) . "...\n\n";

if (isset($response['token'])) {
    echo "✅ Register SUCCESS\n";
    $token = $response['token'];
    echo "Token: " . substr($token, 0, 30) . "...\n\n";
} else {
    echo "❌ Register FAILED\n";
    echo "Full response: " . $result . "\n\n";
    exit(1);
}

// Test 2: Access protected endpoint WITH token
echo "2. Testing Protected Endpoint (with token)...\n";
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n",
        'ignore_errors' => true,
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($baseUrl . '/auth/me', false, $context);
$response = json_decode($result, true);

if (isset($response['data']['id'])) {
    echo "✅ Protected endpoint ACCESS SUCCESS\n";
    echo "User: " . $response['data']['name'] . " (" . $response['data']['email'] . ")\n\n";
} else {
    echo "❌ Protected endpoint ACCESS FAILED\n\n";
}

// Test 3: Access protected endpoint WITHOUT token
echo "3. Testing Protected Endpoint (without token - should fail)...\n";
$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Accept: application/json\r\n",
        'ignore_errors' => true,
    ],
];

$context = stream_context_create($options);
$result = @file_get_contents($baseUrl . '/anggota', false, $context);

if (strpos($http_response_header[0], '401') !== false) {
    echo "✅ Correctly REJECTED (401 Unauthorized)\n\n";
} else {
    echo "❌ Should reject but got: " . $http_response_header[0] . "\n\n";
}

// Test 4: Test multiple GET endpoints
echo "4. Testing All Resource Endpoints...\n";
$endpoints = ['anggota', 'kel-sah', 'data-lo', 'data-ao', 'realisasi', 'data-trs', 'target', 'data-jlh-keluarga'];

$options = [
    'http' => [
        'method' => 'GET',
        'header' => "Authorization: Bearer $token\r\nAccept: application/json\r\n",
        'ignore_errors' => true,
    ],
];

foreach ($endpoints as $endpoint) {
    $context = stream_context_create($options);
    $result = @file_get_contents($baseUrl . '/' . $endpoint, false, $context);
    $response = json_decode($result, true);
    
    if (isset($response['data'])) {
        $count = is_array($response['data']) ? count($response['data']) : 0;
        echo "   ✅ /{$endpoint} - {$count} records\n";
    } else {
        echo "   ❌ /{$endpoint} - Failed\n";
    }
}

echo "\n=== Test Complete ===\n";
