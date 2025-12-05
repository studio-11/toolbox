# IFEN Toolbox - Frontend Implementation Summary

## 📁 Structure des fichiers créés

```
toolbox/
├── api/
│   └── api.php                 # Endpoints API (login, beta, ideas, etc.)
├── briques/
│   ├── brick-beta.php          # Template PHP brique Beta Test
│   ├── brick-ideas.php         # Template PHP brique Idées & Votes
│   └── brick-tools.php         # Template PHP brique Outils
├── css/
│   └── additional.css          # Nouveaux styles (login, audience, difficulté, etc.)
├── includes/
│   ├── config.php              # Configuration + fonctions auth
│   ├── header.php              # Header avec menu utilisateur
│   └── footer.php              # Footer avec initialisation JS
├── js/
│   ├── config.js               # Configuration globale JS
│   ├── utils.js                # Utilitaires (API, modals, notifications)
│   ├── brick-tools.js          # Logique brique Outils
│   ├── brick-beta.js           # Logique brique Beta Test
│   ├── brick-ideas.js          # Logique brique Idées & Votes
│   └── brick-works.js          # Logique statut plateforme
├── sql/
│   └── frontend-updates.sql    # Requêtes SQL (tables, vues, procédures)
├── index.php                   # Page principale (requiert login)
├── login.php                   # Page de connexion IAM
└── logout.php                  # Script de déconnexion
```

---

## 🔐 Système de Login IAM

### Fichiers concernés :
- `login.php` - Page de connexion
- `includes/config.php` - Fonctions d'authentification
- `logout.php` - Déconnexion
- `api/api.php` - Endpoints login/logout/check_auth

### Fonctionnement :
1. L'utilisateur entre son identifiant IAM
2. Vérification dans `mdl_user` (deleted=0, suspended=0)
3. Vérification blacklist dans `toolbox_users`
4. Création/mise à jour utilisateur toolbox
5. Session PHP créée avec structure :
```php
$_SESSION['toolbox_user'] = [
    'id' => $toolboxUserId,
    'mdl_user_id' => $mdlUserId,
    'username' => 'jdupont',
    'name' => 'Jean Dupont',
    'email' => 'jean.dupont@edu.lu',
    'is_admin' => false
];
```

### Fonctions disponibles :
- `isLoggedIn()` - Vérifie si connecté
- `isAdmin()` - Vérifie si admin
- `requireLogin()` - Redirige vers login si non connecté
- `requireAdmin()` - Vérifie droits admin
- `getCurrentUser()` - Retourne infos utilisateur
- `logout()` - Déconnecte l'utilisateur

---

## 📊 Brique Outils - Modifications

### Nouveautés :
1. **Filtre "Public cible"** :
   - 👤 Participant
   - 👔 Manager IFEN
   - 🔧 Admin only

2. **"Difficulté d'utilisation"** (remplace "Temps d'utilisation") :
   - Facile (vert)
   - Intermédiaire (jaune)
   - Avancé (rouge)

3. **Badges audience multiples** sur chaque carte outil

### Fichiers :
- `js/brick-tools.js`
- `briques/brick-tools.php`
- `css/additional.css` (styles .audience-badge, .difficulty-value)

---

## 🧪 Brique Beta Test - Améliorations

### Nouveautés :
1. **Bouton info (i)** dans le header de chaque carte
2. **Popup d'informations détaillées** :
   - Description complète
   - Période de test (dates début/fin)
   - Statistiques (testeurs, retours)
   - Statut inscription
   - Lien cours Moodle (si inscrit + courseid existe)

3. **Modal de succès après inscription** :
   - Prochaines étapes numérotées (1-4)
   - Lien direct vers le cours Moodle

4. **Badge "Vous êtes inscrit !"** sur les cartes

### URL Moodle :
```
https://learningsphere.ifen.lu/course/view.php?id=[beta_course_id]
```

### Fichiers :
- `js/brick-beta.js`
- `briques/brick-beta.php`
- `css/additional.css`

---

