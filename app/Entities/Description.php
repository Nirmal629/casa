<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_description')]
final class Description
{
    public function __construct(
        #[Column('ID')]
        public readonly ?int $id = null,
        #[Column('DESCRIPTION')]
        public readonly ?string $description = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['ID']) ? (int) $row['ID'] : null,
            description: $row['DESCRIPTION'] ?? null,
        );
    }
}
