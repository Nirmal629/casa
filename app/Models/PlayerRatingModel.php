<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class PlayerRatingModel extends Model
{
    protected string $table = 'ca_player_ratings';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'RATER_ID',
        'RATED_PLAYER_ID',
        'SKILL_LEVEL',
        'RANKING',
        'RATING_DATE'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    //Retrieve all rating records for a given player.
    public function getPlayerRatings(int $playerId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE RATED_PLAYER_ID = ? ORDER BY ID DESC");
        $stmt->execute([$playerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // Check if a rater has already rated a specific player.

    public function findByRaterAndPlayer(int $raterId, int $ratedPlayerId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE RATER_ID = ? AND RATED_PLAYER_ID = ? LIMIT 1");
        $stmt->execute([$raterId, $ratedPlayerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // Save or update a player rating record.

    public function saveRating(int $raterId, int $ratedPlayerId, string $skillLevel, int $rank): bool
    {
        $existing = $this->findByRaterAndPlayer($raterId, $ratedPlayerId);

        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE {$this->table} 
                SET SKILL_LEVEL = ?, RANKING = ?, RATING_DATE = NOW() 
                WHERE ID = ?
            ");
            return $stmt->execute([$skillLevel, $rank, $existing['ID']]);
        }

        $stmt = $this->db->prepare("
            INSERT INTO {$this->table} (RATER_ID, RATED_PLAYER_ID, SKILL_LEVEL, RANKING, RATING_DATE) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$raterId, $ratedPlayerId, $skillLevel, $rank]);
    }

    //Retrieve aggregated player activity, daily games breakdown, status, and rankings.
    public function getPlayerStats(array $filters = []): array
    {
        $params = [];
        $innerWhere = $this->buildInnerWhere($filters, $params);
        $outerWhere = $this->buildOuterWhere($filters, $params);
        $sql = $this->getPlayerStatsSql($innerWhere, $outerWhere);

        if (!empty($filters['limit']) && (int) $filters['limit'] > 0) {
            $limit = (int) $filters['limit'];
            $offset = max(0, (int) ($filters['offset'] ?? 0));
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    //Build the inner WHERE clause for raw game joins.
     
    private function buildInnerWhere(array $filters, array &$params): string
    {
        $where = "WHERE BINARY u.USERTYPE = 'Player' AND gj.CONFIRMED = 'Y'";

        if (!empty($filters['host_id']) && (int) $filters['host_id'] > 0) {
            $where .= " AND gj.HOST_ID = ?";
            $params[] = (int) $filters['host_id'];
        }

        if (!empty($filters['player_id']) && (int) $filters['player_id'] > 0) {
            $where .= " AND u.ID = ?";
            $params[] = (int) $filters['player_id'];
        }

        return $where;
    }

    //Build the outer WHERE clause for computed/aggregated player statistics.
    
    private function buildOuterWhere(array $filters, array &$params): string
    {
        $where = [];

        // 1. Skill Level filter
        if (!empty($filters['skill_level'])) {
            $lvl = trim((string) $filters['skill_level']);
            if (strcasecmp($lvl, 'advanced') === 0 || strcasecmp($lvl, 'advance') === 0) {
                $where[] = "(BINARY t.skill_level = 'Advanced' OR BINARY t.skill_level = 'Advance')";
            } else {
                $where[] = "BINARY t.skill_level = ?";
                $params[] = $lvl;
            }
        }

        // 2. Gender filter
        if (!empty($filters['gender'])) {
            $g = trim((string) $filters['gender']);
            if (strcasecmp($g, 'male') === 0 || strcasecmp($g, 'm') === 0) {
                $where[] = "(BINARY t.gender = 'Male' OR BINARY t.gender = 'M')";
            } elseif (strcasecmp($g, 'female') === 0 || strcasecmp($g, 'f') === 0) {
                $where[] = "(BINARY t.gender = 'Female' OR BINARY t.gender = 'F')";
            } else {
                $where[] = "BINARY t.gender = ?";
                $params[] = $g;
            }
        }

        // 3. Status filter ('Active', 'Regular', 'Inactive', 'New')
        if (!empty($filters['status'])) {
            $where[] = "BINARY t.status = ?";
            $params[] = trim((string) $filters['status']);
        }

        // 4. Search query (Player name, email, or ID)
        if (isset($filters['search']) && trim((string) $filters['search']) !== '') {
            $searchVal = trim((string) $filters['search']);
            $searchTerm = '%' . $searchVal . '%';
            $where[] = "(t.player LIKE ? OR t.player_name LIKE ? OR t.player_email LIKE ? OR CAST(t.player_id AS CHAR) = ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchVal;
        }

        return !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    }

    // Construct the full aggregated query for player stats, attendance, and ranking.
    
    private function getPlayerStatsSql(string $innerWhere, string $outerWhere): string
    {
        return "
            SELECT 
                t.*,
                t.status AS computed_status,
                t.preferred AS computed_preferred
            FROM (
                SELECT 
                    t_inner.*,
                    CASE 
                        WHEN t_inner.total_games = 0 OR t_inner.last_played IS NULL THEN 'New'
                        WHEN t_inner.days_ago > 60 THEN 'Inactive'
                        WHEN t_inner.days_ago <= 7 THEN 'Active'
                        ELSE 'Regular'
                    END AS status,

                    CASE
                        WHEN t_inner.total_games = 0 THEN 'Saturday'
                        WHEN (t_inner.sat + t_inner.sun) > (t_inner.mon + t_inner.tue + t_inner.wed + t_inner.thu + t_inner.fri)
                             OR (t_inner.sat > 0 AND t_inner.sat >= GREATEST(t_inner.mon, t_inner.tue, t_inner.wed, t_inner.thu, t_inner.fri)) THEN
                            CASE WHEN t_inner.sat >= t_inner.sun THEN 'Saturday' ELSE 'Sunday' END
                        ELSE 'Weekday'
                    END AS preferred
                FROM (
                    SELECT
                        u.ID AS player_id,
                        u.NAME AS player,
                        u.NAME AS player_name,
                        u.EMAIL AS player_email,
                        u.GENDER AS gender,
                        COALESCE(NULLIF(u.VERIFIED_LEVEL, ''), u.LEVEL, '') AS skill_level,
                        COALESCE(NULLIF(u.VERIFIED_LEVEL, ''), u.LEVEL, '') AS verified_level,
                        u.CURRENT_RANKING AS rank,
                        u.CURRENT_RANKING AS current_ranking,
                        u.PREV_RANKING AS prev_ranking,

                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 0 THEN 1 ELSE 0 END) AS mon,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 0 THEN 1 ELSE 0 END) AS Monday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 1 THEN 1 ELSE 0 END) AS tue,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 1 THEN 1 ELSE 0 END) AS Tuesday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 2 THEN 1 ELSE 0 END) AS wed,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 2 THEN 1 ELSE 0 END) AS Wednesday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 3 THEN 1 ELSE 0 END) AS thu,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 3 THEN 1 ELSE 0 END) AS Thursday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 4 THEN 1 ELSE 0 END) AS fri,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 4 THEN 1 ELSE 0 END) AS Friday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 5 THEN 1 ELSE 0 END) AS sat,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 5 THEN 1 ELSE 0 END) AS Saturday,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 6 THEN 1 ELSE 0 END) AS sun,
                        SUM(CASE WHEN WEEKDAY(e.EVENT_DATE) = 6 THEN 1 ELSE 0 END) AS Sunday,

                        COUNT(e.ID) AS total_games,
                        MAX(e.EVENT_DATE) AS last_played,
                        DATEDIFF(CURRENT_DATE(), MAX(e.EVENT_DATE)) AS days_ago,
                        DATEDIFF(CURRENT_DATE(), MAX(e.EVENT_DATE)) AS days_since_last_game

                    FROM ca_gamejoin gj
                    INNER JOIN ca_users u ON u.ID = gj.USER_ID
                    INNER JOIN ca_events e ON e.ID = gj.GAME_ID
                    {$innerWhere}
                    GROUP BY
                        u.ID,
                        u.NAME,
                        u.EMAIL,
                        u.GENDER,
                        u.VERIFIED_LEVEL,
                        u.LEVEL,
                        u.CURRENT_RANKING,
                        u.PREV_RANKING
                ) AS t_inner
            ) AS t
            {$outerWhere}
            ORDER BY t.rank IS NULL, t.rank ASC, t.player_id ASC
        ";
    }
}
