<?php
require_once __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$page_title = 'Party Lists';

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Invalid CSRF token.'];
        header('Location: partylists.php'); exit;
    }
    $pid = intval($_POST['id'] ?? 0);
    try {
        $stmt = $pdo->prepare('DELETE FROM partylists WHERE id = ?');
        $stmt->execute([$pid]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Partylist deleted.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error deleting partylist: ' . $e->getMessage()];
    }
    header('Location: partylists.php'); exit;
}

// Handle create
$elections = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();
$selected = $_GET['election_id'] ?? ($elections[0]['id'] ?? null);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create']) && isset($_POST['name'])) {
    $eid = intval($_POST['election_id']);
    $name = trim($_POST['name']);
    $abbrev = trim($_POST['abbrev'] ?? '');
    if ($name==='') {
        $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Partylist name required.'];
        header('Location: partylists.php?election_id=' . $eid); exit;
    }
    $stmt = $pdo->prepare('INSERT INTO partylists (election_id, name, abbreviation) VALUES (?, ?, ?)');
    $stmt->execute([$eid, $name, $abbrev]);
    $_SESSION['flash_message'] = ['type'=>'success','message'=>'Partylist added.'];
    header('Location: partylists.php?election_id=' . $eid); exit;
}

// fetch lists
$lists = [];
if ($selected) {
    $stmt = $pdo->prepare('SELECT * FROM partylists WHERE election_id = ?'); $stmt->execute([$selected]); $lists = $stmt->fetchAll();
}

include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Party Lists</h4>
    <div class="small-muted">Add or remove position</div>
  </div>

  <form method="get" class="mb-3">
    <select class="form-select" name="election_id" onchange="this.form.submit()">
      <?php foreach ($elections as $el): ?>
      <option value="<?php echo $el['id']; ?>"<?php if($selected==$el['id']) echo ' selected'; ?>><?php echo htmlspecialchars($el['title']); ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <form method="post" class="row g-2 mb-3">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="election_id" value="<?php echo htmlspecialchars($selected); ?>">
    <div class="col-md-6"><input class="form-control" name="name" placeholder="Position Name" required></div>
    <div class="col-md-4"><input class="form-control" name="abbrev" placeholder="Abbrev"></div>
    <div class="col-md-2 text-end"><button class="btn btn-maroon" name="create">Add</button></div>
  </form>

  <ul class="list-group">
    <?php foreach ($lists as $l): ?>
      <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
          <?php echo htmlspecialchars($l['name']); ?><br>
          <small class="small-muted"><?php echo htmlspecialchars($l['abbreviation']); ?></small>
        </div>
        <div class="d-flex gap-2">
          <a class="btn btn-sm btn-outline-secondary" href="candidates.php?partylist_id=<?php echo $l['id']; ?>">Manage</a>
          <form method="post" onsubmit="return confirm('Delete this partylist and its candidates?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="id" value="<?php echo $l['id']; ?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i> Delete</button>
          </form>
        </div>
      </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php include __DIR__ . '/footer.php'; ?>
