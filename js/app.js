// ==================== CONFIGURATION API ====================

const API_BASE_URL = '/ifen_html/toolbox/api.php';

// Helper pour les appels API
async function apiCall(action, method = 'GET', data = null) {
    const url = `${API_BASE_URL}?action=${action}`;
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin' // Pour les cookies de session Moodle
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!result.success) {
            throw new Error(result.error || 'Erreur inconnue');
        }
        
        return result.data;
    } catch (error) {
        console.error('Erreur API:', error);
        showNotification('Erreur de connexion à l\'API', 'error');
        throw error;
    }
}

// ==================== CHARGEMENT DES OUTILS ====================

let allTools = [];
let allIdeas = [];
let currentFilters = {
    type: 'all',
    goal: 'all',
    search: ''
};

const toolsListEl = document.getElementById('tools-list');
const filterTypeEl = document.getElementById('filter-type');
const filterGoalEl = document.getElementById('filter-goal');
const filterSearchEl = document.getElementById('filter-search');
const heroSearchEl = document.getElementById('hero-search');

async function loadTools() {
    try {
        showLoading(toolsListEl);
        
        // Construire les paramètres de filtre pour l'API
        const params = new URLSearchParams();
        if (currentFilters.type !== 'all') params.append('type', currentFilters.type);
        if (currentFilters.goal !== 'all') params.append('category', currentFilters.goal);
        if (currentFilters.search) params.append('search', currentFilters.search);
        
        const url = `${API_BASE_URL}?action=tools&${params.toString()}`;
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success) {
            allTools = result.data;
            renderTools(allTools);
        }
    } catch (error) {
        console.error('Erreur chargement outils:', error);
        toolsListEl.innerHTML = '<p class="error">Impossible de charger les outils</p>';
    }
}

function renderTools(tools) {
    toolsListEl.innerHTML = '';
    
    if (tools.length === 0) {
        toolsListEl.innerHTML = '<p style="text-align: center; color: var(--gray); padding: 40px;">Aucun outil ne correspond aux filtres sélectionnés.</p>';
        return;
    }
    
    tools.forEach((tool, index) => {
        const card = createToolCard(tool);
        card.style.animationDelay = `${index * 0.1}s`;
        toolsListEl.appendChild(card);
    });
}

function createToolCard(tool) {
    const card = document.createElement('article');
    card.className = 'tool-card';
    
    // Header avec gradient
    const header = document.createElement('div');
    header.className = 'tool-card-header';
    header.style.background = tool.gradient || 'var(--gradient-1)';
    
    const icon = document.createElement('i');
    icon.className = `fas ${tool.icon || 'fa-tools'}`;
    header.appendChild(icon);
    
    // Badge
    if (tool.status === 'new' || tool.is_hot || tool.status === 'beta') {
        const badge = document.createElement('div');
        badge.className = `badge badge-${tool.status === 'new' ? 'new' : tool.status === 'beta' ? 'beta' : 'hot'}`;
        badge.textContent = tool.status === 'new' ? 'Nouveau' : tool.status === 'beta' ? 'Beta' : '🔥 Hot';
        header.appendChild(badge);
    }
    
    card.appendChild(header);
    
    // Content
    const content = document.createElement('div');
    content.className = 'tool-card-content';
    
    // Title
    const title = document.createElement('h3');
    title.className = 'tool-card-title';
    title.textContent = tool.name;
    content.appendChild(title);
    
    // Meta tags
    const meta = document.createElement('div');
    meta.className = 'tool-card-meta';
    
    const typeLabels = {
        course: 'Outil de cours',
        platform: 'Fonctionnalité plateforme'
    };
    
    const typeTag = document.createElement('span');
    typeTag.className = 'meta-tag';
    typeTag.textContent = typeLabels[tool.type] || tool.type;
    meta.appendChild(typeTag);
    
    if (tool.category_name) {
        const catTag = document.createElement('span');
        catTag.className = 'meta-tag';
        catTag.textContent = tool.category_name;
        meta.appendChild(catTag);
    }
    
    if (tool.time_to_use) {
        const timeTag = document.createElement('span');
        timeTag.className = 'meta-tag';
        timeTag.innerHTML = `⏱ ${tool.time_to_use}`;
        meta.appendChild(timeTag);
    }
    
    content.appendChild(meta);
    
    // Description
    const desc = document.createElement('p');
    desc.className = 'tool-card-description';
    desc.textContent = tool.short_description;
    content.appendChild(desc);
    
    // Features
    if (tool.features && tool.features.length > 0) {
        const ul = document.createElement('ul');
        ul.className = 'tool-card-bullets';
        tool.features.forEach(feature => {
            const li = document.createElement('li');
            li.textContent = feature.feature_text;
            ul.appendChild(li);
        });
        content.appendChild(ul);
    }
    
    // Footer avec boutons
    const footer = document.createElement('div');
    footer.className = 'tool-card-footer';
    
    // Bouton favori
    const btnFav = document.createElement('button');
    btnFav.className = `btn ${tool.is_favorited ? 'btn-primary' : 'btn-secondary'}`;
    btnFav.innerHTML = `<i class="fas fa-${tool.is_favorited ? 'heart' : 'heart'}"></i>`;
    btnFav.onclick = (e) => {
        e.stopPropagation();
        toggleFavorite(tool.id, !tool.is_favorited);
    };
    footer.appendChild(btnFav);
    
    // Bouton détails
    const btnDetails = document.createElement('button');
    btnDetails.className = 'btn btn-secondary';
    btnDetails.innerHTML = '<i class="fas fa-info-circle"></i> Détails';
    btnDetails.onclick = () => openToolModal(tool.id);
    footer.appendChild(btnDetails);
    
    // Bouton tutoriel
    const btnTutorial = document.createElement('button');
    btnTutorial.className = 'btn btn-primary';
    btnTutorial.innerHTML = '<i class="fas fa-book"></i> Tutoriel';
    btnTutorial.onclick = () => openTutorial(tool.id);
    footer.appendChild(btnTutorial);
    
    content.appendChild(footer);
    card.appendChild(content);
    
    // Click sur la carte = détails
    card.onclick = () => openToolModal(tool.id);
    
    return card;
}

