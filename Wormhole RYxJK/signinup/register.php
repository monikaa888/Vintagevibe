<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "vintage_vibe";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

function validate_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$user = validate_input($_POST['username']);
$email = validate_input($_POST['email']);
$first_name = validate_input($_POST['first_name']);
$last_name = validate_input($_POST['last_name']);
$raw_password = $_POST['password'];

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<script>
            alert('Invalid email format.');
            window.location.href = 'register.html';
          </script>";
    exit();
}

if (strlen($raw_password) < 8) {
    echo "<script>
            alert('Password must be at least 8 characters long.');
            window.location.href = 'register.html';
          </script>";
    exit();
}

$password = password_hash($raw_password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO user (username, email, first_name, last_name, password) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $user, $email, $first_name, $last_name, $password);

try {
    if ($stmt->execute()) {
        echo "<script>
                alert('Registration successful! Redirecting to login page.');
                window.location.href = 'login.html?registration=success';
              </script>";
        exit();
    } else {
        if ($conn->errno == 1062) {
            echo "<script>
                    alert('An account with this email already exists. Please use a different email address.');
                    window.location.href = 'register.html';
                  </script>";
        } else {
            echo "<script>
                    alert('An unexpected error occurred. Please try again later.');
                    window.location.href = 'register.html';
                  </script>";
        }
    }
} catch (Exception $e) {
    echo "<script>
            alert('Error: " . addslashes($e->getMessage()) . "');
            window.location.href = 'register.html';
          </script>";
}

$stmt->close();
$conn->close();
?>
