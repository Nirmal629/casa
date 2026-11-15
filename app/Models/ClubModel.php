<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class ClubModel extends Model
{
    protected string $table = 'ca_clubs';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'host_id', 'club_name', 'game_type', 'category', 'country',
        'province', 'city', 'club_info', 'schedule', 'cost_info',
        'logo', 'join_type', 'status'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function findByHostId(int $hostId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_clubs WHERE host_id = ? ORDER BY (join_type = 'H') DESC, (status = 'Active') DESC LIMIT 1");
        $stmt->execute([$hostId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getJoinTypeByHostId(int $hostId): string
    {
        $club = $this->findByHostId($hostId);
        return strtoupper(trim((string)($club['join_type'] ?? 'A')));
    }
}
