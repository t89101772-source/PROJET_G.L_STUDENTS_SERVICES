<?php
/**
 * Service de validation des demandes de documents
 * Vérifie que l'étudiant a le droit de recevoir le document demandé
 */

require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    return [
        'valid' => false,
        'error' => 'Erreur de connexion à la base de données'
    ];
}

/**
 * Valide une demande d'attestation de scolarité
 */
function validateAttestationScolarite($pdo, $apogeeNumber, $additionalInfo = []) {
    $errors = [];
    
    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
    $stmt->execute([$apogeeNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return [
            'valid' => false,
            'error' => 'Étudiant non trouvé dans la base de données'
        ];
    }
    
    // Vérifier que l'étudiant est inscrit pour l'année demandée
    // Si aucune année n'est spécifiée, vérifier l'année en cours
    $annee = $additionalInfo['annee_universitaire'] ?? date('Y') . '-' . (date('Y') + 1);
    
    // D'abord vérifier pour l'année spécifiée
    $stmt = $pdo->prepare("
        SELECT * FROM inscription 
        WHERE apogee_number = ? 
        AND annee_universitaire = ?
        AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
    ");
    $stmt->execute([$apogeeNumber, $annee]);
    $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Si pas trouvé pour l'année spécifiée, vérifier toutes les années
    if (!$inscription) {
        $stmt = $pdo->prepare("
            SELECT * FROM inscription 
            WHERE apogee_number = ? 
            AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY annee_universitaire DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($inscription) {
            // Utiliser l'année trouvée
            $annee = $inscription['annee_universitaire'];
        }
    }
    
    if (!$inscription) {
        return [
            'valid' => false,
            'error' => "L'étudiant n'est pas inscrit pour l'année universitaire $annee. Veuillez vérifier les inscriptions dans la base de données."
        ];
    }
    
    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'inscription' => $inscription
        ]
    ];
}

/**
 * Valide une demande d'attestation de réussite
 */
function validateAttestationReussite($pdo, $apogeeNumber, $additionalInfo = []) {
    $errors = [];
    
    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
    $stmt->execute([$apogeeNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return [
            'valid' => false,
            'error' => 'Étudiant non trouvé dans la base de données'
        ];
    }
    
    // Vérifier les informations requises
    $annee = $additionalInfo['annee_universitaire'] ?? null;
    $niveau = $additionalInfo['niveau'] ?? null;
    
    if (empty($annee) || empty($niveau)) {
        return [
            'valid' => false,
            'error' => 'L\'année universitaire et le niveau sont requis pour une attestation de réussite'
        ];
    }
    
    // Vérifier que l'étudiant a réussi cette année/niveau
    $stmt = $pdo->prepare("
        SELECT * FROM resultat_annee 
        WHERE apogee_number = ? 
        AND annee_universitaire = ?
        AND niveau = ?
        AND statut = 'Réussi'
    ");
    $stmt->execute([$apogeeNumber, $annee, $niveau]);
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultat) {
        // Vérifier si l'étudiant était inscrit mais n'a pas réussi
        $stmt = $pdo->prepare("
            SELECT * FROM resultat_annee 
            WHERE apogee_number = ? 
            AND annee_universitaire = ?
            AND niveau = ?
        ");
        $stmt->execute([$apogeeNumber, $annee, $niveau]);
        $resultatCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultatCheck) {
            $statut = $resultatCheck['statut'];
            $message = '';
            
            if ($statut === 'En cours') {
                $message = "L'étudiant est actuellement en train de suivre cette année ($annee - $niveau). Les résultats ne sont pas encore finalisés (statut: En cours). Une attestation de réussite ne peut être délivrée qu'après la validation finale des résultats par le conseil pédagogique.";
            } elseif ($statut === 'Ajourné') {
                $message = "L'étudiant n'a pas réussi cette année ($annee - $niveau). Statut: Ajourné. Une attestation de réussite ne peut pas être délivrée.";
            } elseif ($statut === 'Redoublant') {
                $message = "L'étudiant redouble cette année ($annee - $niveau). Statut: Redoublant. Une attestation de réussite ne peut être délivrée qu'après avoir réussi l'année.";
            } else {
                $message = "L'étudiant n'a pas réussi pour l'année $annee au niveau $niveau. Statut: $statut";
            }
            
            return [
                'valid' => false,
                'error' => $message
            ];
        } else {
            return [
                'valid' => false,
                'error' => "Aucun résultat trouvé pour l'étudiant pour l'année $annee au niveau $niveau"
            ];
        }
    }
    
    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'resultat' => $resultat
        ]
    ];
}

/**
 * Valide une demande de relevé de notes
 */
