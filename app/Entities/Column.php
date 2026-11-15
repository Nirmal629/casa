<?php

declare(strict_types=1);

namespace App\Entities;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_PARAMETER)]
final class Column
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
