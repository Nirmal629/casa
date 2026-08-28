CREATE TABLE IF NOT EXISTS `host_player_carry_forward` (
    `id`              INT(11)       NOT NULL AUTO_INCREMENT,
    `host_id`         INT(11)       NOT NULL,
    `player_id`       INT(11)       NOT NULL,
    `carry_month`     TINYINT(2)    NOT NULL COMMENT 'Month that received the carried balance (destination)',
    `carry_year`      SMALLINT(4)   NOT NULL COMMENT 'Year of the carry_month',
    `opening_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_type`    ENUM('DUE','ADVANCE') NOT NULL DEFAULT 'DUE' COMMENT 'DUE = balance owed, ADVANCE = player credit',
    `source_month`    TINYINT(2)    NOT NULL COMMENT 'Month that was carried forward (source)',
    `source_year`     SMALLINT(4)   NOT NULL,
    `is_locked`       TINYINT(1)    NOT NULL DEFAULT 0 COMMENT 'Mirrors existing month-lock rule for carry_month',
    `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_host_player_carry` (`host_id`, `player_id`, `carry_month`, `carry_year`),
    KEY `idx_hpc_player` (`player_id`),
    KEY `idx_hpc_source` (`source_month`, `source_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. Carry Forward Audit History
--    APPEND-ONLY. Never UPDATE/DELETE rows here.
--    One row per Carry Forward operation (CREATE or UPDATE of the current state).
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ca_host_player_carry_forward_history` (
    `id`                 INT(11)       NOT NULL AUTO_INCREMENT,
    `carry_forward_id`   INT(11)       NOT NULL COMMENT 'FK → host_player_carry_forward.id',
    `host_id`            INT(11)       NOT NULL,
    `player_id`          INT(11)       NOT NULL,
    `source_month`       TINYINT(2)    NOT NULL COMMENT 'Month carried FROM',
    `source_year`        SMALLINT(4)   NOT NULL,
    `destination_month`  TINYINT(2)    NOT NULL COMMENT 'Month carried TO',
    `destination_year`   SMALLINT(4)   NOT NULL,
    `opening_balance`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `balance_type`       ENUM('DUE','ADVANCE') NOT NULL DEFAULT 'DUE',
    `action`             ENUM('CREATE','UPDATE') NOT NULL DEFAULT 'CREATE',
    `remarks`            VARCHAR(255)  DEFAULT NULL,
    `created_by`         INT(11)       DEFAULT NULL COMMENT 'Host user who performed the carry',
    `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_hcf_carry_forward_id` (`carry_forward_id`),
    KEY `idx_hcf_host_player` (`host_id`, `player_id`),
    KEY `idx_hcf_source` (`source_month`, `source_year`),
    KEY `idx_hcf_destination` (`destination_month`, `destination_year`),
    KEY `idx_hcf_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
