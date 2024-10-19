<?php
include 'db_connection.php';

// Set time zone
date_default_timezone_set('UTC');
$conn->query("SET time_zone = '+00:00';");
$conn->set_charset('utf8mb4');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    echo "Invalid or expired token.";
    exit();
}

// Prepare the SQL statement
$sql = "SELECT email, reset_token_expiry FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Token is valid; display the password reset form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Reset Password</title>
    </head>
    <body>
        <h2>Reset Your Password</h2>
        <form method="POST" action="reset_password_process.php">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <label for="password">New Password:</label><br>
            <input type="password" id="password" name="password" required><br>
            <label for="confirm_password">Confirm Password:</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br><br>
            <input type="submit" value="Reset Password">
        </form>
    </body>
    </html>
    <?php
} else {
    // Invalid or expired token
    echo "Invalid or expired token.";
}

$stmt->close();
$conn->close();
?>
