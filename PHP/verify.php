<?php
// Include database connection
include 'db_connection.php';

$response = ["status" => "", "message" => ""];

// Check if the request method is POST (since it's form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the OTP (verification code) from the form submission
    $verification_code = $_POST['verification_code'];

    // Debug log to see what OTP is being received
    file_put_contents('debug_log.txt', "Verification Code Received: {$verification_code}\n", FILE_APPEND);

    // Query to check if the OTP is valid and the account is not yet verified
    $sql = "SELECT * FROM users WHERE otp_code = ? AND verified = 0";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $response["status"] = "error";
        $response["message"] = "Error preparing statement: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    // Bind the OTP code and execute
    $stmt->bind_param("i", $verification_code);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debugging log for number of rows found
    file_put_contents('debug_log.txt', "About to execute query with verification code: {$verification_code}\n", FILE_APPEND);
    file_put_contents('debug_log.txt', "Number of rows found: {$result->num_rows}\n", FILE_APPEND);

    if ($result->num_rows > 0) {
        // OTP is correct, update the user's verified status to 1 and status to pending
        $sql_update = "UPDATE users SET verified = 1, status = 'pending' WHERE otp_code = ?";
        $stmt_update = $conn->prepare($sql_update);

        if (!$stmt_update) {
            $response["status"] = "error";
            $response["message"] = "Error preparing update statement: " . $conn->error;
            echo json_encode($response);
            exit();
        }

        // Bind the OTP code to update the user status
        $stmt_update->bind_param("i", $verification_code);

        if ($stmt_update->execute()) {
            $response["status"] = "success";
            $response["message"] = "Your email has been successfully verified! Your account is now pending approval by the admin.";
        } else {
            $response["status"] = "error";
            $response["message"] = "Error updating verification status.";
        }

        $stmt_update->close();
    } else {
        $response["status"] = "error";
        $response["message"] = "Invalid verification code or the account is already verified.";
    }

    // Return response as JSON
    echo json_encode($response);

    // Close statements and connection
    $stmt->close();
    $conn->close();
} else {
    $response["status"] = "error";
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
}
