<?php
// db_connection.php

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "acadmeter";

// Create a new connection to the MySQL database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection was successful
if ($conn->connect_error) {
    // If there is a connection error, display a message and terminate the script
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
