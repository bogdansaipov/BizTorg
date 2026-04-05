<?php

namespace App\Repositories;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ProfileRepository implements ProfileRepositoryInterface
{
    public function __construct(private User $model)
    {
    }

    public function findUserById(int $id): User
    {
        return $this->model->findOrFail($id);
    }

    public function updateUser(User $user, array $data): void
    {
        $user->update($data);
    }

    public function clearEmailVerified(User $user): void
    {
        $user->email_verified_at = null;
        $user->save();
    }

    public function updateOrCreateProfile(int $userId, array $data): Profile
    {
        return Profile::updateOrCreate(['user_id' => $userId], $data);
    }

    public function createProfile(array $data): Profile
    {
        return Profile::create($data);
    }

    public function toggleFavorite(User $user, int $productId): void
    {
        $user->favoriteProducts()->toggle($productId);
    }

    public function isFavorited(User $user, int $productId): bool
    {
        return $user->favoriteProducts()->where('product_id', $productId)->exists();
    }

    public function getFavoriteProducts(User $user): Collection
    {
        return $user->favoriteProducts()->with('images')->get();
    }

    public function getUserProducts(User $user): Collection
    {
        return $user->products()->with('images')->get();
    }

    public function forgetCache(int $userId): void
    {
        Cache::forget("user_data_{$userId}");
    }
}
