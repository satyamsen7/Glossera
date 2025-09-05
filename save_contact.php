<?php
$input = json_decode(file_get_contents("php://input"), true);

$name    = $input['name'] ?? '';
$email   = $input['email'] ?? null;
$phone   = $input['phone'] ?? '';
$car     = $input['car'] ?? null;
$service = $input['service'] ?? '';
$message = $input['message'] ?? null;

if (empty($name) || empty($phone) || empty($service)) {
    http_response_code(400);
    echo "Missing required fields";
    exit;
}

$conn = new mysqli("localhost", "root", "", "glossera");
if ($conn->connect_error) {
    http_response_code(500);
    echo "DB connection failed";
    exit;
}

$stmt = $conn->prepare("INSERT INTO contact_inquiries (name, email, phone, car, service, message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'unread', NOW())");
$stmt->bind_param("ssssss", $name, $email, $phone, $car, $service, $message);

if ($stmt->execute()) {
    echo "Success";
} else {
    http_response_code(500);
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
