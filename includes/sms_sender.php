<?php
require_once(__DIR__ . '/../config/sms_config.php');

function sendSMS($phone, $message)
{
    // Ensure logs directory exists
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    if (defined('SMS_TEST_MODE') && SMS_TEST_MODE === true) {
        $phones = explode(',', $phone);
        $count = 0;
        foreach ($phones as $p) {
            $p = trim($p);
            if (empty($p))
                continue;
            $logEntry = date('Y-m-d H:i:s') . " | SIMULATOR | To: $p | Message: $message | Status: Success\n";
            file_put_contents(__DIR__ . '/../logs/sms_log.txt', $logEntry, FILE_APPEND);
            $count++;
        }
        return ["status" => "success", "message" => "SIMULATOR: Logged $count messages.", "response" => "SIMULATOR_SUCCESS"];
    }

    // Route to WhatsApp if specified or if Fast2SMS fails (optional logic)
    if (defined('SMS_GATEWAY') && SMS_GATEWAY === 'whatsapp') {
        return sendWhatsApp($phone, $message);
    }

    // Default to Fast2SMS
    return sendFast2SMS($phone, $message);
}

/**
 * Send SMS through WhatsApp (Placeholder/Simulator)
 */
function sendWhatsApp($phone, $message)
{
    if (defined('SMS_TEST_MODE') && SMS_TEST_MODE === true) {
        $logEntry = date('Y-m-d H:i:s') . " | WHATSAPP | To: $phone | Message: $message | Status: Success (Simulator)\n";
        file_put_contents(__DIR__ . '/../logs/whatsapp_log.txt', $logEntry, FILE_APPEND);
        return ["status" => "success", "message" => "WHATSAPP SIMULATOR: Logged message for $phone.", "response" => "WHATSAPP_SIMULATOR_SUCCESS"];
    }

    // Example using a generic WhatsApp API (like UltraMsg or similar)
    // You would need to fill in WHATSAPP_INSTANCE_ID and WHATSAPP_TOKEN in config/sms_config.php
    if (!defined('WHATSAPP_INSTANCE_ID') || !defined('WHATSAPP_TOKEN')) {
        return ["status" => "error", "message" => "WhatsApp configuration missing."];
    }

    $params = [
        'token' => WHATSAPP_TOKEN,
        'to' => $phone,
        'body' => $message
    ];

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api.ultramsg.com/" . WHATSAPP_INSTANCE_ID . "/messages/chat",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_HTTPHEADER => array(
            "content-type: application/x-www-form-urlencoded"
        ),
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    $logEntry = date('Y-m-d H:i:s') . " | WHATSAPP | To: $phone | Response: $response | Error: $err\n";
    file_put_contents(__DIR__ . '/../logs/whatsapp_log.txt', $logEntry, FILE_APPEND);

    if ($err) {
        return ["status" => "error", "message" => "WhatsApp cURL Error: " . $err];
    }

    return ["status" => "success", "response" => $response];
}

function sendFast2SMS($phone, $message)
{
    if (defined('SMS_TEST_MODE') && SMS_TEST_MODE === true) {
        $logEntry = date('Y-m-d H:i:s') . " | SIMULATOR (Fast2SMS) | To: $phone | Message: $message | Status: Success\n";
        file_put_contents(__DIR__ . '/../logs/sms_log.txt', $logEntry, FILE_APPEND);
        return ["status" => "success", "message" => "SIMULATOR: Logged message for $phone.", "response" => "SIMULATOR_SUCCESS"];
    }

    $payload = [
        "route" => "q",
        "message" => $message,
        "language" => "english",
        "flash" => 0,
        "numbers" => $phone
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "authorization: " . FAST2SMS_API_KEY,
            "cache-control: no-cache",
            "accept: application/json",
            "content-type: application/json"
        ],
    ]);

    $response = curl_exec($curl);
    $err = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $logEntry = date('Y-m-d H:i:s') . " | Fast2SMS | To: $phone | Code: $httpCode | Response: $response | Error: $err\n";
    file_put_contents(__DIR__ . '/../logs/sms_log.txt', $logEntry, FILE_APPEND);

    if ($err) {
        return ["status" => "error", "message" => "Fast2SMS cURL Error: " . $err];
    } else {
        $respArr = json_decode($response, true);
        if (isset($respArr['return']) && $respArr['return'] == false) {
            return ["status" => "error", "message" => "Gateway Error: " . ($respArr['message'] ?? 'Unknown error'), "response" => $response];
        }
        return ["status" => "success", "response" => $response];
    }
}
?>