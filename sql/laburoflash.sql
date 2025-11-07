-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-11-2025 a las 22:20:09
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `laburoflash`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `completion_disputes`
--

CREATE TABLE `completion_disputes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `initiated_by` enum('worker','employer') NOT NULL,
  `worker_message` text DEFAULT NULL,
  `employer_message` text DEFAULT NULL,
  `status` enum('pending','resolved','escalated') NOT NULL DEFAULT 'pending',
  `resolved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `completion_disputes`
--

INSERT INTO `completion_disputes` (`id`, `task_id`, `worker_id`, `employer_id`, `initiated_by`, `worker_message`, `employer_message`, `status`, `resolved_by`, `created_at`, `updated_at`) VALUES
(1, 6, 5, 1, 'worker', 'Trabajo completado según lo acordado.', NULL, 'resolved', 1, '2025-10-31 20:29:56', '2025-10-31 20:30:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locations`
--

CREATE TABLE `locations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `label` varchar(80) DEFAULT NULL,
  `address_text` varchar(200) NOT NULL,
  `city` varchar(80) NOT NULL,
  `province` varchar(80) NOT NULL,
  `country` varchar(2) NOT NULL DEFAULT 'AR',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `locations`
--

INSERT INTO `locations` (`id`, `user_id`, `label`, `address_text`, `city`, `province`, `country`, `created_at`) VALUES
(1, NULL, NULL, 'Ciudad de Buenos Aires', 'Buenos Aires', 'CABA', 'AR', '2025-10-21 20:16:29'),
(2, NULL, NULL, 'Provincia de Buenos Aires', 'La Plata', 'Buenos Aires', 'AR', '2025-10-21 20:16:29'),
(3, NULL, NULL, 'La Plata', 'La Plata', 'Buenos Aires', 'AR', '2025-10-21 20:16:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reviews`
--

CREATE TABLE `reviews` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `from_user_id` bigint(20) UNSIGNED NOT NULL,
  `to_user_id` bigint(20) UNSIGNED NOT NULL,
  `role_from` enum('employer','worker') NOT NULL,
  `rating` tinyint(3) UNSIGNED NOT NULL CHECK (`rating` between 1 and 5),
  `comment` varchar(500) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `status` enum('active','past_due','canceled') NOT NULL DEFAULT 'active',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `plan_type` enum('worker_premium','employer_premium') DEFAULT 'worker_premium',
  `price` decimal(10,2) DEFAULT 15000.00,
  `currency` varchar(3) DEFAULT 'ARS',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `renewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `user_id`, `status`, `started_at`, `expires_at`, `plan_type`, `price`, `currency`, `payment_method`, `payment_reference`, `renewed_at`) VALUES
(1, 5, 'active', '2025-11-07 21:21:09', '2025-12-07 21:21:09', 'worker_premium', 15000.00, '0', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sub_categories`
--

CREATE TABLE `sub_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sub_categories`
--

INSERT INTO `sub_categories` (`id`, `name`, `category_id`, `created_at`) VALUES
(1, 'Jardinería', 1, '2025-10-21 23:16:29'),
(2, 'Mantenimiento', 1, '2025-10-21 23:16:29'),
(3, 'Reparaciones', 2, '2025-10-21 23:16:29'),
(4, 'Organización', 2, '2025-10-21 23:16:29'),
(5, 'Otros servicios', 5, '2025-10-21 23:16:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tasks`
--

CREATE TABLE `tasks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `employer_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(120) NOT NULL,
  `description` text NOT NULL,
  `category_id` smallint(5) UNSIGNED DEFAULT NULL,
  `sub_categories_id` int(11) DEFAULT NULL,
  `location_id` bigint(20) UNSIGNED DEFAULT NULL,
  `address_text` varchar(200) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `duration_hours` decimal(4,2) DEFAULT NULL,
  `status` enum('draft','published','assigned','in_progress','completed','cancelled','expired') NOT NULL DEFAULT 'published',
  `is_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tasks`
--

INSERT INTO `tasks` (`id`, `employer_id`, `title`, `description`, `category_id`, `sub_categories_id`, `location_id`, `address_text`, `scheduled_at`, `duration_hours`, `status`, `is_paid`, `created_at`, `published_at`, `expires_at`) VALUES
(2, 1, 'Cortar pasto en jardín', 'Necesito cortar el pasto de mi jardín de 200m2. Debe estar bien cortado y ordenado.', 1, 1, 1, 'Av. Corrientes 1234', NULL, 2.50, 'assigned', 0, '2025-10-21 20:16:29', '2025-10-21 20:16:29', NULL),
(3, 1, 'Limpieza de casa', 'Limpieza general de casa de 3 habitaciones. Incluye cocina, baños y living.', 2, 4, 2, 'Calle Falsa 456', NULL, 4.00, 'published', 0, '2025-10-21 20:16:29', '2025-10-21 20:16:29', NULL),
(4, 1, 'Pasear perro', 'Necesito alguien que pasee a mi perro Golden Retriever por 1 hora diaria.', 3, 5, 3, 'Plaza San Martín', NULL, 1.00, 'published', 0, '2025-10-21 20:16:29', '2025-10-21 20:16:29', NULL),
(5, 1, 'asdadsas', 'aaadsadadadsaa', 1, 1, 1, 'asdadasas', NULL, 2.00, 'completed', 0, '2025-10-21 20:16:59', '2025-10-21 20:16:59', NULL),
(6, 1, 'asddsd', 'este trabajo es de limpiar y organizar mi cocina', 2, 4, 3, 'Calle peronista', NULL, 2.00, 'completed', 0, '2025-10-31 20:27:39', '2025-10-31 20:27:39', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_assignments`
--

CREATE TABLE `task_assignments` (
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `response_id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED NOT NULL,
  `assigned_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('assigned','in_progress','pending_completion','disputed','done','cancelled') NOT NULL DEFAULT 'assigned',
  `completed_at` datetime DEFAULT NULL,
  `confirmed_paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_assignments`
--

INSERT INTO `task_assignments` (`task_id`, `response_id`, `worker_id`, `assigned_at`, `status`, `completed_at`, `confirmed_paid_at`) VALUES
(2, 3, 5, '2025-11-07 17:20:44', 'assigned', NULL, NULL),
(5, 1, 5, '2025-10-31 19:34:17', 'done', '2025-10-31 19:35:49', NULL),
(6, 2, 5, '2025-10-31 20:29:21', 'done', '2025-10-31 20:29:56', '2025-10-31 20:30:24');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_categories`
--

CREATE TABLE `task_categories` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_categories`
--

INSERT INTO `task_categories` (`id`, `name`) VALUES
(1, 'Cortar pasto'),
(4, 'Lavado de auto'),
(2, 'Limpieza'),
(5, 'Otros'),
(3, 'Pasear mascotas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `task_responses`
--

CREATE TABLE `task_responses` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `task_id` bigint(20) UNSIGNED NOT NULL,
  `worker_id` bigint(20) UNSIGNED NOT NULL,
  `message` varchar(240) DEFAULT NULL,
  `status` enum('requested','withdrawn','rejected','selected') NOT NULL DEFAULT 'requested',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `task_responses`
--

INSERT INTO `task_responses` (`id`, `task_id`, `worker_id`, `message`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 5, '', 'selected', '2025-10-31 19:21:21', '2025-10-31 19:34:17'),
(2, 6, 5, 'quiero el laburo soy responsable y se limpiar bien', 'selected', '2025-10-31 20:28:36', '2025-10-31 20:29:21'),
(3, 2, 5, NULL, 'selected', '2025-11-07 17:20:13', '2025-11-07 17:20:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  `last_name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(15) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_login_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `name`, `last_name`, `email`, `password`, `phone_number`, `is_active`, `created_at`, `last_login_at`) VALUES
(1, 'Facundo', 'Villa', 'asd@gmail.com', '$2y$10$DHp4dtaiiCxWzN2Id.ysUOHQ.FI6fc/5dYVblVuz8VzNyma6XXMh6', '1166646665', 1, '2025-10-21 20:01:11', NULL),
(2, 'asdas', 'dsadsa', 'dsadsadsa@gmail.com', '$2y$10$L1ncmAcAjaTnmiSyvYVke.5kwBplcYExq3jNR.FlU7dhgVzbbr0Jm', '11111111', 1, '2025-10-31 18:34:16', NULL),
(3, 'ASDSAS', 'dsada', '123@gmail.com', '$2y$10$ltjUm7fwLzYXM210CTNNce6wGOGKFMxhR/e4bk0S.oZ3RqYbJ2GBW', '12345678', 1, '2025-10-31 19:00:57', NULL),
(5, 'asdf', 'asdf', 'asdd@gmail.com', '$2y$10$nU3p.mi3E/d9slCd9g8EbuMlWWWwIIDBZmFglW7.5.Iq6vj3K1YwO', '123456781', 1, '2025-10-31 19:16:12', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `vtype` enum('email','phone','dni') NOT NULL,
  `status` enum('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `doc_number` varchar(32) DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `worker_profiles`
--

CREATE TABLE `worker_profiles` (
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `bio` varchar(300) DEFAULT NULL,
  `role` enum('Empleador','Trabajador') DEFAULT NULL,
  `skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`skills`)),
  `rating_avg` decimal(3,2) NOT NULL DEFAULT 0.00,
  `completed_cnt` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `zone_hint` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `completion_disputes`
--
ALTER TABLE `completion_disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `worker_id` (`worker_id`),
  ADD KEY `employer_id` (`employer_id`);

--
-- Indices de la tabla `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `city` (`city`,`province`),
  ADD KEY `fk_loc_user` (`user_id`);

--
-- Indices de la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_one_review_per_pair` (`task_id`,`from_user_id`,`to_user_id`),
  ADD KEY `to_user_id` (`to_user_id`,`rating`),
  ADD KEY `fk_rev_from` (`from_user_id`);

--
-- Indices de la tabla `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_active` (`user_id`,`status`);

--
-- Indices de la tabla `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indices de la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`,`scheduled_at`),
  ADD KEY `fk_task_emp` (`employer_id`),
  ADD KEY `fk_task_cat` (`category_id`),
  ADD KEY `fk_task_loc` (`location_id`),
  ADD KEY `fk_task_subcat` (`sub_categories_id`);

--
-- Indices de la tabla `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `fk_ta_resp` (`response_id`),
  ADD KEY `fk_ta_worker` (`worker_id`);

--
-- Indices de la tabla `task_categories`
--
ALTER TABLE `task_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indices de la tabla `task_responses`
--
ALTER TABLE `task_responses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_task_worker` (`task_id`,`worker_id`),
  ADD KEY `task_id` (`task_id`,`status`),
  ADD KEY `fk_tr_worker` (`worker_id`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_e164` (`phone_number`),
  ADD KEY `created_at` (`created_at`);

--
-- Indices de la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_user_vtype` (`user_id`,`vtype`);

--
-- Indices de la tabla `worker_profiles`
--
ALTER TABLE `worker_profiles`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `completion_disputes`
--
ALTER TABLE `completion_disputes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `locations`
--
ALTER TABLE `locations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `task_categories`
--
ALTER TABLE `task_categories`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `task_responses`
--
ALTER TABLE `task_responses`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `completion_disputes`
--
ALTER TABLE `completion_disputes`
  ADD CONSTRAINT `fk_dispute_employer` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_dispute_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_dispute_worker` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `locations`
--
ALTER TABLE `locations`
  ADD CONSTRAINT `fk_loc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `fk_rev_from` FOREIGN KEY (`from_user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_rev_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `fk_rev_to` FOREIGN KEY (`to_user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `fk_task_cat` FOREIGN KEY (`category_id`) REFERENCES `task_categories` (`id`),
  ADD CONSTRAINT `fk_task_emp` FOREIGN KEY (`employer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_task_loc` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `fk_task_subcat` FOREIGN KEY (`sub_categories_id`) REFERENCES `sub_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `fk_ta_resp` FOREIGN KEY (`response_id`) REFERENCES `task_responses` (`id`),
  ADD CONSTRAINT `fk_ta_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `fk_ta_worker` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `task_responses`
--
ALTER TABLE `task_responses`
  ADD CONSTRAINT `fk_tr_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`),
  ADD CONSTRAINT `fk_tr_worker` FOREIGN KEY (`worker_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD CONSTRAINT `fk_uv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Filtros para la tabla `worker_profiles`
--
ALTER TABLE `worker_profiles`
  ADD CONSTRAINT `fk_wp_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
