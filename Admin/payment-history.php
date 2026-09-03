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

// Query to fetch required data
$sql = "SELECT order_username, DATE(order_date) as order_date, 
            SUM(order_total_price) as total_price, 
            mode_payment
        FROM history_table
        WHERE is_cancelled = 'ORDER'
        GROUP BY order_username, DATE(order_date), mode_payment";

$result = $conn->query($sql);
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
    <h1>Customer Payment History</h1>
</div>

<!-- USERNAME FILTER -->
<div class="category-filter-container" style="max-width: 250px; margin: 0 auto; text-align: center;">
    <label for="username-filter">Filter Username:</label>
    <select id="username-filter" onchange="filterPaymentsByUsername()">
        <option value="ALL">All Users</option>
        <?php
        $usernames = [];

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                if (!in_array($row['order_username'], $usernames)) {
                    $usernames[] = $row['order_username'];
                }
            }

            $result->data_seek(0);
        }
        sort($usernames);
        foreach ($usernames as $username):
        ?>
            <option value="<?php echo htmlspecialchars($username); ?>">
                <?php echo htmlspecialchars($username); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>


<!-- Payment History Table -->
<div class="subsection" id="payment-history">
  <table id="payment-history-table">
    <thead>
      <tr>
        <th>Customer Username</th>
        <th>Payment Date</th>
        <th>Amount</th>
        <th>Mode of Payment</th>
      </tr>
    </thead>
    <tbody id="payment-history-body">
      <?php 
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {

                echo "<tr data-username='" .
                     htmlspecialchars($row['order_username']) . "'>";

                echo "<td>" .
                     htmlspecialchars($row['order_username']) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['order_date']) .
                     "</td>";

                echo "<td>" .
                     number_format($row['total_price'], 2) .
                     "</td>";

                echo "<td>" .
                     htmlspecialchars($row['mode_payment']) .
                     "</td>";

                echo "</tr>";
            }

        } else {

            echo "<tr class='no-record-row'>";
            echo "<td colspan='4'>No records found</td>";
            echo "</tr>";

        }
      ?>
    </tbody>
  </table>
</div>


<!-- EXPORT BUTTONS -->
<div class="export-buttons">
    <button type="button" class="export-btn csv-btn" onclick="exportCSV()">CSV</button>
    <button type="button" class="export-btn pdf-btn" onclick="exportPDF()">PDF</button>
</div>
<script>

/* FILTER BY USERNAME */

function filterPaymentsByUsername() {

    const selectedUsername =
        document.getElementById('username-filter').value;

    const rows =
        document.querySelectorAll('#payment-history-body tr');

    rows.forEach(row => {

        const rowUsername =
            row.getAttribute('data-username');

        // Don't process "No records found"
        if (!rowUsername) {
            return;
        }

        // Show everything
        if (selectedUsername === 'ALL') {

            row.style.display = '';

        }

        // Show selected username
        else if (rowUsername === selectedUsername) {

            row.style.display = '';

        }

        // Hide other usernames
        else {

            row.style.display = 'none';

        }

    });

}


/* EXPORT CSV */

function exportCSV() {

    const table =
        document.getElementById('payment-history-table');

    const rows =
        table.querySelectorAll('tr');

    let csv = [];

    rows.forEach(row => {

        // Skip hidden rows
        if (row.style.display === 'none') {
            return;
        }

        const columns =
            row.querySelectorAll('th, td');

        let rowData = [];

        columns.forEach(column => {

            let text =
                column.innerText
                .replace(/"/g, '""')
                .trim();

            rowData.push('"' + text + '"');

        });

        csv.push(rowData.join(','));

    });

    const csvContent =
        csv.join('\n');

    const blob =
        new Blob([csvContent], {
            type: 'text/csv;charset=utf-8;'
        });

    const url =
        URL.createObjectURL(blob);

    const link =
        document.createElement('a');

    link.href = url;

    link.download =
        'customer-payment-history.csv';

    document.body.appendChild(link);

    link.click();

    document.body.removeChild(link);

    URL.revokeObjectURL(url);

}


/* EXPORT PDF */

function exportPDF() {

    const table =
        document.getElementById('payment-history-table');

    const rows =
        table.querySelectorAll('tr');

    let tableHTML = `
        <table>
            <thead>
                <tr>
                    <th>Customer Username</th>
                    <th>Payment Date</th>
                    <th>Amount</th>
                    <th>Mode of Payment</th>
                </tr>
            </thead>
            <tbody>
    `;

    let visibleRows = 0;

    rows.forEach(row => {

        // Skip the original header row
        if (row.querySelector('th')) {
            return;
        }

        // Skip hidden rows caused by the username filter
        if (row.style.display === 'none') {
            return;
        }

        const columns =
            row.querySelectorAll('td');

        if (columns.length === 0) {
            return;
        }

        tableHTML += '<tr>';

        columns.forEach(column => {

            tableHTML += `
                <td>
                    ${column.innerHTML}
                </td>
            `;

        });

        tableHTML += '</tr>';

        visibleRows++;

    });

    tableHTML += `
            </tbody>
        </table>
    `;


    // If no rows are visible
    if (visibleRows === 0) {

        tableHTML = `
            <table>
                <thead>
                    <tr>
                        <th>Customer Username</th>
                        <th>Payment Date</th>
                        <th>Amount</th>
                        <th>Mode of Payment</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td colspan="4">
                            No records found
                        </td>
                    </tr>
                </tbody>
            </table>
        `;

    }


    // Get currently selected username
    const selectedUsername =
        document.getElementById('username-filter').value;

    let reportTitle =
        'Customer Payment History';

    if (selectedUsername !== 'ALL') {

        reportTitle +=
            ' - ' +
            selectedUsername;

    }


    // Open a NEW window
    const printWindow =
        window.open('', '_blank');


    if (!printWindow) {

        alert(
            'Unable to open the PDF window. Please allow pop-ups for this website.'
        );

        return;

    }


    // Create clean printable document
    printWindow.document.write(`

        <!DOCTYPE html>

        <html lang="en">

        <head>

            <meta charset="UTF-8">

            <meta
                name="viewport"
                content="width=device-width, initial-scale=1.0"
            >

            <title>
                ${reportTitle} - PDF
            </title>


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

                    margin-bottom: 10px;

                }


                .filter-info {

                    text-align: center;

                    margin-bottom: 25px;

                    font-size: 14px;

                    color: #555;

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


                tbody tr:nth-child(even) {

                    background-color: #f5f5f5;

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
                ${reportTitle}
            </h1>


            ${
                selectedUsername !== 'ALL'
                    ? `
                        <div class="filter-info">
                            Filtered Username:
                            <strong>
                                ${selectedUsername}
                            </strong>
                        </div>
                      `
                    : ''
            }


            ${tableHTML}


        </body>

        </html>

    `);


    // Finish writing the document
    printWindow.document.close();


    // Wait for the new document to load
    printWindow.onload = function() {

        printWindow.focus();

        printWindow.print();

        printWindow.close();

    };

}

</script>
</body>
</html>