<?php
/**
 * ============================================================
 *  CONFIGURATION
 * ============================================================
 * Fill these in before running the app.
 * NEVER commit this file to a public repo with real credentials.
 */

// --- Gemini API ---
define('GEMINI_API_KEY', 'YOUR_API_KEY');
define('GEMINI_MODEL', 'gemini-2.5-flash-lite');//gemini-2.5-flash-lite

// --- Gmail SMTP ---
// IMPORTANT: You cannot use your normal Gmail password.
// You must generate a 16-character "App Password":
//   1. Enable 2-Step Verification on your Google account
//   2. Go to https://myaccount.google.com/apppasswords
//   3. Generate a password for "Mail" and paste it below
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'yourmail@gmail.com');
define('SMTP_APP_PASSWORD', 'xxxxxxxxxxxxxxxx'); // 16-char app password, no spaces
define('SMTP_FROM_NAME', 'YOUR NAME');