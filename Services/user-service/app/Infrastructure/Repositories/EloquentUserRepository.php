<?php

namespace App\Infrastructure\Repositories;

use App\Domain\User\Entities\User as UserEntity;
use App\Domain\User\Repositories\UserRepositoryInterface;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserType;
use App\Models\User as EloquentUser;
use Carbon\Carbon;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function save(UserEntity $user): void
    {
        $eloquentUser = EloquentUser::find($user->getId()->getValue());
        
        if (!$eloquentUser) {
            $eloquentUser = new EloquentUser();
            $eloquentUser->id = $user->getId()->getValue();
        }

        $eloquentUser->name = $user->getName();
        $eloquentUser->email = $user->getEmail()->getValue();
        $eloquentUser->type = $user->getType()->getValue();
        $eloquentUser->phone = $user->getPhone();
        $eloquentUser->email_verified_at = $user->getEmailVerifiedAt();
        
        $eloquentUser->save();
    }

    public function findById(UserId $id): ?UserEntity
    {
        $eloquentUser = EloquentUser::find($id->getValue());
        
        return $eloquentUser ? $this->toDomainEntity($eloquentUser) : null;
    }

    public function findByEmail(Email $email): ?UserEntity
    {
        $eloquentUser = EloquentUser::where('email', $email->getValue())->first();
        
        return $eloquentUser ? $this->toDomainEntity($eloquentUser) : null;
    }

    public function findByEmailAndType(Email $email, UserType $type): ?UserEntity
    {
        $eloquentUser = EloquentUser::where('email', $email->getValue())
            ->where('type', $type->getValue())
            ->first();
        
        return $eloquentUser ? $this->toDomainEntity($eloquentUser) : null;
    }

    public function existsByEmail(Email $email): bool
    {
        return EloquentUser::where('email', $email->getValue())->exists();
    }

    public function delete(UserId $id): void
    {
        EloquentUser::where('id', $id->getValue())->delete();
    }

    public function findByType(UserType $type, int $limit = 10, int $offset = 0): array
    {
        $eloquentUsers = EloquentUser::where('type', $type->getValue())
            ->limit($limit)
            ->offset($offset)
            ->get();

        return $eloquentUsers->map(fn($user) => $this->toDomainEntity($user))->toArray();
    }

    public function countByType(UserType $type): int
    {
        return EloquentUser::where('type', $type->getValue())->count();
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
