<?php

declare(strict_types=1);

namespace App\Models\Tournament;

use App\Core\Model;
use PDO;

class TournamentUserModel extends Model
{
    protected string $table = 'to_users';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'CA_ID', 'REFFERAL_SOURCE', 'NAME', 'EMAIL', 'PASSWORD', 'LEVEL', 'VERIFIED_LEVEL',
        'EMAIL_PERMISSION', 'WHATSAPP_NUMBER', 'CALL_PERMISSION', 'DOB', 'GENDER',
        'GAMES', 'ADDRESS', 'CITY', 'COUNTRY', 'PROVINCE', 'AREA', 'CURRENCY',
        'TIMEZONE_OFFSET', 'USERTYPE', 'PROFILE_IMAGE', 'LOG_STATUS', 'DEL_STATUS',
        'CURRENT_RANKING', 'REMEMBER_TOKEN', 'PREMIUM', 'TEAM_ID', 'EXISTING'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
