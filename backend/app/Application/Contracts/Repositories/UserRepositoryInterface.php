<?php

declare(strict_types=1);

namespace App\Application\Contracts\Repositories;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?Authenticatable;

    public function findById(string $id): ?Authenticatable;

    public function existsByEmail(string $email): bool;

    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): Authenticatable;

    /**
     * @param  array{name: string, email: string}  $attributes
     */
    public function updateProfile(string $id, array $attributes): Authenticatable;
}
