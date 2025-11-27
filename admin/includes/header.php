<?php
/**
 * IFEN Toolbox Admin - Header
 */
require_once __DIR__ . '/config.php';

// Pages publiques (sans auth requise)
$publicPages = ['login.php'];
$currentFile = basename($_SERVER['PHP_SELF']);

if (!in_array($currentFile, $publicPages)) {
    requireAdmin();
}

$admin = getCurrentAdmin();
$flash = getFlash();

// Navigation
$navItems = [
    ['url' => 'index.php', 'icon' => 'fas fa-tachometer-alt', 'label' => 'Dashboard'],
    ['url' => 'works.php', 'icon' => 'fas fa-hard-hat', 'label' => 'Travaux'],
    ['url' => 'tools.php', 'icon' => 'fas fa-tools', 'label' => 'Outils'],
    ['url' => 'categories.php', 'icon' => 'fas fa-folder', 'label' => 'Catégories'],
    ['url' => 'ideas.php', 'icon' => 'fas fa-lightbulb', 'label' => 'Idées'],
    ['url' => 'beta.php', 'icon' => 'fas fa-flask', 'label' => 'Beta Testing'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Admin') ?> - <?= SITE_TITLE ?></title>
    
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="<?= FONT_URL ?>" rel="stylesheet">
    <link rel="stylesheet" href="<?= FONTAWESOME_URL ?>">
    
    <!-- Admin CSS -->
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
    
    <?php if (isset($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= asset($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
    
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <i class="fas fa-toolbox"></i>
                <span>Toolbox Admin</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <?php foreach ($navItems as $item): ?>
                <a href="<?= url($item['url']) ?>" 
                   class="nav-item <?= $currentFile === $item['url'] ? 'active' : '' ?>">
                    <i class="<?= $item['icon'] ?>"></i>
                    <span><?= $item['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        
        <div class="sidebar-footer">
            <a href="<?= frontendUrl() ?>" class="nav-item" target="_blank">
                <i class="fas fa-external-link-alt"></i>
                <span>Voir le site</span>
            </a>
            <a href="<?= url('logout.php') ?>" class="nav-item nav-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Déconnexion</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-toggle" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
            </div>
            <div class="topbar-right">
                <?php if ($admin): ?>
                    <div class="admin-user">
                        <i class="fas fa-user-circle"></i>
                        <span><?= e($admin['name']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </header>
        
        <!-- Flash Messages -->
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>" id="flashAlert">
                <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'error' ? 'exclamation-circle' : 'info-circle') ?>"></i>
                <span><?= e($flash['message']) ?></span>
                <button class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        <?php endif; ?>
        
        <!-- Page Content -->
        <div class="admin-content">
