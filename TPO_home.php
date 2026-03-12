<?php
session_start();
include 'db.php'; // DB connection

// Fetch distinct programs and years from DB
$prog_result = $conn->query("SELECT DISTINCT prog FROM STUDENTS ORDER BY prog");
$year_result = $conn->query("SELECT DISTINCT year FROM STUDENTS ORDER BY year");

// Default filters (first available if not set)
$default_prog = $prog_result->fetch_assoc()['prog'] ?? 'B.TECH';
$prog_result->data_seek(0); // reset pointer
$default_year = $year_result->fetch_assoc()['year'] ?? 22;
$year_result->data_seek(0);

// Get selected filter values
$prog = isset($_GET['prog']) ? $_GET['prog'] : $default_prog;
$year = isset($_GET['year']) ? $_GET['year'] : $default_year;

// Count students with filters
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM STUDENTS WHERE prog = ? AND year = ?");
$stmt->bind_param("si", $prog, $year);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$total_students = $row['total'];
?>
<!DOCTYPE html>
<html>
<head>
    <title>TPO Home</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<style>
    /* Small filter box at top-right for desktop */
    .filter-box {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 240px;
        background: #fff;
        border: 1px solid #ddd;
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        font-size: 14px;
        z-index: 1000;
    }
    .filter-box select {
        font-size: 13px;
        padding: 5px 8px;
        margin-bottom: 5px;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .filter-box {
            position: static;
            width: 100%;
            margin-bottom: 15px;
            box-shadow: none;
            border: 1px solid #ccc;
        }
        .filter-box select {
            width: 100%;
        }
        .row.mt-5 {
            margin-top: 10px !important;
        }
    }
</style>

</head>
<body class="p-4">
    <div class="container">
        <h2 class="mb-4">TPO Dashboard</h2>

        <!-- Filter Form (top right, direct) -->
        <form method="GET" class="filter-box">
            <div>
                <label class="form-label mb-1">Program</label>
                <select name="prog" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php while($row = $prog_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['prog']; ?>" <?php if($prog == $row['prog']) echo "selected"; ?>>
                            <?php echo $row['prog']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="form-label mb-1">Year</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php while($row = $year_result->fetch_assoc()): ?>
                        <option value="<?php echo $row['year']; ?>" <?php if($year == $row['year']) echo "selected"; ?>>
                            <?php echo $row['year']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </form>
        
        <div class="row mt-5">
            <!-- Total Students Card -->
            <div class="col-md-4">
                <div class="card text-center shadow" style="cursor:pointer;" 
                     onclick="window.location.href='TPO_students.php?prog=<?php echo urlencode($prog); ?>&year=<?php echo urlencode($year); ?>'">
                    <div class="card-body">
                        <h5 class="card-title">Total Students (<?php echo $prog; ?> - <?php echo $year; ?>)</h5>
                        <h2 class="card-text"><?php echo $total_students; ?></h2>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
</html>
