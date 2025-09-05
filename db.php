<?php
$host = "localhost";     // Database host
$user = "root";          // Database username
$pass = "";              // Database password
$db   = "glossera";   // Database name

$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Optional: set charset to utf8
$conn->set_charset("utf8");
?>
