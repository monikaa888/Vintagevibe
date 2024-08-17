<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vintage_vibe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
session_regenerate_id(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.html");
    exit();
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['password'] ?? null;

if (empty($email) || empty($password)) {
    echo "<script>alert('Email or password not provided.'); window.location.href = 'login.html';</script>";
    exit();
}

$sql = "SELECT id, password FROM user WHERE email = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            header("Location: ../index.html");
            exit();
        } else {
            echo "<script>alert('Invalid email or password.'); window.location.href = 'login.html';</script>";
        }
    } else {
        echo "<script>alert('No user found with that email.'); window.location.href = 'login.html';</script>";
    }
    $stmt->close();
} else {
    echo "<script>alert('Error preparing statement: " . $conn->error . "'); window.location.href = 'login.html';</script>";
}

$conn->close();
?>
