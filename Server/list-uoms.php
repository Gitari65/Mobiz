<?php

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
    
    echo "=== UNIT OF MEASURES IN DATABASE ===\n\n";
    
    $result = $mysqli->query("SELECT id, name, abbreviation, type FROM u_o_m_s ORDER BY type, abbreviation");
    
    if ($result && $result->num_rows > 0) {
        $currentType = '';
        while ($row = $result->fetch_assoc()) {
            if ($currentType !== $row['type']) {
                echo "\n" . strtoupper($row['type']) . ":\n";
                echo str_repeat("---", 20) . "\n";
                $currentType = $row['type'];
            }
            echo "  " . str_pad($row['abbreviation'], 10) . " | " . $row['name'] . "\n";
        }
    } else {
        echo "No UOMs found in database\n";
    }
    
    echo "\n=== UOM CONVERSION FACTORS ===\n\n";
    
    $result = $mysqli->query("
        SELECT uc.id, f.abbreviation as from_abbr, t.abbreviation as to_abbr, uc.conversion_factor, uc.description
        FROM uom_conversions uc
        JOIN u_o_m_s f ON uc.from_uom_id = f.id
        JOIN u_o_m_s t ON uc.to_uom_id = t.id
        LIMIT 20
    ");
    
    if ($result && $result->num_rows > 0) {
        echo "Showing first 20 conversions:\n";
        echo str_repeat("-", 80) . "\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['from_abbr'] . " → " . $row['to_abbr'] . ": " . $row['conversion_factor'] . " | " . $row['description'] . "\n";
        }
    } else {
        echo "No conversions found in database\n";
    }
    
    echo "\n\n=== SUMMARY ===\n";
    $uomCount = $mysqli->query("SELECT COUNT(*) as cnt FROM u_o_m_s")->fetch_assoc()['cnt'];
    $convCount = $mysqli->query("SELECT COUNT(*) as cnt FROM uom_conversions")->fetch_assoc()['cnt'];
    
    echo "Total UOMs: $uomCount\n";
    echo "Total Conversion Factors: $convCount\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}