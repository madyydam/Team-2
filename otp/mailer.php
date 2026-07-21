<?php
/*
================================================================
  Team 2 - Academic Planning & Monitoring System
  OTP Email Mailer — Multi-Provider (Brevo / SendGrid / Mailgun)
  File: otp/mailer.php

  This file provides:
    1. sendBrevoOtp()   — Brevo v3 REST API via cURL
    2. sendSendGridOtp() — SendGrid v3 REST API via cURL
    3. sendMailgunOtp() — Mailgun REST API via cURL
    4. sendOtpEmail()   — Unified dispatcher (reads MAIL_PROVIDER)
    5. getOtpEmailHtml() — Premium HTML email template
    6. generateOtp()    — Cryptographically secure 6-digit OTP
================================================================
*/

// Prevent direct file access
if (!defined('OTP_EXPIRY_SECONDS')) {
    require_once __DIR__ . '/config.php';
}

// ============================================================
// 1. SECURE OTP GENERATION
// ============================================================
/**
 * Generates a cryptographically secure 6-digit OTP using random_int().
 * random_int() is CSPRNG-backed (unlike rand() or mt_rand()).
 *
 * @return string  Zero-padded 6-digit string e.g. "004823"
 */
function generateOtp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}


// ============================================================
// 2. BREVO (SENDINBLUE) v3 API — cURL Implementation
// ============================================================
/**
 * Send OTP via Brevo Transactional Email API v3.
 * Docs: https://developers.brevo.com/reference/sendtransacemail
 *
 * @param string $toEmail   Recipient's email address
 * @param string $toName    Recipient's display name
 * @param string $otp       6-digit OTP string
 * @return true|string      true on success, error message string on failure
 */
function sendBrevoOtp(string $toEmail, string $toName, string $otp): true|string {
    // ─── Brevo API Endpoint ──────────────────────────────────
    $apiUrl = 'https://api.brevo.com/v3/smtp/email';

    // ─── Payload per Brevo v3 spec ───────────────────────────
    $payload = json_encode([
        'sender'      => [
            'name'  => MAIL_FROM_NAME,
            'email' => MAIL_FROM_ADDRESS,
        ],
        'to'          => [[
            'email' => $toEmail,
            'name'  => $toName,
        ]],
        'subject'     => 'Your OTP Code — ' . APP_NAME,
        'htmlContent' => getOtpEmailHtml($toName, $otp),
        'textContent' => getOtpEmailPlainText($toName, $otp),  // Plain-text fallback
        'tags'        => ['otp', 'authentication'],
    ], JSON_UNESCAPED_UNICODE);

    // ─── cURL Request ────────────────────────────────────────
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . BREVO_API_KEY,    // ← API key injected from config.php
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // ─── Error Handling ──────────────────────────────────────
    if ($curlErr) {
        return 'cURL error: ' . $curlErr;
    }

    $decoded = json_decode($response, true);

    // Brevo returns 201 Created on success
    if ($httpCode === 201 && isset($decoded['messageId'])) {
        return true;
    }

    // Extract Brevo error message
    $errMsg = $decoded['message'] ?? $decoded['code'] ?? "HTTP $httpCode: $response";
    return 'Brevo error: ' . $errMsg;
}


// ============================================================
// 3. SENDGRID v3 API — cURL Implementation
// ============================================================
/**
 * Send OTP via SendGrid Transactional Email API v3.
 * Docs: https://docs.sendgrid.com/api-reference/mail-send/mail-send
 *
 * @param string $toEmail   Recipient's email address
 * @param string $toName    Recipient's display name
 * @param string $otp       6-digit OTP string
 * @return true|string      true on success, error message string on failure
 */
