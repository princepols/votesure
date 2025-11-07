<?php
require_once __DIR__ . '/../config.php';
session_start();
if (empty($_SESSION['voter_student_id'])) { header('Location: index.php'); exit; }
$student_id = $_SESSION['voter_student_id'];
$stmt = $pdo->prepare("SELECT * FROM elections WHERE status = 'running' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$election = $stmt->fetch();
if (!$election) { die('No active election. Please contact administrator.'); }
$stmt = $pdo->prepare('SELECT p.id AS pid, p.name AS pname, c.id AS cid, c.name AS cname, c.photo AS cphoto, c.position AS cpos FROM partylists p LEFT JOIN candidates c ON p.id = c.partylist_id WHERE p.election_id = ? ORDER BY p.id, c.id');
$stmt->execute([$election['id']]);
$rows = $stmt->fetchAll();
$partylists = [];
foreach ($rows as $r) {
    if (!isset($partylists[$r['pid']])) $partylists[$r['pid']] = ['name'=>$r['pname'],'candidates'=>[]];
    if ($r['cid']) $partylists[$r['pid']]['candidates'][] = ['id'=>$r['cid'],'name'=>$r['cname'],'photo'=>$r['cphoto'],'position'=>$r['cpos']];
}
$page_title = 'Cast Your Vote';
include __DIR__ . '/../includes/header.php';
?>
<div class="row">
  <div class="col-md-10 offset-md-1">
    <div class="card card-modern p-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-0"><?php echo h($election['title']); ?></h4>
          <div class="small-muted"><?php echo h($election['description']); ?></div>
        </div>
        <div class="text-end small-muted">Voter: <?php echo h($student_id); ?></div>
      </div>

      <form method="post" id="voteForm" action="vote_submit.php">
        <?php foreach ($partylists as $pid => $p): ?>
          <div class="mb-4">
            <label class="form-label fw-semibold"><?php echo h($p['name']); ?></label>
            <div class="row g-3">
              <?php if (count($p['candidates'])===0): ?>
                <div class="col-12"><p class="text-muted">No candidates in this partylist.</p></div>
              <?php else: ?>
                <?php foreach ($p['candidates'] as $cand): ?>
                  <div class="col-md-3 col-sm-6">
                    <div class="card candidate-card p-2" data-pid="<?php echo intval($pid); ?>" data-cid="<?php echo intval($cand['id']); ?>">
                      <div style="height:140px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <?php if ($cand['photo']): ?>
                          <img src="/votesure_reborn/uploads/<?php echo h($cand['photo']); ?>" style="max-width:100%; max-height:100%; border-radius:8px;">
                        <?php else: ?>
                          <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#f8f9fb;border-radius:8px;">
                            <i class="fas fa-user fa-3x" style="color:var(--muted)"></i>
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="p-2">
                        <div class="fw-semibold"><?php echo h($cand['name']); ?></div>
                        <div class="small-muted"><?php echo h($cand['position']); ?></div>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                          <div class="form-check">
                            <input class="form-check-input radio-choice" type="radio" name="choice[<?php echo intval($pid); ?>]" value="<?php echo intval($cand['id']); ?>" id="c_<?php echo intval($cand['id']); ?>">
                          </div>
                          <button type="button" class="btn btn-sm btn-outline-secondary select-btn">Select</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="d-flex justify-content-between">
          <a class="btn btn-outline-secondary" href="index.php">Cancel</a>
          <button class="btn btn-violet btn-lg" type="button" id="reviewBtn"><i class="fas fa-check-circle"></i> Review & Submit</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content card-modern p-3">
      <div class="modal-header border-0">
        <h5 class="modal-title">Confirm your choices</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="confirmBody"></div>
      <div class="modal-footer border-0">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
        <button class="btn btn-violet" id="submitVote">Submit Vote</button>
      </div>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.candidate-card .select-btn').forEach(btn=>{
  btn.addEventListener('click', function(){
    const card = this.closest('.candidate-card');
    const pid = card.dataset.pid, cid = card.dataset.cid;
    const radio = card.querySelector('.radio-choice');
    radio.checked = true;
    card.parentElement.querySelectorAll('.candidate-card').forEach(c=>c.classList.remove('selected'));
    card.classList.add('selected');
  });
});
document.querySelectorAll('.candidate-card').forEach(card=>{
  card.addEventListener('click', function(e){
    if (e.target.tagName.toLowerCase() === 'input' || e.target.closest('button')) return;
    const radio = this.querySelector('.radio-choice');
    if (radio) radio.checked = true;
    this.parentElement.querySelectorAll('.candidate-card').forEach(c=>c.classList.remove('selected'));
    this.classList.add('selected');
  });
});
document.getElementById('reviewBtn').addEventListener('click', function(){
  const selected = Array.from(document.querySelectorAll('.radio-choice:checked'));
  const expected = document.querySelectorAll('.form-label.fw-semibold').length;
  if (selected.length < expected) {
    alert('Please select one candidate for each section.');
    return;
  }
  let html = '<ul class="list-group">';
  selected.forEach(s=>{
    const lbl = s.closest('.card').querySelector('.fw-semibold').innerText;
    const pos = s.closest('.card').querySelector('.small-muted').innerText;
    html += '<li class="list-group-item"><strong>'+lbl+'</strong><br><small class="text-muted">'+pos+'</small></li>';
  });
  html += '</ul>';
  document.getElementById('confirmBody').innerHTML = html;
  var modal = new bootstrap.Modal(document.getElementById('confirmModal'));
  modal.show();
});
document.getElementById('submitVote').addEventListener('click', function(){
  document.getElementById('voteForm').submit();
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
