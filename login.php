<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Login & Register Screen - project/login.php
================================================================
*/

session_start();
require_once __DIR__ . '/mail_config.php';

// Redirect to dashboard if already authenticated
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error_msg   = '';
$success_msg = '';
$active_tab  = $_POST['active_tab'] ?? $_GET['tab'] ?? 'login';  // 'login' or 'register'
$reg_step    = $_SESSION['reg_step'] ?? 'form'; // 'form' or 'otp'

// ── Existing Admin Users (Login only) ───────────────────────
$admin_users = [
    'admin'   => ['name' => 'Prof. Balaji A. Chaugule', 'role' => 'Administrator',      'password' => 'admin123'],
    'hod'     => ['name' => 'Prof. Balaji A. Chaugule', 'role' => 'Head of Department', 'password' => 'hod123'],
    'faculty' => ['name' => 'Dr. Neeti Rathore',        'role' => 'Faculty Member',     'password' => 'faculty123'],
];

// ── Registered users stored in session (persists during session) ─
if (!isset($_SESSION['registered_users'])) {
    $_SESSION['registered_users'] = [];
}

// ============================================================
// HANDLE: DIRECT LOGIN (no OTP)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $active_tab = 'login';
    $username   = strtolower(trim($_POST['username'] ?? ''));
    $password   = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_msg = 'Please enter both username and password.';
    } else {
        // 1. Attempt DB authentication
        $db_host = 'localhost';
        $db_user = 'root';
        $db_pass = '';
        $db_name = 'academic_monitoring_db';
        $conn = null;
        try {
            $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
            if (!$conn->connect_error) {
                $conn->set_charset('utf8mb4');
            } else {
                $conn = null;
            }
        } catch (Exception $e) {
            $conn = null;
        }

        if ($conn) {
            $alias_map = [
                'admin'   => 'admin@team2.edu',
                'hod'     => 'hod@team2.edu',
                'faculty' => 'neeti.rathore@zealeducation.com',
            ];
            $lookup_email = $alias_map[$username] ?? $username;
            
            $stmt = $conn->prepare("
                SELECT u.name, u.password, r.name AS role_name 
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.role_id 
                WHERE u.email = ? OR u.email LIKE ? 
                LIMIT 1
            ");
            if ($stmt) {
                $like_pattern = $username . '@%';
                $stmt->bind_param('ss', $lookup_email, $like_pattern);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password']) || $row['password'] === $password) {
                        $found_user = [
                            'name' => $row['name'],
                            'role' => ($row['role_name'] === 'Admin') ? 'Administrator' : (($row['role_name'] === 'HOD') ? 'Head of Department' : (($row['role_name'] === 'Faculty') ? 'Faculty Member' : $row['role_name']))
                        ];
                    }
                }
                $stmt->close();
            }
            $conn->close();
        }

        // 2. Fallback: Check password overrides (from Forgot Password reset)
        if (!$found_user) {
            $expected_pass = $_SESSION['admin_password_overrides'][$username] ?? $admin_users[$username]['password'] ?? null;
            if (array_key_exists($username, $admin_users) && $expected_pass === $password) {
                $found_user = $admin_users[$username];
            }
            // Check registered users in session
            elseif (isset($_SESSION['registered_users'][$username]) && $_SESSION['registered_users'][$username]['password'] === $password) {
                $found_user = $_SESSION['registered_users'][$username];
            }
        }

        if ($found_user) {
            $_SESSION['user_id']   = $username;
            $_SESSION['user_name'] = $found_user['name'];
            $_SESSION['user_role'] = $found_user['role'];
            header("Location: dashboard.php");
            exit();
        } else {
            // Check if username exists but wrong password
            if (array_key_exists($username, $admin_users) || isset($_SESSION['registered_users'][$username])) {
                $error_msg = 'Wrong password. Please try again.';
            } else {
                $error_msg = 'Wrong username. No account found with this username.';
            }
        }
    }
}

