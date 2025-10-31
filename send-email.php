<?php

// robot detection
  $honeypot = trim($_POST["email_real"]);     

  if(!empty($honeypot)) {
    echo "BAD ROBOT!"; 
    exit;
  }

$name = trim($_POST["name"]);
$email = trim($_POST["psta_adrss"]);
$subject = trim($_POST["msg_subject"]);
$message = trim($_POST["message"]);

require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

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

$mail->addReplyTo($email, $name); // reply to sender email

$mail->Subject = $subject;
$mail->Body = $message;

$mail->send();

header("Location: index.html");
