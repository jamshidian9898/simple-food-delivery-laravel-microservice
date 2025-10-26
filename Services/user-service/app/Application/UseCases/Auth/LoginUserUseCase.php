<?php

namespace App\Application\UseCases\Auth;

use App\Application\DTOs\Auth\LoginUserDTO;
use App\Domain\User\Services\AuthenticationServiceInterface;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserType;
use InvalidArgumentException;

class LoginUserUseCase
{
    public function __construct(
        private AuthenticationServiceInterface $authService
    ) {}

    public function execute(LoginUserDTO $dto): array
    {
        $email = Email::fromString($dto->email);
        $userType = UserType::fromString($dto->type);

        // Authenticate user using domain service
        $user = $this->authService->authenticate($email, $userType, $dto->password);

        if (!$user) {
            throw new InvalidArgumentException('Invalid credentials');
        }

        // Generate JWT token
        $token = $this->authService->generateToken($user);

        return [
            'user' => [
                'id' => $user->getId()->getValue(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'type' => $user->getType()->getValue(),
                'phone' => $user->getPhone(),
                'email_verified_at' => $user->getEmailVerifiedAt()?->toISOString(),
            ],
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60
        ];
    }
}
