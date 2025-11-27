<?php
/**
 * IFEN Toolbox Admin - Dashboard
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();

// ============================================
// STATISTIQUES
// ============================================

// Compter les outils
$toolsCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_tools");
    $toolsCount = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Compter les catégories
$categoriesCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_categories");
    $categoriesCount = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Compter les idées
$ideasCount = 0;
$ideasPending = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas");
    $ideasCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'proposed'");
    $ideasPending = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Compter les travaux
$worksCount = 0;
$worksInProgress = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_works");
    $worksCount = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_works WHERE status = 'in_progress'");
    $worksInProgress = $stmt->fetchColumn();
} catch (PDOException $e) {}

// Statut plateforme
$platformStatus = null;
try {
    $stmt = $pdo->query("SELECT id, status, status_message as message FROM toolbox_platform_status WHERE id = 1");
    $platformStatus = $stmt->fetch();
} catch (PDOException $e) {}

// Derniers travaux
$recentWorks = [];
try {
    $stmt = $pdo->query("SELECT * FROM toolbox_works ORDER BY created_at DESC LIMIT 5");
    $recentWorks = $stmt->fetchAll();
} catch (PDOException $e) {}

// Dernières idées
$recentIdeas = [];
try {
    $stmt = $pdo->query("SELECT id, title, status, votes_count as votes, user_name as author_name, created_at FROM toolbox_ideas ORDER BY created_at DESC LIMIT 5");
    $recentIdeas = $stmt->fetchAll();
} catch (PDOException $e) {}

// Labels pour les badges
$statusLabels = [
    'operational' => ['label' => 'Opérationnel', 'class' => 'operational'],
    'maintenance' => ['label' => 'Maintenance', 'class' => 'maintenance'],
    'upgrading' => ['label' => 'Mise à jour', 'class' => 'upgrading'],
    'partial_outage' => ['label' => 'Panne partielle', 'class' => 'partial-outage'],
    'major_outage' => ['label' => 'Panne majeure', 'class' => 'major-outage'],
];

$workStatusLabels = [
    'planned' => ['label' => 'Planifié', 'class' => 'planned'],
    'unplanned' => ['label' => 'Non planifié', 'class' => 'unplanned'],
    'in_progress' => ['label' => 'En cours', 'class' => 'in-progress'],
    'completed' => ['label' => 'Terminé', 'class' => 'completed'],
    'cancelled' => ['label' => 'Annulé', 'class' => 'cancelled'],
];

$priorityLabels = [
    'low' => ['label' => 'Basse', 'class' => 'low'],
    'medium' => ['label' => 'Moyenne', 'class' => 'medium'],
    'high' => ['label' => 'Haute', 'class' => 'high'],
    'critical' => ['label' => 'Critique', 'class' => 'critical'],
];

$ideaStatusLabels = [
    'proposed' => ['label' => 'Proposée', 'class' => 'warning'],
    'in_progress' => ['label' => 'En cours', 'class' => 'in-progress'],
    'completed' => ['label' => 'Réalisée', 'class' => 'completed'],
    'rejected' => ['label' => 'Rejetée', 'class' => 'cancelled'],
];
?>

<!-- Platform Status Banner -->
<?php if ($platformStatus): ?>
    <?php $status = $statusLabels[$platformStatus['status']] ?? ['label' => $platformStatus['status'], 'class' => 'secondary']; ?>
    <div class="card mb-2">
        <div class="card-body" style="padding: 1rem 1.5rem;">
            <div class="d-flex align-center justify-between">
                <div class="d-flex align-center gap-2">
                    <span class="badge badge-<?= $status['class'] ?>">
                        <i class="fas fa-circle" style="font-size: 0.5em;"></i>
                        <?= $status['label'] ?>
                    </span>
                    <span><?= e($platformStatus['message'] ?: 'Tous les systèmes fonctionnent normalement.') ?></span>
                </div>
                <a href="<?= url('works.php') ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-edit"></i>
                    Modifier
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon violet">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $toolsCount ?></div>
            <div class="stat-label">Outils</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon cyan">
            <i class="fas fa-folder"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $categoriesCount ?></div>
            <div class="stat-label">Catégories</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $ideasCount ?></div>
            <div class="stat-label">Idées <?php if ($ideasPending): ?><small>(<?= $ideasPending ?> en attente)</small><?php endif; ?></div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon <?= $worksInProgress > 0 ? 'danger' : 'success' ?>">
            <i class="fas fa-hard-hat"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= $worksCount ?></div>
            <div class="stat-label">Travaux <?php if ($worksInProgress): ?><small>(<?= $worksInProgress ?> en cours)</small><?php endif; ?></div>
        </div>
    </div>
</div>

<div class="form-row">
    <!-- Recent Works -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-hard-hat"></i>
                Travaux récents
            </h3>
            <a href="<?= url('works.php') ?>" class="btn btn-sm btn-outline">
                Voir tout
            </a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentWorks)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3>Aucun travaux</h3>
                    <p>Aucun travaux n'a été enregistré.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <tbody>
                        <?php foreach ($recentWorks as $work): ?>
                            <?php 
                                $ws = $workStatusLabels[$work['status']] ?? ['label' => $work['status'], 'class' => 'secondary'];
                                $pr = $priorityLabels[$work['priority']] ?? ['label' => $work['priority'], 'class' => 'secondary'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($work['title']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= formatRelativeDate($work['created_at']) ?></small>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-<?= $pr['class'] ?>"><?= $pr['label'] ?></span>
                                    <span class="badge badge-<?= $ws['class'] ?>"><?= $ws['label'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Recent Ideas -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-lightbulb"></i>
                Idées récentes
            </h3>
            <a href="<?= url('ideas.php') ?>" class="btn btn-sm btn-outline">
                Voir tout
            </a>
        </div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($recentIdeas)): ?>
                <div class="empty-state">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Aucune idée</h3>
                    <p>Aucune idée n'a été soumise.</p>
                </div>
            <?php else: ?>
                <table class="table">
                    <tbody>
                        <?php foreach ($recentIdeas as $idea): ?>
                            <?php $is = $ideaStatusLabels[$idea['status']] ?? ['label' => $idea['status'], 'class' => 'secondary']; ?>
                            <tr>
                                <td>
                                    <strong><?= e($idea['title']) ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?= e($idea['author_name'] ?? 'Anonyme') ?> · 
                                        <?= $idea['votes'] ?? 0 ?> votes
                                    </small>
                                </td>
                                <td class="text-right">
                                    <span class="badge badge-<?= $is['class'] ?>"><?= $is['label'] ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-bolt"></i>
            Actions rapides
        </h3>
    </div>
    <div class="card-body">
        <div class="btn-group" style="flex-wrap: wrap;">
            <a href="<?= url('tools.php?action=new') ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i>
                Nouvel outil
            </a>
            <a href="<?= url('works.php?action=new') ?>" class="btn btn-secondary">
                <i class="fas fa-plus"></i>
                Nouveau travail
            </a>
            <a href="<?= url('categories.php?action=new') ?>" class="btn btn-outline">
                <i class="fas fa-plus"></i>
                Nouvelle catégorie
            </a>
            <a href="<?= frontendUrl() ?>" class="btn btn-outline" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                Voir le site
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
