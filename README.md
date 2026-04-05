# BizTorg

A full-stack marketplace platform built with Laravel 11, enabling users to list products, manage vendor shops, communicate in real time, and automatically publish listings to social media channels.

---

## Description

BizTorg is a multi-vendor product marketplace targeting Russian-speaking audiences. Sellers can post product listings (for sale or purchase), manage a branded shop profile, and reach buyers across Telegram, Facebook, and Instagram automatically. Buyers can search, filter, favorite products, subscribe to shops, and message sellers directly.

**Key features:**
- Product listings with categories, attributes, images, and location
- Vendor shop profiles with ratings and subscriptions
- One-to-one messaging with image support
- Push notifications via Firebase Cloud Messaging (FCM)
- Automatic social media posting (Telegram, Facebook, Instagram) on product creation
- Full-text + trigram search powered by PostgreSQL tsvector + pg_trgm (Russian language)
- Social OAuth login (Google, Facebook, Telegram)
- Admin panel via TCG Voyager
- REST API with full OpenAPI 3.0 (Swagger UI) documentation

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 11, PHP 8.2+ |
| Database | PostgreSQL 16 |
| ORM | Eloquent |
| API | REST (`/api/v1`) with OpenAPI 3.0 |
| API Docs | darkaonline/l5-swagger (Swagger UI) |
| Authentication | Laravel Sanctum (API), Laravel Breeze (web) |
| Social OAuth | Laravel Socialite (Google, Facebook, Telegram) |
| Real-time | Laravel Reverb (WebSocket), Laravel Echo, Pusher-js |
| Search | PostgreSQL tsvector + pg_trgm trigram search |
| Push Notifications | Firebase (kreait/laravel-firebase) |
| Admin Panel | TCG Voyager |
| Frontend | Blade, Vite 5, Tailwind CSS 3, Alpine.js 3 |
| Queue / Cache / Session | Database driver |
| Containerisation | Docker (PHP-FPM, Nginx, PostgreSQL, queue worker, Reverb) |

---

## Architecture

The codebase follows a strict **Controller → Service → Repository** layered architecture:

- **Controllers** — thin HTTP handlers: validate input, call one service method, return response
- **Services** — all business logic (transactions, social posting, FCM dispatch, data assembly)
- **Repositories** — all database queries, implementing typed interfaces bound via DI in `AppServiceProvider`

```
HTTP Request
    └─► Controller
            └─► Service
                    └─► Repository (via Interface)
                            └─► Eloquent / DB
```

---

## Project Structure

