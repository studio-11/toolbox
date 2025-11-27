<?php
/**
 * IFEN Toolbox Admin - Idées
 * Adapté: problem/details, votes_count, statuts existants
 */
$pageTitle = 'Idées';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();

$ideaStatuses = [
    'proposed' => ['label' => 'Proposée', 'class' => 'warning', 'icon' => 'inbox'],
    'in_progress' => ['label' => 'En cours', 'class' => 'in-progress', 'icon' => 'spinner'],
    'completed' => ['label' => 'Réalisée', 'class' => 'completed', 'icon' => 'check'],
    'rejected' => ['label' => 'Rejetée', 'class' => 'cancelled', 'icon' => 'times'],
];

$ideaTypes = [
    'course' => 'Activité de cours',
    'platform' => 'Plateforme',
    'improvement' => 'Amélioration',
];

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'update_status' && $id > 0) {
        $newStatus = $_POST['status'] ?? '';
        if (array_key_exists($newStatus, $ideaStatuses)) {
            try {
                $stmt = $pdo->prepare("UPDATE toolbox_ideas SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $id]);
                if ($newStatus === 'completed') {
                    $pdo->prepare("UPDATE toolbox_ideas SET completed_at = NOW() WHERE id = ? AND completed_at IS NULL")->execute([$id]);
                }
                setFlash('success', 'Statut mis à jour.'); header('Location: ' . url('ideas.php')); exit;
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'delete_idea' && $id > 0) {
        try {
            $pdo->prepare("DELETE FROM toolbox_votes WHERE idea_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_idea_planning WHERE idea_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM toolbox_ideas WHERE id = ?")->execute([$id]);
            setFlash('success', 'Supprimée.'); header('Location: ' . url('ideas.php')); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

$currentIdea = null;
$planning = null;
if ($action === 'view' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_ideas WHERE id = ?");
    $stmt->execute([$id]);
    $currentIdea = $stmt->fetch();
    if (!$currentIdea) { setFlash('error', 'Non trouvée.'); header('Location: ' . url('ideas.php')); exit; }
    $stmt = $pdo->prepare("SELECT * FROM toolbox_idea_planning WHERE idea_id = ?");
    $stmt->execute([$id]);
    $planning = $stmt->fetch();
}

$filterStatus = $_GET['filter_status'] ?? '';
$filterType = $_GET['filter_type'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM toolbox_ideas WHERE 1=1";
$params = [];
if ($filterStatus) { $sql .= " AND status = ?"; $params[] = $filterStatus; }
if ($filterType) { $sql .= " AND type = ?"; $params[] = $filterType; }
if ($search) { $sql .= " AND (title LIKE ? OR problem LIKE ? OR user_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY CASE status WHEN 'proposed' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END, votes_count DESC, created_at DESC";

$ideas = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $ideas = $stmt->fetchAll(); } catch (PDOException $e) { $error = $e->getMessage(); }

$statsByStatus = [];
try { $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM toolbox_ideas GROUP BY status"); while ($r = $stmt->fetch()) { $statsByStatus[$r['status']] = $r['count']; } } catch (PDOException $e) {}
?>

<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
    <?php foreach ($ideaStatuses as $key => $status): ?>
        <a href="<?= url("ideas.php?filter_status=$key") ?>" class="stat-card" style="text-decoration: none;">
            <div class="stat-icon <?= $key === 'proposed' ? 'warning' : ($key === 'completed' ? 'success' : ($key === 'rejected' ? 'danger' : 'cyan')) ?>" style="width: 40px; height: 40px;">
                <i class="fas fa-<?= $status['icon'] ?>" style="font-size: 1rem;"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value"><?= $statsByStatus[$key] ?? 0 ?></div>
                <div class="stat-label"><?= $status['label'] ?></div>
            </div>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($action === 'view' && $currentIdea): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-lightbulb"></i> <?= e($currentIdea['title']) ?></h3>
        <a href="<?= url('ideas.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-arrow-left"></i> Retour</a>
    </div>
    <div class="card-body">
        <?php $is = $ideaStatuses[$currentIdea['status']] ?? ['label' => $currentIdea['status'], 'class' => 'secondary']; ?>
        <div class="d-flex gap-2 mb-2">
            <span class="badge badge-<?= $is['class'] ?>"><?= $is['label'] ?></span>
            <span class="badge badge-info"><?= $ideaTypes[$currentIdea['type']] ?? $currentIdea['type'] ?></span>
            <span class="badge badge-secondary"><i class="fas fa-thumbs-up"></i> <?= $currentIdea['votes_count'] ?? 0 ?> votes</span>
        </div>
        
        <div class="form-row"><div><p><strong>Auteur:</strong> <?= e($currentIdea['user_name'] ?: 'Anonyme') ?></p><p><strong>Email:</strong> <?= e($currentIdea['user_email'] ?: '-') ?></p></div><div><p><strong>Soumis le:</strong> <?= formatDate($currentIdea['created_at']) ?></p></div></div>
        
        <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border-color);">
        <h4>Problème / Besoin</h4>
        <p style="white-space: pre-wrap; background: var(--bg-body); padding: 1rem; border-radius: var(--border-radius);"><?= e($currentIdea['problem']) ?></p>
        
        <?php if ($currentIdea['details']): ?>
            <h4>Détails</h4>
            <p style="white-space: pre-wrap; background: var(--bg-body); padding: 1rem; border-radius: var(--border-radius);"><?= e($currentIdea['details']) ?></p>
        <?php endif; ?>
        
        <?php if ($planning): ?>
            <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border-color);">
            <h4>Planning</h4>
            <div class="form-row"><div><strong>Phase:</strong> <?= e($planning['current_phase']) ?></div><div><strong>Priorité:</strong> <?= e($planning['priority']) ?></div><div><strong>Progression:</strong> <?= $planning['progress_percent'] ?>%</div><div><strong>Assigné à:</strong> <?= e($planning['assigned_to'] ?: '-') ?></div></div>
        <?php endif; ?>
        
        <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--border-color);">
        <form method="POST"><input type="hidden" name="action" value="update_status">
            <div class="form-row"><div class="form-group"><label class="form-label">Changer le statut</label><select name="status" class="form-control"><?php foreach ($ideaStatuses as $k => $s): ?><option value="<?= $k ?>" <?= $currentIdea['status'] === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select></div></div>
            <div class="btn-group"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Mettre à jour</button></div>
        </form>
        <form method="POST" style="margin-top:1rem;" onsubmit="return confirm('Supprimer définitivement ?');"><input type="hidden" name="action" value="delete_idea"><input type="hidden" name="id" value="<?= $currentIdea['id'] ?>"><button class="btn btn-danger"><i class="fas fa-trash"></i> Supprimer</button></form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><h3 class="card-title"><i class="fas fa-lightbulb"></i> Idées <span class="badge badge-secondary"><?= count($ideas) ?></span></h3></div>
    <div class="card-body" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <form method="GET" class="toolbar" style="margin-bottom: 0;">
            <div class="search-box"><i class="fas fa-search"></i><input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= e($search) ?>"></div>
            <div class="filters">
                <select name="filter_status" class="form-control" onchange="this.form.submit()"><option value="">Statut</option><?php foreach ($ideaStatuses as $k => $s): ?><option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $s['label'] ?></option><?php endforeach; ?></select>
                <select name="filter_type" class="form-control" onchange="this.form.submit()"><option value="">Type</option><?php foreach ($ideaTypes as $k => $v): ?><option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                <?php if ($filterStatus || $filterType || $search): ?><a href="<?= url('ideas.php') ?>" class="btn btn-outline"><i class="fas fa-times"></i></a><?php endif; ?>
            </div>
        </form>
    </div>
    <?php if (empty($ideas)): ?><div class="empty-state"><i class="fas fa-lightbulb"></i><h3>Aucune idée</h3></div>
    <?php else: ?>
        <table class="table"><thead><tr><th>Idée</th><th>Type</th><th>Auteur</th><th>Votes</th><th>Statut</th><th>Date</th><th class="text-right">Actions</th></tr></thead><tbody>
            <?php foreach ($ideas as $i): $is = $ideaStatuses[$i['status']] ?? ['label' => $i['status'], 'class' => 'secondary']; ?>
                <tr><td><strong><?= e($i['title']) ?></strong><br><small class="text-muted"><?= e(mb_substr($i['problem'], 0, 60)) ?>...</small></td><td><span class="badge badge-secondary"><?= $ideaTypes[$i['type']] ?? $i['type'] ?></span></td><td><?= e($i['user_name'] ?: 'Anonyme') ?></td><td><span class="badge badge-info"><i class="fas fa-thumbs-up"></i> <?= $i['votes_count'] ?? 0 ?></span></td><td><span class="badge badge-<?= $is['class'] ?>"><?= $is['label'] ?></span></td><td><small><?= formatRelativeDate($i['created_at']) ?></small></td><td><div class="table-actions"><a href="<?= url("ideas.php?action=view&id={$i['id']}") ?>" class="btn btn-sm btn-icon btn-outline"><i class="fas fa-eye"></i></a><form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_idea"><input type="hidden" name="id" value="<?= $i['id'] ?>"><button class="btn btn-sm btn-icon btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form></div></td></tr>
            <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
