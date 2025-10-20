<?php

declare(strict_types=1);

namespace App\Domain\User;

interface UserRepository {

    /**
     * Save NHS verification token for a user
     * @param int $userId
     * @param string $token
     */
    public function saveNhsVerificationToken(int $userId, string $token): void;
    /**
     * @return User[]
     */
    public function findAll(): array;

    /**
     * @param int $id
     * @return User
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;
}
