<?php

// Database connection parameters
$host = 'localhost';
$username = 'root';
$account_password = '';
$dbname = 'sakamoto';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = new mysqli($host, $username, $account_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the username session exists
if (!isset($_SESSION['account_username'])) {
    header("Location: Log-in.php");
    exit();
}

$username = $_SESSION['account_username'];

// Check login status from database
$sql = "SELECT is_logged FROM user_table WHERE account_username = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($isLogged);
$stmt->fetch();
$stmt->close();

// Allow access only if database says YES
if ($isLogged !== 'YES') {
    header("Location: Log-in.php");
    exit();
}

// UPDATE PROFILE
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && !isset($_POST['logout'])
    && !isset($_POST['changepass'])
) {

    header('Content-Type: application/json');

    // Check required profile fields
    if (
        isset(
            $_POST['account_username'],
            $_POST['account_email']
        )
    ) {

        $newUsername = trim($_POST['account_username']);
        $newEmail = trim($_POST['account_email']);

        // Get the CURRENT account ID from the logged-in username.
        // Do not trust account_id sent from JavaScript.
        $findUserSql = "
            SELECT account_id, account_image
            FROM user_table
            WHERE account_username = ?
        ";

        $findStmt = $conn->prepare($findUserSql);

        if (!$findStmt) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare user lookup.'
            ]);
            exit();
        }

        $findStmt->bind_param("s", $username);
        $findStmt->execute();

        $findResult = $findStmt->get_result();

        if ($findResult->num_rows === 0) {

            $findStmt->close();

            echo json_encode([
                'status' => 'error',
                'message' => 'Current user could not be found.'
            ]);

            exit();
        }

        $currentUser = $findResult->fetch_assoc();

        $account_id = $currentUser['account_id'];
        $oldImage = $currentUser['account_image'];

        $findStmt->close();
        
        // HANDLE PROFILE IMAGE
        $account_image = $oldImage;

        if (
            isset($_FILES['account_image'])
            && $_FILES['account_image']['error'] === UPLOAD_ERR_OK
        ) {

            $imageFile = $_FILES['account_image'];

            $targetDir = '../uploads/account_image/';

            // Create directory if it doesn't exist
            if (!is_dir($targetDir)) {

                if (!mkdir($targetDir, 0777, true)) {

                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Unable to create image directory.'
                    ]);

                    exit();
                }
            }


            // Get extension
            $fileExtension = strtolower(
                pathinfo(
                    $imageFile['name'],
                    PATHINFO_EXTENSION
                )
            );


            // Allowed image types
            $allowedExtensions = [
                'jpg',
                'jpeg',
                'png',
                'gif',
                'webp'
            ];


            if (!in_array($fileExtension, $allowedExtensions)) {

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid image format.'
                ]);

                exit();
            }


            // Generate unique filename
            $newFileName =
                'account_image_' .
                uniqid() .
                '.' .
                $fileExtension;


            $targetFile =
                $targetDir .
                $newFileName;


            // Move uploaded image
            if (
                move_uploaded_file(
                    $imageFile['tmp_name'],
                    $targetFile
                )
            ) {

                // Store path used by the website
                $account_image =
                    '../uploads/account_image/' .
                    $newFileName;

            } else {

                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to upload profile picture.'
                ]);

                exit();
            }
        }


        
        // UPDATE DATABASE
        $updateSql = "
            UPDATE user_table
            SET
                account_username = ?,
                account_email = ?,
                account_image = ?
            WHERE account_id = ?
        ";

        $updateStmt = $conn->prepare($updateSql);

        if (!$updateStmt) {

            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to prepare profile update.'
            ]);

            exit();
        }


        $updateStmt->bind_param(
            "sssi",
            $newUsername,
            $newEmail,
            $account_image,
            $account_id
        );


        
        // EXECUTE UPDATE
        if ($updateStmt->execute()) {

            // Update session username
            $_SESSION['account_username'] = $newUsername;

            $updateStmt->close();

            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully.'
            ]);

            exit();

        } else {

            $error = $updateStmt->error;

            $updateStmt->close();

            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to update profile: ' . $error
            ]);

            exit();
        }

    } else {

        echo json_encode([
            'status' => 'error',
            'message' => 'Username and email are required.'
        ]);

        exit();
    }
}

