<?php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['voter_student_id'])) {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['voter_student_id'];

// Double-check registration
$chk = $pdo->prepare("SELECT * FROM registered_voters WHERE student_id = ?");
$chk->execute([$student_id]);
if (!$chk->fetch()) {
    unset($_SESSION['voter_student_id']);
    $_SESSION['vote_error'] = 'You are not a registered voter.';
    header('Location: index.php');
    exit;
}

// Get active election
$stmt = $pdo->prepare("SELECT * FROM elections WHERE status = 'running' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$election = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$election) {
    die('No active election.');
}

// Get partylists for election
$stmt = $pdo->prepare('SELECT id FROM partylists WHERE election_id = ?');
$stmt->execute([$election['id']]);
$pls = $stmt->fetchAll(PDO::FETCH_COLUMN);

$expected_count = count($pls);
$choices_in = $_POST['choice'] ?? [];

if (!is_array($choices_in) || count($choices_in) < $expected_count) {
    $_SESSION['vote_error'] = 'Please select a candidate for each section.';
    header('Location: vote.php');
    exit;
}

$selected_candidate_ids = [];

foreach ($pls as $pid) {
    if (!isset($choices_in[$pid])) {
        $_SESSION['vote_error'] = 'Please complete all selections.';
        header('Location: vote.php');
        exit;
    }

    $cid = intval($choices_in[$pid]);
    $chk = $pdo->prepare('SELECT COUNT(*) FROM candidates WHERE id = ? AND partylist_id = ?');
    $chk->execute([$cid, $pid]);

    if ($chk->fetchColumn() == 0) {
        $_SESSION['vote_error'] = 'Invalid candidate selection.';
        header('Location: vote.php');
        exit;
    }

    $selected_candidate_ids[] = $cid;
}

try {
    $pdo->beginTransaction();

    // Check if already voted
    $chk = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE election_id = ? AND student_id = ?');
    $chk->execute([$election['id'], $student_id]);
    if ($chk->fetchColumn() > 0) {
        $pdo->rollBack();
        $_SESSION['vote_error'] = 'Your vote is already recorded.';
        header('Location: vote.php');
        exit;
    }

    // Record vote
    $ins = $pdo->prepare('INSERT INTO votes (election_id, student_id, choices) VALUES (?, ?, ?)');
    $ins->execute([$election['id'], $student_id, json_encode(array_values($selected_candidate_ids))]);

    // Update student status (only if record exists)
    $upd = $pdo->prepare('UPDATE students SET voted = 1 WHERE student_id = ?');
    $upd->execute([$student_id]);

    $pdo->commit();
    unset($_SESSION['voter_student_id']);

    header('Location: confirm.php?success=1');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $_SESSION['vote_error'] = 'Error saving vote: ' . $e->getMessage();
    header('Location: vote.php');
    exit;
}
