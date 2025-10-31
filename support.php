<?php

require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// robot detection
$honeypot = trim($_POST["email_real"]);     

if(!empty($honeypot)) {
  header('Location: index.html');
  exit();
}

header('Content-Type: application/json');

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = sanitizeInput($_POST['name'] ?? '');
    $email = filter_var($_POST['psta_adrss'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = sanitizeInput($_POST['msg_subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $response['success'] = false;
        $response['message'] = 'Please fill in all required fields.';
        echo json_encode($response);
        return;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['success'] = false;
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        return;
    }

    $mail = new PHPMailer(true);

    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;

    $mail->isSMTP();
    $mail->SMTPAuth = true;

    $mail->Host       = 'smtp.gmail.com';  
    $mail->SMTPSecure = 'ssl';            //Enable implicit SSL encryption
    $mail->Port       = 465;            

    $mail->Username = "support@smnway.com";
    $mail->Password = "xyehizfsxrjdviin";

    //Recipients
    $mail->setFrom($email, $name);
    $mail->addAddress("support@smnway.com", "Support");

    $mail->addReplyTo($email, $name); 

    $mail->Subject = "Contact Form: $subject";
    $mail->Body = createContactEmail($name, $email, $subject, $message);

    if($mail->send()){
        $response['success'] = true;
        $response['message'] = 'Thank you for your message! We will get back to you soon.';

    }else {
        $response['success'] = false;
        $response['message'] = 'Failed to submit form. Please try again or contact us directly.';
    }

   
    echo json_encode($response);
}

function createContactEmail($name, $email, $subject, $message) {
    $email_message = "New Contact Form Submission\n\n";
    $email_message .= "Name: $name\n";
    $email_message .= "Email: $email\n";
    $email_message .= "Subject: $subject\n\n";
    $email_message .= "Message:\n";
    $email_message .= "$message\n\n";
    $email_message .= "Submitted on: " . date('Y-m-d H:i:s') . "\n";
    
    return $email_message;
}

function sanitizeInput($input) {
    if (is_string($input)) {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    return '';
}

?>
