<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Contracts\Repositories\UserRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?Authenticatable
    {
        return User::where('email', mb_strtolower($email))->first();
    }

    public function findById(string $id): ?Authenticatable
    {
        return User::find($id);
    }

    public function existsByEmail(string $email): bool
    {
        return User::where('email', mb_strtolower($email))->exists();
    }

    public function create(array $attributes): Authenticatable
    {
        return User::create([
            'name' => $attributes['name'],
            'email' => mb_strtolower($attributes['email']),
            'password' => $attributes['password'],
        ]);
    }

    public function updateProfile(string $id, array $attributes): Authenticatable
    {
        $user = User::findOrFail($id);
        $user->fill([
            'name' => $attributes['name'],
            'email' => mb_strtolower($attributes['email']),
        ])->save();

        return $user;
    }
}
