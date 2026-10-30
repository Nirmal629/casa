<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_venue')]
final class Venue
{
    public function __construct(
        #[Column('ID')]
        public readonly ?int $id = null,
        #[Column('NAME')]
        public readonly ?string $name = null,
        #[Column('HOST_ID')]
        public readonly ?int $hostId = null,
        #[Column('COST')]
        public readonly ?float $cost = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['ID']) ? (int) $row['ID'] : null,
            name: $row['NAME'] ?? null,
            hostId: isset($row['HOST_ID']) ? (int) $row['HOST_ID'] : null,
            cost: isset($row['COST']) ? (float) $row['COST'] : null,
        );
    }
}
