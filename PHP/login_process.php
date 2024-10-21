<?php
// Start the session at the very beginning
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Include the database connection with the correct path
include '../PHP/db_connection.php';  

// Enable full error reporting for debugging (remove or adjust in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the username, password, and userType from the POST request
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $userType = strtolower(trim($_POST['userType']));

    // Validate input
    if (empty($username) || empty($password)) {
        $response["status"] = "error";
        $response["message"] = "Username and password are required.";
        echo json_encode($response);
        exit();
    }

    // Prepare SQL query to fetch user based on username or email
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND LOWER(user_type) = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        $response["status"] = "error";
        $response["message"] = "SQL preparation failed: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    // Bind parameters and execute the query
    $stmt->bind_param("sss", $username, $username, $userType);

    if (!$stmt->execute()) {
        $response["status"] = "error";
        $response["message"] = "Query execution failed: " . $stmt->error;
        echo json_encode($response);
        exit();
    }

    // Get the query result
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Fetch user data
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Check if the user is verified and approved
            if ($user['verified'] == 1 && $user['approved'] == 1) {
                // Password is correct and user is verified and approved, create session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = strtolower($user['user_type']);

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Set success response
                $response["status"] = "success";
                $response["message"] = "Login successful!";

                // Set redirect URL based on user type
                if ($_SESSION['user_type'] === 'admin') {
                    $response["redirect"] = "/AcadMeter/php/admin_dashboard.php"; // Adjust path as necessary
                } elseif ($_SESSION['user_type'] === 'instructor') {
                    $response["redirect"] = "/AcadMeter/html/instructorDashboard.php"; // Adjust path as necessary
                } elseif ($_SESSION['user_type'] === 'student') {
                    $response["redirect"] = "/AcadMeter/html/studentDashboard.php"; // Adjust path as necessary
                } else {
                    // Unknown user type
                    $response["status"] = "error";
                    $response["message"] = "Invalid user type.";
                    // Optionally, destroy the session
                    session_unset();
                    session_destroy();
                }
            } else {
                // User is not verified or approved
                $response["status"] = "error";
                if ($user['verified'] == 0) {
                    $response["message"] = "Your email is not verified. Please check your email for the verification code.";
                } else {
                    $response["message"] = "Your account is pending admin approval.";
                }
            }
        } else {
            // Incorrect password
            $response["status"] = "error";
            $response["message"] = "Incorrect username or password.";
        }
    } else {
        // No user found
        $response["status"] = "error";
        $response["message"] = "Incorrect username or password.";
    }

    // Return the response as JSON
    echo json_encode($response);

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    $response["status"] = "error";
    $response["message"] = "Invalid request method.";
    echo json_encode($response);
}
