<?php
// public_html/pages/dashboard/index.php
// User dashboard with Vault analytics

require_once __DIR__ . '/../../classes/MainClass.php';
require_once __DIR__ . '/../../models/BaseModel.php';

$mainClass = new MainClass();

// Check authentication
if (!$mainClass->isAuthenticated()) {
    $_SESSION['intended_url'] = '/dashboard';
    header('Location: /auth/login');
    exit;
}

$currentUser = $mainClass->getCurrentUser();
$user = new User();
$userGame = new UserGame();
$apiKey = new ApiKey();
$purchase = new Purchase();

// Get user data
$userGames = $user->getGames($currentUser['id']);
$apiKeys = $apiKey->getUserKeys($currentUser['id']);
$purchases = $user->getPurchases($currentUser['id']);

// Get Vault-enabled games with recent stats
$vaultGames = [];
$vaultStats = new VaultStats();

foreach ($userGames as $game) {
    if ($game['vault_enabled']) {
        $recentStats = $vaultStats->getRecentStats($game['id'], 1); // Last hour
        $game['recent_stats'] = $recentStats;
        $game['current_players'] = !empty($recentStats) ? end($recentStats)['player_count'] : 0;
        $game['performance'] = !empty($recentStats) ? round(array_sum(array_column($recentStats, 'performance_ms')) / count($recentStats), 2) : 0;
        $vaultGames[] = $game;
    }
}

