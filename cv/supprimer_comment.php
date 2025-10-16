<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_comment']) && isset($_POST['id_publication'])) {
    $id_comment = intval($_POST['id_comment']);
    $id_publication = intval($_POST['id_publication']);

    // Vérifie que le commentaire appartient à l'utilisateur
    $check = $bdd->prepare("SELECT id_users FROM commentaires WHERE id = ?");
    $check->execute([$id_comment]);
    $owner = $check->fetchColumn();

    if (!$owner || $owner != $_SESSION['id']) {
        redirect("commentaire_pub.php?id=$id_publication");
    }

    // Supprime le commentaire
    $delete = $bdd->prepare("DELETE FROM commentaires WHERE id = ?");
    $delete->execute([$id_comment]);

    redirect("commentaire_pub.php?id=$id_publication&success=comment_deleted");
} else {
    redirect("dash1.php");
}
?>