// Check if the form is submitted to change the password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['changepass'])) {

    // Make sure both password fields were submitted
    if (isset($_POST['new_password'], $_POST['confirm_password'])) {

        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Check that the passwords match
        if ($new_password !== $confirm_password) {

            echo json_encode(array(
                'status' => 'error',
                'message' => 'Passwords do not match.'
            ));

            exit();
        }

        // Update password for the currently logged-in user
        $updateSql = "UPDATE user_table 
                      SET account_password = ? 
                      WHERE account_username = ?";

        $stmt = $conn->prepare($updateSql);

        if ($stmt === false) {

            echo json_encode(array(
                'status' => 'error',
                'message' => 'Query preparation failed.'
            ));

            exit();
        }

        $stmt->bind_param(
            "ss",
            $new_password,
            $username
        );

        if ($stmt->execute()) {

            $stmt->close();

            // Redirect back to profile after successful password change
            
            echo "<script>
            alert('Password succesfully changed.');
            </script>";
            echo "<script>
            window.location.href = 'index.php?page=user_profile.php';
            </script>";
            exit();

        } else {

            echo json_encode(array(
                'status' => 'error',
                'message' => 'Failed to update password.'
            ));

            $stmt->close();
            exit();
        }

    } else {

        echo json_encode(array(
            'status' => 'error',
            'message' => 'Password fields are required.'
        ));

        exit();
    }
}