```
BizTorg/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                          # API v1 controllers (thin — delegate to Services)
│   │   │   │   ├── Auth/
│   │   │   │   │   ├── ApiSocialAuthController.php
│   │   │   │   │   └── CustomLoginController.php
│   │   │   │   ├── CategoryController.php
│   │   │   │   ├── ConversationsController.php
│   │   │   │   ├── MessagesController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── ProfileController.php
│   │   │   │   └── RegionsController.php
│   │   │   ├── Auth/                         # Web auth (Breeze)
│   │   │   ├── AttributeAttributeValueController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── Controller.php                # Base (AuthorizesRequests trait)
│   │   │   ├── IndexController.php
│   │   │   ├── NotificationsController.php
│   │   │   ├── ProductController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ShopProfileController.php
│   │   │   ├── ShopRatingController.php
│   │   │   ├── ShopSubscriptionController.php
│   │   │   └── SitemapController.php
│   │   └── Requests/
│   │       ├── Api/                          # API Form Requests
│   │       │   ├── ApiLoginRequest.php
│   │       │   ├── ApiRegisterRequest.php
│   │       │   ├── CreateProductRequest.php
│   │       │   └── SendMessageRequest.php
│   │       ├── StoreProductRequest.php
│   │       ├── StoreShopProfileRequest.php
│   │       ├── UpdateProductRequest.php
│   │       ├── UpdateShopImagesRequest.php
│   │       └── UpdateShopProfileRequest.php
│   ├── Services/                             # Business logic layer
│   │   ├── CategoryService.php
│   │   ├── ConversationService.php
│   │   ├── CurrencyService.php
│   │   ├── FacebookService.php
│   │   ├── IndexService.php
│   │   ├── InstagramService.php
│   │   ├── MessageService.php
│   │   ├── NotificationService.php
│   │   ├── ProductService.php
│   │   ├── ProfileService.php
│   │   ├── SearchService.php                 # tsvector + pg_trgm hybrid search
│   │   ├── ShopService.php
│   │   ├── SitemapService.php
│   │   └── TelegramService.php
│   ├── Repositories/                         # Database query layer
│   │   ├── Contracts/                        # Interfaces (bound in AppServiceProvider)
│   │   │   ├── CategoryRepositoryInterface.php
│   │   │   ├── ConversationRepositoryInterface.php
│   │   │   ├── MessageRepositoryInterface.php
│   │   │   ├── NotificationRepositoryInterface.php
│   │   │   ├── ProductRepositoryInterface.php
│   │   │   ├── ProfileRepositoryInterface.php
│   │   │   ├── RegionRepositoryInterface.php
│   │   │   ├── ShopProfileRepositoryInterface.php
│   │   │   └── UserRepositoryInterface.php
│   │   ├── CategoryRepository.php
│   │   ├── ConversationRepository.php
│   │   ├── MessageRepository.php
│   │   ├── NotificationRepository.php
│   │   ├── ProductRepository.php
│   │   ├── ProfileRepository.php
│   │   ├── RegionRepository.php
│   │   ├── ShopProfileRepository.php
│   │   └── UserRepository.php
│   ├── Models/                               # Eloquent models
│   │   ├── Attribute.php
│   │   ├── AttributeValue.php
│   │   ├── Category.php
│   │   ├── Conversation.php
│   │   ├── Favorite.php
│   │   ├── Message.php
│   │   ├── Notification.php
│   │   ├── Product.php
│   │   ├── ProductImage.php
│   │   ├── Profile.php
│   │   ├── Region.php
│   │   ├── ShopProfile.php
│   │   ├── ShopRating.php
│   │   ├── ShopSubscription.php
│   │   ├── Subcategory.php
│   │   └── User.php
│   ├── Jobs/
│   │   ├── PostToSocialMediaJob.php
│   │   ├── RemoveFromSocialMediaJob.php
│   │   ├── SendFcmNotification.php
│   │   └── UpdateSocialMediaPostsJob.php
│   ├── Policies/
│   │   └── ShopPolicy.php
│   ├── Observers/
│   │   ├── ShopRatingObserver.php
│   │   └── ShopSubscriptionObserver.php
│   ├── OpenApi/                              # OpenAPI 3.0 annotations
│   │   ├── ApiInfo.php                       # Global info, server, security scheme, tags
│   │   └── Schemas/                          # Reusable $ref component schemas
│   │       ├── AuthResponseSchema.php
│   │       ├── CategorySchema.php
│   │       ├── CreateProductRequestSchema.php
│   │       ├── ErrorResponseSchema.php
│   │       ├── FcmTokenRequestSchema.php
│   │       ├── LoginRequestSchema.php
│   │       ├── MessageSchema.php
│   │       ├── NotificationSchema.php
│   │       ├── PaginationSchema.php
│   │       ├── ProductDetailSchema.php
│   │       ├── ProductImageSchema.php
│   │       ├── ProductListItemSchema.php
│   │       ├── ProfileSchema.php
│   │       ├── RateShopRequestSchema.php
│   │       ├── RegionSchema.php
│   │       ├── RegisterRequestSchema.php
│   │       ├── SendMessageRequestSchema.php
│   │       ├── SendVerificationRequestSchema.php
│   │       ├── ShopProfileSchema.php
│   │       ├── SocialAuthRequestSchema.php
│   │       ├── StoreShopRequestSchema.php
│   │       ├── SubcategorySchema.php
│   │       ├── UpdateProductRequestSchema.php
│   │       ├── UpdateProfileRequestSchema.php
│   │       ├── UserSchema.php
│   │       └── ValidationErrorSchema.php
│   └── Providers/
│       └── AppServiceProvider.php            # Repository interface → implementation bindings
├── database/
│   ├── migrations/
│   └── seeders/
├── routes/
│   ├── api.php                               # All /api/v1 routes
│   ├── web.php
│   ├── auth.php
│   └── console.php
├── docker/
│   ├── entrypoint.sh                         # Runs migrations + role seeder on startup
│   ├── nginx/default.conf
│   └── php/php.ini
├── config/
│   └── l5-swagger.php                        # Swagger UI configuration
├── Dockerfile                                # Multi-stage: assets → vendor → app → nginx
├── docker-compose.yml
└── .dockerignore
```

