<?php
$host = "localhost";
$dbname = "phone_directory";
$username = "root"; // change if needed
$password = "";     // change if needed

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8mb4");
?>
