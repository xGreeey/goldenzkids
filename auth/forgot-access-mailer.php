<?php
require_once __DIR__ . '/../config/app.php';



// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load the exact files you just uploaded
require APP_ROOT . '/PHPMailer/Exception.php';
require APP_ROOT . '/PHPMailer/PHPMailer.php';
require APP_ROOT . '/PHPMailer/SMTP.php';

// Create a new PHPMailer instance
$mail = new PHPMailer(true);
$mail->Mailer = "SMTP";

$email_Err = null;
    
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST['email'];
      
       if (!preg_match('/^abc\.guard[0-9]{4}@gmail\.com$/i', trim($email))) {
        $email_Err = 'Please enter the registered email address on your employee file.';
       }

    try {
      $mail->SMTPDebug = SMTP::DEBUG_SERVER;
$mail->SMTPDebug = 3; //Alternative to above constant
      $mail -> isSMTP();
      $mail -> HOST = "'smtp.gmail.com";
      $mail -> SMTPAuth = TRUE;
      $mail -> Username = "abc.guard0021@gmail.com";
      $mail -> Password = "xrkn qpzy oqwp qoxg";
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail -> Port = 587;
        
      $mail -> setFrom("abc.guard0021@gmail.com", "Guard");
      $mail -> addAddress("abc.admin0001@gmail.com", "Admin");
                       
      $mail -> Subject = "Pin Recovery Request";
      $mail  -> Body = "sent request for email recovery";
                       
     if($mail -> send()) {
         echo "Message sent successfully";
     } else {
         echo "Message not sent" . $mail->ErrorInfo;
     }
      
    } catch (Exception $e) {
         echo "Message not sent" . $mail->ErrorInfo;
    }
        
    }
        
        
      


    
    
?>