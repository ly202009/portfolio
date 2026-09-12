<?php
require "vendor/autoload.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

// Check "honeypot" (imo a dumb name and I don't think it's thoroughly effective but oh well better than nothing)
if(!empty($_POST["website"]) || !isset($_POST["website"])){
  exit(0);
}

// Revalidate inputs server-side
if(empty(trim($_POST["name"])) || !isset($_POST["name"])){ // Name is empty
  exit(0);
}

if(!preg_match("/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", trim($_POST["email"])) || !isset($_POST["email"])){ // Email does not fit regex expectations for a normal email address
  exit(0);
}
if(empty(trim($_POST["subject"])) || !isset($_POST["subject"])){ // Subject is not included
  exit(0);
}

if(empty(trim($_POST["message"])) || !isset($_POST["message"])){ // Message is not included
  exit(0);
}

// // ------------------------
// // Recaptcha Stuff
// // ------------------------

// $recaptcha_token = $_POST["g-recaptcha-response"] ?? '';

// // Include Google Cloud dependencies using Composer
// // composer require google/cloud-recaptcha-enterprise
// require 'vendor/autoload.php';
// use Google\Cloud\RecaptchaEnterprise\V1\Client\RecaptchaEnterpriseServiceClient;
// use Google\Cloud\RecaptchaEnterprise\V1\Event;
// use Google\Cloud\RecaptchaEnterprise\V1\Assessment;
// use Google\Cloud\RecaptchaEnterprise\V1\CreateAssessmentRequest;
// use Google\Cloud\RecaptchaEnterprise\V1\TokenProperties\InvalidReason;
// /**
// * Create an assessment to analyze the risk of a UI action.
// * @param string $siteKey The key ID for the reCAPTCHA key (See https://cloud.google.com/recaptcha/docs/create-key)
// * @param string $token The user's response token for which you want to receive a reCAPTCHA score. (See https://cloud.google.com/recaptcha/docs/create-assessment#retrieve_token)
// * @param string $project Your Google Cloud project ID
// */
// function create_assessment(
//    string $siteKey,
//    string $token,
//    string $project
// ): void {
// // TODO: To avoid memory issues, move this client generation outside
// // of this example, and cache it (recommended) or call client.close()
// // before exiting this method.
// $client = new RecaptchaEnterpriseServiceClient();
// $projectName = $client->projectName($project);
//    $event = (new Event())
//        ->setSiteKey($siteKey)
//        ->setToken($token);
//    $assessment = (new Assessment())
//        ->setEvent($event);
//    $request = (new CreateAssessmentRequest())
//        ->setParent($projectName)
//        ->setAssessment($assessment);
//    try {
//        $response = $client->createAssessment($request);
//        // You can use the score only if the assessment is valid,
//        // In case of failures like re-submitting the same token, getValid() will return false
//        if ($response->getTokenProperties()->getValid() == false) {
//            printf('The CreateAssessment() call failed because the token was invalid for the following reason: ');
//            printf(InvalidReason::name($response->getTokenProperties()->getInvalidReason()));
//        } else {
//            printf('The score for the protection action is:');
//            printf($response->getRiskAnalysis()->getScore());
//            // Optional: You can use the following methods to get more data about the token
//            // Action name provided at token generation.
//            // printf($response->getTokenProperties()->getAction() . PHP_EOL);
//            // The timestamp corresponding to the generation of the token.
//            // printf($response->getTokenProperties()->getCreateTime()->getSeconds() . PHP_EOL);
//            // The hostname of the page on which the token was generated.
//            // printf($response->getTokenProperties()->getHostname() . PHP_EOL);
//        }
//    } catch (exception $e) {
//        printf('CreateAssessment() call failed with the following error: ');
//        printf($e);
//    }
// }
// // TODO(Developer): Replace the following before running the sample
// create_assessment(
//    '6LdPNJwtAAAAALJ_bPElDIb2Vn7pi-Nq0XeOi0Ih',
//    $recaptcha_token,
//    'personal-website-1787182955723'
// );



// ----------- IP limiter -----------------
$ip = $_SERVER["REMOTE_ADDR"]; // Get ip address

$rateLimitFile = __DIR__."/rate_limits/".hash("sha256", $ip).".txt"; // Create new file to track current ip

// Set user info
$now = time();
$limit = 10;
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

    exit(0);
}

// ---------------- Messaging system ------------

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
}

// ------- Update IP info (assuming mailing worked completely) -------------

// Record this attempt
$attempts[] = $now;

// Creates/updates file
file_put_contents(
    $rateLimitFile,
    json_encode(array_values($attempts))
);



?>