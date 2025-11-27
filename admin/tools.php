<?php
/**
 * IFEN Toolbox Admin - Gestion des Outils
 * Version mise à jour avec sélecteurs et beta course
 */
$pageTitle = 'Outils';
require_once __DIR__ . '/includes/header.php';

$pdo = getDbConnection();

$toolStatuses = [
    'stable' => ['label' => 'Stable', 'class' => 'success'],
    'new' => ['label' => 'Nouveau', 'class' => 'info'],
    'beta' => ['label' => 'Beta', 'class' => 'warning'],
    'deprecated' => ['label' => 'Déprécié', 'class' => 'danger'],
];

$toolTypes = [
    'course' => 'Activité de cours',
    'platform' => 'Outil plateforme',
];

$difficulties = [
    'debutant' => 'Débutant',
    'intermediaire' => 'Intermédiaire',
    'avance' => 'Avancé',
];

$audiences = [
    'participant' => 'Participant',
    'manager' => 'Manager IFEN',
    'admin' => 'Admin only',
];

// Icônes FontAwesome populaires pour les outils
$popularIcons = [
    'fas fa-puzzle-piece', 'fas fa-video', 'fas fa-file-alt', 'fas fa-comments', 
    'fas fa-users', 'fas fa-chart-bar', 'fas fa-graduation-cap', 'fas fa-book',
    'fas fa-edit', 'fas fa-tasks', 'fas fa-clipboard-list', 'fas fa-question-circle',
    'fas fa-poll', 'fas fa-calendar', 'fas fa-folder', 'fas fa-image',
    'fas fa-music', 'fas fa-microphone', 'fas fa-play-circle', 'fas fa-link',
    'fas fa-code', 'fas fa-database', 'fas fa-cogs', 'fas fa-magic',
    'fas fa-robot', 'fas fa-brain', 'fas fa-lightbulb', 'fas fa-rocket',
    'fas fa-award', 'fas fa-certificate', 'fas fa-star', 'fas fa-heart',
];

// Gradients prédéfinis
$gradients = [
    'linear-gradient(135deg, #502b85, #7c3aed)' => 'Violet IFEN',
    'linear-gradient(135deg, #17a2b8, #0dcaf0)' => 'Cyan IFEN',
    'linear-gradient(135deg, #502b85, #17a2b8)' => 'Violet → Cyan',
    'linear-gradient(135deg, #28a745, #20c997)' => 'Vert',
    'linear-gradient(135deg, #fd7e14, #ffc107)' => 'Orange → Jaune',
    'linear-gradient(135deg, #dc3545, #fd7e14)' => 'Rouge → Orange',
    'linear-gradient(135deg, #6f42c1, #e83e8c)' => 'Violet → Rose',
    'linear-gradient(135deg, #007bff, #6610f2)' => 'Bleu → Indigo',
    'linear-gradient(135deg, #343a40, #6c757d)' => 'Gris foncé',
];

$action = $_GET['action'] ?? 'list';
$id = intval($_GET['id'] ?? 0);
$error = '';

