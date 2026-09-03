<?php
// Define username and password
$validUsername = 'admin';
$validPassword = 'admin';

// Initialize error message
$errorMessage = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve user input
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Validate username and password
    if ($username === $validUsername && $password === $validPassword) {
        // Successful login
        header('Location: Administrator.php?page=home'); // Redirect to dashboard or other page
        exit;
    } else {
        // Invalid credentials
        $errorMessage = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Log-in | Sakamoto's</title>
  <link rel="icon" type="image/x-icon" href="Sakamoto's.png">
  <style>
    /* General Reset */
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
  font-family: 'Sophia', sans-serif;
}


.container {
  display: flex;
  width: 100vw;
  height: 100vh;
}

.left-section {
  flex: 1;
  background-color: #d01e2b;
  display: flex;
  justify-content: center;
  align-items: center;
  color: white;
  overflow: hidden;
}

.mockup {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
}

/* Right Section */
.right-section {
  flex: 1;
  background-color: #f9f5ee;
  padding: 40px;
  display: flex;
  justify-content: center;
  align-items: center;
}


.form-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}

.form-header button {
  flex: 1;
  padding: 10px;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  font-size: 25px;
  cursor: pointer;
}

.form-header button.active {
  border-bottom: 2px solid #d01e2b;
  font-weight: bold;
}


body {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh;
}

/* Login Container */
.login-container {
  background: white;
  width: 100%;
  max-width: 400px;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
  text-align: center;
  border-radius: 20px;
}

/* Logo */
.logo h1 {
  font-size: 1.8rem;
  font-weight: bold;
  color: #d22e2e; /* Tim Hortons red */
  margin-bottom: 20px;
}

/* Form Group */
.form-group {
  position: relative;
  margin-bottom: 25px;
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

/* Login Button */
.login-button {
  background-color: #d22e2e;
  color: white;
  font-size: 1rem;
  padding: 10px;
  width: 100%;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
  transition: background-color 0.3s ease;
}

.login-button:hover {
  background-color: #b02525;
}

/* Footer Links */
.footer {
    margin-top: 15px;
    display: flex;
    align-items: center;
}

.footer a {
  font-size: 0.9rem;
  color: #d22e2e;
  text-decoration: none;
}

.footer a:hover {
  text-decoration: underline;
}

.footer-link {
  margin-top: 10px;
  display: block;
  text-align: center;
  color: #d22e2e;
  text-decoration: none;
  font-size: 0.9rem;
}

.footer-link:hover {
  text-decoration: underline;
}

.footer-login-error-message {
  place-items: center;
}

.footer-register-error-message {
  place-items: center;
  margin-top: 20px;
}

.footer-register-error-message {
  place-items: center;
  margin-top: 15px;
}
  </style>
</head>
<body>
  <div class="container">
    <div class="left-section">
      <img src="Sakamoto's.png" alt="App Mockup" class="mockup">
    </div>
    <div class="right-section">
      <div class="login-container">
        <div class="form-header">
          <button id="signUpBtn" class="active">Administrator Log-in</button>
        </div>
        <form class="login-form" method="POST">
          <div class="form-group">
            <input type="text" id="username" name="username" placeholder="Admin Username" required>
            <label for="username">Admin Username</label>
          </div>
          <div class="form-group">
            <input type="password" id="password" name="password" placeholder="Admin Password" required>
            <label for="password">Admin Password</label>
          </div>
          <button type="submit" class="login-button" id="formButton">Log-in</button>
        </form>
        <div class="footer">
            <?php
            if (!empty($errorMessage)) {
                echo "<p style='color:red;' class='error-message'>$errorMessage</p>";
            }
            ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
