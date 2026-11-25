-- Jalankan sekali di phpMyAdmin (tab SQL) pada database Keuangan Pribadi.
ALTER TABLE `users`
  ADD COLUMN `premium_requested_at` timestamp NULL DEFAULT NULL AFTER `premium_until`,
  ADD COLUMN `premium_request_note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER `premium_requested_at`;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_25_000000_add_premium_request_to_users', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
