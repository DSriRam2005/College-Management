<?php
session_start();
if (!isset($_SESSION['bus_id'])) {
    header("Location: hostelbus.php");
    exit();
}
include 'db.php';

$busname = $_SESSION['busname'];

// Fetch registered students for this bus
$stmt = $conn->prepare("SELECT id, htno, name, ticketno, seatno, timestamp 
                        FROM BUSREG 
                        WHERE busname = ? 
                        ORDER BY timestamp ASC");
$stmt->bind_param("s", $busname);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bus Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 10px;
            background: #fafafa;
        }

        h2, h3 {
            text-align: center;
        }

        a {
            display: inline-block;
            margin: 10px 0;
            text-decoration: none;
            color: white;
            background: #007BFF;
            padding: 8px 14px;
            border-radius: 6px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            font-size: 14px;
        }

        th {
            background: #f8f8f8;
        }

        button {
            padding: 8px 14px;
            background: green;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:disabled {
            background: gray;
            cursor: not-allowed;
        }

        /* ✅ Mobile Responsive */
        @media screen and (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
                width: 100%;
            }

            thead {
                display: none; /* Hide headers */
            }

            tr {
                margin-bottom: 15px;
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 10px;
                background: #fff;
            }

            td {
                border: none;
                text-align: left;
                font-size: 14px;
                padding: 6px 0;
            }

            td:before {
                content: attr(data-label);
                font-weight: bold;
                display: block;
                margin-bottom: 3px;
                color: #333;
            }

            button {
                width: 100%; /* Full-width button for touch */
                padding: 12px;
                font-size: 15px;
            }

            a {
                display: block;
                text-align: center;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($busname); ?> 🚍</h2>
    <p style="text-align:center;">You are now logged in.</p>
    <div style="text-align:center;">
        <a href="hostelbus.php">Logout</a>
    </div>

    <h3>Registered Students</h3>
    <table>
        <thead>
            <tr>
                <th>Hall Ticket No</th>
                <th>Name</th>
                <th>Ticket No</th>
                <th>Seat No</th>
                <th>Registered At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td data-label="Hall Ticket No"><?php echo htmlspecialchars($row['htno']); ?></td>
                <td data-label="Name"><?php echo htmlspecialchars($row['name']); ?></td>
                <td data-label="Ticket No"><?php echo htmlspecialchars($row['ticketno']); ?></td>
                <td data-label="Seat No"><?php echo htmlspecialchars($row['seatno']); ?></td>
                <td data-label="Registered At"><?php echo htmlspecialchars($row['timestamp']); ?></td>
                <td data-label="Action">
                    <?php if (empty($row['seatno'])) { ?>
                        <form method="post" action="assign_seat.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit">Paid ✅</button>
                        </form>
                    <?php } else { ?>
                        <button disabled>Seat Assigned</button>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</body>
</html>
