<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    redirect("liste_amis.php");
}

$user_id = $_SESSION['id'];
$ami_id = intval($_GET['id']);

// Vérifier si amis
$check = $bdd->prepare("SELECT 1 FROM demandes_amis WHERE statut='accepter' AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))");
$check->execute([$user_id, $ami_id, $ami_id, $user_id]);
if (!$check->fetch()) {
    echo "<div class='alert alert-danger'>Vous n'êtes pas amis avec cet utilisateur.</div>";
    exit();
}

// Récupérer le nom de l'ami
$ami_stmt = $bdd->prepare("SELECT nom, prenom FROM users WHERE id = ?");
$ami_stmt->execute([$ami_id]);
$ami_data = $ami_stmt->fetch(PDO::FETCH_ASSOC);

if (!$ami_data) {
    echo "<div class='alert alert-danger'>Utilisateur introuvable.</div>";
    exit();
}

$ami_nom = $ami_data['prenom'] . " " . $ami_data['nom'];

// Envoi message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['message']) && empty($_POST['message_id'])) {
        // Nouveau message
        $msg = sanitize($_POST['message']);
        $insert = $bdd->prepare("INSERT INTO messages (sender_id, receiver_id, message, date_envoie) VALUES (?, ?, ?, NOW())");
        $insert->execute([$user_id, $ami_id, $msg]);
        
        // NOTIFICATION : Créer une notification pour le destinataire
        $message_notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $message_notif->execute([$ami_id, $_SESSION['prenom'] . " " . $_SESSION['nom'] . " vous a envoyé un message"]);
        
        // Redirection pour éviter le re-soumission
        redirect("messagerie.php?id=" . $ami_id);
    }
    elseif (!empty($_POST['message_id']) && !empty($_POST['message'])) {
        // Modification de message
        $message_id = intval($_POST['message_id']);
        $msg = sanitize($_POST['message']);
        
        // Vérifier que l'utilisateur est bien l'expéditeur du message
        $verify_owner = $bdd->prepare("SELECT id FROM messages WHERE id = ? AND sender_id = ?");
        $verify_owner->execute([$message_id, $user_id]);
        
        if ($verify_owner->fetch()) {
            $update = $bdd->prepare("UPDATE messages SET message = ?, est_modifie = 1 WHERE id = ?");
            $update->execute([$msg, $message_id]);
        }
        
        redirect("messagerie.php?id=" . $ami_id);
    }
}

// Suppression de message
if (isset($_GET['delete_id']) && isset($_GET['scope'])) {
    $delete_id = intval($_GET['delete_id']);
    $scope = $_GET['scope'];
    
    // Vérifier que l'utilisateur est bien l'expéditeur du message
    $verify_owner = $bdd->prepare("SELECT id, sender_id FROM messages WHERE id = ?");
    $verify_owner->execute([$delete_id]);
    $message_data = $verify_owner->fetch(PDO::FETCH_ASSOC);
    
    if ($message_data && $message_data['sender_id'] == $user_id) {
        if ($scope === 'me') {
            // Supprimer uniquement pour l'utilisateur actuel
            $delete = $bdd->prepare("UPDATE messages SET deleted_for_sender = 1 WHERE id = ?");
            $delete->execute([$delete_id]);
        } elseif ($scope === 'all') {
            // Supprimer pour tout le monde
            $delete = $bdd->prepare("UPDATE messages SET deleted_for_receiver = 1, deleted_for_sender = 1 WHERE id = ?");
            $delete->execute([$delete_id]);
            
            // Si les deux ont supprimé, on peut supprimer définitivement
            $check_both_deleted = $bdd->prepare("SELECT deleted_for_sender, deleted_for_receiver FROM messages WHERE id = ?");
            $check_both_deleted->execute([$delete_id]);
            $deletion_status = $check_both_deleted->fetch(PDO::FETCH_ASSOC);
            
            if ($deletion_status && $deletion_status['deleted_for_sender'] && $deletion_status['deleted_for_receiver']) {
                $permanent_delete = $bdd->prepare("DELETE FROM messages WHERE id = ?");
                $permanent_delete->execute([$delete_id]);
            }
        }
    }
    
    redirect("messagerie.php?id=" . $ami_id);
}

