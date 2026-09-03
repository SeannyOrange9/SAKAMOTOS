<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$uploadDir = '../uploads/slideshow_file/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['slideshow_file']) && $_FILES['slideshow_file']['error'] === UPLOAD_ERR_OK && isset($_POST['slideshow_code'])) {
    $slideshow_code = $_POST['slideshow_code']; // Get the slideshow_code from the form

    // Ensure the upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmpPath = $_FILES['slideshow_file']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['slideshow_file']['name'], PATHINFO_EXTENSION));
    $newFileName = 'slideshow_' . uniqid() . '.' . $fileExtension;
    $destPath = $uploadDir . $newFileName;

    // Move uploaded file to the target directory
    if (move_uploaded_file($fileTmpPath, $destPath)) {
        // Save the new image path in the database
        $stmt = $conn->prepare("UPDATE slideshow_table SET slideshow_file=? WHERE slideshow_code=?");
        $stmt->bind_param('ss', $destPath, $slideshow_code);

        if ($stmt->execute()) {
            // Redirect to the same page to avoid resubmission
            echo '<meta http-equiv="refresh" content="0;url=Administrator.php?page=slideshow.php" />';
			exit();
        } else {
            echo "Database update error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error moving uploaded file.";
    }
}

$sql = "SELECT slideshow_code, slideshow_file FROM slideshow_table";
$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
* {
    font-family: 'Sophia', sans-serif;
    box-sizing: border-box;
}

body {
    margin: 0;
}

.content-created {
    padding: 20px;
}

h1 {
    color: #d22e2e;
    margin-bottom: 40px;
}

h2 {
    color: #d22e2e;
    margin-bottom: 10px;
}

.intersection {
    margin-bottom: 40px;
}

.intersection-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* SLIDESHOW CARD */

.slideshow-card {
    position: relative;
    width: 1100px;
    height: 500px;
    margin: 0 auto 20px auto;
    border: 1px solid #F0EAD6;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    background-color: #FBFCF8;
}

/* MAIN SLIDESHOW IMAGE */
.slideshow-image {
    width: 1020px;
    height: 361px;
    object-fit: contain;
    display: block;
    margin: 0 auto;
}

/* UPLOAD OVERLAY */
.upload-button-overlay {
    position: absolute;
    top: 10px;
    left: 50%;
    width: 1020px;
    height: 361px;
    transform: translateX(-50%);
    background-color: rgba(255, 255, 255, 0.7);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 10;
}

/* UPLOAD FORM */
.upload-button-overlay form {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Upload icon clickable area */
.upload-button-overlay label {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 220px;
    height: 220px;
    cursor: pointer;
    z-index: 20;
}

.upload-button-overlay label img {
    width: 220px;
    height: 220px;
    object-fit: contain;
    cursor: pointer;
}

    /* Hide actual file input */
.upload-button-overlay input[type="file"] {
    display: none;
}

/* PREVIEW IMAGE */
.upload-preview {
    position: absolute;
    top: 0;
    left: 0;
    width: 1020px;
    height: 361px;
    object-fit: contain;
    z-index: 15;
    display: none;
    pointer-events: none;
}

/* EDIT / SAVE BUTTON */

.edit-save-btn {
    background-color: #d9393d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    transform: translateY(100%);
    transition: background-color 0.3s;
}

 .edit-save-btn:hover {
    background-color: #e0b99d;
    color: #d9393d;
}

/* TOP HEADER */
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

/* Keeps header balanced */
    .header-spacer {
      width: 100px;
}
</style>
<link rel="icon" href="Sakamoto's.png" type="image/x-icon">
</head>


<body>
<div class="top-header">
    <!-- Back button on the left -->
    <a href="Administrator.php?page=customer.php" class="back-button">&larr; Back</a>
    <!-- Title in the center -->
    <h1>Slideshow Settings</h1>
    <!-- Empty space on the right -->
    <div class="header-spacer"></div>
</div>


<div class="content-created">
  <div class="intersection">
    <div class="slideshow-container">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php foreach ($result as $row): ?>
          <div class="slideshow-card">
            <!-- CURRENT SLIDESHOW IMAGE -->
            <img id="product-image-<?php echo htmlspecialchars($row['slideshow_code']); ?>" class="slideshow-image" src="<?php echo htmlspecialchars($row['slideshow_file']); ?>" alt="Sakamoto Slideshow">

            <!-- UPLOAD OVERLAY -->
            <div id="upload-overlay-<?php echo htmlspecialchars($row['slideshow_code']); ?>" class="upload-button-overlay">
              <!-- Upload form -->
              <form method="POST" enctype="multipart/form-data" id="form-<?php echo htmlspecialchars($row['slideshow_code']); ?>">
                <!-- Upload icon -->
                <label for="file-input-<?php echo htmlspecialchars($row['slideshow_code']); ?>"><img src="upload.png" alt="Upload"></label>

                <!-- Actual file input -->
                <input type="file" name="slideshow_file" id="file-input-<?php echo htmlspecialchars($row['slideshow_code']); ?>" accept="image/*" onchange="previewImage('<?php echo htmlspecialchars($row['slideshow_code']); ?>')">
                <!-- Slideshow code -->
                <input type="hidden" name="slideshow_code" value="<?php echo htmlspecialchars($row['slideshow_code']); ?>">
              </form>

              <!-- IMAGE PREVIEW -->
              <img id="preview-<?php echo htmlspecialchars($row['slideshow_code']); ?>" class="upload-preview" alt="Preview Image">
            </div>
            <!-- EDIT / SAVE BUTTON -->
            <button id="edit-btn-<?php echo htmlspecialchars($row['slideshow_code']); ?>" type="button" class="edit-save-btn" onclick="toggleEditSaveButtons('<?php echo htmlspecialchars($row['slideshow_code']); ?>')">Edit Image</button>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No slideshow images available.</p>
      <?php endif; ?>
    </div>
  </div>
</div>


<script>
/* EDIT IMAGE / SAVE IMAGE */

function toggleEditSaveButtons(slideshowCode) {
    var editButton = document.getElementById('edit-btn-' + slideshowCode);
    var uploadOverlay = document.getElementById('upload-overlay-' + slideshowCode);
    var preview = document.getElementById('preview-' + slideshowCode);
    var form = document.getElementById('form-' + slideshowCode);
    var input = document.getElementById('file-input-' + slideshowCode);

    /* EDIT MODE */
    if (editButton.innerText === 'Edit Image') {
        editButton.innerText = 'Save Image';

        /* Show upload overlay */
        uploadOverlay.style.display = 'flex';

        /* Hide preview until an image is selected */
        preview.style.display = 'none';
    }

    /* SAVE MODE */
    else {
        /* Check if an image was selected */

        if (input.files.length > 0) {

          /* Submit the form. This sends: $_FILES['slideshow_file'] and $_POST['slideshow_code'] to PHP. */
            form.submit();

        }

        else {

            /* No image selected. Return to normal mode.*/

            editButton.innerText = 'Edit Image';
            uploadOverlay.style.display = 'none';
            preview.style.display = 'none';
        }
    }
}


/* PREVIEW SELECTED IMAGE */
function previewImage(slideshowCode) {
    var input = document.getElementById('file-input-' + slideshowCode);
    var preview = document.getElementById('preview-' + slideshowCode);

    var file = input.files[0];

    if (file) {

        /* Make sure the selected file is an image */
        if (!file.type.startsWith('image/')) {

            alert('Please select an image file.');

            input.value = '';

            return;
        }

        /* Create preview */
        var reader = new FileReader();

        reader.onload = function(e) {

            preview.src = e.target.result;

            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>



