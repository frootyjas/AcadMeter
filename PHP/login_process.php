<?php
session_start();
header('Content-Type: application/json');

// Include the database connection with the correct path
include '../PHP/db_connection.php';  

// Enable full error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$response = ["status" => "", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the username, password, and userType from the POST request
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $userType = $_POST['userType'];

    // Validate input
    if (empty($username) || empty($password)) {
        $response["status"] = "error";
        $response["message"] = "Username and password are required.";
        echo json_encode($response);
        exit();
    }

    // Debugging: Log received username and userType
    file_put_contents('debug_log.txt', "Received Username: $username, UserType: $userType\n", FILE_APPEND);

    // Check database connection
    if ($conn->connect_error) {
        $response["status"] = "error";
        $response["message"] = "Database connection failed: " . $conn->connect_error;
        echo json_encode($response);
        exit();
    }

    // Prepare SQL query to fetch user based on username or email
    $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND LOWER(user_type) = LOWER(?)";
    $stmt = $conn->prepare($sql);

    // Debugging: Log query preparation
    if ($stmt === false) {
        file_put_contents('debug_log.txt', "SQL preparation failed: " . $conn->error . "\n", FILE_APPEND);
        $response["status"] = "error";
        $response["message"] = "SQL preparation failed: " . $conn->error;
        echo json_encode($response);
        exit();
    }

    // Bind parameters and execute the query
    $stmt->bind_param("sss", $username, $username, $userType);

    // Debugging: Log before executing the query
    file_put_contents('debug_log.txt', "About to execute query: SELECT * FROM users WHERE (username = '$username' OR email = '$username') AND LOWER(user_type) = LOWER('$userType')\n", FILE_APPEND);

    if (!$stmt->execute()) {
        // Log if query execution fails
        file_put_contents('debug_log.txt', "Query execution failed: " . $stmt->error . "\n", FILE_APPEND);
        $response["status"] = "error";
        $response["message"] = "Query execution failed: " . $stmt->error;
        echo json_encode($response);
        exit();
    }

    // Debugging: Log query execution success
    file_put_contents('debug_log.txt', "Query executed successfully.\n", FILE_APPEND);

    // Get the query result
    $result = $stmt->get_result();

    // Debugging: Log the number of rows returned
    file_put_contents('debug_log.txt', "Result num_rows: " . $result->num_rows . "\n", FILE_APPEND);

    if ($result->num_rows === 1) {
        // Fetch user data
        $user = $result->fetch_assoc();

        // Debugging: Log the fetched user data
        file_put_contents('debug_log.txt', "Fetched User Data: " . print_r($user, true) . "\n", FILE_APPEND);

        // Verify the password
        if (password_verify($password, $user['password'])) {
            // Debugging: Log successful password verification
            file_put_contents('debug_log.txt', "Password verified successfully.\n", FILE_APPEND);

            // Password is correct, create session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];

            // Regenerate session ID for security
            session_regenerate_id(true);

            // Set success response
            $response["status"] = "success";
            $response["message"] = "Login successful!";
        } else {
            // Incorrect password
            file_put_contents('debug_log.txt', "Password verification failed.\n", FILE_APPEND);
            $response["status"] = "error";
            $response["message"] = "Incorrect password.";
        }
    } else {
        // No user found
        file_put_contents('debug_log.txt', "User not found for username/email: $username\n", FILE_APPEND);
        $response["status"] = "error";
        $response["message"] = "User not found.";
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
