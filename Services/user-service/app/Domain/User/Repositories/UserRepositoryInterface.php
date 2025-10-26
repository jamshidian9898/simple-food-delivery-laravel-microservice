<?php

namespace App\Domain\User\Repositories;

use App\Domain\User\Entities\User;
use App\Domain\User\ValueObjects\Email;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\User\ValueObjects\UserType;

interface UserRepositoryInterface
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function findByEmailAndType(Email $email, UserType $type): ?User;

    public function existsByEmail(Email $email): bool;

    public function delete(UserId $id): void;

    public function findByType(UserType $type, int $limit = 10, int $offset = 0): array;

    public function countByType(UserType $type): int;
}