// ============================================================
// HANDLE: REGISTER — Step 1: Validate form & send OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_submit') {
    $active_tab = 'register';
    $reg_name   = trim($_POST['reg_name'] ?? '');
    $reg_uname  = strtolower(trim($_POST['reg_username'] ?? ''));
    $reg_email  = strtolower(trim($_POST['reg_email'] ?? ''));
    $reg_pass   = $_POST['reg_password'] ?? '';
    $reg_role   = $_POST['reg_role'] ?? 'Faculty Member';

    if (empty($reg_name) || empty($reg_uname) || empty($reg_email) || empty($reg_pass)) {
        $error_msg = 'Please fill in all fields.';
    } elseif (!filter_var($reg_email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } elseif (strlen($reg_pass) < 6) {
        $error_msg = 'Password must be at least 6 characters.';
    } elseif (array_key_exists($reg_uname, $admin_users) || isset($_SESSION['registered_users'][$reg_uname])) {
        $error_msg = 'This username is already taken. Please choose another.';
    } else {
        // Generate OTP & store registration data in session
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reg_step']      = 'otp';
        $_SESSION['reg_otp']       = $otp;
        $_SESSION['reg_generated'] = time();
        $_SESSION['reg_attempts']  = 0;
        $_SESSION['reg_data']      = [
            'username' => $reg_uname,
            'name'     => $reg_name,
            'email'    => $reg_email,
            'password' => $reg_pass,
            'role'     => $reg_role,
        ];
        $reg_step = 'otp';
        $active_tab = 'register';

        // Send OTP to entered email
        $mail_result = sendOtpEmail($reg_email, $reg_name, $otp);
        if ($mail_result === true) {
            $masked = preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $reg_email);
            $success_msg = "OTP sent to <strong>$masked</strong>. Check your inbox (valid 5 min).";
        } else {
            $success_msg = "Email failed. Demo OTP: <strong>$otp</strong><br><small style='opacity:.7'>Error: $mail_result</small>";
        }
    }
}

// ============================================================
// HANDLE: REGISTER — Step 2: Verify OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_otp') {
    $active_tab = 'register';
    $reg_step   = 'otp';
    $entered    = trim($_POST['otp'] ?? '');
    $stored_otp = $_SESSION['reg_otp'] ?? '';
    $generated  = $_SESSION['reg_generated'] ?? 0;
    $_SESSION['reg_attempts'] = ($_SESSION['reg_attempts'] ?? 0) + 1;

    if (time() - $generated > 300) {
        $error_msg = 'OTP expired. Please go back and register again.';
        unset($_SESSION['reg_step'], $_SESSION['reg_otp'], $_SESSION['reg_data']);
        $reg_step = 'form';
    } elseif ($_SESSION['reg_attempts'] > 5) {
        $error_msg = 'Too many attempts. Please register again.';
        unset($_SESSION['reg_step'], $_SESSION['reg_otp'], $_SESSION['reg_data'], $_SESSION['reg_attempts']);
        $reg_step = 'form';
    } elseif ($entered !== $stored_otp) {
        $error_msg = 'Incorrect OTP. ' . (5 - $_SESSION['reg_attempts']) . ' attempt(s) remaining.';
    } else {
        // OTP verified! Save user & log in
        $data = $_SESSION['reg_data'];
        $_SESSION['registered_users'][$data['username']] = [
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'],
        ];
        $_SESSION['user_id']   = $data['username'];
        $_SESSION['user_name'] = $data['name'];
        $_SESSION['user_role'] = $data['role'];
        unset($_SESSION['reg_step'], $_SESSION['reg_otp'], $_SESSION['reg_data'], $_SESSION['reg_generated'], $_SESSION['reg_attempts']);
        header("Location: dashboard.php");
        exit();
    }
}

// ============================================================
// HANDLE: Resend OTP (Register)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend_reg_otp') {
    $active_tab = 'register';
    $reg_step   = 'otp';
    if (isset($_SESSION['reg_data'])) {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $_SESSION['reg_otp']       = $otp;
        $_SESSION['reg_generated'] = time();
        $_SESSION['reg_attempts']  = 0;
        $data = $_SESSION['reg_data'];
        $mail_result = sendOtpEmail($data['email'], $data['name'], $otp);
        if ($mail_result === true) {
            $masked = preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $data['email']);
            $success_msg = "New OTP sent to <strong>$masked</strong>.";
        } else {
            $success_msg = "Email failed. Demo OTP: <strong>$otp</strong>";
        }
    }
}

// HANDLE: Back from OTP to Register form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'back_to_reg') {
    unset($_SESSION['reg_step'], $_SESSION['reg_otp'], $_SESSION['reg_data'], $_SESSION['reg_generated'], $_SESSION['reg_attempts']);
    $reg_step   = 'form';
    $active_tab = 'register';
}

