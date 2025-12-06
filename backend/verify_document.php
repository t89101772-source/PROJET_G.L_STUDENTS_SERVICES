<?php
/**
 * Page de vérification d'un document via QR Code
 * Accessible via: http://localhost:8000/verify_document.php?id=XXX
 */

require_once __DIR__ . '/config/database.php';

// Définir les en-têtes CORS
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=utf-8');

// Récupérer l'ID de la demande depuis l'URL
$demandeId = $_GET['id'] ?? null;

if (empty($demandeId) || !is_numeric($demandeId)) {
    http_response_code(400);
    die('ID de document invalide');
}

try {
    // Récupérer les informations complètes de la demande
    $stmt = $pdo->prepare("
        SELECT 
            d.*,
            e.nom,
            e.prenom,
            e.email,
            e.cin,
            e.apogee_number
        FROM demande d
        LEFT JOIN etudiant e ON d.apogee_number = e.apogee_number
        WHERE d.id = ? AND d.status = 'Acceptée'
    ");
    $stmt->execute([$demandeId]);
    $demande = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$demande || empty($demande)) {
        http_response_code(404);
        die('Document non trouvé ou non valide');
    }
    
    // Parser les informations supplémentaires si elles existent
    $additionalInfo = !empty($demande['additional_info']) 
        ? json_decode($demande['additional_info'], true) 
        : [];
    
} catch (PDOException $e) {
    http_response_code(500);
    die('Erreur de base de données');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de Document - Université Abdelmalek Essaidi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #667eea;
            padding-bottom: 20px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }
        
        .header h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .status-badge {
            display: inline-block;
            background: #10b981;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 25px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-section h2 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .info-section h2::before {
            content: "📄";
            margin-right: 10px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            flex: 1;
        }
        
        .info-value {
            color: #333;
            flex: 2;
            text-align: right;
        }
        
        .document-details {
            background: #f9fafb;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .verification-code {
            background: #f3f4f6;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-top: 20px;
        }
        
        .verification-code strong {
            color: #667eea;
            font-size: 18px;
            letter-spacing: 2px;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 12px;
        }
        
        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .warning strong {
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">UAE</div>
            <h1>UNIVERSITÉ ABDELMALEK ESSAIDI</h1>
            <p>Vérification de Document Officiel</p>
        </div>
        
        <div style="text-align: center;">
            <span class="status-badge">✓ DOCUMENT AUTHENTIQUE</span>
        </div>
        
        <div class="info-section">
            <h2>Informations du Document</h2>
            <div class="document-details">
                <div class="info-row">
                    <span class="info-label">Type de document :</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($demande['document_type']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Numéro de référence :</span>
                    <span class="info-value"><strong>#<?php echo htmlspecialchars($demande['id']); ?></strong></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date d'émission :</span>
                    <span class="info-value"><?php echo date('d/m/Y', strtotime($demande['date_demande'])); ?></span>
                </div>
                <?php if ($demande['document_path']): ?>
                <div class="info-row">
                    <span class="info-label">Statut :</span>
                    <span class="info-value" style="color: #10b981;">✓ Généré et valide</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="info-section">
            <h2>Informations de l'Étudiant</h2>
            <div class="info-row">
                <span class="info-label">Nom complet :</span>
                <span class="info-value"><strong><?php echo htmlspecialchars($demande['nom'] . ' ' . $demande['prenom']); ?></strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Numéro Apogée :</span>
                <span class="info-value"><?php echo htmlspecialchars($demande['apogee_number']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">CIN :</span>
                <span class="info-value"><?php echo htmlspecialchars($demande['cin']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email :</span>
                <span class="info-value"><?php echo htmlspecialchars($demande['email']); ?></span>
            </div>
        </div>
        
        <?php if (!empty($additionalInfo)): ?>
        <div class="info-section">
            <h2>Détails Supplémentaires</h2>
            <div class="document-details">
                <?php foreach ($additionalInfo as $key => $value): ?>
                <div class="info-row">
                    <span class="info-label"><?php echo ucfirst(str_replace('_', ' ', $key)); ?> :</span>
                    <span class="info-value"><?php echo htmlspecialchars($value); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="verification-code">
            <div style="margin-bottom: 10px; color: #666; font-size: 14px;">Code de vérification</div>
            <strong>#<?php echo htmlspecialchars($demande['id']); ?></strong>
        </div>
        
        <div class="warning">
            <strong>⚠️ Important :</strong> Ce document a été vérifié et authentifié par l'Université Abdelmalek Essaidi. 
            Toute falsification est passible de poursuites judiciaires.
        </div>
        
        <div class="footer">
            <p>Université Abdelmalek Essaidi - Tétouan, Maroc</p>
            <p>Vérifié le <?php echo date('d/m/Y à H:i'); ?></p>
        </div>
    </div>
</body>
</html>

