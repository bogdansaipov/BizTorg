<?php

namespace App\Policies;

use App\Models\ShopProfile;
use App\Models\User;

class ShopPolicy
{
    /**
     * The user can manage (update images, view private data) only their own shop.
     */
    public function manage(User $user, ShopProfile $shop): bool
    {
        return $user->id === $shop->user_id;
    }
}
