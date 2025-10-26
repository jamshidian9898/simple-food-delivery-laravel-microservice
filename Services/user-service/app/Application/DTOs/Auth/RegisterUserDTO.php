<?php

namespace App\Application\DTOs\Auth;

class RegisterUserDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $type,
        public readonly ?string $phone = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            type: $data['type'],
            phone: $data['phone'] ?? null
        );
    }
}
