<?php
// Database Configuration
$host = "localhost";
$dbname = "mother_theresa_college";
$username = "root";
$password = ""; // Change if you have a MySQL password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, 3307);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

function generateOTP($length = 6) {
    return random_int(pow(10, $length - 1), pow(10, $length) - 1);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}
?>