// ==================== MODAL DÉTAILS OUTIL ====================

async function openToolModal(toolId) {
    try {
        showNotification('Chargement...', 'info');
        
        const tool = await apiCall(`tool&id=${toolId}`);
        
        // Tracker la vue
        await apiCall('track', 'POST', {
            tool_id: toolId,
            action_type: 'view'
        });
        
        // Créer et afficher le modal
        const modal = createToolModal(tool);
        document.body.appendChild(modal);
        
        // Animation d'ouverture
        setTimeout(() => modal.classList.add('open'), 10);
        
    } catch (error) {
        showNotification('Erreur lors du chargement des détails', 'error');
    }
}

function createToolModal(tool) {
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    modal.onclick = (e) => {
        if (e.target === modal) closeModal(modal);
    };
    
    const content = document.createElement('div');
    content.className = 'modal-content';
    
    content.innerHTML = `
        <div class="modal-header">
            <h2>${tool.name}</h2>
            <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="modal-body">
            ${tool.screenshot_url ? `<img src="${tool.screenshot_url}" alt="${tool.name}">` : ''}
            
            ${tool.video_url ? `
                <div class="video-container">
                    <iframe src="${tool.video_url}" frameborder="0" allowfullscreen></iframe>
                </div>
            ` : ''}
            
            <div class="tool-description">
                <h3>Description</h3>
                <p>${tool.long_description || tool.short_description}</p>
            </div>
            
            ${tool.tutorial_text ? `
                <div class="tool-tutorial">
                    <h3>Tutoriel</h3>
                    <div>${tool.tutorial_text}</div>
                </div>
            ` : ''}
            
            ${tool.code_snippet ? `
                <div class="tool-code">
                    <h3>Code à copier</h3>
                    <pre><code>${escapeHtml(tool.code_snippet)}</code></pre>
                    <button class="btn btn-primary" onclick="copyToClipboard(this.previousElementSibling.textContent)">
                        <i class="fas fa-copy"></i> Copier
                    </button>
                </div>
            ` : ''}
            
            ${tool.comments && tool.comments.length > 0 ? `
                <div class="tool-comments">
                    <h3>Commentaires (${tool.comments.length})</h3>
                    ${tool.comments.map(c => `
                        <div class="comment">
                            <div class="comment-header">
                                <strong>${c.firstname} ${c.lastname}</strong>
                                ${c.rating ? `<span class="rating">${'⭐'.repeat(c.rating)}</span>` : ''}
                            </div>
                            <p>${c.comment}</p>
                        </div>
                    `).join('')}
                </div>
            ` : ''}
            
            <div class="comment-form">
                <h3>Laisser un commentaire</h3>
                <textarea id="new-comment" rows="4" placeholder="Votre commentaire..."></textarea>
                <div class="rating-input">
                    <label>Note :</label>
                    ${[1,2,3,4,5].map(i => `
                        <span class="star" data-rating="${i}" onclick="selectRating(${i})">⭐</span>
                    `).join('')}
                </div>
                <button class="btn btn-primary" onclick="submitComment(${tool.id})">
                    <i class="fas fa-paper-plane"></i> Envoyer
                </button>
            </div>
        </div>
    `;
    
    modal.appendChild(content);
    return modal;
}

// ==================== FAVORIS ====================

async function toggleFavorite(toolId, shouldAdd) {
    try {
        const method = shouldAdd ? 'POST' : 'DELETE';
        await apiCall('favorite', method, { tool_id: toolId });
        
        showNotification(
            shouldAdd ? 'Ajouté aux favoris !' : 'Retiré des favoris',
            'success'
        );
        
        // Recharger les outils pour mettre à jour l'UI
        await loadTools();
        
    } catch (error) {
        console.error('Erreur favoris:', error);
    }
}

// ==================== IDÉES ====================

