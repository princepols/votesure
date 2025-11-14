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
    $e = $pdo->query("SELECT * FROM elections ORDER BY id DESC LIMIT 1")->fetch(); 
    $eid = $e['id'] ?? null; 
}
if (!$eid) die('No election found.');

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

// Tally votes
$votes_stmt = $pdo->prepare('SELECT * FROM votes WHERE election_id = ?'); 
$votes_stmt->execute([$eid]);
while ($v = $votes_stmt->fetch()) { 
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
    background-color: #800000;
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
</div>

<?php include __DIR__ . '/footer.php'; ?>
