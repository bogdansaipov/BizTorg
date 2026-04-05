<?php

namespace App\Services;

use App\Jobs\PostToSocialMediaJob;
use App\Jobs\RemoveFromSocialMediaJob;
use App\Jobs\SendFcmNotification;
use App\Jobs\UpdateSocialMediaPostsJob;
use App\Models\Product;
use App\Models\ShopProfile;
use App\Models\User;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly TelegramService            $telegramService,
        private readonly FacebookService            $facebookService,
        private readonly InstagramService           $instagramService,
    ) {
    }

    // ── Web product creation ──────────────────────────────────────────────────

    public function createWebProduct(User $user, array $data, Request $request): void
    {
        $slug = Str::slug($data['name'], '-');

        DB::transaction(function () use ($user, $data, $request, $slug) {
            $product = $this->productRepository->create([
                'name'           => $data['name'],
                'slug'           => $slug,
                'subcategory_id' => $data['subcategory_id'],
                'description'    => $data['description'],
                'price'          => $data['price'],
                'currency'       => $data['currency'],
                'latitude'       => $data['latitude'],
                'longitude'      => $data['longitude'],
                'type'           => $data['type'],
                'region_id'      => $data['child_region_id'],
                'user_id'        => $user->id,
            ]);

            foreach (['image1', 'image2', 'image3', 'image4'] as $field) {
                if ($request->hasFile($field)) {
                    try {
                        $path = $request->file($field)->store('product-images', 'public');
                        Log::info("Image uploaded: $path");
                        $this->productRepository->createImage($product->id, $path);
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image: " . $e->getMessage());
                    }
                }
            }

            if (!empty($data['attributes'])) {
                $this->productRepository->syncAttributes($product, $data['attributes']);
            }

            $images = $this->productRepository->getImages($product->id)
                ->pluck('image_url')
                ->map(fn ($p) => asset("storage/{$p}"))
                ->toArray();

            $this->postToTelegram($product, $images);
            $this->postToFacebook($product);
            $this->postToInstagram($product, $images);
        });
    }

    // ── Web product update ────────────────────────────────────────────────────

    public function updateWebProduct(int $productId, array $data, Request $request): void
    {
        $slug = Str::slug($data['name'], '-') . '-' . $productId;

        DB::transaction(function () use ($productId, $data, $request, $slug) {
            $product = $this->productRepository->findById($productId);

            $this->productRepository->update($product, [
                'name'           => $data['name'],
                'slug'           => $slug,
                'subcategory_id' => $data['subcategory_id'],
                'description'    => $data['description'],
                'price'          => $data['price'],
                'currency'       => $data['currency'],
                'latitude'       => $data['latitude'],
                'longitude'      => $data['longitude'],
                'type'           => $data['type'],
                'region_id'      => $data['child_region_id'],
            ]);

            foreach (['image1', 'image2', 'image3', 'image4'] as $field) {
                if ($request->hasFile($field)) {
                    $path = $request->file($field)->store('product-images', 'public');
                    $this->productRepository->createImage($product->id, $path);
                }
            }

            if (!empty($data['attributes'])) {
                $this->productRepository->syncAttributes($product, $data['attributes']);
            }
        });
    }

    // ── API product creation ──────────────────────────────────────────────────

    public function createApiProduct(array $data, Request $request): void
    {
        $slug = Str::slug($data['name'], '-');

        DB::transaction(function () use ($data, $request, $slug) {
            $product = $this->productRepository->create([
                'name'           => $data['name'],
                'slug'           => $slug,
                'subcategory_id' => $data['subcategory_id'],
                'description'    => $data['description'],
                'price'          => $data['price'],
                'currency'       => $data['currency'],
                'latitude'       => $data['latitude'],
                'longitude'      => $data['longitude'],
                'type'           => $data['type'],
                'region_id'      => $data['child_region_id'],
                'user_id'        => $data['uuid'],
                'showNumber'     => $data['showNumber'],
                'number'         => $data['number'] ?? null,
            ]);

            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    try {
                        $path = $image->store('product-images', 'public');
                        $this->productRepository->createImage($product->id, $path);
                        $imagePaths[] = $path;
                        Log::info("Image uploaded: $path");
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image: " . $e->getMessage());
                    }
                }
            }

            if (!empty($data['attributes'])) {
                $this->productRepository->syncAttributes($product, $data['attributes']);
            }

            $user = User::findOrFail($data['uuid']);

            $this->dispatchFcmToSubscribers($user, $product, $imagePaths);

            $images = $this->productRepository->getImages($product->id)
                ->pluck('image_url')
                ->map(fn ($p) => asset("storage/{$p}"))
                ->toArray();

            $contactName  = $user->isShop ? optional($user->shopProfile)->contact_name : $user->name;
            $contactPhone = $user->isShop ? optional($user->shopProfile)->phone : optional($user->profile)->phone;
            $shopName     = $user->isShop ? optional($user->shopProfile)->shop_name : null;

            PostToSocialMediaJob::dispatch($product, $contactName, $contactPhone, $images, $user->isShop, $shopName);
        });
    }

    // ── API product update ────────────────────────────────────────────────────

    public function updateApiProduct(int $productId, User $user, array $data, Request $request): void
    {
        DB::transaction(function () use ($productId, $user, $data, $request) {
            $product = $this->productRepository->findOwnedById($productId, $user->id);

            if (!$product) {
                abort(404, 'Product not found or permission denied');
            }

            $slug = Str::slug($data['name'], '-');

            $this->productRepository->update($product, [
                'name'           => $data['name'],
                'slug'           => $slug,
                'subcategory_id' => $data['subcategory_id'],
                'description'    => $data['description'],
                'price'          => $data['price'],
                'currency'       => $data['currency'],
                'latitude'       => $data['latitude'],
                'longitude'      => $data['longitude'],
                'type'           => $data['type'],
                'region_id'      => $data['child_region_id'],
                'user_id'        => $user->id,
            ]);

            $existingImages = $product->images->map(fn ($img) => asset('storage/' . $img->image_url))->toArray();
            $newImageUrls   = [];

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    try {
                        $path = $image->store('product-images', 'public');
                        $this->productRepository->createImage($product->id, $path);
                        $newImageUrls[] = asset('storage/' . $path);
                    } catch (\Exception $e) {
                        Log::error("Failed to upload image: " . $e->getMessage());
                    }
                }
            }

            if (!empty($data['attributes'])) {
                $this->productRepository->syncAttributes($product, $data['attributes']);
            }

            $allImages = array_merge($existingImages, $newImageUrls);
            UpdateSocialMediaPostsJob::dispatch($product->id, array_merge($data, ['images' => $allImages]));
        });
    }

    // ── Remove product ────────────────────────────────────────────────────────

    public function removeApiProduct(int $productId, User $user): void
    {
        $product = $this->productRepository->findOwnedById($productId, $user->id);

        if (!$product) {
            abort(404, 'Product not found or permission denied');
        }

        RemoveFromSocialMediaJob::dispatch(
            $product->telegram_post_id,
            $product->facebook_post_id,
            $product->insta_post_id
        );

        $this->productRepository->delete($product);
    }

    // ── Delete image ──────────────────────────────────────────────────────────

    public function deleteWebImage(int $imageId): void
    {
        $image = $this->productRepository->findImageById($imageId);

        if (Storage::exists('public/' . $image->image_url)) {
            Storage::delete('public/' . $image->image_url);
        }

        $this->productRepository->deleteImage($image);
    }

    public function deleteApiImage(int $imageId, User $user): void
    {
        $image = $this->productRepository->findImageForUser($imageId, $user->id);

        if (Storage::disk('public')->exists($image->image_url)) {
            Storage::disk('public')->delete($image->image_url);
        }

        $this->productRepository->deleteImage($image);
    }

    // ── Detail pages ──────────────────────────────────────────────────────────

    public function getWebProductDetail(string $slug): array
    {
        $product      = $this->productRepository->findBySlug($slug);
        $user         = $product->user;
        $attributes   = $this->getProductAttributes($product);
        $userProducts = $this->productRepository->getByUser($user->id, [], $product->id, 10);
        $sameProducts = $this->productRepository->getSameProducts(
            $product->subcategory->id, $product->id, $user->products->pluck('id')->toArray(), [], 10
        );

        return compact('product', 'user', 'attributes', 'userProducts', 'sameProducts');
    }

    public function getApiProductDetail(int $id, ?int $requestingUserId = null): array
    {
        $product = $this->productRepository->findById($id, ['images', 'region.parent']);

        return $this->buildApiProductDetailResponse($product, $requestingUserId);
    }

    public function getApiProductDetailBySlug(string $slug, ?int $requestingUserId = null): array
    {
        $product = $this->productRepository->findBySlug($slug, ['images', 'region.parent']);

        if (!$product) {
            abort(404);
        }

        return $this->buildApiProductDetailResponse($product, $requestingUserId);
    }

    public function getWebProductForEdit(int $id): array
    {
        $product           = $this->productRepository->findById($id, ['attributes.attributeValues']);
        $productImages     = $product->images->map(fn ($img) => ['image_url' => $img->image_url]);
        $attributes        = $this->getProductAttributes($product);
        $productAttributes = $attributes->mapWithKeys(function ($attribute) {
            $selected = $attribute->attributeValues->first();
            return [$attribute->id => ['id' => $selected->id ?? null, 'name' => $selected->value ?? 'No value assigned']];
        });

        return compact('product', 'productImages', 'attributes', 'productAttributes');
    }

    public function getApiProductForEdit(int $id): array
    {
        $product       = $this->productRepository->findById($id, ['region.parent', 'attributes.attributeValues', 'images']);
        $productImages = $product->images->map(fn ($img) => ['image_url' => $img->image_url]);
        $attributes    = $this->getProductAttributes($product);
        $productAttributes = $attributes->mapWithKeys(function ($attribute) {
            $selected = $attribute->attributeValues->first();
            return [$attribute->id => ['id' => $selected->id ?? null, 'name' => $selected->value ?? 'No value assigned']];
        });

        return compact('product', 'productImages', 'productAttributes');
    }

    // ── Favorites ─────────────────────────────────────────────────────────────

    public function toggleFavorite(User $user, int $productId): bool
    {
        $user->favoriteProducts()->toggle($productId);
        return $user->favoriteProducts()->where('product_id', $productId)->exists();
    }

    public function getFavoriteIds(User $user): mixed
    {
        return $user->favoriteProducts()->pluck('product_id');
    }

    public function getFavoritesOfUser(User $user): mixed
    {
        return $user->favoriteProducts()->with('images')->get();
    }

    public function getApiUserProducts(User $user): mixed
    {
        return $user->products()->with('images')->get();
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getProductAttributes(Product $product): mixed
    {
        return $product->subcategory->attributes()->with(['attributeValues' => function ($query) use ($product) {
            $query->whereExists(function ($q) use ($product) {
                $q->from('product_attribute_values')
                  ->whereColumn('product_attribute_values.attribute_value_id', 'attribute_values.id')
                  ->where('product_attribute_values.product_id', $product->id);
            });
        }])->get();
    }

    private function buildApiProductDetailResponse(Product $product, ?int $requestingUserId): array
    {
        $user         = $product->user;
        $profile      = $user->profile;
        $attributes   = $this->getProductAttributes($product);
        $userProducts = $this->productRepository->getByUser($user->id, ['images', 'region.parent'], $product->id, 10)
            ->map(fn ($p) => $this->mapProductPreview($p));
        $sameProducts = $this->productRepository->getSameProducts(
            $product->subcategory->id, $product->id, $user->products->pluck('id')->toArray(), ['images', 'region.parent'], 10
        )->map(fn ($p) => $this->mapProductPreview($p, true));

        $shopProfile         = $user->isShop ? $user->shopProfile : null;
        $isAlreadySubscriber = false;
        $hasAlreadyRated     = false;

        if ($requestingUserId && $user->isShop && $shopProfile) {
            $isAlreadySubscriber = $shopProfile->subscribers()->where('user_id', $requestingUserId)->exists();
            $hasAlreadyRated     = $shopProfile->raters()->where('user_id', $requestingUserId)->exists();
        }

        return [
            'shopProfile'          => $shopProfile,
            'isShop'               => $user->isShop ?? false,
            'isAlreadySubscriber'  => $isAlreadySubscriber,
            'hasAlreadyRated'      => $hasAlreadyRated,
            'product'              => $this->mapProductDetail($product, $user, $profile),
            'userProducts'         => $userProducts,
            'sameProducts'         => $sameProducts,
            'user'                 => $user,
            'profile'              => $profile,
            'attributes'           => $attributes,
            'userProductCount'     => $user->products()->count(),
        ];
    }

    private function mapProductDetail(Product $product, User $user, mixed $profile): array
    {
        return [
            'id'                    => $product->id,
            'subcategory_id'        => $product->subcategory_id,
            'name'                  => $product->name,
            'slug'                  => $product->slug,
            'description'           => $product->description,
            'price'                 => $product->price,
            'currency'              => $product->currency,
            'created_at'            => $product->created_at,
            'updated_at'            => $product->updated_at,
            'type'                  => $product->type,
            'region_id'             => $product->region_id,
            'user_id'               => $product->user_id,
            'latitude'              => $product->latitude,
            'longitude'             => $product->longitude,
            'name_tsvector'         => $product->name_tsvector,
            'description_tsvector'  => $product->description_tsvector,
            'slug_tsvector'         => $product->slug_tsvector,
            'showNumber'            => $product->showNumber,
            'number'                => $product->number,
            'images'                => $product->images->map(fn ($img) => ['image_url' => $img->image_url])->toArray(),
            'region'                => optional($product->parentRegion)->name . ' - ' . optional($product->region)->name,
            'user'                  => [
                'id'                => $user->id,
                'name'              => $user->name,
                'email'             => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at'        => $user->created_at,
                'updated_at'        => $user->updated_at,
                'avatar'            => $user->avatar,
                'role_id'           => $user->role_id,
                'settings'          => $user->settings,
                'google_id'         => $user->google_id,
                'facebook_id'       => $user->facebook_id,
                'telegram_id'       => $user->telegram_id,
                'fcm_token'         => $user->fcm_token,
                'profile'           => $profile ? [
                    'id'         => $profile->id,
                    'user_id'    => $profile->user_id,
                    'phone'      => $profile->phone,
                    'region_id'  => $profile->region_id,
                    'address'    => $profile->address,
                    'avatar'     => $profile->avatar,
                    'created_at' => $profile->created_at,
                    'updated_at' => $profile->updated_at,
                    'latitude'   => $profile->latitude,
                    'longitude'  => $profile->longitude,
                ] : null,
                'products' => $user->products->map(fn ($p) => [
                    'id'                   => $p->id,
                    'subcategory_id'       => $p->subcategory_id,
                    'name'                 => $p->name,
                    'slug'                 => $p->slug,
                    'description'          => $p->description,
                    'price'                => $p->price,
                    'currency'             => $p->currency,
                    'created_at'           => $p->created_at,
                    'updated_at'           => $p->updated_at,
                    'type'                 => $p->type,
                    'region_id'            => $p->region_id,
                    'user_id'              => $p->user_id,
                    'latitude'             => $p->latitude,
                    'longitude'            => $p->longitude,
                    'name_tsvector'        => $p->name_tsvector,
                    'description_tsvector' => $p->description_tsvector,
                    'slug_tsvector'        => $p->slug_tsvector,
                ])->toArray(),
            ],
            'subcategory' => [
                'id'          => $product->subcategory->id,
                'category_id' => $product->subcategory->category_id,
                'name'        => $product->subcategory->name,
                'slug'        => $product->subcategory->slug,
                'created_at'  => $product->subcategory->created_at,
                'updated_at'  => $product->subcategory->updated_at,
            ],
        ];
    }

    private function mapProductPreview(Product $product, bool $withUser = false): array
    {
        $data = [
            'id'          => $product->id,
            'slug'        => $product->slug,
            'price'       => $product->price,
            'currency'    => $product->currency,
            'latitude'    => $product->latitude,
            'longitude'   => $product->longitude,
            'region'      => optional($product->parentRegion)->name ?? optional($product->region)->name,
            'type'        => $product->type,
            'name'        => $product->name,
            'created_at'  => $product->created_at,
            'description' => $product->description,
            'images'      => $product->images->map(fn ($img) => ['image_url' => $img->image_url]),
        ];

        if ($withUser) {
            $data['user'] = ['user_name' => optional($product->user)->name ?? 'Неизвестный пользователь'];
        }

        return $data;
    }

    private function dispatchFcmToSubscribers(User $user, Product $product, array $imagePaths): void
    {
        if (!$user->isShop) {
            return;
        }

        $shopProfile = ShopProfile::where('user_id', $user->id)->first();
        if (!$shopProfile) {
            return;
        }

        $subscribers    = $shopProfile->subscribers()->get();
        $firstImageUrl  = !empty($imagePaths) ? asset("storage/{$imagePaths[0]}") : '';
        $shopImageMain  = asset("storage/{$user->shopProfile->profile_url}");

        foreach ($subscribers as $subscriber) {
            if (!$subscriber->fcm_token) {
                continue;
            }
            SendFcmNotification::dispatch(
                $product->id,
                "{$shopProfile->shop_name} опубликовал новое объявление",
                Str::limit("{$product->name} - {$product->description}", 300, '...'),
                $subscriber->fcm_token,
                $firstImageUrl,
                $user->id,
                $shopProfile->shop_name,
                $product->name,
                $product->description,
                $subscriber->id,
                $shopImageMain,
            );
        }
    }

    private function postToTelegram(Product $product, array $images): void
    {
        $info = $this->buildTelegramMessage($product);

        try {
            if (count($images) > 1) {
                $media = array_map(function ($image, $index) use ($info) {
                    $item = ['type' => 'photo', 'media' => $image, 'parse_mode' => 'HTML'];
                    if ($index === 0) {
                        $item['caption'] = $info;
                    }
                    return $item;
                }, $images, array_keys($images));

                $this->telegramService->sendMediaGroup($media);
            } elseif (count($images) === 1) {
                $this->telegramService->sendPhoto($images[0], $info);
            } else {
                $this->telegramService->sendMessage($info);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send Telegram message: " . $e->getMessage());
        }
    }

    private function postToFacebook(Product $product): void
    {
        try {
            $info   = $this->buildFacebookMessage($product);
            $images = $this->productRepository->getImages($product->id)
                ->map(fn ($img) => [
                    'id'        => $img->id,
                    'image_url' => asset('storage/' . str_replace('\\', '/', $img->image_url)),
                ])->toArray();

            $this->facebookService->createPost($info, $images);
        } catch (\Exception $e) {
            Log::error("Failed to send Facebook post: " . $e->getMessage());
        }
    }

    private function postToInstagram(Product $product, array $images): void
    {
        try {
            $region    = optional(optional($product->region)->parent)->name ?? 'Unknown Region';
            $subregion = optional($product->region)->name ?? 'Unknown Subregion';
            $phone     = optional(optional($product->user)->profile)->phone ?? 'No Phone Number Provided';

            $message = "
📢 Объявление: {$product->name}\n
📝 Описание: {$product->description}\n
📍 Регион: {$region}, {$subregion}\n
👤 Контактное лицо: {$product->user->name}\n
📞 Номер телефона: {$phone}\n
🌍 Карта: https://www.google.com/maps?q={$product->latitude},{$product->longitude}\n
🔗 Подробнее: https://biztorg.uz/obyavlenie/{$product->slug}
";

            $this->instagramService->createCarouselPost($message, $images);
        } catch (\Exception $e) {
            Log::error("Failed to send Instagram post: " . $e->getMessage());
        }
    }

    private function buildTelegramMessage(Product $product): string
    {
        $parentName = optional(optional($product->region)->parent)->name;
        $regionName = optional($product->region)->name;
        $phone      = optional(optional($product->user)->profile)->phone;

        return <<<INFO
📢 <b>Объявление:</b> {$product->name}

📝 <b>Описание:</b> {$product->description}

📍 <b>Регион:</b> {$parentName}, {$regionName}

👤 <b>Контактное лицо:</b> {$product->user->name}

📞 <b>Номер телефона:</b> <a href="tel:{$phone}">{$phone}</a>

🌍 <b>Карта:</b> <a href="https://www.google.com/maps?q={$product->latitude},{$product->longitude}">Google Maps</a>

🌍 <b>Карта:</b> <a href="https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm">Yandex Maps</a>

🔗 <a href="https://biztorg.uz/obyavlenie/{$product->slug}">Подробнее по ссылке</a>
INFO;
    }

    private function buildFacebookMessage(Product $product): string
    {
        $parentName = optional(optional($product->region)->parent)->name;
        $regionName = optional($product->region)->name;
        $phone      = optional(optional($product->user)->profile)->phone;

        return <<<INFO
📢 Объявление: {$product->name}

📝 Описание: {$product->description}

📍 Регион: {$parentName}, {$regionName}

👤 Контактное лицо: {$product->user->name}

📞 Номер телефона: {$phone}

🌍 Google Maps: https://www.google.com/maps?q={$product->latitude},{$product->longitude}

🌍 Yandex Maps: https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map&pt={$product->longitude},{$product->latitude},pm2rdm

🔗 Подробнее: https://biztorg.uz/obyavlenie/{$product->slug}
INFO;
    }
}
