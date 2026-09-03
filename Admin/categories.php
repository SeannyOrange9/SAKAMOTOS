<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Add operation
if (isset($_POST['add'])) {
    $category_code = $_POST['category_code'];
    $category_name = $_POST['category_name'];

    if (empty($category_code) || empty($category_name)) {
        echo "Error: Missing required fields.";
        exit;
    }
	
	// Check if category_code or category_name already exists (case-insensitive)
    $check_query = $conn->prepare("SELECT * FROM category_table WHERE LOWER(category_code) = LOWER(?) OR LOWER(category_name) = LOWER(?)");
    $check_query->bind_param('ss', $category_code, $category_name);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
            alert('Error: Category code or category name already exists.');
            window.location.href = 'Administrator.php?page=categories.php';
        </script>";
        exit;
    }
	
	
    $stmt = $conn->prepare("INSERT INTO category_table (category_code, category_name) VALUES (?, ?)");
    $stmt->bind_param('ss', $category_code, $category_name);

    if ($stmt->execute()) {
		$stmt = $conn->prepare("UPDATE category_table SET category_status='Available' WHERE category_code=?");
		$stmt->bind_param('s', $category_code);
		header('Location: Administrator.php?page=categories.php');
        echo "success";  // Return success message to the JS
        exit;
    } else {
        echo "Error inserting record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

// Handle Remove operation
if (isset($_POST['remove'])) {
    $category_code = $_POST['category_code'];
    $stmt = $conn->prepare("DELETE FROM category_table WHERE category_code=?");
    $stmt->bind_param('s', $category_code);
    if ($stmt->execute()) {
		header('Location: Administrator.php?page=categories.php');
        echo "success";  // Return success message to JS
        exit;
    } else {
        echo "Error deleting record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

// Handle Edit operation
if (isset($_POST['edit'])) {
    $category_code = $_POST['category_code'];
    $category_name = $_POST['category_name'];

    if (empty($category_code) || empty($category_name)) {
        echo "Error: Missing required fields.";
        exit;
    }

    $stmt = $conn->prepare("UPDATE category_table SET category_name = ? WHERE category_code = ?");
    $stmt->bind_param('ss', $category_name, $category_code);

    if ($stmt->execute()) {
        echo "success";  // Return success message to the JS
        exit;
    } else {
        echo "Error updating record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

if (isset($_POST['toggle_status'])) {
    $category_code = $_POST['category_code'];
    $category_status = $_POST['category_status'];

    $new_status = ($category_status === 'Available') ? 'Not Available' : 'Available';

    $stmt = $conn->prepare("UPDATE category_table SET category_status = ? WHERE category_code = ?");
    $stmt->bind_param('ss', $new_status, $category_code);

    if ($stmt->execute()) {
        header('Location: Administrator.php?page=categories.php');
        exit;
    } else {
        echo "Error toggling status: " . $stmt->error;
        exit;
    }
    $stmt->close();
}


// Fetch all categories
$result = $conn->query("SELECT category_code, category_name, category_status FROM category_table");
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="tableformat-9.css">
</head>
<body>
<div class="top-header">
    <!-- Back button on the left -->
    <a href="Administrator.php?page=inventory.php" class="back-button">&larr; Back</a>
    <!-- Title in the center -->
    <h1>Category Settings</h1>
    <!-- Empty space on the right to keep title centered -->
    <div class="header-spacer"></div>
</div>

<div class="add-container"><button class="add-button">+</button></div>
 
<div class="subsection" id="categories">
    <div class="table-container">
	<table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Name</th>
		  <th>Status</th>
		  <th>Actions</th>
        </tr>
      </thead>
      <tbody id="category-table-body">
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr id="row-<?php echo $row['category_code']; ?>">
          <td id="category_code-<?php echo $row['category_code']; ?>"><?php echo $row['category_code']; ?></td>
          <td id="category_name-<?php echo $row['category_code']; ?>"><?php echo $row['category_name']; ?></td>
		  <td id="category_status-<?php echo $row['category_code']; ?>"><?php echo $row['category_status']; ?></td>
	      <td>
		  <div class="crud-buttons">
			  <button class="view" onclick="viewCategory('<?php echo $row['category_code']; ?>', '<?php echo $row['category_name']; ?>', '<?php echo $row['category_status']; ?>')">View</button>  
              <button class="edit" id="edit-<?php echo $row['category_code']; ?>" onclick="editCategory('<?php echo $row['category_code']; ?>')">Edit</button>
			  <form method="post" class="remove-form" style="display:inline;">
            <input type="hidden" name="category_code" value="<?php echo $row['category_code']; ?>">
            <button type="button"
        class="remove"
        onclick="confirmRemove('<?php echo $row['category_code']; ?>')">
    Remove
</button>
      </form>
			  <form method="post" style="display:inline;">
				<input type="hidden" name="category_code" value="<?php echo $row['category_code']; ?>">
				<input type="hidden" name="category_status" value="<?php echo $row['category_status']; ?>">
				<button type="submit" name="toggle_status" class="<?php echo strtolower($row['category_status']) === 'available' ? 'disable' : 'enable'; ?>">
			  <?php echo strtolower($row['category_status']) === 'available' ? 'Disable' : 'Enable'; ?>
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

	
  <div class="modal" id="view-category-modal" style="display: none;">
  <div class="modal-content" style="position: relative; margin: auto; top: 50%; transform: translateY(-50%); width: 80%; max-width: 500px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <div class="modal-header">
      <h2 style="margin: 0;">Category Details</h2>
      <button class="close-btn" id="close-category-modal">&times;</button>
    </div>
    <div id="product-info" style="text-align: center;">
      <p style="margin: 0;"><strong>Category Code:</strong> <span id="category-code-detail"></span></p>
      <p style="margin: 0;"><strong>Category Name:</strong> <span id="category-name-detail"></span></p>
    </div>
    <button class="close-button" id="close-view-modal-btn">Close</button>
  </div>
</div>
	
	
  
<!-- Add Category Modal -->
<div class="modal" id="categoriesModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Category</h2>
      <button class="close-btn" id="close-add-modal">&times;</button>
    </div>
    <form id="product-form" method="POST">
      <div class="form-group">
        <input type="text" id="category_code" name="category_code" placeholder="Category Code" required>
        <label for="product-code">Category Code</label>
      </div>
      <div class="form-group">
        <input type="text" id="category_name" name="category_name" placeholder="Category Name" required>
        <label for="product-name">Category Name</label>
      </div>
      <button type="submit" name="add" class="submit-btn">Add Category</button>
    </form>
    <button type="button" class="close-button" id="close-add-modal-btn">Close</button>
  </div>
</div>


<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected Category?</p>
        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<script>

// ADD CATEGORY MODAL
// Function to close the Add Category modal
function closeAddProductModal() {
    var modal = document.getElementById("categoriesModal");
    modal.style.display = "none";
}


// Open Add Category modal
document.querySelector(".add-button").onclick = function() {
    var modal = document.getElementById("categoriesModal");
    modal.style.display = "flex";
};


// Close Add Category modal - X button
document.getElementById("close-add-modal").onclick = closeAddProductModal;


// Close Add Category modal - Close button
document.getElementById("close-add-modal-btn").onclick = closeAddProductModal;


// Close Add Category modal when clicking outside
window.onclick = function(event) {

    var modal = document.getElementById("categoriesModal");

    if (event.target == modal) {
        closeAddProductModal();
    }

};


// VIEW CATEGORY
function viewCategory(categoryCode, categoryName, categoryStatus) {

    // Populate category details
    document.getElementById("category-code-detail").textContent = categoryCode;
    document.getElementById("category-name-detail").textContent = categoryName;

    // Show View Category modal
    document.getElementById("view-category-modal").style.display = "block";
}


// Close View Category modal
document.getElementById("close-view-modal-btn").addEventListener("click", function() {

    document.getElementById("view-category-modal").style.display = "none";

});

// EDIT CATEGORY

function editCategory(categoryCode) {

    const editButton = document.getElementById(`edit-${categoryCode}`);
    const categoryNameTd = document.getElementById(`category_name-${categoryCode}`);

    if (editButton.innerText === "Edit") {

        // ENTER EDIT MODE

        // Lock all other action buttons
        const actionButtons = document.querySelectorAll(
            '.view, .edit, .remove, .toggle-form button'
        );

        actionButtons.forEach(button => {

            // Keep the current Edit button usable
            if (button !== editButton) {
                button.disabled = true;
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.5';
            }

        });


        // Switch to Save mode
        editButton.innerText = "Save";

        categoryNameTd.contentEditable = "true";

        categoryNameTd.style.backgroundColor = "#f9f9f9";

        categoryNameTd.focus();

    } 
    
    else {

        // SAVE CATEGORY

        // Get updated category name
        const updatedCategoryName = categoryNameTd.innerText.trim();


        // Prepare data
        const formData = new FormData();

        formData.append("edit", true);
        formData.append("category_code", categoryCode);
        formData.append("category_name", updatedCategoryName);


        // Send update to PHP
        fetch("categories.php", {
            method: "POST",
            body: formData
        })

        .then(response => response.text())

        .then(result => {

            if (result.trim() === "success") {

                // ==========================================
                // SUCCESS
                // ==========================================

                

                // Unlock all action buttons
                const actionButtons = document.querySelectorAll(
                    '.view, .edit, .remove, .toggle-form button'
                );

                actionButtons.forEach(button => {
                    button.disabled = false;
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';
                });


                // Switch Save → Edit
                editButton.innerText = "Edit";

                categoryNameTd.contentEditable = "false";

                categoryNameTd.style.backgroundColor = "";


                // Reload page
                window.location.href = "Administrator.php?page=categories.php";
                alert("Category updated successfully.");

            } 
            
            else {

                // SAVE FAILED

                alert("Error updating category: " + result);

                // Unlock all action buttons
                const actionButtons = document.querySelectorAll(
                    '.view, .edit, .remove, .toggle-form button'
                );

                actionButtons.forEach(button => {
                    button.disabled = false;
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';
                });


                // Keep editing available
                editButton.innerText = "Save";

                categoryNameTd.contentEditable = "true";

            }

        })

        .catch(error => {

            // FETCH ERROR

            console.error("Error:", error);

            alert("An error occurred while updating the category.");

            // Unlock all action buttons
            const actionButtons = document.querySelectorAll(
                '.view, .edit, .remove, .toggle-form button'
            );

            actionButtons.forEach(button => {
                button.disabled = false;
                button.style.pointerEvents = 'auto';
                button.style.opacity = '1';
            });


            // Keep editing available
            editButton.innerText = "Save";

            categoryNameTd.contentEditable = "true";

        });

    }

}



// REMOVE CATEGORY CONFIRMATION

let categoryToDelete = null;


// Function called when Remove button is clicked
function confirmRemove(categoryCode) {

    // Store the category code
    categoryToDelete = categoryCode;


    // Get confirmation modal
    const modal = document.getElementById("delete-confirm-modal");


    // Show confirmation modal
    modal.style.display = "flex";

}



// CANCEL DELETE

document.getElementById("cancel-delete").onclick = function() {

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";


    // Clear selected category
    categoryToDelete = null;

};



// CONFIRM DELETE

document.getElementById("confirm-delete").onclick = function() {

    // Make sure a category was selected
    if (!categoryToDelete) {
        return;
    }


    // Prepare data to send to PHP
    const formData = new FormData();

    formData.append("remove", true);
    formData.append("category_code", categoryToDelete);


    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";


    // Send delete request to PHP
    fetch("", {
        method: "POST",
        body: formData
    })

    .then(response => response.text())

    .then(result => {

        // Reload the categories page
        window.location.href = "Administrator.php?page=categories.php";

    })

    .catch(error => {

        console.error("Error deleting category:", error);

    });


    // Clear selected category
    categoryToDelete = null;

};

// Close View Category modal - X button
document.getElementById("close-category-modal").addEventListener("click", function() {

    document.getElementById("view-category-modal").style.display = "none";

});
</script>
</body>
</html>

