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

/**
 * Génère un PDF pour une demande
 */
function generatePDF($demande) {
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
    $pdf->SetCreator('Université Abdelmalek Essaidi');
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
    
    // Logo de l'université (si disponible)
    $logoPath = __DIR__ . '/../assets/logo_uae.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 20, 15, 30, 0, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
    }
    
    // En-tête avec le nom de l'université
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetY(20);
    $pdf->Cell(0, 10, 'UNIVERSITÉ ABDELMALEK ESSAIDI', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 5, 'Tétouan, Maroc', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Ligne de séparation
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
    $pdf->Ln(15);
    
    // Titre du document
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, strtoupper($demande['document_type']), 0, 1, 'C');
    $pdf->Ln(10);
    
    // Contenu principal
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(30);
    $pdf->MultiCell(0, 6, 'Je soussigné(e), le Responsable du Service Administratif de l\'Université Abdelmalek Essaidi, certifie que :', 0, 'L');
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
    
    $pdf->Ln(10);
    
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
            
            // Chercher l'inscription réelle dans la BDD
            global $pdo;
            $stmt = $pdo->prepare("
                SELECT * FROM inscription 
                WHERE apogee_number = ? AND annee_universitaire = ?
                ORDER BY date_inscription DESC LIMIT 1
            ");
            $stmt->execute([$demande['apogee_number'], $annee]);
            $inscriptionReelle = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($inscriptionReelle) {
                $niveau = $inscriptionReelle['niveau'];
                $filiere = $inscriptionReelle['filiere'];
                $statut = $inscriptionReelle['statut'];
                $pdf->MultiCell(0, 6, "est régulièrement inscrit(e) à l'Université Abdelmalek Essaidi pour l'année universitaire $annee au niveau $niveau en $filiere (Statut: $statut).", 0, 'L');
            } else {
                $pdf->MultiCell(0, 6, "est régulièrement inscrit(e) à l'Université Abdelmalek Essaidi pour l'année universitaire $annee.", 0, 'L');
            }
            break;
            
        case 'Attestation de réussite':
            // Récupérer les données RÉELLES depuis la base de données
            $annee = $additionalInfo['annee_universitaire'] ?? null;
            $niveau = $additionalInfo['niveau'] ?? null;
            
            if ($annee && $niveau) {
                // Chercher le résultat réel dans la BDD
                global $pdo;
                $stmt = $pdo->prepare("
                    SELECT * FROM resultat_annee 
                    WHERE apogee_number = ? AND annee_universitaire = ? AND niveau = ?
                ");
                $stmt->execute([$demande['apogee_number'], $annee, $niveau]);
                $resultatReel = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($resultatReel && $resultatReel['statut'] === 'Réussi') {
                    $moyenne = number_format($resultatReel['moyenne_generale'], 2);
                    $mention = $resultatReel['mention'] ?? '';
                    $mentionText = $mention ? " avec la mention \"$mention\"" : '';
                    $pdf->MultiCell(0, 6, "a réussi avec succès ses études à l'Université Abdelmalek Essaidi pour l'année universitaire $annee au niveau $niveau avec une moyenne générale de $moyenne/20$mentionText.", 0, 'L');
                } else {
                    $pdf->MultiCell(0, 6, "a réussi ses études à l'Université Abdelmalek Essaidi pour l'année universitaire $annee au niveau $niveau.", 0, 'L');
                }
            } else {
                $pdf->MultiCell(0, 6, "a réussi avec succès ses études à l'Université Abdelmalek Essaidi.", 0, 'L');
            }
            break;
            
        case 'Relevé de notes':
            // Récupérer les notes RÉELLES depuis la base de données
            $annee = $additionalInfo['annee_universitaire'] ?? null;
            $semestre = $additionalInfo['semestre'] ?? 'Tous';
            
            if ($annee) {
                // Chercher les notes réelles dans la BDD
                global $pdo;
                if ($semestre && $semestre !== 'Tous') {
                    $stmt = $pdo->prepare("
                        SELECT * FROM note 
                        WHERE apogee_number = ? AND annee_universitaire = ? AND semestre = ?
                        ORDER BY semestre, code_module
                    ");
                    $stmt->execute([$demande['apogee_number'], $annee, $semestre]);
                } else {
                    $stmt = $pdo->prepare("
                        SELECT * FROM note 
                        WHERE apogee_number = ? AND annee_universitaire = ?
                        ORDER BY semestre, code_module
                    ");
                    $stmt->execute([$demande['apogee_number'], $annee]);
                }
                $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($notes)) {
                    $pdf->Ln(5);
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, "Relevé de notes - Année universitaire $annee" . ($semestre !== 'Tous' ? " - Semestre $semestre" : ""), 0, 1, 'L');
                    $pdf->Ln(3);
                    
                    // Tableau des notes
                    $pdf->SetFont('helvetica', 'B', 9);
                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(20, 7, 'Code', 1, 0, 'C', true);
                    $pdf->Cell(80, 7, 'Module', 1, 0, 'C', true);
                    $pdf->Cell(15, 7, 'Note', 1, 0, 'C', true);
                    $pdf->Cell(20, 7, 'Coef.', 1, 0, 'C', true);
                    $pdf->Cell(25, 7, 'Mention', 1, 0, 'C', true);
                    $pdf->Cell(20, 7, 'Sem.', 1, 1, 'C', true);
                    
                    $pdf->SetFont('helvetica', '', 9);
                    $totalPoints = 0;
                    $totalCoeff = 0;
                    $notesParSemestre = ['S1' => ['points' => 0, 'coeff' => 0], 'S2' => ['points' => 0, 'coeff' => 0]];
                    
                    foreach ($notes as $note) {
                        $points = $note['note'] * $note['coefficient'];
                        $totalPoints += $points;
                        $totalCoeff += $note['coefficient'];
                        
                        if (isset($notesParSemestre[$note['semestre']])) {
                            $notesParSemestre[$note['semestre']]['points'] += $points;
                            $notesParSemestre[$note['semestre']]['coeff'] += $note['coefficient'];
                        }
                        
                        $pdf->Cell(20, 6, $note['code_module'], 1, 0, 'C');
                        $pdf->Cell(80, 6, substr($note['nom_module'], 0, 35), 1, 0, 'L');
                        $pdf->Cell(15, 6, number_format($note['note'], 2), 1, 0, 'C');
                        $pdf->Cell(20, 6, number_format($note['coefficient'], 2), 1, 0, 'C');
                        $pdf->Cell(25, 6, $note['mention'] ?? '-', 1, 0, 'C');
                        $pdf->Cell(20, 6, $note['semestre'], 1, 1, 'C');
                    }
                    
                    // Moyennes
                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 10);
                    if ($totalCoeff > 0) {
                        $moyenneGenerale = $totalPoints / $totalCoeff;
                        $pdf->Cell(0, 7, "Moyenne générale: " . number_format($moyenneGenerale, 2) . "/20", 0, 1, 'R');
                    }
                    
                    // Moyennes par semestre
                    foreach ($notesParSemestre as $sem => $data) {
                        if ($data['coeff'] > 0) {
                            $moyenneSem = $data['points'] / $data['coeff'];
                            $pdf->Cell(0, 6, "Moyenne $sem: " . number_format($moyenneSem, 2) . "/20", 0, 1, 'R');
                        }
                    }
                    
                    // Résultat (validé ou non)
                    $pdf->Ln(3);
                    $pdf->SetFont('helvetica', 'B', 11);
                    if ($annee && isset($resultat)) {
                        $statutResultat = $resultat['statut'] ?? 'En cours';
                        $couleur = ($statutResultat === 'Réussi') ? [0, 128, 0] : [255, 0, 0];
                        $pdf->SetTextColor($couleur[0], $couleur[1], $couleur[2]);
                        $pdf->Cell(0, 7, "Résultat: " . strtoupper($statutResultat), 0, 1, 'L');
                        $pdf->SetTextColor(0, 0, 0);
                    }
                } else {
                    $pdf->MultiCell(0, 6, "Aucune note trouvée pour l'année universitaire $annee.", 0, 'L');
                }
            } else {
                $pdf->MultiCell(0, 6, "Relevé de notes pour l'année universitaire demandée.", 0, 'L');
            }
            break;
            
        case 'Convention de stage':
            // Récupérer les données RÉELLES depuis la base de données
            global $pdo;
            
            // Récupérer l'inscription pour déterminer le type de stage (PFA ou PFE)
            $currentYear = date('Y');
            $anneeCourante = $currentYear . '-' . ($currentYear + 1);
            $anneePrecedente = ($currentYear - 1) . '-' . $currentYear;
            
            $stmtInscription = $pdo->prepare("
                SELECT niveau, filiere, annee_universitaire 
                FROM inscription 
                WHERE apogee_number = ? 
                AND (annee_universitaire = ? OR annee_universitaire = ?)
                AND niveau IN ('2A', '3A')
                AND statut IN ('Inscrit', 'Réinscrit', 'Diplômé')
                ORDER BY annee_universitaire DESC, niveau DESC
                LIMIT 1
            ");
            $stmtInscription->execute([$demande['apogee_number'], $anneeCourante, $anneePrecedente]);
            $inscription = $stmtInscription->fetch(PDO::FETCH_ASSOC);
            
            $typeStage = '';
            if ($inscription) {
                $typeStage = $inscription['niveau'] === '2A' ? 'PFA (Projet de Fin d\'Année)' : 'PFE (Projet de Fin d\'Études)';
            }
            
            $nomEntreprise = $additionalInfo['nom_entreprise'] ?? null;
            
            if ($nomEntreprise) {
                // Chercher le stage réel dans la BDD
                $stmt = $pdo->prepare("
                    SELECT * FROM stage 
                    WHERE apogee_number = ? 
                    AND (annee_universitaire = ? OR annee_universitaire = ?)
                    AND nom_entreprise = ?
                    AND statut IN ('Approuvé', 'En cours', 'Terminé')
                    ORDER BY date_debut DESC LIMIT 1
                ");
                $stmt->execute([$demande['apogee_number'], $anneeCourante, $anneePrecedente, $nomEntreprise]);
                $stageReel = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($stageReel) {
                    $entreprise = $stageReel['nom_entreprise'];
                    $adresse = $stageReel['adresse_entreprise'] ?? 'Non spécifiée';
                    $duree = $stageReel['duree_semaines'] . ' semaines';
                    $dateDebut = date('d/m/Y', strtotime($stageReel['date_debut']));
                    $dateFin = date('d/m/Y', strtotime($stageReel['date_fin']));
                    $sujet = $stageReel['sujet_stage'] ?? 'Non spécifié';
                    $tuteurEntreprise = $stageReel['tuteur_entreprise'] ?? 'Non spécifié';
                    $tuteurUniversitaire = $stageReel['tuteur_universitaire'] ?? 'Non spécifié';
                    
                    if ($typeStage) {
                        $pdf->MultiCell(0, 6, "demande une convention de stage de type $typeStage au sein de l'entreprise : $entreprise située à $adresse pour une durée de $duree du $dateDebut au $dateFin.", 0, 'L');
                    } else {
                        $pdf->MultiCell(0, 6, "demande une convention de stage au sein de l'entreprise : $entreprise située à $adresse pour une durée de $duree du $dateDebut au $dateFin.", 0, 'L');
                    }
                    
                    if ($sujet !== 'Non spécifié') {
                        $pdf->Ln(3);
                        $pdf->MultiCell(0, 6, "Sujet du stage : $sujet", 0, 'L');
                    }
                    
                    if ($tuteurEntreprise !== 'Non spécifié' || $tuteurUniversitaire !== 'Non spécifié') {
                        $pdf->Ln(3);
                        if ($tuteurEntreprise !== 'Non spécifié') {
                            $pdf->MultiCell(0, 6, "Tuteur entreprise : $tuteurEntreprise", 0, 'L');
                        }
                        if ($tuteurUniversitaire !== 'Non spécifié') {
                            $pdf->MultiCell(0, 6, "Tuteur universitaire : $tuteurUniversitaire", 0, 'L');
                        }
                    }
                } else {
                    // Utiliser les données du formulaire si le stage n'est pas encore en BDD
                    $entreprise = $additionalInfo['nom_entreprise'] ?? 'N/A';
                    $adresse = $additionalInfo['adresse_entreprise'] ?? 'N/A';
                    $duree = $additionalInfo['duree_stage'] ?? 'N/A';
                    $dateDebut = isset($additionalInfo['date_debut']) ? date('d/m/Y', strtotime($additionalInfo['date_debut'])) : 'N/A';
                    $dateFin = isset($additionalInfo['date_fin']) ? date('d/m/Y', strtotime($additionalInfo['date_fin'])) : 'N/A';
                    
                    if ($typeStage) {
                        $pdf->MultiCell(0, 6, "demande une convention de stage de type $typeStage au sein de l'entreprise : $entreprise située à $adresse pour une durée de $duree du $dateDebut au $dateFin.", 0, 'L');
                    } else {
                        $pdf->MultiCell(0, 6, "demande une convention de stage au sein de l'entreprise : $entreprise située à $adresse pour une durée de $duree du $dateDebut au $dateFin.", 0, 'L');
                    }
                }
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
    $pdf->Cell(0, 7, "Fait à Tétouan, le $date", 0, 1, 'L');
    
    $pdf->Ln(20);
    
    // Signature
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetX(30);
    $pdf->Cell(0, 7, 'Le Responsable du Service Administratif', 0, 1, 'L');
    
    $pdf->Ln(10);
    
    // QR Code pour vérification
    // URL locale pour le développement (à changer en production)
    $baseUrl = 'http://localhost:8000';
    $verificationUrl = $baseUrl . '/verify_document.php?id=' . $demande['id'];
    
    // Position pour le QR code (en bas à droite)
    $qrX = 150; // Position X
    $qrY = $pdf->GetY() + 10; // Position Y
    $qrSize = 30; // Taille du QR code
    
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

