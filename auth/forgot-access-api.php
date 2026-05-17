<?php
    
require_once __DIR__ . '/../config/app.php';

$email_Err = null;
$http_code = null;
$response = null;

// 1. Check if the form was actually submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Grab the email the guard typed in
    $guard_email = $_POST['email'];
    

    

    // 2. CHECK YOUR DATABASE HERE
    // (Your developer should write the SQL here to check if $guard_email exists in the users table)
    
        $sql = "SELECT Email FROM users WHERE Email = '$guard_email';";
    $email_check = $conn->query($sql);
    $email_exists_in_db = mysqli_num_rows($email_check);
    
    if ($email_exists_in_db === 1) {
        
        // Generate a random 4-digit OTP
        $otp_code = rand(100000, 999999);
        
        // (Optional: Save this $otp_code to your database so you can verify it on the next screen)

        // ==========================================
        // 3. PASTE THE MAILJET SCRIPT HERE
        // ==========================================
        
        $api_key = 'ec467f55c90e121acde6755b38c20cd6';
        $secret_key = 'e56d3d27662e874bfaccc93960809e1b';
        $url = 'https://api.mailjet.com/v3.1/send';

        $data = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => 'abc.admin0001@gmail.com', 
                        'Name' => 'ABC Command Center'
                    ],
                    'To' => [
                        [
                            'Email' => $guard_email, // Uses the email from the form!
                            'Name' => 'Guard'
                        ]
                    ],
                    'Subject' => 'ABC Security: PIN Reset Request',
                    'HTMLPart' => 'Your One-Time Password is: <b>' . $otp_code . '</b>. Enter this in the portal to authorize a new PIN.'
                ]
            ]
        ];

      if ($http_code == 200) {
            // Success! Redirect the guard to the "Enter OTP" screen
            header('Location: ' . app_url('auth/enter-otp.php'));
            exit();
        } else {
            // DEBUG MODE: Print the exact HTTP code and Mailjet's error message
            $email_Err = "Failed! HTTP Code: " . $http_code . " <br> Mailjet Response: " . $response;
        }
        
    } else {
        // If the email is not in the database, trigger the error variable for the HTML page
        $email_Err = "Email not found in the system.";
    }
}
?>