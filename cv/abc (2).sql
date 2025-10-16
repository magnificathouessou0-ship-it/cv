-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 15 oct. 2025 à 18:11
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `abc`
--

-- --------------------------------------------------------

--
-- Structure de la table `blocages`
--

CREATE TABLE `blocages` (
  `id` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_bloque` int(11) NOT NULL,
  `date_blocage` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `date_commentaire` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modif` timestamp NULL DEFAULT NULL,
  `id_users` int(11) NOT NULL,
  `id_publication` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commentaires`
--

INSERT INTO `commentaires` (`id`, `contenu`, `date_commentaire`, `date_modif`, `id_users`, `id_publication`) VALUES
(1, 'fffffffffff', '2025-10-15 12:38:30', NULL, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `demandes_amis`
--

CREATE TABLE `demandes_amis` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `statut` enum('en_attente','accepter','refuser') DEFAULT 'en_attente',
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demandes_amis`
--

INSERT INTO `demandes_amis` (`id`, `sender_id`, `receiver_id`, `statut`, `date_envoi`) VALUES
(1, 2, 1, 'en_attente', '2025-10-15 15:12:30');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_envoie` timestamp NOT NULL DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages_contact`
--

CREATE TABLE `messages_contact` (
  `id` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `sujet` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `id_users` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_notif` timestamp NOT NULL DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `id_users`, `message`, `date_notif`, `lu`) VALUES
(1, 1, 'Votre publication a été likée.', '2025-10-15 12:39:01', 0),
(2, 1, 'Votre publication a été dislikée.', '2025-10-15 12:39:05', 0),
(3, 1, 'Votre publication a été likée.', '2025-10-15 12:39:08', 0),
(4, 1, 'Votre publication a été dislikée.', '2025-10-15 12:39:10', 0),
(5, 1, 'Votre publication a été dislikée.', '2025-10-15 12:39:11', 0),
(6, 1, 'Votre publication a été likée.', '2025-10-15 13:24:27', 0),
(7, 1, 'Votre publication a été likée.', '2025-10-15 13:24:31', 0),
(8, 1, 'Votre publication a été likée.', '2025-10-15 13:25:57', 0),
(9, 1, 'Votre publication a été dislikée.', '2025-10-15 13:26:01', 0),
(10, 1, 'Votre publication a été dislikée.', '2025-10-15 13:50:14', 0),
(11, 1, 'Mayo DEGUENON vous a envoyé une demande d\'ami.', '2025-10-15 15:12:30', 0);

-- --------------------------------------------------------

--
-- Structure de la table `publication`
--

CREATE TABLE `publication` (
  `id` int(11) NOT NULL,
  `contenu` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `id_users` int(11) NOT NULL,
  `date_enregistr` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `publication`
--

INSERT INTO `publication` (`id`, `contenu`, `image`, `id_users`, `date_enregistr`) VALUES
(1, 'ffdddfdfdffffffff', 'img_68ef94b81a225.jpg', 1, '2025-10-15 12:34:00'),
(2, 'hjkl', 'img_68ef96213ed1d.jpg', 1, '2025-10-15 12:40:01'),
(3, 'ghjklm', '', 1, '2025-10-15 12:55:33');

-- --------------------------------------------------------

--
-- Structure de la table `reactions`
--

CREATE TABLE `reactions` (
  `id` int(11) NOT NULL,
  `id_publication` int(11) NOT NULL,
  `id_users` int(11) NOT NULL,
  `type` enum('like','dislike') NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reactions`
--

INSERT INTO `reactions` (`id`, `id_publication`, `id_users`, `type`, `create_at`) VALUES
(2, 3, 1, 'like', '2025-10-15 13:24:27'),
(3, 2, 1, 'dislike', '2025-10-15 13:50:14'),
(4, 1, 1, 'dislike', '2025-10-15 13:26:01');

-- --------------------------------------------------------

--
-- Structure de la table `signalement_comment`
--

CREATE TABLE `signalement_comment` (
  `id` int(11) NOT NULL,
  `id_comment` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_signalement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `signalement_pub`
--

CREATE TABLE `signalement_pub` (
  `id` int(11) NOT NULL,
  `id_pub` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `date_signalement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `tel` varchar(20) DEFAULT NULL,
  `sexe` enum('Masculin','Feminin') NOT NULL,
  `date_naissance` date NOT NULL,
  `adresse` text DEFAULT NULL,
  `mdp` varchar(255) NOT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expiration` datetime DEFAULT NULL,
  `date_inscription` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `tel`, `sexe`, `date_naissance`, `adresse`, `mdp`, `reset_token`, `reset_expiration`, `date_inscription`) VALUES
(1, 'vvv', 'cc', 'momo@gmail.com', '44548696', 'Feminin', '2000-10-15', 'ccccc', '$2y$10$Z5gCChhPEQZGe/jO6Z/LseKDRWW3YnooE5Buo0XbXVzHxH4jrElpK', NULL, NULL, '2025-10-15 12:26:03'),
(2, 'DEGUENON', 'Mayo', 'toto@gmail.com', '445486961', 'Masculin', '2000-07-14', 'ffffffff', '$2y$10$lsqaJAc6cycnDBMPgHXDzu1EeVk/UtGc/gFkldKTWTGuIgJk8Gqtm', NULL, NULL, '2025-10-15 15:11:31');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `blocages`
--
ALTER TABLE `blocages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_blocage` (`id_user`,`id_bloque`),
  ADD KEY `id_bloque` (`id_bloque`);

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_users` (`id_users`),
  ADD KEY `id_publication` (`id_publication`);

--
-- Index pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_demande` (`sender_id`,`receiver_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Index pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_user` (`id_user`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_users` (`id_users`);

--
-- Index pour la table `publication`
--
ALTER TABLE `publication`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_users` (`id_users`);

--
-- Index pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_reaction` (`id_publication`,`id_users`),
  ADD KEY `id_users` (`id_users`);

--
-- Index pour la table `signalement_comment`
--
ALTER TABLE `signalement_comment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_signalement_comment` (`id_comment`,`id_user`),
  ADD KEY `id_user` (`id_user`);

--
-- Index pour la table `signalement_pub`
--
ALTER TABLE `signalement_pub`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_signalement_pub` (`id_pub`,`id_user`),
  ADD KEY `id_user` (`id_user`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `blocages`
--
ALTER TABLE `blocages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `publication`
--
ALTER TABLE `publication`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `signalement_comment`
--
ALTER TABLE `signalement_comment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `signalement_pub`
--
ALTER TABLE `signalement_pub`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `blocages`
--
ALTER TABLE `blocages`
  ADD CONSTRAINT `blocages_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blocages_ibfk_2` FOREIGN KEY (`id_bloque`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandes_amis`
--
ALTER TABLE `demandes_amis`
  ADD CONSTRAINT `demandes_amis_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `demandes_amis_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages_contact`
--
ALTER TABLE `messages_contact`
  ADD CONSTRAINT `messages_contact_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `publication`
--
ALTER TABLE `publication`
  ADD CONSTRAINT `publication_ibfk_1` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`id_publication`) REFERENCES `publication` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reactions_ibfk_2` FOREIGN KEY (`id_users`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `signalement_comment`
--
ALTER TABLE `signalement_comment`
  ADD CONSTRAINT `signalement_comment_ibfk_1` FOREIGN KEY (`id_comment`) REFERENCES `commentaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `signalement_comment_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `signalement_pub`
--
ALTER TABLE `signalement_pub`
  ADD CONSTRAINT `signalement_pub_ibfk_1` FOREIGN KEY (`id_pub`) REFERENCES `publication` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `signalement_pub_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
