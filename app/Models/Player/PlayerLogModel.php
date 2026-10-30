<?php

declare(strict_types=1);

namespace App\Models\Player;

use App\Core\Model;
use PDO;

class PlayerLogModel extends Model
{
    protected string $table = 'ca_player_logs';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID', 'HOST_ID', 'GAME_ID', 'ACTIVITY_TYPE', 'DESCRIPTION', 'META', 'IP_ADDRESS'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function logActivity(int $userId, string $activityType, string $description, ?int $hostId = null, ?int $gameId = null, ?string $meta = null): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        return (bool) $this->create([
            'USER_ID'       => $userId,
            'HOST_ID'       => $hostId,
            'GAME_ID'       => $gameId,
            'ACTIVITY_TYPE' => $activityType,
            'DESCRIPTION'   => $description,
            'META'          => $meta,
            'IP_ADDRESS'    => $ip
        ]);
    }
}
