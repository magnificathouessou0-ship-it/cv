<?php
session_start();
require_once('config.php');

// Fonction de sanitisation
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction de redirection
function redirect($url) {
    header("Location: $url");
    exit();
}

// Initialisation des variables
$nom = $_POST['nom'] ?? '';
$prenom = $_POST['prenom'] ?? '';
$email = $_POST['email'] ?? '';
$tel = $_POST['tel'] ?? '';
$sexe = $_POST['sexe'] ?? '';
$date_naissance = $_POST['date_naissance'] ?? '';
$adresse = $_POST['adresse'] ?? '';
$mdp1 = $_POST['mdp'] ?? '';
$mdp2 = $_POST['mdp2'] ?? '';
$msg = '';

if (isset($_POST['valider'])) {
    // Sécurisation des données
    $nom = sanitize($nom);
    $prenom = sanitize($prenom);
    $email = sanitize($email);
    $tel = sanitize($tel);
    $sexe = sanitize($sexe);
    $date_naissance = sanitize($date_naissance);
    $adresse = sanitize($adresse);
    $mdp1 = sanitize($mdp1);
    $mdp2 = sanitize($mdp2);

    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($tel) ||
        empty($sexe) || empty($date_naissance) || empty($adresse) ||
        empty($mdp1) || empty($mdp2)) {
        $msg = "Veuillez remplir tous les champs.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $msg = "L'adresse email n'est pas valide.";
    }
    elseif (strlen($nom) > 25 || !preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/", $nom)) {
        $msg = "Le nom doit contenir uniquement des lettres et ne pas dépasser 25 caractères.";
    } 
    elseif (strlen($prenom) > 25 || !preg_match("/^[a-zA-ZÀ-ÿ\s'-]+$/", $prenom)) {
        $msg = "Le prénom doit contenir uniquement des lettres et ne pas dépasser 25 caractères.";
    }
    elseif (strlen($adresse) > 255) {
        $msg = "L'adresse ne doit pas dépasser 255 caractères.";
    }
    elseif ($mdp1 !== $mdp2) {
        $msg = "Les mots de passe ne correspondent pas.";
    }
    elseif (!preg_match('/^[0-9]{8,12}$/', $tel)) {
        $msg = "Le numéro de téléphone doit contenir entre 8 et 12 chiffres.";
    }
    else {
        // Vérification âge (15-80 ans)
        $dateNaissance = new DateTime($date_naissance);
        $aujourdhui = new DateTime();
        $age = $dateNaissance->diff($aujourdhui)->y;
        
        if ($age < 15 || $age > 80) {
            $msg = "Vous devez avoir entre 15 et 80 ans pour vous inscrire.";
        } else {
            // Vérification email/tel existants
            $req = $bdd->prepare('SELECT id FROM users WHERE email = ? OR tel = ?');
            $req->execute([$email, $tel]);

            if ($req->rowCount() > 0) {
                $msg = "Cet email ou ce numéro de téléphone est déjà utilisé.";
            } else {
                // Hash du mot de passe en bcrypt
                $mdp_hash = password_hash($mdp1, PASSWORD_DEFAULT);
                
                $query = $bdd->prepare("INSERT INTO users (nom, prenom, email, tel, sexe, date_naissance, adresse, mdp) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $query->execute([$nom, $prenom, $email, $tel, $sexe, $date_naissance, $adresse, $mdp_hash]);

                // Connexion automatique
                $_SESSION['id'] = $bdd->lastInsertId();
                $_SESSION['nom'] = $nom;
                $_SESSION['prenom'] = $prenom;
                $_SESSION['email'] = $email;
                $_SESSION['tel'] = $tel;
                $_SESSION['sexe'] = $sexe;
                $_SESSION['date_naissance'] = $date_naissance;
                $_SESSION['adresse'] = $adresse;

                redirect('dash1.php');
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
    <title>Inscription | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
        }
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="auth-container slide-up mx-auto" style="max-width: 700px;">
        <div class="text-center mb-4">
            <i class="fas fa-user-plus fa-3x mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            <h2 class="text-dark">Rejoignez TalkSpace</h2>
            <p class="text-muted">Créez votre compte et commencez à partager</p>
        </div>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?= $msg ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3" id="inscriptionForm">
            <!-- Nom -->
            <div class="col-md-6">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control" maxlength="25" required value="<?= htmlspecialchars($nom) ?>">
                <div class="valid-feedback">✓ Nom valide</div>
                <div class="invalid-feedback">Le nom doit contenir uniquement des lettres (2-25 caractères)</div>
            </div>

            <!-- Prénom -->
            <div class="col-md-6">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control" maxlength="25" required value="<?= htmlspecialchars($prenom) ?>">
                <div class="valid-feedback">✓ Prénom valide</div>
                <div class="invalid-feedback">Le prénom doit contenir uniquement des lettres (2-25 caractères)</div>
            </div>

            <!-- Email -->
            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" maxlength="50" required value="<?= htmlspecialchars($email) ?>">
                <div class="valid-feedback">✓ Email valide</div>
                <div class="invalid-feedback">Veuillez saisir un email valide (ex: exemple@mail.com)</div>
            </div>

            <!-- Téléphone -->
            <div class="col-md-6">
                <label for="tel" class="form-label">Téléphone</label>
                <input type="tel" name="tel" id="tel" class="form-control" pattern="\d{8,12}" required value="<?= htmlspecialchars($tel) ?>">
                <div class="valid-feedback">✓ Téléphone valide</div>
                <div class="invalid-feedback">8 à 12 chiffres requis (ex: 0123456789)</div>
            </div>

            <!-- Sexe -->
            <div class="col-md-6">
                <label for="sexe" class="form-label">Sexe</label>
                <select name="sexe" id="sexe" class="form-select" required>
                    <option value="" disabled <?= $sexe == '' ? 'selected' : '' ?>>Choisir...</option>
                    <option value="Masculin" <?= $sexe == 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                    <option value="Feminin" <?= $sexe == 'Feminin' ? 'selected' : '' ?>>Féminin</option>
                </select>
                <div class="valid-feedback">✓ Sexe sélectionné</div>
                <div class="invalid-feedback">Veuillez sélectionner votre sexe</div>
            </div>

            <!-- Date de naissance -->
            <div class="col-md-6">
                <label for="date_naissance" class="form-label">Date de naissance</label>
                <input type="date" name="date_naissance" id="date_naissance" class="form-control" required value="<?= htmlspecialchars($date_naissance) ?>">
                <div class="valid-feedback">✓ Date valide</div>
                <div class="invalid-feedback">Vous devez avoir entre 15 et 80 ans</div>
            </div>

            <!-- Adresse -->
            <div class="col-12">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea name="adresse" id="adresse" class="form-control" maxlength="255" required><?= htmlspecialchars($adresse) ?></textarea>
                <div class="valid-feedback">✓ Adresse valide</div>
                <div class="invalid-feedback">L'adresse doit contenir entre 5 et 255 caractères</div>
                <div class="form-text text-end">
                    <span id="adresseCount">0</span>/255 caractères
                </div>
            </div>

            <!-- Mot de passe -->
            <div class="col-md-6">
                <label for="mdp" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <input type="password" name="mdp" id="mdp" class="form-control" required minlength="8">
                    <button type="button" class="btn password-toggle bg-transparent border">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <div class="valid-feedback">✓ Mot de passe sécurisé</div>
                <div class="invalid-feedback">Le mot de passe doit contenir au moins 8 caractères</div>
            </div>

            <!-- Confirmation mot de passe -->
            <div class="col-md-6">
                <label for="mdp2" class="form-label">Confirmer le mot de passe</label>
                <div class="input-group">
                    <input type="password" name="mdp2" id="mdp2" class="form-control" required minlength="8">
                    <button type="button" class="btn password-toggle bg-transparent border">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="valid-feedback">✓ Les mots de passe correspondent</div>
                <div class="invalid-feedback">Les mots de passe ne correspondent pas</div>
            </div>

            <!-- Boutons -->
            <div class="col-12 text-center mt-4">
                <button type="submit" name="valider" class="btn btn-primary btn-lg px-5" id="submitBtn">
                    <i class="fas fa-user-plus me-2"></i>S'inscrire
                </button>
                <a href="login.php" class="btn btn-outline-primary btn-lg px-5 ms-2">
                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Fonction pour réinitialiser tous les états de validation
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

    // Validation du nom
    $('#nom').on('input', function() {
        validateField($(this), function(value) {
            return value.length >= 2 && value.length <= 25 && /^[a-zA-ZÀ-ÿ\s'-]+$/.test(value);
        });
    });

    // Validation du prénom
    $('#prenom').on('input', function() {
        validateField($(this), function(value) {
            return value.length >= 2 && value.length <= 25 && /^[a-zA-ZÀ-ÿ\s'-]+$/.test(value);
        });
    });

    // Validation de l'email
    $('#email').on('input', function() {
        validateField($(this), function(value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(value);
        });
    });

    // Validation du téléphone
    $('#tel').on('input', function() {
        validateField($(this), function(value) {
            return /^\d{8,12}$/.test(value);
        });
    });

    // Validation du sexe
    $('#sexe').on('change', function() {
        validateField($(this), function(value) {
            return value !== '';
        });
    });

    // Validation de la date de naissance
    $('#date_naissance').on('change', function() {
        validateField($(this), function(value) {
            if (!value) return false;
            
            const birthDate = new Date(value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            // Ajuster l'âge si l'anniversaire n'est pas encore arrivé cette année
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            return age >= 15 && age <= 80;
        });
    });

    // Validation de l'adresse avec compteur
    $('#adresse').on('input', function() {
        const value = $(this).val();
        $('#adresseCount').text(value.length);
        
        validateField($(this), function(value) {
            return value.length >= 5 && value.length <= 255;
        });
    });

    // Validation du mot de passe avec indicateur de force
    $('#mdp').on('input', function() {
        const password = $(this).val();
        const strengthBar = $('#passwordStrength');
        
        // Réinitialiser la barre de force
        strengthBar.removeClass('strength-weak strength-medium strength-strong strength-very-strong');
        
        let isValid = validateField($(this), function(value) {
            return value.length >= 8;
        });
        
        if (password.length > 0 && isValid) {
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
    });

    // Validation de la confirmation du mot de passe
    $('#mdp2').on('input', function() {
        validateField($(this), function(value) {
            return value === $('#mdp').val() && value.length >= 8;
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
    $('input, select, textarea').on('focus', function() {
        resetFieldValidation($(this));
    });

    // Validation du formulaire avant soumission
    $('#inscriptionForm').on('submit', function(e) {
        let isValid = true;
        
        // Valider tous les champs obligatoires
        const fields = ['#nom', '#prenom', '#email', '#tel', '#sexe', '#date_naissance', '#adresse', '#mdp', '#mdp2'];
        
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
        }
    });

    // Initialiser le compteur d'adresse
    $('#adresse').trigger('input');
});
</script>
</body>
</html>