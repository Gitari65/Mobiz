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
    
    echo "=== INSERTING MISSING UOMs ===\n\n";
    
    $defaultUOMs = [
        // WEIGHT - Smaller to Larger
        ['name' => 'Milligram', 'abbreviation' => 'mg', 'type' => 'weight', 'description' => 'Small weight measurement'],
        ['name' => 'Gram', 'abbreviation' => 'g', 'type' => 'weight', 'description' => 'Basic weight unit'],
        ['name' => '250 Gram', 'abbreviation' => '250g', 'type' => 'weight', 'description' => 'Quarter kilogram'],
        ['name' => '500 Gram', 'abbreviation' => '500g', 'type' => 'weight', 'description' => 'Half kilogram'],
        ['name' => 'Kilogram', 'abbreviation' => 'kg', 'type' => 'weight', 'description' => 'Standard weight measurement'],
        ['name' => 'Ton', 'abbreviation' => 'ton', 'type' => 'weight', 'description' => 'Large weight measurement'],

        // LENGTH - Smaller to Larger
        ['name' => 'Millimetre', 'abbreviation' => 'mm', 'type' => 'length', 'description' => 'Small length measurement'],
        ['name' => 'Centimetre', 'abbreviation' => 'cm', 'type' => 'length', 'description' => 'Centimetre measurement'],
        ['name' => 'Metre', 'abbreviation' => 'm', 'type' => 'length', 'description' => 'Standard length measurement'],
        ['name' => 'Metre', 'abbreviation' => 'meter', 'type' => 'length', 'description' => 'Alternative to m'],
        ['name' => 'Kilometre', 'abbreviation' => 'km', 'type' => 'length', 'description' => 'Large distance measurement'],

        // AREA
        ['name' => 'Square Metre', 'abbreviation' => 'm²', 'type' => 'area', 'description' => 'Area measurement'],
        ['name' => 'Square Centimetre', 'abbreviation' => 'cm²', 'type' => 'area', 'description' => 'Small area measurement'],

        // COUNT - Individual Items & Packaging
        ['name' => 'Piece', 'abbreviation' => 'pcs', 'type' => 'count', 'description' => 'Individual item'],
        ['name' => 'Piece', 'abbreviation' => 'pc', 'type' => 'count', 'description' => 'Alternative to pcs'],
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
    
    foreach ($defaultUOMs as $uom) {
        // Check if abbreviation already exists
        $check = $mysqli->query("SELECT id FROM u_o_m_s WHERE abbreviation = '" . $mysqli->real_escape_string($uom['abbreviation']) . "'");
        
        if ($check->num_rows === 0) {
            $stmt = $mysqli->prepare("
                INSERT INTO u_o_m_s (name, abbreviation, description, is_system, type, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->bind_param("sssss", 
                $uom['name'],
                $uom['abbreviation'],
                $uom['description'],
                $is_system,
                $uom['type']
            );
            
            $is_system = 1;
            
            if ($stmt->execute()) {
                echo "✓ Inserted: " . $uom['abbreviation'] . " (" . $uom['type'] . ")\n";
                $inserted++;
            } else {
                echo "✗ Failed: " . $uom['abbreviation'] . " - " . $stmt->error . "\n";
            }
            $stmt->close();
        } else {
            $skipped++;
        }
    }
    
    echo "\n=== RESULT ===\n";
    echo "Inserted: $inserted\n";
    echo "Skipped: $skipped (already exist)\n";
    
    // Get final counts
    $uomCount = $mysqli->query("SELECT COUNT(*) as cnt FROM u_o_m_s")->fetch_assoc()['cnt'];
    echo "Total UOMs now: $uomCount\n";
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}