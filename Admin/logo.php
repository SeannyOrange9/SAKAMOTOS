<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = '../uploads/logo_image/';
$logoId = 'logo'; // Default logo_id

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES['logo_image']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION));
    $newFileName = 'logo_' . uniqid() . '.' . $fileExtension;
    $destPath = $uploadDir . $newFileName;

    // Move uploaded file to the target directory
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // Save the new image path in the database
        $stmt = $conn->prepare("UPDATE logo_table SET logo_image=? WHERE logo_id=?");
        $stmt->bind_param('ss', $destPath, $logoId);

        if ($stmt->execute()) {
            
        } else {
            echo "Database update error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error moving uploaded file.";
    }
}

$logoPath = '';
$sql = "SELECT logo_image FROM logo_table WHERE logo_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $logoId);
$stmt->execute();
$stmt->bind_result($logoPath);
$stmt->fetch();
$stmt->close();


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>

body {
    margin: 0;
}

.logo-page {
    width: 100%;
}

.logo-content {
    width: 100%;
    display: flex;
    justify-content: center;
}

.logo-container {
    width: 400px;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    box-sizing: border-box;
}

.logo-container img {
    width: 100%;
    height: auto;
    border-radius: 8px;
}

.upload-overlay {
  display: none;
  position: absolute;
  top: 10px;
  left: 50%;
  width: 400px;
  height: 390px;
  transform: translateX(-52%);
  background-color: rgba(255, 255, 255, 0.7);
  border-radius: 8px;
  cursor: pointer;
  justify-content: center;
  align-items: center;
}

.upload-overlay img {
  width: 220px;
  height: 220px;
  object-fit: contain;
}

.product-name {
  font-size: 18px;
  margin-top: 10px;
  color: #333;
}

.product-price {
  font-size: 16px;
  margin-top: 5px;
  color: #d22e2e;
}

.imagehome {
  width: 350px;
  height: 350px;
  display: block;
  margin-right: auto;
  width: 50%;
}

.submit-btn {
  background-color: #d9393d;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 5px;
  cursor: pointer;
  margin-top: 15px;
  transition: background-color 0.3s;
}

.submit-btn:hover {
  background-color: #e0b99d;
  color: #d9393d;
}

.top-header {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  margin-bottom: 20px;
  min-height: 60px;
}

/* Back button */
.back-button {
    position: absolute;
    left: 15px;
    color: #d22e2e;
    text-decoration: none;
    font-size: 18px;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 5px;
}

.back-button:hover {
    background-color: #f1f1f1;
}

/* Center title */
.top-header h1 {
    margin: 0;
    color: #d22e2e;
}

/* Keeps the header balanced */
.header-spacer {
    width: 100px;
}
  </style>
</head>


<body>
<div class="logo-page">
    <div class="top-header">
        <a href="Administrator.php?page=customer.php" class="back-button">&larr; Back</a>
        <h1>Logo Settings</h1>
    </div>
    <div class="logo-content">
        <div class="logo-container">
            <img id="product-image" src="<?php echo $logoPath; ?>" alt="Sakamoto Logo">
            <div class="upload-overlay">
                <label for="file-input" style="cursor: pointer;">
                    <img src="upload.png" alt="Upload">
                </label>
                <input id="file-input" type="file" name="logo_image"
                       style="display: none;" onchange="handleFileSelect(event)">
            </div>
            <button id="edit-btn" type="button" class="submit-btn" onclick="toggleEditSave(true)">Edit Image</button>
            <button id="save-btn" type="button" class="submit-btn" style="display: none;" onclick="saveImage()">Save Image</button>
        </div>
    </div>
</div>

  <script>
    let selectedFile = null;

    function toggleEditSave(editing) {
      const editBtn = document.getElementById('edit-btn');
      const saveBtn = document.getElementById('save-btn');
      const uploadOverlay = document.querySelector('.upload-overlay');

      if (editing) {
        editBtn.style.display = 'none';
        saveBtn.style.display = 'inline-block';
        uploadOverlay.style.display = 'flex';
      } else {
        editBtn.style.display = 'inline-block';
        saveBtn.style.display = 'none';
        uploadOverlay.style.display = 'none';
      }
    }

    function handleFileSelect(event) {
      selectedFile = event.target.files[0]; // Store the selected file
      if (selectedFile) {
        const reader = new FileReader();
        reader.onload = function(e) {
          // Update the product image preview
          const productImage = document.querySelector('#product-image');
          productImage.src = e.target.result;
        };
        reader.readAsDataURL(selectedFile);
      }
    }

    function saveImage() {
      if (!selectedFile) {
			// If no new image is selected, exit edit mode without uploading
			toggleEditSave(false);
			alert('No new image uploaded. Keeping the current logo.');
			return;
		  }

      const formData = new FormData();
      formData.append('logo_image', selectedFile);

      fetch('logo.php', {
        method: 'POST',
        body: formData,
      })
        .then((response) => response.text())
        .then((data) => {
          console.log(data);
          alert('File uploaded'); // Display success or error message
          toggleEditSave(false); // Switch back to Edit mode after saving
        })
        .catch((error) => {
          console.error('Error uploading file:', error);
        });
    }
  </script>
</body>
</html>

