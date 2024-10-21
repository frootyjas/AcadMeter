<?php
include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Update the paths to match your system's location of PHPMailer
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/SMTP.php';

header('Content-Type: application/json');

// Set time zone
date_default_timezone_set('UTC');
$conn->query("SET time_zone = '+00:00';");
$conn->set_charset('utf8mb4');

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the incoming POST data for debugging
    file_put_contents('debug_log.txt', print_r($_POST, true), FILE_APPEND);
    
    // Retrieve and validate email (change 'resetEmail' to 'usernameEmail')
    $email = isset($_POST['usernameEmail']) ? strtolower(trim($_POST['usernameEmail'])) : null;

    if (empty($email)) {
        file_put_contents('debug_log.txt', "usernameEmail not provided or empty.\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Email not provided or empty.']);
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        file_put_contents('debug_log.txt', "Invalid email format: $email\n", FILE_APPEND);
        exit();
    }

    // Check if the email exists in the database
    $sql = "SELECT username FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        file_put_contents('debug_log.txt', "Failed to prepare SELECT statement: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Database error while checking email.']);
        exit();
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Fetch the username
        $user = $result->fetch_assoc();
        $username = $user['username'];

        // Log the fetched username
        file_put_contents('debug_log.txt', "Username retrieved: $username\n", FILE_APPEND);

        // Send username to the user via email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = ''; // Your Gmail Address
            $mail->Password = ''; // Your Gmail App password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Set from address and log it
            $mail->setFrom('justinmarlosibonga@gmail.com', 'AcadMeter');
            $mail->addAddress($email); // Send to the user's email

            // Set email content
            $mail->isHTML(true);
            $mail->Subject = 'Username Retrieval';
            $mail->Body = "Hi,<br><br>Your username is: <strong>$username</strong>.<br>If you have further issues, feel free to contact us.<br><br>Thanks!<br>AcadMeter Team";
            $mail->send();

            // Log successful email sending
            file_put_contents('debug_log.txt', "Username retrieval email sent to $email\n", FILE_APPEND);
            echo json_encode(['status' => 'success', 'message' => 'Your username has been sent to your email.']);
        } catch (Exception $e) {
            file_put_contents('debug_log.txt', "Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
            echo json_encode(['status' => 'error', 'message' => 'Error sending email.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
        file_put_contents('debug_log.txt', "Email not found: $email\n", FILE_APPEND);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
