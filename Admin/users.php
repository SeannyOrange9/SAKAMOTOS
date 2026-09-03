<?php
session_start();

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add operation
if (isset($_POST['addUser'])) {
    $account_username = $_POST['account_username'];
    $account_email = $_POST['account_email'];
    $account_password = $_POST['account_password'];

    // Server-side validation
    if (empty($account_username) || empty($account_email) || empty($account_password)) {
        $usernameRegError = $emailRegError = $passwordRegError = "All fields must be filled out.";
    } else {
        // Check if username or email already exists in the database
        $checkQuery = "SELECT * FROM user_table WHERE account_username = ? OR account_email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("ss", $account_username, $account_email);
        $stmt->execute();
        $result = $stmt->get_result();

        // If there's already a matching record, show an error message
        if ($result->num_rows > 0) {
            $errorRegMessage = "Username or Email already taken.";
			echo "<script>
				alert('Error: Username or Email name already taken.');
				window.location.href = 'Administrator.php?page=users.php';
			</script>";
			exit;
        } else {
            // Generate a verification code before inserting the user
            $verification_code = rand(100000, 999999); // Generate a 6-digit verification code
            $_SESSION['verification_code'] = $verification_code; // Store it in the session for later use

            // Prepare SQL query to insert user data into the database
            $sql = "INSERT INTO user_table (account_username, account_email, account_password, verification_code) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $account_username, $account_email, $account_password, $verification_code);

            // Execute the query
            if ($stmt->execute()) {
                // Update the status to "Enabled"
                $updateSql = "UPDATE user_table SET account_status = 'Enabled' WHERE verification_code = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("s", $verification_code);
                $updateStmt->execute();
                $updateStmt->close();

                // Add a temporary profile picture
                $temporaryProfilePicture = 'temporary_profile.png';
                $updateProfileSql = "UPDATE user_table SET account_image = ? WHERE verification_code = ?";
                $updateProfileStmt = $conn->prepare($updateProfileSql);
                $updateProfileStmt->bind_param("ss", $temporaryProfilePicture, $verification_code);
                $updateProfileStmt->execute();
                $updateProfileStmt->close();
            } else {
                $errorRegMessage = "Error: " . $stmt->error;
            }
        }
    }
}


// Handle Edit operation
if (isset($_POST['edit'])) {
    $account_id = $_POST['account_id'];
    $account_username = $_POST['account_username'];
    $account_email = $_POST['account_email'];
    $account_password = $_POST['account_password']; // Fixed variable assignment

    // Check for missing fields
    if (empty($account_username) || empty($account_email) || empty($account_password) || empty($account_id)) {
        echo "Error: Missing required fields.";
        exit;
    }

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE user_table SET account_username = ?, account_email = ?, account_password = ? WHERE account_id = ?");
    $stmt->bind_param('ssss', $account_username, $account_email, $account_password, $account_id); // Fixed data types: s (string), d (double), i (integer)

    if ($stmt->execute()) {
        // Update the is_logged field to 'NO' after successful update
        $stmt_logout = $conn->prepare("UPDATE user_table SET is_logged = 'NO' WHERE account_id = ?");
        $stmt_logout->bind_param('s', $account_id);
        $stmt_logout->execute();
        $stmt_logout->close();
		echo "success"; // Return success message to the JS
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    exit; // Ensure script stops after processing the response
}

	
	
	// Handle Edit operation
	if (isset($_FILES['account_image']) && $_FILES['account_image']['error'] === UPLOAD_ERR_OK) {
    $account_id = $_POST['account_id']; // You already have the account code
    $uploadDir = '../uploads/account_image/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Create the upload directory if it doesn't exist
    }

    $fileTmpPath = $_FILES['account_image']['tmp_name'];
    $fileName = uniqid('account_image', true) . '.' . strtolower(pathinfo($_FILES['account_image']['name'], PATHINFO_EXTENSION));
    $destPath = $uploadDir . $fileName;

    // Check for file upload error
    if ($_FILES['account_image']['error'] !== UPLOAD_ERR_OK) {
        echo 'File upload error: ' . $_FILES['account_image']['error'];
        exit;
    }

    // Move the file to the destination path
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $account_image_path = $destPath;
    } else {
        echo "Error uploading the file.";
        exit;
    }

    // Update the image path in the database
    $stmt = $conn->prepare("UPDATE user_table SET account_image=? WHERE account_id=?");
    $stmt->bind_param('ss', $account_image_path, $account_id);

    if ($stmt->execute()) {
        echo "success"; // Return success
		header('Location:Administrator.php?page=users.php');
    } else {
        echo "Error updating record: " . $stmt->error;
    }

    $stmt->close();
    exit;
}

	
	
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the 'delete' action is triggered
    if (isset($_POST['delete']) && isset($_POST['account_ids'])) {
        // Decode the JSON-encoded array from JavaScript
        $account_ids = json_decode($_POST['account_ids']);  // Decode the JSON string into an array
        
        // Ensure that the input is an array
        if (is_array($account_ids) && count($account_ids) > 0) {
            // Escape each category code to prevent SQL injection
            $account_ids = array_map(function($code) use ($conn) {
                return mysqli_real_escape_string($conn, $code);
            }, $account_ids);

            // Convert the array to a comma-separated string for SQL query
            $account_ids_str = implode("','", $account_ids);

            // SQL query to delete categories with the specified code
            $sql = "DELETE FROM user_table WHERE account_id IN ('$account_ids_str')";
            
            if (mysqli_query($conn, $sql)) {
                header('Location: Administrator.php?page=users.php');
            } else {
                echo 'Error deleting categories: ' . mysqli_error($conn);  // Return error message
            }
        } else {
            echo 'Invalid input data';  // Handle invalid input
        }
    }
}


	// Handle Disable/Enable operation
