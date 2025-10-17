<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    $req = $bdd->prepare("SELECT * FROM publication WHERE id = ? AND id_users = ?");
    $req->execute([$id, $_SESSION['id']]);
    $pub = $req->fetch();

    if (!$pub) {
        redirect("liste_pub.php");
    }

    // Supprimer l'image associée si elle existe
    if (!empty($pub['image']) && file_exists(__DIR__ . "/uploads/" . $pub['image'])) {
        unlink(__DIR__ . "/uploads/" . $pub['image']);
    }

    // Supprimer la publication (les commentaires et réactions seront supprimés automatiquement via CASCADE)
    $delete = $bdd->prepare("DELETE FROM publication WHERE id = ? AND id_users = ?");
    $delete->execute([$id, $_SESSION['id']]);

    redirect("liste_pub.php?success=publication_deleted");
} else {
    redirect("liste_pub.php");
}
?>