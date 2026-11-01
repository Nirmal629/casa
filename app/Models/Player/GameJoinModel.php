<?php

declare(strict_types=1);

namespace App\Models\Player;

use App\Core\Model;
use App\Config\Database;
use App\Models\EventModel;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use PDOException;
use mysqli;
use Throwable;

class GameJoinModel extends Model
{
    protected string $table = 'ca_gamejoin';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID',
        'GAME_ID',
        'HOST_ID',
        'PRICE',
        'CURRENCY',
        'TYPE',
        'STATUS',
        'CONFIRMED',
    ];

    private const string DEFAULT_TIMEZONE = 'America/Toronto';

    protected mysqli $mysqli;
    protected EventModel $eventModel;

    private function getTimezone(): DateTimeZone
    {
        $timezoneName = function_exists('env')? (string) env('APP_TIMEZONE', date_default_timezone_get() ?: self::DEFAULT_TIMEZONE): (date_default_timezone_get() ?: self::DEFAULT_TIMEZONE);
        try {
            return new DateTimeZone($timezoneName);
        } catch (Throwable $e) {
            return new DateTimeZone(self::DEFAULT_TIMEZONE);
        }
    }

    public function __construct(?PDO $pdo = null,?mysqli $mysqli = null) {
        parent::__construct($pdo);
        $this->db = $pdo ?? Database::getPdo();
        $this->mysqli = $mysqli ?? Database::getMysqli();
        $this->eventModel = new EventModel($this->db);
    }

    private function response(bool $success,string $message,bool $includeHtml = false): array {
        $result = [
            'status' => $success ? 'success' : 'error',
            'success' => $success,
            'message' => $message,
        ];

        if ($includeHtml && $success) {
            $result['outputHTML'] = '';
        }

        return $result;
    }

    private function validIds(int $userId, int $gameId): bool
    {
        return $userId > 0 && $gameId > 0;
    }

    private function logActivity(int $userId,int $gameId,int $hostId,string $action,string $description): void {
        if (!function_exists('logPlayerActivity')) {
            return;
        }

        $resolvedHost = $hostId;

        if (
            $resolvedHost <= 0 &&
            function_exists('auditResolveGameHost')
        ) {
            $resolvedHost = (int) auditResolveGameHost(
                $this->mysqli,
                $gameId
            );
        }

        logPlayerActivity(
            $this->mysqli,
            $userId,
            $action,
            $description,
            $resolvedHost,
            $gameId
        );
    }

    private function runPostAction(int $gameId,int $userId,int $hostId,string $action,string $description,bool $reverse = false): void {
        $this->eventModel->applyAutoConfirmAndMessage($gameId, true);

        try {
            $this->logActivity(
                $userId,
                $gameId,
                $hostId,
                $action,
                $description
            );
        } catch (Throwable $e) {
            error_log('GameJoin audit error: ' . $e->getMessage());
        }
    }

    public function findByGameAndUser(int $gameId,int $userId): ?array {
        if (!$this->validIds($userId, $gameId)) {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT *
             FROM ca_gamejoin
             WHERE GAME_ID = ? AND USER_ID = ?
             LIMIT 1'
        );

        $stmt->execute([$gameId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getJoinedPlayers(int $gameId): array
    {
        if ($gameId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT
                gj.*,
                u.NAME,
                u.EMAIL,
                u.PROFILE_IMAGE
             FROM ca_gamejoin gj
             INNER JOIN ca_users u
                ON u.ID = gj.USER_ID
             WHERE gj.GAME_ID = ?'
        );

        $stmt->execute([$gameId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getJoinedDefaultGameIds(int $userId): array {
        if ($userId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare(
            'SELECT GAME_ID
             FROM ca_gamejoin_default
             WHERE USER_ID = ?'
        );

        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function isUserJoined(int $gameId,int $userId): bool {
        if (!$this->validIds($userId, $gameId)) {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1
             FROM ca_gamejoin
             WHERE GAME_ID = ? AND USER_ID = ?
             LIMIT 1'
        );

        $stmt->execute([$gameId, $userId]);

        return $stmt->fetchColumn() !== false;
    }

    public function joinEvent(
        int $userId,
        int $gameId,
        int $hostId = 0,
        float|string $cost = 0,
        string $currency = '$',
        string $type = 'Public'
    ): array {
        if (!$this->validIds($userId, $gameId)) {
            return $this->response(
                false,
                'Invalid request parameters.'
            );
        }

        $isOwnerTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnerTx = true;
        }

        try {
            // Lock event row for update to serialize concurrent joins and prevent race conditions
            $stmtLock = $this->db->prepare("SELECT * FROM ca_events WHERE ID = ? FOR UPDATE");
            $stmtLock->execute([$gameId]);
            $event = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Event not found.'
                );
            }

            if (($event['STATUS'] ?? '') !== 'Active') {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'This event is not active. Joining is not permitted.'
                );
            }

            if ($this->eventModel->isEventExpired($gameId)) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'This event has already expired. Joining is not permitted.'
                );
            }

            if ($this->eventModel->isBookingFrozen($event)) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Booking for this event is closed. The booking freeze deadline has passed.'
                );
            }

            // Enforce private event restriction: public join is not allowed for Private events
            if (strcasecmp((string) ($event['EVENT_TYPE'] ?? ''), 'Private') === 0 && strcasecmp($type, 'Private') !== 0) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'This is a private event. Participation is by invitation only.'
                );
            }

            // Authoritative event fields (ignoring client-supplied values)
            $authHostId = (int) ($event['HOST_ID'] ?? $hostId);
            $authCost = (string) ($event['EVENT_COST'] ?? $cost);
            $rawCurr = trim((string) ($event['EVENT_CURRENCY'] ?? $currency));
            $authCurrency = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;

            // Verify gender and skill eligibility using authoritative user profile
            $userStmt = $this->db->prepare("SELECT ID, GENDER, LEVEL, VERIFIED_LEVEL FROM ca_users WHERE ID = ? LIMIT 1");
            $userStmt->execute([$userId]);
            $playerUser = $userStmt->fetch(PDO::FETCH_ASSOC);

            if ($playerUser) {
                // Gender eligibility check
                $eventGender = trim((string) ($event['GENDER_CATEGORY'] ?? ''));
                $playerGender = trim((string) ($playerUser['GENDER'] ?? ($playerUser['gender'] ?? '')));
                if (!empty($eventGender) && strcasecmp($eventGender, 'Mix') !== 0) {
                    if (!empty($playerGender) && strcasecmp($playerGender, 'Mix') !== 0 && strcasecmp($eventGender, $playerGender) !== 0) {
                        if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                        return $this->response(
                            false,
                            "You are not eligible for this event. Event requires {$eventGender} players."
                        );
                    }
                }

                // Skill hierarchy eligibility check (using VERIFIED_LEVEL if set, fallback to LEVEL)
                $eventSkill = trim((string) ($event['GENDER_SKILL_LEVEL'] ?? ''));
                $playerSkill = trim((string) (!empty($playerUser['VERIFIED_LEVEL']) ? $playerUser['VERIFIED_LEVEL'] : (!empty($playerUser['verified_level']) ? $playerUser['verified_level'] : (!empty($playerUser['LEVEL']) ? $playerUser['LEVEL'] : ($playerUser['level'] ?? '')))));
                if (!empty($eventSkill) && strcasecmp($eventSkill, 'Mix') !== 0) {
                    $normalizedPlayerSkill = strtolower($playerSkill);
                    if (isset(\App\Models\EventModel::SKILL_HIERARCHY[$normalizedPlayerSkill])) {
                        $allowedLevels = \App\Models\EventModel::SKILL_HIERARCHY[$normalizedPlayerSkill];
                        $isSkillEligible = false;
                        foreach ($allowedLevels as $allowed) {
                            if (strcasecmp($allowed, $eventSkill) === 0) {
                                $isSkillEligible = true;
                                break;
                            }
                        }
                        if (!$isSkillEligible) {
                            if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                            return $this->response(
                                false,
                                "You are not eligible for this event due to skill level requirements."
                            );
                        }
                    }
                    // If player skill is empty or unknown, allow (explicit unrestricted fallback policy)
                }
            }

            // Check if user is already joined
            $existingJoin = $this->findByGameAndUser($gameId, $userId);
            if ($existingJoin) {
                if (($existingJoin['STATUS'] ?? '') === 'Y') {
                    if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                    return $this->response(
                        false,
                        'You have already joined this event.'
                    );
                }

                // If user had a pending invite (STATUS = 'N'), activate it to STATUS = 'Y'
                $stmtUpdate = $this->db->prepare("UPDATE ca_gamejoin SET STATUS = 'Y', TYPE = ?, PRICE = ?, CURRENCY = ? WHERE ID = ?");
                $stmtUpdate->execute([$type, $authCost, $authCurrency, $existingJoin['ID']]);

                $this->runPostAction(
                    $gameId,
                    $userId,
                    $authHostId,
                    'JOIN_GAME',
                    "Joined {$type} game ID {$gameId}"
                );

                if ($isOwnerTx && $this->db->inTransaction()) {
                    $this->db->commit();
                }

                return $this->response(
                    true,
                    'Event joined successfully.',
                    true
                );
            }

            $stmt = $this->db->prepare(
                "INSERT INTO ca_gamejoin (
                    USER_ID,
                    GAME_ID,
                    HOST_ID,
                    PRICE,
                    CURRENCY,
                    TYPE,
                    STATUS,
                    CREATED_AT
                ) VALUES (?, ?, ?, ?, ?, ?, 'Y', ?)"
            );

            try {
                $inserted = $stmt->execute([
                    $userId,
                    $gameId,
                    $authHostId,
                    $authCost,
                    $authCurrency,
                    $type,
                    date('Y-m-d H:i:s'),
                ]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000' || ($e->errorInfo[1] ?? 0) === 1062) {
                    if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                    return $this->response(
                        false,
                        'You have already joined this event.'
                    );
                }
                throw $e;
            }

            if (!$inserted) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Failed to join event.'
                );
            }

            $this->runPostAction(
                $gameId,
                $userId,
                $authHostId,
                'JOIN_GAME',
                "Joined {$type} game ID {$gameId}"
            );

            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return $this->response(
                true,
                'Event joined successfully.',
                true
            );
        } catch (Throwable $e) {
            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function cancelEvent(int $userId, int $gameId, int $hostId = 0): array {
        if (!$this->validIds($userId, $gameId)) {
            return $this->response(false, 'Invalid user or game ID.');
        }

        $isOwnerTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnerTx = true;
        }

        try {
            // Lock event row for update to ensure atomic roster updates
            $stmtLock = $this->db->prepare("SELECT * FROM ca_events WHERE ID = ? FOR UPDATE");
            $stmtLock->execute([$gameId]);
            $eventData = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if (!$eventData) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Event not found.'
                );
            }

            $hostId = (int) ($eventData['HOST_ID'] ?? $hostId);

            $cancelDate = $eventData['CANCEL_DATE'] ?: ($eventData['EVENT_DATE'] ?? '');
            $cancelTime = $eventData['CANCEL_TIME'] ?: ($eventData['EVENT_TIME'] ?? '');

            if (!$cancelDate || !$cancelTime) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Event cancellation date or time is missing.'
                );
            }

            $timezone = $this->getTimezone();

            try {
                $deadline = new DateTimeImmutable(trim($cancelDate . ' ' . $cancelTime), $timezone);
            } catch (Throwable $e) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'Invalid event cancellation deadline.');
            }

            $now = new DateTimeImmutable('now', $timezone);

            $paymentStmt = $this->db->prepare(
                "SELECT 1
                 FROM ca_payment
                 WHERE USER_ID = ?
                   AND GAME_ID = ?
                   AND STATUS != 'R'
                 LIMIT 1"
            );

            $paymentStmt->execute([$userId, $gameId]);

            if ($paymentStmt->fetchColumn() !== false) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'Event cannot be cancelled as payment has already been made.');
            }

            if ($now > $deadline) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Free cancellation deadline passed (' .
                    $deadline->format('D, d M Y • h:i A') .
                    '). The roster is locked, so this game cannot be cancelled.'
                );
            }

            $stmt = $this->db->prepare(
                'DELETE FROM ca_gamejoin
                 WHERE USER_ID = ? AND GAME_ID = ?'
            );

            $stmt->execute([$userId, $gameId]);

            if ($stmt->rowCount() === 0) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'You are not joined in this event.');
            }

            $this->runPostAction(
                $gameId,
                $userId,
                $hostId,
                'LEAVE_GAME',
                "Left game ID {$gameId}",
                true
            );

            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return $this->response(
                true,
                'Participation cancelled successfully.'
            );
        } catch (Throwable $e) {
            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function acceptInvite(int $userId, int $gameId, int $hostId = 0): array {
        if (!$this->validIds($userId, $gameId)) {
            return $this->response(
                false,
                'Invalid request parameters.'
            );
        }

        $isOwnerTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnerTx = true;
        }

        try {
            $stmtLock = $this->db->prepare("SELECT * FROM ca_events WHERE ID = ? FOR UPDATE");
            $stmtLock->execute([$gameId]);
            $event = $stmtLock->fetch(PDO::FETCH_ASSOC);

            if (!$event) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'Event not found.');
            }

            $hostId = (int) ($event['HOST_ID'] ?? $hostId);

            if ($this->eventModel->isEventExpired($gameId)) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'This event has expired. Joining is not permitted.');
            }

            if ($this->eventModel->isBookingFrozen($event)) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(false, 'Booking for this event is closed. The booking freeze deadline has passed.');
            }

            $stmt = $this->db->prepare(
                "UPDATE ca_gamejoin
                 SET STATUS = 'Y'
                 WHERE USER_ID = ?
                   AND GAME_ID = ?
                   AND TYPE = 'Invite'
                   AND STATUS <> 'Y'"
            );

            $stmt->execute([$userId, $gameId]);

            if ($stmt->rowCount() === 0) {
                if ($isOwnerTx && $this->db->inTransaction()) $this->db->rollBack();
                return $this->response(
                    false,
                    'Invitation not found or already accepted.'
                );
            }

            $this->runPostAction(
                $gameId,
                $userId,
                $hostId,
                'JOIN_GAME',
                "Accepted invite for game ID {$gameId}"
            );

            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->commit();
            }

            return $this->response(true, 'Invitation accepted successfully.', true);
        } catch (Throwable $e) {
            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    //Get aggregate join & confirmation statistics for an event to update UI in real-time.
     
    public function getEventJoinStats(int $gameId, int $userId = 0): array
    {
        if ($gameId <= 0) {
            return [];
        }

        $event = $this->eventModel->getEventById($gameId);
        if (!$event) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT COUNT(*) AS total_join,
                   SUM(CASE WHEN CONFIRMED = 'Y' THEN 1 ELSE 0 END) AS total_confirmed
            FROM ca_gamejoin
            WHERE GAME_ID = ? AND STATUS = 'Y'
        ");
        $stmt->execute([$gameId]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalJoin = (int) ($counts['total_join'] ?? 0);
        $totalConfirmed = (int) ($counts['total_confirmed'] ?? 0);

        $userJoined = false;
        $userConfirmed = null;
        $userJoinStatus = null;

        if ($userId > 0) {
            $userJoin = $this->findByGameAndUser($gameId, $userId);
            if ($userJoin && ($userJoin['STATUS'] ?? '') === 'Y') {
                $userJoined = true;
                $userConfirmed = $userJoin['CONFIRMED'] ?? 'N';
                $userJoinStatus = $userJoin['STATUS'] ?? 'Y';
            }
        }

        $courtsConfirmed = (int) ($event['COURTS_CONFIRMED'] ?? 0);
        $isExpired = $this->eventModel->isEventExpired($gameId);

        $courtStatusText = !empty($event['JOIN_MESSAGE'])
            ? (string) $event['JOIN_MESSAGE']
            : ($courtsConfirmed > 0
                ? "{$courtsConfirmed} court" . ($courtsConfirmed > 1 ? 's' : '') . " confirmed"
                : "0 court confirmed, open spots available for first court");

        $courtStatusBadge = $isExpired
            ? 'COMPLETED'
            : ($totalConfirmed >= 4 ? 'COURT ACTIVE' : 'PENDING MIN 4');

        $eventCost = (float) ($event['EVENT_COST'] ?? 0);
        $rawCurr = trim((string) ($event['EVENT_CURRENCY'] ?? '$'));
        $currency = ($rawCurr === 'CAD' || empty($rawCurr)) ? '$' : $rawCurr;
        $formattedCost = $currency . ' ' . (is_numeric($eventCost) ? number_format((float) $eventCost, 2) : $eventCost);
        $eventCourt = $courtsConfirmed > 0 ? (string) $courtsConfirmed : 'NA';

        return [
            'event_id'           => $gameId,
            'total_join'         => $totalJoin,
            'total_confirmed'    => $totalConfirmed,
            'courts_confirmed'   => $courtsConfirmed,
            'court_status_text'  => $courtStatusText,
            'court_status_badge' => $courtStatusBadge,
            'user_joined'        => $userJoined,
            'user_confirmed'     => $userConfirmed,
            'user_join_status'   => $userJoinStatus,
            'event_cost'         => $eventCost,
            'formatted_cost'     => $formattedCost,
            'event_court'        => $eventCourt,
        ];
    }
}
