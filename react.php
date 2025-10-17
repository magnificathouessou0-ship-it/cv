<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = $_POST['post_id'] ?? null;
    $reaction_type = $_POST['reaction_type'] ?? null;
    $user_id = $_SESSION['id'];

    if ($post_id && $reaction_type) {
        try {
            // Vérifier si l'utilisateur a déjà réagi à cette publication
            $check = $bdd->prepare("SELECT id FROM reactions WHERE id_publication = ? AND id_users = ?");
            $check->execute([$post_id, $user_id]);
            $existing_reaction = $check->fetch();

            if ($existing_reaction) {
                // Mettre à jour la réaction existante
                $update = $bdd->prepare("UPDATE reactions SET type = ? WHERE id = ?");
                $update->execute([$reaction_type, $existing_reaction['id']]);
            } else {
                // Ajouter une nouvelle réaction
                $insert = $bdd->prepare("INSERT INTO reactions (id_publication, id_users, type, date_reaction) VALUES (?, ?, ?, NOW())");
                $insert->execute([$post_id, $user_id, $reaction_type]);
            }

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Données manquantes']);
    }
}