const ideasListEl = document.getElementById('ideas-list');
const ideaFormEl = document.getElementById('idea-form');

async function loadIdeas() {
    try {
        allIdeas = await apiCall('ideas');
        renderIdeas(allIdeas);
    } catch (error) {
        console.error('Erreur chargement idées:', error);
    }
}

function renderIdeas(ideas) {
    ideasListEl.innerHTML = '';
    
    ideas.forEach(idea => {
        const item = createIdeaItem(idea);
        ideasListEl.appendChild(item);
    });
}

function createIdeaItem(idea) {
    const item = document.createElement('div');
    item.className = 'idea-item';
    
    // Votes column
    const votesCol = document.createElement('div');
    votesCol.className = 'idea-votes';
    
    const voteBtn = document.createElement('button');
    voteBtn.className = 'vote-btn';
    voteBtn.innerHTML = '<span>👍</span>';
    voteBtn.disabled = idea.has_voted;
    
    voteBtn.onclick = async () => {
        try {
            await apiCall('vote', 'POST', { idea_id: idea.id });
            showNotification('Vote enregistré !', 'success');
            await loadIdeas();
        } catch (error) {
            showNotification(error.message, 'error');
        }
    };
    
    const count = document.createElement('div');
    count.className = 'vote-count';
    count.textContent = idea.votes_count || 0;
    
    votesCol.appendChild(voteBtn);
    votesCol.appendChild(count);
    
    // Content
    const content = document.createElement('div');
    content.className = 'idea-content';
    
    content.innerHTML = `
        <h4>${idea.title}</h4>
        <span class="idea-type-badge">${getIdeaTypeLabel(idea.type)}</span>
        <p class="idea-description"><strong>Problème :</strong> ${idea.problem}</p>
        ${idea.details ? `<p class="idea-description"><strong>Détails :</strong> ${idea.details}</p>` : ''}
        ${idea.status !== 'proposed' ? `<span class="status-badge status-${idea.status}">${getStatusLabel(idea.status)}</span>` : ''}
    `;
    
    item.appendChild(votesCol);
    item.appendChild(content);
    
    return item;
}

// Formulaire nouvelle idée
ideaFormEl.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const data = {
        title: document.getElementById('idea-title').value,
        type: document.getElementById('idea-type').value,
        problem: document.getElementById('idea-problem').value,
        details: document.getElementById('idea-details').value
    };
    
    try {
        await apiCall('ideas', 'POST', data);
        showNotification('Idée envoyée avec succès !', 'success');
        ideaFormEl.reset();
        await loadIdeas();
        await updateStats();
    } catch (error) {
        showNotification('Erreur : ' + error.message, 'error');
    }
});

// ==================== STATS ====================

async function updateStats() {
    try {
        const stats = await apiCall('stats');
        
        document.getElementById('stat-total-tools').textContent = stats.total_tools;
        document.getElementById('stat-hot-tools').textContent = stats.hot_tools;
        document.getElementById('stat-ideas').textContent = stats.pending_ideas;
        
    } catch (error) {
        console.error('Erreur stats:', error);
    }
}

// ==================== HELPERS ====================

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.add('show'), 10);
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

function showLoading(element) {
    element.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Chargement...</p>
        </div>
    `;
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Code copié !', 'success');
    });
}

function scrollToSection(sectionId) {
    const element = document.getElementById(sectionId);
    if (element) {
        const offset = 20;
        const elementPosition = element.getBoundingClientRect().top + window.pageYOffset;
        window.scrollTo({
            top: elementPosition - offset,
            behavior: 'smooth'
        });
    }
}

function getIdeaTypeLabel(type) {
    const labels = {
        course: 'Module de cours',
        platform: 'Fonctionnalité plateforme',
        improvement: 'Amélioration'
    };
    return labels[type] || type;
}

function getStatusLabel(status) {
    const labels = {
        proposed: 'Proposé',
        in_progress: 'En cours',
        completed: 'Réalisé',
        rejected: 'Refusé'
    };
    return labels[status] || status;
}

// ==================== FILTRES ====================

filterTypeEl.addEventListener('change', () => {
    currentFilters.type = filterTypeEl.value;
    loadTools();
});

filterGoalEl.addEventListener('change', () => {
    currentFilters.goal = filterGoalEl.value;
    loadTools();
});

filterSearchEl.addEventListener('input', () => {
    currentFilters.search = filterSearchEl.value;
    debounce(() => loadTools(), 300);
});

heroSearchEl.addEventListener('input', () => {
    filterSearchEl.value = heroSearchEl.value;
    currentFilters.search = heroSearchEl.value;
    debounce(() => {
        loadTools();
        scrollToSection('tools-section');
    }, 300);
});

// Debounce helper
let debounceTimer;
function debounce(callback, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(callback, delay);
}

// ==================== INITIALISATION ====================

document.addEventListener('DOMContentLoaded', async () => {
    await loadTools();
    await loadIdeas();
    await updateStats();
});

// Rafraîchir les stats toutes les 5 minutes
setInterval(updateStats, 5 * 60 * 1000);
