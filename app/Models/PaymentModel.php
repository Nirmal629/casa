<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class PaymentModel extends Model
{
    protected string $table = 'ca_payment';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID', 'HOST_ID', 'GAME_ID', 'AMOUNT', 'PAYMENT_DATE', 'PAYMENT_TIME',
        'PAYMENT_TYPE', 'DETAILS', 'MESSAGE', 'REJECTION_REASON',
        'REVIEWED_BY', 'REVIEWED_AT', 'STATUS', 'IS_REINITIATED', 'PREV_REJECTION_REASON'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function getPaymentsByGame(int $gameId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_payment WHERE GAME_ID = ? ORDER BY ID DESC");
        $stmt->execute([$gameId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPaymentsByUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_payment WHERE USER_ID = ? ORDER BY ID DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