function validateReleveNotes($pdo, $apogeeNumber, $additionalInfo = []) {
    $errors = [];
    
    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
    $stmt->execute([$apogeeNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return [
            'valid' => false,
            'error' => 'Étudiant non trouvé dans la base de données'
        ];
    }
    
    // Vérifier les informations requises
    $annee = $additionalInfo['annee_universitaire'] ?? null;
    $semestre = $additionalInfo['semestre'] ?? null;
    
    if (empty($annee)) {
        return [
            'valid' => false,
            'error' => 'L\'année universitaire est requise pour un relevé de notes'
        ];
    }
    
    // Vérifier que l'étudiant a des notes pour cette année
    if ($semestre && $semestre !== 'Tous') {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as nb_notes FROM note 
            WHERE apogee_number = ? 
            AND annee_universitaire = ?
            AND semestre = ?
        ");
        $stmt->execute([$apogeeNumber, $annee, $semestre]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as nb_notes FROM note 
            WHERE apogee_number = ? 
            AND annee_universitaire = ?
        ");
        $stmt->execute([$apogeeNumber, $annee]);
    }
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['nb_notes'] == 0) {
        return [
            'valid' => false,
            'error' => "Aucune note trouvée pour l'étudiant pour l'année $annee" . ($semestre ? " semestre $semestre" : "")
        ];
    }
    
    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'nb_notes' => $result['nb_notes']
        ]
    ];
}

/**
 * Valide une demande de convention de stage
 */
