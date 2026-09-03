<?php
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to update flavor status based on quantity
function updateFlavorStatus($conn, $flavor_key, $flavor_qty) {
    // Set status based on quantity
    $flavor_status = ($flavor_qty == 0) ? 'Not Available' : 'Available';

    // Update the flavor status in the database
    $stmt = $conn->prepare("UPDATE flavor_table SET flavor_status = ? WHERE flavor_key = ?");
    $stmt->bind_param('ss', $flavor_status, $flavor_key);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['remove'])) {

        if (!isset($_POST['flavor_key']) || trim($_POST['flavor_key']) === '') {
            echo "error: flavor_key is missing";
            exit;
        }

        $flavor_key = trim($_POST['flavor_key']);

        $stmt = $conn->prepare(
            "DELETE FROM flavor_table WHERE flavor_key = ?"
        );

        if (!$stmt) {
            echo "error: " . $conn->error;
            exit;
        }

        $stmt->bind_param("s", $flavor_key);

        if (!$stmt->execute()) {
            echo "error: " . $stmt->error;
            $stmt->close();
            exit;
        }

        if ($stmt->affected_rows > 0) {
            echo "success";
        } else {
            echo "error: flavor not found";
        }

        $stmt->close();
        exit;
    }
}


if (isset($_POST['toggle_status'])) {
    $flavor_key = $_POST['flavor_key'];
    $flavor_status = $_POST['flavor_status'];

    $new_status = ($flavor_status === 'Available') ? 'Not Available' : 'Available';

    $stmt = $conn->prepare("UPDATE flavor_table SET flavor_status = ? WHERE flavor_key = ?");
    $stmt->bind_param('ss', $new_status, $flavor_key);

    if ($stmt->execute()) {
        header('Location: Administrator.php?page=flavors.php');
        exit;
    } else {
        echo "Error toggling status: " . $stmt->error;
        exit;
    }
    $stmt->close();
}


