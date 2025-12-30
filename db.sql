/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_password_idx` (`login`,`password`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20251223230037', '2025-12-24 01:00:45', 15);

INSERT INTO `user` (`id`, `login`, `phone`, `roles`, `password`) VALUES
(1, 'root', '12345678', '[\"ROLE_USER\", \"ROLE_ROOT\"]', '$2y$13$hiSRi2yWUcejHMEHr.rgEOjBNqQ1vnhQLPdTCQucvF.N3H1gMeAPW'),
(2, 'user1', '1234567', '[\"ROLE_USER\"]', '$2y$13$mnmhEHl8CJE8m4p.MVkrPuFvudXJeqm4AFMEJNHrWEDrJClaQjPmG'),
(3, 'user2', '33333', '[\"ROLE_USER\"]', '$2y$13$ZJW04ccRAYLQWN.3Ls35e.g/E/b2oOPfk.OlX8GMoBOD7RILmeSQm'),
(4, 'user3n', '555', '[\"ROLE_USER\"]', '$2y$13$GtP5xsqRiUPP7qjaxrHQOePUJFOHFQd8CAMhMsc87w/0/RvPjlmpm'),
(5, 'user3', '444', '[\"ROLE_USER\"]', '$2y$13$d1FV7e/tNRw87/rRpBuhBe9C7R.cja8PHbQsRBYnhJoDTrq6C9qTG'),
(6, 'user4', '444', '[\"ROLE_USER\"]', '$2y$13$VxwJyyQ5eE4MADIJXjf83.HN85M.TuLc11r/iX69Kkub6pMJrYsk6'),
(7, 'user4', '444', '[\"ROLE_USER\"]', '$2y$13$y5ofjo12R5Qv6Dsklwr4iuAF4tLGG5ydQ1X.D9c27vGMldcecf9h.'),
(8, 'user4', '444', '[\"ROLE_USER\"]', '$2y$13$3zDhHcA/UxZChjiZxntYTulXPZB1UQCi/jsSNYdKxTM7JIopav/7C');



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
