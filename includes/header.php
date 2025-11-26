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
                <div class="user-info">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo e(getCurrentUser()['name']); ?></span>
                </div>
            </div>
            
            <button class="mobile-menu-toggle" id="mobile-menu-toggle" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile-menu-overlay" id="mobile-menu-overlay"></div>
