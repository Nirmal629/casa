<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_bridie')]
final class Bridie
{
    public function __construct(
        #[Column('ID')]
        public readonly ?int $id = null,
        #[Column('HOST_ID')]
        public readonly ?int $hostId = null,
        #[Column('BRIDE_COST')]
        public readonly ?float $brideCost = null,
        #[Column('BRIDIE_NAME')]
        public readonly ?string $bridieName = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['ID']) ? (int) $row['ID'] : null,
            hostId: isset($row['HOST_ID']) ? (int) $row['HOST_ID'] : null,
            brideCost: isset($row['BRIDE_COST']) ? (float) $row['BRIDE_COST'] : null,
            bridieName: $row['BRIDIE_NAME'] ?? null,
        );
    }
}
