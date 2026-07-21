<?php
/*
================================================================
  Team 2 - Academic Planning & Monitoring System
  OTP Send Script
  File: otp/send_otp.php

  Usage (POST):
    POST /otp/send_otp.php
    Body: email=user@example.com&name=John+Doe&context=login

  Returns JSON:
    Success → { "success": true,  "masked_email": "joh***@example.com" }
    Failure → { "success": false, "error": "Error description" }

  Secure session data stored:
    $_SESSION['otp_data'][context] = {
        hash, email, name, expiry, attempts, last_sent
    }
================================================================
*/

// ─── Bootstrap ──────────────────────────────────────────────
session_start();

// Only allow POST requests to this endpoint
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/mailer.php';

header('Content-Type: application/json; charset=UTF-8');

// ─── Read & Sanitize Inputs ──────────────────────────────────

// Email: validate strictly using PHP filter
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'A valid email address is required.']);
    exit();
}
$email = strtolower(trim($email));

// Name: strip all HTML tags, limit length
$name = strip_tags(trim($_POST['name'] ?? 'User'));
$name = mb_substr($name, 0, 100);  // Max 100 characters
if (empty($name)) {
    $name = 'User';
}

// Context: identifies the purpose of this OTP (e.g. 'login', 'register', 'reset')
// Whitelist only known contexts to prevent session key injection
$allowedContexts = ['login', 'register', 'reset'];
$rawContext = trim($_POST['context'] ?? 'login');
$context    = in_array($rawContext, $allowedContexts, true) ? $rawContext : 'login';

// ─── Rate-Limiting: Cooldown Check ───────────────────────────
// Prevent spamming the OTP send endpoint (even from form resubmissions)
$sessionKey = 'otp_data_' . $context;
$existing   = $_SESSION[$sessionKey] ?? null;

if ($existing && isset($existing['last_sent'])) {
    $elapsed = time() - $existing['last_sent'];
    if ($elapsed < OTP_RESEND_COOLDOWN) {
        $wait = OTP_RESEND_COOLDOWN - $elapsed;
        echo json_encode([
            'success' => false,
            'error'   => "Please wait {$wait} seconds before requesting a new OTP.",
            'wait'    => $wait,
        ]);
        exit();
    }
}

// ─── Generate Secure OTP ─────────────────────────────────────
// Uses PHP's random_int() which is cryptographically secure (CSPRNG)
$otp = generateOtp();  // Returns zero-padded 6-digit string

// Store a HASH of the OTP in session — never store plaintext OTP in session
// password_hash with BCRYPT is overkill for OTP; use sha256 with a salt instead
// We store a salted hash so even if session is dumped, OTP cannot be read directly
$sessionSalt = bin2hex(random_bytes(16));  // 32-char random hex salt
$otpHash     = hash_hmac('sha256', $otp, $sessionSalt);

// ─── Store OTP Metadata in Session ───────────────────────────
$_SESSION[$sessionKey] = [
    'hash'      => $otpHash,               // HMAC-SHA256 of the OTP
    'salt'      => $sessionSalt,           // Salt used for HMAC (stored alongside hash)
    'email'     => $email,                 // Target email (for verification)
    'name'      => $name,                  // Recipient name
    'expiry'    => time() + OTP_EXPIRY_SECONDS,  // Unix timestamp: OTP expires at
    'attempts'  => 0,                      // Wrong attempt counter
    'last_sent' => time(),                 // When this OTP was last generated/sent
    'context'   => $context,              // Purpose of this OTP
];

// Regenerate session ID to prevent session fixation attacks
// (only safe to do when no output has been sent yet — we have Content-Type header above)
session_regenerate_id(true);

// ─── Send OTP Email ──────────────────────────────────────────
$result = sendOtpEmail($email, $name, $otp);

// ─── Respond ─────────────────────────────────────────────────
if ($result === true) {
    // Mask email for privacy in the response: joh***@example.com
    $maskedEmail = preg_replace('/(?<=.{3}).(?=[^@]*@)/', '*', $email);

    echo json_encode([
        'success'      => true,
        'masked_email' => $maskedEmail,
        'expires_in'   => OTP_EXPIRY_SECONDS,
        'message'      => "OTP sent to {$maskedEmail}. Valid for 5 minutes.",
    ]);
} else {
    // Log the error server-side (don't expose internal details to client)
    error_log("[OTP Send Error] [{$context}] Email: {$email} | Error: {$result}");

    echo json_encode([
        'success' => false,
        'error'   => 'Failed to send OTP. Please try again shortly.',
    ]);
}
