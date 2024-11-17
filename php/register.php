<?php
header('Content-Type: application/json'); 

$response = array(); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Connect to the database
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "club";
  $conn = mysqli_connect($servername, $username, $password, $dbname);

  if (!$conn) {
    $response["success"] = false;
    $response["message"] = "Connection failed: " . mysqli_connect_error();
  } else {
    $usn = $_POST["usn"];
    $fullname = $_POST["fullname"];
    $password = $_POST["password"];
    $phone = $_POST["phone"];
    $email = $_POST["email"];
    $year = $_POST["year"];
    $credits = 5;

    // Check the length of USN
    if (strlen($usn) > 15) {
      $response["success"] = false;
      $response["message"] = "Invalid USN!";
    } else {
      // Check if phone contains letters or exceeds 10 digits
      if (preg_match("/[a-zA-Z]/", $phone) || strlen($phone) > 10) {
        $response["success"] = false;
        $response["message"] = "Invalid phone number!";
      } else {
        $sql = "SELECT * FROM register WHERE usn = '$usn' OR phone = '$phone' OR email = '$email'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
          $response["success"] = false;
          $response["message"] = "User already exists!";
        } else {
          $sql1 = "INSERT INTO register (usn, fullname, password, phone, email, year, credits)
          VALUES ('$usn', '$fullname', '$password', '$phone', '$email', '$year', '$credits')";

          if (mysqli_query($conn, $sql1)) {
            $response["success"] = true;
            $response["message"] = "Registration successful!";
          } else {
            $response["success"] = false;
            $response["message"] = "Error occurred while registering: " . mysqli_error($conn);
          }
        }
      }
    }
    mysqli_close($conn);
  }
}

// Return the JSON response
echo json_encode($response);
?>
