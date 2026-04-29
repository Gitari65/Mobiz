<?php
require __DIR__ . '/bootstrap/app.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$plans = \App\Models\Plan::all();
echo "\n=== Subscription Plans ===\n";
foreach ($plans as $plan) {
    $featureCount = count($plan->features ?? []);
    echo "\n✓ Plan: {$plan->name} (\${$plan->price}/month)\n";
    echo "  Status: " . ($plan->is_active ? 'Active' : 'Inactive') . "\n";
    echo "  Features ($featureCount): " . implode(', ', $plan->features ?? []) . "\n";
}
echo "\n";
