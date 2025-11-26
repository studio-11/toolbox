# 🔧 IFEN Toolbox - Brique "Travaux & Mise à jour"
## Documentation complète pour reprise de projet

---

## 📋 RÉSUMÉ DU PROJET

### Contexte
La Toolbox IFEN est une plateforme modulaire fonctionnant par "briques" PHP. Chaque brique est un composant autonome (PHP template + CSS + JS + endpoints API).

### Brique "Travaux & Mise à jour"
Cette brique permet de :
- Afficher le statut de la plateforme LearningSphere (Moodle) dans le hero
- Gérer les travaux : planifiés, non planifiés, en cours, terminés
- Filtrer et rechercher les travaux
- Afficher les détails de chaque travail dans une lightbox

### Approche "Light"
Au lieu d'une section complète en bas de page, la brique s'intègre dans le hero :
- Une 4ème stat cliquable "Travaux" avec indicateur de statut + version
- Au clic → Lightbox avec la liste complète des travaux

---

## 📁 STRUCTURE DES FICHIERS

```
toolbox/
├── index.php                       # Page principale (hero modifié avec stat Travaux)
├── includes/
│   ├── config.php                  # Configuration (existant)
│   ├── header.php                  # Header avec CSS brick-works.css
│   └── footer.php                  # Footer avec JS brick-works.js + init
├── briques/
│   ├── brick-tools.php             # Brique outils (existant)
│   ├── brick-ideas.php             # Brique idées (existant)
│   ├── brick-beta.php              # Brique beta (existant)
│   └── brick-works.php             # ⭐ Templates lightbox travaux
├── css/
│   ├── base.css                    # CSS base (existant)
│   ├── brick-tools.css             # (existant)
│   ├── brick-ideas.css             # (existant)
│   ├── brick-beta.css              # (existant)
│   └── brick-works.css             # ⭐ Styles travaux + modal
├── js/
│   ├── utils.js                    # Utilitaires (existant)
│   ├── brick-tools.js              # (existant)
│   ├── brick-ideas.js              # (existant)
│   ├── brick-beta.js               # (existant)
│   └── brick-works.js              # ⭐ Module JS autonome avec modal intégré
└── api/
    └── api.php                     # API REST avec endpoints travaux
```

---

## 🗄️ BASE DE DONNÉES

### Tables créées

#### 1. `toolbox_platform_status`
Stocke le statut actuel de la plateforme (1 seule ligne).

```sql
CREATE TABLE toolbox_platform_status (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform_name VARCHAR(100) DEFAULT 'LearningSphere',
    platform_version VARCHAR(50) DEFAULT '4.3.2',
    moodle_version VARCHAR(50) DEFAULT 'Moodle 4.3.2+',
    current_status ENUM('operational', 'maintenance', 'upgrading', 'partial_outage', 'major_outage') DEFAULT 'operational',
    status_message TEXT,
    next_planned_maintenance DATETIME,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT
);
```

**Valeurs de `current_status` :**
- `operational` : Tout fonctionne (vert)
- `maintenance` : Maintenance en cours (orange)
- `upgrading` : Mise à jour en cours (bleu)
- `partial_outage` : Panne partielle (rouge)
- `major_outage` : Panne majeure (rouge clignotant)

#### 2. `toolbox_works`
Liste des travaux.

