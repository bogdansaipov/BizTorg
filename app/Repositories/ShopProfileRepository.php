<?php

namespace App\Repositories;

use App\Models\ShopProfile;
use App\Models\User;
use App\Repositories\Contracts\ShopProfileRepositoryInterface;

class ShopProfileRepository implements ShopProfileRepositoryInterface
{
    public function __construct(private ShopProfile $model)
    {
    }

    public function findById(int $id): ?ShopProfile
    {
        return $this->model->find($id);
    }

    public function findByUser(User $user): ?ShopProfile
    {
        return $this->model->where('user_id', $user->id)->first();
    }

    public function findByUserId(int $userId): ?ShopProfile
    {
        return $this->model->where('user_id', $userId)->first();
    }

    public function create(int $userId, array $data): ShopProfile
    {
        return $this->model->create([
            'user_id'        => $userId,
            'shop_name'      => $data['shop_name'],
            'description'    => $data['description'],
            'tax_id_number'  => $data['tax_id_number'] ?? null,
            'contact_name'   => $data['contact_name'] ?? null,
            'address'        => $data['address'] ?? null,
            'phone'          => $data['phone'],
            'facebook_link'  => $data['facebook_link'] ?? null,
            'telegram_link'  => $data['telegram_link'] ?? null,
            'instagram_link' => $data['instagram_link'] ?? null,
            'website'        => $data['website'] ?? null,
            'verified'       => false,
            'rating'         => 0.0,
            'subscribers'    => 0,
            'total_reviews'  => 0,
            'views'          => 0,
            'latitude'       => $data['latitude'] ?? null,
            'longitude'      => $data['longitude'] ?? null,
        ]);
    }

    public function updateOrCreate(int $userId, array $data): ShopProfile
    {
        return $this->model->updateOrCreate(
            ['user_id' => $userId],
            [
                'shop_name'      => $data['shop_name'],
                'description'    => $data['description'],
                'tax_id_number'  => $data['tax_id_number'] ?? null,
                'contact_name'   => $data['contact_name'] ?? null,
                'address'        => $data['address'] ?? null,
                'phone'          => $data['phone'],
                'facebook_link'  => $data['facebook_link'] ?? null,
                'telegram_link'  => $data['telegram_link'] ?? null,
                'instagram_link' => $data['instagram_link'] ?? null,
                'website'        => $data['website'] ?? null,
                'latitude'       => $data['latitude'] ?? null,
                'longitude'      => $data['longitude'] ?? null,
            ]
        );
    }

    public function isSubscribed(User $user, ShopProfile $shop): bool
    {
        return $user->subscribedShops()->where('shop_id', $shop->id)->exists();
    }

    public function subscribe(User $user, ShopProfile $shop): void
    {
        $user->subscribedShops()->attach($shop->id);
        $shop->increment('subscribers');
    }

    public function unsubscribe(User $user, ShopProfile $shop): void
    {
        $user->subscribedShops()->detach($shop->id);
        $shop->decrement('subscribers');
    }

    public function addRating(ShopProfile $shop, int $userId, mixed $rating): void
    {
        $shop->addRating($userId, $rating);
    }
}
