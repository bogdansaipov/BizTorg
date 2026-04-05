<?php

namespace App\Providers;

use App\Models\ShopProfile;
use App\Models\ShopRating;
use App\Models\ShopSubscription;
use App\Observers\ShopRatingObserver;
use App\Observers\ShopSubscriptionObserver;
use App\Policies\ShopPolicy;
use App\Repositories\CategoryRepository;
use App\Repositories\ConversationRepository;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\ProfileRepositoryInterface;
use App\Repositories\Contracts\RegionRepositoryInterface;
use App\Repositories\Contracts\ShopProfileRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\MessageRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\RegionRepository;
use App\Repositories\ShopProfileRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class,      ProductRepository::class);
        $this->app->bind(CategoryRepositoryInterface::class,     CategoryRepository::class);
        $this->app->bind(RegionRepositoryInterface::class,       RegionRepository::class);
        $this->app->bind(ConversationRepositoryInterface::class, ConversationRepository::class);
        $this->app->bind(MessageRepositoryInterface::class,      MessageRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(ShopProfileRepositoryInterface::class,  ShopProfileRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class,      ProfileRepository::class);
        $this->app->bind(UserRepositoryInterface::class,         UserRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
            $event->extendSocialite('telegram', \SocialiteProviders\Telegram\Provider::class);
        });

        ShopSubscription::observe(ShopSubscriptionObserver::class);
        ShopRating::observe(ShopRatingObserver::class);

        Gate::policy(ShopProfile::class, ShopPolicy::class);
    }
}