```sql
CREATE TABLE toolbox_works (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    work_type ENUM('maintenance', 'upgrade', 'feature', 'bugfix', 'security', 'performance', 'other') DEFAULT 'maintenance',
    status ENUM('planned', 'unplanned', 'in_progress', 'completed', 'cancelled') DEFAULT 'unplanned',
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    causes_downtime TINYINT(1) DEFAULT 0,
    estimated_downtime_minutes INT,
    affected_services JSON,
    planned_start_date DATETIME,
    planned_end_date DATETIME,
    actual_start_date DATETIME,
    actual_end_date DATETIME,
    target_version VARCHAR(50),
    from_version VARCHAR(50),
    work_notes TEXT,
    completion_notes TEXT,
    assigned_to VARCHAR(255),
    created_by INT,
    created_by_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Types de travaux (`work_type`) :**
- `maintenance` : Maintenance technique
- `upgrade` : Mise à jour de version
- `feature` : Nouvelle fonctionnalité
- `bugfix` : Correction de bug
- `security` : Correctif sécurité
- `performance` : Optimisation performance
- `other` : Autre

**Statuts (`status`) :**
- `planned` : Planifié (violet)
- `unplanned` : Non planifié (orange)
- `in_progress` : En cours (cyan)
- `completed` : Terminé (vert)
- `cancelled` : Annulé (gris)

#### 3. `toolbox_platform_status_history`
Historique des changements de statut.

```sql
CREATE TABLE toolbox_platform_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    status_message TEXT,
    changed_by INT,
    changed_by_name VARCHAR(255),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### 4. `toolbox_works_notifications` (optionnel)
Abonnements aux notifications.

```sql
CREATE TABLE toolbox_works_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_id INT,
    user_id INT,
    user_email VARCHAR(255),
    notification_type ENUM('all', 'status_change', 'completion') DEFAULT 'all',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_subscription (work_id, user_id)
);
```

### Données de démonstration
Le script SQL inclut 13 travaux de démo :
- 4 terminés
- 4 planifiés
- 4 non planifiés
- 1 en cours

---

## 🔌 ENDPOINTS API

Tous les endpoints sont dans `api/api.php` (switch case).

### Statut plateforme

| Action | Méthode | Description |
|--------|---------|-------------|
| `platform_status` | GET | Récupère le statut actuel |
| `update_platform_status` | PUT | Met à jour le statut (+ historique) |
| `platform_status_history` | GET | Historique des changements |

### Travaux

| Action | Méthode | Description |
|--------|---------|-------------|
| `works_stats` | GET | Compteurs par statut |
| `works` | GET | Liste avec filtres |
| `work` | GET | Détails d'un travail (id) |
| `work_create` | POST | Créer un travail |
| `work_update` | PUT | Modifier un travail |
| `work_delete` | DELETE | Supprimer un travail |
| `work_complete` | POST | Marquer comme terminé |

### Paramètres de filtre pour `works`

| Paramètre | Type | Description |
|-----------|------|-------------|
| `status` | string | Filtre par statut (peut être multiple: `planned,in_progress`) |
| `type` | string | Filtre par type de travail |
| `search` | string | Recherche dans titre/description |
| `downtime` | 0/1 | Filtrer travaux avec interruption |
| `dateFrom` | date | Date début |
| `dateTo` | date | Date fin |
| `upcoming` | 1 | Travaux des 30 prochains jours |
| `limit` | int | Limite (défaut: 50) |

---

## 🎨 COMPOSANTS FRONTEND

### Hero (index.php)
```html
<!-- Stat Travaux cliquable -->
<div class="hero-stat hero-stat-clickable hero-stat-works" id="works-stat-trigger">
    <span class="hero-stat-value">Travaux</span>
    <span class="hero-stat-label">
        <span class="platform-status-dot" id="platform-status-dot"></span>
        <span id="platform-status-text">Chargement...</span>
        <span class="platform-version-inline" id="platform-version-text"></span>
    </span>
</div>
```

### Templates (brick-works.php)
3 templates `<template>` :
1. `template-works-list-modal` : Modal principal avec statut, stats, filtres, liste
2. `template-work-card` : Carte d'un travail dans la liste
3. `template-work-details-modal` : Modal détails d'un travail

### Module JavaScript (brick-works.js)
Module `BrickWorks` autonome avec :
- Système de modal intégré (pas de dépendance à ToolboxUtils.openModal)
- Méthode `apiCall()` propre
- Gestion des événements de filtre avec debounce
- Formatage de dates

**Méthodes principales :**
- `init()` : Initialisation
- `loadInitialData()` : Charge statut + stats pour le hero
- `openWorksModal()` : Ouvre la lightbox
- `loadWorks()` : Charge la liste filtrée
- `showWorkDetails(work)` : Affiche les détails

---

