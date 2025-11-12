-- ============================================
-- より安全な方法: 制約名を動的に取得して削除
-- ============================================

-- 1. 外部キー制約を動的に削除（MySQL 8.0以降）
SET @drop_team_a_fk = (
    SELECT CONCAT('ALTER TABLE `games` DROP FOREIGN KEY `', CONSTRAINT_NAME, '`')
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'games'
      AND COLUMN_NAME = 'team_a_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

SET @drop_team_b_fk = (
    SELECT CONCAT('ALTER TABLE `games` DROP FOREIGN KEY `', CONSTRAINT_NAME, '`')
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'games'
      AND COLUMN_NAME = 'team_b_id'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    LIMIT 1
);

-- 2. カラムをnullableに変更
ALTER TABLE `games` MODIFY `team_a_id` BIGINT UNSIGNED NULL;
ALTER TABLE `games` MODIFY `team_b_id` BIGINT UNSIGNED NULL;

-- 3. 外部キー制約を再追加
ALTER TABLE `games` 
    ADD CONSTRAINT `games_team_a_id_foreign` 
    FOREIGN KEY (`team_a_id`) 
    REFERENCES `teams` (`id`) 
    ON DELETE SET NULL;

ALTER TABLE `games` 
    ADD CONSTRAINT `games_team_b_id_foreign` 
    FOREIGN KEY (`team_b_id`) 
    REFERENCES `teams` (`id`) 
    ON DELETE SET NULL;

