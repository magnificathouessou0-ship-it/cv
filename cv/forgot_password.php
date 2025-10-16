<?php
session_start();
require_once 'config.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!$email) {
        $message = "Veuillez entrer votre email.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "L'adresse email n'est pas valide.";
    } else {
        // Vérifier si l'utilisateur existe
        $stmt = $bdd->prepare("SELECT id, nom, prenom FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Génère token
            $token = bin2hex(random_bytes(32));
            $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

            // Enregistre dans la base
            $upd = $bdd->prepare("UPDATE users SET reset_token=?, reset_expiration=? WHERE id=?");
            $upd->execute([$token, $expiration, $user['id']]);

            // Lien de reset
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=$token";

            // Envoi email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'votre-email@gmail.com'; // À configurer
                $mail->Password   = 'votre-mot-de-passe'; // À configurer
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->SMTPOptions = [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    ]
                ];

                $mail->setFrom('votre-email@gmail.com', 'TalkSpace Support');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = "Réinitialisation de votre mot de passe TalkSpace";
                $mail->Body    = "
                    <h3>Réinitialisation de mot de passe</h3>
                    <p>Bonjour " . htmlspecialchars($user['prenom']) . ",</p>
                    <p>Vous avez demandé la réinitialisation de votre mot de passe TalkSpace.</p>
                    <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
                    <p><a href='$resetLink' style='background: #2E8B57; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block;'>Réinitialiser mon mot de passe</a></p>
                    <p>Ce lien expirera dans 1 heure.</p>
                    <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                    <br>
                    <p>Cordialement,<br>L'équipe TalkSpace</p>
                ";

                $mail->AltBody = "Bonjour " . $user['prenom'] . ",\n\nPour réinitialiser votre mot de passe, cliquez sur ce lien : $resetLink\n\nCe lien expirera dans 1 heure.";

                if ($mail->send()) {
                    $message = "Un lien de réinitialisation a été envoyé à votre email.";
                } else {
                    $message = "Erreur lors de l'envoi de l'email. Veuillez réessayer.";
                }
            } catch (Exception $e) {
                $message = "Erreur lors de l'envoi : " . $mail->ErrorInfo;
            }
        } else {
            $message = "Aucun compte trouvé avec cet email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mot de passe oublié | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, var(--bg-light) 0%, #E8F5E8 100%); min-height:100vh; display:flex; justify-content:center; align-items:center;">
    <div class="auth-container fade-in" style="max-width:450px; width:100%;">
        <div class="text-center mb-4">
            <i class="fas fa-key fa-3x mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            <h4 class="text-dark">Mot de passe oublié</h4>
            <p class="text-muted">Entrez votre email pour réinitialiser votre mot de passe</p>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Adresse email</label>
                <div class="input-group">
                    <input type="email" name="email" id="email" class="form-control" 
                           placeholder="votre@email.com" required>
                    <span class="input-group-text bg-transparent border-0 position-absolute end-0 top-50 translate-middle-y">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="fas fa-paper-plane me-2"></i>Envoyer le lien de réinitialisation
            </button>
            
            <div class="text-center">
                <a href="login.php" class="text-primary text-decoration-none">
                    <i class="fas fa-arrow-left me-1"></i>Retour à la connexion
                </a>
            </div>
        </form>

        <div class="mt-4 p-3 bg-light rounded">
            <h6 class="mb-2"><i class="fas fa-lightbulb me-2 text-warning"></i>Conseil de sécurité</h6>
            <p class="small text-muted mb-0">
                Le lien de réinitialisation sera valable pendant 1 heure. 
                Assurez-vous de vérifier votre dossier de spam si vous ne recevez pas l'email.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>