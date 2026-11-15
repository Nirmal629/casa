<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;

class VenueModel extends Model
{
    protected string $table = 'ca_venue';
    protected string $primaryKey = 'ID';

    protected array $fillable = [
        'NAME', 'HOST_ID', 'COST'
    ];

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct($pdo);
    }
}
