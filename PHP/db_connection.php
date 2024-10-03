<?php
// Database configuration
$servername = "localhost";  // Hostname for the MySQL server, use 'localhost' if the database is hosted on the same server
$username = "root";         // MySQL username, typically 'root' for local development
$password = "";             // MySQL password, leave empty if no password is set for 'root'
$dbname = "acadmeter";      // Name of the database to connect to

// Create a new connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    // If there is a connection error, display a message and terminate the script
    die("Connection failed: " . $conn->connect_error);
}

// If the script reaches this point, the connection was successful
?>
