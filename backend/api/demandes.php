<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/validate_document.php';

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
// S'assurer que les headers sont définis AVANT toute sortie
if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['PATH_INFO'] ?? '';

// Si PATH_INFO est vide, essayer de le construire depuis REQUEST_URI
if (empty($path) && isset($_SERVER['REQUEST_URI'])) {
    $request_uri = $_SERVER['REQUEST_URI'];
    $parsed = parse_url($request_uri, PHP_URL_PATH);
    // Enlever /api/demandes du début
    if (strpos($parsed, '/api/demandes') === 0) {
        $path = substr($parsed, strlen('/api/demandes'));
    } elseif (strpos($parsed, '/demandes') === 0) {
        $path = substr($parsed, strlen('/demandes'));
    }
    if (empty($path)) {
        $path = '/';
    }
}

// Debug pour PATCH
if ($method === 'PATCH') {
    error_log("PATCH request received - PATH_INFO: " . ($path ?? 'empty') . ", REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'empty'));
}

if ($method === 'GET') {
    if (strpos($path, '/student/') !== false) {
        // Get demandes by student
        $apogeeNumber = basename($path);
        $stmt = $pdo->prepare("SELECT * FROM demande WHERE apogee_number = ? ORDER BY date_demande DESC");
        $stmt->execute([$apogeeNumber]);
        echo json_encode($stmt->fetchAll());
    } elseif (strpos($path, '/suivi/') !== false) {
        // Get demande by numero_demande (pour le suivi)
        $numero_demande = basename($path);
        $stmt = $pdo->prepare("
            SELECT d.*, e.nom, e.prenom, e.email 
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            WHERE d.numero_demande = ?
        ");
        $stmt->execute([$numero_demande]);
        $demande = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$demande) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Demande non trouvée',
                'message' => 'Aucune demande trouvée avec ce numéro. Vérifiez le numéro de demande.'
            ]);
        } else {
            echo json_encode($demande);
        }
    } else {
        // Get all demandes
        $stmt = $pdo->query("
            SELECT d.*, e.nom, e.prenom 
            FROM demande d
            LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
            ORDER BY d.date_demande DESC
        ");
        echo json_encode($stmt->fetchAll());
    }
    
} elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $apogeeNumber = trim($input['apogee_number'] ?? '');
    $email = trim(strtolower($input['email'] ?? ''));
    $cin = trim($input['cin'] ?? '');
    $documentType = $input['document_type'] ?? '';
    $additionalInfo = $input['additional_info'] ?? [];
    
    // Validation des champs requis
    if (empty($apogeeNumber) || empty($email) || empty($cin) || empty($documentType)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Champs manquants',
            'message' => 'Tous les champs sont requis : email, numéro Apogée, CIN et type de document'
        ]);
        exit;
    }
    
    try {
        // Vérifier que les trois données (email, apogee_number, CIN) correspondent à un même étudiant
        $stmt = $pdo->prepare("SELECT * FROM etudiant WHERE apogee_number = ? AND LOWER(email) = ? AND cin = ?");
        $stmt->execute([$apogeeNumber, $email, $cin]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$student || empty($student)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Données incorrectes',
                'message' => 'L\'email, le numéro Apogée et le CIN ne correspondent pas à un étudiant enregistré. Veuillez vérifier vos informations.'
            ]);
            exit;
        }
        
        // VALIDATION PRÉLIMINAIRE : Vérifier que l'étudiant peut faire cette demande
        // (Validation basique, validation complète lors de l'acceptation)
        try {
            $validation = validateDocumentRequest($pdo, $documentType, $apogeeNumber, $additionalInfo);
            
            if (!$validation || !isset($validation['valid'])) {
                http_response_code(500);
                echo json_encode([
                    'error' => 'Erreur de validation',
                    'message' => 'La fonction de validation n\'a pas retourné de résultat valide. Vérifiez les logs du serveur.'
                ]);
                exit;
            }
            
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Demande invalide',
                    'message' => $validation['error'] ?? 'Votre demande ne peut pas être créée car les conditions ne sont pas remplies.',
                    'details' => 'Votre demande ne peut pas être créée car les conditions ne sont pas remplies. Veuillez vérifier vos informations.'
                ]);
                exit;
            }
        } catch (Exception $e) {
            http_response_code(500);
            error_log('Validation error: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            echo json_encode([
                'error' => 'Erreur lors de la validation',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            exit;
        }
        
        // Stocker les informations supplémentaires en JSON
        $additionalInfoJson = !empty($additionalInfo) ? json_encode($additionalInfo, JSON_UNESCAPED_UNICODE) : null;
        
        // Générer un numéro de demande unique
        $maxAttempts = 10;
        $numero_demande = null;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $count = $pdo->query("SELECT COUNT(*) FROM demande WHERE numero_demande LIKE 'DEM-" . date('Y') . "-%'")->fetchColumn();
            $numero_demande = 'DEM-' . date('Y') . '-' . str_pad($count + 1 + $i, 6, '0', STR_PAD_LEFT);
            
            // Vérifier l'unicité
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM demande WHERE numero_demande = ?");
            $checkStmt->execute([$numero_demande]);
            if ($checkStmt->fetchColumn() == 0) {
                break; // Numéro unique trouvé
            }
        }
        
        // Insérer la demande avec les informations supplémentaires si le champ existe
        try {
            $stmt = $pdo->prepare("INSERT INTO demande (apogee_number, document_type, status, additional_info, numero_demande) VALUES (?, ?, 'En attente', ?, ?)");
            $stmt->execute([$apogeeNumber, $documentType, $additionalInfoJson, $numero_demande]);
        } catch (PDOException $e) {
            // Si le champ additional_info n'existe pas, insérer sans ce champ
            if (strpos($e->getMessage(), 'additional_info') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO demande (apogee_number, document_type, status, numero_demande) VALUES (?, ?, 'En attente', ?)");
                    $stmt->execute([$apogeeNumber, $documentType, $numero_demande]);
                } catch (PDOException $e2) {
                    // Si numero_demande n'existe pas non plus
                    if (strpos($e2->getMessage(), 'numero_demande') !== false || strpos($e2->getMessage(), 'Unknown column') !== false) {
                        $stmt = $pdo->prepare("INSERT INTO demande (apogee_number, document_type, status) VALUES (?, ?, 'En attente')");
                        $stmt->execute([$apogeeNumber, $documentType]);
                        // Générer le numéro après insertion si la colonne n'existe pas
                        $numero_demande = 'DEM-' . date('Y') . '-' . str_pad($pdo->lastInsertId(), 6, '0', STR_PAD_LEFT);
                    } else {
                        throw $e2;
                    }
                }
            } else {
                throw $e;
            }
        }
        
        $demandeId = $pdo->lastInsertId();
        
        // ENVOYER UN EMAIL DE CONFIRMATION AVEC LE NUMÉRO DE DEMANDE
        try {
            require_once __DIR__ . '/../config/email_template.php';
            
            // Récupérer les infos de l'étudiant pour l'email
            $stmtEmail = $pdo->prepare("SELECT nom, prenom, email FROM etudiant WHERE apogee_number = ?");
            $stmtEmail->execute([$apogeeNumber]);
            $student = $stmtEmail->fetch(PDO::FETCH_ASSOC);
            
            if ($student && !empty($student['email'])) {
                $emailSent = sendEmailConfirmationDemande(
                    $student['email'],
                    $student['nom'],
                    $student['prenom'],
                    $numero_demande,
                    $documentType
                );
                
                if (!$emailSent) {
                    error_log("Échec de l'envoi de l'email de confirmation pour la demande ID: $demandeId");
                }
            }
        } catch (Exception $e) {
            // Ne pas bloquer la création de la demande si l'email échoue
            error_log("Erreur lors de l'envoi de l'email de confirmation: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'id' => $demandeId,
            'numero_demande' => $numero_demande,
            'message' => 'Demande créée avec succès. Un email de confirmation a été envoyé avec votre numéro de demande.'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        error_log('Create demande error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        echo json_encode([
            'error' => 'Erreur de base de données',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        error_log('Create demande error (general): ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        echo json_encode([
            'error' => 'Erreur lors de la création de la demande',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    }
    
} elseif ($method === 'PATCH') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Le path peut être /{id}/status ou juste /{id}
    // Exemple: /1/status ou /1
    $path_clean = trim($path, '/');
    $path_parts = $path_clean ? explode('/', $path_clean) : [];
    $id = $path_parts[0] ?? null;
    $action = $path_parts[1] ?? '';
    
    // Debug: logger le path pour comprendre
    error_log("PATCH request - Path: $path, Clean: $path_clean, Parts: " . json_encode($path_parts) . ", ID: $id, Action: $action, Input: " . json_encode($input));
    
    if ($action === 'status' && $id) {
        $status = $input['status'] ?? '';
        $justification = $input['justification'] ?? null;
        
        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['error' => 'Status is required']);
            exit;
        }
        
        if (empty($id) || !is_numeric($id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid demande ID']);
            exit;
        }
        
        try {
            // Vérifier que la demande existe et récupérer les infos de l'étudiant
            $checkStmt = $pdo->prepare("
                SELECT d.*, e.email, e.nom, e.prenom 
                FROM demande d
                LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
                WHERE d.id = ?
            ");
            $checkStmt->execute([$id]);
            $demande = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$demande || empty($demande)) {
                http_response_code(404);
                echo json_encode(['error' => 'Demande not found']);
                exit;
            }
            
            // VALIDATION CRITIQUE : Si on accepte la demande, vérifier qu'elle est valide
            if ($status === 'Acceptée') {
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
                        'details' => 'Cette demande ne peut pas être acceptée car les conditions ne sont pas remplies. ' . $validation['error']
                    ]);
                    exit;
                }
            }
            
            // Mettre à jour le statut D'ABORD (avant l'envoi d'email pour éviter les problèmes)
            error_log("PATCH - Mise à jour statut pour demande ID: $id, status: $status");
            if (!empty($justification) && $justification !== null) {
                $stmt = $pdo->prepare("UPDATE demande SET status = ?, justification_refus = ? WHERE id = ?");
                $stmt->execute([$status, $justification, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE demande SET status = ?, justification_refus = NULL WHERE id = ?");
                $stmt->execute([$status, $id]);
            }
            error_log("PATCH - Statut mis à jour avec succès");
            
            // ENVOYER LA RÉPONSE JSON IMMÉDIATEMENT (avant la génération PDF/email)
            error_log("PATCH - Envoi réponse JSON IMMÉDIATE pour demande ID: $id, status: $status");
            
            // S'assurer que les headers sont corrects AVANT d'envoyer la réponse
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(200);
            }
            
            // Nettoyer tout output buffer avant d'envoyer la réponse
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            $response = [
                'success' => true, 
                'message' => 'Demande updated successfully',
                'status' => $status,
                'id' => $id
            ];
            
            echo json_encode($response);
            error_log("PATCH - Réponse JSON envoyée: " . json_encode($response));
            
            // Forcer l'envoi de la réponse immédiatement
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                // Si fastcgi_finish_request n'est pas disponible, flush la sortie
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }
            
            // MAINTENANT, faire la génération PDF et l'envoi d'email EN ARRIÈRE-PLAN
            // (après avoir envoyé la réponse au client)
            error_log("PATCH - Début traitement en arrière-plan pour demande ID: $id");

            // 1) Phase ACCEPTATION : générer le PDF et enregistrer le chemin, SANS envoyer d'email
            if ($status === 'Acceptée') {
                try {
                    error_log("PATCH - Acceptée: Génération PDF (sans envoi d'email) pour demande ID: $id");

                    // Générer le numéro d'attestation
                    $numero_attestation = 'ATT-' . date('Y') . '-' . str_pad($id, 6, '0', STR_PAD_LEFT);

                    // Générer le PDF
                    define('PDF_LIB_ONLY', true);
                    require_once __DIR__ . '/generate_document.php';
                    require_once __DIR__ . '/validate_document.php';

                    // Préparer les données pour la génération
                    $additionalInfo = !empty($demande['additional_info']) ? json_decode($demande['additional_info'], true) : [];
                    $validation = validateDocumentRequest($pdo, $demande['document_type'], $demande['apogee_number'], $additionalInfo);

                    if ($validation['valid']) {
                        $demandeForPDF = [
                            'id' => $id,
                            'numero_demande' => $demande['numero_demande'],
                            'numero_attestation' => $numero_attestation,
                            'apogee_number' => $demande['apogee_number'],
                            'email' => $demande['email'],
                            'cin' => $demande['cin'] ?? '',
                            'document_type' => $demande['document_type'],
                            'additional_info' => $demande['additional_info'],
                            'nom' => $demande['nom'],
                            'prenom' => $demande['prenom'],
                        ];

                        $relative_path = generatePDF($demandeForPDF, $validation['data'] ?? []);
                        $pdf_path = __DIR__ . '/../' . $relative_path;

                        if ($relative_path && file_exists($pdf_path)) {
                            error_log("PATCH - PDF généré (sans email): $pdf_path");
                            // Mettre à jour la demande avec le numéro d'attestation et le chemin du PDF
                            $updateStmt = $pdo->prepare("UPDATE demande SET numero_attestation = ?, document_path = ?, status = 'Acceptée' WHERE id = ?");
                            $updateStmt->execute([$numero_attestation, $relative_path, $id]);
                        } else {
                            error_log("PATCH - Erreur: Impossible de générer le PDF pour la demande ID: $id");
                            // Mettre à jour quand même le statut et le numéro d'attestation
                            $updateStmt = $pdo->prepare("UPDATE demande SET numero_attestation = ?, status = 'Acceptée' WHERE id = ?");
                            $updateStmt->execute([$numero_attestation, $id]);
                        }
                    } else {
                        error_log("PATCH - Validation échouée lors de la génération PDF post-acceptation: " . ($validation['error'] ?? 'Erreur inconnue'));
                    }
                } catch (Exception $e) {
                    error_log("PATCH - Exception génération PDF (phase Acceptée, sans email): " . $e->getMessage());
                }
            }

            // 2) Phase REFUS : envoyer un email d'information de refus (logique inchangée)
            $email_sent = false;
            if (!empty($demande['email'])) {
                require_once __DIR__ . '/../config/email_template.php';
                
                try {
                    if ($status === 'Refusée') {
                        error_log("PATCH - Refusée: Envoi email de refus pour demande ID: $id");
                        // Envoyer l'email de refus
                        if (function_exists('sendEmailRefusee')) {
                            $email_sent = sendEmailRefusee(
                                $demande['email'],
                                $demande['nom'],
                                $demande['prenom'],
                                $demande['numero_demande'],
                                $demande['document_type'],
                                $justification ?? 'Aucune justification spécifiée'
                            );
                            
                            if ($email_sent) {
                                error_log("PATCH - Email de refus envoyé pour demande ID: $id");
                                try {
                                    $emailStmt = $pdo->prepare("UPDATE demande SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
                                    $emailStmt->execute([$id]);
                                } catch (PDOException $e) {
                                    // Si les colonnes n'existent pas encore, ignorer l'erreur
                                    if (strpos($e->getMessage(), 'email_sent') === false) {
                                        error_log("PATCH - Erreur update email_sent: " . $e->getMessage());
                                    }
                                }
                            }
                        } else {
                            error_log("PATCH - Fonction sendEmailRefusee non trouvée");
                        }
                    }
                } catch (Exception $e) {
                    error_log("PATCH - Erreur lors de l'envoi de l'email: " . $e->getMessage());
                    error_log("PATCH - Stack trace: " . $e->getTraceAsString());
                    // Ne pas faire échouer la requête si l'email échoue
                }
            }
            
            error_log("PATCH - Traitement en arrière-plan terminé pour demande ID: $id");
            
            // Arrêter le script (la réponse a déjà été envoyée)
            exit(0);
        } catch (PDOException $e) {
            http_response_code(500);
            error_log('Update demande error: ' . $e->getMessage());
            echo json_encode([
                'error' => 'Database error',
                'message' => $e->getMessage()
            ]);
        }
    } else {
        http_response_code(400);
        error_log("PATCH - Invalid request path: $path, ID: $id, Action: $action");
        echo json_encode([
            'error' => 'Invalid request path',
            'path' => $path,
            'id' => $id,
            'action' => $action,
            'path_parts' => $path_parts
        ]);
    }
} else {
    http_response_code(405);
    error_log("Method not allowed: $method for path: $path");
    echo json_encode([
        'error' => 'Method not allowed',
        'method' => $method,
        'path' => $path,
        'allowed_methods' => ['GET', 'POST', 'PATCH']
    ]);
}
?>

