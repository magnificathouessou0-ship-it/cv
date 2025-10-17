<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

// Vérifier que l'utilisateur est connecté et que les données sont présentes
if (!isset($_SESSION['id']) || !isset($_POST['type']) || !isset($_POST['id']) || !isset($_POST['raison'])) {
    redirect("dash1.php");
}

$id_user = $_SESSION['id'];
$type = $_POST['type'];
$id = intval($_POST['id']);
$raison = sanitize($_POST['raison']);
$commentaire = isset($_POST['comment']) ? sanitize($_POST['comment']) : '';

// Tableau des raisons avec leurs descriptions
$raisons = [
    'spam' => 'Spam ou publicité',
    'inappropriate' => 'Contenu inapproprié',
    'false_info' => 'Fausse information',
    'other' => 'Autre raison'
];

// Vérifier que la raison est valide
if (!array_key_exists($raison, $raisons)) {
    redirect("dash1.php?error=invalid_reason");
}

if ($type === 'publication') {
    // Vérifier si la publication existe et récupérer l'auteur
    $checkPub = $bdd->prepare("SELECT id_users, contenu FROM publication WHERE id = ?");
    $checkPub->execute([$id]);
    $publication = $checkPub->fetch(PDO::FETCH_ASSOC);

    if (!$publication) {
        redirect("dash1.php?error=publication_not_found");
    }

    $id_auteur = $publication['id_users'];
    $contenu_publication = substr($publication['contenu'], 0, 100) . (strlen($publication['contenu']) > 100 ? '...' : '');

    // Vérifier si l'utilisateur n'est pas l'auteur
    if ($id_auteur == $id_user) {
        redirect("dash1.php?error=cannot_report_own_content");
    }

    // Vérifier si l'utilisateur a déjà signalé cette publication
    $check = $bdd->prepare("SELECT 1 FROM signalement_pub WHERE id_pub = ? AND id_user = ?");
    $check->execute([$id, $id_user]);
    
    if ($check->fetch()) {
        redirect("dash1.php?error=already_signaled");
    }

    // Enregistrer le signalement avec la raison
    $insert = $bdd->prepare("INSERT INTO signalement_pub (id_pub, id_user, raison, commentaire, date_signalement) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$id, $id_user, $raison, $commentaire]);

    // Compter les signalements
    $count = $bdd->prepare("SELECT COUNT(*) FROM signalement_pub WHERE id_pub = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();

    // Notification à l'auteur
    $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
    $message = "🔔 $pseudo a signalé votre publication pour : " . $raisons[$raison];
    
    $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
    $notif->execute([$id_auteur, $message]);

    // Si 3 signalements ou plus, suppression automatique
    if ($total >= 3) {
        // Récupérer l'image avant suppression pour la supprimer du dossier
        $getImage = $bdd->prepare("SELECT image FROM publication WHERE id = ?");
        $getImage->execute([$id]);
        $image = $getImage->fetchColumn();
        
        // Supprimer l'image du dossier uploads si elle existe
        if ($image && file_exists("uploads/$image")) {
            unlink("uploads/$image");
        }
        
        // Supprimer les réactions associées
        $deleteReactions = $bdd->prepare("DELETE FROM reactions WHERE id_publication = ?");
        $deleteReactions->execute([$id]);
        
        // Supprimer les commentaires associés
        $deleteComments = $bdd->prepare("DELETE FROM commentaires WHERE id_publication = ?");
        $deleteComments->execute([$id]);
        
        // Supprimer les signalements associés
        $deleteReports = $bdd->prepare("DELETE FROM signalement_pub WHERE id_pub = ?");
        $deleteReports->execute([$id]);
        
        // Supprimer la publication
        $deletePub = $bdd->prepare("DELETE FROM publication WHERE id = ?");
        $deletePub->execute([$id]);

        // Notification à l'auteur
        $messageSuppr = "❌ Votre publication a été supprimée automatiquement suite à plusieurs signalements pour : " . $raisons[$raison];
        $notifSuppr = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notifSuppr->execute([$id_auteur, $messageSuppr]);

        $_SESSION['success_message'] = "La publication a été signalée et supprimée automatiquement suite à plusieurs signalements.";

    } else {
        $_SESSION['success_message'] = "Publication signalée avec succès. Merci pour votre vigilance !";
    }

} elseif ($type === 'commentaire') {
    // Vérifier si le commentaire existe et récupérer l'auteur
    $checkCom = $bdd->prepare("SELECT id_users, contenu FROM commentaires WHERE id = ?");
    $checkCom->execute([$id]);
    $commentaire_data = $checkCom->fetch(PDO::FETCH_ASSOC);

    if (!$commentaire_data) {
        redirect("dash1.php?error=comment_not_found");
    }

    $id_auteur = $commentaire_data['id_users'];
    $contenu_commentaire = substr($commentaire_data['contenu'], 0, 100) . (strlen($commentaire_data['contenu']) > 100 ? '...' : '');

    // Vérifier si l'utilisateur n'est pas l'auteur
    if ($id_auteur == $id_user) {
        redirect("dash1.php?error=cannot_report_own_content");
    }

    // Vérifier si l'utilisateur a déjà signalé ce commentaire
    $check = $bdd->prepare("SELECT 1 FROM signalement_comment WHERE id_comment = ? AND id_user = ?");
    $check->execute([$id, $id_user]);
    
    if ($check->fetch()) {
        redirect("dash1.php?error=already_signaled");
    }

    // Enregistrer le signalement avec la raison
    $insert = $bdd->prepare("INSERT INTO signalement_comment (id_comment, id_user, raison, commentaire, date_signalement) VALUES (?, ?, ?, ?, NOW())");
    $insert->execute([$id, $id_user, $raison, $commentaire]);

    // Compter les signalements
    $count = $bdd->prepare("SELECT COUNT(*) FROM signalement_comment WHERE id_comment = ?");
    $count->execute([$id]);
    $total = $count->fetchColumn();

    // Notification à l'auteur
    $pseudo = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']);
    $message = "🔔 $pseudo a signalé votre commentaire pour : " . $raisons[$raison];
    
    $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
    $notif->execute([$id_auteur, $message]);

    // Si 3 signalements ou plus, suppression automatique
    if ($total >= 3) {
        // Supprimer le commentaire
        $deleteCom = $bdd->prepare("DELETE FROM commentaires WHERE id = ?");
        $deleteCom->execute([$id]);
        
        // Supprimer les signalements associés
        $deleteReports = $bdd->prepare("DELETE FROM signalement_comment WHERE id_comment = ?");
        $deleteReports->execute([$id]);

        // Notification à l'auteur
        $messageSuppr = "❌ Votre commentaire a été supprimé automatiquement suite à plusieurs signalements pour : " . $raisons[$raison];
        $notifSuppr = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?, ?, NOW())");
        $notifSuppr->execute([$id_auteur, $messageSuppr]);

        $_SESSION['success_message'] = "Le commentaire a été signalé et supprimé automatiquement suite à plusieurs signalements.";

    } else {
        $_SESSION['success_message'] = "Commentaire signalé avec succès. Merci pour votre vigilance !";
    }

} else {
    redirect("dash1.php?error=invalid_type");
}

redirect("dash1.php?success=signalement_effectue");
?>