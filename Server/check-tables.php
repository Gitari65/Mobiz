<?php

// Simple database check without Laravel bootstrap
$host = '127.0.0.1';
$user = 'root';
$password = '54321';
$database = 'pos_system';

try {
    $mysqli = new mysqli($host, $user, $password, $database);
    
    if ($mysqli->connect_error) {
        echo "Connection failed: " . $mysqli->connect_error . "\n";
        exit(1);
    }
    
    echo "=== DATABASE VERIFICATION ===\n";
    echo "Connection: ✓ OK\n\n";
    
    // Check table counts
    $tables = [
        'u_o_m_s',
        'uom_conversions',
        'products',
        'customers',
        'suppliers'
    ];
    
    echo "Table Status:\n";
    echo "=====================================\n";
    
    foreach ($tables as $table) {
        $result = $mysqli->query("SELECT COUNT(*) as cnt FROM $table");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "$table: " . $row['cnt'] . " records\n";
        } else {
            echo "$table: ✗ TABLE NOT FOUND\n";
        }
    }
    
    echo "\n=== MIGRATION STATUS ===\n";
    $result = $mysqli->query("SELECT COUNT(*) as cnt FROM migrations");
    $row = $result->fetch_assoc();
    echo "Total Migrations Run: " . $row['cnt'] . "\n";
    
    echo "\n=== CONVERSION SYSTEM READY ===\n";
    $uomResult = $mysqli->query("SELECT COUNT(*) as cnt FROM u_o_m_s");
    $convResult = $mysqli->query("SELECT COUNT(*) as cnt FROM uom_conversions");
    
    if ($uomResult && $convResult) {
        $uomRow = $uomResult->fetch_assoc();
        $convRow = $convResult->fetch_assoc();
        echo "✓ Default UOMs: " . $uomRow['cnt'] . " configured\n";
        echo "✓ Conversion Factors: " . $convRow['cnt'] . " set\n";
        echo "✓ UOM Conversion System: READY\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
