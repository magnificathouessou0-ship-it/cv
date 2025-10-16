<?php
session_start();
require_once 'config.php';

$message = "";
$show_form = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Vérifier le token
    $stmt = $bdd->prepare("SELECT id, reset_expiration FROM users WHERE reset_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Vérifier l'expiration
        if (strtotime($user['reset_expiration']) > time()) {
            $show_form = true;
            $user_id = $user['id'];
            
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nouveau = $_POST['nouveau'] ?? '';
                $confirmation = $_POST['confirmation'] ?? '';

                if (empty($nouveau) || empty($confirmation)) {
                    $message = "<div class='alert alert-danger'>Tous les champs sont obligatoires.</div>";
                } elseif ($nouveau !== $confirmation) {
                    $message = "<div class='alert alert-danger'>Les mots de passe ne correspondent pas.</div>";
                } elseif (strlen($nouveau) < 8) {
                    $message = "<div class='alert alert-danger'>Le mot de passe doit contenir au moins 8 caractères.</div>";
                } else {
                    // Hacher le nouveau mot de passe
                    $nouveau_hash = password_hash($nouveau, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe et effacer le token
                    $update = $bdd->prepare("UPDATE users SET mdp = ?, reset_token = NULL, reset_expiration = NULL WHERE id = ?");
                    if ($update->execute([$nouveau_hash, $user_id])) {
                        $message = "<div class='alert alert-success'>Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.</div>";
                        $show_form = false;
                    } else {
                        $message = "<div class='alert alert-danger'>Erreur lors de la réinitialisation. Veuillez réessayer.</div>";
                    }
                }
            }
        } else {
            $message = "<div class='alert alert-danger'>Le lien de réinitialisation a expiré. Veuillez faire une nouvelle demande.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Lien de réinitialisation invalide.</div>";
    }
} else {
    $message = "<div class='alert alert-danger'>Aucun token fourni.</div>";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation du mot de passe | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: linear-gradient(135deg, var(--bg-light) 0%, #E8F5E8 100%); min-height:100vh; display:flex; justify-content:center; align-items:center;">
    <div class="auth-container fade-in" style="max-width:450px; width:100%;">
        <div class="text-center mb-4">
            <i class="fas fa-lock fa-3x mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            <h4 class="text-dark">Nouveau mot de passe</h4>
            <p class="text-muted">Créez votre nouveau mot de passe</p>
        </div>
        
        <?= $message ?>

        <?php if ($show_form): ?>
            <form method="POST">
                <div class="mb-4">
                    <label for="nouveau" class="form-label">Nouveau mot de passe</label>
                    <div class="input-group">
                        <input type="password" name="nouveau" id="nouveau" class="form-control" 
                               placeholder="Minimum 8 caractères" required minlength="8">
                        <button type="button" class="btn password-toggle bg-transparent border">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirmation" class="form-label">Confirmer le mot de passe</label>
                    <div class="input-group">
                        <input type="password" name="confirmation" id="confirmation" class="form-control" 
                               placeholder="Retapez votre mot de passe" required minlength="8">
                        <button type="button" class="btn password-toggle bg-transparent border">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="fas fa-save me-2"></i>Réinitialiser le mot de passe
                </button>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-3">
            <a href="login.php" class="text-primary text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Retour à la connexion
            </a>
        </div>

        <?php if ($show_form): ?>
            <div class="mt-4 p-3 bg-light rounded">
                <h6 class="mb-2"><i class="fas fa-shield-alt me-2 text-primary"></i>Sécurité du mot de passe</h6>
                <ul class="list-unstyled small text-muted mb-0">
                    <li><i class="fas fa-check text-success me-1"></i>Minimum 8 caractères</li>
                    <li><i class="fas fa-check text-success me-1"></i>Mélangez lettres et chiffres</li>
                    <li><i class="fas fa-check text-success me-1"></i>Évitez les mots courants</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>