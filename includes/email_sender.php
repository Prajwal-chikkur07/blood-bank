<?php
/**
 * email_sender.php
 * Sends emails via Brevo Transactional Email API v3.
 * Uses cURL directly — no SDK or SMTP required.
 */

require_once(__DIR__ . '/../config/email_config.php');

/**
 * Send a transactional email via Brevo API.
 *
 * @param string $toEmailAddress  Recipient email
 * @param string $toName          Recipient name
 * @param string $subject         Email subject
 * @param string $bodyText        Plain text body
 * @param string $bodyHtml        HTML body (optional)
 * @return array ['status' => 'success'|'error', 'message' => '...']
 */
function sendEmail($toEmailAddress, $toName, $subject, $bodyText, $bodyHtml = '') {
    // Ensure logs directory exists
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    if (!defined('EMAIL_ENABLED') || !EMAIL_ENABLED) {
        $logEntry = date('Y-m-d H:i:s') . " | EMAIL DISABLED | To: $toEmailAddress | Subject: $subject\n";
        file_put_contents(__DIR__ . '/../logs/sms_log.txt', $logEntry, FILE_APPEND);
        return ['status' => 'success', 'message' => 'Email sending is disabled.'];
    }

    $payload = [
        "sender" => [
            "name"  => EMAIL_FROM_NAME,
            "email" => EMAIL_FROM_ADDRESS
        ],
        "to" => [
            ["email" => $toEmailAddress, "name" => $toName]
        ],
        "subject"     => $subject,
        "textContent" => $bodyText,
        "htmlContent" => !empty($bodyHtml) ? $bodyHtml : "<p>" . nl2br(htmlspecialchars($bodyText)) . "</p>"
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://api.brevo.com/v3/smtp/email",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            "accept: application/json",
            "api-key: " . BREVO_API_KEY,
            "content-type: application/json"
        ]
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $logEntry = date('Y-m-d H:i:s') . " | EMAIL | To: $toEmailAddress | Subject: $subject | Code: $httpCode | Response: $response | Error: $err\n";
    file_put_contents(__DIR__ . '/../logs/sms_log.txt', $logEntry, FILE_APPEND);

    if ($err) {
        return ['status' => 'error', 'message' => 'cURL Error: ' . $err];
    }

    $respArr = json_decode($response, true);

    // Brevo returns 201 on success
    if ($httpCode === 201 && isset($respArr['messageId'])) {
        return ['status' => 'success', 'message' => "Email sent! Message ID: " . $respArr['messageId']];
    } else {
        $errMsg = $respArr['message'] ?? ($respArr['error'] ?? 'Unknown error');
        return ['status' => 'error', 'message' => "Brevo API Error ($httpCode): $errMsg"];
    }
}

/**
 * Build a styled HTML email body for Blood Bank notifications.
 *
 * @param string $donorName  Donor's name
 * @param string $body       Main HTML body content
 * @return string            Full HTML email
 */
function buildEmailTemplate($donorName, $body) {
    return "
    <html>
    <body style='font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px;'>
        <div style='max-width: 600px; margin: auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>
            <div style='background: #e53e3e; padding: 24px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 24px;'>&#10084; Blood Bank</h1>
            </div>
            <div style='padding: 32px;'>
                <p style='font-size: 16px; color: #333;'>Dear <strong>$donorName</strong>,</p>
                $body
                <p style='color: #666; font-size: 14px; margin-top: 32px;'>Thank you for being a lifesaver.<br><strong>Blood Bank Team</strong></p>
            </div>
            <div style='background: #f8f8f8; padding: 16px; text-align: center; color: #999; font-size: 12px;'>
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </body>
    </html>";
}
?>
