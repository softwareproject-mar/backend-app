<?php
/**
 * Activity Log Implementation Test Script
 * 
 * This script tests the activity logging functionality.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Activity Log Implementation Test ===" . PHP_EOL . PHP_EOL;

// Test 1: Check if table exists
echo "1. Checking database table..." . PHP_EOL;
try {
    $tableExists = \DB::select("SHOW TABLES LIKE 'activity_logs'");
    if (count($tableExists) > 0) {
        echo "   ✅ Table 'activity_logs' exists" . PHP_EOL;
    } else {
        echo "   ❌ Table 'activity_logs' NOT found" . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . PHP_EOL;
}

// Test 2: Check Model
echo PHP_EOL . "2. Testing ActivityLog Model..." . PHP_EOL;
try {
    $model = new \App\Models\ActivityLog();
    echo "   ✅ Model instantiated successfully" . PHP_EOL;
    echo "   ✅ Fillable fields: " . implode(', ', $model->getFillable()) . PHP_EOL;
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . PHP_EOL;
}

// Test 3: Check Trait in Services
echo PHP_EOL . "3. Checking Services have LogsActivity trait..." . PHP_EOL;
$services = [
    'AnggotaService',
    'KelSahService',
    'DataKunjunganService',
    'DataLoService',
    'DataAoService',
    'KetuaKsService',
    'SekretarisKsService',
    'DataPenghasilanService',
    'DataPengelolaService',
    'DataJlhKeluargaService',
];

foreach ($services as $serviceName) {
    $className = "App\\Services\\{$serviceName}";
    try {
        $reflection = new \ReflectionClass($className);
        $traits = $reflection->getTraitNames();
        if (in_array('App\\Traits\\LogsActivity', $traits)) {
            echo "   ✅ {$serviceName}" . PHP_EOL;
        } else {
            echo "   ❌ {$serviceName} - LogsActivity trait NOT found" . PHP_EOL;
        }
    } catch (\Exception $e) {
        echo "   ❌ {$serviceName} - Error: " . $e->getMessage() . PHP_EOL;
    }
}

// Test 4: Check Routes
echo PHP_EOL . "4. Checking API routes..." . PHP_EOL;
$routes = \Route::getRoutes();
$activityLogRoutes = 0;
foreach ($routes as $route) {
    if (str_contains($route->uri(), 'activity-logs')) {
        $activityLogRoutes++;
        echo "   ✅ " . $route->methods()[0] . " /api/" . $route->uri() . PHP_EOL;
    }
}
if ($activityLogRoutes === 0) {
    echo "   ❌ No activity-logs routes found" . PHP_EOL;
}

// Test 5: Check Controller
echo PHP_EOL . "5. Testing ActivityLogController..." . PHP_EOL;
try {
    $controller = new \App\Http\Controllers\Api\ActivityLogController();
    echo "   ✅ Controller instantiated successfully" . PHP_EOL;
    
    $reflection = new \ReflectionClass($controller);
    $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
    $methodNames = array_filter(array_map(function($m) {
        return in_array($m->getName(), ['index', 'show']) ? $m->getName() : null;
    }, $methods));
    echo "   ✅ Methods: " . implode(', ', $methodNames) . PHP_EOL;
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . PHP_EOL;
}

// Test 6: Check table structure
echo PHP_EOL . "6. Checking table structure..." . PHP_EOL;
try {
    $columns = \DB::select("DESCRIBE activity_logs");
    echo "   ✅ Table has " . count($columns) . " columns" . PHP_EOL;
    $requiredColumns = ['user_id', 'resource_type', 'action_type', 'status', 'description'];
    foreach ($requiredColumns as $col) {
        $found = false;
        foreach ($columns as $column) {
            if ($column->Field === $col) {
                $found = true;
                break;
            }
        }
        if ($found) {
            echo "   ✅ Column '{$col}' exists" . PHP_EOL;
        } else {
            echo "   ❌ Column '{$col}' NOT found" . PHP_EOL;
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== Test Complete ===" . PHP_EOL;
