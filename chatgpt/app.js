// ==================== DONNÉES MOCK ====================

// Liste des outils (tu pourras plus tard charger ça en JSON depuis la BDD)
const tools = [
    {
        id: 'auto-link-activity',
        name: 'Auto Link Activity',
        type: 'course', // 'course' | 'platform'
        goal: 'guidance', // guidance, virtual-class, resources, quality, interaction, support
        status: 'stable', // stable, beta, new
        isHot: true,
        shortDescription: 'Créer un joli bloc d’introduction qui se relie automatiquement à l’activité juste en dessous (glossaire, forum, quiz, etc.).',
        bullets: [
            'Plus besoin de créer le lien à la main',
            'L’activité réelle est cachée au profit d’une interface propre',
            'Un seul clic pour démarrer la leçon'
        ],
        audience: 'Formateurs (éditeur du cours)',
        timeToUse: '2 minutes',
        difficulty: 'Débutant'
    },
    {
        id: 'ms-teams-webinar',
        name: 'MS Teams Webinar',
        type: 'course',
        goal: 'virtual-class',
        status: 'new',
        isHot: true,
        shortDescription: 'Créer un lien de réunion Microsoft Teams directement depuis le cours Moodle, sans jongler entre plusieurs fenêtres.',
        bullets: [
            'Bouton pour créer la réunion Teams',
            'Remplacement automatique par un bouton de connexion',
            'Message automatique si la réunion n’est pas encore créée'
        ],
        audience: 'Formateurs / secrétariats',
        timeToUse: '3 minutes',
        difficulty: 'Intermédiaire'
    },
    {
        id: 'info-formation-manager',
        name: 'InfoFormation Manager',
        type: 'course',
        goal: 'quality',
        status: 'stable',
        isHot: false,
        shortDescription: 'Relier un cours Moodle à sa fiche formation IFEN et afficher les informations officielles automatiquement.',
        bullets: [
            'Synchronisation avec le catalogue IFEN',
            'Informations pratiques affichées automatiquement',
            'Moins de copier-coller entre sites'
        ],
        audience: 'Formateurs / équipe IFEN',
        timeToUse: '5 minutes',
        difficulty: 'Intermédiaire'
    },
    {
        id: 'ressources-folder',
        name: 'Dossier Ressources',
        type: 'course',
        goal: 'resources',
        status: 'stable',
        isHot: false,
        shortDescription: 'Affichage visuel des dossiers de documents, avec icônes et bouton de téléchargement pour chaque fichier.',
        bullets: [
            'Liste automatiquement les fichiers d’un dossier Moodle',
            'Bouton de téléchargement clair',
            'Interface homogène pour tous les cours'
        ],
        audience: 'Formateurs',
        timeToUse: '2 minutes',
        difficulty: 'Débutant'
    },
    {
        id: 'checklist-formation',
        name: 'Checklist Formation',
        type: 'course',
        goal: 'quality',
        status: 'new',
        isHot: true,
        shortDescription: 'Vérifier en un coup d’œil si tout est prêt pour votre formation (séances, matériel, supports, visio…).',
        bullets: [
            'Checklist personnalisée par formation',
            'Progression globale en pourcentage',
            'Sauvegarde automatique des cases cochées'
        ],
        audience: 'Formateurs',
        timeToUse: '5 à 10 minutes',
        difficulty: 'Intermédiaire'
    },
    {
        id: 'support-technique',
        name: 'Support Technique',
        type: 'platform',
        goal: 'support',
        status: 'stable',
        isHot: true,
        shortDescription: 'Formulaire intégré dans Moodle pour déclarer un incident technique, avec toutes les informations utiles pour le support.',
        bullets: [
            'Accessible depuis l’icône en bas à droite',
            'Envoie un mail complet à l’équipe LearningSphere',
            'Gain de temps pour les utilisateurs et le support'
        ],
        audience: 'Tous les participants / formateurs',
        timeToUse: 'Immédiat',
        difficulty: 'Débutant'
    },
    {
        id: 'wordcloud-progress',
        name: 'Wordcloud + progression',
        type: 'course',
        goal: 'interaction',
        status: 'beta',
        isHot: false,
        shortDescription: 'Amélioration de l’activité Nuage de mots avec affichage du nombre de mots restants et validation automatique.',
        bullets: [
            'Affiche clairement la progression dans l’activité',
            'Popup de félicitations quand tout est rempli',
            'Validation automatique de l’activité et retour au cours'
        ],
        audience: 'Formateurs (activité Wordcloud)',
        timeToUse: '5 minutes',
        difficulty: 'Intermédiaire'
    }
];

