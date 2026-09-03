<?php
// Determine the requested page
$page = $_GET['page'] ?? 'home';

// Define the allowed pages
$allowedPages = ['categories', 'cupsizes', 'flavors', 'add-ons'];

// Check if the requested page is allowed, otherwise set it to 'home'
if (!in_array($page, $allowedPages)) {
    $page = 'home';
}
?>

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
    <h1>Inventory Settings</h1>
  </div>

  <div class="inventory-buttons"><a href="Administrator.php?page=categories.php" class="inventory-button">Categories</a>
    <a href="Administrator.php?page=flavors.php" class="inventory-button">Flavors</a>
    <a href="Administrator.php?page=cupsizes.php" class="inventory-button">Cup Sizes</a>
    <a href="Administrator.php?page=add-ons.php" class="inventory-button">Add-ons</a>
  </div>
</div>
</body>
</html>
