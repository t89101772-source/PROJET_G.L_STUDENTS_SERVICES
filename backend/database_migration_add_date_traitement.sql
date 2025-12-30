-- Migration : Ajout du champ date_traitement pour les demandes
-- Ce champ stocke la date à laquelle une demande a été acceptée ou refusée

-- Ajouter la colonne date_traitement à la table demande
ALTER TABLE `demande` 
ADD COLUMN `date_traitement` timestamp NULL DEFAULT NULL AFTER `date_demande`;

-- Mettre à jour les demandes existantes qui sont déjà acceptées ou refusées
-- On utilise email_sent_at comme approximation pour les demandes acceptées
-- et date_demande pour les demandes refusées (si pas d'email_sent_at)
UPDATE `demande` 
SET `date_traitement` = COALESCE(`email_sent_at`, `date_demande`)
WHERE `status` IN ('Acceptée', 'Refusée', 'Traitée');

-- Ajouter un index pour améliorer les performances des requêtes sur date_traitement
CREATE INDEX `idx_date_traitement` ON `demande` (`date_traitement`);

