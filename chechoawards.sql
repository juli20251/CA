-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2025 a las 22:58:59
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `chechoawards`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `visible` tinyint(1) DEFAULT 0,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `visible`, `orden`) VALUES
(5, 'Mejor Jugador del Año – Haxball', '', 1, 1),
(6, 'Mejor Jugador - Minecraft', '', 1, 2),
(7, 'Mejor Jugador - Pes', '', 1, 3),
(8, 'Mejor Robador de Brainrots', '', 1, 4),
(9, 'Juego del Año', '', 1, 5),
(10, 'Boludeado del Año', '', 1, 6),
(11, 'Humorista del Año', '', 1, 7),
(12, 'Dupla del Año', '', 1, 8),
(13, 'Humilde del Año', '', 1, 9),
(14, 'OG del Año', '', 1, 10),
(15, 'Clip del Año', '', 1, 11),
(16, 'Vip del Año', '', 1, 12),
(17, 'Donador del Año', '', 1, 13),
(18, 'Evento del Año', '', 1, 14),
(19, 'Promesa del Año', '', 1, 15),
(20, 'Extranjero del Año', '', 1, 16),
(21, 'Moderador del Año', '', 1, 17),
(22, 'Viewer del Año', '', 1, 18);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `id` int(11) NOT NULL,
  `clave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `clave`, `valor`, `descripcion`) VALUES
(1, 'votaciones_abiertas', 'true', 'Estado de las votaciones'),
(2, 'mostrar_ternas', 'true', 'Mostrar u ocultar ternas'),
(3, 'fecha_apertura', NULL, 'Fecha programada para abrir votaciones'),
(4, 'fecha_cierre', NULL, 'Fecha programada para cerrar votaciones'),
(5, 'fecha_revelacion', '2025-12-06 01:30:00', 'Fecha programada para revelar ternas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nominados`
--

CREATE TABLE `nominados` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `imagen_url` varchar(255) DEFAULT NULL,
  `tipo_media` enum('imagen','video') DEFAULT 'imagen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `nominados`
--

