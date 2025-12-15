## 🔐 Guide rapide – Configurer VOTRE email pour l'envoi des documents

Ce guide explique comment chaque membre du groupe peut mettre **son propre email + mot de passe d'application** pour que les emails partent avec son adresse (par exemple `prenom.nom@gmail.com`).

---

### 1. Où se trouve la configuration d’email ?

Tout se passe dans ce fichier du backend :

- `backend/config/email_template.php`

Dans ce fichier, la partie importante est :

- `\$mail->Host`, `\$mail->Username`, `\$mail->Password`, `\$mail->setFrom(...)`

Chaque membre peut mettre **son propre compte Gmail** ici.

---

### 2. Créer un mot de passe d’application Gmail (obligatoire)

Pour des raisons de sécurité, on n’utilise **jamais** le vrai mot de passe Gmail dans le code, mais un **mot de passe d’application**.

#### Étapes (chaque membre fait ça sur son compte Google) :

1. Aller sur `https://myaccount.google.com/`
2. Menu **Sécurité**  
3. Activer **Validation en 2 étapes** (si ce n’est pas déjà fait)
4. Ensuite, dans la section **Mots de passe des applications** :
   - Choisir **Application** → `Autre (nom personnalisé)` → écrire par ex. `UnivDocs`
   - Choisir **Appareil** → `Ordinateur Windows` (ou autre)
   - Cliquer sur **Générer**
5. Google affiche un **mot de passe de 16 caractères** (exemple : `abcd efgh ijkl mnop`)
   - Copier ce mot de passe (sans les espaces)
   - **Le garder secret** (ne pas l’envoyer sur WhatsApp / Discord, etc.)

---

### 3. Modifier la configuration dans le projet

Ouvrir le fichier :

- `backend/config/email_template.php`

Chercher cette partie (autour des lignes 130–155) :

```php
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;

// Configuration Gmail - À MODIFIER AVEC VOS IDENTIFIANTS
$mail->Username = 'votre email Gmail'; // REMPLACER par votre email Gmail
$mail->Password = 'votre mot de passe';   // REMPLACER par votre mot de passe d'application Gmail (16 caractères)
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

// Expéditeur
$mail->setFrom('votre email Gmail', 'UnivDocs');
$mail->addReplyTo('votre email Gmail', 'Support UnivDocs');
```

#### À modifier pour VOUS :

1. **Email**
   - Remplacer partout `votre email Gmail` par **votre propre Gmail**  
     Exemple :
   - `prenom.nom@gmail.com`

2. **Mot de passe d’application**
   - Remplacer `niqzbihrzjsfkals` par le **mot de passe d’application de 16 caractères** généré à l’étape 2.

Exemple :

```php
$mail->Username = 'prenom.nom@gmail.com';
$mail->Password = 'abcdabcdabcdabcd'; // votre mot de passe d'application

$mail->setFrom('prenom.nom@gmail.com', 'UnivDocs');
$mail->addReplyTo('prenom.nom@gmail.com', 'Support UnivDocs');
```

> ⚠️ **IMPORTANT** : Ne pas commiter votre vrai mot de passe d’application sur GitHub public.
> - Ce projet est académique : OK pour un usage local / rendu, mais à éviter en prod réelle.

---

### 4. Redémarrer le backend

Après modification du fichier `email_template.php` :

1. Ouvrir un terminal dans le dossier `backend`
2. Lancer (ou relancer) le serveur PHP :

```bash
php -S localhost:8000 router.php
```

3. Tester l’envoi d’email :
   - Accepter une demande dans le **Dashboard admin**
   - Puis cliquer sur **Envoyer email**
   - Vérifier dans la **boîte d’envoi Gmail** (partie Envoyés) que le mail est bien parti.

---

### 5. Résumé pour chaque membre du groupe

1. Créer un **mot de passe d’application Gmail** (compte perso)
2. Éditer `backend/config/email_template.php` :
   - Mettre **son Gmail** dans `Username`, `setFrom`, `addReplyTo`
   - Mettre **son mot de passe d’appli** dans `Password`
3. Redémarrer le backend :
   - `php -S localhost:8000 router.php`
4. Tester l’envoi d’email depuis le Dashboard admin

Chacun peut ainsi utiliser **son propre compte Gmail** pour les tests et les démos.


