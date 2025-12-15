<?php
/**
 * Template d'email professionnel pour l'envoi de documents
 */

// Charger PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function getEmailTemplate($nom, $prenom, $numero_attestation, $numero_demande, $document_type) {
    $nom_complet = $prenom . ' ' . $nom;
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre document est prêt</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
                                🎓 UnivDocs
                            </h1>
                            <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">
                                Système de gestion des attestations
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1f2937; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Bonjour ' . htmlspecialchars($prenom) . ',
                            </h2>
                            
                            <p style="color: #4b5563; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">
                                Nous avons le plaisir de vous informer que votre <strong>' . htmlspecialchars($document_type) . '</strong> est prêt et a été généré avec succès.
                            </p>
                            
                            <div style="background-color: #f9fafb; border-left: 4px solid #6366f1; padding: 20px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    📄 Informations de votre document :
                                </p>
                                <table cellpadding="5" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px; width: 40%;">Numéro de demande :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($numero_demande) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Numéro d\'attestation :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($numero_attestation) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Type de document :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($document_type) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Nom complet :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($nom_complet) . '</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <p style="color: #4b5563; margin: 20px 0; font-size: 16px; line-height: 1.6;">
                                Votre document est joint à cet email au format PDF. Vous pouvez le télécharger et l\'imprimer selon vos besoins.
                            </p>
                            
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;">
                                    <strong>💡 Important :</strong> Conservez ce numéro d\'attestation (' . htmlspecialchars($numero_attestation) . ') pour toute vérification future. Ce document est officiel et peut être utilisé pour vos démarches administratives.
                                </p>
                            </div>
                            
                            <p style="color: #4b5563; margin: 20px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Si vous avez des questions ou besoin d\'assistance, n\'hésitez pas à nous contacter.
                            </p>
                            
                            <p style="color: #4b5563; margin: 30px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Cordialement,<br>
                                <strong style="color: #1f2937;">L\'équipe UnivDocs</strong><br>
                                <span style="color: #6b7280; font-size: 14px;">École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences</span>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px; line-height: 1.6;">
                                Cet email a été envoyé automatiquement. Merci de ne pas y répondre.<br>
                                © ' . date('Y') . ' UnivDocs - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Fonction pour envoyer l'email avec le document en pièce jointe
 */
function sendEmailWithDocument($to_email, $nom, $prenom, $numero_attestation, $numero_demande, $document_type, $pdf_path) {
    // Charger PHPMailer
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        error_log("ERREUR: vendor/autoload.php non trouvé à: $autoload_path");
        return false;
    }
    
    require_once $autoload_path;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration SMTP Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        
        // Configuration Gmail - À MODIFIER AVEC VOS IDENTIFIANTS
        // Pour obtenir un mot de passe d'application Gmail :
        // 1. Allez sur https://myaccount.google.com/
        // 2. Sécurité → Validation en 2 étapes (doit être activée)
        // 3. Mots de passe des applications → Créer un nouveau mot de passe
        // 4. Copiez le mot de passe généré (16 caractères)
        
        $mail->Username = 'votre email'; // REMPLACER par votre email Gmail
        $mail->Password = 'votre mot de passe d'application Gmail'; // REMPLACER par votre mot de passe d'application Gmail (16 caractères)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0; // Mettre à 2 pour debug, 0 pour production
        // Pour activer le debug temporairement, changez 0 en 2 ci-dessus
        // Pour activer le debug, décommenter les lignes suivantes :
        // $mail->SMTPDebug = 2;
        // $mail->Debugoutput = function($str, $level) {
        //     error_log("PHPMailer Debug (level $level): $str");
        // };
        
        // Expéditeur
        $mail->setFrom('votre email', 'UnivDocs');
        $mail->addReplyTo('votre email', 'Support UnivDocs');
        
        // Destinataire
        $mail->addAddress($to_email, $prenom . ' ' . $nom);
        
        // Pièce jointe PDF
        if (file_exists($pdf_path)) {
            $mail->addAttachment($pdf_path, $document_type . '_' . $numero_attestation . '.pdf');
        }
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'Votre ' . $document_type . ' est prêt - ' . $numero_attestation;
        $mail->Body = getEmailTemplate($nom, $prenom, $numero_attestation, $numero_demande, $document_type);
        
        // Version texte alternative
        $mail->AltBody = "Bonjour $prenom,\n\nVotre $document_type est prêt.\n\nNuméro de demande: $numero_demande\nNuméro d'attestation: $numero_attestation\n\nVotre document est joint à cet email.\n\nCordialement,\nL'équipe UnivDocs";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        $error_msg = "Erreur email PHPMailer: {$mail->ErrorInfo} | Exception: " . $e->getMessage();
        error_log($error_msg);
        // Afficher aussi dans la console si en mode debug
        if (php_sapi_name() === 'cli') {
            echo "❌ ERREUR: $error_msg\n";
        }
        return false;
    }
}

