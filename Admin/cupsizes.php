<?php
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to update cup status based on quantity
function updateCupStatus($conn, $cup_key, $cup_quantity) {
    // Set status based on quantity
    $cup_status = ($cup_quantity == 0) ? 'Not Available' : 'Available';

    // Update the cup status in the database
    $stmt = $conn->prepare("UPDATE cups_table SET cup_status = ? WHERE cup_key = ?");
    $stmt->bind_param('ss', $cup_status, $cup_key);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // REMOVE CUP SIZE

    if (isset($_POST['remove'])) {

        if (!isset($_POST['cup_key']) || trim($_POST['cup_key']) === '') {
            die("Invalid request: cup_key is missing.");
        }

        $cup_key = trim($_POST['cup_key']);

        // Prepare DELETE query
        $stmt = $conn->prepare(
            "DELETE FROM cups_table WHERE cup_key = ?"
        );

        if (!$stmt) {
            die("Delete query preparation failed: " . $conn->error);
        }

        $stmt->bind_param("s", $cup_key);

        // Execute DELETE
        if (!$stmt->execute()) {

            die("Error deleting record: " . $stmt->error);

        }

        // Check whether a row was actually deleted
        if ($stmt->affected_rows === 0) {

            $stmt->close();

            die("No cup size was deleted. The cup key may not exist.");

        }

        $stmt->close();

        // Redirect AFTER successful deletion
        header("Location: Administrator.php?page=cupsizes.php");
        exit;
    }
}


if (isset($_POST['toggle_status'])) {
    $cup_key = $_POST['cup_key'];
    $cup_status = $_POST['cup_status'];

    $new_status = ($cup_status === 'Available') ? 'Not Available' : 'Available';

    $stmt = $conn->prepare("UPDATE cups_table SET cup_status = ? WHERE cup_key = ?");
    $stmt->bind_param('ss', $new_status, $cup_key);

    if ($stmt->execute()) {
        echo "<script>
        alert('Cup size deleted successfully!');
        </script>";
        header('Location: Administrator.php?page=cupsizes.php');
        exit;
    } else {
        echo "Error toggling status: " . $stmt->error;
        exit;
    }
    $stmt->close();
}


if (isset($_POST['addCupsize'])) {
    $cup_key = $_POST['cup_key'];
    $cup_type = $_POST['cup_type'];
    $cup_quantity = $_POST['cup_quantity'];
    $cup_plus_price = $_POST['cup_plus_price'];
    
    // Check if required fields are empty or invalid
    if (empty($cup_key) || empty($cup_type) || !isset($cup_quantity) || !is_numeric($cup_quantity) || !isset($cup_plus_price) || empty($cup_plus_price)) {
        echo "Error: Missing required fields.";
        exit;
    }
	
	
	$check_query = $conn->prepare("SELECT * FROM cups_table WHERE LOWER(cup_key) = LOWER(?) OR LOWER(cup_type) = LOWER(?)");
    $check_query->bind_param('ss', $cup_key, $cup_type);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "<script>
            alert('Error: Cup key or cup type already exists.');
            window.location.href = 'Administrator.php?page=cupsizes.php';
        </script>";
        exit;
    }
	

    // Determine the status based on the cup_quantity
    if ($cup_quantity == 0) {
        $cup_status = 'Not Available';
    } else {
        $cup_status = 'Available';
    }

    $stmt = $conn->prepare("INSERT INTO cups_table (cup_key, cup_type, cup_quantity, cup_plus_price, cup_status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('ssiss', $cup_key, $cup_type, $cup_quantity, $cup_plus_price, $cup_status);

    if ($stmt->execute()) {
        echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=cupsizes.php">';
        exit;
    } else {
        echo "Error inserting record: " . $stmt->error;
        exit;
    }
    $stmt->close();
}

