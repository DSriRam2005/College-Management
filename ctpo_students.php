<?php
session_start();
include 'db.php';

// ✅ Allow only CPTO role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'CPTO') {
    header("Location: login.php");
    exit();
}

$cp_classid = $_SESSION['classid'];
$year = $_SESSION['year'] ?? null;
$message = "";

// ✅ Handle save
if (isset($_POST['save_all'])) {
    if (!empty($_POST['teamid']) && is_array($_POST['teamid'])) {
        foreach ($_POST['teamid'] as $student_id => $teamid_input) {
            $teamid_input = trim($teamid_input);
            $leadnum = trim($_POST['teamleadno'][$student_id] ?? '');
            $spocnum = trim($_POST['spoc'][$student_id] ?? '');

            // Convert numeric values to prefixed format
            $teamleadno = $leadnum !== '' ? "L" . $leadnum : null;
            $spoc = $spocnum !== '' ? "SPOC" . $spocnum : null;

            if ($teamid_input === "") {
                // Remove all fields if empty
                $up = $conn->prepare("UPDATE STUDENTS SET teamid=NULL, teamleadno=NULL, spoc=NULL WHERE id=?");
                $up->bind_param("i", $student_id);
                $up->execute();
                continue;
            }

            // ✅ Create formatted teamid
            $teamid = $cp_classid . "_" . $teamid_input;

            // ✅ Insert TEAM user if not exists
            $username = $teamid;
            $password = $teamid;
            $role = "TEAM";

            $check = $conn->prepare("SELECT id FROM USERS WHERE username=?");
            $check->bind_param("s", $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO USERS (username, password, role, year, classid, teamid) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("sssiss", $username, $password, $role, $year, $cp_classid, $teamid);
                $stmt->execute();
            }

            // ✅ Update STUDENTS table
            $up = $conn->prepare("UPDATE STUDENTS SET teamid=?, teamleadno=?, spoc=? WHERE id=?");
            $up->bind_param("sssi", $teamid, $teamleadno, $spoc, $student_id);
            $up->execute();
        }

        $message = "<div class='alert alert-success'>✅ All team details saved successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>⚠️ No data entered.</div>";
    }
}

// ✅ Fetch students list ordered by teamid first, then teamleadno
$stmt = $conn->prepare("
    SELECT *,
           CAST(SUBSTRING_INDEX(teamid, '_', -1) AS UNSIGNED) AS teamnum,
           CAST(SUBSTRING(teamleadno, 2) AS UNSIGNED) AS leadnum
    FROM STUDENTS
    WHERE classid=?
      AND (debarred = 0 OR debarred IS NULL)
    ORDER BY
        CASE WHEN teamid IS NULL OR teamid = '' THEN 0 ELSE 1 END DESC,
        teamnum ASC,
        leadnum ASC,
        htno ASC
");
$stmt->bind_param('s', $cp_classid);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Team Assignments</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background: #f8f9fa;
        }
        table input {
            text-align: center;
        }
        .teamDisplay {
            font-weight: 600;
            color: #0d6efd;
        }
    </style>
</head>
<body class="p-4">
    <h3 class="mb-3">Team Assignment – Class: <?= htmlspecialchars($cp_classid) ?></h3>
    <?= $message ?>

    <form method="post">
        <table class="table table-bordered table-sm table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>HT No</th>
                    <th>Name</th>
                    <th>Program</th>
                    <th>Team No</th>
                    <th>Team ID</th>
                    <th>Lead No (only number)</th>
                    <th>SPOC (only number)</th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $sno = 1;
            while($row = $result->fetch_assoc()): 
                $team_no = $row['teamid'] ? str_replace($cp_classid . "_", "", $row['teamid']) : "";
                $lead_no = $row['teamleadno'] ? preg_replace('/^L/', '', $row['teamleadno']) : "";
                $spoc_no = $row['spoc'] ? preg_replace('/^SPOC/', '', $row['spoc']) : "";
            ?>
                <tr>
                    <td><?= $sno++ ?></td>
                    <td><?= htmlspecialchars($row['htno']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['prog']) ?></td>
                    
                    <!-- Team No -->
                    <td><input type="number" name="teamid[<?= $row['id'] ?>]" value="<?= htmlspecialchars($team_no) ?>" class="form-control form-control-sm" style="width:80px;"></td>
                    
                    <!-- Team ID (live preview) -->
                    <td class="teamDisplay"><?= htmlspecialchars($row['teamid']) ?></td>

                    <!-- Team Lead -->
                    <td><input type="number" name="teamleadno[<?= $row['id'] ?>]" value="<?= htmlspecialchars($lead_no) ?>" class="form-control form-control-sm" placeholder=""></td>

                    <!-- SPOC -->
                    <td><input type="number" name="spoc[<?= $row['id'] ?>]" value="<?= htmlspecialchars($spoc_no) ?>" class="form-control form-control-sm" placeholder=""></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <button type="submit" name="save_all" class="btn btn-success">💾 Save All</button>
    </form>

    <!-- ✅ Live TeamID Preview -->
    <script>
    document.querySelectorAll('input[name^="teamid"]').forEach(input => {
        input.addEventListener('input', function() {
            const classid = "<?= $cp_classid ?>";
            const row = this.closest('tr');
            const teamDisplay = row.querySelector('.teamDisplay');
            const num = this.value.trim();
            teamDisplay.textContent = num ? classid + "_" + num : "";
        });
    });
    </script>
</body>
</html>
