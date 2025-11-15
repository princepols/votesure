<?php
require_once __DIR__ . '/../config.php';
session_start();

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Get election
$eid = $_GET['election_id'] ?? null;
if (!$eid) { 
    // If no election id provided, try to get the latest
    $e = $pdo->query("SELECT * FROM elections ORDER BY id DESC LIMIT 1")->fetch(); 
    $eid = $e['id'] ?? null; 
}

if (!$eid) {
    // Show a styled "No election ongoing" message within the admin layout
    $page_title = 'Election Results';
    include __DIR__ . '/header.php';
    ?>
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card card-modern p-4 mt-4 text-center" style="border-top:8px solid #800000;">
          <div style="font-size:48px; color:#800000; margin-bottom:12px;">
            <i class="fas fa-calendar-times"></i>
          </div>
          <h3 class="mb-2">No election ongoing</h3>
          <p class="small-muted mb-3">There is currently no active election. Start a new election from the admin panel to view results here.</p>
          <div class="d-flex justify-content-center gap-2">
            <a class="btn btn-violet" href="elections.php"><i class="fas fa-calendar-plus me-2"></i>Create Election</a>
            <a class="btn btn-outline-secondary" href="dashboard.php"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
          </div>
        </div>
      </div>
    </div>
    <?php
    include __DIR__ . '/footer.php';
    exit;
}

$election_stmt = $pdo->prepare('SELECT * FROM elections WHERE id = ?'); 
$election_stmt->execute([$eid]); 
$election = $election_stmt->fetch();

$stmt = $pdo->prepare('
    SELECT p.id AS pid, p.name AS pname,
           c.id AS cid, c.name AS cname, c.position, c.photo
    FROM partylists p
    LEFT JOIN candidates c ON p.id = c.partylist_id
    WHERE p.election_id = ?
    ORDER BY p.id, c.id
');
$stmt->execute([$eid]); 
$rows = $stmt->fetchAll();

$partylists = [];
foreach ($rows as $r) { 
    if (!isset($partylists[$r['pid']])) {
        $partylists[$r['pid']] = [
            'name' => $r['pname'],
            'candidates' => []
        ];
    }
    if ($r['cid']) {
        $partylists[$r['pid']]['candidates'][$r['cid']] = [
            'name' => $r['cname'],
            'position' => $r['position'],
            'photo' => $r['photo'],
            'votes' => 0
        ];
    }
}

// Build candidate map (id => [name, position, partylist_id, partylist_name]) for quick lookups later
$candidate_map = [];
foreach ($partylists as $plid => $pl) {
    foreach ($pl['candidates'] as $cid => $cdata) {
        $candidate_map[$cid] = [
            'name' => $cdata['name'],
            'position' => $cdata['position'],
            'partylist_id' => $plid,
            'partylist_name' => $pl['name']
        ];
    }
}

// Tally votes
$votes_stmt = $pdo->prepare('SELECT * FROM votes WHERE election_id = ?'); 
$votes_stmt->execute([$eid]);
$all_votes = $votes_stmt->fetchAll();
foreach ($all_votes as $v) { 
    $choices = json_decode($v['choices'], true); 
    if (is_array($choices)) { 
        foreach ($choices as $cand_id) { 
            foreach ($partylists as &$pl) { 
                if (isset($pl['candidates'][$cand_id])) { 
                    $pl['candidates'][$cand_id]['votes']++; 
                } 
            } 
            unset($pl); 
        } 
    } 
}

// Build votes by student mapping so we can show which candidates each voter selected
$votes_by_student = [];
foreach ($all_votes as $v) {
    $sid = trim($v['student_id']);
    $choices = json_decode($v['choices'], true);
    if (!is_array($choices)) $choices = [];
    $votes_by_student[$sid] = [
        'choices' => $choices,
        'created_at' => $v['created_at'] ?? null,
        'vote_id' => $v['id'] ?? null
    ];
}

// Fetch registered voters for Voter Logs
$voters = $pdo->query('SELECT * FROM registered_voters ORDER BY created_at DESC')->fetchAll();

$page_title = 'Election Results';
include __DIR__ . '/header.php';
$upload_url = '/votesure_reborn/uploads/';
?>

<style>
body {
    background-color: #fafafa;
}

.results-wrapper {
    padding: 20px;
    max-width: 1000px;
    margin: auto;
}

.card {
    border: 1px solid #ddd;
    border-radius: 10px;
    margin-bottom: 25px;
    background-color: #fff;
    box-shadow: 0 3px 8px rgba(0,0,0,0.05);
}

.card-header {
    background-color: #FF9500; /*800000 */
    color: white;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
    font-weight: 600;
}

.candidate-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    border-bottom: 1px solid #eee;
}

.candidate-item:last-child {
    border-bottom: none;
}

.candidate-photo {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
    flex-shrink: 0;
    background-color: #f4f4f4;
    display: flex;
    align-items: center;
    justify-content: center;
}

.candidate-photo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.candidate-info {
    flex: 1;
}

.candidate-info h6 {
    margin: 0;
    font-weight: 600;
}

.candidate-info small {
    color: #888;
}

.vote-box {
    text-align: center;
    min-width: 80px;
}

.vote-box .num {
    font-weight: 700;
    font-size: 1.3rem;
    color: #800000;
}

.winner {
    background-color: #f6fff7;
    border-left: 4px solid #0cae25;
}

.summary-box {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
}

