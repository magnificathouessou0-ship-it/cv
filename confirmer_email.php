<?php
session_start();
if (!isset($_SESSION['email_inscription'])) {
    header('Location: index.php');
    exit();
}

$email = $_SESSION['email_inscription'];
$lien_confirmation = $_SESSION['lien_confirmation'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email envoyé | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-body text-center p-5">
                    <i class="fas fa-envelope fa-5x text-primary mb-4"></i>
                    <h2 class="text-success">Email de confirmation envoyé !</h2>
                    <p class="text-muted mb-4">
                        Un email de confirmation a été envoyé à :<br>
                        <strong class="text-dark"><?= htmlspecialchars($email) ?></strong>
                    </p>
                    
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Vérifiez votre boîte de réception</strong> et cliquez sur le lien de confirmation pour activer votre compte.
                    </div>

                    <?php if ($lien_confirmation): ?>
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i>Si vous ne recevez pas l'email :</h6>
                        <p class="mb-2">Vous pouvez copier ce lien directement :</p>
                        <div class="bg-light p-3 rounded">
                            <code class="text-break"><?= htmlspecialchars($lien_confirmation) ?></code>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="alert alert-light mt-4">
                        <i class="fas fa-lightbulb me-2"></i>
                        <strong>Astuce :</strong> Vérifiez vos spams ou courriers indésirables si vous ne voyez pas l'email.
                    </div>

                    <div class="mt-4">
                        <a href="login.php" class="btn btn-primary me-3">
                            <i class="fas fa-sign-in-alt me-2"></i>Retour à la connexion
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-home me-2"></i>Page d'accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>