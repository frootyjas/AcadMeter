<?php
// Error reporting (remove or adjust in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer classes
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/Exception.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/PHPMailer.php';
require 'C:/xampp/htdocs/AcadMeter/PHPMailer-master/src/SMTP.php';

header('Content-Type: application/json');

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user type
    $userType = isset($_POST['userType']) ? $_POST['userType'] : null;

    if (!$userType) {
        $response["status"] = "error";
        $response["message"] = "User type is missing.";
        echo json_encode($response);
        exit();
    }

    // Common user data
    $username = $_POST['username_' . $userType];
    $email = $_POST['email_' . $userType];
    $password1 = $_POST['password1_' . $userType];
    $password2 = $_POST['password2_' . $userType];

    // Validate passwords
    if ($password1 !== $password2) {
        $response["status"] = "error";
        $response["message"] = "Passwords do not match.";
        echo json_encode($response);
        exit();
    }

    // Check for duplicate username or email in users table
    $sql_check_users = "SELECT username FROM users WHERE username = ? OR email = ?";
    $stmt_check_users = $conn->prepare($sql_check_users);

    if (!$stmt_check_users) {
        $response["status"] = "error";
        $response["message"] = "Error preparing users duplicate check statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt_check_users->bind_param("ss", $username, $email);
    $stmt_check_users->execute();
    $result_users = $stmt_check_users->get_result();

    if ($result_users->num_rows > 0) {
        // Duplicate found in users table
        $response["status"] = "error";
        $response["message"] = "Username or Email already exists. Please choose a different one.";
        echo json_encode($response);
        exit();
    }

    // Check for duplicate username or email in pending_users table
    $sql_check_pending = "SELECT username FROM pending_users WHERE username = ? OR email = ?";
    $stmt_check_pending = $conn->prepare($sql_check_pending);

    if (!$stmt_check_pending) {
        $response["status"] = "error";
        $response["message"] = "Error preparing pending_users duplicate check statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt_check_pending->bind_param("ss", $username, $email);
    $stmt_check_pending->execute();
    $result_pending = $stmt_check_pending->get_result();

    if ($result_pending->num_rows > 0) {
        // Duplicate found in pending_users table
        $response["status"] = "error";
        $response["message"] = "An account with this username or email is pending verification. Please check your email for verification instructions.";
        echo json_encode($response);
        exit();
    }

    // Close duplicate check statements
    $stmt_check_users->close();
    $stmt_check_pending->close();

    // Hash the password
    $passwordHash = password_hash($password1, PASSWORD_BCRYPT);

    // Generate a 6-digit OTP verification code
    $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);  // Ensures 6 digits with leading zeros if necessary

    // Prepare data for insertion into pending_users
    $name = ''; $number = ''; $position_program = ''; $gender = ''; $dob = '';

    // Collect specific user type data
    if ($userType == 'admin') {
        $name = $_POST['admin_name'];
        $number = $_POST['admin_number'];
        $position_program = $_POST['admin_position'];
        $gender = $_POST['gender_admin'];
        $dob = $_POST['date_of_birth_admin'];
    } elseif ($userType == 'instructor') {
        $name = $_POST['instructor_name'];
        $number = $_POST['instructor_number'];
        $position_program = $_POST['instructor_position'];
        $gender = $_POST['gender_instructor'];
        $dob = $_POST['date_of_birth_instructor'];
    } elseif ($userType == 'student') {
        $name = $_POST['student_name'];
        $number = $_POST['student_number'];
        $position_program = $_POST['student_program'];
        $gender = $_POST['gender_student'];
        $dob = $_POST['date_of_birth_student'];
    } else {
        $response["status"] = "error";
        $response["message"] = "Invalid user type.";
        echo json_encode($response);
        exit();
    }

    // Insert into pending_users table
    $sql_pending_user = "INSERT INTO pending_users (username, password, email, user_type, verification_code, name, number, position_program, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_pending_user = $conn->prepare($sql_pending_user);

    if (!$stmt_pending_user) {
        $response["status"] = "error";
        $response["message"] = "Error preparing pending user insert statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt_pending_user->bind_param("ssssssssss", $username, $passwordHash, $email, $userType, $verification_code, $name, $number, $position_program, $gender, $dob);

    if ($stmt_pending_user->execute()) {
        // Send verification email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = ''; // Your Gmail
            $mail->Password = ''; // Your Gmail app pw 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('your_email@gmail.com', 'AcadMeter Team');
            $mail->addAddress($email); // Send the email to the user's email address

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Email Verification Code';
            $mail->Body    = "Hi $name,<br><br>
                              Thank you for registering.<br>
                              Please use the following verification code to verify your email address:<br><br>
                              <strong>$verification_code</strong><br><br>
                              Visit the following link to enter your code:<br>
                              <a href='http://localhost/AcadMeter/html/verificationAccount.html'>Verify Email</a><br><br>
                              Thanks!<br>AcadMeter Team";

            $mail->send();
            $response["status"] = "success";
            $response["message"] = "Registration successful! A verification email has been sent to your email address.";
            $response["redirect"] = "../html/verificationAccount.html"; // Add redirect URL
        } catch (Exception $e) {
            $response["status"] = "error";
            $response["message"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
        echo json_encode($response);
        exit();
    } else {
        $response["status"] = "error";
        $response["message"] = "Error: " . $stmt_pending_user->error;
        echo json_encode($response);
        exit();
    }

    // Close statements and connection
    $stmt_pending_user->close();
    $conn->close();
} else {
    $response["status"] = "error";
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
    exit();
}
