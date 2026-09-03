<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// Start the session at the beginning
session_start();

// Database credentials
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

// Create a connection using mysqli (object-oriented style)
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $email = $_POST['email'];

    // Server-side validation
    if (empty($email)) {
        $errorMessage = "Email must be filled out.";
    } else {
        // Check if email exists in the database
        $checkQuery = "SELECT * FROM user_table WHERE account_email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Generate a verification code
            $verification_code = rand(100000, 999999); // Generate a 6-digit verification code
            $_SESSION['verification_code'] = $verification_code; // Store it in the session for later use

            // Prepare SQL query to update verification code in the database
            $updateQuery = "UPDATE user_table SET verification_code = ? WHERE account_email = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ss", $verification_code, $email);

            // Execute the query
            if ($stmt->execute()) {
                // Send the verification code via email
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'example@gmail.com';
                    $mail->Password = ''; // App password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;

                    $mail->setFrom('example@gmail.com', 'Sakamoto\'s');
                    $mail->addAddress($email); // Send to the registered email
                    $mail->Subject = 'Your Verification Code';
                    $mail->isHTML(true);
                    $mail->Body = "
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                font-size: 14px;
                                text-align: center;
                            }
                            .code {
                                font-weight: bold;
                                color: #d22e2e;
                            }
                            .header {
                                background-color: #d22e2e; /* Red bar */
                                color: white;
                                padding: 10px;
                                font-size: 18px;
                                text-align: center;
                                font-weight: bold;
                            }
                        </style>
                    </head>
                    <body>
                        <div class='header'>Welcome to Sakamoto's</div>
                        <p>Your verification code is: <span class='code'>$verification_code</span></p>
                    </body>
                    </html>
                    ";
                    $mail->send();
                    header("Location:Verification.php");
                    exit();
                } catch (Exception $e) {
                    echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                $errorMessage = "Error: " . $stmt->error;
            }
        } else {
            $errorMessage = "Email not found in the database.";
        }
    }
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Email | Sakamoto's</title>
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
          <button id="signUpBtn" class="active">Enter Email for Resend</button>
        </div>
        <form class="login-form" method="POST">
          <div class="form-group">
            <input type="email" id="mail" name="email" placeholder="Email" required>
            <label for="mail">Email</label>
          </div>
          <button type="submit" class="login-button" id="formButton">Send Code</button>
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
