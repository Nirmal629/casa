<?php

declare(strict_types=1);

namespace App\Models\Host;

use App\Core\Model;
use PDO;

class HostPlayerCarryForwardModel extends Model
{
    protected string $table = 'host_player_carry_forward';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'host_id', 'player_id', 'carry_month', 'carry_year', 'opening_balance',
        'balance_type', 'source_month', 'source_year', 'is_locked'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
