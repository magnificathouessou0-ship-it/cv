<?php
session_start();
require_once('config.php');
require_once('functions.php');
requireLogin();

$id = $_SESSION['id'];
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo_profil'];
        
        // Vérification du type de fichier
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $message = "<div class='alert alert-danger'>Seuls les fichiers JPEG, PNG, GIF et WebP sont autorisés.</div>";
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
            $message = "<div class='alert alert-danger'>La taille du fichier ne doit pas dépasser 5MB.</div>";
        } else {
            // Supprimer l'ancienne photo si elle existe
            $stmt = $bdd->prepare("SELECT photo_profil FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $old_photo = $stmt->fetchColumn();
            
            if ($old_photo && file_exists($old_photo)) {
                unlink($old_photo);
            }
            
            // Générer un nom de fichier unique
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profil_' . $id . '_' . time() . '.' . $extension;
            $upload_path = 'uploads/profils/' . $filename;
            
            // Créer le dossier s'il n'existe pas
            if (!is_dir('uploads/profils')) {
                mkdir('uploads/profils', 0777, true);
            }
            
            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Mettre à jour la base de données
                $update = $bdd->prepare("UPDATE users SET photo_profil = ? WHERE id = ?");
                if ($update->execute([$upload_path, $id])) {
                    $_SESSION['photo_profil'] = $upload_path;
                    $message = "<div class='alert alert-success'>Photo de profil mise à jour avec succès !</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Erreur lors de la mise à jour de la base de données.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Erreur lors du téléchargement du fichier.</div>";
            }
        }
    } elseif (isset($_POST['delete_photo'])) {
        // Supprimer la photo de profil
        $stmt = $bdd->prepare("SELECT photo_profil FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $photo = $stmt->fetchColumn();
        
        if ($photo && file_exists($photo)) {
            unlink($photo);
        }
        
        $update = $bdd->prepare("UPDATE users SET photo_profil = NULL WHERE id = ?");
        if ($update->execute([$id])) {
            $_SESSION['photo_profil'] = null;
            $message = "<div class='alert alert-success'>Photo de profil supprimée avec succès !</div>";
        }
    }
}

header("Location: profil.php?message=" . urlencode($message));
exit();
?>