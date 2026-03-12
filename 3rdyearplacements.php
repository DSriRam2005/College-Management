<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

$message = "";
$student = null;
$placement = null;

// --- Search Student ---
if (isset($_POST['search'])) {
    $htno = $conn->real_escape_string($_POST['htno']);
    $result = $conn->query("SELECT * FROM STUDENTS WHERE htno='$htno'");

    if ($result->num_rows > 0) {
        $student = $result->fetch_assoc();

        // Only allow B.TECH Year 22
        if (strtoupper(trim($student['prog'])) !== 'B.TECH' || $student['year'] != 23) {
            $message = "❌ This portal is only for B.TECH students of year 22.";
            $student = null;
        } else {
            $presult = $conn->query("SELECT * FROM placements WHERE htno='$htno'");
            if ($presult->num_rows > 0) {
                $placement = $presult->fetch_assoc();
            }
        }
    } else {
        $message = "No student found with HT No: $htno";
    }
}

// --- Save Data ---
if (isset($_POST['save'])) {
    $htno = $conn->real_escape_string($_POST['htno']);

    // Fetch current data
    $currentStudent = $conn->query("SELECT * FROM STUDENTS WHERE htno='$htno'")->fetch_assoc();
    $currentPlacement = $conn->query("SELECT * FROM placements WHERE htno='$htno'")->fetch_assoc();

    // Only update empty fields or allow editing email if not verified
    $firstname = !empty($currentStudent['firstname']) ? $currentStudent['firstname'] : $conn->real_escape_string($_POST['firstname']);
    $middlename = !empty($currentStudent['middlename']) ? $currentStudent['middlename'] : $conn->real_escape_string($_POST['middlename']);
    $lastname = !empty($currentStudent['lastname']) ? $currentStudent['lastname'] : $conn->real_escape_string($_POST['lastname']);
    $phone = !empty($currentStudent['phone']) ? $currentStudent['phone'] : $conn->real_escape_string($_POST['phone']);
    $email = (!empty($currentStudent['email']) && !empty($currentPlacement['emailverifys'])) ? $currentStudent['email'] : $conn->real_escape_string($_POST['email']); // read-only if verified

    $emailverifys = !empty($currentPlacement['emailverifys']) ? $currentPlacement['emailverifys'] : $_POST['emailverifys'];
    $gate_applied = !empty($currentPlacement['gate_applied']) ? $currentPlacement['gate_applied'] : $_POST['gate_applied'];
    $gate_app_id = ($gate_applied === 'yes' && empty($currentPlacement['gate_app_id'])) ? $conn->real_escape_string($_POST['gate_app_id']) : $currentPlacement['gate_app_id'];
    $tcs_codevita_reg = !empty($currentPlacement['tcs_codevita_reg']) ? $currentPlacement['tcs_codevita_reg'] : $_POST['tcs_codevita_reg'];
    $tcs_ctdt_id = ($tcs_codevita_reg === 'Yes' && empty($currentPlacement['tcs_ctdt_id'])) ? $conn->real_escape_string($_POST['tcs_ctdt_id']) : $currentPlacement['tcs_ctdt_id'];
    $infosys_verified = isset($currentPlacement['infosys_verified']) && $currentPlacement['infosys_verified']==1 ? 1 : (isset($_POST['infosys_verified']) ? 1 : 0);

    // --- Update student info including email ---
    $updateStudent = $conn->prepare("
        UPDATE STUDENTS 
        SET firstname=?, middlename=?, lastname=?, phone=?, email=? 
        WHERE htno=?
    ");
    $updateStudent->bind_param("ssssss", $firstname, $middlename, $lastname, $phone, $email, $htno);
    $updateStudent->execute();

    // --- Update or insert placement info ---
    if ($currentPlacement) {
        $updatePlacement = $conn->prepare("
            UPDATE placements 
            SET emailverifys=?, gate_applied=?, gate_app_id=?, 
                tcs_codevita_reg=?, tcs_ctdt_id=?, infosys_verified=? 
            WHERE htno=?
        ");
        $updatePlacement->bind_param(
            "sssssis",
            $emailverifys,
            $gate_applied,
            $gate_app_id,
            $tcs_codevita_reg,
            $tcs_ctdt_id,
            $infosys_verified,
            $htno
        );
        $updatePlacement->execute();
    } else {
        $insertPlacement = $conn->prepare("
            INSERT INTO placements 
            (htno, emailverifys, gate_applied, gate_app_id, tcs_codevita_reg, tcs_ctdt_id, infosys_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $insertPlacement->bind_param(
            "ssssssi",
            $htno,
            $emailverifys,
            $gate_applied,
            $gate_app_id,
            $tcs_codevita_reg,
            $tcs_ctdt_id,
            $infosys_verified
        );
        $insertPlacement->execute();
    }

    $message = "✅ Student info updated successfully!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Placement Details Entry</title>
<style>
:root { --primary: #0066cc; --primary-dark: #004999; --bg: #f4f6f9; --text: #333; --success-bg: #d4edda; --success-text: #155724; --error-bg: #f8d7da; --error-text: #721c24; --border: #ddd; }
body { font-family: "Segoe UI", Arial, sans-serif; background: var(--bg); margin: 0; padding: 0; }
.container { max-width: 640px; margin: 50px auto; background: #fff; border-radius: 12px; padding: 35px 40px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); animation: fadeIn 0.5s ease-in; }
h2 { text-align: center; color: var(--primary-dark); margin-bottom: 25px; }
form { display: flex; flex-direction: column; gap: 15px; }
label { font-weight: 600; color: var(--text); }
input[type="text"], input[type="email"], select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 15px; }
input[type="text"]:focus, input[type="email"]:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,123,255,0.15); }
input[readonly], select[disabled] { background-color: #f1f1f1; color: #666; }
input[type="submit"] { background: var(--primary); color: white; border: none; border-radius: 8px; padding: 12px; font-size: 16px; font-weight: 600; cursor: pointer; transition: background 0.3s; }
input[type="submit"]:hover { background: var(--primary-dark); }
.message, .error { text-align: center; font-weight: 600; margin-top: 20px; padding: 12px; border-radius: 8px; }
.message { background: var(--success-bg); color: var(--success-text); }
.error { background: var(--error-bg); color: var(--error-text); }
hr { border: none; border-top: 1px solid var(--border); margin: 25px 0; }
.hidden { display: none; }
input[type="checkbox"] { transform: scale(1.2); margin-right: 8px; }
@keyframes fadeIn { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
@media (max-width: 600px) { .container { margin: 20px; padding: 25px; } }
</style>
<script>
function toggleGate() {
    const gateSelect = document.getElementById('gate_applied');
    const gateDiv = document.getElementById('gate_app_id_div');
    const gateInput = document.getElementById('gate_app_id');
    if (gateSelect.value === 'yes') { gateDiv.style.display='block'; gateInput.required=true; }
    else { gateDiv.style.display='none'; gateInput.required=false; gateInput.value=''; }
}
function toggleTCS() {
    const tcsSelect = document.getElementById('tcs_codevita_reg');
    const tcsDiv = document.getElementById('tcs_ctdt_id_div');
    const tcsInput = document.getElementById('tcs_ctdt_id');
    if (tcsSelect.value === 'Yes') { tcsDiv.style.display='block'; tcsInput.required=true; }
    else { tcsDiv.style.display='none'; tcsInput.required=false; tcsInput.value=''; }
}
</script>
</head>
<body>
<div class="container">
<h2>🎓 Placement Details Entry</h2>

<form method="post">
<label>Enter Hall Ticket Number (HT No):</label>
<input type="text" name="htno" required placeholder="Enter your HT Number">
<input type="submit" name="search" value="Search Student">
</form>

<?php if ($student): ?>
<hr>
<form method="post">
<input type="hidden" name="htno" value="<?= htmlspecialchars($student['htno']) ?>">

<label>HT No:</label>
<input type="text" value="<?= htmlspecialchars($student['htno']) ?>" readonly>

<label>Student Name:</label>
<input type="text" value="<?= htmlspecialchars($student['name']) ?>" readonly>

<label>Email:</label>
<input type="email" name="email" value="<?= htmlspecialchars($student['email'] ?? '') ?>" required <?= !empty($placement['emailverifys']) ? 'readonly' : '' ?>>

<label>Email Verification (for above email):</label>
<select name="emailverifys" required <?= !empty($placement['emailverifys']) ? 'disabled' : '' ?>>
<option value="">-- Select --</option>
<option value="correct" <?= (($placement['emailverifys'] ?? '')==='correct')?'selected':''?>>Correct</option>
<option value="wrong" <?= (($placement['emailverifys'] ?? '')==='wrong')?'selected':''?>>Wrong</option>
</select>

<label>Phone Number:</label>
<input type="text" name="phone" value="<?= htmlspecialchars($student['phone'] ?? '') ?>" <?= !empty($student['phone']) ? 'readonly' : '' ?> required>

<label>First Name:</label>
<input type="text" name="firstname" value="<?= htmlspecialchars($student['firstname'] ?? '') ?>" <?= !empty($student['firstname']) ? 'readonly' : '' ?> required>

<label>Middle Name:</label>
<input type="text" name="middlename" value="<?= htmlspecialchars($student['middlename'] ?? '') ?>" <?= !empty($student['middlename']) ? 'readonly' : '' ?>>

<label>Last Name:</label>
<input type="text" name="lastname" value="<?= htmlspecialchars($student['lastname'] ?? '') ?>" <?= !empty($student['lastname']) ? 'readonly' : '' ?> required>

<label>GATE Applied:</label>
<select name="gate_applied" id="gate_applied" onchange="toggleGate()" required <?= !empty($placement['gate_applied']) ? 'disabled' : '' ?>>
<option value="">-- Select --</option>
<option value="yes" <?= (($placement['gate_applied'] ?? '')==='yes')?'selected':''?>>Yes</option>
<option value="no" <?= (($placement['gate_applied'] ?? '')==='no')?'selected':''?>>No</option>
</select>

<div id="gate_app_id_div" class="<?= (($placement['gate_applied'] ?? '')==='yes')?'':'hidden' ?>">
<label>GATE Application ID:</label>
<input type="text" name="gate_app_id" id="gate_app_id" value="<?= htmlspecialchars($placement['gate_app_id'] ?? '') ?>" <?= !empty($placement['gate_app_id']) ? 'readonly' : '' ?>>
</div>

<label>TCS CodeVita Registered:</label>
<select name="tcs_codevita_reg" id="tcs_codevita_reg" onchange="toggleTCS()" required <?= !empty($placement['tcs_codevita_reg']) ? 'disabled' : '' ?>>
<option value="">-- Select --</option>
<option value="Yes" <?= (($placement['tcs_codevita_reg'] ?? '')==='Yes')?'selected':''?>>Yes</option>
<option value="No" <?= (($placement['tcs_codevita_reg'] ?? '')==='No')?'selected':''?>>No</option>
</select>

<div id="tcs_ctdt_id_div" class="<?= (($placement['tcs_codevita_reg'] ?? '')==='Yes')?'':'hidden' ?>">
<label>TCS CT/DT ID:</label>
<input type="text" name="tcs_ctdt_id" id="tcs_ctdt_id" value="<?= htmlspecialchars($placement['tcs_ctdt_id'] ?? '') ?>" <?= !empty($placement['tcs_ctdt_id']) ? 'readonly' : '' ?>>
</div>

<input type="submit" name="save" value="Save Details">
</form>
<?php endif; ?>

<?php if ($message): ?>
<div class="<?= strpos($message,'❌')!==false?'error':'message' ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>
</div>

<script>
toggleGate();
toggleTCS();
</script>
</body>
</html>
