<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

// Inclure PHPMailer
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/Exception.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = trim($_POST['nom'] ?? ($_SESSION['nom'] ?? ''));
    $email   = trim($_POST['email'] ?? ($_SESSION['email'] ?? ''));
    $sujet   = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$nom || !$email || !$sujet || !$message) {
        $error = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "L'adresse email n'est pas valide.";
    } else {
        // Enregistrer dans la base de données
        $ins = $bdd->prepare("INSERT INTO messages_contact (id_user, nom, email, sujet, message) VALUES (?, ?, ?, ?, ?)");
        $ins->execute([$_SESSION['id'], $nom, $email, $sujet, $message]);

        // Envoyer l'email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'talkspace2025@gmail.com'; // À configurer
            $mail->Password   = 'rcfvtsjswvzwvtqh'; // À configurer
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom('talkspace2025@gmail.com', 'TalkSpace ');
            $mail->addAddress('talkspace2025@gmail.com'); // Email de l'admin
            $mail->addReplyTo($email, $nom);

            $mail->isHTML(true);
            $mail->Subject = "Nouveau message de contact: $sujet";
            $mail->Body    = "
                <h3>Nouveau message de contact</h3>
                <p><strong>De:</strong> $nom &lt;$email&gt;</p>
                <p><strong>Sujet:</strong> $sujet</p>
                <p><strong>Message:</strong></p>
                <div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
                <br>
                <p><small>Envoyé depuis TalkSpace</small></p>
            ";

            $mail->AltBody = "De: $nom ($email)\nSujet: $sujet\n\nMessage:\n$message";

            if ($mail->send()) {
                $success = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
            } else {
                $error = "Erreur lors de l'envoi du message. Veuillez réessayer.";
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'envoi : " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="dash1.php">
            <i class="fas fa-users me-2"></i>TalkSpace
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dash1.php"><i class="fas fa-home me-1"></i>Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link active" href="contact.php"><i class="fas fa-envelope me-1"></i>Contact</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card slide-up">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Contactez-nous</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-4">
                        Vous avez une question, une suggestion ou besoin d'aide ? 
                        Remplissez ce formulaire et nous vous répondrons dans les plus brefs délais.
                    </p>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?= $success ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="nom" class="form-label">Votre nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="email" class="form-label">Votre email</label>
                            <input type="email" name="email" id="email" class="form-control" 
                                   value="<?= htmlspecialchars($_SESSION['email']) ?>" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="sujet" class="form-label">Sujet</label>
                            <input type="text" name="sujet" id="sujet" class="form-control" 
                                   placeholder="Objet de votre message" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="message" class="form-label">Votre message</label>
                            <textarea name="message" id="message" rows="6" class="form-control" 
                                      placeholder="Décrivez votre demande en détail..." required></textarea>
                            <div class="form-text text-end">
                                <span id="charCount">0</span>/2000 caractères
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Informations de contact -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h5 class="mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Autres moyens de contact</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope fa-2x text-primary me-3"></i>
                                <div>
                                    <h6 class="mb-1">Email</h6>
                                    <p class="text-muted mb-0">talkspace2025@gmail.com</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-clock fa-2x text-success me-3"></i>
                                <div>
                                    <h6 class="mb-1">Temps de réponse</h6>
                                    <p class="text-muted mb-0">Sous 24-48 heures</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <h6 class="mb-2">Sujets fréquents :</h6>
                        <ul class="list-unstyled small text-muted">
                            <li><i class="fas fa-check text-success me-2"></i>Problèmes de compte</li>
                            <li><i class="fas fa-check text-success me-2"></i>Suggestions d'amélioration</li>
                            <li><i class="fas fa-check text-success me-2"></i>Signaler un contenu inapproprié</li>
                            <li><i class="fas fa-check text-success me-2"></i>Questions techniques</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
<script>
// Compteur de caractères pour le message
document.getElementById('message').addEventListener('input', function() {
    const charCount = this.value.length;
    document.getElementById('charCount').textContent = charCount;
    
    if (charCount > 1990) {
        document.getElementById('charCount').classList.add('text-danger');
    } else {
        document.getElementById('charCount').classList.remove('text-danger');
    }
});
</script>
</body>
</html>