function sendSendGridOtp(string $toEmail, string $toName, string $otp): true|string {
    $apiUrl = 'https://api.sendgrid.com/v3/mail/send';

    $payload = json_encode([
        'personalizations' => [[
            'to'      => [['email' => $toEmail, 'name' => $toName]],
            'subject' => 'Your OTP Code — ' . APP_NAME,
        ]],
        'from'             => ['email' => MAIL_FROM_ADDRESS, 'name' => MAIL_FROM_NAME],
        'content'          => [
            ['type' => 'text/plain', 'value' => getOtpEmailPlainText($toName, $otp)],
            ['type' => 'text/html',  'value' => getOtpEmailHtml($toName, $otp)],
        ],
        'categories'       => ['otp', 'authentication'],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . SENDGRID_API_KEY,  // ← API key from config.php
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return 'cURL error: ' . $curlErr;
    }

    // SendGrid returns 202 Accepted on success (empty body)
    if ($httpCode === 202) {
        return true;
    }

    $decoded = json_decode($response, true);
    $errors  = $decoded['errors'][0]['message'] ?? "HTTP $httpCode";
    return 'SendGrid error: ' . $errors;
}


// ============================================================
// 4. MAILGUN REST API — cURL Implementation
// ============================================================
/**
 * Send OTP via Mailgun REST API.
 * Docs: https://documentation.mailgun.com/docs/mailgun/api-reference/openapi-final/tag/Messages/
 *
 * @param string $toEmail   Recipient's email address
 * @param string $toName    Recipient's display name
 * @param string $otp       6-digit OTP string
 * @return true|string      true on success, error message string on failure
 */
function sendMailgunOtp(string $toEmail, string $toName, string $otp): true|string {
    // Mailgun API endpoints differ by region
    $region = (MAILGUN_REGION === 'eu') ? 'api.eu.mailgun.net' : 'api.mailgun.net';
    $apiUrl = "https://{$region}/v3/" . MAILGUN_DOMAIN . '/messages';

    $postData = [
        'from'    => MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'to'      => "{$toName} <{$toEmail}>",
        'subject' => 'Your OTP Code — ' . APP_NAME,
        'html'    => getOtpEmailHtml($toName, $otp),
        'text'    => getOtpEmailPlainText($toName, $otp),
        'o:tag'   => 'otp-authentication',
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_TIMEOUT        => 15,
        // Mailgun uses HTTP Basic Auth: username='api', password=your_api_key
        CURLOPT_USERPWD        => 'api:' . MAILGUN_API_KEY,  // ← API key from config.php
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        return 'cURL error: ' . $curlErr;
    }

    $decoded = json_decode($response, true);

    // Mailgun returns 200 with { id, message: 'Queued. Thank you.' }
    if ($httpCode === 200 && isset($decoded['id'])) {
        return true;
    }

    $errMsg = $decoded['message'] ?? "HTTP $httpCode: $response";
    return 'Mailgun error: ' . $errMsg;
}


// ============================================================
// 5. UNIFIED DISPATCHER
// ============================================================
/**
 * Dispatches OTP email through the configured provider.
 * Reads the MAIL_PROVIDER constant from config.php.
 *
 * @param string $toEmail   Validated recipient email
 * @param string $toName    Sanitized recipient name
 * @param string $otp       6-digit OTP
 * @return true|string      true on success, error string on failure
 */
function sendOtpEmail(string $toEmail, string $toName, string $otp): true|string {
    // Sanitize inputs before dispatch
    $toEmail = filter_var(trim($toEmail), FILTER_SANITIZE_EMAIL);
    $toName  = htmlspecialchars(trim($toName), ENT_QUOTES, 'UTF-8');

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return 'Invalid email address provided.';
    }

    switch (MAIL_PROVIDER) {
        case 'brevo':
            return sendBrevoOtp($toEmail, $toName, $otp);

        case 'sendgrid':
            return sendSendGridOtp($toEmail, $toName, $otp);

        case 'mailgun':
            return sendMailgunOtp($toEmail, $toName, $otp);

        default:
            return 'Unknown mail provider configured: ' . MAIL_PROVIDER;
    }
}


// ============================================================
// 6. PREMIUM HTML EMAIL TEMPLATE
// ============================================================
/**
 * Returns a beautiful, responsive HTML email for the OTP.
 * Uses inline CSS for maximum email client compatibility.
 * Table-based layout for Outlook support.
 *
 * @param string $name  Recipient's name
 * @param string $otp   6-digit OTP
 * @return string       Complete HTML document string
 */
