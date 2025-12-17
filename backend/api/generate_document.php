<?php
/**
 * Génération de documents PDF pour les attestations
 * Utilise TCPDF pour créer des documents officiels avec le logo de l'université
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validate_document.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données'
    ]);
    exit;
}

// Charger TCPDF
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'TCPDF non installé',
        'message' => 'Veuillez installer TCPDF en exécutant: cd backend && composer install',
        'instructions' => 'Consultez INSTALLATION_PDF.md pour plus de détails'
    ]);
    exit;
}

require_once $autoloadPath;

// Permet d'utiliser ce fichier comme "lib" sans exécuter l'endpoint (pour resend-document, etc.)
if (!defined('PDF_LIB_ONLY')) {
    header('Content-Type: application/json; charset=utf-8');

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $demandeId = $input['demande_id'] ?? null;
        
        if (empty($demandeId) || !is_numeric($demandeId)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID de demande invalide']);
            exit;
        }
        
        try {
            // Récupérer les informations de la demande et de l'étudiant
            $stmt = $pdo->prepare("
                SELECT d.*, e.nom, e.prenom, e.email, e.cin, e.apogee_number
                FROM demande d
                LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
                WHERE d.id = ?
            ");
            $stmt->execute([$demandeId]);
            $demande = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$demande || empty($demande)) {
                http_response_code(404);
                echo json_encode(['error' => 'Demande non trouvée']);
                exit;
            }
            
            if ($demande['status'] !== 'Acceptée') {
                http_response_code(400);
                echo json_encode(['error' => 'La demande doit être acceptée pour générer le document']);
                exit;
            }
            
            // VALIDATION CRITIQUE : Vérifier que l'étudiant a le droit de recevoir ce document
            $additionalInfo = !empty($demande['additional_info']) 
                ? json_decode($demande['additional_info'], true) 
                : [];
            
            $validation = validateDocumentRequest(
                $pdo, 
                $demande['document_type'], 
                $demande['apogee_number'], 
                $additionalInfo
            );
            
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Validation échouée',
                    'message' => $validation['error'],
                    'document_type' => $demande['document_type'],
                    'details' => 'Le document ne peut pas être généré car les conditions ne sont pas remplies.'
                ]);
                exit;
            }
            
            // Générer le PDF
            $pdfPath = generatePDF($demande, $validation['data'] ?? []);
            
            // Mettre à jour la demande avec le chemin du document
            try {
                // Vérifier si la colonne existe, sinon l'ajouter
                $checkColumn = $pdo->query("SHOW COLUMNS FROM demande LIKE 'document_path'");
                if ($checkColumn->rowCount() == 0) {
                    // Ajouter la colonne si elle n'existe pas
                    $pdo->exec("ALTER TABLE demande ADD COLUMN document_path VARCHAR(500) NULL");
                }
                
                $updateStmt = $pdo->prepare("UPDATE demande SET document_path = ? WHERE id = ?");
                $updateStmt->execute([$pdfPath, $demandeId]);
            } catch (PDOException $e) {
                error_log('Error updating document_path: ' . $e->getMessage());
                // Continuer même si la mise à jour échoue
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Document généré avec succès',
                'document_path' => $pdfPath,
                'download_url' => '/api/download-document?demande_id=' . $demandeId
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            error_log('Generate document error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Erreur lors de la génération du document',
                'message' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
}

/**
 * Nettoie le niveau pour n'afficher que la partie principale (CI3, CI2, 2AP1, 2AP2) sans le semestre
 */
function cleanNiveau($niveau) {
    if (empty($niveau)) {
        return $niveau;
    }
    
    // Si le niveau contient un tiret suivi de "S" (ex: "CI3-S9", "2AP1-S1"), extraire seulement la partie avant le tiret
    if (preg_match('/^([^-]+)/', $niveau, $matches)) {
        return trim($matches[1]);
    }
    
    return $niveau;
}

