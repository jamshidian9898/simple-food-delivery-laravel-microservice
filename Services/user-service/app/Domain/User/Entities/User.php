<?php

namespace App\Domain\User\Entities;

use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserType;
use Carbon\Carbon;

class User
{
    public function __construct(
        private UserId $id,
        private string $name,
        private Email $email,
        private UserType $type,
        private ?string $phone = null,
        private ?Carbon $emailVerifiedAt = null,
        private ?Carbon $createdAt = null,
        private ?Carbon $updatedAt = null
    ) {}

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getType(): UserType
    {
        return $this->type;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmailVerifiedAt(): ?Carbon
    {
        return $this->emailVerifiedAt;
    }

    public function getCreatedAt(): ?Carbon
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?Carbon
    {
        return $this->updatedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = Carbon::now();
    }

    public function updatePhone(?string $phone): void
    {
        $this->phone = $phone;
        $this->updatedAt = Carbon::now();
    }

    public function markEmailAsVerified(): void
    {
        $this->emailVerifiedAt = Carbon::now();
        $this->updatedAt = Carbon::now();
    }

    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }

    public function isCustomer(): bool
    {
        return $this->type->equals(UserType::customer());
    }

    public function isRestaurant(): bool
    {
        return $this->type->equals(UserType::restaurant());
    }

    public function isCourier(): bool
    {
        return $this->type->equals(UserType::courier());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'name' => $this->name,
            'email' => $this->email->getValue(),
            'type' => $this->type->getValue(),
            'phone' => $this->phone,
            'email_verified_at' => $this->emailVerifiedAt?->toISOString(),
            'created_at' => $this->createdAt?->toISOString(),
            'updated_at' => $this->updatedAt?->toISOString(),
        ];
    }
}
