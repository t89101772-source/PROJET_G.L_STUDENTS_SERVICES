-- =====================================================
-- BASE DE DONNÉES - UNIVERSITÉ CITÉ DES SCIENCES
-- Système de Gestion Documentaire Académique
-- Version FINALE - Sans crédits, coefficients = 1
-- Modules réels - Cycle Préparatoire (sans filière) + Cycle Ingénieur (avec filière)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Supprimer la base de données si elle existe
DROP DATABASE IF EXISTS `student_admin_db`;

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS `student_admin_db` 
  DEFAULT CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

USE `student_admin_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- SUPPRESSION DE TOUTES LES TABLES
-- =====================================================

DROP TABLE IF EXISTS `resultat_semestre`;
DROP TABLE IF EXISTS `reclamation`;
DROP TABLE IF EXISTS `demande`;
DROP TABLE IF EXISTS `note`;
DROP TABLE IF EXISTS `resultat_annee`;
DROP TABLE IF EXISTS `inscription`;
DROP TABLE IF EXISTS `module`;
DROP TABLE IF EXISTS `niveau`;
DROP TABLE IF EXISTS `filiere`;
DROP TABLE IF EXISTS `etudiant`;
DROP TABLE IF EXISTS `administrateur`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- CRÉATION DES TABLES OPTIMISÉES
-- =====================================================

-- Table: administrateur
CREATE TABLE `administrateur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: etudiant
CREATE TABLE `etudiant` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apogee_number` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cin` varchar(20) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `apogee_number` (`apogee_number`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cin` (`cin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: filiere
CREATE TABLE `filiere` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: niveau
CREATE TABLE `niveau` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(15) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `numero_semestre` int(11) NOT NULL,
  `annee_academique` int(11) NOT NULL,
  `type_cycle` enum('Préparatoire','Cycle Ingénieur') NOT NULL,
  `annee_cycle` int(11) DEFAULT NULL,
  `semestre_annee` enum('S1','S2') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: module (SANS crédit, coefficient = 1)
CREATE TABLE `module` (
  `code_module` varchar(20) NOT NULL,
  `nom_module` varchar(200) NOT NULL,
  `filiere_id` int(11) DEFAULT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  `coefficient` decimal(4,2) NOT NULL DEFAULT 1.00,
  `type` enum('Cours','TP','TD','Projet','Stage') DEFAULT 'Cours',
  `description` text DEFAULT NULL,
  PRIMARY KEY (`code_module`),
  KEY `idx_filiere_id` (`filiere_id`),
  KEY `idx_niveau_id` (`niveau_id`),
  CONSTRAINT `fk_module_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filiere` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_module_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: inscription
CREATE TABLE `inscription` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apogee_number` varchar(20) NOT NULL,
  `filiere_id` int(11) DEFAULT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  `annee_universitaire` varchar(20) NOT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `filiere` varchar(100) DEFAULT NULL,
  `statut` enum('Inscrit','Réinscrit','Diplômé','Abandon') NOT NULL DEFAULT 'Inscrit',
  `date_inscription` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apogee_number` (`apogee_number`),
  CONSTRAINT `fk_inscription_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE,
  CONSTRAINT `fk_inscription_filiere` FOREIGN KEY (`filiere_id`) REFERENCES `filiere` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inscription_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: note (SANS crédit, coefficient = 1)
CREATE TABLE `note` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apogee_number` varchar(20) NOT NULL,
  `annee_universitaire` varchar(20) NOT NULL,
  `semestre` enum('S1','S2','S3','S4','S5','S6','S7','S8','S9','S10') NOT NULL,
  `code_module` varchar(20) NOT NULL,
  `nom_module` varchar(200) NOT NULL,
  `niveau_id` int(11) DEFAULT NULL,
  `note` decimal(4,2) NOT NULL,
  `coefficient` decimal(4,2) NOT NULL DEFAULT 1.00,
  `mention` enum('Très Bien','Bien','Assez Bien','Passable','Ajourné') DEFAULT NULL,
  `date_examen` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apogee_number` (`apogee_number`),
  KEY `idx_code_module` (`code_module`),
  CONSTRAINT `fk_note_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_module` FOREIGN KEY (`code_module`) REFERENCES `module` (`code_module`) ON DELETE CASCADE,
  CONSTRAINT `fk_note_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: resultat_semestre (SANS crédits)
CREATE TABLE `resultat_semestre` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apogee_number` varchar(20) NOT NULL,
  `niveau_id` int(11) NOT NULL,
  `annee_universitaire` varchar(20) NOT NULL,
  `moyenne_semestre` decimal(4,2) NOT NULL,
  `statut` enum('Validé','Ajourné','Rattrapage') NOT NULL DEFAULT 'Validé',
  `mention` enum('Très Bien','Bien','Assez Bien','Passable','Ajourné') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apogee_number` (`apogee_number`),
  KEY `idx_niveau_id` (`niveau_id`),
  CONSTRAINT `fk_resultat_semestre_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE,
  CONSTRAINT `fk_resultat_semestre_niveau` FOREIGN KEY (`niveau_id`) REFERENCES `niveau` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: resultat_annee
CREATE TABLE `resultat_annee` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `apogee_number` varchar(20) NOT NULL,
  `annee_universitaire` varchar(20) NOT NULL,
  `niveau` varchar(50) NOT NULL,
  `filiere` varchar(100) NOT NULL,
  `moyenne_generale` decimal(4,2) NOT NULL,
  `statut` enum('Réussi','Ajourné','Redoublant','En cours') NOT NULL DEFAULT 'En cours',
  `mention` enum('Très Bien','Bien','Assez Bien','Passable','Ajourné') DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_resultat_annee_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `demande` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_demande` varchar(50) NOT NULL,
  `numero_attestation` varchar(50) DEFAULT NULL,
  `apogee_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `cin` varchar(20) NOT NULL,
  `document_type` enum('Attestation de scolarité','Attestation de réussite','Relevé de notes','Convention de stage','Réclamation','Autre') NOT NULL,
  `status` enum('En attente','Acceptée','Refusée','Traitée') NOT NULL DEFAULT 'En attente',
  `justification_refus` text DEFAULT NULL,
  `additional_info` json DEFAULT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `date_demande` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_demande` (`numero_demande`),
  KEY `idx_apogee_number` (`apogee_number`),
  CONSTRAINT `fk_demande_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: reclamation
CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero_attestation_reclamee` varchar(50) NOT NULL,
  `numero_demande_reclamee` varchar(50) DEFAULT NULL,
  `demande_id` int(11) DEFAULT NULL,
  `apogee_number` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `motif` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `status` enum('En attente','En cours','Résolue','Rejetée','Fermée') NOT NULL DEFAULT 'En attente',
  `reponse_admin` text DEFAULT NULL,
  `reponse` text DEFAULT NULL,
  `date_reclamation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_reponse` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_demande_id` (`demande_id`),
  CONSTRAINT `fk_reclamation_demande` FOREIGN KEY (`demande_id`) REFERENCES `demande` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reclamation_etudiant` FOREIGN KEY (`apogee_number`) REFERENCES `etudiant` (`apogee_number`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INSERTION DES DONNÉES DE BASE
-- =====================================================

-- Administrateur
INSERT INTO `administrateur` (`login`, `password_hash`, `email`, `nom`, `prenom`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@novatech.example', 'Admin', 'System');

-- Filières (7 filières réelles)
INSERT INTO `filiere` (`code`, `nom`, `description`) VALUES
('BDIA', 'Big Data et Intelligence Artificielle', 'Formation en big data, intelligence artificielle, machine learning et data science'),
('GI', 'Génie Informatique', 'Formation en informatique, développement logiciel, réseaux et systèmes'),
('IM', 'Ingénierie Mécatronique', 'Formation en mécatronique, robotique, automatisation et systèmes embarqués'),
('GSTR', 'Génie des Systèmes des Télécommunications et Réseaux', 'Formation en télécommunications, réseaux, systèmes de communication et technologies mobiles'),
('SCM', 'Management de la Chaine Logistique', 'Formation en logistique, transport, supply chain management et optimisation'),
('CYBER', 'Génie Cybersecurity', 'Formation en cybersécurité, sécurité des systèmes d\'information et protection des données'),
('SE', 'Systèmes Embarqués', 'Formation en systèmes embarqués, électronique embarquée et développement de systèmes temps réel');

-- Niveaux/Semestres (10 semestres)
INSERT INTO `niveau` (`code`, `nom`, `numero_semestre`, `annee_academique`, `type_cycle`, `annee_cycle`, `semestre_annee`) VALUES
('2AP1-S1', '2ème Année Préparatoire 1 - Semestre 1', 1, 1, 'Préparatoire', NULL, 'S1'),
('2AP1-S2', '2ème Année Préparatoire 1 - Semestre 2', 2, 1, 'Préparatoire', NULL, 'S2'),
('2AP2-S3', '2ème Année Préparatoire 2 - Semestre 3', 3, 2, 'Préparatoire', NULL, 'S1'),
('2AP2-S4', '2ème Année Préparatoire 2 - Semestre 4', 4, 2, 'Préparatoire', NULL, 'S2'),
('CI1-S5', 'Cycle Ingénieur 1 - Semestre 5', 5, 3, 'Cycle Ingénieur', 1, 'S1'),
('CI1-S6', 'Cycle Ingénieur 1 - Semestre 6', 6, 3, 'Cycle Ingénieur', 1, 'S2'),
('CI2-S7', 'Cycle Ingénieur 2 - Semestre 7', 7, 4, 'Cycle Ingénieur', 2, 'S1'),
('CI2-S8', 'Cycle Ingénieur 2 - Semestre 8', 8, 4, 'Cycle Ingénieur', 2, 'S2'),
('CI3-S9', 'Cycle Ingénieur 3 - Semestre 9', 9, 5, 'Cycle Ingénieur', 3, 'S1'),
('CI3-S10', 'Cycle Ingénieur 3 - Semestre 10', 10, 5, 'Cycle Ingénieur', 3, 'S2');

-- Étudiants (4 étudiants avec leurs emails)
INSERT INTO `etudiant` (`apogee_number`, `nom`, `prenom`, `email`, `cin`, `date_naissance`, `telephone`, `adresse`) VALUES
('A12345', 'Elafi', 'Fatima', 'fatima.elafi@etu.uae.ac.ma', 'AB123456', '2000-05-15', '0612345678', 'Tétouan, Maroc'),
('B67890', 'Benali', 'Ahmed', 't89101772@gmail.com', 'CD234567', '2001-08-20', '0623456789', 'Tanger, Maroc'),
('C11223', 'Elassal', 'Douae', 'elassal.douae@etu.uae.ac.ma', 'EF345678', '2002-03-10', '0634567890', 'Fès, Maroc'),
('D44556', 'Alami', 'Youssef', 'takeyournotes3@gmail.com', 'GH456789', '2000-11-25', '0645678901', 'Casablanca, Maroc');

-- =====================================================
-- MODULES RÉELS POUR GÉNIE INFORMATIQUE
-- =====================================================

-- Modules Génie Informatique (GI) - Tous les semestres avec modules réels
INSERT INTO `module` (`code_module`, `nom_module`, `filiere_id`, `niveau_id`, `coefficient`, `type`) VALUES
-- 2AP1-S1
('GI-MATH1', 'Analyse Mathématique 1', 1, 1, 1.00, 'Cours'),
('GI-ALG1', 'Algèbre 1', 1, 1, 1.00, 'Cours'),
('GI-PROG1', 'Programmation en C', 1, 1, 1.00, 'TP'),
('GI-ARCHI', 'Architecture des Ordinateurs', 1, 1, 1.00, 'Cours'),
('GI-ANG1', 'Anglais Technique', 1, 1, 1.00, 'Cours'),
-- 2AP1-S2
('GI-MATH2', 'Analyse Mathématique 2', 1, 2, 1.00, 'Cours'),
('GI-ALG2', 'Algèbre 2', 1, 2, 1.00, 'Cours'),
('GI-PROG2', 'Programmation Orientée Objet (Java)', 1, 2, 1.00, 'TP'),
('GI-BDD1', 'Bases de Données Relationnelles', 1, 2, 1.00, 'Cours'),
('GI-RESEAU1', 'Introduction aux Réseaux', 1, 2, 1.00, 'Cours'),
-- 2AP2-S3
('GI-MATH3', 'Probabilités et Statistiques', 1, 3, 1.00, 'Cours'),
('GI-ALGO', 'Algorithmique et Structures de Données', 1, 3, 1.00, 'Cours'),
('GI-PROG3', 'Programmation Web (HTML/CSS/JavaScript)', 1, 3, 1.00, 'TP'),
('GI-BDD2', 'Bases de Données Avancées', 1, 3, 1.00, 'Cours'),
('GI-SYS1', 'Systèmes d\'Exploitation (Linux)', 1, 3, 1.00, 'Cours'),
-- 2AP2-S4
('GI-MATH4', 'Mathématiques Discrètes', 1, 4, 1.00, 'Cours'),
('GI-PROG4', 'Développement Web Backend (PHP/Python)', 1, 4, 1.00, 'TP'),
('GI-RESEAU2', 'Réseaux et Protocoles', 1, 4, 1.00, 'Cours'),
('GI-SECU1', 'Sécurité Informatique', 1, 4, 1.00, 'Cours'),
('GI-PROJ1', 'Projet de Développement', 1, 4, 1.00, 'Projet'),
-- CI1-S5
('GI-IA1', 'Intelligence Artificielle', 1, 5, 1.00, 'Cours'),
('GI-WEB1', 'Développement Web Avancé (React/Node.js)', 1, 5, 1.00, 'TP'),
('GI-CLOUD', 'Cloud Computing et Virtualisation', 1, 5, 1.00, 'Cours'),
('GI-MOBILE', 'Développement Mobile (Android/iOS)', 1, 5, 1.00, 'TP'),
('GI-GESTION', 'Gestion de Projet Informatique', 1, 5, 1.00, 'Cours'),
-- CI1-S6
('GI-IA2', 'Machine Learning et Deep Learning', 1, 6, 1.00, 'Cours'),
('GI-BIGDATA', 'Big Data et Analytics', 1, 6, 1.00, 'Cours'),
('GI-DEVOPS', 'DevOps et CI/CD', 1, 6, 1.00, 'TP'),
('GI-ARCHI-SYS', 'Architecture des Systèmes Distribués', 1, 6, 1.00, 'Cours'),
('GI-PROJ2', 'Projet Intégré', 1, 6, 1.00, 'Projet'),
-- CI2-S7
('GI-BLOCKCHAIN', 'Blockchain et Cryptomonnaies', 1, 7, 1.00, 'Cours'),
('GI-IOT', 'Internet des Objets (IoT)', 1, 7, 1.00, 'Cours'),
('GI-CYBERSECU', 'Cybersécurité Avancée', 1, 7, 1.00, 'Cours'),
('GI-ENTREP', 'Entrepreneuriat et Innovation', 1, 7, 1.00, 'Cours'),
('GI-PROJ3', 'Projet de Recherche', 1, 7, 1.00, 'Projet'),
-- CI2-S8 (PFA)
('GI-PFA', 'Projet de Fin d\'Année (PFA)', 1, 8, 1.00, 'Stage'),
('GI-COMM', 'Communication Professionnelle', 1, 8, 1.00, 'Cours'),
-- CI3-S9
('GI-ADVANCED', 'Technologies Émergentes', 1, 9, 1.00, 'Cours'),
('GI-ETHIQUE', 'Éthique et Déontologie en Informatique', 1, 9, 1.00, 'Cours'),
('GI-PREP-PFE', 'Préparation au PFE', 1, 9, 1.00, 'Projet'),
-- CI3-S10 (PFE)
('GI-PFE', 'Projet de Fin d\'Études (PFE)', 1, 10, 1.00, 'Stage');

-- =====================================================
-- MODULES RÉELS POUR GÉNIE INDUSTRIEL
-- =====================================================

INSERT INTO `module` (`code_module`, `nom_module`, `filiere_id`, `niveau_id`, `coefficient`, `type`) VALUES
-- 2AP1-S1
('GInd-MATH1', 'Mathématiques Appliquées 1', 2, 1, 1.00, 'Cours'),
('GInd-ECO1', 'Économie Générale', 2, 1, 1.00, 'Cours'),
('GInd-GEST1', 'Introduction à la Gestion', 2, 1, 1.00, 'Cours'),
('GInd-STAT1', 'Statistiques Descriptives', 2, 1, 1.00, 'Cours'),
('GInd-ANG1', 'Anglais des Affaires', 2, 1, 1.00, 'Cours'),
-- 2AP1-S2
('GInd-MATH2', 'Mathématiques Appliquées 2', 2, 2, 1.00, 'Cours'),
('GInd-COMPTA', 'Comptabilité Générale', 2, 2, 1.00, 'Cours'),
('GInd-MARKET1', 'Marketing Fondamental', 2, 2, 1.00, 'Cours'),
('GInd-STAT2', 'Statistiques Inférentielles', 2, 2, 1.00, 'Cours'),
('GInd-COMM1', 'Communication', 2, 2, 1.00, 'Cours'),
-- 2AP2-S3
('GInd-MATH3', 'Recherche Opérationnelle 1', 2, 3, 1.00, 'Cours'),
('GInd-LOG1', 'Logistique et Transport', 2, 3, 1.00, 'Cours'),
('GInd-PROD1', 'Gestion de Production', 2, 3, 1.00, 'Cours'),
('GInd-QUAL1', 'Qualité et Normes', 2, 3, 1.00, 'Cours'),
('GInd-ANG2', 'Anglais', 2, 3, 1.00, 'Cours'),
-- 2AP2-S4
('GInd-MATH4', 'Recherche Opérationnelle 2', 2, 4, 1.00, 'Cours'),
('GInd-SUPPLY', 'Supply Chain Management', 2, 4, 1.00, 'Cours'),
('GInd-FINANCE1', 'Finance d\'Entreprise', 2, 4, 1.00, 'Cours'),
('GInd-OPTIM1', 'Optimisation des Processus', 2, 4, 1.00, 'Cours'),
('GInd-COMM2', 'Communication 2', 2, 4, 1.00, 'Cours'),
-- CI1-S5
('GInd-GEST-PROJ', 'Gestion de Projet', 2, 5, 1.00, 'Cours'),
('GInd-ERP', 'Systèmes ERP (SAP)', 2, 5, 1.00, 'TP'),
('GInd-AUTOMAT', 'Automatisation Industrielle', 2, 5, 1.00, 'Cours'),
('GInd-RH', 'Gestion des Ressources Humaines', 2, 5, 1.00, 'Cours'),
('GInd-ANG3', 'Anglais', 2, 5, 1.00, 'Cours'),
-- CI1-S6
('GInd-STRAT', 'Stratégie d\'Entreprise', 2, 6, 1.00, 'Cours'),
('GInd-LEAN', 'Lean Manufacturing', 2, 6, 1.00, 'Cours'),
('GInd-SIMUL1', 'Simulation de Processus', 2, 6, 1.00, 'TP'),
('GInd-INNOV', 'Innovation et Créativité', 2, 6, 1.00, 'Cours'),
('GInd-PROJ1', 'Projet Industriel', 2, 6, 1.00, 'Projet'),
-- CI2-S7
('GInd-SIMUL2', 'Simulation Avancée', 2, 7, 1.00, 'Cours'),
('GInd-IA', 'Intelligence Artificielle Appliquée', 2, 7, 1.00, 'Cours'),
('GInd-DECISION', 'Aide à la Décision', 2, 7, 1.00, 'Cours'),
('GInd-INTERNAT', 'Commerce International', 2, 7, 1.00, 'Cours'),
('GInd-PROJ2', 'Projet de Recherche', 2, 7, 1.00, 'Projet'),
-- CI2-S8 (PFA)
('GInd-PFA', 'Projet de Fin d\'Année (PFA)', 2, 8, 1.00, 'Stage'),
('GInd-ENTREP', 'Entrepreneuriat', 2, 8, 1.00, 'Cours'),
-- CI3-S9
('GInd-ADVANCED', 'Technologies Avancées', 2, 9, 1.00, 'Cours'),
('GInd-ETHIQUE', 'Éthique Professionnelle', 2, 9, 1.00, 'Cours'),
('GInd-PREP-PFE', 'Préparation au PFE', 2, 9, 1.00, 'Projet'),
-- CI3-S10 (PFE)
('GInd-PFE', 'Projet de Fin d\'Études (PFE)', 2, 10, 1.00, 'Stage');

-- =====================================================
-- MODULES RÉELS POUR GÉNIE CIVIL
-- =====================================================

INSERT INTO `module` (`code_module`, `nom_module`, `filiere_id`, `niveau_id`, `coefficient`, `type`) VALUES
-- 2AP1-S1
('GC-MATH1', 'Mathématiques 1', 3, 1, 1.00, 'Cours'),
('GC-PHYS1', 'Physique Générale', 3, 1, 1.00, 'Cours'),
('GC-MAT1', 'Matériaux de Construction', 3, 1, 1.00, 'Cours'),
('GC-DESSIN1', 'Dessin Technique et DAO', 3, 1, 1.00, 'TP'),
('GC-ANG1', 'Anglais Technique', 3, 1, 1.00, 'Cours'),
-- 2AP1-S2
('GC-MATH2', 'Mathématiques 2', 3, 2, 1.00, 'Cours'),
('GC-PHYS2', 'Mécanique des Solides', 3, 2, 1.00, 'Cours'),
('GC-MAT2', 'Matériaux Avancés', 3, 2, 1.00, 'Cours'),
('GC-STRUCT1', 'Résistance des Matériaux', 3, 2, 1.00, 'Cours'),
('GC-COMM1', 'Communication', 3, 2, 1.00, 'Cours'),
-- 2AP2-S3
('GC-MATH3', 'Mathématiques 3', 3, 3, 1.00, 'Cours'),
('GC-STRUCT2', 'Structures Métalliques', 3, 3, 1.00, 'Cours'),
('GC-BETON1', 'Béton Armé 1', 3, 3, 1.00, 'Cours'),
('GC-TOPOG', 'Topographie', 3, 3, 1.00, 'TP'),
('GC-ANG2', 'Anglais', 3, 3, 1.00, 'Cours'),
-- 2AP2-S4
('GC-MATH4', 'Mathématiques 4', 3, 4, 1.00, 'Cours'),
('GC-STRUCT3', 'Structures en Béton', 3, 4, 1.00, 'Cours'),
('GC-ROUTES', 'Routes et Chaussées', 3, 4, 1.00, 'Cours'),
('GC-HYDRAU', 'Hydraulique', 3, 4, 1.00, 'Cours'),
('GC-COMM2', 'Communication 2', 3, 4, 1.00, 'Cours'),
-- CI1-S5
('GC-STRUCT4', 'Structures Avancées', 3, 5, 1.00, 'Cours'),
('GC-FOND', 'Géotechnique et Fondations', 3, 5, 1.00, 'Cours'),
('GC-INSTALL', 'Installations Techniques', 3, 5, 1.00, 'Cours'),
('GC-GEST-CHANT', 'Gestion de Chantier', 3, 5, 1.00, 'Cours'),
('GC-ANG3', 'Anglais', 3, 5, 1.00, 'Cours'),
-- CI1-S6
('GC-STRUCT5', 'Calcul des Structures', 3, 6, 1.00, 'Cours'),
('GC-ENVIRON', 'Environnement et Développement Durable', 3, 6, 1.00, 'Cours'),
('GC-SECU', 'Sécurité et Prévention', 3, 6, 1.00, 'Cours'),
('GC-PROJ1', 'Projet de Conception', 3, 6, 1.00, 'Projet'),
('GC-ANG4', 'Anglais', 3, 6, 1.00, 'Cours'),
-- CI2-S7
('GC-STRUCT6', 'Structures Spéciales', 3, 7, 1.00, 'Cours'),
('GC-URBAN', 'Urbanisme et Aménagement', 3, 7, 1.00, 'Cours'),
('GC-REGLEM', 'Réglementation et Normes', 3, 7, 1.00, 'Cours'),
('GC-PROJ2', 'Projet Intégré', 3, 7, 1.00, 'Projet'),
('GC-ANG5', 'Anglais', 3, 7, 1.00, 'Cours'),
-- CI2-S8 (PFA)
('GC-PFA', 'Projet de Fin d\'Année (PFA)', 3, 8, 1.00, 'Stage'),
('GC-ENTREP', 'Entrepreneuriat', 3, 8, 1.00, 'Cours'),
-- CI3-S9
('GC-ADVANCED', 'Technologies Avancées', 3, 9, 1.00, 'Cours'),
('GC-DURABLE', 'Construction Durable', 3, 9, 1.00, 'Cours'),
('GC-ETHIQUE', 'Éthique Professionnelle', 3, 9, 1.00, 'Cours'),
('GC-PREP-PFE', 'Préparation au PFE', 3, 9, 1.00, 'Projet'),
-- CI3-S10 (PFE)
('GC-PFE', 'Projet de Fin d\'Études (PFE)', 3, 10, 1.00, 'Stage');

-- =====================================================
-- MODULES RÉELS POUR GÉNIE ÉLECTRIQUE
-- =====================================================

INSERT INTO `module` (`code_module`, `nom_module`, `filiere_id`, `niveau_id`, `coefficient`, `type`) VALUES
-- 2AP1-S1
('GE-MATH1', 'Mathématiques 1', 4, 1, 1.00, 'Cours'),
('GE-ELEC1', 'Électricité Générale', 4, 1, 1.00, 'Cours'),
('GE-ELEC2', 'Électronique Analogique 1', 4, 1, 1.00, 'Cours'),
('GE-PHYS1', 'Physique Appliquée', 4, 1, 1.00, 'Cours'),
('GE-ANG1', 'Anglais Technique', 4, 1, 1.00, 'Cours'),
-- 2AP1-S2
('GE-MATH2', 'Mathématiques 2', 4, 2, 1.00, 'Cours'),
('GE-ELEC3', 'Électrotechnique', 4, 2, 1.00, 'Cours'),
('GE-ELEC4', 'Électronique Numérique', 4, 2, 1.00, 'Cours'),
('GE-SIGNAUX', 'Traitement du Signal', 4, 2, 1.00, 'Cours'),
('GE-COMM1', 'Communication', 4, 2, 1.00, 'Cours'),
-- 2AP2-S3
('GE-MATH3', 'Mathématiques 3', 4, 3, 1.00, 'Cours'),
('GE-AUTOMAT1', 'Automatisme Industriel', 4, 3, 1.00, 'Cours'),
('GE-MACHINES', 'Machines Électriques', 4, 3, 1.00, 'Cours'),
('GE-ENERGIE', 'Énergies Renouvelables', 4, 3, 1.00, 'Cours'),
('GE-ANG2', 'Anglais', 4, 3, 1.00, 'Cours'),
-- 2AP2-S4
('GE-MATH4', 'Mathématiques 4', 4, 4, 1.00, 'Cours'),
('GE-RESEAU', 'Réseaux Électriques', 4, 4, 1.00, 'Cours'),
('GE-CONTROL', 'Systèmes de Contrôle', 4, 4, 1.00, 'Cours'),
('GE-INSTALL', 'Installations Électriques', 4, 4, 1.00, 'Cours'),
('GE-COMM2', 'Communication 2', 4, 4, 1.00, 'Cours'),
-- CI1-S5
('GE-AUTOMAT2', 'Automatisme Avancé', 4, 5, 1.00, 'Cours'),
('GE-PLC', 'Programmation PLC', 4, 5, 1.00, 'TP'),
('GE-SECU', 'Sécurité Électrique', 4, 5, 1.00, 'Cours'),
('GE-GEST', 'Gestion de Projet Électrique', 4, 5, 1.00, 'Cours'),
('GE-ANG3', 'Anglais', 4, 5, 1.00, 'Cours'),
-- CI1-S6
('GE-SMART', 'Smart Grid et Réseaux Intelligents', 4, 6, 1.00, 'Cours'),
('GE-IOT', 'Internet des Objets (IoT)', 4, 6, 1.00, 'TP'),
('GE-PROJ1', 'Projet Électrique', 4, 6, 1.00, 'Projet'),
('GE-INNOV', 'Innovation Technologique', 4, 6, 1.00, 'Cours'),
('GE-ANG4', 'Anglais', 4, 6, 1.00, 'Cours'),
-- CI2-S7
('GE-ADVANCED', 'Technologies Avancées', 4, 7, 1.00, 'Cours'),
('GE-IA', 'Intelligence Artificielle Appliquée', 4, 7, 1.00, 'Cours'),
('GE-PROJ2', 'Projet de Recherche', 4, 7, 1.00, 'Projet'),
('GE-DECISION', 'Aide à la Décision', 4, 7, 1.00, 'Cours'),
('GE-ANG5', 'Anglais', 4, 7, 1.00, 'Cours'),
-- CI2-S8 (PFA)
('GE-PFA', 'Projet de Fin d\'Année (PFA)', 4, 8, 1.00, 'Stage'),
('GE-ENTREP', 'Entrepreneuriat', 4, 8, 1.00, 'Cours'),
-- CI3-S9
('GE-ADVANCED2', 'Technologies Émergentes', 4, 9, 1.00, 'Cours'),
('GE-RECHERCHE', 'Méthodologie de Recherche', 4, 9, 1.00, 'Cours'),
('GE-ETHIQUE', 'Éthique Professionnelle', 4, 9, 1.00, 'Cours'),
('GE-PREP-PFE', 'Préparation au PFE', 4, 9, 1.00, 'Projet'),
-- CI3-S10 (PFE)
('GE-PFE', 'Projet de Fin d\'Études (PFE)', 4, 10, 1.00, 'Stage');

-- =====================================================
-- MODULES RÉELS POUR GÉNIE MÉCANIQUE
-- =====================================================

INSERT INTO `module` (`code_module`, `nom_module`, `filiere_id`, `niveau_id`, `coefficient`, `type`) VALUES
-- 2AP1-S1
('GM-MATH1', 'Mathématiques 1', 5, 1, 1.00, 'Cours'),
('GM-MECA1', 'Mécanique du Point', 5, 1, 1.00, 'Cours'),
('GM-THERMO1', 'Thermodynamique 1', 5, 1, 1.00, 'Cours'),
('GM-DESSIN1', 'Dessin Technique et CAO', 5, 1, 1.00, 'TP'),
('GM-ANG1', 'Anglais Technique', 5, 1, 1.00, 'Cours'),
-- 2AP1-S2
('GM-MATH2', 'Mathématiques 2', 5, 2, 1.00, 'Cours'),
('GM-MECA2', 'Mécanique des Solides', 5, 2, 1.00, 'Cours'),
('GM-THERMO2', 'Thermodynamique 2', 5, 2, 1.00, 'Cours'),
('GM-RESIST', 'Résistance des Matériaux', 5, 2, 1.00, 'Cours'),
('GM-COMM1', 'Communication', 5, 2, 1.00, 'Cours'),
-- 2AP2-S3
('GM-MATH3', 'Mathématiques 3', 5, 3, 1.00, 'Cours'),
('GM-MECA3', 'Mécanique des Fluides', 5, 3, 1.00, 'Cours'),
('GM-CAO', 'Conception Assistée par Ordinateur', 5, 3, 1.00, 'TP'),
('GM-FABRIC', 'Procédés de Fabrication', 5, 3, 1.00, 'Cours'),
('GM-ANG2', 'Anglais', 5, 3, 1.00, 'Cours'),
-- 2AP2-S4
('GM-MATH4', 'Mathématiques 4', 5, 4, 1.00, 'Cours'),
('GM-MECA4', 'Vibrations et Acoustique', 5, 4, 1.00, 'Cours'),
('GM-MATERIAUX', 'Science des Matériaux', 5, 4, 1.00, 'Cours'),
('GM-CONCEPT1', 'Conception Mécanique', 5, 4, 1.00, 'Cours'),
('GM-COMM2', 'Communication 2', 5, 4, 1.00, 'Cours'),
-- CI1-S5
('GM-MECA5', 'Mécanique Avancée', 5, 5, 1.00, 'Cours'),
('GM-CFD', 'Dynamique des Fluides Numérique (CFD)', 5, 5, 1.00, 'TP'),
('GM-CONCEPT2', 'Conception et Dimensionnement', 5, 5, 1.00, 'Cours'),
('GM-GEST', 'Gestion de Projet Mécanique', 5, 5, 1.00, 'Cours'),
('GM-ANG3', 'Anglais', 5, 5, 1.00, 'Cours'),
-- CI1-S6
('GM-MECA6', 'Mécanique des Systèmes', 5, 6, 1.00, 'Cours'),
('GM-ROBOT', 'Robotique Industrielle', 5, 6, 1.00, 'Cours'),
('GM-PROJ1', 'Projet de Conception', 5, 6, 1.00, 'Projet'),
('GM-INNOV', 'Innovation en Mécanique', 5, 6, 1.00, 'Cours'),
('GM-ANG4', 'Anglais', 5, 6, 1.00, 'Cours'),
-- CI2-S7
('GM-ADVANCED', 'Technologies Avancées', 5, 7, 1.00, 'Cours'),
('GM-IA', 'Intelligence Artificielle Appliquée', 5, 7, 1.00, 'Cours'),
('GM-PROJ2', 'Projet de Recherche', 5, 7, 1.00, 'Projet'),
('GM-DECISION', 'Aide à la Décision', 5, 7, 1.00, 'Cours'),
('GM-ANG5', 'Anglais', 5, 7, 1.00, 'Cours'),
-- CI2-S8 (PFA)
('GM-PFA', 'Projet de Fin d\'Année (PFA)', 5, 8, 1.00, 'Stage'),
('GM-ENTREP', 'Entrepreneuriat', 5, 8, 1.00, 'Cours'),
-- CI3-S9
('GM-ADVANCED2', 'Technologies Émergentes', 5, 9, 1.00, 'Cours'),
('GM-RECHERCHE', 'Méthodologie de Recherche', 5, 9, 1.00, 'Cours'),
('GM-ETHIQUE', 'Éthique Professionnelle', 5, 9, 1.00, 'Cours'),
('GM-PREP-PFE', 'Préparation au PFE', 5, 9, 1.00, 'Projet'),
-- CI3-S10 (PFE)
('GM-PFE', 'Projet de Fin d\'Études (PFE)', 5, 10, 1.00, 'Stage');

-- =====================================================
-- INSCRIPTIONS POUR TOUS LES ÉTUDIANTS
-- =====================================================

-- Inscriptions pour Fatima Elafi (A12345) - Génie Informatique
INSERT INTO `inscription` (`apogee_number`, `filiere_id`, `niveau_id`, `annee_universitaire`, `niveau`, `filiere`, `statut`, `date_inscription`) VALUES
('A12345', NULL, 1, '2020-2021', '2AP1-S1', 'Cycle Préparatoire', 'Inscrit', '2020-09-15'),
('A12345', NULL, 2, '2020-2021', '2AP1-S2', 'Cycle Préparatoire', 'Inscrit', '2021-02-15'),
('A12345', NULL, 3, '2021-2022', '2AP2-S3', 'Cycle Préparatoire', 'Inscrit', '2021-09-15'),
('A12345', NULL, 4, '2021-2022', '2AP2-S4', 'Cycle Préparatoire', 'Inscrit', '2022-02-15'),
('A12345', 1, 5, '2022-2023', 'CI1-S5', 'Génie Informatique', 'Inscrit', '2022-09-15'),
('A12345', 1, 6, '2022-2023', 'CI1-S6', 'Génie Informatique', 'Inscrit', '2023-02-15'),
('A12345', 1, 7, '2023-2024', 'CI2-S7', 'Génie Informatique', 'Inscrit', '2023-09-15'),
('A12345', 1, 8, '2023-2024', 'CI2-S8', 'Génie Informatique', 'Inscrit', '2024-02-15'),
('A12345', 1, 9, '2024-2025', 'CI3-S9', 'Génie Informatique', 'Inscrit', '2024-09-15'),
('A12345', 1, 10, '2024-2025', 'CI3-S10', 'Génie Informatique', 'Inscrit', '2025-02-15');

-- Inscriptions pour Ahmed Benali (B67890) - Génie Industriel
INSERT INTO `inscription` (`apogee_number`, `filiere_id`, `niveau_id`, `annee_universitaire`, `niveau`, `filiere`, `statut`, `date_inscription`) VALUES
('B67890', NULL, 1, '2020-2021', '2AP1-S1', 'Cycle Préparatoire', 'Inscrit', '2020-09-15'),
('B67890', NULL, 2, '2020-2021', '2AP1-S2', 'Cycle Préparatoire', 'Inscrit', '2021-02-15'),
('B67890', NULL, 3, '2021-2022', '2AP2-S3', 'Cycle Préparatoire', 'Inscrit', '2021-09-15'),
('B67890', NULL, 4, '2021-2022', '2AP2-S4', 'Cycle Préparatoire', 'Inscrit', '2022-02-15'),
('B67890', 2, 5, '2022-2023', 'CI1-S5', 'Génie Industriel', 'Inscrit', '2022-09-15'),
('B67890', 2, 6, '2022-2023', 'CI1-S6', 'Génie Industriel', 'Inscrit', '2023-02-15'),
('B67890', 2, 7, '2023-2024', 'CI2-S7', 'Génie Industriel', 'Inscrit', '2023-09-15'),
('B67890', 2, 8, '2023-2024', 'CI2-S8', 'Génie Industriel', 'Inscrit', '2024-02-15'),
('B67890', 2, 9, '2024-2025', 'CI3-S9', 'Génie Industriel', 'Inscrit', '2024-09-15'),
('B67890', 2, 10, '2024-2025', 'CI3-S10', 'Génie Industriel', 'Inscrit', '2025-02-15');

-- Inscriptions pour Douae Elassal (C11223) - Génie Civil
INSERT INTO `inscription` (`apogee_number`, `filiere_id`, `niveau_id`, `annee_universitaire`, `niveau`, `filiere`, `statut`, `date_inscription`) VALUES
('C11223', NULL, 1, '2021-2022', '2AP1-S1', 'Cycle Préparatoire', 'Inscrit', '2021-09-15'),
('C11223', NULL, 2, '2021-2022', '2AP1-S2', 'Cycle Préparatoire', 'Inscrit', '2022-02-15'),
('C11223', NULL, 3, '2022-2023', '2AP2-S3', 'Cycle Préparatoire', 'Inscrit', '2022-09-15'),
('C11223', NULL, 4, '2022-2023', '2AP2-S4', 'Cycle Préparatoire', 'Inscrit', '2023-02-15'),
('C11223', 3, 5, '2023-2024', 'CI1-S5', 'Génie Civil', 'Inscrit', '2023-09-15'),
('C11223', 3, 6, '2023-2024', 'CI1-S6', 'Génie Civil', 'Inscrit', '2024-02-15'),
('C11223', 3, 7, '2024-2025', 'CI2-S7', 'Génie Civil', 'Inscrit', '2024-09-15'),
('C11223', 3, 8, '2024-2025', 'CI2-S8', 'Génie Civil', 'Inscrit', '2025-02-15');

-- Inscriptions pour Youssef Alami (D44556) - Génie Électrique
INSERT INTO `inscription` (`apogee_number`, `filiere_id`, `niveau_id`, `annee_universitaire`, `niveau`, `filiere`, `statut`, `date_inscription`) VALUES
('D44556', NULL, 1, '2020-2021', '2AP1-S1', 'Cycle Préparatoire', 'Inscrit', '2020-09-15'),
('D44556', NULL, 2, '2020-2021', '2AP1-S2', 'Cycle Préparatoire', 'Inscrit', '2021-02-15'),
('D44556', NULL, 3, '2021-2022', '2AP2-S3', 'Cycle Préparatoire', 'Inscrit', '2021-09-15'),
('D44556', NULL, 4, '2021-2022', '2AP2-S4', 'Cycle Préparatoire', 'Inscrit', '2022-02-15'),
('D44556', 4, 5, '2022-2023', 'CI1-S5', 'Génie Électrique', 'Inscrit', '2022-09-15'),
('D44556', 4, 6, '2022-2023', 'CI1-S6', 'Génie Électrique', 'Inscrit', '2023-02-15'),
('D44556', 4, 7, '2023-2024', 'CI2-S7', 'Génie Électrique', 'Inscrit', '2023-09-15'),
('D44556', 4, 8, '2023-2024', 'CI2-S8', 'Génie Électrique', 'Inscrit', '2024-02-15'),
('D44556', 4, 9, '2024-2025', 'CI3-S9', 'Génie Électrique', 'Inscrit', '2024-09-15'),
('D44556', 4, 10, '2024-2025', 'CI3-S10', 'Génie Électrique', 'Inscrit', '2025-02-15');

-- =====================================================
-- NOTES POUR TOUS LES ÉTUDIANTS (coefficient = 1)
-- =====================================================

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S1
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-MATH1', 'Analyse Mathématique 1', 1, '2020-2021', 'S1', 15.0, 1.00, 'Bien', '2020-12-15'),
('A12345', 'GI-ALG1', 'Algèbre 1', 1, '2020-2021', 'S1', 16.0, 1.00, 'Bien', '2020-12-18'),
('A12345', 'GI-PROG1', 'Programmation en C', 1, '2020-2021', 'S1', 17.0, 1.00, 'Très Bien', '2020-12-20'),
('A12345', 'GI-ARCHI', 'Architecture des Ordinateurs', 1, '2020-2021', 'S1', 14.5, 1.00, 'Bien', '2020-12-22'),
('A12345', 'GI-ANG1', 'Anglais Technique', 1, '2020-2021', 'S1', 15.5, 1.00, 'Bien', '2020-12-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S2
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-MATH2', 'Analyse Mathématique 2', 2, '2020-2021', 'S2', 16.0, 1.00, 'Bien', '2021-05-15'),
('A12345', 'GI-ALG2', 'Algèbre 2', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-18'),
('A12345', 'GI-PROG2', 'Programmation Orientée Objet (Java)', 2, '2020-2021', 'S2', 18.0, 1.00, 'Très Bien', '2021-05-20'),
('A12345', 'GI-BDD1', 'Bases de Données Relationnelles', 2, '2020-2021', 'S2', 15.0, 1.00, 'Bien', '2021-05-22'),
('A12345', 'GI-RESEAU1', 'Introduction aux Réseaux', 2, '2020-2021', 'S2', 14.5, 1.00, 'Bien', '2021-05-10');

-- Notes pour Ahmed Benali (B67890) - Génie Industriel - S1
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('B67890', 'GInd-MATH1', 'Mathématiques Appliquées 1', 1, '2020-2021', 'S1', 14.0, 1.00, 'Bien', '2020-12-15'),
('B67890', 'GInd-ECO1', 'Économie Générale', 1, '2020-2021', 'S1', 15.5, 1.00, 'Bien', '2020-12-18'),
('B67890', 'GInd-GEST1', 'Introduction à la Gestion', 1, '2020-2021', 'S1', 16.0, 1.00, 'Bien', '2020-12-20'),
('B67890', 'GInd-STAT1', 'Statistiques Descriptives', 1, '2020-2021', 'S1', 13.5, 1.00, 'Assez Bien', '2020-12-22'),
('B67890', 'GInd-ANG1', 'Anglais des Affaires', 1, '2020-2021', 'S1', 15.0, 1.00, 'Bien', '2020-12-10');

-- Notes pour Douae Elassal (C11223) - Génie Civil - S1
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('C11223', 'GC-MATH1', 'Mathématiques 1', 1, '2021-2022', 'S1', 17.0, 1.00, 'Très Bien', '2021-12-15'),
('C11223', 'GC-PHYS1', 'Physique Générale', 1, '2021-2022', 'S1', 16.5, 1.00, 'Très Bien', '2021-12-18'),
('C11223', 'GC-MAT1', 'Matériaux de Construction', 1, '2021-2022', 'S1', 15.0, 1.00, 'Bien', '2021-12-20'),
('C11223', 'GC-DESSIN1', 'Dessin Technique et DAO', 1, '2021-2022', 'S1', 18.0, 1.00, 'Très Bien', '2021-12-22'),
('C11223', 'GC-ANG1', 'Anglais Technique', 1, '2021-2022', 'S1', 16.0, 1.00, 'Bien', '2021-12-10');

-- Notes pour Youssef Alami (D44556) - Génie Électrique - S1
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('D44556', 'GE-MATH1', 'Mathématiques 1', 1, '2020-2021', 'S1', 15.0, 1.00, 'Bien', '2020-12-15'),
('D44556', 'GE-ELEC1', 'Électricité Générale', 1, '2020-2021', 'S1', 16.5, 1.00, 'Très Bien', '2020-12-18'),
('D44556', 'GE-ELEC2', 'Électronique Analogique 1', 1, '2020-2021', 'S1', 14.0, 1.00, 'Bien', '2020-12-20'),
('D44556', 'GE-PHYS1', 'Physique Appliquée', 1, '2020-2021', 'S1', 15.5, 1.00, 'Bien', '2020-12-22'),
('D44556', 'GE-ANG1', 'Anglais Technique', 1, '2020-2021', 'S1', 16.0, 1.00, 'Bien', '2020-12-10');

-- =====================================================
-- NOTES COMPLÉMENTAIRES POUR TOUS LES SEMESTRES VALIDÉS
-- =====================================================

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S3
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-MATH3', 'Probabilités et Statistiques', 3, '2021-2022', 'S3', 15.5, 1.00, 'Bien', '2021-12-15'),
('A12345', 'GI-ALGO', 'Algorithmique et Structures de Données', 3, '2021-2022', 'S3', 16.0, 1.00, 'Bien', '2021-12-18'),
('A12345', 'GI-PROG3', 'Programmation Web (HTML/CSS/JavaScript)', 3, '2021-2022', 'S3', 17.5, 1.00, 'Très Bien', '2021-12-20'),
('A12345', 'GI-BDD2', 'Bases de Données Avancées', 3, '2021-2022', 'S3', 15.0, 1.00, 'Bien', '2021-12-22'),
('A12345', 'GI-SYS1', 'Systèmes d\'Exploitation (Linux)', 3, '2021-2022', 'S3', 15.5, 1.00, 'Bien', '2021-12-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S4
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-MATH4', 'Mathématiques Discrètes', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-15'),
('A12345', 'GI-PROG4', 'Développement Web Backend (PHP/Python)', 4, '2021-2022', 'S4', 17.0, 1.00, 'Très Bien', '2022-05-18'),
('A12345', 'GI-RESEAU2', 'Réseaux et Protocoles', 4, '2021-2022', 'S4', 16.5, 1.00, 'Bien', '2022-05-20'),
('A12345', 'GI-SECU1', 'Sécurité Informatique', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-22'),
('A12345', 'GI-PROJ1', 'Projet de Développement', 4, '2021-2022', 'S4', 17.0, 1.00, 'Très Bien', '2022-05-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S5
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-IA1', 'Intelligence Artificielle', 5, '2022-2023', 'S5', 16.5, 1.00, 'Bien', '2022-12-15'),
('A12345', 'GI-WEB1', 'Développement Web Avancé (React/Node.js)', 5, '2022-2023', 'S5', 17.0, 1.00, 'Très Bien', '2022-12-18'),
('A12345', 'GI-CLOUD', 'Cloud Computing et Virtualisation', 5, '2022-2023', 'S5', 16.0, 1.00, 'Bien', '2022-12-20'),
('A12345', 'GI-MOBILE', 'Développement Mobile (Android/iOS)', 5, '2022-2023', 'S5', 16.5, 1.00, 'Bien', '2022-12-22'),
('A12345', 'GI-GESTION', 'Gestion de Projet Informatique', 5, '2022-2023', 'S5', 16.0, 1.00, 'Bien', '2022-12-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S6
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-IA2', 'Machine Learning et Deep Learning', 6, '2022-2023', 'S6', 17.5, 1.00, 'Très Bien', '2023-05-15'),
('A12345', 'GI-BIGDATA', 'Big Data et Analytics', 6, '2022-2023', 'S6', 17.0, 1.00, 'Très Bien', '2023-05-18'),
('A12345', 'GI-DEVOPS', 'DevOps et CI/CD', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-20'),
('A12345', 'GI-ARCHI-SYS', 'Architecture des Systèmes Distribués', 6, '2022-2023', 'S6', 17.0, 1.00, 'Très Bien', '2023-05-22'),
('A12345', 'GI-PROJ2', 'Projet Intégré', 6, '2022-2023', 'S6', 17.0, 1.00, 'Très Bien', '2023-05-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S7
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-BLOCKCHAIN', 'Blockchain et Cryptomonnaies', 7, '2023-2024', 'S7', 16.5, 1.00, 'Bien', '2023-12-15'),
('A12345', 'GI-IOT', 'Internet des Objets (IoT)', 7, '2023-2024', 'S7', 17.0, 1.00, 'Très Bien', '2023-12-18'),
('A12345', 'GI-CYBERSECU', 'Cybersécurité Avancée', 7, '2023-2024', 'S7', 16.5, 1.00, 'Bien', '2023-12-20'),
('A12345', 'GI-ENTREP', 'Entrepreneuriat et Innovation', 7, '2023-2024', 'S7', 17.0, 1.00, 'Très Bien', '2023-12-22'),
('A12345', 'GI-PROJ3', 'Projet de Recherche', 7, '2023-2024', 'S7', 17.0, 1.00, 'Très Bien', '2023-12-10');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S8
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-PFA', 'Projet de Fin d\'Année (PFA)', 8, '2023-2024', 'S8', 18.0, 1.00, 'Très Bien', '2024-05-15'),
('A12345', 'GI-COMM', 'Communication Professionnelle', 8, '2023-2024', 'S8', 16.5, 1.00, 'Bien', '2024-05-18');

-- Notes pour Fatima Elafi (A12345) - Génie Informatique - S9
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
('A12345', 'GI-ADVANCED', 'Technologies Émergentes', 9, '2024-2025', 'S9', 17.0, 1.00, 'Très Bien', '2024-12-15'),
('A12345', 'GI-ETHIQUE', 'Éthique et Déontologie en Informatique', 9, '2024-2025', 'S9', 17.0, 1.00, 'Très Bien', '2024-12-18'),
('A12345', 'GI-PREP-PFE', 'Préparation au PFE', 9, '2024-2025', 'S9', 17.0, 1.00, 'Très Bien', '2024-12-20');

-- Notes pour Ahmed Benali (B67890) - Génie Industriel - S2 à S10
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
-- S2
('B67890', 'GInd-MATH2', 'Mathématiques Appliquées 2', 2, '2020-2021', 'S2', 15.0, 1.00, 'Bien', '2021-05-15'),
('B67890', 'GInd-COMPTA', 'Comptabilité Générale', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-18'),
('B67890', 'GInd-MARKET1', 'Marketing Fondamental', 2, '2020-2021', 'S2', 14.5, 1.00, 'Bien', '2021-05-20'),
('B67890', 'GInd-STAT2', 'Statistiques Inférentielles', 2, '2020-2021', 'S2', 15.0, 1.00, 'Bien', '2021-05-22'),
('B67890', 'GInd-COMM1', 'Communication', 2, '2020-2021', 'S2', 15.0, 1.00, 'Bien', '2021-05-10'),
-- S3
('B67890', 'GInd-MATH3', 'Recherche Opérationnelle 1', 3, '2021-2022', 'S3', 15.25, 1.00, 'Bien', '2021-12-15'),
('B67890', 'GInd-LOG1', 'Logistique et Transport', 3, '2021-2022', 'S3', 15.5, 1.00, 'Bien', '2021-12-18'),
('B67890', 'GInd-PROD1', 'Gestion de Production', 3, '2021-2022', 'S3', 15.0, 1.00, 'Bien', '2021-12-20'),
('B67890', 'GInd-QUAL1', 'Qualité et Normes', 3, '2021-2022', 'S3', 15.0, 1.00, 'Bien', '2021-12-22'),
('B67890', 'GInd-ANG2', 'Anglais', 3, '2021-2022', 'S3', 15.5, 1.00, 'Bien', '2021-12-10'),
-- S4
('B67890', 'GInd-MATH4', 'Recherche Opérationnelle 2', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-15'),
('B67890', 'GInd-SUPPLY', 'Supply Chain Management', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-18'),
('B67890', 'GInd-FINANCE1', 'Finance d\'Entreprise', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-20'),
('B67890', 'GInd-OPTIM1', 'Optimisation des Processus', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-22'),
('B67890', 'GInd-COMM2', 'Communication 2', 4, '2021-2022', 'S4', 15.5, 1.00, 'Bien', '2022-05-10'),
-- S5
('B67890', 'GInd-GEST-PROJ', 'Gestion de Projet', 5, '2022-2023', 'S5', 15.75, 1.00, 'Bien', '2022-12-15'),
('B67890', 'GInd-ERP', 'Systèmes ERP (SAP)', 5, '2022-2023', 'S5', 15.5, 1.00, 'Bien', '2022-12-18'),
('B67890', 'GInd-AUTOMAT', 'Automatisation Industrielle', 5, '2022-2023', 'S5', 16.0, 1.00, 'Bien', '2022-12-20'),
('B67890', 'GInd-RH', 'Gestion des Ressources Humaines', 5, '2022-2023', 'S5', 15.5, 1.00, 'Bien', '2022-12-22'),
('B67890', 'GInd-ANG3', 'Anglais', 5, '2022-2023', 'S5', 16.0, 1.00, 'Bien', '2022-12-10'),
-- S6
('B67890', 'GInd-STRAT', 'Stratégie d\'Entreprise', 6, '2022-2023', 'S6', 16.0, 1.00, 'Bien', '2023-05-15'),
('B67890', 'GInd-LEAN', 'Lean Manufacturing', 6, '2022-2023', 'S6', 16.0, 1.00, 'Bien', '2023-05-18'),
('B67890', 'GInd-SIMUL1', 'Simulation de Processus', 6, '2022-2023', 'S6', 16.0, 1.00, 'Bien', '2023-05-20'),
('B67890', 'GInd-INNOV', 'Innovation et Créativité', 6, '2022-2023', 'S6', 16.0, 1.00, 'Bien', '2023-05-22'),
('B67890', 'GInd-PROJ1', 'Projet Industriel', 6, '2022-2023', 'S6', 16.0, 1.00, 'Bien', '2023-05-10'),
-- S7
('B67890', 'GInd-SIMUL2', 'Simulation Avancée', 7, '2023-2024', 'S7', 16.25, 1.00, 'Bien', '2023-12-15'),
('B67890', 'GInd-IA', 'Intelligence Artificielle Appliquée', 7, '2023-2024', 'S7', 16.25, 1.00, 'Bien', '2023-12-18'),
('B67890', 'GInd-DECISION', 'Aide à la Décision', 7, '2023-2024', 'S7', 16.25, 1.00, 'Bien', '2023-12-20'),
('B67890', 'GInd-INTERNAT', 'Commerce International', 7, '2023-2024', 'S7', 16.25, 1.00, 'Bien', '2023-12-22'),
('B67890', 'GInd-PROJ2', 'Projet de Recherche', 7, '2023-2024', 'S7', 16.25, 1.00, 'Bien', '2023-12-10'),
-- S8
('B67890', 'GInd-PFA', 'Projet de Fin d\'Année (PFA)', 8, '2023-2024', 'S8', 16.5, 1.00, 'Bien', '2024-05-15'),
('B67890', 'GInd-ENTREP', 'Entrepreneuriat', 8, '2023-2024', 'S8', 16.5, 1.00, 'Bien', '2024-05-18'),
-- S9
('B67890', 'GInd-ADVANCED', 'Technologies Avancées', 9, '2024-2025', 'S9', 16.75, 1.00, 'Bien', '2024-12-15'),
('B67890', 'GInd-ETHIQUE', 'Éthique Professionnelle', 9, '2024-2025', 'S9', 16.75, 1.00, 'Bien', '2024-12-18'),
('B67890', 'GInd-PREP-PFE', 'Préparation au PFE', 9, '2024-2025', 'S9', 16.75, 1.00, 'Bien', '2024-12-20'),
-- S10
('B67890', 'GInd-PFE', 'Projet de Fin d\'Études (PFE)', 10, '2024-2025', 'S10', 17.0, 1.00, 'Très Bien', '2025-05-15');

-- Notes pour Douae Elassal (C11223) - Génie Civil - S2 à S8
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
-- S2
('C11223', 'GC-MATH2', 'Mathématiques 2', 2, '2021-2022', 'S2', 17.0, 1.00, 'Très Bien', '2022-05-15'),
('C11223', 'GC-PHYS2', 'Mécanique des Solides', 2, '2021-2022', 'S2', 17.0, 1.00, 'Très Bien', '2022-05-18'),
('C11223', 'GC-MAT2', 'Matériaux Avancés', 2, '2021-2022', 'S2', 17.0, 1.00, 'Très Bien', '2022-05-20'),
('C11223', 'GC-STRUCT1', 'Résistance des Matériaux', 2, '2021-2022', 'S2', 17.0, 1.00, 'Très Bien', '2022-05-22'),
('C11223', 'GC-COMM1', 'Communication', 2, '2021-2022', 'S2', 17.0, 1.00, 'Très Bien', '2022-05-10'),
-- S3
('C11223', 'GC-MATH3', 'Mathématiques 3', 3, '2022-2023', 'S3', 16.75, 1.00, 'Bien', '2022-12-15'),
('C11223', 'GC-STRUCT2', 'Structures Métalliques', 3, '2022-2023', 'S3', 16.75, 1.00, 'Bien', '2022-12-18'),
('C11223', 'GC-BETON1', 'Béton Armé 1', 3, '2022-2023', 'S3', 16.75, 1.00, 'Bien', '2022-12-20'),
('C11223', 'GC-TOPOG', 'Topographie', 3, '2022-2023', 'S3', 16.75, 1.00, 'Bien', '2022-12-22'),
('C11223', 'GC-ANG2', 'Anglais', 3, '2022-2023', 'S3', 16.75, 1.00, 'Bien', '2022-12-10'),
-- S4
('C11223', 'GC-MATH4', 'Mathématiques 4', 4, '2022-2023', 'S4', 17.25, 1.00, 'Très Bien', '2023-05-15'),
('C11223', 'GC-STRUCT3', 'Structures en Béton', 4, '2022-2023', 'S4', 17.25, 1.00, 'Très Bien', '2023-05-18'),
('C11223', 'GC-ROUTES', 'Routes et Chaussées', 4, '2022-2023', 'S4', 17.25, 1.00, 'Très Bien', '2023-05-20'),
('C11223', 'GC-HYDRAU', 'Hydraulique', 4, '2022-2023', 'S4', 17.25, 1.00, 'Très Bien', '2023-05-22'),
('C11223', 'GC-COMM2', 'Communication 2', 4, '2022-2023', 'S4', 17.25, 1.00, 'Très Bien', '2023-05-10'),
-- S5
('C11223', 'GC-STRUCT4', 'Structures Avancées', 5, '2023-2024', 'S5', 17.0, 1.00, 'Très Bien', '2023-12-15'),
('C11223', 'GC-FOND', 'Géotechnique et Fondations', 5, '2023-2024', 'S5', 17.0, 1.00, 'Très Bien', '2023-12-18'),
('C11223', 'GC-INSTALL', 'Installations Techniques', 5, '2023-2024', 'S5', 17.0, 1.00, 'Très Bien', '2023-12-20'),
('C11223', 'GC-GEST-CHANT', 'Gestion de Chantier', 5, '2023-2024', 'S5', 17.0, 1.00, 'Très Bien', '2023-12-22'),
('C11223', 'GC-ANG3', 'Anglais', 5, '2023-2024', 'S5', 17.0, 1.00, 'Très Bien', '2023-12-10'),
-- S6
('C11223', 'GC-STRUCT5', 'Calcul des Structures', 6, '2023-2024', 'S6', 17.5, 1.00, 'Très Bien', '2024-05-15'),
('C11223', 'GC-ENVIRON', 'Environnement et Développement Durable', 6, '2023-2024', 'S6', 17.5, 1.00, 'Très Bien', '2024-05-18'),
('C11223', 'GC-SECU', 'Sécurité et Prévention', 6, '2023-2024', 'S6', 17.5, 1.00, 'Très Bien', '2024-05-20'),
('C11223', 'GC-PROJ1', 'Projet de Conception', 6, '2023-2024', 'S6', 17.5, 1.00, 'Très Bien', '2024-05-22'),
('C11223', 'GC-ANG4', 'Anglais', 6, '2023-2024', 'S6', 17.5, 1.00, 'Très Bien', '2024-05-10'),
-- S7
('C11223', 'GC-STRUCT6', 'Structures Spéciales', 7, '2024-2025', 'S7', 17.25, 1.00, 'Très Bien', '2024-12-15'),
('C11223', 'GC-URBAN', 'Urbanisme et Aménagement', 7, '2024-2025', 'S7', 17.25, 1.00, 'Très Bien', '2024-12-18'),
('C11223', 'GC-REGLEM', 'Réglementation et Normes', 7, '2024-2025', 'S7', 17.25, 1.00, 'Très Bien', '2024-12-20'),
('C11223', 'GC-PROJ2', 'Projet Intégré', 7, '2024-2025', 'S7', 17.25, 1.00, 'Très Bien', '2024-12-22'),
('C11223', 'GC-ANG5', 'Anglais', 7, '2024-2025', 'S7', 17.25, 1.00, 'Très Bien', '2024-12-10'),
-- S8
('C11223', 'GC-PFA', 'Projet de Fin d\'Année (PFA)', 8, '2024-2025', 'S8', 17.75, 1.00, 'Très Bien', '2025-05-15'),
('C11223', 'GC-ENTREP', 'Entrepreneuriat', 8, '2024-2025', 'S8', 17.75, 1.00, 'Très Bien', '2025-05-18');

-- Notes pour Youssef Alami (D44556) - Génie Électrique - S2 à S10
INSERT INTO `note` (`apogee_number`, `code_module`, `nom_module`, `niveau_id`, `annee_universitaire`, `semestre`, `note`, `coefficient`, `mention`, `date_examen`) VALUES
-- S2
('D44556', 'GE-MATH2', 'Mathématiques 2', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-15'),
('D44556', 'GE-ELEC3', 'Électrotechnique', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-18'),
('D44556', 'GE-ELEC4', 'Électronique Numérique', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-20'),
('D44556', 'GE-SIGNAUX', 'Traitement du Signal', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-22'),
('D44556', 'GE-COMM1', 'Communication', 2, '2020-2021', 'S2', 15.5, 1.00, 'Bien', '2021-05-10'),
-- S3
('D44556', 'GE-MATH3', 'Mathématiques 3', 3, '2021-2022', 'S3', 15.75, 1.00, 'Bien', '2021-12-15'),
('D44556', 'GE-AUTOMAT1', 'Automatisme Industriel', 3, '2021-2022', 'S3', 15.75, 1.00, 'Bien', '2021-12-18'),
('D44556', 'GE-MACHINES', 'Machines Électriques', 3, '2021-2022', 'S3', 15.75, 1.00, 'Bien', '2021-12-20'),
('D44556', 'GE-ENERGIE', 'Énergies Renouvelables', 3, '2021-2022', 'S3', 15.75, 1.00, 'Bien', '2021-12-22'),
('D44556', 'GE-ANG2', 'Anglais', 3, '2021-2022', 'S3', 15.75, 1.00, 'Bien', '2021-12-10'),
-- S4
('D44556', 'GE-MATH4', 'Mathématiques 4', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-15'),
('D44556', 'GE-RESEAU', 'Réseaux Électriques', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-18'),
('D44556', 'GE-CONTROL', 'Systèmes de Contrôle', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-20'),
('D44556', 'GE-INSTALL', 'Installations Électriques', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-22'),
('D44556', 'GE-COMM2', 'Communication 2', 4, '2021-2022', 'S4', 16.0, 1.00, 'Bien', '2022-05-10'),
-- S5
('D44556', 'GE-AUTOMAT2', 'Automatisme Avancé', 5, '2022-2023', 'S5', 16.25, 1.00, 'Bien', '2022-12-15'),
('D44556', 'GE-PLC', 'Programmation PLC', 5, '2022-2023', 'S5', 16.25, 1.00, 'Bien', '2022-12-18'),
('D44556', 'GE-SECU', 'Sécurité Électrique', 5, '2022-2023', 'S5', 16.25, 1.00, 'Bien', '2022-12-20'),
('D44556', 'GE-GEST', 'Gestion de Projet Électrique', 5, '2022-2023', 'S5', 16.25, 1.00, 'Bien', '2022-12-22'),
('D44556', 'GE-ANG3', 'Anglais', 5, '2022-2023', 'S5', 16.25, 1.00, 'Bien', '2022-12-10'),
-- S6
('D44556', 'GE-SMART', 'Smart Grid et Réseaux Intelligents', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-15'),
('D44556', 'GE-IOT', 'Internet des Objets (IoT)', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-18'),
('D44556', 'GE-PROJ1', 'Projet Électrique', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-20'),
('D44556', 'GE-INNOV', 'Innovation Technologique', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-22'),
('D44556', 'GE-ANG4', 'Anglais', 6, '2022-2023', 'S6', 16.5, 1.00, 'Bien', '2023-05-10'),
-- S7
('D44556', 'GE-ADVANCED', 'Technologies Avancées', 7, '2023-2024', 'S7', 16.75, 1.00, 'Bien', '2023-12-15'),
('D44556', 'GE-IA', 'Intelligence Artificielle Appliquée', 7, '2023-2024', 'S7', 16.75, 1.00, 'Bien', '2023-12-18'),
('D44556', 'GE-PROJ2', 'Projet de Recherche', 7, '2023-2024', 'S7', 16.75, 1.00, 'Bien', '2023-12-20'),
('D44556', 'GE-DECISION', 'Aide à la Décision', 7, '2023-2024', 'S7', 16.75, 1.00, 'Bien', '2023-12-22'),
('D44556', 'GE-ANG5', 'Anglais', 7, '2023-2024', 'S7', 16.75, 1.00, 'Bien', '2023-12-10'),
-- S8
('D44556', 'GE-PFA', 'Projet de Fin d\'Année (PFA)', 8, '2023-2024', 'S8', 17.0, 1.00, 'Très Bien', '2024-05-15'),
('D44556', 'GE-ENTREP', 'Entrepreneuriat', 8, '2023-2024', 'S8', 17.0, 1.00, 'Très Bien', '2024-05-18'),
-- S9
('D44556', 'GE-ADVANCED2', 'Technologies Émergentes', 9, '2024-2025', 'S9', 17.25, 1.00, 'Très Bien', '2024-12-15'),
('D44556', 'GE-RECHERCHE', 'Méthodologie de Recherche', 9, '2024-2025', 'S9', 17.25, 1.00, 'Très Bien', '2024-12-18'),
('D44556', 'GE-ETHIQUE', 'Éthique Professionnelle', 9, '2024-2025', 'S9', 17.25, 1.00, 'Très Bien', '2024-12-20'),
('D44556', 'GE-PREP-PFE', 'Préparation au PFE', 9, '2024-2025', 'S9', 17.25, 1.00, 'Très Bien', '2024-12-22'),
-- S10
('D44556', 'GE-PFE', 'Projet de Fin d\'Études (PFE)', 10, '2024-2025', 'S10', 17.5, 1.00, 'Très Bien', '2025-05-15');

-- =====================================================
-- RÉSULTATS PAR SEMESTRE
-- =====================================================

-- Résultats pour Fatima Elafi (A12345)
INSERT INTO `resultat_semestre` (`apogee_number`, `niveau_id`, `annee_universitaire`, `moyenne_semestre`, `statut`, `mention`) VALUES
('A12345', 1, '2020-2021', 15.60, 'Validé', 'Bien'),
('A12345', 2, '2020-2021', 15.80, 'Validé', 'Bien'),
('A12345', 3, '2021-2022', 15.75, 'Validé', 'Bien'),
('A12345', 4, '2021-2022', 16.25, 'Validé', 'Bien'),
('A12345', 5, '2022-2023', 16.50, 'Validé', 'Bien'),
('A12345', 6, '2022-2023', 17.00, 'Validé', 'Très Bien'),
('A12345', 7, '2023-2024', 16.75, 'Validé', 'Bien'),
('A12345', 8, '2023-2024', 17.25, 'Validé', 'Très Bien'),
('A12345', 9, '2024-2025', 17.00, 'Validé', 'Très Bien'),
('A12345', 10, '2024-2025', 0.00, 'En cours', NULL);

-- Résultats pour Ahmed Benali (B67890)
INSERT INTO `resultat_semestre` (`apogee_number`, `niveau_id`, `annee_universitaire`, `moyenne_semestre`, `statut`, `mention`) VALUES
('B67890', 1, '2020-2021', 14.80, 'Validé', 'Bien'),
('B67890', 2, '2020-2021', 15.00, 'Validé', 'Bien'),
('B67890', 3, '2021-2022', 15.25, 'Validé', 'Bien'),
('B67890', 4, '2021-2022', 15.50, 'Validé', 'Bien'),
('B67890', 5, '2022-2023', 15.75, 'Validé', 'Bien'),
('B67890', 6, '2022-2023', 16.00, 'Validé', 'Bien'),
('B67890', 7, '2023-2024', 16.25, 'Validé', 'Bien'),
('B67890', 8, '2023-2024', 16.50, 'Validé', 'Bien'),
('B67890', 9, '2024-2025', 16.75, 'Validé', 'Bien'),
('B67890', 10, '2024-2025', 17.00, 'Validé', 'Très Bien');

-- Résultats pour Douae Elassal (C11223)
INSERT INTO `resultat_semestre` (`apogee_number`, `niveau_id`, `annee_universitaire`, `moyenne_semestre`, `statut`, `mention`) VALUES
('C11223', 1, '2021-2022', 16.70, 'Validé', 'Bien'),
('C11223', 2, '2021-2022', 17.00, 'Validé', 'Très Bien'),
('C11223', 3, '2022-2023', 16.75, 'Validé', 'Bien'),
('C11223', 4, '2022-2023', 17.25, 'Validé', 'Très Bien'),
('C11223', 5, '2023-2024', 17.00, 'Validé', 'Très Bien'),
('C11223', 6, '2023-2024', 17.50, 'Validé', 'Très Bien'),
('C11223', 7, '2024-2025', 17.25, 'Validé', 'Très Bien'),
('C11223', 8, '2024-2025', 17.75, 'Validé', 'Très Bien');

-- Résultats pour Youssef Alami (D44556)
INSERT INTO `resultat_semestre` (`apogee_number`, `niveau_id`, `annee_universitaire`, `moyenne_semestre`, `statut`, `mention`) VALUES
('D44556', 1, '2020-2021', 15.40, 'Validé', 'Bien'),
('D44556', 2, '2020-2021', 15.50, 'Validé', 'Bien'),
('D44556', 3, '2021-2022', 15.75, 'Validé', 'Bien'),
('D44556', 4, '2021-2022', 16.00, 'Validé', 'Bien'),
('D44556', 5, '2022-2023', 16.25, 'Validé', 'Bien'),
('D44556', 6, '2022-2023', 16.50, 'Validé', 'Bien'),
('D44556', 7, '2023-2024', 16.75, 'Validé', 'Bien'),
('D44556', 8, '2023-2024', 17.00, 'Validé', 'Très Bien'),
('D44556', 9, '2024-2025', 17.25, 'Validé', 'Très Bien'),
('D44556', 10, '2024-2025', 17.50, 'Validé', 'Très Bien');

-- =====================================================
-- RÉSULTATS PAR ANNÉE
-- =====================================================

-- Résultats annuels pour Fatima Elafi (A12345)
INSERT INTO `resultat_annee` (`apogee_number`, `annee_universitaire`, `niveau`, `filiere`, `moyenne_generale`, `statut`, `mention`) VALUES
('A12345', '2020-2021', '2AP1', 'Cycle Préparatoire', 15.70, 'Réussi', 'Bien'),
('A12345', '2021-2022', '2AP2', 'Cycle Préparatoire', 16.00, 'Réussi', 'Bien'),
('A12345', '2022-2023', 'CI1', 'Génie Informatique', 16.75, 'Réussi', 'Bien'),
('A12345', '2023-2024', 'CI2', 'Génie Informatique', 17.00, 'Réussi', 'Très Bien'),
('A12345', '2024-2025', 'CI3', 'Génie Informatique', 17.25, 'En cours', 'Très Bien');

-- Résultats annuels pour Ahmed Benali (B67890)
INSERT INTO `resultat_annee` (`apogee_number`, `annee_universitaire`, `niveau`, `filiere`, `moyenne_generale`, `statut`, `mention`) VALUES
('B67890', '2020-2021', '2AP1', 'Cycle Préparatoire', 14.90, 'Réussi', 'Bien'),
('B67890', '2021-2022', '2AP2', 'Cycle Préparatoire', 15.38, 'Réussi', 'Bien'),
('B67890', '2022-2023', 'CI1', 'Génie Industriel', 15.88, 'Réussi', 'Bien'),
('B67890', '2023-2024', 'CI2', 'Génie Industriel', 16.38, 'Réussi', 'Bien'),
('B67890', '2024-2025', 'CI3', 'Génie Industriel', 16.88, 'Réussi', 'Bien');

-- Résultats annuels pour Douae Elassal (C11223)
INSERT INTO `resultat_annee` (`apogee_number`, `annee_universitaire`, `niveau`, `filiere`, `moyenne_generale`, `statut`, `mention`) VALUES
('C11223', '2021-2022', '2AP1', 'Cycle Préparatoire', 16.85, 'Réussi', 'Bien'),
('C11223', '2022-2023', '2AP2', 'Cycle Préparatoire', 17.00, 'Réussi', 'Très Bien'),
('C11223', '2023-2024', 'CI1', 'Génie Civil', 17.25, 'Réussi', 'Très Bien'),
('C11223', '2024-2025', 'CI2', 'Génie Civil', 17.50, 'En cours', 'Très Bien');

-- Résultats annuels pour Youssef Alami (D44556)
INSERT INTO `resultat_annee` (`apogee_number`, `annee_universitaire`, `niveau`, `filiere`, `moyenne_generale`, `statut`, `mention`) VALUES
('D44556', '2020-2021', '2AP1', 'Cycle Préparatoire', 15.45, 'Réussi', 'Bien'),
('D44556', '2021-2022', '2AP2', 'Cycle Préparatoire', 15.88, 'Réussi', 'Bien'),
('D44556', '2022-2023', 'CI1', 'Génie Électrique', 16.38, 'Réussi', 'Bien'),
('D44556', '2023-2024', 'CI2', 'Génie Électrique', 16.88, 'Réussi', 'Bien'),
('D44556', '2024-2025', 'CI3', 'Génie Électrique', 17.38, 'Réussi', 'Très Bien');

-- =====================================================
-- FIN DU SCRIPT
-- =====================================================

SELECT '✅ Base de données optimisée créée avec succès !' AS message;
SELECT '📊 Statistiques:' AS info;
SELECT COUNT(*) AS nombre_filieres FROM `filiere`;
SELECT COUNT(*) AS nombre_niveaux FROM `niveau`;
SELECT COUNT(*) AS nombre_etudiants FROM `etudiant`;
SELECT COUNT(*) AS nombre_modules FROM `module`;
SELECT COUNT(*) AS nombre_inscriptions FROM `inscription`;
SELECT COUNT(*) AS nombre_notes FROM `note`;
SELECT COUNT(*) AS nombre_resultats_annee FROM `resultat_annee`;
SELECT COUNT(*) AS nombre_reclamations FROM `reclamation`;

