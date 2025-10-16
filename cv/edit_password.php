<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien = $_POST['ancien'] ?? '';
    $nouveau = $_POST['nouveau'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if (empty($ancien) || empty($nouveau) || empty($confirmation)) {
        $message = "<div class='alert alert-danger'>Tous les champs sont obligatoires.</div>";
    } elseif ($nouveau !== $confirmation) {
        $message = "<div class='alert alert-danger'>Le nouveau mot de passe et la confirmation ne correspondent pas.</div>";
    } elseif (strlen($nouveau) < 8) {
        $message = "<div class='alert alert-danger'>Le nouveau mot de passe doit contenir au moins 8 caractères.</div>";
    } else {
        $id_user = $_SESSION['id'];

        // Vérifier mot de passe actuel
        $stmt = $bdd->prepare("SELECT mdp FROM users WHERE id = ?");
        $stmt->execute([$id_user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $message = "<div class='alert alert-danger'>Utilisateur introuvable.</div>";
        } else {
            $hash_enregistre = $row['mdp'];

            // Vérification du mot de passe actuel
            $valide = false;
            
            // Si ancien hash SHA1
            if (strlen($hash_enregistre) === 40 && sha1($ancien) === $hash_enregistre) {
                $valide = true;
            }
            // Si nouveau hash bcrypt
            elseif (password_verify($ancien, $hash_enregistre)) {
                $valide = true;
            }

            if (!$valide) {
                $message = "<div class='alert alert-danger'>Ancien mot de passe incorrect.</div>";
            } else {
                // Hacher en bcrypt
                $nouveau_hash = password_hash($nouveau, PASSWORD_DEFAULT);

                $update = $bdd->prepare("UPDATE users SET mdp = ? WHERE id = ?");
                if ($update->execute([$nouveau_hash, $id_user])) {
                    $message = "<div class='alert alert-success'>Mot de passe changé avec succès.</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour du mot de passe.</div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le mot de passe | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .is-valid {
            border-color: #198754 !important;
            box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important;
        }
        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }
        .valid-feedback {
            display: none;
            color: #198754;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }
        .is-valid ~ .valid-feedback {
            display: block;
        }
        .is-invalid ~ .invalid-feedback {
            display: block;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-medium { background-color: #ffc107; width: 50%; }
        .strength-strong { background-color: #28a745; width: 75%; }
        .strength-very-strong { background-color: #20c997; width: 100%; }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
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
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="card slide-up">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Modifier le mot de passe</h4>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    
                    <form method="post" id="passwordForm">
                        <div class="mb-4">
                            <label for="ancien" class="form-label">Ancien mot de passe</label>
                            <div class="input-group">
                                <input type="password" name="ancien" id="ancien" class="form-control" required>
                                <button type="button" class="btn password-toggle bg-transparent border">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="valid-feedback">✓ Ancien mot de passe valide</div>
                            <div class="invalid-feedback">Veuillez saisir votre ancien mot de passe</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="nouveau" class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" name="nouveau" id="nouveau" class="form-control" required minlength="8">
                                <button type="button" class="btn password-toggle bg-transparent border">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength"></div>
                            <div class="valid-feedback">✓ Mot de passe sécurisé</div>
                            <div class="invalid-feedback">Le mot de passe doit contenir au moins 8 caractères</div>
                            <div class="form-text">Le mot de passe doit contenir au moins 8 caractères</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <div class="input-group">
                                <input type="password" name="confirmation" id="confirmation" class="form-control" required minlength="8">
                                <button type="button" class="btn password-toggle bg-transparent border">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="valid-feedback">✓ Les mots de passe correspondent</div>
                            <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="profil.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour au profil
                            </a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save me-1"></i>Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Conseils de sécurité -->
            <div class="card mt-4 fade-in">
                <div class="card-body">
                    <h6><i class="fas fa-shield-alt me-2 text-primary"></i>Conseils de sécurité</h6>
                    <ul class="list-unstyled mb-0">
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Utilisez au moins 8 caractères</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Mélangez lettres, chiffres et symboles</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Évitez les mots de passe courants</small></li>
                        <li><small class="text-muted"><i class="fas fa-check text-success me-1"></i>Ne réutilisez pas d'anciens mots de passe</small></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Fonction pour réinitialiser la validation d'un champ
    function resetFieldValidation(field) {
        field.removeClass('is-valid is-invalid');
    }

    // Fonction de validation générique
    function validateField(field, validationFn) {
        const value = field.val().trim();
        const isValid = validationFn(value);
        
        resetFieldValidation(field);
        
        if (value === '') {
            // Champ vide - pas de validation
            return false;
        } else if (isValid) {
            field.addClass('is-valid');
            return true;
        } else {
            field.addClass('is-invalid');
            return false;
        }
    }

    // Validation de l'ancien mot de passe
    $('#ancien').on('input', function() {
        validateField($(this), function(value) {
            return value.length >= 1; // Au moins 1 caractère
        });
    });

    // Validation du nouveau mot de passe avec indicateur de force
    $('#nouveau').on('input', function() {
        const password = $(this).val();
        const strengthBar = $('#passwordStrength');
        
        // Réinitialiser la barre de force
        strengthBar.removeClass('strength-weak strength-medium strength-strong strength-very-strong');
        
        let isValid = validateField($(this), function(value) {
            return value.length >= 8;
        });
        
        if (password.length > 0) {
            let strength = 0;
            
            // Longueur
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Complexité
            if (/[a-z]/.test(password)) strength += 1;
            if (/[A-Z]/.test(password)) strength += 1;
            if (/[0-9]/.test(password)) strength += 1;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 1;
            
            // Appliquer les classes CSS
            if (strength <= 2) {
                strengthBar.addClass('strength-weak');
            } else if (strength <= 4) {
                strengthBar.addClass('strength-medium');
            } else if (strength <= 6) {
                strengthBar.addClass('strength-strong');
            } else {
                strengthBar.addClass('strength-very-strong');
            }
        }
        
        // Valider aussi la confirmation si elle existe
        if ($('#confirmation').val().length > 0) {
            $('#confirmation').trigger('input');
        }
    });

    // Validation de la confirmation du mot de passe
    $('#confirmation').on('input', function() {
        validateField($(this), function(value) {
            return value === $('#nouveau').val() && value.length >= 8;
        });
    });

    // Toggle visibilité mot de passe
    $('.password-toggle').click(function() {
        const input = $(this).closest('.input-group').find('input');
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Réinitialiser la validation quand l'utilisateur commence à taper
    $('input').on('focus', function() {
        resetFieldValidation($(this));
        
        // Cacher les messages d'erreur PHP
        $('.alert-danger').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Validation du formulaire avant soumission
    $('#passwordForm').on('submit', function(e) {
        let isValid = true;
        
        // Valider tous les champs
        const fields = ['#ancien', '#nouveau', '#confirmation'];
        
        fields.forEach(function(fieldId) {
            const field = $(fieldId);
            field.trigger('input'); // Déclencher la validation
            
            if (!field.val().trim() || field.hasClass('is-invalid')) {
                isValid = false;
                if (!field.val().trim()) {
                    field.addClass('is-invalid');
                }
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            
            // Scroll vers le premier champ invalide
            const firstInvalid = $('.is-invalid').first();
            if (firstInvalid.length) {
                $('html, body').animate({
                    scrollTop: firstInvalid.offset().top - 100
                }, 500);
                
                // Animation sur le champ invalide
                firstInvalid.addClass('animate__animated animate__headShake');
                setTimeout(() => {
                    firstInvalid.removeClass('animate__animated animate__headShake');
                }, 1000);
            }
            
            // Afficher un message d'erreur général
            if (!$('.alert-danger').length) {
                const errorMsg = '<div class="alert alert-danger mt-3">Veuillez corriger les erreurs dans le formulaire avant de soumettre.</div>';
                $(errorMsg).insertBefore('#passwordForm');
            }
        } else {
            // Masquer les messages d'erreur existants
            $('.alert-danger').remove();
        }
    });

    // Cacher les messages d'erreur PHP quand l'utilisateur commence à taper
    $('input').on('input', function() {
        $('.alert-danger').fadeOut(300, function() {
            $(this).remove();
        });
    });

    // Désactiver le bouton de soumission initialement
    $('#submitBtn').prop('disabled', false);
});
</script>

<!-- Inclure animate.css pour les animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>