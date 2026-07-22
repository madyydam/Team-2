<?php
/*
================================================================
Team 2 - Academic Planning & Monitoring System
Forgot Password — OTP-based Password Reset
File: forgot_password.php
================================================================
Steps:
  1. User enters username → system looks up their email from DB / Session
  2. OTP sent to that email
  3. User enters 6-digit OTP to verify identity
  4. User sets new password → updated in DB & Session (case-sensitive)
================================================================
*/

session_start();
require_once __DIR__ . '/mail_config.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// ─── DB Connection ────────────────────────────────────────────
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

// ─── Hardcoded admin users (fallback) ────────────────────────
$admin_users_fallback = [
    'admin'   => ['name' => 'Prof. Balaji A. Chaugule', 'email' => 'admin@team2.edu',             'password' => 'admin123'],
    'hod'     => ['name' => 'Prof. Balaji A. Chaugule', 'email' => 'hod@team2.edu',               'password' => 'hod123'],
    'faculty' => ['name' => 'Dr. Neeti Rathore',        'email' => 'neeti.rathore@zealeducation.com', 'password' => 'faculty123'],
];

// ─── State machine ────────────────────────────────────────────
// step: 'username' → 'otp' → 'newpassword' → 'done'
$fp_step = $_SESSION['fp_step'] ?? 'username';

$error_msg   = '';
$success_msg = '';

// ============================================================
// STEP 1: User submits username → lookup email → send OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fp_find_user') {
    $entered_username = strtolower(trim($_POST['fp_username'] ?? ''));

    if (empty($entered_username)) {
        $error_msg = 'Please enter your username.';
    } else {
        $user_found = false;
        $user_email = '';
        $user_name  = '';

        // 1a. Check DB first
        if ($conn) {
            $alias_map = [
                'admin'   => 'admin@team2.edu',
                'hod'     => 'hod@team2.edu',
                'faculty' => 'neeti.rathore@zealeducation.com',
            ];

            if (isset($alias_map[$entered_username])) {
                $lookup_email = $alias_map[$entered_username];
                $stmt2 = $conn->prepare("SELECT name, email FROM users WHERE email = ? LIMIT 1");
                if ($stmt2) {
                    $stmt2->bind_param('s', $lookup_email);
                    $stmt2->execute();
                    $result2 = $stmt2->get_result();
                    if ($row = $result2->fetch_assoc()) {
                        $user_found = true;
                        $user_email = $row['email'];
                        $user_name  = $row['name'];
                    }
                    $stmt2->close();
                }
            } else {
                $like_pattern = $entered_username . '@%';
                $stmt3 = $conn->prepare("SELECT name, email FROM users WHERE email LIKE ? OR email = ? LIMIT 1");
                if ($stmt3) {
                    $stmt3->bind_param('ss', $like_pattern, $entered_username);
                    $stmt3->execute();
                    $result3 = $stmt3->get_result();
                    if ($row = $result3->fetch_assoc()) {
                        $user_found = true;
                        $user_email = $row['email'];
                        $user_name  = $row['name'];
                    }
                    $stmt3->close();
                }
            }
        }

        // 1b. Fallback: check session registered users
        if (!$user_found && isset($_SESSION['registered_users'][$entered_username])) {
            $reg = $_SESSION['registered_users'][$entered_username];
            $user_found = true;
            $user_email = $reg['email'];
            $user_name  = $reg['name'];
        }

        // 1c. Fallback: check hardcoded admin users
        if (!$user_found && isset($admin_users_fallback[$entered_username])) {
            $adm = $admin_users_fallback[$entered_username];
            $user_found = true;
            $user_email = $adm['email'];
            $user_name  = $adm['name'];
        }

        if (!$user_found) {
            $error_msg = 'No account found with this username. Please check and try again.';
        } else {
            // Generate & send OTP
            $otp = generateOtp();
            $sessionSalt = bin2hex(random_bytes(16));
            $otpHash     = hash_hmac('sha256', $otp, $sessionSalt);

            $_SESSION['fp_step']       = 'otp';
            $_SESSION['fp_username']   = $entered_username;
            $_SESSION['fp_email']      = $user_email;
            $_SESSION['fp_name']       = $user_name;
            $_SESSION['fp_otp_hash']   = $otpHash;
            $_SESSION['fp_otp_salt']   = $sessionSalt;
            $_SESSION['fp_otp_expiry'] = time() + 300;
            $_SESSION['fp_attempts']   = 0;
            $_SESSION['fp_last_sent']  = time();
            $fp_step = 'otp';

            $mail_result = sendOtpEmail($user_email, $user_name, $otp);
            $masked = preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $user_email);
            if ($mail_result === true) {
                $success_msg = "OTP sent to <strong>$masked</strong>. Valid for 5 minutes. Check your inbox.";
            } else {
                $success_msg = "Email delivery issue. Demo OTP: <strong>$otp</strong><br><small style='opacity:.65'>Error: $mail_result</small>";
            }
        }
    }
}