// Idées initiales (prototype)
let ideas = [
    {
        id: 1,
        title: 'Bloc de feedback rapide sur le cours',
        type: 'course',
        problem: 'Les formateurs n’ont pas toujours un retour rapide des participants pendant la formation.',
        details: 'Un bloc avec 3 boutons (👍 / 🤔 / 👎) et un champ commentaire facultatif. Résumé visible dans le rapport du cours.',
        votes: 7
    },
    {
        id: 2,
        title: 'Statistiques globales pour Auto Link Activity',
        type: 'improvement',
        problem: 'Difficile de savoir combien de participants cliquent réellement sur les blocs Auto Link.',
        details: 'Ajouter un petit compteur d’utilisation et un rapport simple dans le cours.',
        votes: 4
    },
    {
        id: 3,
        title: 'Mode sombre pour la Toolbox',
        type: 'platform',
        problem: 'Certains formateurs travaillent le soir et aimeraient une interface moins lumineuse.',
        details: '',
        votes: 2
    }
];

// ==================== RENDU OUTILS ====================

const toolsListEl = document.getElementById('tools-list');
const filterTypeEl   = document.getElementById('filter-type');
const filterGoalEl   = document.getElementById('filter-goal');
const filterSearchEl = document.getElementById('filter-search');

function renderTools() {
    const typeFilter = filterTypeEl.value;
    const goalFilter = filterGoalEl.value;
    const searchTerm = filterSearchEl.value.toLowerCase().trim();

    toolsListEl.innerHTML = '';

    const filtered = tools.filter(tool => {
        if (typeFilter !== 'all' && tool.type !== typeFilter) return false;
        if (goalFilter !== 'all' && tool.goal !== goalFilter) return false;
        if (searchTerm) {
            const text = (tool.name + ' ' + tool.shortDescription).toLowerCase();
            if (!text.includes(searchTerm)) return false;
        }
        return true;
    });

    if (filtered.length === 0) {
        toolsListEl.innerHTML = '<p>Aucun outil ne correspond aux filtres sélectionnés.</p>';
        return;
    }

    filtered.forEach(tool => {
        const card = document.createElement('article');
        card.className = 'ls-card';

        if (tool.isHot || tool.status === 'new') {
            const badge = document.createElement('div');
            badge.className = 'ls-card-badge';
            badge.textContent = tool.status === 'new' ? 'Nouveau' : 'Hot';
            card.appendChild(badge);
        }

        const title = document.createElement('h3');
        title.className = 'ls-card-title';
        title.textContent = tool.name;
        card.appendChild(title);

        const meta = document.createElement('div');
        meta.className = 'ls-card-meta';

        const typeLabel = tool.type === 'course' ? 'Outil de cours' : 'Fonctionnalité plateforme';
        const goalMap = {
            guidance: 'Guidage & navigation',
            'virtual-class': 'Classes virtuelles',
            resources: 'Ressources & docs',
            quality: 'Qualité & suivi',
            interaction: 'Interaction & activités',
            support: 'Support & incidents'
        };

        meta.innerHTML = `
            <span>${typeLabel}</span>
            <span>${goalMap[tool.goal] || ''}</span>
            <span>⏱ ${tool.timeToUse}</span>
            <span>🎯 ${tool.difficulty}</span>
        `;
        card.appendChild(meta);

        const desc = document.createElement('p');
        desc.className = 'ls-card-desc';
        desc.textContent = tool.shortDescription;
        card.appendChild(desc);

        if (tool.bullets && tool.bullets.length) {
            const ul = document.createElement('ul');
            ul.className = 'ls-card-bullets';
            tool.bullets.forEach(b => {
                const li = document.createElement('li');
                li.textContent = b;
                ul.appendChild(li);
            });
            card.appendChild(ul);
        }

        const actions = document.createElement('div');
        actions.className = 'ls-card-actions';

        const btnDetails = document.createElement('button');
        btnDetails.className = 'btn ghost';
        btnDetails.textContent = 'Voir la fiche';
        btnDetails.addEventListener('click', () => {
            // Ici tu pourras ouvrir un modal ou rediriger vers toolbox.php?tool=id
            alert(`Prototype : fiche détaillée pour "${tool.name}" à venir.`);
        });

        const btnTutorial = document.createElement('button');
        btnTutorial.className = 'btn primary';
        btnTutorial.textContent = 'Tutoriel / code';
        btnTutorial.addEventListener('click', () => {
            alert('Prototype : tutoriel vidéo / snippet de code à intégrer ici.');
        });

        actions.appendChild(btnDetails);
        actions.appendChild(btnTutorial);
        card.appendChild(actions);

        toolsListEl.appendChild(card);
    });
}

