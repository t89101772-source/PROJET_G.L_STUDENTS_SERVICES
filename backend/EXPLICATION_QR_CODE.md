# 📱 Explication du Système de Vérification par QR Code

## 🎯 Comment ça fonctionne ?

### 1. **Génération du QR Code dans le PDF**
Quand l'admin génère un document PDF, un QR code est automatiquement ajouté en bas du document. Ce QR code contient une URL unique qui pointe vers une page de vérification.

### 2. **Scan du QR Code**
Quand quelqu'un scanne le QR code avec son téléphone :
- Le téléphone lit l'URL encodée dans le QR code
- Il ouvre automatiquement cette URL dans le navigateur
- La page de vérification s'affiche avec toutes les informations du document

### 3. **Page de Vérification**
La page affiche :
- ✅ Statut "DOCUMENT AUTHENTIQUE"
- 📄 Type de document
- 👤 Informations de l'étudiant (nom, Apogée, CIN, email)
- 📅 Date d'émission
- 🔢 Code de vérification unique
- ⚠️ Avertissement contre la falsification

---

## 🔧 Configuration

### **En Développement (actuel)**
```
URL du QR Code: http://localhost:8000/verify_document.php?id=XXX
```

### **En Production (à configurer)**
Vous devez changer l'URL dans `generate_document.php` :

```php
// Dans generate_document.php, ligne ~250
$baseUrl = 'https://votre-domaine.com'; // Changez cette ligne
$verificationUrl = $baseUrl . '/verify_document.php?id=' . $demande['id'];
```

---

## ✅ Avantages

1. **Authenticité** : Permet de vérifier qu'un document est réel et émis par l'université
2. **Sécurité** : Impossible de falsifier le QR code (il pointe vers une base de données sécurisée)
3. **Transparence** : N'importe qui peut scanner et vérifier le document
4. **Traçabilité** : Chaque document a un code unique

---

## 🚀 Utilisation

1. **Générer un document** : L'admin clique sur "Générer PDF"
2. **Le PDF contient le QR code** : En bas à droite du document
3. **Scanner avec un téléphone** : Utiliser l'appareil photo ou une app de scan QR
4. **Vérification automatique** : La page s'ouvre avec les détails du document

---

## 📝 Exemple d'URL générée

```
http://localhost:8000/verify_document.php?id=7
```

Cette URL affiche toutes les informations de la demande #7 si elle est acceptée.

---

## 🔒 Sécurité

- ✅ Seuls les documents **acceptés** peuvent être vérifiés
- ✅ Les documents refusés ou en attente ne sont pas accessibles
- ✅ L'URL contient un ID unique qui ne peut pas être deviné facilement
- ✅ La page vérifie que le document existe dans la base de données

---

## 🎨 Personnalisation

Vous pouvez modifier le design de la page de vérification dans :
```
backend/verify_document.php
```

Changez les couleurs, le logo, ou ajoutez d'autres informations selon vos besoins.

---

## 🌐 Pour la Production

1. **Achetez un domaine** (ex: `uae-verification.ma`)
2. **Configurez le serveur** pour héberger `verify_document.php`
3. **Changez l'URL dans `generate_document.php`**
4. **Testez** en scannant un QR code généré

---

## 💡 Idées d'Amélioration

- Ajouter un logo de l'université dans le QR code
- Ajouter une date d'expiration pour les documents
- Envoyer un email de notification quand un document est scanné
- Statistiques de scans par document