// Fetch user details from the database
$stmt = $conn->prepare("SELECT account_id, account_image, account_email, account_password FROM user_table WHERE account_username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $account_id = $user['account_id'];
    $profile_picture_path = $user['account_image'];
    $account_email = $user['account_email'];
} else {
    $account_id = '';
    $profile_picture_path = '';
    $account_email = '';
}
if (isset($_POST['logout']) && $_POST['logout'] === 'true'){ 
    // Update query to change 'is_logged' status for the current user based on their username
    $sql = "UPDATE user_table SET is_logged = 'NO' WHERE account_username = ?";  // Use the username to target the correct user

    // Prepare and execute the query
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);  // Bind the username from the session
    if ($stmt->execute()) {
        // Destroy the session
        session_destroy();
        // Redirect to login page
        header("Location: Log-in.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }

    $stmt->close();
}

$stmt->close();

$query = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$logosak = $row['logo_image'];
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
.profile-card {
    background-color: #ffffff;
    border-radius: 8px;
    border: 2px solid red;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    padding: 20px;
    width: 700px;
    margin-left: auto;
    margin-right: auto;
    box-sizing: border-box;
    margin-top: 0px;
}

.profile-image img {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin-right: 30px; /* Increased spacing */
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    margin: 0 0 10px 0;
    font-size: 1.8rem; /* Increased font size */
    color: #333333;
}

.profile-info p {
    margin: 5px 0;
    color: #555555;
}

.edit-btn {
    background-color: #d9393d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s;
}

.edit-btn:hover {
    background-color: #e0b99d;
	color: #d9393d;
}

.form-group {
    position: relative;
    margin-bottom: 25px;
}

input[type="checkbox"] {
    display: inline-block;
    margin: 5px;
    width: auto; /* Prevent width from being constrained */
}


input {
    width: 100%;
    padding: 12px 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    outline: none;
    background-color: white;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

input:focus {
    border-color: #d22e2e;
    background-color: #fff;
}

label {
    position: absolute;
    top: 12px;
    left: 10px;
    font-size: 1rem;
    color: #aaa;
    background: white;
    padding: 0 5px;
    transition: all 0.3s ease;
    opacity: 0; /* Hidden by default */
    pointer-events: none; /* Prevent interaction */
}

input::placeholder {
    color: #aaa;
    transition: opacity 0.3s ease;
}

input:focus::placeholder {
    opacity: 0; /* Hide placeholder on focus */
}

input:not(:placeholder-shown) + label,
input:focus + label {
    top: -12px;
    left: 10px;
    font-size: 0.8rem;
    color: #d22e2e;
    opacity: 1; /* Show the label */
}

.form-group {
    margin-bottom: 15px;
}

/* Ensure text fields fit within the container */
input, select {
    width: calc(100% - 20px);
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; /* Ensures padding is included in width calculation */
}

.submit-btn {
    background-color: #d22e2e;
    color: white;
    font-size: 1rem;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-btn:hover {
    background-color: #b02525;
}

/* PASSWORD MODAL */

.modal {
    display: none;
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(3px);
    justify-content: center;
    align-items: center;
    z-index: 9999;
}

/* MODAL CONTENT */

.modal-content {
    background: white;
    padding: 20px;
    border-radius: 8px;
    width: 90%;
    max-width: 570px;
    text-align: center;
    box-sizing: border-box;
}

/* MODAL HEADER */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.modal-header h2 {
    margin: 0;
    color: #d22e2e;
}

.close-btn {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
}


.confirmpass-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.confirmpass-modal {
    background: white;
    width: 350px;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.confirmpass-modal h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #d22e2e;
}

.confirmpass-modal p {
    margin-bottom: 25px;
}

.confirmpass-modal-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.confirmpass-modal-buttons button {
    padding: 8px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

#cancel-confirmpass {
    background-color: #ccc;
    color: #333;
}

#confirm-confirmpass {
    background-color: #d22e2e;
    color: white;
}

#confirm-confirmpass:hover {
    background-color: #b52222;
}

/* FORM GROUP / INPUTS */
.form-group {
    position: relative;
    margin-bottom: 15px;
}

input,
select {
    width: calc(100% - 20px);
    padding: 10px;
    font-size: 1rem;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
    outline: none;
    background-color: white;
    transition: border-color 0.3s ease, background-color 0.3s ease;
}

input:focus {
    border-color: #d22e2e;
    background-color: #fff;
}

label {
    position: absolute;
    top: 12px;
    left: 10px;
    font-size: 1rem;
    color: #aaa;
    background: white;
    padding: 0 5px;
    transition: all 0.3s ease;
    opacity: 0;
    pointer-events: none;
}

input::placeholder {
    color: #aaa;
    transition: opacity 0.3s ease;
}

input:focus::placeholder {
    opacity: 0;
}

input:not(:placeholder-shown) + label,
input:focus + label {
    top: -12px;
    left: 10px;
    font-size: 0.8rem;
    color: #d22e2e;
    opacity: 1;
}


/* SUBMIT BUTTON */
.submit-btn {
    background-color: #d22e2e;
    color: white;
    font-size: 1rem;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.submit-btn:hover {
    background-color: #b02525;
}

input[type="checkbox"] {
    display: inline-block;
    margin: 5px;
    width: auto;
}

/* CLOSE BUTTON */
.close-button {
    background-color: black;
    color: white;
    font-size: 1rem;
    padding: 10px;
    width: 100%;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-top: 20px;
}

.changepass-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}

.changepass-modal {
    background: white;
    width: 350px;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 5px 25px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.changepass-modal h3 {
    margin-top: 0;
    margin-bottom: 10px;
    color: #d22e2e;
}

.changepass-modal p {
    margin-bottom: 25px;
}

.changepass-modal-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
}

.changepass-modal-buttons button {
    padding: 8px 18px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 14px;
}

#cancel-changepass {
    background-color: #ccc;
    color: #333;
}

#confirm-changepass {
    background-color: #d22e2e;
    color: white;
}

#confirm-changepass:hover {
    background-color: #b52222;
}

.confirm-email-btn {
    background-color: #d22e2e;
    color: white;
    border: none;
    cursor: pointer;
}

.confirm-email-btn:hover {
    background-color: #b82428;
}

#confirm-changepass,
#confirm-changeemail {
    background-color: #d22e2e;
    color: white;
    border: none;
    cursor: pointer;
}

#confirm-changepass:hover,
#confirm-changeemail:hover {
    background-color: #b82428;
}


