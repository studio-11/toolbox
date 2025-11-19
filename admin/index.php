<?php
/**
 * IFEN Toolbox Admin - Dashboard
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

// Gestion logout
if (isset($_GET['logout'])) {
    adminLogout();
}

// Charger les stats
$pdo = getDBConnection();

// Stats globales
$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE status != 'deprecated'");
$stats['total_tools'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_tools WHERE is_hot = 1 OR status = 'new'");
$stats['hot_tools'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT SUM(installations_count) FROM toolbox_tools");
$stats['total_installations'] = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'proposed'");
$stats['pending_ideas'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_comments WHERE is_approved = 0");
$stats['pending_comments'] = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM toolbox_favorites");
$stats['active_users'] = $stmt->fetchColumn();

// Outils récents
$stmt = $pdo->query("
    SELECT t.*, c.name as category_name
    FROM toolbox_tools t
    LEFT JOIN toolbox_categories c ON t.category_id = c.id
    ORDER BY t.created_at DESC
    LIMIT 5
");
$recent_tools = $stmt->fetchAll();

// Idées récentes
$stmt = $pdo->query("
    SELECT * FROM toolbox_ideas
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_ideas = $stmt->fetchAll();

// Activité récente (commentaires)
$stmt = $pdo->query("
    SELECT c.*, t.name as tool_name
    FROM toolbox_comments c
    LEFT JOIN toolbox_tools t ON c.tool_id = t.id
    ORDER BY c.created_at DESC
    LIMIT 5
");
$recent_comments = $stmt->fetchAll();

$page_title = 'Dashboard';
require_once(__DIR__ . '/includes/header.php');
?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-1);">
            <i class="fas fa-tools"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['total_tools']; ?></div>
            <div class="stat-label">Outils actifs</div>
            <div class="stat-change up">
                <i class="fas fa-arrow-up"></i> +<?php echo $stats['hot_tools']; ?> ce mois
            </div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-2);">
            <i class="fas fa-download"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo number_format($stats['total_installations']); ?></div>
            <div class="stat-label">Installations totales</div>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: var(--gradient-3);">
            <i class="fas fa-lightbulb"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['pending_ideas']; ?></div>
            <div class="stat-label">Idées en attente</div>
            <?php if ($stats['pending_ideas'] > 0): ?>
                <a href="ideas.php" class="btn btn-sm btn-primary" style="margin-top: 8px;">
                    Modérer <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
            <i class="fas fa-comments"></i>
        </div>
        <div class="stat-content">
            <div class="stat-number"><?php echo $stats['pending_comments']; ?></div>
            <div class="stat-label">Commentaires à modérer</div>
            <?php if ($stats['pending_comments'] > 0): ?>
                <a href="comments.php" class="btn btn-sm btn-primary" style="margin-top: 8px;">
                    Modérer <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Sections -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-top: 30px;">
    <!-- Outils récents -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-tools"></i>
            Outils récents
        </h3>
        
        <?php if (empty($recent_tools)): ?>
            <div class="empty-state">
                <i class="fas fa-tools"></i>
                <p>Aucun outil pour le moment</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($recent_tools as $tool): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: var(--light); border-radius: 8px;">
                        <div>
                            <div style="font-weight: 600; color: var(--dark);">
                                <?php echo htmlspecialchars($tool['name']); ?>
                            </div>
                            <div style="font-size: 0.85rem; color: var(--gray);">
                                <?php echo htmlspecialchars($tool['category_name'] ?? 'Sans catégorie'); ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <?php if ($tool['is_hot']): ?>
                                <span class="badge badge-danger">🔥 HOT</span>
                            <?php endif; ?>
                            <?php if ($tool['status'] === 'new'): ?>
                                <span class="badge badge-success">NEW</span>
                            <?php endif; ?>
                            <a href="tool-edit.php?id=<?php echo $tool['id']; ?>" class="btn btn-sm btn-secondary btn-icon">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="tools.php" class="btn btn-secondary" style="margin-top: 15px; width: 100%;">
                <i class="fas fa-list"></i> Voir tous les outils
            </a>
        <?php endif; ?>
    </div>
    
    <!-- Idées récentes -->
    <div class="section">
        <h3 class="section-title">
            <i class="fas fa-lightbulb"></i>
            Idées récentes
        </h3>
        
        <?php if (empty($recent_ideas)): ?>
            <div class="empty-state">
                <i class="fas fa-lightbulb"></i>
                <p>Aucune idée pour le moment</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($recent_ideas as $idea): ?>
                    <div style="padding: 12px; background: var(--light); border-radius: 8px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <div style="font-weight: 600; color: var(--dark); flex: 1;">
                                <?php echo htmlspecialchars($idea['title']); ?>
                            </div>
                            <span class="badge badge-<?php 
                                echo $idea['status'] === 'proposed' ? 'info' : 
                                    ($idea['status'] === 'in_progress' ? 'warning' : 
                                    ($idea['status'] === 'completed' ? 'success' : 'danger')); 
                            ?>">
                                <?php echo strtoupper($idea['status']); ?>
                            </span>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--gray);">
                            <?php echo htmlspecialchars(substr($idea['problem'], 0, 100)) . '...'; ?>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                            <div style="font-size: 0.85rem; color: var(--gray-light);">
                                <i class="fas fa-thumbs-up"></i> <?php echo $idea['votes_count']; ?> votes
                            </div>
                            <a href="ideas.php" class="btn btn-sm btn-secondary btn-icon">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <a href="ideas.php" class="btn btn-secondary" style="margin-top: 15px; width: 100%;">
                <i class="fas fa-list"></i> Voir toutes les idées
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Commentaires récents -->
<div class="section" style="margin-top: 25px;">
    <h3 class="section-title">
        <i class="fas fa-comments"></i>
        Activité récente - Commentaires
    </h3>
    
    <?php if (empty($recent_comments)): ?>
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <p>Aucun commentaire pour le moment</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Outil</th>
                        <th>Commentaire</th>
                        <th>Note</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_comments as $comment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['tool_name'] ?? 'N/A'); ?></td>
                            <td>
                                <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo htmlspecialchars($comment['comment']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($comment['rating']): ?>
                                    <?php for ($i = 0; $i < $comment['rating']; $i++): ?>
                                        <i class="fas fa-star" style="color: #f59e0b;"></i>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <small style="color: var(--gray-light);">
                                    <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($comment['is_approved']): ?>
                                    <span class="badge badge-success">Approuvé</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">En attente</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="comments.php" class="btn btn-sm btn-secondary btn-icon">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <a href="comments.php" class="btn btn-secondary" style="margin-top: 15px;">
            <i class="fas fa-list"></i> Voir tous les commentaires
        </a>
    <?php endif; ?>
</div>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>