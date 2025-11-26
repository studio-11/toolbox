# 🧰 IFEN Toolbox - Architecture PHP Modulaire

## 📁 Structure des fichiers

```
toolbox/
│
├── 📄 index.php                     # Page principale (assembleur)
│
├── 📂 includes/                     # Fichiers partagés
│   ├── config.php                  # Configuration (DB, chemins, helpers)
│   ├── header.php                  # En-tête HTML + navigation
│   └── footer.php                  # Pied de page + scripts JS
│
├── 📂 briques/                      # ⭐ BRIQUES MODULAIRES
│   ├── brick-tools.php             # Brique 1: Outils Disponibles
│   ├── brick-ideas.php             # Brique 2: Idées & Votes
│   └── brick-beta.php              # Brique 3: Beta Testing
│
├── 📂 css/
│   ├── base.css                    # Variables IFEN + composants
│   ├── layout.css                  # Header, hero, footer
│   ├── brick-tools.css             # Styles Brique 1
│   ├── brick-ideas.css             # Styles Brique 2
│   └── brick-beta.css              # Styles Brique 3
│
├── 📂 js/
│   ├── config.js                   # Configuration JS
│   ├── utils.js                    # Utilitaires partagés
│   ├── brick-tools.js              # Logique Brique 1
│   ├── brick-ideas.js              # Logique Brique 2
│   └── brick-beta.js               # Logique Brique 3
│
├── 📂 api/
│   └── api.php                     # API backend (endpoints)
│
└── 📂 sql/
    ├── schema.sql                  # Schéma complet BDD
    ├── analyse-simple.sql          # Script d'analyse
    └── mise-a-jour-incrementale.sql # Mise à jour
```

---

## 🎯 Avantage de cette architecture

### ✅ Travail isolé par brique

**Pour modifier la Brique 1 (Outils)** → Éditer uniquement :
- `briques/brick-tools.php` (HTML/PHP)
- `js/brick-tools.js` (JavaScript)
- `css/brick-tools.css` (Styles)

**Pour modifier la Brique 2 (Idées)** → Éditer uniquement :
- `briques/brick-ideas.php`
- `js/brick-ideas.js`
- `css/brick-ideas.css`

**Pour modifier la Brique 3 (Beta)** → Éditer uniquement :
- `briques/brick-beta.php`
- `js/brick-beta.js`
- `css/brick-beta.css`

### ✅ Pas d'impact sur les autres briques

Chaque brique est **autonome**. Modifier une brique n'affecte pas les autres.

---

## 🚀 Installation

### 1. Upload des fichiers

```bash
# Copier tout le contenu vers :
/var/www/html/ifen_html/toolbox/
```

### 2. Configuration

Éditer `includes/config.php` :

```php
// Connexion base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ifenlmsmain1db');
define('DB_USER', 'votre_user');
define('DB_PASS', 'votre_password');

// Chemin de base
define('BASE_URL', '/ifen_html/toolbox');
```

### 3. Base de données

Exécuter dans phpMyAdmin :
```sql
-- Fichier : sql/schema.sql
```

### 4. Test

Ouvrir : `https://lms.ifen.lu/ifen_html/toolbox/`

---

## 🧱 Comment ajouter une nouvelle brique

### 1. Créer le fichier PHP

```php
// briques/brick-nouveau.php
<?php
/**
 * BRIQUE X : NOUVELLE BRIQUE
 * ==========================
 */
?>
<div class="brick-container brick-nouveau" id="brick-nouveau">
    <!-- Votre HTML ici -->
</div>
```

### 2. Créer le fichier JS

```javascript
// js/brick-nouveau.js
const BrickNouveau = {
    async init(containerId) {
        // Votre logique ici
    }
};
```

### 3. Créer le fichier CSS

```css
/* css/brick-nouveau.css */
.brick-nouveau {
    /* Vos styles ici */
}
```

### 4. Inclure dans index.php

