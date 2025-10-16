<?php
session_start();
require_once('config.php');

// Vérifier que l'utilisateur est connecté et qu'une publication est ciblée
if (!isset($_SESSION['id']) ||!isset($_GET['id_pub'])) {
    header("Location: dash1.php");
    exit();
}

$user_id = $_SESSION['id'];
$id_pub = $_GET['id_pub'];

// Vérifier si la publication existe
$checkPub = $bdd->prepare("SELECT id_users FROM publication WHERE id =?");
$checkPub->execute([$id_pub]);
$id_auteur = $checkPub->fetchColumn();

if (!$id_auteur) {
    header("Location: dash1.php");
    exit();
}

// Vérifier si l'utilisateur a déjà signalé cette publication
$check = $bdd->prepare("SELECT 1 FROM signalement_pub WHERE id_pub =? AND id_user =?");
$check->execute([$id_pub, $user_id]);
if ($check->fetch()) {
    echo "Vous avez déjà signalé cette publication.";
    exit();
}

// Enregistrer le signalement
$insert = $bdd->prepare("INSERT INTO signalement_pub (id_pub, id_user) VALUES (?,?)");
$insert->execute([$id_pub, $user_id]);

// Compter combien de fois cette publication a été signalée
$count = $bdd->prepare("SELECT COUNT(*) FROM signalement_pub WHERE id_pub =?");
$count->execute([$id_pub]);
$total = $count->fetchColumn(); 

// Envoie une notification à l'auteur si ce n'est pas lui qui signale
if ($id_auteur!= $user_id) {
    $pseudo = $_SESSION['nom'];
    $message = $pseudo. " a signalé votre publication.";

    $notif = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?,?, NOW())");
    $notif->execute([$id_auteur, $message]);
}

// Si la publication a été signalée 5 fois ou plus, on la supprime
if ($total>= 5) {
    // Supprime la publication
    $deletePub = $bdd->prepare("DELETE FROM publication WHERE id =?");
    $deletePub->execute([$id_pub]);

    // Supprimer les signalements liés à cette publication
    $deleteSignals = $bdd->prepare("DELETE FROM signalement_pub WHERE id_pub =?");
    $deleteSignals->execute([$id_pub]);

    // Notifier à l'auteur que sa publication a été supprimée
    $messageSuppr = "Votre publication a été supprimée suite à plusieurs signalements.";
    $notifSuppr = $bdd->prepare("INSERT INTO notifications (id_users, message, date_notif) VALUES (?,?, NOW())");
    $notifSuppr->execute([$id_auteur, $messageSuppr]);
}

// Redirige vers le tableau de bord
header("Location: dash1.php");
exit();
?>