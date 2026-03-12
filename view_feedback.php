<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'db.php';

// Get total feedback count
$totalQuery = $conn->query("SELECT COUNT(*) AS total FROM infosys_feedback");
$total = $totalQuery->fetch_assoc()['total'];

// Get averages for numeric ratings
$avgQuery = $conn->query("
    SELECT 
        AVG(coding_accuracy) AS avg_coding,
        AVG(problem_solving) AS avg_problem,
        AVG(time_management) AS avg_time,
        AVG(conceptual_clarity) AS avg_concept,
        AVG(application_training) AS avg_training
    FROM infosys_feedback
");
$avg = $avgQuery->fetch_assoc();

// Get most common responses for enums
function getCommon($conn, $field) {
    $sql = "SELECT $field, COUNT(*) AS c FROM infosys_feedback WHERE $field IS NOT NULL GROUP BY $field ORDER BY c DESC LIMIT 1";
    $res = $conn->query($sql);
    return ($res && $res->num_rows > 0) ? $res->fetch_assoc()[$field] : '-';
}
$progRel = getCommon($conn, 'prog_fund_relevance');
$progPrep = getCommon($conn, 'prog_fund_preparedness');
$dsaRel = getCommon($conn, 'dsa_relevance');
$dsaPrep = getCommon($conn, 'dsa_preparedness');
$aptRel = getCommon($conn, 'aptitude_relevance');
$aptPrep = getCommon($conn, 'aptitude_preparedness');
$mockRel = getCommon($conn, 'mock_relevance');
$mockPrep = getCommon($conn, 'mock_preparedness');
$confidence = getCommon($conn, 'confidence_level');
$readiness = getCommon($conn, 'overall_readiness');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Infosys Feedback Summary Report</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f4f6f9; font-family: 'Poppins', sans-serif; }
.container { margin-top: 40px; }
.section-title { background: #003366; color: white; padding: 8px 12px; border-radius: 6px; margin-top: 25px; }
.count-box { background: #003366; color: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align:center; }
.table th { background: #003366; color: white; }
</style>
</head>
<body>
<div class="container">
  <h3 class="text-center mb-4">📊 Infosys Return Test Feedback Summary</h3>

  <div class="count-box">
    <h5>Total Feedback Submitted: <b><?= $total ?></b></h5>
  </div>

  <!-- Section A -->
  <div class="section-title">🔍 Section A: Test Performance Overview</div>
  <table class="table table-bordered">
    <thead><tr><th>Area Assessed</th><th>Average Rating (1–5)</th></tr></thead>
    <tbody>
      <tr><td>Coding Accuracy</td><td><?= number_format($avg['avg_coding'], 2) ?></td></tr>
      <tr><td>Problem-Solving Approach</td><td><?= number_format($avg['avg_problem'], 2) ?></td></tr>
      <tr><td>Time Management</td><td><?= number_format($avg['avg_time'], 2) ?></td></tr>
      <tr><td>Conceptual Clarity</td><td><?= number_format($avg['avg_concept'], 2) ?></td></tr>
      <tr><td>Application of Training</td><td><?= number_format($avg['avg_training'], 2) ?></td></tr>
    </tbody>
  </table>

  <!-- Section B -->
  <div class="section-title">📚 Section B: Training Effectiveness (Most Common Responses)</div>
  <table class="table table-bordered">
    <thead><tr><th>Training Module</th><th>Relevance</th><th>Preparedness</th></tr></thead>
    <tbody>
      <tr><td>Programming Fundamentals</td><td><?= $progRel ?></td><td><?= $progPrep ?></td></tr>
      <tr><td>DSA (Data Structures & Algorithms)</td><td><?= $dsaRel ?></td><td><?= $dsaPrep ?></td></tr>
      <tr><td>Aptitude & Logical Reasoning</td><td><?= $aptRel ?></td><td><?= $aptPrep ?></td></tr>
      <tr><td>Mock Test Practice</td><td><?= $mockRel ?></td><td><?= $mockPrep ?></td></tr>
    </tbody>
  </table>

  <!-- Section C -->
  <div class="section-title">💬 Section C: Candidate Reflections</div>
  <table class="table table-bordered">
    <tbody>
      <tr><th>Most Common Confidence Level</th><td><?= $confidence ?></td></tr>
    </tbody>
  </table>

  <!-- Section D -->
  <div class="section-title">✅ Section D: Final Remarks</div>
  <table class="table table-bordered">
    <tbody>
      <tr><th>Most Common Overall Readiness</th><td><?= $readiness ?></td></tr>
    </tbody>
  </table>
</div>
</body>
</html>
