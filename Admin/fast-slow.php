<?php 
// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'sakamoto';

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


/*
|--------------------------------------------------------------------------
| FAST / SLOW MOVING ANALYSIS
|--------------------------------------------------------------------------
| Analysis period: Last 30 days
|
| Sales Velocity = Total Quantity Sold / 30 Days
|
| Fast = Above or equal to the average sales velocity
| Slow = Below the average sales velocity
|--------------------------------------------------------------------------
*/

$analysis_days = 30;


/*
|--------------------------------------------------------------------------
| PRODUCT MOVEMENT
|--------------------------------------------------------------------------
*/

$product_query = "
SELECT 
    p.product_name,
    SUM(h.order_quantity) AS total_sales,
    (SUM(h.order_quantity) / $analysis_days) AS sales_velocity
FROM history_table h
JOIN product_table p 
    ON h.order_product_name = p.product_name
WHERE h.is_cancelled = 'ORDER'
AND h.order_date >= DATE_SUB(CURDATE(), INTERVAL $analysis_days DAY)
GROUP BY p.product_name
ORDER BY sales_velocity DESC
";

$product_result = $conn->query($product_query);

$products = [];

if ($product_result) {
    while ($row = $product_result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Calculate average product sales velocity
$product_average = 0;

if (count($products) > 0) {
    $product_total_velocity = 0;

    foreach ($products as $row) {
        $product_total_velocity += (float)$row['sales_velocity'];
    }

    $product_average = $product_total_velocity / count($products);
}


/* CUP SIZE MOVEMENT */

$cups_query = "
SELECT 
    c.cup_type,
    SUM(h.order_quantity) AS total_sales,
    (SUM(h.order_quantity) / $analysis_days) AS sales_velocity
FROM history_table h
JOIN cups_table c 
    ON h.order_cup_size = c.cup_type
WHERE h.is_cancelled = 'ORDER'
AND h.order_date >= DATE_SUB(CURDATE(), INTERVAL $analysis_days DAY)
GROUP BY c.cup_type
ORDER BY sales_velocity DESC
";

$cups_result = $conn->query($cups_query);

$cups = [];

if ($cups_result) {
    while ($row = $cups_result->fetch_assoc()) {
        $cups[] = $row;
    }
}

// Calculate average cup-size sales velocity
$cups_average = 0;

if (count($cups) > 0) {
    $cups_total_velocity = 0;

    foreach ($cups as $row) {
        $cups_total_velocity += (float)$row['sales_velocity'];
    }

    $cups_average = $cups_total_velocity / count($cups);
}


/* FLAVOR MOVEMENT */

$flavor_query = "
SELECT 
    f.flavor_name,
    SUM(h.order_quantity) AS total_sales,
    (SUM(h.order_quantity) / $analysis_days) AS sales_velocity
FROM history_table h
JOIN flavor_table f 
    ON h.order_flavor = f.flavor_name
WHERE h.is_cancelled = 'ORDER'
AND h.order_date >= DATE_SUB(CURDATE(), INTERVAL $analysis_days DAY)
GROUP BY f.flavor_name
ORDER BY sales_velocity DESC
";

$flavor_result = $conn->query($flavor_query);

$flavors = [];

if ($flavor_result) {
    while ($row = $flavor_result->fetch_assoc()) {
        $flavors[] = $row;
    }
}

// Calculate average flavor sales velocity
$flavor_average = 0;

if (count($flavors) > 0) {
    $flavor_total_velocity = 0;

    foreach ($flavors as $row) {
        $flavor_total_velocity += (float)$row['sales_velocity'];
    }

    $flavor_average = $flavor_total_velocity / count($flavors);
}


/* ADD-ON MOVEMENT */

$addon_query = "
SELECT 
    a.add_on_name,
    SUM(h.order_quantity) AS total_sales,
    (SUM(h.order_quantity) / $analysis_days) AS sales_velocity
FROM history_table h
JOIN add_on_table a 
    ON h.order_add_on = a.add_on_name
WHERE h.is_cancelled = 'ORDER'
AND h.order_date >= DATE_SUB(CURDATE(), INTERVAL $analysis_days DAY)
GROUP BY a.add_on_name
ORDER BY sales_velocity DESC
";

$addon_result = $conn->query($addon_query);

$addons = [];

if ($addon_result) {
    while ($row = $addon_result->fetch_assoc()) {
        $addons[] = $row;
    }
}

// Calculate average add-on sales velocity
$addon_average = 0;

if (count($addons) > 0) {
    $addon_total_velocity = 0;

    foreach ($addons as $row) {
        $addon_total_velocity += (float)$row['sales_velocity'];
    }

    $addon_average = $addon_total_velocity / count($addons);
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
<div class="page-header"><h1>Fast/Slow Moving Items</h1></div>


<!-- PRODUCT MOVEMENT TABLE -->
<div class="subsection" id="product-movement">
<h2>Fast/Slow Moving Product</h2>
<table>
    <thead>
      <tr>
        <th>Product</th>
        <th>Quantity Sold (30 Days)</th>
        <th>Sales/Day</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($products) > 0): ?>
        <?php foreach ($products as $row): ?>
          <?php
          $velocity = (float)$row['sales_velocity'];
          $status = ($velocity >= $product_average)
              ? 'Fast'
              : 'Slow';
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($row['product_name']) ?>
            </td>
            <td>
              <?= htmlspecialchars($row['total_sales']) ?>
            </td>
            <td>
              <?= number_format($velocity, 2) ?>
            </td>
            <td>
              <?= $status ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4">No product sales recorded within the last 30 days.</td>
        </tr>
      <?php endif; ?>
    </tbody>
</table>
</div>


<!-- CUP SIZE MOVEMENT TABLE -->

<div class="subsection" id="cup-size-movement">
<h2>Fast/Slow Moving Cup Size</h2>
<table>
    <thead>
      <tr>
        <th>Cup-Size</th>
        <th>Quantity Sold (30 Days)</th>
        <th>Sales/Day</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($cups) > 0): ?>
        <?php foreach ($cups as $row): ?>
          <?php
          $velocity = (float)$row['sales_velocity'];
          $status = ($velocity >= $cups_average)
              ? 'Fast'
              : 'Slow';
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($row['cup_type']) ?>
            </td>
            <td>
              <?= htmlspecialchars($row['total_sales']) ?>
            </td>
            <td>
              <?= number_format($velocity, 2) ?>
            </td>
            <td>
              <?= $status ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4">No cup-size sales recorded within the last 30 days.</td>
        </tr>
      <?php endif; ?>
    </tbody>
</table>
</div>


<!-- FLAVOR MOVEMENT TABLE -->

<div class="subsection" id="flavor-movement">
<h2>Fast/Slow Moving Flavor</h2>
<table>
    <thead>
      <tr>
        <th>Flavor</th>
        <th>Quantity Sold (30 Days)</th>
        <th>Sales/Day</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($flavors) > 0): ?>
        <?php foreach ($flavors as $row): ?>
          <?php
          $velocity = (float)$row['sales_velocity'];
          $status = ($velocity >= $flavor_average)
              ? 'Fast'
              : 'Slow';
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($row['flavor_name']) ?>
            </td>
            <td>
              <?= htmlspecialchars($row['total_sales']) ?>
            </td>
            <td>
              <?= number_format($velocity, 2) ?>
            </td>
            <td>
              <?= $status ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="4">No flavor sales recorded within the last 30 days.</td>
        </tr>
      <?php endif; ?>
    </tbody>
</table>
</div>


<!-- ADD-ON MOVEMENT TABLE -->
<div class="subsection" id="addons-movement">
<h2>Fast/Slow Moving Add-ons</h2>
<table>
    <thead>
      <tr>
        <th>Add-On</th>
        <th>Quantity Sold (30 Days)</th>
        <th>Sales/Day</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php if (count($addons) > 0): ?>
        <?php foreach ($addons as $row): ?>
          <?php
          $velocity = (float)$row['sales_velocity'];
          $status = ($velocity >= $addon_average)
              ? 'Fast'
              : 'Slow';
          ?>
          <tr>
            <td>
              <?= htmlspecialchars($row['add_on_name']) ?>
            </td>
            <td>
              <?= htmlspecialchars($row['total_sales']) ?>
            </td>
            <td>
              <?= number_format($velocity, 2) ?>
            </td>
            <td>
              <?= $status ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr> <td colspan="4">No add-on sales recorded within the last 30 days.</td></tr>
      <?php endif; ?>
    </tbody>
</table>
</div>
</body>
</html>