---

## API Documentation (Swagger UI)

When the app is running, the full interactive API reference is available at:

```
http://localhost/api/documentation
```

All endpoints are documented with request schemas, response schemas, authentication requirements, and example values. Authenticated endpoints require a **Bearer token** obtained from `/api/v1/auth/login` or `/api/v1/auth/register`.

To regenerate the docs after annotation changes:

```bash
docker compose exec app php artisan l5-swagger:generate
```

---

## Database Documentation

### `users`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string (nullable) | Display name |
| email | string (nullable) | Email address |
| password | string | Hashed password |
| role_id | bigint (FK) | References `roles.id` (2 = user, 1 = admin) |
| google_id | string (nullable) | Google OAuth ID |
| facebook_id | string (nullable) | Facebook OAuth ID |
| telegram_id | string (nullable) | Telegram OAuth ID |
| avatar | string (nullable) | Avatar image URL |
| fcm_token | string (nullable) | Firebase push notification token |
| isShop | boolean | Whether the user has a shop |
| email_verified_at | timestamp | Email verification timestamp |
| created_at / updated_at | timestamp | Timestamps |

---

### `profiles`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint (FK) | References `users.id` |
| phone | string (nullable) | Contact phone number |
| region_id | bigint (nullable, FK) | References `regions.id` |
| address | string (nullable) | Address text |
| avatar | string (nullable) | Avatar URL |
| latitude | decimal (nullable) | GPS latitude |
| longitude | decimal (nullable) | GPS longitude |
| created_at / updated_at | timestamp | Timestamps |

---

### `categories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Category name |
| slug | string | URL-friendly identifier |
| image_url | string (nullable) | Category image |
| created_at / updated_at | timestamp | Timestamps |

---

### `subcategories`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| category_id | bigint (FK) | References `categories.id` |
| name | string | Subcategory name |
| slug | string | URL-friendly identifier |
| created_at / updated_at | timestamp | Timestamps |

---

### `products`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| subcategory_id | bigint (FK) | References `subcategories.id` |
| user_id | bigint (FK) | References `users.id` |
| region_id | bigint (nullable, FK) | References `regions.id` |
| name | string | Product title |
| slug | string | URL-friendly identifier |
| description | text | Product description |
| price | decimal | Product price |
| currency | string | Price currency (`сум` / `доллар`) |
| type | enum | `sale` or `purchase` |
| latitude | decimal (nullable) | GPS latitude |
| longitude | decimal (nullable) | GPS longitude |
| showNumber | boolean (nullable) | Whether to show phone number |
| number | string (nullable) | Contact number shown on listing |
| facebook_post_id | string (nullable) | ID of the associated Facebook post |
| telegram_post_id | string (nullable) | ID of the associated Telegram message |
| insta_post_id | string (nullable) | ID of the associated Instagram post |
| created_at / updated_at | timestamp | Timestamps |

**Full-text search:** `tsvector` columns on `name`, `description`, `slug` (PostgreSQL, Russian language). Trigram similarity via `pg_trgm` extension for fuzzy matching.

---

