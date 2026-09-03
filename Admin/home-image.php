<?php

$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "sakamoto";

$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, 3306);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


/* UPDATE IMAGE / DESCRIPTION
   ========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['home_image_code'])) {

        $backgroundCode = $_POST['home_image_code'];
        $description = $_POST['home_image_description'] ?? '';


        /*
         * Check if a new image was uploaded
         */
        if (
            isset($_FILES['file']) &&
            $_FILES['file']['error'] === UPLOAD_ERR_OK
        ) {

            $file = $_FILES['file'];

            $newFileName = $_POST['filename'] ?? '';

            // Directory to save the uploaded file
            $uploadDir = '../uploads/home_image/';

            $filePath = $uploadDir . basename($newFileName);


            /*
             * Move uploaded file
             */
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {

                echo "Failed to upload file.";
                exit;
            }


            /*
             * UPDATE BOTH IMAGE AND DESCRIPTION
             */
            $stmt = $conn->prepare("
                UPDATE home_image_table
                SET home_image = ?,
                    home_image_description = ?
                WHERE home_image_code = ?
            ");


            if (!$stmt) {

                echo "Prepare failed: " . $conn->error;
                exit;
            }


            $stmt->bind_param(
                "sss",
                $filePath,
                $description,
                $backgroundCode
            );

        } else {

            /*
             * NO NEW IMAGE
             *
             * Update description only.
             */
            $stmt = $conn->prepare("
                UPDATE home_image_table
                SET home_image_description = ?
                WHERE home_image_code = ?
            ");


            if (!$stmt) {

                echo "Prepare failed: " . $conn->error;
                exit;
            }


            $stmt->bind_param(
                "ss",
                $description,
                $backgroundCode
            );
        }


        /*
         * Execute UPDATE
         */
        if ($stmt->execute()) {

            echo "Updated successfully.";

        } else {

            echo "Database update failed: " . $stmt->error;
        }


        $stmt->close();

        exit;
    }


    echo "Missing home_image_code.";

    exit;
}



/* FETCH HOME IMAGES */

$sql = "
    SELECT
        home_image,
        home_image_code,
        home_image_description
    FROM home_image_table
";

$result = $conn->query($sql);

$backgroundImages = [];


if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $backgroundImages[] = [

            'image' => $row['home_image'],

            'code' => $row['home_image_code'],

            'description' => $row['home_image_description']

        ];
    }
}


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

/* BACKGROUND CARD */

.background-card {
    width: 30%;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    position: relative;
    box-sizing: border-box;
}

.background-card .background-image {
    width: 100%;
    height: auto;
    border-radius: 8px;
    display: block;
}

/* DESCRIPTION */
.background-description-input {
    width: 95%;
    min-height: 60px;
    margin-top: 12px;
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 5px;
    resize: vertical;
    box-sizing: border-box;
    font-size: 14px;
    background-color: #f5f5f5;
    color: #555;
}

/* Description while editing */
.background-description-input:not(:disabled) {
    background-color: white;
    border: 1px solid #d22e2e;
    color: #333;
    outline: none;
}

/* EDIT / SAVE BUTTON */
.back-submit-btn {
    background-color: #d9393d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-top: 15px;
    transition: background-color 0.3s;
}

.back-submit-btn:hover {
    background-color: #e0b99d;
    color: #d9393d;
}

/* CONTENT */
.background-content {
    padding: 20px;
}

.background-subsection {
    margin-bottom: 40px;
}

/* BACKGROUND CONTAINER */
.background-container {
    display: flex;
    justify-content: center;
    flex-grow: 1;
    gap: 100px;
}

/* UPLOAD OVERLAY */

.background-upload-overlay {
    display: none;
    position: absolute;
    top: 10px;
    left: 10px;
    width: calc(100% - 20px);
    height: 305px;
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 8px;
    cursor: pointer;
    justify-content: center;
    align-items: center;
    z-index: 5;
}

.background-upload-overlay img {
    width: 220px;
    height: 220px;
    object-fit: contain;
    transform: none;
}

