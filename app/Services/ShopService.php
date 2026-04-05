<?php

namespace App\Services;

use App\Models\ShopProfile;
use App\Models\User;
use App\Repositories\Contracts\ShopProfileRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ShopService
{
    public function __construct(private readonly ShopProfileRepositoryInterface $shopProfileRepository)
    {
    }

    /**
     * Create a new shop profile for the given user.
     * Returns null if the user already owns a shop.
     */
    public function createShop(User $user, array $data): ?ShopProfile
    {
        if ($this->shopProfileRepository->findByUser($user)) {
            return null;
        }

        $shop = $this->shopProfileRepository->create($user->id, $data);

        $user->update(['isShop' => true]);

        Log::info('ShopProfile created', ['shop_id' => $shop->id, 'user_id' => $user->id]);

        return $shop;
    }

    /**
     * Update or create a shop profile for the given user.
     */
    public function updateShop(User $user, array $data): ShopProfile
    {
        $shop = $this->shopProfileRepository->updateOrCreate($user->id, $data);

        Log::info('ShopProfile updated', ['shop_id' => $shop->id, 'user_id' => $user->id]);

        return $shop;
    }

    /**
     * Upload banner and/or profile images for a shop.
     */
    public function updateImages(ShopProfile $shop, ?UploadedFile $banner, ?UploadedFile $profile): ShopProfile
    {
        if ($banner) {
            $path = $banner->store('banners', 'public');
            Log::info("Banner image uploaded: $path");
            $shop->banner_url = $path;
        }

        if ($profile) {
            $path = $profile->store('avatars', 'public');
            Log::info("Profile image uploaded: $path");
            $shop->profile_url = $path;
        }

        $shop->save();

        Log::info('ShopProfile images updated', ['shop_id' => $shop->id]);

        return $shop;
    }

    /**
     * Subscribe a user to a shop. Returns false if already subscribed.
     */
    public function subscribe(User $user, ShopProfile $shop): bool
    {
        if ($this->shopProfileRepository->isSubscribed($user, $shop)) {
            return false;
        }

        $this->shopProfileRepository->subscribe($user, $shop);

        Log::info('User subscribed to shop', ['user_id' => $user->id, 'shop_id' => $shop->id]);

        return true;
    }

    /**
     * Unsubscribe a user from a shop. Returns false if not subscribed.
     */
    public function unsubscribe(User $user, ShopProfile $shop): bool
    {
        if (!$this->shopProfileRepository->isSubscribed($user, $shop)) {
            return false;
        }

        $this->shopProfileRepository->unsubscribe($user, $shop);

        Log::info('User unsubscribed from shop', ['user_id' => $user->id, 'shop_id' => $shop->id]);

        return true;
    }
}
