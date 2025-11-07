<?php
require_once __DIR__ . '/../config.php';
if (isset($_GET['export']) && $_GET['export']=='votes') {
    $eid = $_GET['election_id'] ?? null; if (!$eid) die('No election id');
    header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="votes_election_' . $eid . '.csv"');
    $out = fopen('php://output', 'w'); fputcsv($out, ['vote_id','election_id','student_id','choices','created_at']);
    $stmt = $pdo->prepare('SELECT * FROM votes WHERE election_id = ?'); $stmt->execute([$eid]); while ($r = $stmt->fetch()) fputcsv($out, [$r['id'],$r['election_id'],$r['student_id'],$r['choices'],$r['created_at']]);
    exit;
}
$elections = $pdo->query('SELECT * FROM elections ORDER BY id DESC')->fetchAll();
$page_title = 'Reports';
include __DIR__ . '/header.php';
?>
<div class="card card-modern p-4">
  <h4>Generate Reports</h4>
  <form class="row g-2">
    <div class="col-md-8"><select class="form-select" name="election_id"><?php foreach ($elections as $e): ?><option value="<?php echo $e['id']; ?>"><?php echo h($e['title']); ?></option><?php endforeach; ?></select></div>
    <div class="col-md-4 text-end"><button class="btn btn-violet" name="export" value="votes">Export Votes (CSV)</button></div>
  </form>
</div>
<?php include __DIR__ . '/footer.php'; ?>
