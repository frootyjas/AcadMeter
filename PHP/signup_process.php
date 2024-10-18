<?php
// error reporting (to be removed in main branch)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// change the address to the local address for testing
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

    // Check for duplicate username or email
    $sql_check = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt_check = $conn->prepare($sql_check);

    if (!$stmt_check) {
        $response["status"] = "error";
        $response["message"] = "Error preparing duplicate check statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt_check->bind_param("ss", $username, $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        // Duplicate found
        $response["status"] = "error";
        $response["message"] = "Username or Email already exists. Please choose a different one.";
        echo json_encode($response);
        exit();
    }

    // Hash the password
    $passwordHash = password_hash($password1, PASSWORD_BCRYPT);

    // Generate a verification code
    $verification_code = bin2hex(random_bytes(16));  // Generate a random verification code
    $verified = 0;  // User is not verified initially

    // Insert into users table
    $sql_user = "INSERT INTO users (username, password, email, user_type, verification_code, verified) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_user = $conn->prepare($sql_user);

    if (!$stmt_user) {
        $response["status"] = "error";
        $response["message"] = "Error preparing user insert statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt_user->bind_param("sssssi", $username, $passwordHash, $email, $userType, $verification_code, $verified);

    if ($stmt_user->execute()) {
        // Handle specific user type data (e.g., admin, instructor, student)
        $user_id = $stmt_user->insert_id;

        if ($userType == 'admin') {
            $name = $_POST['admin_name'];
            $employee_number = $_POST['admin_number'];
            $position = $_POST['admin_position'];
            $gender = $_POST['gender_admin'];
            $dob = $_POST['date_of_birth_admin'];

            $sql_specific = "INSERT INTO admins (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_specific = $conn->prepare($sql_specific);

            if (!$stmt_specific) {
                $response["status"] = "error";
                $response["message"] = "Error preparing admin insert statement: " . $conn->error;
                echo json_encode($response);
                exit();
            }

            $stmt_specific->bind_param("isssss", $user_id, $name, $employee_number, $position, $gender, $dob);
        } elseif ($userType == 'instructor') {
            $name = $_POST['instructor_name'];
            $employee_number = $_POST['instructor_number'];
            $position = $_POST['instructor_position'];
            $gender = $_POST['gender_instructor'];
            $dob = $_POST['date_of_birth_instructor'];

            $sql_specific = "INSERT INTO instructors (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_specific = $conn->prepare($sql_specific);

            if (!$stmt_specific) {
                $response["status"] = "error";
                $response["message"] = "Error preparing instructor insert statement: " . $conn->error;
                echo json_encode($response);
                exit();
            }

            $stmt_specific->bind_param("isssss", $user_id, $name, $employee_number, $position, $gender, $dob);
        } elseif ($userType == 'student') {
            $name = $_POST['student_name'];
            $student_number = $_POST['student_number'];
            $program = $_POST['student_program'];
            $gender = $_POST['gender_student'];
            $dob = $_POST['date_of_birth_student'];

            $sql_specific = "INSERT INTO students (user_id, name, student_number, program, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_specific = $conn->prepare($sql_specific);

            if (!$stmt_specific) {
                $response["status"] = "error";
                $response["message"] = "Error preparing student insert statement: " . $conn->error;
                echo json_encode($response);
                exit();
            }

            $stmt_specific->bind_param("isssss", $user_id, $name, $student_number, $program, $gender, $dob);
        } else {
            $response["status"] = "error";
            $response["message"] = "Invalid user type.";
            echo json_encode($response);
            exit();
        }

        // Execute the insertion into the specific table
        if ($stmt_specific->execute()) {
            // Send verification email using PHPMailer
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = '';  // Your Gmail 
                $mail->Password = ''; // Your Gmail App password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                // Recipients
                $mail->setFrom('youremail@gmail.com', 'AcadMeter Team');
                $mail->addAddress($email); // Send the email to the user's email address

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Verification';
                $mail->Body    = "Hi $name,<br><br>Thank you for registering. 
                                  Please click the link below to verify your email:<br>
                                  <a href='http://localhost/AcadMeter/verify.php?code=$verification_code'>Verify Email</a><br><br>Thanks!<br>AcadMeter Team";

                $mail->send();
                $response["status"] = "success";
                $response["message"] = "Registration successful! A verification email has been sent to your email address.";
            } catch (Exception $e) {
                $response["status"] = "error";
                $response["message"] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
            echo json_encode($response);
            exit();
        } else {
            $response["status"] = "error";
            $response["message"] = "Error: " . $stmt_specific->error;
            echo json_encode($response);
            exit();
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "Error: " . $stmt_user->error;
        echo json_encode($response);
        exit();
    }

    // Close statements and connection
    $stmt_check->close();
    $stmt_user->close();
    if (isset($stmt_specific)) {
        $stmt_specific->close();
    }
    $conn->close();
} else {
    $response["status"] = "error";
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
    exit();
}
