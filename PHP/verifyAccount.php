<?php
// Include database connection
include 'db_connection.php';

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get JSON data from the request body
    $data = json_decode(file_get_contents('php://input'), true);
    $verification_code = isset($data['verification_code']) ? trim($data['verification_code']) : '';

    // Debug log to see what verification code is being received
    file_put_contents('debug_log.txt', "Verification Code Received: {$verification_code}\n", FILE_APPEND);

    // Check if verification code is missing
    if (empty($verification_code)) {
        file_put_contents('debug_log.txt', "No verification code provided.\n", FILE_APPEND);
        $response["status"] = "error";
        $response["message"] = "Verification code is missing.";
        echo json_encode($response);
        exit();
    }

    // Query to check if the verification code is valid and the account is not yet verified
    $sql = "SELECT otp_code, verified FROM users WHERE otp_code = ? AND verified = 0";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $response["status"] = "error";
        $response["message"] = "Error preparing statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    $stmt->bind_param("i", $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Log the number of rows and the fetched OTP
    file_put_contents('debug_log.txt', "About to execute query with verification code: {$verification_code}\n", FILE_APPEND);
    file_put_contents('debug_log.txt', "Number of rows found: {$result->num_rows}\n", FILE_APPEND);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        file_put_contents('debug_log.txt', "Fetched OTP: {$user['otp_code']}, Verified Status: {$user['verified']}\n", FILE_APPEND);

        // OTP is correct, update the user's verified status to 1 and status to pending
        $sql_update = "UPDATE users SET verified = 1, status = 'pending' WHERE otp_code = ?";
        $stmt_update = $conn->prepare($sql_update);

        if (!$stmt_update) {
            $response["status"] = "error";
            $response["message"] = "Error preparing update statement: " . $conn->error;
            echo json_encode($response);
            exit();
        }

        $stmt_update->bind_param("i", $verification_code);

        if ($stmt_update->execute()) {
            // After success, display message with a "Go Home" button
            echo json_encode([
                "status" => "success",
                "message" => "Your email has been successfully verified! Your account is now pending approval by the admin.",
                "showGoHomeButton" => true // Add this key to trigger the button on the frontend
            ]);
        } else {
            $response["status"] = "error";
            $response["message"] = "Error updating verification status.";
            echo json_encode($response);
        }

        $stmt_update->close();
    } else {
        $response["status"] = "error";
        $response["message"] = "Invalid verification code or the account is already verified.";
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
