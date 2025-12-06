  -- ============================================================================
-- BASE DE DONNÉES COMPLÈTE - SYSTÈME DE GESTION ÉTUDIANTE (ENSA)
-- ============================================================================
-- Ce fichier contient la structure complète de la base de données
-- avec toutes les tables, données de test, vues, procédures et triggers
-- Système : ENSA (Écoles Nationales des Sciences Appliquées)
-- Niveaux : CPI1, CPI2, 1A, 2A, 3A, M1, M2
-- ============================================================================
 

-- Créer la base de données
CREATE DATABASE IF NOT EXISTS student_admin_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE student_admin_db;

-- ============================================================================
-- 1. Table des Administrateurs
-- ============================================================================
CREATE TABLE IF NOT EXISTS administrateur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE COMMENT 'Identifiant de connexion',
    password_hash VARCHAR(255) NULL COMMENT 'Mot de passe hashé (optionnel, peut utiliser admin123)',
    email VARCHAR(100) NULL COMMENT 'Email de l''administrateur',
    nom VARCHAR(100) NULL COMMENT 'Nom de l''administrateur',
    prenom VARCHAR(100) NULL COMMENT 'Prénom de l''administrateur',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. Table des Étudiants
-- ============================================================================
CREATE TABLE IF NOT EXISTS etudiant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL UNIQUE COMMENT 'Numéro Apogée unique',
    nom VARCHAR(100) NOT NULL COMMENT 'Nom de l''étudiant',
    prenom VARCHAR(100) NOT NULL COMMENT 'Prénom de l''étudiant',
    email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Email institutionnel',
    cin VARCHAR(20) NOT NULL UNIQUE COMMENT 'Carte d''identité nationale',
    date_naissance DATE NULL COMMENT 'Date de naissance',
    telephone VARCHAR(20) NULL COMMENT 'Numéro de téléphone',
    adresse TEXT NULL COMMENT 'Adresse complète',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX IF NOT EXISTS idx_etudiant_email ON etudiant(email);
CREATE INDEX IF NOT EXISTS idx_etudiant_cin ON etudiant(cin);

