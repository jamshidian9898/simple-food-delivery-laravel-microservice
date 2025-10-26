<?php

namespace App\Application\UseCases\Auth;

use App\Application\DTOs\Auth\RegisterUserDTO;
use App\Domain\User\Entities\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\Services\AuthenticationServiceInterface;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserType;
use InvalidArgumentException;

class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AuthenticationServiceInterface $authService
    ) {}

    public function execute(RegisterUserDTO $dto): array
    {
        $email = Email::fromString($dto->email);
        $userType = UserType::fromString($dto->type);

        // Check if user already exists
        if ($this->userRepository->existsByEmail($email)) {
            throw new InvalidArgumentException('User with this email already exists');
        }

        // Create domain entity
        $user = new User(
            id: UserId::generate(),
            name: $dto->name,
            email: $email,
            type: $userType,
            phone: $dto->phone
        );

        // Register user with password using domain service
        $this->authService->registerUser($user, $dto->password);

        // generate token
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