/**
 * Template d'email pour demande refusée
 */
function getEmailTemplateRefusee($nom, $prenom, $numero_demande, $document_type, $justification) {
    $nom_complet = $prenom . ' ' . $nom;
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande refusée</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
                                🎓 UnivDocs
                            </h1>
                            <p style="color: #fee2e2; margin: 10px 0 0 0; font-size: 14px;">
                                Notification de refus
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1f2937; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Bonjour ' . htmlspecialchars($prenom) . ',
                            </h2>
                            
                            <p style="color: #4b5563; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">
                                Nous vous informons que votre demande de <strong>' . htmlspecialchars($document_type) . '</strong> a été examinée et <strong style="color: #dc2626;">refusée</strong>.
                            </p>
                            
                            <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    📄 Informations de votre demande :
                                </p>
                                <table cellpadding="5" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px; width: 40%;">Numéro de demande :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($numero_demande) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Type de document :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($document_type) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Statut :</td>
                                        <td style="color: #dc2626; font-size: 14px; font-weight: 600;">Refusée</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="background-color: #fff7ed; border: 1px solid #fed7aa; padding: 15px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    📝 Justification :
                                </p>
                                <p style="margin: 0; color: #92400e; font-size: 14px; line-height: 1.6;">
                                    ' . nl2br(htmlspecialchars($justification ?: 'Aucune justification spécifiée')) . '
                                </p>
                            </div>
                            
                            <p style="color: #4b5563; margin: 20px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Si vous avez des questions concernant cette décision ou souhaitez faire une réclamation, n\'hésitez pas à nous contacter.
                            </p>
                            
                            <p style="color: #4b5563; margin: 30px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Cordialement,<br>
                                <strong style="color: #1f2937;">L\'équipe UnivDocs</strong><br>
                                <span style="color: #6b7280; font-size: 14px;">École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences</span>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px; line-height: 1.6;">
                                Cet email a été envoyé automatiquement. Merci de ne pas y répondre.<br>
                                © ' . date('Y') . ' UnivDocs - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Template email: confirmation de création de réclamation
 */
function getEmailTemplateConfirmationReclamation($nom, $prenom, $reclamation_id, $numero_attestation, $numero_demande = null) {
    $ref_demande = $numero_demande ? htmlspecialchars($numero_demande) : 'N/A';
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Réclamation reçue</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f5f5f5;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:20px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 2px 10px rgba(0,0,0,0.08);">
          <tr>
            <td style="background: linear-gradient(135deg,#0ea5e9 0%, #6366f1 100%); padding:26px; text-align:center;">
              <h1 style="color:#fff; margin:0; font-size:26px;">🎓 UnivDocs</h1>
              <p style="color:#e0e7ff; margin:8px 0 0 0; font-size:13px;">Confirmation de réclamation</p>
            </td>
          </tr>
          <tr>
            <td style="padding:28px 26px;">
              <h2 style="margin:0 0 14px 0; color:#111827; font-size:20px;">Bonjour ' . htmlspecialchars($prenom) . ',</h2>
              <p style="margin:0 0 14px 0; color:#374151; font-size:15px; line-height:1.6;">
                Nous avons bien reçu votre réclamation. Elle sera traitée par l\'administration dès que possible.
              </p>

              <div style="background:#f9fafb; border-left:4px solid #6366f1; padding:16px; border-radius:6px; margin:18px 0;">
                <p style="margin:0 0 8px 0; color:#111827; font-weight:700; font-size:13px;">🧾 Référence</p>
                <table cellpadding="4" cellspacing="0" style="width:100%; font-size:13px; color:#111827;">
                  <tr>
                    <td style="color:#6b7280; width:46%;">ID Réclamation :</td>
                    <td style="font-weight:700;">#' . htmlspecialchars($reclamation_id) . '</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;">Numéro d\'attestation :</td>
                    <td style="font-weight:700;">' . htmlspecialchars($numero_attestation) . '</td>
                  </tr>
                  <tr>
                    <td style="color:#6b7280;">Numéro de demande :</td>
                    <td style="font-weight:700;">' . $ref_demande . '</td>
                  </tr>
                </table>
              </div>

              <p style="margin:0; color:#4b5563; font-size:14px; line-height:1.6;">
                Vous recevrez un email lorsqu\'une réponse sera apportée à votre réclamation.
              </p>

              <p style="margin:20px 0 0 0; color:#4b5563; font-size:14px; line-height:1.6;">
                Cordialement,<br><strong>L\'équipe UnivDocs</strong>
              </p>
            </td>
          </tr>
          <tr>
            <td style="background:#f9fafb; padding:16px; text-align:center; border-top:1px solid #e5e7eb;">
              <p style="margin:0; color:#6b7280; font-size:12px; line-height:1.6;">
                Cet email a été envoyé automatiquement. © ' . date('Y') . ' UnivDocs
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

/**
 * Envoyer email: confirmation de réclamation (création)
 */
function sendEmailConfirmationReclamation($to_email, $nom, $prenom, $reclamation_id, $numero_attestation, $numero_demande = null) {
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        error_log("ERREUR: vendor/autoload.php non trouvé à: $autoload_path");
        return false;
    }

    require_once $autoload_path;

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votre email';
        $mail->Password = 'votre mot de passe de l'app';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;

        $mail->setFrom('votre email', 'UnivDocs');
        $mail->addReplyTo('votre email', 'Support UnivDocs');
        $mail->addAddress($to_email, $prenom . ' ' . $nom);

        $mail->isHTML(true);
        $mail->Subject = "Réclamation reçue - #$reclamation_id";
        $mail->Body = getEmailTemplateConfirmationReclamation($nom, $prenom, $reclamation_id, $numero_attestation, $numero_demande);
        $mail->AltBody = "Bonjour $prenom,\n\nVotre réclamation a bien été reçue.\nID: #$reclamation_id\nAttestation: $numero_attestation\nDemande: " . ($numero_demande ?: 'N/A') . "\n\nCordialement,\nUnivDocs";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email confirmation réclamation: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Template d'email pour réclamation traitée
 */
function getEmailTemplateReclamation($nom, $prenom, $numero_demande, $document_type, $reponse_admin) {
    $nom_complet = $prenom . ' ' . $nom;
    
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse à votre réclamation</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
                                🎓 UnivDocs
                            </h1>
                            <p style="color: #d1fae5; margin: 10px 0 0 0; font-size: 14px;">
                                Réponse à votre réclamation
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1f2937; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Bonjour ' . htmlspecialchars($prenom) . ',
                            </h2>
                            
                            <p style="color: #4b5563; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">
                                Nous avons examiné votre réclamation concernant votre demande de <strong>' . htmlspecialchars($document_type) . '</strong> et nous vous apportons la réponse suivante.
                            </p>
                            
                            <div style="background-color: #f9fafb; border-left: 4px solid #10b981; padding: 20px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    📄 Informations de votre demande :
                                </p>
                                <table cellpadding="5" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px; width: 40%;">Numéro de demande :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($numero_demande) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Type de document :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($document_type) . '</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="background-color: #ecfdf5; border: 1px solid #a7f3d0; padding: 15px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    💬 Réponse de l\'administration :
                                </p>
                                <p style="margin: 0; color: #065f46; font-size: 14px; line-height: 1.6;">
                                    ' . nl2br(htmlspecialchars($reponse_admin)) . '
                                </p>
                            </div>
                            
                            <p style="color: #4b5563; margin: 20px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Si vous avez d\'autres questions ou besoin d\'assistance supplémentaire, n\'hésitez pas à nous contacter.
                            </p>
                            
                            <p style="color: #4b5563; margin: 30px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Cordialement,<br>
                                <strong style="color: #1f2937;">L\'équipe UnivDocs</strong><br>
                                <span style="color: #6b7280; font-size: 14px;">École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences</span>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px; line-height: 1.6;">
                                Cet email a été envoyé automatiquement. Merci de ne pas y répondre.<br>
                                © ' . date('Y') . ' UnivDocs - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

/**
 * Fonction pour envoyer un email de refus (sans PDF)
 */
function sendEmailRefusee($to_email, $nom, $prenom, $numero_demande, $document_type, $justification) {
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        error_log("ERREUR: vendor/autoload.php non trouvé à: $autoload_path");
        return false;
    }
    
    require_once $autoload_path;
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votre email';
        $mail->Password = 'votre mot de passe de l'app';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        
        $mail->setFrom('votre email', 'UnivDocs');
        $mail->addReplyTo('votre email', 'Support UnivDocs');
        $mail->addAddress($to_email, $prenom . ' ' . $nom);
        
        $mail->isHTML(true);
        $mail->Subject = 'Votre demande a été refusée - ' . $numero_demande;
        $mail->Body = getEmailTemplateRefusee($nom, $prenom, $numero_demande, $document_type, $justification);
        $mail->AltBody = "Bonjour $prenom,\n\nVotre demande de $document_type (Numéro: $numero_demande) a été refusée.\n\nJustification: " . ($justification ?: 'Aucune justification spécifiée') . "\n\nCordialement,\nL'équipe UnivDocs";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email refus: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour envoyer un email de réponse à réclamation
 */
function sendEmailReclamation($to_email, $nom, $prenom, $numero_demande, $document_type, $reponse_admin) {
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        error_log("ERREUR: vendor/autoload.php non trouvé à: $autoload_path");
        return false;
    }
    
    require_once $autoload_path;
    
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votre email';
        $mail->Password = 'votre mot de passe de l'app';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        
        $mail->setFrom('votre email', 'UnivDocs');
        $mail->addReplyTo('votre email', 'Support UnivDocs');
        $mail->addAddress($to_email, $prenom . ' ' . $nom);
        
        $mail->isHTML(true);
        $mail->Subject = 'Réponse à votre réclamation - ' . $numero_demande;
        $mail->Body = getEmailTemplateReclamation($nom, $prenom, $numero_demande, $document_type, $reponse_admin);
        $mail->AltBody = "Bonjour $prenom,\n\nNous avons examiné votre réclamation concernant votre demande $document_type (Numéro: $numero_demande).\n\nRéponse: $reponse_admin\n\nCordialement,\nL'équipe UnivDocs";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email réclamation: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email de confirmation avec le numéro de demande
 * Appelé automatiquement lors de la création d'une demande
 */
function sendEmailConfirmationDemande($to_email, $nom, $prenom, $numero_demande, $document_type) {
    // Charger PHPMailer
    $autoload_path = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload_path)) {
        error_log("ERREUR: vendor/autoload.php non trouvé à: $autoload_path");
        return false;
    }
    
    require_once $autoload_path;
    
    $mail = new PHPMailer(true);
    
    try {
        // Configuration SMTP Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'votre email';
        $mail->Password = 'votre mot de passe de l'app';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';
        $mail->SMTPDebug = 0;
        
        // Expéditeur
        $mail->setFrom('votre email', 'UnivDocs');
        $mail->addReplyTo('votre email', 'Support UnivDocs');
        
        // Destinataire
        $mail->addAddress($to_email, $prenom . ' ' . $nom);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'Confirmation de votre demande - ' . $numero_demande;
        $mail->Body = getEmailTemplateConfirmation($nom, $prenom, $numero_demande, $document_type);
        $mail->AltBody = "Bonjour $prenom,\n\nVotre demande de $document_type a été créée avec succès.\n\nNuméro de demande: $numero_demande\n\nVous recevrez un email avec votre document une fois la demande traitée.\n\nCordialement,\nL'équipe UnivDocs";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email confirmation: {$mail->ErrorInfo} | Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Template d'email de confirmation de demande
 */
function getEmailTemplateConfirmation($nom, $prenom, $numero_demande, $document_type) {
    return '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de votre demande</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;">
                                🎓 UnivDocs
                            </h1>
                            <p style="color: #e0e7ff; margin: 10px 0 0 0; font-size: 14px;">
                                Confirmation de votre demande
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #1f2937; margin: 0 0 20px 0; font-size: 24px; font-weight: 600;">
                                Bonjour ' . htmlspecialchars($prenom) . ',
                            </h2>
                            
                            <p style="color: #4b5563; margin: 0 0 20px 0; font-size: 16px; line-height: 1.6;">
                                Nous avons bien reçu votre demande de <strong>' . htmlspecialchars($document_type) . '</strong>.
                            </p>
                            
                            <div style="background-color: #f9fafb; border-left: 4px solid #6366f1; padding: 20px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1f2937; font-weight: 600; font-size: 14px;">
                                    📝 Informations de votre demande :
                                </p>
                                <table cellpadding="5" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px; width: 40%;">Numéro de demande :</td>
                                        <td style="color: #1f2937; font-size: 16px; font-weight: 600;">' . htmlspecialchars($numero_demande) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Type de document :</td>
                                        <td style="color: #1f2937; font-size: 14px; font-weight: 600;">' . htmlspecialchars($document_type) . '</td>
                                    </tr>
                                    <tr>
                                        <td style="color: #6b7280; font-size: 14px;">Statut :</td>
                                        <td style="color: #f59e0b; font-size: 14px; font-weight: 600;">⏳ En attente de traitement</td>
                                    </tr>
                                </table>
                            </div>
                            
                            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; padding: 15px; margin: 25px 0; border-radius: 5px;">
                                <p style="margin: 0 0 10px 0; color: #1e40af; font-weight: 600; font-size: 14px;">
                                    ℹ️ Prochaines étapes :
                                </p>
                                <p style="margin: 0; color: #1e40af; font-size: 14px; line-height: 1.6;">
                                    Votre demande est en cours de traitement par l\'administration. Vous recevrez un email avec votre document une fois la demande validée.
                                </p>
                            </div>
                            
                            <p style="color: #4b5563; margin: 30px 0 0 0; font-size: 16px; line-height: 1.6;">
                                <strong>Important :</strong> Conservez bien votre numéro de demande <strong>' . htmlspecialchars($numero_demande) . '</strong> pour suivre l\'avancement de votre demande.
                            </p>
                            
                            <p style="color: #4b5563; margin: 30px 0 0 0; font-size: 16px; line-height: 1.6;">
                                Cordialement,<br>
                                <strong style="color: #1f2937;">L\'équipe UnivDocs</strong><br>
                                <span style="color: #6b7280; font-size: 14px;">École Supérieure d’Ingénierie NovaTech - Université Cité des Sciences</span>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; color: #6b7280; font-size: 12px; line-height: 1.6;">
                                Cet email a été envoyé automatiquement. Merci de ne pas y répondre.<br>
                                © ' . date('Y') . ' UnivDocs - Tous droits réservés
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}
?>

