<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.html");
    exit;
}

// Verify Turnstile token
$token = $_POST['cf-turnstile-response'];
$secret = "";

$verify = file_get_contents(
    "https://challenges.cloudflare.com/turnstile/v0/siteverify",
    false,
    stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => "Content-Type: application/x-www-form-urlencoded",
            "content" => http_build_query([
                "secret" => $secret,
                "response" => $token
            ])
        ]
    ])
);

$outcome = json_decode($verify);

if ($outcome->success) {
    // Challenge passed, process the email
    
    // Sanitize inputs
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    $company = filter_var($_POST['company'], FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(["error" => "All fields are required"]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["error" => "Invalid email format"]);
        exit;
    }

    // Email configuration
    $to = "your@email.com";
    $subject = "New Contact Form Submission";
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $email\r\n";
    $headers .= "Reply-To: $email\r\n";

    $emailBody = "
    <html>
    <head>
        <title>New Contact Form Message</title>
    </head>
    <body>
        <h2>Contact Form Details</h2>
        <p><strong>Name:</strong> $name</p>
        <p><strong>Email:</strong> $email</p>
        " . (!empty($company) ? "<p><strong>Company:</strong> $company</p>" : "") . "
        <p><strong>Message:</strong><br>$message</p>
    </body>
    </html>
    ";

    try {
        $mailSent = mail($to, $subject, $emailBody, $headers);
        
        if ($mailSent) {
            echo json_encode(["success" => "Message sent successfully"]);
        } else {
            echo json_encode(["error" => "Failed to send message"]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => "An error occurred"]);
    }
} else {
    // Challenge failed
    echo json_encode(["error" => "Security check failed. Please try again."]);
    exit;
}
?>