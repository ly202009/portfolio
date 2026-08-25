<?php
require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Revalidate inputs server-side
if(trim($_POST["name"]) === ""){ // Name is empty
  exit(1);
}

if(!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($_POST["email"]))){ // Email does not fit regex expectations for a normal email address
  exit(1);
}
if(trim($_POST["subject"]) === ""){ // Subject is not included
  exit(1);
}

if(trim($_POST["message"]) === ""){ // Message is not included
  exit(1);
}



$ENV;

try{
  $ENV = parse_ini_file("./secrets/.env");
}catch(Exception $e){
  echo "".$e->getMessage()."";
  exit(1);
}

$name = $_POST["name"];
$email = $_POST["email"];
$subject = $_POST["subject"];
$message = $_POST["message"];


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
  exit(1);
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
  exit(1);
}

// Leave php
header("Location: sent.html");
?>