<?php
include 'db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "ID,HTNO,Name,TeamID,Paid_TF,Paid_OT,Paid_Bus,Paid_Hos,Paid_Old,Paid_Mess,Pay_Date,ReceiptNo,Method,Created_At\n";

$query = "
    SELECT 
        p.id, p.htno, p.name, p.teamid, 
        p.paid_tf, p.paid_ot, p.paid_bus, p.paid_hos, p.paid_old, p.paid_mess, 
        p.pay_date, p.receiptno, p.method, p.created_at
    FROM PAYMENTS p
    INNER JOIN STUDENTS s ON p.htno = s.htno
    WHERE s.year = 25
    ORDER BY p.pay_date DESC
";

$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo implode(',', array_map(function($v) {
            return '"' . str_replace('"', '""', $v) . '"';
        }, $row)) . "\n";
    }
} else {
    echo "No records found for year 25\n";
}
?>
