<?php
// includes/header.php
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo isset($page_title)?htmlspecialchars($page_title):'VoteSure'; ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root{
  --maroon-700:#5a0000;
  --maroon-600:#800000; /* primary */
  --maroon-500:#9b2c2c; /* accent */
  --light-gray:#f2f2f2;
  --muted:#6b7280;
  --card-radius:12px;
  --shadow: 0 10px 30px rgba(0,0,0,0.06);
  --border-color:#e6e6e6;
  --text-color:#222222;
}
*{box-sizing:border-box;}
body{
  background: linear-gradient(180deg,var(--light-gray),#ffffff);
  color:var(--text-color);
  font-family: "Segoe UI", Roboto, Arial, sans-serif;
  -webkit-font-smoothing:antialiased;
}
.topbar {
  background: linear-gradient(90deg,var(--maroon-700),var(--maroon-600));
  color:white; padding:12px 20px; border-radius:0 0 12px 12px; box-shadow: var(--shadow);
}
.brand { display:flex; align-items:center; gap:12px; font-weight:700; }
.brand img{ height:48px; width:auto; border-radius:8px; background:rgba(255,255,255,0.04); padding:6px; }
.card-modern { border:none; border-radius:var(--card-radius); box-shadow:var(--shadow); background: #fff; }
.btn-maroon { background: var(--maroon-600); border-color: transparent; color: white; }
.btn-maroon:hover { background: var(--maroon-700); }
.sidebar { min-height: calc(100vh - 92px); background: #fff; border-radius:10px; padding:12px; border:1px solid var(--border-color); box-shadow: 0 6px 18px rgba(0,0,0,0.03); }
.small-muted { color:var(--muted); font-size:13px; }
.table-actions form { display:inline-block; margin:0; }
.list-group-item .small-muted { display:block; margin-top:4px; color:var(--muted); font-size:12px; }
footer.site-footer { margin-top:40px; padding:20px 0; text-align:center; color:var(--muted); font-size:13px; }
.container-main { margin-top:18px; }
.alert-fixed { position:fixed; top:16px; right:16px; z-index:1100; }
</style>
</head>
<body>
<div class="topbar">
  <div class="container d-flex align-items-center">
    <div class="brand">
      <img src="/votesure_reborn/votesurelogo.png" alt="VoteSure">
      <div>
        <div style="font-size:18px">VoteSure</div>
        <div style="font-size:12px; opacity:0.9;">VoteSure Voting System</div>
      </div>
    </div>
    <div class="ms-auto text-end small-muted"><?php echo date('F j, Y'); ?></div>
  </div>
</div>
<div class="container container-main">
<?php
// simple flash message display (if set)
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['flash_message'])):
  $fm = $_SESSION['flash_message'];
  unset($_SESSION['flash_message']);
?>
  <div class="alert alert-<?php echo htmlspecialchars($fm['type'] ?? 'info'); ?> alert-fixed" role="alert">
    <?php echo htmlspecialchars($fm['message'] ?? ''); ?>
  </div>
<?php endif; ?>
