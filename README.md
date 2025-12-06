# 🎓 Système de Gestion des Services Étudiants - ENSA

Application web complète pour la gestion des demandes de documents administratifs pour les étudiants de l'ENSA (École Nationale des Sciences Appliquées).

## 📋 Fonctionnalités

### 👨‍🎓 Espace Étudiant
- **Connexion** : Email institutionnel + Numéro Apogée (sans mot de passe)
- **Demande de documents** :
  - Attestation de scolarité
  - Attestation de réussite
  - Relevé de notes
  - Convention de stage (PFA/PFE)
- **Suivi des demandes** : Statut en temps réel
- **Téléchargement de documents** : PDF générés avec QR code
- **Réclamations** : Formulaire de réclamation en cas de problème

### 👨‍💼 Espace Administrateur
- **Dashboard** : Statistiques des demandes
- **Gestion des demandes** : Acceptation/Refus avec justification
- **Génération de documents** : PDF avec logo universitaire
- **Envoi d'emails** : Notification automatique aux étudiants
- **Historique** : Filtres et recherche avancée
- **Gestion des réclamations** : Réponse aux étudiants

## 🏗️ Architecture

### Backend (PHP)
- **API REST** : Endpoints pour toutes les fonctionnalités
- **Base de données MySQL** : Structure complète ENSA (CPI1, CPI2, 1A, 2A, 3A, M1, M2)
- **Génération PDF** : TCPDF avec QR code
- **Validation métier** : Vérifications strictes selon les règles ENSA

### Frontend (React + Vite)
- **Interface moderne** : Tailwind CSS + Framer Motion
- **Gestion d'état** : React Query pour les données
- **Authentification** : Context API
- **Responsive** : Design adaptatif

## 🚀 Installation

### Prérequis
- PHP 8.0+
- MySQL 5.7+
- Node.js 18+
- Composer

### Backend

```bash
cd backend
composer install
```

Configurer la base de données dans `config/database.php` :
```php
$host = 'localhost';
$dbname = 'student_admin_db';
$username = 'root';
$password = '';
```

Importer la base de données :
```bash
mysql -u root -p student_admin_db < database_complete_ensa.sql
```

Démarrer le serveur :
```bash
php -S localhost:8000 router.php
```

### Frontend

```bash
cd frontend
npm install
npm run dev
```

L'application sera accessible sur `http://localhost:3000`

## 📊 Base de Données

### Structure ENSA
- **CPI1, CPI2** : Cycle Préparatoire Intégré
- **1A, 2A, 3A** : Cycle Ingénieur (avec filières : Génie Info, Génie Mécanique, etc.)
- **M1, M2** : Master

### Tables principales
- `etudiant` : Informations des étudiants
- `demande` : Demandes de documents
- `inscription` : Inscriptions par année
- `resultat_annee` : Résultats académiques
- `note` : Notes détaillées par module
- `stage` : Stages PFA/PFE
- `administrateur` : Comptes administrateurs

## 🧪 Tests

### Étudiant de test recommandé
- **Numéro Apogée** : `B67890`
- **Email** : `fatima.benali@student.ensa.ma`
- **CIN** : `CD234567`

Cet étudiant permet de tester toutes les fonctionnalités :
- ✅ Attestation de scolarité (toutes années)
- ✅ Attestation de réussite (années validées)
- ✅ Relevé de notes (tous semestres)
- ✅ Convention de stage PFA (2A)

Voir `backend/GUIDE_TEST_COMPLET.md` pour les détails.

### Compte Admin
- **Login** : `admin@ensa.ma`
- **Password** : `admin123`

## 📝 Règles de Validation

### Attestation de Réussite
- ✅ L'étudiant doit avoir un statut "Réussi" pour l'année demandée
- ❌ Refusée si statut "En cours" ou "Ajourné"

### Convention de Stage
- **PFA (2A)** : Durée 8-12 semaines (2-3 mois)
- **PFE (3A)** : Durée 16-24 semaines (4-6 mois)
- ❌ Refusée si l'étudiant n'est pas en 2A ou 3A

### Relevé de Notes
- ✅ Doit avoir des notes pour l'année/semestre demandé
- Affiche les modules, notes, coefficients et mentions

## 🔒 Sécurité

- Validation côté serveur pour toutes les demandes
- Vérification des données (email, Apogée, CIN) avant création
- QR code sur les PDF pour vérification d'authenticité
- Protection CORS configurée

## 📦 Technologies

- **Backend** : PHP 8.4, MySQL, TCPDF
- **Frontend** : React 18, Vite, Tailwind CSS, Framer Motion
- **API** : REST avec CORS
- **PDF** : TCPDF avec QR code

## 👥 Auteurs

Projet développé pour l'ENSA - Système de gestion des services étudiants.

## 📄 Licence

Projet académique - ENSA