// ============================================================
// STEP 2: Resend OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fp_resend_otp') {
    $fp_step = 'otp';
    if (isset($_SESSION['fp_email'], $_SESSION['fp_name'])) {
        $last_sent = $_SESSION['fp_last_sent'] ?? 0;
        if (time() - $last_sent < 30) {
            $wait = 30 - (time() - $last_sent);
            $error_msg = "Please wait {$wait}s before resending.";
        } else {
            $otp = generateOtp();
            $sessionSalt = bin2hex(random_bytes(16));
            $otpHash     = hash_hmac('sha256', $otp, $sessionSalt);
            $_SESSION['fp_otp_hash']   = $otpHash;
            $_SESSION['fp_otp_salt']   = $sessionSalt;
            $_SESSION['fp_otp_expiry'] = time() + 300;
            $_SESSION['fp_attempts']   = 0;
            $_SESSION['fp_last_sent']  = time();

            $mail_result = sendOtpEmail($_SESSION['fp_email'], $_SESSION['fp_name'], $otp);
            $masked = preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $_SESSION['fp_email']);
            if ($mail_result === true) {
                $success_msg = "New OTP sent to <strong>$masked</strong>.";
            } else {
                $success_msg = "Email failed. Demo OTP: <strong>$otp</strong>";
            }
        }
    } else {
        header("Location: forgot_password.php");
        exit();
    }
}