if (isset($_POST['toggle_status'])) {
    $account_id = $_POST['account_id'];
    $current_status = $_POST['account_status'];
    $new_status = ($current_status === 'Enabled') ? 'Disabled' : 'Enabled';

    $stmt = $conn->prepare("UPDATE user_table SET account_status=? WHERE account_id=?");
    $stmt->bind_param('ss', $new_status, $account_id);
    if ($stmt->execute()) {
		header('Location: Administrator.php?page=users.php');
		exit;
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

// Fetch all categories
$result = $conn->query("SELECT account_id, account_username, account_email, account_password, account_status, account_image FROM user_table");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="tableformat-9.css">
</head>
<body>
  <div class="page-header">
    <h1>User Account Settings</h1>
  </div>
  <div class="page-header">
    <div class="add-container">
      <button class="add-more-button">+</button>  
    </div>
    <div class="account-search-container">
    <label for="account-search">Search Account:</label>

<div class="account-search-wrapper">
    <input type="text" id="account-search" placeholder="Type username..." autocomplete="off">
    <button type="button" id="clear-account-search" class="clear-account-search" onclick="clearAccountSearch()">Clear </button>
    <div id="account-suggestions" class="account-suggestions"></div>
</div>
</div>
    <button class="select-account" onclick="deleteSelectedAccounts()">Delete Account</button>
    </div>	
	<div class="subsection" id="categories">
    <div class="table-container">
	<table>
      <thead>
        <tr>
          <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
          <th>Username</th>
          <th>Email</th>
          <th>Password</th>
		  <th>Profile<br/>Picture</th>
		  <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="category-table-body">
<?php while ($row = $result->fetch_assoc()) { 
 // Use the stored relative path
?>

<tr id="row-<?php echo $row['account_id']; ?>">
    <td><input type="checkbox" class="account-checkbox" data-id="<?php echo $row['account_id']; ?>"></td>
    <td id="account_username-<?php echo $row['account_id']; ?>"><?php echo $row['account_username']; ?></td>
    <td id="email-<?php echo $row['account_id']; ?>"><?php echo $row['account_email']; ?></td>
    <td id="password-<?php echo $row['account_id']; ?>"><?php echo $row['account_password']; ?></td>
    <td>
    <?php if ($row['account_image']) { ?>
        <img src="<?php echo htmlspecialchars($row['account_image']) ?>" alt="Account Image" style="width: 50px; height: 50px; object-fit: cover;">
    <?php } else { ?>
        <span>No Image</span>
    <?php } ?>
    </td>
    <td id="account_status-<?php echo $row['account_id']; ?>"><?php echo $row['account_status']; ?></td>
    <td>
        <div class="crud-buttons">
			<button class="view" onclick="viewUser('<?php echo $row['account_username']; ?>', '<?php echo $row['account_email']; ?>', '<?php echo $row['account_password']; ?>', '<?php echo $row['account_status']; ?>', '<?php echo $row['account_image']; ?>',)">View</button>
            <button class="edit" id="edit-<?php echo $row['account_id']; ?>" onclick="lockUserActions(this); editUser('<?php echo $row['account_id']; ?>')">Edit</button>
			<form method="post" class="toggle-form">
            <input type="hidden" name="account_id" value="<?php echo $row['account_id']; ?>">
            <input type="hidden" name="account_status" value="<?php echo $row['account_status']; ?>">
			<button type="submit" name="toggle_status" class="<?php echo $row['account_status'] === 'Enabled' ? 'disable' : 'enable'; ?>">
            <?php echo $row['account_status'] === 'Enabled' ? 'Disable' : 'Enable'; ?>
            </button>
        </form>
		</div>
    </td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>

  
  <!-- Add Account Modal -->
  <div class="modal" id="adding-account-modal" style="display: none;">
    <div class="modal-content-add">
      <div class="modal-header">
        <h2>Add New Account</h2>
        <button class="close-btn" id="close-add-prd-modal">&times;</button>
      </div>
      <form id="account-form" method="POST">
        <div class="form-group">
          <input type="text" id="account-username" name="account_username" placeholder="Account Username" required>
          <label for="account-username">Account Username</label>
        </div>
        <div class="form-group">
          <input type="email" id="account-email" name="account_email" placeholder="Account Email" required>
          <label for="account-email">Account Email</label>
        </div>
        <div class="form-group">
          <input type="password" id="account-password" name="account_password" placeholder="Account Password" required>
          <label for="account-password">Account Password</label>
        </div>
        <button type="submit" class="submit-btn" name="addUser">Add Account</button>  
      </form>
	  <button class="close-button" id="close-add-modal-form">Close</button>
    </div>
  </div>

<!-- View Account Modal -->
<div class="ViewModal" id="view-modal" style="display: none;">
    <div class="modal-content-view">
        <div class="modal-header">
            <h2 style="margin: 0;">User Account Details</h2>
            <button class="close-btn" id="close-viewa-modal">&times;</button>
        </div>
        <div id="account-details">
            <div id="account-image-container">
                <img id="view-user-image" alt="Account Image">
            </div>
            <div id="details-container">
                <div id="account-info" style="margin-left: 0;">
                    <p>
                        <strong>Account Username:</strong>
                        <span id="user-name-detail"></span>
                    </p>
                    <p>
                        <strong>Account Email:</strong>
                        <span id="user-email-detail"></span>
                    </p>
                    <p>
                        <strong>Account Password:</strong>
                        <span id="user-password-detail"></span>
                    </p>
                    <p>
                        <strong>Account Status:</strong>
                        <span id="user-status-detail"></span>
                    </p>
                </div>
            </div>
        </div>
        <button class="close-button" id="close-view-modal-btn">Close</button>
    </div>
</div>

<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected accounts?</p>

        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
    function editImage(account_id) {
  const editButton = document.getElementById('edit_image-' + account_id);
  const uploadIcon = document.getElementById('uploadIcon-' + account_id);

  if (editButton.textContent === 'Edit Image') {
    // Show the upload icon and change button text
    editButton.textContent = ' ';
    uploadIcon.style.display = 'block';
  } else {
    // Save the new image and send it to the server
    const fileInput = document.getElementById('accountImageInput-' + account_id);
    if (fileInput.files.length > 0) {
      const form = new FormData();
      form.append('account_id', account_id);
      form.append('account_image', fileInput.files[0]);

      fetch('users.php', {
        method: 'POST',
        body: form,
      })
      .then(response => response.text())
      .then(data => {
        if (data.trim() === 'success') {
          alert('Image updated successfully!');
        } else {
          alert('Error updating image');
        }
        // Hide the upload icon and reset the button
        uploadIcon.style.display = 'none';
        editButton.textContent = 'Edit Image';
      })
      .catch(error => console.error('Error:', error));
    } else {
      alert('No image selected.');
    }
  }
}
  

// Handle editing the user (toggle between Edit and Save)
function editUser(account_id) {
    const userNameCell = document.getElementById('account_username-' + account_id);
    const userEmailCell = document.getElementById('email-' + account_id);
    const userPassCell = document.getElementById('password-' + account_id);
    const editButton = document.getElementById('edit-' + account_id);

    if (editButton.textContent.trim() === 'Edit') {

        // Lock all other action buttons
        const actionButtons = document.querySelectorAll(
            '.view, .edit, .toggle-form button'
        );

        actionButtons.forEach(button => {
            if (button !== editButton) {
                button.disabled = true;
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.5';
            }
        });

        // Turn the row into editable state
        userNameCell.innerHTML =
            `<input type="text" value="${userNameCell.textContent.trim()}">`;

        userEmailCell.innerHTML =
            `<input type="text" value="${userEmailCell.textContent.trim()}">`;

        userPassCell.innerHTML =
            `<input type="text" value="${userPassCell.textContent.trim()}">`;

        // Change Edit → Save
        editButton.textContent = 'Save';

    } else {

        // Save the new values
        const newAccUserName =
            userNameCell.querySelector('input').value.trim();

        const newAccEmail =
            userEmailCell.querySelector('input').value.trim();

        const newAccPass =
            userPassCell.querySelector('input').value.trim();

        const form = new FormData();

        form.append('account_id', account_id);
        form.append('account_username', newAccUserName);
        form.append('account_email', newAccEmail);
        form.append('account_password', newAccPass);
        form.append('edit', true);

        fetch('users.php', {
            method: 'POST',
            body: form
        })
        .then(response => response.text())
        .then(data => {

            if (data.trim() === 'success') {

                // Update the table with the new values
                userNameCell.textContent = newAccUserName;
                userEmailCell.textContent = newAccEmail;
                userPassCell.textContent = newAccPass;

                // Change Save → Edit
                editButton.textContent = 'Edit';

                // Unlock all other action buttons
                const actionButtons = document.querySelectorAll(
                    '.view, .edit, .toggle-form button'
                );

                actionButtons.forEach(button => {
                    button.disabled = false;
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';
                });
                alert('Account updated successfully!');

            } else {

                alert('Error updating user. Please try again.');

                // Unlock buttons if saving fails
                const actionButtons = document.querySelectorAll(
                    '.view, .edit, .toggle-form button'
                );

                actionButtons.forEach(button => {
                    button.disabled = false;
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';
                });

                window.location.href = 'Administrator.php?page=users.php';
            }
        })
        .catch(error => {

            console.error('Error:', error);

            // Unlock buttons if there is a fetch error
            const actionButtons = document.querySelectorAll(
                '.view, .edit, .toggle-form button'
            );

            actionButtons.forEach(button => {
                button.disabled = false;
                button.style.pointerEvents = 'auto';
                button.style.opacity = '1';
            });
        });
    }
}



// Delete selected accounts
function deleteSelectedAccounts() {

    const selectedCheckboxes = document.querySelectorAll('.account-checkbox:checked');
    const idsToDelete = [];

    selectedCheckboxes.forEach(checkbox => {
        idsToDelete.push(checkbox.dataset.id);
    });

    if (idsToDelete.length === 0) {
        alert('No account is selected!');
        return;
    }

    // Show confirmation modal
    const modal = document.getElementById('delete-confirm-modal');
    modal.style.display = 'flex';

    // Cancel button
    document.getElementById('cancel-delete').onclick = function () {
        modal.style.display = 'none';
    };

    // Confirm delete button
    document.getElementById('confirm-delete').onclick = function () {

        // Hide modal
        modal.style.display = 'none';

        // Send selected account IDs to PHP
        const formData = new FormData();

        formData.append('delete', true);
        formData.append('account_ids', JSON.stringify(idsToDelete));

        fetch('users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {

            if (data.trim() === 'success') {

                // Remove deleted rows from table
                selectedCheckboxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');

                    if (row) {
                        row.remove();
                    }
                });

            } else {

                alert('Account deleted Successfully');
                window.location.href = 'Administrator.php?page=users.php';

            }

        })
        .catch(error => {
            console.error('Error:', error);
        });
    };
}


