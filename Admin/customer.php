<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="tableformat-7.css">
<style>
/* INVENTORY SETTINGS BUTTONS */

.inventory-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    column-gap: 20px;
    row-gap: 20px;
    width: calc(100% - 60px);
    margin-left: 30px;
    margin-top: 15px;
}

.inventory-button {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 250px;
    background-color: #d22e2e;
    color: white;
    text-decoration: none;
    font-size: 50px;
    font-weight: bold;
    border-radius: 8px;
    box-sizing: border-box;
    transition: background-color 0.2s ease;
}

.inventory-button:hover {
    background-color: #b52222;
}

.page-header h1 {
    color: #b52222;
}

.page-header {
    display: flex;
    justify-content: center;
    align-items: center;
}
</style>
</head>
<body>


<div class="inventory-page">
  <div class="page-header">
    <h1>Customer Page Settings</h1>
  </div>
  <div class="inventory-buttons">
    <a href="Administrator.php?page=slideshow.php" class="inventory-button">Slideshow</a>
    <a href="Administrator.php?page=home-image.php" class="inventory-button">Home Image</a>
    <a href="Administrator.php?page=logo.php" class="inventory-button">Logo</a>
    <a href="Administrator.php?page=page-color.php" class="inventory-button">Page Color</a>
  </div>
</div>
</body>
</html>
