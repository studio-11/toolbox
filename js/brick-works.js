/**
 * ============================================
 * BRICK WORKS - Module JavaScript (Version Light)
 * ============================================
 * Version autonome avec gestion de modal intégrée
 */

const BrickWorks = {
    // Configuration
    config: {
        statusLabels: {
            operational: 'Opérationnel',
            maintenance: 'En maintenance',
            upgrading: 'Mise à jour en cours',
            partial_outage: 'Panne partielle',
            major_outage: 'Panne majeure'
        },
        workStatusLabels: {
            planned: 'Planifié',
            unplanned: 'Non planifié',
            in_progress: 'En cours',
            completed: 'Terminé',
            cancelled: 'Annulé'
        },
        typeLabels: {
            maintenance: 'Maintenance',
            upgrade: 'Mise à jour',
            feature: 'Fonctionnalité',
            bugfix: 'Correction',
            security: 'Sécurité',
            performance: 'Performance',
            other: 'Autre'
        },
        priorityLabels: {
            low: 'Basse',
            medium: 'Moyenne',
            high: 'Haute',
            critical: 'Critique'
        }
    },
    
    // État
    state: {
        platformStatus: null,
        stats: null,
        works: [],
        currentFilter: ''
    },
    
    /**
     * Initialisation
     */
    async init() {
        console.log('🔧 BrickWorks: Initialisation...');
        
        // Créer le container de modal s'il n'existe pas
        this.createModalContainer();
        
        // Charger les données initiales
        await this.loadInitialData();
        
        // Attacher l'événement au trigger
        const trigger = document.getElementById('works-stat-trigger');
        if (trigger) {
            trigger.addEventListener('click', () => this.openWorksModal());
        }
        
        console.log('✅ BrickWorks: Initialisé');
    },
    
    /**
     * Créer le container de modal
     */
    createModalContainer() {
        if (document.getElementById('works-modal-container')) return;
        
        const container = document.createElement('div');
        container.id = 'works-modal-container';
        container.className = 'works-modal-overlay';
        container.innerHTML = `
            <div class="works-modal">
                <div class="works-modal-header-bar">
                    <h2 class="works-modal-title"></h2>
                    <button class="works-modal-close" aria-label="Fermer">&times;</button>
                </div>
                <div class="works-modal-body"></div>
            </div>
        `;
        document.body.appendChild(container);
        
        // Fermer au clic sur l'overlay
        container.addEventListener('click', (e) => {
            if (e.target === container) {
                this.closeModal();
            }
        });
        
        // Fermer avec le bouton X
        container.querySelector('.works-modal-close').addEventListener('click', () => {
            this.closeModal();
        });
        
        // Fermer avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && container.classList.contains('active')) {
                this.closeModal();
            }
        });
    },
    
    /**
     * Ouvrir le modal
     */
    openModal(title, content, options = {}) {
        const container = document.getElementById('works-modal-container');
        if (!container) return;
        
        container.querySelector('.works-modal-title').textContent = title;
        container.querySelector('.works-modal-body').innerHTML = content;
        
        // Taille du modal
        const modal = container.querySelector('.works-modal');
        modal.className = 'works-modal';
        if (options.size === 'large') {
            modal.classList.add('works-modal-large');
        }
        
        container.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Callback onOpen
        if (options.onOpen) {
            setTimeout(options.onOpen, 50);
        }
    },
    
    /**
     * Fermer le modal
     */
    closeModal() {
        const container = document.getElementById('works-modal-container');
        if (!container) return;
        
        container.classList.remove('active');
        document.body.style.overflow = '';
    },
    
    /**
     * Charger les données initiales pour le hero
     */
    async loadInitialData() {
        try {
            // Charger statut plateforme
            const status = await this.apiCall('platform_status');
            if (status) {
                this.state.platformStatus = status;
                this.updateHeroIndicator(status);
            }
            
            // Charger stats travaux
            const stats = await this.apiCall('works_stats');
            if (stats) {
                this.state.stats = stats;
                this.updateHeroStats(stats);
            }
        } catch (error) {
            console.error('Erreur chargement données travaux:', error);
        }
    },
    
    /**
     * Appel API simplifié
     */
    async apiCall(action, params = {}) {
        const url = new URL(window.TOOLBOX_CONFIG?.apiUrl || 'api/api.php', window.location.href);
        url.searchParams.set('action', action);
        
        for (const [key, value] of Object.entries(params)) {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erreur API');
        }
        
        return data.data;
    },
    
    /**
     * Formater une date
     */
    formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    },
    
    /**
     * Mettre à jour l'indicateur dans le hero
     */
    updateHeroIndicator(status) {
        const dot = document.getElementById('platform-status-dot');
        const statusText = document.getElementById('platform-status-text');
        const versionText = document.getElementById('platform-version-text');
        
        if (dot) {
            dot.className = 'platform-status-dot ' + (status.current_status || 'operational');
        }
        
        if (statusText) {
            statusText.textContent = this.config.statusLabels[status.current_status] || 'Opérationnel';
        }
        
        if (versionText && status.moodle_version) {
            versionText.textContent = status.moodle_version;
        }
    },
    
    /**
     * Mettre à jour les stats dans le hero (non utilisé dans cette version)
     */
    updateHeroStats(stats) {
        // Stats non affichées dans le hero dans cette version
    },
    
    /**
     * Ouvrir le modal des travaux
     */
    async openWorksModal(filterStatus = '') {
        this.state.currentFilter = filterStatus;
        
        // Récupérer le template
        const template = document.getElementById('template-works-list-modal');
        if (!template) {
            console.error('Template works-list-modal non trouvé');
            return;
        }
        
        const content = template.content.cloneNode(true);
        const container = document.createElement('div');
        container.appendChild(content);
        
        // Ouvrir le modal
        this.openModal('Travaux & Mises à jour', container.innerHTML, {
            size: 'large',
            onOpen: () => this.initModalContent(filterStatus)
        });
    },
    
    /**
     * Initialiser le contenu du modal
     */
    async initModalContent(filterStatus = '') {
        // Remplir le statut plateforme
        await this.loadPlatformStatusInModal();
        
        // Remplir les stats
        await this.loadStatsInModal();
        
        // Attacher les événements des filtres
        this.attachFilterEvents();
        
        // Attacher les événements des stats cliquables
        this.attachQuickStatEvents();
        
        // Charger les travaux
        await this.loadWorks(filterStatus);
    },
    
    /**
     * Charger le statut plateforme dans le modal
     */
    async loadPlatformStatusInModal() {
        try {
            const status = this.state.platformStatus || await this.apiCall('platform_status');
            if (!status) return;
            
            this.state.platformStatus = status;
            
            // Chercher dans le modal body
            const modalBody = document.querySelector('.works-modal-body');
            if (!modalBody) return;
            
            const card = modalBody.querySelector('.platform-status-card');
            if (!card) return;
            
            // Indicateur de statut
            const dot = card.querySelector('.status-dot');
            const text = card.querySelector('.status-text');
            if (dot) {
                dot.className = 'status-dot ' + (status.current_status || 'operational');
            }
            if (text) {
                text.textContent = this.config.statusLabels[status.current_status] || 'Opérationnel';
            }
            
            // Version
            const versionValue = card.querySelector('.version-value');
            if (versionValue) versionValue.textContent = status.platform_version || 'v4.3.2';
            
            // Moodle version
            const moodleVersion = card.querySelector('.moodle-version');
            if (moodleVersion) moodleVersion.textContent = status.moodle_version || '4.3.2+';
            
            // Prochaine maintenance
            const nextMaintenance = modalBody.querySelector('.platform-next-maintenance');
            if (nextMaintenance) {
                if (status.next_planned_maintenance) {
                    const date = new Date(status.next_planned_maintenance);
                    const nextDate = nextMaintenance.querySelector('.next-date');
                    if (nextDate) {
                        nextDate.textContent = date.toLocaleDateString('fr-FR', { 
                            day: 'numeric', 
                            month: 'long', 
                            hour: '2-digit', 
                            minute: '2-digit' 
                        });
                    }
                    nextMaintenance.style.display = '';
                } else {
                    nextMaintenance.style.display = 'none';
                }
            }
            
            // Message
            const messageDisplay = modalBody.querySelector('.platform-message');
            if (messageDisplay) {
                if (status.status_message) {
                    messageDisplay.textContent = status.status_message;
                    messageDisplay.style.display = '';
                } else {
                    messageDisplay.style.display = 'none';
                }
            }
            
        } catch (error) {
            console.error('Erreur chargement statut:', error);
        }
    },
    
    /**
     * Charger les stats dans le modal
     */
    async loadStatsInModal() {
        try {
            const stats = this.state.stats || await this.apiCall('works_stats');
            if (!stats) return;
            
            this.state.stats = stats;
            
            // Chercher dans le modal body
            const modalBody = document.querySelector('.works-modal-body');
            if (!modalBody) return;
            
            const quickStats = modalBody.querySelectorAll('.quick-stat');
            quickStats.forEach(stat => {
                const filter = stat.dataset.filter;
                const valueEl = stat.querySelector('.quick-stat-value');
                if (valueEl && filter && stats[filter] !== undefined) {
                    valueEl.textContent = stats[filter] || 0;
                }
            });
            
        } catch (error) {
            console.error('Erreur chargement stats:', error);
        }
    },
    
    /**
     * Attacher les événements des stats cliquables
     */
    attachQuickStatEvents() {
        const modalBody = document.querySelector('.works-modal-body');
        if (!modalBody) return;
        
        modalBody.querySelectorAll('.quick-stat[data-filter]').forEach(stat => {
            stat.addEventListener('click', () => {
                const filter = stat.dataset.filter;
                
                // Toggle le filtre
                modalBody.querySelectorAll('.quick-stat').forEach(s => s.classList.remove('active'));
                
                const filterSelect = modalBody.querySelector('#works-filter-status');
                
                if (this.state.currentFilter === filter) {
                    this.state.currentFilter = '';
                    if (filterSelect) filterSelect.value = '';
                } else {
                    this.state.currentFilter = filter;
                    stat.classList.add('active');
                    if (filterSelect) filterSelect.value = filter;
                }
                
                this.loadWorks();
            });
        });
    },
    
    /**
     * Attacher les événements des filtres
     */
    attachFilterEvents() {
        const modalBody = document.querySelector('.works-modal-body');
        if (!modalBody) return;
        
        // Recherche avec debounce
        const searchInput = modalBody.querySelector('#works-search');
        if (searchInput) {
            let debounceTimer;
            searchInput.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => this.loadWorks(), 300);
            });
        }
        
        // Filtres select
        ['#works-filter-status', '#works-filter-type'].forEach(selector => {
            const select = modalBody.querySelector(selector);
            if (select) {
                select.addEventListener('change', () => {
                    // Reset les quick stats actives si on change le filtre status
                    if (selector === '#works-filter-status') {
                        modalBody.querySelectorAll('.quick-stat').forEach(s => s.classList.remove('active'));
                        this.state.currentFilter = select.value;
                    }
                    this.loadWorks();
                });
            }
        });
        
        // Checkbox downtime
        const downtimeCheckbox = modalBody.querySelector('#works-filter-downtime');
        if (downtimeCheckbox) {
            downtimeCheckbox.addEventListener('change', () => this.loadWorks());
        }
    },
    
    /**
     * Charger les travaux
     */
    async loadWorks(statusFilter = null) {
        const modalBody = document.querySelector('.works-modal-body');
        if (!modalBody) return;
        
        const container = modalBody.querySelector('#works-list-container') || modalBody.querySelector('.works-list');
        if (!container) return;
        
        // Afficher le loading
        container.innerHTML = `
            <div class="loading-placeholder">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Chargement des travaux...</span>
            </div>
        `;
        
        try {
            // Construire les paramètres
            const params = { limit: 50 };
            
            // Filtre statut
            const statusSelect = modalBody.querySelector('#works-filter-status');
            const status = statusFilter || (statusSelect ? statusSelect.value : '');
            if (status) params.status = status;
            
            // Filtre type
            const typeSelect = modalBody.querySelector('#works-filter-type');
            const type = typeSelect ? typeSelect.value : '';
            if (type) params.type = type;
            
            // Filtre recherche
            const searchInput = modalBody.querySelector('#works-search');
            const search = searchInput ? searchInput.value : '';
            if (search) params.search = search;
            
            // Filtre downtime
            const downtimeCheckbox = modalBody.querySelector('#works-filter-downtime');
            const downtime = downtimeCheckbox ? downtimeCheckbox.checked : false;
            if (downtime) params.downtime = 1;
            
            // Appel API
            const works = await this.apiCall('works', params);
            this.state.works = works || [];
            
            // Afficher
            this.renderWorks(container, this.state.works);
            
        } catch (error) {
            console.error('Erreur chargement travaux:', error);
            container.innerHTML = `
                <div class="empty-placeholder">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Erreur lors du chargement</span>
                </div>
            `;
        }
    },
    
    /**
     * Afficher les travaux
     */
    renderWorks(container, works) {
        if (!works || works.length === 0) {
            container.innerHTML = `
                <div class="empty-placeholder">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Aucun travail trouvé</span>
                </div>
            `;
            return;
        }
        
        const template = document.getElementById('template-work-card');
        container.innerHTML = '';
        
        works.forEach(work => {
            const card = template.content.cloneNode(true);
            const cardEl = card.querySelector('.work-card');
            
            // ID
            cardEl.dataset.workId = work.id;
            
            // Barre de statut
            const statusBar = card.querySelector('.work-card-status-bar');
            statusBar.classList.add(work.status);
            
            // Badges
            const typeBadge = card.querySelector('.badge-type');
            typeBadge.textContent = this.config.typeLabels[work.work_type] || work.work_type;
            typeBadge.classList.add(work.work_type);
            
            const statusBadge = card.querySelector('.badge-status');
            statusBadge.textContent = this.config.workStatusLabels[work.status] || work.status;
            statusBadge.classList.add(work.status);
            
            const priorityBadge = card.querySelector('.badge-priority');
            priorityBadge.textContent = this.config.priorityLabels[work.priority] || work.priority;
            priorityBadge.classList.add(work.priority);
            
            // Downtime
            const downtimeIndicator = card.querySelector('.work-downtime-indicator');
            if (work.causes_downtime == 1) {
                downtimeIndicator.style.display = '';
                const duration = card.querySelector('.downtime-duration');
                if (work.estimated_downtime_minutes) {
                    duration.textContent = this.formatDuration(work.estimated_downtime_minutes);
                }
            }
            
            // Titre et description
            card.querySelector('.work-card-title').textContent = work.title;
            card.querySelector('.work-card-description').textContent = work.description || '';
            
            // Date
            const dateValue = card.querySelector('.date-value');
            if (work.status === 'completed' && work.actual_end_date) {
                dateValue.textContent = 'Terminé le ' + this.formatDate(work.actual_end_date);
            } else if (work.planned_start_date) {
                dateValue.textContent = 'Prévu le ' + this.formatDate(work.planned_start_date);
            } else {
                dateValue.textContent = 'Date non définie';
            }
            
            // Version
            const versionEl = card.querySelector('.work-version');
            if (work.target_version) {
                versionEl.style.display = '';
                versionEl.querySelector('.version-value').textContent = 
                    work.from_version ? `${work.from_version} → ${work.target_version}` : work.target_version;
            }
            
            // Clic pour détails
            cardEl.addEventListener('click', () => this.showWorkDetails(work));
            
            container.appendChild(card);
        });
    },
    
    /**
     * Afficher les détails d'un travail
     */
    showWorkDetails(work) {
        const template = document.getElementById('template-work-details-modal');
        if (!template) return;
        
        const content = template.content.cloneNode(true);
        
        // Badges
        const typeBadge = content.querySelector('.badge-type');
        typeBadge.textContent = this.config.typeLabels[work.work_type] || work.work_type;
        typeBadge.classList.add(work.work_type);
        
        const statusBadge = content.querySelector('.badge-status');
        statusBadge.textContent = this.config.workStatusLabels[work.status] || work.status;
        statusBadge.classList.add(work.status);
        
        const priorityBadge = content.querySelector('.badge-priority');
        priorityBadge.textContent = this.config.priorityLabels[work.priority] || work.priority;
        priorityBadge.classList.add(work.priority);
        
        // Titre et description
        content.querySelector('.work-details-title').textContent = work.title;
        content.querySelector('.work-description-text').textContent = work.description || 'Aucune description';
        
        // Dates planifiées
        const plannedDates = content.querySelector('.planned-dates');
        if (work.planned_start_date) {
            let dateStr = this.formatDate(work.planned_start_date);
            if (work.planned_end_date) {
                dateStr += ' → ' + this.formatDate(work.planned_end_date);
            }
            plannedDates.textContent = dateStr;
        } else {
            plannedDates.textContent = 'Non définie';
        }
        
        // Dates réelles
        const actualDatesItem = content.querySelector('.actual-dates-item');
        if (work.actual_start_date || work.actual_end_date) {
            actualDatesItem.style.display = '';
            const actualDates = content.querySelector('.actual-dates');
            let dateStr = work.actual_start_date ? this.formatDate(work.actual_start_date) : '?';
            if (work.actual_end_date) {
                dateStr += ' → ' + this.formatDate(work.actual_end_date);
            }
            actualDates.textContent = dateStr;
        }
        
        // Version
        const versionItem = content.querySelector('.version-item');
        if (work.target_version) {
            versionItem.style.display = '';
            const versionInfo = content.querySelector('.version-info');
            versionInfo.textContent = work.from_version ? 
                `${work.from_version} → ${work.target_version}` : work.target_version;
        }
        
        // Downtime
        const downtimeItem = content.querySelector('.downtime-item');
        if (work.causes_downtime == 1) {
            downtimeItem.style.display = '';
            const downtimeInfo = content.querySelector('.downtime-info');
            downtimeInfo.innerHTML = `<span style="color: #dc3545;"><i class="fas fa-exclamation-triangle"></i> Oui</span>`;
            if (work.estimated_downtime_minutes) {
                downtimeInfo.innerHTML += ` (${this.formatDuration(work.estimated_downtime_minutes)})`;
            }
        }
        
        // Services affectés
        const servicesItem = content.querySelector('.services-item');
        if (work.affected_services) {
            servicesItem.style.display = '';
            const servicesList = content.querySelector('.services-list');
            let services = work.affected_services;
            if (typeof services === 'string') {
                try {
                    services = JSON.parse(services);
                } catch (e) {
                    services = services.split(',').map(s => s.trim());
                }
            }
            servicesList.textContent = Array.isArray(services) ? services.join(', ') : services;
        }
        
        // Notes techniques
        const notesSection = content.querySelector('.notes-section');
        if (work.work_notes) {
            notesSection.style.display = '';
            content.querySelector('.work-notes-text').textContent = work.work_notes;
        }
        
        // Notes de complétion
        const completionSection = content.querySelector('.completion-section');
        if (work.completion_notes) {
            completionSection.style.display = '';
            content.querySelector('.work-completion-text').textContent = work.completion_notes;
        }
        
        // Modifier le bouton retour
        const backBtn = content.querySelector('.btn-secondary');
        if (backBtn) {
            backBtn.onclick = () => this.openWorksModal();
        }
        
        // Créer le container
        const container = document.createElement('div');
        container.appendChild(content);
        
        // Ouvrir le modal
        this.openModal(work.title, container.innerHTML, {
            size: 'medium'
        });
    },
    
    /**
     * Formater une durée en minutes
     */
    formatDuration(minutes) {
        if (!minutes) return '';
        
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        
        if (hours > 0) {
            return mins > 0 ? `${hours}h${mins}min` : `${hours}h`;
        }
        return `${mins}min`;
    }
};

// Auto-init quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    BrickWorks.init();
});
