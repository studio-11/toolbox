/**
 * IFEN Toolbox - Configuration Globale
 * ====================================
 * Ce fichier contient la configuration partagée entre toutes les briques
 */

const TOOLBOX_CONFIG = {
    // API Configuration
    api: {
        baseUrl: '/ifen_html/toolbox/api/api.php',
        timeout: 10000,
        retryAttempts: 3
    },
    
    // Statuts des outils
    toolStatus: {
        stable: { label: 'Stable', badge: 'success', icon: 'fa-check-circle' },
        new: { label: 'Nouveau', badge: 'info', icon: 'fa-star' },
        beta: { label: 'Beta', badge: 'warning', icon: 'fa-flask' },
        testing: { label: 'En test', badge: 'warning', icon: 'fa-vial' },
        deprecated: { label: 'Déprécié', badge: 'danger', icon: 'fa-exclamation-triangle' }
    },
    
    // Statuts des idées
    ideaStatus: {
        proposed: { label: 'Proposé', badge: 'info', icon: 'fa-lightbulb' },
        under_review: { label: 'En révision', badge: 'secondary', icon: 'fa-search' },
        planned: { label: 'Planifié', badge: 'primary', icon: 'fa-calendar-alt' },
        in_progress: { label: 'En cours', badge: 'warning', icon: 'fa-cog fa-spin' },
        completed: { label: 'Terminé', badge: 'success', icon: 'fa-check' },
        rejected: { label: 'Refusé', badge: 'danger', icon: 'fa-times' }
    },
    
    // Phases de développement
    devPhases: {
        analysis: { label: 'Analyse', progress: 10, icon: 'fa-search' },
        design: { label: 'Design', progress: 30, icon: 'fa-pencil-ruler' },
        development: { label: 'Développement', progress: 60, icon: 'fa-code' },
        testing: { label: 'Tests', progress: 85, icon: 'fa-vial' },
        deployment: { label: 'Déploiement', progress: 95, icon: 'fa-rocket' },
        completed: { label: 'Terminé', progress: 100, icon: 'fa-check-circle' }
    },
    
    // Types de feedback beta
    feedbackTypes: {
        bug: { label: 'Bug', icon: 'fa-bug', color: '#ef4444' },
        suggestion: { label: 'Suggestion', icon: 'fa-lightbulb', color: '#ffc107' },
        question: { label: 'Question', icon: 'fa-question-circle', color: '#00b2bb' },
        praise: { label: 'Bravo', icon: 'fa-thumbs-up', color: '#10b981' },
        general: { label: 'Général', icon: 'fa-comment', color: '#64748b' }
    },
    
    // Types de review
    reviewTypes: {
        code_review: { label: 'Code Review', icon: 'fa-code' },
        design_review: { label: 'Design Review', icon: 'fa-palette' },
        ux_review: { label: 'UX Review', icon: 'fa-user' },
        security_review: { label: 'Security Review', icon: 'fa-shield-alt' },
        performance_review: { label: 'Performance Review', icon: 'fa-tachometer-alt' },
        general: { label: 'Général', icon: 'fa-clipboard-check' }
    },
    
    // Priorités
    priorities: {
        low: { label: 'Basse', color: '#64748b', icon: 'fa-arrow-down' },
        medium: { label: 'Moyenne', color: '#00b2bb', icon: 'fa-minus' },
        high: { label: 'Haute', color: '#ffc107', icon: 'fa-arrow-up' },
        critical: { label: 'Critique', color: '#ef4444', icon: 'fa-exclamation' }
    },
    
    // Types d'idées
    ideaTypes: {
        course: { label: 'Module de cours', emoji: '🧩', icon: 'fa-puzzle-piece' },
        platform: { label: 'Fonctionnalité plateforme', emoji: '⚙️', icon: 'fa-cog' },
        improvement: { label: 'Amélioration', emoji: '✨', icon: 'fa-magic' }
    },
    
    // Slider configuration
    slider: {
        autoplay: true,
        autoplaySpeed: 5000,
        slidesToShow: 3,
        slidesToScroll: 1,
        responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 2 } },
            { breakpoint: 768, settings: { slidesToShow: 1 } }
        ]
    },
    
    // Animation settings
    animations: {
        duration: 300,
        easing: 'ease-out'
    }
};

// Exporter pour utilisation globale
window.TOOLBOX_CONFIG = TOOLBOX_CONFIG;
