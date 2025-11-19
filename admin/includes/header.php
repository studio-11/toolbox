<?php
/**
 * Header commun pour toutes les pages admin
 */

if (!defined('TOOLBOX_INTERNAL')) {
    die('Accès direct interdit');
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$username = getAdminUsername();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin'; ?> - IFEN Toolbox</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-rocket"></i>
                    <span>IFEN Toolbox</span>
                </div>
                <div class="version">Admin v1.0</div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="index.php" class="nav-item <?php echo $current_page === 'index' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="tools.php" class="nav-item <?php echo $current_page === 'tools' || $current_page === 'tool-edit' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Outils</span>
                </a>
                
                <a href="categories.php" class="nav-item <?php echo $current_page === 'categories' ? 'active' : ''; ?>">
                    <i class="fas fa-folder-tree"></i>
                    <span>Catégories</span>
                </a>
                
                <a href="ideas.php" class="nav-item <?php echo $current_page === 'ideas' ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb"></i>
                    <span>Idées</span>
                    <?php
                    // Compter les idées en attente
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_ideas WHERE status = 'proposed'");
                        $count = $stmt->fetchColumn();
                        if ($count > 0) {
                            echo '<span class="badge">' . $count . '</span>';
                        }
                    } catch (Exception $e) {
                        // Ignorer silencieusement
                    }
                    ?>
                </a>
                
                <a href="comments.php" class="nav-item <?php echo $current_page === 'comments' ? 'active' : ''; ?>">
                    <i class="fas fa-comments"></i>
                    <span>Commentaires</span>
                    <?php
                    // Compter les commentaires non approuvés
                    try {
                        $pdo = getDBConnection();
                        $stmt = $pdo->query("SELECT COUNT(*) FROM toolbox_comments WHERE is_approved = 0");
                        $count = $stmt->fetchColumn();
                        if ($count > 0) {
                            echo '<span class="badge">' . $count . '</span>';
                        }
                    } catch (Exception $e) {
                        // Ignorer silencieusement
                    }
                    ?>
                </a>
                
                <div class="nav-divider"></div>
                
                <a href="../index.html" class="nav-item" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Voir la Toolbox</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($username); ?></div>
                        <div class="user-role">Administrateur</div>
                    </div>
                </div>
                <a href="?logout=1" class="btn-logout" title="Déconnexion">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <div class="main-header">
                <h1><?php echo $page_title ?? 'Dashboard'; ?></h1>
                <div class="header-actions">
                    <?php if (isset($header_actions)) echo $header_actions; ?>
                </div>
            </div>
            
            <div class="main-body">