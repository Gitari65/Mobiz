#!/bin/bash

# UOM Conversion System - API Testing Script

echo "==================================="
echo "UOM CONVERSION SYSTEM - TEST SUITE"
echo "==================================="
echo ""

# API Base URL
BASE_URL="http://localhost:8000/api"
ADMIN_TOKEN=""
PRODUCT_ID=""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Test 1: Get all UOMs
echo -e "${YELLOW}TEST 1: Get All UOMs${NC}"
echo "GET $BASE_URL/uoms"
echo ""
curl -s "$BASE_URL/uoms" | jq '.' | head -50
echo ""
echo ""

# Test 2: Get UOMs by type (volume)
echo -e "${YELLOW}TEST 2: Get UOMs by Type (Volume)${NC}"
echo "GET $BASE_URL/uoms?type=volume"
echo ""
curl -s "$BASE_URL/uoms?type=volume" | jq '.'
echo ""
echo ""

# Test 3: Get UOMs by type (weight)
echo -e "${YELLOW}TEST 3: Get UOMs by Type (Weight)${NC}"
echo "GET $BASE_URL/uoms?type=weight"
echo ""
curl -s "$BASE_URL/uoms?type=weight" | jq '.'
echo ""
echo ""

# Test 4: Get conversion factors for a specific UOM
echo -e "${YELLOW}TEST 4: List UOM Conversions${NC}"
echo "GET $BASE_URL/uom-conversions (if available)"
echo ""
curl -s "$BASE_URL/uom-conversions?limit=10" 2>/dev/null | jq '.' || echo "Endpoint not yet created"
echo ""
echo ""

# Test 5: Direct Database Check
echo -e "${YELLOW}TEST 5: Database Verification${NC}"
echo "Checking database for UOMs and conversions..."
php -r "
\$mysqli = new mysqli('127.0.0.1', 'root', '54321', 'pos_system');
\$uomCount = \$mysqli->query('SELECT COUNT(*) as cnt FROM u_o_m_s')->fetch_assoc()['cnt'];
\$convCount = \$mysqli->query('SELECT COUNT(*) as cnt FROM uom_conversions')->fetch_assoc()['cnt'];
echo \"Total UOMs: \$uomCount\\n\";
echo \"Total Conversions: \$convCount\\n\";
\$mysqli->close();
"
echo ""
echo ""

# Test 6: Test UOMConversionService in Laravel Tinker
echo -e "${YELLOW}TEST 6: UOMConversionService - Convert 10 kg to grams${NC}"
echo "Running: UOMConversionService::convert(10, 5, 2) assuming kg=5, g=2"
echo ""
php -d display_errors=off artisan tinker --execute="
\$result = \App\Services\UOMConversionService::convert(10, 5, 2);
echo 'Result: ' . \$result . ' grams';
" 2>/dev/null || echo "Direct tinker test - manual verification required"
echo ""
echo ""

echo -e "${GREEN}==================================="
echo "TEST SUITE COMPLETE"
echo "===================================${NC}"
echo ""
echo "Next: Test via API by creating a product with purchase_uom_id"
