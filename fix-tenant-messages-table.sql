-- Fix tenant_messages table structure
-- Run this in Clever Cloud MySQL console

-- Check current structure
DESCRIBE tenant_messages;

-- The table was created with user_id but code expects tenant_id
-- We need to add tenant_id column

-- Step 1: Add tenant_id column (will be populated from user_id via tenant relationship)
ALTER TABLE `tenant_messages` 
ADD COLUMN `tenant_id` bigint(20) unsigned NULL AFTER `id`;

-- Step 2: Populate tenant_id from user_id by looking up the tenant
UPDATE `tenant_messages` tm
INNER JOIN `tenants` t ON t.user_id = tm.user_id
SET tm.tenant_id = t.id;

-- Step 3: Make tenant_id NOT NULL now that it's populated
ALTER TABLE `tenant_messages`
MODIFY COLUMN `tenant_id` bigint(20) unsigned NOT NULL;

-- Step 4: Add foreign key constraint
ALTER TABLE `tenant_messages`
ADD CONSTRAINT `tenant_messages_tenant_id_foreign` 
  FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE;

-- Step 5: Add index
ALTER TABLE `tenant_messages`
ADD INDEX `tenant_messages_tenant_id_index` (`tenant_id`);

-- Step 6: Verify the fix
DESCRIBE tenant_messages;

-- Should now show both user_id and tenant_id columns
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'tenant_messages' 
  AND COLUMN_NAME IN ('user_id', 'tenant_id', 'room_id');

