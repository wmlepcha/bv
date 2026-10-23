-- Live update 2026-10-19. Additive only. Take a phpMyAdmin export first. Run ONCE.
-- users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `avatar_animal` varchar(255) NULL AFTER `avatar_path`,
  ADD COLUMN IF NOT EXISTS `whatsapp_number` varchar(255) NULL;

-- student_profiles
ALTER TABLE `student_profiles`
  ADD COLUMN IF NOT EXISTS `placement_status` varchar(255) NOT NULL DEFAULT 'open',
  ADD COLUMN IF NOT EXISTS `professions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(`professions`)),
  ADD COLUMN IF NOT EXISTS `registration_purpose` varchar(255) NOT NULL DEFAULT 'training',
  ADD COLUMN IF NOT EXISTS `placement_requested_at` timestamp NULL,
  ADD INDEX IF NOT EXISTS `student_profiles_placement_status_index` (`placement_status`),
  ADD INDEX IF NOT EXISTS `student_profiles_registration_purpose_index` (`registration_purpose`);

-- batch_enrollments
ALTER TABLE `batch_enrollments`
  ADD COLUMN IF NOT EXISTS `attendance_alert_sent_at` timestamp NULL;

-- payments (existing rows get channel = 'online')
ALTER TABLE `payments`
  ADD COLUMN IF NOT EXISTS `channel` varchar(255) NOT NULL DEFAULT 'online',
  ADD COLUMN IF NOT EXISTS `collected_by_user_id` bigint unsigned NULL,
  ADD COLUMN IF NOT EXISTS `notes` text NULL,
  ADD COLUMN IF NOT EXISTS `voided_at` timestamp NULL,
  ADD COLUMN IF NOT EXISTS `voided_by_user_id` bigint unsigned NULL,
  ADD COLUMN IF NOT EXISTS `void_reason` text NULL,
  ADD COLUMN IF NOT EXISTS `center_id` bigint unsigned NULL,
  ADD INDEX IF NOT EXISTS `payments_channel_index` (`channel`),
  ADD CONSTRAINT `payments_collected_by_user_id_foreign` FOREIGN KEY (`collected_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_voided_by_user_id_foreign` FOREIGN KEY (`voided_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_center_id_foreign` FOREIGN KEY (`center_id`) REFERENCES `centers` (`id`) ON DELETE SET NULL;

-- recruiter projects
CREATE TABLE IF NOT EXISTS `recruiter_projects` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `sector` varchar(255) NOT NULL,
  `timeline` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `finalized_at` timestamp NULL DEFAULT NULL,
  `requirement_template` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`requirement_template`)),
  `recruiter_updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `recruiter_projects_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recruiter_project_candidates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recruiter_project_id` bigint unsigned NOT NULL,
  `student_profile_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned NULL DEFAULT NULL,
  `congratulated_at` timestamp NULL DEFAULT NULL,
  `requirements_sent_at` timestamp NULL DEFAULT NULL,
  `sent_to_recruiter_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_candidate_unique` (`recruiter_project_id`,`student_profile_id`),
  KEY `recruiter_project_candidates_student_profile_id_foreign` (`student_profile_id`),
  KEY `recruiter_project_candidates_approved_by_foreign` (`approved_by`),
  CONSTRAINT `recruiter_project_candidates_recruiter_project_id_foreign` FOREIGN KEY (`recruiter_project_id`) REFERENCES `recruiter_projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recruiter_project_candidates_student_profile_id_foreign` FOREIGN KEY (`student_profile_id`) REFERENCES `student_profiles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recruiter_project_candidates_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `project_requirements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `recruiter_project_candidate_id` bigint unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) NULL DEFAULT NULL,
  `original_name` varchar(255) NULL DEFAULT NULL,
  `uploaded_by` bigint unsigned NULL DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `review_status` varchar(255) NOT NULL DEFAULT 'pending',
  `rejection_note` text NULL,
  `sample_path` varchar(255) NULL DEFAULT NULL,
  `sample_name` varchar(255) NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint unsigned NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_requirements_recruiter_project_candidate_id_foreign` (`recruiter_project_candidate_id`),
  KEY `project_requirements_uploaded_by_foreign` (`uploaded_by`),
  KEY `project_requirements_reviewed_by_foreign` (`reviewed_by`),
  CONSTRAINT `project_requirements_recruiter_project_candidate_id_foreign` FOREIGN KEY (`recruiter_project_candidate_id`) REFERENCES `recruiter_project_candidates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_requirements_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_requirements_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- attendance
CREATE TABLE IF NOT EXISTS `attendance_sessions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `batch_id` bigint unsigned NOT NULL,
  `opened_by` bigint unsigned NULL DEFAULT NULL,
  `secret` varchar(64) NOT NULL,
  `ttl_seconds` smallint unsigned NOT NULL DEFAULT 15,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_sessions_batch_id_closed_at_index` (`batch_id`,`closed_at`),
  KEY `attendance_sessions_opened_by_foreign` (`opened_by`),
  CONSTRAINT `attendance_sessions_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_sessions_opened_by_foreign` FOREIGN KEY (`opened_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `status` varchar(255) NOT NULL,
  `method` varchar(255) NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `marked_by` bigint unsigned NULL DEFAULT NULL,
  `note` varchar(255) NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `attendance_records_attendance_session_id_student_id_unique` (`attendance_session_id`,`student_id`),
  KEY `attendance_records_student_id_foreign` (`student_id`),
  KEY `attendance_records_marked_by_foreign` (`marked_by`),
  CONSTRAINT `attendance_records_attendance_session_id_foreign` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_records_marked_by_foreign` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `attendance_record_changes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attendance_session_id` bigint unsigned NOT NULL,
  `student_id` bigint unsigned NOT NULL,
  `changed_by` bigint unsigned NULL DEFAULT NULL,
  `from_status` varchar(255) NULL DEFAULT NULL,
  `to_status` varchar(255) NULL DEFAULT NULL,
  `reason` varchar(255) NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attendance_record_changes_attendance_session_id_foreign` (`attendance_session_id`),
  KEY `attendance_record_changes_student_id_foreign` (`student_id`),
  KEY `attendance_record_changes_changed_by_foreign` (`changed_by`),
  CONSTRAINT `attendance_record_changes_attendance_session_id_foreign` FOREIGN KEY (`attendance_session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_record_changes_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `attendance_record_changes_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- leave requests
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `student_id` bigint unsigned NOT NULL,
  `batch_id` bigint unsigned NOT NULL,
  `from_date` date NOT NULL,
  `to_date` date NOT NULL,
  `reason` varchar(500) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `decided_by` bigint unsigned NULL DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `decision_note` varchar(255) NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `leave_requests_student_id_status_index` (`student_id`,`status`),
  KEY `leave_requests_batch_id_status_index` (`batch_id`,`status`),
  KEY `leave_requests_decided_by_foreign` (`decided_by`),
  CONSTRAINT `leave_requests_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `leave_requests_decided_by_foreign` FOREIGN KEY (`decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- record the 17 migrations as run (skips any already present)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT v.m, (SELECT COALESCE(MAX(`batch`),0)+1 FROM (SELECT `batch` FROM `migrations`) AS b)
FROM (
  SELECT '2026_10_01_053651_ensure_dashboard_activated_at_on_users_table' AS m UNION ALL
  SELECT '2026_10_11_065533_add_placement_fields_to_student_profiles_table' UNION ALL
  SELECT '2026_10_11_083424_create_recruiter_projects_table' UNION ALL
  SELECT '2026_10_11_083425_create_recruiter_project_candidates_table' UNION ALL
  SELECT '2026_10_11_105344_add_avatar_animal_to_users_table' UNION ALL
  SELECT '2026_10_11_114206_add_finalize_fields_to_recruiter_projects' UNION ALL
  SELECT '2026_10_11_114207_add_approval_to_recruiter_project_candidates' UNION ALL
  SELECT '2026_10_11_114208_create_project_requirements_table' UNION ALL
  SELECT '2026_10_11_124927_add_messaging_timestamps_to_recruiter_project_candidates' UNION ALL
  SELECT '2026_10_11_132157_create_attendance_tables' UNION ALL
  SELECT '2026_10_11_134916_create_leave_requests_table' UNION ALL
  SELECT '2026_10_15_062736_add_review_fields_to_project_requirements_table' UNION ALL
  SELECT '2026_10_15_062736_add_sent_to_recruiter_to_recruiter_project_candidates' UNION ALL
  SELECT '2026_10_15_063138_add_whatsapp_number_to_users_table' UNION ALL
  SELECT '2026_10_15_101615_add_registration_purpose_to_student_profiles_table' UNION ALL
  SELECT '2026_10_16_081353_add_offline_fields_to_payments_table' UNION ALL
  SELECT '2026_10_16_093258_add_center_id_to_payments_table'
) AS v
WHERE NOT EXISTS (SELECT 1 FROM `migrations` x WHERE x.`migration` = v.m);
