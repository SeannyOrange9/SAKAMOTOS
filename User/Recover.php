<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

session_start(); // Start the session to store and retrieve data

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verification_code'])) {
    // Get the input verification code from the form
    $inputCode = $_POST['verification_code'];
    
    // Query to check if the verification code exists in the database
    $sql = "SELECT account_id FROM user_table WHERE verification_code = ?";
    
    // Prepare and execute the query to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inputCode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update the status to "Enabled"
        $updateSql = "UPDATE user_table SET account_status = 'Enabled' WHERE verification_code = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("s", $inputCode);
        $updateStmt->execute();
        $updateStmt->close();
        
        // Update logged-in status
        $updateloggedSql = "UPDATE user_table SET is_logged = 'YES' WHERE verification_code = ?";
        $updateloggedStmt = $conn->prepare($updateloggedSql);
        $updateloggedStmt->bind_param("s", $inputCode);
        $updateloggedStmt->execute();
        $updateloggedStmt->close();

        $successMessage = "Verification successful!";
        header("Location: index.php?page=home.php");
        exit();
    } else {
        $errorMessage = "Invalid verification code.";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Retrieve Code | Sakamoto's</title>
  <link rel="icon" type="image/x-icon" href="Sakamoto's.png">
  <link rel="stylesheet" href="style1.css">
</head>
<body>
  <div class="container">
    <div class="left-section">
      <img src="Sakamoto's.png" alt="App Mockup" class="mockup">
    </div>
    <div class="right-section">
      <div class="login-container">
        <div class="form-header">
          <button id="signUpBtn" class="active">Retrieve Code</button>
        </div>
        <form class="login-form" method="POST">
          <div class="form-group">
            <input type="text" id="vcode" name="verification_code" placeholder="Verification Code" required>
            <label for="vcode">Recovery Code</label>
          </div>
          <button type="submit" class="login-button" id="formButton">Retreive Account</button>
          <a href="Forgot.php" id="resendCode" class="footer-link">Resend Code?</a>
        </form>
        <div class="footer" style="color: <?php echo isset($successMessage) ? 'green' : (isset($errorMessage)  ? 'red' : ''); ?>;">
        <?php
        if (isset($successMessage)) {
          echo $successMessage;
        } 
        if (isset($errorMessage)) {
          echo $errorMessage;
        } 
        ?>
      </div>
      </div>
    </div>
  </div>
</body>
</html>