// ============================================================
// STEP 3: Verify OTP
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fp_verify_otp') {
    $fp_step      = 'otp';
    $entered_otp  = trim($_POST['fp_otp'] ?? '');
    $_SESSION['fp_attempts'] = ($_SESSION['fp_attempts'] ?? 0) + 1;

    if (!preg_match('/^\d{6}$/', $entered_otp)) {
        $error_msg = 'Please enter a valid 6-digit OTP.';
    } elseif (!isset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'])) {
        $error_msg = 'OTP session expired. Please start over.';
        unset($_SESSION['fp_step'], $_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt']);
        $fp_step = 'username';
    } elseif (time() > ($_SESSION['fp_otp_expiry'] ?? 0)) {
        $error_msg = 'OTP has expired. Please request a new one.';
        unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt']);
    } elseif ($_SESSION['fp_attempts'] > 5) {
        $error_msg = 'Too many failed attempts. Please start over.';
        unset($_SESSION['fp_step'], $_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'],
              $_SESSION['fp_email'], $_SESSION['fp_username'], $_SESSION['fp_name']);
        $fp_step = 'username';
    } else {
        $expectedHash = hash_hmac('sha256', $entered_otp, $_SESSION['fp_otp_salt']);
        $isCorrect = hash_equals($_SESSION['fp_otp_hash'], $expectedHash);
        if (!$isCorrect) {
            $left = max(0, 5 - $_SESSION['fp_attempts']);
            $error_msg = "Incorrect OTP. {$left} attempt(s) remaining.";
        } else {
            // OTP verified!
            unset($_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt']);
            $_SESSION['fp_step']         = 'newpassword';
            $_SESSION['fp_otp_verified'] = true;
            $fp_step = 'newpassword';
            $success_msg = 'OTP verified! Please set your new password.';
        }
    }
}

// ============================================================
// STEP 4: Set new password → update DB & Session
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fp_set_password') {
    $fp_step = 'newpassword';
    if (!($_SESSION['fp_otp_verified'] ?? false)) {
        $error_msg = 'Unauthorized. Please complete OTP verification first.';
        $fp_step = 'username';
        unset($_SESSION['fp_step'], $_SESSION['fp_otp_verified']);
    } else {
        $new_pass    = $_POST['fp_new_password']    ?? '';
        $confirm_pass= $_POST['fp_confirm_password'] ?? '';
        $username    = $_SESSION['fp_username'] ?? '';
        $email       = $_SESSION['fp_email']    ?? '';

        if (empty($new_pass) || empty($confirm_pass)) {
            $error_msg = 'Please fill in both password fields.';
        } elseif (strlen($new_pass) < 6) {
            $error_msg = 'Password must be at least 6 characters.';
        } elseif ($new_pass !== $confirm_pass) {
            $error_msg = 'Passwords do not match. Please try again.';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $updated = false;

            // 1. Update in DB if available
            if ($conn && !empty($email)) {
                $upd_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                if ($upd_stmt) {
                    $upd_stmt->bind_param('ss', $hashed, $email);
                    $upd_stmt->execute();
                    if ($upd_stmt->affected_rows > 0) {
                        $updated = true;
                    }
                    $upd_stmt->close();
                }
            }

            // 2. Also update in session registered_users if present
            if (!empty($username) && isset($_SESSION['registered_users'][$username])) {
                $_SESSION['registered_users'][$username]['password'] = $new_pass;
                $updated = true;
            }

            // 3. Update hardcoded admin users in session override store
            if (isset($admin_users_fallback[$username])) {
                if (!isset($_SESSION['admin_password_overrides'])) {
                    $_SESSION['admin_password_overrides'] = [];
                }
                $_SESSION['admin_password_overrides'][$username] = $new_pass;
                $updated = true;
            }

            if ($updated || $conn) {
                unset(
                    $_SESSION['fp_step'], $_SESSION['fp_username'], $_SESSION['fp_email'],
                    $_SESSION['fp_name'], $_SESSION['fp_otp_verified'], $_SESSION['fp_attempts'],
                    $_SESSION['fp_last_sent'], $_SESSION['fp_otp_expiry']
                );
                $_SESSION['fp_step'] = 'done';
                $fp_step = 'done';
                $success_msg = 'Password updated successfully! You can now log in with your new password.';
            } else {
                $error_msg = 'Could not update password. Please try again or contact your administrator.';
            }
        }
    }
}

// ─── Back / Cancel ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'fp_back') {
    unset(
        $_SESSION['fp_step'], $_SESSION['fp_username'], $_SESSION['fp_email'],
        $_SESSION['fp_name'], $_SESSION['fp_otp_hash'], $_SESSION['fp_otp_salt'],
        $_SESSION['fp_otp_expiry'], $_SESSION['fp_attempts'], $_SESSION['fp_last_sent'],
        $_SESSION['fp_otp_verified']
    );
    header("Location: forgot_password.php");
    exit();
}

// ─── Sync step from session ───────────────────────────────────
if (empty($fp_step) || !in_array($fp_step, ['username','otp','newpassword','done'])) {
    $fp_step = $_SESSION['fp_step'] ?? 'username';
}

