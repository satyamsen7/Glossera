<?php
// Get JSON data
$input = json_decode(file_get_contents("php://input"), true);

$service = $input['service'] ?? '';
$price   = $input['price'] ?? '';
$name    = $input['name'] ?? '';
$phone   = $input['phone'] ?? '';
$email   = $input['email'] ?? null;

// Validate
if (empty($service) || empty($price) || empty($name) || empty($phone)) {
    http_response_code(400);
    echo "Missing required fields";
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "", "glossera");
if ($conn->connect_error) {
    http_response_code(500);
    echo "DB connection failed";
    exit;
}

// Insert query
$stmt = $conn->prepare("INSERT INTO bookings (service_name, price, name, phone, email, status) VALUES (?, ?, ?, ?, ?, 'unread')");
$stmt->bind_param("sssss", $service, $price, $name, $phone, $email);

if ($stmt->execute()) {
    echo "Success";
} else {
    http_response_code(500);
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
