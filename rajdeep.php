<?php
// ================== DB Connection ==================
$conn = new mysqli(
    "p3nlmysql19plsk.secureserver.net:3306",
    "tiger",
    "g41K1@u6o",
    "kiet_admissions"
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ================== Function to Run Query ==================
function runBusQuery($conn, $sql) {
    // ✅ Restrict only to BUSREGFNL table
    if (stripos($sql, "BUSREGFNL") === false) {
        die("<p style='color:red;'>❌ Error: Only BUSREGFNL table queries are allowed.</p>");
    }

    $result = $conn->query($sql);

    if ($result === false) {
        die("<p style='color:red;'>❌ Query Error: " . $conn->error . "</p>");
    }

    return $result;
}

// ================== Handle Form Submission ==================
$output = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && !empty($_POST['query'])) {
    $sql = trim($_POST['query']);
    $result = runBusQuery($conn, $sql);

    if ($result instanceof mysqli_result) {
        // SELECT query → show table
        $output .= "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>";
        $output .= "<tr style='background:#ddd;'>";
        foreach ($result->fetch_fields() as $field) {
            $output .= "<th>" . htmlspecialchars($field->name) . "</th>";
        }
        $output .= "</tr>";

        while ($row = $result->fetch_assoc()) {
            $output .= "<tr>";
            foreach ($row as $value) {
                $output .= "<td>" . htmlspecialchars($value) . "</td>";
            }
            $output .= "</tr>";
        }
        $output .= "</table>";
    } else {
        // INSERT/UPDATE/DELETE → show success message
        $output .= "<p style='color:green;'>✅ Query executed successfully. Rows affected: " . $conn->affected_rows . "</p>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>BUSREGFNL Query Runner</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        textarea { width: 100%; height: 100px; font-family: monospace; font-size: 14px; }
        button { padding: 10px 20px; font-size: 14px; cursor: pointer; margin-top: 10px; }
        table { margin-top: 20px; width: 100%; }
        th, td { padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h2>🚍 BUSREGFNL Query Runner</h2>
    <form method="POST">
        <label for="query">Enter SQL Query (only for BUSREGFNL):</label><br>
        <textarea name="query" id="query" required><?php echo htmlspecialchars($_POST['query'] ?? "SELECT * FROM BUSREGFNL LIMIT 10"); ?></textarea><br>
        <button type="submit">Run Query</button>
    </form>

    <div style="margin-top:20px;">
        <?php echo $output; ?>
    </div>
</body>
</html>
