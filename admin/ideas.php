<?php
/**
 * IFEN Toolbox Admin - Modération des idées
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

$pdo = getDBConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = intval($_POST['id']);
    
    try {
        if ($action === 'update_status') {
            $new_status = $_POST['status'];
            
            $stmt = $pdo->prepare("UPDATE toolbox_ideas SET status = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            
            logAdminAction('idea_status_changed', ['id' => $id, 'status' => $new_status]);
            $success = 'Statut mis à jour avec succès';
            
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM toolbox_ideas WHERE id = ?");
            $stmt->execute([$id]);
            
            logAdminAction('idea_deleted', ['id' => $id]);
            $success = 'Idée supprimée avec succès';
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Filtres
$status_filter = $_GET['status'] ?? 'all';
$sort = $_GET['sort'] ?? 'date_desc';

// Construction de la requête
$sql = "SELECT i.*, 
        (SELECT COUNT(*) FROM toolbox_votes WHERE idea_id = i.id) as votes_count
        FROM toolbox_ideas i
        WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $sql .= " AND i.status = :status";
    $params['status'] = $status_filter;
}

// Tri
switch ($sort) {
    case 'date_desc':
        $sql .= " ORDER BY i.created_at DESC";
        break;
    case 'date_asc':
        $sql .= " ORDER BY i.created_at ASC";
        break;
    case 'votes_desc':
        $sql .= " ORDER BY i.votes_count DESC";
        break;
    case 'votes_asc':
        $sql .= " ORDER BY i.votes_count ASC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ideas = $stmt->fetchAll();

$page_title = 'Modération des idées';
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

<!-- Filtres -->
<div class="section">
    <form method="GET" action="" class="form-row">
        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="proposed" <?php echo $status_filter === 'proposed' ? 'selected' : ''; ?>>Proposé</option>
                <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>En cours</option>
                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Réalisé</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Refusé</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="sort">Tri</label>
            <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
                <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Plus récent</option>
                <option value="date_asc" <?php echo $sort === 'date_asc' ? 'selected' : ''; ?>>Plus ancien</option>
                <option value="votes_desc" <?php echo $sort === 'votes_desc' ? 'selected' : ''; ?>>Plus de votes</option>
                <option value="votes_asc" <?php echo $sort === 'votes_asc' ? 'selected' : ''; ?>>Moins de votes</option>
            </select>
        </div>
    </form>
</div>

<!-- Liste des idées -->
<div class="section">
    <h3 class="section-title">
        <i class="fas fa-lightbulb"></i>
        Idées (<?php echo count($ideas); ?>)
    </h3>
    
    <?php if (empty($ideas)): ?>
        <div class="empty-state">
            <i class="fas fa-lightbulb"></i>
            <h3>Aucune idée</h3>
            <p>Aucune idée ne correspond à vos critères.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <?php foreach ($ideas as $idea): ?>
                <div style="background: white; border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <h4 style="font-size: 1.2rem; font-weight: 600; color: var(--dark); margin-bottom: 8px;">
                                <?php echo htmlspecialchars($idea['title']); ?>
                            </h4>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <span class="badge <?php 
                                    echo $idea['type'] === 'course' ? 'badge-primary' : 
                                        ($idea['type'] === 'platform' ? 'badge-info' : 'badge-warning');
                                ?>">
                                    <?php 
                                    $types = [
                                        'course' => '🧩 Module cours',
                                        'platform' => '⚙️ Plateforme',
                                        'improvement' => '✨ Amélioration'
                                    ];
                                    echo $types[$idea['type']] ?? $idea['type'];
                                    ?>
                                </span>
                                
                                <span class="badge badge-<?php 
                                    echo $idea['status'] === 'proposed' ? 'info' : 
                                        ($idea['status'] === 'in_progress' ? 'warning' : 
                                        ($idea['status'] === 'completed' ? 'success' : 'danger')); 
                                ?>">
                                    <?php 
                                    $statuses = [
                                        'proposed' => 'Proposé',
                                        'in_progress' => 'En cours',
                                        'completed' => 'Réalisé',
                                        'rejected' => 'Refusé'
                                    ];
                                    echo $statuses[$idea['status']] ?? $idea['status'];
                                    ?>
                                </span>
                                
                                <span class="badge badge-secondary">
                                    <i class="fas fa-thumbs-up"></i> <?php echo $idea['votes_count']; ?> vote(s)
                                </span>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 8px;">
                            <button type="button" class="btn btn-sm btn-info btn-icon" 
                                    onclick='viewIdea(<?php echo json_encode($idea); ?>)'
                                    title="Voir détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger btn-icon" 
                                    onclick="deleteIdea(<?php echo $idea['id']; ?>, '<?php echo addslashes($idea['title']); ?>')"
                                    title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <div style="font-weight: 600; color: var(--dark); margin-bottom: 5px;">Problème :</div>
                        <div style="color: var(--gray);"><?php echo nl2br(htmlspecialchars($idea['problem'])); ?></div>
                    </div>
                    
                    <?php if ($idea['details']): ?>
                        <div style="margin-bottom: 15px;">
                            <div style="font-weight: 600; color: var(--dark); margin-bottom: 5px;">Détails :</div>
                            <div style="color: var(--gray);"><?php echo nl2br(htmlspecialchars($idea['details'])); ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                        <div style="font-size: 0.85rem; color: var(--gray-light);">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($idea['user_name'] ?? 'Anonyme'); ?>
                            · <i class="fas fa-clock"></i> <?php echo date('d/m/Y à H:i', strtotime($idea['created_at'])); ?>
                        </div>
                        
                        <form method="POST" action="" style="margin: 0;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="id" value="<?php echo $idea['id']; ?>">
                            <select name="status" class="form-control" style="width: auto; display: inline-block; padding: 6px 12px;" onchange="this.form.submit()">
                                <option value="proposed" <?php echo $idea['status'] === 'proposed' ? 'selected' : ''; ?>>📝 Proposé</option>
                                <option value="in_progress" <?php echo $idea['status'] === 'in_progress' ? 'selected' : ''; ?>>⏳ En cours</option>
                                <option value="completed" <?php echo $idea['status'] === 'completed' ? 'selected' : ''; ?>>✅ Réalisé</option>
                                <option value="rejected" <?php echo $idea['status'] === 'rejected' ? 'selected' : ''; ?>>❌ Refusé</option>
                            </select>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Détails -->
<div id="idea-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Détails de l'idée</h3>
            <button type="button" class="modal-close" onclick="closeModal('idea-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="modal-content">
            <!-- Contenu dynamique -->
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('idea-modal')">
                Fermer
            </button>
        </div>
    </div>
</div>

<script>
function viewIdea(idea) {
    const types = {
        'course': '🧩 Module cours',
        'platform': '⚙️ Plateforme',
        'improvement': '✨ Amélioration'
    };
    
    const statuses = {
        'proposed': 'Proposé',
        'in_progress': 'En cours',
        'completed': 'Réalisé',
        'rejected': 'Refusé'
    };
    
    document.getElementById('modal-title').textContent = idea.title;
    document.getElementById('modal-content').innerHTML = `
        <div style="margin-bottom: 20px;">
            <span class="badge badge-primary">${types[idea.type]}</span>
            <span class="badge badge-info">${statuses[idea.status]}</span>
            <span class="badge badge-secondary"><i class="fas fa-thumbs-up"></i> ${idea.votes_count} vote(s)</span>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 8px;">Problème :</h4>
            <p style="color: var(--gray);">${AdminUtils.escapeHtml(idea.problem)}</p>
        </div>
        
        ${idea.details ? `
            <div style="margin-bottom: 20px;">
                <h4 style="font-size: 1rem; font-weight: 600; margin-bottom: 8px;">Détails :</h4>
                <p style="color: var(--gray);">${AdminUtils.escapeHtml(idea.details)}</p>
            </div>
        ` : ''}
        
        <div style="padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 0.85rem; color: var(--gray-light);">
            <i class="fas fa-user"></i> ${AdminUtils.escapeHtml(idea.user_name || 'Anonyme')}
            · <i class="fas fa-clock"></i> ${AdminUtils.formatDate(idea.created_at)}
        </div>
    `;
    
    openModal('idea-modal');
}

async function deleteIdea(id, title) {
    if (await AdminUtils.confirmAction(
        `Êtes-vous sûr de vouloir supprimer l'idée "<strong>${title}</strong>" ?<br><br>Cette action supprimera également tous les votes associés.`,
        'Supprimer l\'idée'
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