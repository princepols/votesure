<?php
require_once __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$page_title = 'Candidates';

// Define upload directory
$upload_dir = __DIR__ . '/../uploads/';
$upload_url = '/votesure_reborn/uploads/';

// Ensure upload directory exists and is writable
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

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
            $path = $upload_dir . $row['photo'];
            if (file_exists($path)) {
                if (!unlink($path)) {
                    error_log("Failed to delete candidate photo: " . $path);
                }
            }
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
      <h4>Select Position to Manage Candidates</h4>
      <?php if (empty($pls)): ?>
        <div class="alert alert-warning">No positions found. Create a position first.</div>
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
            $_SESSION['flash_message'] = ['type'=>'warning','message'=>'Invalid photo type. Allowed: JPG, JPEG, PNG, WEBP, GIF'];
            header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
        }
        
        // Check file size (max 5MB)
        if ($_FILES['photo']['size'] > 5 * 1024 * 1024) {
            $_SESSION['flash_message'] = ['type'=>'warning','message'=>'File too large. Max 5MB allowed.'];
            header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
        }
        
        // Check for upload errors
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_message'] = ['type'=>'danger','message'=>'File upload error: ' . $_FILES['photo']['error']];
            header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
        }
        
        $photo_name = uniqid('c_') . '.' . $ext;
        $target_path = $upload_dir . $photo_name;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
            $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Failed to upload photo. Check directory permissions.'];
            header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
        }
        
        // Verify the file was actually uploaded
        if (!file_exists($target_path)) {
            $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Photo upload failed. File not found.'];
            $photo_name = null;
        }
    }
    
    try {
        $stmt = $pdo->prepare('INSERT INTO candidates (partylist_id, name, position, photo) VALUES (?, ?, ?, ?)');
        $stmt->execute([$partylist_id, $name, $position, $photo_name]);
        $_SESSION['flash_message'] = ['type'=>'success','message'=>'Candidate added successfully.'];
    } catch (Exception $e) {
        $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Error adding candidate: ' . $e->getMessage()];
    }
    
    header('Location: candidates.php?partylist_id=' . $partylist_id); exit;
}

// fetch partylist & candidates
$stmt = $pdo->prepare('SELECT * FROM partylists WHERE id = ?'); 
$stmt->execute([$partylist_id]); 
$pl = $stmt->fetch();

if (!$pl) {
    $_SESSION['flash_message'] = ['type'=>'danger','message'=>'Partylist not found.'];
    header('Location: candidates.php'); exit;
}

$candidates = $pdo->prepare('SELECT * FROM candidates WHERE partylist_id = ? ORDER BY id DESC'); 
$candidates->execute([$partylist_id]); 
$cands = $candidates->fetchAll();

$page_title = 'Candidates';
include __DIR__ . '/header.php';

// Debug info (remove in production)
$debug_info = "";
if (isset($_GET['debug'])) {
    $debug_info = "Upload Dir: " . $upload_dir . " | Exists: " . (file_exists($upload_dir) ? 'Yes' : 'No') . " | Writable: " . (is_writable($upload_dir) ? 'Yes' : 'No');
}
?>

<?php if ($debug_info): ?>
<div class="alert alert-info small"><?php echo $debug_info; ?></div>
<?php endif; ?>

