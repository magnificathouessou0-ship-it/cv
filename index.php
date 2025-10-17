<?php
session_start();
require_once('config.php');
require_once('functions.php');

// Import PHPMailer classes - DOIT ÊTRE PLACÉ ICI AU DÉBUT
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Vérifier si la table a les colonnes de confirmation
function tableAvecConfirmationEmail($bdd) {
    try {
        $test = $bdd->query("SHOW COLUMNS FROM users LIKE 'token_confirmation'");
        return ($test->rowCount() > 0);
    } catch (Exception $e) {
        return false;
    }
}

$avec_confirmation_email = tableAvecConfirmationEmail($bdd);

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
$success_msg = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        $query = $bdd->prepare("SELECT id, email, token_expiration FROM users WHERE token_confirmation = ? AND email_confirme = 0");
        $query->execute([$token]);
        $user = $query->fetch();
        
        if ($user) {
            $maintenant = date('Y-m-d H:i:s');
            if ($user['token_expiration'] > $maintenant) {
                $update = $bdd->prepare("UPDATE users SET email_confirme = 1, token_confirmation = NULL, token_expiration = NULL WHERE id = ?");
                $update->execute([$user['id']]);
                
                $_SESSION['success_msg'] = "Votre compte a été activé avec succès ! Vous pouvez maintenant vous connecter.";
                header('Location: login.php');
                exit();
            } else {
                $msg = " Le lien de confirmation a expiré. Veuillez vous réinscrire.";
            }
        } else {
            $msg = " Token de confirmation invalide ou compte déjà activé.";
        }
    } catch (PDOException $e) {
        $msg = "Erreur lors de la confirmation : " . $e->getMessage();
    }
}

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
        $dateNaissance = new DateTime($date_naissance);
        $aujourdhui = new DateTime();
        $age = $dateNaissance->diff($aujourdhui)->y;
        
        if ($age < 15 || $age > 80) {
            $msg = "Vous devez avoir entre 15 et 80 ans pour vous inscrire.";
        } else {
            $req = $bdd->prepare('SELECT id FROM users WHERE email = ? OR tel = ?');
            $req->execute([$email, $tel]);

            if ($req->rowCount() > 0) {
                $msg = "Cet email ou ce numéro de téléphone est déjà utilisé.";
            } else {
                $mdp_hash = password_hash($mdp1, PASSWORD_DEFAULT);
                
                if ($avec_confirmation_email) {
                    try {
                        $token_confirmation = bin2hex(random_bytes(32));
                        $date_expiration = date('Y-m-d H:i:s', strtotime('+24 hours'));
                        
                        $query = $bdd->prepare("INSERT INTO users (nom, prenom, email, tel, sexe, date_naissance, adresse, mdp, token_confirmation, token_expiration, email_confirme) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                        
                        if ($query->execute([$nom, $prenom, $email, $tel, $sexe, $date_naissance, $adresse, $mdp_hash, $token_confirmation, $date_expiration])) {
                            
                            // Créer le lien de confirmation UNIVERSEL
                            $lien_confirmation = genererLienConfirmation($token_confirmation);
                            
                            if (envoyerEmailAvecPHPMailer($email, $prenom . ' ' . $nom, $lien_confirmation)) {
                                $_SESSION['success_msg'] = "Inscription réussie ! Un email de confirmation a été envoyé à <strong>$email</strong>. Vérifiez votre boîte de réception et cliquez sur le lien pour activer votre compte.";
                                header('Location: login.php');
                                exit();
                            } else {
                                creerFichierConfirmation($email, $prenom . ' ' . $nom, $lien_confirmation);
                                $_SESSION['success_msg'] = "Inscription réussie ! (Email simulé - vérifiez le dossier emails_simulation)";
                                header('Location: login.php');
                                exit();
                            }
                        } else {
                            $msg = "Erreur lors de l'inscription avec confirmation email.";
                        }
                    } catch (PDOException $e) {
                        $msg = "Erreur base de données : " . $e->getMessage();
                    }
                } else {
                    try {
                        $query = $bdd->prepare("INSERT INTO users (nom, prenom, email, tel, sexe, date_naissance, adresse, mdp) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        if ($query->execute([$nom, $prenom, $email, $tel, $sexe, $date_naissance, $adresse, $mdp_hash])) {
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
                        } else {
                            $msg = "Erreur lors de l'inscription.";
                        }
                    } catch (PDOException $e) {
                        $msg = "Erreur base de données : " . $e->getMessage();
                    }
                }
            }
        }
    }
}

