<?php

// Test Script untuk API Endpoints

$baseUrl = 'http://127.0.0.1:8000/api';

echo "=== Testing Authentication & Resource Endpoints ===\n\n";

// 1. Test Register
echo "1. Testing REGISTER...\n";
$registerData = [
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123',
];

$ch = curl_init($baseUrl . '/auth/register');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registerData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$registerResponse = curl_exec($ch);
$registerData = json_decode($registerResponse, true);
curl_close($ch);

if (isset($registerData['token'])) {
    echo "✅ Register successful!\n";
    echo "   Token: " . substr($registerData['token'], 0, 20) . "...\n";
    echo "   User: " . $registerData['user']['name'] . " (" . $registerData['user']['email'] . ")\n\n";
    $token = $registerData['token'];
} else {
    echo "❌ Register failed!\n";
    echo "   Response: " . $registerResponse . "\n\n";
    exit(1);
}

// 2. Test Login
echo "2. Testing LOGIN...\n";
$loginData = [
    'email' => $registerData['user']['email'],
    'password' => 'password123',
];

$ch = curl_init($baseUrl . '/auth/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
$loginResponse = curl_exec($ch);
$loginData = json_decode($loginResponse, true);
curl_close($ch);

if (isset($loginData['token'])) {
    echo "✅ Login successful!\n";
    echo "   Token: " . substr($loginData['token'], 0, 20) . "...\n\n";
    $token = $loginData['token'];
} else {
    echo "❌ Login failed!\n";
    echo "   Response: " . $loginResponse . "\n\n";
}

// 3. Test GET /auth/me
echo "3. Testing GET /auth/me (with token)...\n";
$ch = curl_init($baseUrl . '/auth/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$meResponse = curl_exec($ch);
$meData = json_decode($meResponse, true);
curl_close($ch);

if (isset($meData['data']['id'])) {
    echo "✅ /auth/me successful!\n";
    echo "   User ID: " . $meData['data']['id'] . "\n";
    echo "   Name: " . $meData['data']['name'] . "\n\n";
} else {
    echo "❌ /auth/me failed!\n";
    echo "   Response: " . $meResponse . "\n\n";
}

// 4. Test Protected Endpoints
$endpoints = [
    'data-kunjungan',
    'anggota',
    'kel-sah',
    'data-lo',
    'data-ao',
    'realisasi',
    'data-trs',
    'target',
    'data-jlh-keluarga',
];

echo "4. Testing PROTECTED ENDPOINTS (GET list)...\n";
foreach ($endpoints as $endpoint) {
    $ch = curl_init($baseUrl . '/' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json',
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        $count = isset($data['data']) ? count($data['data']) : 0;
        echo "   ✅ GET /{$endpoint} - {$httpCode} - {$count} records\n";
    } else {
        echo "   ❌ GET /{$endpoint} - {$httpCode}\n";
    }
}

// 5. Test without token (should fail)
echo "\n5. Testing ENDPOINT WITHOUT TOKEN (should fail)...\n";
$ch = curl_init($baseUrl . '/anggota');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "✅ Correctly rejected (401 Unauthorized)\n\n";
} else {
    echo "❌ Should return 401 but got {$httpCode}\n\n";
}

// 6. Test Logout
echo "6. Testing LOGOUT...\n";
$ch = curl_init($baseUrl . '/auth/logout');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$logoutResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    echo "✅ Logout successful!\n";
    $logoutData = json_decode($logoutResponse, true);
    echo "   Message: " . ($logoutData['message'] ?? 'N/A') . "\n\n";
} else {
    echo "❌ Logout failed! HTTP Code: {$httpCode}\n\n";
}

// 7. Test using revoked token (should fail)
echo "7. Testing WITH REVOKED TOKEN (should fail)...\n";
$ch = curl_init($baseUrl . '/auth/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "✅ Correctly rejected revoked token (401 Unauthorized)\n\n";
} else {
    echo "❌ Should return 401 but got {$httpCode}\n\n";
}

echo "=== All Tests Completed ===\n";
