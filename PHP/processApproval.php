<?php
// Include database connection
include '../PHP/db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/SMTP.php';

// Start session and check if admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo "Access denied.";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = intval($_POST['user_id']);
    $action = $_POST['action'];

    // Fetch user details
    $sql = "SELECT * FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result->num_rows === 1) {
        $user = $user_result->fetch_assoc();
        $email = $user['email'];
        $name = $user['username']; // Or fetch the name from the appropriate table
        $userType = $user['user_type'];

        if ($action == 'approve') {
            // Update user status to approved
            $sql_update = "UPDATE users SET approved = 1, status = 'approved' WHERE user_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $user_id);
            $stmt_update->execute();

            // Send approval email
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = ''; //Gmail
                $mail->Password = ''; // Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('your_email@example.com', 'AcadMeter Team');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Account Approved';
                $mail->Body    = "Hi $name,<br><br>
                                  Congratulations! Your account has been approved by the admin.<br>
                                  You can now log in to your account and start using our services.<br><br>
                                  <a href='http://localhost/AcadMeter/html/signIn.html'>Log In</a><br><br>
                                  Thanks!<br>AcadMeter Team";

                $mail->send();
                echo "User approved and notified via email.";
            } catch (Exception $e) {
                echo "User approved but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } elseif ($action == 'reject') {
            // Update user status to rejected
            $sql_update = "UPDATE users SET approved = 0, status = 'rejected' WHERE user_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("i", $user_id);
            $stmt_update->execute();

            // Send rejection email
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = ''; // Gmail 
                $mail->Password = ''; // Gmail app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('your_email@example.com', 'AcadMeter Team');
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Account Rejected';
                $mail->Body    = "Hi $name,<br><br>
                                  We regret to inform you that your account registration has been rejected by the admin.<br>
                                  If you believe this is a mistake or have any questions, please contact us.<br><br>
                                  Thanks!<br>AcadMeter Team";

                $mail->send();
                echo "User rejected and notified via email.";
            } catch (Exception $e) {
                echo "User rejected but email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Invalid action.";
        }
    } else {
        echo "User not found.";
    }
} else {
    echo "Invalid request.";
}
