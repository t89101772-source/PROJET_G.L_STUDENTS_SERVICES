# 🧪 Guide de Test Complet - Toutes les Attestations

## 👤 Étudiant Recommandé pour Tester TOUT

### **Étudiant : B67890 (Benali Fatima)**

**Informations de connexion :**
- **Numéro Apogée :** `B67890`
- **Email :** `fatima.benali@student.ensa.ma`
- **CIN :** `CD234567`

---

## 📋 Parcours Complet de l'Étudiant B67890

### ✅ Inscriptions (Historique complet)
- **2020-2021 :** CPI1 (Cycle Préparatoire Intégré) - ✅ Diplômé
- **2021-2022 :** CPI2 (Cycle Préparatoire Intégré) - ✅ Diplômé
- **2022-2023 :** 1A Génie Mécanique - ✅ Diplômé
- **2023-2024 :** 2A Génie Mécanique - ✅ Diplômé
- **2024-2025 :** 2A Génie Mécanique - 📚 **Inscrit** (actuellement)

### ✅ Résultats Validés
- **2020-2021 CPI1 :** Réussi - Moyenne 14.0/20 - Mention "Assez Bien"
- **2021-2022 CPI2 :** Réussi - Moyenne 15.0/20 - Mention "Bien"
- **2022-2023 1A :** Réussi - Moyenne 14.5/20 - Mention "Assez Bien"
- **2023-2024 2A :** Réussi - Moyenne 15.0/20 - Mention "Bien"
- **2024-2025 2A :** En cours - Moyenne S1: 15.5/20

### ✅ Notes Disponibles
- **2022-2023 (1A) :** Notes S1 et S2 disponibles
- **2023-2024 (2A) :** Notes S1 et S2 disponibles
- **2024-2025 (2A) :** Notes S1 disponibles (S2 en cours)

### ✅ Stage Approuvé
- **2024-2025 :** Stage PFA (2A) - DataSoft Solutions
- **Durée :** 8 semaines (2 mois)
- **Dates :** 15/06/2025 - 15/08/2025
- **Statut :** Approuvé

---

## 🧪 Tests à Effectuer

### 1. ✅ **Attestation de Scolarité**

**Test 1 : Année en cours (2024-2025)**
```
Type : Attestation de scolarité
Année universitaire : 2024-2025 (optionnel)
```
**Résultat attendu :** ✅ Acceptée
- Niveau : 2A Génie Mécanique
- Statut : Inscrit

**Test 2 : Année précédente (2023-2024)**
```
Type : Attestation de scolarité
Année universitaire : 2023-2024
```
**Résultat attendu :** ✅ Acceptée
- Niveau : 2A Génie Mécanique
- Statut : Diplômé

---

### 2. ✅ **Attestation de Réussite**

**Test 1 : 2A - Année 2023-2024 (Réussi)**
```
Type : Attestation de réussite
Année universitaire : 2023-2024
Niveau : 2A
```
**Résultat attendu :** ✅ Acceptée
- Moyenne : 15.0/20
- Mention : Bien
- Statut : Réussi

**Test 2 : 1A - Année 2022-2023 (Réussi)**
```
Type : Attestation de réussite
Année universitaire : 2022-2023
Niveau : 1A
```
**Résultat attendu :** ✅ Acceptée
- Moyenne : 14.5/20
- Mention : Assez Bien

**Test 3 : 2A - Année 2024-2025 (En cours) - ❌ DOIT ÉCHOUER**
```
Type : Attestation de réussite
Année universitaire : 2024-2025
Niveau : 2A
```
**Résultat attendu :** ❌ Refusée
- Message : "L'étudiant est actuellement en train de suivre cette année. Les résultats ne sont pas encore finalisés (statut: En cours)."

---

### 3. ✅ **Relevé de Notes**

**Test 1 : Année 2023-2024 - Semestre S1**
```
Type : Relevé de notes
Année universitaire : 2023-2024
Semestre : S1
```
**Résultat attendu :** ✅ Acceptée
- Affiche les notes du S1 de 2A Génie Mécanique
- Modules : Mécanique avancée, CAO/DAO

**Test 2 : Année 2023-2024 - Semestre S2**
```
Type : Relevé de notes
Année universitaire : 2023-2024
Semestre : S2
```
**Résultat attendu :** ✅ Acceptée
- Affiche les notes du S2 de 2A Génie Mécanique
- Modules : Fabrication mécanique, Automatique