### `product_images`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| product_id | bigint (FK) | References `products.id` |
| image_url | string | Storage path of the image |
| created_at / updated_at | timestamp | Timestamps |

---

### `attributes` / `attribute_values`

Flexible product attribute system. Attributes (e.g. "Color") belong to subcategories. Attribute values (e.g. "Red") belong to attributes. Products link to attribute values via the `product_attribute_values` pivot.

---

### `regions`

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| name | string | Region name |
| slug | string | URL-friendly identifier |
| parent_id | bigint (nullable, FK) | Self-referential parent region |
| latitude / longitude | decimal (nullable) | GPS coordinates |
| created_at / updated_at | timestamp | Timestamps |

Hierarchical: top-level regions have `parent_id = null`. Child regions reference their parent.

---

### `conversations` / `messages`

One-to-one chat. A `conversation` links two users; `messages` belong to a conversation with a `sender_id`. Supports text and image attachments.

---

### `notifications`

| Column | Type | Description |
|--------|------|-------------|
| receiver_id | bigint (FK) | Target user |
| sender_id | bigint (nullable, FK) | Originating user |
| type | string | Notification type |
| content | text | Notification content |
| hasBeenSeen | boolean | Read status |
| priority | enum | `low`, `medium`, `high` |
| metadata | json (nullable) | Additional structured data |

---

### `shop_profiles`

Full shop profile including branding (banner/profile images), contact info, social links, rating aggregates, and subscriber count.

---

### `shop_subscriptions` / `shop_ratings`

Pivot tables for shop subscriptions (unique per user+shop) and ratings (1–5, unique per user+shop, with check constraint).

---

## API Endpoints

### Authentication — `/api/v1/auth`

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/v1/auth/register` | — | Register a new user |
| POST | `/api/v1/auth/login` | — | Login with email/password |
| POST | `/api/v1/auth/google` | — | Sign in with Google OAuth token |
| POST | `/api/v1/auth/facebook` | — | Sign in with Facebook OAuth token |
| POST | `/api/v1/auth/send-verification-code` | — | Send email verification code |
| POST | `/api/v1/auth/verify-and-register` | — | Verify code and complete registration |

### Users & Profiles

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/user/{id}` | — | Get user details |
| GET | `/api/v1/user/{id}/fcm-token` | — | Get user's FCM token |
| POST | `/api/v1/store-fcm-token` | — | Store FCM push token |
| POST | `/api/v1/clear-fcm-token` | — | Clear FCM push token |
| GET | `/api/v1/profile/{id}` | — | Get user profile as JSON |
| POST | `/api/v1/profile/update` | Sanctum | Update user profile |

### Categories & Home

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/home` | — | Home feed: categories + paginated products |
| GET | `/api/v1/categories` | — | List all categories |
| GET | `/api/v1/{categoryId}/subcategories` | — | Subcategories for a category |
| GET | `/api/v1/find-category/subcategory/{id}` | — | Find parent category by subcategory |
| GET | `/api/v1/search` | — | Full-text + trigram product search |
| GET | `/api/v1/search-recommendations` | — | Search suggestions (subcategory names) |

### Products

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/{subcategoryId}/products` | — | Products in a subcategory |
| GET | `/api/v1/{subcategoryId}/attributes` | — | Attributes for a subcategory |
| GET | `/api/v1/category/{categoryId}/products` | — | Products in a category |
| GET | `/api/v1/filter-products` | — | Filter products (price, type, region, attributes, search) |
| GET | `/api/v1/product/{productId}` | — | Get product detail by ID |
| GET | `/api/v1/product/slug/{productSlug}` | — | Get product detail by slug |
| GET | `/api/v1/fetch/product/{id}` | — | Get product data for edit form |
| POST | `/api/v1/product/create` | — | Create a new product listing |
| POST | `/api/v1/product/update/{id}` | Sanctum | Update a product listing |
| DELETE | `/api/v1/products/delete/{productId}` | Sanctum | Delete a product |
| DELETE | `/api/v1/product/image/{id}` | Sanctum | Delete a product image |
| GET | `/api/v1/favorites` | Sanctum | Get current user's favourite IDs |
| POST | `/api/v1/favorite/toggle` | Sanctum | Toggle a product favourite |
| GET | `/api/v1/user/favorites/{uuid}` | Sanctum | Get full favourites list for a user |
| GET | `/api/v1/user/{uuid}/products` | Sanctum | Get products created by a user |