$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
$reg_user_data = $_SESSION['reg_data'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team 2 – Login & Register</title>
    <meta name="description" content="Secure login and registration portal for Team 2 Academic Planning & Monitoring System.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
    /* ── Auth Tab Switcher ── */
    .auth-tabs {
        display: flex;
        background: rgba(15,23,42,0.05);
        border-radius: var(--radius-md);
        padding: 4px;
        margin-bottom: 1.75rem;
        gap: 4px;
    }
    .auth-tab-btn {
        flex: 1;
        padding: 0.6rem 1rem;
        border: none;
        background: transparent;
        border-radius: calc(var(--radius-md) - 2px);
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.25s ease;
        font-family: inherit;
    }
    .auth-tab-btn.active {
        background: var(--primary);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(37,99,235,0.35);
    }
    .auth-tab-btn:not(.active):hover {
        color: var(--text-primary);
        background: rgba(37,99,235,0.08);
    }

    /* ── OTP Digit Inputs ── */
    .otp-input-group {
        display: flex;
        gap: 0.5rem;
        justify-content: center;
        margin: 1.5rem 0;
    }
    .otp-digit {
        width: 48px;
        height: 56px;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        background: var(--glass-bg);
        color: var(--text-primary);
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
        caret-color: var(--primary);
        font-family: inherit;
    }
    .otp-digit:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.12); }
    .otp-digit.filled { border-color: var(--accent); }

    /* ── Countdown & Resend ── */
    .countdown-badge {
        display: inline-block;
        background: rgba(239,68,68,0.08);
        color: var(--danger);
        border: 1px solid rgba(239,68,68,0.2);
        border-radius: 99px;
        padding: 0.15rem 0.6rem;
        font-size: 0.7rem;
        font-weight: 700;
        margin-left: 0.4rem;
    }
    .resend-btn {
        background: none;
        border: none;
        color: var(--primary);
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        padding: 0;
        text-decoration: underline;
        font-family: inherit;
    }
    .resend-btn:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: none; }

    /* ── OTP Info Card ── */
    .otp-info-card {
        background: linear-gradient(135deg,rgba(37,99,235,.06),rgba(37,99,235,.02));
        border: 1px solid rgba(37,99,235,.15);
        border-radius: var(--radius-md);
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .otp-info-card svg { flex-shrink: 0; fill: var(--primary); }

    /* ── Divider ── */
    .form-divider {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin: 1.25rem 0;
        color: var(--text-muted);
        font-size: 0.75rem;
        font-weight: 500;
    }
    .form-divider::before, .form-divider::after {
        content: '';
        flex: 1;
        height: 1px;
        background: var(--border-color);
    }

    /* ── Role Select ── */
    .role-select-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }
    .role-option {
        position: relative;
    }
    .role-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }
    .role-option label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.3rem;
        padding: 0.65rem 0.5rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-secondary);
        transition: all 0.2s;
        text-align: center;
    }
    .role-option label svg { fill: var(--text-muted); transition: fill 0.2s; }
    .role-option input:checked + label {
        border-color: var(--primary);
        background: rgba(37,99,235,0.06);
        color: var(--primary);
    }
    .role-option input:checked + label svg { fill: var(--primary); }
    .role-option label:hover { border-color: var(--border-hover); }

    /* ── Admin badge on login ── */
    .admin-hint {
        background: rgba(16,185,129,0.07);
        border: 1px solid rgba(16,185,129,0.2);
        border-radius: var(--radius-sm);
        padding: 0.65rem 1rem;
        font-size: 0.75rem;
        color: var(--accent);
        margin-bottom: 1.25rem;
        line-height: 1.5;
    }
    .admin-hint strong { display: block; margin-bottom: 0.2rem; font-size: 0.78rem; }
    </style>
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