// ===============================
// ADD ACCOUNT MODAL
// ===============================

document.addEventListener("DOMContentLoaded", function () {
    const addButton = document.querySelector(".add-more-button");
    const addModal = document.getElementById("adding-account-modal");
    const overlay = document.getElementById("modal-overlay");

    const closeAddX = document.getElementById("close-add-prd-modal");
    const closeAddButton = document.getElementById("close-add-modal-form");

    // Open Add Account Modal
    addButton.addEventListener("click", function () {
        addModal.style.display = "flex";   // ✅ use flex
        overlay.style.display = "block";
    });

    // Close Add Account Modal - X button
    closeAddX.addEventListener("click", function () {
        addModal.style.display = "none";
        overlay.style.display = "none";
    });

    // Close Add Account Modal - Close button
    closeAddButton.addEventListener("click", function () {
        addModal.style.display = "none";
        overlay.style.display = "none";
    });
});




// ===============================
// VIEW ACCOUNT MODAL
// ===============================

function viewUser(userName, userEmail, userPass, userStatus, userImage) {

    document.getElementById("user-name-detail").textContent = userName;
    document.getElementById("user-email-detail").textContent = userEmail;
    document.getElementById("user-password-detail").textContent = userPass;
    document.getElementById("user-status-detail").textContent = userStatus;

    const imageElement = document.getElementById("view-user-image");

    if (userImage && userImage.trim() !== "") {
        imageElement.src = userImage;
        imageElement.style.display = "block";
    } else {
        imageElement.style.display = "none";
    }

    document.getElementById("view-modal").style.display = "flex";
    document.getElementById("modal-overlay").style.display = "block";
}



