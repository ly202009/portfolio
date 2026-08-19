
<?php
$ENV;

try{
  $ENV = parse_ini_file(".env");
}catch(Exception $e){
  echo "".$e->getMessage()."";
}
$name = $_POST["name"];
$email = $_POST["email"];
$subject = $_POST["subject"];
$message = $_POST["message"];
require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

$mail = new PHPMailer(true);

// $mail->SMTPDebug = SMTP::DEBUG_SERVER;

// -----------------
// Config
// -----------------
$mail->isSMTP();
$mail->SMTPAuth = true;

$mail->Host = "smtp.gmail.com";
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

$mail->Port = 587;

$mail->Username = $ENV["SMTP_USERNAME"];
$mail->Password = $ENV["SMTP_PASSWORD"];


$mail->setFrom($ENV["SMTP_USERNAME"], "LiwenYao.ca Contact Form");

// -----------------
// Email to me
// -----------------

$mail->addReplyTo($email, $name);
$mail->addAddress("liwen.y37@gmail.com");

$mail->Subject = $subject;
$mail->Body = "Name: $name
Email: $email

Message:
$message";

try{
  $mail->send();
}catch(Exception $e){
  echo "". $e->getMessage();
}

// -----------------
// Send confirmation mail
// -----------------
$mail->clearAddresses();
$mail->clearReplyTos();

$mail->addAddress($email, $name);

$mail->Subject = "LiwenYao.ca Contact Form: $subject";
$mail->Body = "Thanks for reaching out! Here's a receipt:

Subject: $subject

Your Message:
$message";

try{
  $mail->send();
}catch(Exception $e){
  echo "". $e->getMessage();
}

// Leave php
header("Location: sent.html");
?>