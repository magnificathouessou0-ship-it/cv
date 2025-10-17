<?php
session_start(); // AJOUTEZ CETTE LIGNE
require_once('config.php');
require_once('functions.php'); // AJOUTEZ AUSSI CETTE LIGNE
$success_msg = $_SESSION['success_msg'] ?? '';
unset($_SESSION['success_msg']); // Effacer le message après l'avoir récupéré

$erreur = "";

if (isset($_POST["som"])) {
    $email = sanitize($_POST['email']);
    $mdp = $_POST['mdp'];

    if (empty($email) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs du formulaire";
    } else {
        $qlq = $bdd->prepare("SELECT * FROM users WHERE email = ?");
        $qlq->execute([$email]);
        $user = $qlq->fetch();

        if (!$user) {
            $erreur = "Mauvais mail ou mot de passe";
        } else {
            // AJOUTEZ LA VÉRIFICATION DE LA CONFIRMATION D'EMAIL
            if (isset($user['email_confirme']) && $user['email_confirme'] == 0) {
                $erreur = "Veuillez confirmer votre email avant de vous connecter. Vérifiez votre boîte de réception.";
            } else {
                // Vérification du mot de passe (support SHA1 et bcrypt)
                $motdepasseBase = $user['mdp'];
                $valide = false;

                // Si ancien hash SHA1
                if (strlen($motdepasseBase) === 40 && sha1($mdp) === $motdepasseBase) {
                    $valide = true;
                    // Migrer vers bcrypt
                    $nouveauHash = password_hash($mdp, PASSWORD_DEFAULT);
                    $update = $bdd->prepare("UPDATE users SET mdp = ? WHERE id = ?");
                    $update->execute([$nouveauHash, $user['id']]);
                }
                // Si nouveau hash bcrypt
                elseif (password_verify($mdp, $motdepasseBase)) {
                    $valide = true;
                }

                if ($valide) {
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    $_SESSION['tel'] = $user['tel'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['sexe'] = $user['sexe'];
                    $_SESSION['date_naissance'] = $user['date_naissance'];
                    $_SESSION['adresse'] = $user['adresse'];

                    redirect("dash1.php");
                } else {
                    $erreur = "Mauvais mail ou mot de passe";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Connexion | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .password-toggle {
            cursor: pointer;
            z-index: 10;
        }
        .input-group {
            position: relative;
        }
        .input-group-text {
            background: transparent !important;
            border: none !important;
        }
    </style>
</head>

<body>
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="auth-container fade-in" style="max-width: 450px; width: 100%;">
        <div class="logo text-center mb-4">
            <i class="fas fa-users fa-3x mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            <h4 class="text-dark">Connexion</h4>
            <p class="text-muted">Retrouvez vos amis sur TalkSpace</p>
        </div>

        <form action="" method="POST">
            <div class="mb-4">
                <label for="email" class="form-label">Adresse e-mail</label>
                <div class="input-group">
                    <input type="email" class="form-control" id="email" name="email" required 
                           placeholder="exemple@mail.com" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" />
                    <span class="input-group-text">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                </div>
            </div>

            <div class="mb-4">
                <label for="mdp" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="mdp" name="mdp" required 
                           placeholder="Votre mot de passe" />
                    <button type="button" class="btn password-toggle input-group-text">
                        <i class="fas fa-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="som" class="btn btn-primary w-100 py-2">
                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
            </button>

            <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                <a href="forgot_password.php" class="text-primary text-decoration-none">
                    <i class="fas fa-key me-1"></i>Mot de passe oublié ?
                </a>
                <a href="index.php" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-2"></i>Créer un compte
                </a>
            </div>
        </form>

        <?php if (!empty($erreur)) : ?>
            <div class="alert alert-danger mt-4 animate__animated animate__shakeX">
                <i class="fas fa-exclamation-circle me-2"></i><?= $erreur ?>
            </div>
        <?php endif; ?>

        <!-- Message d'information sur la confirmation d'email -->
        <div class="alert alert-info mt-3">
            <i class="fas fa-info-circle me-2"></i>
            <small>Si vous venez de vous inscrire, vérifiez votre email pour confirmer votre compte.</small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toggle visibilité mot de passe
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.closest('.input-group').querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});

// Animation pour les erreurs
<?php if (!empty($erreur)): ?>
document.addEventListener('DOMContentLoaded', function() {
    const alert = document.querySelector('.alert-danger');
    if (alert) {
        alert.classList.add('animate__animated', 'animate__shakeX');
    }
});
<?php endif; ?>
</script>
</body>
</html>