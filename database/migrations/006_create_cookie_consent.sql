-- Cookie consent log for GDPR compliance
CREATE TABLE IF NOT EXISTS `cookie_consent_log` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `preferences` json NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `consent_version` varchar(10) DEFAULT '1.0',
    `action` enum('granted', 'updated', 'revoked') DEFAULT 'granted',
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_ip_address` (`ip_address`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_action` (`action`),
    CONSTRAINT `fk_cookie_consent_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Page views table (if not already exists from analytics migration)
CREATE TABLE IF NOT EXISTS `page_views` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `session_id` varchar(255) DEFAULT NULL,
    `page_type` varchar(50) NOT NULL,
    `page_id` varchar(255) DEFAULT NULL,
    `url` varchar(500) DEFAULT NULL,
    `title` varchar(255) DEFAULT NULL,
    `referrer` varchar(500) DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `device_type` varchar(50) DEFAULT NULL,
    `browser` varchar(100) DEFAULT NULL,
    `os` varchar(100) DEFAULT NULL,
    `country` varchar(2) DEFAULT NULL,
    `view_duration` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`),
    KEY `idx_page_type` (`page_type`),
    KEY `idx_page_id` (`page_id`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_ip_address` (`ip_address`),
    CONSTRAINT `fk_page_views_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User preferences table (enhanced)
CREATE TABLE IF NOT EXISTS `user_preferences` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned NOT NULL,
    `preference_key` varchar(100) NOT NULL,
    `preference_value` text DEFAULT NULL,
    `category` varchar(50) DEFAULT 'general',
    `is_public` boolean DEFAULT false,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_preference` (`user_id`, `preference_key`),
    KEY `idx_category` (`category`),
    KEY `idx_public` (`is_public`),
    CONSTRAINT `fk_user_preferences_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- GDPR data requests table
CREATE TABLE IF NOT EXISTS `gdpr_requests` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `email` varchar(255) NOT NULL,
    `request_type` enum('export', 'delete', 'rectify', 'restrict', 'object') NOT NULL,
    `status` enum('pending', 'processing', 'completed', 'rejected') DEFAULT 'pending',
    `description` text DEFAULT NULL,
    `verification_token` varchar(255) DEFAULT NULL,
    `verification_expires` timestamp NULL DEFAULT NULL,
    `is_verified` boolean DEFAULT false,
    `processed_by` bigint(20) unsigned DEFAULT NULL,
    `processed_at` timestamp NULL DEFAULT NULL,
    `data_file_path` varchar(500) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_email` (`email`),
    KEY `idx_request_type` (`request_type`),
    KEY `idx_status` (`status`),
    KEY `idx_verification_token` (`verification_token`),
    KEY `idx_created_at` (`created_at`),
    CONSTRAINT `fk_gdpr_requests_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_gdpr_requests_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email tracking for compliance
CREATE TABLE IF NOT EXISTS `email_tracking` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` bigint(20) unsigned DEFAULT NULL,
    `email` varchar(255) NOT NULL,
    `email_type` varchar(50) NOT NULL,
    `template` varchar(100) DEFAULT NULL,
    `subject` varchar(255) DEFAULT NULL,
    `status` enum('sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained') DEFAULT 'sent',
    `tracking_id` varchar(255) DEFAULT NULL,
    `metadata` json DEFAULT NULL,
    `sent_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `opened_at` timestamp NULL DEFAULT NULL,
    `clicked_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_email` (`email`),
    KEY `idx_email_type` (`email_type`),
    KEY `idx_status` (`status`),
    KEY `idx_tracking_id` (`tracking_id`),
    KEY `idx_sent_at` (`sent_at`),
    CONSTRAINT `fk_email_tracking_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data retention policies
CREATE TABLE IF NOT EXISTS `data_retention_policies` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `table_name` varchar(100) NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `retention_period_days` int(11) NOT NULL,
    `last_cleanup` timestamp NULL DEFAULT NULL,
    `is_active` boolean DEFAULT true,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_table_name` (`table_name`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default data retention policies
INSERT INTO `data_retention_policies` (`table_name`, `description`, `retention_period_days`) VALUES
('page_views', 'Page view analytics data', 365),
('cookie_consent_log', 'Cookie consent compliance logs', 2555), -- 7 years for legal compliance
('audit_logs', 'User activity audit logs', 2555), -- 7 years for security
('email_tracking', 'Email delivery and engagement tracking', 730), -- 2 years
('user_sessions', 'User session data', 30),
('gdpr_requests', 'GDPR data request records', 2555), -- 7 years for legal compliance
('contact_inquiries', 'Contact form submissions', 1095) -- 3 years
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Create indexes for better performance
CREATE INDEX idx_cookie_consent_user_created ON cookie_consent_log(user_id, created_at);
CREATE INDEX idx_page_views_session_created ON page_views(session_id, created_at);
CREATE INDEX idx_gdpr_requests_status_created ON gdpr_requests(status, created_at);
CREATE INDEX idx_email_tracking_type_sent ON email_tracking(email_type, sent_at);

-- Insert sample cookie policy data into settings
INSERT INTO `settings` (`key`, `value`, `type`, `description`, `is_public`) VALUES
('cookie_policy_version', '1.0', 'string', 'Current version of cookie policy', true),
('cookie_policy_last_updated', '2025-01-01', 'string', 'Last update date of cookie policy', true),
('gdpr_compliance_enabled', 'true', 'boolean', 'Enable GDPR compliance features', false),
('cookie_consent_required', 'true', 'boolean', 'Require cookie consent for non-essential cookies', true),
('analytics_cookie_enabled', 'true', 'boolean', 'Enable analytics cookies by default', false),
('marketing_cookie_enabled', 'false', 'boolean', 'Enable marketing cookies by default', false),
('data_retention_enabled', 'true', 'boolean', 'Enable automatic data retention cleanup', false)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;