// Catégories
$categories = [];
try {
    $stmt = $pdo->query("SELECT * FROM toolbox_categories ORDER BY display_order, name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {}

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postAction = $_POST['action'] ?? '';
    
    if ($postAction === 'save_tool') {
        // Gérer les audiences multiples
        $audienceArray = $_POST['audience'] ?? [];
        $audienceString = is_array($audienceArray) ? implode(',', $audienceArray) : '';
        
        $d = [
            'name' => trim($_POST['name'] ?? ''),
            'slug' => trim($_POST['slug'] ?? ''),
            'type' => $_POST['type'] ?? 'course',
            'short_description' => trim($_POST['short_description'] ?? ''),
            'long_description' => trim($_POST['long_description'] ?? ''),
            'category_id' => intval($_POST['category_id'] ?? 0) ?: null,
            'icon' => trim($_POST['icon'] ?? ''),
            'gradient' => trim($_POST['gradient'] ?? ''),
            'status' => $_POST['status'] ?? 'stable',
            'is_hot' => isset($_POST['is_hot']) ? 1 : 0,
            'difficulty' => $_POST['difficulty'] ?? 'intermediaire',
            'audience' => $audienceString,
            'screenshot_url' => trim($_POST['screenshot_url'] ?? ''),
            'video_url' => trim($_POST['video_url'] ?? ''),
            'beta_course_id' => ($_POST['status'] === 'beta') ? (intval($_POST['beta_course_id'] ?? 0) ?: null) : null,
            'beta_start_date' => ($_POST['status'] === 'beta') ? ($_POST['beta_start_date'] ?: null) : null,
            'beta_end_date' => ($_POST['status'] === 'beta') ? ($_POST['beta_end_date'] ?: null) : null,
        ];
        
        if (empty($d['slug']) && !empty($d['name'])) {
            $d['slug'] = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $d['name']));
        }
        
        if (empty($d['name']) || empty($d['short_description'])) {
            $error = 'Nom et description courte obligatoires.';
        } else {
            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare("UPDATE toolbox_tools SET name=?, slug=?, type=?, short_description=?, long_description=?, category_id=?, icon=?, gradient=?, status=?, is_hot=?, difficulty=?, audience=?, screenshot_url=?, video_url=?, beta_course_id=?, beta_start_date=?, beta_end_date=? WHERE id=?");
                    $stmt->execute([$d['name'], $d['slug'], $d['type'], $d['short_description'], $d['long_description'], $d['category_id'], $d['icon'], $d['gradient'], $d['status'], $d['is_hot'], $d['difficulty'], $d['audience'], $d['screenshot_url'], $d['video_url'], $d['beta_course_id'], $d['beta_start_date'], $d['beta_end_date'], $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO toolbox_tools (name, slug, type, short_description, long_description, category_id, icon, gradient, status, is_hot, difficulty, audience, screenshot_url, video_url, beta_course_id, beta_start_date, beta_end_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$d['name'], $d['slug'], $d['type'], $d['short_description'], $d['long_description'], $d['category_id'], $d['icon'], $d['gradient'], $d['status'], $d['is_hot'], $d['difficulty'], $d['audience'], $d['screenshot_url'], $d['video_url'], $d['beta_course_id'], $d['beta_start_date'], $d['beta_end_date']]);
                }
                setFlash('success', 'Outil enregistré.'); header('Location: ' . url('tools.php')); exit;
            } catch (PDOException $e) { $error = $e->getMessage(); }
        }
    }
    
    if ($postAction === 'delete_tool' && $id > 0) {
        try {
            $pdo->prepare("DELETE FROM toolbox_tools WHERE id = ?")->execute([$id]);
            setFlash('success', 'Supprimé.'); header('Location: ' . url('tools.php')); exit;
        } catch (PDOException $e) { $error = $e->getMessage(); }
    }
}

// Edition
$currentTool = null;
$currentAudiences = [];
if ($action === 'edit' && $id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM toolbox_tools WHERE id = ?");
    $stmt->execute([$id]);
    $currentTool = $stmt->fetch();
    if (!$currentTool) { setFlash('error', 'Non trouvé.'); header('Location: ' . url('tools.php')); exit; }
    $currentAudiences = $currentTool['audience'] ? explode(',', $currentTool['audience']) : [];
}

