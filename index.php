<?php
/**
 * IFEN Toolbox - Page Principale
 * ==============================
 * Assembleur des briques modulaires
 */

// Configuration
require_once __DIR__ . '/includes/config.php';

// Header
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1 class="hero-title"><i class="fas fa-toolbox"></i> Toolbox IFEN</h1>
        <p class="hero-subtitle">Découvrez les outils numériques pour enrichir vos formations</p>
        
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="hero-stat-value" id="stat-tools">--</span>
                <span class="hero-stat-label">Outils disponibles</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value" id="stat-beta">--</span>
                <span class="hero-stat-label">En beta test</span>
            </div>
            <div class="hero-stat">
                <span class="hero-stat-value" id="stat-ideas">--</span>
                <span class="hero-stat-label">Idées proposées</span>
            </div>
        </div>
        
        <div class="hero-actions">
            <a href="#tools-section" class="btn btn-accent btn-lg">
                <i class="fas fa-tools"></i> Explorer les outils
            </a>
            <a href="#ideas-section" class="btn btn-outline-light btn-lg">
                <i class="fas fa-lightbulb"></i> Proposer une idée
            </a>
        </div>
    </div>
</section>

<!-- Navigation rapide (sticky) -->
<nav class="quick-nav" id="quick-nav">
    <div class="quick-nav-container">
        <a href="#tools-section" class="quick-nav-item active" data-section="tools-section">
            <i class="fas fa-tools"></i>
            <span>Outils Disponibles</span>
        </a>
        <a href="#beta-section" class="quick-nav-item" data-section="beta-section">
            <i class="fas fa-flask"></i>
            <span>Beta Testing</span>
        </a>
        <a href="#ideas-section" class="quick-nav-item" data-section="ideas-section">
            <i class="fas fa-lightbulb"></i>
            <span>Idées & Votes</span>
        </a>
    </div>
</nav>

<!-- Contenu principal -->
<main class="main-content">
    
    <!-- ========================================== -->
    <!-- BRIQUE 1 : OUTILS DISPONIBLES -->
    <!-- ========================================== -->
    <section id="tools-section" class="brick-section">
        <?php include __DIR__ . '/briques/brick-tools.php'; ?>
    </section>
    
    <!-- ========================================== -->
    <!-- BRIQUE 2 : BETA TESTING -->
    <!-- ========================================== -->
    <section id="beta-section" class="brick-section">
        <?php include __DIR__ . '/briques/brick-beta.php'; ?>
    </section>
    
    <!-- ========================================== -->
    <!-- BRIQUE 3 : IDÉES & VOTES -->
    <!-- ========================================== -->
    <section id="ideas-section" class="brick-section">
        <?php include __DIR__ . '/briques/brick-ideas.php'; ?>
    </section>
    
</main>

<!-- Bouton scroll to top -->
<button class="scroll-to-top" id="scroll-to-top" title="Retour en haut">
    <i class="fas fa-chevron-up"></i>
</button>

<?php
// Footer
require_once __DIR__ . '/includes/footer.php';
?>