### Messaging

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| POST | `/api/v1/send/message` | Sanctum | Send a message |
| GET | `/api/v1/getMessages/{receiver_id}` | Sanctum | Get messages in a conversation |
| GET | `/api/v1/user/get/chat/conversations` | Sanctum | Get all conversations |
| POST | `/api/v1/upload/chat-image` | Sanctum | Upload a chat image |

### Notifications

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/notifications` | Sanctum | Get unseen notifications |
| POST | `/api/v1/notifications/mark-all-seen` | Sanctum | Mark all notifications as seen |
| POST | `/api/v1/notifications/mark-seen-for-chat` | Sanctum | Mark chat notifications as seen |

### Regions

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/regions` | — | Get all top-level regions |
| GET | `/api/v1/{parentRegionId}/child_regions` | — | Get child regions |

### Shops

| Method | URL | Auth | Description |
|--------|-----|------|-------------|
| GET | `/api/v1/shops/{userId}` | — | Get all products for a shop/user |
| POST | `/api/v1/shop-profiles` | Sanctum | Create a shop profile |
| POST | `/api/v1/shop/update` | Sanctum | Update shop information |
| POST | `/api/v1/{shopId}/upload-profile-images` | Sanctum | Upload shop banner/profile image |
| GET | `/api/v1/{shopId}/getShop` | Sanctum | Get shop profile details |
| POST | `/api/v1/subscribe/{shopId}` | Sanctum | Subscribe to a shop |
| POST | `/api/v1/unsubscribe/{shopId}` | Sanctum | Unsubscribe from a shop |
| POST | `/api/v1/shop/rate` | Sanctum | Rate a shop (1–5) |

---

## Environment Variables

Copy `.env.docker` to `.env` for Docker setup, or `.env.example` for local setup.

### Application

| Variable | Description |
|----------|-------------|
| `APP_NAME` | Application name |
| `APP_ENV` | Environment (`local`, `production`) |
| `APP_KEY` | Laravel application key |
| `APP_DEBUG` | Debug mode (`true` / `false`) |
| `APP_URL` | Base URL of the application |
| `L5_SWAGGER_CONST_HOST` | Base URL used in Swagger UI server definition |
| `L5_SWAGGER_GENERATE_ALWAYS` | Auto-regenerate docs on each request (`true` in dev) |

### Database

| Variable | Description |
|----------|-------------|
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | Database host |
| `DB_PORT` | `5432` |
| `DB_DATABASE` | Database name |
| `DB_USERNAME` | Database user |
| `DB_PASSWORD` | Database password |

### Social OAuth

| Variable | Description |
|----------|-------------|
| `GOOGLE_CLIENT_ID / SECRET / REDIRECT` | Google OAuth credentials |
| `FACEBOOK_CLIENT_ID / SECRET / REDIRECT` | Facebook OAuth credentials |
| `TELEGRAM_TOKEN` | Telegram bot token |
| `CHAT_ID` | Telegram channel ID for product announcements |

### Firebase / Push

| Variable | Description |
|----------|-------------|
| `FIREBASE_*` | Firebase project credentials for FCM (see `config/firebase.php`) |

### Broadcasting

| Variable | Description |
|----------|-------------|
| `BROADCAST_CONNECTION` | `reverb` |
| `REVERB_APP_ID / KEY / SECRET / HOST / PORT` | Laravel Reverb WebSocket config |

---

## Docker Setup

The repository includes a complete Docker Compose stack:

