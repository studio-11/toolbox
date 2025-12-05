<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e(SITE_TITLE); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://lms.ifen.lu/ifenCSS/images/favicon.png">
    
    <!-- Fonts -->
    <link rel="stylesheet" href="<?php echo FONT_URL; ?>">
    <link rel="stylesheet" href="<?php echo FONTAWESOME_URL; ?>">
    
    <!-- CSS IFEN Moodle (styles globaux) -->
    <link rel="stylesheet" href="<?php echo IFEN_MOODLE_CSS; ?>">
    
    <!-- CSS Base Toolbox -->
    <link rel="stylesheet" href="<?php echo asset('css/base.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/layout.css'); ?>">
    
    <!-- CSS Briques -->
    <link rel="stylesheet" href="<?php echo asset('css/brick-tools.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/brick-ideas.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/brick-beta.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/brick-works.css'); ?>">
</head>
<body>
    <!-- Header -->
    <header class="toolbox-header" id="main-header">
        <div class="header-container">
            <div class="header-logo">
                <a href="<?php echo url(); ?>">
                    <img src="https://lms.ifen.lu/ifen_images/IFEN_logo.png" alt="IFEN" class="logo-img" onerror="this.style.display='none'">
                    <span class="logo-text">Toolbox</span>
                </a>
            </div>
            
            <nav class="header-nav" id="main-nav">
                <a href="#tools-section" class="nav-link">
                    <i class="fas fa-tools"></i> Outils
                </a>
                <a href="#ideas-section" class="nav-link">
                    <i class="fas fa-lightbulb"></i> Idées
                </a>
                <a href="#beta-section" class="nav-link">
                    <i class="fas fa-flask"></i> Beta
                </a>
            </nav>
            
            <div class="header-actions">
                <?php if (isLoggedIn()): ?>
                    <div class="user-menu">
                        <div class="user-info" id="user-menu-trigger">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo e(getCurrentUser()['name']); ?></span>
                            <?php if (isAdmin()): ?>
                                <span class="admin-badge" title="Administrateur">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                            <?php endif; ?>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="user-dropdown" id="user-dropdown">
                            <div class="dropdown-header">
                                <strong><?php echo e(getCurrentUser()['name']); ?></strong>
                                <small><?php echo e(getCurrentUser()['email']); ?></small>
                            </div>
                            <?php if (isAdmin()): ?>
                                <a href="<?php echo url('admin/'); ?>" class="dropdown-item">
                                    <i class="fas fa-cog"></i> Administration
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo url('logout.php'); ?>" class="dropdown-item dropdown-item-danger">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?php echo url('login.php'); ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-sign-in-alt"></i> Connexion
                    </a>
                <?php endif; ?>
            </div>
            
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>

    <style>
    /* Styles pour le menu utilisateur */
    .user-menu {
        position: relative;
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background 0.2s;
    }
    
    .user-info:hover {
        background: rgba(0, 0, 0, 0.05);
    }
    
    .admin-badge {
        background: var(--accent, #ffc107);
        color: var(--primary, #20164D);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.7rem;
    }
    
    .user-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        min-width: 220px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.2s ease;
        z-index: 1000;
        overflow: hidden;
    }
    
    .user-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(5px);
    }
    
    .dropdown-header {
        padding: 15px;
        background: var(--gray-50, #f8fafc);
        border-bottom: 1px solid var(--gray-200, #e2e8f0);
    }
    
    .dropdown-header strong {
        display: block;
        color: var(--primary, #20164D);
    }
    
    .dropdown-header small {
        color: var(--gray-500, #64748b);
        font-size: 0.85rem;
    }
    
    .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 15px;
        color: var(--gray-700, #334155);
        text-decoration: none;
        transition: background 0.2s;
    }
    
    .dropdown-item:hover {
        background: var(--gray-100, #f1f5f9);
    }
    
    .dropdown-item i {
        width: 18px;
        text-align: center;
        color: var(--gray-500, #64748b);
    }
    
    .dropdown-item-danger {
        color: #ef4444;
    }
    
    .dropdown-item-danger i {
        color: #ef4444;
    }
    
    .dropdown-item-danger:hover {
        background: #fee2e2;
    }
    </style>

    <script>
    // Toggle du menu utilisateur
    document.addEventListener('DOMContentLoaded', function() {
        const trigger = document.getElementById('user-menu-trigger');
        const dropdown = document.getElementById('user-dropdown');
        
        if (trigger && dropdown) {
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('open');
            });
            
            document.addEventListener('click', function() {
                dropdown.classList.remove('open');
            });
        }
    });
    </script>
