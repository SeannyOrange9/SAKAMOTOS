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
    // Determine the action
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        // Get form data
    $username = $_POST['uname'];
    $email = $_POST['maile'];
    $password = $_POST['passe'];
    $cpassword = $_POST['cpassword'];

    // Server-side validation
        if (empty($username) || empty($email) || empty($password) || empty($cpassword)) {

        $_SESSION['register_error'] = "All fields must be filled out.";

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } elseif ($password !== $cpassword) {

        $_SESSION['register_error'] = "Passwords do not match!";

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } else { 
        // Check if username or email already exists in the database
        $checkQuery = "SELECT * FROM user_table WHERE account_username = ? OR account_email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If there's already a matching record, show an error message
        if ($result->num_rows > 0) {

            $_SESSION['register_error'] = "Username or Email already taken.";

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

            } else {

            // Generate a verification code before inserting the user
            $verification_code = rand(100000, 999999); // Generate a 6-digit verification code
            $_SESSION['verification_code'] = $verification_code; // Store it in the session for later use

            // Prepare SQL query to insert user data into the database
            $sql = "INSERT INTO user_table (account_username, account_email, account_password, verification_code) VALUES (?, ?, ?, ?)";  // Note the column name is `verification_code`
            
            // Prepare statement
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $username, $email, $password, $verification_code);

            // Execute the query
            if ($stmt->execute()) {
                // Get the email to send the verification code
                $recipientEmail = $email;

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
                    $mail->addAddress($recipientEmail); // Send to the registered email
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
								background-color: #e7decc;
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
							
							h1 {
								color: #d22e2e;
								text-align: center;
							}
							
							.center {
								text-align: center;
							}
							
                        </style>
						</head>
						<body>
							<div class='header'><img src='mailer.png' style='width: 5px;'></div>
							<h1>Welcome to Sakamoto's</h1>
							<div class='center'>
							<p>Your verification code is: <span class='code'>$verification_code</span></p>
							</div>
						</body>
						</html>
                    ";
                    $mail->send();
                    echo "Verification code sent to $recipientEmail.";
                    header("Location: Verification.php");
                    exit();
                } catch (Exception $e) {
                    echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {

            $_SESSION['register_error'] = "Error: " . $stmt->error;

            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        }
        }
    }

    } else if ($action === 'login') {
    // Log-in logic
    $inputUsername = $_POST['account_username'];
    $inputPassword = $_POST['account_password'];

    // Query to fetch the stored password and status for the provided username
    $sql = "SELECT account_id, account_password, account_status, is_logged FROM user_table WHERE account_username = ?";

    // Prepare and execute the query to prevent SQL injection
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Check if account is blocked
        if ($row['account_status'] === 'Disabled') {
            $_SESSION['login_error'] = "Your account is blocked";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();

        // Check password
        } elseif ($inputPassword === $row['account_password']) {
            if (empty($row['is_logged']) || $row['is_logged'] === 'NO') {
                $updateisloggedsql = "UPDATE user_table SET is_logged = 'YES' WHERE account_username = ?";
                $stmtUpdate = $conn->prepare($updateisloggedsql);
                $stmtUpdate->bind_param("s", $inputUsername);
                $stmtUpdate->execute();
                $stmtUpdate->close();

                $_SESSION['account_username'] = $inputUsername;
                header("Location: index.php?page=home.php");
                exit();
            }
            elseif ($row['is_logged'] === 'YES') {
                // If user is already logged in, allow to proceed
                $_SESSION['account_username'] = $inputUsername;
                header("Location: index.php?page=home.php");
                exit();
            }
            else {
                $_SESSION['login_error'] = "User already logged in this device or other devices!";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            // Wrong password
            $_SESSION['login_error'] = "Wrong Username or Password!";
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        // Username does not exist
        $_SESSION['login_error'] = "Wrong Username or Password!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    $stmt->close();
}


}

// Get login error from session
$loginError = $_SESSION['login_error'] ?? '';

// Remove it immediately so it only appears once
unset($_SESSION['login_error']);

// Get registration error from session
$registerError = $_SESSION['register_error'] ?? '';

// Remove it immediately so it only appears once
unset($_SESSION['register_error']);

$query = "SELECT logo_image FROM logo_table WHERE logo_id = 'logo'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

$logosak = $row['logo_image'];

// Close the database connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Sakamoto's</title>
  <link rel="icon" type="image/x-icon" href="Sakamoto's.png">
  <link rel="stylesheet" href="log-register.css">
  <style>
  </style>

</head>
<body>
	<div class="container">
  <div class="left-section">
    <img src="<?php echo $logosak; ?>" alt="App Mockup" class="mockup">
  </div>
  <div class="right-section">
    <div class="login-container">
      <div class="form-header">
        <button id="signUpBtn" class="active">Register</button>
        <button id="signInBtn">Log-in</button>
      </div>

      <!-- Registration Form -->
<form id="registerForm" class="login-form" method="POST" style="display: block;">
    <input type="hidden" name="action" value="register">
    <div class="form-group">
        <input type="text" id="regUsername" name="uname" placeholder="Username" required>
        <label for="regUsername">Username</label>
    </div>
    <div class="form-group">
        <input type="email" id="email" name="maile" placeholder="Email" required>
        <label for="email">Email</label>
    </div>
    <div class="form-group">
        <input type="password" id="password" name="passe" placeholder="Password" required>
        <label for="password">Password</label>
    </div>
    <div class="form-group">
        <input type="password" id="confirmPassword" name="cpassword" placeholder="Confirm Password" required>
        <label for="confirmPassword">Confirm Password</label>
    </div>
    
    <div style="display: flex; gap: 1px;">
        <div style="padding: 0px;">
        <input type="checkbox" id="showPasswordRegister">
        </div>
        <div>
        <p style="color:#d22e2e;">&nbsp;&nbsp;Show Password</p>
        </div>
    </div>
    <button type="submit" class="login-button">Create Account</button>
    <div class="footer-register-error-message" style="color: red;">
        <?php echo $registerError; ?>
    </div>
</form>

<!-- Log-in Form -->
<form id="loginForm" class="login-form" method="POST" style="display: none;">
    <input type="hidden" name="action" value="login">
    <div class="form-group">
        <input type="text" id="loginUsername" name="account_username" placeholder="Username" required>
        <label for="loginUsername">Username</label>
    </div>
    <div class="form-group">
        <input type="password" id="loginPassword" name="account_password" placeholder="Password" required>
        <label for="loginPassword">Password</label>	
    </div>
    <div style="display: flex; gap: 1px;">
        <div style="padding: 0px;">
        <input type="checkbox" id="showPasswordLogin">
        </div>
        <div>
        <p style="color:#d22e2e;">&nbsp;&nbsp;Show Password</p>
        </div>
    </div>
    <button type="submit" class="login-button">Log-in</button>
    <a href="Forgot.php" class="footer-link">Forgot Password?</a>
    <div class="footer" id="loginMessage"></div>

    <div class="footer-login-error-message" style="color: red;">
        <?php echo $loginError; ?>
    </div>
    </form>
	
	  
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const signUpBtn = document.getElementById('signUpBtn');
    const signInBtn = document.getElementById('signInBtn');
    const registerForm = document.getElementById('registerForm');
    const loginForm = document.getElementById('loginForm');

    const switchToSignIn = () => {
        signUpBtn.classList.remove('active');
        signInBtn.classList.add('active');
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
        document.title = "Log-in | Sakamoto's";
    };

    const switchToSignUp = () => {
        signInBtn.classList.remove('active');
        signUpBtn.classList.add('active');
        registerForm.style.display = 'block';
        loginForm.style.display = 'none';
        document.title = "Register | Sakamoto's";
    };

    signUpBtn.addEventListener('click', switchToSignUp);
    signInBtn.addEventListener('click', switchToSignIn);

    <?php
        if (!empty($loginError)) {
            echo "switchToSignIn();";
        } elseif (!empty($registerError)) {
            echo "switchToSignUp();";
        } else {
            echo "switchToSignUp();";
        }
    ?>

    // Show password
    const passwordField = document.getElementById('loginPassword');
    const showPasswordCheckbox = document.getElementById('showPasswordLogin');

    showPasswordCheckbox.addEventListener('change', function () {
        passwordField.type = this.checked ? 'text' : 'password';
    });

    // Register form show password
        const regPasswordField = document.getElementById('password');
        const regConfirmPasswordField = document.getElementById('confirmPassword');
        const showPasswordRegisterCheckbox = document.getElementById('showPasswordRegister');

        showPasswordRegisterCheckbox.addEventListener('change', function () {
        const type = this.checked ? 'text' : 'password';
        regPasswordField.type = type;
        regConfirmPasswordField.type = type;
        });

});
</script>
</body>
</html>