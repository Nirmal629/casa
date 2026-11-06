# Executive summary

The repository is a multi-application PHP system with a custom application core, tournament/scoring code, API scripts, and a CodeIgniter 4 POS/admin application. This is an initial triage, not a completed audit.

Confirmed architectural facts: Graphify found 2,618 nodes and 4,536 edges; `HostController` is the most connected node (59 edges), followed by `Clubadmin` (57), `Model` (49), `LedgerModel` (48), and `EventModel` (41). No import cycles were detected by Graphify.

Priority review areas are authorization boundaries, database writes and transaction handling, score/state mutation, and legacy/versioned PHP entry points. No severity counts are asserted yet.

Two security review leads are recorded for verification: legacy credential compatibility and the dual remember-me cookie lifecycle. Neither is classified as a confirmed vulnerability yet.
