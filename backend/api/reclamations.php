<?php
require_once __DIR__ . '/../config/database.php';

// Vérifier que la connexion à la base de données est établie
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode([
        'message' => 'Erreur de connexion à la base de données',
        'error' => 'Impossible de se connecter à la base de données. Vérifiez la configuration dans config/database.php'
    ]);
    exit;
}

// Les en-têtes CORS sont déjà définis dans index.php/router.php
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

if ($method === 'GET') {
    if (strpos($path, '/student/') !== false) {
        $apogeeNumber = basename($path);
        $stmt = $pdo->prepare("
            SELECT r.*, d.document_type 
            FROM reclamation r
            LEFT JOIN demande d ON r.demande_id = d.id
            WHERE r.apogee_number = ?
            ORDER BY r.date_reclamation DESC
        ");
        $stmt->execute([$apogeeNumber]);
        echo json_encode($stmt->fetchAll());
    } else {
        $stmt = $pdo->query("
            SELECT r.*, d.document_type, e.nom, e.prenom
            FROM reclamation r
            LEFT JOIN demande d ON r.demande_id = d.id
            LEFT JOIN etudiant e ON r.apogee_number = e.apogee_number
            ORDER BY r.date_reclamation DESC
        ");
        echo json_encode($stmt->fetchAll());
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $demandeId = $input['demande_id'] ?? '';
    $numeroAttestation = $input['numero_attestation'] ?? '';
    $motif = $input['motif'] ?? null; // Optionnel
    $description = $input['description'] ?? '';
    $numeroDemande = null;
    
    // Si numero_attestation est fourni, récupérer demande_id, apogee_number et email
    $apogeeNumber = null;
    $email = null;
    if (!empty($numeroAttestation) && empty($demandeId)) {
        $stmt = $pdo->prepare("
            SELECT d.id, d.apogee_number, d.numero_demande, d.numero_attestation, e.email 
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            WHERE d.numero_attestation = ?
        ");
        $stmt->execute([$numeroAttestation]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$demande) {
            http_response_code(404);
            echo json_encode(['error' => 'Numéro d\'attestation non trouvé']);
            exit;
        }
        
        $demandeId = $demande['id'];
        $apogeeNumber = $demande['apogee_number'];
        $email = $demande['email'];
        $numeroDemande = $demande['numero_demande'] ?? null;
        $numeroAttestation = $demande['numero_attestation'] ?? $numeroAttestation;
    } elseif (!empty($demandeId)) {
        // Si demande_id est fourni directement, récupérer apogee_number et email
        $stmt = $pdo->prepare("
            SELECT d.apogee_number, d.numero_demande, d.numero_attestation, e.email 
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            WHERE d.id = ?
        ");
        $stmt->execute([$demandeId]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($demande) {
            $apogeeNumber = $demande['apogee_number'];
            $email = $demande['email'];
            $numeroDemande = $demande['numero_demande'] ?? null;
            // Si l'étudiant ne fournit pas le numéro d'attestation, on le récupère depuis la demande
            if (empty($numeroAttestation)) {
                $numeroAttestation = $demande['numero_attestation'] ?? '';
            }
        }
    }
    
    if (empty($demandeId) || empty($description) || empty($numeroAttestation)) {
        http_response_code(400);
        echo json_encode(['error' => 'Numéro d\'attestation et description sont requis']);
        exit;
    }
    
    if (empty($apogeeNumber)) {
        http_response_code(400);
        echo json_encode(['error' => 'Impossible de récupérer le numéro Apogée de la demande']);
        exit;
    }
    
    if (empty($email)) {
        http_response_code(400);
        echo json_encode(['error' => 'Impossible de récupérer l\'email de l\'étudiant']);
        exit;
    }
    
    try {
        // Insérer la réclamation (compat: stocker aussi numero_demande_reclamee si disponible)
        $stmt = $pdo->prepare("INSERT INTO reclamation (demande_id, apogee_number, email, numero_attestation_reclamee, numero_demande_reclamee, motif, description, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'En attente')");
        error_log("Insert reclamation - demande_id: $demandeId, apogee_number: $apogeeNumber, email: $email, numero_attestation_reclamee: $numeroAttestation");
        $stmt->execute([$demandeId, $apogeeNumber, $email, $numeroAttestation, $numeroDemande ?? null, $motif, $description]);
        
        $reclamationId = $pdo->lastInsertId();
        
        // Email auto à la création de réclamation
        try {
            require_once __DIR__ . '/../config/email_template.php';
            $stmtEmail = $pdo->prepare("SELECT nom, prenom, email FROM etudiant WHERE apogee_number = ?");
            $stmtEmail->execute([$apogeeNumber]);
            $student = $stmtEmail->fetch(PDO::FETCH_ASSOC);
            if ($student && !empty($student['email']) && function_exists('sendEmailConfirmationReclamation')) {
                sendEmailConfirmationReclamation(
                    $student['email'],
                    $student['nom'],
                    $student['prenom'],
                    $reclamationId,
                    $numeroAttestation,
                    $numeroDemande ?? null
                );
            }
        } catch (Exception $e) {
            error_log("Erreur email confirmation réclamation: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'id' => $reclamationId,
            'message' => 'Réclamation créée avec succès'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Create reclamation error: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    }
    
} elseif ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Le path peut être /{id}/respond, /{id}/close, /{id}/reject, /{id}/reopen, /{id}/resend-document
    $path_clean = trim($path, '/');
    $path_parts = $path_clean ? explode('/', $path_clean) : [];
    $id = $path_parts[0] ?? null;
    $action = $path_parts[1] ?? '';
    
    if (empty($id) || !is_numeric($id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid reclamation ID']);
        exit;
    }
    
    if ($action === 'respond') {
        $reponse = $input['reponse'] ?? '';
        
        if (empty($reponse)) {
            http_response_code(400);
            echo json_encode(['error' => 'Response is required']);
            exit;
        }
        
        try {
            // Vérifier que la réclamation existe et récupérer les infos
            $checkStmt = $pdo->prepare("
                SELECT r.*, d.numero_demande, d.document_type, r.apogee_number, r.email, e.nom, e.prenom
                FROM reclamation r
                LEFT JOIN demande d ON r.demande_id = d.id
                LEFT JOIN etudiant e ON r.apogee_number = e.apogee_number
                WHERE r.id = ?
            ");
            $checkStmt->execute([$id]);
            $reclamation = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reclamation) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }
            
            // Mettre à jour le statut et la réponse (compat admin: reponse_admin + reponse)
            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'Résolue', reponse_admin = ?, reponse = ?, date_reponse = NOW() WHERE id = ?");
            $stmt->execute([$reponse, $reponse, $id]);
            
            // Envoyer un email à l'étudiant
            if (!empty($reclamation['email'])) {
                require_once __DIR__ . '/../config/email_template.php';
                
                try {
                    $email_sent = sendEmailReclamation(
                        $reclamation['email'],
                        $reclamation['nom'],
                        $reclamation['prenom'],
                        $reclamation['numero_demande'],
                        $reclamation['document_type'],
                        $reponse
                    );
                    
                    if ($email_sent) {
                        error_log("Email de réclamation envoyé avec succès à: {$reclamation['email']}");
                    }
                } catch (Exception $e) {
                    error_log("Erreur lors de l'envoi de l'email de réclamation: " . $e->getMessage());
                    // Ne pas faire échouer la requête si l'email échoue
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Reclamation responded successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Respond reclamation error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } elseif ($action === 'reject') {
        $motif = trim($input['motif'] ?? '');
        if (empty($motif)) {
            http_response_code(400);
            echo json_encode(['error' => 'Motif is required']);
            exit;
        }

        try {
            $checkStmt = $pdo->prepare("
                SELECT r.*, d.numero_demande, d.document_type, r.email, e.nom, e.prenom
                FROM reclamation r
                LEFT JOIN demande d ON r.demande_id = d.id
                LEFT JOIN etudiant e ON r.apogee_number = e.apogee_number
                WHERE r.id = ?
            ");
            $checkStmt->execute([$id]);
            $reclamation = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$reclamation) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'Rejetée', reponse_admin = ?, reponse = ?, date_reponse = NOW() WHERE id = ?");
            $stmt->execute([$motif, $motif, $id]);

            // Email étudiant
            if (!empty($reclamation['email'])) {
                require_once __DIR__ . '/../config/email_template.php';
                try {
                    sendEmailReclamation(
                        $reclamation['email'],
                        $reclamation['nom'],
                        $reclamation['prenom'],
                        $reclamation['numero_demande'],
                        $reclamation['document_type'],
                        "Votre réclamation a été rejetée. Motif :\n" . $motif
                    );
                } catch (Exception $e) {
                    error_log("Erreur email rejet réclamation: " . $e->getMessage());
                }
            }

            echo json_encode(['success' => true, 'message' => 'Reclamation rejected successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Reject reclamation error: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'reopen') {
        try {
            $checkStmt = $pdo->prepare("SELECT id FROM reclamation WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'En cours' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Reclamation reopened successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Reopen reclamation error: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'resend-document') {
        try {
            $stmt = $pdo->prepare("
                SELECT r.id as reclamation_id, r.demande_id, r.email as reclamation_email, r.numero_attestation_reclamee,
                       d.id as demande_id, d.numero_demande, d.numero_attestation, d.document_type, d.document_path, d.additional_info, d.apogee_number,
                       e.nom, e.prenom, e.email as student_email, e.cin
                FROM reclamation r
                LEFT JOIN demande d ON r.demande_id = d.id
                LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || empty($row['demande_id'])) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation or linked demande not found']);
                exit;
            }

            $toEmail = $row['student_email'] ?: $row['reclamation_email'];
            if (empty($toEmail)) {
                http_response_code(400);
                echo json_encode(['error' => 'Student email not found']);
                exit;
            }

            // Numéro attestation
            $numeroAttestation = $row['numero_attestation'] ?: ('ATT-' . date('Y') . '-' . str_pad($row['demande_id'], 6, '0', STR_PAD_LEFT));
            if (empty($row['numero_attestation'])) {
                $up = $pdo->prepare("UPDATE demande SET numero_attestation = ? WHERE id = ?");
                $up->execute([$numeroAttestation, $row['demande_id']]);
            }

            // PDF path
            $relative = $row['document_path'] ?: null;
            $abs = $relative ? (__DIR__ . '/../' . $relative) : null;

            // Regénérer si nécessaire
            if (!$abs || !file_exists($abs)) {
                if (!defined('PDF_LIB_ONLY')) define('PDF_LIB_ONLY', true);
                require_once __DIR__ . '/generate_document.php';
                require_once __DIR__ . '/validate_document.php';

                $demande = [
                    'id' => $row['demande_id'],
                    'numero_demande' => $row['numero_demande'],
                    'numero_attestation' => $numeroAttestation,
                    'apogee_number' => $row['apogee_number'],
                    'email' => $toEmail,
                    'cin' => $row['cin'] ?? '',
                    'document_type' => $row['document_type'],
                    'additional_info' => $row['additional_info'],
                    'nom' => $row['nom'],
                    'prenom' => $row['prenom'],
                ];

                $additionalInfo = !empty($row['additional_info']) ? json_decode($row['additional_info'], true) : [];
                $validation = validateDocumentRequest($pdo, $row['document_type'], $row['apogee_number'], $additionalInfo);
                if (!$validation['valid']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Validation failed', 'message' => $validation['error']]);
                    exit;
                }

                $relative = generatePDF($demande, $validation['data'] ?? []);
                $abs = __DIR__ . '/../' . $relative;

                $up = $pdo->prepare("UPDATE demande SET document_path = ? WHERE id = ?");
                $up->execute([$relative, $row['demande_id']]);
            }

            if (!$abs || !file_exists($abs)) {
                http_response_code(500);
                echo json_encode(['error' => 'PDF generation failed']);
                exit;
            }

            require_once __DIR__ . '/../config/email_template.php';
            $sent = sendEmailWithDocument(
                $toEmail,
                $row['nom'],
                $row['prenom'],
                $numeroAttestation,
                $row['numero_demande'],
                $row['document_type'],
                $abs
            );

            if (!$sent) {
                http_response_code(500);
                echo json_encode(['error' => 'Email send failed']);
                exit;
            }

            echo json_encode(['success' => true, 'message' => 'Document resent successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Resend document error: ' . $e->getMessage());
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            http_response_code(500);
            error_log('Resend document error: ' . $e->getMessage());
            echo json_encode(['error' => 'Server error', 'message' => $e->getMessage()]);
        }
    } elseif ($action === 'close') {
        try {
            // Vérifier que la réclamation existe
            $checkStmt = $pdo->prepare("SELECT id FROM reclamation WHERE id = ?");
            $checkStmt->execute([$id]);
            if (!$checkStmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Reclamation not found']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE reclamation SET status = 'Fermée' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Reclamation closed successfully']);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Close reclamation error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action. Use /respond, /reject, /reopen, /resend-document or /close']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>

