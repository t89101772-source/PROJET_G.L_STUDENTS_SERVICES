## 🚀 Guide rapide – Lancer et utiliser l’application UnivDocs (local)

Guide simple pour exécuter le **backend (PHP)** et le **frontend (React)** sur votre machine.

---

### 1. Prérequis à installer (une seule fois)

Sur votre ordinateur, il faut avoir :

- **PHP 8+**
- **MySQL** (XAMPP / WAMP / MAMP ou serveur MySQL classique)
- **Node.js 18+** (inclut `npm`)
- **Composer** (pour installer PHPMailer / TCPDF)

Pour vérifier :

```bash
php -v
node -v
npm -v
composer -V
```

---

### 2. Préparer la base de données

1. Démarrer MySQL (XAMPP/WAMP ou autre)
2. Créer la base si besoin :

```bash
mysql -u root -p
CREATE DATABASE student_admin_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

 

4. Vérifier la configuration dans `backend/config/database.php` :

```php
$host = '127.0.0.1'; // ou 'localhost'
$dbname = 'student_admin_db';
$username = 'root';
$password = '';      // mettre votre mot de passe MySQL si vous en avez un
```

---

### 3. Lancer le backend (API PHP)

Dans un **premier terminal** :

```bash
cd backend
composer install   # une seule fois
php -S localhost:8000 router.php
```

- Le backend sera accessible sur `http://localhost:8000`
- Les routes API sont sous `http://localhost:8000/api/...`

Laisser ce terminal **ouvert** pendant que vous utilisez l’application.

---

### 4. Lancer le frontend (React / Vite)

Dans un **deuxième terminal** :

```bash
cd frontend
npm install       # une seule fois
npm run dev
```

Vite va afficher une URL du type :

- `http://localhost:3000/`

Ouvrir cette URL dans le navigateur.

---

### 5. Se connecter comme administrateur

1. Aller sur la page d’accueil : `http://localhost:3000/`
2. Cliquer sur le bouton **Espace admin** / **Admin** (selon ton UI)
3. Utiliser le compte admin configuré dans la base (par défaut dans le README initial, à adapter à votre SQL) :

Exemple typique :

- **Login** : `admin`
- **Mot de passe** : `admin123`  

(Voir le contenu de `backend/database_OPTIMISE_FINAL.sql` dans la table `administrateur` pour les vrais identifiants.) 

---

### 6. Tester un scénario complet

1. **Créer une demande** depuis la page d’accueil (formulaire public) :
   - Renseigner : email, Apogée, CIN, type de document, etc.
2. Aller sur `http://localhost:3000/admin/dashboard` et se connecter.
3. Dans **Gestion des Demandes** :
   - Filtrer / trouver la demande
   - Cliquer sur **Détails** (optionnel)
   - Cliquer sur **Accepter** → le PDF est généré
   - Cliquer sur **Consulter** → PDF dans une modale + possibilité d’imprimer
   - Cliquer sur **Envoyer email** → envoi du PDF à l’étudiant (si SMTP bien configuré)
4. Tester également une **Réclamation** :
   - Depuis la page d’accueil → créer une réclamation
   - Dans le Dashboard → **Gestion des Réclamations** → Voir / Répondre / Rejeter / Consulter document

---

### 7. Arrêter l’application

Pour fermer l’application :

- Dans le terminal **frontend** : `Ctrl + C`
- Dans le terminal **backend** : `Ctrl + C`

La base de données reste intacte, vous pouvez relancer plus tard en refaisant les étapes 3 et 4 seulement.

---

### 8. Résumé ultra-court pour le groupe

1. **Une fois** : installer PHP, MySQL, Node, Composer + importer `database_OPTIMISE_FINAL.sql`.
2. **À chaque session** :
   - Terminal 1 :
     - `cd backend`
     - `php -S localhost:8000 router.php`
   - Terminal 2 :
     - `cd frontend`
     - `npm run dev`
3. Ouvrir `http://localhost:3000` dans le navigateur.
4. Se connecter en admin pour gérer les demandes et réclamations.