-- ============================================================================
-- 3. Table des Demandes de Documents
-- ============================================================================
CREATE TABLE IF NOT EXISTS demande (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL COMMENT 'Référence à l''étudiant',
    document_type ENUM(
        'Attestation de scolarité', 
        'Attestation de réussite', 
        'Relevé de notes', 
        'Convention de stage', 
        'Autre'
    ) NOT NULL COMMENT 'Type de document demandé',
    date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Date de création de la demande',
    status ENUM('En attente', 'Acceptée', 'Refusée') DEFAULT 'En attente' COMMENT 'Statut de la demande',
    justification_refus TEXT NULL COMMENT 'Justification en cas de refus',
    additional_info JSON NULL COMMENT 'Informations supplémentaires (année, niveau, semestre, etc.)',
    document_path VARCHAR(500) NULL COMMENT 'Chemin vers le document PDF généré',
    email_sent BOOLEAN DEFAULT FALSE COMMENT 'Email envoyé à l''étudiant',
    email_sent_at TIMESTAMP NULL COMMENT 'Date d''envoi de l''email',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_demande_status (status),
    INDEX idx_demande_date (date_demande),
    INDEX idx_demande_apogee (apogee_number),
    INDEX idx_demande_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. Table des Réclamations
-- ============================================================================
CREATE TABLE IF NOT EXISTS reclamation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    demande_id INT NOT NULL COMMENT 'Référence à la demande concernée',
    motif VARCHAR(255) NOT NULL COMMENT 'Motif de la réclamation',
    description TEXT NOT NULL COMMENT 'Description détaillée de la réclamation',
    status ENUM('En attente', 'En cours', 'Résolue', 'Rejetée') DEFAULT 'En attente',
    reponse TEXT NULL COMMENT 'Réponse de l''administration',
    date_reclamation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_reponse TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (demande_id) REFERENCES demande(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reclamation_status (status),
    INDEX idx_reclamation_demande (demande_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. Table des Inscriptions (SYSTÈME ENSA)
-- ============================================================================
CREATE TABLE IF NOT EXISTS inscription (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL COMMENT 'Référence à l''étudiant',
    annee_universitaire VARCHAR(20) NOT NULL COMMENT 'Ex: 2024-2025',
    niveau VARCHAR(50) NOT NULL COMMENT 'CPI1, CPI2, 1A, 2A, 3A, M1, M2 (système ENSA)',
    filiere VARCHAR(100) NOT NULL COMMENT 'Filière d''étude (Génie Informatique, Génie Mécanique, Génie Électrique, Génie Civil, etc.)',
    statut ENUM('Inscrit', 'Réinscrit', 'Diplômé', 'Abandon') DEFAULT 'Inscrit',
    date_inscription DATE NOT NULL,
    date_fin DATE NULL COMMENT 'Date de fin d''inscription',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_inscription_apogee (apogee_number),
    INDEX idx_inscription_annee (annee_universitaire),
    INDEX idx_inscription_niveau (niveau),
    INDEX idx_inscription_statut (statut),
    INDEX idx_inscription_filiere (filiere),
    UNIQUE KEY unique_inscription (apogee_number, annee_universitaire, niveau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. Table des Modules
-- ============================================================================
CREATE TABLE IF NOT EXISTS module (
    code_module VARCHAR(20) PRIMARY KEY COMMENT 'Code unique du module',
    nom_module VARCHAR(255) NOT NULL COMMENT 'Nom complet du module',
    coefficient DECIMAL(3,2) DEFAULT 1.00 COMMENT 'Coefficient du module',
    credit INT DEFAULT 0 COMMENT 'Crédits ECTS',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. Table des Notes
-- ============================================================================
CREATE TABLE IF NOT EXISTS note (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL,
    annee_universitaire VARCHAR(20) NOT NULL,
    semestre ENUM('S1', 'S2', 'S3', 'S4', 'S5', 'S6') NOT NULL,
    code_module VARCHAR(20) NOT NULL,
    nom_module VARCHAR(255) NOT NULL,
    note DECIMAL(4,2) NOT NULL COMMENT 'Note sur 20',
    coefficient DECIMAL(3,2) DEFAULT 1.00,
    credit INT DEFAULT 0,
    mention ENUM('Très Bien', 'Bien', 'Assez Bien', 'Passable', 'Ajourné') NULL,
    date_examen DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (code_module) REFERENCES module(code_module) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_note_apogee (apogee_number),
    INDEX idx_note_annee (annee_universitaire),
    INDEX idx_note_semestre (semestre),
    INDEX idx_note_module (code_module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. Table des Résultats par Année (SYSTÈME ENSA)
-- ============================================================================
CREATE TABLE IF NOT EXISTS resultat_annee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL,
    annee_universitaire VARCHAR(20) NOT NULL,
    niveau VARCHAR(50) NOT NULL COMMENT 'CPI1, CPI2, 1A, 2A, 3A, M1, M2 (système ENSA)',
    filiere VARCHAR(100) NULL COMMENT 'Filière (Génie Informatique, Génie Mécanique, etc.) - NULL pour CPI',
    moyenne_generale DECIMAL(4,2) NULL,
    moyenne_s1 DECIMAL(4,2) NULL,
    moyenne_s2 DECIMAL(4,2) NULL,
    statut ENUM('Réussi', 'Ajourné', 'Redoublant', 'En cours') DEFAULT 'En cours',
    mention ENUM('Très Bien', 'Bien', 'Assez Bien', 'Passable', 'Ajourné') NULL,
    date_validation DATE NULL COMMENT 'Date de validation du résultat',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_resultat_apogee (apogee_number),
    INDEX idx_resultat_annee (annee_universitaire),
    INDEX idx_resultat_niveau (niveau),
    INDEX idx_resultat_statut (statut),
    INDEX idx_resultat_filiere (filiere),
    UNIQUE KEY unique_resultat (apogee_number, annee_universitaire, niveau)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 9. Table des Stages
-- ============================================================================
CREATE TABLE IF NOT EXISTS stage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL,
    annee_universitaire VARCHAR(20) NOT NULL,
    nom_entreprise VARCHAR(255) NOT NULL,
    adresse_entreprise VARCHAR(255) NULL,
    telephone_entreprise VARCHAR(20) NULL,
    email_entreprise VARCHAR(255) NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    duree_semaines INT NOT NULL,
    sujet_stage TEXT NULL,
    tuteur_entreprise VARCHAR(255) NULL,
    tuteur_universitaire VARCHAR(255) NULL,
    statut ENUM('En attente', 'Approuvé', 'En cours', 'Terminé', 'Annulé') DEFAULT 'En attente',
    convention_generee BOOLEAN DEFAULT FALSE,
    date_convention DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_stage_apogee (apogee_number),
    INDEX idx_stage_annee (annee_universitaire),
    INDEX idx_stage_statut (statut),
    INDEX idx_stage_dates (date_debut, date_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 10. Table des Diplômes
-- ============================================================================
CREATE TABLE IF NOT EXISTS diplome (
    id INT AUTO_INCREMENT PRIMARY KEY,
    apogee_number VARCHAR(20) NOT NULL,
    type_diplome ENUM('Licence', 'Master', 'Doctorat') NOT NULL,
    filiere VARCHAR(100) NOT NULL,
    annee_obtention YEAR NOT NULL,
    mention ENUM('Très Bien', 'Bien', 'Assez Bien', 'Passable') NULL,
    date_emission DATE DEFAULT (CURRENT_DATE),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (apogee_number) REFERENCES etudiant(apogee_number) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_diplome_apogee (apogee_number),
    INDEX idx_diplome_annee (annee_obtention),
    UNIQUE KEY unique_diplome (apogee_number, type_diplome, annee_obtention)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================================================

-- ----------------------------------------------------------------------------
-- Administrateurs
-- ----------------------------------------------------------------------------
INSERT INTO administrateur (login, password_hash, email, nom, prenom) VALUES
('admin@ensa.ma', NULL, 'admin@ensa.ma', 'Administrateur', 'Principal')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ----------------------------------------------------------------------------
-- Étudiants
-- ----------------------------------------------------------------------------
INSERT INTO etudiant (apogee_number, nom, prenom, email, cin, date_naissance, telephone, adresse) VALUES
('A12345', 'Alami', 'Ahmed', 'ahmed.alami@student.ensa.ma', 'AB123456', '2000-05-15', '0612345678', 'Tétouan, Maroc'),
('B67890', 'Benali', 'Fatima', 'fatima.benali@student.ensa.ma', 'CD234567', '2001-03-20', '0623456789', 'Tanger, Maroc'),
('C11223', 'Chraibi', 'Youssef', 'youssef.chraibi@student.ensa.ma', 'EF345678', '2002-07-10', '0634567890', 'Casablanca, Maroc'),
('D44556', 'Dahbi', 'Sara', 'sara.dahbi@student.ensa.ma', 'GH456789', '2001-11-25', '0645678901', 'Rabat, Maroc'),
('E78901', 'El Fassi', 'Omar', 'omar.elfassi@student.ensa.ma', 'IJ567890', '1999-09-30', '0656789012', 'Fès, Maroc'),
('F23456', 'Fadili', 'Layla', 'layla.fadili@student.ensa.ma', 'KL678901', '2003-01-12', '0667890123', 'Meknès, Maroc'),
('G56789', 'Ghazali', 'Mehdi', 'mehdi.ghazali@student.ensa.ma', 'MN789012', '2000-08-18', '0678901234', 'Agadir, Maroc'),
('H89012', 'Hassani', 'Nadia', 'nadia.hassani@student.ensa.ma', 'OP890123', '1999-12-05', '0689012345', 'Marrakech, Maroc'),
('I34567', 'Idrissi', 'Karim', 'karim.idrissi@student.ensa.ma', 'QR901234', '2002-04-22', '0690123456', 'Oujda, Maroc'),
('J67890', 'Jazouli', 'Amina', 'amina.jazouli@student.ensa.ma', 'ST012345', '1998-06-14', '0601234567', 'Tétouan, Maroc')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ----------------------------------------------------------------------------
-- Modules
-- ----------------------------------------------------------------------------
INSERT INTO module (code_module, nom_module, coefficient, credit) VALUES
-- Modules CPI
('MAT101', 'Mathématiques 1', 4.0, 6),
('MAT102', 'Mathématiques 2', 4.0, 6),
('PHY101', 'Physique 1', 3.0, 5),
('PHY102', 'Physique 2', 3.0, 5),
('CHM101', 'Chimie 1', 2.0, 3),
('INF101', 'Informatique', 3.0, 4),
('INF202', 'Structures de données', 4.0, 5),
('ANG101', 'Anglais', 2.0, 2),
('ANG102', 'Anglais 2', 2.0, 2),
('MAT201', 'Mathématiques 3', 4.0, 6),
('MAT202', 'Mathématiques 4', 4.0, 6),
('PHY201', 'Physique 3', 3.0, 5),
('PHY202', 'Physique 4', 3.0, 5),
('INF201', 'Algorithmique', 4.0, 5),
('ELC201', 'Électricité', 3.0, 4),
-- Modules Génie Informatique
('INF301', 'Base de données', 3.0, 4),
('INF302', 'Programmation Orientée Objet', 4.0, 5),
('INF303', 'Réseaux informatiques', 3.0, 4),
('INF304', 'Systèmes d''exploitation', 2.0, 3),
('INF305', 'Développement Web', 4.0, 5),
('INF306', 'Intelligence Artificielle', 3.0, 4),
('INF307', 'Sécurité informatique', 2.0, 3),
('INF501', 'Projet de fin d''études', 6.0, 10),
('INF502', 'Stage en entreprise', 4.0, 8),
('INF503', 'Sécurité informatique avancée', 3.0, 4),
('INF504', 'Intelligence Artificielle', 4.0, 5),
('INF601', 'Architecture logicielle', 4.0, 5),
('INF602', 'Big Data', 3.0, 4),
('INF603', 'Cloud Computing', 3.0, 4),
-- Modules Génie Mécanique
('MEC301', 'Mécanique des solides', 4.0, 5),
('MEC302', 'Résistance des matériaux', 4.0, 5),
('MEC303', 'Thermodynamique', 3.0, 4),
('MEC304', 'Mécanique des fluides', 4.0, 5),
('MEC305', 'Conception mécanique', 3.0, 4),
('MEC401', 'Mécanique avancée', 4.0, 5),
('MEC402', 'CAO/DAO', 3.0, 4),
('MEC403', 'Fabrication mécanique', 4.0, 5),
('MEC404', 'Automatique', 3.0, 4),
-- Modules Génie Électrique
('ELC301', 'Électronique analogique', 4.0, 5),
('ELC302', 'Électrotechnique', 4.0, 5),
('ELC303', 'Automatique', 3.0, 4),
('ELC501', 'Projet de fin d''études', 6.0, 10),
('ELC502', 'Énergies renouvelables', 4.0, 5)
ON DUPLICATE KEY UPDATE nom_module = VALUES(nom_module);

-- ----------------------------------------------------------------------------
-- Inscriptions (SYSTÈME ENSA)
-- ----------------------------------------------------------------------------
INSERT INTO inscription (apogee_number, annee_universitaire, niveau, filiere, statut, date_inscription) VALUES
-- Étudiant A12345 : Parcours complet ENSA
('A12345', '2022-2023', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2022-09-01'),
('A12345', '2023-2024', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2023-09-01'),
('A12345', '2024-2025', '1A', 'Génie Informatique', 'Inscrit', '2024-09-01'),

-- Étudiant B67890 : En 2A Génie Mécanique
('B67890', '2020-2021', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2020-09-01'),
('B67890', '2021-2022', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2021-09-01'),
('B67890', '2022-2023', '1A', 'Génie Mécanique', 'Diplômé', '2022-09-01'),
('B67890', '2023-2024', '2A', 'Génie Mécanique', 'Diplômé', '2023-09-01'),
('B67890', '2024-2025', '2A', 'Génie Mécanique', 'Inscrit', '2024-09-01'),

-- Étudiant C11223 : En CPI2
('C11223', '2023-2024', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2023-09-01'),
('C11223', '2024-2025', 'CPI2', 'Cycle Préparatoire Intégré', 'Inscrit', '2024-09-01'),

-- Étudiant D44556 : En 1A Génie Électrique
('D44556', '2022-2023', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2022-09-01'),
('D44556', '2023-2024', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2023-09-01'),
('D44556', '2024-2025', '1A', 'Génie Électrique', 'Inscrit', '2024-09-01'),

-- Étudiant E78901 : En 3A Génie Informatique (dernière année cycle ingénieur)
('E78901', '2019-2020', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2019-09-01'),
('E78901', '2020-2021', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2020-09-01'),
('E78901', '2021-2022', '1A', 'Génie Informatique', 'Diplômé', '2021-09-01'),
('E78901', '2022-2023', '2A', 'Génie Informatique', 'Diplômé', '2022-09-01'),
('E78901', '2023-2024', '3A', 'Génie Informatique', 'Diplômé', '2023-09-01'),
('E78901', '2024-2025', 'M1', 'Génie Informatique', 'Inscrit', '2024-09-01'),

-- Étudiant F23456 : En CPI1 (nouveau)
('F23456', '2024-2025', 'CPI1', 'Cycle Préparatoire Intégré', 'Inscrit', '2024-09-01'),

-- Étudiant G56789 : En 2A Génie Mécanique
('G56789', '2021-2022', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2021-09-01'),
('G56789', '2022-2023', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2022-09-01'),
('G56789', '2023-2024', '1A', 'Génie Mécanique', 'Diplômé', '2023-09-01'),
('G56789', '2024-2025', '2A', 'Génie Mécanique', 'Inscrit', '2024-09-01'),

-- Étudiant H89012 : En 3A Génie Électrique
('H89012', '2020-2021', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2020-09-01'),
('H89012', '2021-2022', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2021-09-01'),
('H89012', '2022-2023', '1A', 'Génie Électrique', 'Diplômé', '2022-09-01'),
('H89012', '2023-2024', '2A', 'Génie Électrique', 'Diplômé', '2023-09-01'),
('H89012', '2024-2025', '3A', 'Génie Électrique', 'Inscrit', '2024-09-01'),

-- Étudiant I34567 : En 1A Génie Civil
('I34567', '2022-2023', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2022-09-01'),
('I34567', '2023-2024', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2023-09-01'),
('I34567', '2024-2025', '1A', 'Génie Civil', 'Inscrit', '2024-09-01'),

-- Étudiant J67890 : En M2 Génie Informatique
('J67890', '2018-2019', 'CPI1', 'Cycle Préparatoire Intégré', 'Diplômé', '2018-09-01'),
('J67890', '2019-2020', 'CPI2', 'Cycle Préparatoire Intégré', 'Diplômé', '2019-09-01'),
('J67890', '2020-2021', '1A', 'Génie Informatique', 'Diplômé', '2020-09-01'),
('J67890', '2021-2022', '2A', 'Génie Informatique', 'Diplômé', '2021-09-01'),
('J67890', '2022-2023', '3A', 'Génie Informatique', 'Diplômé', '2022-09-01'),
('J67890', '2023-2024', 'M1', 'Génie Informatique', 'Diplômé', '2023-09-01'),
('J67890', '2024-2025', 'M2', 'Génie Informatique', 'Inscrit', '2024-09-01')
ON DUPLICATE KEY UPDATE statut = VALUES(statut);

-- ----------------------------------------------------------------------------
-- Résultats (SYSTÈME ENSA)
-- ----------------------------------------------------------------------------
INSERT INTO resultat_annee (apogee_number, annee_universitaire, niveau, filiere, moyenne_generale, moyenne_s1, moyenne_s2, statut, mention) VALUES
-- Résultats CPI (Cycle Préparatoire Intégré)
('A12345', '2022-2023', 'CPI1', 'Cycle Préparatoire Intégré', 15.5, 15.0, 16.0, 'Réussi', 'Bien'),
('A12345', '2023-2024', 'CPI2', 'Cycle Préparatoire Intégré', 16.0, 16.5, 15.5, 'Réussi', 'Bien'),
('B67890', '2020-2021', 'CPI1', 'Cycle Préparatoire Intégré', 14.0, 13.5, 14.5, 'Réussi', 'Assez Bien'),
('B67890', '2021-2022', 'CPI2', 'Cycle Préparatoire Intégré', 15.0, 15.5, 14.5, 'Réussi', 'Bien'),
('C11223', '2023-2024', 'CPI1', 'Cycle Préparatoire Intégré', 13.5, 13.0, 14.0, 'Réussi', 'Passable'),
('D44556', '2022-2023', 'CPI1', 'Cycle Préparatoire Intégré', 15.0, 14.5, 15.5, 'Réussi', 'Bien'),
('D44556', '2023-2024', 'CPI2', 'Cycle Préparatoire Intégré', 15.5, 15.0, 16.0, 'Réussi', 'Bien'),

-- Résultats Cycle Ingénieur (1A, 2A, 3A)
('A12345', '2024-2025', '1A', 'Génie Informatique', 16.0, 16.5, NULL, 'En cours', NULL),
('B67890', '2022-2023', '1A', 'Génie Mécanique', 14.5, 14.0, 15.0, 'Réussi', 'Assez Bien'),
('B67890', '2023-2024', '2A', 'Génie Mécanique', 15.0, 15.5, 14.5, 'Réussi', 'Bien'),
('D44556', '2024-2025', '1A', 'Génie Électrique', 13.75, 13.75, NULL, 'En cours', NULL),
('E78901', '2021-2022', '1A', 'Génie Informatique', 16.5, 17.0, 16.0, 'Réussi', 'Bien'),
('E78901', '2022-2023', '2A', 'Génie Informatique', 17.0, 17.5, 16.5, 'Réussi', 'Bien'),
('E78901', '2023-2024', '3A', 'Génie Informatique', 17.5, 18.0, 17.0, 'Réussi', 'Très Bien'),
('G56789', '2023-2024', '1A', 'Génie Mécanique', 15.5, 16.0, 15.0, 'Réussi', 'Bien'),
('G56789', '2024-2025', '2A', 'Génie Mécanique', 16.0, 16.5, 15.5, 'En cours', NULL),
('H89012', '2022-2023', '1A', 'Génie Électrique', 15.0, 14.5, 15.5, 'Réussi', 'Bien'),
('H89012', '2023-2024', '2A', 'Génie Électrique', 15.5, 15.0, 16.0, 'Réussi', 'Bien'),
('H89012', '2024-2025', '3A', 'Génie Électrique', 16.0, 16.5, NULL, 'En cours', NULL),
('I34567', '2024-2025', '1A', 'Génie Civil', 14.0, 14.0, NULL, 'En cours', NULL),

-- Résultats Master
('E78901', '2024-2025', 'M1', 'Génie Informatique', 16.5, 17.0, 16.0, 'En cours', NULL),
('J67890', '2023-2024', 'M1', 'Génie Informatique', 17.0, 17.5, 16.5, 'Réussi', 'Bien'),
('J67890', '2024-2025', 'M2', 'Génie Informatique', 17.5, 18.0, NULL, 'En cours', NULL)
ON DUPLICATE KEY UPDATE statut = VALUES(statut);

-- ----------------------------------------------------------------------------
-- Notes (SYSTÈME ENSA)
-- ----------------------------------------------------------------------------
INSERT INTO note (apogee_number, annee_universitaire, semestre, code_module, nom_module, note, coefficient, mention) VALUES
-- Notes pour A12345 - CPI1 - 2022-2023
('A12345', '2022-2023', 'S1', 'MAT101', 'Mathématiques 1', 15.0, 4.0, 'Bien'),
('A12345', '2022-2023', 'S1', 'PHY101', 'Physique 1', 16.0, 3.0, 'Bien'),
('A12345', '2022-2023', 'S1', 'CHM101', 'Chimie 1', 14.5, 2.0, 'Assez Bien'),
('A12345', '2022-2023', 'S1', 'ANG101', 'Anglais', 15.5, 2.0, 'Bien'),
('A12345', '2022-2023', 'S2', 'MAT102', 'Mathématiques 2', 16.0, 4.0, 'Bien'),
('A12345', '2022-2023', 'S2', 'PHY102', 'Physique 2', 15.5, 3.0, 'Bien'),
('A12345', '2022-2023', 'S2', 'INF101', 'Informatique', 17.0, 3.0, 'Bien'),
('A12345', '2022-2023', 'S2', 'ANG102', 'Anglais 2', 16.0, 2.0, 'Bien'),

-- Notes pour A12345 - CPI2 - 2023-2024
('A12345', '2023-2024', 'S1', 'MAT201', 'Mathématiques 3', 16.5, 4.0, 'Bien'),
('A12345', '2023-2024', 'S1', 'PHY201', 'Physique 3', 17.0, 3.0, 'Bien'),
('A12345', '2023-2024', 'S1', 'INF201', 'Algorithmique', 18.0, 4.0, 'Très Bien'),
('A12345', '2023-2024', 'S1', 'ELC201', 'Électricité', 15.0, 3.0, 'Bien'),
('A12345', '2023-2024', 'S2', 'MAT202', 'Mathématiques 4', 15.5, 4.0, 'Bien'),
('A12345', '2023-2024', 'S2', 'INF202', 'Structures de données', 16.0, 4.0, 'Bien'),
('A12345', '2023-2024', 'S2', 'PHY202', 'Physique 4', 15.0, 3.0, 'Bien'),

-- Notes pour A12345 - 1A Génie Info - 2024-2025
('A12345', '2024-2025', 'S1', 'INF301', 'Base de données', 16.5, 3.0, 'Bien'),
('A12345', '2024-2025', 'S1', 'INF302', 'Programmation Orientée Objet', 18.0, 4.0, 'Très Bien'),
('A12345', '2024-2025', 'S1', 'INF303', 'Réseaux informatiques', 15.0, 3.0, 'Bien'),
('A12345', '2024-2025', 'S1', 'INF304', 'Systèmes d''exploitation', 14.5, 2.0, 'Assez Bien'),

-- Notes pour B67890 - 1A Génie Mécanique - 2022-2023
('B67890', '2022-2023', 'S1', 'MEC301', 'Mécanique des solides', 14.0, 4.0, 'Assez Bien'),
('B67890', '2022-2023', 'S1', 'MEC302', 'Résistance des matériaux', 15.0, 4.0, 'Bien'),
('B67890', '2022-2023', 'S1', 'MEC303', 'Thermodynamique', 14.5, 3.0, 'Assez Bien'),
('B67890', '2022-2023', 'S2', 'MEC304', 'Mécanique des fluides', 15.5, 4.0, 'Bien'),
('B67890', '2022-2023', 'S2', 'MEC305', 'Conception mécanique', 14.0, 3.0, 'Assez Bien'),

-- Notes pour B67890 - 2A Génie Mécanique - 2023-2024
('B67890', '2023-2024', 'S1', 'MEC401', 'Mécanique avancée', 15.0, 4.0, 'Bien'),
('B67890', '2023-2024', 'S1', 'MEC402', 'CAO/DAO', 15.5, 3.0, 'Bien'),
('B67890', '2023-2024', 'S2', 'MEC403', 'Fabrication mécanique', 14.5, 4.0, 'Assez Bien'),
('B67890', '2023-2024', 'S2', 'MEC404', 'Automatique', 15.0, 3.0, 'Bien'),

-- Notes pour E78901 - 3A Génie Info - 2023-2024
('E78901', '2023-2024', 'S1', 'INF501', 'Projet de fin d''études', 18.0, 6.0, 'Très Bien'),
('E78901', '2023-2024', 'S1', 'INF502', 'Stage en entreprise', 17.5, 4.0, 'Bien'),
('E78901', '2023-2024', 'S2', 'INF503', 'Sécurité informatique avancée', 17.0, 3.0, 'Bien'),
('E78901', '2023-2024', 'S2', 'INF504', 'Intelligence Artificielle', 17.5, 4.0, 'Bien'),

-- Notes pour E78901 - M1 Génie Info - 2024-2025
('E78901', '2024-2025', 'S1', 'INF601', 'Architecture logicielle', 17.0, 4.0, 'Bien'),
('E78901', '2024-2025', 'S1', 'INF602', 'Big Data', 16.5, 3.0, 'Bien'),
('E78901', '2024-2025', 'S1', 'INF603', 'Cloud Computing', 17.5, 3.0, 'Bien'),

-- Notes pour C11223 - CPI2 - 2024-2025
('C11223', '2024-2025', 'S1', 'MAT201', 'Mathématiques 3', 13.0, 4.0, 'Passable'),
('C11223', '2024-2025', 'S1', 'PHY201', 'Physique 3', 14.5, 3.0, 'Assez Bien'),
('C11223', '2024-2025', 'S1', 'INF201', 'Algorithmique', 14.0, 4.0, 'Assez Bien'),

-- Notes pour D44556 - 1A Génie Électrique - 2024-2025
('D44556', '2024-2025', 'S1', 'ELC301', 'Électronique analogique', 14.0, 4.0, 'Assez Bien'),
('D44556', '2024-2025', 'S1', 'ELC302', 'Électrotechnique', 13.5, 4.0, 'Passable'),
('D44556', '2024-2025', 'S1', 'ELC303', 'Automatique', 14.5, 3.0, 'Assez Bien'),

-- Notes pour H89012 - 3A Génie Électrique - 2024-2025
('H89012', '2024-2025', 'S1', 'ELC501', 'Projet de fin d''études', 16.0, 6.0, 'Bien'),
('H89012', '2024-2025', 'S1', 'ELC502', 'Énergies renouvelables', 15.5, 4.0, 'Bien')
ON DUPLICATE KEY UPDATE note = VALUES(note);

-- ----------------------------------------------------------------------------
-- Stages (UNIQUEMENT pour étudiants en 2A ou 3A)
-- 2A = PFA (Projet de Fin d'Année) - Stage de 2-3 mois
-- 3A = PFE (Projet de Fin d'Études) - Stage de 4-6 mois
-- ----------------------------------------------------------------------------
INSERT INTO stage (apogee_number, annee_universitaire, nom_entreprise, adresse_entreprise, date_debut, date_fin, duree_semaines, sujet_stage, tuteur_entreprise, tuteur_universitaire, statut) VALUES
-- Étudiant B67890 en 2A Génie Mécanique - PFA (Stage court 2-3 mois)
('B67890', '2024-2025', 'DataSoft Solutions', 'Rabat, Hay Riad, Maroc', '2025-06-15', '2025-08-15', 8, 'Conception et optimisation de systèmes mécaniques', 'M. Hassan Benali', 'Prof. Ahmed Alami', 'Approuvé'),

-- Étudiant G56789 en 2A Génie Mécanique - PFA (Stage court 2-3 mois)
('G56789', '2024-2025', 'Mecanique Pro', 'Casablanca, Zone Industrielle, Maroc', '2025-07-01', '2025-08-31', 8, 'Analyse et amélioration des processus de fabrication', 'Mme. Fatima Chraibi', 'Prof. Youssef Idrissi', 'En attente'),

-- Étudiant H89012 en 3A Génie Électrique - PFE (Stage long 4-6 mois)
('H89012', '2024-2025', 'ElectroTech Maroc', 'Tanger, Technopolis, Maroc', '2025-02-01', '2025-07-31', 24, 'Développement d''un système de gestion d''énergie renouvelable', 'M. Omar El Fassi', 'Prof. Nadia Hassani', 'Approuvé'),

-- Étudiant E78901 en 3A Génie Informatique (année précédente) - PFE (Stage long 4-6 mois)
('E78901', '2023-2024', 'TechCorp Maroc', 'Casablanca, Zone Industrielle, Maroc', '2024-02-15', '2024-07-15', 20, 'Développement d''une application web pour la gestion des ressources humaines', 'M. Karim Jazouli', 'Prof. Mehdi Ghazali', 'Terminé'),

-- Étudiant J67890 en M2 Génie Informatique - Stage de recherche (optionnel)
('J67890', '2024-2025', 'BioLab Research', 'Rabat, Hay Riad, Maroc', '2025-03-01', '2025-08-31', 24, 'Recherche sur l''intelligence artificielle appliquée à la médecine', 'Dr. Amina Jazouli', 'Prof. Layla Fadili', 'En cours')
ON DUPLICATE KEY UPDATE statut = VALUES(statut);

-- ----------------------------------------------------------------------------
-- Demandes de test
-- ----------------------------------------------------------------------------
INSERT INTO demande (apogee_number, document_type, status, additional_info) VALUES
('A12345', 'Attestation de scolarité', 'Acceptée', JSON_OBJECT('annee_universitaire', '2024-2025')),
('B67890', 'Attestation de réussite', 'Acceptée', JSON_OBJECT('annee_universitaire', '2023-2024', 'niveau', '2A')),
('C11223', 'Relevé de notes', 'En attente', JSON_OBJECT('annee_universitaire', '2024-2025', 'semestre', 'S1')),
-- Convention de stage pour B67890 en 2A (PFA) - Stage court 2-3 mois (8-12 semaines)
('B67890', 'Convention de stage', 'Acceptée', JSON_OBJECT(
    'nom_entreprise', 'DataSoft Solutions',
    'adresse_entreprise', 'Rabat, Hay Riad, Maroc',
    'duree_stage', '8 semaines',
    'date_debut', '2025-06-15',
    'date_fin', '2025-08-15'
)),
-- Convention de stage pour H89012 en 3A (PFE) - Stage long 4-6 mois (16-24 semaines)
('H89012', 'Convention de stage', 'Acceptée', JSON_OBJECT(
    'nom_entreprise', 'ElectroTech Maroc',
    'adresse_entreprise', 'Tanger, Technopolis, Maroc',
    'duree_stage', '24 semaines',
    'date_debut', '2025-02-01',
    'date_fin', '2025-07-31'
)),
('D44556', 'Attestation de scolarité', 'En attente', JSON_OBJECT('annee_universitaire', '2024-2025')),
('E78901', 'Attestation de réussite', 'Acceptée', JSON_OBJECT('annee_universitaire', '2023-2024', 'niveau', '3A')),
('H89012', 'Relevé de notes', 'Acceptée', JSON_OBJECT('annee_universitaire', '2024-2025', 'semestre', 'S1'))
ON DUPLICATE KEY UPDATE status = VALUES(status);

-- ============================================================================
-- VUES
-- ============================================================================

-- Vue des demandes complètes avec informations étudiant
CREATE OR REPLACE VIEW v_demandes_completes AS
SELECT 
    d.id,
    d.apogee_number,
    e.nom,
    e.prenom,
    e.email,
    e.cin,
    d.document_type,
    d.date_demande,
    d.status,
    d.justification_refus,
    d.additional_info,
    d.document_path,
    d.email_sent,
    d.email_sent_at
FROM demande d
LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number;

-- Vue des réclamations complètes
CREATE OR REPLACE VIEW v_reclamations_completes AS
SELECT 
    r.id,
    r.demande_id,
    d.document_type,
    d.apogee_number,
    e.nom,
    e.prenom,
    e.email,
    r.motif,
    r.description,
    r.status,
    r.reponse,
    r.date_reclamation,
    r.date_reponse
FROM reclamation r
LEFT JOIN demande d ON r.demande_id = d.id
LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number;

-- Vue des étudiants inscrits
CREATE OR REPLACE VIEW v_etudiant_inscrit AS
SELECT 
    e.apogee_number,
    e.nom,
    e.prenom,
    e.email,
    i.annee_universitaire,
    i.niveau,
    i.filiere,
    i.statut as statut_inscription
FROM etudiant e
INNER JOIN inscription i ON e.apogee_number = i.apogee_number
WHERE i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé');

-- Vue des étudiants ayant réussi
CREATE OR REPLACE VIEW v_etudiant_reussi AS
SELECT 
    e.apogee_number,
    e.nom,
    e.prenom,
    ra.annee_universitaire,
    ra.niveau,
    ra.filiere,
    ra.moyenne_generale,
    ra.mention,
    ra.statut
FROM etudiant e
INNER JOIN resultat_annee ra ON e.apogee_number = ra.apogee_number
WHERE ra.statut = 'Réussi';

-- Vue des notes des étudiants
CREATE OR REPLACE VIEW v_etudiant_notes AS
SELECT 
    e.apogee_number,
    e.nom,
    e.prenom,
    n.annee_universitaire,
    n.semestre,
    n.code_module,
    n.nom_module,
    n.note,
    n.coefficient,
    n.mention
FROM etudiant e
INNER JOIN note n ON e.apogee_number = n.apogee_number;

-- ============================================================================
-- PROCÉDURES STOCKÉES
-- ============================================================================

DELIMITER //

-- Procédure pour obtenir les statistiques admin
CREATE PROCEDURE IF NOT EXISTS sp_get_admin_stats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM demande WHERE status = 'En attente') as demandes_en_attente,
        (SELECT COUNT(*) FROM demande WHERE status = 'Acceptée') as demandes_acceptees,
        (SELECT COUNT(*) FROM demande WHERE status = 'Refusée') as demandes_refusees,
        (SELECT COUNT(*) FROM reclamation WHERE status = 'En attente') as reclamations_en_attente,
        (SELECT COUNT(*) FROM etudiant) as total_etudiants,
        (SELECT COUNT(*) FROM demande WHERE DATE(date_demande) = CURDATE()) as demandes_aujourdhui;
END //

-- Procédure pour obtenir les statistiques étudiant
CREATE PROCEDURE IF NOT EXISTS sp_get_student_stats(IN p_apogee_number VARCHAR(20))
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM demande WHERE apogee_number = p_apogee_number AND status = 'En attente') as demandes_en_attente,
        (SELECT COUNT(*) FROM demande WHERE apogee_number = p_apogee_number AND status = 'Acceptée') as demandes_acceptees,
        (SELECT COUNT(*) FROM demande WHERE apogee_number = p_apogee_number AND status = 'Refusée') as demandes_refusees,
        (SELECT COUNT(*) FROM reclamation r 
         INNER JOIN demande d ON r.demande_id = d.id 
         WHERE d.apogee_number = p_apogee_number) as total_reclamations;
END //

-- Procédure pour valider une demande
CREATE PROCEDURE IF NOT EXISTS sp_validate_demande(
    IN p_demande_id INT,
    IN p_status ENUM('Acceptée', 'Refusée'),
    IN p_justification TEXT
)
BEGIN
    UPDATE demande 
    SET status = p_status,
        justification_refus = IF(p_status = 'Refusée', p_justification, NULL),
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_demande_id;
END //

DELIMITER ;

-- ============================================================================
-- TRIGGERS
-- ============================================================================

DELIMITER //

-- Trigger pour mettre à jour email_sent_at quand email_sent passe à TRUE
CREATE TRIGGER IF NOT EXISTS trg_update_email_sent_at
BEFORE UPDATE ON demande
FOR EACH ROW
BEGIN
    IF NEW.email_sent = TRUE AND OLD.email_sent = FALSE THEN
        SET NEW.email_sent_at = CURRENT_TIMESTAMP;
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- COMPTAGE DES ENREGISTREMENTS
-- ============================================================================

SELECT 'Étudiants' as `Table`, COUNT(*) as Nombre FROM etudiant
UNION ALL
SELECT 'Administrateurs' as `Table`, COUNT(*) as Nombre FROM administrateur
UNION ALL
SELECT 'Demandes' as `Table`, COUNT(*) as Nombre FROM demande
UNION ALL
SELECT 'Réclamations' as `Table`, COUNT(*) as Nombre FROM reclamation
UNION ALL
SELECT 'Inscriptions' as `Table`, COUNT(*) as Nombre FROM inscription
UNION ALL
SELECT 'Notes' as `Table`, COUNT(*) as Nombre FROM note
UNION ALL
SELECT 'Résultats' as `Table`, COUNT(*) as Nombre FROM resultat_annee
UNION ALL
SELECT 'Stages' as `Table`, COUNT(*) as Nombre FROM stage;

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
SELECT 'Base de données créée avec succès !' AS message;