<div class="login-container">

    <!-- Left: Branding Panel -->
    <div class="login-left">
        <div class="login-blobs">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
        </div>
        <div class="login-left-content">
            <div class="login-brand">
                <svg viewBox="0 0 24 24" style="width:38px;height:38px;fill:var(--primary);">
                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
                </svg>
                <span>Team 2</span>
            </div>
            <h1 class="login-tagline">Academic Planning &<br>Monitoring System</h1>
            <p class="login-description">
                Unified departmental portal for scheduling, attendance tracking, lab management, and academic reporting — secured with email verification.
            </p>

            <!-- Feature bullets -->
            <div style="margin-top: 2rem; display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ([
                    ['🗓️', 'Smart Timetable Scheduling'],
                    ['📊', 'Real-time Attendance Tracking'],
                    ['🔬', 'Lab Session Management'],
                    ['📋', 'Automated Report Generation'],
                ] as [$icon, $text]): ?>
                <div style="display:flex;align-items:center;gap:0.6rem;font-size:0.82rem;color:rgba(255,255,255,0.75);">
                    <span style="font-size:1rem;"><?= $icon ?></span>
                    <span><?= $text ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="login-left-footer">
            &copy; 2026 Team 2 Academic Portal. Built for educational efficiency.
        </div>
    </div>

    <!-- Right: Auth Card -->
    <div class="login-right">
        <div class="login-card glass-card">

            <!-- Tab Switcher -->
            <?php if ($reg_step !== 'otp'): ?>
            <div class="auth-tabs" id="authTabs">
                <button type="button" class="auth-tab-btn <?= $active_tab === 'login' ? 'active' : '' ?>"
                    id="tabLoginBtn" onclick="switchTab('login')">
                    🔑 Login
                </button>
                <button type="button" class="auth-tab-btn <?= $active_tab === 'register' ? 'active' : '' ?>"
                    id="tabRegisterBtn" onclick="switchTab('register')">
                    ✨ Register
                </button>
            </div>
            <?php endif; ?>

            <!-- Alert Messages -->
            <?php if (!empty($error_msg)): ?>
            <div style="background:rgba(239,68,68,.08);border-left:4px solid var(--danger);padding:.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;font-size:.8rem;color:var(--danger);line-height:1.5;">
                ⚠️ <?= $error_msg ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($success_msg)): ?>
            <div style="background:rgba(16,185,129,.08);border-left:4px solid var(--accent);padding:.75rem 1rem;border-radius:var(--radius-sm);margin-bottom:1.25rem;font-size:.8rem;color:var(--accent);line-height:1.5;">
                ✅ <?= $success_msg ?>
            </div>
            <?php endif; ?>

            <!-- ================================================ -->
            <!-- TAB 1: LOGIN -->
            <!-- ================================================ -->
            <div id="loginPanel" style="display: <?= $active_tab === 'login' ? 'block' : 'none' ?>;">
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <p>Sign in directly to your portal account</p>
                </div>

                <!-- Admin hint -->
                <div class="admin-hint">
                    <strong>🛡️ Admin Credentials (Demo)</strong>
                    <code>admin / admin123</code> &nbsp;·&nbsp;
                    <code>hod / hod123</code> &nbsp;·&nbsp;
                    <code>faculty / faculty123</code>
                </div>

                <form action="login.php" method="POST" id="loginForm">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="active_tab" value="login">

                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input class="form-control form-control-glow" type="text" id="username" name="username"
                            placeholder="Enter your username" required autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="margin-bottom:1.25rem;">
                        <label class="form-label" for="password">Password</label>
                        <div style="position:relative;">
                            <input class="form-control form-control-glow" type="password" id="password" name="password"
                                placeholder="••••••••" required style="padding-right:3rem;">
                            <button type="button" onclick="togglePwd('password','eyeIconLogin')"
                                style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;">
                                <svg id="eyeIconLogin" viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="login-options" style="margin-bottom:1.5rem;">
                        <label class="form-check">
                            <input type="checkbox" name="remember" checked>
                            <span>Remember Me</span>
                        </label>
                        <a class="forgot-password" href="forgot_password.php">
                            Forgot Password?
                        </a>
                    </div>

                    <button class="btn btn-primary" type="submit" style="width:100%;padding:.85rem;font-size:.9rem;">
                        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                            <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                        </svg>
                        Sign In
                    </button>

                    <div class="form-divider">or</div>
                    <p style="text-align:center;font-size:.8rem;color:var(--text-muted);">
                        New user? <a href="#" onclick="switchTab('register')" style="color:var(--primary);font-weight:600;">Create an account →</a>
                    </p>
                </form>
            </div>

            <!-- ================================================ -->
            <!-- TAB 2: REGISTER -->
            <!-- ================================================ -->
            <div id="registerPanel" style="display: <?= $active_tab === 'register' ? 'block' : 'none' ?>;">

                <?php if ($reg_step === 'form'): ?>
                <!-- Step 1: Registration Form -->
                <div class="login-header">
                    <h2>Create Account</h2>
                    <p>Register and verify your email to get started</p>
                </div>

                <form action="login.php" method="POST" id="registerForm">
                    <input type="hidden" name="action" value="register_submit">
                    <input type="hidden" name="active_tab" value="register">

                    <!-- Role Selection -->
                    <div style="margin-bottom:1rem;">
                        <label class="form-label" style="margin-bottom:.5rem;">Select Role</label>
                        <div class="role-select-group">
                            <div class="role-option">
                                <input type="radio" name="reg_role" id="role_admin" value="Administrator">
                                <label for="role_admin">
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/></svg>
                                    Admin
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="reg_role" id="role_hod" value="Head of Department">
                                <label for="role_hod">
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                                    HoD
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="reg_role" id="role_faculty" value="Faculty Member" checked>
                                <label for="role_faculty">
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                    Faculty
                                </label>
                            </div>
                            <div class="role-option">
                                <input type="radio" name="reg_role" id="role_student" value="Student">
                                <label for="role_student">
                                    <svg viewBox="0 0 24 24" style="width:18px;height:18px;"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                                    Student
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg_name">Full Name</label>
                        <input class="form-control form-control-glow" type="text" id="reg_name" name="reg_name"
                            placeholder="e.g. Dr. Anjali Sharma" required
                            value="<?= htmlspecialchars($_POST['reg_name'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg_username">Username</label>
                        <input class="form-control form-control-glow" type="text" id="reg_username" name="reg_username"
                            placeholder="e.g. anjali_sharma" required
                            value="<?= htmlspecialchars($_POST['reg_username'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg_email">
                            Email Address
                            <span style="font-size:.72rem;color:var(--primary);font-weight:500;margin-left:.4rem;">OTP will be sent here</span>
                        </label>
                        <input class="form-control form-control-glow" type="email" id="reg_email" name="reg_email"
                            placeholder="your@gmail.com" required
                            value="<?= htmlspecialchars($_POST['reg_email'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" for="reg_password">Password <span style="font-size:.72rem;color:var(--text-muted);">(min. 6 characters)</span></label>
                        <div style="position:relative;">
                            <input class="form-control form-control-glow" type="password" id="reg_password" name="reg_password"
                                placeholder="Create a strong password" required style="padding-right:3rem;">
                            <button type="button" onclick="togglePwd('reg_password','eyeIconReg')"
                                style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);padding:0;">
                                <svg id="eyeIconReg" viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;">
                                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit" style="width:100%;padding:.85rem;font-size:.9rem;">
                        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                            <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                        </svg>
                        Send OTP & Verify Email
                    </button>

                    <div class="form-divider">or</div>
                    <p style="text-align:center;font-size:.8rem;color:var(--text-muted);">
                        Already have an account? <a href="#" onclick="switchTab('login')" style="color:var(--primary);font-weight:600;">Sign in →</a>
                    </p>
                </form>

                <?php else: ?>
                <!-- Step 2: OTP Verification (Register) -->
                <div style="text-align:center;margin-bottom:1.25rem;">
                    <div style="width:56px;height:56px;background:rgba(37,99,235,0.1);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 0.75rem;">
                        <svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:var(--primary);"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                    </div>
                    <h2 style="margin:0 0 .35rem;font-size:1.25rem;">Verify Your Email</h2>
                    <p style="color:var(--text-muted);font-size:.82rem;margin:0;">Check your inbox for the 6-digit OTP</p>
                </div>

                <?php if ($reg_user_data): ?>
                <div class="otp-info-card">
                    <svg viewBox="0 0 24 24" style="width:20px;height:20px;">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                    <div>
                        Registering as <strong><?= htmlspecialchars($reg_user_data['name']) ?></strong>
                        (<?= htmlspecialchars($reg_user_data['role']) ?>)<br>
                        OTP sent to: <strong><?= htmlspecialchars($reg_user_data['email']) ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <form action="login.php" method="POST" id="regOtpForm">
                    <input type="hidden" name="action" value="register_otp">
                    <input type="hidden" name="active_tab" value="register">
                    <input type="hidden" name="otp" id="regOtpHidden">

                    <label class="form-label" style="text-align:center;display:block;margin-bottom:.25rem;">
                        Enter 6-digit OTP
                        <span class="countdown-badge" id="regCountdownBadge">05:00</span>
                    </label>

                    <div class="otp-input-group" id="regOtpBoxes">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" class="otp-digit" maxlength="1" inputmode="numeric"
                            pattern="[0-9]" id="regotp<?= $i ?>" <?= $i === 0 ? 'autofocus' : '' ?>>
                        <?php endfor; ?>
                    </div>

                    <button class="btn btn-primary" type="submit" id="regVerifyBtn"
                        style="width:100%;padding:.85rem;font-size:.9rem;margin-bottom:1rem;" disabled>
                        <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                        </svg>
                        Verify & Create Account
                    </button>
                </form>

                <!-- Resend OTP -->
                <form action="login.php" method="POST" style="text-align:center;margin-bottom:.75rem;">
                    <input type="hidden" name="action" value="resend_reg_otp">
                    <input type="hidden" name="active_tab" value="register">
                    <span style="font-size:.78rem;color:var(--text-muted);">Didn't receive the code?</span>
                    <button type="submit" class="resend-btn" id="regResendBtn" disabled>Resend OTP</button>
                    <span id="regResendTimer" style="font-size:.72rem;color:var(--text-muted);"></span>
                </form>

                <!-- Back button -->
                <form action="login.php" method="POST" style="text-align:center;">
                    <input type="hidden" name="action" value="back_to_reg">
                    <input type="hidden" name="active_tab" value="register">
                    <button type="submit" style="background:none;border:none;color:var(--text-muted);font-size:.78rem;cursor:pointer;text-decoration:underline;">
                        ← Back to registration form
                    </button>
                </form>
                <?php endif; ?>
            </div>

        </div><!-- .login-card -->
    </div><!-- .login-right -->

