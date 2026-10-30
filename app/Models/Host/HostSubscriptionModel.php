<?php

declare(strict_types=1);

namespace App\Models\Host;

use App\Core\Model;
use PDO;

class HostSubscriptionModel extends Model
{
    protected string $table = 'ca_host_subscription';
    protected string $primaryKey = 'id';

    protected array $fillable = [
        'host_id', 'player_id', 'sub_year', 'sub_month', 'amount', 'currency',
        'games_count', 'expense_id', 'status', 'is_locked', 'is_carried',
        'applied_by', 'applied_at'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
