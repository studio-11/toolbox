<?php
/**
 * IFEN Toolbox Admin - Gestion des catégories
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

$pdo = getDBConnection();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO toolbox_categories (name, slug, description, icon, color, display_order)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['slug'],
                $_POST['description'],
                $_POST['icon'],
                $_POST['color'],
                intval($_POST['display_order'] ?? 0)
            ]);
            
            logAdminAction('category_created', ['name' => $_POST['name']]);
            $success = 'Catégorie créée avec succès';
            
        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE toolbox_categories 
                SET name = ?, slug = ?, description = ?, icon = ?, color = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['slug'],
                $_POST['description'],
                $_POST['icon'],
                $_POST['color'],
                intval($_POST['display_order'] ?? 0),
                intval($_POST['id'])
            ]);
            
            logAdminAction('category_updated', ['id' => $_POST['id'], 'name' => $_POST['name']]);
            $success = 'Catégorie mise à jour avec succès';
            
        } elseif ($action === 'delete') {
            $id = intval($_POST['id']);
            
            // Vérifier qu'aucun outil n'utilise cette catégorie
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM toolbox_tools WHERE category_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $error = "Impossible de supprimer : $count outil(s) utilise(nt) cette catégorie";
            } else {
                $stmt = $pdo->prepare("DELETE FROM toolbox_categories WHERE id = ?");
                $stmt->execute([$id]);
                
                logAdminAction('category_deleted', ['id' => $id]);
                $success = 'Catégorie supprimée avec succès';
            }
        }
    } catch (Exception $e) {
        $error = 'Erreur : ' . $e->getMessage();
    }
}

// Charger les catégories
$stmt = $pdo->query("
    SELECT c.*, 
           COUNT(t.id) as tools_count
    FROM toolbox_categories c
    LEFT JOIN toolbox_tools t ON t.category_id = c.id
    GROUP BY c.id
    ORDER BY c.display_order ASC, c.name ASC
");
$categories = $stmt->fetchAll();

$page_title = 'Gestion des catégories';
$header_actions = '<button type="button" class="btn btn-primary" onclick="openCreateModal()"><i class="fas fa-plus"></i> Nouvelle catégorie</button>';
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

<div class="section">
    <h3 class="section-title">
        <i class="fas fa-folder-tree"></i>
        Catégories (<?php echo count($categories); ?>)
    </h3>
    
    <?php if (empty($categories)): ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>Aucune catégorie</h3>
            <p>Créez votre première catégorie pour organiser vos outils.</p>
            <button type="button" class="btn btn-primary" onclick="openCreateModal()" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Créer une catégorie
            </button>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Ordre</th>
                        <th>Catégorie</th>
                        <th>Slug</th>
                        <th>Icône</th>
                        <th>Couleur</th>
                        <th>Outils</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600;"><?php echo $cat['display_order']; ?></td>
                            <td>
                                <div style="font-weight: 600; color: var(--dark);">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </div>
                                <?php if ($cat['description']): ?>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        <?php echo htmlspecialchars(substr($cat['description'], 0, 80)); ?>...
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($cat['slug']); ?></code></td>
                            <td style="text-align: center; font-size: 1.5rem;">
                                <i class="<?php echo htmlspecialchars($cat['icon'] ?: 'fas fa-folder'); ?>"></i>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 30px; height: 30px; border-radius: 6px; background: <?php echo htmlspecialchars($cat['color'] ?: '#ccc'); ?>; border: 1px solid #ddd;"></div>
                                    <code><?php echo htmlspecialchars($cat['color'] ?: '—'); ?></code>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-info"><?php echo $cat['tools_count']; ?> outil(s)</span>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px;">
                                    <button type="button" class="btn btn-sm btn-secondary btn-icon" 
                                            onclick='editCategory(<?php echo json_encode($cat); ?>)'
                                            title="Éditer">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger btn-icon" 
                                            onclick="deleteCategory(<?php echo $cat['id']; ?>, '<?php echo addslashes($cat['name']); ?>', <?php echo $cat['tools_count']; ?>)"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Création/Édition -->
<div id="category-modal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title" id="modal-title">Nouvelle catégorie</h3>
            <button type="button" class="modal-close" onclick="closeModal('category-modal')">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" action="" id="category-form">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="form-id">
            
            <div class="modal-body">
                <div class="form-group">
                    <label for="form-name">Nom <span class="required">*</span></label>
                    <input type="text" id="form-name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="form-slug">Slug <span class="required">*</span></label>
                    <input type="text" id="form-slug" name="slug" class="form-control" required>
                    <div class="form-help">Utilisé dans les URLs, pas d'espaces</div>
                </div>
                
                <div class="form-group">
                    <label for="form-description">Description</label>
                    <textarea id="form-description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="form-icon">Icône Font Awesome</label>
                        <input type="text" id="form-icon" name="icon" class="form-control" placeholder="fa-folder">
                    </div>
                    
                    <div class="form-group">
                        <label for="form-color">Couleur</label>
                        <input type="color" id="form-color" name="color" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="form-order">Ordre d'affichage</label>
                        <input type="number" id="form-order" name="display_order" class="form-control" value="0" min="0">
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('category-modal')">
                    Annuler
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modal-title').textContent = 'Nouvelle catégorie';
    document.getElementById('form-action').value = 'create';
    document.getElementById('category-form').reset();
    document.getElementById('form-id').value = '';
    openModal('category-modal');
}

function editCategory(category) {
    document.getElementById('modal-title').textContent = 'Éditer la catégorie';
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-id').value = category.id;
    document.getElementById('form-name').value = category.name;
    document.getElementById('form-slug').value = category.slug;
    document.getElementById('form-description').value = category.description || '';
    document.getElementById('form-icon').value = category.icon || '';
    document.getElementById('form-color').value = category.color || '#667eea';
    document.getElementById('form-order').value = category.display_order;
    openModal('category-modal');
}

async function deleteCategory(id, name, toolsCount) {
    if (toolsCount > 0) {
        AdminUtils.showNotification(`Impossible de supprimer : ${toolsCount} outil(s) utilise(nt) cette catégorie`, 'danger');
        return;
    }
    
    if (await AdminUtils.confirmAction(
        `Êtes-vous sûr de vouloir supprimer la catégorie "<strong>${name}</strong>" ?`,
        'Supprimer la catégorie'
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

// Auto-génération du slug
document.getElementById('form-name').addEventListener('input', function() {
    const slug = this.value
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    
    document.getElementById('form-slug').value = slug;
});
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>