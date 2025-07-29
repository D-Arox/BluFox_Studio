<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/includes/config.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug OAuth Credentials - BluFox Studio</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: #000;
            padding: 30px;
            border: 2px solid #00ff00;
            border-radius: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #00ff00;
            padding-bottom: 20px;
        }
        .credential-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #333;
            border-radius: 5px;
            background: #111;
        }
        .credential-name {
            font-weight: bold;
            color: #00ffff;
            width: 250px;
        }
        .credential-value {
            flex: 1;
            margin: 0 20px;
            padding: 10px;
            background: #222;
            border-radius: 3px;
            word-break: break-all;
            font-size: 12px;
        }
        .credential-status {
            width: 100px;
            text-align: center;
            font-weight: bold;
        }
        .status-ok {
            color: #00ff00;
        }
        .status-missing {
            color: #ff0000;
        }
        .status-empty {
            color: #ffff00;
        }
        .section {
            margin: 30px 0;
            padding: 20px;
            border: 1px solid #333;
            border-radius: 5px;
        }
        .section-title {
            color: #ff00ff;
            font-size: 18px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }
        .env-file-content {
            background: #111;
            border: 1px solid #333;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .missing-item {
            color: #ff0000;
            background: #330000;
        }
        .present-item {
            color: #00ff00;
            background: #003300;
        }
        .warning {
            background: #330;
            border: 1px solid #ff0;
            color: #ff0;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .file-info {
            color: #888;
            font-size: 11px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 OAUTH CREDENTIALS DEBUG</h1>
            <p>Analyzing your BluFox Studio OAuth configuration...</p>
            <div class="warning">
                ⚠️ WARNING: This page shows sensitive configuration data. DELETE THIS FILE after debugging!
            </div>
        </div>

        <div class="section">
            <div class="section-title">📋 Environment Variables Status</div>
            
            <?php
            // Check all OAuth-related environment variables
            $env_vars = [
                'ROBLOX_CLIENT_ID' => [
                    'current' => ROBLOX_CLIENT_ID,
                    'expected' => '6692844983306448575',
                    'description' => 'Your Roblox OAuth App Client ID'
                ],
                'ROBLOX_CLIENT_SECRET' => [
                    'current' => ROBLOX_CLIENT_SECRET,
                    'expected' => '[SECRET FROM ROBLOX]',
                    'description' => 'Your Roblox OAuth App Client Secret'
                ],
                'ROBLOX_REDIRECT_URI' => [
                    'current' => ROBLOX_REDIRECT_URI,
                    'expected' => 'https://blufox-studio.com/auth/callback',
                    'description' => 'OAuth callback URL'
                ],
                'JWT_SECRET' => [
                    'current' => JWT_SECRET,
                    'expected' => '[RANDOM 64-CHAR STRING]',
                    'description' => 'JWT token signing secret'
                ],
                'SITE_URL' => [
                    'current' => SITE_URL,
                    'expected' => 'https://blufox-studio.com',
                    'description' => 'Your website URL'
                ]
            ];

            foreach ($env_vars as $var_name => $info) {
                $current = $info['current'];
                $is_secret = in_array($var_name, ['ROBLOX_CLIENT_SECRET', 'JWT_SECRET']);
                
                // Determine status
                if (empty($current)) {
                    $status = 'MISSING';
                    $status_class = 'status-missing';
                    $row_class = 'missing-item';
                } elseif ($current === $info['expected']) {
                    $status = 'CORRECT';
                    $status_class = 'status-ok';
                    $row_class = 'present-item';
                } else {
                    $status = 'SET';
                    $status_class = 'status-ok';
                    $row_class = 'present-item';
                }
                
                // Display value (hide secrets partially)
                if ($is_secret && !empty($current)) {
                    $display_value = substr($current, 0, 8) . '...' . substr($current, -4) . ' (Length: ' . strlen($current) . ')';
                } elseif (empty($current)) {
                    $display_value = '[NOT SET]';
                } else {
                    $display_value = $current;
                }
                
                echo '<div class="credential-row ' . $row_class . '">';
                echo '<div class="credential-name">' . $var_name . '</div>';
                echo '<div class="credential-value">' . htmlspecialchars($display_value) . '<br><small>' . $info['description'] . '</small></div>';
                echo '<div class="credential-status ' . $status_class . '">' . $status . '</div>';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <div class="section-title">📁 Environment File Analysis</div>
            
            <?php
            // Check for .env file
            $env_paths = [
                __DIR__ . '/.env',
                __DIR__ . '/../.env',
                __DIR__ . '/../../.env',
                __DIR__ . '/../../../.env'
            ];
            
            $env_file_found = false;
            $env_file_path = '';
            
            foreach ($env_paths as $path) {
                if (file_exists($path)) {
                    $env_file_found = true;
                    $env_file_path = $path;
                    break;
                }
            }
            
            if ($env_file_found) {
                echo '<div class="present-item" style="padding: 15px; margin: 10px 0;">';
                echo '✅ .env file found at: ' . htmlspecialchars($env_file_path);
                echo '</div>';
                
                // Show .env file content with sensitive data hidden
                $env_content = file_get_contents($env_file_path);
                $lines = explode("\n", $env_content);
                $display_content = '';
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) {
                        $display_content .= $line . "\n";
                        continue;
                    }
                    
                    if (strpos($line, '=') !== false) {
                        list($key, $value) = explode('=', $line, 2);
                        $key = trim($key);
                        $value = trim($value, '"\'');
                        
                        if (in_array($key, ['ROBLOX_CLIENT_SECRET', 'JWT_SECRET', 'DB_PASSWORD'])) {
                            if (empty($value)) {
                                $display_content .= $key . '="" # ❌ EMPTY - NEEDS VALUE' . "\n";
                            } else {
                                $display_content .= $key . '="***HIDDEN***" # ✅ SET' . "\n";
                            }
                        } else {
                            if (empty($value)) {
                                $display_content .= $key . '="" # ❌ EMPTY' . "\n";
                            } else {
                                $display_content .= $key . '="' . $value . '" # ✅ SET' . "\n";
                            }
                        }
                    } else {
                        $display_content .= $line . "\n";
                    }
                }
                
                echo '<div class="env-file-content">' . htmlspecialchars($display_content) . '</div>';
            } else {
                echo '<div class="missing-item" style="padding: 15px; margin: 10px 0;">';
                echo '❌ .env file NOT FOUND in any of these locations:';
                foreach ($env_paths as $path) {
                    echo '<br>   • ' . htmlspecialchars($path);
                }
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <div class="section-title">🔧 What You Need To Do</div>
            
            <?php
            $missing_vars = [];
            $issues = [];
            
            if (empty(ROBLOX_CLIENT_ID)) {
                $missing_vars[] = 'ROBLOX_CLIENT_ID';
                $issues[] = 'Set ROBLOX_CLIENT_ID to: 6692844983306448575';
            }
            
            if (empty(ROBLOX_CLIENT_SECRET)) {
                $missing_vars[] = 'ROBLOX_CLIENT_SECRET';
                $issues[] = 'Get ROBLOX_CLIENT_SECRET from Roblox Creator Hub (https://create.roblox.com/credentials)';
            }
            
            if (empty(ROBLOX_REDIRECT_URI)) {
                $missing_vars[] = 'ROBLOX_REDIRECT_URI';
                $issues[] = 'Set ROBLOX_REDIRECT_URI to: https://blufox-studio.com/auth/callback';
            }
            
            if (empty(JWT_SECRET)) {
                $missing_vars[] = 'JWT_SECRET';
                $issues[] = 'Generate a random JWT_SECRET (64+ characters)';
            }
            
            if (!empty($missing_vars)) {
                echo '<div class="missing-item" style="padding: 15px; margin: 10px 0;">';
                echo '<strong>❌ MISSING VARIABLES:</strong><br>';
                foreach ($missing_vars as $var) {
                    echo '   • ' . $var . '<br>';
                }
                echo '</div>';
                
                echo '<div style="padding: 15px; background: #111; border: 1px solid #333; margin: 10px 0;">';
                echo '<strong>🔨 ACTION ITEMS:</strong><br>';
                foreach ($issues as $i => $issue) {
                    echo ($i + 1) . '. ' . $issue . '<br>';
                }
                echo '</div>';
            } else {
                echo '<div class="present-item" style="padding: 15px; margin: 10px 0;">';
                echo '✅ All required variables are set! OAuth should work now.';
                echo '</div>';
            }
            ?>
        </div>

        <div class="section">
            <div class="section-title">📝 Correct .env File Template</div>
            <div class="env-file-content">
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=srv1969.hstgr.io
DB_PORT=3306
DB_NAME=u721549786_blufox_main
DB_USER=u721549786_blufox_db
DB_PASSWORD="your_actual_database_password"

# Roblox OAuth Configuration
ROBLOX_CLIENT_ID=6692844983306448575
ROBLOX_CLIENT_SECRET="get_this_from_roblox_creator_hub"
ROBLOX_REDIRECT_URI=https://blufox-studio.com/auth/callback

# JWT Configuration
JWT_SECRET="generate_a_random_64_character_string"

# Site Configuration
SITE_URL=https://blufox-studio.com
SITE_NAME=BluFox Studio
CONTACT_EMAIL=support@blufox-studio.com

# Debug Configuration
DEBUG_MODE=true
ERROR_REPORTING=true
            </div>
        </div>

        <div class="file-info">
            Server Path: <?php echo htmlspecialchars(__DIR__); ?><br>
            Current Time: <?php echo date('Y-m-d H:i:s'); ?><br>
            PHP Version: <?php echo PHP_VERSION; ?>
        </div>
    </div>
</body>
</html>