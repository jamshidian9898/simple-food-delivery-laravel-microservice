<?php

namespace App\Domain\User\Services;

use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserType;

interface AuthenticationServiceInterface
{
    /**
     * Authenticate user with email, type and password
     */
    public function authenticate(Email $email, UserType $type, string $password): ?User;

    /**
     * Verify if password matches user's stored password
     */
    public function verifyPassword(string $plainPassword, string $hashedPassword): bool;
    
    /**
     * Generate JWT token
     */
    public function generateToken(User $user): string;

    /**
     * Register new user with password
     */
    public function registerUser(User $user, string $password): void;
}