/**
 * Génère un PDF pour une demande
 */
function generatePDF($demande, $validationData = []) {
    // Créer le dossier documents s'il n'existe pas
    $documentsDir = __DIR__ . '/../documents/attestations';
    if (!is_dir($documentsDir)) {
        mkdir($documentsDir, 0755, true);
    }
    
    // Charger les constantes TCPDF si nécessaire
    if (!defined('PDF_PAGE_ORIENTATION')) {
        define('PDF_PAGE_ORIENTATION', 'P'); // Portrait
    }
    if (!defined('PDF_UNIT')) {
        define('PDF_UNIT', 'mm');
    }
    if (!defined('PDF_PAGE_FORMAT')) {
        define('PDF_PAGE_FORMAT', 'A4');
    }
    
    // Créer une instance de TCPDF
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Informations du document
    $pdf->SetCreator('École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences');
    $pdf->SetAuthor('Service Administratif');
    $pdf->SetTitle($demande['document_type']);
    $pdf->SetSubject('Attestation Officielle');
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(true);
    $pdf->setPrintFooter(true);
    
    // Marges
    $pdf->SetMargins(20, 40, 20);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    
    // Ajouter une page
    $pdf->AddPage();

    // =====================================================
    // En-tête premium: logo centré + cartouche blanc + bordures dorée et bleue
    // =====================================================
    $pageW = $pdf->getPageWidth();

    // Couleurs (pro)
    $blue = [37, 99, 235];   // #2563eb
    $gold = [212, 175, 55];  // doré

    // Cartouche logo
    $boxW = 70;
    $boxH = 30;
    $boxX = ($pageW - $boxW) / 2;
    $boxY = 10;

    // Fond blanc + double bordure (extérieur bleu, intérieur doré)
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetLineWidth(0.6);
    $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
    if (method_exists($pdf, 'RoundedRect')) {
        $pdf->RoundedRect($boxX, $boxY, $boxW, $boxH, 3, '1111', 'DF');
    } else {
        $pdf->Rect($boxX, $boxY, $boxW, $boxH, 'DF');
    }

    $pdf->SetLineWidth(0.4);
    $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
    $inset = 1.2;
    if (method_exists($pdf, 'RoundedRect')) {
        $pdf->RoundedRect($boxX + $inset, $boxY + $inset, $boxW - 2 * $inset, $boxH - 2 * $inset, 2.4, '1111', 'D');
    } else {
        $pdf->Rect($boxX + $inset, $boxY + $inset, $boxW - 2 * $inset, $boxH - 2 * $inset, 'D');
    }

    // Logo (SVG recommandé) - centré dans la cartouche (fond blanc => lisible)
    // Utiliser un logo "PDF-safe" (sans gradients/texte) pour éviter les rendus bizarres
    $logoSvgPath = __DIR__ . '/../assets/logo_novatech_mark.svg';
    $logoPngFallback = __DIR__ . '/../assets/logo_uae.png';
    $logoW = 22;
    $logoH = 22;
    $logoX = $boxX + ($boxW - $logoW) / 2;
    $logoY = $boxY + ($boxH - $logoH) / 2;
    if (file_exists($logoSvgPath) && method_exists($pdf, 'ImageSVG')) {
        $pdf->ImageSVG($logoSvgPath, $logoX, $logoY, $logoW, $logoH, '', '', 'T', 0, false);
    } elseif (file_exists($logoPngFallback)) {
        $pdf->Image($logoPngFallback, $logoX, $logoY, $logoW, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }

    // Lignes décoratives sous le cartouche
    $lineY = $boxY + $boxH + 6;
    $pdf->SetLineWidth(0.7);
    $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
    $pdf->Line(20, $lineY, 190, $lineY);
    $pdf->SetLineWidth(0.35);
    $pdf->SetDrawColor($blue[0], $blue[1], $blue[2]);
    $pdf->Line(20, $lineY + 1.6, 190, $lineY + 1.6);
    
    // En-tête (texte)
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetY($lineY + 6);
    $pdf->Cell(0, 8, 'UNIVERSITÉ CITÉ DES SCIENCES', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'École Supérieure d’Ingénierie NovaTech', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 5, 'Université Cité des Sciences', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Ligne de séparation
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->Ln(15);
    
    // Titre du document
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, strtoupper($demande['document_type']), 0, 1, 'C');
    $pdf->Ln(10);

    // Pour la convention de stage: document "convention" (pas une attestation "certifie que")
    if ($demande['document_type'] !== 'Convention de stage') {
        // Contenu principal
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetX(30);
        $pdf->MultiCell(0, 6, 'Je soussigné(e), le Responsable du Service Administratif de l\'École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences, certifie que :', 0, 'L');
        $pdf->Ln(10);

        // Informations de l'étudiant
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(40);
        $pdf->Cell(60, 7, 'Nom et Prénom :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $demande['nom'] . ' ' . $demande['prenom'], 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(40);
        $pdf->Cell(60, 7, 'Numéro Apogée :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $demande['apogee_number'], 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(40);
        $pdf->Cell(60, 7, 'CIN :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $demande['cin'], 0, 1, 'L');

        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetX(40);
        $pdf->Cell(60, 7, 'Email :', 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(0, 7, $demande['email'], 0, 1, 'L');

        // Pour l'attestation de scolarité, ajouter le niveau dans les informations
        if ($demande['document_type'] === 'Attestation de scolarité') {
            $additionalInfo = !empty($demande['additional_info']) ? json_decode($demande['additional_info'], true) : [];
            $annee = $additionalInfo['annee_universitaire'] ?? date('Y') . '-' . (date('Y') + 1);
            
            // Utiliser d'abord les données de validation si disponibles
            $inscriptionData = $validationData['inscription'] ?? null;
            
            // Si pas de données de validation, chercher l'inscription dans la BDD
            if (!$inscriptionData || empty($inscriptionData['niveau'])) {
                global $pdo;
                // Chercher d'abord pour l'année spécifiée
                $stmt = $pdo->prepare("
                    SELECT * FROM inscription 
                    WHERE apogee_number = ? AND annee_universitaire = ?
                    ORDER BY date_inscription DESC LIMIT 1
                ");
                $stmt->execute([$demande['apogee_number'], $annee]);
                $inscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si pas trouvé pour l'année spécifiée, chercher la dernière inscription valide
                if (!$inscriptionData || empty($inscriptionData['niveau'])) {
                    $stmt = $pdo->prepare("
                        SELECT * FROM inscription 
                        WHERE apogee_number = ? 
                        AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
                        ORDER BY annee_universitaire DESC, date_inscription DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$demande['apogee_number']]);
                    $inscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            // Afficher le niveau s'il est disponible (sans semestre, juste le niveau: CI3, CI2, 2AP1, 2AP2)
            if ($inscriptionData && !empty($inscriptionData['niveau'])) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->SetX(40);
                $pdf->Cell(60, 7, 'Niveau :', 0, 0, 'L');
                $pdf->SetFont('helvetica', '', 11);
                $niveauDisplay = cleanNiveau($inscriptionData['niveau']);
                
                // Ajouter la filière si disponible
                if (!empty($inscriptionData['filiere']) && $inscriptionData['filiere'] !== 'Cycle Préparatoire') {
                    $niveauDisplay .= ' - ' . $inscriptionData['filiere'];
                }
                
                $pdf->Cell(0, 7, $niveauDisplay, 0, 1, 'L');
            }
        }

        $pdf->Ln(10);
    }
    
    // Contenu spécifique selon le type de document
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(30);
    
    $additionalInfo = !empty($demande['additional_info']) ? json_decode($demande['additional_info'], true) : [];
    
    // Utiliser les données de validation si disponibles
    $inscription = $validationData['inscription'] ?? null;
    $resultat = $validationData['resultat'] ?? null;
    
    switch ($demande['document_type']) {
        case 'Attestation de scolarité':
            // Récupérer les données RÉELLES depuis la base de données
            $annee = $additionalInfo['annee_universitaire'] ?? date('Y') . '-' . (date('Y') + 1);
            
            // Utiliser d'abord les données de validation si disponibles
            $inscriptionReelle = $validationData['inscription'] ?? null;
            
            // Si pas de données de validation, chercher l'inscription dans la BDD
            if (!$inscriptionReelle || empty($inscriptionReelle['niveau'])) {
                global $pdo;
                // Chercher d'abord pour l'année spécifiée
                $stmt = $pdo->prepare("
                    SELECT * FROM inscription 
                    WHERE apogee_number = ? AND annee_universitaire = ?
                    ORDER BY date_inscription DESC LIMIT 1
                ");
                $stmt->execute([$demande['apogee_number'], $annee]);
                $inscriptionReelle = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Si pas trouvé pour l'année spécifiée, chercher la dernière inscription valide
                if (!$inscriptionReelle || empty($inscriptionReelle['niveau'])) {
                    $stmt = $pdo->prepare("
                        SELECT * FROM inscription 
                        WHERE apogee_number = ? 
                        AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
                        ORDER BY annee_universitaire DESC, date_inscription DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$demande['apogee_number']]);
                    $inscriptionReelle = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            if ($inscriptionReelle && !empty($inscriptionReelle['niveau'])) {
                $niveau = cleanNiveau($inscriptionReelle['niveau']);
                $filiere = $inscriptionReelle['filiere'] ?? null;
                $statut = $inscriptionReelle['statut'] ?? 'Inscrit';
                // Utiliser l'année de l'inscription trouvée si différente de celle demandée
                $anneeAffichage = $inscriptionReelle['annee_universitaire'] ?? $annee;
                
                // Mentionner l'année exacte d'inscription avec le niveau (sans semestre: CI3, CI2, 2AP1, 2AP2)
                if ($filiere && $filiere !== 'Cycle Préparatoire') {
                    $pdf->MultiCell(0, 6, "est régulièrement inscrit(e) à l'École Supérieure d'Ingénierie NovaTech - Université Cité des Sciences pour l'année universitaire $anneeAffichage au niveau $niveau en $filiere (Statut: $statut).", 0, 'L');
                } else {
                    // Cycle préparatoire (pas de filière) ou filière non spécifiée
                    $pdf->MultiCell(0, 6, "est régulièrement inscrit(e) à l'École Supérieure d'Ingénierie NovaTech - Université Cité des Sciences pour l'année universitaire $anneeAffichage au niveau $niveau (Statut: $statut).", 0, 'L');
                }
            } else {
                // Fallback si aucune inscription trouvée (ne devrait pas arriver après validation)
                $pdf->MultiCell(0, 6, "est régulièrement inscrit(e) à l'École Supérieure d'Ingénierie NovaTech - Université Cité des Sciences pour l'année universitaire $annee.", 0, 'L');
            }
            break;
            
        case 'Attestation de réussite':
            // Récupérer les données RÉELLES depuis la base de données
            $niveauDemande = $additionalInfo['niveau'] ?? null;
            
            // On utilise la validation (dernière année "Réussi" pour ce niveau)
            $resultatReel = $resultat ?: null;
            if (!$resultatReel) {
                global $pdo;
                if (!empty($niveauDemande)) {
                    $stmt = $pdo->prepare("
                        SELECT ra.*
                        FROM resultat_annee ra
                        WHERE ra.apogee_number = ? AND ra.niveau = ? AND ra.statut = 'Réussi'
                        ORDER BY ra.annee_universitaire DESC, ra.id DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$demande['apogee_number'], $niveauDemande]);
                    $resultatReel = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
                
                if ($resultatReel && $resultatReel['statut'] === 'Réussi') {
                    $annee = $resultatReel['annee_universitaire'] ?? '—';
                    $niveau = $resultatReel['niveau'] ?? 'N/A';
                    $filiere = $resultatReel['filiere'] ?? 'N/A';
                    $moyenne = number_format($resultatReel['moyenne_generale'], 2);
                    $mention = $resultatReel['mention'] ?? '';
                    
                    // Texte détaillé avec toutes les informations
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "a validé avec succès le niveau $niveau pour l'année universitaire $annee au sein de l'École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences.", 0, 'L');
                    $pdf->Ln(5);
                    
                    // Détails supplémentaires
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(40);
                    $pdf->Cell(70, 7, 'Niveau :', 0, 0, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->Cell(0, 7, $niveau, 0, 1, 'L');
                    
                    if ($filiere !== 'N/A') {
                        $pdf->SetFont('helvetica', 'B', 11);
                        $pdf->SetX(40);
                        $pdf->Cell(70, 7, 'Filière :', 0, 0, 'L');
                        $pdf->SetFont('helvetica', '', 11);
                        $pdf->Cell(0, 7, $filiere, 0, 1, 'L');
                    }
                    
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(40);
                    $pdf->Cell(70, 7, 'Moyenne générale :', 0, 0, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->Cell(0, 7, $moyenne . '/20', 0, 1, 'L');
                    
                    if ($mention) {
                        $pdf->SetFont('helvetica', 'B', 11);
                        $pdf->SetX(40);
                        $pdf->Cell(70, 7, 'Mention :', 0, 0, 'L');
                        $pdf->SetFont('helvetica', '', 11);
                        $pdf->Cell(0, 7, $mention, 0, 1, 'L');
                    }
                } else {
                    $pdf->MultiCell(0, 6, "Aucun résultat de réussite n'a été trouvé pour ce niveau. Veuillez vérifier les résultats en base de données.", 0, 'L');
                }
            break;
            
        case 'Relevé de notes':
            // Relevé (options):
            // - niveau_cible = Tous => S1 jusqu'au dernier semestre validé
            // - niveau_cible = un niveau => semestres validés de ce niveau
            global $pdo;

            $niveauCible = $validationData['niveau_cible'] ?? ($additionalInfo['niveau_cible'] ?? 'Tous');
            $included = $validationData['included_semestres'] ?? null;

            if (!is_array($included) || empty($included)) {
                // Fallback minimal si validationData non transmis
                $stmt = $pdo->prepare("
                    SELECT MAX(n.numero_semestre) AS max_sem
                    FROM resultat_semestre rs
                    INNER JOIN niveau n ON n.id = rs.niveau_id
                    WHERE rs.apogee_number = ?
                      AND rs.statut = 'Validé'
                ");
                $stmt->execute([$demande['apogee_number']]);
                $maxValidated = (int)($stmt->fetchColumn() ?: 0);
                if ($maxValidated <= 0) $maxValidated = 10;
                $included = range(1, $maxValidated);
                $niveauCible = 'Tous';
            }

            $placeholders = implode(',', array_fill(0, count($included), '?'));
            $params = array_merge([$demande['apogee_number']], $included);

            // Notes (toutes années confondues) sur les semestres inclus
            $stmt = $pdo->prepare("
                SELECT nt.*, n.numero_semestre
                FROM note nt
                LEFT JOIN niveau n ON n.id = nt.niveau_id
                WHERE nt.apogee_number = ?
                  AND COALESCE(n.numero_semestre, CAST(SUBSTRING(nt.semestre, 2) AS UNSIGNED)) IN ($placeholders)
                ORDER BY COALESCE(n.numero_semestre, CAST(SUBSTRING(nt.semestre, 2) AS UNSIGNED)) ASC, nt.code_module ASC
            ");
            $stmt->execute($params);
            $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($notes)) {
                $pdf->MultiCell(0, 6, "Aucune note trouvée pour cet étudiant. Impossible de générer un relevé de notes.", 0, 'L');
                break;
            }

            // Résultats par semestre pour afficher les moyennes semestrielles si disponibles
            $stmt = $pdo->prepare("
                SELECT rs.niveau_id, rs.annee_universitaire, rs.moyenne_semestre, rs.statut, rs.mention, n.numero_semestre
                FROM resultat_semestre rs
                INNER JOIN niveau n ON n.id = rs.niveau_id
                WHERE rs.apogee_number = ?
                  AND n.numero_semestre IN ($placeholders)
                ORDER BY n.numero_semestre ASC
            ");
            $stmt->execute($params);
            $semResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $semMap = [];
            foreach ($semResults as $sr) {
                $semMap[(int)$sr['numero_semestre']] = $sr;
            }

            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 11);
            $minSem = min($included);
            $maxSem = max($included);
            if ($niveauCible === 'Tous') {
                $pdf->Cell(0, 7, "Relevé de notes (complet) - S{$minSem} à S{$maxSem}", 0, 1, 'L');
            } else {
                $pdf->Cell(0, 7, "Relevé de notes - Niveau {$niveauCible} (semestres validés)", 0, 1, 'L');
            }
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', '', 10);
            $pdf->MultiCell(0, 6, "Ce relevé présente les notes des semestres validés disponibles en base de données pour l'étudiant.", 0, 'L');
            $pdf->Ln(2);

            // Groupement par semestre
            $bySem = [];
            foreach ($notes as $n) {
                $semNum = (int)($n['numero_semestre'] ?: (int)substr($n['semestre'], 1));
                if (!isset($bySem[$semNum])) $bySem[$semNum] = [];
                $bySem[$semNum][] = $n;
            }
            ksort($bySem);

            $overallPoints = 0.0;
            $overallCoeff = 0.0;

            foreach ($bySem as $semNum => $rows) {
                $semLabel = 'S' . $semNum;
                $year = $rows[0]['annee_universitaire'] ?? '';

                $pdf->Ln(3);
                $pdf->SetFont('helvetica', 'B', 10);
                $title = "Semestre {$semLabel}" . ($year ? " - {$year}" : "");
                $pdf->Cell(0, 6, $title, 0, 1, 'L');

                if (isset($semMap[$semNum])) {
                    $sr = $semMap[$semNum];
                    $pdf->SetFont('helvetica', '', 9);
                    $pdf->Cell(0, 5, "Moyenne: " . number_format((float)$sr['moyenne_semestre'], 2) . "/20  |  Statut: {$sr['statut']}" . ($sr['mention'] ? "  |  Mention: {$sr['mention']}" : ""), 0, 1, 'L');
                }

                // Tableau
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Cell(22, 7, 'Code', 1, 0, 'C', true);
                $pdf->Cell(88, 7, 'Module', 1, 0, 'C', true);
                $pdf->Cell(14, 7, 'Note', 1, 0, 'C', true);
                $pdf->Cell(14, 7, 'Coef.', 1, 0, 'C', true);
                $pdf->Cell(28, 7, 'Mention', 1, 1, 'C', true);

                $pdf->SetFont('helvetica', '', 9);
                $semPoints = 0.0;
                $semCoeff = 0.0;
                foreach ($rows as $r) {
                    $coef = (float)$r['coefficient'];
                    $noteVal = (float)$r['note'];
                    $points = $noteVal * $coef;
                    $semPoints += $points;
                    $semCoeff += $coef;
                    $overallPoints += $points;
                    $overallCoeff += $coef;

                    $pdf->Cell(22, 6, $r['code_module'], 1, 0, 'C');
                    $pdf->Cell(88, 6, mb_substr($r['nom_module'], 0, 42), 1, 0, 'L');
                    $pdf->Cell(14, 6, number_format($noteVal, 2), 1, 0, 'C');
                    $pdf->Cell(14, 6, number_format($coef, 2), 1, 0, 'C');
                    $pdf->Cell(28, 6, $r['mention'] ?? '-', 1, 1, 'C');
                }

                if ($semCoeff > 0) {
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->Cell(110, 6, "Moyenne {$semLabel}", 1, 0, 'R', true);
                    $pdf->Cell(56, 6, number_format($semPoints / $semCoeff, 2) . "/20", 1, 1, 'C', true);
                }
            }

            // Afficher la moyenne globale seulement si ce n'est pas un relevé complet (Tous)
            if ($overallCoeff > 0 && $niveauCible !== 'Tous') {
                $pdf->Ln(4);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(0, 7, "Moyenne globale (S1 à S{$maxSem}) : " . number_format($overallPoints / $overallCoeff, 2) . "/20", 0, 1, 'R');
            }
            break;
            
        case 'Convention de stage':
            // Récupérer les données RÉELLES depuis la base de données
            global $pdo;
            
            // Utiliser le type de stage choisi par l'étudiant
            $typeStageChoisi = $additionalInfo['type_stage'] ?? null;
            
            if ($typeStageChoisi === 'PFA') {
                $typeStage = 'PFA (Projet de Fin d\'Année)';
            } elseif ($typeStageChoisi === 'PFE') {
                $typeStage = 'PFE (Projet de Fin d\'Études)';
            } else {
                // Fallback : déterminer automatiquement si non spécifié
                $typeStage = 'Stage';
            }
            
            $nomEntreprise = $additionalInfo['nom_entreprise'] ?? null;
            
            if ($nomEntreprise) {
                // Fonction pour générer le texte professionnel de convention de stage
                $generateConventionText = function($pdf, $demande, $entreprise, $adresseEntreprise, $typeStage, $duree, $encadrant) {
                    $pdf->Ln(2);
                    $studentName = trim(($demande['prenom'] ?? '') . ' ' . ($demande['nom'] ?? ''));

                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "La présente convention définit le cadre d'accueil et de réalisation d'un stage au bénéfice du Stagiaire {$studentName}, inscrit(e) à l'École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences.", 0, 'L');

                    $pdf->Ln(4);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Parties', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "1) L'École Supérieure d’Ingénierie NovaTech, ci-après « l'École ».\n2) L'entreprise {$entreprise}, sise à {$adresseEntreprise}, ci-après « l'Entreprise ».\n3) Le Stagiaire {$studentName} (Apogée: {$demande['apogee_number']}, CIN: {$demande['cin']}).", 0, 'L');

                    $pdf->Ln(4);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Article 1 - Objet', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "L'Entreprise accueille le Stagiaire dans le cadre d'un {$typeStage}. Le stage a pour objectif de permettre au Stagiaire d'appliquer ses acquis, de développer des compétences professionnelles et de découvrir l'environnement de travail.", 0, 'L');

                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Article 2 - Durée et organisation', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "Durée indicative: {$duree}.\nLes modalités pratiques (planning, lieu, missions, outils) sont définies par l'Entreprise, en concertation avec l'École, conformément au règlement interne et aux règles de sécurité.", 0, 'L');

                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Article 3 - Encadrement et suivi', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $encTxt = ($encadrant && $encadrant !== 'Non spécifié') ? $encadrant : "un encadrant désigné par l'Entreprise";
                    $pdf->MultiCell(0, 6, "Le Stagiaire est encadré par {$encTxt}. L'École peut désigner un référent pédagogique et assurer le suivi du stage selon les procédures internes.", 0, 'L');

                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Article 4 - Confidentialité et conduite', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "Le Stagiaire s'engage à respecter la confidentialité des informations et documents auxquels il/elle accède, ainsi que les règles de discipline, d'éthique et de sécurité de l'Entreprise.", 0, 'L');

                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, 'Article 5 - Dispositions finales', 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->MultiCell(0, 6, "La présente convention atteste uniquement de l'accord d'accueil. Toute clause spécifique (assurance, gratification, propriété intellectuelle) peut être précisée par l'Entreprise selon sa politique interne et la réglementation en vigueur.", 0, 'L');
                };
                
                // Utiliser les données du formulaire
                $entreprise = $additionalInfo['nom_entreprise'] ?? 'N/A';
                $adresseEntreprise = $additionalInfo['adresse_entreprise'] ?? 'N/A';
                $duree = $additionalInfo['duree_stage'] ?? 'N/A';
                $encadrant = $additionalInfo['encadrant'] ?? 'Non spécifié';
                
                $generateConventionText($pdf, $demande, $entreprise, $adresseEntreprise, $typeStage, $duree, $encadrant);
            } else {
                $pdf->MultiCell(0, 6, "demande une convention de stage" . ($typeStage ? " de type $typeStage" : "") . ".", 0, 'L');
            }
            break;
            
        default:
            $description = $additionalInfo['description'] ?? 'Document demandé';
            $pdf->MultiCell(0, 6, "demande : $description", 0, 'L');
            break;
    }
    
    $pdf->Ln(15);
    
    // Date et lieu
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(30);
    $date = date('d/m/Y');
    $pdf->Cell(0, 7, "Fait à la Cité des Sciences, le $date", 0, 1, 'L');
    
    $pdf->Ln(20);
    
    // Signature
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetX(30);
    $pdf->Cell(0, 7, 'Le Responsable du Service Administratif', 0, 1, 'L');
    
    $pdf->Ln(10);
    
    // QR Code pour vérification
    // URL locale pour le développement (à changer en production)
    // IMPORTANT: localhost ne fonctionne QUE sur l'ordinateur local
    // Pour tester depuis un téléphone, utilisez l'IP locale (ex: http://192.168.1.100:8000)
    // En production, utilisez le vrai domaine (ex: https://votre-domaine.com)
   $baseUrl = 'http://localhost:8000'; // ⚠️ Changez en IP locale pour tester sur téléphone
   
   $verificationUrl = $baseUrl . '/verify_document.php?id=' . $demande['id'];
    
    // Position pour le QR code (en bas à droite) - éviter pages vides
    $qrSize = 30; // Taille du QR code
    $qrX = 150; // Position X

    $pageH = $pdf->getPageHeight();
    $bottomMargin = method_exists($pdf, 'getFooterMargin') ? $pdf->getFooterMargin() : 10;
    $safeBottom = $pageH - $bottomMargin - 8;
    $qrYFixed = $safeBottom - $qrSize - 10; // place le QR au-dessus du footer

    // Si le contenu actuel descend trop bas, passer à une nouvelle page
    if ($pdf->GetY() > ($qrYFixed - 22)) {
        $pdf->AddPage();
    }

    $qrY = $qrYFixed;
    
    $pdf->SetX($qrX);
    $pdf->write2DBarcode($verificationUrl, 'QRCODE,L', $qrX, $qrY, $qrSize, $qrSize, [
        'border' => true,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => [0, 0, 0],
        'bgcolor' => false,
        'module_width' => 1,
        'module_height' => 1
    ], 'N');
    
    // Texte sous le QR code
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY($qrX, $qrY + $qrSize + 2);
    $pdf->Cell($qrSize, 5, 'Scanner pour verifier', 0, 1, 'C');
    
    // Code de vérification textuel
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetXY(30, $qrY);
    $pdf->Cell(0, 5, 'Code de verification: #' . $demande['id'], 0, 1, 'L');
    
    // Nom du fichier
    $filename = 'attestation_' . $demande['id'] . '_' . date('Ymd') . '.pdf';
    $filepath = $documentsDir . '/' . $filename;
    
    // Sauvegarder le PDF
    $pdf->Output($filepath, 'F');
    
    return 'documents/attestations/' . $filename;
}
?>

