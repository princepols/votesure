<?php
require_once __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['election_id']; $action = $_POST['action'];
    if ($action === 'start') { $pdo->query("UPDATE elections SET status='draft'"); $stmt = $pdo->prepare("UPDATE elections SET status='running' WHERE id=?"); $stmt->execute([$id]); }
    elseif ($action === 'close') { $stmt = $pdo->prepare("UPDATE elections SET status='closed' WHERE id=?"); $stmt->execute([$id]); }
    header('Location: start_election.php'); exit;
}
$elections = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();
$page_title = 'Start/Stop Election';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <h4>Start / Stop Election</h4>
  <form method="post" class="row g-2 align-items-center">
    <div class="col-md-8">
      <select class="form-select" name="election_id"><?php foreach ($elections as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo h($e['title']); ?> (<?php echo h($e['status']); ?>)</option><?php endforeach; ?></select>
    </div>
    <div class="col-md-4 text-end">
      <button class="btn btn-violet" name="action" value="start">Start Election</button>
      <button class="btn btn-outline-danger" name="action" value="close">Close Election</button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
