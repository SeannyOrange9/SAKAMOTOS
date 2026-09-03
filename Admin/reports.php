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

session_start();


// SALES DATA FOR CHARTS
$sql = "SELECT
            h.order_product_name,
            p.category_code,
            SUM(h.order_quantity) AS product_sales,
            SUM(h.order_total_price) AS total_sales,
            c.category_name
        FROM history_table h
        JOIN product_table p
            ON h.order_product_name = p.product_name
        JOIN category_table c
            ON p.category_code = c.category_code
        WHERE h.is_cancelled = 'ORDER'
        GROUP BY
            h.order_product_name,
            p.category_code,
            c.category_name
        ORDER BY product_sales DESC";

$result = $conn->query($sql);


// Prepare data for bar and pie charts
$product_sales = [];
$category_sales = [];
$product_totals = [];

while ($row = $result->fetch_assoc()) {

    $product_sales[] = [
        'product_name' => $row['order_product_name'],
        'sales' => $row['product_sales'],
        'total_sales' => $row['total_sales']
    ];

    if (!isset($category_sales[$row['category_name']])) {
        $category_sales[$row['category_name']] = 0;
    }

    $category_sales[$row['category_name']] += $row['product_sales'];

    $product_totals[$row['order_product_name']] = $row['total_sales'];
}

$barLabels = array_column($product_sales, 'product_name');
$barData = array_column($product_sales, 'sales');
$barTotalSales = array_column($product_sales, 'total_sales');

$pieLabels = array_keys($category_sales);
$pieData = array_values($category_sales);


// TOP 10 SALES PERFORMANCE REPORT
$report_sql = "SELECT
                    h.order_product_name,
                    SUM(h.order_quantity) AS quantity_sold,
                    SUM(h.order_total_price) AS total_revenue
               FROM history_table h
               WHERE h.is_cancelled = 'ORDER'
               GROUP BY h.order_product_name
               ORDER BY quantity_sold DESC
               LIMIT 10";

$report_result = $conn->query($report_sql);

$top_products = [];

