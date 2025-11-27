<?php
/**
 * IFEN Toolbox Admin - Catégories
 * Adapté: display_order au lieu de sort_order
 */
$pageTitle = 'Catégories';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();
$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'save_category') {
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'icon' => trim($_POST['icon'] ?? ''),
            'color' => trim($_POST['color'] ?? '#502b85'),
            'display_order' => intval($_POST['display_order'] ?? 0),
        ];
        
        if (empty($d['slug']) && !empty($d['name'])) {
            $d['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $d['name']));
        }
        
        if (empty($d['name'])) {
            $error = 'Le nom est obligatoire.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE toolbox_categories SET name=?, slug=?, description=?, icon=?, color=?, display_order=? WHERE id=?");
                    $stmt->execute([$d['name'], $d['slug'], $d['description'], $d['icon'], $d['color'], $d['display_order'], $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO toolbox_categories (name, slug, description, icon, color, display_order) VALUES (?,?,?,?,?,?)");
                    $stmt->execute([$d['name'], $d['slug'], $d['description'], $d['icon'], $d['color'], $d['display_order']]);
                }
                setFlash('success', 'Catégorie enregistrée.'); header('Location: ' . url('categories.php')); exit;
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'delete_category' && $id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM toolbox_tools WHERE category_id = ?");
            $stmt->execute([$id]);
            if ($stmt->fetchColumn() > 0) { $error = 'Catégorie contient des outils.'; }
            else { $pdo->prepare("DELETE FROM toolbox_categories WHERE id = ?")->execute([$id]); setFlash('success', 'Supprimée.'); header('Location: ' . url('categories.php')); exit; }
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

$currentCat = null;
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_categories WHERE id = ?");
    $stmt->execute([$id]);
    $currentCat = $stmt->fetch();
    if (!$currentCat) { setFlash('error', 'Non trouvée.'); header('Location: ' . url('categories.php')); exit; }
}

$categories = [];
try {
    $stmt = $pdo->query("SELECT c.*, COUNT(t.id) as tools_count FROM toolbox_categories c LEFT JOIN toolbox_tools t ON c.id = t.category_id GROUP BY c.id ORDER BY c.display_order, c.name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) { $error = $e->getMessage(); }
?>

<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus' ?>"></i> <?= $action === 'edit' ? 'Modifier' : 'Nouvelle' ?> catégorie</h3>
        <a href="<?= url('categories.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-times"></i></a>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="save_category">
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Nom</label><input type="text" name="name" class="form-control" required value="<?= e($currentCat['name'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= e($currentCat['slug'] ?? '') ?>"></div>
            </div>
            <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= e($currentCat['description'] ?? '') ?></textarea></div>
            <div class="form-row">
                <div class="form-group"><label class="form-label">Icône</label><input type="text" name="icon" class="form-control" value="<?= e($currentCat['icon'] ?? '') ?>" placeholder="fas fa-..."></div>
                <div class="form-group"><label class="form-label">Couleur</label><div class="d-flex gap-1"><input type="color" name="color" value="<?= e($currentCat['color'] ?? '#502b85') ?>" style="width:50px;height:38px;"><input type="text" class="form-control" value="<?= e($currentCat['color'] ?? '#502b85') ?>" disabled></div></div>
                <div class="form-group"><label class="form-label">Ordre</label><input type="number" name="display_order" class="form-control" value="<?= e($currentCat['display_order'] ?? 0) ?>" min="0"></div>
            </div>
            <div class="btn-group"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button><a href="<?= url('categories.php') ?>" class="btn btn-outline">Annuler</a></div>
        </form>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-folder"></i> Catégories <span class="badge badge-secondary"><?= count($categories) ?></span></h3>
        <a href="<?= url('categories.php?action=new') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nouvelle</a>
    </div>
    <?php if (empty($categories)): ?>
        <div class="empty-state"><i class="fas fa-folder-open"></i><h3>Aucune catégorie</h3></div>
    <?php else: ?>
        <table class="table">
            <thead><tr><th></th><th>Nom</th><th>Description</th><th>Outils</th><th>Ordre</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td class="text-center"><?php if ($c['icon']): ?><i class="<?= e($c['icon']) ?>" style="font-size:1.25rem;color:<?= e($c['color'] ?? '#502b85') ?>;"></i><?php else: ?><span style="display:inline-block;width:20px;height:20px;border-radius:4px;background:<?= e($c['color'] ?? '#502b85') ?>;"></span><?php endif; ?></td>
                    <td><strong><?= e($c['name']) ?></strong><br><small class="text-muted"><?= e($c['slug']) ?></small></td>
                    <td><?= e($c['description'] ?: '-') ?></td>
                    <td><span class="badge badge-secondary"><?= $c['tools_count'] ?></span></td>
                    <td><?= $c['display_order'] ?></td>
                    <td><div class="table-actions">
                        <a href="<?= url("categories.php?action=edit&id={$c['id']}") ?>" class="btn btn-sm btn-icon btn-outline"><i class="fas fa-edit"></i></a>
                        <?php if ($c['tools_count'] == 0): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_category"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn btn-sm btn-icon btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form>
                        <?php endif; ?>
                    </div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