function genererLienConfirmation($token) {
    $protocole = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME']; // Chemin du script actuel (login.php)
    
    $base_url = $protocole . '://' . $host . dirname($script);
    
    $base_url = rtrim($base_url, '/');
    
    return $base_url . '/index.php?token=' . $token;
}

// Fonction pour envoyer l'email de confirmation avec PHPMailer
function envoyerEmailAvecPHPMailer($destinataire, $nom_complet, $lien_confirmation) {
    $mail = new PHPMailer(true);
    
    try {
        // Configuration du serveur SMTP Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'talkspace2025@gmail.com';
        $mail->Password   = 'rcfvtsjswvzwvtqh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Destinataires
        $mail->setFrom('talkspace2025@gmail.com', 'TalkSpace');
        $mail->addAddress($destinataire, $nom_complet);
        
        // Contenu de l'email
        $mail->isHTML(true);
        $mail->Subject = 'Confirmez votre inscription sur TalkSpace';
        $mail->CharSet = 'UTF-8';
        
        // Corps de l'email en HTML
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Confirmation d'inscription</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 30px; text-align: center; }
                .content { padding: 30px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; font-weight: bold; }
                .footer { text-align: center; margin-top: 20px; padding: 20px; background: #f8f9fa; color: #666; font-size: 12px; }
                .link-box { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 15px 0; word-break: break-all; font-family: monospace; }
                .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Bienvenue sur TalkSpace !</h1>
                </div>
                <div class='content'>
                    <h2>Bonjour $nom_complet,</h2>
                    <p>Merci de vous être inscrit sur <strong>TalkSpace</strong>. Nous sommes ravis de vous accueillir !</p>
                    
                    <p>Pour activer votre compte et commencer à partager, veuillez confirmer votre adresse email en cliquant sur le bouton ci-dessous :</p>
                    
                    <div style='text-align: center;'>
                        <a href='$lien_confirmation' class='button'>Confirmer mon email</a>
                    </div>
                    
                    <p>Si le bouton ne fonctionne pas, copiez-collez ce lien dans votre navigateur :</p>
                    <div class='link-box'>$lien_confirmation</div>
                    
                    <div class='warning'>
                        <strong> Important :</strong> Ce lien expirera dans 24 heures.
                    </div>
                    
                    <p>Si vous n'avez pas créé de compte sur TalkSpace, veuillez ignorer cet email.</p>
                    
                    <p>À bientôt sur TalkSpace !</p>
                    
                    <p>Cordialement,<br><strong>L'équipe TalkSpace</strong></p>
                </div>
                <div class='footer'>
                    <p>© 2025 TalkSpace. Tous droits réservés.</p>
                    <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Version texte alternative
        $mail->AltBody = "Bonjour $nom_complet,\n\nMerci de vous être inscrit sur TalkSpace.\n\nPour activer votre compte, cliquez sur ce lien : $lien_confirmation\n\nCe lien expirera dans 24 heures.\n\nCordialement,\nL'équipe TalkSpace";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        // Log l'erreur
        error_log("Erreur PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

// Fonction de secours pour créer un fichier de confirmation
function creerFichierConfirmation($email, $nom_complet, $lien_confirmation) {
    $dossier_emails = __DIR__ . '/emails_simulation';
    if (!is_dir($dossier_emails)) {
        mkdir($dossier_emails, 0755, true);
    }
    
    $contenu_email = "
    =============================================
    EMAIL DE CONFIRMATION TALKSPACE (SIMULATION)
    =============================================
    
    Destinataire: $email
    Nom: $nom_complet
    Date: " . date('Y-m-d H:i:s') . "
    
    Bonjour $nom_complet,
    
    Merci de vous être inscrit sur TalkSpace. 
    Pour activer votre compte, cliquez sur le lien suivant :
    
    $lien_confirmation
    
     Ce lien expirera dans 24 heures.
    
    Si vous n'avez pas créé de compte, ignorez cet email.
    
    © 2025 TalkSpace
    ";
    
    $fichier_email = $dossier_emails . '/confirmation_' . $email . '_' . time() . '.txt';
    file_put_contents($fichier_email, $contenu_email);
    
    return true;
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
        .is-valid { border-color: #198754 !important; box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25) !important; }
        .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important; }
        .valid-feedback, .invalid-feedback { display: none; font-size: 0.875em; margin-top: 0.25rem; }
        .is-valid ~ .valid-feedback, .is-invalid ~ .invalid-feedback { display: block; }
        .valid-feedback { color: #198754; }
        .invalid-feedback { color: #dc3545; }
        .password-strength { height: 5px; margin-top: 5px; border-radius: 3px; transition: all 0.3s ease; }
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-medium { background-color: #ffc107; width: 50%; }
        .strength-strong { background-color: #28a745; width: 75%; }
        .strength-very-strong { background-color: #20c997; width: 100%; }
        .auth-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 2rem; margin-top: 2rem; }
        :root { --primary-color: #667eea; --secondary-color: #764ba2; }
        .info-alert { background: linear-gradient(135deg, #e3f2fd, #bbdefb); border: 1px solid #90caf9; border-radius: 10px; padding: 15px; margin-bottom: 20px; }
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

        <?php if ($avec_confirmation_email): ?>
        <!-- Information sur la confirmation par email -->
        <div class="info-alert animate__animated animate__fadeIn">
            <div class="d-flex align-items-center">
                <i class="fas fa-envelope text-primary fa-2x me-3"></i>
                <div>
                    <h6 class="mb-1">Confirmation par email requise</h6>
                    <p class="mb-0 small">Après l'inscription, vous recevrez un email de confirmation avec un lien pour activer votre compte.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success animate__animated animate__fadeIn">
                <?= $success_msg ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="alert alert-danger animate__animated animate__shakeX"><?= $msg ?></div>
        <?php endif; ?>

        <form method="post" class="row g-3" id="inscriptionForm">
            <!-- Tous les champs du formulaire -->
            <div class="col-md-6">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control" maxlength="25" required value="<?= htmlspecialchars($nom) ?>">
                <div class="valid-feedback">✓ Nom valide</div>
                <div class="invalid-feedback">Le nom doit contenir uniquement des lettres (2-25 caractères)</div>
            </div>

            <div class="col-md-6">
                <label for="prenom" class="form-label">Prénom</label>
                <input type="text" name="prenom" id="prenom" class="form-control" maxlength="25" required value="<?= htmlspecialchars($prenom) ?>">
                <div class="valid-feedback">✓ Prénom valide</div>
                <div class="invalid-feedback">Le prénom doit contenir uniquement des lettres (2-25 caractères)</div>
            </div>

            <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email" name="email" id="email" class="form-control" maxlength="50" required value="<?= htmlspecialchars($email) ?>">
                <div class="valid-feedback">✓ Email valide</div>
                <div class="invalid-feedback">Veuillez saisir un email valide</div>
            </div>

            <div class="col-md-6">
                <label for="tel" class="form-label">Téléphone</label>
                <input type="tel" name="tel" id="tel" class="form-control" pattern="\d{8,12}" required value="<?= htmlspecialchars($tel) ?>">
                <div class="valid-feedback">✓ Téléphone valide</div>
                <div class="invalid-feedback">8 à 12 chiffres requis</div>
            </div>

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

            <div class="col-md-6">
                <label for="date_naissance" class="form-label">Date de naissance</label>
                <input type="date" name="date_naissance" id="date_naissance" class="form-control" required value="<?= htmlspecialchars($date_naissance) ?>">
                <div class="valid-feedback">✓ Date valide</div>
                <div class="invalid-feedback">Vous devez avoir entre 15 et 80 ans</div>
            </div>

            <div class="col-12">
                <label for="adresse" class="form-label">Adresse</label>
                <textarea name="adresse" id="adresse" class="form-control" maxlength="255" required><?= htmlspecialchars($adresse) ?></textarea>
                <div class="valid-feedback">✓ Adresse valide</div>
                <div class="invalid-feedback">L'adresse doit contenir entre 5 et 255 caractères</div>
                <div class="form-text text-end">
                    <span id="adresseCount">0</span>/255 caractères
                </div>
            </div>

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
    // ... (le reste du code JavaScript reste identique)
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