<?php

namespace App\Repositories\Contracts;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Collection;

interface ProfileRepositoryInterface
{
    public function findUserById(int $id): User;
    public function updateUser(User $user, array $data): void;
    public function clearEmailVerified(User $user): void;
    public function updateOrCreateProfile(int $userId, array $data): Profile;
    public function createProfile(array $data): Profile;
    public function toggleFavorite(User $user, int $productId): void;
    public function isFavorited(User $user, int $productId): bool;
    public function getFavoriteProducts(User $user): Collection;
    public function getUserProducts(User $user): Collection;
    public function forgetCache(int $userId): void;
}
