<?php
require_once __DIR__ . '/../config.php';
session_start();

$page_title = 'Voter Station';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');

    if ($student_id === '') {
        $message = 'Please enter or scan your Student ID.';
    } else {
        // 🔍 Check if student ID exists in the registered_voters list (admin-registered)
        $stmt = $pdo->prepare('SELECT * FROM registered_voters WHERE TRIM(student_id) = TRIM(?)');
        $stmt->execute([$student_id]);
        $reg = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$reg) {
            $message = 'This Student ID is not registered for voting. Please contact the administrator.';
        } else {
            // ✅ Student is registered — check if already in students table
            $stmt2 = $pdo->prepare('SELECT * FROM students WHERE TRIM(student_id) = TRIM(?)');
            $stmt2->execute([$student_id]);
            $student = $stmt2->fetch(PDO::FETCH_ASSOC);

            // If not found, insert student record for tracking votes
            if (!$student) {
                $ins = $pdo->prepare('INSERT INTO students (student_id, name, voted) VALUES (?, ?, 0)');
                $ins->execute([$student_id, $reg['student_name'] ?? null]);
                $student = ['student_id' => $student_id, 'voted' => 0];
            }

            // Check if already voted
            if (!empty($student['voted']) && $student['voted'] == 1) {
                $message = 'This Student ID has already voted. Access refused.';
            } else {
                $_SESSION['voter_student_id'] = $student_id;
                header('Location: vote.php');
                exit;
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card card-modern p-4 shadow-lg">
      <h4 class="mb-1 text-center">Voter Station</h4>
      <p class="small-muted text-center mb-3">
        Please enter or scan your Student ID to start voting.<br>
        <span class="text-danger">Only IDs registered by the administrator are valid.</span>
      </p>

      <?php if ($message): ?>
        <div class="alert alert-danger text-center"><?php echo htmlspecialchars($message); ?></div>
      <?php endif; ?>

      <form method="post" class="row g-3" id="idForm">
        <div class="col-12">
          <label class="form-label fw-bold">Student ID</label>
          <input class="form-control form-control-lg"
                 id="student_id"
                 name="student_id"
                 autofocus
                 required
                 placeholder="Scan or type your Student ID here...">
        </div>
        <div class="col-12 text-center mt-3">
          <button class="btn btn-violet btn-lg px-5" type="submit">
            <i class="fas fa-vote-yea me-2"></i> Start Voting
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('student_id').addEventListener('keyup', function(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    document.getElementById('idForm').submit();
  }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
