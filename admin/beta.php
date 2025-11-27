<?php
/**
 * IFEN Toolbox Admin - Beta Testing
 * Adapté: beta_testers et beta_feedback liés aux outils (tool_id)
 */
$pageTitle = 'Beta Testing';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();

$testerStatuses = [
    'registered' => ['label' => 'Inscrit', 'class' => 'info'],
    'active' => ['label' => 'Actif', 'class' => 'success'],
    'completed' => ['label' => 'Terminé', 'class' => 'completed'],
    'dropped' => ['label' => 'Abandonné', 'class' => 'cancelled'],
];

$feedbackTypes = [
    'bug' => ['label' => 'Bug', 'class' => 'danger'],
    'suggestion' => ['label' => 'Suggestion', 'class' => 'info'],
    'question' => ['label' => 'Question', 'class' => 'warning'],
    'praise' => ['label' => 'Félicitations', 'class' => 'success'],
    'general' => ['label' => 'Général', 'class' => 'secondary'],
];

$feedbackStatuses = [
    'new' => ['label' => 'Nouveau', 'class' => 'warning'],
    'reviewed' => ['label' => 'Lu', 'class' => 'info'],
    'in_progress' => ['label' => 'En cours', 'class' => 'in-progress'],
    'resolved' => ['label' => 'Résolu', 'class' => 'success'],
    'wontfix' => ['label' => 'Non traité', 'class' => 'secondary'],
];

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';

