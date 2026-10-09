-- Create tenant_deposits table
-- Run this in Clever Cloud MySQL console

CREATE TABLE IF NOT EXISTS `tenant_deposits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint(20) unsigned NOT NULL,
  `tenant_id` bigint(20) unsigned NOT NULL,
  `amount_required` decimal(10,2) NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_deducted` decimal(10,2) NOT NULL DEFAULT 0.00,
  `amount_refunded` decimal(10,2) NOT NULL DEFAULT 0.00,
  `deduction_reason` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_deposits_contract_id_foreign` (`contract_id`),
  KEY `tenant_deposits_tenant_id_foreign` (`tenant_id`),
  KEY `tenant_deposits_contract_id_tenant_id_index` (`contract_id`,`tenant_id`),
  CONSTRAINT `tenant_deposits_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_deposits_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify
DESCRIBE tenant_deposits;
