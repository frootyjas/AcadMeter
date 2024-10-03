<?php

// Enable error reporting for debugging -to be remove once merged to main 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_connection.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get user type
    $userType = isset($_POST['userType']) ? $_POST['userType'] : null;  // admin, instructor, or student

    if (!$userType) {
        echo "User type is missing.";
        exit();
    }

    // Common user data
    $username = $_POST['username_' . $userType];
    $email = $_POST['email_' . $userType];
    $password1 = $_POST['password1_' . $userType];
    $password2 = $_POST['password2_' . $userType];

    // Validate passwords
    if ($password1 !== $password2) {
        echo "Passwords do not match.";
        exit();
    }

    // Hash the password
    $passwordHash = password_hash($password1, PASSWORD_BCRYPT);

    // Insert into users table
    $sql_user = "INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)";
    $stmt_user = $conn->prepare($sql_user);

    if (!$stmt_user) {
        echo "Error preparing user insert statement: " . $conn->error;
        exit();
    }

    $stmt_user->bind_param("ssss", $username, $passwordHash, $email, $userType);

    if ($stmt_user->execute()) {
        // Get the inserted user ID
        $user_id = $stmt_user->insert_id;

        // Prepare data for specific user type
        if ($userType == 'admin') {
            $name = $_POST['admin_name'];
            $employee_number = $_POST['admin_number'];
            $position = $_POST['admin_position'];
            $gender = $_POST['gender_admin'];
            $dob = $_POST['date_of_birth_admin'];

            $sql_specific = "INSERT INTO admins (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_specific = $conn->prepare($sql_specific);

            if (!$stmt_specific) {
                echo "Error preparing admin insert statement: " . $conn->error;
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
                echo "Error preparing instructor insert statement: " . $conn->error;
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
                echo "Error preparing student insert statement: " . $conn->error;
                exit();
            }

            $stmt_specific->bind_param("isssss", $user_id, $name, $student_number, $program, $gender, $dob);
        } else {
            echo "Invalid user type.";
            exit();
        }

        // Execute the insertion into the specific table
        if ($stmt_specific->execute()) {
            // Registration successful
            echo "Registration successful!";
            header("Location: ../html/verifyAccount.html");
            exit();
        } else {
            echo "Error: " . $stmt_specific->error;
            exit();
        }
    } else {
        echo "Error: " . $stmt_user->error;
        exit();
    }

    // Close the statements and connection
    $stmt_user->close();
    if (isset($stmt_specific)) {
        $stmt_specific->close();
    }
    $conn->close();
} else {
    echo "Invalid request method.";
    exit();
}
?>
