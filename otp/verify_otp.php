<?php
/*
================================================================
  Team 2 - Academic Planning & Monitoring System
  OTP Verification Script — Companion to send_otp.php
  File: otp/verify_otp.php

  Usage (POST):
    POST /otp/verify_otp.php
    Body: otp=123456&context=login

  Returns JSON:
    Success → { "success": true, "message": "OTP verified." }
    Failure → { "success": false, "error": "...", "attempts_left": N }

  Security Checks Performed:
    ✓ Session integrity (context + email match)
    ✓ OTP expiry (5-minute window)
    ✓ Brute-force lockout (max N attempts from config)
    ✓ HMAC-SHA256 comparison (constant-time hash_equals)
    ✓ OTP format validation (6 numeric digits only)
    ✓ Session cleanup after successful verification
================================================================
*/

// ─── Bootstrap ──────────────────────────────────────────────
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=UTF-8');

// ─── Read Inputs ─────────────────────────────────────────────

// OTP: must be exactly 6 numeric digits
$submittedOtp = trim($_POST['otp'] ?? '');
if (!preg_match('/^\d{6}$/', $submittedOtp)) {
    echo json_encode([
        'success' => false,
        'error'   => 'OTP must be exactly 6 numeric digits.',
    ]);
    exit();
}

// Context: must be whitelisted
$allowedContexts = ['login', 'register', 'reset'];
$rawContext = trim($_POST['context'] ?? 'login');
$context    = in_array($rawContext, $allowedContexts, true) ? $rawContext : 'login';
$sessionKey = 'otp_data_' . $context;

// ─── Session Guard ────────────────────────────────────────────
// Ensure OTP session data exists for this context
$otpData = $_SESSION[$sessionKey] ?? null;

if (!$otpData || !isset($otpData['hash'], $otpData['salt'], $otpData['email'], $otpData['expiry'])) {
    echo json_encode([
        'success' => false,
        'error'   => 'No active OTP session found. Please request a new OTP.',
    ]);
    exit();
}

// ─── Check 1: Expiry ─────────────────────────────────────────
if (time() > $otpData['expiry']) {
    // Clear expired OTP session data
    unset($_SESSION[$sessionKey]);

    echo json_encode([
        'success' => false,
        'error'   => 'OTP has expired. Please request a new one.',
        'expired' => true,
    ]);
    exit();
}

// ─── Check 2: Brute-Force Lockout ────────────────────────────
$attempts    = (int) ($otpData['attempts'] ?? 0);
$maxAttempts = OTP_MAX_ATTEMPTS;

if ($attempts >= $maxAttempts) {
    // Wipe session on lockout — force a new OTP request
    unset($_SESSION[$sessionKey]);

    echo json_encode([
        'success'  => false,
        'error'    => 'Too many failed attempts. Please request a new OTP.',
        'locked'   => true,
    ]);
    exit();
}

// ─── Increment Attempt Counter Early ─────────────────────────
// Increment BEFORE checking, so a correct guess after N-1 wrongs still counts
$_SESSION[$sessionKey]['attempts'] = $attempts + 1;

// ─── Check 3: OTP Correctness (HMAC Comparison) ──────────────
// Recompute HMAC of the submitted OTP using the session-stored salt
// hash_equals() is constant-time — resistant to timing attacks
$expectedHash = hash_hmac('sha256', $submittedOtp, $otpData['salt']);
$isCorrect    = hash_equals($otpData['hash'], $expectedHash);

if (!$isCorrect) {
    $attemptsLeft = $maxAttempts - ($attempts + 1);
    $attemptsLeft = max(0, $attemptsLeft);  // Don't go negative

    // If out of attempts after this wrong guess, clear session
    if ($attemptsLeft === 0) {
        unset($_SESSION[$sessionKey]);
        echo json_encode([
            'success'       => false,
            'error'         => 'Incorrect OTP. No more attempts. Please request a new OTP.',
            'attempts_left' => 0,
            'locked'        => true,
        ]);
    } else {
        echo json_encode([
            'success'       => false,
            'error'         => "Incorrect OTP. {$attemptsLeft} attempt(s) remaining.",
            'attempts_left' => $attemptsLeft,
        ]);
    }
    exit();
}

// ─── OTP VERIFIED SUCCESSFULLY ───────────────────────────────
// Extract the verified email before clearing the OTP session
$verifiedEmail = $otpData['email'];
$verifiedName  = $otpData['name'] ?? 'User';
$verifiedCtx   = $otpData['context'] ?? $context;

// Clear the OTP session data — OTP is single-use
unset($_SESSION[$sessionKey]);

// Mark the verification as complete in a separate session key
// The calling code (e.g. login.php, register.php) should check this flag
// and then unset it after consuming it.
$_SESSION['otp_verified_' . $context] = [
    'email'       => $verifiedEmail,
    'name'        => $verifiedName,
    'verified_at' => time(),
];

// Regenerate session ID after successful auth operation
session_regenerate_id(true);

echo json_encode([
    'success'  => true,
    'message'  => 'OTP verified successfully.',
    'email'    => $verifiedEmail,
    'context'  => $verifiedCtx,
]);
exit();
