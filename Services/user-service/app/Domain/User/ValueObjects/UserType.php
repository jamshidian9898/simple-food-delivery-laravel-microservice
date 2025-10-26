<?php

namespace App\Domain\User\ValueObjects;

use InvalidArgumentException;

class UserType
{
    public const CUSTOMER = 'customer';
    public const RESTAURANT = 'restaurant';
    public const COURIER = 'courier';

    private const VALID_TYPES = [
        self::CUSTOMER,
        self::RESTAURANT,
        self::COURIER,
    ];

    private string $value;

    public function __construct(string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid user type: %s. Valid types are: %s', $value, implode(', ', self::VALID_TYPES))
            );
        }
        
        $this->value = $value;
    }

    public static function customer(): self
    {
        return new self(self::CUSTOMER);
    }

    public static function restaurant(): self
    {
        return new self(self::RESTAURANT);
    }

    public static function courier(): self
    {
        return new self(self::COURIER);
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(UserType $other): bool
    {
        return $this->value === $other->value;
    }

    public function isCustomer(): bool
    {
        return $this->value === self::CUSTOMER;
    }

    public function isRestaurant(): bool
    {
        return $this->value === self::RESTAURANT;
    }

    public function isCourier(): bool
    {
        return $this->value === self::COURIER;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function getValidTypes(): array
    {
        return self::VALID_TYPES;
    }
}