</div><!-- .login-container -->

<?php include 'footer.php'; ?>

<script>
// ── Tab Switcher ──────────────────────────────────────────────
function switchTab(tab) {
    const loginPanel    = document.getElementById('loginPanel');
    const registerPanel = document.getElementById('registerPanel');
    const tabLoginBtn   = document.getElementById('tabLoginBtn');
    const tabRegBtn     = document.getElementById('tabRegisterBtn');

    if (tab === 'login') {
        loginPanel.style.display    = 'block';
        registerPanel.style.display = 'none';
        tabLoginBtn.classList.add('active');
        tabRegBtn.classList.remove('active');
    } else {
        loginPanel.style.display    = 'none';
        registerPanel.style.display = 'block';
        tabRegBtn.classList.add('active');
        tabLoginBtn.classList.remove('active');
    }
}

// ── Password Toggle ───────────────────────────────────────────
function togglePwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const eyeOn  = '<path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>';
    const eyeOff = '<path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.804 11.804 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>';
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = eyeOff;
    } else {
        input.type = 'password';
        icon.innerHTML = eyeOn;
    }
}

// ── OTP Box Logic (Register Step 2) ──────────────────────────
<?php if ($reg_step === 'otp'): ?>
(function () {
    const digits     = Array.from({length: 6}, (_, i) => document.getElementById('regotp' + i));
    const hiddenOtp  = document.getElementById('regOtpHidden');
    const verifyBtn  = document.getElementById('regVerifyBtn');
    const resendBtn  = document.getElementById('regResendBtn');
    const resendTimer = document.getElementById('regResendTimer');
    const countdown  = document.getElementById('regCountdownBadge');

    function updateHidden() {
        const val = digits.map(d => d.value).join('');
        hiddenOtp.value = val;
        const ok = val.length === 6 && /^\d{6}$/.test(val);
        verifyBtn.disabled = !ok;
        digits.forEach(d => d.classList.toggle('filled', d.value !== ''));
    }

    digits.forEach((box, idx) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(-1);
            if (box.value && idx < 5) digits[idx + 1].focus();
            updateHidden();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && idx > 0) digits[idx - 1].focus();
            if (e.key === 'ArrowLeft'  && idx > 0) digits[idx - 1].focus();
            if (e.key === 'ArrowRight' && idx < 5) digits[idx + 1].focus();
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
            updateHidden();
            if (pasted.length >= 6) verifyBtn.focus();
        });
    });

    // Countdown (5 min)
    let secs = 300;
    (function tick() {
        const m = String(Math.floor(secs / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        countdown.textContent = m + ':' + s;
        if (secs <= 0) {
            countdown.textContent = 'Expired';
            verifyBtn.disabled = true;
        } else { secs--; setTimeout(tick, 1000); }
    })();

    // Resend cooldown (30s)
    let rs = 30;
    (function resendTick() {
        if (rs > 0) {
            resendTimer.textContent = '(' + rs + 's)';
            resendBtn.disabled = true;
            rs--; setTimeout(resendTick, 1000);
        } else {
            resendTimer.textContent = '';
            resendBtn.disabled = false;
        }
    })();
})();
<?php endif; ?>
</script>

</body>
</html>
