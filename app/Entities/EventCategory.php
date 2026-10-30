<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_event_category')]
final class EventCategory
{
    public function __construct(
        #[Column('ID')]
        public readonly ?int $id = null,
        #[Column('NAME')]
        public readonly ?string $name = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['ID']) ? (int) $row['ID'] : null,
            name: $row['NAME'] ?? null,
        );
    }
}
