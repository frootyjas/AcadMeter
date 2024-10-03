<?php
// Include the database connection file
include 'db_connection.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get common user details
    $username = $_POST['username_admin'] ?? $_POST['username_instructor'] ?? $_POST['username_student'];
    $password = password_hash($_POST['password1_admin'] ?? $_POST['password1_instructor'] ?? $_POST['password1_student'], PASSWORD_BCRYPT);
    $email = $_POST['email_admin'] ?? $_POST['email_instructor'] ?? $_POST['email_student'];
    $userType = $_POST['userType'];  // admin, instructor, or student

    // Insert into the users table
    $sql = "INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $email, $userType);

    if ($stmt->execute()) {
        // Get the inserted user ID
        $user_id = $stmt->insert_id;

        // Insert into the specific table based on user type
        if ($userType == 'admin') {
            $name = $_POST['admin_name'];
            $employee_number = $_POST['admin_number'];
            $position = $_POST['admin_position'];
            $gender = $_POST['gender_admin'];
            $dob = $_POST['date_of_birth_admin'];

            $sql = "INSERT INTO admins (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $user_id, $name, $employee_number, $position, $gender, $dob);
        } elseif ($userType == 'instructor') {
            $name = $_POST['instructor_name'];
            $employee_number = $_POST['instructor_number'];
            $position = $_POST['instructor_position'];
            $gender = $_POST['gender_instructor'];
            $dob = $_POST['date_of_birth_instructor'];

            $sql = "INSERT INTO instructors (user_id, name, employee_number, position, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $user_id, $name, $employee_number, $position, $gender, $dob);
        } elseif ($userType == 'student') {
            $name = $_POST['student_name'];
            $student_number = $_POST['student_number'];
            $program = $_POST['student_program'];
            $gender = $_POST['gender_student'];
            $dob = $_POST['date_of_birth_student'];

            $sql = "INSERT INTO students (user_id, name, student_number, program, gender, date_of_birth) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssss", $user_id, $name, $student_number, $program, $gender, $dob);
        }

        // Execute the insertion into the specific table
        if ($stmt->execute()) {
            echo "Registration successful!";
            // Redirect to verification page
            header("Location: verifyAccount.html");
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method.";
}
?>
