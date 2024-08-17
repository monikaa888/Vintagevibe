
<?php
session_start();

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbname = "vintage_vibe";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT id, username, first_name, last_name, email FROM user WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare SQL statement']);
    $conn->close();
    exit();
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $first_name, $last_name, $email);
$stmt->fetch();

if ($username) {
    echo json_encode([
        'id' => $id,
        'username' => $username,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email
    ]);
} else {
    echo json_encode(['error' => 'No user found']);
}

$stmt->close();
$conn->close();
?>