// Outils en beta
$betaTools = [];
try {
    $stmt = $pdo->query("SELECT t.*, 
        (SELECT COUNT(*) FROM toolbox_beta_testers bt WHERE bt.tool_id = t.id) as testers_count,
        (SELECT COUNT(*) FROM toolbox_beta_feedback bf WHERE bf.tool_id = t.id) as feedback_count
        FROM toolbox_tools t 
        WHERE t.status = 'beta' 
        ORDER BY t.name");
    $betaTools = $stmt->fetchAll();
} catch (PDOException $e) { $error = $e->getMessage(); }

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'update_tester') {
        $testerId = intval($_POST['tester_id'] ?? 0);
        $newStatus = $_POST['tester_status'] ?? '';
        if ($testerId && array_key_exists($newStatus, $testerStatuses)) {
            try {
                $pdo->prepare("UPDATE toolbox_beta_testers SET status = ? WHERE id = ?")->execute([$newStatus, $testerId]);
                setFlash('success', 'Statut mis à jour.');
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'update_feedback') {
        $feedbackId = intval($_POST['feedback_id'] ?? 0);
        $newStatus = $_POST['feedback_status'] ?? '';
        $response = trim($_POST['admin_response'] ?? '');
        if ($feedbackId && array_key_exists($newStatus, $feedbackStatuses)) {
            try {
                $pdo->prepare("UPDATE toolbox_beta_feedback SET status = ?, admin_response = ? WHERE id = ?")->execute([$newStatus, $response, $feedbackId]);
                setFlash('success', 'Feedback mis à jour.');
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'delete_tester') {
        $testerId = intval($_POST['tester_id'] ?? 0);
        if ($testerId) {
            try { $pdo->prepare("DELETE FROM toolbox_beta_testers WHERE id = ?")->execute([$testerId]); setFlash('success', 'Supprimé.'); } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'delete_feedback') {
        $feedbackId = intval($_POST['feedback_id'] ?? 0);
        if ($feedbackId) {
            try { $pdo->prepare("DELETE FROM toolbox_beta_feedback WHERE id = ?")->execute([$feedbackId]); setFlash('success', 'Supprimé.'); } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
}

// Vue détaillée d'un outil beta
$currentTool = null;
$testers = [];
$feedbacks = [];
if ($action === 'view' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_tools WHERE id = ?");
    $stmt->execute([$id]);
    $currentTool = $stmt->fetch();
    if (!$currentTool) { setFlash('error', 'Outil non trouvé.'); header('Location: ' . url('beta.php')); exit; }
    
    $stmt = $pdo->prepare("SELECT * FROM toolbox_beta_testers WHERE tool_id = ? ORDER BY registered_at DESC");
    $stmt->execute([$id]);
    $testers = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT * FROM toolbox_beta_feedback WHERE tool_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $feedbacks = $stmt->fetchAll();
}

// Stats globales
$totalTesters = 0;
$totalFeedbacks = 0;
$newFeedbacks = 0;
try {
    $totalTesters = $pdo->query("SELECT COUNT(*) FROM toolbox_beta_testers")->fetchColumn();
    $totalFeedbacks = $pdo->query("SELECT COUNT(*) FROM toolbox_beta_feedback")->fetchColumn();
    $newFeedbacks = $pdo->query("SELECT COUNT(*) FROM toolbox_beta_feedback WHERE status = 'new'")->fetchColumn();
} catch (PDOException $e) {}
?>

<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon violet"><i class="fas fa-flask"></i></div><div class="stat-content"><div class="stat-value"><?= count($betaTools) ?></div><div class="stat-label">Outils en beta</div></div></div>
    <div class="stat-card"><div class="stat-icon cyan"><i class="fas fa-users"></i></div><div class="stat-content"><div class="stat-value"><?= $totalTesters ?></div><div class="stat-label">Testeurs</div></div></div>
    <div class="stat-card"><div class="stat-icon success"><i class="fas fa-comments"></i></div><div class="stat-content"><div class="stat-value"><?= $totalFeedbacks ?></div><div class="stat-label">Feedbacks</div></div></div>
    <div class="stat-card"><div class="stat-icon warning"><i class="fas fa-exclamation"></i></div><div class="stat-content"><div class="stat-value"><?= $newFeedbacks ?></div><div class="stat-label">Nouveaux</div></div></div>
</div>

<?php if ($action === 'view' && $currentTool): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-flask"></i> <?= e($currentTool['name']) ?> <span class="badge badge-warning">BETA</span></h3>
        <a href="<?= url('beta.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
    <div class="card-body">
        <p><?= e($currentTool['short_description']) ?></p>
        <div class="form-row">
            <div><strong>Période beta:</strong> <?= $currentTool['beta_start_date'] ? formatDate($currentTool['beta_start_date'], 'd/m/Y') : '-' ?> → <?= $currentTool['beta_end_date'] ? formatDate($currentTool['beta_end_date'], 'd/m/Y') : '-' ?></div>
        </div>
    </div>
</div>

<!-- Testeurs -->
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-users"></i> Testeurs <span class="badge badge-secondary"><?= count($testers) ?></span></h3></div>
    <?php if (empty($testers)): ?><div class="empty-state"><i class="fas fa-users"></i><h3>Aucun testeur</h3></div>
    <?php else: ?>
        <table class="table"><thead><tr><th>Nom</th><th>Email</th><th>Inscrit le</th><th>Statut</th><th class="text-right">Actions</th></tr></thead><tbody>
            <?php foreach ($testers as $t): $ts = $testerStatuses[$t['status']] ?? ['label' => $t['status'], 'class' => 'secondary']; ?>
                <tr><td><?= e($t['user_name']) ?></td><td><?= e($t['user_email']) ?></td><td><small><?= formatRelativeDate($t['registered_at']) ?></small></td>
                <td><form method="POST" class="d-flex gap-1"><input type="hidden" name="action" value="update_tester"><input type="hidden" name="tester_id" value="<?= $t['id'] ?>"><select name="tester_status" class="form-control" style="width:auto;padding:0.25rem;" onchange="this.form.submit()"><?php foreach ($testerStatuses as $k => $s): ?><option value="<?= $k ?>" <?= $t['status'] === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select></form></td>
                <td><form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_tester"><input type="hidden" name="tester_id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-icon btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form></td></tr>
            <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</div>

<!-- Feedbacks -->
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-comments"></i> Feedbacks <span class="badge badge-secondary"><?= count($feedbacks) ?></span></h3></div>
    <?php if (empty($feedbacks)): ?><div class="empty-state"><i class="fas fa-comments"></i><h3>Aucun feedback</h3></div>
    <?php else: ?>
        <?php foreach ($feedbacks as $f): $ft = $feedbackTypes[$f['feedback_type']] ?? ['label' => $f['feedback_type'], 'class' => 'secondary']; $fs = $feedbackStatuses[$f['status']] ?? ['label' => $f['status'], 'class' => 'secondary']; ?>
            <div class="card-body" style="border-bottom: 1px solid var(--border-color);">
                <div class="d-flex justify-between mb-1">
                    <div><strong><?= e($f['user_name'] ?? 'Anonyme') ?></strong> <span class="badge badge-<?= $ft['class'] ?>"><?= $ft['label'] ?></span> <span class="badge badge-<?= $fs['class'] ?>"><?= $fs['label'] ?></span></div>
                    <small class="text-muted"><?= formatRelativeDate($f['created_at']) ?></small>
                </div>
                <?php if ($f['title']): ?><h4><?= e($f['title']) ?></h4><?php endif; ?>
                <p><?= nl2br(e($f['content'])) ?></p>
                <?php if ($f['rating']): ?><p><strong>Note:</strong> <?= str_repeat('⭐', $f['rating']) ?></p><?php endif; ?>
                <?php if ($f['admin_response']): ?><div style="background:#e8f4fd;padding:0.75rem;border-radius:var(--border-radius);border-left:3px solid var(--ifen-violet);margin-top:0.5rem;"><strong>Réponse:</strong> <?= e($f['admin_response']) ?></div><?php endif; ?>
                <form method="POST" class="mt-2 d-flex gap-1">
                    <input type="hidden" name="action" value="update_feedback">
                    <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                    <select name="feedback_status" class="form-control" style="width:auto;"><?php foreach ($feedbackStatuses as $k => $s): ?><option value="<?= $k ?>" <?= $f['status'] === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select>
                    <input type="text" name="admin_response" class="form-control" placeholder="Réponse..." value="<?= e($f['admin_response'] ?? '') ?>" style="flex:1;">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_feedback"><input type="hidden" name="feedback_id" value="<?= $f['id'] ?>"><button class="btn btn-sm btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Liste des outils en beta -->
<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-flask"></i> Outils en Beta <span class="badge badge-secondary"><?= count($betaTools) ?></span></h3></div>
    <?php if (empty($betaTools)): ?>
        <div class="empty-state"><i class="fas fa-flask"></i><h3>Aucun outil en beta</h3><p>Passez un outil en statut "Beta" pour le voir apparaître ici.</p></div>
    <?php else: ?>
        <table class="table"><thead><tr><th>Outil</th><th>Période</th><th>Testeurs</th><th>Feedbacks</th><th class="text-right">Actions</th></tr></thead><tbody>
            <?php foreach ($betaTools as $t): ?>
                <tr>
                    <td><strong><?= e($t['name']) ?></strong><br><small class="text-muted"><?= e(mb_substr($t['short_description'], 0, 50)) ?>...</small></td>
                    <td><small><?= $t['beta_start_date'] ? formatDate($t['beta_start_date'], 'd/m/Y') : '-' ?> → <?= $t['beta_end_date'] ? formatDate($t['beta_end_date'], 'd/m/Y') : '-' ?></small></td>
                    <td><span class="badge badge-info"><?= $t['testers_count'] ?></span></td>
                    <td><span class="badge badge-secondary"><?= $t['feedback_count'] ?></span></td>
                    <td><a href="<?= url("beta.php?action=view&id={$t['id']}") ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> Gérer</a></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
