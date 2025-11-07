<?php
require_once __DIR__ . '/../config.php';
$eid = $_GET['election_id'] ?? null;
if (!$eid) { $e = $pdo->query("SELECT * FROM elections ORDER BY id DESC LIMIT 1")->fetch(); $eid = $e['id'] ?? null; }
if (!$eid) die('No election.');
$election = $pdo->prepare('SELECT * FROM elections WHERE id = ?'); $election->execute([$eid]); $election = $election->fetch();
$stmt = $pdo->prepare('SELECT p.id AS pid, p.name AS pname, c.id AS cid, c.name AS cname FROM partylists p LEFT JOIN candidates c ON p.id = c.partylist_id WHERE p.election_id = ? ORDER BY p.id, c.id');
$stmt->execute([$eid]); $rows = $stmt->fetchAll();
$partylists = [];
foreach ($rows as $r) { if (!isset($partylists[$r['pid']])) $partylists[$r['pid']] = ['name'=>$r['pname'],'candidates'=>[]]; if ($r['cid']) $partylists[$r['pid']]['candidates'][$r['cid']] = ['name'=>$r['cname'],'votes'=>0]; }
$votes = $pdo->prepare('SELECT * FROM votes WHERE election_id = ?'); $votes->execute([$eid]);
while ($v = $votes->fetch()) { $choices = json_decode($v['choices'], true); if (is_array($choices)) { foreach ($choices as $cand_id) { foreach ($partylists as $pid => &$pl) { if (isset($pl['candidates'][$cand_id])) { $pl['candidates'][$cand_id]['votes']++; } } unset($pl); } } }
$page_title = 'Results';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Results — <?php echo h($election['title']); ?></h4>
    <div>
      <a class="btn btn-outline-secondary" href="reports.php?export=votes&election_id=<?php echo $eid; ?>">Export CSV</a>
    </div>
  </div>

  <?php foreach ($partylists as $plid => $pl): ?>
    <h5><?php echo h($pl['name']); ?></h5>
    <div class="row g-3 mb-4">
      <div class="col-md-6">
        <ul class="list-group">
          <?php foreach ($pl['candidates'] as $cid => $c): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <?php echo h($c['name']); ?> <span class="badge bg-primary rounded-pill"><?php echo intval($c['votes']); ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-md-6">
        <canvas id="chart_<?php echo $plid; ?>" height="220"></canvas>
        <script>
          (function(){
            const labels = <?php echo json_encode(array_map(function($c){return $c['name'];}, $pl['candidates'])); ?>;
            const data = <?php echo json_encode(array_map(function($c){return intval($c['votes']);}, $pl['candidates'])); ?>;
            const ctx = document.getElementById('chart_<?php echo $plid; ?>').getContext('2d');
            new Chart(ctx, {
              type: 'bar',
              data: { labels: labels, datasets: [{ label: 'Votes', data: data, backgroundColor: 'rgba(139,92,246,0.7)' }] }, 
              options: { responsive:true, plugins:{legend:{display:false}} }
            });
          })();
        </script>
      </div>
    </div>
  <?php endforeach; ?>

</div>
<?php include __DIR__ . '/footer.php'; ?>
