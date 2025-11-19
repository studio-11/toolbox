<?php
/**
 * IFEN Toolbox Admin - Gestion des outils
 */

require_once(__DIR__ . '/includes/auth.php');
requireAdmin();

$pdo = getDBConnection();

// Gestion suppression
if (isset($_GET['delete']) && isset($_GET['csrf'])) {
    if (verifyCsrfToken($_GET['csrf'])) {
        $id = intval($_GET['delete']);
        
        try {
            $stmt = $pdo->prepare("DELETE FROM toolbox_tools WHERE id = ?");
            $stmt->execute([$id]);
            
            logAdminAction('tool_deleted', ['tool_id' => $id]);
            
            header('Location: tools.php?success=deleted');
            exit;
        } catch (Exception $e) {
            $error = 'Erreur lors de la suppression: ' . $e->getMessage();
        }
    }
}

// Filtres
$type_filter = $_GET['type'] ?? 'all';
$category_filter = $_GET['category'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Construction de la requête
$sql = "SELECT t.*, c.name as category_name,
        (SELECT COUNT(*) FROM toolbox_comments WHERE tool_id = t.id) as comments_count,
        (SELECT COUNT(*) FROM toolbox_favorites WHERE tool_id = t.id) as favorites_count
        FROM toolbox_tools t
        LEFT JOIN toolbox_categories c ON t.category_id = c.id
        WHERE 1=1";

$params = [];

if ($type_filter !== 'all') {
    $sql .= " AND t.type = :type";
    $params['type'] = $type_filter;
}

if ($category_filter !== 'all') {
    $sql .= " AND t.category_id = :category";
    $params['category'] = $category_filter;
}

if ($status_filter !== 'all') {
    $sql .= " AND t.status = :status";
    $params['status'] = $status_filter;
}

if ($search) {
    $sql .= " AND (t.name LIKE :search OR t.short_description LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tools = $stmt->fetchAll();

// Charger les catégories pour le filtre
$stmt = $pdo->query("SELECT * FROM toolbox_categories ORDER BY name");
$categories = $stmt->fetchAll();

$page_title = 'Gestion des outils';
$header_actions = '<a href="tool-edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> Nouvel outil</a>';
require_once(__DIR__ . '/includes/header.php');
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php 
        switch ($_GET['success']) {
            case 'deleted':
                echo 'Outil supprimé avec succès';
                break;
            case 'created':
                echo 'Outil créé avec succès';
                break;
            case 'updated':
                echo 'Outil mis à jour avec succès';
                break;
        }
        ?>
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
            <label for="type">Type</label>
            <select name="type" id="type" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>Tous les types</option>
                <option value="course" <?php echo $type_filter === 'course' ? 'selected' : ''; ?>>Outil de cours</option>
                <option value="platform" <?php echo $type_filter === 'platform' ? 'selected' : ''; ?>>Fonctionnalité plateforme</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="category">Catégorie</label>
            <select name="category" id="category" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="status">Statut</label>
            <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="stable" <?php echo $status_filter === 'stable' ? 'selected' : ''; ?>>Stable</option>
                <option value="beta" <?php echo $status_filter === 'beta' ? 'selected' : ''; ?>>Beta</option>
                <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>Nouveau</option>
                <option value="deprecated" <?php echo $status_filter === 'deprecated' ? 'selected' : ''; ?>>Déprécié</option>
            </select>
        </div>
        
        <div class="form-group" style="flex: 2;">
            <label for="search">Recherche</label>
            <input 
                type="text" 
                name="search" 
                id="search" 
                class="form-control" 
                placeholder="Rechercher un outil..."
                value="<?php echo htmlspecialchars($search); ?>"
            >
        </div>
        
        <div class="form-group">
            <label style="opacity: 0;">Actions</label>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrer
                </button>
                <?php if ($type_filter !== 'all' || $category_filter !== 'all' || $status_filter !== 'all' || $search): ?>
                    <a href="tools.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Réinitialiser
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<!-- Liste des outils -->
<div class="section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 class="section-title" style="margin: 0;">
            <i class="fas fa-list"></i>
            Outils (<?php echo count($tools); ?>)
        </h3>
    </div>
    
    <?php if (empty($tools)): ?>
        <div class="empty-state">
            <i class="fas fa-tools"></i>
            <h3>Aucun outil trouvé</h3>
            <p>Aucun outil ne correspond à vos critères de recherche.</p>
            <a href="tool-edit.php" class="btn btn-primary" style="margin-top: 20px;">
                <i class="fas fa-plus"></i> Créer un outil
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table id="tools-table">
                <thead>
                    <tr>
                        <th>Outil</th>
                        <th>Type</th>
                        <th>Catégorie</th>
                        <th>Statut</th>
                        <th style="text-align: center;">Stats</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tools as $tool): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 50px; height: 50px; border-radius: 8px; background: <?php echo htmlspecialchars($tool['gradient'] ?? 'var(--gradient-1)'); ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.3rem;">
                                        <i class="<?php echo htmlspecialchars($tool['icon'] ?? 'fas fa-cube'); ?>"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--dark);">
                                            <?php echo htmlspecialchars($tool['name']); ?>
                                        </div>
                                        <div style="font-size: 0.85rem; color: var(--gray); max-width: 400px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?php echo htmlspecialchars($tool['short_description']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($tool['type'] === 'course'): ?>
                                    <span class="badge badge-primary">
                                        <i class="fas fa-puzzle-piece"></i> Cours
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-cog"></i> Plateforme
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($tool['category_name'] ?? 'Non catégorisé'); ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = [
                                    'stable' => 'badge-success',
                                    'beta' => 'badge-warning',
                                    'new' => 'badge-info',
                                    'deprecated' => 'badge-danger'
                                ];
                                ?>
                                <span class="badge <?php echo $badge_class[$tool['status']] ?? 'badge-secondary'; ?>">
                                    <?php echo strtoupper($tool['status']); ?>
                                </span>
                                <?php if ($tool['is_hot']): ?>
                                    <span class="badge badge-danger">🔥 HOT</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="font-size: 0.85rem; color: var(--gray);">
                                    <div><i class="fas fa-eye"></i> <?php echo number_format($tool['views_count']); ?></div>
                                    <div><i class="fas fa-download"></i> <?php echo number_format($tool['installations_count']); ?></div>
                                    <div><i class="fas fa-comments"></i> <?php echo $tool['comments_count']; ?></div>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; gap: 8px; justify-content: center;">
                                    <a href="tool-edit.php?id=<?php echo $tool['id']; ?>" 
                                       class="btn btn-sm btn-secondary btn-icon" 
                                       title="Éditer">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../index.html?tool=<?php echo $tool['id']; ?>" 
                                       class="btn btn-sm btn-info btn-icon" 
                                       title="Voir" 
                                       target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="deleteTool(<?php echo $tool['id']; ?>, '<?php echo addslashes($tool['name']); ?>')" 
                                            class="btn btn-sm btn-danger btn-icon" 
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

<script>
async function deleteTool(id, name) {
    if (await AdminUtils.confirmAction(
        `Êtes-vous sûr de vouloir supprimer l'outil "<strong>${name}</strong>" ?<br><br>Cette action est irréversible et supprimera également tous les commentaires et statistiques associés.`,
        'Supprimer l\'outil'
    )) {
        window.location.href = `tools.php?delete=${id}&csrf=<?php echo generateCsrfToken(); ?>`;
    }
}

// Recherche en temps réel
const searchInput = document.getElementById('search');
if (searchInput) {
    const debouncedFilter = AdminUtils.debounce(() => {
        const term = searchInput.value.toLowerCase();
        const rows = document.querySelectorAll('#tools-table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    }, 300);
    
    searchInput.addEventListener('input', debouncedFilter);
}
</script>

<?php require_once(__DIR__ . '/includes/footer.php'); ?>