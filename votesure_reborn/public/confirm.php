<?php
require_once __DIR__ . '/../config.php';
session_start();
$success = isset($_GET['success']);
$page_title = 'Confirmation';
include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card card-modern p-4 text-center">
      <?php if ($success): ?>
        <h3 class="text-success">Thank you! Your vote has been recorded.</h3>
        <p class="small-muted">Your participation matters. Please inform staff if you experience issues.</p>
        <a class="btn btn-violet" href="index.php">Back to Voter Station</a>
      <?php else: ?>
        <h3 class="text-danger">No vote was recorded.</h3>
        <a class="btn btn-outline-primary" href="index.php">Back</a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