// Récupérer messages (uniquement ceux non supprimés pour l'utilisateur actuel)
$stmt = $bdd->prepare("SELECT m.id, m.message, m.sender_id, m.receiver_id, m.date_envoie, m.est_modifie, 
                              m.deleted_for_sender, m.deleted_for_receiver,
                              u.nom AS sender_nom, u.prenom AS sender_prenom 
                       FROM messages m 
                       JOIN users u ON m.sender_id = u.id 
                       WHERE ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
                       AND (m.sender_id = ? AND m.deleted_for_sender = 0 OR m.receiver_id = ? AND m.deleted_for_receiver = 0)
                       ORDER BY m.date_envoie ASC");
$stmt->execute([$user_id, $ami_id, $ami_id, $user_id, $user_id, $user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer les messages comme lus
$update_lus = $bdd->prepare("UPDATE messages SET lu = 1 WHERE receiver_id = ? AND sender_id = ? AND lu = 0");
$update_lus->execute([$user_id, $ami_id]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie | TalkSpace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-container {
            max-width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            height: 85vh;
        }
        .chat-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 20px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            background: #f8f9fa;
            gap: 15px;
        }
        .chat-input-container {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            background: white;
            border-radius: 0 0 15px 15px;
            flex-shrink: 0;
        }
        .message {
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        .message-sent {
            background: #007bff;
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
            margin-left: auto;
        }
        .message-received {
            background: white;
            color: #333;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
            border: 1px solid #e0e0e0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-time {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 5px;
            text-align: right;
        }
        .message-received .message-time {
            text-align: left;
            color: #666;
        }
        .input-group {
            gap: 10px;
        }
        .message-input {
            border-radius: 25px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            flex: 1;
        }
        .send-btn {
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transition: all 0.3s ease;
        }
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .send-btn:active {
            transform: scale(0.95);
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
        }
        
        /* Styles pour les actions des messages */
        .message-actions {
            position: absolute;
            top: -10px;
            right: 10px;
            opacity: 0;
            transition: opacity 0.3s ease;
            display: flex;
            gap: 5px;
        }
        
        .message-sent .message-actions {
            right: auto;
            left: 10px;
        }
        
        .message:hover .message-actions {
            opacity: 1;
        }
        
        .message-action-btn {
            width: 25px;
            height: 25px;
            border-radius: 50%;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .message-sent .message-action-btn {
            background: rgba(255, 255, 255, 0.9);
            color: #007bff;
        }
        
        .message-received .message-action-btn {
            background: rgba(0, 0, 0, 0.1);
            color: #666;
        }
        
        .message-action-btn:hover {
            transform: scale(1.1);
        }
        
        .edit-indicator {
            font-size: 0.6rem;
            font-style: italic;
            margin-top: 2px;
            opacity: 0.8;
        }
        
        /* Styles pour le modal de suppression */
        .delete-option {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .delete-option:hover {
            background-color: #f8f9fa;
            border-color: #007bff;
        }
        
        .delete-option.selected {
            background-color: #e7f3ff;
            border-color: #007bff;
        }
        
        .delete-option input[type="radio"] {
            margin-right: 10px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .chat-container {
                height: 90vh;
                border-radius: 0;
            }
            .chat-header {
                border-radius: 0;
                padding: 12px 15px;
            }
            .chat-messages {
                padding: 15px;
            }
            .message {
                max-width: 85%;
            }
            .chat-input-container {
                padding: 12px 15px;
            }
            .message-input {
                padding: 10px 15px;
            }
            .send-btn {
                width: 45px;
                height: 45px;
            }
            .message-actions {
                opacity: 1; /* Toujours visible sur mobile */
            }
        }
        
        @media (max-width: 576px) {
            .message {
                max-width: 90%;
            }
            .chat-header h6 {
                font-size: 0.9rem;
            }
            .chat-header small {
                font-size: 0.75rem;
            }
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
                <li class="nav-item"><a class="nav-link" href="liste_amis.php"><i class="fas fa-user-friends me-1"></i>Amis</a></li>
                <li class="nav-item"><a class="nav-link active" href="messagerie.php"><i class="fas fa-comments me-1"></i>Messagerie</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="profil.php"><i class="fas fa-user me-1"></i>Profil</a></li>
                <li class="nav-item"><a class="nav-link" href="deconnexion.php"><i class="fas fa-sign-out-alt me-1"></i>Déconnexion</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid p-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8 col-xl-6">
            <div class="chat-container">
                <!-- En-tête de la conversation -->
                <div class="chat-header">
                    <div class="d-flex align-items-center">
                        <a href="liste_amis.php" class="btn btn-sm btn-light me-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($ami_nom) ?></h6>
                            <small class="opacity-75">
                                <?php 
                                $last_seen = $bdd->prepare("SELECT MAX(date_envoie) FROM messages WHERE sender_id = ?");
                                $last_seen->execute([$ami_id]);
                                $last_activity = $last_seen->fetchColumn();
                                if ($last_activity) {
                                    $diff = time() - strtotime($last_activity);
                                    if ($diff < 300) { // 5 minutes
                                        echo 'En ligne';
                                    } else {
                                        echo 'Dernière connexion: ' . date('H:i', strtotime($last_activity));
                                    }
                                } else {
                                    echo 'Hors ligne';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                    <div class="chat-actions">
                        <a href="profil_ami.php?id=<?= $ami_id ?>" class="btn btn-sm btn-light" title="Voir le profil">
                            <i class="fas fa-user"></i>
                        </a>
                    </div>
                </div>

                <!-- Zone des messages -->
                <div class="chat-messages" id="chatMessages">
                    <?php if (empty($messages)): ?>
                        <div class="empty-state">
                            <i class="fas fa-comments fa-4x mb-3 opacity-50"></i>
                            <h5 class="text-muted">Aucun message échangé</h5>
                            <p class="text-muted text-center">Envoyez le premier message pour commencer la conversation !</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $m): ?>
                            <div class="d-flex <?= $m['sender_id'] == $user_id ? 'justify-content-end' : 'justify-content-start' ?>">
                                <div class="message <?= $m['sender_id'] == $user_id ? 'message-sent' : 'message-received' ?>" id="message-<?= $m['id'] ?>">
                                    <!-- Actions pour les messages envoyés -->
                                    <?php if ($m['sender_id'] == $user_id): ?>
                                        <div class="message-actions">
                                            <button class="message-action-btn edit-message" 
                                                    data-message-id="<?= $m['id'] ?>" 
                                                    data-message-content="<?= ($m['message']) ?>"
                                                    title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="message-action-btn delete-message" 
                                                    data-message-id="<?= $m['id'] ?>"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="message-content">
                                        <?= nl2br(($m['message'])) ?>
                                    </div>
                                    <div class="message-time">
                                        <?= date('H:i', strtotime($m['date_envoie'])) ?>
                                        <?php if ($m['est_modifie']): ?>
                                            <div class="edit-indicator">(modifié)</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Zone de saisie -->
                <div class="chat-input-container">
                    <form method="POST" class="d-flex gap-2 align-items-center" id="messageForm">
                        <input type="hidden" name="message_id" id="messageId" value="">
                        <input type="text" name="message" class="form-control message-input" 
                               placeholder="Tapez votre message..." required
                               maxlength="1000" id="messageInput">
                        <button type="submit" class="send-btn" id="submitBtn">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="cancelEdit" style="display: none;">
                            Annuler
                        </button>
                    </form>
                    <div class="form-text text-center mt-2">
                        <small>Appuyez sur Entrée pour envoyer</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Options de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Choisissez comment vous souhaitez supprimer ce message :</p>
                
                <div class="delete-option" onclick="selectOption('me')">
                    <input type="radio" id="optionMe" name="deleteScope" value="me">
                    <label for="optionMe" class="w-100">
                        <strong>Supprimer uniquement pour moi</strong>
                        <div class="text-muted small">
                            Le message sera supprimé de votre conversation, mais restera visible pour <?= htmlspecialchars($ami_data['prenom']) ?>.
                        </div>
                    </label>
                </div>
                
                <div class="delete-option" onclick="selectOption('all')">
                    <input type="radio" id="optionAll" name="deleteScope" value="all">
                    <label for="optionAll" class="w-100">
                        <strong>Supprimer pour tout le monde</strong>
                        <div class="text-muted small">
                            Le message sera supprimé pour vous et pour <?= htmlspecialchars($ami_data['prenom']) ?>. Cette action est irréversible.
                        </div>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-scroll vers le bas
document.addEventListener('DOMContentLoaded', function() {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    // Focus sur l'input
    document.getElementById('messageInput').focus();
});

// Envoyer avec Entrée
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        if (this.value.trim() !== '') {
            this.form.submit();
        }
    }
});

// Gestion de l'édition des messages
document.querySelectorAll('.edit-message').forEach(button => {
    button.addEventListener('click', function() {
        const messageId = this.getAttribute('data-message-id');
        const messageContent = this.getAttribute('data-message-content');
        
        // Remplir le formulaire avec le message à modifier
        document.getElementById('messageId').value = messageId;
        document.getElementById('messageInput').value = messageContent;
        document.getElementById('messageInput').focus();
        
        // Changer le bouton d'envoi
        document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i>';
        
        // Afficher le bouton d'annulation
        document.getElementById('cancelEdit').style.display = 'block';
    });
});

// Annuler l'édition
document.getElementById('cancelEdit').addEventListener('click', function() {
    resetForm();
});

function resetForm() {
    document.getElementById('messageId').value = '';
    document.getElementById('messageInput').value = '';
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-paper-plane"></i>';
    document.getElementById('cancelEdit').style.display = 'none';
    document.getElementById('messageInput').focus();
}

// Gestion de la suppression des messages
const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
let currentDeleteId = null;
let selectedScope = null;

function selectOption(scope) {
    selectedScope = scope;
    document.getElementById('confirmDelete').disabled = false;
    
    // Mettre à jour l'apparence des options
    document.querySelectorAll('.delete-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    if (scope === 'me') {
        document.querySelector('[for="optionMe"]').closest('.delete-option').classList.add('selected');
    } else {
        document.querySelector('[for="optionAll"]').closest('.delete-option').classList.add('selected');
    }
}

document.querySelectorAll('.delete-message').forEach(button => {
    button.addEventListener('click', function() {
        currentDeleteId = this.getAttribute('data-message-id');
        selectedScope = null;
        document.getElementById('confirmDelete').disabled = true;
        
        // Réinitialiser les sélections
        document.querySelectorAll('.delete-option').forEach(option => {
            option.classList.remove('selected');
        });
        document.querySelectorAll('input[name="deleteScope"]').forEach(radio => {
            radio.checked = false;
        });
        
        deleteModal.show();
    });
});

document.getElementById('confirmDelete').addEventListener('click', function() {
    if (currentDeleteId && selectedScope) {
        window.location.href = 'messagerie.php?id=<?= $ami_id ?>&delete_id=' + currentDeleteId + '&scope=' + selectedScope;
    }
});

// Auto-refresh des messages toutes les 5 secondes (désactivé pendant l'édition)
let isEditing = false;

document.getElementById('messageInput').addEventListener('focus', function() {
    isEditing = this.value !== '' && document.getElementById('messageId').value !== '';
});

document.getElementById('messageInput').addEventListener('blur', function() {
    isEditing = false;
});

setInterval(function() {
    if (isEditing) return;
    
    const chatMessages = document.getElementById('chatMessages');
    const isScrolledToBottom = chatMessages.scrollHeight - chatMessages.clientHeight <= chatMessages.scrollTop + 100;
    
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newMessages = doc.getElementById('chatMessages');
            if (newMessages) {
                chatMessages.innerHTML = newMessages.innerHTML;
                if (isScrolledToBottom) {
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
                
                // Réattacher les événements après le rafraîchissement
                attachMessageEvents();
            }
        })
        .catch(error => console.log('Erreur de rafraîchissement:', error));
}, 5000);

function attachMessageEvents() {
    // Réattacher les événements d'édition
    document.querySelectorAll('.edit-message').forEach(button => {
        button.addEventListener('click', function() {
            const messageId = this.getAttribute('data-message-id');
            const messageContent = this.getAttribute('data-message-content');
            
            document.getElementById('messageId').value = messageId;
            document.getElementById('messageInput').value = messageContent;
            document.getElementById('messageInput').focus();
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save"></i>';
            document.getElementById('cancelEdit').style.display = 'block';
        });
    });
    
    // Réattacher les événements de suppression
    document.querySelectorAll('.delete-message').forEach(button => {
        button.addEventListener('click', function() {
            currentDeleteId = this.getAttribute('data-message-id');
            selectedScope = null;
            document.getElementById('confirmDelete').disabled = true;
            
            document.querySelectorAll('.delete-option').forEach(option => {
                option.classList.remove('selected');
            });
            document.querySelectorAll('input[name="deleteScope"]').forEach(radio => {
                radio.checked = false;
            });
            
            deleteModal.show();
        });
    });
}

// Animation des nouveaux messages
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.addedNodes.length) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType === 1 && node.classList.contains('message')) {
                    node.style.opacity = '0';
                    node.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        node.style.transition = 'all 0.3s ease';
                        node.style.opacity = '1';
                        node.style.transform = 'translateY(0)';
                    }, 50);
                }
            });
        }
    });
});

observer.observe(document.getElementById('chatMessages'), {
    childList: true,
    subtree: true
});
</script>
</body>
</html>