function getOtpEmailHtml(string $name, string $otp): string {
    // Escape for HTML safety inside the template
    $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safeOtp  = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');

    // Build individual digit boxes for the OTP display
    $digitBoxesHtml = '';
    foreach (str_split($safeOtp) as $digit) {
        $digitBoxesHtml .= "
        <td style='
            width: 48px;
            height: 60px;
            text-align: center;
            vertical-align: middle;
            font-size: 28px;
            font-weight: 800;
            background: #f0f4ff;
            border: 2px solid #2563eb;
            border-radius: 10px;
            color: #2563eb;
            padding: 0;
        '>{$digit}</td>
        <td style='width:8px;'></td>";
    }

    $year    = APP_YEAR;
    $appName = htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8');
    $tagline = htmlspecialchars(APP_TAGLINE, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Your OTP — {$appName}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Helvetica Neue',Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">

<!-- Email Wrapper -->
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f1f5f9;padding:48px 16px;">
  <tr>
    <td align="center">

      <!-- Card Container (max 560px) -->
      <table role="presentation" cellpadding="0" cellspacing="0" width="560" style="max-width:560px;width:100%;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.10);">

        <!-- ━━━ HEADER ━━━ -->
        <tr>
          <td style="background:linear-gradient(135deg,#1d4ed8 0%,#3b82f6 100%);padding:36px 40px;text-align:center;">
            <!-- Graduation cap icon (text emoji, safe for all clients) -->
            <div style="font-size:36px;margin-bottom:10px;line-height:1;">🎓</div>
            <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;letter-spacing:0.3px;">{$appName}</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.80);font-size:13px;">{$tagline}</p>
          </td>
        </tr>

        <!-- ━━━ BODY ━━━ -->
        <tr>
          <td style="padding:40px 40px 32px;">

            <!-- Greeting label -->
            <p style="margin:0 0 6px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">
              Login Verification
            </p>

            <!-- Personalized greeting -->
            <h2 style="margin:0 0 18px;color:#0f172a;font-size:20px;font-weight:700;">
              Hello, {$safeName} 👋
            </h2>

            <!-- Explanation -->
            <p style="margin:0 0 28px;color:#475569;font-size:14px;line-height:1.7;">
              We received a login request for your <strong>{$appName}</strong> account.
              Use the one-time password (OTP) below to complete your sign-in.
              This code is valid for <strong>5 minutes</strong> only.
            </p>

            <!-- ── OTP Display Box ── -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f8faff;border:1px solid #e2e8f0;border-radius:14px;padding:28px 20px;margin-bottom:28px;">
              <tr>
                <td align="center">
                  <p style="margin:0 0 18px;color:#64748b;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;">
                    Your One-Time Password
                  </p>
                  <!-- OTP Digit Boxes -->
                  <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto;">
                    <tr>
                      {$digitBoxesHtml}
                    </tr>
                  </table>
                  <!-- Expiry notice -->
                  <p style="margin:18px 0 0;color:#94a3b8;font-size:12px;">
                    ⏱ Expires in <strong>5 minutes</strong>
                  </p>
                </td>
              </tr>
            </table>

            <!-- ── Security Notice ── -->
            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#fff7ed;border-left:4px solid #f97316;border-radius:8px;margin-bottom:28px;">
              <tr>
                <td style="padding:16px 18px;">
                  <p style="margin:0;color:#9a3412;font-size:13px;line-height:1.6;">
                    🔒 <strong>Security Notice:</strong> Never share this OTP with anyone.
                    Our team will <strong>never</strong> ask you for this code via phone, chat, or email.
                  </p>
                </td>
              </tr>
            </table>

            <!-- Disclaimer -->
            <p style="margin:0;color:#94a3b8;font-size:12px;line-height:1.7;">
              If you did not request this login, you can safely ignore this email.
              Your account remains secure and no changes have been made.
            </p>

          </td>
        </tr>

        <!-- ━━━ DIVIDER ━━━ -->
        <tr>
          <td style="height:1px;background:#e2e8f0;"></td>
        </tr>

        <!-- ━━━ FOOTER ━━━ -->
        <tr>
          <td style="background:#f8fafc;padding:20px 40px;text-align:center;">
            <p style="margin:0 0 4px;color:#94a3b8;font-size:12px;">
              &copy; {$year} {$appName}
            </p>
            <p style="margin:0;color:#cbd5e1;font-size:11px;">
              Built for educational efficiency · {$tagline}
            </p>
          </td>
        </tr>

      </table>
      <!-- /Card Container -->

    </td>
  </tr>
</table>
<!-- /Email Wrapper -->

</body>
</html>
HTML;
}


// ============================================================
// 7. PLAIN-TEXT FALLBACK
// ============================================================
/**
 * Returns a plain-text version of the OTP email.
 * Used as a fallback for email clients that cannot render HTML.
 */
function getOtpEmailPlainText(string $name, string $otp): string {
    $appName = APP_NAME;
    $tagline = APP_TAGLINE;
    return "
{$appName} | {$tagline}
═══════════════════════════════════

Hello, {$name}!

Your One-Time Password (OTP) is:

  ➤  {$otp}

This code is valid for 5 minutes only.

IMPORTANT: Never share this OTP with anyone.
Our team will never ask you for this code.

If you did not request this login, you can safely ignore this email.

───────────────────────────────────
© " . APP_YEAR . " {$appName}
    ";
}