<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Candidates for <?php echo htmlspecialchars($pl['name']); ?></h4>
    <div class="small-muted">Add or remove candidate</div>
  </div>

  <form method="post" enctype="multipart/form-data" class="row g-3 mb-3">
    <input type="hidden" name="partylist_id" value="<?php echo htmlspecialchars($partylist_id); ?>">
    <div class="col-md-4">
        <input class="form-control" name="name" placeholder="Candidate name" required>
    </div>
    <div class="col-md-3">
        <input class="form-control" name="position" placeholder="Party list name">
    </div>
    <div class="col-md-3">
        <input class="form-control" type="file" name="photo" accept="image/*">
        <small class="text-muted">Max 5MB, JPG, PNG, WEBP, GIF</small>
    </div>
    <div class="col-md-2 text-end">
        <button class="btn btn-maroon" name="add_candidate">Add Candidate</button>
    </div>
  </form>

  <!-- Upload Directory Check -->
  <?php if (!is_writable($upload_dir)): ?>
  <div class="alert alert-warning">
    <strong>Warning:</strong> Upload directory is not writable. Please check permissions for: <?php echo $upload_dir; ?>
  </div>
  <?php endif; ?>

  <div class="row g-3">
    <?php if (empty($cands)): ?>
      <div class="col-12">
        <div class="alert alert-info">No candidates yet for this partylist.</div>
      </div>
    <?php endif; ?>
    
    <?php foreach ($cands as $c): 
        $image_path = $upload_dir . $c['photo'];
        $image_url = $upload_url . $c['photo'];
        $image_exists = $c['photo'] && file_exists($image_path);
    ?>
      <div class="col-md-4">
        <div class="card p-3">
          <?php if ($c['photo'] && $image_exists): ?>
            <img src="<?php echo $image_url; ?>" 
                 alt="<?php echo htmlspecialchars($c['name']); ?>" 
                 style="width:100%;height:200px;object-fit:cover;border-radius:8px;margin-bottom:12px;"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div style="width:100%;height:200px;display:none;align-items:center;justify-content:center;background:#f8f9fa;border-radius:8px;margin-bottom:12px;">
              <div class="text-center">
                <i class="fas fa-image fa-2x text-muted mb-2"></i>
                <div class="small text-muted">Image not found</div>
              </div>
            </div>
          <?php else: ?>
            <div style="width:100%;height:200px;display:flex;align-items:center;justify-content:center;background:#f8f9fa;border-radius:8px;margin-bottom:12px;">
              <div class="text-center">
                <i class="fas fa-user fa-3x text-muted mb-2"></i>
                <div class="small text-muted">No photo</div>
                <?php if ($c['photo'] && !$image_exists): ?>
                  <div class="small text-danger mt-1">File missing: <?php echo $c['photo']; ?></div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
          
          <div class="fw-semibold h5 mb-1"><?php echo htmlspecialchars($c['name']); ?></div>
          <div class="small-muted mb-2"><?php echo htmlspecialchars($c['position'] ?: 'No position specified'); ?></div>
          
          <?php if ($c['photo']): ?>
          <div class="small text-muted mb-2">
            Photo: <?php echo $c['photo']; ?>
            <?php if (!$image_exists): ?>
              <span class="text-danger">(File not found)</span>
            <?php endif; ?>
          </div>
          <?php endif; ?>
          
          <div class="mt-2 d-flex gap-2">
            <!-- Delete button -->
            <form method="post" onsubmit="return confirm('Are you sure you want to delete <?php echo htmlspecialchars($c['name']); ?>?');" class="ms-auto">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
              <input type="hidden" name="action" value="delete">
              <button class="btn btn-sm btn-outline-danger" type="submit">
                <i class="fas fa-trash"></i> Delete
              </button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Debug section (remove in production) -->
<div class="card mt-4">
  <div class="card-header">
    <h6>Debug Information</h6>
  </div>
  <div class="card-body small">
    <p><strong>Upload Directory:</strong> <?php echo $upload_dir; ?></p>
    <p><strong>Directory Exists:</strong> <?php echo file_exists($upload_dir) ? 'Yes' : 'No'; ?></p>
    <p><strong>Directory Writable:</strong> <?php echo is_writable($upload_dir) ? 'Yes' : 'No'; ?></p>
    <p><strong>Upload URL Base:</strong> <?php echo $upload_url; ?></p>
    <?php if (isset($_FILES['photo'])): ?>
      <p><strong>Last Upload Info:</strong> <?php echo json_encode($_FILES['photo']); ?></p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>