| Service | Image | Purpose |
|---------|-------|---------|
| `postgres` | postgres:16-alpine | PostgreSQL database |
| `app` | Dockerfile (`app` stage) | PHP-FPM application server |
| `nginx` | Dockerfile (`nginx` stage) | HTTP server — serves static files, proxies PHP |
| `queue` | same as `app` | Background queue worker (`queue:work`) |
| `reverb` | same as `app` | Laravel Reverb WebSocket server |

### Quick start

```bash
# 1. Copy environment file
cp .env.docker .env

# 2. Generate an application key — paste the output into APP_KEY in .env
docker compose run --rm --no-deps --entrypoint php app artisan key:generate --show

# 3. Build images and start the stack
docker compose up --build -d

# 4. Generate Swagger docs
docker compose exec app php artisan l5-swagger:generate
```

The app is available at **http://localhost** (port 80).  
Swagger UI is at **http://localhost/api/documentation**.  
Reverb WebSocket listens on **port 8080**.

> On first start, migrations and role seeding run automatically via `docker/entrypoint.sh`.

### Useful commands

```bash
# Start the stack
docker compose up -d

# Rebuild after code changes
docker compose up --build -d

# Run an Artisan command
docker compose exec app php artisan <command>

# Regenerate Swagger docs
docker compose exec app php artisan l5-swagger:generate

# Regenerate autoloader (after adding new classes)
docker compose exec app composer dump-autoload --optimize

# Follow app logs
docker compose logs -f app

# Open a shell
docker compose exec app sh

# Stop all services
docker compose down

# Stop and remove volumes (⚠ deletes database and uploads)
docker compose down -v
```

### Volumes

| Volume | Mounted at | Purpose |
|--------|-----------|---------|
| `biztorg_pg_data` | `/var/lib/postgresql/data` | Database files |
| `app_storage` | `/var/www/html/storage` | Uploads, logs, compiled views |

### Ports

| Port | Service | Override via |
|------|---------|--------------|
| 80 | Nginx | `APP_PORT` in `.env` |
| 5432 | PostgreSQL | `DB_FORWARD_PORT` in `.env` |
| 8080 | Reverb WebSocket | `REVERB_PORT` in `.env` |

---

## Local Development Setup

### Requirements

- PHP 8.2+, Composer
- Node.js 20+, npm
- PostgreSQL 14+

```bash
git clone <repo-url> && cd BizTorg
composer install
npm install
cp .env.example .env
php artisan key:generate
# configure .env (DB, OAuth, Firebase, Telegram...)
php artisan migrate
php artisan db:seed --class=RolesTableSeeder
php artisan storage:link
npm run build
php artisan serve
```

### Development scripts

```bash
php artisan serve          # HTTP server
npm run dev                # Vite HMR
php artisan queue:listen   # Queue worker
php artisan reverb:start   # WebSocket server
```

---

## Additional Notes

### Authentication
- **Web:** Session-based via Laravel Breeze
- **API:** Token-based via Laravel Sanctum (Bearer token in `Authorization` header)
- **Social:** Google, Facebook, Telegram via Laravel Socialite

### Admin Panel
- TCG Voyager available at `/admin`
- Seeded with roles (`admin`, `user`) and permissions via `database/seeders/`

### Search
- Hybrid approach: PostgreSQL `tsvector` full-text search (Russian) + `pg_trgm` trigram similarity
- Implemented in `SearchService` — deduplicates and ranks results from both strategies

### Social Media Auto-Posting
When a product is created, the app dispatches jobs to:
1. Post to the configured **Telegram** channel
2. Create a **Facebook** post via Graph API
3. Create an **Instagram** carousel post via Graph API

Post IDs are stored on the product for future updates/deletions.

### Queues
- Driver: `database`
- Jobs: `PostToSocialMediaJob`, `RemoveFromSocialMediaJob`, `UpdateSocialMediaPostsJob`, `SendFcmNotification`

### Real-Time
- Laravel Reverb handles WebSocket connections for live messaging and notifications
