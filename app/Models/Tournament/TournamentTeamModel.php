<?php

declare(strict_types=1);

namespace App\Models\Tournament;

use App\Core\Model;
use PDO;

class TournamentTeamModel extends Model
{
    protected string $table = 'to_teams';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'NAME', 'TOURNAMENT_ID', 'GROUP_NAME'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