function validateConventionStage($pdo, $apogeeNumber, $additionalInfo = []) {
    $errors = [];
    
    // Vérifier que l'étudiant existe
    $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
    $stmt->execute([$apogeeNumber]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$student) {
        return [
            'valid' => false,
            'error' => 'Étudiant non trouvé dans la base de données'
        ];
    }
    
    // Vérifier les informations requises
    $nomEntreprise = $additionalInfo['nom_entreprise'] ?? null;
    $dateDebut = $additionalInfo['date_debut'] ?? null;
    $dateFin = $additionalInfo['date_fin'] ?? null;
    $annee = date('Y') . '-' . (date('Y') + 1);
    
    if (empty($nomEntreprise) || empty($dateDebut) || empty($dateFin)) {
        return [
            'valid' => false,
            'error' => 'Les informations de l\'entreprise et les dates sont requises'
        ];
    }
    
    // IMPORTANT: Vérifier que l'étudiant est en 2A (PFA) ou 3A (PFE)
    // 2A = 4ème année = PFA (Projet de Fin d'Année) - Stage court 2-3 mois
    // 3A = 5ème année = PFE (Projet de Fin d'Études) - Stage long 4-6 mois
    $currentYear = date('Y');
    $anneeCourante = $currentYear . '-' . ($currentYear + 1);
    $anneePrecedente = ($currentYear - 1) . '-' . $currentYear;
    
    $stmt = $pdo->prepare("
        SELECT * FROM inscription 
        WHERE apogee_number = ? 
        AND (annee_universitaire = ? OR annee_universitaire = ?)
        AND niveau IN ('2A', '3A')
        AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
        ORDER BY annee_universitaire DESC, niveau DESC
        LIMIT 1
    ");
    $stmt->execute([$apogeeNumber, $anneeCourante, $anneePrecedente]);
    $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inscription) {
        // Vérifier quel est le niveau actuel de l'étudiant pour donner un message plus précis
        $stmtNiveau = $pdo->prepare("
            SELECT niveau, annee_universitaire, filiere 
            FROM inscription 
            WHERE apogee_number = ? 
            AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY annee_universitaire DESC, 
                     CASE niveau 
                         WHEN 'CPI1' THEN 1 
                         WHEN 'CPI2' THEN 2 
                         WHEN '1A' THEN 3 
                         WHEN '2A' THEN 4 
                         WHEN '3A' THEN 5 
                         WHEN 'M1' THEN 6 
                         WHEN 'M2' THEN 7 
                         ELSE 0 
                     END DESC
            LIMIT 1
        ");
        $stmtNiveau->execute([$apogeeNumber]);
        $niveauActuel = $stmtNiveau->fetch(PDO::FETCH_ASSOC);
        
        if ($niveauActuel) {
            return [
                'valid' => false,
                'error' => "Les conventions de stage ne sont disponibles que pour les étudiants en 2A (PFA - 4ème année) ou 3A (PFE - 5ème année). Vous êtes actuellement en {$niveauActuel['niveau']} ({$niveauActuel['filiere']})."
            ];
        } else {
            return [
                'valid' => false,
                'error' => "L'étudiant doit être inscrit en 2A (PFA) ou 3A (PFE) pour effectuer un stage. Les conventions de stage ne sont pas disponibles pour les autres niveaux."
            ];
        }
    }
    
    // Vérifier les dates (date de fin après date de début)
    if (strtotime($dateFin) <= strtotime($dateDebut)) {
        return [
            'valid' => false,
            'error' => 'La date de fin doit être postérieure à la date de début'
        ];
    }
    
    // Vérifier la durée du stage selon le niveau
    $dateDebutObj = new DateTime($dateDebut);
    $dateFinObj = new DateTime($dateFin);
    $dureeSemaines = floor($dateDebutObj->diff($dateFinObj)->days / 7);
    
    if ($inscription['niveau'] === '2A') {
        // PFA : Stage de 2-3 mois (8-12 semaines)
        if ($dureeSemaines < 8 || $dureeSemaines > 12) {
            return [
                'valid' => false,
                'error' => "Pour un stage PFA (2A - 4ème année), la durée doit être entre 2 et 3 mois (8-12 semaines). Durée actuelle : {$dureeSemaines} semaines."
            ];
        }
    } elseif ($inscription['niveau'] === '3A') {
        // PFE : Stage de 4-6 mois (16-24 semaines)
        if ($dureeSemaines < 16 || $dureeSemaines > 24) {
            return [
                'valid' => false,
                'error' => "Pour un stage PFE (3A - 5ème année), la durée doit être entre 4 et 6 mois (16-24 semaines). Durée actuelle : {$dureeSemaines} semaines."
            ];
        }
    }
    
    // Vérifier que le stage est dans le futur ou en cours
    if (strtotime($dateDebut) > strtotime('+1 year')) {
        return [
            'valid' => false,
            'error' => 'La date de début du stage ne peut pas être dans plus d\'un an'
        ];
    }
    
    // Vérifier s'il existe déjà un stage approuvé ou en attente pour cette période
    $stmt = $pdo->prepare("
        SELECT * FROM stage 
        WHERE apogee_number = ? 
        AND statut IN ('Approuvé', 'En attente', 'En cours')
        AND (
            (date_debut <= ? AND date_fin >= ?) OR
            (date_debut <= ? AND date_fin >= ?) OR
            (date_debut >= ? AND date_fin <= ?)
        )
    ");
    $stmt->execute([$apogeeNumber, $dateDebut, $dateDebut, $dateFin, $dateFin, $dateDebut, $dateFin]);
    $stageExistant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stageExistant) {
        // Si le stage existe déjà avec les mêmes informations, c'est OK
        if ($stageExistant['nom_entreprise'] === $nomEntreprise && 
            $stageExistant['date_debut'] === $dateDebut && 
            $stageExistant['date_fin'] === $dateFin) {
            // C'est le même stage, on peut continuer
        } else {
            return [
                'valid' => false,
                'error' => 'Un stage existe déjà pour cette période. Veuillez vérifier vos dates ou contacter l\'administration.'
            ];
        }
    } else {
        // Aucun stage trouvé, vérifier si on doit créer le stage ou si c'est une erreur
        // Pour l'instant, on accepte la demande mais l'admin devra créer le stage
        // ou l'étudiant devra d'abord créer le stage dans le système
    }
    
    // Récupérer le stage si il existe
    $stmtStage = $pdo->prepare("
        SELECT * FROM stage 
        WHERE apogee_number = ? 
        AND nom_entreprise = ? 
        AND date_debut = ? 
        AND date_fin = ?
        AND statut IN ('Approuvé', 'En attente', 'En cours')
        LIMIT 1
    ");
    $stmtStage->execute([$apogeeNumber, $nomEntreprise, $dateDebut, $dateFin]);
    $stage = $stmtStage->fetch(PDO::FETCH_ASSOC);
    
    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'inscription' => $inscription,
            'stage' => $stage,
            'duree_semaines' => $dureeSemaines,
            'type_stage' => $inscription['niveau'] === '2A' ? 'PFA' : 'PFE'
        ]
    ];
}

/**
 * Fonction principale de validation
 */
function validateDocumentRequest($pdo, $documentType, $apogeeNumber, $additionalInfo = []) {
    switch ($documentType) {
        case 'Attestation de scolarité':
            return validateAttestationScolarite($pdo, $apogeeNumber, $additionalInfo);
            
        case 'Attestation de réussite':
            return validateAttestationReussite($pdo, $apogeeNumber, $additionalInfo);
            
        case 'Relevé de notes':
            return validateReleveNotes($pdo, $apogeeNumber, $additionalInfo);
            
        case 'Convention de stage':
            return validateConventionStage($pdo, $apogeeNumber, $additionalInfo);
            
        case 'Autre':
            // Pour "Autre", on vérifie juste que l'étudiant existe
            $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
            $stmt->execute([$apogeeNumber]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return [
                    'valid' => false,
                    'error' => 'Étudiant non trouvé dans la base de données'
                ];
            }
            
            return [
                'valid' => true,
                'data' => ['student' => $student]
            ];
            
        default:
            return [
                'valid' => false,
                'error' => 'Type de document non reconnu'
            ];
    }
}

?>

