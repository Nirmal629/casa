# Database and performance

Graphify identifies `app/Config/Database.php`, `app/Database/MigrationRunner.php`, `UserModel`, `GameJoinModel`, `PlayerLedgerModel`, and multiple controller/view database consumers. The graph also shows both PDO and mysqli dependencies in the player/game area, which is a review hotspot.

Execution plans, query frequency, and production-like benchmarks were not run.
