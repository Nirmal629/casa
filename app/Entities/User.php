<?php

declare(strict_types=1);

namespace App\Entities;


#[Table('ca_users')]
final class User
{
    public function __construct(
        #[Column('ID')] public readonly ?int $id = null,
        #[Column('NAME')] public readonly ?string $name = null,
        #[Column('EMAIL')] public readonly ?string $email = null,
        #[Column('PASSWORD_HASH')] public readonly ?string $passwordHash = null,
        #[Column('USERTYPE')] public readonly ?string $userType = null,
        #[Column('PROFILE_IMAGE')] public readonly ?string $profileImage = null,
        #[Column('LOG_STATUS')] public readonly ?string $logStatus = null,
        #[Column('DEL_STATUS')] public readonly ?string $delStatus = null,
        #[Column('STATUS')] public readonly ?string $status = null,
        #[Column('PREMIUM')] public readonly ?string $premium = null,
        #[Column('REMEMBER_TOKEN')] public readonly ?string $rememberToken = null,
        #[Column('CREATED_AT')] public readonly mixed $createdAt = null,
        #[Column('UPDATED_AT')] public readonly mixed $updatedAt = null,
        #[Column('DELETED_AT')] public readonly mixed $deletedAt = null,
    ) {
    }

    public static function fromArray(array $row): self
    {
        return new self(
            id: isset($row['ID']) ? (int) $row['ID'] : null,
            name: $row['NAME'] ?? null,
            email: $row['EMAIL'] ?? null,
            passwordHash: $row['PASSWORD_HASH'] ?? null,
            userType: $row['USERTYPE'] ?? null,
            profileImage: $row['PROFILE_IMAGE'] ?? null,
            logStatus: $row['LOG_STATUS'] ?? null,
            delStatus: $row['DEL_STATUS'] ?? null,
            status: $row['STATUS'] ?? null,
            premium: $row['PREMIUM'] ?? null,
            rememberToken: $row['REMEMBER_TOKEN'] ?? null,
            createdAt: $row['CREATED_AT'] ?? null,
            updatedAt: $row['UPDATED_AT'] ?? null,
            deletedAt: $row['DELETED_AT'] ?? null,
        );
    }
}
