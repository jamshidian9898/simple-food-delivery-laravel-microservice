<?php

namespace App\Application\DTOs\Auth;

class LoginUserDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly string $type
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            type: $data['type']
        );
    }
}
