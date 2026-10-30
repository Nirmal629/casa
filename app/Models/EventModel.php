<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use Throwable;

class EventModel extends Model
{
    protected string $table = 'ca_events';
    protected string $primaryKey = 'ID';
    protected array $fillable = [
        'HOST_ID',
        'HOST_NAME',
        'CUP_NAME',
        'EVENT_COUNTRY',
        'EVENT_PROVINCE',
        'EVENT_CITY',
        'EVENT_CURRENCY',
        'EVENT_VENUE',
        'EVENT_CATEGORY',
        'GENDER_CATEGORY',
        'GENDER_SKILL_LEVEL',
        'EVENT_TYPE',
        'EVENT_DATE',
        'EVENT_TIME',
        'TO_TIME',
        'CANCEL_DATE',
        'CANCEL_TIME',
        'EVENT_COST',
        'EVENT_DISCOUNT',
        'EVENT_DESCRIPTION',
        'EVENT_MESSAGE',
        'FACILITY_COST',
        'ACCESSORIES_COST',
        'SNACKS_COST',
        'TOTAL_EVENT_COST',
        'TOTAL_PLAYER_COST',
        'PROFIT_LOSS',
        'CLUB_COST',
        'BIRDIE_USED',
        'BIRDIE_COST',
        'BIRDIE_PRICE',
        'JOIN_MESSAGE',
        'AUTOMATION',
        'STATUS'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }


