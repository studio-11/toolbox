    <!-- Footer simple -->
    <footer class="toolbox-footer-simple">
        <p>&copy; <?php echo date('Y'); ?> IFEN - Institut de Formation de l'Éducation Nationale</p>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Configuration globale pour JS
        window.TOOLBOX_CONFIG = {
            apiUrl: '<?php echo API_URL; ?>',
            baseUrl: '<?php echo BASE_URL; ?>',
            user: <?php echo json_encode(getCurrentUser()); ?>,
            version: '<?php echo APP_VERSION; ?>'
        };
    </script>
    
    <!-- JS Modules -->
    <script src="<?php echo asset('js/config.js'); ?>"></script>
    <script src="<?php echo asset('js/utils.js'); ?>"></script>
    <script src="<?php echo asset('js/brick-tools.js'); ?>"></script>
    <script src="<?php echo asset('js/brick-ideas.js'); ?>"></script>
    <script src="<?php echo asset('js/brick-beta.js'); ?>"></script>
    
    <!-- JS Initialisation -->
    <script>
        document.addEventListener('DOMContentLoaded', async function() {
            // Mobile menu
            const mobileToggle = document.getElementById('mobile-menu-toggle');
            const mobileOverlay = document.getElementById('mobile-menu-overlay');
            const mainNav = document.getElementById('main-nav');
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    mainNav.classList.toggle('active');
                    mobileOverlay.classList.toggle('active');
                });
            }
            
            if (mobileOverlay) {
                mobileOverlay.addEventListener('click', function() {
                    mainNav.classList.remove('active');
                    mobileOverlay.classList.remove('active');
                });
            }
            
            // Quick nav active state
            const quickNavItems = document.querySelectorAll('.quick-nav-item');
            const sections = document.querySelectorAll('.brick-section');
            
            const observerOptions = {
                rootMargin: '-20% 0px -70% 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const sectionId = entry.target.id;
                        quickNavItems.forEach(item => {
                            item.classList.toggle('active', item.dataset.section === sectionId);
                        });
                    }
                });
            }, observerOptions);
            
            sections.forEach(section => observer.observe(section));
            
            // Scroll to top button
            const scrollTopBtn = document.getElementById('scroll-to-top');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 500) {
                    scrollTopBtn.classList.add('visible');
                } else {
                    scrollTopBtn.classList.remove('visible');
                }
            });
            
            if (scrollTopBtn) {
                scrollTopBtn.addEventListener('click', function() {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            }
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const headerOffset = 120;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Charger les stats
            try {
                const stats = await ToolboxUtils.apiCall('stats');
                if (stats) {
                    document.getElementById('stat-tools').textContent = stats.tools_count || 0;
                    document.getElementById('stat-beta').textContent = stats.beta_count || 0;
                    document.getElementById('stat-ideas').textContent = stats.ideas_count || 0;
                }
            } catch (e) {
                console.warn('Stats non disponibles');
            }
            
            // Initialiser les briques
            try {
                await BrickTools.init('tools-section');
                await BrickIdeas.init('ideas-section');
                await BrickBeta.init('beta-section');
            } catch (e) {
                console.error('Erreur initialisation briques:', e);
            }
        });
    </script>
</body>
</html>