/* CONFIRM USERNAME CHANGE */
#confirm-changeusername {
    background-color: #d22e2e;
    color: white;
    border: none;
    cursor: pointer;
}

#confirm-changeusername:hover {
    background-color: #b82428;
}

/* CONFIRM USERNAME + EMAIL CHANGE */

#confirm-changeusernameemail {
    background-color: #d22e2e;
    color: white;
    border: none;
    cursor: pointer;
}

#confirm-changeusernameemail:hover {
    background-color: #b82428;
}

section {
    margin-top: 50px;
    margin-bottom: 50px;
    display: block;
}
</style>
    <link rel="stylesheet" href="style.php">
    <link rel="icon" href="<?php echo $logosak; ?>" type="image/x-icon">
</head>
<body>
<section>
    <div class="profile-card">
        <!-- Profile Image with Overlay -->
        <div class="profile-image-container"
             style="position: relative; width: 200px; height: 200px; border-radius: 50%; overflow: hidden;">
            <img src="<?php echo htmlspecialchars($profile_picture_path); ?>" 
                 alt="Profile Picture" 
                 style="width: 200px; height: 200px; object-fit: cover; border-radius: 50%;" 
                 id="profilePicturePreview">

            <!-- Image Overlay -->
            <div class="image-overlay"
                 onclick="document.getElementById('editable-image').click()" 
                 style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(128, 128, 128, 0.5); z-index: 2; border-radius: 50%; display: none; align-items: center; justify-content: center; opacity: 0; cursor: pointer; transition: opacity 0.3s;">
                <img src="upload.png" alt="Upload Icon" style="width: 200px; height: 200px; object-fit: contain;">
            </div>
        </div>
        <input type="file" id="editable-image" accept="image/*" style="display: none;" onchange="previewProfilePicture(event)">

        <!-- Profile Info Section -->
        <div class="profile-info" style="margin-left:40px;">
            <h1><span> Account Information</span></h1>
            <div style="height:10px;"></div>
            <p><strong>Username:</strong>
            <input type="text" id="editable-username" value="<?php echo htmlspecialchars($username); ?>" data-original-username="<?php echo htmlspecialchars($username); ?>" readonly style="border: none; background: transparent;"></p>
            <p><strong>Email:</strong><input type="email" id="editable-email" value="<?php echo htmlspecialchars($account_email); ?>" data-original-email="<?php echo htmlspecialchars($account_email); ?>" readonly style="border: none; background: transparent;"></p>
            <p><button class="edit-btn "id="edit-save-btn"onclick="toggleEdit()">Edit Profile</button>
                <button class="edit-btn" id="change-pass" type="button"> Change Password </button>
                <button class="edit-btn" onclick="logout()" type="button"> Log-out </button></p>
        </div>
    </div>
</section>


<!--CHANGE PASSWORD MODAL-->
<div class="modal" id="changepassModal">
    <div class="modal-content"
         style=" position: relative; margin: auto; top: 50%; transform: translateY(-50%); width: 80%; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
        <div class="modal-header">
            <h2>Password Settings</h2>
            <button class="close-btn" id="close-changepass-modal" type="button">&times;</button>
        </div>

        <form id="changepass-form" method="POST">
            <div class="form-group">
                <input type="password" id="new-password" name="new_password" placeholder="New Password" required>
                <label for="new-password">New Password</label>
            </div>
            <div class="form-group">
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm-password">Confirm Password</label>
            </div>
        <div style="display: flex; gap: 0px;">
        <div>
        <input type="checkbox" id="showPasswordLogin">
        </div>
        <div style="margin-top:-12px;">
        <p style="color:#d22e2e;">&nbsp;&nbsp;Show Password</p>
        </div>
        </div>
            <button type="submit" name="changepass"class="submit-btn">Change Password</button>
        </form>
        <button type="button" class="close-button" id="close-changepass-modal-btn">Close</button>
    </div>
</div>