// Liste
$filterCategory = $_GET['filter_category'] ?? '';
$filterStatus = $_GET['filter_status'] ?? '';
$filterType = $_GET['filter_type'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT t.*, c.name as category_name FROM toolbox_tools t LEFT JOIN toolbox_categories c ON t.category_id = c.id WHERE 1=1";
$params = [];
if ($filterCategory) { $sql .= " AND t.category_id = ?"; $params[] = $filterCategory; }
if ($filterStatus) { $sql .= " AND t.status = ?"; $params[] = $filterStatus; }
if ($filterType) { $sql .= " AND t.type = ?"; $params[] = $filterType; }
if ($search) { $sql .= " AND (t.name LIKE ? OR t.short_description LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
$sql .= " ORDER BY t.name";

$tools = [];
try { $stmt = $pdo->prepare($sql); $stmt->execute($params); $tools = $stmt->fetchAll(); } catch (PDOException $e) { $error = $e->getMessage(); }
?>

<?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= e($error) ?></div><?php endif; ?>

<?php if ($action === 'new' || $action === 'edit'): ?>
<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-<?= $action === 'edit' ? 'edit' : 'plus' ?>"></i> <?= $action === 'edit' ? 'Modifier' : 'Nouvel' ?> outil</h3>
        <a href="<?= url('tools.php') ?>" class="btn btn-sm btn-outline"><i class="fas fa-times"></i> Annuler</a>
    </div>
    <div class="card-body">
        <form method="POST" id="toolForm">
            <input type="hidden" name="action" value="save_tool">
            
            <div class="form-row">
                <div class="form-group"><label class="form-label required">Nom</label><input type="text" name="name" class="form-control" required value="<?= e($currentTool['name'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Slug</label><input type="text" name="slug" class="form-control" value="<?= e($currentTool['slug'] ?? '') ?>" placeholder="auto-généré"></div>
            </div>
            
            <div class="form-group"><label class="form-label required">Description courte</label><input type="text" name="short_description" class="form-control" required value="<?= e($currentTool['short_description'] ?? '') ?>"></div>
            <div class="form-group"><label class="form-label">Description longue</label><textarea name="long_description" class="form-control" rows="3"><?= e($currentTool['long_description'] ?? '') ?></textarea></div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Type</label><select name="type" class="form-control"><?php foreach ($toolTypes as $k => $v): ?><option value="<?= $k ?>" <?= ($currentTool['type'] ?? 'course') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Catégorie</label><select name="category_id" class="form-control"><option value="">--</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($currentTool['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label class="form-label">Statut</label><select name="status" id="statusSelect" class="form-control"><?php foreach ($toolStatuses as $k => $v): ?><option value="<?= $k ?>" <?= ($currentTool['status'] ?? 'stable') === $k ? 'selected' : '' ?>><?= $v['label'] ?></option><?php endforeach; ?></select></div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Difficulté d'utilisation</label><select name="difficulty" class="form-control"><?php foreach ($difficulties as $k => $v): ?><option value="<?= $k ?>" <?= ($currentTool['difficulty'] ?? 'intermediaire') === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
                <div class="form-group">
                    <label class="form-label">Public cible</label>
                    <div class="checkbox-group" style="display:flex;gap:1rem;flex-wrap:wrap;padding:0.5rem 0;">
                        <?php foreach ($audiences as $k => $v): ?>
                            <label class="form-check" style="margin:0;">
                                <input type="checkbox" name="audience[]" value="<?= $k ?>" <?= in_array($k, $currentAudiences) ? 'checked' : '' ?>>
                                <span><?= $v ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Sélecteur d'icône -->
            <div class="form-group">
                <label class="form-label">Icône FontAwesome</label>
                <div class="icon-selector">
                    <input type="text" name="icon" id="iconInput" class="form-control" value="<?= e($currentTool['icon'] ?? '') ?>" placeholder="fas fa-puzzle-piece">
                    <div class="icon-preview" id="iconPreview" style="display:inline-flex;align-items:center;justify-content:center;width:40px;height:38px;background:var(--bg-body);border-radius:var(--border-radius);margin-left:0.5rem;">
                        <i class="<?= e($currentTool['icon'] ?? 'fas fa-puzzle-piece') ?>" style="font-size:1.25rem;color:var(--ifen-violet);"></i>
                    </div>
                </div>
                <div class="icon-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(40px,1fr));gap:0.5rem;margin-top:0.5rem;max-height:120px;overflow-y:auto;padding:0.5rem;background:var(--bg-body);border-radius:var(--border-radius);">
                    <?php foreach ($popularIcons as $icon): ?>
                        <button type="button" class="icon-btn" data-icon="<?= $icon ?>" style="padding:0.5rem;border:1px solid var(--border-color);border-radius:var(--border-radius);background:white;cursor:pointer;transition:all 0.2s;" title="<?= $icon ?>">
                            <i class="<?= $icon ?>"></i>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sélecteur de gradient -->
            <div class="form-group">
                <label class="form-label">Gradient</label>
                <input type="text" name="gradient" id="gradientInput" class="form-control" value="<?= e($currentTool['gradient'] ?? '') ?>" placeholder="linear-gradient(135deg, #502b85, #17a2b8)">
                <div class="gradient-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:0.5rem;margin-top:0.5rem;">
                    <?php foreach ($gradients as $grad => $name): ?>
                        <button type="button" class="gradient-btn" data-gradient="<?= e($grad) ?>" style="padding:0.75rem;border:2px solid transparent;border-radius:var(--border-radius);background:<?= $grad ?>;color:white;cursor:pointer;font-size:0.75rem;font-weight:600;text-shadow:0 1px 2px rgba(0,0,0,0.3);" title="<?= $name ?>">
                            <?= $name ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group"><label class="form-label">Screenshot URL</label><input type="url" name="screenshot_url" class="form-control" value="<?= e($currentTool['screenshot_url'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Video URL</label><input type="url" name="video_url" class="form-control" value="<?= e($currentTool['video_url'] ?? '') ?>"></div>
            </div>
            
            <!-- Section Beta (visible uniquement si statut = beta) -->
            <div id="betaSection" style="display:<?= ($currentTool['status'] ?? '') === 'beta' ? 'block' : 'none' ?>;background:var(--bg-body);padding:1rem;border-radius:var(--border-radius);margin:1rem 0;border-left:4px solid var(--color-warning);">
                <h4 style="margin:0 0 1rem;color:var(--color-warning);"><i class="fas fa-flask"></i> Configuration Beta Test</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Course ID Moodle</label>
                        <input type="number" name="beta_course_id" class="form-control" value="<?= e($currentTool['beta_course_id'] ?? '') ?>" placeholder="Ex: 123">
                        <small class="text-muted">ID du cours de démonstration sur LearningSphere</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date début beta</label>
                        <input type="date" name="beta_start_date" class="form-control" value="<?= $currentTool['beta_start_date'] ?? '' ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date fin beta</label>
                        <input type="date" name="beta_end_date" class="form-control" value="<?= $currentTool['beta_end_date'] ?? '' ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-group"><label class="form-check"><input type="checkbox" name="is_hot" value="1" <?= ($currentTool['is_hot'] ?? 0) ? 'checked' : '' ?>> 🔥 Hot</label></div>
            
            <div class="btn-group"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button><a href="<?= url('tools.php') ?>" class="btn btn-outline">Annuler</a></div>
        </form>
    </div>
</div>

<script>
// Sélecteur d'icône
document.querySelectorAll('.icon-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const icon = this.dataset.icon;
        document.getElementById('iconInput').value = icon;
        document.getElementById('iconPreview').innerHTML = '<i class="' + icon + '" style="font-size:1.25rem;color:var(--ifen-violet);"></i>';
        document.querySelectorAll('.icon-btn').forEach(b => b.style.borderColor = 'var(--border-color)');
        this.style.borderColor = 'var(--ifen-violet)';
    });
});

// Mise à jour preview icône en temps réel
document.getElementById('iconInput').addEventListener('input', function() {
    document.getElementById('iconPreview').innerHTML = '<i class="' + this.value + '" style="font-size:1.25rem;color:var(--ifen-violet);"></i>';
});

// Sélecteur de gradient
document.querySelectorAll('.gradient-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('gradientInput').value = this.dataset.gradient;
        document.querySelectorAll('.gradient-btn').forEach(b => b.style.borderColor = 'transparent');
        this.style.borderColor = 'white';
    });
});