if (isset($_POST['edit'])) {
    $cup_key = $_POST['cup_key'];
    $cup_type = $_POST['cup_type'];
    $cup_quantity = $_POST['cup_quantity'];
    $cup_plus_price = $_POST['cup_plus_price'];

    if (!isset($_POST['cup_key'], $_POST['cup_type'], $_POST['cup_quantity'], $_POST['cup_plus_price'])) {
        echo "Missing required fields!";
        exit;
    }

    // Prepare the UPDATE query
    $stmt = $conn->prepare("UPDATE cups_table SET cup_type = ?, cup_quantity = ?, cup_plus_price = ? WHERE cup_key = ?");
    $stmt->bind_param('sids', $cup_type, $cup_quantity, $cup_plus_price, $cup_key);

    if ($stmt->execute()) {
        // Check if quantity is zero and update status if necessary
        if ($cup_quantity == 0) {
            $stmt_status = $conn->prepare("UPDATE cups_table SET cup_status = 'Not Available' WHERE cup_key = ?");
            $stmt_status->bind_param('s', $cup_key);
            $stmt_status->execute();
            $stmt_status->close();
        } else {
        // Vice versa: if quantity is greater than 0, update status to 'Available'
        $stmt_status = $conn->prepare("UPDATE cups_table SET cup_status = 'Available' WHERE cup_key = ?");
        $stmt_status->bind_param('s', $cup_key);
        $stmt_status->execute();
        $stmt_status->close();
    }
        echo "success";
        echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=cupsizes.php">';
		exit;
		echo "<script type='text/javascript'>
            window.location.href = 'Administrator.php?page=cupsizes.php';
          </script>";
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
    
}


// Fetch all categories
$result = $conn->query("SELECT cup_key, cup_type, cup_quantity, cup_plus_price, cup_status FROM cups_table");
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
    <h1>Cup Sizes Settings</h1>
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
          <th>Type</th>
		  <th>Quantity</th>
		  <th>Price</th>
		  <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="cup-table-body">
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr id="row-<?php echo $row['cup_key']; ?>">
          <td id="cup_key-<?php echo $row['cup_key']; ?>"><?php echo $row['cup_key']; ?></td>
          <td id="cup_type-<?php echo $row['cup_key']; ?>"><?php echo $row['cup_type']; ?></td>
		  <td id="cup_quantity-<?php echo $row['cup_key']; ?>"><?php echo $row['cup_quantity']; ?></td>
		  <td id="cup_plus_price-<?php echo $row['cup_key']; ?>"><?php echo $row['cup_plus_price']; ?></td>
		  <td id="cup_status-<?php echo $row['cup_key']; ?>"><?php echo $row['cup_status']; ?></td>
          <td>
            <div class="crud-buttons">
			  <button class="view" onclick="viewCup('<?php echo $row['cup_key']; ?>', '<?php echo $row['cup_type']; ?>', '<?php echo $row['cup_quantity']; ?>', '<?php echo $row['cup_plus_price']; ?>', '<?php echo $row['cup_status']; ?>')">View</button>  
              <button class="edit" id="edit-<?php echo $row['cup_key']; ?>" onclick="editCup('<?php echo $row['cup_key']; ?>')">Edit</button>
			
<form method="post" style="display:inline;" action="cupsizes.php" class="remove-form">
<input type="hidden" name="cup_key" value="<?php echo $row['cup_key']; ?>">
<input type="hidden" name="remove" value="1">
<button type="submit" class="remove"> Remove </button>
</form>			
			<form method="POST" style="display:inline;" action="cupsizes.php" class="toggle-form">
			<input type="hidden" name="cup_key" value="<?php echo $row['cup_key']; ?>">
			<input type="hidden" name="cup_status" value="<?php echo $row['cup_status']; ?>">

			<!-- Disable or enable button logic -->
			<button type="submit" name="toggle_status" 
				class="<?php echo strtolower($row['cup_status']) === 'available' ? 'disable' : 'enable'; ?>">
				<?php echo strtolower($row['cup_status']) === 'available' ? 'Disable' : 'Enable'; ?>
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

	
  <div class="modal" id="view-cup-modal" style="display: none;">
  <div class="modal-content" style="position: relative; margin: auto; top: 50%; transform: translateY(-50%); width: 80%; max-width: 500px; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
    <div class="modal-header">
      <h2 style="margin: 0;">Cup Size Details</h2>
      <button class="close-btn" id="close-cup-modal">&times;</button>
    </div>
    <div id="product-info" style="text-align: center;">
      <p style="margin: 0;"><strong>Cup Code:</strong> <span id="cup-code-detail"></span></p>
      <p style="margin: 0;"><strong>Cup Type:</strong> <span id="cup-name-detail"></span></p>
	  <p style="margin: 0;"><strong>Quantity:</strong> <span id="cup-qty-detail"></span></p>
      <p style="margin: 0;"><strong>Cup Price:</strong> <span id="cup-price-detail"></span></p>
	  <p style="margin: 0;"><strong>Cup Status:</strong> <span id="cup-status-detail"></span></p> 
    </div>
    <button class="close-button" id="close-viewc-modal-btn">Close</button>
  </div>
</div>
	
	
  
<!-- Add Product Modal -->
<div class="modal" id="cupsizesModal">
  <div class="modal-content">
    <div class="modal-header">
      <h2>Add New Cup Size</h2>
      <button class="close-btn" id="close-add-cup-modal">&times;</button>
    </div>
    <form id="product-form" method="POST">
      <div class="form-group">
        <input type="text" id="cup_key" name="cup_key" placeholder="Cup Code" required>
        <label for="cup_key">Cup Code</label>
      </div>
      <div class="form-group">
        <input type="text" id="cup_type" name="cup_type" placeholder="Cup Type" required>
        <label for="cup_type">Cup Type</label>
      </div>
	  <div class="form-group">
        <input type="number" id="cup_quantity" name="cup_quantity" placeholder="Cup Quantity" required>
        <label for="cup_quantity">Quantity</label>
      </div>
	  <div class="form-group">
        <input type="number" id="cup_plus_price" name="cup_plus_price" placeholder="Cup Price" required>
        <label for="cup_plus_price">Cup Price</label>
      </div>
      <button type="submit" name="addCupsize" class="submit-btn">Add Cup Size</button>
    </form>
    <button type="button" class="close-button" id="close-add-cup-modal-btn">Close</button>
  </div>
</div>

<!--Confirm Button-->
<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected Cup?</p>
        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const addButton = document.querySelector(".add-button");
    const modal = document.getElementById("cupsizesModal");

    const closeModalButtons = [
        document.getElementById("close-add-cup-modal"),
        document.getElementById("close-add-cup-modal-btn"),
    ];

    // Open the modal
    const openModal = () => {
        modal.style.display = "flex";
    };

    // Close the modal
    const closeModal = () => {
        modal.style.display = "none";
    };

    // "+" button
    addButton.addEventListener("click", openModal);

    // X and Close buttons
    closeModalButtons.forEach(button => {
        if (button) {
            button.addEventListener("click", closeModal);
        }
    });

    // Close when clicking the overlay
    window.addEventListener("click", (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });
});



