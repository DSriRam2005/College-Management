<?php
include 'db.php'; // your DB connection file

// ✅ Define the target year
$yearPrefix = '25';

// ✅ Query payments where htno starts with '25'
$sql = "SELECT * FROM PAYMENTS WHERE htno LIKE CONCAT(?, '%') ORDER BY pay_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $yearPrefix);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payments - Year <?php echo htmlspecialchars($yearPrefix); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        h2 {
            color: #333;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: center;
        }
        th {
            background-color: #007BFF;
            color: white;
        }
        tr:nth-child(even) {background-color: #f9f9f9;}
        tr:hover {background-color: #f1f1f1;}
    </style>
</head>
<body>

<h2>Payment Records - Year <?php echo htmlspecialchars($yearPrefix); ?></h2>

<table>
    <tr>
        <th>S.No</th>
        <th>ID</th>
        <th>HTNO</th>
        <th>Name</th>
        <th>Team ID</th>
        <th>TF</th>
        <th>OT</th>
        <th>Bus</th>
        <th>Hostel</th>
        <th>Old</th>
        <th>Mess</th>
        <th>Pay Date</th>
        <th>Receipt No</th>
        <th>Method</th>
        <th>Created At</th>
    </tr>
    <?php
    if ($result->num_rows > 0):
        $sno = 1;
        while ($row = $result->fetch_assoc()):
    ?>
        <tr>
            <td><?php echo $sno++; ?></td>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['htno']); ?></td>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['teamid']); ?></td>
            <td><?php echo $row['paid_tf']; ?></td>
            <td><?php echo $row['paid_ot']; ?></td>
            <td><?php echo $row['paid_bus']; ?></td>
            <td><?php echo $row['paid_hos']; ?></td>
            <td><?php echo $row['paid_old']; ?></td>
            <td><?php echo $row['paid_mess']; ?></td>
            <td><?php echo $row['pay_date']; ?></td>
            <td><?php echo htmlspecialchars($row['receiptno']); ?></td>
            <td><?php echo $row['method']; ?></td>
            <td><?php echo $row['created_at']; ?></td>
        </tr>
    <?php
        endwhile;
    else:
    ?>
        <tr><td colspan="15">No records found for year <?php echo htmlspecialchars($yearPrefix); ?></td></tr>
    <?php endif; ?>
</table>

</body>
</html>
