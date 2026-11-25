-- Jalankan sekali di phpMyAdmin (tab SQL) pada database Keuangan Pribadi.
CREATE TABLE `crash_reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `android` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stack` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `occurred_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crash_reports_user_id_foreign` (`user_id`),
  KEY `crash_reports_created_at_index` (`created_at`),
  CONSTRAINT `crash_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_26_000000_create_crash_reports_table', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
