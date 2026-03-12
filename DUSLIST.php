<?php
// Include the database connection file
require_once 'db.php';

// Check if a specific bus name is requested
if (isset($_GET['busname']) && !empty($_GET['busname'])) {
    // --- View for a specific bus's students ---
    $busname = urldecode($_GET['busname']);

    // SQL query to get student details for the selected bus
    // Use a prepared statement to prevent SQL injection
    $sql = "SELECT htno, name, ticketno, seatno
            FROM BUSREG
            WHERE busname = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $busname);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_students = $result->num_rows; // Get the total count of students
    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Students for <?php echo htmlspecialchars($busname); ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                color: #333;
                margin: 0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .container {
                background-color: #fff;
                padding: 20px 40px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 90%;
                max-width: 800px;
                margin-top: 20px;
            }
            h2 {
                color: #0056b3;
                text-align: center;
                margin-bottom: 20px;
            }
            .total-count {
                text-align: center;
                font-size: 1.2em;
                font-weight: bold;
                margin-bottom: 20px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #007bff;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            tr:hover {
                background-color: #e9ecef;
            }
            a {
                color: #007bff;
                text-decoration: none;
                font-weight: bold;
            }
            a:hover {
                text-decoration: underline;
            }
            .back-link {
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Students Registered for <?php echo htmlspecialchars($busname); ?></h2>
            <p class="total-count">Total Students: <?php echo $total_students; ?></p>
            <table>
                <tr>
                    <th>Hall Ticket No</th>
                    <th>Name</th>
                    <th>Ticket No</th>
                    <th>Seat No</th>
                </tr>
                <?php
                if ($total_students > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["htno"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["ticketno"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["seatno"]) . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No students registered for this bus.</td></tr>";
                }
                ?>
            </table>
            <div class="back-link">
                <a href="DUSLIST.php">Back to Bus Counts</a>
            </div>
        </div>
    </body>
    </html>

    <?php
    // Close the prepared statement and database connection
    $stmt->close();
    $conn->close();
} else {
    // --- Main view showing all bus counts ---
    
    // SQL query to get bus names and registered student counts
    $sql = "SELECT B.BUSNAME, COUNT(R.id) AS `Registered Students`
            FROM DBUSES B
            LEFT JOIN BUSREG R ON B.BUSNAME = R.busname
            GROUP BY B.BUSNAME";

    $result = $conn->query($sql);
    
    // Calculate the total number of registered students across all buses
    $total_all_students_query = "SELECT COUNT(id) AS total_students FROM BUSREG";
    $total_result = $conn->query($total_all_students_query);
    $total_row = $total_result->fetch_assoc();
    $total_all_students = $total_row['total_students'];

    ?>

    <!DOCTYPE html>
    <html>
    <head>
        <title>Bus Registration Counts</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f4f4;
                color: #333;
                margin: 0;
                padding: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .container {
                background-color: #fff;
                padding: 20px 40px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                width: 90%;
                max-width: 800px;
                margin-top: 20px;
            }
            h2 {
                color: #0056b3;
                text-align: center;
                margin-bottom: 20px;
            }
            .total-count {
                text-align: center;
                font-size: 1.2em;
                font-weight: bold;
                margin-bottom: 20px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            }
            th, td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            th {
                background-color: #007bff;
                color: white;
                font-weight: bold;
            }
            tr:nth-child(even) {
                background-color: #f2f2f2;
            }
            tr:hover {
                background-color: #e9ecef;
            }
            a {
                color: #007bff;
                text-decoration: none;
                font-weight: bold;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Bus Registration Summary</h2>
            <p class="total-count">Total Registered Students: <?php echo $total_all_students; ?></p>
            <table>
                <tr>
                    <th>Bus Name</th>
                    <th>Registered Students</th>
                </tr>
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["BUSNAME"]). "</td>";
                        echo "<td><a href='DUSLIST.php?busname=" . urlencode($row["BUSNAME"]) . "'>" . htmlspecialchars($row["Registered Students"]) . "</a></td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>0 results</td></tr>";
                }
                ?>
            </table>
        </div>
    </body>
    </html>

    <?php
    // Close the database connection
    $conn->close();
}
?>