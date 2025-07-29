<?php
// BluFox Studio - Test Required Functions
// Create this as test-functions.php in your root directory

require_once 'includes/config.php';

echo "<h1>BluFox Studio - Function Test</h1>";

// Test 1: Check if Auth class exists and methods work
echo "<h2>Auth Class Test</h2>";
if (class_exists('Auth')) {
    echo "✅ Auth class exists<br>";
    
    if (method_exists('Auth', 'check')) {
        echo "✅ Auth::check() method exists<br>";
    } else {
        echo "❌ Auth::check() method missing<br>";
    }
    
    if (method_exists('Auth', 'login')) {
        echo "✅ Auth::login() method exists<br>";
    } else {
        echo "❌ Auth::login() method missing<br>";
    }
    
    if (method_exists('Auth', 'handleRobloxUser')) {
        echo "✅ Auth::handleRobloxUser() method exists<br>";
    } else {
        echo "❌ Auth::handleRobloxUser() method missing<br>";
    }
} else {
    echo "❌ Auth class not found<br>";
}

// Test 2: Check helper functions
echo "<h2>Helper Functions Test</h2>";
if (function_exists('redirect')) {
    echo "✅ redirect() function exists<br>";
} else {
    echo "❌ redirect() function missing<br>";
}

if (function_exists('logActivity')) {
    echo "✅ logActivity() function exists<br>";
} else {
    echo "❌ logActivity() function missing<br>";
}

if (function_exists('generateCSRFToken')) {
    echo "✅ generateCSRFToken() function exists<br>";
} else {
    echo "❌ generateCSRFToken() function missing<br>";
}

// Test 3: Database connection
echo "<h2>Database Test</h2>";
try {
    if (function_exists('db')) {
        $database = db();
        echo "✅ db() function exists<br>";
        
        // Test a simple query
        $result = $database->fetch("SELECT 1 as test");
        if ($result && $result['test'] == 1) {
            echo "✅ Database connection working<br>";
        } else {
            echo "❌ Database query failed<br>";
        }
    } else {
        echo "❌ db() function missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test 4: Check if users table exists
echo "<h2>Users Table Test</h2>";
try {
    $database = db();
    $tables = $database->fetchAll("SHOW TABLES LIKE 'users'");
    if (count($tables) > 0) {
        echo "✅ Users table exists<br>";
        
        // Check table structure
        $columns = $database->fetchAll("DESCRIBE users");
        $requiredColumns = ['id', 'roblox_id', 'username', 'display_name', 'role'];
        
        foreach ($requiredColumns as $col) {
            $found = false;
            foreach ($columns as $column) {
                if ($column['Field'] === $col) {
                    $found = true;
                    break;
                }
            }
            if ($found) {
                echo "✅ Column '$col' exists<br>";
            } else {
                echo "❌ Column '$col' missing<br>";
            }
        }
    } else {
        echo "❌ Users table not found<br>";
    }
} catch (Exception $e) {
    echo "❌ Users table check error: " . $e->getMessage() . "<br>";
}

// Test 5: OAuth Configuration
echo "<h2>OAuth Configuration Test</h2>";
echo "ROBLOX_CLIENT_ID: " . (ROBLOX_CLIENT_ID ? "✅ Set" : "❌ Empty") . "<br>";
echo "ROBLOX_CLIENT_SECRET: " . (ROBLOX_CLIENT_SECRET ? "✅ Set" : "❌ Empty") . "<br>";
echo "ROBLOX_REDIRECT_URI: " . ROBLOX_REDIRECT_URI . "<br>";

// Test 6: Session functionality
echo "<h2>Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "❌ Session not active<br>";
}

// Test 7: cURL availability
echo "<h2>cURL Test</h2>";
if (extension_loaded('curl')) {
    echo "✅ cURL extension loaded<br>";
    
    // Test basic cURL functionality
    $ch = curl_init();
    if ($ch) {
        echo "✅ cURL init successful<br>";
        curl_close($ch);
    } else {
        echo "❌ cURL init failed<br>";
    }
} else {
    echo "❌ cURL extension not loaded<br>";
}

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Fix any ❌ issues shown above</li>";
echo "<li>Replace your auth/callback.php with the debug version</li>";
echo "<li>Try logging in again and check your error logs</li>";
echo "</ol>";
?>