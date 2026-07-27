-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : lun. 27 juil. 2026 à 12:13
-- Version du serveur : 8.0.41-0ubuntu0.24.04.1
-- Version de PHP : 8.4.23

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `moto_api`
--

-- --------------------------------------------------------

--
-- Structure de la table `absences`
--

CREATE TABLE `absences` (
  `id` bigint UNSIGNED NOT NULL,
  `conducteur_id` bigint UNSIGNED NOT NULL,
  `type` enum('absence','maladie','conge','accident','autorisation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nombre_jours` int UNSIGNED NOT NULL DEFAULT '0',
  `retenue` decimal(14,2) NOT NULL DEFAULT '0.00',
  `motif` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `affectations`
--

CREATE TABLE `affectations` (
  `id` bigint UNSIGNED NOT NULL,
  `moto_id` bigint UNSIGNED NOT NULL,
  `conducteur_id` bigint UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `motif_changement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

CREATE TABLE `alertes` (
  `id` bigint UNSIGNED NOT NULL,
  `type` enum('entretien_moto','assurance_expiration','vidange_necessaire','versement_retard','moto_en_panne','conducteur_absent','dette_non_remboursee') COLLATE utf8mb4_unicode_ci NOT NULL,
  `alertable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alertable_id` bigint UNSIGNED NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lue` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avances`
--

CREATE TABLE `avances` (
  `id` bigint UNSIGNED NOT NULL,
  `conducteur_id` bigint UNSIGNED NOT NULL,
  `type` enum('avance','provision') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'avance',
  `montant` decimal(14,2) NOT NULL,
  `montant_rembourse` decimal(14,2) NOT NULL DEFAULT '0.00',
  `solde` decimal(14,2) NOT NULL DEFAULT '0.00',
  `date_octroi` date NOT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `avance_remboursements`
--

CREATE TABLE `avance_remboursements` (
  `id` bigint UNSIGNED NOT NULL,
  `avance_id` bigint UNSIGNED NOT NULL,
  `montant` decimal(14,2) NOT NULL,
  `date_remboursement` date NOT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `conducteurs`
--

CREATE TABLE `conducteurs` (
  `id` bigint UNSIGNED NOT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sexe` enum('homme','femme') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cin` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_permis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_embauche` date DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_urgence_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_urgence_telephone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('actif','suspendu','inactif') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'actif',
  `moto_id` bigint UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id` bigint UNSIGNED NOT NULL,
  `moto_id` bigint UNSIGNED DEFAULT NULL,
  `date_depense` date NOT NULL,
  `categorie` enum('reparation','entretien','assurance','carburant','huile_moteur','pneus','batterie','lavage','parking','carte_grise','taxes','amendes','accessoires','divers') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(14,2) NOT NULL,
  `justificatif` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int UNSIGNED NOT NULL,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_personal_access_tokens_table', 1),
(3, '2025_01_01_000001_create_motos_table', 1),
(4, '2025_01_01_000002_create_conducteurs_table', 1),
(5, '2025_01_01_000003_create_affectations_table', 1),
(6, '2025_01_01_000004_create_versements_table', 1),
(7, '2025_01_01_000005_create_absences_table', 1),
(8, '2025_01_01_000006_create_avances_table', 1),
(9, '2025_01_01_000007_create_reparations_table', 1),
(10, '2025_01_01_000008_create_depenses_table', 1),
(11, '2025_01_01_000009_create_notifications_table', 1);

-- --------------------------------------------------------

--
-- Structure de la table `motos`
--

CREATE TABLE `motos` (
  `id` bigint UNSIGNED NOT NULL,
  `immatriculation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marque` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modele` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `couleur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annee_fabrication` smallint UNSIGNED DEFAULT NULL,
  `numero_chassis` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_moteur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_achat` date DEFAULT NULL,
  `prix_achat` decimal(14,2) DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_vehicule` enum('moto','voiture') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'moto',
  `montant_versement_mensuel` decimal(14,2) NOT NULL DEFAULT '600000.00',
  `montant_versement_journalier` decimal(14,2) NOT NULL DEFAULT '0.00',
  `statut` enum('disponible','en_circulation','en_reparation','en_entretien','accidentee','hors_service','vendue') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'disponible',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reparations`
--

CREATE TABLE `reparations` (
  `id` bigint UNSIGNED NOT NULL,
  `moto_id` bigint UNSIGNED NOT NULL,
  `date_reparation` date NOT NULL,
  `type_reparation` enum('vidange','changement_pneus','chaine','batterie','embrayage','moteur','carburateur','freins','suspension','peinture','accident','revision_complete','autres') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `garage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mecanicien` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kilometrage` int UNSIGNED DEFAULT NULL,
  `pieces_remplacees` text COLLATE utf8mb4_unicode_ci,
  `montant` decimal(14,2) NOT NULL DEFAULT '0.00',
  `photo_facture` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('administrateur','gestionnaire','comptable','consultation') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'consultation',
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `role`, `actif`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Administrateur', 'admin@moto-api.com', NULL, '$2y$12$RJ.aflr7qPFXU9gfDwRQnepT9X3bsFrAzDnbwvk7hPq7OXed7STxG', 'administrateur', 1, NULL, '2026-07-27 08:38:31', '2026-07-27 08:38:31'),
(2, 'Gestionnaire', 'gestionnaire@moto-api.com', NULL, '$2y$12$YulI4GFriYhJ2HJPdJA1xelOCskRNsddM4awQE.9q7pLUJe4SK7wW', 'gestionnaire', 1, NULL, '2026-07-27 08:38:31', '2026-07-27 08:38:31');

-- --------------------------------------------------------

--
-- Structure de la table `versements`
--

CREATE TABLE `versements` (
  `id` bigint UNSIGNED NOT NULL,
  `moto_id` bigint UNSIGNED NOT NULL,
  `conducteur_id` bigint UNSIGNED DEFAULT NULL,
  `date_versement` date NOT NULL,
  `periodicite` enum('journalier','hebdomadaire','mensuel','annuel') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'journalier',
  `montant_attendu` decimal(14,2) NOT NULL,
  `montant_verse` decimal(14,2) NOT NULL DEFAULT '0.00',
  `reste_a_payer` decimal(14,2) NOT NULL DEFAULT '0.00',
  `en_retard` tinyint(1) NOT NULL DEFAULT '0',
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `absences`
--
ALTER TABLE `absences`
  ADD PRIMARY KEY (`id`),
  ADD KEY `absences_conducteur_id_foreign` (`conducteur_id`);

--
-- Index pour la table `affectations`
--
ALTER TABLE `affectations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `affectations_moto_id_foreign` (`moto_id`),
  ADD KEY `affectations_conducteur_id_foreign` (`conducteur_id`);

--
-- Index pour la table `alertes`
--
ALTER TABLE `alertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alertes_alertable_type_alertable_id_index` (`alertable_type`,`alertable_id`);

--
-- Index pour la table `avances`
--
ALTER TABLE `avances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avances_conducteur_id_foreign` (`conducteur_id`);

--
-- Index pour la table `avance_remboursements`
--
ALTER TABLE `avance_remboursements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `avance_remboursements_avance_id_foreign` (`avance_id`);

--
-- Index pour la table `conducteurs`
--
ALTER TABLE `conducteurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `conducteurs_telephone_unique` (`telephone`),
  ADD UNIQUE KEY `conducteurs_cin_unique` (`cin`),
  ADD KEY `conducteurs_moto_id_foreign` (`moto_id`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `depenses_moto_id_date_depense_index` (`moto_id`,`date_depense`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `motos`
--
ALTER TABLE `motos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `motos_immatriculation_unique` (`immatriculation`),
  ADD UNIQUE KEY `motos_numero_chassis_unique` (`numero_chassis`),
  ADD UNIQUE KEY `motos_numero_moteur_unique` (`numero_moteur`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Index pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Index pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reparations_moto_id_foreign` (`moto_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Index pour la table `versements`
--
ALTER TABLE `versements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `versements_conducteur_id_foreign` (`conducteur_id`),
  ADD KEY `versements_moto_id_date_versement_index` (`moto_id`,`date_versement`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `absences`
--
ALTER TABLE `absences`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `affectations`
--
ALTER TABLE `affectations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `alertes`
--
ALTER TABLE `alertes`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `avances`
--
ALTER TABLE `avances`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `avance_remboursements`
--
ALTER TABLE `avance_remboursements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `conducteurs`
--
ALTER TABLE `conducteurs`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `motos`
--
ALTER TABLE `motos`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reparations`
--
ALTER TABLE `reparations`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `versements`
--
ALTER TABLE `versements`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `absences`
--
ALTER TABLE `absences`
  ADD CONSTRAINT `absences_conducteur_id_foreign` FOREIGN KEY (`conducteur_id`) REFERENCES `conducteurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `affectations`
--
ALTER TABLE `affectations`
  ADD CONSTRAINT `affectations_conducteur_id_foreign` FOREIGN KEY (`conducteur_id`) REFERENCES `conducteurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `affectations_moto_id_foreign` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avances`
--
ALTER TABLE `avances`
  ADD CONSTRAINT `avances_conducteur_id_foreign` FOREIGN KEY (`conducteur_id`) REFERENCES `conducteurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `avance_remboursements`
--
ALTER TABLE `avance_remboursements`
  ADD CONSTRAINT `avance_remboursements_avance_id_foreign` FOREIGN KEY (`avance_id`) REFERENCES `avances` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conducteurs`
--
ALTER TABLE `conducteurs`
  ADD CONSTRAINT `conducteurs_moto_id_foreign` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `depenses_moto_id_foreign` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reparations`
--
ALTER TABLE `reparations`
  ADD CONSTRAINT `reparations_moto_id_foreign` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `versements`
--
ALTER TABLE `versements`
  ADD CONSTRAINT `versements_conducteur_id_foreign` FOREIGN KEY (`conducteur_id`) REFERENCES `conducteurs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `versements_moto_id_foreign` FOREIGN KEY (`moto_id`) REFERENCES `motos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
