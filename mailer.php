<?php
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inclure PHPMailer
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/SMTP.php';

/**
 * Fonction pour envoyer des emails avec PHPMailer
 */
function envoyerEmail($destinataire, $nom_destinataire, $sujet, $contenu_html, $contenu_texte = '') {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'talkspace2025@gmail.com';
        $mail->Password   = 'rcfvtsjswvzwvtqh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Options SSL pour le développement
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Expéditeur
        $mail->setFrom('talkspace2025@gmail.com', 'TalkSpace');
        $mail->addAddress($destinataire, $nom_destinataire);
        
        // Contenu
        $mail->isHTML(true);
        $mail->Subject = $sujet;
        $mail->Body    = $contenu_html;
        $mail->AltBody = $contenu_texte ?: strip_tags($contenu_html);

        return $mail->send();
        
    } catch (Exception $e) {
        // En cas d'erreur, créer un fichier de log
        error_log("Erreur email: " . $e->getMessage());
        return false;
    }
}

/**
 * Fonction pour envoyer l'email de confirmation d'inscription
 */
function envoyerEmailConfirmation($email, $nom, $prenom, $token) {
    $lien_confirmation = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/confirmer_email.php?token=" . $token;
    
    $sujet = "Confirmation de votre compte TalkSpace";
    
    $contenu_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
            .container { background: white; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
            .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { padding: 20px; }
            .button { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Bienvenue sur TalkSpace !</h1>
            </div>
            <div class='content'>
                <h3>Bonjour $prenom $nom,</h3>
                <p>Merci de vous être inscrit sur TalkSpace. Pour activer votre compte, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :</p>
                
                <div style='text-align: center;'>
                    <a href='$lien_confirmation' class='button'>Confirmer mon email</a>
                </div>
                
                <p>Si le bouton ne fonctionne pas, vous pouvez copier-coller ce lien dans votre navigateur :</p>
                <p><a href='$lien_confirmation'>$lien_confirmation</a></p>
                
                <p><strong>Important :</strong> Ce lien expirera dans 24 heures.</p>
                
                <p>Si vous n'avez pas créé de compte sur TalkSpace, veuillez ignorer cet email.</p>
            </div>
            <div class='footer'>
                <p>© 2024 TalkSpace. Tous droits réservés.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $contenu_texte = "
    Confirmation de votre compte TalkSpace
    
    Bonjour $prenom $nom,
    
    Merci de vous être inscrit sur TalkSpace. 
    Pour activer votre compte, cliquez sur le lien suivant :
    
    $lien_confirmation
    
    Ce lien expirera dans 24 heures.
    
    Si vous n'avez pas créé de compte, ignorez cet email.
    
    © 2024 TalkSpace
    ";
    
    return envoyerEmail($email, "$prenom $nom", $sujet, $contenu_html, $contenu_texte);
}

/**
 * Fonction pour envoyer un email de contact
 */
function envoyerEmailContact($nom, $email_expediteur, $sujet, $message) {
    $sujet_email = "Nouveau message de contact: $sujet";
    
    $contenu_html = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #007bff; color: white; padding: 15px; border-radius: 5px 5px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
            .message-box { background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007bff; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Nouveau message de contact</h2>
            </div>
            <div class='content'>
                <p><strong>De:</strong> $nom &lt;$email_expediteur&gt;</p>
                <p><strong>Sujet:</strong> $sujet</p>
                <p><strong>Message:</strong></p>
                <div class='message-box'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <br>
                <p><small>Envoyé depuis le formulaire de contact TalkSpace</small></p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $contenu_texte = "
    Nouveau message de contact
    
    De: $nom ($email_expediteur)
    Sujet: $sujet
    
    Message:
    $message
    
    Envoyé depuis TalkSpace
    ";
    
    // Envoyer à l'admin
    return envoyerEmail('talkspace2025@gmail.com', 'Admin TalkSpace', $sujet_email, $contenu_html, $contenu_texte);
}

/**
 * Fonction de simulation pour le développement local
 */
function simulerEmail($email, $nom, $sujet, $contenu) {
    $dossier_emails = __DIR__ . '/emails';
    if (!is_dir($dossier_emails)) {
        mkdir($dossier_emails, 0755, true);
    }
    
    $fichier_email = $dossier_emails . '/simulation_' . $email . '_' . time() . '.html';
    $contenu_fichier = "
    <h2>SIMULATION EMAIL - $sujet</h2>
    <p><strong>À:</strong> $nom &lt;$email&gt;</p>
    <hr>
    $contenu
    <hr>
    <p><em>Cet email a été simulé en mode développement</em></p>
    ";
    
    return file_put_contents($fichier_email, $contenu_fichier) !== false;
}
?>