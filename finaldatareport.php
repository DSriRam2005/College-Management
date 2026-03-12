<?php
// cpto_report.php
session_start();
include 'db.php'; // your database connection

// Check if user is logged in and role is CPTO
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: index.php");
    exit();
}

// Get CPTO's classid
$cp_classid = $_SESSION['classid'];

// Fetch distinct team IDs in this class ordered by teamid
$teams_sql = "SELECT DISTINCT teamid FROM STUDENTS WHERE classid = ? ORDER BY teamid ASC";
$teams_stmt = $conn->prepare($teams_sql);
$teams_stmt->bind_param("s", $cp_classid);
$teams_stmt->execute();
$teams_result = $teams_stmt->get_result();
$teams = [];
while ($row = $teams_result->fetch_assoc()) {
    $teams[] = $row['teamid'];
}
$teams_stmt->close();

// Helper function to display value or empty space if NULL
function display_value($value) {
    return isset($value) && $value !== null ? htmlspecialchars($value) : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CPTO Report</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
    table { border-collapse: collapse; width: 100%; background: #fff; margin-bottom: 40px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    h3 { margin-top: 40px; }
    .wrong-email { color: red; font-weight: bold; }
</style>
</head>
<body>

<h2>CPTO Report - Class: <?php echo display_value($cp_classid); ?></h2>

<?php
if (count($teams) > 0):
    foreach ($teams as $teamid):
        // Fetch students for this team ordered by HTNO
        $sql = "
        SELECT 
            S.htno AS HTNO,
            S.name AS NAME,
            S.email AS EMAIL,
            P.tcs_ctdt_id AS `TCS CodeVita ID`,
            P.gate_app_id AS `GATE App ID`,
            P.emailverifys AS EMAIL_STATUS,
            CASE 
                WHEN P.infosys_verified = 1 THEN 'YES'
                ELSE 'NO'
            END AS `INFOSES VERIFIED`
        FROM STUDENTS S
        LEFT JOIN placements P ON S.htno = P.htno
        WHERE S.classid = ? AND S.teamid = ?
        ORDER BY S.htno ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $cp_classid, $teamid);
        $stmt->execute();
        $result = $stmt->get_result();
        ?>

        <h3>Team: <?php echo display_value($teamid); ?></h3>
        <table>
            <thead>
                <tr>
                    <th>HTNO</th>
                    <th>NAME</th>
                    <th>TCS CodeVita ID</th>
                    <th>GATE App ID</th>
                    <th>Email</th>
                    <th>INFOSES VERIFIED</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo display_value($row['HTNO']); ?></td>
                            <td><?php echo display_value($row['NAME']); ?></td>
                            <td><?php echo display_value($row['TCS CodeVita ID']); ?></td>
                            <td><?php echo display_value($row['GATE App ID']); ?></td>
                            <td>
                                <?php
                                if ($row['EMAIL_STATUS'] === 'correct') {
                                    echo display_value($row['EMAIL']); // Show actual email
                                } elseif ($row['EMAIL_STATUS'] === 'wrong') {
                                    echo '<span class="wrong-email">WRONG</span>'; // Show WRONG
                                } else {
                                    echo ''; // blank if NULL or undefined
                                }
                                ?>
                            </td>
                            <td><?php echo display_value($row['INFOSES VERIFIED']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php
        $stmt->close();
    endforeach;
else:
    echo "<p>No teams found for this class.</p>";
endif;
?>

</body>
</html>

<?php
$conn->close();
?>
