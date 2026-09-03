<?php
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to update add-on status based on quantity
function updateAddOnStatus($conn, $add_on_key, $add_on_qty) {
    // Set status based on quantity
    $add_on_status = ($add_on_qty == 0) ? 'Not Available' : 'Available';

    // Update the add-on status in the database
    $stmt = $conn->prepare("UPDATE add_on_table SET add_on_status = ? WHERE add_on_key = ?");
    $stmt->bind_param('ss', $add_on_status, $add_on_key);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (isset($_POST['remove'])) {

        if (!isset($_POST['add_on_key']) || trim($_POST['add_on_key']) === '') {
            echo "error: add_on_key is missing";
            exit;
        }

        $add_on_key = trim($_POST['add_on_key']);

        $stmt = $conn->prepare(
            "DELETE FROM add_on_table WHERE add_on_key = ?"
        );

        if (!$stmt) {
            echo "error: " . $conn->error;
            exit;
        }

        $stmt->bind_param("s", $add_on_key);

        if (!$stmt->execute()) {
            echo "error: " . $stmt->error;
            $stmt->close();
            exit;
        }

        if ($stmt->affected_rows > 0) {
            echo "success";
        } else {
            echo "error: add-on not found";
        }

        $stmt->close();
        exit;
    }
}


if (isset($_POST['toggle_status'])) {
    $add_on_key = $_POST['add_on_key'];
    $add_on_status = $_POST['add_on_status'];

    $new_status = ($add_on_status === 'Available') ? 'Not Available' : 'Available';

    $stmt = $conn->prepare("UPDATE add_on_table SET add_on_status = ? WHERE add_on_key = ?");
    $stmt->bind_param('ss', $new_status, $add_on_key);

    if ($stmt->execute()) {
        header('Location: Administrator.php?page=add-ons.php');
        exit;
    } else {
        echo "Error toggling status: " . $stmt->error;
        exit;
    }
    $stmt->close();
}


