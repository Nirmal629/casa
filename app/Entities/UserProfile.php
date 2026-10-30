<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_user_profile')]
final class UserProfile
{
    public function __construct(
        #[Column('USER_ID')] public readonly int $userId,
        #[Column('PASSWORD')] public readonly ?string $password = null,
        #[Column('DOB')] public readonly ?string $dob = null,
        #[Column('GENDER')] public readonly ?string $gender = null,
        #[Column('LEVEL')] public readonly ?string $level = null,
        #[Column('VERIFIED_LEVEL')] public readonly ?string $verifiedLevel = null,
        #[Column('WHATSAPP_NUMBER')] public readonly ?string $whatsappNumber = null,
        #[Column('ADDRESS')] public readonly ?string $address = null,
        #[Column('COUNTRY')] public readonly ?string $country = null,
        #[Column('PROVINCE')] public readonly ?string $province = null,
        #[Column('CITY')] public readonly ?string $city = null,
        #[Column('AREA')] public readonly ?string $area = null,
        #[Column('CURRENCY')] public readonly ?string $currency = null,
        #[Column('TIMEZONE_OFFSET')] public readonly ?string $timezoneOffset = null,
        #[Column('CURRENT_RANKING')] public readonly ?int $currentRanking = null,
        #[Column('GAMES')] public readonly ?string $games = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            userId: (int) ($row['USER_ID'] ?? 0), password: $row['PASSWORD'] ?? null,
            dob: $row['DOB'] ?? null, gender: $row['GENDER'] ?? null,
            level: $row['LEVEL'] ?? null, verifiedLevel: $row['VERIFIED_LEVEL'] ?? null,
            whatsappNumber: $row['WHATSAPP_NUMBER'] ?? null, address: $row['ADDRESS'] ?? null,
            city: $row['CITY'] ?? null, province: $row['PROVINCE'] ?? null,
            country: $row['COUNTRY'] ?? null, area: $row['AREA'] ?? null,
            currency: $row['CURRENCY'] ?? null, timezoneOffset: $row['TIMEZONE_OFFSET'] ?? null,
            currentRanking: isset($row['CURRENT_RANKING']) ? (int) $row['CURRENT_RANKING'] : null,
            games: $row['GAMES'] ?? null,
        );
    }
}
