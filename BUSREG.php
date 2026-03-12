<?php
// Set timezone to India/Kolkata
date_default_timezone_set("Asia/Kolkata");

// ==================== DB1 Connection ====================
$db1_server   = "sql211.infinityfree.com";
$db1_username = "if0_39689452";
$db1_password = "0JaTuFZVF3U0L";
$db1_name     = "if0_39689452_12_9";

$db1 = new mysqli($db1_server, $db1_username, $db1_password, $db1_name);
if ($db1->connect_error) {
    die("DB1 Connection failed: " . $db1->connect_error);
}

// ==================== DB2 Connection ====================
$db2_server   = "sql313.infinityfree.com";
$db2_username = "if0_39723681";
$db2_password = "0ckajZsehKrdkW8";
$db2_name     = "if0_39723681_btech2025";

$db2 = new mysqli($db2_server, $db2_username, $db2_password, $db2_name);
if ($db2->connect_error) {
    die("DB2 Connection failed: " . $db2->connect_error);
}

// ==================== DB3 Connection ====================
$db3_server   = "p3nlmysql19plsk.secureserver.net";
$db3_username = "tiger";
$db3_password = "g41K1@u6o";
$db3_name     = "kiet_admissions";

$db3 = new mysqli($db3_server, $db3_username, $db3_password, $db3_name, 3306);
if ($db3->connect_error) {
    die("DB3 Connection failed: " . $db3->connect_error);
}


$message = "";
$student = null;
$buses   = [];
$ticket  = null;  // holds ticket record (existing or new)

// Fetch buses from DB1
$res = $db1->query("SELECT * FROM DBUSES ORDER BY BUSNAME");
while ($row = $res->fetch_assoc()) {
    $buses[] = $row;
}

// ✅ Handle Search
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_htno'])) {
    $htno = trim($_POST['search_htno']);

    // Search in DB1
    $stmt1 = $db1->prepare("SELECT htno AS idno, name FROM STUDENTS WHERE htno=?");
    $stmt1->bind_param("s", $htno);
    $stmt1->execute();
    $r1 = $stmt1->get_result()->fetch_assoc();

    // Search in DB2
    $stmt2 = $db2->prepare("SELECT HT_NO AS idno, name FROM students WHERE HT_NO=?");
    $stmt2->bind_param("s", $htno);
    $stmt2->execute();
    $r2 = $stmt2->get_result()->fetch_assoc();

    if ($r1) $student = $r1;
    elseif ($r2) $student = $r2;
    else $message = "❌ Student not found in either DB.";

    // If student exists, check BUSREG
    if ($student) {
        $stmt = $db1->prepare("SELECT htno,name,busname,ticketno,seatno,timestamp 
                               FROM BUSREG WHERE htno=?");
        $stmt->bind_param("s", $htno);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        if ($existing) {
            $ticket = $existing;
        }
    }
}