function editCup(cupKey) {
    const editButton = document.getElementById(`edit-${cupKey}`);
    const cupTypeTd = document.getElementById(`cup_type-${cupKey}`);
    const cupQuantityTd = document.getElementById(`cup_quantity-${cupKey}`);
    const cupPlusPriceTd = document.getElementById(`cup_plus_price-${cupKey}`);

    if (editButton.innerText === "Edit") {

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
        cupTypeTd.contentEditable = "true";
        cupQuantityTd.contentEditable = "true";
        cupPlusPriceTd.contentEditable = "true";
        cupTypeTd.style.backgroundColor = "#f9f9f9"; // Highlight editable area
        cupQuantityTd.style.backgroundColor = "#f9f9f9";
        cupPlusPriceTd.style.backgroundColor = "#f9f9f9";
        cupTypeTd.focus();
    } else {
        // Save the updated values
        const updatedCupType = cupTypeTd.innerText;
        const updatedCupQuantity = cupQuantityTd.innerText;
        const updatedCupPlusPrice = cupPlusPriceTd.innerText;

        // Make an AJAX request to update the database
        const formData = new FormData();
        formData.append("edit", true);
        formData.append("cup_key", cupKey);
        formData.append("cup_type", updatedCupType);
        formData.append("cup_quantity", updatedCupQuantity);
        formData.append("cup_plus_price", updatedCupPlusPrice);

        fetch("cupsizes.php", { // Assuming the PHP file handling the update is "update_cup.php"
            method: "POST",
            body: formData,
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() === "success") {
                alert("Error updating cup size: " + result);
            } else {
                alert("Cup size updated successfully.");
            }
        })
        .catch(error => {
            console.error("Error:", error);
        });

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
        cupTypeTd.contentEditable = "false";
        cupQuantityTd.contentEditable = "false";
        cupPlusPriceTd.contentEditable = "false";
        cupTypeTd.style.backgroundColor = ""; // Reset background color
        cupQuantityTd.style.backgroundColor = "";
        cupPlusPriceTd.style.backgroundColor = "";
    }
}
	
	function viewCup(cupCode, cupType, cupQty, cupPrice, cupStatus) {
    // Update the modal content with the passed values
    document.getElementById("cup-code-detail").textContent = cupCode;
    document.getElementById("cup-name-detail").textContent = cupType;
    document.getElementById("cup-qty-detail").textContent = cupQty;
    document.getElementById("cup-price-detail").textContent = cupPrice;
    document.getElementById("cup-status-detail").textContent = cupStatus;

    // Show the modal
    document.getElementById("view-cup-modal").style.display = "block";
  }

  // Close modal by clicking the close button or the close modal button
  document.getElementById("close-cup-modal").addEventListener("click", function() {
    document.getElementById("view-cup-modal").style.display = "none";
  });

  document.getElementById("close-viewc-modal-btn").addEventListener("click", function() {
    document.getElementById("view-cup-modal").style.display = "none";
  });

// REMOVE CUP SIZE CONFIRMATION

let removeFormToSubmit = null;


// SHOW DELETE CONFIRMATION

document.querySelectorAll('.remove-form').forEach(form => {

    form.addEventListener('submit', function(event) {

        // Prevent the form from submitting immediately
        event.preventDefault();

        // Remember the selected form
        removeFormToSubmit = this;

        // Show confirmation modal
        document.getElementById("delete-confirm-modal").style.display = "flex";
    });

});



// CANCEL DELETE
document.getElementById("cancel-delete").addEventListener("click", function() {

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";

    // Clear selected form
    removeFormToSubmit = null;

});



// CONFIRM DELETE
document.getElementById("confirm-delete").addEventListener("click", function() {

    // Make sure a form was selected
    if (!removeFormToSubmit) {
        console.error("No remove form selected.");
        return;
    }

    // Save the form reference
    const form = removeFormToSubmit;

    // Clear the variable
    removeFormToSubmit = null;

    // Hide confirmation modal
    document.getElementById("delete-confirm-modal").style.display = "none";

    // Submit directly WITHOUT triggering the submit event again
    form.submit();

});

</script>
</body>
</html>