$pageTitle = 'Dashboard';
$pageDescription = 'Manage your Roblox games, view Vault analytics, and access your BluFox Studio account.';
$pageKeywords = 'BluFox Studio dashboard, Vault analytics, Roblox game management, DataStore statistics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include __DIR__ . '/../../components/global/head.php'; ?>
    <?php echo generateSEOTags($pageTitle, $pageDescription, $pageKeywords); ?>
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="dashboard-page">
    <?php include __DIR__ . '/../../components/global/header.php'; ?>
    
    <main class="dashboard">
        <div class="container">
            <div class="dashboard-header">
                <div class="user-welcome">
                    <img src="<?php echo htmlspecialchars($currentUser['avatar_url'] ?? '/assets/images/default-avatar.png'); ?>" 
                         alt="<?php echo htmlspecialchars($currentUser['username']); ?>" 
                         class="user-avatar-large">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($currentUser['display_name'] ?? $currentUser['username']); ?>!</h1>
                        <p>Last login: <?php echo $currentUser['last_login'] ? date('M j, Y \a\t g:i A', strtotime($currentUser['last_login'])) : 'First time'; ?></p>
                    </div>
                </div>
                
                <div class="quick-actions">
                    <a href="/products/vault-datastore-system" class="btn btn-primary">
                        <i class="icon-plus"></i> Add New Game
                    </a>
                    <button class="btn btn-secondary" onclick="generateApiKey()">
                        <i class="icon-key"></i> Generate API Key
                    </button>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="icon-gamepad"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo count($userGames); ?></span>
                        <span class="stat-label">Games Connected</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="icon-database"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo count($vaultGames); ?></span>
                        <span class="stat-label">Vault Enabled</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="icon-users"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo array_sum(array_column($vaultGames, 'current_players')); ?></span>
                        <span class="stat-label">Active Players</span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="icon-key"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number"><?php echo count(array_filter($apiKeys, fn($k) => $k['is_active'])); ?></span>
                        <span class="stat-label">Active API Keys</span>
                    </div>
                </div>
            </div>
            
            <!-- Main Dashboard Content -->
            <div class="dashboard-grid">
                
                <!-- Vault Games Section -->
                <section class="dashboard-section vault-games">
                    <div class="section-header">
                        <h2>Vault Analytics</h2>
                        <button class="btn btn-outline btn-small" onclick="refreshVaultData()">
                            <i class="icon-refresh"></i> Refresh
                        </button>
                    </div>
                    
                    <?php if (empty($vaultGames)): ?>
                        <div class="empty-state">
                            <i class="icon-database"></i>
                            <h3>No Vault Games Yet</h3>
                            <p>Add your first Roblox game and enable Vault to see real-time analytics here.</p>
                            <a href="/products/vault-datastore-system" class="btn btn-primary">Get Started with Vault</a>
                        </div>
                    <?php else: ?>
                        <div class="vault-games-grid">
                            <?php foreach ($vaultGames as $game): ?>
                            <div class="vault-game-card" data-game-id="<?php echo $game['id']; ?>">
                                <div class="game-header">
                                    <div class="game-info">
                                        <?php if ($game['game_icon']): ?>
                                            <img src="<?php echo htmlspecialchars($game['game_icon']); ?>" alt="<?php echo htmlspecialchars($game['game_name']); ?>" class="game-icon">
                                        <?php endif; ?>
                                        <div>
                                            <h3><?php echo htmlspecialchars($game['game_name']); ?></h3>
                                            <p>Game ID: <?php echo $game['game_id']; ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="game-status">
                                        <span class="status-indicator <?php echo $game['current_players'] > 0 ? 'online' : 'offline'; ?>"></span>
                                        <span><?php echo $game['current_players'] > 0 ? 'Online' : 'Offline'; ?></span>
                                    </div>
                                </div>
                                
                                <div class="game-metrics">
                                    <div class="metric">
                                        <span class="metric-value"><?php echo number_format($game['current_players']); ?></span>
                                        <span class="metric-label">Players</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-value"><?php echo $game['performance']; ?>ms</span>
                                        <span class="metric-label">Avg Response</span>
                                    </div>
                                    <div class="metric">
                                        <span class="metric-value">
                                            <?php echo !empty($game['recent_stats']) ? count($game['recent_stats']) : 0; ?>
                                        </span>
                                        <span class="metric-label">Data Points</span>
                                    </div>
                                </div>
                                
                                <div class="game-actions">
                                    <a href="/dashboard/game/<?php echo $game['id']; ?>" class="btn btn-primary btn-small">
                                        View Details
                                    </a>
                                    <button class="btn btn-secondary btn-small" onclick="viewLicenseKey('<?php echo $game['vault_license_key']; ?>')">
                                        License Key
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Recent Activity -->
                <section class="dashboard-section recent-activity">
                    <div class="section-header">
                        <h2>Recent Activity</h2>
                    </div>
                    
                    <div class="activity-list">
                        <?php 
                        // Get recent activity (this would be from a proper activity log in production)
                        $recentActivity = [];
                        
                        // Add purchase activity
                        foreach ($purchases as $purchaseItem) {
                            $recentActivity[] = [
                                'type' => 'purchase',
                                'message' => 'Purchased ' . $purchaseItem['product_name'],
                                'date' => $purchaseItem['purchased_at'],
                                'icon' => 'shopping-bag'
                            ];
                        }
                        
                        // Add game activity
                        foreach ($userGames as $game) {
                            if ($game['is_verified']) {
                                $recentActivity[] = [
                                    'type' => 'game',
                                    'message' => 'Verified game: ' . $game['game_name'],
                                    'date' => $game['updated_at'],
                                    'icon' => 'check-circle'
                                ];
                            }
                        }
                        
                        // Sort by date
                        usort($recentActivity, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                        $recentActivity = array_slice($recentActivity, 0, 10);
                        ?>
                        
                        <?php if (empty($recentActivity)): ?>
                            <div class="empty-state">
                                <p>No recent activity to show.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="icon-<?php echo $activity['icon']; ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <p><?php echo htmlspecialchars($activity['message']); ?></p>
                                    <span class="activity-date"><?php echo date('M j, g:i A', strtotime($activity['date'])); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="dashboard-section api-keys">
                    <div class="section-header">
                        <h2>API Keys</h2>
                        <button class="btn btn-primary btn-small" onclick="showGenerateKeyModal()">
                            <i class="icon-plus"></i> Generate Key
                        </button>
                    </div>

                    <?php if (empty($apiKeys)): ?>
                        <div class="empty-state">
                            <i class="icon-key"></i>
                            <h3>No API Keys</h3>
                            <p>Generate an API key to start using the Vault system programmatically.</p>
                        </div>
                    <?php else: ?>
                        <div class="api-keys-list">
                            <?php foreach ($apiKeys as $key): ?>
                                <div class="api-key-item">
                                    <div class="key-info">
                                        <h4><?php echo htmlspecialchars($key['key_name']); ?></h4>
                                        <p>Created: <?php echo date('M j, Y', strtotime($key['created_at'])); ?></p>
                                        <p>Last used: <?php echo $key['last_used'] ? date('M j, Y', strtotime($key['last_used'])) : 'Never'; ?></p>
                                    </div>
                                    
                                    <div class="key-stats">
                                        <span class="requests-count"><?php echo number_format($key['requests_count']); ?> requests</span>
                                        <span class="key-status <?php echo $key['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="key-actions">
                                        <?php if ($key['is_active']): ?>
                                        <button class="btn btn-danger btn-small" onclick="revokeApiKey(<?php echo $key['id']; ?>)">
                                            Revoke
                                        </button>
                                        <?php else: ?>
                                            <span class="text-muted">Revoked</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
                
                <!-- Purchases Section -->
                <section class="dashboard-section purchases">
                    <div class="section-header">
                        <h2>Purchases & Downloads</h2>
                        <a href="/products" class="btn btn-primary btn-small">
                            <i class="icon-shopping-cart"></i> Browse Products
                        </a>
                    </div>
                    
                    <?php if (empty($purchases)): ?>
                        <div class="empty-state">
                            <i class="icon-shopping-bag"></i>
                            <h3>No Purchases Yet</h3>
                            <p>Purchase the Vault DataStore System to unlock powerful features for your Roblox games.</p>
                            <a href="/products/vault-datastore-system" class="btn btn-primary">View Vault System</a>
                        </div>
                    <?php else: ?>
                        <div class="purchases-list">
                            <?php foreach ($purchases as $purchaseItem): ?>
                            <div class="purchase-item">
                                <div class="purchase-info">
                                    <h4><?php echo htmlspecialchars($purchaseItem['product_name']); ?></h4>
                                    <p>Version: <?php echo htmlspecialchars($purchaseItem['version']); ?></p>
                                    <p>Purchased: <?php echo date('M j, Y', strtotime($purchaseItem['purchased_at'])); ?></p>
                                </div>
                                
                                <div class="purchase-status">
                                    <span class="status-badge status-<?php echo $purchaseItem['status']; ?>">
                                        <?php echo ucfirst($purchaseItem['status']); ?>
                                    </span>
                                    <span class="download-count">
                                        <?php echo $purchaseItem['download_count']; ?>/<?php echo $purchaseItem['max_downloads']; ?> downloads
                                    </span>
                                </div>
                                
                                <div class="purchase-actions">
                                    <?php if ($purchaseItem['status'] === 'completed'): ?>
                                        <a href="/download/<?php echo $purchaseItem['id']; ?>" class="btn btn-primary btn-small">
                                            <i class="icon-download"></i> Download
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
            
            <!-- Quick Actions Sidebar -->
            <aside class="dashboard-sidebar">
                <div class="sidebar-section">
                    <h3>Quick Actions</h3>
                    <div class="quick-actions-list">
                        <a href="/products/vault-datastore-system" class="quick-action">
                            <i class="icon-database"></i>
                            <span>Get Vault System</span>
                        </a>
                        <a href="/documentation" class="quick-action">
                            <i class="icon-book"></i>
                            <span>View Documentation</span>
                        </a>
                        <a href="/contact" class="quick-action">
                            <i class="icon-support"></i>
                            <span>Get Support</span>
                        </a>
                        <a href="/profile" class="quick-action">
                            <i class="icon-settings"></i>
                            <span>Account Settings</span>
                        </a>
                    </div>
                </div>
                
                <div class="sidebar-section">
                    <h3>System Status</h3>
                    <div class="status-list">
                        <div class="status-item">
                            <span class="status-indicator online"></span>
                            <span>API Status: Online</span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator online"></span>
                            <span>Vault System: Operational</span>
                        </div>
                        <div class="status-item">
                            <span class="status-indicator online"></span>
                            <span>Dashboard: Online</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </main>
    
    <!-- Modals -->
    
    <!-- Generate API Key Modal -->
    <div id="generate-api-key-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Generate API Key</h3>
                <button class="modal-close" onclick="closeModal('generate-api-key-modal')">&times;</button>
            </div>
            
            <div class="modal-body">
                <form id="generate-key-form">
                    <div class="form-group">
                        <label for="key-name">Key Name</label>
                        <input type="text" id="key-name" name="name" required placeholder="e.g., Production Key">
                    </div>
                    
                    <div class="form-group">
                        <label>Permissions</label>
                        <div class="checkbox-group">
                            <label>
                                <input type="checkbox" name="permissions[]" value="vault:read" checked>
                                Vault Read Access
                            </label>
                            <label>
                                <input type="checkbox" name="permissions[]" value="vault:write" checked>
                                Vault Write Access
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal('generate-api-key-modal')">Cancel</button>
                <button class="btn btn-primary" onclick="submitGenerateKey()">Generate Key</button>
            </div>
        </div>
    </div>
    
    <!-- License Key Modal -->
    <div id="license-key-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Vault License Key</h3>
                <button class="modal-close" onclick="closeModal('license-key-modal')">&times;</button>
            </div>
            
            <div class="modal-body">
                <p>Use this license key in your Roblox game to connect to the Vault system:</p>
                <div class="license-key-display">
                    <input type="text" id="license-key-text" readonly class="license-key-input">
                    <button class="btn btn-secondary btn-small" onclick="copyLicenseKey()">
                        <i class="icon-copy"></i> Copy
                    </button>
                </div>
                <div class="license-info">
                    <h4>How to use:</h4>
                    <ol>
                        <li>Copy the license key above</li>
                        <li>In your Roblox game, configure Vault with this key</li>
                        <li>Vault will automatically connect to your dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../../components/global/footer.php'; ?>
    
    <script>
        // Dashboard JavaScript functionality
        
        function showGenerateKeyModal() {
            document.getElementById('generate-api-key-modal').style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function viewLicenseKey(licenseKey) {
            document.getElementById('license-key-text').value = licenseKey;
            document.getElementById('license-key-modal').style.display = 'block';
        }
        
        function copyLicenseKey() {
            const input = document.getElementById('license-key-text');
            input.select();
            document.execCommand('copy');
            
            // Show feedback
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<i class="icon-check"></i> Copied!';
            setTimeout(() => {
                button.innerHTML = originalText;
            }, 2000);
        }
        
        function submitGenerateKey() {
            const form = document.getElementById('generate-key-form');
            const formData = new FormData(form);
            
            const data = {
                name: formData.get('name'),
                permissions: formData.getAll('permissions[]')
            };
            
            fetch('/api/v1/auth/generate-key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('API Key generated successfully! Key: ' + data.key + '\n\nPlease copy this key now, you won\'t see it again.');
                    closeModal('generate-api-key-modal');
                    location.reload(); // Refresh to show new key
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to generate API key');
            });
        }
        
        function revokeApiKey(keyId) {
            if (!confirm('Are you sure you want to revoke this API key? This action cannot be undone.')) {
                return;
            }
            
            fetch('/api/v1/auth/revoke-key', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ key_id: keyId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    alert('API key revoked successfully');
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to revoke API key');
            });
        }
        
        function refreshVaultData() {
            const button = event.target;
            button.disabled = true;
            button.innerHTML = '<i class="icon-spinner"></i> Refreshing...';
            
            // Refresh vault data for all games
            const gameCards = document.querySelectorAll('.vault-game-card');
            let completed = 0;
            
            gameCards.forEach(card => {
                const gameId = card.getAttribute('data-game-id');
                
                fetch(`/api/v1/user/stats/${gameId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.error) {
                            updateGameCard(card, data);
                        }
                        completed++;
                        if (completed === gameCards.length) {
                            button.disabled = false;
                            button.innerHTML = '<i class="icon-refresh"></i> Refresh';
                        }
                    })
                    .catch(error => {
                        console.error('Error refreshing game data:', error);
                        completed++;
                        if (completed === gameCards.length) {
                            button.disabled = false;
                            button.innerHTML = '<i class="icon-refresh"></i> Refresh';
                        }
                    });
            });
            
            // If no games, just reset button
            if (gameCards.length === 0) {
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="icon-refresh"></i> Refresh';
                }, 1000);
            }
        }
        
        function updateGameCard(card, data) {
            // Update player count
            const playerCount = card.querySelector('.metric-value');
            if (playerCount && data.recent_stats && data.recent_stats.length > 0) {
                const latest = data.recent_stats[data.recent_stats.length - 1];
                playerCount.textContent = latest.player_count || 0;
            }
        }
        
        // Auto-refresh vault data every 5 minutes
        setInterval(function() {
            const refreshButton = document.querySelector('[onclick="refreshVaultData()"]');
            if (refreshButton && !refreshButton.disabled) {
                refreshVaultData();
            }
        }, 5 * 60 * 1000);
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };
    </script>
</body>
</html>