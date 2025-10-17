<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id = $_SESSION['id'];

$stmt = $bdd->prepare("SELECT nom, prenom, email, tel, sexe, date_naissance, adresse FROM users WHERE id = ?");
$stmt->execute([$id]);
$users = $stmt->fetch();

// Récupérer la liste des amis
$amis_stmt = $bdd->prepare("
    SELECT u.id, u.nom, u.prenom, u.email, u.sexe 
    FROM users u 
    INNER JOIN demandes_amis da ON (u.id = da.sender_id OR u.id = da.receiver_id) 
    WHERE (da.sender_id = ? OR da.receiver_id = ?) 
    AND da.statut = 'accepter' 
    AND u.id != ?
");
$amis_stmt->execute([$id, $id, $id]);
$amis = $amis_stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['supprimer_ami'])) {
        $ami_id = intval($_POST['ami_id']);
        
        // Supprimer l'amitié
        $delete_ami = $bdd->prepare("
            DELETE FROM demandes_amis 
            WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) 
            AND statut = 'accepter'
        ");
        if ($delete_ami->execute([$id, $ami_id, $ami_id, $id])) {
            $_SESSION['success_message'] = "Ami supprimé avec succès";
            header("Location: profil.php");
            exit();
        }
    } else {
        // Mise à jour du profil
        $nom = sanitize($_POST['nom']);
        $prenom = sanitize($_POST['prenom']);
        $email = sanitize($_POST['email']);
        $tel = sanitize($_POST['tel']);
        $sexe = sanitize($_POST['sexe']);
        $adresse = sanitize($_POST['adresse']);

        // Validation des données
        if (empty($nom) || empty($prenom) || empty($email) || empty($tel) || empty($sexe)) {
            $message = "<div class='alert alert-danger'>Tous les champs obligatoires doivent être remplis.</div>";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "<div class='alert alert-danger'>L'adresse email n'est pas valide.</div>";
        } else {
            // Vérifier si l'email existe déjà pour un autre utilisateur
            $checkEmail = $bdd->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $id]);
            
            if ($checkEmail->fetch()) {
                $message = "<div class='alert alert-danger'>Cet email est déjà utilisé par un autre utilisateur.</div>";
            } else {
                $update = $bdd->prepare("UPDATE users SET nom = ?, prenom = ?, email = ?, tel = ?, sexe = ?, adresse = ? WHERE id = ?");
                if ($update->execute([$nom, $prenom, $email, $tel, $sexe, $adresse, $id])) {
                    $message = "<div class='alert alert-success'>Profil mis à jour avec succès !</div>";
                    
                    // Mettre à jour les données de session
                    $_SESSION['nom'] = $nom;
                    $_SESSION['prenom'] = $prenom;
                    $_SESSION['email'] = $email;
                    $_SESSION['tel'] = $tel;
                    $_SESSION['sexe'] = $sexe;
                    $_SESSION['adresse'] = $adresse;
                    
                    // Recharger les données utilisateur
                    $stmt->execute([$id]);
                    $users = $stmt->fetch();
                } else {
                    $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour du profil.</div>";
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
    <title>Mon Profil | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
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
        .form-control:focus, .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .ami-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .ami-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .ami-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, #007bff, #6610f2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.5rem;
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
                <li class="nav-item"><a class="nav-link" href="publication_form.php"><i class="fas fa-plus me-1"></i>Nouvelle Publication</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_pub.php"><i class="fas fa-list me-1"></i>Mes Publications</a></li>
                <li class="nav-item"><a class="nav-link" href="explorer.php"><i class="fas fa-search me-1"></i>Explorer</a></li>
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <!-- Colonne principale - Profil -->
        <div class="col-12 col-lg-8">
            <div class="card slide-up">
                <div class="card-header">
                    <h4 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifier mon profil</h4>
                </div>
                <div class="card-body">
                    <?= $message ?>
                    
                    <form method="post" class="row g-4" id="profilForm">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" value="<?= htmlspecialchars($users['nom']) ?>" required>
                            <div class="valid-feedback">✓ Nom valide</div>
                            <div class="invalid-feedback">Le nom doit contenir uniquement des lettres (2-25 caractères)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="<?= htmlspecialchars($users['prenom']) ?>" required>
                            <div class="valid-feedback">✓ Prénom valide</div>
                            <div class="invalid-feedback">Le prénom doit contenir uniquement des lettres (2-25 caractères)</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="<?= htmlspecialchars($users['email']) ?>" required>
                            <div class="valid-feedback">✓ Email valide</div>
                            <div class="invalid-feedback">Veuillez saisir un email valide</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="tel" id="tel" class="form-control" value="<?= htmlspecialchars($users['tel']) ?>" required>
                            <div class="valid-feedback">✓ Téléphone valide</div>
                            <div class="invalid-feedback">8 à 12 chiffres requis</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" name="date_naissance" class="form-control" value="<?= htmlspecialchars($users['date_naissance']) ?>" required readonly>
                            <div class="form-text">La date de naissance ne peut pas être modifiée</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Sexe</label>
                            <select name="sexe" id="sexe" class="form-select" required>
                                <option value="" disabled selected>Choisir...</option>
                                <option value="Masculin" <?= $users['sexe'] == 'Masculin' ? 'selected' : '' ?>>Masculin</option>
                                <option value="Feminin" <?= $users['sexe'] == 'Feminin' ? 'selected' : '' ?>>Féminin</option>
                            </select>
                            <div class="valid-feedback">✓ Sexe sélectionné</div>
                            <div class="invalid-feedback">Veuillez sélectionner votre sexe</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea name="adresse" id="adresse" class="form-control" rows="3"><?= htmlspecialchars($users['adresse']) ?></textarea>
                            <div class="valid-feedback">✓ Adresse valide</div>
                            <div class="invalid-feedback">L'adresse doit contenir entre 5 et 255 caractères</div>
                            <div class="form-text text-end">
                                <span id="adresseCount"><?= strlen($users['adresse']) ?></span>/255 caractères
                            </div>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100 py-2" id="submitBtn">
                                <i class="fas fa-save me-2"></i>Mettre à jour le profil
                            </button>
                        </div>
                    </form>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <a href="edit_password.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-lock me-2"></i>Modifier le mot de passe
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="dash1.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-arrow-left me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistiques du profil -->
            <div class="card mt-4 fade-in">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Mes statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-newspaper fa-2x text-primary mb-2"></i>
                                <h4>
                                    <?php 
                                    $pub_count = $bdd->prepare("SELECT COUNT(*) FROM publication WHERE id_users = ?");
                                    $pub_count->execute([$id]);
                                    echo $pub_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Publications</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-comments fa-2x text-success mb-2"></i>
                                <h4>
                                    <?php 
                                    $com_count = $bdd->prepare("SELECT COUNT(*) FROM commentaires WHERE id_users = ?");
                                    $com_count->execute([$id]);
                                    echo $com_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Commentaires</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border rounded p-3">
                                <i class="fas fa-user-friends fa-2x text-warning mb-2"></i>
                                <h4>
                                    <?php 
                                    $amis_count = $bdd->prepare("SELECT COUNT(*) FROM demandes_amis WHERE (sender_id = ? OR receiver_id = ?) AND statut = 'accepter'");
                                    $amis_count->execute([$id, $id]);
                                    echo $amis_count->fetchColumn();
                                    ?>
                                </h4>
                                <p class="text-muted mb-0">Amis</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne latérale - Liste des amis -->
        <div class="col-12 col-lg-4">
            <div class="card slide-up">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>Mes amis (<?= count($amis) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if ($amis): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($amis as $ami): ?>
                                <div class="list-group-item d-flex align-items-center p-3 ami-card">
                                    <div class="ami-avatar me-3">
                                        <?= strtoupper(substr($ami['prenom'], 0, 1) . substr($ami['nom'], 0, 1)) ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($ami['email']) ?></small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-<?= $ami['sexe'] == 'Masculin' ? 'mars' : 'venus' ?> me-1"></i>
                                            <?= htmlspecialchars($ami['sexe']) ?>
                                        </small>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="profil_ami.php?id=<?= $ami['id'] ?>">
                                                    <i class="fas fa-eye me-2"></i>Voir le profil
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button class="dropdown-item text-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#modalSupprimerAmi"
                                                        data-ami-id="<?= $ami['id'] ?>"
                                                        data-ami-nom="<?= htmlspecialchars($ami['prenom'] . ' ' . $ami['nom']) ?>">
                                                    <i class="fas fa-user-times me-2"></i>Supprimer
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                            <h6 class="text-muted">Aucun ami pour le moment</h6>
                            <p class="text-muted small">Ajoutez des amis pour les voir ici</p>
                            <a href="explorer.php" class="btn btn-primary btn-sm">
                                <i class="fas fa-search me-1"></i>Trouver des amis
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="card mt-4 fade-in">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Actions rapides</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="liste_amis.php" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>Gérer mes amis
                        </a>
                        <a href="explorer.php" class="btn btn-outline-success">
                            <i class="fas fa-search me-2"></i>Trouver des amis
                        </a>
                        <a href="publication_form.php" class="btn btn-outline-warning">
                            <i class="fas fa-plus me-2"></i>Nouvelle publication
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Supprimer un ami -->
<div class="modal fade" id="modalSupprimerAmi" tabindex="-1" aria-labelledby="modalSupprimerAmiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalSupprimerAmiLabel">
                    <i class="fas fa-user-times me-2"></i>Supprimer un ami
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="fas fa-exclamation-triangle text-warning fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Confirmer la suppression</h6>
                        <p class="mb-0">Êtes-vous sûr de vouloir supprimer <strong id="ami-nom-suppression"></strong> de votre liste d'amis ?</p>
                    </div>
                </div>
                <div class="alert alert-warning mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    <small>Cette action est irréversible. Vous devrez renvoyer une demande d'amitié pour redevenir amis.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form method="post" id="delete-ami-form">
                    <input type="hidden" name="ami_id" id="delete-ami-id">
                    <button type="submit" name="supprimer_ami" class="btn btn-danger">
                        <i class="fas fa-user-times me-1"></i>Supprimer l'ami
                    </button>
                </form>
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

    // Validation de l'adresse avec compteur
    $('#adresse').on('input', function() {
        const value = $(this).val();
        $('#adresseCount').text(value.length);
        
        validateField($(this), function(value) {
            return value.length >= 5 && value.length <= 255;
        });
    });

    // Gestion du modal de suppression d'ami
    $('#modalSupprimerAmi').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget);
        const amiId = button.data('ami-id');
        const amiNom = button.data('ami-nom');
        
        $('#delete-ami-id').val(amiId);
        $('#ami-nom-suppression').text(amiNom);
    });

    // Réinitialiser la validation quand l'utilisateur commence à taper
    $('input, select, textarea').on('focus', function() {
        resetFieldValidation($(this));
    });

    // Validation du formulaire avant soumission
    $('#profilForm').on('submit', function(e) {
        let isValid = true;
        
        // Valider tous les champs obligatoires
        const fields = ['#nom', '#prenom', '#email', '#tel', '#sexe', '#adresse'];
        
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
                $(errorMsg).insertBefore('#profilForm');
            }
        } else {
            // Masquer les messages d'erreur existants
            $('.alert-danger').remove();
        }
    });

    $('input, select, textarea').on('input', function() {
        $('.alert-danger').fadeOut(300, function() {
            $(this).remove();
        });
    });

    $('#nom, #prenom, #email, #tel, #sexe, #adresse').trigger('input');
});
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
</body>
</html>