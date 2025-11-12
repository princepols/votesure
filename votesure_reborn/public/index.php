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
        $stmt = $pdo->prepare('SELECT * FROM students WHERE student_id = ?');
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        if (!$student) {
            $stmt = $pdo->prepare('INSERT INTO students (student_id, name) VALUES (?, ?)');
            $stmt->execute([$student_id, null]);
            $student = ['student_id' => $student_id, 'voted' => 0];
        }
        if ($student['voted']) {
            $message = 'This Student ID has already voted. Access refused.';
        } else {
            $_SESSION['voter_student_id'] = $student_id;
            header('Location: vote.php');
            exit;
        }
    }
}
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card card-modern p-4">
      <h4 class="mb-1">Voter Station</h4>
      <p class="small-muted">Put your ID in the field, ID's are automatically registered when you start voting.</p>
      <?php if($message): ?><div class="alert alert-danger"><?php echo h($message); ?></div><?php endif; ?>
      <form method="post" class="row g-3" id="idForm">
        <div class="col-12">
          <label class="form-label">Student ID</label>
          <input class="form-control form-control-lg" id="student_id" name="student_id" autofocus required placeholder="Scan or type student ID">
        </div>
        <div class="col-12 d-flex justify-content-between align-items-center">
          <button class="btn btn-violet btn-lg" type="submit"><i class="fas fa-vote-yea"></i> Start Voting</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
document.getElementById('student_id').addEventListener('keyup', function(e){
  if (e.key === 'Enter') {
    document.getElementById('idForm').submit();
  }
});
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