<!--CONFIRM PASSWORD CHANGE MODAL-->
<div id="changepass-changepass-modal"
     class="changepass-modal-overlay">
    <div class="changepass-modal">
        <h3> Confirm Password Change</h3>
        <p> Are you sure you want to change password?</p>
        <div class="changepass-modal-buttons">
            <button type="button" id="cancel-changepass">Cancel</button>
            <button type="button" id="confirm-changepass">Yes, Change Password</button>
        </div>
    </div>
</div>

<!-- CONFIRM EMAIL CHANGE MODAL -->
<div id="changeemail-changeemail-modal"
     class="changepass-modal-overlay">
    <div class="changepass-modal">
        <h3> Confirm Email Change </h3>
        <p> Are you sure you want to change your email?</p>
        <div class="changepass-modal-buttons">
            <button type="button" id="cancel-changeemail">Cancel </button>
            <button type="button" id="confirm-changeemail">Yes, Change Email</button>
        </div>
    </div>
</div>

<!-- CONFIRM USERNAME CHANGE MODAL -->
<div id="changeusername-changeusername-modal"
     class="changepass-modal-overlay">
    <div class="changepass-modal">
        <h3> Confirm Username Change</h3>
        <p> Are you sure you want to change your username?</p>
        <div class="changepass-modal-buttons">
            <button type="button" id="cancel-changeusername">Cancel </button>
            <button type="button" id="confirm-changeusername">Yes, Change Username</button>
        </div>
    </div>
</div>


<!-- CONFIRM USERNAME AND EMAIL CHANGE MODAL -->
<div id="changeusernameemail-changeusernameemail-modal"
     class="changepass-modal-overlay">
    <div class="changepass-modal">
        <h3> Confirm Profile Changes</h3>
        <p>Are you sure you want to change both your username and email?</p>
        <div class="changepass-modal-buttons">
            <button type="button" id="cancel-changeusernameemail">Cancel</button>
            <button type="button" id="confirm-changeusernameemail">Yes, Change Both</button>
        </div>
    </div>
</div>

<!--LOGOUT FORM-->
<form id="logoutForm" method="post" action="user_profile.php" style="display: none;">
    <input type="hidden" name="logout" value="true">
</form>

<script>
// Show password
const newPasswordField = document.getElementById('new-password');
const confirmPasswordField = document.getElementById('confirm-password');
const showPasswordCheckbox = document.getElementById('showPasswordLogin');

showPasswordCheckbox.addEventListener('change', function () {

    const passwordType = this.checked ? 'text' : 'password';

    newPasswordField.type = passwordType;
    confirmPasswordField.type = passwordType;

});


/* CHANGE PASSWORD MODAL */
// Get Change Password modal
const modal = document.getElementById('changepassModal');

// Get Change Password button
const openModalBtn = document.getElementById('change-pass');

// Get close buttons
const closeModalBtns = document.querySelectorAll(
    '#close-changepass-modal, #close-changepass-modal-btn'
);

// Get Change Password form
const changePassForm = document.getElementById('changepass-form');

// Get confirmation modal
const confirmChangePassModal =
    document.getElementById('changepass-changepass-modal');

// Get confirmation buttons
const cancelChangePassBtn =
    document.getElementById('cancel-changepass');

const confirmChangePassBtn =
    document.getElementById('confirm-changepass');


// Open Change Password modal
const openModal = () => {

    modal.style.display = 'block';

};


// Close Change Password modal
const closeModal = () => {

    modal.style.display = 'none';

};


// Open Change Password modal
openModalBtn.addEventListener('click', openModal);


// Close Change Password modal using X or Close button
closeModalBtns.forEach(button => {

    button.addEventListener('click', closeModal);

});



/* CONFIRM PASSWORD CHANGE */

// Intercept Change Password form submission
changePassForm.addEventListener('submit', function(event) {

    // Prevent the password from being changed immediately
    event.preventDefault();


    // Get password values
    const newPassword =
        document.getElementById('new-password').value;

    const confirmPassword =
        document.getElementById('confirm-password').value;


    // Check if passwords match
    if (newPassword !== confirmPassword) {

        alert('Passwords do not match.');

        return;

    }


    // Open confirmation modal
    confirmChangePassModal.style.display = 'flex';

});


