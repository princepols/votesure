<?php
require_once __DIR__ . '/../config.php';
session_start();

$page_title = 'Register Voters';

// Optional: CSRF protection token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Invalid request'];
        header('Location: register_voters.php');
        exit;
    }

    // ADD voter
    if ($_POST['action'] === 'add') {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['student_name'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year = trim($_POST['year_level'] ?? '');

        if ($student_id === '') {
            $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Student ID is required'];
        } else {
            try {
                // Check for duplicate ID
                $check = $pdo->prepare('SELECT * FROM registered_voters WHERE TRIM(student_id) = TRIM(?)');
                $check->execute([$student_id]);

                if ($check->fetch()) {
                    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'This Student ID is already registered.'];
                } else {
                    $ins = $pdo->prepare('INSERT INTO registered_voters (student_id, student_name, course, year_level) VALUES (?, ?, ?, ?)');
                    $ins->execute([$student_id, $name, $course, $year]);
                    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Voter successfully registered!'];
                }
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Database Error: ' . $e->getMessage()];
            }
        }

        header('Location: register_voters.php');
        exit;
    }

    // DELETE voter
    elseif ($_POST['action'] === 'delete' && !empty($_POST['id'])) {
        $id = intval($_POST['id']);
        $del = $pdo->prepare('DELETE FROM registered_voters WHERE id = ?');
        $del->execute([$id]);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Registered voter removed'];
        header('Location: register_voters.php');
        exit;
    }
}

// Fetch all voters
$rows = $pdo->query('SELECT * FROM registered_voters ORDER BY created_at DESC')->fetchAll();

include __DIR__ . '/header.php';
?>

<div class="card card-modern p-4">
  <h4>Register Voters</h4>

  <?php if (!empty($_SESSION['flash_message'])): 
    $f = $_SESSION['flash_message'];
    echo '<div class="alert alert-' . htmlspecialchars($f['type']) . '">' . htmlspecialchars($f['message']) . '</div>';
    unset($_SESSION['flash_message']);
  endif; ?>

  <form method="post" class="row g-2 mb-3">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <input type="hidden" name="action" value="add">

    <div class="col-md-3">
      <input class="form-control" name="student_id" placeholder="Student ID" required>
    </div>
    <div class="col-md-3">
      <input class="form-control" name="student_name" placeholder="Full name">
    </div>
    <div class="col-md-3">
      <input class="form-control" name="course" placeholder="Course">
    </div>

    <div class="col-md-2">
      <input class="form-control" name="year_level" placeholder="Year level">
    </div>
    <div class="col-md-1 text-end">
      <button class="btn btn-maroon">Add</button>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-striped align-middle">
      <thead>
        <tr>
          <th>ID</th>
          <th>Student ID</th>
          <th>Name</th>
          <th>Course</th>
          <th>Year</th>
          <th>Added</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?php echo htmlspecialchars($r['id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_id']); ?></td>
          <td><?php echo htmlspecialchars($r['student_name']); ?></td>
          <td><?php echo htmlspecialchars($r['course']); ?></td>
          <td><?php echo htmlspecialchars($r['year_level']); ?></td>
          <td><?php echo htmlspecialchars($r['created_at']); ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Remove this registered voter?')">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
              <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
