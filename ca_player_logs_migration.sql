CREATE TABLE `ca_player_logs` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `USER_ID` int(11) NOT NULL COMMENT 'Foreign key to ca_users ID',
  `ACTIVITY_TYPE` varchar(50) NOT NULL COMMENT 'e.g. LOGIN, VIEW_PLAYERS, JOIN_GAME, LOGOUT',
  `DESCRIPTION` text DEFAULT NULL COMMENT 'Additional context (e.g. Joined game ID 123)',
  `CREATED_AT` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`)
  -- Note: Do not add strict foreign keys to ca_users if soft-deletes are used, otherwise:
  -- FOREIGN KEY (`USER_ID`) REFERENCES `ca_users`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
