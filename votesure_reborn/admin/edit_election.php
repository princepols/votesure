<?php
require_once __DIR__ . '/../config.php';
$id = $_GET['id'] ?? null;
if (!$id) header('Location: elections.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare('UPDATE elections SET title=?, description=?, status=? WHERE id=?');
    $stmt->execute([$_POST['title'], $_POST['description'], $_POST['status'], $id]);
    header('Location: elections.php'); exit;
}
$stmt = $pdo->prepare('SELECT * FROM elections WHERE id=?'); $stmt->execute([$id]); $e = $stmt->fetch();
$page_title = 'Edit Election';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <h4>Edit Election</h4>
  <form method="post" class="row g-3">
    <div class="col-md-6"><input class="form-control" name="title" value="<?php echo h($e['title']); ?>" required></div>
    <div class="col-md-6"><input class="form-control" name="description" value="<?php echo h($e['description']); ?>"></div>
    <div class="col-md-3"><select class="form-select" name="status">
      <option value="draft"<?php if($e['status']=='draft') echo ' selected'; ?>>Draft</option>
      <option value="running"<?php if($e['status']=='running') echo ' selected'; ?>>Running</option>
      <option value="closed"<?php if($e['status']=='closed') echo ' selected'; ?>>Closed</option>
    </select></div>
    <div class="col-md-3 text-end"><button class="btn btn-violet">Save</button></div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
