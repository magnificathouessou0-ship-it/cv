<?php
session_start();
require_once('config.php');

if (!isset($_SESSION['id']) || !isset($_GET['id'])) {
    header("Location: explorer.php");
    exit();
}

$id_user = $_SESSION['id'];
$id_bloque = intval($_GET['id']);

// Vérifier si déjà bloqué
$check = $bdd->prepare("SELECT * FROM blocages WHERE id_user = ? AND id_bloque = ?");
$check->execute([$id_user, $id_bloque]);

if ($check->rowCount() == 0) {
    $insert = $bdd->prepare("INSERT INTO blocages (id_user, id_bloque) VALUES (?, ?)");
    $insert->execute([$id_user, $id_bloque]);
}

header("Location: explorer.php");
exit();