// ✅ Handle Registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $htno    = $_POST['htno'];
    $name    = $_POST['name'];
    $busname = $_POST['busname'];

    // Check if already registered
    $stmt = $db1->prepare("SELECT * FROM BUSREG WHERE htno=?");
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $already = $stmt->get_result()->fetch_assoc();

    if ($already) {
        $ticket = $already;
        $message = "⚠️ Already registered. Here is existing ticket.";
    } else {
        // Next ticket number per bus
        $stmt = $db1->prepare("SELECT COUNT(*)+1 AS nextno FROM BUSREG WHERE busname=?");
        $stmt->bind_param("s", $busname);
        $stmt->execute();
        $nextno = $stmt->get_result()->fetch_assoc()['nextno'];

        // ✅ Apply rule: 1–45 numeric, after that W1, W2, ...
        if ($nextno <= 45) {
            $ticketno = (string)$nextno;
        } else {
            $ticketno = "W" . ($nextno - 45);
        }

        $seatno   = ""; // seat allocation later
        $ts       = date("Y-m-d H:i:s");

        // Insert into BUSREG (DB1)
        $stmt_ins = $db1->prepare(
            "INSERT INTO BUSREG (htno,name,busname,ticketno,seatno,timestamp) 
             VALUES (?,?,?,?,?,?)"
        );
        $stmt_ins->bind_param("ssssss", $htno, $name, $busname, $ticketno, $seatno, $ts);

        if ($stmt_ins->execute()) {
            // ✅ Also insert into BUSREGFNL (DB3)
            $stmt3 = $db3->prepare(
                "INSERT INTO BUSREGFNL (htno,name,busname,ticketno,seatno,timestamp) 
                 VALUES (?,?,?,?,?,?)"
            );
            $stmt3->bind_param("ssssss", $htno, $name, $busname, $ticketno, $seatno, $ts);
            $stmt3->execute(); // we don't break flow if this fails

            $ticket = [
                "htno"     => $htno,
                "name"     => $name,
                "busname"  => $busname,
                "ticketno" => $ticketno,
                "seatno"   => $seatno,
                "timestamp"=> $ts
            ];
            $message = "✅ Registered successfully!";
        } else {
            $message = "❌ Error during registration: " . $db1->error;
        }
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dussehra Bus Registration & Ticket Slip</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
/* Mobile First Styles (default) */
body {
  padding: 15px;
  background: linear-gradient(135deg, #f8f9fa, #e8f0fe);
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  color: #333;
  overflow-x: hidden;
}

h2 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0d6efd;
  text-align: center;
  margin-bottom: 20px;
}

.card, .ticket-slip {
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  border: none;
  width: 100%;          /* ✅ always fit screen width */
  max-width: 100%;      /* ✅ remove desktop restriction */
  margin: 0 auto 20px;
}

.ticket-slip {
  border: 2px dashed #0d6efd;
  padding: 20px;
}

.ticket-slip h4 {
  color: #0d6efd;
  margin-bottom: 15px;
  font-weight: 700;
  text-align: center;
}

.btn {
  border-radius: 25px;
  padding: 10px 20px;
  font-weight: 600;
  transition: transform 0.2s, box-shadow 0.2s;
  font-size: 1rem;
  width: 100%;          /* ✅ make buttons full width on mobile */
  max-width: 300px;     /* still neat on desktop */
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

form input, form select {
  border-radius: 10px !important;
  border: 1px solid #ccc;
  padding: 10px;
  font-size: 1rem;
  width: 100%;          /* ✅ inputs fill screen */
}

.alert {
  border-radius: 10px;
  font-weight: 500;
  text-align: center;
  font-size: 0.95rem;
  width: 100%;
  margin: 0 auto 20px;
}

/* Search form */
form.search-form {
  width: 100%;
  margin: auto;
}

form.d-flex {
  flex-wrap: wrap;
  gap: 10px;
}

/* Specific Mobile Styles */
@media (max-width: 576px) {
  body { padding: 10px; }

  h2 {
    font-size: 1.3rem;
    margin-bottom: 15px;
  }

  .btn {
    font-size: 0.9rem;
    padding: 8px 15px;
    width: 100%;   /* ✅ full width buttons on mobile */
  }

  .ticket-slip p {
    font-size: 0.95rem;
  }
}

/* Print Styles */
@media print {
  body * { visibility: hidden; }
  #printable, #printable * { visibility: visible; }
  #printable { position: absolute; left: 0; top: 0; width: 100%; }
}

</style>
</head>
<body class="container mt-4">
    <h2>🎟️ Dussehra Bus Registration & Ticket Slip</h2>

    <!-- Search Form -->
    <form method="post" class="search-form mb-4 d-flex justify-content-center">
        <input type="text" name="search_htno" placeholder="Enter HTNO" class="form-control me-2" required>
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <!-- Status Message -->
    <?php if ($message): ?>
        <div class="alert alert-info mx-auto mb-4" style="max-width:500px;">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Registration Form -->
    <?php if ($student && !$ticket): ?>
        <div class="card p-3 mb-4">
            <h5 class="text-center mb-3">Student Found</h5>
            <p><b>HTNO:</b> <?= htmlspecialchars($student['idno']) ?><br>
               <b>Name:</b> <?= htmlspecialchars($student['name']) ?></p>
            <form method="post">
                <input type="hidden" name="htno" value="<?= htmlspecialchars($student['idno']) ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars($student['name']) ?>">
                <label class="form-label">Select Bus:</label>
                <select name="busname" class="form-select mb-3" required>
                    <option value="">-- Select --</option>
                    <?php foreach ($buses as $bus): ?>
                        <option value="<?= htmlspecialchars($bus['BUSNAME']) ?>">
                            <?= htmlspecialchars($bus['BUSNAME']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="register" class="btn btn-success w-100">Register</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Ticket Slip -->
    <?php if ($ticket): ?>
        <div id="printable" class="ticket-slip card mt-4">
            <h4>Bus Ticket Slip</h4>
            <p><strong>HTNO:</strong> <?= htmlspecialchars($ticket['htno']) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($ticket['name']) ?></p>
            <p><strong>Bus:</strong> <?= htmlspecialchars($ticket['busname']) ?></p>
            <p><strong>Ticket No:</strong> <?= htmlspecialchars($ticket['ticketno']) ?></p>
            <p><strong>Seat No:</strong> <?= $ticket['seatno'] ? htmlspecialchars($ticket['seatno']) : "To be allocated" ?></p>
            <p><strong>Registered At:</strong> <?= htmlspecialchars($ticket['timestamp']) ?></p>
            <div class="text-center mt-3">
                <button onclick="window.print();" class="btn btn-primary">Print Ticket</button>
            </div>
        </div>
    <?php endif; ?>
</body>
</html>
