<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_clubs')]
final class Club
{
    public function __construct(
        #[Column('id')]
        public readonly ?int $id = null,
        #[Column('host_id')]
        public readonly ?int $hostId = null,
        #[Column('club_name')]
        public readonly ?string $clubName = null,
        #[Column('game_type')]
        public readonly ?string $gameType = null,
        #[Column('category')]
        public readonly ?string $category = null,
        #[Column('country')]
        public readonly ?string $country = null,
        #[Column('province')]
        public readonly ?string $province = null,
        #[Column('city')]
        public readonly ?string $city = null,
        #[Column('club_info')]
        public readonly ?string $clubInfo = null,
        #[Column('schedule')]
        public readonly ?string $schedule = null,
        #[Column('cost_info')]
        public readonly ?string $costInfo = null,
        #[Column('logo')]
        public readonly ?string $logo = null,
        #[Column('join_type')]
        public readonly ?string $joinType = null,
        #[Column('status')]
        public readonly ?string $status = null,
        #[Column('created_at')]
        public readonly mixed $createdAt = null,
        #[Column('updated_at')]
        public readonly mixed $updatedAt = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['id']) ? (int) $row['id'] : null,
            hostId: isset($row['host_id']) ? (int) $row['host_id'] : null,
            clubName: $row['club_name'] ?? null,
            gameType: $row['game_type'] ?? null,
            category: $row['category'] ?? null,
            country: $row['country'] ?? null,
            province: $row['province'] ?? null,
            city: $row['city'] ?? null,
            clubInfo: $row['club_info'] ?? null,
            schedule: $row['schedule'] ?? null,
            costInfo: $row['cost_info'] ?? null,
            logo: $row['logo'] ?? null,
            joinType: $row['join_type'] ?? null,
            status: $row['status'] ?? null,
            createdAt: $row['created_at'] ?? null,
            updatedAt: $row['updated_at'] ?? null,
        );
    }
}
