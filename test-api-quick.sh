#!/bin/bash

# Backend API Quick Test Script
# Run this after starting your server

BASE_URL="http://localhost/backend-app/public/api"
TOKEN=""

echo "🚀 Backend API Testing Script"
echo "================================"
echo ""

# Test 1: Register
echo "✅ Test 1: Register User"
REGISTER_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }')

echo "Response: $REGISTER_RESPONSE"
echo ""

# Test 2: Login
echo "✅ Test 2: Login"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }')

TOKEN=$(echo $LOGIN_RESPONSE | grep -o '"token":"[^"]*' | sed 's/"token":"//')
echo "Token: $TOKEN"
echo ""

# Test 3: Create Ketua KS
echo "✅ Test 3: Create Ketua KS"
CREATE_KETUA=$(curl -s -X POST "$BASE_URL/ketua-ks" \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
    "ID_KET": "KET001",
    "NO_AGT": "AGT001",
    "NAMA": "Ahmad Test",
    "STAT": "AKTIF",
    "TGL_STAT": "2025-01-01",
    "NO_SK": 123
  }')

echo "Response: $CREATE_KETUA"
echo ""

# Test 4: Get All Ketua KS
echo "✅ Test 4: Get All Ketua KS"
GET_ALL=$(curl -s -X GET "$BASE_URL/ketua-ks?per_page=15" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $GET_ALL"
echo ""

# Test 5: Dashboard
echo "✅ Test 5: Get Dashboard"
DASHBOARD=$(curl -s -X GET "$BASE_URL/dashboard" \
  -H "Authorization: Bearer $TOKEN")

echo "Response: $DASHBOARD"
echo ""

echo "================================"
echo "🎉 Testing Complete!"
echo ""
echo "If all tests passed, your API is working correctly!"
echo "Token for Postman: $TOKEN"