// View Account Modal - X button
document.getElementById("close-viewa-modal").addEventListener("click", function () {

    document.getElementById("view-modal").style.display = "none";
    document.getElementById("modal-overlay").style.display = "none";

});


// View Account Modal - Close button
document.getElementById("close-view-modal-btn").addEventListener("click", function () {

    document.getElementById("view-modal").style.display = "none";
    document.getElementById("modal-overlay").style.display = "none";

});



// ===============================
// SELECT ALL CHECKBOXES
// ===============================

function toggleSelectAll() {

    const selectAllCheckbox = document.getElementById("select-all");
    const checkboxes = document.querySelectorAll(".account-checkbox");

    checkboxes.forEach(function (checkbox) {
        checkbox.checked = selectAllCheckbox.checked;
    });

}


function lockUserActions(clickedButton) {
    const actionButtons = document.querySelectorAll(
        '.view, .edit, .toggle-form button'
    );

    actionButtons.forEach(button => {
        if (button !== clickedButton) {
            button.disabled = true;
            button.style.pointerEvents = 'none';
            button.style.opacity = '0.5';
        }
    });

    // Keep the button that was clicked usable
    clickedButton.disabled = false;
    clickedButton.style.pointerEvents = 'auto';
}

function unlockUserActions() {
    const actionButtons = document.querySelectorAll(
        '.view, .edit, .toggle-form button'
    );

    actionButtons.forEach(button => {
        button.disabled = false;
        button.style.pointerEvents = 'auto';
        button.style.opacity = '1';
    });
}

