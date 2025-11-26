# TOOLBOX IFEN - Documentation Complète
## Contexte de développement pour continuation

---

# 1. INFORMATIONS GÉNÉRALES

## Projet
- **Nom** : Toolbox IFEN
- **Version** : 2.0.0
- **Description** : Plateforme de gestion d'outils numériques pour l'IFEN (Institut de Formation de l'Éducation Nationale - Luxembourg)
- **URL de production** : https://lms.ifen.lu/ifen_html/toolbox/

## Architecture
- **Backend** : PHP 7.4+
- **Base de données** : MySQL/MariaDB (mysql.restena.lu)
- **Frontend** : HTML5, CSS3, JavaScript vanilla
- **Style** : Charte graphique IFEN (violet #20164D, cyan #00b2bb, jaune #ffc107)

---

# 2. STRUCTURE DES FICHIERS

```
/var/www/html/ifen_html/toolbox/
│
├── index.php                          # Page principale - assembleur des briques
├── README.md                          # Documentation
│
├── includes/
│   ├── config.php                     # Configuration DB + helpers
│   ├── header.php                     # En-tête HTML (masqué, remplacé par quick-nav)
│   └── footer.php                     # Footer simple (bande + copyright)
│
├── briques/                           # ⭐ COMPOSANTS MODULAIRES
│   ├── brick-tools.php                # Brique 1: Outils Disponibles
│   ├── brick-beta.php                 # Brique 2: Beta Testing
│   └── brick-ideas.php                # Brique 3: Idées & Votes
│
├── css/
│   ├── base.css                       # Variables CSS, reset, composants de base
│   ├── layout.css                     # Hero, navigation, footer
│   ├── brick-tools.css                # Styles brique Outils
│   ├── brick-ideas.css                # Styles brique Idées
│   └── brick-beta.css                 # Styles brique Beta
│
├── js/
│   ├── config.js                      # Configuration globale JS
│   ├── utils.js                       # Utilitaires partagés (API calls, etc.)
│   ├── brick-tools.js                 # Logique brique Outils
│   ├── brick-ideas.js                 # Logique brique Idées
│   └── brick-beta.js                  # Logique brique Beta
│
├── api/
│   └── api.php                        # API REST backend
│
└── sql/
    ├── schema.sql                     # Schéma complet de la BDD
    ├── mise-a-jour-precise.sql        # Script de migration
    └── donnees-demo.sql               # Données de démonstration
```

---

# 3. BASE DE DONNÉES

## Connexion
```php
$credentials_file = '/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php';

// Contenu du fichier credentials :
return [
    'host' => 'mysql.restena.lu',
    'db' => 'ifenlmsmain1db',
    'user' => 'ifen',
    'pass' => '5Qmeytvw9JTyNMnL'
];
```

## Tables existantes (13 tables)

### Tables principales
```sql
-- OUTILS
toolbox_tools (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    type ENUM('course','platform') NOT NULL DEFAULT 'course',
    category_id INT,
    status ENUM('stable','beta','new','deprecated') DEFAULT 'stable',
    is_hot TINYINT(1) DEFAULT 0,
    short_description TEXT NOT NULL,
    long_description TEXT,
    audience VARCHAR(255),
    time_to_use VARCHAR(50),
    difficulty ENUM('debutant','intermediaire','avance') DEFAULT 'intermediaire',
    icon VARCHAR(100),
    gradient VARCHAR(255),
    screenshot_url VARCHAR(500),
    video_url VARCHAR(500),
    tutorial_text TEXT,
    code_snippet TEXT,
    installation_steps TEXT,
    views_count INT DEFAULT 0,
    installations_count INT DEFAULT 0,
    rating_avg DECIMAL(3,2) DEFAULT 0.00,
    rating_count INT DEFAULT 0,
    beta_start_date DATE,           -- Ajouté pour beta testing
    beta_end_date DATE,             -- Ajouté pour beta testing
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    published_at TIMESTAMP,
    deprecated_at TIMESTAMP,
    created_by INT
);

-- CATÉGORIES
toolbox_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(50),
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- IDÉES
toolbox_ideas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    type ENUM('course','platform','improvement') NOT NULL,
    problem TEXT NOT NULL,
    details TEXT,
    status ENUM('proposed','in_progress','completed','rejected') DEFAULT 'proposed',
    votes_count INT DEFAULT 0,
    user_id INT NOT NULL,
    user_name VARCHAR(255),
    user_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP
);

-- VOTES
toolbox_votes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idea_id INT NOT NULL,
    user_id INT NOT NULL,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (idea_id, user_id)
);

-- FAVORIS
toolbox_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_favorite (tool_id, user_id)
);

-- COMMENTAIRES
toolbox_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(255),
    comment TEXT NOT NULL,
    rating INT,
    is_approved TINYINT(1) DEFAULT 0,
    is_flagged TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- FEATURES DES OUTILS
toolbox_tool_features (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    feature_text VARCHAR(500) NOT NULL,
    display_order INT DEFAULT 0
);

-- STATISTIQUES D'UTILISATION
toolbox_tool_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    action_type ENUM('view','install','download','tutorial_view') NOT NULL,
    user_id INT,
    course_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- CHANGELOG
toolbox_changelog (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    version VARCHAR(50) NOT NULL,
    release_date DATE NOT NULL,
    changes TEXT NOT NULL,
    type ENUM('major','minor','patch','fix') DEFAULT 'minor',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Tables ajoutées (nouvelles fonctionnalités)
```sql
-- REVIEW LOGS (historique des reviews admin)
toolbox_tool_reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    reviewer_name VARCHAR(200),
    review_type ENUM('code_review','design_review','ux_review','security_review','performance_review','general') DEFAULT 'general',
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    status ENUM('pending','approved','rejected','needs_changes') DEFAULT 'pending',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_date TIMESTAMP
);

-- PLANIFICATION DES IDÉES (Kanban)
toolbox_idea_planning (
    id INT PRIMARY KEY AUTO_INCREMENT,
    idea_id INT NOT NULL UNIQUE,
    planned_start_date DATE,
    planned_end_date DATE,
    actual_start_date DATE,
    actual_end_date DATE,
    progress_percent INT DEFAULT 0,
    current_phase ENUM('analysis','design','development','testing','deployment','completed') DEFAULT 'analysis',
    assigned_to VARCHAR(200),
    assigned_to_id INT,
    dev_notes TEXT,
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    resulting_tool_id INT,
    planned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- INSCRIPTIONS BETA TESTING
toolbox_beta_testers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(200),
    user_email VARCHAR(255),
    status ENUM('registered','active','completed','dropped') DEFAULT 'registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity_at TIMESTAMP,
    UNIQUE KEY unique_tester (tool_id, user_id)
);

-- FEEDBACK BETA
toolbox_beta_feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tool_id INT NOT NULL,
    user_id INT NOT NULL,
    user_name VARCHAR(200),
    feedback_type ENUM('bug','suggestion','question','praise','general') DEFAULT 'general',
    title VARCHAR(255),
    content TEXT NOT NULL,
    rating INT,
    status ENUM('new','reviewed','in_progress','resolved','wont_fix') DEFAULT 'new',
    admin_response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Vues SQL
```sql
-- Outils disponibles (stable + new)
CREATE VIEW v_tools_available AS
SELECT t.*, c.name AS category_name, c.icon AS category_icon, c.color AS category_color
FROM toolbox_tools t
LEFT JOIN toolbox_categories c ON t.category_id = c.id
WHERE t.status IN ('stable', 'new')
ORDER BY t.is_hot DESC, t.created_at DESC;

-- Outils en beta avec stats
CREATE VIEW v_tools_beta AS
SELECT t.*, c.name AS category_name, c.icon AS category_icon,
       COUNT(DISTINCT bt.id) AS testers_count,
       COUNT(DISTINCT bf.id) AS feedback_count,
       DATEDIFF(t.beta_end_date, CURDATE()) AS days_remaining
FROM toolbox_tools t
LEFT JOIN toolbox_categories c ON t.category_id = c.id
LEFT JOIN toolbox_beta_testers bt ON t.id = bt.tool_id
LEFT JOIN toolbox_beta_feedback bf ON t.id = bf.tool_id
WHERE t.status = 'beta'
GROUP BY t.id
ORDER BY t.beta_start_date DESC;

-- Idées en attente (non planifiées)
CREATE VIEW v_ideas_pending AS
SELECT i.*, (SELECT COUNT(*) FROM toolbox_votes WHERE idea_id = i.id) AS total_votes
FROM toolbox_ideas i
LEFT JOIN toolbox_idea_planning p ON i.id = p.idea_id
WHERE p.id IS NULL AND i.status IN ('proposed', 'in_progress')
ORDER BY i.votes_count DESC, i.created_at DESC;

-- Idées planifiées
CREATE VIEW v_ideas_planned AS
SELECT i.*, p.planned_start_date, p.planned_end_date, p.progress_percent,
       p.current_phase, p.assigned_to, p.priority AS planning_priority
FROM toolbox_ideas i
INNER JOIN toolbox_idea_planning p ON i.id = p.idea_id
ORDER BY FIELD(p.priority, 'critical', 'high', 'medium', 'low'), p.planned_start_date ASC;
```

---

# 4. CONFIGURATION PHP

## includes/config.php
```php
<?php
// Mode debug
define('DEBUG_MODE', true);

// Chemins
define('BASE_PATH', __DIR__ . '/..');
define('BASE_URL', '/ifen_html/toolbox');
define('API_URL', BASE_URL . '/api/api.php');

// Titre et version
define('SITE_TITLE', 'Toolbox IFEN');
define('APP_VERSION', '2.0.0');

// Ressources externes
define('FONT_URL', 'https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@400;500;600;700&display=swap');
define('FONTAWESOME_URL', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');
define('IFEN_BG_URL', 'https://lms.ifen.lu/ifen_images/Backgrounds_transverse.jpg');
define('IFEN_BG_PASTEL_URL', 'https://lms.ifen.lu/ifen_images/Fond_pastel_transverse.jpg');
define('IFEN_MOODLE_CSS', '/ifenCSS/custom-moodle-styles.css');

// Connexion BDD
$credentials_file = '/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php';
if (file_exists($credentials_file)) {
    $db_credentials = require $credentials_file;
    define('DB_HOST', $db_credentials['host']);
    define('DB_NAME', $db_credentials['db']);
    define('DB_USER', $db_credentials['user']);
    define('DB_PASS', $db_credentials['pass']);
} else {
    define('DB_HOST', 'mysql.restena.lu');
    define('DB_NAME', 'ifenlmsmain1db');
    define('DB_USER', 'ifen');
    define('DB_PASS', '5Qmeytvw9JTyNMnL');
}

// Fonction de connexion PDO
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// Utilisateur courant (à adapter pour Moodle)
function getCurrentUser() {
    // TODO: Intégration Moodle
    // global $USER;
    // return ['id' => $USER->id, 'name' => $USER->firstname.' '.$USER->lastname, 'email' => $USER->email];
    return ['id' => 1, 'name' => 'Utilisateur Test', 'email' => 'test@ifen.lu'];
}

// Helpers
function e($string) { return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8'); }
function url($path = '') { return BASE_URL . '/' . ltrim($path, '/'); }
function asset($path) { return url($path) . '?v=' . APP_VERSION; }
function isAdmin() { return true; } // TODO: Adapter selon système
```

---

# 5. API ENDPOINTS

## Base URL
```
/ifen_html/toolbox/api/api.php?action=XXX
```

## Endpoints disponibles

| Action | Méthode | Description | Paramètres |
|--------|---------|-------------|------------|
| `stats` | GET | Statistiques globales | - |
| `tools` | GET | Liste des outils | `status=available\|beta` |
| `tool` | GET | Détails d'un outil | `id=X` |
| `tool_reviews` | GET | Reviews d'un outil | `tool_id=X` |
| `categories` | GET | Liste des catégories | - |
| `ideas` | GET | Liste des idées | `status=pending\|planned` |
| `idea` | POST | Créer une idée | JSON body |
| `vote` | POST | Voter pour une idée | `idea_id` |
| `user_votes` | GET | Votes de l'utilisateur | - |
| `plan_idea` | POST | Planifier une idée | JSON body |
| `update_planning` | PUT | Modifier planification | JSON body |
| `unplan_idea` | DELETE | Retirer planification | `idea_id` |
| `beta_register` | POST | S'inscrire au beta | `tool_id` |
| `user_beta_registrations` | GET | Inscriptions beta user | - |
| `beta_feedback` | POST | Envoyer feedback | JSON body |
| `beta_feedbacks` | GET | Liste feedbacks | `tool_id=X` |
| `favorite` | POST/DELETE | Gérer favoris | `tool_id` |
| `user_favorites` | GET | Favoris de l'utilisateur | - |
| `track` | POST | Tracker action | `tool_id`, `action_type` |

---

# 6. DESIGN ET STYLES

## Couleurs IFEN
```css
:root {
    --primary: #20164D;        /* Violet foncé */
    --primary-light: #2d1f6b;
    --secondary: #00b2bb;      /* Cyan */
    --secondary-light: #00d4df;
    --accent: #ffc107;         /* Jaune */
    --accent-light: #ffd54f;
}
```

## Typographie
- **Police principale** : Barlow Semi Condensed (Google Fonts)
- **Fallback** : -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif

## Ressources images IFEN
```
https://lms.ifen.lu/ifen_images/Backgrounds_transverse.jpg
https://lms.ifen.lu/ifen_images/Fond_pastel_transverse.jpg
https://lms.ifen.lu/ifen_images/IFEN_logo.png
https://lms.ifen.lu/ifen_images/transversal_header.png
```

## CSS existant Moodle
```
/ifenCSS/custom-moodle-styles.css
```

---

# 7. FONCTIONNALITÉS PAR BRIQUE

## Brique 1 : Outils Disponibles (`brick-tools.php`)
- Affichage grille responsive
- Vue slider horizontal avec navigation
- Filtres (type, catégorie, recherche)
- Système de favoris
- Modal détails avec tutoriel
- Review Logs popup
- Compteur d'installations et vues

## Brique 2 : Beta Testing (`brick-beta.php`)
- Liste des outils en beta
- Countdown (jours restants)
- Badge "ending soon" (< 7 jours)
- Inscription beta test
- Formulaire feedback structuré (Bug, Suggestion, Question, Bravo)
- Notation par étoiles (1-5)
- Liste des feedbacks avec réponses admin

## Brique 3 : Idées & Votes (`brick-ideas.php`)
- Formulaire proposition d'idées
- Système de votes (1 vote par utilisateur)
- Onglet "Idées en attente"
- Onglet "En programmation" (vue Kanban)
- 6 colonnes Kanban : Analyse → Design → Développement → Tests → Déploiement → Terminé
- Planification avec dates, priorité, assignation
- Barre de progression

---

# 8. DONNÉES DE DÉMONSTRATION

## Catégories (6)
- Évaluation, Collaboration, Multimédia, Organisation, Présentation, Communication

## Outils (13)
- **Stables** : H5P, BigBlueButton, Wooclap, Genially, Forum, Wiki
- **Nouveaux** : Padlet, Canva
- **Beta** : Learning Analytics Dashboard, AI Quiz Generator, Virtual Classroom 3D

## Idées (8)
- 6 en attente de votes
- 2 en programmation avec planification

## Stats de test
- 20 votes
- 12 beta testers
- 8 feedbacks

---

# 9. CHEMINS SERVEUR

```
/export/hosting/men/ifen/htdocs-lms/          # Racine Moodle
/export/hosting/men/ifen/htdocs-lms/ifen_html/toolbox/  # Toolbox
/export/hosting/men/ifen/htdocs-lms/ifenCSS/  # CSS IFEN global
/export/hosting/men/ifen/htdocs-lms/ifen_credentials/   # Credentials DB
```

---

# 10. TODO / AMÉLIORATIONS FUTURES

## À implémenter
- [ ] Intégration Moodle pour `getCurrentUser()`
- [ ] Panel Admin complet (CRUD outils, modération)
- [ ] Système de notifications
- [ ] Export PDF des feedbacks
- [ ] Statistiques avancées

## Sécurité
- [ ] Validation CSRF sur les formulaires
- [ ] Rate limiting API
- [ ] Sanitization des inputs

---

# 11. COMMANDES UTILES

## Vérifier la structure
```bash
ls -la /var/www/html/ifen_html/toolbox/
```

## Test connexion BDD
```php
<?php
require_once 'includes/config.php';
$pdo = getDbConnection();
echo "Connexion OK";
```

## Vider les caches navigateur
```
Ctrl+Shift+R (Windows/Linux)
Cmd+Shift+R (Mac)
```

---

*Document généré le 26 novembre 2025*
*Version Toolbox : 2.0.0*
