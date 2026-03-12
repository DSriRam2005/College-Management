<?php
include 'db.php'; // DB connection

$student = null;
$message = "";
$htno = "";

// Step 1: Search by HTNO
if (isset($_POST['search'])) {
    $htno = trim($_POST['htno']);
    $sql = "SELECT * FROM STUDENTS WHERE prog='B.TECH' AND year=23 AND htno=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $htno);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();

    if ($student) {
        // Check if already submitted
        $check = $conn->prepare("SELECT 1 FROM 3FEEDBACK WHERE htno=? LIMIT 1");
        $check->bind_param("s", $htno);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "<p style='color:orange;'>⚠️ You have already submitted feedback. Thank you!</p>";
            $student = null; // prevent form display
        }
    } else {
        $message = "<p style='color:red;'>Student not found or not eligible.</p>";
    }
}

// Step 2: Submit Feedback
if (isset($_POST['submit_feedback'])) {
    $required_fields = [
        'htno','usefulness','speed','confidence','skills','difficulty','strategies',
        'trainers_knowledge','trainers_explain','trainers_helpful','guidance',
        'trainer_like','trainer_improve','overall_exp','format_engage',
        'like_most','improve_next','more_hackathons','suggestions'
    ];

    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        $message = "<p style='color:red;'>⚠️ Please fill all fields: " . implode(", ", $missing) . "</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO 3FEEDBACK 
            (htno,usefulness,speed,confidence,skills,difficulty,strategies,
             trainers_knowledge,trainers_explain,trainers_helpful,guidance,
             trainer_like,trainer_improve,overall_exp,format_engage,
             like_most,improve_next,more_hackathons,suggestions)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param("sissssiiissssiissss",
            $_POST['htno'],
            $_POST['usefulness'],
            $_POST['speed'],
            $_POST['confidence'],
            $_POST['skills'],
            $_POST['difficulty'],
            $_POST['strategies'],
            $_POST['trainers_knowledge'],
            $_POST['trainers_explain'],
            $_POST['trainers_helpful'],
            $_POST['guidance'],
            $_POST['trainer_like'],
            $_POST['trainer_improve'],
            $_POST['overall_exp'],
            $_POST['format_engage'],
            $_POST['like_most'],
            $_POST['improve_next'],
            $_POST['more_hackathons'],
            $_POST['suggestions']
        );
        $stmt->execute();
        $message = "<p style='color:green;'>✅ Feedback submitted successfully!</p>";
        $student = null;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Hackathon Feedback</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; background: #f4f7fa; margin: 0; padding: 20px; }
        h2 { text-align: center; color: #333; }
        form { margin-bottom: 20px; }
        .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 20px; }
        label { font-weight: 600; display: block; margin: 15px 0 8px; color: #444; }
        textarea, input[type="text"], select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ccc; font-size: 15px; }
        button { background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; margin-top: 15px; width: 100%; }
        button:hover { background: #0056b3; }

        .stars { display: inline-flex; flex-direction: row-reverse; justify-content: flex-end; }
        .stars input { display: none; }
        .stars label { font-size: 28px; color: #ccc; cursor: pointer; transition: color 0.2s; }
        .stars input:checked ~ label { color: gold; }
        .stars label:hover, .stars label:hover ~ label { color: gold; }

        .options { display: flex; gap: 15px; margin-top: 8px; }
        .options input { display: none; }
        .options label { padding: 8px 15px; border: 2px solid #007bff; border-radius: 20px; cursor: pointer; transition: 0.3s; }
        .options input:checked + label { background: #007bff; color: white; }

        @media (max-width: 600px) { .stars label { font-size: 20px; } button { font-size: 14px; } }
    </style>
</head>
<body>
<h2>🚀 Hackathon Feedback Form</h2>
<?php echo $message; ?>

<!-- Student Search -->
<form method="post">
    <label>Enter HTNO:</label>
    <input type="text" name="htno" value="<?php echo htmlspecialchars($htno); ?>" required>
    <button type="submit" name="search">Search</button>
</form>

<?php if ($student): ?>
<div class="card">
    <h3>Welcome <?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['htno']); ?>)</h3>
    <form method="post" name="feedback">
        <input type="hidden" name="htno" value="<?php echo htmlspecialchars($student['htno']); ?>">

        <!-- Star Ratings -->
        <?php
        $star_fields = [
            'usefulness'=>'Usefulness of Hackathon',
            'trainers_knowledge'=>'Trainers Knowledge',
            'trainers_explain'=>'Trainers Explanation',
            'trainers_helpful'=>'Trainers Helpful',
            'overall_exp'=>'Overall Experience',
            'format_engage'=>'Hackathon Format Engagement'
        ];
        foreach ($star_fields as $name=>$label): ?>
            <label><?php echo $label; ?>:</label>
            <div class="stars">
                <?php for($i=5;$i>=1;$i--): ?>
                <input type="radio" id="<?php echo $name.$i;?>" name="<?php echo $name;?>" value="<?php echo $i;?>"><label for="<?php echo $name.$i;?>">★</label>
                <?php endfor; ?>
            </div>
        <?php endforeach; ?>

        <!-- Radio Groups -->
        <?php
        $radio_fields = [
            'speed'=>['Yes','Somewhat','No'],
            'confidence'=>['Yes','Somewhat','No'],
            'difficulty'=>['Too Easy','Balanced','Too Difficult'],
            'guidance'=>['Yes','Somewhat','No'],
            'more_hackathons'=>['Yes','No','Maybe']
        ];
        foreach ($radio_fields as $name=>$options):
        ?>
        <label><?php echo ucwords(str_replace('_',' ',$name)); ?>:</label>
        <div class="options">
            <?php foreach($options as $idx=>$opt): ?>
            <input type="radio" id="<?php echo $name.$idx;?>" name="<?php echo $name;?>" value="<?php echo $opt;?>"><label for="<?php echo $name.$idx;?>"><?php echo $opt;?></label>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <!-- Textareas -->
        <?php
        $text_fields = [
            'skills'=>'What new concepts/skills did you learn?',
            'strategies'=>'New strategies for CodeVita',
            'trainer_like'=>'What did you like about trainers style?',
            'trainer_improve'=>'What could trainers improve?',
            'like_most'=>'What did you like most about the hackathon?',
            'improve_next'=>'What could be improved next time?',
            'suggestions'=>'Suggestions for next hackathon'
        ];
        foreach ($text_fields as $name=>$label): ?>
            <label><?php echo $label; ?>:</label>
            <textarea name="<?php echo $name;?>"></textarea>
        <?php endforeach; ?>

        <button type="submit" name="submit_feedback">✅ Submit Feedback</button>
    </form>
</div>
<?php endif; ?>

<script>
// Full client-side validation
window.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form[name="feedback"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let valid = true;
        let firstInvalid = null;

        // Check textareas
        form.querySelectorAll('textarea').forEach(f => {
            if (!f.value.trim()) {
                valid = false;
                f.style.border = '2px solid red';
                if (!firstInvalid) firstInvalid = f;
            } else {
                f.style.border = '1px solid #ccc';
            }
        });

        // Check radio groups including stars
        const radioNames = [...new Set([...form.querySelectorAll('input[type="radio"]')].map(r => r.name))];
        radioNames.forEach(name => {
            const checked = form.querySelector(`input[name="${name}"]:checked`);
            if (!checked) {
                valid = false;
                form.querySelectorAll(`input[name="${name}"] + label`).forEach(l => l.style.border = '2px solid red');
                if (!firstInvalid) firstInvalid = form.querySelector(`input[name="${name}"] + label`);
            } else {
                form.querySelectorAll(`input[name="${name}"] + label`).forEach(l => l.style.border = 'none');
            }
        });

        if (!valid) {
            e.preventDefault();
            alert('⚠️ Please fill all mandatory fields!');
            if (firstInvalid) firstInvalid.scrollIntoView({behavior: 'smooth', block: 'center'});
        }
    });
});
</script>

</body>
</html>