$theme_mode = isset($_COOKIE['theme_mode']) ? $_COOKIE['theme_mode'] : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Team 2 Academic Portal</title>
    <meta name="description" content="Reset your password via OTP email verification on Team 2 Academic Portal.">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
    .fp-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg-primary);
        padding: 1.5rem;
    }
    .fp-card {
        width: 100%;
        max-width: 440px;
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-lg);
        padding: 2.25rem 2rem;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
    }
    .fp-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), #7c3aed, #06b6d4);
    }
    .fp-logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        margin-bottom: 1.75rem;
        text-decoration: none;
    }
    .fp-logo span {
        font-size: 1rem;
        font-weight: 700;
        color: var(--text-primary);
    }
    .fp-header {
        text-align: center;
        margin-bottom: 1.75rem;
    }
    .fp-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
    }
    .fp-icon.blue  { background: rgba(37,99,235,0.1); }
    .fp-icon.amber { background: rgba(245,158,11,0.1); }
    .fp-header h2 {
        margin: 0 0 0.35rem;
        font-size: 1.3rem;
        color: var(--text-primary);
    }
    .fp-header p {
        margin: 0;
        font-size: 0.82rem;
        color: var(--text-muted);
        line-height: 1.5;
    }
    .fp-steps {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0;
        margin-bottom: 1.75rem;
    }
    .fp-step-dot {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
        background: var(--bg-secondary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 700;
        color: var(--text-muted);
        transition: all 0.3s;
    }
    .fp-step-dot.active {
        border-color: var(--primary);
        background: var(--primary);
        color: #fff;
        box-shadow: 0 0 0 4px rgba(37,99,235,0.15);
    }
    .fp-step-dot.done {
        border-color: var(--accent);
        background: var(--accent);
        color: #fff;
    }
    .fp-step-line {
        flex: 1;
        height: 2px;
        background: var(--border-color);
        max-width: 50px;
        transition: background 0.3s;
    }
    .fp-step-line.done { background: var(--accent); }

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
    .otp-digit:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
    }
    .otp-digit.filled { border-color: var(--primary); background: rgba(37,99,235,0.05); }

    .countdown-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: rgba(239,68,68,0.08);
        color: var(--danger);
        border: 1px solid rgba(239,68,68,0.2);
        border-radius: 99px;
        padding: 0.2rem 0.65rem;
        font-size: 0.72rem;
        font-weight: 700;
        margin-left: 0.4rem;
    }
    .countdown-badge.safe {
        background: rgba(16,185,129,0.08);
        color: var(--accent);
        border-color: rgba(16,185,129,0.2);
    }

    .pwd-strength-bar {
        height: 4px;
        border-radius: 2px;
        margin-top: 0.4rem;
        transition: width 0.4s, background 0.4s;
        width: 0%;
    }
    .pwd-hint { font-size: 0.7rem; margin-top: 0.25rem; min-height: 1rem; }

    .fp-info-card {
        background: linear-gradient(135deg,rgba(37,99,235,.06),rgba(37,99,235,.02));
        border: 1px solid rgba(37,99,235,.15);
        border-radius: var(--radius-md);
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        font-size: 0.8rem;
        color: var(--text-secondary);
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .fp-success { text-align: center; padding: 1rem 0; }
    .fp-success-icon {
        width: 72px;
        height: 72px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1.25rem;
    }

    .resend-btn {
        background: none; border: none;
        color: var(--primary); font-weight: 600;
        font-size: 0.8rem; cursor: pointer;
        padding: 0; text-decoration: underline;
        font-family: inherit;
    }
    .resend-btn:disabled { opacity: 0.4; cursor: not-allowed; text-decoration: none; }

    .pwd-wrapper { position: relative; }
    .pwd-eye-btn {
        position: absolute; right: 0.75rem; top: 50%;
        transform: translateY(-50%);
        background: none; border: none;
        cursor: pointer; color: var(--text-muted);
        padding: 0; display: flex; align-items: center;
    }
    .pwd-eye-btn:hover { color: var(--primary); }

    .alert-error {
        background: rgba(239,68,68,.08);
        border-left: 4px solid var(--danger);
        padding: .75rem 1rem;
        border-radius: var(--radius-sm);
        margin-bottom: 1.25rem;
        font-size: .8rem;
        color: var(--danger);
        line-height: 1.5;
    }
    .alert-success {
        background: rgba(16,185,129,.08);
        border-left: 4px solid var(--accent);
        padding: .75rem 1rem;
        border-radius: var(--radius-sm);
        margin-bottom: 1.25rem;
        font-size: .8rem;
        color: var(--accent);
        line-height: 1.5;
    }
    </style>
</head>
<body class="<?= $theme_mode === 'dark' ? 'dark-mode' : '' ?>">

<div class="fp-container">
    <div class="fp-card">

        <a href="login.php" class="fp-logo">
            <svg viewBox="0 0 24 24" style="width:28px;height:28px;fill:var(--primary);">
                <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6L23 9 12 3zm6.82 8.18L12 14.91 5.18 11.18 12 7.45l6.82 3.73zM12 18.72l-5-2.73v-3.72l5 2.73 5-2.73v3.72l-5 2.73z"/>
            </svg>
            <span>Team 2 Portal</span>
        </a>

        <?php if ($fp_step !== 'done'): ?>
        <div class="fp-steps">
            <div class="fp-step-dot <?= $fp_step === 'username' ? 'active' : 'done' ?>">
                <?= $fp_step === 'username' ? '1' : '✓' ?>
            </div>
            <div class="fp-step-line <?= in_array($fp_step, ['otp','newpassword']) ? 'done' : '' ?>"></div>
            <div class="fp-step-dot <?= $fp_step === 'otp' ? 'active' : ($fp_step === 'newpassword' ? 'done' : '') ?>">
                <?= $fp_step === 'newpassword' ? '✓' : '2' ?>
            </div>
            <div class="fp-step-line <?= $fp_step === 'newpassword' ? 'done' : '' ?>"></div>
            <div class="fp-step-dot <?= $fp_step === 'newpassword' ? 'active' : '' ?>">3</div>
        </div>
        <?php endif; ?>

        <?php if (!empty($error_msg)): ?>
        <div class="alert-error">⚠️ <?= $error_msg ?></div>
        <?php endif; ?>
        <?php if (!empty($success_msg)): ?>
        <div class="alert-success">✅ <?= $success_msg ?></div>
        <?php endif; ?>

        <!-- STEP 1: Username -->
        <?php if ($fp_step === 'username'): ?>
        <div class="fp-header">
            <div class="fp-icon blue">
                <svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:var(--primary);">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <h2>Forgot Password?</h2>
            <p>Enter your username to receive an OTP on your registered email address.</p>
        </div>

        <form action="forgot_password.php" method="POST" autocomplete="off">
            <input type="hidden" name="action" value="fp_find_user">

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="fp_username">Username</label>
                <input class="form-control form-control-glow" type="text"
                    id="fp_username" name="fp_username"
                    placeholder="Enter your username"
                    value="<?= htmlspecialchars($_POST['fp_username'] ?? '') ?>"
                    required autofocus>
                <small style="font-size:.72rem;color:var(--text-muted);margin-top:.3rem;display:block;">
                    e.g., admin, hod, faculty, or registered username
                </small>
            </div>

            <button class="btn btn-primary" type="submit"
                style="width:100%;padding:.85rem;font-size:.9rem;margin-bottom:1rem;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                Send OTP to Email
            </button>

            <p style="text-align:center;font-size:.8rem;color:var(--text-muted);">
                Remember password? <a href="login.php" style="color:var(--primary);font-weight:600;">Sign in →</a>
            </p>
        </form>

        <!-- STEP 2: OTP -->
        <?php elseif ($fp_step === 'otp'): ?>
        <div class="fp-header">
            <div class="fp-icon blue">
                <svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:var(--primary);">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>
            <h2>Verify OTP</h2>
            <p>Enter the 6-digit OTP code sent to your registered email.</p>
        </div>

        <?php if (isset($_SESSION['fp_email'])): ?>
        <div class="fp-info-card">
            <svg viewBox="0 0 24 24" style="width:20px;height:20px;fill:var(--primary);flex-shrink:0;">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
            </svg>
            <div>
                OTP sent to: <strong><?= htmlspecialchars(preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $_SESSION['fp_email'])) ?></strong><br>
                <span style="font-size:.72rem;opacity:.75;">Code expires in 5 minutes.</span>
            </div>
        </div>
        <?php endif; ?>

        <form action="forgot_password.php" method="POST" id="fpOtpForm">
            <input type="hidden" name="action" value="fp_verify_otp">
            <input type="hidden" name="fp_otp" id="fpOtpHidden">

            <label class="form-label" style="text-align:center;display:block;margin-bottom:.25rem;">
                Enter 6-digit OTP
                <span class="countdown-badge safe" id="fpCountdown">05:00</span>
            </label>

            <div class="otp-input-group">
                <?php for ($i = 0; $i < 6; $i++): ?>
                <input type="text" class="otp-digit" maxlength="1" inputmode="numeric"
                    pattern="[0-9]" id="fpotp<?= $i ?>" <?= $i === 0 ? 'autofocus' : '' ?>>
                <?php endfor; ?>
            </div>

            <button class="btn btn-primary" type="submit" id="fpVerifyBtn"
                style="width:100%;padding:.85rem;font-size:.9rem;margin-bottom:1rem;" disabled>
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
                Verify & Continue
            </button>
        </form>

        <form action="forgot_password.php" method="POST" style="text-align:center;margin-bottom:.75rem;">
            <input type="hidden" name="action" value="fp_resend_otp">
            <span style="font-size:.78rem;color:var(--text-muted);">Didn't receive code? </span>
            <button type="submit" class="resend-btn" id="fpResendBtn" disabled>Resend OTP</button>
            <span id="fpResendTimer" style="font-size:.72rem;color:var(--text-muted);"></span>
        </form>

        <form action="forgot_password.php" method="POST" style="text-align:center;">
            <input type="hidden" name="action" value="fp_back">
            <button type="submit" style="background:none;border:none;color:var(--text-muted);font-size:.78rem;cursor:pointer;text-decoration:underline;">
                ← Back
            </button>
        </form>

        <!-- STEP 3: New Password -->
        <?php elseif ($fp_step === 'newpassword'): ?>
        <div class="fp-header">
            <div class="fp-icon amber">
                <svg viewBox="0 0 24 24" style="width:26px;height:26px;fill:#f59e0b;">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM12 17c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1s3.1 1.39 3.1 3.1v2z"/>
                </svg>
            </div>
            <h2>Set New Password</h2>
            <p>Create a new case-sensitive password for your account.</p>
        </div>

        <form action="forgot_password.php" method="POST" autocomplete="off">
            <input type="hidden" name="action" value="fp_set_password">

            <div class="form-group">
                <label class="form-label" for="fp_new_password">New Password (case-sensitive)</label>
                <div class="pwd-wrapper">
                    <input class="form-control form-control-glow" type="password"
                        id="fp_new_password" name="fp_new_password"
                        placeholder="••••••••" required style="padding-right:3rem;"
                        oninput="checkPwdStrength(this.value)">
                    <button type="button" class="pwd-eye-btn" onclick="toggleFpPwd('fp_new_password','eyeNew')">
                        <svg id="eyeNew" viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
                <div style="background:var(--border-color);border-radius:2px;margin-top:.4rem;height:4px;">
                    <div id="pwdStrengthBar" class="pwd-strength-bar"></div>
                </div>
                <div id="pwdHint" class="pwd-hint" style="color:var(--text-muted);"></div>
            </div>

            <div class="form-group" style="margin-bottom:1.5rem;">
                <label class="form-label" for="fp_confirm_password">Confirm New Password</label>
                <div class="pwd-wrapper">
                    <input class="form-control form-control-glow" type="password"
                        id="fp_confirm_password" name="fp_confirm_password"
                        placeholder="••••••••" required style="padding-right:3rem;"
                        oninput="checkPwdMatch()">
                    <button type="button" class="pwd-eye-btn" onclick="toggleFpPwd('fp_confirm_password','eyeConfirm')">
                        <svg id="eyeConfirm" viewBox="0 0 24 24" style="width:18px;height:18px;fill:currentColor;">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    </button>
                </div>
                <div id="pwdMatchHint" class="pwd-hint"></div>
            </div>

            <button class="btn btn-primary" type="submit"
                style="width:100%;padding:.85rem;font-size:.9rem;margin-bottom:1rem;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                    <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                </svg>
                Save New Password
            </button>
        </form>

        <!-- STEP 4: Success -->
        <?php elseif ($fp_step === 'done'): ?>
        <div class="fp-success">
            <div class="fp-success-icon">
                <svg viewBox="0 0 24 24" style="width:34px;height:34px;fill:white;">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            <h2 style="margin:0 0 .5rem;font-size:1.3rem;">Password Updated!</h2>
            <p style="color:var(--text-muted);font-size:.84rem;margin:0 0 1.75rem;line-height:1.6;">
                Your password has been changed successfully.<br>
                You can now log in with your new password.
            </p>
            <a href="login.php" class="btn btn-primary"
                style="display:inline-flex;align-items:center;gap:.5rem;padding:.85rem 2rem;font-size:.9rem;text-decoration:none;">
                <svg viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                    <path d="M11 7L9.6 8.4l2.6 2.6H2v2h10.2l-2.6 2.6L11 17l5-5-5-5zm9 12h-8v2h8c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2h-8v2h8v14z"/>
                </svg>
                Go to Login
            </a>
        </div>
        <?php unset($_SESSION['fp_step']); ?>
        <?php endif; ?>

    </div>
