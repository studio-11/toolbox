/**
 * IFEN Toolbox - Application Principale
 * ======================================
 * Logique commune et orchestration des briques
 */

(function() {
    'use strict';
    
    // ==================== MOBILE MENU ====================
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const headerNav = document.getElementById('header-nav');
    
    if (mobileMenuBtn && headerNav) {
        mobileMenuBtn.addEventListener('click', () => {
            headerNav.classList.toggle('open');
            const icon = mobileMenuBtn.querySelector('i');
            icon.classList.toggle('fa-bars');
            icon.classList.toggle('fa-times');
        });
        
        // Fermer le menu mobile au clic sur un lien
        headerNav.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                headerNav.classList.remove('open');
                const icon = mobileMenuBtn.querySelector('i');
                icon.classList.add('fa-bars');
                icon.classList.remove('fa-times');
            });
        });
    }
    
    // ==================== QUICK NAV ====================
    const quickNavBtns = document.querySelectorAll('.quick-nav-btn');
    
    quickNavBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.dataset.target;
            const target = document.getElementById(targetId);
            
            if (target) {
                // Update active state
                quickNavBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                
                // Scroll to section
                const headerHeight = document.querySelector('.toolbox-header')?.offsetHeight || 0;
                const quickNavHeight = document.querySelector('.quick-nav')?.offsetHeight || 0;
                const offset = headerHeight + quickNavHeight + 20;
                
                const position = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: position, behavior: 'smooth' });
            }
        });
    });
    
    // Update quick nav on scroll
    const observerOptions = {
        root: null,
        rootMargin: '-30% 0px -70% 0px',
        threshold: 0
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const sectionId = entry.target.id;
                quickNavBtns.forEach(btn => {
                    btn.classList.toggle('active', btn.dataset.target === sectionId);
                });
                
                // Update header nav
                document.querySelectorAll('.header-nav .nav-link').forEach(link => {
                    link.classList.toggle('active', link.getAttribute('href') === `#${sectionId}`);
                });
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.brick-section').forEach(section => {
        observer.observe(section);
    });
    
    // ==================== SCROLL TO TOP ====================
    const scrollToTopBtn = document.getElementById('scroll-to-top');
    
    if (scrollToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.pageYOffset > 500) {
                scrollToTopBtn.classList.add('visible');
            } else {
                scrollToTopBtn.classList.remove('visible');
            }
        });
        
        scrollToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // ==================== LOAD STATS ====================
    async function loadStats() {
        try {
            const stats = await ToolboxUtils.apiCall('stats');
            
            const statTools = document.getElementById('stat-tools');
            const statBeta = document.getElementById('stat-beta');
            const statIdeas = document.getElementById('stat-ideas');
            
            if (statTools) statTools.textContent = stats.tools_count || 0;
            if (statBeta) statBeta.textContent = stats.beta_count || 0;
            if (statIdeas) statIdeas.textContent = stats.ideas_count || 0;
        } catch (error) {
            // Fallback values
            const statTools = document.getElementById('stat-tools');
            const statBeta = document.getElementById('stat-beta');
            const statIdeas = document.getElementById('stat-ideas');
            
            if (statTools) statTools.textContent = '12';
            if (statBeta) statBeta.textContent = '3';
            if (statIdeas) statIdeas.textContent = '8';
        }
    }
    
    // ==================== SMOOTH SCROLL FOR ANCHOR LINKS ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const targetId = href.substring(1);
            const target = document.getElementById(targetId);
            
            if (target) {
                const headerHeight = document.querySelector('.toolbox-header')?.offsetHeight || 0;
                const quickNavHeight = document.querySelector('.quick-nav')?.offsetHeight || 0;
                const offset = headerHeight + quickNavHeight + 20;
                
                const position = target.getBoundingClientRect().top + window.pageYOffset - offset;
                window.scrollTo({ top: position, behavior: 'smooth' });
            }
        });
    });
    
    // ==================== INITIALIZATION ====================
    document.addEventListener('DOMContentLoaded', async () => {
        console.log('🚀 IFEN Toolbox starting...');
        
        // Charger les stats
        loadStats();
        
        // Les briques s'initialisent elles-mêmes via leurs propres scripts
        // Vérifier qu'elles sont chargées
        setTimeout(() => {
            const loadedBricks = [];
            if (typeof BrickTools !== 'undefined') loadedBricks.push('Tools');
            if (typeof BrickIdeas !== 'undefined') loadedBricks.push('Ideas');
            if (typeof BrickBeta !== 'undefined') loadedBricks.push('Beta');
            
            console.log('✅ Briques chargées:', loadedBricks.join(', '));
            console.log('🎉 IFEN Toolbox ready!');
        }, 100);
    });
    
})();
