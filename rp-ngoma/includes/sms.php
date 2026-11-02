<?php
/** Sends an SMS through Africa's Talking, or simulates it when no API key is set. Always logs to sms_log. */
function send_sms(int $userId, string $phone, string $message): string {
    $status = 'simulated'; $response = 'No API key set in config.php; message saved only.';
    if (SMS_API_KEY !== '' && function_exists('curl_init')) {
        $post = ['username' => SMS_USERNAME, 'to' => $phone, 'message' => $message];
        if (SMS_SENDER !== '') $post['from'] = SMS_SENDER;
        $ch = curl_init(SMS_URL);
        curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post), CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['apiKey: ' . SMS_API_KEY, 'Accept: application/json']]);
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $status = ($response !== false && $code >= 200 && $code < 300) ? 'sent' : 'failed';
        if ($response === false) $response = curl_error($ch);
        curl_close($ch);
    }
    db()->prepare('INSERT INTO sms_log (user_id,phone,message,status,response) VALUES (?,?,?,?,?)')
        ->execute([$userId, $phone, $message, $status, (string)$response]);
    return $status;
}
