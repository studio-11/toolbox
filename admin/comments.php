<?php
/**
 * IFEN Toolbox Admin - Modération des commentaires
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

$pdo = getDBConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE toolbox_comments SET is_approved = 1 WHERE id = ?");
            $stmt->execute([$id]);
            
            logAdminAction('comment_approved', ['id' => $id]);
            $success = 'Commentaire approuvé';
            
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE toolbox_comments SET is_approved = 0 WHERE id = ?");
            $stmt->execute([$id]);
            
            logAdminAction('comment_rejected', ['id' => $id]);
            $success = 'Commentaire rejeté';
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM toolbox_comments WHERE id = ?");
            $stmt->execute([$id]);
            
            logAdminAction('comment_deleted', ['id' => $id]);
            $success = 'Commentaire supprimé';
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Filtres
$filter = $_GET['filter'] ?? 'pending';

// Construction de la requête
$sql = "SELECT c.*, t.name as tool_name
        FROM toolbox_comments c
        LEFT JOIN toolbox_tools t ON c.tool_id = t.id
        WHERE 1=1";

$params = [];

if ($filter === 'pending') {
    $sql .= " AND c.is_approved = 0";
} elseif ($filter === 'approved') {
    $sql .= " AND c.is_approved = 1";
}

$sql .= " ORDER BY c.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

// Compter les commentaires en attente
$stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_comments WHERE is_approved = 0");
$pending_count = $stmt->fetchColumn();

$page_title = 'Modération des commentaires';
require_once(__DIR__ . '/includes/header.php');
?>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<!-- Tabs / Filtres -->
<div class="section">
    <div style="display: flex; gap: 10px; border-bottom: 2px solid #e2e8f0;">
        <a href="?filter=pending" 
           class="btn <?php echo $filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>"
           style="border-radius: 8px 8px 0 0;">
            <i class="fas fa-clock"></i> En attente
            <?php if ($pending_count > 0): ?>
                <span class="badge" style="background: white; color: var(--primary); margin-left: 5px;">
                    <?php echo $pending_count; ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="?filter=approved" 
           class="btn <?php echo $filter === 'approved' ? 'btn-primary' : 'btn-secondary'; ?>"
           style="border-radius: 8px 8px 0 0;">
            <i class="fas fa-check"></i> Approuvés
        </a>
        <a href="?filter=all" 
           class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>"
           style="border-radius: 8px 8px 0 0;">
            <i class="fas fa-list"></i> Tous
        </a>
    </div>
</div>

<!-- Liste des commentaires -->
<div class="section">
    <h3 class="section-title">
        <i class="fas fa-comments"></i>
        Commentaires (<?php echo count($comments); ?>)
    </h3>
    
    <?php if (empty($comments)): ?>
        <div class="empty-state">
            <i class="fas fa-comments"></i>
            <h3>Aucun commentaire</h3>
            <p>
                <?php 
                if ($filter === 'pending') {
                    echo 'Aucun commentaire en attente de modération.';
                } elseif ($filter === 'approved') {
                    echo 'Aucun commentaire approuvé pour le moment.';
                } else {
                    echo 'Aucun commentaire pour le moment.';
                }
                ?>
            </p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($comments as $comment): ?>
                <div style="background: <?php echo $comment['is_approved'] ? '#d1fae5' : '#fef3c7'; ?>; border: 2px solid <?php echo $comment['is_approved'] ? '#10b981' : '#f59e0b'; ?>; border-radius: 12px; padding: 20px;">
                    
                    <!-- Header -->
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                <div style="font-weight: 600; color: var(--dark);">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($comment['user_name'] ?? 'Utilisateur #' . $comment['user_id']); ?>
                                </div>
                                
                                <?php if ($comment['rating']): ?>
                                    <div>
                                        <?php for ($i = 0; $i < $comment['rating']; $i++): ?>
                                            <i class="fas fa-star" style="color: #f59e0b;"></i>
                                        <?php endfor; ?>
                                        <?php for ($i = $comment['rating']; $i < 5; $i++): ?>
                                            <i class="far fa-star" style="color: #cbd5e1;"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($comment['is_approved']): ?>
                                    <span class="badge badge-success">
                                        <i class="fas fa-check"></i> Approuvé
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock"></i> En attente
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="font-size: 0.85rem; color: var(--gray);">
                                <i class="fas fa-tools"></i> 
                                <strong><?php echo htmlspecialchars($comment['tool_name'] ?? 'Outil inconnu'); ?></strong>
                                · <i class="fas fa-clock"></i> <?php echo date('d/m/Y à H:i', strtotime($comment['created_at'])); ?>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <?php if (!$comment['is_approved']): ?>
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-success btn-icon" title="Approuver">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="POST" action="" style="margin: 0;">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="id" value="<?php echo $comment['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-warning btn-icon" title="Retirer l'approbation">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-danger btn-icon" 
                                    onclick="deleteComment(<?php echo $comment['id']; ?>)"
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Contenu -->
                    <div style="background: white; padding: 15px; border-radius: 8px; color: var(--dark);">
                        <?php echo nl2br(htmlspecialchars($comment['comment'])); ?>
                    </div>
                    
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
async function deleteComment(id) {
    if (await AdminUtils.confirmAction(
        'Êtes-vous sûr de vouloir supprimer ce commentaire ?<br><br>Cette action est irréversible.',
        'Supprimer le commentaire'
    )) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>