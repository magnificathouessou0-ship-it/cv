<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id_user = $_SESSION['id'];
$id_publication = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$type = isset($_GET['type']) ? $_GET['type'] : '';

if (!$id_publication || !in_array($type, ['like', 'dislike'])) {
    header('Location: dash1.php');
    exit;
}

// Vérifier si une réaction existe déjà
$check = $bdd->prepare("SELECT * FROM reactions WHERE id_users = ? AND id_publication = ?");
$check->execute([$id_user, $id_publication]);
$existing = $check->fetch();

if ($existing) {
    if ($existing['type'] === $type) {
        // Si l'utilisateur clique à nouveau sur le même type → suppression (annuler la réaction)
        $delete = $bdd->prepare("DELETE FROM reactions WHERE id_users = ? AND id_publication = ?");
        $delete->execute([$id_user, $id_publication]);
    } else {
        // Si l'utilisateur change de type → mise à jour (like ↔ dislike)
        $update = $bdd->prepare("UPDATE reactions SET type = ? WHERE id_users = ? AND id_publication = ?");
        $update->execute([$type, $id_user, $id_publication]);
    }
} else {
    // Aucune réaction → on insère une nouvelle
    $insert = $bdd->prepare("INSERT INTO reactions (id_users, id_publication, type) VALUES (?, ?, ?)");
    $insert->execute([$id_user, $id_publication, $type]);
}

// Retour à la page d’origine
header('Location: dash1.php');
exit;
?>
