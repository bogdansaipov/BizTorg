<?php

namespace App\Repositories\Contracts;

use App\Models\ShopProfile;
use App\Models\User;

interface ShopProfileRepositoryInterface
{
    public function findById(int $id): ?ShopProfile;
    public function findByUser(User $user): ?ShopProfile;
    public function findByUserId(int $userId): ?ShopProfile;
    public function create(int $userId, array $data): ShopProfile;
    public function updateOrCreate(int $userId, array $data): ShopProfile;
    public function isSubscribed(User $user, ShopProfile $shop): bool;
    public function subscribe(User $user, ShopProfile $shop): void;
    public function unsubscribe(User $user, ShopProfile $shop): void;
    public function addRating(ShopProfile $shop, int $userId, mixed $rating): void;
}
