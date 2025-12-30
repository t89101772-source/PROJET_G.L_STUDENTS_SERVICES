<?php
/**
 * Service de validation des demandes de documents
 * Vérifie que l'étudiant a le droit de recevoir le document demandé
 */

require_once __DIR__ . '/../config/database.php';

// Note: La vérification de $pdo est faite dans chaque fonction individuellement
// car ce fichier peut être inclus plusieurs fois et $pdo peut ne pas être défini au moment de l'inclusion

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
    
    // Nouveau modèle: Niveau obligatoire, année universitaire détectée automatiquement
    $niveauRequested = $additionalInfo['niveau'] ?? null;

    if (empty($niveauRequested)) {
        return [
            'valid' => false,
            'error' => 'Le niveau est requis pour une attestation de réussite'
        ];
    }

    // Prendre automatiquement la dernière année "Réussi" pour ce niveau
    $stmt = $pdo->prepare("
        SELECT * FROM resultat_annee
        WHERE apogee_number = ?
          AND niveau = ?
          AND statut = 'Réussi'
        ORDER BY annee_universitaire DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([$apogeeNumber, $niveauRequested]);
    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultat) {
        // Si pas de "Réussi", vérifier s'il y a une ligne pour ce niveau (En cours / Ajourné / Redoublant)
        $stmt = $pdo->prepare("
            SELECT * FROM resultat_annee
            WHERE apogee_number = ?
              AND niveau = ?
            ORDER BY annee_universitaire DESC, id DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber, $niveauRequested]);
        $resultatCheck = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resultatCheck) {
            $statut = $resultatCheck['statut'];
            $message = '';
            $annee = $resultatCheck['annee_universitaire'] ?? '—';
            $niveau = $resultatCheck['niveau'] ?? $niveauRequested;
            if ($statut === 'En cours') {
                $message = "L'étudiant est actuellement en cours de formation pour le niveau $niveau (année $annee). Une attestation de réussite ne peut être délivrée qu'après validation finale des résultats.";
            } elseif ($statut === 'Ajourné') {
                $message = "L'étudiant n'a pas réussi le niveau $niveau (année $annee). Statut: Ajourné. Une attestation de réussite ne peut pas être délivrée.";
            } elseif ($statut === 'Redoublant') {
                $message = "L'étudiant est en redoublement pour le niveau $niveau (année $annee). Une attestation de réussite ne peut être délivrée qu'après réussite.";
            } else {
                $message = "L'étudiant n'a pas réussi le niveau $niveau (année $annee). Statut: $statut";
            }
            
            return [
                'valid' => false,
                'error' => $message
            ];
        } else {
            return [
                'valid' => false,
                'error' => "Aucun résultat trouvé pour l'étudiant pour le niveau $niveauRequested"
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

    // Mode demandé:
    // - niveau_cible = un niveau => relevé limité aux semestres validés de CE niveau
    // Le niveau est obligatoire et ne peut plus être "Tous"
    $niveauCible = $additionalInfo['niveau_cible'] ?? null;
    if (empty($niveauCible) || $niveauCible === 'Tous') {
        return [
            'valid' => false,
            'error' => 'Veuillez sélectionner un niveau spécifique pour le relevé de notes (2AP1, 2AP2, CI1, CI2 ou CI3).'
        ];
    }

    $ranges = [
        '2AP1' => [1, 2],
        '2AP2' => [3, 4],
        'CI1'  => [5, 6],
        'CI2'  => [7, 8],
        'CI3'  => [9, 10],
    ];

    // Semestres validés (source prioritaire)
    $stmt = $pdo->prepare("
        SELECT DISTINCT n.numero_semestre
        FROM resultat_semestre rs
        INNER JOIN niveau n ON n.id = rs.niveau_id
        WHERE rs.apogee_number = ?
          AND rs.statut = 'Validé'
        ORDER BY n.numero_semestre ASC
    ");
    $stmt->execute([$apogeeNumber]);
    $validatedList = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    // Fallback: si pas de resultat_semestre, prendre les semestres présents dans note
    if (empty($validatedList)) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT CAST(SUBSTRING(semestre, 2) AS UNSIGNED) AS s
            FROM note
            WHERE apogee_number = ?
            ORDER BY s ASC
        ");
        $stmt->execute([$apogeeNumber]);
        $validatedList = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    if (empty($validatedList)) {
        return [
            'valid' => false,
            'error' => "Aucune note trouvée pour l'étudiant. Impossible de générer un relevé de notes."
        ];
    }

    $included = [];
    $mode = 'Niveau';

    // Vérifier que le niveau est valide
    if (!isset($ranges[$niveauCible])) {
        return [
            'valid' => false,
            'error' => "Niveau invalide pour le relevé de notes. Les niveaux valides sont : 2AP1, 2AP2, CI1, CI2, CI3."
        ];
    }
    
    // Calculer les semestres inclus pour ce niveau
    [$minS, $maxS] = $ranges[$niveauCible];
    foreach ($validatedList as $s) {
        if ($s >= $minS && $s <= $maxS) $included[] = $s;
    }
    sort($included);
    $included = array_values(array_unique($included));
    if (empty($included)) {
        return [
            'valid' => false,
            'error' => "Aucun semestre validé trouvé pour le niveau $niveauCible."
        ];
    }

    // Vérifier qu'il existe des notes sur les semestres inclus
    $placeholders = implode(',', array_fill(0, count($included), '?'));
    $params = array_merge([$apogeeNumber], $included);
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS nb
        FROM note nt
        LEFT JOIN niveau n ON n.id = nt.niveau_id
        WHERE nt.apogee_number = ?
          AND COALESCE(n.numero_semestre, CAST(SUBSTRING(nt.semestre, 2) AS UNSIGNED)) IN ($placeholders)
    ");
    $stmt->execute($params);
    $nb = (int)$stmt->fetchColumn();

    if ($nb <= 0) {
        return [
            'valid' => false,
            'error' => "Aucune note trouvée pour les semestres sélectionnés. Veuillez vérifier les notes en base de données."
        ];
    }

    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'mode' => $mode,
            'niveau_cible' => $niveauCible,
            'included_semestres' => $included,
            'nb_notes' => $nb,
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
    $adresseEntreprise = $additionalInfo['adresse_entreprise'] ?? null;
    $telEntreprise = $additionalInfo['tel_entreprise'] ?? null;
    $emailEntreprise = $additionalInfo['email_entreprise'] ?? null;
    $representantEntreprise = $additionalInfo['representant_entreprise'] ?? null;
    $qualiteRepresentant = $additionalInfo['qualite_representant'] ?? null;
    $typeStageChoisi = $additionalInfo['type_stage'] ?? null; // PFA ou PFE choisi par l'étudiant
    $dateDebut = $additionalInfo['date_debut'] ?? null;
    $dateFin = $additionalInfo['date_fin'] ?? null;
    $encadrant = $additionalInfo['encadrant'] ?? null;
    $tuteur = $additionalInfo['tuteur'] ?? null;
    $themeStage = $additionalInfo['theme_stage'] ?? null;
    $annee = date('Y') . '-' . (date('Y') + 1);
    
    // Vérifier tous les champs requis
    $missingFields = [];
    if (empty($nomEntreprise)) $missingFields[] = 'nom de l\'entreprise';
    if (empty($adresseEntreprise)) $missingFields[] = 'adresse de l\'entreprise';
    if (empty($telEntreprise)) $missingFields[] = 'téléphone de l\'entreprise';
    if (empty($emailEntreprise)) $missingFields[] = 'email de l\'entreprise';
    if (empty($representantEntreprise)) $missingFields[] = 'représentant de l\'entreprise';
    if (empty($qualiteRepresentant)) $missingFields[] = 'qualité du représentant';
    if (empty($typeStageChoisi)) $missingFields[] = 'type de stage';
    if (empty($dateDebut)) $missingFields[] = 'date de début';
    if (empty($dateFin)) $missingFields[] = 'date de fin';
    if (empty($encadrant)) $missingFields[] = 'encadrant';
    if (empty($tuteur)) $missingFields[] = 'tuteur pédagogique';
    if (empty($themeStage)) $missingFields[] = 'thème du stage';
    
    if (!empty($missingFields)) {
        return [
            'valid' => false,
            'error' => 'Les champs suivants sont requis : ' . implode(', ', $missingFields)
        ];
    }
    
    // Vérifier que le type de stage choisi est valide
    if (!in_array($typeStageChoisi, ['PFA', 'PFE'])) {
        return [
            'valid' => false,
            'error' => 'Le type de stage doit être PFA ou PFE'
        ];
    }
    
    // IMPORTANT: Vérifier que l'étudiant peut effectuer ce type de stage
    // Si PFA choisi : doit être en CI2-S8 ou avoir déjà fait un PFA
    // Si PFE choisi : doit être en CI3-S9/CI3-S10 ou avoir déjà fait un PFE
    // CI2-S8 = 4ème année, semestre 8 = PFA (Projet de Fin d'Année) - Stage court 2-3 mois
    // CI3-S9/CI3-S10 = 5ème année, semestres 9/10 = PFE (Projet de Fin d'Études) - Stage long 4-6 mois
    $currentYear = date('Y');
    $anneeCourante = $currentYear . '-' . ($currentYear + 1);
    $anneePrecedente = ($currentYear - 1) . '-' . $currentYear;
    
    // Vérifier selon le type de stage choisi
    if ($typeStageChoisi === 'PFA') {
        // D'abord vérifier si l'étudiant est déjà en CI3 (alors il ne peut plus demander PFA)
        $stmt = $pdo->prepare("
            SELECT i.annee_universitaire, n.code AS niveau_code, n.numero_semestre
            FROM inscription i
            LEFT JOIN niveau n ON i.niveau_id = n.id
            WHERE i.apogee_number = ?
              AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY i.annee_universitaire DESC, n.numero_semestre DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber]);
        $currentInscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentInscription) {
            $currentNiveau = $currentInscription['niveau_code'] ?? '';
            $currentSemestre = (int)($currentInscription['numero_semestre'] ?? 0);
            
            // Si l'étudiant est en CI3 (semestre 9 ou 10), il ne peut plus demander PFA
            if (strpos($currentNiveau, 'CI3') !== false || $currentSemestre >= 9) {
                return [
                    'valid' => false,
                    'error' => "Convention PFA refusée : vous êtes déjà en CI3 (5ème année). Le PFA concerne uniquement la 4ème année (CI2-S8). Si vous avez déjà validé le semestre S9, vous devez demander une convention PFE pour votre stage de fin d'études."
                ];
            }
        }
        
        // Pour PFA : vérifier si l'étudiant est en CI2-S8
        $stmt = $pdo->prepare("
            SELECT i.*, n.code as niveau_code, n.numero_semestre
            FROM inscription i
            LEFT JOIN niveau n ON i.niveau_id = n.id
            WHERE i.apogee_number = ? 
            AND (i.annee_universitaire = ? OR i.annee_universitaire = ?)
            AND (n.code = 'CI2-S8' OR n.numero_semestre = 8)
            AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY i.annee_universitaire DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber, $anneeCourante, $anneePrecedente]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // IMPORTANT: Sans table stage, la validation se base uniquement sur l'inscription
        if (!$inscription) {
            // Aide: récupérer le niveau actuel le plus récent pour produire un message clair
            $curLabel = $currentInscription
                ? (($currentInscription['niveau_code'] ?: ('S' . ($currentInscription['numero_semestre'] ?? ''))) . ' (' . ($currentInscription['annee_universitaire'] ?? '—') . ')')
                : 'non disponible';

            return [
                'valid' => false,
                'error' => "Convention PFA refusée : le PFA concerne uniquement la 4ème année (CI2-S8). Votre niveau actuel détecté est : $curLabel. Pour demander une convention PFA, vous devez être inscrit en CI2-S8 après avoir validé le semestre S7."
            ];
        }

        // Règle métier: PFA seulement après validation du semestre S7
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM resultat_semestre rs
            INNER JOIN niveau n ON n.id = rs.niveau_id
            WHERE rs.apogee_number = ?
              AND n.numero_semestre = 7
              AND rs.statut = 'Validé'
        ");
        $stmt->execute([$apogeeNumber]);
        if ((int)$stmt->fetchColumn() <= 0) {
            return [
                'valid' => false,
                'error' => "Convention PFA refusée : vous devez d'abord valider le semestre S7 (pré-requis du PFA)."
            ];
        }

        // Vérifier si S8 est validé
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM resultat_semestre rs
            INNER JOIN niveau n ON n.id = rs.niveau_id
            WHERE rs.apogee_number = ?
              AND n.numero_semestre = 8
              AND rs.statut = 'Validé'
        ");
        $stmt->execute([$apogeeNumber]);
        $s8Valide = (int)$stmt->fetchColumn() > 0;
        
        // Si S8 est validé, vérifier que l'étudiant n'est pas encore inscrit en CI3
        if ($s8Valide) {
            // Vérifier si l'étudiant est déjà inscrit en CI3
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM inscription i
                LEFT JOIN niveau n ON i.niveau_id = n.id
                WHERE i.apogee_number = ?
                  AND (n.code LIKE 'CI3%' OR n.numero_semestre >= 9)
                  AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ");
            $stmt->execute([$apogeeNumber]);
            $enCI3 = (int)$stmt->fetchColumn() > 0;
            
            if ($enCI3) {
                return [
                    'valid' => false,
                    'error' => "Convention PFA refusée : vous êtes déjà inscrit en CI3 (5ème année). Le PFA concerne uniquement la 4ème année (CI2-S8). Si vous avez déjà validé le semestre S9, vous devez demander une convention PFE pour votre stage de fin d'études."
                ];
            }
            // Si S8 est validé mais pas encore en CI3, c'est le cas où il peut demander PFA
        }
    } elseif ($typeStageChoisi === 'PFE') {
        // D'abord vérifier si l'étudiant est en CI2 (alors il ne peut pas encore demander PFE)
        $stmt = $pdo->prepare("
            SELECT i.annee_universitaire, n.code AS niveau_code, n.numero_semestre
            FROM inscription i
            LEFT JOIN niveau n ON i.niveau_id = n.id
            WHERE i.apogee_number = ?
              AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY i.annee_universitaire DESC, n.numero_semestre DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber]);
        $currentInscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($currentInscription) {
            $currentNiveau = $currentInscription['niveau_code'] ?? '';
            $currentSemestre = (int)($currentInscription['numero_semestre'] ?? 0);
            
            // Si l'étudiant est en CI2 (semestre 7 ou 8), il ne peut pas encore demander PFE
            if (strpos($currentNiveau, 'CI2') !== false || ($currentSemestre >= 7 && $currentSemestre <= 8)) {
                return [
                    'valid' => false,
                    'error' => "Convention PFE refusée : vous êtes en CI2 (4ème année). Le PFE concerne uniquement la 5ème année (CI3-S9/CI3-S10). Pour votre stage de 4ème année, vous devez demander une convention PFA après avoir validé le semestre S7."
                ];
            }
        }
        
        // Pour PFE : vérifier si l'étudiant est en CI3-S9/CI3-S10
        $stmt = $pdo->prepare("
            SELECT i.*, n.code as niveau_code, n.numero_semestre
            FROM inscription i
            LEFT JOIN niveau n ON i.niveau_id = n.id
            WHERE i.apogee_number = ? 
            AND (i.annee_universitaire = ? OR i.annee_universitaire = ?)
            AND (n.code IN ('CI3-S9', 'CI3-S10') OR n.numero_semestre IN (9, 10))
            AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY i.annee_universitaire DESC, n.numero_semestre DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber, $anneeCourante, $anneePrecedente]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // IMPORTANT: Sans table stage, la validation se base uniquement sur l'inscription
        if (!$inscription) {
            // Aide: récupérer le niveau actuel le plus récent pour produire un message clair
            $curLabel = $currentInscription
                ? (($currentInscription['niveau_code'] ?: ('S' . ($currentInscription['numero_semestre'] ?? ''))) . ' (' . ($currentInscription['annee_universitaire'] ?? '—') . ')')
                : 'non disponible';

            return [
                'valid' => false,
                'error' => "Convention PFE refusée : le PFE concerne uniquement la 5ème année (CI3-S9/CI3-S10). Votre niveau actuel détecté est : $curLabel. Pour demander une convention PFE, vous devez être inscrit en CI3 après avoir validé le semestre S9."
            ];
        }

        // Règle métier: PFE seulement après validation du semestre S9
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM resultat_semestre rs
            INNER JOIN niveau n ON n.id = rs.niveau_id
            WHERE rs.apogee_number = ?
              AND n.numero_semestre = 9
              AND rs.statut = 'Validé'
        ");
        $stmt->execute([$apogeeNumber]);
        if ((int)$stmt->fetchColumn() <= 0) {
            return [
                'valid' => false,
                'error' => "Convention PFE refusée : vous devez d'abord valider le semestre S9 (pré-requis du PFE)."
            ];
        }

        // Si S10 est déjà validé, le PFE est terminé => pas de convention
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM resultat_semestre rs
            INNER JOIN niveau n ON n.id = rs.niveau_id
            WHERE rs.apogee_number = ?
              AND n.numero_semestre = 10
              AND rs.statut = 'Validé'
        ");
        $stmt->execute([$apogeeNumber]);
        if ((int)$stmt->fetchColumn() > 0) {
            return [
                'valid' => false,
                'error' => "Convention PFE refusée : le semestre S10 est déjà validé, donc le PFE est terminé et la convention n'est plus nécessaire."
            ];
        }
    }
    
    // Si on arrive ici, soit l'étudiant est dans le bon niveau, soit il a déjà fait ce type de stage
    // On récupère l'inscription la plus récente pour la validation de durée
    if (!isset($inscription) || !$inscription) {
        $stmt = $pdo->prepare("
            SELECT i.*, n.code as niveau_code, n.numero_semestre
            FROM inscription i
            LEFT JOIN niveau n ON i.niveau_id = n.id
            WHERE i.apogee_number = ? 
            AND i.statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
            ORDER BY i.annee_universitaire DESC, n.numero_semestre DESC
            LIMIT 1
        ");
        $stmt->execute([$apogeeNumber]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Vérifier les dates et calculer la durée
    if (!empty($dateDebut) && !empty($dateFin)) {
        // Convertir les dates en timestamp si elles sont au format YYYY-MM-DD
        $dateDebutTs = null;
        $dateFinTs = null;
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebut)) {
            $dateDebutTs = strtotime($dateDebut);
        } else {
            $dateDebutTs = strtotime($dateDebut);
        }
        
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFin)) {
            $dateFinTs = strtotime($dateFin);
        } else {
            $dateFinTs = strtotime($dateFin);
        }
        
        if ($dateDebutTs === false || $dateFinTs === false) {
            return [
                'valid' => false,
                'error' => 'Les dates de début et de fin doivent être au format valide (YYYY-MM-DD)'
            ];
        }
        
        if ($dateDebutTs >= $dateFinTs) {
            return [
                'valid' => false,
                'error' => 'La date de fin doit être postérieure à la date de début'
            ];
        }
        
        // Calculer la durée en mois
        $diff = $dateFinTs - $dateDebutTs;
        $dureeMois = round($diff / (30 * 24 * 60 * 60)); // Approximation : 30 jours = 1 mois
        
        // Utiliser le type de stage choisi par l'étudiant
        $niveauCode = $inscription['niveau_code'] ?? '';
        $numeroSemestre = $inscription['numero_semestre'] ?? 0;
        
        // Valider la durée selon le type de stage choisi
        if ($typeStageChoisi === 'PFA') {
            // PFA : Stage de 2-3 mois
            if ($dureeMois < 2 || $dureeMois > 3) {
                $suggestion = '';
                if ($dureeMois >= 4 && $dureeMois <= 6) {
                    $suggestion = " Note : Une durée de {$dureeMois} mois correspond à un stage PFE (5ème année). Vous devez être en CI3-S9 ou CI3-S10 pour effectuer un PFE.";
                }
                return [
                    'valid' => false,
                    'error' => "Pour un stage PFA (CI2-S8 - 4ème année), la durée doit être entre 2 et 3 mois. Durée calculée : {$dureeMois} mois.{$suggestion}"
                ];
            }
        } elseif ($typeStageChoisi === 'PFE') {
            // PFE : Stage de 4-6 mois
            if ($dureeMois < 4 || $dureeMois > 6) {
                $suggestion = '';
                if ($dureeMois >= 2 && $dureeMois <= 3) {
                    $suggestion = " Note : Une durée de {$dureeMois} mois correspond à un stage PFA (4ème année). Pour un PFA, vous devez être en CI2-S8.";
                } elseif ($dureeMois < 2) {
                    $suggestion = " La durée minimale pour un stage PFE est de 4 mois.";
                } elseif ($dureeMois > 6) {
                    $suggestion = " La durée maximale pour un stage PFE est de 6 mois.";
                }
                return [
                    'valid' => false,
                    'error' => "Pour un stage PFE (CI3-S9/CI3-S10 - 5ème année), la durée doit être entre 4 et 6 mois. Durée calculée : {$dureeMois} mois.{$suggestion}"
                ];
            }
        }
    } else {
        // Si les dates ne sont pas fournies, on ne peut pas valider la durée
        // Mais on continue quand même car les dates sont déjà vérifiées comme requises plus haut
        $dureeMois = 0;
    }
    
    // Validation réussie - Pas de vérification de dates ni de sujet (car pas encore assignés)
    // La convention peut être générée avec les informations fournies
    
    return [
        'valid' => true,
        'data' => [
            'student' => $student,
            'inscription' => $inscription,
            'duree_mois' => $dureeMois,
            'type_stage' => $typeStageChoisi // Utiliser le type choisi
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
            
        case 'Réclamation':
            // Pour "Réclamation", on vérifie que l'étudiant existe et que le numéro de demande existe
            $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
            $stmt->execute([$apogeeNumber]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return [
                    'valid' => false,
                    'error' => 'Étudiant non trouvé dans la base de données'
                ];
            }
            
            // Vérifier que le numéro de demande existe si fourni
            $numero_demande = $additionalInfo['numero_demande'] ?? null;
            if ($numero_demande) {
                $demandeStmt = $pdo->prepare("SELECT * FROM demande WHERE numero_demande = ? AND apogee_number = ?");
                $demandeStmt->execute([$numero_demande, $apogeeNumber]);
                $demande = $demandeStmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$demande) {
                    return [
                        'valid' => false,
                        'error' => 'Numéro de demande non trouvé ou ne correspond pas à votre compte'
                    ];
                }
            }
            
            return [
                'valid' => true,
                'data' => ['student' => $student]
            ];
            
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