// Afficher/masquer section beta selon le statut
document.getElementById('statusSelect').addEventListener('change', function() {
    document.getElementById('betaSection').style.display = this.value === 'beta' ? 'block' : 'none';
});
</script>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tools"></i> Outils <span class="badge badge-secondary"><?= count($tools) ?></span></h3>
        <a href="<?= url('tools.php?action=new') ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Nouveau</a>
    </div>
    <div class="card-body" style="border-bottom: 1px solid var(--border-color); padding-bottom: 1rem;">
        <form method="GET" class="toolbar" style="margin-bottom: 0;">
            <div class="search-box"><i class="fas fa-search"></i><input type="text" name="search" class="form-control" placeholder="Rechercher..." value="<?= e($search) ?>"></div>
            <div class="filters">
                <select name="filter_type" class="form-control" onchange="this.form.submit()"><option value="">Type</option><?php foreach ($toolTypes as $k => $v): ?><option value="<?= $k ?>" <?= $filterType === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select>
                <select name="filter_category" class="form-control" onchange="this.form.submit()"><option value="">Catégorie</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= $filterCategory == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
                <select name="filter_status" class="form-control" onchange="this.form.submit()"><option value="">Statut</option><?php foreach ($toolStatuses as $k => $v): ?><option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v['label'] ?></option><?php endforeach; ?></select>
            </div>
        </form>
    </div>
    <?php if (empty($tools)): ?><div class="empty-state"><i class="fas fa-tools"></i><h3>Aucun outil</h3></div>
    <?php else: ?>
        <table class="table"><thead><tr><th></th><th>Nom</th><th>Type</th><th>Catégorie</th><th>Statut</th><th>Stats</th><th class="text-right">Actions</th></tr></thead><tbody>
            <?php foreach ($tools as $t): $ts = $toolStatuses[$t['status']] ?? ['label' => $t['status'], 'class' => 'secondary']; ?>
                <tr>
                    <td class="text-center"><?php if ($t['icon']): ?><i class="<?= e($t['icon']) ?>" style="font-size:1.25rem;color:var(--ifen-violet);"></i><?php else: ?><i class="fas fa-cube text-muted"></i><?php endif; ?></td>
                    <td><strong><?= e($t['name']) ?></strong><?php if ($t['is_hot']): ?> 🔥<?php endif; ?><?php if ($t['status'] === 'beta' && $t['beta_course_id']): ?> <small class="text-muted">(Course #<?= $t['beta_course_id'] ?>)</small><?php endif; ?><br><small class="text-muted"><?= e(mb_substr($t['short_description'], 0, 50)) ?>...</small></td>
                    <td><span class="badge badge-<?= $t['type'] === 'platform' ? 'info' : 'secondary' ?>"><?= $toolTypes[$t['type']] ?? $t['type'] ?></span></td>
                    <td><?= e($t['category_name'] ?? '-') ?></td>
                    <td><span class="badge badge-<?= $ts['class'] ?>"><?= $ts['label'] ?></span></td>
                    <td><small class="text-muted"><i class="fas fa-eye"></i> <?= $t['views_count'] ?? 0 ?> · ⭐ <?= number_format($t['rating_avg'] ?? 0, 1) ?></small></td>
                    <td><div class="table-actions"><a href="<?= url("tools.php?action=edit&id={$t['id']}") ?>" class="btn btn-sm btn-icon btn-outline"><i class="fas fa-edit"></i></a><form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ?');"><input type="hidden" name="action" value="delete_tool"><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-icon btn-outline" style="color:var(--color-danger);"><i class="fas fa-trash"></i></button></form></div></td>
                </tr>
            <?php endforeach; ?>
        </tbody></table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
