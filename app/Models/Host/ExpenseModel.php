<?php

declare(strict_types=1);

namespace App\Models\Host;

use App\Core\Model;
use PDO;

class ExpenseModel extends Model
{
    protected string $table = 'ca_expense';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'USER_ID', 'HOST_ID', 'VENUE', 'TYPE', 'AMOUNT', 'CURRENCY', 'EXPENSE_DATE', 'EXPENSE_TIME', 'STATUS'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }

    public function getExpensesByHost(int $hostId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM ca_expense WHERE HOST_ID = ? ORDER BY ID DESC");
        $stmt->execute([$hostId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
