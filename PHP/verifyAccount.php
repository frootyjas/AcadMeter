<?php
// Include database connection
include 'db_connection.php';

header('Content-Type: application/json');

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON data from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $email = isset($data['email']) ? trim($data['email']) : '';
    $verification_code = isset($data['verification_code']) ? trim($data['verification_code']) : '';

    // Debug log to see what verification code is being received
    file_put_contents('debug_log.txt', "Email: {$email}, Verification Code Received: {$verification_code}\n", FILE_APPEND);

    // Check if email or verification code is missing
    if (empty($email) || empty($verification_code)) {
        file_put_contents('debug_log.txt', "Email or verification code missing.\n", FILE_APPEND);
        $response["status"] = "error";
        $response["message"] = "Email and verification code are required.";
        echo json_encode($response);
        exit();
    }

    // Query to check if the verification code and email match in pending_users
    $sql = "SELECT * FROM pending_users WHERE email = ? AND verification_code = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $response["status"] = "error";
        $response["message"] = "Error preparing statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("ss", $email, $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Log the number of rows found
    file_put_contents('debug_log.txt', "Number of rows found: {$result->num_rows}\n", FILE_APPEND);

    if ($result->num_rows === 1) {
        $pending_user = $result->fetch_assoc();
        file_put_contents('debug_log.txt', "Pending user found: " . print_r($pending_user, true) . "\n", FILE_APPEND);

        // Begin transaction
        $conn->begin_transaction();

        try {
            // Insert into users table
            $sql_insert_user = "INSERT INTO users (username, password, email, user_type, verification_code, verified, approved) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert_user = $conn->prepare($sql_insert_user);

            if (!$stmt_insert_user) {
                throw new Exception("Error preparing user insert statement: " . $conn->error);
            }

            $verified = 1;  // Now verified
            $approved = 0;  // Still needs admin approval

            $stmt_insert_user->bind_param(
                "sssssii",
                $pending_user['username'],
                $pending_user['password'],
                $pending_user['email'],
                $pending_user['user_type'],
                $pending_user['verification_code'],
                $verified,
                $approved
            );

            $stmt_insert_user->execute();
            $user_id = $stmt_insert_user->insert_id;

            // Insert into specific user type table
            if ($pending_user['user_type'] == 'admin') {
                $sql_specific = "INSERT INTO admins (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            } elseif ($pending_user['user_type'] == 'instructor') {
                $sql_specific = "INSERT INTO instructors (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            } elseif ($pending_user['user_type'] == 'student') {
                $sql_specific = "INSERT INTO students (user_id, name, student_number, program, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            } else {
                throw new Exception("Invalid user type.");
            }

            $stmt_specific = $conn->prepare($sql_specific);

            if (!$stmt_specific) {
                throw new Exception("Error preparing specific user insert statement: " . $conn->error);
            }

            $stmt_specific->bind_param(
                "isssss",
                $user_id,
                $pending_user['name'],
                $pending_user['number'],
                $pending_user['position_program'],
                $pending_user['gender'],
                $pending_user['date_of_birth']
            );

            $stmt_specific->execute();

            // Delete from pending_users table
            $sql_delete_pending = "DELETE FROM pending_users WHERE pending_user_id = ?";
            $stmt_delete_pending = $conn->prepare($sql_delete_pending);

            if (!$stmt_delete_pending) {
                throw new Exception("Error preparing delete statement: " . $conn->error);
            }

            $stmt_delete_pending->bind_param("i", $pending_user['pending_user_id']);
            $stmt_delete_pending->execute();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Your email has been successfully verified! Your account is now pending approval by the admin.",
                "showGoHomeButton" => true // Add this key to trigger the button on the frontend
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            $response["status"] = "error";
            $response["message"] = $e->getMessage();
            echo json_encode($response);
        }

    } else {
        $response["status"] = "error";
        $response["message"] = "Invalid verification code or email.";
        echo json_encode($response);
    }

    // Close statements and connection
    $stmt->close();
    $conn->close();
} else {
    $response["status"] = "error";
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
}