// Cancel password change
cancelChangePassBtn.addEventListener('click', function() {

    confirmChangePassModal.style.display = 'none';

});


// Confirm password change
confirmChangePassBtn.addEventListener('click', function() {

    // Hide confirmation modal
    confirmChangePassModal.style.display = 'none';

    // Create hidden input so PHP knows this is a password change
    const changePassInput = document.createElement('input');

    changePassInput.type = 'hidden';
    changePassInput.name = 'changepass';
    changePassInput.value = '1';

    changePassForm.appendChild(changePassInput);

    // Actually submit the password form
    changePassForm.submit();

});



/* PROFILE IMAGE OVERLAY */

const profileContainer =
    document.querySelector('.profile-image-container');

const imageOverlay =
    document.querySelector('.image-overlay');


if (profileContainer && imageOverlay) {

    profileContainer.addEventListener('mouseover', function () {

        imageOverlay.style.opacity = '1';

    });


    profileContainer.addEventListener('mouseout', function () {

        imageOverlay.style.opacity = '0';

    });

}



/* PREVIEW UPLOADED PROFILE IMAGE */

function previewProfilePicture(event) {

    const file = event.target.files[0];

    if (!file) {
        return;
    }

    const reader = new FileReader();

    reader.onload = function () {

        const output =
            document.getElementById('profilePicturePreview');

        output.src = reader.result;

    };

    reader.readAsDataURL(file);

}



/* =========================================================
   LOGOUT
   ========================================================= */

function logout() {

    document.getElementById('logoutForm').submit();

}



/* =========================================================
   EDIT PROFILE
   ========================================================= */

function toggleEdit() {

    const editSaveButton =
        document.getElementById('edit-save-btn');

    const usernameInput =
        document.getElementById('editable-username');

    const emailInput =
        document.getElementById('editable-email');

    const imageOverlay =
        document.querySelector('.image-overlay');


    if (editSaveButton.textContent.trim() === 'Edit Profile') {

        // Enable editing

        editSaveButton.textContent = 'Save Changes';

        usernameInput.readOnly = false;
        emailInput.readOnly = false;

        usernameInput.style.border = '1px solid var(--primary-color)';
        emailInput.style.border = '1px solid var(--primary-color)';

        imageOverlay.style.display = 'flex';
        imageOverlay.style.opacity = '1';
        imageOverlay.style.pointerEvents = 'auto';

    }

    else {

        
        // GET ORIGINAL VALUES
        

        const originalUsername =
            usernameInput.getAttribute('data-original-username');

        const originalEmail =
            emailInput.getAttribute('data-original-email');


        
        // GET CURRENT VALUES
        

        const currentUsername =
            usernameInput.value.trim();

        const currentEmail =
            emailInput.value.trim();


        
        // CHECK WHAT WAS CHANGED
        

        const usernameChanged =
            currentUsername !== originalUsername;

        const emailChanged =
            currentEmail !== originalEmail;


        
        // BOTH USERNAME AND EMAIL CHANGED
        

        if (usernameChanged && emailChanged) {

            changeUsernameEmailModal.style.display = 'flex';

            return;

        }


        
        // USERNAME ONLY CHANGED
        

        if (usernameChanged) {

            changeUsernameModal.style.display = 'flex';

            return;

        }


        
        // EMAIL ONLY CHANGED
        

        if (emailChanged) {

            changeEmailModal.style.display = 'flex';

            return;

        }


        
        // NOTHING CHANGED
        

        saveProfile();

        editSaveButton.textContent = 'Edit Profile';

        usernameInput.readOnly = true;
        emailInput.readOnly = true;

        usernameInput.style.border = 'none';
        emailInput.style.border = 'none';

        imageOverlay.style.display = 'none';
        imageOverlay.style.opacity = '0';
        imageOverlay.style.pointerEvents = 'none';

    }

}



/* EMAIL CHANGE CONFIRMATION */
/* PROFILE CHANGE CONFIRMATIONS */
// EMAIL MODAL
const changeEmailModal =
    document.getElementById('changeemail-changeemail-modal');