INSERT INTO `nominados` (`id`, `categoria_id`, `nombre`, `descripcion`, `imagen_url`, `tipo_media`) VALUES
(8, 6, 'MARKI', 'guapo, gracioso, 1.87m y fueguino', 'http://localhost/chechoawards/uploads/img_692e27c8aeb3f_1764632520.webp', 'imagen'),
(9, 11, 'MARKI', 'guapo, gracioso, 1.87m y fueguino', 'http://localhost/chechoawards/uploads/img_692e2884cc656_1764632708.webp', 'imagen'),
(10, 19, 'MARKI', 'guapo, gracioso, 1.87m y fueguino', 'http://localhost/chechoawards/uploads/img_692e2a7bcf359_1764633211.webp', 'imagen'),
(11, 13, 'DANI', 'Tifosi (por desgracia)', 'http://localhost/chechoawards/uploads/img_692e2aae0c2c2_1764633262.webp', 'imagen'),
(12, 21, 'DANI', 'Tifosi (por desgracia)', 'http://localhost/chechoawards/uploads/img_692e2ad658efd_1764633302.webp', 'imagen'),
(13, 5, 'BLASSY', 'Checho estoy enojado 😠', 'http://localhost/chechoawards/uploads/img_692e2b226e68a_1764633378.webp', 'imagen'),
(14, 13, 'BLASSY', 'Checho estoy enojado 😠', 'http://localhost/chechoawards/uploads/img_692e2bed54e7a_1764633581.webp', 'imagen'),
(15, 19, 'BLASSY', 'Checho estoy enojado 😠', 'http://localhost/chechoawards/uploads/img_692e2bfe3b753_1764633598.webp', 'imagen'),
(16, 5, 'NEX', 'sexy, locutor, 1.82 y italiano', 'http://localhost/chechoawards/uploads/img_692e2cb75129b_1764633783.webp', 'imagen'),
(18, 8, 'NEX', 'sexy, locutor, 1.82 y italiano', 'http://localhost/chechoawards/uploads/img_692e2cf3489a9_1764633843.webp', 'imagen'),
(19, 6, 'NEX', 'sexy, locutor, 1.82 y italiano', 'http://localhost/chechoawards/uploads/img_692e2d5bc9e71_1764633947.webp', 'imagen'),
(20, 21, 'NEX', 'sexy, locutor, 1.82 y italiano', 'http://localhost/chechoawards/uploads/img_692e2d7191f07_1764633969.webp', 'imagen'),
(21, 22, 'NEX', 'sexy, locutor, 1.82 y italiano', 'http://localhost/chechoawards/uploads/img_692e2d81db126_1764633985.webp', 'imagen'),
(22, 5, 'NIJIKA', 'soy racista xenofobo y homofobico', 'http://localhost/chechoawards/uploads/img_692e2df639b6d_1764634102.webp', 'imagen'),
(23, 5, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e326755a02_1764635239.webp', 'imagen'),
(24, 6, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e3295200fb_1764635285.webp', 'imagen'),
(25, 11, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e32a1e3585_1764635297.webp', 'imagen'),
(26, 16, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e32b34a49a_1764635315.webp', 'imagen'),
(27, 17, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e32c85db90_1764635336.webp', 'imagen'),
(28, 22, 'JOKKER', '', 'http://localhost/chechoawards/uploads/img_692e32d5b06ea_1764635349.webp', 'imagen'),
(29, 18, 'MARKIVENTURAS', 'Las markiventuras son una serie hecha por el chechista \"Marki\", empezo como una serie chiste, sin una historia muy complicada, pero por el exito de el primer episodio, marki decidio continuar la serie pero dandole una historia compleja y con muchas referencias, hasta lo que es hoy.', 'http://localhost/chechoawards/uploads/img_692e336bafc89_1764635499.webp', 'imagen');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('user','admin') DEFAULT 'user',
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `fecha_registro`) VALUES
(1, 'Administrador', 'admin@chechoawards.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2025-12-01 19:00:53'),
(2, 'juli', 'juli@gmail.com', '$2y$10$AYjFsA83Ob/mn5jBC1I/peQOFVME/CzGxZNU2vbkLaflBqxiz2Vxq', 'user', '2025-12-01 23:31:01'),
(3, 'prueba', 'prueba@gmail.com', '123456', 'admin', '2025-12-04 21:27:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `votos`
--

CREATE TABLE `votos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nominado_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `fecha_voto` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `votos`
--

INSERT INTO `votos` (`id`, `usuario_id`, `nominado_id`, `categoria_id`, `fecha_voto`) VALUES
(1, 2, 22, 5, '2025-12-04 21:53:20'),
(2, 2, 25, 11, '2025-12-04 21:55:06'),
(3, 2, 11, 13, '2025-12-04 21:55:09'),
(4, 2, 26, 16, '2025-12-04 21:55:11'),
(5, 2, 27, 17, '2025-12-04 21:55:16'),
(6, 2, 29, 18, '2025-12-04 21:55:18'),
(7, 2, 15, 19, '2025-12-04 21:55:20'),
(8, 2, 12, 21, '2025-12-04 21:55:28'),
(9, 2, 18, 8, '2025-12-04 21:55:36'),
(10, 2, 24, 6, '2025-12-04 21:55:39');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `clave` (`clave`);

--
-- Indices de la tabla `nominados`
--
ALTER TABLE `nominados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `votos`
--
ALTER TABLE `votos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `voto_unico` (`usuario_id`,`categoria_id`),
  ADD KEY `nominado_id` (`nominado_id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `nominados`
--
ALTER TABLE `nominados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `votos`
--
ALTER TABLE `votos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `nominados`
--
ALTER TABLE `nominados`
  ADD CONSTRAINT `nominados_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `votos`
--
ALTER TABLE `votos`
  ADD CONSTRAINT `votos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votos_ibfk_2` FOREIGN KEY (`nominado_id`) REFERENCES `nominados` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `votos_ibfk_3` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
