-- Jalankan sekali di phpMyAdmin (tab SQL) pada database Keuangan Pribadi.
ALTER TABLE `users`
  ADD COLUMN `google_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `email`,
  ADD UNIQUE KEY `users_google_id_unique` (`google_id`);

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_27_000000_add_google_id_to_users', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