## 🎨 COULEURS ET STYLES

### Palette de statuts
| Statut | Couleur | Hex |
|--------|---------|-----|
| operational | Vert | #28a745 |
| maintenance | Orange | #fd7e14 |
| upgrading | Bleu | #007bff |
| partial_outage | Rouge | #dc3545 |
| major_outage | Rouge | #dc3545 |

### Palette de travaux
| Statut | Couleur | Hex |
|--------|---------|-----|
| in_progress | Cyan | #17a2b8 |
| planned | Violet IFEN | #502b85 |
| unplanned | Orange | #fd7e14 |
| completed | Vert | #28a745 |
| cancelled | Gris | #6c757d |

### Palette de priorités
| Priorité | Couleur |
|----------|---------|
| low | Vert clair |
| medium | Jaune |
| high | Orange |
| critical | Rouge |

---

## ⚙️ CONFIGURATION

### Variables globales JS
```javascript
window.TOOLBOX_CONFIG = {
    apiUrl: 'api/api.php',
    baseUrl: '...',
    user: { id, name, email },
    version: '2.0.0'
};
```

### Labels de configuration (dans BrickWorks.config)
```javascript
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
```

---

## 📝 NOTES TECHNIQUES

### Gestion des dates
- `planned_start_date` / `planned_end_date` : Dates prévues
- `actual_start_date` : Rempli automatiquement quand status → `in_progress`
- `actual_end_date` : Rempli automatiquement quand status → `completed`

### Services affectés
Stockés en JSON dans `affected_services`. Peut être :
- Un tableau JSON : `["LMS", "BigBlueButton", "H5P"]`
- Une chaîne séparée par virgules : `"LMS, BigBlueButton, H5P"`

### Tri des travaux
Ordre par défaut :
1. `in_progress` (en cours d'abord)
2. `planned` (puis planifiés)
3. `unplanned` (puis non planifiés)
4. `completed` (terminés en dernier)
5. Par date planifiée croissante
6. Par date de création décroissante

---

## 🚀 INSTALLATION

### 1. Exécuter le SQL
```bash
mysql -u ifen -p ifenlmsmain1db < sql/brick-works-schema.sql
```

### 2. Copier les fichiers
- `index.php` → Racine
- `briques/brick-works.php` → briques/
- `css/brick-works.css` → css/
- `js/brick-works.js` → js/

### 3. Vérifier les includes
Dans `header.php` :
```php
<link rel="stylesheet" href="css/brick-works.css">
```

Dans `footer.php` :
```php
<script src="js/brick-works.js"></script>
```

### 4. Ajouter les endpoints API
Copier les cases `platform_status`, `works_stats`, `works`, etc. dans `api/api.php` avant le `default`.

---

## 🔮 ÉVOLUTIONS POSSIBLES

- [ ] Panel admin pour gérer les travaux (CRUD complet)
- [ ] Notifications par email aux utilisateurs abonnés
- [ ] Calendrier visuel des travaux planifiés
- [ ] Export PDF/Excel des travaux
- [ ] Intégration avec système de tickets
- [ ] API webhooks pour notifications externes
- [ ] Mode sombre

---

## 📅 HISTORIQUE

| Date | Version | Description |
|------|---------|-------------|
| 2025-11-26 | 1.0 | Création initiale de la brique |
| 2025-11-26 | 1.1 | Passage en version "light" (hero + lightbox) |
| 2025-11-26 | 1.2 | Correction des variables CSS et sélecteurs JS |
| 2025-11-26 | 1.3 | Renommage en "Travaux" avec statut + version |

---

## 📚 FICHIERS DE RÉFÉRENCE

Pour reprendre le projet, les fichiers essentiels sont :
1. `sql/brick-works-schema.sql` - Structure BDD + données démo
2. `api/api-works-endpoints.php` - Tous les endpoints API
3. `index.php` - Hero modifié
4. `briques/brick-works.php` - Templates HTML
5. `css/brick-works.css` - Styles complets
6. `js/brick-works.js` - Module JS autonome

---

*Documentation générée le 26 novembre 2025*
