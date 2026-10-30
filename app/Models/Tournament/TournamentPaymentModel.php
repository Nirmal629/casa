<?php

declare(strict_types=1);

namespace App\Models\Tournament;

use App\Core\Model;
use PDO;

class TournamentPaymentModel extends Model
{
    protected string $table = 'to_payments';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID', 'GAME_ID', 'AMOUNT', 'PAYMENT_DATE', 'PAYMENT_TIME',
        'PAYMENT_TYPE', 'DETAILS', 'MESSAGE', 'STATUS', 'APPROVED_BY',
        'APPROVED_DATE', 'DECLINED_DATE'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
