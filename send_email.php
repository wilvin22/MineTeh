<?php
/**
 * Email sending via Brevo (formerly Sendinblue) HTTPS API.
 * Free tier: 300 emails/day. Works on InfinityFree (outbound HTTPS, not blocked).
 *
 * Setup:
 *  1. Sign up free at https://app.brevo.com
 *  2. Go to SMTP & API → API Keys → Generate a new key
 *  3. Replace BREVO_API_KEY below with your key
 *  4. Replace SENDER_EMAIL with the email you verified in Brevo
 */

define('BREVO_API_KEY',  'x');   // ← paste your Brevo API key
define('SENDER_EMAIL',   'mineteh640@gmail.com');        // ← must be verified in Brevo
define('SENDER_NAME',    'MineTeh');

function sendPasswordResetEmail($to_email, $reset_code, $username) {
    $subject = 'MineTeh — Your Password Reset Code';

    $html = '
    <div style="font-family:Arial,sans-serif;max-width:520px;margin:0 auto;padding:20px;">
        <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:28px;border-radius:12px 12px 0 0;text-align:center;">
            <h1 style="color:white;margin:0;font-size:24px;">🔐 Password Reset</h1>
        </div>
        <div style="background:#f9f9f9;padding:28px;border-radius:0 0 12px 12px;border:1px solid #e0e0e0;">
            <p style="color:#333;">Hi <strong>' . htmlspecialchars($username) . '</strong>,</p>
            <p style="color:#555;">Use the code below to reset your MineTeh password. It expires in <strong>15 minutes</strong>.</p>
            <div style="background:white;border:2px dashed #945a9b;border-radius:10px;padding:20px;text-align:center;margin:20px 0;">
                <span style="font-size:36px;font-weight:bold;color:#945a9b;letter-spacing:8px;font-family:monospace;">' . $reset_code . '</span>
            </div>
            <p style="color:#888;font-size:13px;">If you didn\'t request this, you can safely ignore this email.</p>
        </div>
    </div>';

    $plain = "Hi $username,\n\nYour MineTeh password reset code is: $reset_code\n\nExpires in 15 minutes.\n\nIf you didn't request this, ignore this email.";

    $payload = [
        'sender'     => ['name' => SENDER_NAME, 'email' => SENDER_EMAIL],
        'to'         => [['email' => $to_email]],
        'subject'    => $subject,
        'htmlContent'=> $html,
        'textContent'=> $plain,
    ];

    $ch = curl_init('https://api.brevo.com/v3/smtp/email');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'api-key: ' . BREVO_API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);

    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Brevo returns 201 on success
    return ($http_code === 201);
}