.summary-item {
    flex: 1 1 200px;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.summary-item .num {
    font-size: 1.5rem;
    font-weight: bold;
    color: #800000;
}

/* Voter logs table */
.table-voters {
    width: 100%;
    border-collapse: collapse;
}

.table-voters th, .table-voters td {
    border: 1px solid #e9e9e9;
    padding: 10px 12px;
    text-align: left;
    vertical-align: top;
}

.table-voters th {
    background: #f8f8f8;
    font-weight: 600;
    color: #333;
}

.small-muted { color: #888; }
.partylist-block { margin-bottom: 8px; }
.partylist-block .pl-name { font-weight: 700; display:block; margin-bottom:4px; }
.partylist-block .pl-choice { margin-left:8px; }
</style>

<div class="results-wrapper">
    <h2 class="mb-4 text-center"><?= h($election['title']) ?> - Results</h2>

    <div class="summary-box">
        <div class="summary-item">
            <div class="num"><?= count($partylists) ?></div>
            <div>Partylists</div>
        </div>
        <div class="summary-item">
            <div class="num">
                <?= array_sum(array_map(fn($pl) => count($pl['candidates']), $partylists)) ?>
            </div>
            <div>Candidates</div>
        </div>
        <div class="summary-item">
            <div class="num">
                <?php 
                $total_votes = 0;
                foreach ($partylists as $pl) {
                    foreach ($pl['candidates'] as $c) $total_votes += $c['votes'];
                }
                echo $total_votes;
                ?>
            </div>
            <div>Total Votes</div>
        </div>
    </div>

    <?php if (empty($partylists)): ?>
        <div class="alert alert-secondary text-center">No partylists or candidates available.</div>
    <?php endif; ?>

    <?php foreach ($partylists as $plid => $pl): 
        if (empty($pl['candidates'])) continue;

        $winner_id = null;
        $max_votes = 0;
        foreach ($pl['candidates'] as $cid => $c) {
            if ($c['votes'] > $max_votes) {
                $max_votes = $c['votes'];
                $winner_id = $cid;
            }
        }
    ?>
        <div class="card">
            <div class="card-header">
                <?= h($pl['name']) ?>
            </div>
            <div class="card-body p-0">
                <?php foreach ($pl['candidates'] as $cid => $c): 
                    $is_winner = ($cid === $winner_id && $c['votes'] > 0);
                    $photo = $c['photo'] ? $upload_url . $c['photo'] : '';
                ?>
                    <div class="candidate-item <?= $is_winner ? 'winner' : '' ?>">
                        <div class="candidate-photo">
                            <?php if ($photo && file_exists(__DIR__ . '/../uploads/' . basename($c['photo']))): ?>
                                <img src="<?= $photo ?>" alt="<?= h($c['name']) ?>">
                            <?php else: ?>
                                <i class="fas fa-user text-secondary"></i>
                            <?php endif; ?>
                        </div>
                        <div class="candidate-info">
                            <h6><?= h($c['name']) ?></h6>
                            <small><?= h($c['position'] ?: 'Candidate') ?></small>
                            <?php if ($is_winner): ?>
                                <div><span class="badge bg-success">Winner</span></div>
                            <?php endif; ?>
                        </div>
                        <div class="vote-box">
                            <div class="num"><?= $c['votes'] ?></div>
                            <small>votes</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Voter Logs Section -->
    <div class="card">
      <div class="card-header">
        Voter Logs
      </div>
      <div class="card-body">
        <?php if (empty($voters)): ?>
          <div class="alert alert-info">No registered voters found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-striped table-voters">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Student ID</th>
                  <th>Name</th>
                  <th>Course</th>
                  <th>Year</th>
                  <th>Added</th>
                  <th>Voted Candidates</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($voters as $v): 
                    $sid = trim($v['student_id']);
                    $choices = $votes_by_student[$sid]['choices'] ?? [];
                    // group chosen candidates by partylist name
                    $by_party = [];
                    if (is_array($choices) && count($choices) > 0) {
                        foreach ($choices as $vcid) {
                            if (isset($candidate_map[$vcid])) {
                                $pl_name = $candidate_map[$vcid]['partylist_name'] ?? 'Unknown';
                                $pos = $candidate_map[$vcid]['position'] ?? '';
                                $name = $candidate_map[$vcid]['name'] ?? ('#' . intval($vcid));
                                $by_party[$pl_name][] = ['position' => $pos, 'name' => $name];
                            } else {
                                $by_party['Unknown'][] = ['position' => '', 'name' => '#' . intval($vcid)];
                            }
                        }
                    }
                ?>
                  <tr>
                    <td><?= h($v['id']) ?></td>
                    <td><?= h($v['student_id']) ?></td>
                    <td><?= h($v['student_name']) ?></td>
                    <td><?= h($v['course']) ?></td>
                    <td><?= h($v['year_level']) ?></td>
                    <td><?= h($v['created_at']) ?></td>
                    <td>
                      <?php if (empty($by_party)): ?>
                        <span class="small-muted">Not voted</span>
                      <?php else: ?>
                        <?php foreach ($by_party as $pl_name => $items): ?>
                          <div class="partylist-block">
                            <span class="pl-name"><?= h($pl_name) ?>:</span>
                            <?php foreach ($items as $it): ?>
                              <div class="pl-choice"><?= ($it['position'] ? h($it['position']) . ': ' : '') . h($it['name']) ?></div>
                            <?php endforeach; ?>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>