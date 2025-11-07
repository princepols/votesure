<?php
require_once __DIR__ . '/../config.php';
session_start();
if (!empty($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit; }
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Invalid credentials.';
    }
}
$page_title = 'Admin Login';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <h4>Admin Login</h4>
  <?php if ($message): ?><div class="alert alert-danger"><?php echo h($message); ?></div><?php endif; ?>
  <form method="post" class="row g-3">
    <div class="col-md-6"><input class="form-control" name="username" placeholder="Username" required></div>
    <div class="col-md-6"><input class="form-control" type="password" name="password" placeholder="Password" required></div>
    <div class="col-12 text-end"><button class="btn btn-violet">Login</button></div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
