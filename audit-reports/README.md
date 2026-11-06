# Read-only PHP application audit

Status: initial architecture and risk triage, 2026-11-06.

Scope is evidence gathered from the existing Graphify graph and repository metadata. Application source was not modified. Findings are not considered confirmed until the cited execution path and safe tests are reviewed.

Graphify snapshot: 673 files, 2,618 nodes, 4,536 edges, 395 communities; 90% extracted and 10% inferred; no import cycles detected. Graph exclusions include vendor, CodeIgniter system/writable, uploads, profile images, PHPMailer, and other generated/assets paths.

Audit order: authentication/authorization → direct APIs and file handling → ledger/database writes → tournament scoring/state transitions → POS/admin → tests, operations, and refactoring.

See the numbered reports for the current evidence and remaining coverage.
