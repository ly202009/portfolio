<?php
require __DIR__."/../vendor/autoload.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);

    echo json_encode([
        "success" => false,
        "message" => "Method not allowed."
    ]);

    exit;
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Check "honeypot" (imo a dumb name and I don't think it's thoroughly effective but oh well better than nothing)
if(!empty($_POST["website"]) || !isset($_POST["website"])){
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => ""
  ]);

  exit;
}

// Revalidate inputs server-side
if(empty(trim($_POST["name"])) || !isset($_POST["name"])){ // Name is empty
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Invalid name."
  ]);

  exit;
}

if(!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($_POST["email"])) || !isset($_POST["email"])){ // Email does not fit regex expectations for a normal email address
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Invalid email."
  ]);

  exit;
}
if(empty(trim($_POST["subject"])) || !isset($_POST["subject"])){ // Subject is not included
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Invalid subject."
  ]);

  exit;
}

if(empty(trim($_POST["message"])) || !isset($_POST["message"])){ // Message is not included
  http_response_code(400);

  echo json_encode([
    "success" => false,
    "message" => "Invalid message."
  ]);

  exit;
}

// ----------- IP limiter -----------------
$ip = $_SERVER["REMOTE_ADDR"]; // Get ip address

$rateLimitFile = __DIR__."/rate_limits/".hash("sha256", $ip).".txt"; // Create new file to track current ip

// Set user info
$now = time();
$limit = 300;
$window = 600; // 10 minutes

if (!is_dir(__DIR__."/rate_limits")) { // If rate_limits folder does not exist then create folder
    mkdir(__DIR__."/rate_limits", 0755, true);
}

$attempts = [];

if (file_exists($rateLimitFile)) { // Get previous attempts if the file exists
    $attempts = json_decode(file_get_contents($rateLimitFile), true) ?? [];
}

// Remove attempts older than 10 minutes
$attempts = array_filter(
    $attempts,
    fn($timestamp) => $timestamp > $now - $window
);

if (count($attempts) >= $limit) {
    http_response_code(429); // Too many requests

    echo json_encode([
        "success" => false,
        "message" => "Too many submissions. Try again later."
    ]);

    exit;
}

// ---------------- Messaging system ------------

$ENV;

try{
  $ENV = parse_ini_file(__DIR__."/../secrets/.env");
}catch(Exception $e){
  echo json_encode([
    "success" => false,
    "message" => "Failed to parse ENV file."
  ]);
  exit;
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
// Send confirmation email (confirm email given exists)
// -----------------

$mail->Subject = "LiwenYao.ca Form Receipt: $subject";
$mail->Body = "Thanks for reaching out! Here's a receipt:

Subject: $subject

Your Message:
$message";

try{
  $mail->addAddress($email, $name);
  $mail->send();
}catch(Exception $e){
  http_response_code(500);

  echo json_encode([
    "success" => false,
    "message" => "Failed to send confirmation email."
  ]);
  exit;
}

// -----------------
// Send copy of email to me
// -----------------
// Clear previous email
$mail->clearAddresses();

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
  http_response_code(500);

  echo json_encode([
    "success" => false,
    "message" => "Failed to send message to Liwen's email. You can email me directly at liwen.y37@gmail.com"
  ]);
  exit;
}

// ------- Update IP info (assuming mailing worked completely) -------------

// Record this attempt
$attempts[] = $now;

// Creates/updates file
file_put_contents(
    $rateLimitFile,
    json_encode(array_values($attempts))
);

// Send success message
http_response_code(200);

echo json_encode([
  "success" => true,
  "message" => "Message sent!"
]);
exit(0);
?>