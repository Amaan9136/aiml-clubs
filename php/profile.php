<?php
session_start();

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../login.html");
    exit();
}

$mysqli = new mysqli("localhost", "root", "", "club");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$response = array(); // Initialize the response array

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newName = $_POST["name"];
    $newPhone = $_POST["phone"];
    $newEmail = $_POST["email"];
    $newPassword = $_POST["password"];
    $newWhatsApp = $_POST["whatsapp"];
    $newInsta = $_POST["insta"];
    $newLinkedIn = $_POST["linkedin"];
    $newGitHub = $_POST["github"];
    $newGender = $_POST["gender"];

    $usn = $_SESSION['usn'];
    $sessionPhone = $_SESSION['phone'];

    if ($newPhone !== "" && !is_numeric($newPhone)) {
        $response['success'] = false;
        $response['message'] = "Invalid phone number!";
        echo json_encode($response);
        exit;
    }

    $uploadsDirectory = __DIR__ . "/uploads";

    if (!file_exists($uploadsDirectory)) {
        if (!mkdir($uploadsDirectory, 0755, true)) {
            $response['success'] = false;
            $response['message'] = "Failed to create 'uploads' directory.";
            echo json_encode($response);
            exit;
        }
    }

    if (isset($_FILES["profilepicture"]["tmp_name"]) && !empty($_FILES["profilepicture"]["tmp_name"])) {
        $targetDirectory = "uploads/";
        $profilePictureName = uniqid() . "_" . basename($_FILES["profilepicture"]["name"]);
        $targetFile = $targetDirectory . $profilePictureName;

        // Retrieve the old profile picture filename from the database
        $oldProfilePicture = "";
        $sql = "SELECT profilepicture FROM register WHERE usn = ? OR phone = ?";
        $stmt = $mysqli->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ss", $usn, $sessionPhone);

            if ($stmt->execute()) {
                $stmt->bind_result($oldProfilePicture);

                if ($stmt->fetch() && !empty($oldProfilePicture)) {
                    // Construct the full file path for the old profile picture
                    $oldProfilePicturePath = "uploads/" . $oldProfilePicture;
                }

                $stmt->close();
            } else {
                $response['success'] = false;
                $response['message'] = "Error retrieving old profile picture: " . $stmt->error;
                echo json_encode($response);
                exit;
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Error preparing statement: " . $mysqli->error;
            echo json_encode($response);
            exit;
        }

        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $allowedExtensions = array("jpg", "jpeg", "png", "gif");

        if (in_array($imageFileType, $allowedExtensions)) {
            if (move_uploaded_file($_FILES["profilepicture"]["tmp_name"], $targetFile)) {
                $sql = "UPDATE register SET profilepicture = ? WHERE usn = ? OR phone = ?";
                $stmt = $mysqli->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("sss", $profilePictureName, $usn, $sessionPhone);

                    if ($stmt->execute()) {
                        $response['success'] = true;
                        $response['message'] = "Profile picture updated successfully.";
                        $response['profilePictureUrl'] = $targetFile; // Add profile picture URL to the response

                        // Delete the old profile picture only if the update was successful
                        if (!empty($oldProfilePicture) && $response['success']) {
                            $oldProfilePicturePath = "uploads/" . $oldProfilePicture;
                            if (file_exists($oldProfilePicturePath)) {
                                unlink($oldProfilePicturePath);
                            }
                        }
                    } else {
                        $response['success'] = false;
                        $response['message'] = "Error updating profile picture: " . $stmt->error;
                    }

                    $stmt->close();
                } else {
                    $response['success'] = false;
                    $response['message'] = "Error preparing statement: " . $mysqli->error;
                }
            } else {
                $response['success'] = false;
                $response['message'] = "Error uploading profile picture.";
            }
        } else {
            $response['success'] = false;
            $response['message'] = "Invalid file format. Allowed formats: JPG, JPEG, PNG, GIF.";
        }
    } else {
        $sql = "UPDATE register SET ";
        $updates = array();

        if (!empty($newName)) {
            $sql .= "fullname = ?, ";
            $updates[] = $newName;
        }
        if (!empty($newPhone)) {
            $sql .= "phone = ?, ";
            $updates[] = $newPhone;
        }
        if (!empty($newEmail)) {
            $sql .= "email = ?, ";
            $updates[] = $newEmail;
        }
        if (!empty($newPassword)) {
            $sql .= "password = ?, ";
            $updates[] = $newPassword;
        }
        if (!empty($newWhatsApp)) {
            $sql .= "whatsapp = ?, ";
            $updates[] = $newWhatsApp;
        }
        if (!empty($newInsta)) {
            $sql .= "instagram = ?, ";
            $updates[] = $newInsta;
        }
        if (!empty($newLinkedIn)) {
            $sql .= "linkedin = ?, ";
            $updates[] = $newLinkedIn;
        }
        if (!empty($newGitHub)) {
            $sql .= "github = ?, ";
            $updates[] = $newGitHub;
        }
        if (!empty($newGender)) {
            $sql .= "gender = ?, ";
            $updates[] = $newGender;
        }

        $sql = rtrim($sql, ', ');

        if (!empty($updates)) {
            $sql .= " WHERE usn = ? OR phone = ?";
            $stmt = $mysqli->prepare($sql);

            if ($stmt) {
                $bindParams = array_merge($updates, array($usn, $sessionPhone));
                $types = str_repeat('s', count($bindParams));
                $stmt->bind_param($types, ...$bindParams);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    // Add updated data to the response
                    $response['name'] = $newName;
                    $response['phone'] = $newPhone;
                    $response['email'] = $newEmail;
                    $response['whatsapp'] = $newWhatsApp;
                    $response['instagram'] = $newInsta;
                    $response['linkedin'] = $newLinkedIn;
                    $response['github'] = $newGitHub;
                    $response['message'] = "Profile updated successfully.";
                } else {
                    $response['success'] = false;
                    $response['message'] = "Error updating profile: " . $stmt->error;
                }

                $stmt->close();
            } else {
                $response['success'] = false;
                $response['message'] = "Error preparing statement: " . $mysqli->error;
            }
        } else {
            $response['success'] = true; // No fields to update is considered a success
            $response['message'] = "No fields to update.";
        }
    }

    $mysqli->close();

    // Send the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
