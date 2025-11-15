<?php
if (session_status() == PHP_SESSION_NONE) session_start();
// if (basename($_SERVER['PHP_SELF']) != 'login.php' && empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../config.php';
$page_title = isset($page_title)?$page_title:'Admin Panel';
include __DIR__ . '/../includes/header.php';
// background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar {
    background: linear-gradient(135deg, #B07D35 0%, #F5A433 100%); /*background: linear-gradient(135deg, #800000 0%, #611212 100%); */
    min-height: 100vh;
    padding: 20px 0;
    box-shadow: 3px 0 15px rgba(0,0,0,0.1);
}

.nav-link {
    color: rgba(255,255,255,0.8) !important;
    padding: 12px 20px !important;
    margin: 4px 15px;
    border-radius: 8px;
    transition: all 0.3s ease;
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.nav-link::before {
    content: '';
    position: absolute;
    left: -100%;
    top: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
    transition: left 0.5s ease;
}

.nav-link:hover::before {
    left: 100%;
}

.nav-link:hover {
    color: #fff !important;
    background: rgba(255,255,255,0.15);
    transform: translateX(8px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.nav-link.active {
    color: #fff !important;
    background: rgba(255,255,255,0.2);
    border-left: 4px solid #fff;
    transform: translateX(8px);
}

.nav-link i {
    width: 20px;
    text-align: center;
    transition: transform 0.3s ease;
}

.nav-link:hover i {
    transform: scale(1.2);
}

.nav-link.active i {
    transform: scale(1.1);
}

.nav-link.text-danger {
    background: rgba(220,53,69,0.1);
    border: 1px solid rgba(220,53,69,0.3);
}

.nav-link.text-danger:hover {
    background: rgba(220,53,69,0.2);
    color: #fff !important;
    transform: translateX(8px);
}

.sidebar-header {
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 20px;
}

.admin-info {
    background: rgba(255,255,255,0.1);
    padding: 15px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.2);
}

.admin-info .fw-bold {
    color: #fff;
    font-size: 1.1em;
}

.admin-info .small-muted {
    color: rgba(255,255,255,0.7);
    font-size: 0.85em;
}

.logo-container {
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    margin-bottom: 20px;
}

.logo-container img {
    filter: brightness(0) invert(1);
    transition: transform 0.3s ease;
}

.logo-container:hover img {
    transform: scale(1.05);
}

/* Smooth page transition effect */
.main-content {
    opacity: 0;
    animation: fadeIn 0.5s ease forwards;
}

@keyframes fadeIn {
    to {
        opacity: 1;
    }
}


@media (max-width: 768px) {
  .sidebar {
    min-height: auto;
    position: relative;
  }
  .main-content {
    padding: 10px;
  }
}




/* === Admin-only fix for white seams on cards and tables ===
   Paste inside the <style> block in admin/header.php
*/
.card, .card-modern {
  border: 0 !important;
  box-shadow: 0 8px 24px rgba(0,0,0,0.06) !important;
  background-clip: padding-box;
  overflow: hidden;
  border-radius: 12px !important;
}

.card .card-header, .card-header {
  border: 0 !important;
  background-clip: padding-box;
}

.table, .table-voters, table {
  border-collapse: collapse !important;
  background: transparent !important;
}
.table td, .table th, .table-voters td, .table-voters th {
  background: transparent !important;
  border: 1px solid rgba(0,0,0,0.06) !important;
  vertical-align: top;
}

.table-striped tbody tr:nth-of-type(odd) td {
  background-color: rgba(0,0,0,0.02) !important;
}

.btn:focus, .form-control:focus, .list-group-item:focus {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(128,0,0,0.06) !important;
}

.partylist-block, .pl-choice, .candidate-item, .candidate-info {
  background: transparent !important;
}







/* Sidebar seam/outlines fix — paste into admin/header.php style block */

/* Remove white/bright border and clip children at rounded corners */
.sidebar {
  border: 0 !important;                     /* remove bright outline */
  box-shadow: 0 8px 24px rgba(0,0,0,0.08) !important; /* subtle depth */
  background-clip: padding-box;             /* prevent inner backgrounds leaking */
  overflow: hidden;                         /* clip children to rounded corners */
  border-radius: 12px !important;           /* match card radius */
  padding: 18px 0 !important;               /* keep spacing */
  background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.00)) , linear-gradient(135deg, #e08b22 0%, #b26200 100%); /* preserve gradient look if desired */
}

/* Make inner sidebar panels transparent and avoid extra borders */
.logo-container, .sidebar-header, .admin-info {
  background: transparent !important;
  border: 0 !important;
}

/* Nav links: remove inner pale backgrounds that cause seams, keep hover styles */
.nav-link {
  background: transparent !important;
  border: 0 !important;
  box-shadow: none !important;
  color: rgba(255,255,255,0.9) !important;
}

/* Ensure active and hover states still look distinct without hard white lines */
.nav-link:hover, .nav-link.active {
  background: rgba(255,255,255,0.06) !important;
  transform: translateX(6px);
  color: #fff !important;
}

/* Remove any small white outlines on link focus but keep accessible ring */
.nav-link:focus, .nav-link:active {
  outline: none !important;
  box-shadow: 0 0 0 3px rgba(255,255,255,0.06) !important;
}

/* Ensure list-group / items inside sidebar don't introduce borders */
ul.nav, .nav-item, .list-group-item {
  background: transparent !important;
  border: 0 !important;
}

/* If there were thin separators, soften them */
.sidebar .logo-container, .sidebar-header {
  border-bottom: 1px solid rgba(255,255,255,0.04);
}

/* Small tweak to keep the highlight color subtle (avoid bright strokes) */
.nav-link.text-danger {
  background: rgba(220,53,69,0.08) !important;
  border: 0 !important;
}

/* Optional: if you still see a 1px seam due to subpixel rendering, enable this debug helper temporarily:
.sidebar { transform: translateZ(0); -webkit-backface-visibility: hidden; }
*/
</style>

<div class="row">
  <div class="col-md-3 col-lg-2">
    <div class="sidebar">
      <div class="logo-container">
        <img src="/votesure_reborn/votesurelogo.png" style="height:64px" alt="VoteSure Logo">
      </div>
      <div class="sidebar-header">
        <div class="admin-info">
          <div class="fw-bold">Admin Panel</div>
          <div class="small-muted">Manage Everything</div>
        </div>
      </div>
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'elections.php' ? 'active' : ''; ?>" href="elections.php">
            <i class="fas fa-calendar-check me-2"></i>Elections
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'partylists.php' ? 'active' : ''; ?>" href="partylists.php">
            <i class="fas fa-list-alt me-2"></i>Positions
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'candidates.php' ? 'active' : ''; ?>" href="candidates.php">
            <i class="fas fa-user-friends me-2"></i>Candidates
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'start_election.php' ? 'active' : ''; ?>" href="start_election.php">
            <i class="fas fa-power-off me-2"></i>Start / Stop
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'results.php' ? 'active' : ''; ?>" href="results.php">
            <i class="fas fa-chart-bar me-2"></i>Results
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'register_voters.php' ? 'active' : ''; ?>" href="register_voters.php">
            <i class="fas fa-chart-bar me-2"></i>Register Voters
          </a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
            <i class="fas fa-file-csv me-2"></i>Reports
          </a>
        </li>

        
        <li class="nav-item">
          <a class="nav-link text-danger" href="logout.php">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
          </a>
        </li>

      </ul>
    </div>
  </div>
  <div class="col-md-9 col-lg-10">
    <div class="main-content">