filterTypeEl.addEventListener('change', renderTools);
filterGoalEl.addEventListener('change', renderTools);
filterSearchEl.addEventListener('input', renderTools);

// ==================== IDEES & VOTES ====================

const ideasListEl = document.getElementById('ideas-list');
const ideaFormEl  = document.getElementById('idea-form');
const ideaMsgEl   = document.getElementById('idea-message');

// Prototype : stockage local des votes pour empêcher un spam simple
const votedIdeaIds = new Set(JSON.parse(localStorage.getItem('ls_toolbox_votes') || '[]'));

function saveVoteState() {
    localStorage.setItem('ls_toolbox_votes', JSON.stringify([...votedIdeaIds]));
}

function renderIdeas() {
    ideasListEl.innerHTML = '';

    // Tri par nombre de votes décroissant
    const sorted = [...ideas].sort((a, b) => b.votes - a.votes);

    const typeLabelMap = {
        course: 'Module de cours',
        platform: 'Fonctionnalité plateforme',
        improvement: 'Amélioration'
    };

    sorted.forEach(idea => {
        const item = document.createElement('div');
        item.className = 'ls-idea-item';

        const votesCol = document.createElement('div');
        votesCol.className = 'ls-idea-votes';

        const voteBtn = document.createElement('button');
        voteBtn.innerHTML = `👍 <span>+1</span>`;

        if (votedIdeaIds.has(idea.id)) {
            voteBtn.disabled = true;
            voteBtn.style.opacity = '0.6';
        }

        voteBtn.addEventListener('click', () => {
            idea.votes += 1;
            votedIdeaIds.add(idea.id);
            saveVoteState();
            renderIdeas();
        });

        const count = document.createElement('div');
        count.className = 'ls-idea-count';
        count.textContent = idea.votes;

        votesCol.appendChild(voteBtn);
        votesCol.appendChild(count);

        const content = document.createElement('div');
        content.className = 'ls-idea-content';

        const title = document.createElement('h4');
        title.textContent = idea.title;

        const tags = document.createElement('div');
        tags.className = 'ls-idea-tags';
        tags.textContent = typeLabelMap[idea.type] || '';

        const desc = document.createElement('p');
        desc.className = 'ls-idea-description';
        desc.innerHTML = `<strong>Problème :</strong> ${idea.problem}`;

        if (idea.details) {
            const details = document.createElement('p');
            details.className = 'ls-idea-description';
            details.innerHTML = `<strong>Détails :</strong> ${idea.details}`;
            content.appendChild(details);
        }

        content.appendChild(title);
        content.appendChild(tags);
        content.appendChild(desc);

        item.appendChild(votesCol);
        item.appendChild(content);

        ideasListEl.appendChild(item);
    });
}

ideaFormEl.addEventListener('submit', (e) => {
    e.preventDefault();

    const title   = document.getElementById('idea-title').value.trim();
    const type    = document.getElementById('idea-type').value;
    const problem = document.getElementById('idea-problem').value.trim();
    const details = document.getElementById('idea-details').value.trim();

    if (!title || !type || !problem) {
        ideaMsgEl.textContent = 'Merci de remplir les champs obligatoires.';
        ideaMsgEl.style.color = '#b91c1c';
        return;
    }

    const newId = ideas.length ? Math.max(...ideas.map(i => i.id)) + 1 : 1;

    const newIdea = {
        id: newId,
        title,
        type,
        problem,
        details,
        votes: 0
    };

    // Dans la V2 : envoyer au serveur via fetch POST
    ideas.push(newIdea);
    ideaFormEl.reset();
    ideaMsgEl.textContent = 'Merci ! Votre idée a bien été enregistrée (prototype local).';
    ideaMsgEl.style.color = '#059669';
    renderIdeas();
});

// ==================== NAVIGATION BOUTONS HEADER ====================

document.querySelectorAll('.btn[data-target]').forEach(btn => {
    btn.addEventListener('click', () => {
        const targetId = btn.getAttribute('data-target');
        const el = document.getElementById(targetId);
        if (el) {
            window.scrollTo({
                top: el.offsetTop - 20,
                behavior: 'smooth'
            });
        }
    });
});

// ==================== INIT ====================

renderTools();
renderIdeas();
