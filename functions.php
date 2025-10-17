<?php
// functions.php

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function requireLogin() {
    if (!isset($_SESSION['id'])) {
        redirect('login.php');
    }
}

function isLoggedIn() {
    return isset($_SESSION['id']);
}

function getUserInfo($bdd, $userId) {
    $req = $bdd->prepare('SELECT * FROM users WHERE id = ?');
    $req->execute([$userId]);
    return $req->fetch(PDO::FETCH_ASSOC);
}
function getUserPhoto($user_id, $bdd) {
    $stmt = $bdd->prepare("SELECT photo_profil FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $photo = $stmt->fetchColumn();
    
    return $photo ?: null;
}

function displayUserPhoto($photo, $size = 'sm', $alt = 'Photo de profil') {
    if ($photo && file_exists($photo)) {
        return '<img src="' . htmlspecialchars($photo) . '" alt="' . htmlspecialchars($alt) . '" class="user-photo-' . $size . '">';
    } else {
        return '<i class="fas fa-user-circle text-muted user-photo-' . $size . '"></i>';
    }
}
?>
