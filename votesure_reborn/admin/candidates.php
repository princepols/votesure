<?php
require_once __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$page_title = 'Candidates';

// Delete candidate (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid CSRF token.'];
        header('Location: candidates.php'); exit;
    }
    $cid = intval($_POST['id'] ?? 0);
    try {
        // fetch photo name to delete file
        $stmt = $pdo->prepare('SELECT photo FROM candidates WHERE id = ?');
        $stmt->execute([$cid]);
        $row = $stmt->fetch();
        $pdo->prepare('DELETE FROM candidates WHERE id = ?')->execute([$cid]);
        // remove file if present
        if ($row && !empty($row['photo'])) {
            $path = __DIR__ . '/../uploads/' . $row['photo'];
            if (file_exists($path)) @unlink($path);
        }
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Candidate deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting candidate: ' . $e->getMessage()];
    }
    header('Location: candidates.php'); exit;
}

// Determine partylist
$partylist_id = isset($_GET['partylist_id']) ? intval($_GET['partylist_id']) : (isset($_POST['partylist_id']) ? intval($_POST['partylist_id']) : null);

// If no partylist chosen, show chooser
if (!$partylist_id) {
    $pls = $pdo->query('SELECT p.id AS pid, p.name AS pname, e.title AS etitle FROM partylists p LEFT JOIN elections e ON p.election_id = e.id ORDER BY e.id DESC, p.id')->fetchAll();
    $page_title = 'Candidates - Select Partylist';
    include __DIR__ . '/header.php';
    ?>
    <div class="card card-modern p-4">
      <h4>Select Partylist to Manage Candidates</h4>
      <?php if (empty($pls)): ?>
        <div class="alert alert-warning">No partylists found. Create a partylist first.</div>
      <?php else: ?>
        <ul class="list-group">
        <?php foreach ($pls as $p): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <div><strong><?php echo htmlspecialchars($p['pname']); ?></strong><br><small class="small-muted"><?php echo htmlspecialchars($p['etitle']); ?></small></div>
            <div><a class="btn btn-maroon" href="candidates.php?partylist_id=<?php echo $p['pid']; ?>">Manage</a></div>
          </li>
        <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
    <?php include __DIR__ . '/footer.php';
    exit;
}

// Add candidate (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_candidate']) && isset($_POST['name'])) {
    // simple validation
    $name = trim($_POST['name']);
    $position = trim($_POST['position'] ?? '');
    $photo_name = null;
    if (!empty($_FILES['photo']['name'])) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Invalid photo type.'];
            header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
        }
        $photo_name = uniqid('c_') . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../uploads/' . $photo_name);
    }
    $stmt = $pdo->prepare('INSERT INTO candidates (partylist_id, name, position, photo) VALUES (?, ?, ?, ?)');
    $stmt->execute([$partylist_id, $name, $position, $photo_name]);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Candidate added.'];
    header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
}

// fetch partylist & candidates
$stmt = $pdo->prepare('SELECT * FROM partylists WHERE id = ?'); $stmt->execute([$partylist_id]); $pl = $stmt->fetch();
$candidates = $pdo->prepare('SELECT * FROM candidates WHERE partylist_id = ? ORDER BY id DESC'); $candidates->execute([$partylist_id]); $cands = $candidates->fetchAll();

$page_title = 'Candidates';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Candidates for <?php echo htmlspecialchars($pl['name']); ?></h4>
    <div class="small-muted">Add or remove candidate</div>
  </div>

  <form method="post" enctype="multipart/form-data" class="row g-3 mb-3">
    <input type="hidden" name="partylist_id" value="<?php echo htmlspecialchars($partylist_id); ?>">
    <div class="col-md-4"><input class="form-control" name="name" placeholder="Candidate name" required></div>
    <div class="col-md-4"><input class="form-control" name="position" placeholder="Partylist Name"></div>
    <div class="col-md-3"><input class="form-control" type="file" name="photo" accept="image/*"></div>
    <div class="col-md-1 text-end"><button class="btn btn-maroon" name="add_candidate">Add</button></div>
  </form>

  <div class="row g-3">
    <?php if (empty($cands)): ?>
      <div class="col-12"><div class="alert alert-info">No candidates yet for this partylist.</div></div>
    <?php endif; ?>
    <?php foreach ($cands as $c): ?>
      <div class="col-md-4">
        <div class="card p-2">
          <?php if ($c['photo']): ?>
            <img src="/votesure_reborn/uploads/<?php echo htmlspecialchars($c['photo']); ?>" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:8px;">
          <?php else: ?>
            <div style="width:100%;height:160px;display:flex;align-items:center;justify-content:center;background:#fafafa;border-radius:8px;margin-bottom:8px;">
              <i class="fas fa-user fa-3x" style="color:var(--muted)"></i>
            </div>
          <?php endif; ?>
          <div class="fw-semibold"><?php echo htmlspecialchars($c['name']); ?></div>
          <div class="small-muted"><?php echo htmlspecialchars($c['position']); ?></div>
          <div class="mt-2 d-flex gap-2">
            <!-- Delete button -->
            <form method="post" onsubmit="return confirm('Delete this candidate?');" class="ms-auto">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i> Delete</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
