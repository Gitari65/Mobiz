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
    
    echo "=== COMPLETING UOM SETUP ===\n\n";
    
    // Insert missing area and count UOMs (skip if name already exists)
    $missingUOMs = [
        // AREA - only if m² doesn't exist
        ['name' => 'Square Centimetre', 'abbreviation' => 'cm²', 'type' => 'area', 'description' => 'Small area measurement'],
        
        // COUNT - skip pc/pcs which should exist, add the rest
        ['name' => 'Dozen', 'abbreviation' => 'dz', 'type' => 'count', 'description' => 'Set of 12 items'],
        ['name' => 'Box', 'abbreviation' => 'box', 'type' => 'count', 'description' => 'Boxed items'],
        ['name' => 'Carton', 'abbreviation' => 'ctn', 'type' => 'count', 'description' => 'Carton packaging'],
        ['name' => 'Pack', 'abbreviation' => 'pack', 'type' => 'count', 'description' => 'Package of items'],
        ['name' => 'Packet', 'abbreviation' => 'pkt', 'type' => 'count', 'description' => 'Small packet'],
        ['name' => 'Bottle', 'abbreviation' => 'bottle', 'type' => 'count', 'description' => 'Bottled product'],
        ['name' => 'Can', 'abbreviation' => 'can', 'type' => 'count', 'description' => 'Canned product'],
        ['name' => 'Jar', 'abbreviation' => 'jar', 'type' => 'count', 'description' => 'Jar packaging'],
        ['name' => 'Bundle', 'abbreviation' => 'bundle', 'type' => 'count', 'description' => 'Bundled items'],
        ['name' => 'Pair', 'abbreviation' => 'pair', 'type' => 'count', 'description' => 'Set of 2 items'],
        ['name' => 'Set', 'abbreviation' => 'set', 'type' => 'count', 'description' => 'Set of items'],
    ];
    
    $inserted = 0;
    $skipped = 0;
    
    foreach ($missingUOMs as $uom) {
        $check = $mysqli->query("SELECT id FROM u_o_m_s WHERE abbreviation = '" . $mysqli->real_escape_string($uom['abbreviation']) . "'");
        
        if ($check->num_rows === 0) {
            $stmt = $mysqli->prepare("
                INSERT INTO u_o_m_s (name, abbreviation, description, is_system, type, created_at, updated_at)
                VALUES (?, ?, ?, 1, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("ssss", 
                $uom['name'],
                $uom['abbreviation'],
                $uom['description'],
                $uom['type']
            );
            
            if ($stmt->execute()) {
                echo "✓ Inserted: " . $uom['abbreviation'] . "\n";
                $inserted++;
            } else {
                echo "✗ Failed: " . $uom['abbreviation'] . " - " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            $skipped++;
        }
    }
    
    echo "\nInserted: $inserted, Skipped: $skipped\n";
    
    // Clear and recreate conversions
    echo "\n=== REFRESHING CONVERSION FACTORS ===\n";
    $mysqli->query("DELETE FROM uom_conversions");
    echo "✓ Cleared old conversions\n";
    
    // Get all UOMs for reference
    $uoms = [];
    $result = $mysqli->query("SELECT id, abbreviation FROM u_o_m_s");
    while ($row = $result->fetch_assoc()) {
        $uoms[$row['abbreviation']] = $row['id'];
    }
    
    echo "\nAvailable UOMs: " . implode(", ", array_keys($uoms)) . "\n\n";
    
    $conversions = [
        // VOLUME
        ['from' => 'ml', 'to' => 'L', 'factor' => 0.001],
        ['from' => 'L', 'to' => 'ml', 'factor' => 1000],
        ['from' => '250ml', 'to' => 'ml', 'factor' => 250],
        ['from' => 'ml', 'to' => '250ml', 'factor' => 0.004],
        ['from' => '500ml', 'to' => 'ml', 'factor' => 500],
        ['from' => 'ml', 'to' => '500ml', 'factor' => 0.002],
        ['from' => '750ml', 'to' => 'ml', 'factor' => 750],
        ['from' => 'ml', 'to' => '750ml', 'factor' => 0.001333],
        ['from' => 'dl', 'to' => 'ml', 'factor' => 100],
        ['from' => 'ml', 'to' => 'dl', 'factor' => 0.01],
        
        // WEIGHT
        ['from' => 'mg', 'to' => 'g', 'factor' => 0.001],
        ['from' => 'g', 'to' => 'mg', 'factor' => 1000],
        ['from' => 'g', 'to' => 'kg', 'factor' => 0.001],
        ['from' => 'kg', 'to' => 'g', 'factor' => 1000],
        ['from' => '250g', 'to' => 'g', 'factor' => 250],
        ['from' => 'g', 'to' => '250g', 'factor' => 0.004],
        ['from' => '500g', 'to' => 'g', 'factor' => 500],
        ['from' => 'g', 'to' => '500g', 'factor' => 0.002],
        ['from' => 'ton', 'to' => 'kg', 'factor' => 1000],
        ['from' => 'kg', 'to' => 'ton', 'factor' => 0.001],
        
        // LENGTH
        ['from' => 'mm', 'to' => 'cm', 'factor' => 0.1],
        ['from' => 'cm', 'to' => 'mm', 'factor' => 10],
        ['from' => 'cm', 'to' => 'm', 'factor' => 0.01],
        ['from' => 'm', 'to' => 'cm', 'factor' => 100],
        ['from' => 'm', 'to' => 'km', 'factor' => 0.001],
        ['from' => 'km', 'to' => 'm', 'factor' => 1000],
        
        // AREA
        ['from' => 'cm²', 'to' => 'm²', 'factor' => 0.0001],
        ['from' => 'm²', 'to' => 'cm²', 'factor' => 10000],
    ];
    
    $convCreated = 0;
    $convSkipped = 0;
    
    foreach ($conversions as $conv) {
        if (isset($uoms[$conv['from']]) && isset($uoms[$conv['to']])) {
            $stmt = $mysqli->prepare("
                INSERT INTO uom_conversions (from_uom_id, to_uom_id, conversion_factor, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("iid", $uoms[$conv['from']], $uoms[$conv['to']], $conv['factor']);
            
            if ($stmt->execute()) {
                $convCreated++;
            } else {
                echo "✗ Failed to insert conversion: " . $conv['from'] . " → " . $conv['to'] . "\n";
            }
            $stmt->close();
        } else {
            $convSkipped++;
            if (!isset($uoms[$conv['from']]) || !isset($uoms[$conv['to']])) {
                echo "⊘ Skipped conversion (UOM missing): " . $conv['from'] . " → " . $conv['to'] . "\n";
            }
        }
    }
    
    echo "\n=== FINAL STATISTICS ===\n";
    $uomCount = $mysqli->query("SELECT COUNT(*) as cnt FROM u_o_m_s")->fetch_assoc()['cnt'];
    $convCount = $mysqli->query("SELECT COUNT(*) as cnt FROM uom_conversions")->fetch_assoc()['cnt'];
    
    echo "✓ Total UOMs: $uomCount\n";
    echo "✓ Total Conversion Factors: $convCount\n";
    echo "✓ UOM Conversion System: READY\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}