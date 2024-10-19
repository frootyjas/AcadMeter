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
    $resetEmail = strtolower(trim($_POST['resetEmail']));

    // Check if email is valid
    if (!filter_var($resetEmail, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit();
    }

    // Check if the email exists in the database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        file_put_contents('debug_log.txt', "Failed to prepare SELECT statement: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['status' => 'error', 'message' => 'Database error while checking email.']);
        exit();
    }

    $stmt->bind_param("s", $resetEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Generate reset token
        $user = $result->fetch_assoc();
        $resetToken = bin2hex(random_bytes(50));
        $resetTokenExpiry = date("Y-m-d H:i:s", strtotime('+1 hour'));

        file_put_contents('debug_log.txt', "Generated Reset Token: $resetToken\nReset Token Expiry: $resetTokenExpiry\n", FILE_APPEND);

        // Update the database with the reset token and expiry
        $updateSql = "UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {
            file_put_contents('debug_log.txt', "Failed to prepare UPDATE statement: " . $conn->error . "\n", FILE_APPEND);
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare update statement.']);
            exit();
        }

        $updateStmt->bind_param("sss", $resetToken, $resetTokenExpiry, $resetEmail);

        if ($updateStmt->execute()) {
            $affectedRows = $updateStmt->affected_rows;
            file_put_contents('debug_log.txt', "Affected Rows: $affectedRows\n", FILE_APPEND);

            if ($affectedRows > 0) {
                file_put_contents('debug_log.txt', "Token and Expiry saved successfully for $resetEmail\n", FILE_APPEND);

                // Send reset email using PHPMailer
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'justinmarlosibonga@gmail.com'; 
                    $mail->Password = 'mvnhppaolniedhvv'; // Your Gmail App password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('no-reply@example.com', 'AcadMeter');
                    $mail->addAddress($resetEmail);

                    $mail->isHTML(true);
                    $mail->Subject = 'Password Reset Request';
                    $mail->Body = "We received a request to reset your password. Click the link below to reset it:<br>
                                  <a href='http://localhost/AcadMeter/PHP/reset_password.php?token=$resetToken'>Reset Password</a><br>
                                  This link will expire in 1 hour.";
                    $mail->send();

                    echo json_encode(['status' => 'success', 'message' => 'A password reset link has been sent to your email.']);
                } catch (Exception $e) {
                    file_put_contents('debug_log.txt', "Mailer Error: {$mail->ErrorInfo}\n", FILE_APPEND);
                    echo json_encode(['status' => 'error', 'message' => 'Error sending email.']);
                }
            } else {
                file_put_contents('debug_log.txt', "No rows updated. Email may not exist or token not saved.\n", FILE_APPEND);
                echo json_encode(['status' => 'error', 'message' => 'Failed to save reset token.']);
            }
        } else {
            file_put_contents('debug_log.txt', "Failed to execute update statement: " . $updateStmt->error . "\n", FILE_APPEND);
            echo json_encode(['status' => 'error', 'message' => 'Failed to save reset token.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Email not found.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
