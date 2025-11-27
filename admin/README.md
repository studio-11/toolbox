# IFEN Toolbox Admin

Interface d'administration pour la plateforme IFEN Toolbox.

## 📁 Structure

```
admin/
├── api/
│   └── api.php          # API AJAX pour opérations async
├── css/
│   └── admin.css        # Styles principaux (IFEN branding)
├── js/
│   └── admin.js         # JavaScript principal
├── includes/
│   ├── config.php       # Configuration & connexion DB
│   ├── header.php       # Header commun
│   └── footer.php       # Footer commun
├── sql/
│   └── migration.sql    # Script de migration DB
├── index.php            # Dashboard
├── login.php            # Page de connexion
├── logout.php           # Déconnexion
├── works.php            # Gestion des travaux & statut plateforme
├── tools.php            # Gestion des outils
├── categories.php       # Gestion des catégories
├── ideas.php            # Modération des idées
└── beta.php             # Gestion beta testing
```

## 🚀 Installation

### 1. Copier les fichiers
```bash
cp -r admin/ /export/hosting/men/ifen/htdocs-lms/ifen_html/toolbox/admin/
```

### 2. Exécuter la migration SQL
```bash
mysql -u ifen -p ifenlmsdb < admin/sql/migration.sql
```

### 3. Configurer les credentials
Le fichier `includes/config.php` charge automatiquement les credentials depuis :
```
/export/hosting/men/ifen/htdocs-lms/ifen_credentials/db_credentials_learningsphere.php
```

### 4. Accéder à l'admin
```
https://learningsphere.ifen.lu/ifen_html/toolbox/admin/
```

## 🔐 Authentification

**Compte par défaut :**
- Email: `admin@ifen.lu`
- Password: `admin2024`

⚠️ **Changez ce mot de passe en production !**

Pour ajouter des admins, insérez dans la table `toolbox_admins` :
```sql
INSERT INTO toolbox_admins (name, email, password_hash, role) VALUES
('Nouveau Admin', 'email@ifen.lu', '$2y$10$...hash...', 'admin');
```

Générer un hash de mot de passe :
```php
echo password_hash('votre_mot_de_passe', PASSWORD_DEFAULT);
```

## 🎨 Charte Graphique IFEN

### Couleurs principales
| Couleur | Hex | Usage |
|---------|-----|-------|
| Violet IFEN | `#502b85` | Primaire, accents |
| Cyan IFEN | `#17a2b8` | Secondaire, liens |
| Jaune IFEN | `#ffc107` | Highlights |

### Statuts Plateforme
| Statut | Couleur | Badge |
|--------|---------|-------|
| Opérationnel | `#28a745` | vert |
| Maintenance | `#fd7e14` | orange |
| Mise à jour | `#007bff` | bleu |
| Panne partielle | `#dc3545` | rouge |
| Panne majeure | `#dc3545` | rouge |

### Statuts Travaux
| Statut | Couleur |
|--------|---------|
| Planifié | `#502b85` (violet) |
| Non planifié | `#fd7e14` (orange) |
| En cours | `#17a2b8` (cyan) |
| Terminé | `#28a745` (vert) |
| Annulé | `#6c757d` (gris) |

### Priorités
| Priorité | Couleur |
|----------|---------|
| Basse | vert |
| Moyenne | jaune |
| Haute | orange |
| Critique | rouge |

## 📱 Fonctionnalités

### Dashboard (`index.php`)
- Vue d'ensemble des statistiques
- Statut de la plateforme
- Travaux récents
- Idées récentes
- Actions rapides

### Travaux (`works.php`)
- Gestion du statut plateforme (opérationnel, maintenance, etc.)
- CRUD travaux (maintenance, mises à jour, features, bugfixes)
- Filtres par statut et type
- Historique des changements

### Outils (`tools.php`)
- CRUD outils
- Association aux catégories
- Gestion des statuts (stable, new, beta, deprecated)
- Outils mis en avant (featured)
- Ordre d'affichage personnalisable

### Catégories (`categories.php`)
- CRUD catégories
- Icônes FontAwesome
- Couleurs personnalisées
- Compteur d'outils par catégorie

### Idées (`ideas.php`)
- Modération des idées soumises
- Workflow: soumise → en revue → planifiée → en cours → réalisée
- Réponse admin aux utilisateurs
- Statistiques par statut

### Beta Testing (`beta.php`)
- Gestion des programmes beta
- Inscription/approbation des testeurs
- Collecte de feedbacks
- Statuts: recrutement, actif, pausé, terminé

## 🔧 API Endpoints

L'API (`api/api.php`) expose les actions suivantes :

### Platform Status
- `get_platform_status` - Obtenir le statut actuel
- `update_platform_status` - Mettre à jour le statut

### Works
- `get_works` - Liste des travaux (filtrable)
- `update_work_status` - Changer le statut d'un travail
- `delete_work` - Supprimer un travail

### Tools
- `get_tools` - Liste des outils (filtrable)
- `toggle_tool_featured` - Basculer le statut featured
- `update_tool_order` - Modifier l'ordre
- `delete_tool` - Supprimer un outil

### Categories
- `get_categories` - Liste des catégories
- `delete_category` - Supprimer une catégorie

### Ideas
- `get_ideas` - Liste des idées (filtrable)
- `update_idea_status` - Changer statut + réponse admin
- `delete_idea` - Supprimer une idée

### Beta
- `get_beta_programs` - Liste des programmes
- `get_beta_testers` - Testeurs d'un programme
- `update_tester_status` - Approuver/rejeter testeur
- `delete_beta_program` - Supprimer un programme

### Statistics
- `get_stats` - Statistiques globales

## 🔒 Sécurité

- Sessions PHP pour l'authentification
- Mots de passe hashés (bcrypt)
- Protection CSRF via formulaires POST
- Échappement des sorties HTML (`htmlspecialchars`)
- Requêtes préparées PDO (anti SQL injection)
- API protégée par authentification

## 📝 Notes

- Police: Barlow Semi Condensed
- Icons: FontAwesome 6.4
- Compatible: MySQL 5.7+ / MariaDB 10.2+
- PHP: 7.4+

## 🐛 Debug

Activer le mode debug dans `includes/config.php` :
```php
define('DEBUG_MODE', true);
```

---

**IFEN - Institut de Formation de l'Éducation Nationale**