## 💡 Brique Idées & Votes - Nouveaux types

### Types d'idées (MIS À JOUR) :
| Valeur | Label | Emoji |
|--------|-------|-------|
| `course_activity` | Activité de cours | 📚 |
| `course_resource` | Ressource de cours | 📄 |
| `platform_feature` | Fonctionnalité plateforme | ⚙️ |
| `other` | Autres | 📌 |

### Bouton "Programmer" :
- Visible **uniquement pour les admins**
- Situé en bas du listing des idées
- Ouvre un panel de sélection des idées les plus votées
- Permet de définir : dates, priorité, phase, assignation

### Fichiers :
- `js/brick-ideas.js`
- `briques/brick-ideas.php`
- `css/additional.css`

---

## 🔧 Configuration JavaScript

### `js/config.js` - Constantes :

```javascript
// Types d'idées
ideaTypes: {
    course_activity: { label: 'Activité de cours', emoji: '📚' },
    course_resource: { label: 'Ressource de cours', emoji: '📄' },
    platform_feature: { label: 'Fonctionnalité plateforme', emoji: '⚙️' },
    other: { label: 'Autres', emoji: '📌' }
}

// Public cible
targetAudiences: {
    participant: { label: 'Participant', icon: 'fa-user', color: '#1e40af' },
    manager: { label: 'Manager IFEN', icon: 'fa-user-tie', color: '#92400e' },
    admin: { label: 'Admin only', icon: 'fa-user-shield', color: '#991b1b' }
}

// Difficulté d'utilisation
difficultyLevels: {
    easy: { label: 'Facile', color: '#065f46' },
    medium: { label: 'Intermédiaire', color: '#92400e' },
    hard: { label: 'Avancé', color: '#991b1b' }
}
```

---

## 🗄️ Modifications SQL requises

### Nouvelles tables :
- `toolbox_users` - Gestion utilisateurs + blacklist
- `toolbox_sessions` - Sessions de connexion

### Colonnes modifiées :
- `toolbox_tools.target_audience` - JSON array des audiences
- `toolbox_tools.beta_course_id` - ID du cours Moodle pour beta
- `toolbox_ideas.type` - ENUM étendu avec nouveaux types

### Voir : `sql/frontend-updates.sql`

---

## 📱 Header - Menu utilisateur

### Affichage :
- Avatar avec initiales
- Nom + email
- Badge "ADMIN" si admin
- Dropdown avec :
  - Lien Administration (si admin)
  - Bouton Déconnexion

### Fichier : `includes/header.php`

---

## 🚀 Déploiement

### Ordre d'exécution :
1. Exécuter `sql/frontend-updates.sql` sur la base de données
2. Copier les fichiers PHP dans `/export/hosting/men/ifen/htdocs-html/ifen_html/toolbox/`
3. Copier les fichiers JS dans le dossier `js/`
4. Copier `additional.css` dans le dossier `css/`
5. Vérifier les chemins dans `config.php` (DB_HOST, DB_NAME, etc.)
6. Tester le login avec un identifiant IAM valide

### Variables à vérifier :
```php
// Dans config.php
define('DB_HOST', 'mysql.restena.lu');
define('DB_NAME', 'ifenlmsmain1db');
define('DB_USER', 'xxx');
define('DB_PASS', 'xxx');
define('MOODLE_COURSE_URL', 'https://learningsphere.ifen.lu/course/view.php?id=');
```

---

## ✅ Checklist des fonctionnalités

- [x] Login IAM avec vérification mdl_user
- [x] Système blacklist via toolbox_users
- [x] Header avec menu utilisateur et badge admin
- [x] Filtre public cible sur outils (participant, manager, admin)
- [x] Difficulté d'utilisation (remplace temps d'utilisation)
- [x] Popup info détaillée beta test
- [x] Lien cours Moodle après inscription beta
- [x] Nouveaux types d'idées (4 types)
- [x] Bouton "Programmer" admin-only
- [x] API endpoints pour toutes les actions
- [x] Styles CSS pour tous les nouveaux composants