**Test 3 : Année 2023-2024 - Tous les semestres**
```
Type : Relevé de notes
Année universitaire : 2023-2024
Semestre : Tous
```
**Résultat attendu :** ✅ Acceptée
- Affiche toutes les notes (S1 + S2)
- Moyenne générale calculée

**Test 4 : Année 2024-2025 - Semestre S1 (En cours)**
```
Type : Relevé de notes
Année universitaire : 2024-2025
Semestre : S1
```
**Résultat attendu :** ✅ Acceptée
- Affiche les notes partielles du S1
- Note : L'année n'est pas terminée

---

### 4. ✅ **Convention de Stage**

**Test 1 : Stage PFA (2A) - Valide**
```
Type : Convention de stage
Nom entreprise : DataSoft Solutions
Adresse entreprise : Rabat, Hay Riad, Maroc
Durée : 8 semaines
Date début : 2025-06-15
Date fin : 2025-08-15
```
**Résultat attendu :** ✅ Acceptée
- Étudiant en 2A (PFA)
- Durée : 8 semaines (dans la limite 8-12 semaines)
- Stage approuvé dans la BDD

**Test 2 : Stage avec durée incorrecte - ❌ DOIT ÉCHOUER**
```
Type : Convention de stage
Nom entreprise : DataSoft Solutions
Date début : 2025-06-15
Date fin : 2025-07-15 (4 semaines seulement)
```
**Résultat attendu :** ❌ Refusée
- Message : "Pour un stage PFA (2A - 4ème année), la durée doit être entre 2 et 3 mois (8-12 semaines). Durée actuelle : 4 semaines."

**Test 3 : Étudiant en 1A - ❌ DOIT ÉCHOUER**
```
Utiliser l'étudiant A12345 (en 1A)
Type : Convention de stage
```
**Résultat attendu :** ❌ Refusée
- Message : "Les conventions de stage ne sont disponibles que pour les étudiants en 2A (PFA - 4ème année) ou 3A (PFE - 5ème année). Vous êtes actuellement en 1A."

---

## 🎯 Étudiant Alternatif : E78901 (Omar El Fassi)

**Pour tester PFE (3A) :**
- **Numéro Apogée :** `E78901`
- **Email :** `omar.elfassi@student.ensa.ma`
- **CIN :** `IJ567890`

**Parcours :**
- ✅ 3A Génie Informatique (2023-2024) - Réussi
- ✅ M1 Génie Informatique (2024-2025) - En cours
- ✅ Stage PFE terminé (2023-2024)

**Test Convention de Stage PFE :**
```
Type : Convention de stage
Nom entreprise : TechCorp Maroc
Date début : 2024-02-15
Date fin : 2024-07-15
Durée : 20 semaines (5 mois)
```
**Résultat attendu :** ✅ Acceptée (si l'année est 2023-2024)

---

## 📝 Résumé des Tests

| Type d'Attestation | Étudiant | Année | Résultat |
|-------------------|----------|-------|----------|
| **Scolarité** | B67890 | 2024-2025 | ✅ Acceptée |
| **Scolarité** | B67890 | 2023-2024 | ✅ Acceptée |
| **Réussite** | B67890 | 2023-2024 (2A) | ✅ Acceptée |
| **Réussite** | B67890 | 2024-2025 (2A) | ❌ Refusée (En cours) |
| **Relevé** | B67890 | 2023-2024 (S1) | ✅ Acceptée |
| **Relevé** | B67890 | 2023-2024 (Tous) | ✅ Acceptée |
| **Convention** | B67890 | 2024-2025 (PFA) | ✅ Acceptée |
| **Convention** | A12345 | 2024-2025 (1A) | ❌ Refusée |

---

## 🚀 Étapes pour Tester

1. **Connectez-vous** avec l'étudiant B67890
2. **Créez une demande** pour chaque type d'attestation
3. **Vérifiez les validations** (acceptation/refus)
4. **Générez les PDFs** pour les demandes acceptées
5. **Téléchargez** les documents générés

---

## ⚠️ Cas d'Erreur à Tester

1. ❌ Attestation de réussite pour une année "En cours"
2. ❌ Convention de stage pour un étudiant en 1A
3. ❌ Convention de stage avec durée incorrecte
4. ❌ Relevé de notes pour une année sans notes

