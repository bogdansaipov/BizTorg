<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\RegionRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProfileService
{
    public function __construct(
        private readonly ProfileRepositoryInterface $profileRepository,
        private readonly RegionRepositoryInterface  $regionRepository,
    ) {
    }

    /**
     * Update the authenticated user's data and profile (web).
     */
    public function update(User $user, array $data, ?UploadedFile $avatarFile): void
    {
        $this->profileRepository->updateUser($user, [
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->isDirty('email')) {
            $this->profileRepository->clearEmailVerified($user);
        }

        $avatarPath = $user->profile->avatar ?? null;

        if ($avatarFile) {
            if ($avatarPath && \Storage::exists('public/' . $avatarPath)) {
                \Storage::delete('public/' . $avatarPath);
            }
            $avatarPath = $avatarFile->store('avatars', 'public');
        }

        $this->profileRepository->updateOrCreateProfile($user->id, [
            'avatar'     => $avatarPath,
            'phone'      => $data['phone'],
            'address'    => $data['address'],
            'region_id'  => $data['region_id'],
            'latitude'   => $data['latitude'],
            'longitude'  => $data['longitude'],
        ]);
    }

    /**
     * Create a new profile (web store).
     */
    public function store(array $data, ?UploadedFile $avatarFile): Profile
    {
        if ($avatarFile) {
            $data['avatar'] = $avatarFile->store('avatars', 'public');
        } else {
            $data['avatar'] = null;
        }

        return $this->profileRepository->createProfile($data);
    }

    /**
     * Toggle a favorite product for the user, return new isFavorited state.
     */
    public function toggleFavorite(User $user, int $productId): bool
    {
        $this->profileRepository->toggleFavorite($user, $productId);
        return $this->profileRepository->isFavorited($user, $productId);
    }

    /**
     * Update API profile (by user ID).
     */
    public function updateApiProfile(int $userId, array $data): void
    {
        $user = $this->profileRepository->findUserById($userId);

        $this->profileRepository->updateUser($user, [
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        if ($user->wasChanged('email')) {
            $this->profileRepository->clearEmailVerified($user);
        }

        $this->profileRepository->updateOrCreateProfile($user->id, [
            'phone'     => $data['phone'],
            'region_id' => $data['region_id'] ?? null,
        ]);

        $this->profileRepository->forgetCache($userId);
    }

    /**
     * Get full user data for the API profile endpoint.
     */
    public function getApiUserData(int $id, ?int $currentUserId): array
    {
        $user        = $this->profileRepository->findUserById($id);
        $userProfile = $user->profile;
        $region      = $userProfile ? $this->regionRepository->findBySlug($userProfile->region_id ?? '') : null;

        $isAlreadySubscriber = false;
        $hasAlreadyRated     = false;
        $shopProfile         = null;

        if ($user->isShop) {
            $shopProfile = $user->shopProfile;

            if ($currentUserId && $shopProfile) {
                $isAlreadySubscriber = $shopProfile->subscribers()->where('user_id', $currentUserId)->exists();
                $hasAlreadyRated     = $shopProfile->raters()->where('user_id', $currentUserId)->exists();
            }
        }

        return [
            'user'                => $user,
            'user_profile'        => $userProfile,
            'region'              => $region,
            'isShop'              => $user->isShop,
            'shop_profile'        => $shopProfile,
            'isAlreadySubscriber' => $isAlreadySubscriber,
            'hasAlreadyRated'     => $hasAlreadyRated,
        ];
    }
}
