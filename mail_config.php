<?php
/*
================================================================
  Team 2 - Academic Planning & Monitoring System
  Mail Configuration — Upgraded to Brevo v3 API (with PHPMailer fallback)
  File: mail_config.php
================================================================

  ──────────────────────────────────────────────────────────────
  BREVO API SETUP (Recommended — Free 300 emails/day)
  ──────────────────────────────────────────────────────────────
  1. Sign up at https://app.brevo.com/
  2. Go to: Account (top right) → SMTP & API → API Keys
  3. Click "Generate a new API key" → copy it
  4. Paste it in BREVO_API_KEY below.
  5. Verify your sender email at: Senders & IPs → Senders → Add a Sender
  6. Update MAIL_FROM_ADDRESS to match that verified sender.

  ──────────────────────────────────────────────────────────────
  GMAIL FALLBACK (PHPMailer via SMTP)
  ──────────────────────────────────────────────────────────────
  If you prefer Gmail SMTP as a fallback, set MAIL_PROVIDER to
  'gmail' and fill in GMAIL_ADDRESS + GMAIL_APP_PASSWORD.
  To get a Gmail App Password:
    1. Enable 2-Step Verification on your Google Account
    2. Go to: Security → App Passwords → Mail + Windows PC
    3. Paste the 16-digit password in GMAIL_APP_PASSWORD below.
  ──────────────────────────────────────────────────────────────
*/

// ══════════════════════════════════════════════════════════════
// ▶ PROVIDER SELECTION — Set one of: 'brevo' | 'gmail'
// ══════════════════════════════════════════════════════════════
define('MAIL_PROVIDER', 'gmail');

// ── Brevo API Key ─────────────────────────────────────────────
define('BREVO_API_KEY', 'YOUR_BREVO_API_KEY_HERE');  // ← Paste Brevo v3 API key here

// ── Gmail SMTP Settings ───────────────────────────────────────
define('GMAIL_ADDRESS',      'madhurdhadve7@gmail.com');
define('GMAIL_APP_PASSWORD', 'lujm vlrx kmfv cubx');

// ── Sender Identity ───────────────────────────────────────────
define('MAIL_FROM_ADDRESS', GMAIL_ADDRESS);
define('MAIL_FROM_NAME',    'Team 2 Academic Portal');

// ── OTP Settings ──────────────────────────────────────────────
define('OTP_EXPIRY_SECONDS',  300);  // 5 minutes
define('OTP_MAX_ATTEMPTS',    5);    // Lockout after 5 wrong guesses
define('OTP_RESEND_COOLDOWN', 30);   // Wait 30s before resending
define('APP_NAME',    'Team 2 Academic Portal');
define('APP_YEAR',    '2026');
define('APP_TAGLINE', 'Academic Planning & Monitoring System');

// ── Load PHPMailer (only needed for Gmail SMTP fallback) ──────
if (MAIL_PROVIDER === 'gmail') {
    if (file_exists(__DIR__ . '/phpmailer/PHPMailer.php')) {
        require_once __DIR__ . '/phpmailer/Exception.php';
        require_once __DIR__ . '/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/SMTP.php';
    }
}

// ════════════════════════════════════════════════════════════════
// FUNCTIONS
// ════════════════════════════════════════════════════════════════

/**
 * Generates a cryptographically secure 6-digit OTP.
 * Uses PHP's random_int() — CSPRNG-backed, safe for security use.
 *
 * @return string  Zero-padded 6-digit OTP e.g. "047291"
 */
function generateOtp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ────────────────────────────────────────────────────────────────
// UNIFIED SEND FUNCTION — Called by login.php, register, etc.
// ────────────────────────────────────────────────────────────────

/**
 * Sends an OTP email via the configured mail provider.
 * Drops in as a replacement for the old sendOtpEmail() signature.
 *
 * @param string $to_email  Recipient email address
 * @param string $to_name   Recipient display name
 * @param string $otp       6-digit OTP string
 * @return true|string      true on success, error string on failure
 */
function sendOtpEmail(string $to_email, string $to_name, string $otp): true|string {
    // Input sanitization
    $to_email = filter_var(trim($to_email), FILTER_SANITIZE_EMAIL);
    $to_name  = htmlspecialchars(strip_tags(trim($to_name)), ENT_QUOTES, 'UTF-8');

    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address.';
    }

    if (MAIL_PROVIDER === 'brevo') {
        return _sendViaBrevo($to_email, $to_name, $otp);
    }

    if (MAIL_PROVIDER === 'gmail') {
        return _sendViaGmail($to_email, $to_name, $otp);
    }

    return 'Unknown MAIL_PROVIDER: ' . MAIL_PROVIDER;
}


// ═══════════════════════════════════════════════════════════════
// PROVIDER: BREVO v3 REST API
// ═══════════════════════════════════════════════════════════════

/**
 * Sends email via Brevo Transactional Email API v3 (cURL).
 * Docs: https://developers.brevo.com/reference/sendtransacemail
 */
