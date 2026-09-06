-- ============================================================================
--  Host Audit Log / User-Activity Analytics
--  Extends the EXISTING ca_player_logs activity system (no competing table).
--  Applied idempotently by run_migrations.php -- safe to run repeatedly.
-- ============================================================================

-- 1. Host / game / context columns so an activity row can be attributed to a
--    specific host's games and analysed without extra joins.
ALTER TABLE `ca_player_logs`
    ADD COLUMN `HOST_ID`    INT(11)      NULL DEFAULT NULL AFTER `USER_ID`,
    ADD COLUMN `GAME_ID`    INT(11)      NULL DEFAULT NULL AFTER `HOST_ID`,
    ADD COLUMN `META`       TEXT         NULL DEFAULT NULL AFTER `DESCRIPTION`,
    ADD COLUMN `IP_ADDRESS` VARCHAR(45)  NULL DEFAULT NULL AFTER `META`;

-- 2. Indexes for the audit queries (user timeline, host-scoped analytics,
--    activity-type filtering, per-game rollups, date-range scans).
ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_user_created`  (`USER_ID`, `CREATED_AT`);
ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_host_created`  (`HOST_ID`, `CREATED_AT`);
ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_type_created`  (`ACTIVITY_TYPE`, `CREATED_AT`);
ALTER TABLE `ca_player_logs` ADD INDEX `idx_apl_game`          (`GAME_ID`);

-- Canonical business activity types written into ACTIVITY_TYPE:
--   LOGIN, LOGOUT                 (global, HOST_ID/GAME_ID NULL)      -- already emitted
--   GAME_LIST_VIEWED             (HOST_ID set, META = {"count":N})
--   GAME_VIEWED                  (HOST_ID + GAME_ID set)
--   JOIN_GAME                    (HOST_ID + GAME_ID set)
--   LEAVE_GAME                   (HOST_ID + GAME_ID set)
--   VIEW_PLAYERS                 (legacy; kept as-is)