const cancelChangeEmailBtn =
    document.getElementById('cancel-changeemail');

const confirmChangeEmailBtn =
    document.getElementById('confirm-changeemail');



// USERNAME MODAL
const changeUsernameModal =
    document.getElementById('changeusername-changeusername-modal');

const cancelChangeUsernameBtn =
    document.getElementById('cancel-changeusername');

const confirmChangeUsernameBtn =
    document.getElementById('confirm-changeusername');



// BOTH USERNAME + EMAIL MODAL
const changeUsernameEmailModal =
    document.getElementById(
        'changeusernameemail-changeusernameemail-modal'
    );

const cancelChangeUsernameEmailBtn =
    document.getElementById(
        'cancel-changeusernameemail'
    );

const confirmChangeUsernameEmailBtn =
    document.getElementById(
        'confirm-changeusernameemail'
    );



// RETURN PROFILE TO NORMAL STATE
function resetProfileEditState() {

    const editSaveButton =
        document.getElementById('edit-save-btn');

    const usernameInput =
        document.getElementById('editable-username');

    const emailInput =
        document.getElementById('editable-email');

    const imageOverlay =
        document.querySelector('.image-overlay');


    // Restore original values
    usernameInput.value =
        usernameInput.getAttribute('data-original-username');

    emailInput.value =
        emailInput.getAttribute('data-original-email');


    // Return to normal state
    editSaveButton.textContent = 'Edit Profile';

    usernameInput.readOnly = true;
    emailInput.readOnly = true;

    usernameInput.style.border = 'none';
    emailInput.style.border = 'none';

    imageOverlay.style.display = 'none';
    imageOverlay.style.opacity = '0';
    imageOverlay.style.pointerEvents = 'none';

}



// CANCEL EMAIL CHANGE
cancelChangeEmailBtn.addEventListener('click', function() {

    changeEmailModal.style.display = 'none';

    resetProfileEditState();

});



// CONFIRM EMAIL CHANGE
confirmChangeEmailBtn.addEventListener('click', function() {

    changeEmailModal.style.display = 'none';

    saveProfile();

});



// CANCEL USERNAME CHANGE
cancelChangeUsernameBtn.addEventListener('click', function() {

    changeUsernameModal.style.display = 'none';

    resetProfileEditState();

});



// CONFIRM USERNAME CHANGE
confirmChangeUsernameBtn.addEventListener('click', function() {

    changeUsernameModal.style.display = 'none';

    saveProfile();

});



// CANCEL BOTH CHANGES
cancelChangeUsernameEmailBtn.addEventListener('click', function() {

    changeUsernameEmailModal.style.display = 'none';

    resetProfileEditState();

});



// CONFIRM BOTH CHANGES
confirmChangeUsernameEmailBtn.addEventListener('click', function() {

    changeUsernameEmailModal.style.display = 'none';

    saveProfile();

});


/* SAVE PROFILE */
function saveProfile() {

    const formData = new FormData();

    const usernameInput =
        document.getElementById('editable-username').value.trim();

    const emailInput =
        document.getElementById('editable-email').value.trim();

    const imageInput =
        document.getElementById('editable-image');


    formData.append(
        'account_username',
        usernameInput
    );

    formData.append(
        'account_email',
        emailInput
    );


    // Only send an image if the user selected a NEW image
    if (imageInput.files.length > 0) {

        formData.append(
            'account_image',
            imageInput.files[0]
        );
    }


    fetch('user_profile.php', {

        method: 'POST',

        body: formData

    })

    .then(response => {

        if (!response.ok) {
            throw new Error(
                'Server returned HTTP ' + response.status
            );
        }

        return response.json();

    })

    .then(data => {

        if (data.status === 'success') {

            alert(data.message);

            location.reload();

        } else {

            alert(data.message);

        }

    })

    .catch(error => {

        console.error('Profile update error:', error);

        alert(
            'An error occurred while saving the profile.'
        );

    });
}

</script>
</body>
</html>