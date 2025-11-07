<?php
if (session_status() == PHP_SESSION_NONE) session_start();
if (basename($_SERVER['PHP_SELF']) != 'login.php' && empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config.php';
$page_title = isset($page_title)?$page_title:'Admin Panel';
include __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <div class="col-md-3">
    <div class="sidebar">
      <div class="text-center mb-3"><img src="/votesure_reborn/votesurelogo.png" style="height:64px"></div>
      <div class="mb-3 text-center">
        <div class="fw-bold">Admin</div>
        <div class="small-muted">Mange everything</div>
      </div>
      <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="elections.php"><i class="fas fa-calendar-check me-2"></i>Elections</a></li>
        <li class="nav-item"><a class="nav-link" href="partylists.php"><i class="fas fa-list-alt me-2"></i>Party Lists</a></li>
        <li class="nav-item"><a class="nav-link" href="candidates.php"><i class="fas fa-user-friends me-2"></i>Candidates</a></li>
        <li class="nav-item"><a class="nav-link" href="start_election.php"><i class="fas fa-power-off me-2"></i>Start / Stop</a></li>
        <li class="nav-item"><a class="nav-link" href="results.php"><i class="fas fa-chart-bar me-2"></i>Results</a></li>
        <li class="nav-item"><a class="nav-link" href="reports.php"><i class="fas fa-file-csv me-2"></i>Reports</a></li>
        <li class="nav-item"><a class="nav-link text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
  <div class="col-md-9">
