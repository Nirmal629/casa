-- Per-match settings override tournament defaults without changing them.
ALTER TABLE `to_matches`
  ADD COLUMN `COURT_NO` int(11) DEFAULT NULL AFTER `COURT_ID`,
  ADD COLUMN `GAME_SETS` tinyint(1) DEFAULT NULL AFTER `COURT_NO`,
  ADD COLUMN `DEUCE_ENABLED` tinyint(1) DEFAULT NULL AFTER `GAME_SETS`;