/* HEADER */
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
</head>
<body>
<!-- HEADER -->
    <div class="top-header">
        <!-- Back button -->
        <a href="Administrator.php?page=customer.php" class="back-button">&larr; Back </a>
        <!-- Title -->
        <h1> Home Image Settings</h1>
        <!-- Empty space -->
        <div class="header-spacer"></div>
    </div>
    <!-- CONTENT -->
    <div class="background-content">
        <div class="background-subsection">
            <div class="background-container">
                <?php foreach ($backgroundImages as $background): ?>
                    <!-- BACKGROUND CARD -->
                    <div class="background-card" data-background-code="<?php echo htmlspecialchars($background['code'],ENT_QUOTES,'UTF-8'); ?>" >
                        <!-- IMAGE -->
                        <img src="<?php echo htmlspecialchars( $background['image'], ENT_QUOTES,'UTF-8');?>" alt="Home Image" class="background-image">
                        <!-- DESCRIPTION -->
                        <textarea class="background-description-input" disabled><?php echo htmlspecialchars( $background['description'], ENT_QUOTES, 'UTF-8');?></textarea>
                        <!-- UPLOAD OVERLAY -->
                        <div class="background-upload-overlay">
                            <label for="file-background-input-<?php echo htmlspecialchars($background['code'], ENT_QUOTES, 'UTF-8');?>" style="cursor: pointer;">
                            <img src="upload.png" alt="Upload"></label>
                            <input id="file-background-input-<?php echo htmlspecialchars($background['code'], ENT_QUOTES, 'UTF-8' );?>" type="file" name="home_image[]" accept="image/*" style="display: none;" onchange="handleBackgroundSelect(event)" >
                        </div>
                        <!-- EDIT / SAVE BUTTON -->
                        <button type="button" class="back-submit-btn" onclick="toggleEditingSaving(this)">Edit Image</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

<script>
/* IMAGE PREVIEW */

function handleBackgroundSelect(event) {

            const file = event.target.files[0];


            if (file) {

                const reader = new FileReader();


                reader.onload = function (e) {


                    const backgroundCard =
                        event.target.closest(
                            '.background-card'
                        );


                    const imgElement =
                        backgroundCard.querySelector(
                            '.background-image'
                        );


                    /*
                     * Display the selected image immediately
                     * before saving.
                     */

                    imgElement.src = e.target.result;

                };


                reader.readAsDataURL(file);
            }
        }



    /* EDIT / SAVE */
    function toggleEditingSaving(button) {


            const backgroundCard =
                button.closest(
                    '.background-card'
                );


            const overlay =
                backgroundCard.querySelector(
                    '.background-upload-overlay'
                );


            const imgElement =
                backgroundCard.querySelector(
                    '.background-image'
                );


            const fileInput =
                backgroundCard.querySelector(
                    'input[type="file"]'
                );


            const descriptionInput =
                backgroundCard.querySelector(
                    '.background-description-input'
                );


            /* Get the actual home_image_code from the data attribute. */

            const backgroundCode =
                backgroundCard.dataset.backgroundCode;



            /* EDIT MODE */

            if (
                button.textContent.trim()
                ===
                "Edit Image"
            ) {


                button.textContent = "Save Image";


                /* Show image upload overlay */

                overlay.style.display = "flex";


                /* Enable description editing */

                descriptionInput.disabled = false;


                /* Place cursor inside description */

                descriptionInput.focus();


                return;
            }



            /* SAVE MODE */

            if (
                button.textContent.trim()
                ===
                "Save Image"
            ) {


                const file =
                    fileInput.files[0];


                const description =
                    descriptionInput.value;



                /* CREATE FORM DATA */

                const formData =
                    new FormData();



                /* ALWAYS send home_image_code */

                formData.append(
                    "home_image_code",
                    backgroundCode
                );



                /* ALWAYS send description */

                formData.append(
                    "home_image_description",
                    description
                );



                /* IF NEW IMAGE WAS SELECTED */

                if (file) {


                    const newFileName =
                        `background_${Date.now()}_${file.name}`;



                    /* Send actual image */

                    formData.append(
                        "file",
                        file
                    );



                    /* Send new filename */

                    formData.append(
                        "filename",
                        newFileName
                    );


                }



                /* SEND TO PHP */

                fetch(
                    'home-image.php',
                    {
                        method: 'POST',
                        body: formData
                    }
                )


                .then(
                    response => response.text()
                )


                .then(
                    data => {


                        console.log(
                            "Server response:",
                            data
                        );


                        /* If update succeeded, reload the page. */

                        if (
                            data.includes(
                                "Updated successfully."
                            )
                        ) {
                            alert("Image successfully updated");
                            location.reload();

                        } else {

                            alert(data);

                        }

                    }
                )


                .catch(
                    error => {

                        console.error(
                            "Error updating image:",
                            error
                        );


                        alert(
                            "An error occurred while updating."
                        );

                    }
                );



                /* RETURN TO VIEW MODE */

                button.textContent =
                    "Edit Image";


                overlay.style.display =
                    "none";


                descriptionInput.disabled =
                    true;

            }

        }

</script>
</body>
</html>