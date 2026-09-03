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
if (isset($_POST['addProduct'])) { 

     
    // AUTOMATIC PRODUCT CODE GENERATION
     

    $next_product_number = 101;

    $next_code_query = $conn->query("
        SELECT MAX(
            CAST(SUBSTRING(product_code, 3) AS UNSIGNED)
        ) AS max_number
        FROM product_table
        WHERE product_code REGEXP '^P-[0-9]+$'
    ");

    if ($next_code_query) {
        $next_code_row = $next_code_query->fetch_assoc();

        if (
            isset($next_code_row['max_number']) &&
            $next_code_row['max_number'] !== null
        ) {
            $next_product_number =
                (int)$next_code_row['max_number'] + 1;
        }
    }

    // Generate final database code
    $product_code = 'P-' . $next_product_number;


     
    // OTHER PRODUCT INFORMATION
     

    $product_name = trim($_POST['product_name']);
    $minutes = $_POST['minutes'];
    $product_price = $_POST['product_price'];
    $category_code = $_POST['category_code'];


     
    // VALIDATE OTHER FIELDS
     

    if (
        empty($product_name) ||
        empty($minutes) ||
        empty($product_price) ||
        empty($category_code)
    ) {

        echo "<script>
            alert('Error: Missing fields.');
            window.location.href =
                'Administrator.php?page=menu.php';
        </script>";

        exit;
    }


     
    // CHECK DUPLICATE PRODUCT CODE / NAME
     

    $check_query = $conn->prepare("
        SELECT *
        FROM product_table
        WHERE LOWER(product_code) = LOWER(?)
           OR LOWER(product_name) = LOWER(?)
    ");

    $check_query->bind_param(
        'ss',
        $product_code,
        $product_name
    );

    $check_query->execute();

    $result = $check_query->get_result();


    if ($result->num_rows > 0) {

        echo "<script>
            alert('Error: Product code or product name already exists.');
            window.location.href =
                'Administrator.php?page=menu.php';
        </script>";

        exit;
    }


     
    // INSERT PRODUCT
     

    $stmt = $conn->prepare("
        INSERT INTO product_table
        (
            product_code,
            product_name,
            minutes,
            product_price,
            category_code,
            product_created_date
        )
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->bind_param(
        'ssids',
        $product_code,
        $product_name,
        $minutes,
        $product_price,
        $category_code
    );


    if ($stmt->execute()) {

        
        // SET PRODUCT STATUS
        

        $stmt_status = $conn->prepare("
            UPDATE product_table
            SET product_status = 'AVAILABLE'
            WHERE product_code = ?
        ");

        $stmt_status->bind_param(
            's',
            $product_code
        );

        $stmt_status->execute();
        $stmt_status->close();


        
        // SET TEMPORARY PRODUCT IMAGE
        

        $temporaryProductImage = 'sakamoto.png';

        $updateProductImageStmt = $conn->prepare("
            UPDATE product_table
            SET product_image = ?
            WHERE product_code = ?
        ");

        $updateProductImageStmt->bind_param(
            "ss",
            $temporaryProductImage,
            $product_code
        );

        $updateProductImageStmt->execute();
        $updateProductImageStmt->close();


        
        // REDIRECT AFTER SUCCESS
        

        header(
            'Location: Administrator.php?page=menu.php'
        );

        exit;

    } else {

        echo "Error inserting record: " .
             $stmt->error;

        exit;
    }

    $stmt->close();
}



// HANDLE EDIT PRODUCT
if (isset($_POST['edit'])) {

    $product_code = $_POST['product_code'];
    $product_name = trim($_POST['product_name']);
    $minutes = $_POST['minutes'];
    $product_price = $_POST['product_price'];
    $category_code = $_POST['category_code'];


    
    // CHECK FOR MISSING FIELDS
    

    if (
        empty($product_code) ||
        empty($product_name) ||
        empty($minutes) ||
        empty($product_price) ||
        empty($category_code)
    ) {

        echo "Error: Missing required fields.";
        exit;
    }


    
    // UPDATE PRODUCT
    

    $stmt = $conn->prepare("
        UPDATE product_table
        SET
            product_name = ?,
            minutes = ?,
            product_price = ?,
            category_code = ?
        WHERE product_code = ?
    ");

    $stmt->bind_param(
        'sidss',
        $product_name,
        $minutes,
        $product_price,
        $category_code,
        $product_code
    );


    if ($stmt->execute()) {

        echo "success";

    } else {

        echo "Error updating record: " . $stmt->error;

    }

    $stmt->close();
    exit;
}

	
	
// Handle Edit operation for product image
if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $product_code = $_POST['product_code'];
    $uploadDir = '../uploads/product_image/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES['product_image']['tmp_name'];
    $fileName = uniqid('product_', true) . '.' . strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $destPath = $uploadDir . $fileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $product_image_path = $destPath;

        $stmt = $conn->prepare("UPDATE product_table SET product_image=? WHERE product_code=?");
        $stmt->bind_param('ss', $product_image_path, $product_code);

        if ($stmt->execute()) {
            // ✅ Only echo "success" for AJAX
            echo "success";
        } else {
            echo "error";
        }

        $stmt->close();
        exit;
    } else {
        echo "error";
        exit;
    }
}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the 'delete' action is triggered
    if (isset($_POST['delete']) && isset($_POST['product_codes'])) {
        // Decode the JSON-encoded array from JavaScript
        $product_codes = json_decode($_POST['product_codes']);  // Decode the JSON string into an array
        
        // Ensure that the input is an array
        if (is_array($product_codes) && count($product_codes) > 0) {
            // Escape each category code to prevent SQL injection
            $product_codes = array_map(function($code) use ($conn) {
                return mysqli_real_escape_string($conn, $code);
            }, $product_codes);

            // Convert the array to a comma-separated string for SQL query
            $product_codes_str = implode("','", $product_codes);

            // SQL query to delete categories with the specified category_code
            $sql = "DELETE FROM product_table WHERE product_code IN ('$product_codes_str')";
            
            if (mysqli_query($conn, $sql)) {
                header('Location: Administrator.php?page=menu.php');
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
    $product_code = $_POST['product_code'];
    $current_status = $_POST['product_status'];
    $new_status = ($current_status === 'AVAILABLE') ? 'NOT AVAILABLE' : 'AVAILABLE';

    $stmt = $conn->prepare("UPDATE product_table SET product_status=? WHERE product_code=?");
    $stmt->bind_param('ss', $new_status, $product_code);
    if ($stmt->execute()) {
		header('Location: Administrator.php?page=menu.php');
		exit;
    } else {
        echo "Error updating record: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

// Fetch all categories
$category_result = $conn->query("SELECT category_code, category_name FROM category_table"); // Assuming 'categories' is the table name
$categories = [];
if ($category_result->num_rows > 0) {
    while ($row = $category_result->fetch_assoc()) {
        $categories[] = $row; // Store category data in an array
    }
}

// Fetch all categories
$result = $conn->query("SELECT product_code, product_name, minutes, product_price, product_status, product_image, category_code FROM product_table");

 
// GET NEXT PRODUCT CODE NUMBER
// Example:
// P-101
// P-102
// Next = 103
 

$next_product_number = 101; // Starting number

$next_code_query = $conn->query("
    SELECT MAX(
        CAST(SUBSTRING(product_code, 3) AS UNSIGNED)
    ) AS max_number
    FROM product_table
    WHERE product_code REGEXP '^P-[0-9]+$'
");

if ($next_code_query) {
    $next_code_row = $next_code_query->fetch_assoc();

    if (
        isset($next_code_row['max_number']) &&
        $next_code_row['max_number'] !== null
    ) {
        $next_product_number = (int)$next_code_row['max_number'] + 1;
    }
}
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
    <h1>Menu Settings</h1>
  </div>
  <div class="page-header">
    <div class="add-container">
      <button class="add-more-button">+</button>  
    </div>

<!-- CATEGORY FILTER -->
    <div class="category-filter-container">
    <label for="category-filter">Filter Category:</label>
    <select id="category-filter" onchange="filterProductsByCategory()">
    <option value="ALL">All Categories</option>
        <?php foreach ($categories as $category): ?>
        <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
            <?php echo htmlspecialchars($category['category_name']); ?>
        </option>
        <?php endforeach; ?>
        </select>
    </div>

    <button class="select-product" onclick="deleteSelectedProducts()">Delete Product</button>
</div>
	
	<div class="subsection" id="categories">
    <div class="table-container">
	<table>
      <thead>
        <tr>
      <th><input type="checkbox" id="select-all" onclick="toggleSelectAll()"></th>
      <th>P–Code</th>
      <th>P–Name</th>
	  <th>Minutes<br/>Prep.</th>
	  <th>Price (Php)</th>
	  <th>Image</th>
	  <th>Status</th>
	  <th>Category</th>
      <th>Actions</th>
        </tr>
      </thead>

      <tbody id="category-table-body">
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr id="row-<?php echo $row['product_code']; ?>">
          <td><input type="checkbox" class="product-checkbox" data-id="<?php echo $row['product_code']; ?>"></td>
          <td id="product_code-<?php echo $row['product_code']; ?>"><?php echo $row['product_code']; ?></td>
          <td id="product_name-<?php echo $row['product_code']; ?>"><?php echo $row['product_name']; ?></td>
		  <td id="minutes-<?php echo $row['product_code']; ?>"><?php echo $row['minutes']; ?></td>
		  <td id="price-<?php echo $row['product_code']; ?>"><?php echo $row['product_price']; ?></td>
		  <td>
		  <?php if (!empty($row['product_image'])) { ?>
			<img src="<?php echo htmlspecialchars($row['product_image']); ?>" alt="Product Image" style="width: 50px; height: 50px; object-fit: cover;">
		  <?php } else { ?>
			<span>No Image</span>
		  <?php } ?>
		</td>
		  <td id="product_status-<?php echo $row['product_code']; ?>"><?php echo $row['product_status']; ?></td>
		  <td id="category_code-<?php echo $row['product_code']; ?>"><?php echo $row['category_code']; ?></td>
          <td>
            <div class="crud-buttons">
              <button class="view" onclick="viewProduct('<?php echo $row['product_code']; ?>', '<?php echo $row['product_name']; ?>', '<?php echo $row['minutes']; ?>', '<?php echo $row['product_price']; ?>', '<?php echo $row['product_status']; ?>', '<?php echo $row['category_code']; ?>', '<?php echo htmlspecialchars($row['product_image']); ?>')"> View</button>
			  <button class="edit" id="edit-<?php echo $row['product_code']; ?>" onclick="editProduct('<?php echo $row['product_code']; ?>')">Edit</button>
              <button type="button" class="edit_image" id="edit_image-<?php echo htmlspecialchars($row['product_code']); ?>" 
              onclick='editImage(
            <?php echo json_encode($row['product_code']); ?>,
            <?php echo json_encode($row['product_name']); ?>,
            <?php echo json_encode($row['product_image']); ?>
                    )'>🖼️</button>
			  <form method="post" class="toggle-form">
			  <input type="hidden" name="product_code" value="<?php echo $row['product_code']; ?>">
			 <input type="hidden" name="product_status" value="<?php echo $row['product_status']; ?>"> <!-- Fixed name -->	
				<button type="submit" name="toggle_status" class="<?php echo $row['product_status'] === 'AVAILABLE' ? 'disable' : 'enable'; ?>">
			<?php echo $row['product_status'] === 'AVAILABLE' ? 'Disable' : 'Enable'; ?>
			</button>			  
			</div>
	    </form>
          </td>
        </tr>
        <?php } ?>
      </tbody>
    </table>
	</div>
	</div>
  
  <!-- Add Product Modal -->
  <div class="AddModal" id="adding-product-modal" style="display: none;">
    <div class="modal-content-add">
      <div class="modal-header">
        <h2>Add New Product</h2>
        <button class="close-btn" id="close-add-prd-modal">&times;</button>
      </div>
        <form id="product-form" method="POST">
        <div class="form-group"> 
        <input type="number" id="product-code" name="product_code" placeholder="Product Number" value="<?php echo htmlspecialchars($next_product_number); ?>" min="101" step="1" required>
        <label for="product-code">Product Code</label> 
      </div>
        <div class="form-group">
          <input type="text" id="product-name" name="product_name" placeholder="Product Name" required>
          <label for="product-name">Product Name</label>
        </div>
        <div class="form-group">
          <input type="number" id="minutes-preparation" name="minutes" placeholder="Minutes Preparation" required>
          <label for="minutes-preparation">Minutes Preparation</label>
        </div>
        <div class="form-group">
          <input type="number" id="price" name="product_price" placeholder="Price" required>
          <label for="price">Price</label>
        </div>
        <div class="form-group">
          <select id="category-code" name="category_code" required>
			<option value="">Select Category</option>
			<?php foreach ($categories as $category): ?>
			  <option value="<?php echo htmlspecialchars($category['category_name']); ?>">
				<?php echo htmlspecialchars($category['category_name']); ?>
			  </option>
			<?php endforeach; ?>
		  </select>
        </div>
        <button type="submit" class="submit-btn" name="addProduct" >Add Product</button>  
      </form>
	  <button class="close-button" id="close-add-modal-form">Close</button>
    </div>
  </div>


<!--VIEW MODAL-->
<div class="ViewModal" id="view-modal" style="display: none;">
    <div class="modal-content-view">
        <div class="modal-header">
            <h2 style="margin: 0;">Product Details</h2>
            <button class="close-btn" id="close-viewa-modal">&times;</button>
        </div>
        <div id="product-details">
            <div id="product-image-container">
                <img id="view-product-image" alt="Account Image">
            </div>
            <div id="product-details-container">
            <div id="product-info" style="margin-left: 0;">
            <p><strong>Product Code:</strong>
			<span id="product-code-detail"></span></p>
            <p><strong>Product Name:</strong>
			<span id="product-name-detail"></span></p>
            <p><strong>Minutes of Prep:</strong>
			<span id="minutes-preparation-detail"></span> mins</p>
            <p><strong>Price :</strong>
			<span id="product-price-detail"></span></p>
            <p><strong>Product Status:</strong>
			<span id="product-status-detail"></span></p>
            <p><strong>Category Code:</strong>
			<span id="category-code-detail"></span></p>
          </div>
        </div>
      </div> <!-- Closing tag for #product-details -->
      <button class="close-button" id="close-viewp-modal-btn">Close</button>
    </div>
  </div>
  


<div id="delete-confirm-modal" class="delete-modal-overlay">
    <div class="delete-modal">
        <h3>Confirm Deletion</h3>
        <p>Are you sure you want to delete the selected products?</p>
        <div class="delete-modal-buttons">
            <button type="button" id="cancel-delete">Cancel</button>
            <button type="button" id="confirm-delete">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- EDIT PRODUCT IMAGE MODAL -->

<div class="EditImageModal" id="edit-image-modal" style="display: none;">
    <div class="edit-image-modal-content">
        <!-- Header -->
        <div class="modal-header">
            <h2 style="margin: 0;">
                Edit Product Image
            </h2>
            <button type="button" class="close-btn" id="close-edit-image-modal">&times;</button>
        </div>
        <!-- Product Name -->
        <p class="edit-image-product-name">Product:<strong id="edit-image-product-name"></strong></p>
        <!-- IMAGE FRAME -->
        <div class="edit-image-preview-container">
    <img id="edit-image-preview" src="" alt="Product Image Preview">
    <span id="edit-image-no-image"> No Image</span>
    <!-- Camera button -->
    <label for="edit-product-image" class="upload-new-image-btn" id="upload-new-image-btn">📸</label>
    <!-- Hidden file input -->
    <input type="file" name="product_image" id="edit-product-image" accept="image/*" style="display: none;">
</div>

<!-- UPLOAD FORM -->
        <form method="POST" enctype="multipart/form-data" id="edit-image-form">
            <!-- Hidden Product Code -->
            <input type="hidden" name="product_code" id="edit-image-product-code-input">
            <!-- FINAL UPLOAD BUTTON -->
            <button type="submit" class="upload-image-button" id="final-upload-image-btn" name="uploadProductImage"> Upload Image</button>
        </form>
        <!-- Cancel -->
        <button type="button" class="close-button" id="close-edit-image-modal-btn">Cancel</button>
    </div>
</div>

<script>
 
// EDIT PRODUCT IMAGE MODAL
function editImage(product_code, product_name, product_image) {

    const modal =
        document.getElementById("edit-image-modal");

    const productNameText =
        document.getElementById("edit-image-product-name");

    const productCodeInput =
        document.getElementById("edit-image-product-code-input");

    const currentImage =
        document.getElementById("edit-image-preview");

    const noImage =
        document.getElementById("edit-image-no-image");

    const fileInput =
        document.getElementById("edit-product-image");


    
    // PRODUCT NAME
    productNameText.textContent = product_name;


    
    // PRODUCT CODE
    // Hidden - used by menu.php
    productCodeInput.value = product_code;


    // RESET FILE INPUT
    fileInput.value = "";


    
    // SHOW ORIGINAL PRODUCT IMAGE
    if (product_image && product_image.trim() !== "") {

        currentImage.src = product_image;

        currentImage.style.display = "block";

        noImage.style.display = "none";

    } else {

        currentImage.src = "";

        currentImage.style.display = "none";

        noImage.style.display = "block";
    }


    
    // OPEN MODAL
    modal.style.display = "flex";
}



 
// SELECT NEW IMAGE
// CAMERA BUTTON OPENS FILE PICKER
document.getElementById("edit-product-image")
    .addEventListener("change", function () {

        const file = this.files[0];

        const preview =
            document.getElementById("edit-image-preview");

        const noImage =
            document.getElementById("edit-image-no-image");


        
        // NO FILE SELECTED
        if (!file) {
            return;
        }

        // CHECK FILE TYPE
        if (!file.type.startsWith("image/")) {

            alert("Please select an image file.");

            this.value = "";

            return;
        }


        
        // PREVIEW NEW IMAGE
        const reader = new FileReader();

        reader.onload = function (event) {

            /* Replace the current image in the SAME preview frame. */

            preview.src = event.target.result;

            preview.style.display = "block";

            noImage.style.display = "none";

        };

        reader.readAsDataURL(file);

    });



 
// UPLOAD PRODUCT IMAGE
// THIS IS THE OFFICIAL SAVE ACTION
 
document.getElementById("edit-image-form")
    .addEventListener("submit", function (event) {

        event.preventDefault();


        const product_code =
            document.getElementById(
                "edit-image-product-code-input"
            ).value;


        const fileInput =
            document.getElementById(
                "edit-product-image"
            );


        
        // CHECK FOR NEW IMAGE
        if (fileInput.files.length === 0) {

            alert(
                "Please select a new image first."
            );

            return;
        }


        
        // CREATE FORMDATA
        const form = new FormData();

        form.append(
            "product_code",
            product_code
        );

        form.append(
            "product_image",
            fileInput.files[0]
        );


        
        // SEND TO menu.php
        fetch("menu.php", {
            method: "POST",
            body: form
        })

        .then(response => response.text())

        .then(data => {

    if (data.trim() === "success") {

    alert("Image updated successfully!");

    // Close modal first
    document.getElementById("edit-image-modal")
        .style.display = "none";

        // Reload page so the new image appears
        window.location.reload();

} else {

        alert("Error updating image.");

    }

})

        .catch(error => {

            console.error(
                "Error:",
                error
            );

            alert(
                "Error updating image."
            );

        });

    });



 
// CLOSE - X BUTTON
document.getElementById("close-edit-image-modal")
    .addEventListener("click", function () {

        document.getElementById(
            "edit-image-modal"
        ).style.display = "none";

    });



 
// CLOSE - CANCEL BUTTON
document.getElementById("close-edit-image-modal-btn")
    .addEventListener("click", function () {

        document.getElementById(
            "edit-image-modal"
        ).style.display = "none";

    });




 
// CLOSE - X BUTTON
document.getElementById("close-edit-image-modal")
    .addEventListener("click", function () {

        document.getElementById(
            "edit-image-modal"
        ).style.display = "none";

    });



 
// CLOSE - CANCEL BUTTON
document.getElementById("close-edit-image-modal-btn")
    .addEventListener("click", function () {

        document.getElementById(
            "edit-image-modal"
        ).style.display = "none";

    });
  
  // Handle editing the product (toggle between edit and save)
 
// EDIT PRODUCT
// Allows editing:
// Product Name
// Minutes
// Price
// Category
 

function editProduct(product_code) {

    const productNameCell =
        document.getElementById('product_name-' + product_code);

    const productMinsCell =
        document.getElementById('minutes-' + product_code);

    const productPriceCell =
        document.getElementById('price-' + product_code);

    const productCategoryCell =
        document.getElementById('category_code-' + product_code);

    const editButton =
        document.getElementById('edit-' + product_code);


    
    // ENTER EDIT MODE
    if (editButton.textContent.trim() === 'Edit') {

        // LOCK OTHER ACTION BUTTONS
        const actionButtons = document.querySelectorAll(
            '.view, .edit, .edit_image, .toggle-form button'
        );

        actionButtons.forEach(button => {

            if (button !== editButton) {

                button.disabled = true;
                button.style.pointerEvents = 'none';
                button.style.opacity = '0.5';

            }

        });


        // SAVE ORIGINAL VALUES
        const currentProductName =
            productNameCell.textContent.trim();

        const currentMinutes =
            productMinsCell.textContent.trim();

        const currentPrice =
            productPriceCell.textContent.trim();

        const currentCategory =
            productCategoryCell.textContent.trim();


        // PRODUCT NAME
        productNameCell.innerHTML = `
            <input
                type="text"
                value="${currentProductName.replace(/"/g, '&quot;')}"
                style="width: 130px; box-sizing: border-box;"
            >
        `;


        // MINUTES
        productMinsCell.innerHTML = `
            <input
                type="number"
                value="${currentMinutes}"
                style="width: 70px; box-sizing: border-box;"
            >
        `;


        // PRICE
        productPriceCell.innerHTML = `
            <input
                type="number"
                value="${currentPrice}"
                step="0.01"
                style="width: 80px; box-sizing: border-box;"
            >
        `;


        // CATEGORY DROPDOWN
        let categoryOptions = `
            <option value="">Select Category</option>
        `;


        // Get categories from PHP
        <?php foreach ($categories as $category): ?>

            categoryOptions += `
                <option
                    value="<?php echo htmlspecialchars($category['category_name'], ENT_QUOTES); ?>"
                    ${currentCategory === "<?php echo htmlspecialchars($category['category_name'], ENT_QUOTES); ?>" ? 'selected' : ''}
                >
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </option>
            `;

        <?php endforeach; ?>


        productCategoryCell.innerHTML = `
            <select id="category-code" name="category_code" style=" width: 110px; height: 30px; font-size: 12px; padding: 2px 4px; box-sizing: border-box;">
                ${categoryOptions}
            </select>
        `;


        // CHANGE EDIT → SAVE
        editButton.textContent = 'Save';


    } else {

        
        // SAVE MODE
        const newProductName =
            productNameCell
                .querySelector('input')
                .value
                .trim();

        const newProductMinutes =
            productMinsCell
                .querySelector('input')
                .value
                .trim();

        const newProductPrice =
            productPriceCell
                .querySelector('input')
                .value
                .trim();

        const newProductCategory =
            productCategoryCell
                .querySelector('select')
                .value
                .trim();


        
        // VALIDATE
        if (
            newProductName === '' ||
            newProductMinutes === '' ||
            newProductPrice === '' ||
            newProductCategory === ''
        ) {

            alert('Please complete all product fields.');

            return;
        }


        
        // CREATE FORMDATA
        const form = new FormData();

        form.append(
            'product_code',
            product_code
        );

        form.append(
            'product_name',
            newProductName
        );

        form.append(
            'minutes',
            newProductMinutes
        );

        form.append(
            'product_price',
            newProductPrice
        );

        form.append(
            'category_code',
            newProductCategory
        );

        form.append(
            'edit',
            true
        );


        
        // SEND TO menu.php
        fetch('menu.php', {

            method: 'POST',
            body: form

        })

        .then(response => response.text())

        .then(data => {

            if (data.trim() === 'success') {

                // UPDATE TABLE
                productNameCell.textContent =
                    newProductName;

                productMinsCell.textContent =
                    newProductMinutes;

                productPriceCell.textContent =
                    newProductPrice;

                productCategoryCell.textContent =
                    newProductCategory;


                // CHANGE SAVE → EDIT
                editButton.textContent = 'Edit';


                // UNLOCK OTHER ACTION BUTTONS
                const actionButtons =
                    document.querySelectorAll(
                        '.view, .edit, .edit_image, .toggle-form button'
                    );

                actionButtons.forEach(button => {

                    button.disabled = false;
                    button.style.pointerEvents = 'auto';
                    button.style.opacity = '1';

                });


                alert(
                    'Product updated successfully!'
                );


            } else {

                alert(
                    'Error updating product. Please try again.'
                );

                window.location.href =
                    'Administrator.php?page=menu.php';

            }

        })

        .catch(error => {

            console.error(
                'Error:',
                error
            );

            alert(
                'Error updating product.'
            );

        });

    }
}

  // Delete selected categories
function deleteSelectedProducts() {
  const selectedCheckboxes = document.querySelectorAll('.product-checkbox:checked');
  const idsToDelete = [];

  selectedCheckboxes.forEach(checkbox => {
    idsToDelete.push(checkbox.dataset.id);
  });
  
  if (idsToDelete.length === 0) {
        alert('No product is selected!');
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

        // Send selected Product IDs/Codes to PHP
        const formData = new FormData();

        formData.append('delete', true);
        formData.append('product_codes', JSON.stringify(idsToDelete));

        fetch('menu.php', {
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

                alert('Product deleted Successfully');
                window.location.href = 'Administrator.php?page=menu.php';

            }

        })
        .catch(error => {
            console.error('Error:', error);
        });
    };
}

// ADD PRODUCT MODAL
document.addEventListener("DOMContentLoaded", () => {
    const addButton = document.querySelector(".add-more-button");
    const addModal = document.getElementById("adding-product-modal");
    const overlay = document.getElementById("modal-overlay");

    const closeAddX = document.getElementById("close-add-prd-modal");
    const closeAddButton = document.getElementById("close-add-modal-form");

    // Open Add Product Modal
    addButton.addEventListener("click", function () {
        addModal.style.display = "flex";
        overlay.style.display = "block";
    });

    // Close Add Product Modal - X button
    closeAddX.addEventListener("click", function () {
        addModal.style.display = "none";
        overlay.style.display = "none";
    });

    // Close Add Product Modal - Close button
    closeAddButton.addEventListener("click", function () {
        addModal.style.display = "none";
        overlay.style.display = "none";
    });
});


// VIEW PRODUCT MODAL
function viewProduct(
    productCode,
    productName,
    productMins,
    productPrice,
    productStatus,
    categoryCode,
    productImage
) {

    // Populate Product Details
    document.getElementById("product-code-detail").textContent = productCode;
    document.getElementById("product-name-detail").textContent = productName;
    document.getElementById("minutes-preparation-detail").textContent = productMins;
    document.getElementById("product-price-detail").textContent = productPrice;
    document.getElementById("product-status-detail").textContent = productStatus;
    document.getElementById("category-code-detail").textContent = categoryCode;


    // Product Image
    const imageElement = document.getElementById("view-product-image");

    if (productImage && productImage.trim() !== "") {
        imageElement.src = productImage;
        imageElement.style.display = "block";
    } else {
        imageElement.style.display = "none";
    }


    // Show Product View Modal
    document.getElementById("view-modal").style.display = "flex";
    document.getElementById("modal-overlay").style.display = "block";
}



// VIEW PRODUCT MODAL - X BUTTON
document.getElementById("close-viewa-modal").addEventListener("click", function () {

    document.getElementById("view-modal").style.display = "none";
    document.getElementById("modal-overlay").style.display = "none";

});



// VIEW PRODUCT MODAL - CLOSE BUTTON
document.getElementById("close-viewp-modal-btn").addEventListener("click", function () {

    document.getElementById("view-modal").style.display = "none";
    document.getElementById("modal-overlay").style.display = "none";

});



 // Toggle select all checkboxes
  function toggleSelectAll() {
    const selectAllCheckbox = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.product-checkbox');
    checkboxes.forEach(checkbox => {
      checkbox.checked = selectAllCheckbox.checked;
    });
  }


 
// CATEGORY FILTER
function filterProductsByCategory() {

    const selectedCategory =
        document.getElementById('category-filter').value;

    const rows =
        document.querySelectorAll('#category-table-body tr');

    rows.forEach(row => {

        const categoryCell =
            row.querySelector('[id^="category_code-"]');

        if (!categoryCell) {
            return;
        }

        const rowCategory =
            categoryCell.textContent.trim();

        // Show everything
        if (selectedCategory === 'ALL') {

            row.style.display = '';

        }

        // Show only selected category
        else if (rowCategory === selectedCategory) {

            row.style.display = '';

        }

        // Hide products from other categories
        else {

            row.style.display = 'none';

        }

    });

}
  </script>
  <div id="modal-overlay"></div>
</body>
</html>
