<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Login Screen - project/login.php
================================================================
*/

session_start();

// Redirect to dashboard if already authenticated
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Server-side validation
    if (empty($username) || empty($password)) {
        $error_msg = 'Please fill in all credentials.';
    } else {
        // Mock credentials dictionary
        $mock_users = [
            'admin' => ['name' => 'Prof. Balaji A. Chaugule', 'role' => 'Administrator', 'password' => 'admin123'],
            'hod' => ['name' => 'Prof. Balaji A. Chaugule', 'role' => 'Head of Department', 'password' => 'hod123'],
            'faculty' => ['name' => 'Dr. Neeti Rathore', 'role' => 'Faculty Member', 'password' => 'faculty123']
        ];
        
        $user_key = strtolower($username);
        if (array_key_exists($user_key, $mock_users) && $mock_users[$user_key]['password'] === $password) {
            // Authentication successful!
            $_SESSION['user_id'] = $user_key;
            $_SESSION['user_name'] = $mock_users[$user_key]['name'];
            $_SESSION['user_role'] = $mock_users[$user_key]['role'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error_msg = 'Invalid username or password. Try admin/admin123, hod/hod123, or faculty/faculty123.';
        }
    }
}

$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team 2 - Log In</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

    <div class="login-container">
        
        <!-- Left Side: Interactive Portal Branding -->
        <div class="login-left">
            <div class="login-blobs">
                <div class="blob blob-1"></div>
                <div class="blob blob-2"></div>
            </div>
            
            <div class="login-left-content">
                <div class="login-brand">
                    <!-- Academic Logo SVG -->
                    <svg viewBox="0 0 24 24" style="width: 38px; height: 38px; fill: var(--primary);">
                        <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
                    </svg>
                    <span>Team 2</span>
                </div>
                <h1 class="login-tagline">Academic Planning &<br>Monitoring System</h1>
                <p class="login-description">
                    Unlock departmental optimization. Gain access to unified course planning modules, live classroom schedules, conflict-detection engines, and laboratories workload metrics.
                </p>
            </div>
            
            <div class="login-left-footer">
                &copy; 2026 Team 2 Academic Portal. Built for educational efficiency.
            </div>
        </div>
        
        <!-- Right Side: Translucent Login Card -->
        <div class="login-right">
            <div class="login-card glass-card">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Enter university portal credentials to log in</p>
                </div>
                
                <?php if (!empty($error_msg)): ?>
                    <div style="background-color: rgba(239, 68, 68, 0.1); border-left: 4px solid var(--danger); padding: 0.75rem 1rem; border-radius: var(--radius-sm); margin-bottom: 1.5rem; font-size: 0.78rem; color: var(--danger); line-height: 1.5;">
                        <?= htmlspecialchars($error_msg) ?>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <!-- Username -->
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control form-control-glow" type="text" id="username" name="username" placeholder="e.g. admin, hod, faculty" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>
                    
                    <!-- Password -->
                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label class="form-label" for="password">Password</label>
                        <input class="form-control form-control-glow" type="password" id="password" name="password" placeholder="••••••••" required>
                    </div>
                    
                    <!-- Options -->
                    <div class="login-options">
                        <label class="form-check">
                            <input type="checkbox" name="remember" checked>
                            <span>Remember Me</span>
                        </label>
                        <a class="forgot-password" href="#" onclick="showToast('Password recovery simulator: Please contact your college administrator to reset your portal password.', 'info')">Forgot Password?</a>
                    </div>
                    
                    <!-- Login Button -->
                    <button class="btn btn-primary" type="submit" style="width: 100%; padding: 0.85rem; font-size: 0.9rem;">
                        <span>Authenticate Session</span>
                        <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; fill: currentColor;">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        
    </div>

    <!-- Include footer for background Javascipt alerts and notifications helper -->
    <?php include 'footer.php'; ?>