    public function getDefaultSubscriptionEvents(int $hostId, string $vlevel = '', string $gender = ''): array
    {
        if ($hostId <= 0) {
            return [];
        }

        $sql = "SELECT * FROM ca_events_default 
                WHERE HOST_ID = :host_id 
                  AND (GENDER_SKILL_LEVEL = :vlevel OR GENDER_SKILL_LEVEL = 'Mix') 
                  AND (GENDER_CATEGORY = :gender OR GENDER_CATEGORY = 'Mix') 
                  AND EVENT_CATEGORY != 'Snacks And Kerala Knook'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':host_id' => $hostId,
            ':vlevel' => $vlevel,
            ':gender' => $gender,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getEventById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM ca_events WHERE ID = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function isEventExpired(int|array $event): bool
    {
        $eventData = is_array($event) ? $event : $this->getEventById($event);
        if (!$eventData) {
            return true;
        }

        $eventDateStr = trim((string) ($eventData['EVENT_DATE'] ?? date('Y-m-d')));
        $eventStartTimeStr = trim((string) ($eventData['EVENT_TIME'] ?? '00:00:00'));
        $eventEndTimeStr = trim((string) ($eventData['TO_TIME'] ?? ''));

        $timezoneName = function_exists('env')
            ? (string) env('APP_TIMEZONE', date_default_timezone_get() ?: 'America/Toronto')
            : (date_default_timezone_get() ?: 'America/Toronto');

        try {
            $tz = new DateTimeZone($timezoneName);
        } catch (Throwable $e) {
            $tz = new DateTimeZone('America/Toronto');
        }

        try {
            $startDt = new DateTimeImmutable("{$eventDateStr} {$eventStartTimeStr}", $tz);
            if (!empty($eventEndTimeStr)) {
                $endDt = new DateTimeImmutable("{$eventDateStr} {$eventEndTimeStr}", $tz);
                if ($endDt < $startDt) {
                    $endDt = $endDt->modify('+1 day'); // Past midnight
                }
            } else {
                $endDt = $startDt->modify('+2 hours'); // Default 2 hours
            }

            $now = new DateTimeImmutable('now', $tz);
            return $now >= $endDt;
        } catch (Throwable $e) {
            $startTimestamp = strtotime("{$eventDateStr} {$eventStartTimeStr}");
            if (!empty($eventEndTimeStr)) {
                $endTimestamp = strtotime("{$eventDateStr} {$eventEndTimeStr}");
                if ($endTimestamp < $startTimestamp) {
                    $endTimestamp += 86400;
                }
            } else {
                $endTimestamp = $startTimestamp + 7200;
            }
            return time() >= $endTimestamp;
        }
    }

    public function updateJoinMessage(int $gameId, string $message): bool
    {
        if ($gameId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE {$this->table} SET JOIN_MESSAGE = :message WHERE ID = :id");
        return $stmt->execute([':message' => $message, ':id' => $gameId]);
    }

    public function deleteEventForHost(int $id, int $hostId, bool $isAdmin = false): bool
    {
        if ($id <= 0 || ($hostId <= 0 && !$isAdmin)) {
            return false;
        }

        // Clean up joins associated with this event
        $stmtJoin = $this->db->prepare("DELETE FROM ca_gamejoin WHERE GAME_ID = :id");
        $stmtJoin->execute([':id' => $id]);

        if ($isAdmin) {
            $stmt = $this->db->prepare("DELETE FROM ca_events WHERE ID = :id");
            $stmt->execute([':id' => $id]);
            return $stmt->rowCount() > 0;
        }

        $stmt = $this->db->prepare("DELETE FROM ca_events WHERE ID = :id AND HOST_ID = :host_id");
        $stmt->execute([':id' => $id, ':host_id' => $hostId]);
        return $stmt->rowCount() > 0;
    }

    public const array SKILL_HIERARCHY = [
        'beginner'       => ['Beginner'],
        'amateur'        => ['Beginner', 'Amateur'],
        'intermediate'   => ['Beginner', 'Amateur', 'Intermediate'],
        'intermediate +' => ['Beginner', 'Amateur', 'Intermediate', 'Intermediate +', 'Intermediate+'],
        'intermediate+'  => ['Beginner', 'Amateur', 'Intermediate', 'Intermediate +', 'Intermediate+'],
        'advance'        => ['Beginner', 'Amateur', 'Intermediate', 'Intermediate +', 'Intermediate+', 'Advance', 'Advanced'],
        'advanced'       => ['Beginner', 'Amateur', 'Intermediate', 'Intermediate +', 'Intermediate+', 'Advance', 'Advanced'],
    ];

    //Normalize filter inputs from associative array or object.
    private function normalizeFilters(array|object $filters): array
    {
        if (is_object($filters)) {
            $filters = (array) $filters;
        }

        $year = isset($filters['year']) && $filters['year'] !== null ? (int) $filters['year'] : null;
        $month = isset($filters['month']) && $filters['month'] !== null ? (int) $filters['month'] : null;

        if ($year !== null && ($year < 1970 || $year > 2100)) {
            $year = null;
        }
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        return [
            'host_id'         => (int) ($filters['host_id'] ?? $filters['hostId'] ?? 0),
            'year'            => $year,
            'month'           => $month,
            'event_category'  => $filters['event_category'] ?? $filters['eventCategory'] ?? null,
            'is_player'       => (bool) ($filters['is_player'] ?? $filters['isPlayer'] ?? false),
            'joined_only'     => (bool) ($filters['joined_only'] ?? $filters['joinedOnly'] ?? false),
            'gender'          => trim((string) ($filters['gender'] ?? '')),
            'skill_level'     => trim((string) ($filters['skill_level'] ?? $filters['skillLevel'] ?? '')),
            'session_user_id' => (int) ($filters['session_user_id'] ?? $filters['sessionUserId'] ?? 0),
            'player_id'       => (int) ($filters['player_id'] ?? $filters['playerId'] ?? ($filters['session_user_id'] ?? 0)),
        ];
    }

    //Build date range condition (sargable for index optimization on EVENT_DATE).
    private function applyDateFilter(array &$conditions, array &$params, ?int $year, ?int $month): void
    {
        if (!empty($year) && !empty($month)) {
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $conditions[] = "e.EVENT_DATE BETWEEN :start_date AND :end_date";
            $params[':start_date'] = $startDate;
            $params[':end_date'] = date('Y-m-t', strtotime($startDate));
        } elseif (!empty($year)) {
            $conditions[] = "e.EVENT_DATE BETWEEN :start_date AND :end_date";
            $params[':start_date'] = sprintf('%04d-01-01', $year);
            $params[':end_date'] = sprintf('%04d-12-31', $year);
        } elseif (!empty($month)) {
            $conditions[] = "MONTH(e.EVENT_DATE) = :month";
            $params[':month'] = (int) $month;
        }
    }

    //Build category condition.
    private function applyCategoryFilter(array &$conditions, array &$params, ?string $eventCategory): void
    {
        if (!empty($eventCategory) && $eventCategory !== 'All') {
            $conditions[] = "e.EVENT_CATEGORY = :event_category";
            $params[':event_category'] = $eventCategory;
        }
    }

    //Build player gender and skill level eligibility conditions.
    private function applyPlayerFilter(array &$conditions, array &$params, bool $isPlayer, string $gender, string $skillLevel): void
    {
        if (!$isPlayer) {
            return;
        }

        if (!empty($gender) && strcasecmp($gender, 'Mix') !== 0) {
            $conditions[] = "(e.GENDER_CATEGORY = :player_gender OR e.GENDER_CATEGORY = 'Mix' OR e.GENDER_CATEGORY = '' OR e.GENDER_CATEGORY IS NULL)";
            $params[':player_gender'] = $gender;
        }

        $normalizedLevel = strtolower(trim($skillLevel));
        $allowedLevels = self::SKILL_HIERARCHY[$normalizedLevel] ?? [];
        if (!empty($allowedLevels)) {
            $placeholders = [];
            foreach ($allowedLevels as $idx => $lvl) {
                $paramKey = ":allowed_lvl_" . $idx;
                $placeholders[] = $paramKey;
                $params[$paramKey] = $lvl;
            }
            $inClause = implode(', ', $placeholders);
            $conditions[] = "(e.GENDER_SKILL_LEVEL IN ($inClause) OR e.GENDER_SKILL_LEVEL = 'Mix' OR e.GENDER_SKILL_LEVEL = '' OR e.GENDER_SKILL_LEVEL IS NULL)";
        }
    }

    //Get active events filtered by host, date range, category, and player eligibility.
    public function getFilteredEvents(array|object $filters): array
    {
        $f = $this->normalizeFilters($filters);

        if ($f['host_id'] <= 0) {
            return [];
        }

        $conditions = ["e.HOST_ID = :host_id", "e.STATUS = 'Active'"];
        $params = [':host_id' => $f['host_id']];

        if (!empty($f['joined_only'])) {
            if ($f['player_id'] <= 0) { return []; }
            $conditions[] = "EXISTS (SELECT 1 FROM ca_gamejoin pgj WHERE pgj.GAME_ID = e.ID AND pgj.USER_ID = :schedule_player_id AND pgj.STATUS = 'Y')";
            $params[':schedule_player_id'] = $f['player_id'];
        }

        $this->applyDateFilter($conditions, $params, $f['year'], $f['month']);
        $this->applyCategoryFilter($conditions, $params, $f['event_category']);
        $this->applyPlayerFilter($conditions, $params, $f['is_player'], $f['gender'], $f['skill_level']);

        $whereClause = implode(" AND ", $conditions);

        // Fetch events cleanly without expensive N*6 correlated subqueries
        $sql = "
            SELECT e.*
            FROM ca_events e
            WHERE {$whereClause}
            ORDER BY e.EVENT_DATE DESC, e.EVENT_TIME DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    //Batch fetch joined players for multiple game IDs, indexed by GAME_ID.
    public function getJoinsByEventIds(array $eventIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $eventIds)));
        if (empty($ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $joinSql = "
            SELECT 
                gj.GAME_ID,
                gj.USER_ID,
                gj.CONFIRMED,
                gj.STATUS,
                u.NAME
            FROM ca_gamejoin gj
            INNER JOIN ca_users u ON gj.USER_ID = u.ID
            WHERE gj.GAME_ID IN ($placeholders)
            ORDER BY gj.GAME_ID ASC, u.NAME ASC
        ";

        $joinStmt = $this->db->prepare($joinSql);
        $joinStmt->execute($ids);
        $joins = $joinStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $joinsByGame = [];
        foreach ($joins as $j) {
            $joinsByGame[(int) $j['GAME_ID']][] = $j;
        }

        return $joinsByGame;
    }

    //Format shareable event summary (e.g. WhatsApp broadcast text).
    public function formatEventShareText(array $event, string $playerListText = ''): string
    {
        $hostName = $event['HOST_NAME'] ?? '';
        $eventCategory = $event['EVENT_CATEGORY'] ?? '';
        $genderCategory = $event['GENDER_CATEGORY'] ?? '';
        $genderSkillLevel = $event['GENDER_SKILL_LEVEL'] ?? '';
        $eventVenue = $event['EVENT_VENUE'] ?? '';

        $dateTimestamp = !empty($event['EVENT_DATE']) ? strtotime($event['EVENT_DATE']) : false;
        $dayName = $dateTimestamp !== false ? date('l', $dateTimestamp) : '';
        $dateFormatted = $dateTimestamp !== false ? date('d|m|Y', $dateTimestamp) : '';

        $startTimeTs = !empty($event['EVENT_TIME']) ? strtotime($event['EVENT_TIME']) : false;
        $startTime = $startTimeTs !== false ? date('h:i A', $startTimeTs) : '';

        $endTimeTs = !empty($event['TO_TIME']) ? strtotime($event['TO_TIME']) : false;
        $endTime = $endTimeTs !== false ? ' to ' . date('h:i A', $endTimeTs) : '';
        $timeFormatted = $startTime . $endTime;

        return "Host:                {$hostName}\n"
            . "Game:                {$eventCategory}\n"
            . "Category:            {$genderCategory}\n"
            . "Level:               {$genderSkillLevel}\n"
            . "Day:                 {$dayName}\n"
            . "Date:                {$dateFormatted}\n"
            . "Time:                {$timeFormatted}\n"
            . "Venue:               {$eventVenue}\n"
            . "___________\n"
            . "{$playerListText}"
            . "___________\n\n"
            . "Players are requested to join the game using the portal.\n"
            . "Reach out to admin for any further queries/suggestions.\n\n"
            . "Warm regards,\n"
            . "🏸Casa Badminton Club\n"
            . "📞+1 437 981 0512\n"
            . "🌐https://casainfotech.com/staging\n"
            . "💪Stay fit and active!";
    }

    //Enrich an event with join statistics, session user status, and formatted WhatsApp message.
    private function enrichEventWithJoins(array &$event, array $gameJoins, int $sessionUserId): void
    {
        $totalJoined = 0;
        $totalConfirmed = 0;
        $userJoinedCount = 0;
        $userConfirmed = null;
        $userJoinStatus = null;
        $playerLines = [];
        $numberedPlayerLines = [];
        $counter = 1;

        foreach ($gameJoins as $gj) {
            $isAccepted = ($gj['STATUS'] ?? '') === 'Y';
            $isConfirmed = ($gj['CONFIRMED'] ?? '') === 'Y';

            $uId = (int) ($gj['USER_ID'] ?? 0);
            if ($sessionUserId > 0 && $uId === $sessionUserId) {
                $userJoinedCount++;
                if ($userConfirmed === null) {
                    $userConfirmed = $gj['CONFIRMED'] ?? null;
                    $userJoinStatus = $gj['STATUS'] ?? null;
                }
            }

            // Only count in total_join and player list if STATUS = 'Y' (accepted join)
            if ($isAccepted) {
                $totalJoined++;
                if ($isConfirmed) {
                    $totalConfirmed++;
                }

                $name = trim((string) ($gj['NAME'] ?? ''));
                $statusLabel = $isConfirmed ? 'Confirmed' : 'Joined';
                $playerLines[] = "{$name} ({$statusLabel})";
                $numberedPlayerLines[] = sprintf("%02d) %s (%s)", $counter++, $name, $statusLabel);
            }
        }

        $event['total_join'] = $totalJoined;
        $event['total_confirmed'] = $totalConfirmed;
        $event['user_joined_count'] = $userJoinedCount;
        $event['user_confirmed'] = $userConfirmed;
        $event['user_join_status'] = $userJoinStatus;
        $event['player_list'] = !empty($playerLines) ? implode("\n", $playerLines) : null;

        $playerListText = !empty($numberedPlayerLines) ? implode("\n", $numberedPlayerLines) . "\n" : "";
        $event['formatted_event_data'] = $this->formatEventShareText($event, $playerListText);
    }

    //Get complete scheduled events data with aggregated player joins and formatted text.
    public function getEventData(array|object $filters): array
    {
        $f = $this->normalizeFilters($filters);
        $events = $this->getFilteredEvents($f);

        if (empty($events)) {
            return [];
        }

        $eventIds = array_column($events, 'ID');
        $joinsByGame = $this->getJoinsByEventIds($eventIds);

        foreach ($events as &$event) {
            $eventId = (int) ($event['ID'] ?? 0);
            $this->enrichEventWithJoins($event, $joinsByGame[$eventId] ?? [], $f['session_user_id']);
        }
        unset($event);

        return $events;
    }

    // Helper to calculate duration in hours supporting overnight/midnight crossover
    public function calculateEventDurationHours(string $startTime, string $endTime, ?string $eventDate = null): float
    {
        $baseDate = !empty($eventDate) ? $eventDate : date('Y-m-d');
        $start = new \DateTime("{$baseDate} {$startTime}");
        $end = new \DateTime("{$baseDate} {$endTime}");
        if ($end <= $start) {
            $end->modify('+1 day');
        }
        $interval = $start->diff($end);
        $hours = ($interval->days * 24) + $interval->h + ($interval->i / 60);
        return $hours > 0 ? (float) $hours : 1.0;
    }

    // Helper to fetch host-specific birdie price without cross-host contamination
    public function getHostBirdiePrice(int $hostId): float
    {
        if ($hostId <= 0) {
            return 0.0;
        }
        $stmtBirdie = $this->db->prepare("SELECT BRIDIE FROM ca_bride WHERE HOST_ID = ? LIMIT 1");
        $stmtBirdie->execute([$hostId]);
        $birdieRow = $stmtBirdie->fetch(PDO::FETCH_ASSOC);
        return (float) ($birdieRow['BRIDIE'] ?? 0.0);
    }

    // Authoritative badminton cost calculations (central single implementation)
    public function calculateBadmintonCosts(
        int $hostId,
        string $venue,
        string $startTime,
        string $toTime,
        ?string $eventDate = null,
        int $calcPlayers = 4,
        int $courtsConfirmed = 1
    ): array {
        $venueStmt = $this->db->prepare("SELECT COST FROM ca_venue WHERE NAME = ? AND HOST_ID = ? LIMIT 1");
        $venueStmt->execute([$venue, $hostId]);
        $venueRow = $venueStmt->fetch(PDO::FETCH_ASSOC);
        $courtRentPerHour = $venueRow ? (float) ($venueRow['COST'] ?? 0.0) : 0.0;

        $birdiePrice = $this->getHostBirdiePrice($hostId);
        $hours = $this->calculateEventDurationHours($startTime, $toTime, $eventDate);
        if ($hours <= 0) {
            $hours = 1.0;
        }

        $calcPlayers = max(1, $calcPlayers);
        $courtsConfirmed = max(1, $courtsConfirmed);

        $birdiesUsed = (trim($venue) === 'Epic Badminton') ? ($calcPlayers * 2) : $calcPlayers;

        $facilityCost = (int) ceil($courtRentPerHour * $hours * $courtsConfirmed);
        $accessoriesCost = (int) ceil($birdiePrice * $birdiesUsed);
        $clubCost = 1.0 * $calcPlayers;
        $totalEventCost = (int) ceil($facilityCost + $accessoriesCost + $clubCost);
        $perPlayerCost = (int) ceil($totalEventCost / $calcPlayers);

        return [
            'court_rent_per_hour' => $courtRentPerHour,
            'birdie_price'        => $birdiePrice,
            'hours'               => $hours,
            'calc_players'        => $calcPlayers,
            'courts_confirmed'    => $courtsConfirmed,
            'birdies_used'        => $birdiesUsed,
            'facility_cost'       => $facilityCost,
            'accessories_cost'    => $accessoriesCost,
            'club_cost'           => $clubCost,
            'total_event_cost'    => $totalEventCost,
            'per_player_cost'     => $perPlayerCost,
        ];
    }

    // Execute Auto-Confirmation, court calculation, and message updates using PDO ($this->db)
    public function applyAutoConfirmAndMessage(int $gameId, bool $allowAutoConfirm = true): void
    {
        if ($gameId <= 0) {
            return;
        }

        $eventRow = $this->getEventById($gameId);
        if (!$eventRow) {
            return;
        }

        $isOwnerTx = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $isOwnerTx = true;
        }

        try {
            if (($eventRow['AUTOMATION'] ?? '') === 'Y') {
                $hostId = (int) $eventRow['HOST_ID'];
                $event_time = (string) $eventRow['EVENT_TIME'];
                $to_time = (string) $eventRow['TO_TIME'];

                $stmtPlayers = $this->db->prepare("
                    SELECT ID
                    FROM ca_gamejoin
                    WHERE GAME_ID = ?
                      AND STATUS = 'Y'
                    ORDER BY CREATED_AT ASC
                ");
                $stmtPlayers->execute([$gameId]);
                $players = $stmtPlayers->fetchAll(PDO::FETCH_COLUMN) ?: [];
                $joined = count($players);

                if ($joined < 4) {
                    $calc = $this->calculateBadmintonCosts(
                        $hostId,
                        (string) ($eventRow['EVENT_VENUE'] ?? ''),
                        $event_time,
                        $to_time,
                        $eventRow['EVENT_DATE'] ?? null,
                        4,
                        1
                    );

                    $message = '';
                    if ($joined == 0) {
                        $message = '0 court confirmed, 6 spots available for first court';
                    } elseif ($joined == 1) {
                        $message = '0 court confirmed, 5 spots available for first court';
                    } elseif ($joined == 2) {
                        $message = '0 court confirmed, 4 spots available for first court';
                    } elseif ($joined == 3) {
                        $message = '0 court confirmed, 3 spots available for first court';
                    } elseif ($joined == 4) {
                        $message = '0 court confirmed, 2 spots available for first court';
                    }

                    $this->updateJoinMessage($gameId, $message);

                    $updateSql = "
                        UPDATE ca_events SET
                            EVENT_COST = ?,
                            FACILITY_COST = ?,
                            ACCESSORIES_COST = ?,
                            TOTAL_EVENT_COST = ?,
                            CLUB_COST = ?,
                            BIRDIE_USED = ?,
                            BIRDIE_COST = ?,
                            BIRDIE_PRICE = ?,
                            COURTS_CONFIRMED = 0
                        WHERE ID = ?
                    ";
                    $this->db->prepare($updateSql)->execute([
                        $calc['per_player_cost'],
                        $calc['facility_cost'],
                        $calc['accessories_cost'],
                        $calc['total_event_cost'],
                        $calc['club_cost'],
                        $calc['birdies_used'],
                        $calc['accessories_cost'],
                        $calc['birdie_price'],
                        $gameId
                    ]);

                    if ($allowAutoConfirm) {
                        $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'N' WHERE GAME_ID = ?")->execute([$gameId]);
                        $this->db->prepare("UPDATE ca_gamejoin SET PRICE = ? WHERE GAME_ID = ? AND STATUS = 'Y'")->execute([$calc['per_player_cost'], $gameId]);
                    }

                    $stmtSum = $this->db->prepare("SELECT SUM(PRICE) AS PRICE FROM ca_gamejoin WHERE GAME_ID = ? AND CONFIRMED = 'Y'");
                    $stmtSum->execute([$gameId]);
                    $total_player_cost = (float) ($stmtSum->fetchColumn() ?: 0);
                    $profit_loss = (int) ceil($total_player_cost - $calc['total_event_cost']);

                    $this->db->prepare("
                        UPDATE ca_events SET
                            TOTAL_PLAYER_COST = ?,
                            PROFIT_LOSS = ?
                        WHERE ID = ?
                    ")->execute([$total_player_cost, $profit_loss, $gameId]);
                } else {
                    // $joined >= 4
                    $stmtRef = $this->db->prepare("
                        SELECT *
                        FROM casa_game_test_cases
                        WHERE players_joined = ? AND venue_name != 'Manual'
                        LIMIT 1
                    ");
                    $stmtRef->execute([$joined]);
                    $ref = $stmtRef->fetch(PDO::FETCH_ASSOC);

                    if (!$ref) {
                        if ($isOwnerTx && $this->db->inTransaction()) {
                            $this->db->commit();
                        }
                        return;
                    }

                    $message = (string) $ref['game_message'];
                    $this->updateJoinMessage($gameId, $message);

                    $autoConfirm = trim((string) $ref['auto_confirm']);
                    if ($allowAutoConfirm) {
                        if ($autoConfirm === 'Confirm prior' || $autoConfirm === 'Auto confirm') {
                            $inPlaceholders = implode(',', array_fill(0, count($players), '?'));
                            $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'Y' WHERE ID IN ($inPlaceholders)")->execute($players);
                        } elseif ($autoConfirm === 'Wait') {
                            $confirmedCount = (int) $ref['courts_confirmed'] * 6;
                            $confirmIds = array_slice($players, 0, $confirmedCount);
                            $waitIds = array_slice($players, $confirmedCount);

                            if (!empty($confirmIds)) {
                                $inConf = implode(',', array_fill(0, count($confirmIds), '?'));
                                $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'Y' WHERE ID IN ($inConf)")->execute($confirmIds);
                            }
                            if (!empty($waitIds)) {
                                $inWait = implode(',', array_fill(0, count($waitIds), '?'));
                                $this->db->prepare("UPDATE ca_gamejoin SET CONFIRMED = 'N' WHERE ID IN ($inWait)")->execute($waitIds);
                            }
                        }
                    }

                    $stmtConf = $this->db->prepare("
                        SELECT COUNT(*) AS total
                        FROM ca_gamejoin
                        WHERE GAME_ID = ?
                          AND CONFIRMED = 'Y'
                    ");
                    $stmtConf->execute([$gameId]);
                    $confirmed_players = (int) $stmtConf->fetchColumn();
                    $calc_players = max(1, $confirmed_players);

                    $calc = $this->calculateBadmintonCosts(
                        $hostId,
                        (string) ($eventRow['EVENT_VENUE'] ?? ''),
                        $event_time,
                        $to_time,
                        $eventRow['EVENT_DATE'] ?? null,
                        $calc_players,
                        (int) ($ref['courts_confirmed'] ?? 1)
                    );

                    if ($allowAutoConfirm) {
                        $this->db->prepare("UPDATE ca_gamejoin SET PRICE = ? WHERE GAME_ID = ? AND STATUS = 'Y'")->execute([$calc['per_player_cost'], $gameId]);
                    }

                    $stmtSum = $this->db->prepare("SELECT SUM(PRICE) AS PRICE FROM ca_gamejoin WHERE GAME_ID = ? AND CONFIRMED = 'Y'");
                    $stmtSum->execute([$gameId]);
                    $total_player_cost = (float) ($stmtSum->fetchColumn() ?: 0);
                    $profit_loss = (int) ceil($total_player_cost - $calc['total_event_cost']);

                    $this->db->prepare("
                        UPDATE ca_events SET
                            EVENT_COST = ?,
                            FACILITY_COST = ?,
                            ACCESSORIES_COST = ?,
                            TOTAL_EVENT_COST = ?,
                            TOTAL_PLAYER_COST = ?,
                            CLUB_COST = ?,
                            BIRDIE_USED = ?,
                            PROFIT_LOSS = ?,
                            BIRDIE_COST = ?,
                            BIRDIE_PRICE = ?,
                            COURTS_CONFIRMED = ?
                        WHERE ID = ?
                    ")->execute([
                        $calc['per_player_cost'],
                        $calc['facility_cost'],
                        $calc['accessories_cost'],
                        $calc['total_event_cost'],
                        $total_player_cost,
                        $calc['club_cost'],
                        $calc['birdies_used'],
                        $profit_loss,
                        $calc['accessories_cost'],
                        $calc['birdie_price'],
                        (int) $ref['courts_confirmed'],
                        $gameId
                    ]);
                }
            } else {
                // AUTOMATION != 'Y'
                $stmtPlayers = $this->db->prepare("
                    SELECT ID
                    FROM ca_gamejoin
                    WHERE GAME_ID = ?
                      AND STATUS = 'Y'
                    ORDER BY CREATED_AT ASC
                ");
                $stmtPlayers->execute([$gameId]);
                $players = $stmtPlayers->fetchAll(PDO::FETCH_COLUMN) ?: [];
                $joined = count($players);

                $stmtRef = $this->db->prepare("
                    SELECT *
                    FROM casa_game_test_cases
                    WHERE players_joined = ? AND venue_name = 'Manual'
                    LIMIT 1
                ");
                $stmtRef->execute([$joined]);
                $ref = $stmtRef->fetch(PDO::FETCH_ASSOC);

                if ($ref) {
                    $message = (string) $ref['game_message'];
                    $this->updateJoinMessage($gameId, $message);
                }
            }

            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($isOwnerTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            \error_log('[EventModel::applyAutoConfirmAndMessage Error] ' . $e->getMessage());
            throw $e;
        }
    }
}
