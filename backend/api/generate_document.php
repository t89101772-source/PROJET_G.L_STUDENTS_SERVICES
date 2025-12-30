<?php
/**
 * Génération de documents PDF pour les attestations
 * Utilise TCPDF pour créer des documents officiels avec le logo de l'université
 */

// Ne charger la base de données que si ce n'est pas déjà fait
if (!isset($pdo) || $pdo === null) {
    require_once __DIR__ . '/../config/database.php';
}

require_once __DIR__ . '/validate_document.php';

// Vérifier que la connexion à la base de données est établie (seulement si appelé directement)
if (!defined('PDF_LIB_ONLY')) {
    if (!isset($pdo) || $pdo === null) {
        http_response_code(500);
        echo json_encode([
            'message' => 'Erreur de connexion à la base de données',
            'error' => 'Impossible de se connecter à la base de données'
        ]);
        exit;
    }
}

// Charger TCPDF
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    if (!defined('PDF_LIB_ONLY')) {
        http_response_code(500);
        echo json_encode([
            'error' => 'TCPDF non installé',
            'message' => 'Veuillez installer TCPDF en exécutant: cd backend && composer install',
            'instructions' => 'Consultez INSTALLATION_PDF.md pour plus de détails'
        ]);
        exit;
    } else {
        error_log("ERREUR: TCPDF non installé - autoload.php non trouvé à: $autoloadPath");
        throw new Exception("TCPDF non installé");
    }
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
 * Génère le footer commun pour tous les documents (attestations)
 */
function generateCommonFooter($pdf, $documentType = 'document', $apogeeNumber = '') {
    $pdf->Ln(10);
    $dateActuelle = date('d F Y', strtotime('now'));
    $dateActuelleFormat = date('d/m/Y', strtotime('now'));
    
    // N° étudiant en bas à gauche
    if (!empty($apogeeNumber)) {
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetX(30);
        $pdf->Cell(0, 6, "N° étudiant: " . $apogeeNumber, 0, 1, 'L');
        $pdf->Ln(3);
    }
    
    // Ligne 1 : Fait a TETOUAN
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetX(30);
    $pdf->Cell(0, 6, "Fait a TETOUAN, le " . $dateActuelle, 0, 1, 'L');
    
    // Ligne 2 : Fait à la Cité des Sciences
    $pdf->SetX(30);
    $pdf->Cell(0, 6, "Fait a la Cite des Sciences, le " . $dateActuelleFormat, 0, 1, 'L');
    
    $pdf->Ln(5);
    
    // Ligne 3 : Le Directeur
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetX(30);
    $pdf->Cell(0, 6, "Le Directeur de l'Ecole Superieure d'Ingenierie NovaTech", 0, 1, 'L');
    
    $pdf->Ln(10);
    
    // Zone tampon (à droite)
    // Service à gauche
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetX(30);
    $pdf->Cell(80, 6, 'Service des Affaires Estudiantines', 0, 0, 'L');
    
    // Tampon depuis l'image (à droite)
    $tamponX = 120;
    $tamponY = $pdf->GetY();
    $tamponPath = __DIR__ . '/../assets/tampon.jpeg';
    $tamponWidth = 50;
    $tamponHeight = 50;
    
    // Vérifier si l'image existe et l'insérer
    if (file_exists($tamponPath)) {
        // Insérer l'image du tampon
        $pdf->Image($tamponPath, $tamponX, $tamponY, $tamponWidth, $tamponHeight, 'JPEG', '', false, false, 0, false, false, false);
    } else {
        // Fallback : tampon simulé si l'image n'existe pas
        $pdf->SetLineWidth(1.5);
        $pdf->SetDrawColor(0, 0, 0);
        $tamponRadius = 25;
        $pdf->Circle($tamponX + $tamponWidth/2, $tamponY + $tamponHeight/2, $tamponRadius, 0, 360);
    }
    
    // Texte "Le Directeur" sous le tampon
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetXY($tamponX, $tamponY + $tamponHeight + 5);
    $pdf->Cell($tamponWidth, 5, 'Le Directeur', 0, 1, 'C');
    
    $pdf->SetY($tamponY + $tamponHeight + 15);
    
    $pdf->Ln(5);
    
    // Avis important (texte adapté selon le type de document)
    $avisText = "Avis important: Il ne peut etre delivre qu'un seul exemplaire de cette attestation. Aucun duplicata ne sera fourni.";
    if ($documentType === 'Relevé de notes') {
        $avisText = "Avis important: Il ne peut etre delivre qu'un seul exemplaire du present releve de note. Aucun duplicata ne sera fourni.";
    }
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetX(30);
    $pdf->MultiCell(0, 4, $avisText, 0, 'L');
}

/**
 * Génère un PDF pour une demande
 */
