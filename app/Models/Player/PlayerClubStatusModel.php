<?php

declare(strict_types=1);

namespace App\Models\Player;

use App\Core\Model;
use PDO;

class PlayerClubStatusModel extends Model
{
    protected string $table = 'ca_player_club_status';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'club_id', 'host_id', 'player_id', 'status'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function findMembership(int $hostId, int $playerId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_player_club_status WHERE host_id = ? AND player_id = ? LIMIT 1");
        $stmt->execute([$hostId, $playerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
