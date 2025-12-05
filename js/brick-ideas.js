/**
 * IFEN Toolbox - Brique "Idées & Votes"
 * =====================================
 * Version mise à jour avec nouveaux types d'idées
 * et bouton Programmer visible uniquement pour admin
 */

const BrickIdeas = {
    
    // État local
    state: {
        ideas: [],
        plannedIdeas: [],
        filters: {
            type: '',
            sortBy: 'votes'
        },
        userVotes: [],
        isAdmin: false
    },
    
    // Éléments DOM
    elements: {
        container: null,
        ideasList: null,
        plannedContainer: null
    },
    
    // ==================== INITIALISATION ====================
    
    async init(containerId = 'ideas-section') {
        this.elements.container = document.getElementById(containerId);
        if (!this.elements.container) {
            console.error('BrickIdeas: Container not found');
            return;
        }
        
        this.elements.ideasList = this.elements.container.querySelector('#ideas-pending-list');
        this.elements.plannedContainer = this.elements.container.querySelector('#kanban-container');
        
        // Vérifier si admin (depuis la config globale)
        this.state.isAdmin = window.TOOLBOX_CONFIG?.user?.is_admin || false;
        
        this.bindEvents();
        await this.loadIdeas();
    },
    
    bindEvents() {
        // Bouton nouvelle idée
        const btnNewIdea = this.elements.container.querySelector('#btn-new-idea');
        if (btnNewIdea) {
            btnNewIdea.addEventListener('click', () => this.showNewIdeaModal());
        }
        
        // Tabs
        this.elements.container.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => this.switchTab(btn.dataset.tab));
        });
        
        // Filtres
        const filterType = this.elements.container.querySelector('#ideas-filter-type');
        if (filterType) {
            filterType.addEventListener('change', () => {
                this.state.filters.type = filterType.value;
                this.renderIdeas();
            });
        }
        
        const filterSort = this.elements.container.querySelector('#ideas-filter-sort');
        if (filterSort) {
            filterSort.addEventListener('change', () => {
                this.state.filters.sortBy = filterSort.value;
                this.renderIdeas();
            });
        }
        
        // Events délégués pour les cartes
        if (this.elements.ideasList) {
            this.elements.ideasList.addEventListener('click', (e) => {
                const card = e.target.closest('.idea-card');
                if (!card) return;
                
                const ideaId = parseInt(card.dataset.ideaId);
                
                if (e.target.closest('.vote-btn')) {
                    e.stopPropagation();
                    this.voteIdea(ideaId);
                    return;
                }
                
                if (e.target.closest('.idea-details-btn')) {
                    e.stopPropagation();
                    this.showIdeaDetails(ideaId);
                    return;
                }
                
                if (e.target.closest('.idea-plan-btn')) {
                    e.stopPropagation();
                    this.showPlanningModal(ideaId);
                    return;
                }
            });
        }
    },
    
    // ==================== CHARGEMENT ====================
    
    async loadIdeas() {
        if (this.elements.ideasList) {
            ToolboxUtils.showLoading(this.elements.ideasList);
        }
        
        try {
            // Charger les idées en attente
            const pendingIdeas = await ToolboxUtils.apiCall('ideas', 'GET', null, { status: 'pending' });
            this.state.ideas = pendingIdeas || [];
            
            // Charger les idées planifiées
            const plannedIdeas = await ToolboxUtils.apiCall('ideas', 'GET', null, { status: 'planned' });
            this.state.plannedIdeas = plannedIdeas || [];
            
            // Charger les votes de l'utilisateur
            try {
                const userVotes = await ToolboxUtils.apiCall('user_votes');
                this.state.userVotes = Array.isArray(userVotes) ? userVotes : [];
            } catch (e) {
                this.state.userVotes = ToolboxUtils.getLocal('voted_ideas', []);
            }
            
            this.renderIdeas();
            this.renderPlannedIdeas();
            this.updateCounts();
            
        } catch (error) {
            console.error('Error loading ideas:', error);
            if (this.elements.ideasList) {
                ToolboxUtils.showError(this.elements.ideasList);
            }
        }
    },
    
    // ==================== RENDU ====================
    
    renderIdeas() {
        if (!this.elements.ideasList) return;
        
        const filtered = this.filterAndSortIdeas();
        
        if (filtered.length === 0) {
            const emptyState = this.elements.container.querySelector('#ideas-pending-empty');
            if (emptyState) emptyState.style.display = 'flex';
            this.elements.ideasList.innerHTML = '';
            return;
        }
        
        const emptyState = this.elements.container.querySelector('#ideas-pending-empty');
        if (emptyState) emptyState.style.display = 'none';
        
        this.elements.ideasList.innerHTML = filtered.map((idea, index) => 
            this.createIdeaCard(idea, index)
        ).join('');
    },
    
    filterAndSortIdeas() {
        let filtered = [...this.state.ideas];
        
        // Filtre par type
        if (this.state.filters.type) {
            filtered = filtered.filter(i => i.type === this.state.filters.type);
        }
        
        // Tri
        switch (this.state.filters.sortBy) {
            case 'votes':
                filtered.sort((a, b) => (b.votes_count || 0) - (a.votes_count || 0));
                break;
            case 'recent':
                filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                break;
            case 'oldest':
                filtered.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
                break;
        }
        
        return filtered;
    },
    
    createIdeaCard(idea, index) {
        const hasVoted = this.state.userVotes.includes(idea.id);
        const typeInfo = TOOLBOX_CONFIG.ideaTypes[idea.type] || TOOLBOX_CONFIG.ideaTypes.other;
        
        return `
            <article class="idea-card" data-idea-id="${idea.id}" style="animation-delay: ${index * 0.05}s">
                <div class="idea-card-vote">
                    <button class="vote-btn ${hasVoted ? 'voted' : ''}" ${hasVoted ? 'disabled' : ''} title="${hasVoted ? 'Vous avez voté' : 'Voter pour cette idée'}">
                        <i class="fas fa-chevron-up"></i>
                    </button>
                    <span class="vote-count">${idea.votes_count || 0}</span>
                    <span class="vote-label">votes</span>
                </div>
                
                <div class="idea-card-content">
                    <div class="idea-header">
                        <span class="idea-type-badge">
                            ${typeInfo.emoji} ${typeInfo.label}
                        </span>
                        <span class="idea-date">${ToolboxUtils.formatRelativeDate(idea.created_at)}</span>
                    </div>
                    
                    <h3 class="idea-title">${ToolboxUtils.escapeHtml(idea.title)}</h3>
                    <p class="idea-problem">${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(idea.problem, 150))}</p>
                    
                    <div class="idea-footer">
                        <span class="idea-author">
                            <i class="fas fa-user"></i>
                            <span>${idea.user_name || 'Anonyme'}</span>
                        </span>
                        <div class="idea-actions">
                            <button class="btn btn-sm idea-details-btn">
                                <i class="fas fa-info-circle"></i> Détails
                            </button>
                            ${this.state.isAdmin ? `
                                <button class="btn btn-sm btn-primary idea-plan-btn admin-only-btn" title="Programmer cette idée">
                                    <i class="fas fa-calendar-plus"></i> Programmer
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </article>
        `;
    },
    
    renderPlannedIdeas() {
        if (!this.elements.plannedContainer) return;
        
        // Grouper par phase
        const byPhase = {};
        this.state.plannedIdeas.forEach(idea => {
            const phase = idea.current_phase || 'analysis';
            if (!byPhase[phase]) byPhase[phase] = [];
            byPhase[phase].push(idea);
        });
        
        // Mettre à jour les compteurs et les cartes de chaque colonne
        Object.keys(TOOLBOX_CONFIG.devPhases).forEach(phase => {
            const column = this.elements.plannedContainer.querySelector(`[data-phase="${phase}"]`);
            if (!column) return;
            
            const countEl = column.querySelector('.phase-count');
            const cardsEl = column.querySelector('.kanban-cards');
            
            const ideas = byPhase[phase] || [];
            
            if (countEl) countEl.textContent = ideas.length;
            
            if (cardsEl) {
                cardsEl.innerHTML = ideas.map(idea => this.createKanbanCard(idea)).join('');
            }
        });
        
        // Gérer l'état vide
        const emptyState = this.elements.container.querySelector('#ideas-planned-empty');
        if (emptyState) {
            emptyState.style.display = this.state.plannedIdeas.length === 0 ? 'flex' : 'none';
        }
    },
    
    createKanbanCard(idea) {
        const priorityInfo = TOOLBOX_CONFIG.priorities[idea.priority] || TOOLBOX_CONFIG.priorities.medium;
        const progress = idea.progress_percent || TOOLBOX_CONFIG.devPhases[idea.current_phase]?.progress || 0;
        
        return `
            <div class="kanban-card" data-idea-id="${idea.id}">
                <div class="kanban-card-header">
                    <span class="priority-badge" style="background: ${priorityInfo.color}20; color: ${priorityInfo.color}">
                        <i class="fas ${priorityInfo.icon}"></i> ${priorityInfo.label}
                    </span>
                </div>
                
                <h4 class="kanban-card-title">${ToolboxUtils.escapeHtml(idea.title)}</h4>
                
                <div class="kanban-card-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: ${progress}%"></div>
                    </div>
                    <span class="progress-text">${progress}%</span>
                </div>
                
                <div class="kanban-card-meta">
                    ${idea.assigned_to ? `
                        <span class="assigned-to" title="Assigné à">
                            <i class="fas fa-user"></i>
                            <span>${idea.assigned_to}</span>
                        </span>
                    ` : ''}
                    ${idea.planned_end_date ? `
                        <span class="due-date" title="Date prévue">
                            <i class="fas fa-calendar"></i>
                            <span>${ToolboxUtils.formatDate(idea.planned_end_date)}</span>
                        </span>
                    ` : ''}
                </div>
            </div>
        `;
    },
    
    updateCounts() {
        const pendingCount = this.elements.container.querySelector('#count-pending');
        const plannedCount = this.elements.container.querySelector('#count-planned');
        
        if (pendingCount) pendingCount.textContent = this.state.ideas.length;
        if (plannedCount) plannedCount.textContent = this.state.plannedIdeas.length;
    },
    
    switchTab(tab) {
        this.elements.container.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.tab === tab);
        });
        
        this.elements.container.querySelectorAll('.tab-content').forEach(content => {
            content.classList.toggle('active', content.id === `tab-${tab}`);
        });
    },
    
    // ==================== ACTIONS ====================
    
    showNewIdeaModal() {
        const content = `
            <div class="new-idea-form">
                <form id="form-new-idea">
                    <div class="form-group">
                        <label for="idea-title-input">Titre de l'idée *</label>
                        <input type="text" id="idea-title-input" name="title" class="form-input" 
                               placeholder="Ex: Outil de création de quiz interactifs" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="idea-type-input">Type d'idée *</label>
                        <select id="idea-type-input" name="type" class="form-input" required>
                            <option value="">-- Sélectionner un type --</option>
                            <option value="course_activity">📚 Activité de cours</option>
                            <option value="course_resource">📄 Ressource de cours</option>
                            <option value="platform_feature">⚙️ Fonctionnalité plateforme</option>
                            <option value="other">📌 Autres</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="idea-problem-input">Quel problème cela résout-il ? *</label>
                        <textarea id="idea-problem-input" name="problem" class="form-textarea" rows="3" 
                                  placeholder="Décrivez le besoin ou le problème que cette idée adresse..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="idea-details-input">Détails supplémentaires (optionnel)</label>
                        <textarea id="idea-details-input" name="details" class="form-textarea" rows="3"
                                  placeholder="Ajoutez des précisions, exemples, liens utiles..."></textarea>
                    </div>
                </form>
            </div>
        `;
        
        const modal = ToolboxUtils.createModal({
            title: 'Proposer une nouvelle idée',
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Annuler</button>
                <button class="btn btn-primary" id="btn-submit-idea">
                    <i class="fas fa-paper-plane"></i> Soumettre l'idée
                </button>
            `
        });
        
        modal.querySelector('#btn-submit-idea').addEventListener('click', async () => {
            await this.submitIdea(modal);
        });
    },
    
    async submitIdea(modal) {
        const form = modal.querySelector('#form-new-idea');
        const formData = new FormData(form);
        
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
            
            ToolboxUtils.closeModal(modal);
            ToolboxUtils.showNotification('Votre idée a été soumise avec succès !', 'success');
            
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
            
            this.state.userVotes.push(ideaId);
            ToolboxUtils.saveLocal('voted_ideas', this.state.userVotes);
            
            const idea = this.state.ideas.find(i => i.id === ideaId);
            if (idea) idea.votes_count = (idea.votes_count || 0) + 1;
            
            this.renderIdeas();
            ToolboxUtils.showNotification('Vote enregistré !', 'success');
            
        } catch (error) {
            ToolboxUtils.showNotification('Erreur lors du vote', 'error');
        }
    },
    
    showIdeaDetails(ideaId) {
        const idea = this.state.ideas.find(i => i.id === ideaId);
        if (!idea) return;
        
        const typeInfo = TOOLBOX_CONFIG.ideaTypes[idea.type] || TOOLBOX_CONFIG.ideaTypes.other;
        
        const content = `
            <div class="idea-details">
                <div class="idea-meta-header">
                    <span class="idea-type-badge large">${typeInfo.emoji} ${typeInfo.label}</span>
                    <span class="votes-badge"><i class="fas fa-arrow-up"></i> ${idea.votes_count || 0} votes</span>
                </div>
                
                <div class="detail-section">
                    <h4><i class="fas fa-question-circle"></i> Problème à résoudre</h4>
                    <p>${ToolboxUtils.escapeHtml(idea.problem)}</p>
                </div>
                
                ${idea.details ? `
                    <div class="detail-section">
                        <h4><i class="fas fa-align-left"></i> Détails supplémentaires</h4>
                        <p>${ToolboxUtils.escapeHtml(idea.details)}</p>
                    </div>
                ` : ''}
                
                <div class="detail-section">
                    <h4><i class="fas fa-info-circle"></i> Informations</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <span class="label">Proposée par</span>
                            <span class="value">${idea.user_name || 'Anonyme'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="label">Date</span>
                            <span class="value">${ToolboxUtils.formatDate(idea.created_at)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        ToolboxUtils.createModal({
            title: idea.title,
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Fermer</button>
                ${this.state.isAdmin ? `
                    <button class="btn btn-primary" id="btn-plan-from-details" data-idea-id="${ideaId}">
                        <i class="fas fa-calendar-plus"></i> Programmer
                    </button>
                ` : ''}
            `
        });
        
        const planBtn = document.getElementById('btn-plan-from-details');
        if (planBtn) {
            planBtn.addEventListener('click', () => {
                document.querySelector('.toolbox-modal-overlay')?.remove();
                this.showPlanningModal(ideaId);
            });
        }
    },
    
    showPlanningModal(ideaId) {
        if (!this.state.isAdmin) {
            ToolboxUtils.showNotification('Action réservée aux administrateurs', 'warning');
            return;
        }
        
        const idea = this.state.ideas.find(i => i.id === ideaId);
        if (!idea) return;
        
        const today = new Date().toISOString().split('T')[0];
        
        const content = `
            <div class="planning-form">
                <div class="idea-summary">
                    <h4>${ToolboxUtils.escapeHtml(idea.title)}</h4>
                    <p>${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(idea.problem, 100))}</p>
                    <span class="votes-badge"><i class="fas fa-arrow-up"></i> ${idea.votes_count || 0} votes</span>
                </div>
                
                <hr>
                
                <form id="planning-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="plan-start">Date de début prévue *</label>
                            <input type="date" id="plan-start" name="planned_start_date" value="${today}" required>
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
                        <input type="text" id="plan-assigned" name="assigned_to" placeholder="Nom du responsable">
                    </div>
                    
                    <div class="form-group">
                        <label for="plan-notes">Notes de développement</label>
                        <textarea id="plan-notes" name="dev_notes" rows="3" placeholder="Notes techniques, contraintes..."></textarea>
                    </div>
                </form>
            </div>
        `;
        
        const modal = ToolboxUtils.createModal({
            title: 'Programmer cette idée',
            size: 'medium',
            content: content,
            footer: `
                <button class="btn btn-secondary" data-modal-close>Annuler</button>
                <button class="btn btn-primary" id="btn-confirm-plan">
                    <i class="fas fa-rocket"></i> Lancer la programmation
                </button>
            `
        });
        
        modal.querySelector('#btn-confirm-plan').addEventListener('click', async () => {
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
            
            try {
                await ToolboxUtils.apiCall('plan_idea', 'POST', planData);
                
                ToolboxUtils.closeModal(modal);
                ToolboxUtils.showNotification('Idée passée en programmation !', 'success');
                
                await this.loadIdeas();
                this.switchTab('planned');
                
            } catch (error) {
                ToolboxUtils.showNotification('Erreur lors de la planification', 'error');
            }
        });
    },
    
    // Méthode pour ouvrir le panel programmer (appelée depuis le bouton admin)
    openProgrammerPanel() {
        if (!this.state.isAdmin) {
            ToolboxUtils.showNotification('Action réservée aux administrateurs', 'warning');
            return;
        }
        
        // Afficher une liste des idées les plus votées pour en sélectionner une
        const topIdeas = [...this.state.ideas].sort((a, b) => (b.votes_count || 0) - (a.votes_count || 0)).slice(0, 10);
        
        const content = `
            <div class="programmer-panel">
                <p>Sélectionnez une idée à programmer :</p>
                <div class="ideas-selection-list">
                    ${topIdeas.length === 0 ? `
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Aucune idée disponible</p>
                        </div>
                    ` : topIdeas.map(idea => `
                        <div class="idea-selection-item" data-idea-id="${idea.id}">
                            <div class="idea-votes">
                                <i class="fas fa-arrow-up"></i>
                                <span>${idea.votes_count || 0}</span>
                            </div>
                            <div class="idea-info">
                                <strong>${ToolboxUtils.escapeHtml(idea.title)}</strong>
                                <small>${ToolboxUtils.escapeHtml(ToolboxUtils.truncate(idea.problem, 80))}</small>
                            </div>
                            <button class="btn btn-sm btn-primary select-idea-btn">
                                <i class="fas fa-check"></i> Sélectionner
                            </button>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
        
        const modal = ToolboxUtils.createModal({
            title: 'Programmer une idée',
            size: 'medium',
            content: content
        });
        
        modal.querySelectorAll('.select-idea-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const ideaId = parseInt(btn.closest('.idea-selection-item').dataset.ideaId);
                ToolboxUtils.closeModal(modal);
                this.showPlanningModal(ideaId);
            });
        });
    }
};

// Exporter
window.BrickIdeas = BrickIdeas;