</div>

<?php include 'footer.php'; ?>

<script>
function toggleFpPwd(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const eyeOn  = 'M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z';
    const eyeOff = 'M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.804 11.804 0 001 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z';
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = `<path d="${eyeOff}"/>`;
    } else {
        input.type = 'password';
        icon.innerHTML = `<path d="${eyeOn}"/>`;
    }
}

function checkPwdStrength(val) {
    const bar  = document.getElementById('pwdStrengthBar');
    const hint = document.getElementById('pwdHint');
    if (!bar) return;
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const levels = [
        { pct: 0,   color: 'transparent', label: '' },
        { pct: 20,  color: '#ef4444', label: '🔴 Very Weak' },
        { pct: 40,  color: '#f97316', label: '🟠 Weak' },
        { pct: 60,  color: '#eab308', label: '🟡 Fair' },
        { pct: 80,  color: '#22c55e', label: '🟢 Strong' },
        { pct: 100, color: '#10b981', label: '✅ Very Strong' },
    ];
    const lv = levels[score] || levels[0];
    bar.style.width = lv.pct + '%';
    bar.style.background = lv.color;
    hint.textContent = lv.label;
    hint.style.color = lv.color;
    checkPwdMatch();
}

function checkPwdMatch() {
    const pw1  = document.getElementById('fp_new_password');
    const pw2  = document.getElementById('fp_confirm_password');
    const hint = document.getElementById('pwdMatchHint');
    if (!pw1 || !pw2 || !hint || !pw2.value) { if(hint) hint.textContent = ''; return; }
    if (pw1.value === pw2.value) {
        hint.textContent = '✅ Passwords match';
        hint.style.color = 'var(--accent)';
    } else {
        hint.textContent = '❌ Passwords do not match';
        hint.style.color = 'var(--danger)';
    }
}

<?php if ($fp_step === 'otp'): ?>
(function () {
    const digits     = Array.from({length: 6}, (_, i) => document.getElementById('fpotp' + i));
    const hiddenOtp  = document.getElementById('fpOtpHidden');
    const verifyBtn  = document.getElementById('fpVerifyBtn');
    const resendBtn  = document.getElementById('fpResendBtn');
    const resendTimer= document.getElementById('fpResendTimer');
    const countdown  = document.getElementById('fpCountdown');

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
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((ch, i) => { if (digits[i]) digits[i].value = ch; });
            updateHidden();
            if (pasted.length >= 6) verifyBtn.focus();
        });
    });

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
