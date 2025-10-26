<?php

namespace App\Infrastructure\Services;

use App\Domain\User\Entities\User as UserEntity;
use App\Domain\User\Services\AuthenticationServiceInterface;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserType;
use App\Models\User as EloquentUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class LaravelAuthenticationService implements AuthenticationServiceInterface
{
    private ?EloquentUser $lastAuthenticatedUser = null;

    public function authenticate(Email $email, UserType $type, string $password): ?UserEntity
    {
        $eloquentUser = EloquentUser::where('email', $email->getValue())
            ->where('type', $type->getValue())
            ->first();

        if (!$eloquentUser || !Hash::check($password, $eloquentUser->password)) {
            return null;
        }

        $this->lastAuthenticatedUser = $eloquentUser;

        return $this->toDomainEntity($eloquentUser);
    }

    public function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return Hash::check($plainPassword, $hashedPassword);
    }

    public function generateToken(UserEntity $user): string
    {
        $eloquentUser = $this->lastAuthenticatedUser;
        
        if (!$eloquentUser || 
            $eloquentUser->email !== $user->getEmail()->getValue() ||
            $eloquentUser->type !== $user->getType()->getValue()) {
            
            $eloquentUser = EloquentUser::where('email', $user->getEmail()->getValue())
                ->where('type', $user->getType()->getValue())
                ->first();
        }

        if (!$eloquentUser) {
            throw new \RuntimeException('User not found for token generation');
        }

        return JWTAuth::fromUser($eloquentUser);
    }

    public function registerUser(UserEntity $user, string $password): void
    {
        $eloquentUser = new EloquentUser();
        $eloquentUser->id = $user->getId()->getValue();
        $eloquentUser->name = $user->getName();
        $eloquentUser->email = $user->getEmail()->getValue();
        $eloquentUser->type = $user->getType()->getValue();
        $eloquentUser->phone = $user->getPhone();
        $eloquentUser->password = Hash::make($password);
        $eloquentUser->save();
    }

    private function toDomainEntity(EloquentUser $eloquentUser): UserEntity
    {
        return new UserEntity(
            id: UserId::fromString($eloquentUser->id),
            name: $eloquentUser->name,
            email: Email::fromString($eloquentUser->email),
            type: UserType::fromString($eloquentUser->type),
            phone: $eloquentUser->phone,
            emailVerifiedAt: $eloquentUser->email_verified_at ? Carbon::parse($eloquentUser->email_verified_at) : null,
            createdAt: $eloquentUser->created_at ? Carbon::parse($eloquentUser->created_at) : null,
            updatedAt: $eloquentUser->updated_at ? Carbon::parse($eloquentUser->updated_at) : null
        );
    }
}