if (isset($_POST['addFlavor'])) {
    $flavor_key = $_POST['flavor_key'];
    $flavor_name = $_POST['flavor_name'];
    $flavor_qty = $_POST['flavor_qty'];
    $flavor_price = $_POST['flavor_price'];
    
    // Check if required fields are empty or invalid
    if (empty($flavor_key) || empty($flavor_name) || !isset($flavor_qty) || !is_numeric($flavor_qty) || !isset($flavor_price) || empty($flavor_price)) {
        echo "Error: Missing required fields.";
        exit;
    }
	
	// Check if flavor_key or flavor_name already exists (case-insensitive)
    $check_query = $conn->prepare("SELECT * FROM flavor_table WHERE LOWER(flavor_key) = LOWER(?) OR LOWER(flavor_name) = LOWER(?)");
    $check_query->bind_param('ss', $flavor_key, $flavor_name);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
            alert('Error: Flavor key or flavor name already exists.');
            window.location.href = 'Administrator.php?page=flavors.php';
        </script>";
        exit;
    }
	
    // Determine the status based on the flavor_qty
    if ($flavor_qty == 0) {
        $flavor_status = 'Not Available';
    } else {
        $flavor_status = 'Available';
    }

    $stmt = $conn->prepare("INSERT INTO flavor_table (flavor_key, flavor_name, flavor_qty, flavor_price, flavor_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiss', $flavor_key, $flavor_name, $flavor_qty, $flavor_price, $flavor_status);

    if ($stmt->execute()) {
        echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=flavors.php">';
        exit;
    } else {
        echo "Error inserting record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

if (isset($_POST['edit'])) {

    $flavor_key = $_POST['flavor_key'];
    $flavor_name = $_POST['flavor_name'];
    $flavor_qty = $_POST['flavor_qty'];
    $flavor_price = $_POST['flavor_price'];

    if (!isset($_POST['flavor_key'], $_POST['flavor_name'], $_POST['flavor_qty'], $_POST['flavor_price'])) {
        echo "Missing required fields!";
        exit;
    }

    // Prepare the UPDATE query
    $stmt = $conn->prepare("
        UPDATE flavor_table
        SET flavor_name = ?, flavor_qty = ?, flavor_price = ?
        WHERE flavor_key = ?
    ");

    $stmt->bind_param(
        'sids',
        $flavor_name,
        $flavor_qty,
        $flavor_price,
        $flavor_key
    );

    if ($stmt->execute()) {

        // Update status based on quantity
        if ($flavor_qty == 0) {

            $stmt_status = $conn->prepare("
                UPDATE flavor_table
                SET flavor_status = 'Not Available'
                WHERE flavor_key = ?
            ");

        } else {

            $stmt_status = $conn->prepare("
                UPDATE flavor_table
                SET flavor_status = 'Available'
                WHERE flavor_key = ?
            ");

        }

        $stmt_status->bind_param('s', $flavor_key);
        $stmt_status->execute();
        $stmt_status->close();

        // IMPORTANT:
        // Return ONLY "success" to JavaScript
        echo "success";
        exit;

    } else {

        echo "Error updating record: " . $stmt->error;
        exit;

    }
}


// Fetch all categories
$result = $conn->query("SELECT flavor_key, flavor_name, flavor_price, flavor_qty, flavor_status FROM flavor_table");
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
    <a href="Administrator.php?page=inventory.php" class="back-button"> &larr; Back</a>
    <!-- Title in the center -->
    <h1>Flavor Settings</h1>
    <!-- Empty space on the right to keep title centered -->
    <div class="header-spacer"></div>
</div>
<div class="add-container"><button class="add-button">+</button></div>
<!-- Breakfast Section -->
<div class="subsection" id="categories">
<div class="table-container">
	<table>
      <thead>
        <tr>
          <th>Code</th>
          <th>Type</th>
		  <th>Quantity</th>
		  <th>Price</th>
		  <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="flavor-table-body">
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr id="row-<?php echo $row['flavor_key']; ?>">
          <td id="flavor_key-<?php echo $row['flavor_key']; ?>"><?php echo $row['flavor_key']; ?></td>
          <td id="flavor_name-<?php echo $row['flavor_key']; ?>"><?php echo $row['flavor_name']; ?></td>
		  <td id="flavor_qty-<?php echo $row['flavor_key']; ?>"><?php echo $row['flavor_qty']; ?></td>
		  <td id="flavor_price-<?php echo $row['flavor_key']; ?>"><?php echo $row['flavor_price']; ?></td>
		  <td id="flavor_status-<?php echo $row['flavor_key']; ?>"><?php echo $row['flavor_status']; ?></td>
          <td>
            <div class="crud-buttons">
			  <button class="view" onclick="viewFlavor('<?php echo $row['flavor_key']; ?>', '<?php echo $row['flavor_name']; ?>', '<?php echo $row['flavor_qty']; ?>', '<?php echo $row['flavor_price']; ?>', '<?php echo $row['flavor_status']; ?>')">View</button>  
              <button class="edit" id="edit-<?php echo $row['flavor_key']; ?>" onclick="editFlavor('<?php echo $row['flavor_key']; ?>')">Edit</button>
			
<form method="post" class="remove-form" style="display:inline;">
    <input type="hidden"name="flavor_key"value="<?php echo $row['flavor_key']; ?>">
    <button type="button" class="remove" onclick="confirmRemove('<?php echo $row['flavor_key']; ?>')">Remove</button></form>			
            <form method="POST" class="toggle-form" style="display:inline;" action="flavors.php">
			<input type="hidden" name="flavor_key" value="<?php echo $row['flavor_key']; ?>">
			<input type="hidden" name="flavor_status" value="<?php echo $row['flavor_status']; ?>">
			<!-- Disable or enable button logic -->
			<button type="submit" name="toggle_status" class="<?php echo strtolower($row['flavor_status']) === 'available' ? 'disable' : 'enable'; ?>"><?php echo strtolower($row['flavor_status']) === 'available' ? 'Disable' : 'Enable'; ?></button>
		</form>    
            </div>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
    </table>
	</div>
</div>

  <div class="modal" id="view-flavor-modal" style="display: none;">
  <div class="modal-content" style="position: relative; margin: auto; top: 50%; transform: translateY(-50%); width: 80%; max-width: 500px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <div class="modal-header">
      <h2 style="margin: 0;">Flavor Details</h2>
      <button class="close-btn" id="close-flavor-modal">&times;</button>
    </div>
    <div id="product-info" style="text-align: center;">
      <p style="margin: 0;"><strong>Flavor Code:</strong> <span id="flavor-code-detail"></span></p>
      <p style="margin: 0;"><strong>Flavor Name:</strong> <span id="flavor-name-detail"></span></p>
	  <p style="margin: 0;"><strong>Quantity:</strong> <span id="flavor-qty-detail"></span></p>
      <p style="margin: 0;"><strong>Flavor Price:</strong> <span id="flavor-price-detail"></span></p>
	  <p style="margin: 0;"><strong>Flavor Status:</strong> <span id="flavor-status-detail"></span></p> 
    </div>
    <button class="close-button" id="close-viewf-modal-btn">Close</button>
  </div>
</div>
	
	
  
<!-- Add Flavor Modal -->
<div class="modal" id="flavorsModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Flavor</h2>
      <button class="close-btn" id="close-add-flavor-modal">&times;</button>
    </div>
    <form id="product-form" method="POST">
      <div class="form-group">
        <input type="text" id="flavor_key" name="flavor_key" placeholder="Flavor Code" required>
        <label for="flavor_key">Flavor Code</label>
      </div>
      <div class="form-group">
        <input type="text" id="flavor_name" name="flavor_name" placeholder="Flavor Name" required>
        <label for="flavor_name">Flavor Name</label>
      </div>
	  <div class="form-group">
        <input type="number" id="flavor_qty" name="flavor_qty" placeholder="Flavor Quantity" required>
        <label for="flavor_qty">Quantity</label>
      </div>
	  <div class="form-group">
        <input type="number" id="flavor_price" name="flavor_price" placeholder="Flavor Price" required>
        <label for="flavor_price">Flavor Price</label>
      </div>
      <button type="submit" name="addFlavor" class="submit-btn">Add Flavor</button>
    </form>
    <button type="button" class="close-button" id="close-add-flavor-modal-btn">Close</button>
  </div>
</div>


<!--Confirm Button-->
<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected Flavors?</p>
        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>


<script>

// ADD FLAVOR MODAL
document.addEventListener("DOMContentLoaded", function () {

    const addButton = document.querySelector(".add-button");
    const modal = document.getElementById("flavorsModal");

    const closeAddModal = document.getElementById("close-add-flavor-modal");
    const closeAddModalBtn = document.getElementById("close-add-flavor-modal-btn");


    // Open Add Flavor modal
    if (addButton) {
        addButton.addEventListener("click", function () {
            modal.style.display = "flex";
        });
    }


    // Close Add Flavor modal - X
    if (closeAddModal) {
        closeAddModal.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }


    // Close Add Flavor modal - Close button
    if (closeAddModalBtn) {
        closeAddModalBtn.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }


    // Close when clicking outside Add Flavor modal
    window.addEventListener("click", function (event) {

        if (event.target === modal) {
            modal.style.display = "none";
        }

    });

});



// VIEW FLAVOR
function viewFlavor(
    flavorKey,
    flavorName,
    flavorQty,
    flavorPrice,
    flavorStatus
) {

    // Populate modal
    document.getElementById("flavor-code-detail").textContent = flavorKey;
    document.getElementById("flavor-name-detail").textContent = flavorName;
    document.getElementById("flavor-qty-detail").textContent = flavorQty;
    document.getElementById("flavor-price-detail").textContent = flavorPrice;
    document.getElementById("flavor-status-detail").textContent = flavorStatus;


    // Show View Flavor modal
    document.getElementById("view-flavor-modal").style.display = "block";
}



// CLOSE VIEW FLAVOR MODAL - X
document.getElementById("close-flavor-modal").addEventListener("click", function () {

    document.getElementById("view-flavor-modal").style.display = "none";

});



// CLOSE VIEW FLAVOR MODAL - CLOSE BUTTON
document.getElementById("close-viewf-modal-btn").addEventListener("click", function () {

    document.getElementById("view-flavor-modal").style.display = "none";

});



// EDIT FLAVOR
function editFlavor(flavorKey) {

    const editButton =
        document.getElementById(`edit-${flavorKey}`);

    const flavorNameTd =
        document.getElementById(`flavor_name-${flavorKey}`);

    const flavorQuantityTd =
        document.getElementById(`flavor_qty-${flavorKey}`);

    const flavorPriceTd =
        document.getElementById(`flavor_price-${flavorKey}`);

    // EDIT MODE
    if (editButton.innerText.trim() === "Edit") {

        
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

        flavorNameTd.contentEditable = "true";
        flavorQuantityTd.contentEditable = "true";
        flavorPriceTd.contentEditable = "true";


        // Highlight editable fields
        flavorNameTd.style.backgroundColor = "#f9f9f9";
        flavorQuantityTd.style.backgroundColor = "#f9f9f9";
        flavorPriceTd.style.backgroundColor = "#f9f9f9";

        flavorNameTd.focus();

    }


    
    // SAVE MODE
    else {

        // Get updated values
        const updatedFlavorName =
            flavorNameTd.innerText.trim();

        const updatedFlavorQuantity =
            flavorQuantityTd.innerText.trim();

        const updatedFlavorPrice =
            flavorPriceTd.innerText.trim();


        // Prepare form data
        const formData = new FormData();

        formData.append("edit", true);
        formData.append("flavor_key", flavorKey);
        formData.append("flavor_name", updatedFlavorName);
        formData.append("flavor_qty", updatedFlavorQuantity);
        formData.append("flavor_price", updatedFlavorPrice);


        // SEND UPDATE TO PHP
        fetch("flavors.php", {
            method: "POST",
            body: formData
        })

        .then(response => response.text())

        .then(result => {

            if (result.trim() === "success") {

                
                // SUCCESS
                

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

                flavorNameTd.contentEditable = "false";
                flavorQuantityTd.contentEditable = "false";
                flavorPriceTd.contentEditable = "false";


                // Remove highlighting
                flavorNameTd.style.backgroundColor = "";
                flavorQuantityTd.style.backgroundColor = "";
                flavorPriceTd.style.backgroundColor = "";


                // Reload page
                window.location.href =
                    "Administrator.php?page=flavors.php";

                alert("Flavor updated successfully.");

            }

            else {

                
                // SAVE FAILED
                alert("Error updating flavor: " + result);


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

                flavorNameTd.contentEditable = "true";
                flavorQuantityTd.contentEditable = "true";
                flavorPriceTd.contentEditable = "true";

            }

        })

        .catch(error => {

            
            // FETCH ERROR
            

            console.error("Error:", error);

            alert("An error occurred while updating the flavor.");


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

            flavorNameTd.contentEditable = "true";
            flavorQuantityTd.contentEditable = "true";
            flavorPriceTd.contentEditable = "true";

        });

    }

}


// REMOVE FLAVOR CONFIRMATION
let flavorToDelete = null;



// FUNCTION CALLED BY REMOVE BUTTON
function confirmRemove(flavorCode) {

    // Store the selected flavor code
    flavorToDelete = flavorCode;

    // Get confirmation modal
    const modal = document.getElementById("delete-confirm-modal");

    // Show confirmation modal
    modal.style.display = "flex";
}



// CANCEL DELETE
document.getElementById("cancel-delete").onclick = function () {

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";

    // Clear selected flavor
    flavorToDelete = null;
};



// CONFIRM DELETE
document.getElementById("confirm-delete").onclick = function () {

    // Make sure a flavor was selected
    if (!flavorToDelete) {
        return;
    }

    // Prepare data to send to PHP
    const formData = new FormData();

    formData.append("remove", "1");
    formData.append("flavor_key", flavorToDelete);

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";

    // Send delete request to PHP
    fetch("flavors.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {

        if (result.trim() === "success") {

            alert("Flavor deleted successfully");

            window.location.href =
                "Administrator.php?page=flavors.php";

        } else {

            alert("Error deleting flavor: " + result);

        }

    })
    .catch(error => {

        console.error("Error deleting flavor:", error);
        alert("An error occurred while deleting the flavor.");

    });

    flavorToDelete = null;
};

</script>
</body>
</html>