function _sendViaBrevo(string $email, string $name, string $otp): true|string {
    $payload = json_encode([
        'sender'      => ['name' => MAIL_FROM_NAME, 'email' => MAIL_FROM_ADDRESS],
        'to'          => [['email' => $email, 'name' => $name]],
        'subject'     => 'Your OTP Code — ' . APP_NAME,
        'htmlContent' => getOtpEmailTemplate($name, $otp),
        'textContent' => "Hello {$name},\n\nYour OTP for " . APP_NAME . " is: {$otp}\n\nValid for 5 minutes. Do not share it with anyone.",
        'tags'        => ['otp'],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . BREVO_API_KEY,  // ← Injected from constant above
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return 'cURL error: ' . $curlErr;
    }

    // Brevo returns HTTP 201 on success
    if ($httpCode === 201) {
        return true;
    }

    $body = json_decode($response, true);
    return 'Brevo error: ' . ($body['message'] ?? "HTTP {$httpCode}");
}


// ═══════════════════════════════════════════════════════════════
// PROVIDER: GMAIL via PHPMailer SMTP (Fallback)
// ═══════════════════════════════════════════════════════════════

/**
 * Sends email via Gmail SMTP using PHPMailer.
 * Requires phpmailer/ directory and GMAIL_APP_PASSWORD.
 */
function _sendViaGmail(string $email, string $name, string $otp): true|string {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return 'PHPMailer not loaded. Set MAIL_PROVIDER to brevo.';
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_ADDRESS;
        $mail->Password   = GMAIL_APP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(GMAIL_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your OTP Code — ' . APP_NAME;
        $mail->Body    = getOtpEmailTemplate($name, $otp);
        $mail->AltBody = "Hello {$name},\n\nYour OTP is: {$otp}\n\nValid for 5 minutes.";

        $mail->send();
        return true;
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        return $mail->ErrorInfo;
    }
}


// ═══════════════════════════════════════════════════════════════
// HTML EMAIL TEMPLATE
// ═══════════════════════════════════════════════════════════════

/**
 * Returns a premium, responsive HTML email template for OTP delivery.
 * Uses table-based layout + inline CSS for full email client compatibility.
 *
 * @param string $name  Recipient display name
 * @param string $otp   6-digit OTP string
 * @return string       Complete HTML email body
 */
function getOtpEmailTemplate(string $name, string $otp): string {
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $year     = APP_YEAR;
    $appName  = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');
    $tagline  = htmlspecialchars(APP_TAGLINE, ENT_QUOTES, 'UTF-8');

    // Build individual OTP digit boxes (table cells)
    $digitBoxes = '';
    foreach (str_split($safeOtp) as $d) {
        $digitBoxes .= "
        <td style='width:48px;height:60px;text-align:center;vertical-align:middle;
                   font-size:28px;font-weight:800;background:#f0f4ff;
                   border:2px solid #2563eb;border-radius:10px;color:#2563eb;
                   padding:0;'>{$d}</td>
        <td style='width:6px;'></td>";
    }

    return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Your OTP — {$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Arial,sans-serif;">

<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f1f5f9;padding:48px 16px;">
<tr><td align="center">

  <table role="presentation" cellpadding="0" cellspacing="0" width="560" style="max-width:560px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.10);">

    <!-- HEADER -->
    <tr>
      <td style="background:linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%);padding:36px 40px;text-align:center;">
        <div style="font-size:36px;margin-bottom:10px;">🎓</div>
        <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">{$appName}</h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.80);font-size:13px;">{$tagline}</p>
      </td>
    </tr>

    <!-- BODY -->
    <tr>
      <td style="padding:40px 40px 32px;">
        <p style="margin:0 0 6px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">Login Verification</p>
        <h2 style="margin:0 0 18px;color:#0f172a;font-size:20px;font-weight:700;">Hello, {$safeName} 👋</h2>
        <p style="margin:0 0 28px;color:#475569;font-size:14px;line-height:1.7;">
          We received a login request for your <strong>{$appName}</strong> account.
          Use the one-time password below to complete your sign-in.
          This code is valid for <strong>5 minutes</strong> only.
        </p>

        <!-- OTP BOX -->
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f8faff;border:1px solid #e2e8f0;border-radius:14px;margin-bottom:28px;">
          <tr><td style="padding:28px 20px;" align="center">
            <p style="margin:0 0 18px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">Your One-Time Password</p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;"><tr>{$digitBoxes}</tr></table>
            <p style="margin:18px 0 0;color:#94a3b8;font-size:12px;">⏱ Expires in <strong>5 minutes</strong></p>
          </td></tr>
        </table>

        <!-- SECURITY NOTICE -->
        <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#fff7ed;border-left:4px solid #f97316;border-radius:8px;margin-bottom:28px;">
          <tr><td style="padding:16px 18px;">
            <p style="margin:0;color:#9a3412;font-size:13px;line-height:1.6;">
              🔒 <strong>Security Notice:</strong> Never share this OTP with anyone.
              Our team will <strong>never</strong> ask you for this code via phone, chat, or email.
            </p>
          </td></tr>
        </table>

        <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.7;">
          If you did not request this login, you can safely ignore this email. Your account remains secure.
        </p>
      </td>
    </tr>

    <!-- DIVIDER -->
    <tr><td style="height:1px;background:#e2e8f0;"></td></tr>

    <!-- FOOTER -->
    <tr>
      <td style="background:#f8fafc;padding:20px 40px;text-align:center;">
        <p style="margin:0 0 4px;color:#94a3b8;font-size:12px;">&copy; {$year} {$appName}</p>
        <p style="margin:0;color:#cbd5e1;font-size:11px;">Built for educational efficiency · {$tagline}</p>
      </td>
    </tr>

  </table>

</td></tr>
</table>

</body>
</html>
HTML;
}
