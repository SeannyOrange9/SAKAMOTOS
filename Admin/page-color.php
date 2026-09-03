<?php
session_start();

$configFile = 'theme_config.php';


/* Default theme colors */
$defaultConfig = [
    'background_color' => '#f5f5f5',
    'primary_color'    => '#d9393d',
    'secondary_color'  => '#ffffff'
];


/* Load existing configuration */
if (file_exists($configFile)) {

    $config = include $configFile;

    if (!is_array($config)) {
        $config = $defaultConfig;
    }

} else {

    $config = $defaultConfig;
}


/* Save colors */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_colors'])) {

    $colorKeys = array_keys($defaultConfig);

    foreach ($colorKeys as $key) {

        if (isset($_POST[$key])) {

            $value = $_POST[$key];

            /* Accept only hexadecimal colors */
            if (preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                $config[$key] = $value;
            }
        }
    }


    /* Create theme_config.php */
    $configData = "<?php\n\nreturn [\n";

    foreach ($config as $key => $value) {
        $configData .= "    '" . $key . "' => '" . $value . "',\n";
    }

    $configData .= "];\n";


    /* Save configuration*/
    file_put_contents($configFile, $configData);


    /* Store success message */
    $_SESSION['color_saved'] = true;


    /* Prevent form resubmission */
    header("Location: Administrator.php?page=page-color.php");
    exit;
}


/* Check whether colors were successfully saved */
$showSavedAlert = false;

if (isset($_SESSION['color_saved']) && $_SESSION['color_saved'] === true) {

    $showSavedAlert = true;

    unset($_SESSION['color_saved']);
}


/* Reload current configuration */
if (file_exists($configFile)) {

    $config = include $configFile;

    if (!is_array($config)) {
        $config = $defaultConfig;
    }

} else {

    $config = $defaultConfig;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
* {
    box-sizing: border-box;
}

body {
	margin: 0;
	background-color: #ffffff;
}

h1 {
	color: #d22e2e;
	margin-bottom: 40px;
	text-align:center;
}

h2 {
	color: #d22e2e;
    margin-bottom: 10px;
    margin-left: 50px;
}

.subsection {
    margin-bottom: 40px;
}

.subsection-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}


/* PAGE HEADER */
.page-header {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    padding: 20px 30px;
}

.page-header h1 {
    margin: 0;
    text-align: center;
}

.color-container {
    width: 100%;
    max-width: 1100px;
    margin: 0 auto 40px auto;
    padding: 0 20px;
}

.color-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 15px;
}

.color-card {
    background-color: #ffffff;
    border-radius: 10px;
    padding: 18px;
    min-height: 160px;
    min-width: 0;
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.10);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    overflow: hidden;
    transition: transform 0.2s ease,
                box-shadow 0.2s ease;
}

.color-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 16px rgba(0, 0, 0, 0.15);
}

/* CARD TITLE */
.color-card h3 {
    color: #d22e2e;
    margin: 0 0 8px 0;
	font-size: 18px;
}

/* CARD DESCRIPTION */
.color-card p {
	color: #666666;
	margin: 0 0 20px 0;
	font-size: 14px;
	line-height: 1.4;
}

/* COLOR PICKER */
.color-picker {
	width: 80px;
    height: 50px;
    padding: 2px;
    border: 1px solid #cccccc;
    border-radius: 6px;
	background-color: #ffffff;
    cursor: pointer;
}

/* SAVE BUTTON */
.button-container {
	display: flex;
    justify-content: flex-end;
	margin-top: 25px;
}

.save-button {
	background-color: #d9393d;
	color: #ffffff;
	border: none;
	padding: 12px 25px;
	border-radius: 6px;
	cursor: pointer;
	font-size: 15px;
}

.save-button:hover {
    background-color: #b9272b;
}

/* BACK BUTTON */
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

/* TABLET */
@media (max-width: 1000px) {
	.color-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 600px) {
    .color-grid {
        grid-template-columns: 1fr;
	}

	.color-container {
        width: 100%;
        padding: 0 15px;
    }

    .page-header {
        padding: 15px;
    }

    .page-header h1 {
        font-size: 24px;
    }
}

/* MOBILE */
@media (max-width: 550px) {
	.color-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
    	padding: 15px;
    }

    .page-header h1 {
    	font-size: 24px;
    }

    .color-container {
    	width: 92%;
    }
}
</style>
</head>
<body>
    <!-- PAGE HEADER -->
    <div class="page-header">
        <!-- Back button on the left -->
    <a href="Administrator.php?page=customer.php" class="back-button">&larr; Back</a>
        <h1>Page Color Settings</h1>
        <!-- Keeps the title centered -->
        <div style="width: 90px;"></div>
    </div>

<div class="color-container">
    <form method="POST">
        <div class="color-grid">
            <!-- BACKGROUND COLOR -->
            <div class="color-card">
                <h3>Background Color</h3>
                <p> Overall background of the customer pages. </p>
                <input type="color" class="color-picker" name="background_color" value="<?= htmlspecialchars($config['background_color']) ?>"></div>
                <!-- PRIMARY COLOR -->
            <div class="color-card">
                <h3>Primary Color</h3>
                <p> Main brand color used for navigation, buttons, headings, and other primary elements.</p>
                <input type="color" class="color-picker" name="primary_color" value="<?= htmlspecialchars($config['primary_color']) ?>">
            </div>

            <!-- SECONDARY COLOR -->
            <div class="color-card">
                <h3>Secondary Color</h3>
                <p> Secondary color used for contrast, such as button text and light backgrounds.</p>
                <input type="color" class="color-picker" name="secondary_color" value="<?= htmlspecialchars($config['secondary_color']) ?>">
            </div>
        </div>
        <!-- SAVE BUTTON -->
        <div class="button-container">
            <button type="submit" name="change_colors" class="save-button"> Save Colors </button></div>
    </form>
</div>


    <!-- SUCCESS ALERT -->
    <?php if ($showSavedAlert): ?>
        <script>
            alert("Colors have been successfully saved.");
        </script>
    <?php endif; ?>
</body>
</html>