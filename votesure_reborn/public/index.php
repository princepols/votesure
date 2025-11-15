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

    <!-- About / Purpose Section -->
    <div class="card card-modern p-4 mt-4">
      <h5 class="mb-2">About VoteSure</h5>
      <p class="small-muted">VoteSure is a lightweight electronic voting system built for student government and small organizational elections. It focuses on usability for voters and simple, auditable tools for administrators.</p>

      <div class="mt-3">
        <h6>Purpose</h6>
        <p class="small-muted">To digitalized and simplify the election process for schools or organizations by allowing students to vote securely and allowing admins to manage elections easily.</p>

        <h6>Introduction</h6>
        <p class="small-muted">This station lets voters authenticate using their Student ID. Once verified, voters can participate in currently active elections created by administrators. Administrators control who is allowed to vote by registering voter IDs.</p>

        <h6>Core Functionality</h6>
        <ul class="small-muted">
          <li>Admin interface to create and manage elections, partylists, and candidates.</li>
          <li>Administrator-managed registration of voter IDs (so only approved voters can vote).</li>
          <li>One-vote-per-student enforced with server-side checks and database constraints.</li>
          <li>Support for candidate photos and CSV export for audit and reporting.</li>
          <li>Results interface with tallies and per-voter logs for transparency and review.</li>
        </ul>

        <h6>How to Vote</h6>
        <ol class="small-muted">
          <li>Register your Student ID from Adminstrators.</li>
          <li>Enter or scan your Student ID in the field above and click "Start Voting".</li>
          <li>Select one candidate for each party list/position.</li>
          <li>Review your choices and submit. A confirmation screen will appear when your vote is recorded.</li>
        </ol>

        <h6>Credits</h6>
        <p class="small-muted">Group 10 Researchers:</p>
        <ul class="small-muted">
          <li>Adrian Manuel Gaspe</li>
          <li>Prince Ryan Policianos</li>
          <li>Mon Calix Lucena</li>
          <li>Nathalie Suzynne Hemelian</li>
        </ul>
        <p class="small-muted">Research Adviser: Dr. Archie S. Gonzaga, MAEDITI, LPT</p>
      </div>

      <div class="mt-3 small-muted">
        <strong>Note:</strong> If your Student ID is not registered or you encounter issues, contact the election administrator. For technical problems, report to the support team.
      </div>
    </div>

  </div>

  <!-- Optional side column with quick links / admin contact -->
  <div class="col-md-4">
    <div class="card card-modern p-3 mt-4">
      <h6 class="mb-2">Quick Links</h6>
      <ul class="small-muted">
        <li><a href="/votesure_reborn/admin/login.php">Administrator Login</a></li>
        <li><a href="/votesure_reborn/admin/registers_voters.php">Voter Registration (Admin)</a></li>
        <li><a href="/votesure_reborn/admin/results.php">View Results (Admin)</a></li>
      </ul>
    </div>

    <div class="card card-modern p-3 mt-3">
      <h6 class="mb-2">Security & Privacy</h6>
      <p class="small-muted mb-0">Votes are stored in the system database and tied to anonymized records for auditing. Only authorized administrators can access detailed results and voter logs.</p>
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