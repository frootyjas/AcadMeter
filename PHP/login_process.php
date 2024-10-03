<?php

session_start();

include 'db_connection.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the username/email and password from the POST request
    $username_email = $_POST['username_email'];
    $password = $_POST['password'];

    // Prepare a statement to select the user
    $sql = "SELECT * FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_email, $username_email);

    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a user with the given username/email exists
    if ($result->num_rows === 1) {
        // Fetch user data
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Password is correct

            // Store user data in the session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            // Redirect to the appropriate dashboard based on user type
            if ($user['user_type'] == 'admin') {
                header("Location: ../html/admin_dashboard.php");
            } elseif ($user['user_type'] == 'instructor') {
                header("Location: ../html/instructor_dashboard.php");
            } elseif ($user['user_type'] == 'student') {
                header("Location: ../html/student_dashboard.php");
            } else {
                // If user type is unknown, redirect to a default page
                header("Location: ../html/index.html");
            }
            exit();
        } else {
            // Password is incorrect
            $error_message = "Incorrect password.";
            header("Location: ../html/signIn.html?error=" . urlencode($error_message));
            exit();
        }
    } else {
        // Username/email does not exist
        $error_message = "User not found.";
        header("Location: ../html/signIn.html?error=" . urlencode($error_message));
        exit();
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
