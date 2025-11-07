<?php
require_once __DIR__ . '/../config.php';
session_start();

// require admin login for safety
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); exit;
}

// CSRF token setup
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

$page_title = 'Manage Elections';

// Handle deletion (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && !empty($_POST['id'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid request (CSRF).'];
        header('Location: elections.php'); exit;
    }
    $id = intval($_POST['id']);
    try {
        $stmt = $pdo->prepare('DELETE FROM elections WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Election deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting election: ' . $e->getMessage()];
    }
    header('Location: elections.php'); exit;
}

// Handle creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create']) && !isset($_POST['action'])) {
    $title = trim($_POST['title'] ?? '');
    $descr = trim($_POST['description'] ?? '');
    if ($title === '') {
        $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Election title required.'];
        header('Location: elections.php'); exit;
    }
    $stmt = $pdo->prepare('INSERT INTO elections (title, description, status) VALUES (?, ?, ?)');
    $stmt->execute([$title, $descr, 'draft']);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Election created.'];
    header('Location: elections.php'); exit;
}

// fetch
$rows = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();

include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Manage Elections</h4>
    <div class="small-muted">Create, edit or remove</div>
  </div>

  <form method="post" class="row g-3 mb-3">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="col-md-5"><input class="form-control" name="title" placeholder="Election title" required></div>
    <div class="col-md-5"><input class="form-control" name="description" placeholder="Short description"></div>
    <div class="col-md-2 text-end"><button class="btn btn-maroon" name="create">Create Election</button></div>
  </form>

  <ul class="list-group">
    <?php foreach($rows as $r): ?>
      <li class="list-group-item d-flex justify-content-between align-items-start">
        <div>
          <div class="fw-semibold"><?php echo htmlspecialchars($r['title']); ?></div>
          <div class="small-muted"><?php echo htmlspecialchars($r['description']); ?></div>
          <div class="small-muted">Status: <?php echo htmlspecialchars($r['status']); ?></div>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-secondary" href="edit_election.php?id=<?php echo $r['id']; ?>">Edit</a>

          <form method="post" onsubmit="return confirm('Delete this election and all related party lists, candidates, and votes?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i> Delete</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>

<?php include __DIR__ . '/footer.php'; ?>
