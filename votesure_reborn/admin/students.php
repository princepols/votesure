<?php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Handle Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id']);
    $name = trim($_POST['name']);

    if ($student_id && $name) {
        $stmt = $pdo->prepare("INSERT INTO students (student_id, name) VALUES (?, ?)");
        try {
            $stmt->execute([$student_id, $name]);
            $success = "Student ID registered successfully!";
        } catch (PDOException $e) {
            $error = "Error: This student ID might already exist.";
        }
    } else {
        $error = "All fields are required.";
    }
}

// Fetch all students
$students = $pdo->query("SELECT * FROM students ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Registered Students';
include __DIR__ . '/header.php';
?>

<div class="row">
  <div class="col-md-10 offset-md-1">
    <div class="card card-modern p-4">
      <h4 class="mb-4">Register New Student ID</h4>

      <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
      <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>

      <form method="post" class="mb-4">
        <div class="mb-3">
          <label class="form-label">Student ID</label>
          <input type="text" name="student_id" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-violet">Register ID</button>
      </form>

      <hr>

      <h5 class="mb-3">Registered Students</h5>
      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>ID</th>
            <th>Student ID</th>
            <th>Name</th>
            <th>Voted</th>
            <th>Registered At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($students as $s): ?>
            <tr>
              <td><?php echo $s['id']; ?></td>
              <td><?php echo htmlspecialchars($s['student_id']); ?></td>
              <td><?php echo htmlspecialchars($s['name']); ?></td>
              <td><?php echo $s['voted'] ? '✅ Yes' : '❌ No'; ?></td>
              <td><?php echo $s['created_at']; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
