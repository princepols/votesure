<?php
require_once __DIR__ . '/../config.php';
session_start();
$page_title = 'Dashboard';
include __DIR__ . '/header.php';
$totalE = $pdo->query('SELECT COUNT(*) FROM elections')->fetchColumn();
$totalS = $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
$totalV = $pdo->query('SELECT COUNT(*) FROM votes')->fetchColumn();
?>

<div class="alert alert-success text-center mt-4" style="font-size: 1.2rem;">
👋 Welcome, Admin! Manage your elections, candidates, and reports below.
</div>



<div class="card card-modern p-4">
<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Dashboard</h4>
<div class="small-muted">Overview</div>
</div>
<div class="row g-3">
<div class="col-md-4"><div class="card p-3"><h6>Elections</h6><div class="fw-bold"><?php echo $totalE; ?></div></div></div>
<div class="col-md-4"><div class="card p-3"><h6>Registered Students</h6><div class="fw-bold"><?php echo $totalS; ?></div></div></div>
<div class="col-md-4"><div class="card p-3"><h6>Votes Cast</h6><div class="fw-bold"><?php echo $totalV; ?></div></div></div>
</div>
<hr>
<div class="row">
<div class="col-md-6">
<div class="card p-3">
<h6>Recent Elections</h6>
<ul class="list-group">
<?php foreach($pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 5') as $e): ?>
<li class="list-group-item d-flex justify-content-between align-items-center"><?php echo h($e['title']); ?> <span class="small-muted"><?php echo h($e['status']); ?></span></li>
<?php endforeach; ?>
</ul>
</div>
</div>
<div class="col-md-6">
<div class="card p-3">
<h6>Quick Actions</h6>
<a class="btn btn-violet" href="elections.php">Manage Elections</a>
<a class="btn btn-outline-secondary" href="partylists.php">Party Lists</a>
</div>
</div>
</div>
</div>
<?php include __DIR__ . '/footer.php'; ?>