if (isset($_POST['addAddOns'])) {
    $add_on_key = $_POST['add_on_key'];
    $add_on_name = $_POST['add_on_name'];
    $add_on_qty = $_POST['add_on_qty'];
    $add_on_price = $_POST['add_on_price'];
    
    // Check if required fields are empty or invalid
    if (empty($add_on_key) || empty($add_on_name) || !isset($add_on_qty) || !is_numeric($add_on_qty) || !isset($add_on_price) || empty($add_on_price)) {
        echo "Error: Missing required fields.";
        exit;
    }
	
	
	// Check if add_on_key or add_on_name already exists (case-insensitive)
    $check_query = $conn->prepare("SELECT * FROM add_on_table WHERE LOWER(add_on_key) = LOWER(?) OR LOWER(add_on_name) = LOWER(?)");
    $check_query->bind_param('ss', $add_on_key, $add_on_name);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
            alert('Error: Add-on key or add-on name already exists.');
            window.location.href = 'Administrator.php?page=add-ons.php';
        </script>";
        exit;
    }
	
    // Determine the status based on the add_on_qty
    if ($add_on_qty == 0) {
        $add_on_status = 'Not Available';
    } else {
        $add_on_status = 'Available';
    }

    $stmt = $conn->prepare("INSERT INTO add_on_table (add_on_key, add_on_name, add_on_qty, add_on_price, add_on_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiss', $add_on_key, $add_on_name, $add_on_qty, $add_on_price, $add_on_status);

    if ($stmt->execute()) {
        echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=add-ons.php">';
        exit;
    } else {
        echo "Error inserting record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

if (isset($_POST['editAddOns'])) {
    $add_on_key = $_POST['add_on_key'];
    $add_on_name = $_POST['add_on_name'];
    $add_on_qty = $_POST['add_on_qty'];
    $add_on_price = $_POST['add_on_price'];

    if (!isset($_POST['add_on_key'], $_POST['add_on_name'], $_POST['add_on_qty'], $_POST['add_on_price'])) {
        echo "Missing required fields!";
        exit;
    }

    // Prepare the UPDATE query
    $stmt = $conn->prepare("UPDATE add_on_table SET add_on_name = ?, add_on_qty = ?, add_on_price = ? WHERE add_on_key = ?");
    $stmt->bind_param('sids', $add_on_name, $add_on_qty, $add_on_price, $add_on_key);

    if ($stmt->execute()) {
        // Check if quantity is zero and update status if necessary
        if ($add_on_qty == 0) {
            $stmt_status = $conn->prepare("UPDATE add_on_table SET add_on_status = 'Not Available' WHERE add_on_key = ?");
            $stmt_status->bind_param('s', $add_on_key);
            $stmt_status->execute();
            $stmt_status->close();
        } else {
        // Vice versa: if quantity is greater than 0, update status to 'Available'
        $stmt_status = $conn->prepare("UPDATE add_on_table SET add_on_status = 'Available' WHERE add_on_key = ?");
        $stmt_status->bind_param('s', $add_on_key);
        $stmt_status->execute();
        $stmt_status->close();
    }
        echo "success";
        echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=add-ons.php">';
		exit;
		echo "<script type='text/javascript'>
            window.location.href = 'Administrator.php?page=add-ons.php';
          </script>";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
    
}


// Fetch all categories
$result = $conn->query("SELECT add_on_key, add_on_name, add_on_qty, add_on_price, add_on_status FROM add_on_table");
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
    <h1>Add-ons Settings</h1>
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
      <tbody id="addon-table-body">
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr id="row-<?php echo $row['add_on_key']; ?>">
          <td id="add_on_key-<?php echo $row['add_on_key']; ?>"><?php echo $row['add_on_key']; ?></td>
          <td id="add_on_name-<?php echo $row['add_on_key']; ?>"><?php echo $row['add_on_name']; ?></td>
		  <td id="add_on_qty-<?php echo $row['add_on_key']; ?>"><?php echo $row['add_on_qty']; ?></td>
		  <td id="add_on_price-<?php echo $row['add_on_key']; ?>"><?php echo $row['add_on_price']; ?></td>
		  <td id="add_on_status-<?php echo $row['add_on_key']; ?>"><?php echo $row['add_on_status']; ?></td>
          <td>
        <div class="crud-buttons">
		<button class="view" onclick="viewAddOn('<?php echo $row['add_on_key']; ?>', '<?php echo $row['add_on_name']; ?>', '<?php echo $row['add_on_qty']; ?>', '<?php echo $row['add_on_price']; ?>', '<?php echo $row['add_on_status']; ?>')">View</button>  
        <button class="edit" id="edit-<?php echo $row['add_on_key']; ?>" onclick="editAddonse('<?php echo $row['add_on_key']; ?>')">Edit</button>	
        <form method="post" style="display:inline;" action="add-ons.php">
            <input type="hidden" name="add_on_key" value="<?php echo $row['add_on_key']; ?>">
            <button type="button" class="remove" onclick="confirmRemoveAddon('<?php echo $row['add_on_key']; ?>')">Remove</button>
        </form>	
			<form method="POST" style="display:inline;" action="add-ons.php" class="toggle-form">
			<input type="hidden" name="add_on_key" value="<?php echo $row['add_on_key']; ?>">
			<input type="hidden" name="add_on_status" value="<?php echo $row['add_on_status']; ?>">
			<!-- Disable or enable button logic -->
			<button type="submit" name="toggle_status" class="<?php echo strtolower($row['add_on_status']) === 'available' ? 'disable' : 'enable'; ?>"> <?php echo strtolower($row['add_on_status']) === 'available' ? 'Disable' : 'Enable'; ?>
			</button>
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


  <div class="modal" id="view-addon-modal" style="display: none;">
  <div class="modal-content">
    <div class="modal-header">
      <h2 style="margin: 0;">Add-on Details</h2>
      <button class="close-btn" id="close-addon-modal">&times;</button>
    </div>
    <div id="product-info" style="text-align: center;">
      <p style="margin: 0;"><strong>Add-on Code:</strong> <span id="addon-code-detail"></span></p>
      <p style="margin: 0;"><strong>Add-on Name:</strong> <span id="addon-name-detail"></span></p>
	  <p style="margin: 0;"><strong>Add-on Quantity:</strong> <span id="addon-qty-detail"></span></p>
      <p style="margin: 0;"><strong>Add-on Price:</strong> <span id="addon-price-detail"></span></p>
	  <p style="margin: 0;"><strong>Add-on Status:</strong> <span id="addon-status-detail"></span></p> 
    </div>
    <button class="close-button" id="close-viewad-modal-btn">Close</button>
  </div>
</div>
	
	
 
<!-- Add Add-on Modal -->
<div class="modal" id="addonsModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Add-on</h2>
      <button class="close-btn" id="close-add-addon-modal">&times;</button>
    </div>
    <form id="product-form" method="POST">
      <div class="form-group">
        <input type="text" id="add_on_key" name="add_on_key" placeholder="Add-on Code" required>
        <label for="add_on_key">Add-on Code</label>
      </div>
      <div class="form-group">
        <input type="text" id="add_on_name" name="add_on_name" placeholder="Add-on Name" required>
        <label for="add_on_name">Add-on Name</label>
      </div>
	  <div class="form-group">
        <input type="number" id="add_on_qty" name="add_on_qty" placeholder="Add-on Quantity" required>
        <label for="add_on_qty">Quantity</label>
      </div>
	  <div class="form-group">
        <input type="number" id="add_on_price" name="add_on_price" placeholder="Add-on Price" required>
        <label for="add_on_price">Add-on Price</label>
      </div>
      <button type="submit" name="addAddOns" class="submit-btn">Add Add-on</button>
    </form>
    <button type="button" class="close-button" id="close-add-addon-modal-btn">Close</button>
  </div>
</div>

<!--Confirm Button-->
<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected Add-on?</p>
        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const addButton = document.querySelector(".add-button");
  const modal = document.getElementById("addonsModal");
  
  const closeModalButtons = [
    document.getElementById("close-add-addon-modal"),
    document.getElementById("close-add-addon-modal-btn"),
  ];

  // Function to open the modal
  const openModal = () => {
    modal.style.display = "flex";
  };

  // Function to close the modal
  const closeModal = () => {
    modal.style.display = "none";
  };

  // Event listener for the "+" button
  addButton.addEventListener("click", openModal);

  // Event listeners for the close buttons
  closeModalButtons.forEach(button => button.addEventListener("click", closeModal));

  // Close modal when clicking outside the modal content
  window.addEventListener("click", (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });
});



function editAddonse(addonKey) {
    const editButton = document.getElementById(`edit-${addonKey}`);
    const addonNameTd = document.getElementById(`add_on_name-${addonKey}`);
    const addonQuantityTd = document.getElementById(`add_on_qty-${addonKey}`);
    const addonPriceTd = document.getElementById(`add_on_price-${addonKey}`);

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

        // Switch to "Save" mode
        editButton.innerText = "Save";
        addonNameTd.contentEditable = "true";
        addonQuantityTd.contentEditable = "true";
        addonPriceTd.contentEditable = "true";
        addonNameTd.style.backgroundColor = "#f9f9f9"; // Highlight editable area
        addonQuantityTd.style.backgroundColor = "#f9f9f9";
        addonPriceTd.style.backgroundColor = "#f9f9f9";
        addonNameTd.focus();
    } else {
        // Save the updated values
        const updatedAddonType = addonNameTd.innerText;
        const updatedAddonQuantity = addonQuantityTd.innerText;
        const updatedAddonPrice = addonPriceTd.innerText;

        // Make an AJAX request to update the database
        const formData = new FormData();
        formData.append("editAddOns", true);
        formData.append("add_on_key", addonKey);
        formData.append("add_on_name", updatedAddonType);
        formData.append("add_on_qty", updatedAddonQuantity);
        formData.append("add_on_price", updatedAddonPrice);

        fetch("add-ons.php", { // Assuming the PHP file handling the update is "add-ons.php"
            method: "POST",
            body: formData,
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                alert("Error updating Add-On: " + result);
            } else {
                alert("Add-on updated successfully.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });

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

        // Switch back to "Edit" mode
        editButton.innerText = "Edit";
        addonNameTd.contentEditable = "false";
        addonQuantityTd.contentEditable = "false";
        addonPriceTd.contentEditable = "false";
        addonNameTd.style.backgroundColor = ""; // Reset background color
        addonQuantityTd.style.backgroundColor = "";
        addonPriceTd.style.backgroundColor = "";
    }
}
	
	function viewAddOn(addonKey, addonName, addonQty, addonPrice, addonStatus) {
    // Update the modal content with the passed values
    document.getElementById("addon-code-detail").textContent = addonKey;
    document.getElementById("addon-name-detail").textContent = addonName;
    document.getElementById("addon-qty-detail").textContent = addonQty;
    document.getElementById("addon-price-detail").textContent = addonPrice;
    document.getElementById("addon-status-detail").textContent = addonStatus;

    // Show the modal
    document.getElementById("view-addon-modal").style.display = "flex";
  }

  // Close modal by clicking the close button or the close modal button
  document.getElementById("close-addon-modal").addEventListener("click", function() {
    document.getElementById("view-addon-modal").style.display = "none";
  });

  document.getElementById("close-viewad-modal-btn").addEventListener("click", function() {
    document.getElementById("view-addon-modal").style.display = "none";
  });


// REMOVE ADD-ON CONFIRMATION

let addOnToDelete = null;


// FUNCTION CALLED BY REMOVE BUTTON

function confirmRemoveAddon(addOnKey) {

    // Store the selected Add-on key
    addOnToDelete = addOnKey;

    // Get confirmation modal
    const modal = document.getElementById("delete-confirm-modal");

    // Show confirmation modal
    modal.style.display = "flex";
}


// CANCEL DELETE

document.getElementById("cancel-delete").onclick = function () {

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";

    // Clear selected Add-on
    addOnToDelete = null;
};


// CONFIRM DELETE

document.getElementById("confirm-delete").onclick = function () {

    if (!addOnToDelete) {
        return;
    }

    const formData = new FormData();

    formData.append("remove", "1");
    formData.append("add_on_key", addOnToDelete);

    document.getElementById("delete-confirm-modal").style.display = "none";

    fetch("add-ons.php", {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(result => {

        result = result.trim();

        if (result === "success") {

            alert('Add-ons deleted successfully');
            window.location.href =
                "Administrator.php?page=add-ons.php";

        } else {

            alert("Error deleting Add-on: " + result);

        }

    })
    .catch(error => {

        console.error("Error deleting Add-on:", error);
        alert("An error occurred while deleting the Add-on.");

    });

    addOnToDelete = null;
};
</script>
</body>
</html>

