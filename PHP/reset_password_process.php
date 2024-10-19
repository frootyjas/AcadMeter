<?php
include 'db_connection.php';

// Set time zone
date_default_timezone_set('UTC');
$conn->query("SET time_zone = '+00:00';");
$conn->set_charset('utf8mb4');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate inputs
    if (empty($token) || empty($password) || empty($confirmPassword)) {
        echo "All fields are required.";
        exit();
    }

    if ($password !== $confirmPassword) {
        echo "Passwords do not match.";
        exit();
    }

    // Check password strength (optional but recommended)
    if (strlen($password) < 8) {
        echo "Password must be at least 8 characters long.";
        exit();
    }

    // Prepare the SQL statement to get the user
    $sql = "SELECT email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $email = $row['email'];

        // Hash the new password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Update the user's password and clear the reset token
        $updateSql = "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param('ss', $hashedPassword, $email);

        if ($updateStmt->execute()) {
            echo "Your password has been reset successfully.";
        } else {
            echo "Failed to reset password. Please try again.";
        }

        $updateStmt->close();
    } else {
        echo "Invalid or expired token.";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
