<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (!is_authenticated()) {
    redirect('/?logged_out=1');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        redirect('/', 403);
    }
    
    try {
        logout();
        redirect('/?logged_out=1');
    } catch (Exception $e) {
        if (DEBUG_MODE) {
            error_log("Logout Error: " . $e->getMessage());
        }
        redirect('/');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $csrf_token = generate_csrf_token();
    $page_title = "Logout - BluFox Studio";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo escape_html($page_title); ?></title>
        <link rel="stylesheet" href="/assets/css/global.css">
        <link rel="stylesheet" href="/assets/css/components.css">
    </head>
    <body>
        <div class="logout-container">
            <div class="logout-card">
                <svg class="logout-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                    <polyline points="16 17 21 12 16 7" />
                    <path d="M21 12H9" />
                </svg>
                
                <h1 class="logout-title">Confirm Logout</h1>
                <p class="logout-message">
                    Are you sure you want to sign out of your BluFox Studio account?
                </p>
                
                <div class="logout-actions">
                    <form method="POST" style="flex: 1;">
                        <input type="hidden" name="csrf_token" value="<?php echo escape_html($csrf_token); ?>">
                        <button type="submit" class="btn btn-danger" style="width: 100%;">
                            Yes, Sign Out
                        </button>
                    </form>
                    <a href="/dashboard" class="btn btn-secondary" style="flex: 1;">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
        
        <script>
            let autoLogoutTimer = setTimeout(() => {
                if (confirm('Auto-logout in 3 seconds. Continue?')) {
                    document.querySelector('form').submit();
                }
            }, 10000); 
            
            document.addEventListener('click', () => {
                clearTimeout(autoLogoutTimer);
            });
        </script>
    </body>
    </html>
    <?php
} else {
    redirect('/', 405);
}