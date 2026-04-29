#!/usr/bin/env powershell

<#
.SYNOPSIS
    UOM Conversion System - API Test Suite
.DESCRIPTION
    Tests the UOM conversion system via REST API endpoints
#>

Write-Host "=================================" -ForegroundColor Cyan
Write-Host "UOM CONVERSION SYSTEM - API TESTS" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Green
Write-Host ""

$BaseUrl = "http://localhost:8000/api"
$TestResults = @()

# Test 1: Get all UOMs
Write-Host "TEST 1: Get All UOMs" -ForegroundColor Yellow
Write-Host "GET $BaseUrl/uoms" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/uoms" -Method Get -ErrorAction Stop
    $count = ($response | Measure-Object).Count
    Write-Host "✓ Success - Retrieved $count UOMs" -ForegroundColor Green
    
    # Show sample
    $response | Select-Object -First 3 | ForEach-Object {
        Write-Host "  - $($_.abbreviation) ($($_.type)) - $($_.name)"
    }
} catch {
    Write-Host "✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host ""

# Test 2: Get UOMs by Type
Write-Host "TEST 2: Filter UOMs by Type (Weight)" -ForegroundColor Yellow
Write-Host "GET $BaseUrl/uoms?type=weight" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/uoms?type=weight" -Method Get -ErrorAction Stop
    $count = ($response | Measure-Object).Count
    Write-Host "✓ Success - Retrieved $count weight UOMs" -ForegroundColor Green
    
    # Show all
    $response | ForEach-Object {
        Write-Host "  - $($_.abbreviation) - $($_.name)"
    }
} catch {
    Write-Host "✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host ""

# Test 3: Get UOMs by Type (Volume)
Write-Host "TEST 3: Filter UOMs by Type (Volume)" -ForegroundColor Yellow
Write-Host "GET $BaseUrl/uoms?type=volume" -ForegroundColor Gray
Write-Host ""

try {
    $response = Invoke-RestMethod -Uri "$BaseUrl/uoms?type=volume" -Method Get -ErrorAction Stop
    $count = ($response | Measure-Object).Count
    Write-Host "✓ Success - Retrieved $count volume UOMs" -ForegroundColor Green
    
    $response | ForEach-Object {
        Write-Host "  - $($_.abbreviation) - $($_.name)"
    }
} catch {
    Write-Host "✗ Failed: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host ""

Write-Host "=================================" -ForegroundColor Cyan
Write-Host "API TESTS COMPLETE" -ForegroundColor Green
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Next Steps:" -ForegroundColor Yellow
Write-Host "1. Start Laravel dev server: php artisan serve"
Write-Host "2. Run this test again to verify API endpoints"
Write-Host "3. Test creating a product with purchase_uom_id"
Write-Host ""
