-- ============================================
-- 手順1: 外部キー制約名を確認
-- ============================================
-- このSQLを実行して、制約名を確認してください
SHOW CREATE TABLE `games`;

-- ============================================
-- 手順2: 以下のSQLを実行（制約名を確認後に調整）
-- ============================================
-- 外部キー制約を削除（制約名は上記の結果に合わせて変更してください）
-- 一般的なLaravelの命名規則: games_team_a_id_foreign, games_team_b_id_foreign

ALTER TABLE `games` DROP FOREIGN KEY `games_team_a_id_foreign`;
ALTER TABLE `games` DROP FOREIGN KEY `games_team_b_id_foreign`;

-- カラムをnullableに変更
ALTER TABLE `games` MODIFY `team_a_id` BIGINT UNSIGNED NULL;
ALTER TABLE `games` MODIFY `team_b_id` BIGINT UNSIGNED NULL;

-- 外部キー制約を再追加（nullable対応）
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