```php
<!-- Dans index.php -->
<section id="nouveau-section" class="brick-section">
    <?php include __DIR__ . '/briques/brick-nouveau.php'; ?>
</section>
```

### 5. Charger les fichiers

Dans `includes/header.php` :
```php
<link rel="stylesheet" href="<?php echo asset('css/brick-nouveau.css'); ?>">
```

Dans `includes/footer.php` :
```php
<script src="<?php echo asset('js/brick-nouveau.js'); ?>"></script>
```

---

## 📋 Les 3 briques existantes

### 🔧 Brique 1 : Outils Disponibles

**Fichiers :**
- `briques/brick-tools.php`
- `js/brick-tools.js`
- `css/brick-tools.css`

**Fonctionnalités :**
- Vue grille + slider horizontal
- Filtres (type, catégorie, recherche)
- Favoris
- Modal détails + tutoriel
- Review Logs popup

---

### 💡 Brique 2 : Idées & Votes

**Fichiers :**
- `briques/brick-ideas.php`
- `js/brick-ideas.js`
- `css/brick-ideas.css`

**Fonctionnalités :**
- Formulaire de proposition
- Système de votes
- Workflow de programmation
- Vue Kanban par phase

---

### 🧪 Brique 3 : Beta Testing

**Fichiers :**
- `briques/brick-beta.php`
- `js/brick-beta.js`
- `css/brick-beta.css`

**Fonctionnalités :**
- Liste des outils en beta
- Inscription testeurs
- Feedback structuré (Bug, Suggestion, Question, Bravo)
- Notation par étoiles
- Review Logs popup

---

## 🔌 API Endpoints

L'API est dans `api/api.php`. Endpoints principaux :

| Action | Méthode | Description |
|--------|---------|-------------|
| `stats` | GET | Statistiques globales |
| `tools` | GET | Liste des outils |
| `ideas` | GET | Liste des idées |
| `vote` | POST | Voter pour une idée |
| `beta_register` | POST | Inscription beta |
| `beta_feedback` | POST | Envoyer feedback |

---

## 🎨 Personnalisation

### Couleurs IFEN

Dans `css/base.css` :
```css
:root {
    --primary: #20164D;      /* Violet IFEN */
    --secondary: #00b2bb;    /* Cyan IFEN */
    --accent: #ffc107;       /* Jaune IFEN */
}
```

### Configuration JS

Dans `js/config.js` :
```javascript
const TOOLBOX_CONFIG = {
    api: { baseUrl: '/ifen_html/toolbox/api/api.php' },
    // ...
};
```

---

## 📱 Responsive

Breakpoints :
- Desktop : > 1024px (slider 3 cartes)
- Tablette : 768-1024px (slider 2 cartes)
- Mobile : < 768px (slider 1 carte)

---

## ✅ Checklist déploiement

- [ ] Fichiers uploadés
- [ ] `includes/config.php` configuré
- [ ] Base de données créée (`sql/schema.sql`)
- [ ] Test sur Desktop
- [ ] Test sur Mobile
- [ ] API fonctionnelle

---

## 📞 Support

**Email** : support@ifen.lu  
**Version** : 2.0.0 PHP  
**Date** : Novembre 2024

---

## 📝 Résumé

| Élément | Fichier(s) |
|---------|------------|
| Configuration | `includes/config.php` |
| En-tête | `includes/header.php` |
| Pied de page | `includes/footer.php` |
| Brique Outils | `briques/brick-tools.php` + `js/brick-tools.js` + `css/brick-tools.css` |
| Brique Idées | `briques/brick-ideas.php` + `js/brick-ideas.js` + `css/brick-ideas.css` |
| Brique Beta | `briques/brick-beta.php` + `js/brick-beta.js` + `css/brick-beta.css` |
| API | `api/api.php` |
| Base de données | `sql/schema.sql` |

**1 brique = 3 fichiers (PHP + JS + CSS)** → Modification isolée ! 🎯
