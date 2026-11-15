<?php

declare(strict_types=1);

namespace App\Models\Tournament;

use App\Core\Model;
use PDO;

class TournamentModel extends Model
{
    protected string $table = 'to_tournaments';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'HOST_ID', 'HOST_NAME', 'CUP_NAME', 'EVENT_COUNTRY', 'EVENT_PROVINCE',
        'EVENT_CITY', 'EVENT_CURRENCY', 'EVENT_VENUE', 'EVENT_CATEGORY',
        'GENDER_CATEGORY', 'GENDER_SKILL_LEVEL', 'EVENT_TYPE', 'EVENT_DATE',
        'EVENT_TIME', 'TO_TIME', 'CANCEL_DATE', 'CANCEL_TIME', 'EVENT_COST',
        'EVENT_DISCOUNT', 'EVENT_DESCRIPTION', 'EVENT_MESSAGE', 'FACILITY_COST',
        'ACCESSORIES_COST', 'SNACKS_COST', 'TOTAL_EVENT_COST', 'TOTAL_PLAYER_COST',
        'PROFIT_LOSS', 'CLUB_COST', 'BIRDIE_USED', 'BIRDIE_COST', 'BIRDIE_PRICE',
        'JOIN_MESSAGE', 'AUTOMATION', 'STATUS', 'AMOUNT', 'PAYMENT_ID',
        'PAYMENT_DEADLINE', 'REPORTING_TIME', 'MATCH_START_TIME', 'DRAW_ANNOUNCEMENT',
        'SHUTTLE_TYPE', 'MATCH_FORMAT', 'POPUP_MESSAGE', 'PAYMENT_MAIL', 'GROUPS_REQUIRED'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function getUpcomingTournaments(int $userId): array
    {
        $sql = "SELECT e.*, b.IMGAE, 
                (SELECT COUNT(*) FROM to_teams WHERE TOURNAMENT_ID = e.ID) as joined_count,
                (SELECT COUNT(*) FROM to_users tu 
                 JOIN to_teams tt ON tu.TEAM_ID = tt.ID 
                 WHERE tt.TOURNAMENT_ID = e.ID AND tu.CA_ID = :uid1) as is_joined
                FROM to_tournaments e 
                LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
                WHERE e.STATUS = 'Active' AND e.EVENT_DATE >= CURDATE() 
                ORDER BY e.ID DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid1' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUpcomingActiveTournaments(): array
    {
        $sql = "SELECT e.*, b.IMGAE, 
                (SELECT COUNT(*) FROM to_tournaments WHERE EVENTS_ID = e.ID) as joined_count 
                FROM to_tournaments e 
                LEFT JOIN to_tournamet_banners b ON e.ID = b.EVENTS_ID 
                WHERE e.STATUS = 'Active' AND e.EVENT_DATE >= CURDATE() 
                ORDER BY e.ID DESC";

        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }
}
