<?php
/*
================================================================
  Team 2 - Academic Planning & Monitoring System
  OTP System — Central Configuration
  File: otp/config.php
================================================================
  SETUP INSTRUCTIONS:
  ─────────────────────────────────────────────────────────────
  Option A: Brevo (Sendinblue) — Recommended (free 300 emails/day)
    1. Sign up at https://app.brevo.com/
    2. Go to: Account → SMTP & API → API Keys → Create API Key
    3. Paste the key in BREVO_API_KEY below.

  Option B: SendGrid (free 100 emails/day)
    1. Sign up at https://sendgrid.com/
    2. Go to: Settings → API Keys → Create API Key (Full Access)
    3. Paste the key in SENDGRID_API_KEY below and set
       MAIL_PROVIDER to 'sendgrid'.

  Option C: Mailgun (free 5,000 emails/3 months)
    1. Sign up at https://www.mailgun.com/
    2. Go to: Sending → Domains → Your Domain → API Keys
    3. Paste the key in MAILGUN_API_KEY below and set
       MAIL_PROVIDER to 'mailgun'. Also set MAILGUN_DOMAIN.
  ─────────────────────────────────────────────────────────────
*/

// ── Mail Provider Selection ─────────────────────────────────
// Options: 'brevo' | 'sendgrid' | 'mailgun'
define('MAIL_PROVIDER', 'brevo');

// ── Brevo (Sendinblue) Settings ──────────────────────────────
define('BREVO_API_KEY', 'YOUR_BREVO_API_KEY_HERE');        // ← Paste your Brevo v3 API key here

// ── SendGrid Settings (if using SendGrid) ────────────────────
define('SENDGRID_API_KEY', 'YOUR_SENDGRID_API_KEY_HERE');  // ← Paste your SendGrid API key here

// ── Mailgun Settings (if using Mailgun) ──────────────────────
define('MAILGUN_API_KEY', 'YOUR_MAILGUN_API_KEY_HERE');    // ← Paste your Mailgun private API key here
define('MAILGUN_DOMAIN',  'mg.yourdomain.com');            // ← Your verified Mailgun domain
define('MAILGUN_REGION',  'us');                           // ← 'us' or 'eu'

// ── Sender Identity (must match a verified sender in your mail provider) ──
define('MAIL_FROM_ADDRESS', 'noreply@yourdomain.com');     // ← Verified sender email
define('MAIL_FROM_NAME',    'Team 2 Academic Portal');

// ── OTP Settings ─────────────────────────────────────────────
define('OTP_EXPIRY_SECONDS', 300);   // 5 minutes
define('OTP_MAX_ATTEMPTS',   5);     // Lock after 5 wrong attempts
define('OTP_RESEND_COOLDOWN', 30);   // Seconds before resend is allowed

// ── Application Settings ──────────────────────────────────────
define('APP_NAME',    'Team 2 Academic Portal');
define('APP_YEAR',    '2026');
define('APP_TAGLINE', 'Academic Planning & Monitoring System');
