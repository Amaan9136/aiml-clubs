<?php
// Set the session lifetime to 5 days (5 * 24 * 60 * 60 seconds)
$sessionLifetime = 5 * 24 * 60 * 60;

// Set session cookie parameters
session_set_cookie_params($sessionLifetime);

// Start the session
session_start();

// Connect to the database
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "club";

$mysqli = new mysqli($servername, $username, $password, $dbname);

// Check for errors
if ($mysqli->connect_error) {
    die("Failed to connect to the database: " . $mysqli->connect_error);
}

// Get the login data from the request
$input = json_decode(file_get_contents('php://input'), true);
$UsnOrMobile = $input["UsnOrMobile"];
$password = $input["password"];

// Escape special characters to prevent SQL injection
$UsnOrMobile = $mysqli->real_escape_string($UsnOrMobile);
$password = $mysqli->real_escape_string($password);

// Query the database to check if the USN or phone number and password match
$result = $mysqli->query("SELECT * FROM register WHERE usn = '$UsnOrMobile' OR phone = '$UsnOrMobile'");

if ($result->num_rows === 1) {
    // USN or Mobile number exists, check password
    $row = $result->fetch_assoc();
    if ($row["password"] === $password) {
        // Login successful
        $_SESSION['logged_in'] = true; //setting logged_in flag in session
        $_SESSION['phone'] = $row['phone'];
        $_SESSION['usn'] = $row['usn'];

        // Set session expiration time
        $_SESSION['expiry'] = time() + $sessionLifetime;

        $response = array(
            "success" => true,
            "message" => "Login successful!"
        );
    } else {
        // Invalid password
        $response = array(
            "success" => false,
            "message" => "Invalid password!"
        );
    }
} else {
    // Invalid USN or Mobile number
    $response = array(
        "success" => false,
        "message" => "USN or Mobile number not registered!"
    );
}

// Send the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
