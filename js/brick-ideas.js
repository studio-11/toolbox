/**
 * IFEN Toolbox - Brique "Idées & Votes"
 * =====================================
 * Gestion des idées proposées, votes et passage en programmation
 */

const BrickIdeas = {
    
    // État local
    state: {
        ideas: [],
        plannedIdeas: [],
        filters: {
            type: 'all',
            status: 'pending', // 'pending' ou 'planned'
            sortBy: 'votes' // 'votes', 'date', 'status'
        },
        userVotes: [] // IDs des idées votées par l'utilisateur
    },
    
    // Éléments DOM
    elements: {
        container: null,
        ideasList: null,
        plannedList: null,
        form: null
    },
    
    // ==================== INITIALISATION ====================
    
    async init(containerId = 'ideas-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickIdeas: Container not found');
            return;
        }
        
        this.render();
        this.bindEvents();
        await this.loadIdeas();
    },
    
    render() {
        this.elements.container.innerHTML = `
            <div class="brick-ideas">
                <!-- Header -->
                <div class="brick-header">
                    <div class="brick-title-wrapper">
                        <h2 class="brick-title">
                            <i class="fas fa-lightbulb"></i>
                            Idées & Votes
                        </h2>
                        <p class="brick-subtitle">Proposez vos idées et votez pour celles qui vous plaisent</p>
                    </div>
                </div>
                
                <!-- Tabs Navigation -->
                <div class="ideas-tabs">
                    <button class="tab-btn active" data-tab="pending">
                        <i class="fas fa-inbox"></i>
                        Idées en attente
                        <span class="tab-count" id="pending-count">0</span>
                    </button>
                    <button class="tab-btn" data-tab="planned">
                        <i class="fas fa-calendar-alt"></i>
                        En programmation
                        <span class="tab-count" id="planned-count">0</span>
                    </button>
                </div>
                
                <!-- Tab Content: Pending Ideas -->
                <div class="tab-content active" id="tab-pending">
                    <!-- Formulaire de soumission -->
                    <div class="idea-form-container">
                        <h3><i class="fas fa-plus-circle"></i> Proposer une nouvelle idée</h3>
                        <form id="idea-form" class="idea-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="idea-title">Titre de l'idée *</label>
                                    <input type="text" id="idea-title" name="title" required 
                                           placeholder="Ex: Outil de création de quiz interactifs">
                                </div>
                                <div class="form-group">
                                    <label for="idea-type">Type *</label>
                                    <select id="idea-type" name="type" required>
                                        <option value="">Sélectionner...</option>
                                        <option value="course">🧩 Module de cours</option>
                                        <option value="platform">⚙️ Fonctionnalité plateforme</option>
                                        <option value="improvement">✨ Amélioration existante</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="idea-problem">Quel problème cela résout-il ? *</label>
                                <textarea id="idea-problem" name="problem" required rows="3"
                                          placeholder="Décrivez le problème ou le besoin que cette idée adresse..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="idea-details">Détails supplémentaires (optionnel)</label>
                                <textarea id="idea-details" name="details" rows="3"
                                          placeholder="Ajoutez des précisions, exemples ou références..."></textarea>
                            </div>
                            
                            <div class="form-actions">
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-eraser"></i> Effacer
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Soumettre l'idée
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Filtres et Tri -->
                    <div class="ideas-filters">
                        <div class="filter-group">
                            <label>Type</label>
                            <select id="filter-ideas-type" class="filter-select">
                                <option value="all">Tous les types</option>
                                <option value="course">🧩 Module de cours</option>
                                <option value="platform">⚙️ Plateforme</option>
                                <option value="improvement">✨ Amélioration</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label>Trier par</label>
                            <select id="filter-ideas-sort" class="filter-select">
                                <option value="votes">Plus votées</option>
                                <option value="date">Plus récentes</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Liste des idées -->
                    <div class="ideas-list" id="ideas-list">
                        <!-- Généré dynamiquement -->
                    </div>
                </div>
                
                <!-- Tab Content: Planned Ideas -->
                <div class="tab-content" id="tab-planned">
                    <div class="planned-header">
                        <h3><i class="fas fa-tasks"></i> Idées en cours de développement</h3>
                        <p>Ces idées ont été sélectionnées et sont en cours de réalisation</p>
                    </div>
                    
                    <!-- Timeline des idées planifiées -->
                    <div class="planned-timeline" id="planned-list">
                        <!-- Généré dynamiquement -->
                    </div>
                </div>
            </div>
        `;
        
        // Récupérer les éléments
        this.elements.ideasList = this.elements.container.querySelector('#ideas-list');
        this.elements.plannedList = this.elements.container.querySelector('#planned-list');
        this.elements.form = this.elements.container.querySelector('#idea-form');
    },
    
    bindEvents() {
        // Tabs
        this.elements.container.querySelectorAll('[data-tab]').forEach(btn => {
            btn.addEventListener('click', () => {
                this.switchTab(btn.dataset.tab);
            });
        });
        
        // Formulaire
        this.elements.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitIdea();
        });
        
        // Filtres
        this.elements.container.querySelector('#filter-ideas-type')?.addEventListener('change', (e) => {
            this.state.filters.type = e.target.value;
            this.renderIdeas();
        });
        
        this.elements.container.querySelector('#filter-ideas-sort')?.addEventListener('change', (e) => {
            this.state.filters.sortBy = e.target.value;
            this.renderIdeas();
        });
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadIdeas() {
        ToolboxUtils.showLoading(this.elements.ideasList);
        ToolboxUtils.showLoading(this.elements.plannedList);
        
        try {
            // Charger les idées en attente
            const pendingIdeas = await ToolboxUtils.apiCall('ideas', 'GET', null, {
                status: 'pending'
            });
            this.state.ideas = pendingIdeas;
            
            // Charger les idées planifiées
            const plannedIdeas = await ToolboxUtils.apiCall('ideas', 'GET', null, {
                status: 'planned'
            });
            this.state.plannedIdeas = plannedIdeas;
            
            // Charger les votes de l'utilisateur
            try {
                const userVotes = await ToolboxUtils.apiCall('user_votes');
                this.state.userVotes = userVotes.map(v => v.idea_id);
            } catch (e) {
                this.state.userVotes = ToolboxUtils.getLocal('voted_ideas', []);
            }
            
            this.renderIdeas();
            this.renderPlannedIdeas();
            this.updateCounts();
            
        } catch (error) {
            console.error('Error loading ideas:', error);
            ToolboxUtils.showError(this.elements.ideasList);
        }
    },
    
    // ==================== RENDU ====================
    
    renderIdeas() {
        const filtered = this.filterAndSortIdeas();
        
        if (filtered.length === 0) {
            ToolboxUtils.showEmptyState(
                this.elements.ideasList,
                'fa-lightbulb',
                'Aucune idée pour le moment',
                'Soyez le premier à proposer une idée !'
            );
            return;
        }
        
        this.elements.ideasList.innerHTML = filtered.map((idea, index) => 
            this.createIdeaCard(idea, index)
        ).join('');
        
        this.bindIdeaEvents();
    },
    
    filterAndSortIdeas() {
        let filtered = [...this.state.ideas];
        
        // Filtre type
        if (this.state.filters.type !== 'all') {
            filtered = filtered.filter(i => i.type === this.state.filters.type);
        }
        
        // Tri
        if (this.state.filters.sortBy === 'votes') {
            filtered.sort((a, b) => (b.votes_count || 0) - (a.votes_count || 0));
        } else if (this.state.filters.sortBy === 'date') {
            filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
        }
        
        return filtered;
    },
    
    createIdeaCard(idea, index) {
        const hasVoted = this.state.userVotes.includes(idea.id);
        const typeInfo = TOOLBOX_CONFIG.ideaTypes[idea.type] || TOOLBOX_CONFIG.ideaTypes.course;
        const statusInfo = TOOLBOX_CONFIG.ideaStatus[idea.status] || TOOLBOX_CONFIG.ideaStatus.proposed;
        
        return `
            <article class="idea-card" data-idea-id="${idea.id}" 
                     style="animation-delay: ${index * 0.05}s">
                <div class="idea-vote-section">
                    <button class="vote-btn ${hasVoted ? 'voted' : ''}" 
                            data-action="vote" ${hasVoted ? 'disabled' : ''}>
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <span class="vote-count">${idea.votes_count || 0}</span>
                    <span class="vote-label">votes</span>
                </div>
                
                <div class="idea-content">
                    <div class="idea-header">
                        <span class="idea-type">
                            ${typeInfo.emoji} ${typeInfo.label}
                        </span>
                        ${ToolboxUtils.createStatusBadge(idea.status, TOOLBOX_CONFIG.ideaStatus)}
                    </div>
                    
                    <h3 class="idea-title">${ToolboxUtils.escapeHtml(idea.title)}</h3>
                    
                    <p class="idea-problem">${ToolboxUtils.escapeHtml(idea.problem)}</p>
                    
                    ${idea.details ? `
                        <div class="idea-details">
                            <button class="toggle-details" data-action="toggle-details">
                                <i class="fas fa-chevron-down"></i> Voir les détails
                            </button>
                            <div class="details-content hidden">
                                ${ToolboxUtils.escapeHtml(idea.details)}
                            </div>
                        </div>
                    ` : ''}
                    
                    <div class="idea-footer">
                        <span class="idea-author">
                            <i class="fas fa-user"></i> ${idea.user_name || 'Anonyme'}
                        </span>
                        <span class="idea-date">
                            <i class="fas fa-clock"></i> ${ToolboxUtils.formatRelativeDate(idea.created_at)}
                        </span>
                        
                        <div class="idea-actions">
                            <button class="btn btn-sm btn-secondary" data-action="plan" 
                                    title="Passer en programmation">
                                <i class="fas fa-calendar-plus"></i> Programmer
                            </button>
                        </div>
                    </div>
                </div>
            </article>
        `;
    },
    
    renderPlannedIdeas() {
        if (this.state.plannedIdeas.length === 0) {
            ToolboxUtils.showEmptyState(
                this.elements.plannedList,
                'fa-calendar-alt',
                'Aucune idée en programmation',
                'Les idées sélectionnées apparaîtront ici'
            );
            return;
        }
        
        // Grouper par phase
        const byPhase = {};
        this.state.plannedIdeas.forEach(idea => {
            const phase = idea.current_phase || 'analysis';
            if (!byPhase[phase]) byPhase[phase] = [];
            byPhase[phase].push(idea);
        });
        
        this.elements.plannedList.innerHTML = `
            <div class="planned-phases">
                ${Object.entries(TOOLBOX_CONFIG.devPhases).map(([phase, info]) => `
                    <div class="phase-column" data-phase="${phase}">
                        <div class="phase-header">
                            <i class="fas ${info.icon}"></i>
                            <span>${info.label}</span>
                            <span class="phase-count">${(byPhase[phase] || []).length}</span>
                        </div>
                        <div class="phase-items">
                            ${(byPhase[phase] || []).map(idea => this.createPlannedCard(idea)).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
        
        this.bindPlannedEvents();
    },
    
    createPlannedCard(idea) {
        const priorityInfo = TOOLBOX_CONFIG.priorities[idea.priority] || TOOLBOX_CONFIG.priorities.medium;
        const progress = idea.progress_percent || TOOLBOX_CONFIG.devPhases[idea.current_phase]?.progress || 0;
        
        return `
            <div class="planned-card" data-idea-id="${idea.id}">
                <div class="planned-card-header">
                    <span class="planned-title">${ToolboxUtils.escapeHtml(idea.title)}</span>
                    ${ToolboxUtils.createPriorityBadge(idea.priority)}
                </div>
                
                ${ToolboxUtils.createProgressBar(progress)}
                
                <div class="planned-meta">
                    ${idea.planned_start_date ? `
                        <span class="meta-item">
                            <i class="fas fa-play"></i> ${ToolboxUtils.formatDate(idea.planned_start_date)}
                        </span>
                    ` : ''}
                    ${idea.planned_end_date ? `
                        <span class="meta-item">
                            <i class="fas fa-flag-checkered"></i> ${ToolboxUtils.formatDate(idea.planned_end_date)}
                        </span>
                    ` : ''}
                    ${idea.assigned_to ? `
                        <span class="meta-item">
                            <i class="fas fa-user"></i> ${idea.assigned_to}
                        </span>
                    ` : ''}
                </div>
                
                <div class="planned-actions">
                    <button class="btn btn-xs btn-secondary" data-action="view-details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-xs btn-secondary" data-action="edit-planning">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        `;
    },
    
    // ==================== EVENTS ====================
    
    bindIdeaEvents() {
        this.elements.ideasList.querySelectorAll('.idea-card').forEach(card => {
            const ideaId = parseInt(card.dataset.ideaId);
            
            // Vote
            card.querySelector('[data-action="vote"]')?.addEventListener('click', () => {
                this.voteIdea(ideaId);
            });
            
            // Toggle details
            card.querySelector('[data-action="toggle-details"]')?.addEventListener('click', (e) => {
                const btn = e.currentTarget;
                const content = card.querySelector('.details-content');
                content.classList.toggle('hidden');
                btn.innerHTML = content.classList.contains('hidden') 
                    ? '<i class="fas fa-chevron-down"></i> Voir les détails'
                    : '<i class="fas fa-chevron-up"></i> Masquer les détails';
            });
            
            // Programmer
            card.querySelector('[data-action="plan"]')?.addEventListener('click', () => {
                this.showPlanningModal(ideaId);
            });
        });
    },
    
    bindPlannedEvents() {
        this.elements.plannedList.querySelectorAll('.planned-card').forEach(card => {
            const ideaId = parseInt(card.dataset.ideaId);
            
            card.querySelector('[data-action="view-details"]')?.addEventListener('click', () => {
                this.showPlannedDetails(ideaId);
            });
            
            card.querySelector('[data-action="edit-planning"]')?.addEventListener('click', () => {
                this.showEditPlanningModal(ideaId);
            });
        });
    },
    
    switchTab(tab) {
        // Update buttons
        this.elements.container.querySelectorAll('[data-tab]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        
        // Update content
        this.elements.container.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `tab-${tab}`);
        });
        
        this.state.filters.status = tab;
    },
    
    updateCounts() {
        const pendingCount = this.elements.container.querySelector('#pending-count');
        const plannedCount = this.elements.container.querySelector('#planned-count');
        
        if (pendingCount) pendingCount.textContent = this.state.ideas.length;
        if (plannedCount) plannedCount.textContent = this.state.plannedIdeas.length;
    },
    
    // ==================== ACTIONS ====================
    
    async submitIdea() {
        const formData = new FormData(this.elements.form);
        const data = {
            title: formData.get('title'),
            type: formData.get('type'),
            problem: formData.get('problem'),
            details: formData.get('details')
        };
        
        if (!data.title || !data.type || !data.problem) {
            ToolboxUtils.showNotification('Veuillez remplir tous les champs obligatoires', 'warning');
            return;
        }
        
        try {
            await ToolboxUtils.apiCall('idea', 'POST', data);
            
            ToolboxUtils.showNotification('Votre idée a été soumise avec succès !', 'success');
            this.elements.form.reset();
            await this.loadIdeas();
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de la soumission', 'error');
        }
    },
    
    async voteIdea(ideaId) {
        if (this.state.userVotes.includes(ideaId)) {
            ToolboxUtils.showNotification('Vous avez déjà voté pour cette idée', 'info');
            return;
        }
        
        try {
            await ToolboxUtils.apiCall('vote', 'POST', { idea_id: ideaId });
            
            // Mettre à jour localement
            this.state.userVotes.push(ideaId);
            ToolboxUtils.saveLocal('voted_ideas', this.state.userVotes);
            
            // Mettre à jour le compteur
            const idea = this.state.ideas.find(i => i.id === ideaId);
            if (idea) idea.votes_count = (idea.votes_count || 0) + 1;
            
            this.renderIdeas();
            ToolboxUtils.showNotification('Vote enregistré !', 'success');
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors du vote', 'error');
        }
    },
    
    showPlanningModal(ideaId) {
        const idea = this.state.ideas.find(i => i.id === ideaId);
        if (!idea) return;
        
        const today = new Date().toISOString().split('T')[0];
        
        const modal = ToolboxUtils.createModal({
            title: 'Passer en programmation',
            size: 'medium',
            content: `
                <div class="planning-form">
                    <div class="idea-summary">
                        <h4>${ToolboxUtils.escapeHtml(idea.title)}</h4>
                        <p>${ToolboxUtils.escapeHtml(idea.problem)}</p>
                        <span class="votes-badge">
                            <i class="fas fa-arrow-up"></i> ${idea.votes_count || 0} votes
                        </span>
                    </div>
                    
                    <hr>
                    
                    <form id="planning-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="plan-start">Date de début prévue *</label>
                                <input type="date" id="plan-start" name="planned_start_date" 
                                       value="${today}" required>
                            </div>
                            <div class="form-group">
                                <label for="plan-end">Date de fin prévue</label>
                                <input type="date" id="plan-end" name="planned_end_date">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="plan-priority">Priorité *</label>
                                <select id="plan-priority" name="priority" required>
                                    <option value="low">🟢 Basse</option>
                                    <option value="medium" selected>🟡 Moyenne</option>
                                    <option value="high">🟠 Haute</option>
                                    <option value="critical">🔴 Critique</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="plan-phase">Phase initiale</label>
                                <select id="plan-phase" name="current_phase">
                                    ${Object.entries(TOOLBOX_CONFIG.devPhases).map(([key, val]) => 
                                        `<option value="${key}">${val.label}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="plan-assigned">Assigné à</label>
                            <input type="text" id="plan-assigned" name="assigned_to" 
                                   placeholder="Nom du responsable">
                        </div>
                        
                        <div class="form-group">
                            <label for="plan-notes">Notes de développement</label>
                            <textarea id="plan-notes" name="dev_notes" rows="3"
                                      placeholder="Notes techniques, contraintes, etc."></textarea>
                        </div>
                    </form>
                </div>
            `,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Annuler</button>
                <button class="btn btn-primary" data-action="confirm-plan">
                    <i class="fas fa-rocket"></i> Lancer la programmation
                </button>
            `
        });
        
        modal.querySelector('[data-action="confirm-plan"]').addEventListener('click', async () => {
            const form = modal.querySelector('#planning-form');
            const formData = new FormData(form);
            
            const planData = {
                idea_id: ideaId,
                planned_start_date: formData.get('planned_start_date'),
                planned_end_date: formData.get('planned_end_date'),
                priority: formData.get('priority'),
                current_phase: formData.get('current_phase'),
                assigned_to: formData.get('assigned_to'),
                dev_notes: formData.get('dev_notes')
            };
            
            await this.planIdea(planData, modal);
        });
    },
    
    async planIdea(planData, modal) {
        try {
            await ToolboxUtils.apiCall('plan_idea', 'POST', planData);
            
            ToolboxUtils.closeModal(modal);
            ToolboxUtils.showNotification('Idée passée en programmation !', 'success');
            
            // Recharger les données
            await this.loadIdeas();
            
            // Basculer vers l'onglet planifié
            this.switchTab('planned');
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors de la planification', 'error');
        }
    },
    
    showPlannedDetails(ideaId) {
        const idea = this.state.plannedIdeas.find(i => i.id === ideaId);
        if (!idea) return;
        
        const phaseInfo = TOOLBOX_CONFIG.devPhases[idea.current_phase] || TOOLBOX_CONFIG.devPhases.analysis;
        
        ToolboxUtils.createModal({
            title: idea.title,
            size: 'medium',
            content: `
                <div class="planned-details">
                    <div class="detail-section">
                        <h4><i class="fas fa-info-circle"></i> Description</h4>
                        <p>${ToolboxUtils.escapeHtml(idea.problem)}</p>
                        ${idea.details ? `<p class="details-text">${ToolboxUtils.escapeHtml(idea.details)}</p>` : ''}
                    </div>
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-tasks"></i> Avancement</h4>
                        <div class="progress-visual">
                            <div class="phase-indicator active">
                                <i class="fas ${phaseInfo.icon}"></i>
                                <span>${phaseInfo.label}</span>
                            </div>
                            ${ToolboxUtils.createProgressBar(idea.progress_percent || phaseInfo.progress)}
                        </div>
                    </div>
                    
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Priorité</span>
                            ${ToolboxUtils.createPriorityBadge(idea.priority)}
                        </div>
                        <div class="detail-item">
                            <span class="label">Début prévu</span>
                            <span class="value">${ToolboxUtils.formatDate(idea.planned_start_date) || '-'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Fin prévue</span>
                            <span class="value">${ToolboxUtils.formatDate(idea.planned_end_date) || '-'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Assigné à</span>
                            <span class="value">${idea.assigned_to || '-'}</span>
                        </div>
                    </div>
                    
                    ${idea.dev_notes ? `
                        <div class="detail-section">
                            <h4><i class="fas fa-sticky-note"></i> Notes de développement</h4>
                            <div class="notes-box">${ToolboxUtils.escapeHtml(idea.dev_notes)}</div>
                        </div>
                    ` : ''}
                    
                    <div class="detail-section">
                        <h4><i class="fas fa-history"></i> Historique</h4>
                        <div class="history-item">
                            <span class="date">${ToolboxUtils.formatDate(idea.created_at)}</span>
                            <span class="event">Idée proposée par ${idea.user_name || 'Anonyme'}</span>
                        </div>
                        <div class="history-item">
                            <span class="date">${ToolboxUtils.formatDate(idea.planned_at)}</span>
                            <span class="event">Passage en programmation</span>
                        </div>
                    </div>
                </div>
            `
        });
    },
    
    showEditPlanningModal(ideaId) {
        const idea = this.state.plannedIdeas.find(i => i.id === ideaId);
        if (!idea) return;
        
        const modal = ToolboxUtils.createModal({
            title: 'Modifier la planification',
            size: 'medium',
            content: `
                <form id="edit-planning-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-phase">Phase actuelle</label>
                            <select id="edit-phase" name="current_phase">
                                ${Object.entries(TOOLBOX_CONFIG.devPhases).map(([key, val]) => 
                                    `<option value="${key}" ${idea.current_phase === key ? 'selected' : ''}>
                                        ${val.label}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-progress">Progression (%)</label>
                            <input type="number" id="edit-progress" name="progress_percent" 
                                   value="${idea.progress_percent || 0}" min="0" max="100">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-start">Date de début</label>
                            <input type="date" id="edit-start" name="planned_start_date" 
                                   value="${idea.planned_start_date || ''}">
                        </div>
                        <div class="form-group">
                            <label for="edit-end">Date de fin</label>
                            <input type="date" id="edit-end" name="planned_end_date"
                                   value="${idea.planned_end_date || ''}">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-priority">Priorité</label>
                            <select id="edit-priority" name="priority">
                                ${Object.entries(TOOLBOX_CONFIG.priorities).map(([key, val]) => 
                                    `<option value="${key}" ${idea.priority === key ? 'selected' : ''}>
                                        ${val.label}
                                    </option>`
                                ).join('')}
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit-assigned">Assigné à</label>
                            <input type="text" id="edit-assigned" name="assigned_to"
                                   value="${idea.assigned_to || ''}">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-notes">Notes</label>
                        <textarea id="edit-notes" name="dev_notes" rows="3">${idea.dev_notes || ''}</textarea>
                    </div>
                </form>
            `,
            footer: `
                <button class="btn btn-danger" data-action="unplan">
                    <i class="fas fa-undo"></i> Retirer de la programmation
                </button>
                <button class="btn btn-secondary" data-modal-close>Annuler</button>
                <button class="btn btn-primary" data-action="save">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            `
        });
        
        // Save
        modal.querySelector('[data-action="save"]').addEventListener('click', async () => {
            const form = modal.querySelector('#edit-planning-form');
            const formData = new FormData(form);
            
            const updateData = {
                idea_id: ideaId,
                current_phase: formData.get('current_phase'),
                progress_percent: parseInt(formData.get('progress_percent')),
                planned_start_date: formData.get('planned_start_date'),
                planned_end_date: formData.get('planned_end_date'),
                priority: formData.get('priority'),
                assigned_to: formData.get('assigned_to'),
                dev_notes: formData.get('dev_notes')
            };
            
            try {
                await ToolboxUtils.apiCall('update_planning', 'PUT', updateData);
                ToolboxUtils.closeModal(modal);
                ToolboxUtils.showNotification('Planification mise à jour', 'success');
                await this.loadIdeas();
            } catch (error) {
                ToolboxUtils.showNotification('Erreur lors de la mise à jour', 'error');
            }
        });
        
        // Unplan
        modal.querySelector('[data-action="unplan"]').addEventListener('click', async () => {
            const confirmed = await ToolboxUtils.confirmAction(
                'Voulez-vous vraiment retirer cette idée de la programmation ? Elle retournera dans la liste des idées en attente.',
                'Confirmer le retrait'
            );
            
            if (confirmed) {
                try {
                    await ToolboxUtils.apiCall('unplan_idea', 'DELETE', { idea_id: ideaId });
                    ToolboxUtils.closeModal(modal);
                    ToolboxUtils.showNotification('Idée retirée de la programmation', 'success');
                    await this.loadIdeas();
                    this.switchTab('pending');
                } catch (error) {
                    ToolboxUtils.showNotification('Erreur lors du retrait', 'error');
                }
            }
        });
    }
};

// Exporter
window.BrickIdeas = BrickIdeas;