function generatePDF($demande, $validationData = []) {
    error_log("generatePDF - DÉBUT - Type document: " . ($demande['document_type'] ?? 'N/A') . ", ID: " . ($demande['id'] ?? 'N/A'));
    
    // Créer le dossier documents s'il n'existe pas
    $documentsDir = __DIR__ . '/../documents/attestations';
    if (!is_dir($documentsDir)) {
        mkdir($documentsDir, 0755, true);
        error_log("generatePDF - Dossier créé: $documentsDir");
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
    try {
        error_log("generatePDF - Création instance TCPDF...");
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        error_log("generatePDF - Instance TCPDF créée avec succès");
    } catch (Exception $e) {
        error_log("generatePDF - ERREUR création TCPDF: " . $e->getMessage());
        throw $e;
    } catch (Error $e) {
        error_log("generatePDF - ERREUR FATALE création TCPDF: " . $e->getMessage());
        throw $e;
    }
    
    // Informations du document
    $pdf->SetCreator('École Supérieure d\'Ingénierie NovaTech - Université Cité des Sciences');
    $pdf->SetAuthor('Service Administratif');
    $pdf->SetTitle($demande['document_type']);
    $pdf->SetSubject('Attestation Officielle');
    error_log("generatePDF - Informations document définies");
    
    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Marges
    $pdf->SetMargins(20, 40, 20);
    $pdf->SetHeaderMargin(10);
    $pdf->SetFooterMargin(10);
    error_log("generatePDF - Marges définies");
    
    // Ajouter une page
    // Pour le relevé de notes, l'attestation de réussite et l'attestation de scolarité, on ne crée pas de page ici (ils ont leur propre design)
    // Pour les autres documents, on crée une page
    error_log("generatePDF - Vérification type document pour création page: " . ($demande['document_type'] ?? 'N/A'));
    if ($demande['document_type'] !== 'Relevé de notes' && $demande['document_type'] !== 'Attestation de réussite' && $demande['document_type'] !== 'Attestation de scolarité' && $demande['document_type'] !== 'Convention de stage') {
        error_log("generatePDF - Création page et header général");
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
        $pdf->Cell(0, 6, 'École Supérieure d\'Ingénierie NovaTech', 0, 1, 'C');
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
    error_log("generatePDF - Header général créé");
    } else {
        error_log("generatePDF - Pas de page/header général (document spécial)");
    }

    // Pour la convention de stage, le relevé de notes, l'attestation de réussite et l'attestation de scolarité: documents spéciaux (pas une attestation "certifie que")
    error_log("generatePDF - Vérification type document pour contenu principal: " . ($demande['document_type'] ?? 'N/A'));
    if ($demande['document_type'] !== 'Convention de stage' && $demande['document_type'] !== 'Relevé de notes' && $demande['document_type'] !== 'Attestation de réussite' && $demande['document_type'] !== 'Attestation de scolarité') {
        error_log("generatePDF - Génération contenu principal");
        // Contenu principal
        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetX(30);
        $pdf->MultiCell(0, 6, 'Je soussigné(e), le Responsable du Service Administratif de l\'École Supérieure d\'Ingénierie NovaTech - Université Cité des Sciences, certifie que :', 0, 'L');
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
            error_log("generatePDF - Section attestation de scolarité dans contenu principal");
            try {
                $additionalInfo = !empty($demande['additional_info']) ? json_decode($demande['additional_info'], true) : [];
                $annee = $additionalInfo['annee_universitaire'] ?? date('Y') . '-' . (date('Y') + 1);
                error_log("generatePDF - Année: $annee");
                
                // Utiliser d'abord les données de validation si disponibles
                $inscriptionData = $validationData['inscription'] ?? null;
                error_log("generatePDF - Inscription validation: " . ($inscriptionData ? 'OUI' : 'NON'));
                
                // Si pas de données de validation, chercher l'inscription dans la BDD
                if (!$inscriptionData || empty($inscriptionData['niveau'])) {
                    error_log("generatePDF - Recherche inscription en BDD");
                    global $pdo;
                    // Chercher d'abord pour l'année spécifiée
                    $stmt = $pdo->prepare("
                        SELECT * FROM inscription 
                        WHERE apogee_number = ? AND annee_universitaire = ?
                        ORDER BY date_inscription DESC LIMIT 1
                    ");
                    $stmt->execute([$demande['apogee_number'], $annee]);
                    $inscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
                    error_log("generatePDF - Inscription trouvée (année spécifique): " . ($inscriptionData ? 'OUI' : 'NON'));
                    
                    // Si pas trouvé pour l'année spécifiée, chercher la dernière inscription valide
                    if (!$inscriptionData || empty($inscriptionData['niveau'])) {
                        error_log("generatePDF - Recherche dernière inscription valide");
                        $stmt = $pdo->prepare("
                            SELECT * FROM inscription 
                            WHERE apogee_number = ? 
                            AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
                            ORDER BY annee_universitaire DESC, date_inscription DESC 
                            LIMIT 1
                        ");
                        $stmt->execute([$demande['apogee_number']]);
                        $inscriptionData = $stmt->fetch(PDO::FETCH_ASSOC);
                        error_log("generatePDF - Inscription trouvée (dernière valide): " . ($inscriptionData ? 'OUI' : 'NON'));
                    }
                }
            } catch (Exception $e) {
                error_log("generatePDF - ERREUR dans section attestation de scolarité: " . $e->getMessage());
                error_log("generatePDF - Stack trace: " . $e->getTraceAsString());
                $inscriptionData = null;
            } catch (Error $e) {
                error_log("generatePDF - ERREUR FATALE dans section attestation de scolarité: " . $e->getMessage());
                error_log("generatePDF - Stack trace: " . $e->getTraceAsString());
                $inscriptionData = null;
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
        error_log("generatePDF - Contenu principal généré");
    } else {
        error_log("generatePDF - Pas de contenu principal (document spécial)");
    }
    
    // Contenu spécifique selon le type de document
    error_log("generatePDF - Avant switch, type: " . ($demande['document_type'] ?? 'N/A'));
    
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(30);
    
    $additionalInfo = !empty($demande['additional_info']) ? json_decode($demande['additional_info'], true) : [];
    error_log("generatePDF - Additional info: " . (!empty($additionalInfo) ? 'OUI' : 'NON'));
    
    // Utiliser les données de validation si disponibles
    $inscription = $validationData['inscription'] ?? null;
    $resultat = $validationData['resultat'] ?? null;
    error_log("generatePDF - Validation data: inscription=" . ($inscription ? 'OUI' : 'NON') . ", resultat=" . ($resultat ? 'OUI' : 'NON'));
    
    switch ($demande['document_type']) {
        case 'Attestation de scolarité':
            error_log("generatePDF - Attestation de scolarité - Début");
            
            // Créer une nouvelle page avec le design spécifique selon l'image
            $pdf->AddPage();
            error_log("generatePDF - Attestation de scolarité - Page créée");
            
            // Header spécifique pour l'attestation de scolarité (selon l'image fournie)
            $pdf->SetY(10);
            
            // Logo au centre
            $logoPath = __DIR__ . '/../assets/logo_novatech_mark.svg';
            $logoPngFallback = __DIR__ . '/../assets/logo_uae.png';
            $logoSize = 25; // Taille du logo circulaire
            $pageWidth = $pdf->getPageWidth();
            $logoX = ($pageWidth - $logoSize) / 2;
            $logoY = 10;
            
            // Dessiner un cercle pour le logo (comme dans l'image)
            $pdf->SetLineWidth(0.5);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Circle($logoX + $logoSize/2, $logoY + $logoSize/2, $logoSize/2);
            
            // Insérer le logo dans le cercle
            if (file_exists($logoPath) && method_exists($pdf, 'ImageSVG')) {
                $pdf->ImageSVG($logoPath, $logoX + 2, $logoY + 2, $logoSize - 4, $logoSize - 4, '', '', 'T', 0, false);
            } elseif (file_exists($logoPngFallback)) {
                $pdf->Image($logoPngFallback, $logoX + 2, $logoY + 2, $logoSize - 4, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Texte à gauche (français)
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetY(10);
            $pdf->SetX(20);
            $pdf->Cell(70, 5, 'ROYAUME DU MAROC', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetX(20);
            $pdf->Cell(70, 5, 'UNIVERSITE CITE DES SCIENCES', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->Cell(70, 5, 'Ecole Superieure d\'Ingenierie NovaTech', 0, 1, 'L');
            
            $pdf->SetX(20);
            $pdf->Cell(70, 5, 'Tetouan', 0, 1, 'L');
            
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetX(20);
            $pdf->SetTextColor(0, 0, 0);
            $serviceY = $pdf->GetY();
            $pdf->Cell(70, 5, 'Service des Affaires Estudiantines', 'B', 1, 'L'); // Souligné avec 'B'
            
            // Texte à droite (arabe)
            $pdf->SetFont('dejavusans', 'B', 10);
            $pdf->SetY(10);
            $pdf->SetX(110);
            $pdf->Cell(80, 5, 'المملكة المغربية', 0, 1, 'R');
            
            $pdf->SetFont('dejavusans', 'B', 9);
            $pdf->SetX(110);
            $pdf->Cell(80, 5, 'جامعة مدينة العلوم', 0, 1, 'R');
            
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetX(110);
            $pdf->Cell(80, 5, 'المدرسة العليا للهندسة نوفاتيك', 0, 1, 'R');
            
            $pdf->SetX(110);
            $pdf->Cell(80, 5, 'تطوان', 0, 1, 'R');
            
            $pdf->SetFont('dejavusans', '', 8);
            $pdf->SetX(110);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(80, 5, 'مصلحة الشؤون الطلابية', 'B', 1, 'R'); // Souligné avec 'B'
            
            // Réinitialiser la couleur du texte
            $pdf->SetTextColor(0, 0, 0);
            
            // Positionner Y après le header
            $pdf->SetY(max($serviceY + 8, $logoY + $logoSize + 5));
            $pdf->Ln(5);
            error_log("generatePDF - Attestation de scolarité - Header créé");
            
            // Titre "ATTESTATION DE SCOLARITE" dans un cadre
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetX(20);
            $pdf->SetLineWidth(1.5);
            $pdf->Cell(170, 12, 'ATTESTATION DE SCOLARITE', 1, 1, 'C', false);
            $pdf->Ln(10);
            error_log("generatePDF - Attestation de scolarité - Titre créé");
            
            // Récupérer les données RÉELLES depuis la base de données
            global $pdo;
            
            // Récupérer les informations de l'étudiant (date de naissance, etc.)
            try {
                $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
                $stmt->execute([$demande['apogee_number']]);
                $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("generatePDF - Attestation de scolarité - Étudiant récupéré: " . ($etudiant ? 'OUI' : 'NON'));
            } catch (Exception $e) {
                error_log("generatePDF - Attestation de scolarité - ERREUR récupération étudiant: " . $e->getMessage());
                $etudiant = null;
            }
            
            $annee = $additionalInfo['annee_universitaire'] ?? date('Y') . '-' . (date('Y') + 1);
            error_log("generatePDF - Attestation de scolarité - Année: $annee");
            
            // Utiliser d'abord les données de validation si disponibles
            $inscriptionReelle = $validationData['inscription'] ?? null;
            error_log("generatePDF - Attestation de scolarité - Inscription validation: " . ($inscriptionReelle ? 'OUI' : 'NON'));
            
            // Si pas de données de validation, chercher l'inscription dans la BDD
            if (!$inscriptionReelle || empty($inscriptionReelle['niveau'])) {
                error_log("generatePDF - Attestation de scolarité - Recherche inscription en BDD");
                try {
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
                    error_log("generatePDF - Attestation de scolarité - Inscription trouvée: " . ($inscriptionReelle ? 'OUI' : 'NON'));
                } catch (Exception $e) {
                    error_log("generatePDF - Attestation de scolarité - ERREUR recherche inscription: " . $e->getMessage());
                    $inscriptionReelle = null;
                }
            }
            
            // Texte d'introduction
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetX(30);
            $pdf->MultiCell(0, 6, "Le Directeur de l'Ecole Superieure d'Ingenierie NovaTech atteste que l'étudiant(e):", 0, 'L');
            $pdf->Ln(5);
            
            // Nom de l'étudiant en gras
            $nomComplet = trim(($demande['prenom'] ?? '') . ' ' . ($demande['nom'] ?? ''));
            $titre = 'Mademoiselle'; // Par défaut, on peut améliorer avec un champ genre
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->SetX(30);
            $pdf->Cell(0, 7, $titre . ' ' . strtoupper($nomComplet), 0, 1, 'L');
            $pdf->Ln(3);
            
            // Numéro de la carte d'identité nationale
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetX(30);
            $cinValue = !empty($demande['cin']) ? $demande['cin'] : (!empty($etudiant['cin']) ? $etudiant['cin'] : 'N/A');
            $pdf->Cell(0, 6, "Numéro de la carte d'identité nationale : " . $cinValue, 0, 1, 'L');
            $pdf->Ln(2);
            
            // Code national de l'étudiant (Apogée)
            $pdf->SetX(30);
            $pdf->Cell(0, 6, "Code national de l'étudiant(e) : " . $demande['apogee_number'], 0, 1, 'L');
            $pdf->Ln(2);
            
            // Date et lieu de naissance
            if (!empty($etudiant['date_naissance'])) {
                $dateNaiss = date('d F Y', strtotime($etudiant['date_naissance']));
                $lieuNaiss = !empty($etudiant['adresse']) ? $etudiant['adresse'] : 'N/A';
                $pdf->SetX(30);
                $pdf->Cell(0, 6, "née le " . $dateNaiss . ($lieuNaiss !== 'N/A' ? " à " . $lieuNaiss . " (MAROC)" : ""), 0, 1, 'L');
                $pdf->Ln(2);
            }
            
            // Poursuit ses études
            if ($inscriptionReelle && !empty($inscriptionReelle['niveau'])) {
                $niveau = cleanNiveau($inscriptionReelle['niveau']);
                $filiere = $inscriptionReelle['filiere'] ?? null;
                $anneeAffichage = $inscriptionReelle['annee_universitaire'] ?? $annee;
                $anneeAffichageFormatted = str_replace('-', '/', $anneeAffichage);
                
                // Formater le niveau pour l'affichage
                $niveauDisplay = $niveau;
                if (strpos($niveau, '2AP') !== false) {
                    $niveauDisplay = '2°Année Préparatoire';
                } elseif (strpos($niveau, 'CI') !== false) {
                    $niveauDisplay = 'Cycle Ingénieur - ' . $niveau;
                }
                
                $pdf->SetX(30);
                $pdf->Cell(0, 6, "Poursuit ses études à l'Ecole Superieure d'Ingenierie NovaTech pour l'année universitaire " . $anneeAffichageFormatted . ".", 0, 1, 'L');
                $pdf->Ln(5);
                
                // Diplôme, Filière, Année
                if ($filiere && $filiere !== 'Cycle Préparatoire') {
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Diplôme: Diplôme d'Ingénieur - " . $filiere, 0, 1, 'L');
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Filière: " . $filiere, 0, 1, 'L');
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Année: " . $niveauDisplay . ": " . $filiere, 0, 1, 'L');
                } else {
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Diplôme: Cycle Préparatoire", 0, 1, 'L');
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Année: " . $niveauDisplay, 0, 1, 'L');
                }
            } else {
                error_log("generatePDF - Attestation de scolarité - Pas d'inscription trouvée, message par défaut");
                $pdf->SetX(30);
                $pdf->Cell(0, 6, "Poursuit ses études à l'Ecole Superieure d'Ingenierie NovaTech pour l'année universitaire " . str_replace('-', '/', $annee) . ".", 0, 1, 'L');
            }
            error_log("generatePDF - Attestation de scolarité - Fin du case, avant break");
            break;
            
        case 'Attestation de réussite':
            error_log("generatePDF - Attestation de réussite - Début");
            
            // Créer une nouvelle page avec le design spécifique
            $pdf->AddPage();
            error_log("generatePDF - Attestation de réussite - Page créée");
            
            // Header spécifique pour l'attestation de réussite
            $pdf->SetY(10);
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 8, 'UNIVERSITE CITE DES SCIENCES', 0, 1, 'C');
            $pdf->SetFont('dejavusans', 'B', 16);
            $pdf->Cell(0, 8, 'جامعة مدينة العلوم', 0, 1, 'C');
            $pdf->Ln(5);
            error_log("generatePDF - Attestation de réussite - Header créé");
            
            // Récupérer les données RÉELLES depuis la base de données
            global $pdo;
            
            // Récupérer les informations de l'étudiant (date de naissance, etc.)
            try {
                $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
                $stmt->execute([$demande['apogee_number']]);
                $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("generatePDF - Attestation de réussite - Étudiant récupéré: " . ($etudiant ? 'OUI' : 'NON'));
            } catch (Exception $e) {
                error_log("generatePDF - Attestation de réussite - ERREUR récupération étudiant: " . $e->getMessage());
                $etudiant = null;
            }
            
            $niveauDemande = $additionalInfo['niveau'] ?? null;
            error_log("generatePDF - Attestation de réussite - Niveau demandé: " . ($niveauDemande ?: 'NON'));
            
            // On utilise la validation (dernière année "Réussi" pour ce niveau)
            $resultatReel = $resultat ?: null;
            error_log("generatePDF - Attestation de réussite - Résultat validation: " . ($resultatReel ? 'OUI' : 'NON'));
            
            if (!$resultatReel) {
                if (!empty($niveauDemande)) {
                    try {
                        $stmt = $pdo->prepare("
                            SELECT ra.*
                            FROM resultat_annee ra
                            WHERE ra.apogee_number = ? AND ra.niveau = ? AND ra.statut = 'Réussi'
                            ORDER BY ra.annee_universitaire DESC, ra.id DESC
                            LIMIT 1
                        ");
                        $stmt->execute([$demande['apogee_number'], $niveauDemande]);
                        $resultatReel = $stmt->fetch(PDO::FETCH_ASSOC);
                        error_log("generatePDF - Attestation de réussite - Résultat recherché: " . ($resultatReel ? 'TROUVÉ' : 'NON TROUVÉ'));
                    } catch (Exception $e) {
                        error_log("generatePDF - Attestation de réussite - ERREUR recherche résultat: " . $e->getMessage());
                        $resultatReel = null;
                    }
                } else {
                    error_log("generatePDF - Attestation de réussite - Pas de niveau demandé, recherche sans niveau");
                    try {
                        $stmt = $pdo->prepare("
                            SELECT ra.*
                            FROM resultat_annee ra
                            WHERE ra.apogee_number = ? AND ra.statut = 'Réussi'
                            ORDER BY ra.annee_universitaire DESC, ra.id DESC
                            LIMIT 1
                        ");
                        $stmt->execute([$demande['apogee_number']]);
                        $resultatReel = $stmt->fetch(PDO::FETCH_ASSOC);
                        error_log("generatePDF - Attestation de réussite - Résultat recherché (sans niveau): " . ($resultatReel ? 'TROUVÉ' : 'NON TROUVÉ'));
                    } catch (Exception $e) {
                        error_log("generatePDF - Attestation de réussite - ERREUR recherche résultat (sans niveau): " . $e->getMessage());
                        $resultatReel = null;
                    }
                }
            }
            
            // Position pour le contenu
            $pdf->SetY(50);
            error_log("generatePDF - Attestation de réussite - Position Y définie");
            
            // Titre "ATTESTATION DE REUSSITE" dans un cadre
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetX(20);
            $pdf->SetLineWidth(1.5);
            $pdf->Cell(170, 12, 'ATTESTATION DE REUSSITE', 1, 1, 'C', false);
            $pdf->Ln(10);
            error_log("generatePDF - Attestation de réussite - Titre créé");
            
            // Texte d'introduction
            $pdf->SetFont('helvetica', '', 11);
            $pdf->SetX(30);
            $pdf->MultiCell(0, 6, "Le Directeur de l'Ecole Superieure d'Ingenierie NovaTech atteste que", 0, 'L');
            $pdf->Ln(5);
            error_log("generatePDF - Attestation de réussite - Texte intro créé");
            
            error_log("generatePDF - Attestation de réussite - Vérification resultatReel: " . ($resultatReel ? 'EXISTE' : 'NULL') . ", statut: " . ($resultatReel['statut'] ?? 'N/A'));
            
            if ($resultatReel && $resultatReel['statut'] === 'Réussi') {
                error_log("generatePDF - Attestation de réussite - Résultat trouvé, génération contenu");
                $annee = $resultatReel['annee_universitaire'] ?? '—';
                $niveau = $resultatReel['niveau'] ?? 'N/A';
                
                // Formater le niveau pour l'affichage
                $niveauDisplay = $niveau;
                if (strpos($niveau, '2AP') !== false) {
                    $niveauDisplay = '2°année Préparatoire';
                } elseif (strpos($niveau, 'CI') !== false) {
                    $niveauDisplay = 'Cycle Ingénieur - ' . $niveau;
                }
                
                // Déterminer le titre (Mademoiselle/Monsieur) - on peut utiliser le genre si disponible, sinon on devine
                $titre = 'Mademoiselle'; // Par défaut, on peut améliorer avec un champ genre
                $nomComplet = trim(($demande['prenom'] ?? '') . ' ' . ($demande['nom'] ?? ''));
                
                // Nom de l'étudiant en gras
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetX(30);
                $pdf->Cell(0, 7, $titre . ' ' . strtoupper($nomComplet), 0, 1, 'L');
                $pdf->Ln(3);
                
                // Date et lieu de naissance
                if (!empty($etudiant['date_naissance'])) {
                    $dateNaiss = date('d F Y', strtotime($etudiant['date_naissance']));
                    $lieuNaiss = !empty($etudiant['adresse']) ? $etudiant['adresse'] : 'N/A';
                    $pdf->SetFont('helvetica', '', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "née le " . $dateNaiss . ($lieuNaiss !== 'N/A' ? " à " . $lieuNaiss : ""), 0, 1, 'L');
                    $pdf->Ln(3);
                }
                
                // Texte "a été déclarée admise au niveau"
                $pdf->SetFont('helvetica', '', 11);
                $pdf->SetX(30);
                $pdf->Cell(0, 6, "a été déclarée admise au niveau", 0, 1, 'L');
                $pdf->Ln(3);
                
                // Niveau en gras
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetX(30);
                $pdf->Cell(0, 7, $niveauDisplay, 0, 1, 'L');
                $pdf->Ln(3);
                
                // Année universitaire avec ligne en dessous
                $pdf->SetFont('helvetica', '', 11);
                $pdf->SetX(30);
                $pdf->Cell(0, 6, "au titre de l'année universitaire " . str_replace('-', '/', $annee), 0, 1, 'L');
                
                // Ligne horizontale sous l'année universitaire
                $lineY = $pdf->GetY() + 2;
                $pdf->SetLineWidth(0.5);
                $pdf->Line(30, $lineY, 180, $lineY);
                $pdf->SetY($lineY + 5);
                error_log("generatePDF - Attestation de réussite - Contenu généré avec succès");
            } else {
                error_log("generatePDF - Attestation de réussite - Aucun résultat trouvé, affichage message d'erreur");
                $pdf->SetFont('helvetica', '', 11);
                $pdf->SetX(30);
                $pdf->MultiCell(0, 6, "Aucun résultat de réussite n'a été trouvé pour ce niveau. Veuillez vérifier les résultats en base de données.", 0, 'L');
            }
            error_log("generatePDF - Attestation de réussite - Fin du case, avant break");
            break;
            
        case 'Relevé de notes':
            // Relevé : niveau_cible obligatoire (un niveau spécifique : 2AP1, 2AP2, CI1, CI2, CI3)
            global $pdo;

            $niveauCible = $validationData['niveau_cible'] ?? ($additionalInfo['niveau_cible'] ?? null);
            $included = $validationData['included_semestres'] ?? null;

            // Vérifier que le niveau est spécifié et valide
            if (empty($niveauCible) || $niveauCible === 'Tous') {
                $pdf->MultiCell(0, 6, "Erreur : Veuillez sélectionner un niveau spécifique pour le relevé de notes (2AP1, 2AP2, CI1, CI2 ou CI3).", 0, 'L');
                break;
            }

            // Si included n'est pas fourni, le calculer depuis le niveau
            if (!is_array($included) || empty($included)) {
                $ranges = [
                    '2AP1' => [1, 2],
                    '2AP2' => [3, 4],
                    'CI1'  => [5, 6],
                    'CI2'  => [7, 8],
                    'CI3'  => [9, 10],
                ];
                
                if (!isset($ranges[$niveauCible])) {
                    $pdf->MultiCell(0, 6, "Erreur : Niveau invalide pour le relevé de notes.", 0, 'L');
                    break;
                }
                
                // Récupérer les semestres validés pour ce niveau
                [$minS, $maxS] = $ranges[$niveauCible];
                $stmt = $pdo->prepare("
                    SELECT DISTINCT n.numero_semestre
                    FROM resultat_semestre rs
                    INNER JOIN niveau n ON n.id = rs.niveau_id
                    WHERE rs.apogee_number = ?
                      AND rs.statut = 'Validé'
                      AND n.numero_semestre >= ? AND n.numero_semestre <= ?
                    ORDER BY n.numero_semestre ASC
                ");
                $stmt->execute([$demande['apogee_number'], $minS, $maxS]);
                $included = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
                
                if (empty($included)) {
                    $pdf->MultiCell(0, 6, "Aucun semestre validé trouvé pour le niveau $niveauCible.", 0, 'L');
                    break;
                }
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

            // Récupérer les infos étudiant complètes
            $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
            $stmt->execute([$demande['apogee_number']]);
            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Récupérer l'année universitaire du relevé (première année trouvée dans les notes)
            $anneeUnivReleve = !empty($notes) ? $notes[0]['annee_universitaire'] : (date('Y') . '-' . (date('Y') + 1));
            
            // Récupérer l'inscription pour l'année universitaire du relevé (pour avoir le niveau exact)
            $stmt = $pdo->prepare("
                SELECT * FROM inscription 
                WHERE apogee_number = ? AND annee_universitaire = ?
                ORDER BY date_inscription DESC 
                LIMIT 1
            ");
            $stmt->execute([$demande['apogee_number'], $anneeUnivReleve]);
            $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Si pas trouvé pour cette année, prendre la plus récente
            if (!$inscription) {
                $stmt = $pdo->prepare("
                    SELECT * FROM inscription 
                    WHERE apogee_number = ? 
                    ORDER BY annee_universitaire DESC, date_inscription DESC 
                    LIMIT 1
                ");
                $stmt->execute([$demande['apogee_number']]);
                $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // =====================================================
            // DESIGN UNIFORME BASÉ SUR L'IMAGE (NOIR ET BLANC)
            // Une page par session (semestre)
            // =====================================================
            
            // Année universitaire
            $anneeUniv = !empty($notes) ? $notes[0]['annee_universitaire'] : (date('Y') . '-' . (date('Y') + 1));

            // Groupement par semestre
            $bySem = [];
            foreach ($notes as $n) {
                $semNum = (int)($n['numero_semestre'] ?: (int)substr($n['semestre'], 1));
                if (!isset($bySem[$semNum])) $bySem[$semNum] = [];
                $bySem[$semNum][] = $n;
            }
            ksort($bySem);

            $totalPages = count($bySem);
            $currentPage = 0;
            $overallPoints = 0.0;
            $overallCoeff = 0.0;
            $semestreMoyennes = []; // Stocker les moyennes de chaque semestre
            
            // Fonction pour générer l'en-tête selon l'image exacte
            $generateHeader = function($pdf, $anneeUniv, $sessionNum, $currentPage, $totalPages) {
                $pdf->SetY(5);
                
                // CADRE RECTANGULAIRE (comme dans l'image) - bordure épaisse
                $frameX = 20;
                $frameY = $pdf->GetY();
                $frameWidth = 155;
                $frameHeight = 22; // Hauteur du cadre interne
                
                // Bordure épaisse pour le cadre
                $pdf->SetLineWidth(1.0);
                $pdf->SetDrawColor(0, 0, 0);
                
                // Ligne 1 DANS LE CADRE : UNIVERSITE CITE DES SCIENCES (gauche) / جامعة مدينة العلوم (droite)
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetX($frameX + 5);
                $pdf->SetY($frameY + 3);
                $pdf->Cell(70, 7, 'UNIVERSITE CITE DES SCIENCES', 0, 0, 'L');
                
                $pdf->SetFont('dejavusans', 'B', 9);
                $pdf->SetX($frameX + 75);
                $pdf->Cell(75, 7, 'جامعة مدينة العلوم', 0, 1, 'R');
                
                // Ligne 2 DANS LE CADRE : Année universitaire (gauche français) / السنة الجامعية (droite arabe)
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetX($frameX + 5);
                $pdf->SetY($frameY + 14);
                $pdf->Cell(70, 6, 'Annee universitaire ' . $anneeUniv, 0, 0, 'L');
                
                $pdf->SetFont('dejavusans', '', 9);
                $pdf->SetX($frameX + 75);
                $pdf->Cell(75, 6, 'السنة الجامعية ' . $anneeUniv, 0, 1, 'R');
                
                // Dessiner le cadre autour des deux lignes (bordure épaisse) - SANS lignes horizontales à l'intérieur
                $pdf->SetLineWidth(1.0);
                $pdf->Rect($frameX, $frameY, $frameWidth, $frameHeight);
                
                // Ligne 3 HORS CADRE (en dessous) : École (gauche) / المدرسة العليا للهندسة نوفاتيك (droite)
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetX($frameX);
                $pdf->SetY($frameY + $frameHeight + 3);
                $pdf->Cell(80, 6, 'Ecole Superieure d\'Ingenierie NovaTech', 0, 0, 'L');
                
                $pdf->SetFont('dejavusans', '', 8);
                $pdf->SetX($frameX + 80);
                $pdf->Cell(75, 6, 'المدرسة العليا للهندسة نوفاتيك', 0, 1, 'R');
                
                $pdf->SetY($frameY + $frameHeight + 12);
                
                // Titre principal encadré (épais)
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->SetX(20);
                $pdf->SetLineWidth(1.5);
                $pdf->Cell(160, 10, 'RELEVE DE NOTES ET RESULTATS', 1, 1, 'C', false);
                
                // Session encadrée (fin) - centrée sous le titre
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetX(20);
                $pdf->SetLineWidth(0.5);
                $pdf->Cell(160, 8, 'Session ' . $sessionNum, 1, 0, 'C', false);
                
                // Numéro de page à droite du titre
                $pdf->SetFont('helvetica', '', 8);
                $pdf->SetX(180);
                $pdf->Cell(0, 8, 'Page : ' . $currentPage . ' / ' . $totalPages, 0, 1, 'L');
                
                $pdf->Ln(5);
            };
            
            // Informations étudiant (une seule fois, sur la première page)
            $nomComplet = trim(($etudiant['prenom'] ?? '') . ' ' . ($etudiant['nom'] ?? ''));
            
            // Déterminer le niveau basé sur les semestres inclus dans le relevé (pas sur l'inscription)
            $niveauDisplay = '';
            $filiereDisplay = '';
            
            // Mapping des semestres vers les niveaux
            $semestreToNiveau = [
                1 => '2AP1', 2 => '2AP1',
                3 => '2AP2', 4 => '2AP2',
                5 => 'CI1', 6 => 'CI1',
                7 => 'CI2', 8 => 'CI2',
                9 => 'CI3', 10 => 'CI3'
            ];
            
            // Trouver le niveau basé sur le semestre le plus élevé dans le relevé
            $maxSemestre = max(array_keys($bySem));
            $niveauCode = $semestreToNiveau[$maxSemestre] ?? '';
            
            // Récupérer la filière depuis l'inscription ou les notes
            $filiere = null;
            if ($inscription && !empty($inscription['filiere'])) {
                $filiere = $inscription['filiere'];
            } else {
                // Essayer de récupérer depuis les notes (via niveau)
                $stmt = $pdo->prepare("
                    SELECT DISTINCT i.filiere
                    FROM inscription i
                    INNER JOIN niveau n ON i.niveau_id = n.id
                    WHERE i.apogee_number = ?
                      AND n.numero_semestre IN (" . implode(',', array_keys($bySem)) . ")
                      AND i.filiere IS NOT NULL
                      AND i.filiere != 'Cycle Préparatoire'
                    ORDER BY i.annee_universitaire DESC
                    LIMIT 1
                ");
                $stmt->execute([$demande['apogee_number']]);
                $filiereRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($filiereRow) {
                    $filiere = $filiereRow['filiere'];
                }
            }
            
            // Formater l'affichage du niveau
            if ($niveauCode) {
                if (strpos($niveauCode, '2AP') !== false) {
                    $niveauDisplay = $niveauCode; // 2AP1 ou 2AP2 (au lieu de "2ème Année Préparatoire")
                } elseif (strpos($niveauCode, 'CI') !== false) {
                    $niveauDisplay = $niveauCode; // CI1, CI2, CI3
                    if ($filiere && $filiere !== 'Cycle Préparatoire') {
                        $filiereDisplay = $filiere;
                    }
                }
            } else {
                // Fallback : utiliser l'inscription si disponible
                if ($inscription && !empty($inscription['niveau'])) {
                    $niveauDisplay = cleanNiveau($inscription['niveau']);
                    // Si c'est 2AP, garder 2AP1 ou 2AP2 (ne pas transformer en "2ème Année Préparatoire")
                    if (strpos($niveauDisplay, 'CI') !== false) {
                        // Garder CI1, CI2, CI3
                        if ($filiere && $filiere !== 'Cycle Préparatoire') {
                            $filiereDisplay = $filiere;
                        }
                    }
                    // Si c'est 2AP, cleanNiveau devrait déjà retourner 2AP1 ou 2AP2
                }
            }
            
            // Créer la première page AVANT la boucle (car pour Relevé de notes, on ne crée pas de page initiale)
            $pdf->AddPage();
            
            // Parcourir chaque semestre et créer une page par semestre
            foreach ($bySem as $semNum => $rows) {
                $currentPage++;
                
                // Créer une nouvelle page pour chaque session (sauf la première qui vient d'être créée)
                if ($currentPage > 1) {
                    $pdf->AddPage();
                }
                
                // Générer l'en-tête
                $generateHeader($pdf, $anneeUniv, $semNum, $currentPage, $totalPages);
                
                // Informations étudiant sur TOUTES les pages - format compact
                $pdf->Ln(1);
                $pdf->SetFont('helvetica', '', 9);
                $pdf->SetX(30);
                $pdf->Cell(0, 5, $nomComplet, 0, 1, 'L');
                
                $pdf->SetX(30);
                $pdf->Cell(40, 5, 'CNE:', 0, 0, 'L');
                $pdf->Cell(50, 5, $demande['apogee_number'], 0, 0, 'L');
                // Récupérer le CIN depuis la demande ou l'étudiant
                $cinValue = !empty($demande['cin']) ? $demande['cin'] : (!empty($etudiant['cin']) ? $etudiant['cin'] : 'N/A');
                $pdf->Cell(30, 5, 'CIN:', 0, 0, 'L');
                $pdf->Cell(0, 5, $cinValue, 0, 1, 'L');
                
                if ($niveauDisplay) {
                    $pdf->SetX(30);
                    // Afficher le niveau avec la filière si c'est un cycle ingénieur
                    $niveauText = 'inscrite en ' . $niveauDisplay;
                    if ($filiereDisplay && strpos($niveauDisplay, 'CI') !== false) {
                        $niveauText .= ' - ' . $filiereDisplay;
                    }
                    $pdf->Cell(0, 5, $niveauText, 0, 1, 'L');
                }
                
                // Afficher "a obtenu les notes suivantes:" seulement sur la première page
                if ($currentPage === 1) {
                    $pdf->SetX(30);
                    $pdf->Cell(0, 5, 'a obtenu les notes suivantes:', 0, 1, 'L');
                }
                $pdf->Ln(2);
                
                // Tableau pour ce semestre - format compact pour tenir sur une page
                $pdf->SetFont('helvetica', 'B', 8);
                $pdf->SetFillColor(255, 255, 255);
                $pdf->SetDrawColor(0, 0, 0);
                
                // En-tête du tableau (colonnes compactes)
                $pdf->Cell(50, 6, 'Module', 1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Note/20', 1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Session', 1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Pts jury', 1, 0, 'C', true);
                $pdf->Cell(20, 6, 'Resultat', 1, 1, 'C', true);
                
                // Calculer la moyenne de ce semestre
                $semPoints = 0.0;
                $semCoeff = 0.0;
                
                // Lignes du tableau pour ce semestre - format compact
                $pdf->SetFont('helvetica', '', 8);
                foreach ($rows as $r) {
                    $coef = (float)$r['coefficient'];
                    $noteVal = (float)$r['note'];
                    $points = $noteVal * $coef;
                    $semPoints += $points;
                    $semCoeff += $coef;
                    $overallPoints += $points;
                    $overallCoeff += $coef;

                    // Déterminer le résultat
                    $resultat = 'Non Valide';
                    if (isset($semMap[$semNum])) {
                        $sr = $semMap[$semNum];
                        $resultat = ($sr['statut'] === 'Validé') ? 'Valide' : 'Non Valide';
                    } else {
                        $resultat = ($noteVal >= 10) ? 'Valide' : 'Non Valide';
                    }
                    
                    // Format de la session : S + numéro (format compact)
                    $sessionText = 'S' . $semNum;
                    
                    $pdf->Cell(50, 5, mb_substr($r['nom_module'], 0, 28), 1, 0, 'L');
                    $pdf->Cell(20, 5, number_format($noteVal, 2), 1, 0, 'C');
                    $pdf->Cell(20, 5, $sessionText, 1, 0, 'C');
                    $pdf->Cell(20, 5, '', 1, 0, 'C'); // Pts jury vide
                    $pdf->Cell(20, 5, $resultat, 1, 1, 'C');
                }
                
                // Moyenne de ce semestre - format compact
                $moyenneSemestre = ($semCoeff > 0) ? ($semPoints / $semCoeff) : 0.0;
                $moyenneSemestreFormatted = number_format($moyenneSemestre, 2);
                $semestreMoyennes[$semNum] = $moyenneSemestreFormatted;
                
                // Déterminer le statut selon la moyenne
                $statutSemestre = '';
                if ($moyenneSemestre >= 10) {
                    $statutSemestre = 'Admis';
                } else {
                    $statutSemestre = 'Ajourné';
                }
                
                // Vérifier l'espace disponible avant d'afficher la moyenne
                $currentY = $pdf->GetY();
                if ($currentY < 250) {
                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 10);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 6, "Moyenne Session {$semNum}: {$moyenneSemestreFormatted}/20 - {$statutSemestre}", 0, 1, 'L');
                }
                
                // Si c'est la dernière page, afficher la moyenne globale avec statut
                if ($currentPage === $totalPages && $totalPages > 1) {
                    $moyenneGlobale = ($overallCoeff > 0) ? ($overallPoints / $overallCoeff) : 0.0;
                    $moyenneGlobaleFormatted = number_format($moyenneGlobale, 2);
                    
                    // Déterminer le statut global
                    $statutGlobal = '';
                    if ($moyenneGlobale >= 10) {
                        $statutGlobal = 'Admis';
                    } else {
                        $statutGlobal = 'Ajourné';
                    }
                    
                    $pdf->Ln(5);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->SetX(30);
                    $pdf->Cell(0, 7, "Moyenne globale: {$moyenneGlobaleFormatted}/20 - {$statutGlobal}", 0, 1, 'L');
                }
                
                // Pied de page sur CHAQUE page - utiliser la fonction commune
                generateCommonFooter($pdf, 'Relevé de notes', $demande['apogee_number']);
            }
            
            break;
            
        case 'Convention de stage':
            error_log("generatePDF - Convention de stage - Début");
            
            // Récupérer les données RÉELLES depuis la base de données
            global $pdo;
            
            // Créer la première page
            $pdf->AddPage();
            error_log("generatePDF - Convention de stage - Page 1 créée");
            
            // Filigrane "CONFIDENTIEL" en diagonale (sur toutes les pages)
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetFont('helvetica', 'B', 60);
            $pdf->StartTransform();
            $pdf->Rotate(45, 105, 150);
            $pdf->SetXY(50, 120);
            $pdf->Cell(0, 0, 'CONFIDENTIEL', 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetTextColor(0, 0, 0);
            
            // Header avec logo au centre (comme attestation de scolarité)
            $pdf->SetY(10);
            
            // Logo au centre
            $logoPath = __DIR__ . '/../assets/logo_novatech_mark.svg';
            $logoPngFallback = __DIR__ . '/../assets/logo_uae.png';
            $logoSize = 25;
            $pageWidth = $pdf->getPageWidth();
            $logoX = ($pageWidth - $logoSize) / 2;
            $logoY = 10;
            
            // Dessiner un cercle pour le logo
            $pdf->SetLineWidth(0.5);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->Circle($logoX + $logoSize/2, $logoY + $logoSize/2, $logoSize/2);
            
            // Insérer le logo dans le cercle
            if (file_exists($logoPath) && method_exists($pdf, 'ImageSVG')) {
                $pdf->ImageSVG($logoPath, $logoX + 2, $logoY + 2, $logoSize - 4, $logoSize - 4, '', '', 'T', 0, false);
            } elseif (file_exists($logoPngFallback)) {
                $pdf->Image($logoPngFallback, $logoX + 2, $logoY + 2, $logoSize - 4, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
            }
            
            // Texte à gauche (français)
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetY(10);
            $pdf->SetX(20);
            $pdf->Cell(70, 4, 'UNIVERSITE CITE DES SCIENCES', 0, 1, 'L');
            $pdf->SetX(20);
            $pdf->Cell(70, 4, 'Ecole Superieure d\'Ingenierie NovaTech', 0, 1, 'L');
            $pdf->SetX(20);
            $pdf->Cell(70, 4, 'Tetouan', 0, 1, 'L');
            
            // Texte à droite (arabe)
            $pdf->SetFont('dejavusans', '', 9);
            $pdf->SetY(10);
            $pdf->SetX(110);
            $pdf->Cell(80, 4, 'جامعة مدينة العلوم', 0, 1, 'R');
            $pdf->SetX(110);
            $pdf->Cell(80, 4, 'المدرسة العليا للهندسة نوفاتيك', 0, 1, 'R');
            $pdf->SetX(110);
            $pdf->Cell(80, 4, 'تطوان', 0, 1, 'R');
            
            // Positionner Y après le header
            $pdf->SetY($logoY + $logoSize + 5);
            $pdf->Ln(3);
            
            // Titre "CONVENTION DE STAGE" avec type de stage
            $pdf->SetFont('helvetica', 'B', 14);
            $typeStageChoisi = $additionalInfo['type_stage'] ?? null;
            $typeStageText = '';
            if ($typeStageChoisi === 'PFA') {
                $typeStageText = ' - PFA (Projet de Fin d\'Année)';
            } elseif ($typeStageChoisi === 'PFE') {
                $typeStageText = ' - PFE (Projet de Fin d\'Études)';
            }
            $pdf->Cell(0, 8, 'CONVENTION DE STAGE' . $typeStageText, 0, 1, 'C');
            
            // Sous-titre
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->Cell(0, 5, '(2 exemplaires imprimés en recto-verso)', 0, 1, 'C');
            $pdf->Ln(5);
            
            // Section "ENTRE"
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'ENTRE', 0, 1, 'L');
            $pdf->Ln(2);
            
            // Récupérer les informations de l'étudiant
            $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ?");
            $stmt->execute([$demande['apogee_number']]);
            $etudiant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Récupérer l'inscription pour la filière
            $stmt = $pdo->prepare("SELECT * FROM inscription WHERE apogee_number = ? ORDER BY annee_universitaire DESC, date_inscription DESC LIMIT 1");
            $stmt->execute([$demande['apogee_number']]);
            $inscription = $stmt->fetch(PDO::FETCH_ASSOC);
            $filiere = $inscription['filiere'] ?? 'Non spécifiée';
            
            // Partie 1: L'Etablissement
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'L\'Etablissement :', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 4.5, 'L\'Ecole Superieure d\'Ingenierie NovaTech, Universite Cite des Sciences - Tetouan', 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Adresse: B.P. 2222, Mhannech II, Tetouan, Maroc', 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Tel. +212 5 39 68 80 27; Fax. +212 39 99 46 24.', 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Représenté par le Professeur en qualité de Directeur.', 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Ci-après, dénommé l\'Etablissement.', 0, 1, 'L');
            $pdf->Ln(2);
            
            // Partie 2: L'ENTREPRISE
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'ET', 0, 1, 'L');
            $pdf->Ln(1);
            $pdf->Cell(0, 5, 'L\'ENTREPRISE :', 0, 1, 'L');
            
            $nomEntreprise = $additionalInfo['nom_entreprise'] ?? '';
            $adresseEntreprise = $additionalInfo['adresse_entreprise'] ?? '';
            $telEntreprise = $additionalInfo['tel_entreprise'] ?? '';
            $emailEntreprise = $additionalInfo['email_entreprise'] ?? '';
            $representantEntreprise = $additionalInfo['representant_entreprise'] ?? '';
            $qualiteRepresentant = $additionalInfo['qualite_representant'] ?? '';
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 4.5, 'La Société : ' . ($nomEntreprise ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Adresse : ' . ($adresseEntreprise ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Tel : ' . ($telEntreprise ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Email : ' . ($emailEntreprise ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Représentée par Monsieur ' . ($representantEntreprise ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'en qualité ' . ($qualiteRepresentant ?: '................................'), 0, 1, 'L');
            $pdf->Cell(0, 4.5, 'Ci-après dénommée L\'ENTREPRISE.', 0, 1, 'L');
            $pdf->Ln(3);
            
            // Utiliser le type de stage choisi par l'étudiant
            $typeStageChoisi = $additionalInfo['type_stage'] ?? null;
            if ($typeStageChoisi === 'PFA') {
                $typeStage = 'PFA (Projet de Fin d\'Année)';
            } elseif ($typeStageChoisi === 'PFE') {
                $typeStage = 'PFE (Projet de Fin d\'Études)';
            } else {
                $typeStage = 'Stage';
            }
            
            $studentName = trim(($demande['prenom'] ?? '') . ' ' . ($demande['nom'] ?? ''));
            
            // Formater les dates si elles sont au format YYYY-MM-DD
            $dateDebutRaw = $additionalInfo['date_debut'] ?? '';
            $dateFinRaw = $additionalInfo['date_fin'] ?? '';
            
            if (!empty($dateDebutRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDebutRaw)) {
                // Convertir de YYYY-MM-DD vers DD/MM/YYYY
                $dateDebut = date('d/m/Y', strtotime($dateDebutRaw));
            } else {
                $dateDebut = $dateDebutRaw ?: '............';
            }
            
            if (!empty($dateFinRaw) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFinRaw)) {
                // Convertir de YYYY-MM-DD vers DD/MM/YYYY
                $dateFin = date('d/m/Y', strtotime($dateFinRaw));
            } else {
                $dateFin = $dateFinRaw ?: '............';
            }
            
            $encadrant = $additionalInfo['encadrant'] ?? '............';
            $tuteur = $additionalInfo['tuteur'] ?? '............';
            $themeStage = $additionalInfo['theme_stage'] ?? '';
            
            // Article 1: Engagement
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 1: Engagement', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "L'ENTREPRISE accepte de recevoir à titre de stagiaire {$studentName} étudiant de la filière du Cycle Ingénieur «{$filiere}» de l'ENSA de Tetouan, Universite Cite des Sciences (Tetouan), pour une période allant du {$dateDebut} au {$dateFin}.", 0, 'L');
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4, "En aucun cas, cette convention ne pourra autoriser les étudiants à s'absenter durant la période des contrôles ou des enseignements.", 0, 'L');
            $pdf->Ln(2);
            
            // Article 2: Objet
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 2: Objet', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Le stage aura pour objet essentiel d'assurer l'application pratique de l'enseignement donné par l'Etablissement, et ce, en organisant des visites sur les installations et en réalisant des études proposées par L'ENTREPRISE.", 0, 'L');
            $pdf->Ln(2);
            
            // Article 3: Encadrement et suivi
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 3: Encadrement et suivi', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Pour accompagner le Stagiaire durant son stage, et ainsi instaurer une véritable collaboration L'ENTREPRISE/Stagiaire/Etablissement, L'ENTREPRISE désigne Mme/Mr {$encadrant} encadrant(e) et parrain(e), pour superviser et assurer la qualité du travail fourni par le Stagiaire.", 0, 'L');
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "L'Etablissement désigne {$tuteur} en tant que tuteur qui procurera une assistance pédagogique.", 0, 'L');
            $pdf->Ln(2);
            
            // Article 4: Programme
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 4: Programme', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->Cell(0, 4, 'Le thème du stage est: «', 0, 0, 'L');
            $pdf->Ln(4);
            if ($themeStage) {
                $pdf->SetX(25);
                $pdf->MultiCell(165, 4, $themeStage, 0, 'L');
            } else {
                $pdf->SetX(25);
                $pdf->MultiCell(165, 4, str_repeat('.', 200), 0, 'L');
            }
            $pdf->SetX(20);
            $pdf->Cell(0, 4, '»', 0, 1, 'L');
            
            // Créer la page 2 après l'Article 4 (forcer la création pour avoir exactement 2 pages)
            $pdf->AddPage();
            // Réappliquer le filigrane sur la page 2
            $pdf->SetTextColor(200, 200, 200);
            $pdf->SetFont('helvetica', 'B', 60);
            $pdf->StartTransform();
            $pdf->Rotate(45, 105, 150);
            $pdf->SetXY(50, 120);
            $pdf->Cell(0, 0, 'CONFIDENTIEL', 0, 0, 'C');
            $pdf->StopTransform();
            $pdf->SetTextColor(0, 0, 0);
            // Réinitialiser la position Y
            $pdf->SetY(20);
            
            // Article 5: Indemnité
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 5: Indemnité', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Si L'ENTREPRISE et l'étudiant le conviennent, ce dernier pourra recevoir de la part de l'entreprise une indemnité forfaitaire pour les frais engagés au cours de la mission confiée à l'étudiant.", 0, 'L');
            $pdf->Ln(1);
            
            // Article 6: Règlement
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 6: Règlement', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Durant le stage, le stagiaire reste sous la responsabilité de l'Etablissement. L'étudiant doit informer l'école dans les 24 heures de toute modification de la convention signée, faute de quoi il assumera l'entière responsabilité du non-respect. Le stagiaire est soumis à la discipline et au règlement intérieur de l'entreprise. L'entreprise se réserve le droit de mettre fin au stage en cas de non-respect, après consultation du Directeur de l'Etablissement.", 0, 'L');
            $pdf->Ln(1);
            
            // Article 7: Confidentialité
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 7: Confidentialité', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Le stagiaire ainsi que toutes les personnes intervenant dans son travail (y compris l'administration de l'Etablissement et le tuteur pédagogique) sont tenus au secret professionnel. Ils s'engagent à ne pas diffuser les informations recueillies pour des publications, conférences ou communications sans accord préalable de L'ENTREPRISE. Cette obligation reste valable même après expiration du stage.", 0, 'L');
            $pdf->Ln(1);
            
            // Article 8: Assurance
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 8: Assurance accident de travail', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "Le stagiaire doit souscrire une assurance couvrant la Responsabilité Civile et les Accidents de Travail pendant le stage et les déplacements. En cas d'accident de travail pendant le stage, L'ENTREPRISE s'engage à fournir immédiatement à l'Etablissement toutes les informations nécessaires à la déclaration d'accident.", 0, 'L');
            $pdf->Ln(1);
            
            // Article 9: Evaluation
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 9: Evaluation de L\'ENTREPRISE', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "A l'issue du stage, le parrain établira un rapport d'appréciation générale sur le travail effectué et le comportement du stagiaire pendant son séjour à L'ENTREPRISE. L'ENTREPRISE remettra au stagiaire une attestation indiquant la nature et la durée des travaux effectués.", 0, 'L');
            $pdf->Ln(1);
            
            // Article 10: Rapport de stage
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 5, 'Article 10: Rapport de stage', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetX(20);
            $pdf->MultiCell(0, 4.5, "A l'issue de chaque stage, le stagiaire rédigera un rapport détaillant son travail et son expérience au sein de L'ENTREPRISE. Ce rapport sera communiqué à L'ENTREPRISE et restera strictement confidentiel.", 0, 'L');
            $pdf->Ln(2);
            
            // Vérifier si on a assez d'espace pour le footer (date + signatures + tampon)
            // Hauteur nécessaire: ~60mm (date: 4mm, Ln: 3mm, signatures: 25mm, tampon: 35mm, marge: 3mm)
            $currentY = $pdf->GetY();
            $pageHeight = $pdf->getPageHeight();
            $footerMargin = 60; // Hauteur nécessaire pour le footer complet
            
            // Si on dépasse, on est déjà sur la page 2, donc on continue
            // Sinon, vérifier si on a assez d'espace
            if ($currentY + $footerMargin > ($pageHeight - 20)) {
                // Pas assez d'espace, mais on est déjà sur la page 2, donc on continue quand même
                // On réduit les espacements pour que tout tienne
                $pdf->Ln(2);
            } else {
                $pdf->Ln(3);
            }
            
            // Footer avec date et signatures
            $pdf->SetFont('helvetica', '', 8);
            $dateActuelle = date('d/m/Y');
            $pdf->Cell(0, 4, "Fait à Tetouan en deux exemplaires, le {$dateActuelle}", 0, 1, 'L');
            $pdf->Ln(2);
            
            // Zones de signature (2x2) - format compact
            $signY = $pdf->GetY();
            $signWidth = 80;
            $signHeight = 18;
            $marginX = 20;
            $marginBetween = 10;
            
            // Ligne 1: Stagiaire et Coordonnateur
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY($marginX, $signY);
            $pdf->Cell($signWidth, 4, 'Nom et signature du Stagiaire', 'T', 0, 'C');
            $pdf->SetXY($marginX + $signWidth + $marginBetween, $signY);
            $pdf->Cell($signWidth, 4, 'Le Coordonnateur de la filière', 'T', 0, 'C');
            
            // Ligne 2: Etablissement et Entreprise
            $pdf->SetXY($marginX, $signY + $signHeight);
            $pdf->Cell($signWidth, 4, 'Signature et cachet de L\'Etablissement', 'T', 0, 'C');
            
            // Ajouter le tampon sous "Signature et cachet de L'Etablissement" - positionné pour ne pas cacher le texte
            $tamponPath = __DIR__ . '/../assets/tampon.jpeg';
            $tamponWidth = 25;
            $tamponHeight = 25;
            $tamponX = $marginX + ($signWidth - $tamponWidth) / 2;
            // Positionner le tampon plus bas pour ne pas cacher le texte au-dessus
            $tamponY = $signY + $signHeight + 8;
            
            if (file_exists($tamponPath)) {
                $pdf->Image($tamponPath, $tamponX, $tamponY, $tamponWidth, $tamponHeight, 'JPEG', '', false, false, 0, false, false, false);
            }
            
            $pdf->SetXY($marginX + $signWidth + $marginBetween, $signY + $signHeight);
            $pdf->Cell($signWidth, 4, 'Signature et cachet de L\'ENTREPRISE', 'T', 0, 'C');
            
            // Ne pas afficher le numéro de page seul - supprimé pour éviter l'affichage isolé
            
            error_log("generatePDF - Convention de stage - Terminée");
            
            break;
            
        default:
            $description = $additionalInfo['description'] ?? 'Document demandé';
            $pdf->MultiCell(0, 6, "demande : $description", 0, 'L');
            break;
    }
    
    error_log("generatePDF - Avant footer, type: " . ($demande['document_type'] ?? 'N/A'));
    
    // Footer commun pour toutes les attestations (sauf relevé de notes et convention de stage qui ont leur propre footer)
    if ($demande['document_type'] !== 'Relevé de notes' && $demande['document_type'] !== 'Convention de stage') {
        error_log("generatePDF - Génération footer pour: " . ($demande['document_type'] ?? 'N/A'));
        try {
            generateCommonFooter($pdf, $demande['document_type'], $demande['apogee_number']);
            error_log("generatePDF - Footer généré avec succès");
        } catch (Exception $e) {
            error_log("generatePDF - ERREUR lors de la génération footer: " . $e->getMessage());
            throw $e;
        }
    } else {
        error_log("generatePDF - Pas de footer (Relevé de notes)");
    }
    
    // Nom du fichier
    $filename = 'attestation_' . $demande['id'] . '_' . date('Ymd') . '.pdf';
    $filepath = $documentsDir . '/' . $filename;
    error_log("generatePDF - Nom fichier: $filename, chemin: $filepath");
    
    // Sauvegarder le PDF
    try {
        error_log("generatePDF - Tentative de sauvegarde PDF...");
        $pdf->Output($filepath, 'F');
        error_log("generatePDF - PDF sauvegardé avec succès: $filepath, existe: " . (file_exists($filepath) ? 'OUI' : 'NON'));
    } catch (Exception $e) {
        error_log("generatePDF - ERREUR lors de la sauvegarde PDF: " . $e->getMessage());
        error_log("generatePDF - Stack trace: " . $e->getTraceAsString());
        throw $e;
    } catch (Error $e) {
        error_log("generatePDF - ERREUR FATALE lors de la sauvegarde PDF: " . $e->getMessage());
        error_log("generatePDF - Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
    
    $relativePath = 'documents/attestations/' . $filename;
    error_log("generatePDF - Retour de la fonction: $relativePath");
    return $relativePath;
}
?>