if ($report_result) {

    while ($row = $report_result->fetch_assoc()) {

        $top_products[] = $row;

    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="tableformat-9.css">
<style>
.page-header {
    justify-content: center;
}

.page-header h1 {
    color: #d22e2e;
    text-align: center;
}

/* GRAPH LAYOUT */
.graphs-container {

    display: flex;
    gap: 30px;
    margin: 40px 50px;
    align-items: stretch;

}

.graph-box {
    flex: 1;
    min-width: 0;
}

.graph-box h2 {
    color: #d22e2e;
    margin: 0 0 15px 0;
}

/* BAR CHART */
.chart-bar {
    height: 400px;
    margin-left: 0;
    margin-right: 0;
}

/* PIE CHART */
.chart-pie {
    position: relative;
    width: 100%;
    height: 400px;
    padding-bottom: 0;
}

.chart-pie canvas {
    position: absolute;
    width: 100%;
    height: 100%;
}

/* BUTTON CONTAINER */
.pdf-button-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 20px;
    margin-bottom: 40px;
}

/* PDF BUTTON */
.pdf-btn {
    padding: 10px 30px;
    background-color: #d22e2e;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

.pdf-btn:hover {
    background-color: #b52222;
}

/* CSV BUTTON */
.csv-btn {
    padding: 10px 30px;
    background-color: #2e7d32;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.csv-btn:hover {
    background-color: #256628;
}

/* SALES PERFORMANCE REPORT */
.sales-report {
    margin-left: 50px;
    margin-right: 50px;
    margin-top: 30px;
}

.sales-report h2 {
    color: #d22e2e;
    margin-bottom: 15px;
}

/* DIVIDER BETWEEN TABLE AND GRAPHS */
.report-divider {
    margin: 50px 50px 40px 50px;
    border: none;
    border-top: 2px solid #d22e2e;
}

/* HIDE BUTTONS WHEN PRINTING */
@media print {
    .pdf-button-container {
        display: none !important;
        }
}

/* RESPONSIVE GRAPH LAYOUT */
@media (max-width: 800px) {
    .graphs-container {
        flex-direction: column;
        margin-left: 30px;
        margin-right: 30px;
        }
}
</style>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<!-- PAGE HEADER -->
    <div class="page-header">
        <h1>Reports</h1>
    </div>
    <!-- SALES PERFORMANCE REPORT -->
    <div class="sales-report">
        <h2>Sales Performance Report</h2>
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Product</th>
                    <th>Quantity Sold</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($top_products) > 0): ?>
                    <?php $rank = 1;?>
                    <?php foreach ($top_products as $row): ?>
                        <tr>
                            <td> <?= $rank ?></td>
                            <td>
                                <?= htmlspecialchars(
                                    $row['order_product_name']
                                ) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars(
                                    $row['quantity_sold']
                                ) ?>
                            </td>
                            <td>
                                ₱<?= number_format(
                                    (float)$row['total_revenue'],
                                    2
                                ) ?>
                            </td>
                        </tr>
                        <?php $rank++;?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">
                            No sales records available.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <!-- TABLE PDF AND CSV BUTTONS -->
        <div class="pdf-button-container">
            <!-- PDF -->
            <button type="button" class="pdf-btn" onclick="printReportAsPDF()">PDF</button>
            <!-- CSV -->
            <button type="button" class="csv-btn" onclick="exportSalesCSV()">CSV</button>
        </div>
    </div>

    <!-- DIVIDER -->
    <hr class="report-divider">
    <!-- TWO GRAPH LAYOUT -->
    <div class="graphs-container">
        <!-- GRAPH 1 - SALES OF PRODUCT -->
        <div class="graph-box">
            <h2>Sales of Product</h2>
            <div class="chart-bar">
                <canvas id="barChart"></canvas>
            </div>

            <!-- PDF BUTTON -->
            <div class="pdf-button-container">
                <button type="button" class="pdf-btn" onclick="printChartAsPDF('barChart', 'Sales of Product' )">PDF</button>
            </div>
        </div>

        <!-- GRAPH 2 - CATEGORY REPORT -->
        <div class="graph-box">
            <h2>Category Report</h2>
            <div class="chart-pie">
                <canvas id="pieChart"></canvas>
            </div>

            <!-- PDF BUTTON -->
            <div class="pdf-button-container">
                <button type="button" class="pdf-btn" onclick="printChartAsPDF('pieChart', 'Category Report')">PDF</button>
            </div>
        </div>
    </div>


    <!-- ALL JAVASCRIPT -->
    <script>
        /* BAR CHART */
        var ctxBar =
            document
                .getElementById('barChart')
                .getContext('2d');

        var barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels:
                    <?php echo json_encode($barLabels);?>,
                datasets: [{
                    label: 'Total Sales Value',
                    data: <?php echo json_encode($barTotalSales);?>,
                    backgroundColor: '#0000FF',
                    borderColor: '#0000FF',
                    borderWidth: 1
                }]
            },


            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });


        /* PIE CHART */
        var ctxPie =
            document
                .getElementById('pieChart')
                .getContext('2d');


        var pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels:
                    <?php echo json_encode($pieLabels);?>,
                datasets: [{
                    data: <?php echo json_encode($pieData);?>,

                    backgroundColor: [
                        '#FF0000',
                        '#0000FF',
                        '#FFFF00',
                        '#00FF00',
                        '#FF00FF'
                    ],


                    hoverBackgroundColor: [
                        '#FF6347',
                        '#1E90FF',
                        '#FFD700',
                        '#32CD32',
                        '#FF1493'
                    ]
                }]
            },


            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        /* PRINT CHART AS PDF */

        function printChartAsPDF(chartId, chartTitle) {
            // Get selected chart
            const canvas = document.getElementById(chartId);

            if (!canvas) {
                alert( 'Chart could not be found.');
                return;
            }

            // Convert chart into image
            const chartImage = canvas.toDataURL('image/png');

            // Open new window
            const printWindow = window.open('', '_blank');

            if (!printWindow) {
                alert( 'Unable to open the PDF window. Please allow pop-ups for this website.');
                return;
            }

            // Create printable document
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title> ${chartTitle} - PDF</title>
                    <style>
                        body {
                            margin: 0;
                            padding: 40px;
                            text-align: center;
                            font-family: Arial, sans-serif;
                            background: white;
                        }

                        h1 {
                            margin-bottom: 30px;
                            color: #d22e2e;
                        }

                        img {
                            max-width: 100%;
                            height: auto;
                        }

                        @media print {
                            body {
                                padding: 20px;
                            }
                        }
                    </style>
                </head>

                <body>
                    <h1>
                        ${chartTitle}
                    </h1>
                    <img src="${chartImage}" alt="${chartTitle}">
                </body>
                </html>
            `);

            // Finish writing
            printWindow.document.close();

            // Print after loading
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
        }

        /* PRINT SALES PERFORMANCE REPORT AS PDF */
        function printReportAsPDF() {

            // Get table
            const reportTable =
                document.querySelector(
                    '.sales-report table'
                );


            if (!reportTable) {
                alert('Sales performance table could not be found.');
                return;
            }


            // Get table HTML
            const tableHTML = reportTable.outerHTML;

            // Open new window
            const printWindow = window.open('', '_blank');

            if (!printWindow) {
                alert('Unable to open the PDF window. Please allow pop-ups for this website.');
                return;
            }


            // Create printable document
            printWindow.document.write(`
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title> Sales Performance Report - PDF</title>
                    <style>
                        body {
                            margin: 0;
                            padding: 40px;
                            font-family: Arial, sans-serif;
                            background: white;
                            color: #000;
                        }
                        h1 {
                            text-align: center;
                            color: #d22e2e;
                            margin-bottom: 30px;
                        }

                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 20px;
                        }

                        th {
                            background-color: #d22e2e;
                            color: white;
                            padding: 10px;
                            text-align: left;
                            border: 1px solid #d22e2e;
                        }

                        td {
                            padding: 10px;
                            border: 1px solid #ddd;
                            text-align: left;
                        }

                        @media print {
                            body {
                                padding: 20px;
                            }

                            table {
                                page-break-inside: auto;
                            }

                            tr {
                                page-break-inside: avoid;
                                page-break-after: auto;
                            }
                        }
                    </style>
                </head>

                <body>
                    <h1>
                        Sales Performance Report
                    </h1>
                    ${tableHTML}
                </body>
                </html>
            `);

            // Finish writing
            printWindow.document.close();


            // Print
            printWindow.onload = function() {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            };
        }

        /* EXPORT SALES PERFORMANCE REPORT AS CSV */
        function exportSalesCSV() {

            // Get the sales report table
            const table =
                document.querySelector(
                    '.sales-report table'
                );

            if (!table) {
                alert('Sales performance table could not be found.');
                return;
            }


            // Get all table rows
            const rows = table.querySelectorAll('tr');

            let csv = [];

            // Convert table rows to CSV
            rows.forEach(row => {
                const columns = row.querySelectorAll('th, td');
                let rowData = [];

                columns.forEach(column => {
                    let text =
                        column.innerText
                            .replace(/"/g, '""')
                            .trim();

                    rowData.push(
                        '"' + text + '"'
                    );

                });

                csv.push(
                    rowData.join(',')
                );

            });


            // Create CSV content
            const csvContent =
                csv.join('\n');

            // Create CSV file
            const blob =
                new Blob(
                    [csvContent],
                    {
                        type: 'text/csv;charset=utf-8;'
                    }
                );


            // Create temporary download URL
            const url = URL.createObjectURL(blob);


            // Create download link
            const link =
                document.createElement('a');


            link.href = url;


            link.download = 'sales_performance_report.csv';

            // Trigger download
            document.body.appendChild(link);

            link.click();

            document.body.removeChild(link);


            // Clean up temporary URL
            URL.revokeObjectURL(url);

        }
    </script>
</body>
</html>