// ===============================
// ACCOUNT SEARCH + SUGGESTIONS
// ===============================

const accountSearchInput =
    document.getElementById("account-search");

const accountSuggestions =
    document.getElementById("account-suggestions");


accountSearchInput.addEventListener("input", function () {

    const searchValue =
        this.value.trim().toLowerCase();

    const rows =
        document.querySelectorAll("#category-table-body tr");

    // Clear previous suggestions
    accountSuggestions.innerHTML = "";

    // If search is empty
    if (searchValue === "") {

        rows.forEach(function (row) {
            row.style.display = "";
        });

        accountSuggestions.style.display = "none";

        return;
    }


    const matchingAccounts = [];


    rows.forEach(function (row) {

        const usernameCell =
            row.querySelector('[id^="account_username-"]');

        if (!usernameCell) {
            return;
        }

        const username =
            usernameCell.textContent.trim();

        const usernameLower =
            username.toLowerCase();


        // Filter table
        if (usernameLower.includes(searchValue)) {

            row.style.display = "";

            matchingAccounts.push(username);

        } else {

            row.style.display = "none";

        }

    });


// CREATE SUGGESTIONS
    if (matchingAccounts.length > 0) {

        matchingAccounts.forEach(function (username) {

            const suggestion =
                document.createElement("div");

            suggestion.className =
                "account-suggestion";

            suggestion.textContent =
                username;


            suggestion.addEventListener("click", function () {

                accountSearchInput.value =
                    username;

                accountSuggestions.innerHTML = "";

                accountSuggestions.style.display =
                    "none";


                // Show only selected account
                rows.forEach(function (row) {

                    const usernameCell =
                        row.querySelector(
                            '[id^="account_username-"]'
                        );

                    if (!usernameCell) {
                        return;
                    }

                    const rowUsername =
                        usernameCell.textContent.trim();


                    if (rowUsername === username) {

                        row.style.display = "";

                    } else {

                        row.style.display = "none";

                    }

                });

            });


            accountSuggestions.appendChild(
                suggestion
            );

        });


        accountSuggestions.style.display =
            "block";

    } else {

        accountSuggestions.style.display =
            "none";

    }

});


// CLEAR SEARCH
function clearAccountSearch() {

    const searchInput =
        document.getElementById("account-search");

    searchInput.value = "";

    accountSuggestions.innerHTML = "";

    accountSuggestions.style.display =
        "none";


    const rows =
        document.querySelectorAll("#category-table-body tr");

    rows.forEach(function (row) {

        row.style.display = "";

    });

    searchInput.focus();
}


// CLOSE SUGGESTIONS WHEN CLICKING
// OUTSIDE THE SEARCH AREA
document.addEventListener("click", function (event) {

    const searchWrapper =
        document.querySelector(
            ".account-search-wrapper"
        );

    if (!searchWrapper.contains(event.target)) {

        accountSuggestions.style.display =
            "none";

    }

});
  </script>
  <div id="modal-overlay"></div>
</body>
</html>
