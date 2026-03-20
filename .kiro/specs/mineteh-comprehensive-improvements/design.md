# Design Document: MineTeh Marketplace Comprehensive Improvements

## Overview

This design document specifies the technical implementation for transforming the MineTeh marketplace platform from 58% complete to a production-ready, secure, and fully functional e-commerce system. The platform is a PHP-based auction and marketplace using Supabase (PostgreSQL) as the database backend.

### Current System State

The MineTeh platform currently includes:
- User authentication with session management
- Listing creation and management (fixed price and auction)
- Bidding system with real-time updates
- Shopping cart and checkout flow
- Messaging system between buyers and sellers
- Notification system
- Admin panel for platform management
- Rider delivery system with automated assignment
- Image carousel for listing photos
- Location autocomplete for Philippine cities
- REST API endpoints for mobile integration

### Design Goals

This comprehensive improvement initiative addresses 42 requirements across six major categories:

1. **Security Enhancements** (Requirements 1-8): Implement enterprise-grade security including bcrypt password hashing, CSRF protection, SQL injection prevention, XSS filtering, rate limiting, secure file uploads, email verification, and password reset flows.

2. **Core Features** (Requirements 9-13): Add payment gateway integration, review/rating system, seller analytics dashboard, buyer protection with dispute resolution, and advanced search with filtering.

3. **Database Integrity** (Requirements 14-16): Establish foreign key constraints, check constraints, validation rules, and performance indexes across all tables.

4. **API Improvements** (Requirements 17-18): Complete RESTful API with proper versioning, documentation, and mobile app support.

5. **Performance Optimization** (Requirements 19-22): Implement comprehensive error handling, image optimization, database query optimization, and frontend asset optimization.

6. **User Experience & Operations** (Requirements 23-42): Responsive design, accessibility compliance, content moderation, enhanced notifications, delivery tracking, location-based features, UX improvements, input validation, auction enhancements, messaging improvements, cart/checkout refinements, admin dashboard, security audit logging, database backups, environment configuration, testing infrastructure, CI/CD pipeline, and documentation.


### Technology Stack

- **Backend**: PHP 7.4+ with object-oriented architecture
- **Database**: Supabase (PostgreSQL 14+) with Row Level Security
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5
- **Authentication**: Session-based with secure token management
- **File Storage**: Local filesystem with CDN integration capability
- **Payment Processing**: GCash/PayPal/Stripe integration
- **Email**: PHP mail() or SMTP (configurable)
- **Caching**: File-based caching with Redis capability
- **Testing**: PHPUnit for unit tests, Selenium for E2E tests
- **Deployment**: Git-based with automated CI/CD pipeline

## Architecture

### System Architecture

The MineTeh platform follows a three-tier architecture:

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Web Pages   │  │  Admin Panel │  │  REST API    │      │
│  │  (PHP/HTML)  │  │  (PHP/HTML)  │  │  (JSON)      │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                     Business Logic Layer                     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Auth Module  │  │ Listing Mgmt │  │ Order Mgmt   │      │
│  ├──────────────┤  ├──────────────┤  ├──────────────┤      │
│  │ Security     │  │ Bid System   │  │ Payment      │      │
│  ├──────────────┤  ├──────────────┤  ├──────────────┤      │
│  │ Validation   │  │ Search       │  │ Delivery     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
                            │
┌─────────────────────────────────────────────────────────────┐
│                       Data Access Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │  Supabase    │  │  File System │  │  Cache       │      │
│  │  Client      │  │  Manager     │  │  Manager     │      │
│  └──────────────┘  └──────────────┘  └──────────────┘      │
└─────────────────────────────────────────────────────────────┘
```

### Security Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Security Layers                         │
│                                                               │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  1. Transport Security (HTTPS, Secure Headers)      │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  2. Authentication (Session, CSRF, Rate Limiting)   │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  3. Input Validation (XSS, SQL Injection)           │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  4. Authorization (Role-based Access Control)       │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  5. Data Security (Encryption, RLS Policies)        │    │
│  └─────────────────────────────────────────────────────┘    │
│                            │                                  │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  6. Audit Logging (Security Events, Actions)        │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```


## Components and Interfaces

### 1. Authentication Module (`includes/auth.php`)

Handles user authentication, session management, and security.

**Public Interface:**
```php
class AuthenticationModule {
    // User registration with email verification
    public function register(string $email, string $password, array $userData): array;
    
    // User login with rate limiting
    public function login(string $email, string $password): array;
    
    // User logout
    public function logout(): bool;
    
    // Session validation
    public function validateSession(string $sessionToken): ?array;
    
    // Email verification
    public function verifyEmail(string $token): bool;
    public function resendVerification(string $email): bool;
    
    // Password reset
    public function requestPasswordReset(string $email): bool;
    public function validateResetToken(string $token): bool;
    public function resetPassword(string $token, string $newPassword): bool;
    
    // Session management
    public function regenerateSession(): void;
    public function cleanupExpiredSessions(): int;
}
```

### 2. Security Module (`includes/security.php`)

Provides security utilities and middleware.

**Public Interface:**
```php
class SecurityModule {
    // CSRF protection
    public function generateCSRFToken(): string;
    public function validateCSRFToken(string $token): bool;
    
    // Input sanitization
    public function sanitize($input, string $type = 'string');
    public function validate($input, string $type, array $options = []): bool;
    
    // XSS prevention
    public function escapeOutput(string $content): string;
    public function sanitizeHTML(string $html, array $allowedTags = []): string;
    
    // Rate limiting
    public function checkRateLimit(string $key, int $maxAttempts, int $windowSeconds): bool;
    public function recordAttempt(string $key): void;
    
    // Security headers
    public function setSecurityHeaders(): void;
    
    // Audit logging
    public function logSecurityEvent(string $event, array $details): void;
}
```

### 3. Payment Gateway Module (`includes/payment.php`)

Integrates with payment providers (GCash, PayPal, Stripe).

**Public Interface:**
```php
interface PaymentGateway {
    public function createPayment(float $amount, string $currency, array $metadata): array;
    public function verifyWebhook(array $payload, string $signature): bool;
    public function processRefund(string $transactionId, float $amount): array;
    public function getTransactionStatus(string $transactionId): array;
}

class GCashGateway implements PaymentGateway { /* ... */ }
class PayPalGateway implements PaymentGateway { /* ... */ }
class StripeGateway implements PaymentGateway { /* ... */ }

class PaymentManager {
    public function __construct(PaymentGateway $gateway);
    public function processPayment(int $orderId, float $amount): array;
    public function handleWebhook(array $payload): bool;
    public function refundOrder(int $orderId, float $amount): bool;
}
```

### 4. Listing Management Module (`includes/listing.php`)

Manages product listings and auctions.

**Public Interface:**
```php
class ListingManager {
    // CRUD operations
    public function createListing(int $userId, array $listingData): int;
    public function updateListing(int $listingId, array $listingData): bool;
    public function deleteListing(int $listingId): bool;
    public function getListing(int $listingId): ?array;
    
    // Search and filtering
    public function searchListings(array $filters, int $page = 1, int $perPage = 20): array;
    public function getListingsByCategory(int $categoryId, int $page = 1): array;
    public function getListingsByLocation(string $location, float $radius): array;
    
    // Image management
    public function uploadImages(int $listingId, array $files): array;
    public function deleteImage(int $imageId): bool;
    public function reorderImages(int $listingId, array $imageOrder): bool;
    
    // Auction management
    public function closeAuction(int $listingId): bool;
    public function extendAuction(int $listingId, int $minutes): bool;
}
```

### 5. Bidding System Module (`includes/bidding.php`)

Handles auction bidding logic.

**Public Interface:**
```php
class BiddingSystem {
    // Place bid with validation
    public function placeBid(int $listingId, int $userId, float $amount): array;
    
    // Get bid history
    public function getBidHistory(int $listingId): array;
    public function getUserBids(int $userId): array;
    
    // Bid validation
    public function validateBid(int $listingId, int $userId, float $amount): array;
    public function calculateMinimumBid(int $listingId): float;
    
    // Auction end handling
    public function processAuctionEnd(int $listingId): bool;
    public function notifyBidders(int $listingId, string $event): void;
}
```


### 6. Order Management Module (`includes/order.php`)

Manages orders, transactions, and fulfillment.

**Public Interface:**
```php
class OrderManager {
    // Order creation
    public function createOrder(int $buyerId, array $items, array $shippingInfo): int;
    public function createOrderFromAuction(int $listingId, int $winnerId): int;
    
    // Order status management
    public function updateOrderStatus(int $orderId, string $status): bool;
    public function getOrder(int $orderId): ?array;
    public function getUserOrders(int $userId, string $role = 'buyer'): array;
    
    // Payment processing
    public function processPayment(int $orderId, array $paymentData): bool;
    public function releasePayment(int $orderId): bool;
    public function processRefund(int $orderId, float $amount): bool;
    
    // Dispute handling
    public function openDispute(int $orderId, int $userId, string $reason): int;
    public function resolveDispute(int $disputeId, string $resolution): bool;
}
```

### 7. Review System Module (`includes/review.php`)

Manages user reviews and ratings.

**Public Interface:**
```php
class ReviewSystem {
    // Review management
    public function createReview(int $orderId, int $buyerId, int $sellerId, int $rating, string $comment): int;
    public function updateReview(int $reviewId, int $rating, string $comment): bool;
    public function deleteReview(int $reviewId): bool;
    
    // Review retrieval
    public function getSellerReviews(int $sellerId, int $page = 1): array;
    public function getSellerRating(int $sellerId): array;
    public function canUserReview(int $userId, int $orderId): bool;
    
    // Seller response
    public function addSellerResponse(int $reviewId, string $response): bool;
    
    // Moderation
    public function flagReview(int $reviewId, string $reason): bool;
    public function moderateReview(int $reviewId, string $action): bool;
}
```

### 8. Notification System Module (`includes/notification.php`)

Handles in-app and email notifications.

**Public Interface:**
```php
class NotificationSystem {
    // Create notifications
    public function createNotification(int $userId, string $type, string $message, array $metadata = []): int;
    public function sendBulkNotifications(array $userIds, string $type, string $message): int;
    
    // Retrieve notifications
    public function getUserNotifications(int $userId, bool $unreadOnly = false): array;
    public function getUnreadCount(int $userId): int;
    
    // Mark as read
    public function markAsRead(int $notificationId): bool;
    public function markAllAsRead(int $userId): bool;
    
    // Email notifications
    public function sendEmail(string $to, string $subject, string $body, array $options = []): bool;
    
    // Notification preferences
    public function updatePreferences(int $userId, array $preferences): bool;
    public function getPreferences(int $userId): array;
}
```

### 9. Delivery System Module (`services/DeliveryManager.php`)

Manages delivery assignments and tracking.

**Public Interface:**
```php
class DeliveryManager {
    // Delivery assignment
    public function assignDelivery(int $orderId): ?int;
    public function findAvailableRiders(float $lat, float $lng, float $radiusKm = 10): array;
    public function notifyRider(int $riderId, int $deliveryId): bool;
    
    // Delivery tracking
    public function updateDeliveryStatus(int $deliveryId, string $status): bool;
    public function uploadProofOfDelivery(int $deliveryId, string $imagePath): bool;
    public function getDeliveryDetails(int $deliveryId): ?array;
    
    // Rider management
    public function getRiderPerformance(int $riderId): array;
    public function updateRiderLocation(int $riderId, float $lat, float $lng): bool;
}
```

### 10. Search and Filter Module (`includes/search.php`)

Provides advanced search functionality.

**Public Interface:**
```php
class SearchEngine {
    // Full-text search
    public function search(string $query, array $filters = [], int $page = 1): array;
    public function autocomplete(string $query, int $limit = 10): array;
    
    // Filtering
    public function applyFilters(array $filters): self;
    public function filterByCategory(int $categoryId): self;
    public function filterByPriceRange(float $min, float $max): self;
    public function filterByLocation(string $location, float $radius): self;
    public function filterByCondition(string $condition): self;
    
    // Sorting
    public function sortBy(string $field, string $direction = 'ASC'): self;
    
    // Execution
    public function execute(): array;
    public function count(): int;
}
```

### 11. Image Optimization Module (`includes/image.php`)

Handles image processing and optimization.

**Public Interface:**
```php
class ImageOptimizer {
    // Image processing
    public function optimize(string $sourcePath, array $options = []): string;
    public function generateThumbnail(string $sourcePath, int $width, int $height): string;
    public function convertToWebP(string $sourcePath, int $quality = 80): string;
    
    // Responsive images
    public function generateResponsiveSizes(string $sourcePath): array;
    
    // Validation
    public function validateImage(string $path): bool;
    public function validateMimeType(string $path, array $allowedTypes): bool;
    public function scanForMalware(string $path): bool;
}
```

### 12. Cache Manager (`includes/cache.php`)

Provides caching functionality.

**Public Interface:**
```php
class CacheManager {
    // Cache operations
    public function get(string $key, $default = null);
    public function set(string $key, $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function clear(): bool;
    
    // Cache invalidation
    public function invalidatePattern(string $pattern): int;
    public function invalidateTag(string $tag): int;
    
    // Cache statistics
    public function getStats(): array;
}
```


## Data Models

### Enhanced Database Schema

The following schema enhancements address Requirements 14-16 (database integrity, constraints, and indexing).

#### Core Tables

**users** (enhanced from accounts)
```sql
CREATE TABLE users (
    user_id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL CHECK (email ~* '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$'),
    password_hash VARCHAR(255) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'restricted', 'banned')),
    restriction_until TIMESTAMP,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    verification_expires TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMP DEFAULT NOW(),
    last_login TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_users_verification ON users(verification_token) WHERE verification_token IS NOT NULL;
```

**user_sessions**
```sql
CREATE TABLE user_sessions (
    session_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_sessions_token ON user_sessions(session_token);
CREATE INDEX idx_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_sessions_expires ON user_sessions(expires_at);
```

**password_resets**
```sql
CREATE TABLE password_resets (
    reset_id BIGSERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_password_resets_token ON password_resets(token);
CREATE INDEX idx_password_resets_email ON password_resets(email);
```

**listings** (enhanced)
```sql
CREATE TABLE listings (
    listing_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL CHECK (LENGTH(title) BETWEEN 5 AND 100),
    description TEXT NOT NULL CHECK (LENGTH(description) BETWEEN 20 AND 5000),
    price DECIMAL(10, 2) NOT NULL CHECK (price >= 0),
    starting_price DECIMAL(10, 2) CHECK (starting_price >= 0),
    reserve_price DECIMAL(10, 2) CHECK (reserve_price >= 0),
    current_price DECIMAL(10, 2) CHECK (current_price >= 0),
    min_bid_increment DECIMAL(10, 2) DEFAULT 10.00,
    listing_type VARCHAR(20) DEFAULT 'fixed' CHECK (listing_type IN ('fixed', 'auction')),
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'sold', 'closed', 'flagged', 'removed')),
    category_id INT REFERENCES categories(category_id) ON DELETE SET NULL,
    condition VARCHAR(20) CHECK (condition IN ('new', 'like_new', 'good', 'fair', 'poor')),
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    view_count INT DEFAULT 0,
    favorite_count INT DEFAULT 0,
    auction_end_time TIMESTAMP CHECK (auction_end_time > created_at),
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_listings_user ON listings(user_id);
CREATE INDEX idx_listings_category ON listings(category_id);
CREATE INDEX idx_listings_status_created ON listings(status, created_at DESC);
CREATE INDEX idx_listings_location ON listings(latitude, longitude) WHERE latitude IS NOT NULL;
CREATE INDEX idx_listings_price ON listings(price);
CREATE INDEX idx_listings_auction_end ON listings(auction_end_time) WHERE listing_type = 'auction';
CREATE INDEX idx_listings_fulltext ON listings USING GIN(to_tsvector('english', title || ' ' || description));
```

**listing_images**
```sql
CREATE TABLE listing_images (
    image_id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT NOT NULL REFERENCES listings(listing_id) ON DELETE CASCADE,
    image_path VARCHAR(500) NOT NULL,
    thumbnail_path VARCHAR(500),
    display_order INT DEFAULT 0,
    file_size INT,
    mime_type VARCHAR(50),
    uploaded_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_listing_images_listing ON listing_images(listing_id, display_order);
```

**categories**
```sql
CREATE TABLE categories (
    category_id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT REFERENCES categories(category_id) ON DELETE SET NULL,
    icon VARCHAR(50),
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_categories_parent ON categories(parent_id);
CREATE INDEX idx_categories_slug ON categories(slug);
```


**bids**
```sql
CREATE TABLE bids (
    bid_id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT NOT NULL REFERENCES listings(listing_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    bid_time TIMESTAMP DEFAULT NOW() NOT NULL,
    is_winning BOOLEAN DEFAULT FALSE,
    CONSTRAINT no_self_bidding CHECK (
        user_id != (SELECT user_id FROM listings WHERE listing_id = bids.listing_id)
    )
);

CREATE INDEX idx_bids_listing_amount ON bids(listing_id, amount DESC);
CREATE INDEX idx_bids_user ON bids(user_id, bid_time DESC);
CREATE INDEX idx_bids_winning ON bids(listing_id, is_winning) WHERE is_winning = TRUE;
```

**orders**
```sql
CREATE TABLE orders (
    order_id BIGSERIAL PRIMARY KEY,
    buyer_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT,
    seller_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE RESTRICT,
    listing_id BIGINT REFERENCES listings(listing_id) ON DELETE RESTRICT,
    total_amount DECIMAL(10, 2) NOT NULL CHECK (total_amount > 0),
    shipping_cost DECIMAL(10, 2) DEFAULT 0 CHECK (shipping_cost >= 0),
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded')),
    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'failed', 'refunded')),
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100),
    shipping_postal_code VARCHAR(20),
    tracking_number VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    paid_at TIMESTAMP,
    shipped_at TIMESTAMP,
    delivered_at TIMESTAMP,
    cancelled_at TIMESTAMP
);

CREATE INDEX idx_orders_buyer ON orders(buyer_id, created_at DESC);
CREATE INDEX idx_orders_seller_status ON orders(seller_id, status);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_payment_status ON orders(payment_status);
```

**transactions**
```sql
CREATE TABLE transactions (
    transaction_id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    transaction_ref VARCHAR(255) UNIQUE NOT NULL,
    payment_gateway VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL CHECK (amount > 0),
    currency VARCHAR(3) DEFAULT 'PHP',
    status VARCHAR(20) NOT NULL CHECK (status IN ('pending', 'completed', 'failed', 'refunded')),
    gateway_response TEXT,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    completed_at TIMESTAMP
);

CREATE INDEX idx_transactions_order ON transactions(order_id);
CREATE INDEX idx_transactions_ref ON transactions(transaction_ref);
CREATE INDEX idx_transactions_status ON transactions(status);
```

**reviews**
```sql
CREATE TABLE reviews (
    review_id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    buyer_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    seller_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT CHECK (LENGTH(comment) <= 1000),
    seller_response TEXT CHECK (LENGTH(seller_response) <= 1000),
    is_flagged BOOLEAN DEFAULT FALSE,
    flag_reason TEXT,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    updated_at TIMESTAMP DEFAULT NOW(),
    responded_at TIMESTAMP,
    UNIQUE(order_id)
);

CREATE INDEX idx_reviews_seller_rating ON reviews(seller_id, rating);
CREATE INDEX idx_reviews_buyer ON reviews(buyer_id);
CREATE INDEX idx_reviews_flagged ON reviews(is_flagged) WHERE is_flagged = TRUE;
```

**favorites**
```sql
CREATE TABLE favorites (
    favorite_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    listing_id BIGINT NOT NULL REFERENCES listings(listing_id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    UNIQUE(user_id, listing_id)
);

CREATE INDEX idx_favorites_user ON favorites(user_id, created_at DESC);
CREATE INDEX idx_favorites_listing ON favorites(listing_id);
```

**cart_items**
```sql
CREATE TABLE cart_items (
    cart_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    listing_id BIGINT NOT NULL REFERENCES listings(listing_id) ON DELETE CASCADE,
    quantity INT DEFAULT 1 CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT NOW() NOT NULL,
    UNIQUE(user_id, listing_id)
);

CREATE INDEX idx_cart_user ON cart_items(user_id);
```

**messages**
```sql
CREATE TABLE messages (
    message_id BIGSERIAL PRIMARY KEY,
    conversation_id BIGINT NOT NULL REFERENCES conversations(conversation_id) ON DELETE CASCADE,
    sender_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    message_text TEXT NOT NULL CHECK (LENGTH(message_text) BETWEEN 1 AND 1000),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_messages_conversation ON messages(conversation_id, created_at DESC);
CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_unread ON messages(conversation_id, is_read) WHERE is_read = FALSE;
```

**conversations**
```sql
CREATE TABLE conversations (
    conversation_id BIGSERIAL PRIMARY KEY,
    listing_id BIGINT REFERENCES listings(listing_id) ON DELETE SET NULL,
    participant1_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    participant2_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    last_message_at TIMESTAMP DEFAULT NOW(),
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    UNIQUE(participant1_id, participant2_id, listing_id)
);

CREATE INDEX idx_conversations_participants ON conversations(participant1_id, participant2_id);
CREATE INDEX idx_conversations_last_message ON conversations(last_message_at DESC);
```


**notifications**
```sql
CREATE TABLE notifications (
    notification_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read, created_at DESC);
CREATE INDEX idx_notifications_type ON notifications(type);
```

**deliveries**
```sql
CREATE TABLE deliveries (
    delivery_id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    rider_id BIGINT REFERENCES riders(rider_id) ON DELETE SET NULL,
    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed')),
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    distance_km DECIMAL(10, 2),
    estimated_time INT,
    proof_of_delivery VARCHAR(500),
    delivery_notes TEXT,
    assigned_at TIMESTAMP,
    picked_up_at TIMESTAMP,
    delivered_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_deliveries_order ON deliveries(order_id);
CREATE INDEX idx_deliveries_rider_status ON deliveries(rider_id, status);
CREATE INDEX idx_deliveries_status ON deliveries(status);
```

**riders**
```sql
CREATE TABLE riders (
    rider_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT UNIQUE REFERENCES users(user_id) ON DELETE CASCADE,
    vehicle_type VARCHAR(50) NOT NULL,
    license_number VARCHAR(50) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    status VARCHAR(20) DEFAULT 'available' CHECK (status IN ('available', 'busy', 'offline')),
    rating DECIMAL(3, 2) DEFAULT 5.00 CHECK (rating BETWEEN 0 AND 5),
    total_deliveries INT DEFAULT 0,
    current_latitude DECIMAL(10, 8),
    current_longitude DECIMAL(11, 8),
    last_location_update TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_riders_status ON riders(status);
CREATE INDEX idx_riders_location ON riders(current_latitude, current_longitude) WHERE status = 'available';
CREATE INDEX idx_riders_rating ON riders(rating DESC);
```

**disputes**
```sql
CREATE TABLE disputes (
    dispute_id BIGSERIAL PRIMARY KEY,
    order_id BIGINT NOT NULL REFERENCES orders(order_id) ON DELETE CASCADE,
    opened_by BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    reason TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'open' CHECK (status IN ('open', 'investigating', 'resolved', 'closed')),
    resolution TEXT,
    resolved_by BIGINT REFERENCES users(user_id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL,
    resolved_at TIMESTAMP
);

CREATE INDEX idx_disputes_order ON disputes(order_id);
CREATE INDEX idx_disputes_status ON disputes(status);
CREATE INDEX idx_disputes_opened_by ON disputes(opened_by);
```

**dispute_evidence**
```sql
CREATE TABLE dispute_evidence (
    evidence_id BIGSERIAL PRIMARY KEY,
    dispute_id BIGINT NOT NULL REFERENCES disputes(dispute_id) ON DELETE CASCADE,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    evidence_type VARCHAR(20) CHECK (evidence_type IN ('text', 'image')),
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_dispute_evidence_dispute ON dispute_evidence(dispute_id, created_at);
```

**rate_limits**
```sql
CREATE TABLE rate_limits (
    limit_id BIGSERIAL PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    attempt_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT NOW() NOT NULL,
    blocked_until TIMESTAMP,
    UNIQUE(identifier, endpoint, window_start)
);

CREATE INDEX idx_rate_limits_identifier ON rate_limits(identifier, endpoint);
CREATE INDEX idx_rate_limits_window ON rate_limits(window_start);
```

**audit_logs**
```sql
CREATE TABLE audit_logs (
    log_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT REFERENCES users(user_id) ON DELETE SET NULL,
    event_type VARCHAR(50) NOT NULL,
    event_description TEXT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSONB,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_audit_logs_user ON audit_logs(user_id, created_at DESC);
CREATE INDEX idx_audit_logs_event ON audit_logs(event_type, created_at DESC);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at DESC);
```

**user_addresses**
```sql
CREATE TABLE user_addresses (
    address_id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    label VARCHAR(50),
    full_address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW() NOT NULL
);

CREATE INDEX idx_user_addresses_user ON user_addresses(user_id);
CREATE INDEX idx_user_addresses_default ON user_addresses(user_id, is_default) WHERE is_default = TRUE;
```


### Database Migration Strategy

All schema changes will be implemented through versioned migration scripts:

**Migration File Structure:**
```
database/migrations/
├── 001_create_user_sessions.sql
├── 002_create_password_resets.sql
├── 003_enhance_listings_table.sql
├── 004_create_transactions_table.sql
├── 005_create_reviews_table.sql
├── 006_create_disputes_table.sql
├── 007_create_rate_limits_table.sql
├── 008_create_audit_logs_table.sql
├── 009_create_user_addresses_table.sql
├── 010_add_foreign_keys.sql
├── 011_add_check_constraints.sql
├── 012_add_indexes.sql
└── rollback/
    ├── 001_rollback.sql
    ├── 002_rollback.sql
    └── ...
```

Each migration includes:
1. Forward migration SQL
2. Rollback SQL
3. Data migration scripts (if needed)
4. Verification queries

### Row Level Security (RLS) Policies

Enhanced RLS policies for Supabase:

```sql
-- Users can only update their own profile
CREATE POLICY "users_update_own" ON users
FOR UPDATE USING (user_id = current_setting('app.user_id')::bigint);

-- Listings are viewable by everyone, editable by owner
CREATE POLICY "listings_select_all" ON listings
FOR SELECT USING (true);

CREATE POLICY "listings_update_owner" ON listings
FOR UPDATE USING (user_id = current_setting('app.user_id')::bigint);

-- Orders visible to buyer and seller only
CREATE POLICY "orders_select_participant" ON orders
FOR SELECT USING (
    buyer_id = current_setting('app.user_id')::bigint OR
    seller_id = current_setting('app.user_id')::bigint OR
    current_setting('app.is_admin')::boolean = true
);

-- Messages visible to conversation participants only
CREATE POLICY "messages_select_participant" ON messages
FOR SELECT USING (
    sender_id = current_setting('app.user_id')::bigint OR
    EXISTS (
        SELECT 1 FROM conversations c
        WHERE c.conversation_id = messages.conversation_id
        AND (c.participant1_id = current_setting('app.user_id')::bigint
             OR c.participant2_id = current_setting('app.user_id')::bigint)
    )
);

-- Reviews visible to all, editable by author
CREATE POLICY "reviews_select_all" ON reviews
FOR SELECT USING (true);

CREATE POLICY "reviews_update_author" ON reviews
FOR UPDATE USING (buyer_id = current_setting('app.user_id')::bigint);

-- Audit logs visible to admins only
CREATE POLICY "audit_logs_admin_only" ON audit_logs
FOR SELECT USING (current_setting('app.is_admin')::boolean = true);
```

## API Specifications

### RESTful API Endpoints (Requirements 17-18)

All API endpoints follow REST conventions and return JSON responses.

**Base URL:** `/api/v1/`

**Authentication:** Bearer token in Authorization header
```
Authorization: Bearer {session_token}
```

**Standard Response Format:**
```json
{
    "success": true|false,
    "data": {...} | [...],
    "message": "Success message",
    "errors": [...],
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 100,
        "total_pages": 5
    }
}
```

**HTTP Status Codes:**
- 200: Success
- 201: Created
- 400: Bad Request (validation errors)
- 401: Unauthorized (missing/invalid token)
- 403: Forbidden (insufficient permissions)
- 404: Not Found
- 429: Too Many Requests (rate limit exceeded)
- 500: Internal Server Error

### Authentication Endpoints

**POST /api/v1/auth/register**
```json
Request:
{
    "email": "user@example.com",
    "password": "SecurePass123!",
    "username": "johndoe",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "09123456789"
}

Response (201):
{
    "success": true,
    "data": {
        "user_id": 123,
        "email": "user@example.com",
        "username": "johndoe",
        "email_verified": false
    },
    "message": "Registration successful. Please check your email to verify your account."
}
```

**POST /api/v1/auth/login**
```json
Request:
{
    "email": "user@example.com",
    "password": "SecurePass123!"
}

Response (200):
{
    "success": true,
    "data": {
        "user_id": 123,
        "email": "user@example.com",
        "username": "johndoe",
        "first_name": "John",
        "last_name": "Doe",
        "is_admin": false,
        "session_token": "abc123xyz789..."
    },
    "message": "Login successful"
}
```

**POST /api/v1/auth/logout**
```json
Request: (requires authentication)
{}

Response (200):
{
    "success": true,
    "message": "Logout successful"
}
```

**POST /api/v1/auth/verify-email**
```json
Request:
{
    "token": "verification_token_here"
}

Response (200):
{
    "success": true,
    "message": "Email verified successfully"
}
```

**POST /api/v1/auth/forgot-password**
```json
Request:
{
    "email": "user@example.com"
}

Response (200):
{
    "success": true,
    "message": "Password reset instructions sent to your email"
}
```

**POST /api/v1/auth/reset-password**
```json
Request:
{
    "token": "reset_token_here",
    "password": "NewSecurePass123!",
    "password_confirmation": "NewSecurePass123!"
}

Response (200):
{
    "success": true,
    "message": "Password reset successful"
}
```


### Listing Endpoints

**GET /api/v1/listings**
```json
Query Parameters:
- page: int (default: 1)
- per_page: int (default: 20, max: 100)
- category: int
- min_price: float
- max_price: float
- condition: string
- location: string
- radius: float (km)
- sort: string (price_asc, price_desc, date_asc, date_desc, relevance)
- search: string

Response (200):
{
    "success": true,
    "data": [
        {
            "listing_id": 1,
            "title": "iPhone 13 Pro",
            "description": "Excellent condition...",
            "price": 45000.00,
            "listing_type": "fixed",
            "status": "active",
            "category": "Electronics",
            "condition": "like_new",
            "location": "Manila",
            "images": [
                "https://example.com/uploads/img_123.jpg"
            ],
            "seller": {
                "user_id": 456,
                "username": "seller123",
                "rating": 4.8
            },
            "created_at": "2024-01-15T10:30:00Z"
        }
    ],
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 150,
        "total_pages": 8
    }
}
```

**GET /api/v1/listings/:id**
```json
Response (200):
{
    "success": true,
    "data": {
        "listing_id": 1,
        "title": "iPhone 13 Pro",
        "description": "Excellent condition, barely used...",
        "price": 45000.00,
        "listing_type": "fixed",
        "status": "active",
        "category": {
            "category_id": 5,
            "name": "Electronics"
        },
        "condition": "like_new",
        "location": "Manila",
        "latitude": 14.5995,
        "longitude": 120.9842,
        "view_count": 234,
        "favorite_count": 12,
        "images": [
            {
                "image_id": 1,
                "url": "https://example.com/uploads/img_123.jpg",
                "thumbnail": "https://example.com/uploads/thumb_123.jpg"
            }
        ],
        "seller": {
            "user_id": 456,
            "username": "seller123",
            "first_name": "John",
            "rating": 4.8,
            "total_reviews": 45,
            "total_sales": 120
        },
        "created_at": "2024-01-15T10:30:00Z",
        "updated_at": "2024-01-15T10:30:00Z"
    }
}
```

**POST /api/v1/listings** (requires authentication)
```json
Request (multipart/form-data):
{
    "title": "iPhone 13 Pro",
    "description": "Excellent condition...",
    "price": 45000.00,
    "listing_type": "fixed",
    "category_id": 5,
    "condition": "like_new",
    "location": "Manila",
    "images[]": [File, File, File]
}

Response (201):
{
    "success": true,
    "data": {
        "listing_id": 1,
        "title": "iPhone 13 Pro",
        "status": "active"
    },
    "message": "Listing created successfully"
}
```

**PUT /api/v1/listings/:id** (requires authentication, owner only)
```json
Request:
{
    "title": "iPhone 13 Pro - Updated",
    "price": 43000.00,
    "description": "Price reduced!"
}

Response (200):
{
    "success": true,
    "data": {
        "listing_id": 1,
        "title": "iPhone 13 Pro - Updated",
        "price": 43000.00
    },
    "message": "Listing updated successfully"
}
```

**DELETE /api/v1/listings/:id** (requires authentication, owner only)
```json
Response (200):
{
    "success": true,
    "message": "Listing deleted successfully"
}
```

### Bidding Endpoints

**POST /api/v1/bids** (requires authentication)
```json
Request:
{
    "listing_id": 1,
    "amount": 50000.00
}

Response (201):
{
    "success": true,
    "data": {
        "bid_id": 123,
        "listing_id": 1,
        "amount": 50000.00,
        "is_winning": true
    },
    "message": "Bid placed successfully"
}
```

**GET /api/v1/listings/:id/bids**
```json
Response (200):
{
    "success": true,
    "data": [
        {
            "bid_id": 123,
            "amount": 50000.00,
            "bidder": "user***",
            "bid_time": "2024-01-15T14:30:00Z",
            "is_winning": true
        }
    ]
}
```

### Order Endpoints

**GET /api/v1/orders** (requires authentication)
```json
Query Parameters:
- role: string (buyer|seller)
- status: string
- page: int

Response (200):
{
    "success": true,
    "data": [
        {
            "order_id": 1,
            "listing": {
                "listing_id": 1,
                "title": "iPhone 13 Pro",
                "image": "https://example.com/uploads/img_123.jpg"
            },
            "buyer": {
                "user_id": 123,
                "username": "buyer123"
            },
            "seller": {
                "user_id": 456,
                "username": "seller123"
            },
            "total_amount": 45000.00,
            "status": "paid",
            "payment_status": "paid",
            "created_at": "2024-01-15T15:00:00Z"
        }
    ],
    "meta": {
        "page": 1,
        "per_page": 20,
        "total": 10
    }
}
```

**GET /api/v1/orders/:id** (requires authentication)
```json
Response (200):
{
    "success": true,
    "data": {
        "order_id": 1,
        "listing": {...},
        "buyer": {...},
        "seller": {...},
        "total_amount": 45000.00,
        "shipping_cost": 200.00,
        "status": "delivered",
        "payment_status": "paid",
        "payment_method": "gcash",
        "transaction_id": "TXN123456",
        "shipping_address": "123 Main St, Manila",
        "tracking_number": "TRACK123",
        "delivery": {
            "delivery_id": 1,
            "rider": {
                "rider_id": 1,
                "name": "Rider Name",
                "phone": "09123456789"
            },
            "status": "delivered",
            "proof_of_delivery": "https://example.com/uploads/pod_123.jpg"
        },
        "created_at": "2024-01-15T15:00:00Z",
        "delivered_at": "2024-01-16T10:00:00Z"
    }
}
```


### Message Endpoints

**GET /api/v1/messages** (requires authentication)
```json
Response (200):
{
    "success": true,
    "data": [
        {
            "conversation_id": 1,
            "listing": {
                "listing_id": 1,
                "title": "iPhone 13 Pro",
                "image": "https://example.com/uploads/img_123.jpg"
            },
            "other_user": {
                "user_id": 456,
                "username": "seller123",
                "first_name": "John"
            },
            "last_message": {
                "message_text": "Is this still available?",
                "created_at": "2024-01-15T16:00:00Z"
            },
            "unread_count": 2
        }
    ]
}
```

**POST /api/v1/messages** (requires authentication)
```json
Request:
{
    "recipient_id": 456,
    "listing_id": 1,
    "message_text": "Is this still available?"
}

Response (201):
{
    "success": true,
    "data": {
        "message_id": 123,
        "conversation_id": 1,
        "message_text": "Is this still available?",
        "created_at": "2024-01-15T16:00:00Z"
    },
    "message": "Message sent successfully"
}
```

**GET /api/v1/conversations/:id/messages** (requires authentication)
```json
Response (200):
{
    "success": true,
    "data": [
        {
            "message_id": 123,
            "sender_id": 123,
            "message_text": "Is this still available?",
            "is_read": true,
            "created_at": "2024-01-15T16:00:00Z"
        }
    ]
}
```

### Notification Endpoints

**GET /api/v1/notifications** (requires authentication)
```json
Query Parameters:
- unread_only: boolean
- page: int

Response (200):
{
    "success": true,
    "data": [
        {
            "notification_id": 1,
            "type": "new_bid",
            "title": "New bid on your listing",
            "message": "Someone placed a bid of ₱50,000 on iPhone 13 Pro",
            "link": "/home/listing-details.php?id=1",
            "is_read": false,
            "created_at": "2024-01-15T14:30:00Z"
        }
    ],
    "meta": {
        "unread_count": 5
    }
}
```

**PUT /api/v1/notifications/:id/read** (requires authentication)
```json
Response (200):
{
    "success": true,
    "message": "Notification marked as read"
}
```

**PUT /api/v1/notifications/read-all** (requires authentication)
```json
Response (200):
{
    "success": true,
    "message": "All notifications marked as read"
}
```

### Category Endpoints

**GET /api/v1/categories**
```json
Response (200):
{
    "success": true,
    "data": [
        {
            "category_id": 1,
            "name": "Electronics",
            "slug": "electronics",
            "icon": "fa-laptop",
            "listing_count": 234
        }
    ]
}
```

### Review Endpoints

**POST /api/v1/reviews** (requires authentication)
```json
Request:
{
    "order_id": 1,
    "rating": 5,
    "comment": "Great seller, fast shipping!"
}

Response (201):
{
    "success": true,
    "data": {
        "review_id": 1,
        "rating": 5,
        "comment": "Great seller, fast shipping!"
    },
    "message": "Review submitted successfully"
}
```

**GET /api/v1/users/:id/reviews**
```json
Response (200):
{
    "success": true,
    "data": {
        "average_rating": 4.8,
        "total_reviews": 45,
        "reviews": [
            {
                "review_id": 1,
                "rating": 5,
                "comment": "Great seller!",
                "buyer": {
                    "username": "buyer123"
                },
                "seller_response": "Thank you!",
                "created_at": "2024-01-15T10:00:00Z"
            }
        ]
    }
}
```

### Analytics Endpoints (Seller Dashboard)

**GET /api/v1/analytics/dashboard** (requires authentication)
```json
Response (200):
{
    "success": true,
    "data": {
        "revenue": {
            "current_month": 125000.00,
            "all_time": 450000.00
        },
        "listings": {
            "active": 12,
            "sold": 45,
            "total_views": 2340
        },
        "orders": {
            "pending": 3,
            "processing": 5,
            "completed": 45
        },
        "rating": {
            "average": 4.8,
            "total_reviews": 45
        },
        "sales_chart": [
            {
                "date": "2024-01-01",
                "revenue": 5000.00,
                "orders": 3
            }
        ],
        "top_listings": [
            {
                "listing_id": 1,
                "title": "iPhone 13 Pro",
                "views": 234,
                "favorites": 12,
                "sales": 1
            }
        ]
    }
}
```

### Rate Limiting

All API endpoints are rate-limited:
- Authentication endpoints: 5 requests per 15 minutes per IP
- General API endpoints: 100 requests per minute per user
- Search endpoints: 20 requests per minute per user

Rate limit headers included in all responses:
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1642345678
```

When rate limit is exceeded:
```json
Response (429):
{
    "success": false,
    "message": "Rate limit exceeded. Please try again later.",
    "retry_after": 60
}
```


## Security Implementation Details

### 1. Password Hashing (Requirement 1.1)

```php
// Password hashing with bcrypt
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

// Password verification
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// Check if rehashing is needed (cost factor changed)
function needsRehash(string $hash): bool {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}
```

### 2. Session Management (Requirements 1.2-1.7)

```php
// Generate cryptographically secure session token
function generateSessionToken(): string {
    return bin2hex(random_bytes(32));
}

// Create session
function createSession(int $userId, string $ipAddress, string $userAgent): string {
    global $supabase;
    
    // Limit concurrent sessions
    $sessions = $supabase->select('user_sessions', 'session_id', ['user_id' => $userId]);
    if (count($sessions) >= 5) {
        // Delete oldest session
        $oldest = $supabase->customQuery('user_sessions', 'session_id', 
            "user_id=eq.$userId&order=created_at.asc&limit=1");
        $supabase->delete('user_sessions', ['session_id' => $oldest[0]['session_id']]);
    }
    
    $token = generateSessionToken();
    $expiresAt = date('Y-m-d H:i:s', time() + SESSION_LIFETIME);
    
    $supabase->insert('user_sessions', [
        'user_id' => $userId,
        'session_token' => $token,
        'ip_address' => $ipAddress,
        'user_agent' => $userAgent,
        'expires_at' => $expiresAt
    ]);
    
    return $token;
}

// Validate session
function validateSession(string $token): ?array {
    global $supabase;
    
    $session = $supabase->select('user_sessions', '*', ['session_token' => $token], true);
    
    if (!$session) {
        return null;
    }
    
    // Check expiration
    if (strtotime($session['expires_at']) < time()) {
        $supabase->delete('user_sessions', ['session_id' => $session['session_id']]);
        return null;
    }
    
    return $session;
}

// Delete session (logout)
function deleteSession(string $token): bool {
    global $supabase;
    return $supabase->delete('user_sessions', ['session_token' => $token]);
}

// Cleanup expired sessions (run via cron)
function cleanupExpiredSessions(): int {
    global $supabase;
    $now = date('Y-m-d H:i:s');
    $deleted = $supabase->customQuery('user_sessions', 'session_id', "expires_at=lt.$now");
    if ($deleted) {
        foreach ($deleted as $session) {
            $supabase->delete('user_sessions', ['session_id' => $session['session_id']]);
        }
        return count($deleted);
    }
    return 0;
}
```

### 3. CSRF Protection (Requirements 2.1-2.5)

```php
// includes/csrf.php
class CSRFProtection {
    public static function generateToken(): string {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function validateToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function regenerateToken(): void {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    public static function getTokenField(): string {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function validateRequest(): bool {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || 
            $_SERVER['REQUEST_METHOD'] === 'PUT' || 
            $_SERVER['REQUEST_METHOD'] === 'DELETE') {
            
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            
            if (!self::validateToken($token)) {
                http_response_code(403);
                die(json_encode(['success' => false, 'message' => 'CSRF token validation failed']));
            }
        }
        return true;
    }
}
```

### 4. Rate Limiting (Requirements 5.1-5.10)

```php
// includes/rate-limit.php
class RateLimiter {
    private $supabase;
    
    public function __construct($supabase) {
        $this->supabase = $supabase;
    }
    
    public function checkLimit(string $identifier, string $endpoint, int $maxAttempts, int $windowSeconds): bool {
        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
        
        // Check if blocked
        $blocked = $this->supabase->customQuery('rate_limits', 'blocked_until', 
            "identifier=eq.$identifier&endpoint=eq.$endpoint&blocked_until=gt." . date('Y-m-d H:i:s'));
        
        if (!empty($blocked)) {
            http_response_code(429);
            $retryAfter = strtotime($blocked[0]['blocked_until']) - time();
            header("Retry-After: $retryAfter");
            return false;
        }
        
        // Count attempts in window
        $attempts = $this->supabase->customQuery('rate_limits', 'attempt_count', 
            "identifier=eq.$identifier&endpoint=eq.$endpoint&window_start=gte.$windowStart");
        
        $totalAttempts = array_sum(array_column($attempts, 'attempt_count'));
        
        if ($totalAttempts >= $maxAttempts) {
            // Block for specified time
            $blockUntil = date('Y-m-d H:i:s', time() + $windowSeconds * 2);
            $this->supabase->insert('rate_limits', [
                'identifier' => $identifier,
                'endpoint' => $endpoint,
                'attempt_count' => 0,
                'window_start' => date('Y-m-d H:i:s'),
                'blocked_until' => $blockUntil
            ]);
            
            // Log violation
            $this->logViolation($identifier, $endpoint);
            
            http_response_code(429);
            header("Retry-After: " . ($windowSeconds * 2));
            return false;
        }
        
        return true;
    }
    
    public function recordAttempt(string $identifier, string $endpoint): void {
        $this->supabase->insert('rate_limits', [
            'identifier' => $identifier,
            'endpoint' => $endpoint,
            'attempt_count' => 1,
            'window_start' => date('Y-m-d H:i:s')
        ]);
    }
    
    public function cleanup(): int {
        $cutoff = date('Y-m-d H:i:s', time() - 86400);
        $old = $this->supabase->customQuery('rate_limits', 'limit_id', "window_start=lt.$cutoff");
        if ($old) {
            foreach ($old as $record) {
                $this->supabase->delete('rate_limits', ['limit_id' => $record['limit_id']]);
            }
            return count($old);
        }
        return 0;
    }
    
    private function logViolation(string $identifier, string $endpoint): void {
        global $supabase;
        $supabase->insert('audit_logs', [
            'event_type' => 'rate_limit_exceeded',
            'event_description' => "Rate limit exceeded for $endpoint",
            'ip_address' => $identifier,
            'metadata' => json_encode(['endpoint' => $endpoint])
        ]);
    }
}
```

### 5. Input Validation and Sanitization (Requirements 3.5, 4.1, 32.1-32.12)

```php
// Enhanced security.php validation functions
class InputValidator {
    public static function validateEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePhone(string $phone): bool {
        return preg_match('/^(09|\+639)\d{9}$/', $phone);
    }
    
    public static function validatePrice(string $price): bool {
        return preg_match('/^\d+(\.\d{1,2})?$/', $price) && floatval($price) >= 0;
    }
    
    public static function validateListingTitle(string $title): array {
        $errors = [];
        $length = strlen($title);
        
        if ($length < 5) {
            $errors[] = 'Title must be at least 5 characters';
        }
        if ($length > 100) {
            $errors[] = 'Title must not exceed 100 characters';
        }
        
        return $errors;
    }
    
    public static function validateListingDescription(string $description): array {
        $errors = [];
        $length = strlen($description);
        
        if ($length < 20) {
            $errors[] = 'Description must be at least 20 characters';
        }
        if ($length > 5000) {
            $errors[] = 'Description must not exceed 5000 characters';
        }
        
        return $errors;
    }
    
    public static function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    public static function sanitizeFilename(string $filename): string {
        // Remove path traversal attempts
        $filename = basename($filename);
        
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
        
        // Prevent double extensions
        $parts = explode('.', $filename);
        if (count($parts) > 2) {
            $ext = array_pop($parts);
            $filename = implode('_', $parts) . '.' . $ext;
        }
        
        return $filename;
    }
    
    public static function sanitizeHTML(string $html, array $allowedTags = []): string {
        if (empty($allowedTags)) {
            return htmlspecialchars($html, ENT_QUOTES, 'UTF-8');
        }
        
        return strip_tags($html, $allowedTags);
    }
}
```


## Correctness Properties

A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.

The following properties are derived from the acceptance criteria and will be implemented as property-based tests to ensure comprehensive validation across all possible inputs.

### Authentication and Session Management Properties

**Property 1: Session Token Uniqueness**
*For any* two concurrent user sessions, the generated session tokens must be unique and not collide.
**Validates: Requirements 1.2**

**Property 2: Session Data Completeness**
*For any* created session, the database record must contain all required fields: user_id, session_token, ip_address, user_agent, and expires_at.
**Validates: Requirements 1.3**

**Property 3: Expired Session Cleanup**
*For any* session with expires_at timestamp in the past, attempting to validate the session must return null and delete the session record.
**Validates: Requirements 1.4**

**Property 4: Session ID Regeneration on Login**
*For any* user login, the session ID after successful authentication must differ from any session ID that existed before authentication.
**Validates: Requirements 1.5**

**Property 5: Concurrent Session Limit**
*For any* user, the total number of active sessions must never exceed 5, with the oldest session being removed when the limit is reached.
**Validates: Requirements 1.6**

**Property 6: Logout Session Deletion**
*For any* valid session token, calling logout must remove the session record from the database and subsequent validation must fail.
**Validates: Requirements 1.7**

**Property 7: Invalid Session Returns 401**
*For any* invalid or expired session token, authenticated requests must return HTTP 401 status.
**Validates: Requirements 1.9**

**Property 8: Authentication Event Logging**
*For any* authentication event (login, logout, failed attempt), an audit log entry must be created with the event type, user_id, IP address, and timestamp.
**Validates: Requirements 1.10**

### CSRF Protection Properties

**Property 9: CSRF Token Uniqueness Per Session**
*For any* two different user sessions, the CSRF tokens must be unique.
**Validates: Requirements 2.1**

**Property 10: CSRF Token Validation**
*For any* state-changing request (POST, PUT, DELETE) with a CSRF token that doesn't match the session token, the request must be rejected with HTTP 403 status.
**Validates: Requirements 2.3, 2.4**

**Property 11: CSRF Token Regeneration**
*For any* sensitive operation (password change, email change), the CSRF token after the operation must differ from the token before the operation.
**Validates: Requirements 2.5**

**Property 12: Referer Header Validation**
*For any* state-changing request without a valid Referer header matching the application domain, the request must be rejected.
**Validates: Requirements 2.7**

### Input Validation and SQL Injection Prevention Properties

**Property 13: Invalid Column Name Rejection**
*For any* dynamic query with a column name not in the whitelist, the query must be rejected with an error.
**Validates: Requirements 3.4**

**Property 14: Dangerous Input Sanitization**
*For any* user input containing SQL injection patterns (e.g., '; DROP TABLE), the sanitized output must not contain executable SQL syntax.
**Validates: Requirements 3.5**

**Property 15: LIKE Pattern Escaping**
*For any* LIKE clause pattern containing special characters (%, _, \\), the characters must be properly escaped to prevent unintended wildcard matching.
**Validates: Requirements 3.6**

**Property 16: Numeric Type Casting**
*For any* non-numeric input to a numeric parameter field, the system must either reject the input or cast it to zero/null.
**Validates: Requirements 3.7**

### XSS Prevention Properties

**Property 17: HTML Entity Encoding**
*For any* user-generated content containing HTML special characters (<, >, &, ", '), the displayed output must have these characters encoded as HTML entities.
**Validates: Requirements 4.1**

**Property 18: HTML Tag Sanitization**
*For any* rich text input, only tags in the allowlist (p, br, strong, em, ul, ol, li) must remain after sanitization, with all other tags stripped.
**Validates: Requirements 4.4**

**Property 19: Content Storage Round-Trip**
*For any* user input string, storing it to the database and retrieving it must return the exact original string without encoding.
**Validates: Requirements 4.5**

**Property 20: Path Traversal Prevention**
*For any* file upload with a filename containing path traversal patterns (../, ..\, /etc/, C:\\), the sanitized filename must not contain these patterns.
**Validates: Requirements 4.7**

### Rate Limiting Properties

**Property 21: Login Rate Limit Enforcement**
*For any* IP address, after 5 failed login attempts within 15 minutes, the 6th attempt must be blocked with HTTP 429 status.
**Validates: Requirements 5.1, 5.2**

**Property 22: Registration Rate Limit**
*For any* IP address, after 3 registration attempts within 1 hour, the 4th attempt must be blocked with HTTP 429 status.
**Validates: Requirements 5.3**

**Property 23: Password Reset Rate Limit**
*For any* email address, after 3 password reset requests within 1 hour, the 4th request must be blocked with HTTP 429 status.
**Validates: Requirements 5.4**

**Property 24: API Rate Limit**
*For any* authenticated user, after 100 API requests within 1 minute, the 101st request must be blocked with HTTP 429 status and Retry-After header.
**Validates: Requirements 5.5, 5.7**

**Property 25: Rate Limit Persistence**
*For any* rate-limited action, the attempt count must be stored in the rate_limits table with identifier, endpoint, and window_start.
**Validates: Requirements 5.8**

**Property 26: Rate Limit Cleanup**
*For any* rate limit record older than 24 hours, running the cleanup function must remove the record from the database.
**Validates: Requirements 5.9**

**Property 27: Rate Limit Violation Logging**
*For any* rate limit violation, an audit log entry must be created with event type "rate_limit_exceeded".
**Validates: Requirements 5.10**

### File Upload Properties

**Property 28: MIME Type Validation**
*For any* uploaded file, if the MIME type is not in [image/jpeg, image/png, image/gif, image/webp], the upload must be rejected.
**Validates: Requirements 6.1, 6.2**

**Property 29: File Size Limit**
*For any* uploaded file larger than 5MB, the upload must be rejected with an error message.
**Validates: Requirements 6.3**

**Property 30: Image Count Limit Per Listing**
*For any* listing, attempting to upload more than 5 images must be rejected with an error message.
**Validates: Requirements 6.4**

**Property 31: Filename Uniqueness**
*For any* two uploaded files, even with the same original filename, the generated filenames must be unique.
**Validates: Requirements 6.5**

**Property 32: Executable Code Detection**
*For any* uploaded file containing PHP code patterns (<?php, <?=), the upload must be rejected.
**Validates: Requirements 6.8**

**Property 33: Double Extension Rejection**
*For any* filename with double extensions (e.g., image.php.jpg), the upload must be rejected.
**Validates: Requirements 6.9**

**Property 34: Image Compression**
*For any* uploaded image, the compressed output file size must be at least 30% smaller than the original (unless already optimally compressed).
**Validates: Requirements 6.11**

**Property 35: Thumbnail Generation**
*For any* uploaded image, a thumbnail must be generated with dimensions of exactly 150x150 pixels.
**Validates: Requirements 6.12**


### Email Verification Properties

**Property 36: Verification Token Generation**
*For any* user registration, a verification token must be generated, stored in the database, and included in the verification email.
**Validates: Requirements 7.1, 7.2, 7.3**

**Property 37: Verification Token Expiry**
*For any* verification token created more than 24 hours ago, attempting to verify with that token must fail.
**Validates: Requirements 7.4**

**Property 38: Email Verification Success**
*For any* valid verification token, clicking the verification link must set email_verified to true for the associated user.
**Validates: Requirements 7.5**

**Property 39: Unverified User Restrictions**
*For any* user with email_verified = false, attempting to create a listing or place a bid must be rejected.
**Validates: Requirements 7.6**

**Property 40: Verification Resend Rate Limit**
*For any* user, requesting verification email resend within 5 minutes of the previous request must be rejected.
**Validates: Requirements 7.8**

**Property 41: Expired Token Regeneration**
*For any* expired verification token, requesting resend must generate a new token and invalidate the old one.
**Validates: Requirements 7.9**

**Property 42: Verification Token Cleanup**
*For any* successful email verification, the verification_token field must be set to null.
**Validates: Requirements 7.10**

### Password Reset Properties

**Property 43: Reset Token Generation**
*For any* password reset request, a unique reset token must be generated and stored in the password_resets table.
**Validates: Requirements 8.1, 8.2, 8.3**

**Property 44: Reset Token Expiry**
*For any* reset token created more than 1 hour ago, attempting to use it must fail.
**Validates: Requirements 8.4**

**Property 45: Password Confirmation Validation**
*For any* password reset attempt where password and password_confirmation don't match, the reset must be rejected.
**Validates: Requirements 8.6**

**Property 46: Password Minimum Length**
*For any* new password with length less than 8 characters, the password reset must be rejected.
**Validates: Requirements 8.7**

**Property 47: Session Invalidation on Password Change**
*For any* successful password change, all existing sessions for that user must be deleted from user_sessions table.
**Validates: Requirements 8.8**

**Property 48: Reset Token Cleanup**
*For any* successful password reset, the reset token must be marked as used or deleted.
**Validates: Requirements 8.9**

**Property 49: Password Reset Logging**
*For any* password reset event, an audit log entry must be created with event type "password_reset".
**Validates: Requirements 8.10**

### Payment Gateway Properties

**Property 50: Webhook Signature Validation**
*For any* payment webhook with an invalid signature, the webhook must be rejected and not processed.
**Validates: Requirements 9.3**

**Property 51: Order Status Update on Payment**
*For any* completed payment webhook, the associated order status must be updated to "paid".
**Validates: Requirements 9.4**

**Property 52: Transaction Record Creation**
*For any* payment attempt, a transaction record must be created with transaction_ref, payment_gateway, amount, currency, and status.
**Validates: Requirements 9.5**

**Property 53: Payment Refund Processing**
*For any* refund request on a paid order, the refund must be processed through the payment gateway and order status updated to "refunded".
**Validates: Requirements 9.7**

**Property 54: Payment Event Logging**
*For any* payment event (success, failure, refund), an audit log entry must be created.
**Validates: Requirements 9.8**

**Property 55: Sensitive Data Exclusion**
*For any* payment transaction, the database must not contain credit card numbers or CVV codes in any table.
**Validates: Requirements 9.9**

**Property 56: Payment Receipt Email**
*For any* successful payment, an email receipt must be sent to the buyer's email address.
**Validates: Requirements 9.11**

### Review System Properties

**Property 57: Review Enablement After Delivery**
*For any* order with status "delivered", the buyer must be able to submit a review.
**Validates: Requirements 10.1**

**Property 58: Review Submission**
*For any* review submission with rating between 1-5 and comment under 1000 characters, the review must be stored successfully.
**Validates: Requirements 10.2, 10.3**

**Property 59: Average Rating Calculation**
*For any* seller with N reviews, the average rating must equal the sum of all ratings divided by N, rounded to 2 decimal places.
**Validates: Requirements 10.4, 10.7**

**Property 60: One Review Per Order**
*For any* order, attempting to submit a second review must be rejected.
**Validates: Requirements 10.5**

**Property 61: Seller Response Limit**
*For any* review, the seller must be able to add exactly one response, and attempting to add a second response must be rejected.
**Validates: Requirements 10.6**

**Property 62: Review Edit Time Window**
*For any* review created more than 7 days ago, attempting to edit it must be rejected.
**Validates: Requirements 10.9**

**Property 63: Review Content Moderation**
*For any* review containing profanity from a predefined list, the review must be flagged with is_flagged = true.
**Validates: Requirements 10.10**

### Dispute Resolution Properties

**Property 64: Payment Escrow**
*For any* order with status "paid", the payment must remain in escrow (not released to seller) until status changes to "delivered" or dispute is resolved.
**Validates: Requirements 12.1**

**Property 65: Dispute Opening Time Window**
*For any* order delivered more than 7 days ago, attempting to open a dispute must be rejected.
**Validates: Requirements 12.2**

**Property 66: Automatic Payment Release**
*For any* order with status "delivered" for 7 days without a dispute, the payment must be automatically released to the seller.
**Validates: Requirements 12.7**

### Search and Filtering Properties

**Property 67: Full-Text Search**
*For any* search query, all returned listings must have the search term present in either the title or description (case-insensitive).
**Validates: Requirements 13.1**

**Property 68: Price Range Filtering**
*For any* search with min_price and max_price filters, all returned listings must have price >= min_price AND price <= max_price.
**Validates: Requirements 13.2**

**Property 69: Category Filtering**
*For any* search with category filter, all returned listings must belong to the specified category.
**Validates: Requirements 13.2**

**Property 70: Location Radius Filtering**
*For any* search with location and radius filters, all returned listings must be within the specified radius (in km) from the location.
**Validates: Requirements 13.2**

**Property 71: Search Result Sorting**
*For any* search with sort parameter "price_asc", the returned listings must be ordered by price in ascending order.
**Validates: Requirements 13.3**

**Property 72: Pagination Consistency**
*For any* search query, the union of all pages must equal the complete result set with no duplicates or missing items.
**Validates: Requirements 13.4**


### Database Integrity Properties

**Property 73: Foreign Key Cascade Deletion**
*For any* user deletion, all associated listings, bids, favorites, and cart items must be automatically deleted due to CASCADE constraints.
**Validates: Requirements 14.1, 14.2, 14.3, 14.10, 14.11**

**Property 74: Foreign Key Restrict Deletion**
*For any* user with existing orders (as buyer or seller), attempting to delete the user must be rejected due to RESTRICT constraints.
**Validates: Requirements 14.4, 14.5**

**Property 75: Check Constraint Validation - Price**
*For any* listing with price < 0, the database insert/update must be rejected by the check constraint.
**Validates: Requirements 15.1**

**Property 76: Check Constraint Validation - Bid Amount**
*For any* bid with amount <= 0, the database insert must be rejected by the check constraint.
**Validates: Requirements 15.2**

**Property 77: Check Constraint Validation - Rating**
*For any* review with rating outside the range [1, 5], the database insert must be rejected by the check constraint.
**Validates: Requirements 15.3**

**Property 78: Email Format Validation**
*For any* user registration with invalid email format, the database insert must be rejected by the check constraint.
**Validates: Requirements 15.4**

**Property 79: Unique Constraint Enforcement**
*For any* two users with the same email address, the second insert must be rejected by the unique constraint.
**Validates: Requirements 15.8**

**Property 80: Auction End Time Validation**
*For any* auction listing with auction_end_time <= created_at, the database insert must be rejected by the check constraint.
**Validates: Requirements 15.12**

### API Response Properties

**Property 81: API JSON Response Format**
*For any* API endpoint response, the JSON must contain "success" (boolean), "data" (object/array), and "message" (string) fields.
**Validates: Requirements 17.1**

**Property 82: API Status Code Correctness**
*For any* successful API request, the HTTP status code must be 200 (GET), 201 (POST create), or 204 (DELETE).
**Validates: Requirements 17.2**

**Property 83: API Authentication Requirement**
*For any* protected API endpoint request without a valid Authorization header, the response must be HTTP 401.
**Validates: Requirements 17.4**

**Property 84: API Pagination Metadata**
*For any* paginated API response, the meta object must contain page, per_page, total, and total_pages fields.
**Validates: Requirements 17.8**

**Property 85: API Input Validation**
*For any* API request with invalid parameters (e.g., negative price, invalid email), the response must be HTTP 400 with error details.
**Validates: Requirements 17.20**

### Input Validation Properties

**Property 86: Email Validation**
*For any* email input, validation must pass only if the format matches the regex pattern for valid email addresses.
**Validates: Requirements 32.2**

**Property 87: Phone Number Validation**
*For any* Philippine phone number, validation must pass only if it matches the format 09XXXXXXXXX or +639XXXXXXXXX.
**Validates: Requirements 32.3**

**Property 88: Price Validation**
*For any* price input, validation must pass only if it's a positive number with maximum 2 decimal places.
**Validates: Requirements 32.4**

**Property 89: Listing Title Length Validation**
*For any* listing title with length outside the range [5, 100], validation must fail with a specific error message.
**Validates: Requirements 32.5**

**Property 90: Listing Description Length Validation**
*For any* listing description with length outside the range [20, 5000], validation must fail with a specific error message.
**Validates: Requirements 32.6**

**Property 91: Date Future Validation**
*For any* auction end time that is not at least 1 hour in the future, validation must fail.
**Validates: Requirements 32.10**

### Auction System Properties

**Property 92: Bid Amount Validation**
*For any* bid on an auction, the bid amount must be greater than the current highest bid plus the minimum bid increment.
**Validates: Requirements 33.3, 33.4**

**Property 93: Self-Bidding Prevention**
*For any* auction listing, the seller must not be able to place a bid on their own listing.
**Validates: Requirements 33.5**

**Property 94: Auction Extension on Late Bid**
*For any* bid placed within the last 5 minutes of an auction, the auction end time must be extended by 5 minutes.
**Validates: Requirements 33.6**

**Property 95: Automatic Order Creation**
*For any* auction that ends with at least one bid, an order must be automatically created for the highest bidder.
**Validates: Requirements 33.7**

**Property 96: Auction Cancellation Restriction**
*For any* auction with at least one bid, attempting to cancel the auction must be rejected.
**Validates: Requirements 33.11**

### Messaging System Properties

**Property 97: Message Length Validation**
*For any* message with length outside the range [1, 1000], the message send must be rejected.
**Validates: Requirements 34.5**

**Property 98: Message Read Status Update**
*For any* conversation opened by a user, all unread messages in that conversation must be marked as read.
**Validates: Requirements 34.3**

### Cart and Checkout Properties

**Property 99: Cart Item Availability Validation**
*For any* checkout attempt, if any cart item has status != 'active', the item must be removed from cart with a notification.
**Validates: Requirements 35.4, 35.5**

**Property 100: Multi-Vendor Order Separation**
*For any* cart containing items from N different sellers, the checkout must create N separate orders, one per seller.
**Validates: Requirements 35.10**

**Property 101: Cart Cleanup After Checkout**
*For any* successful checkout, all items in the user's cart must be removed.
**Validates: Requirements 35.12**

### Audit Logging Properties

**Property 102: Login Attempt Logging**
*For any* login attempt (successful or failed), an audit log entry must be created with username, IP address, user agent, and timestamp.
**Validates: Requirements 37.1, 37.2**

**Property 103: Password Change Logging**
*For any* password change, an audit log entry must be created with user_id, IP address, and timestamp.
**Validates: Requirements 37.3**

**Property 104: Admin Action Logging**
*For any* admin action (user suspension, content removal), an audit log entry must be created with admin user_id, action type, and target.
**Validates: Requirements 37.5**

**Property 105: Payment Transaction Logging**
*For any* payment transaction, an audit log entry must be created with transaction_id, amount, status, and timestamp.
**Validates: Requirements 37.6**

**Property 106: File Upload Logging**
*For any* file upload, an audit log entry must be created with filename, file size, MIME type, and user_id.
**Validates: Requirements 37.8**

### Delivery System Properties

**Property 107: Rider Assignment Within Radius**
*For any* order requiring delivery, the assigned rider must be within 10km of the pickup location.
**Validates: Requirements 28.1**

**Property 108: Rider Workload Limit**
*For any* rider, the number of concurrent assigned deliveries must not exceed 5.
**Validates: Requirements 28.8**

**Property 109: Delivery Status Progression**
*For any* delivery, the status transitions must follow the valid sequence: pending → assigned → picked_up → in_transit → delivered.
**Validates: Requirements 27.2**

### Notification System Properties

**Property 110: Bid Notification Creation**
*For any* new bid placed on a listing, a notification must be created for the listing owner.
**Validates: Requirements 26.1**

**Property 111: Outbid Notification Creation**
*For any* bid that is outbid by a higher bid, a notification must be created for the outbid user.
**Validates: Requirements 26.2**

**Property 112: Order Status Change Notification**
*For any* order status change, a notification must be created for the buyer.
**Validates: Requirements 26.4**


## Error Handling

### Error Handling Strategy (Requirement 19)

The system implements a comprehensive error handling strategy with multiple layers:

**1. Error Logging**
```php
// includes/error-handler.php
class ErrorHandler {
    private static $logFile = __DIR__ . '/../logs/error.log';
    private static $criticalEmails = ['admin@mineteh.com'];
    
    public static function init(): void {
        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }
    
    public static function handleError($severity, $message, $file, $line): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $error = [
            'type' => 'error',
            'severity' => self::getSeverityName($severity),
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $_SERVER['REQUEST_URI'] ?? 'CLI',
            'user_id' => $_SESSION['user_id'] ?? null
        ];
        
        self::logError($error);
        
        if ($severity === E_ERROR || $severity === E_USER_ERROR) {
            self::sendCriticalAlert($error);
        }
        
        return true;
    }
    
    public static function handleException(Throwable $e): void {
        $error = [
            'type' => 'exception',
            'class' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        self::sendCriticalAlert($error);
        self::displayErrorPage($e);
    }
    
    private static function logError(array $error): void {
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents(self::$logFile, json_encode($error) . "\n", FILE_APPEND);
    }
    
    private static function displayErrorPage(Throwable $e): void {
        http_response_code(500);
        
        if (ENVIRONMENT === 'production') {
            include __DIR__ . '/../views/errors/500.php';
        } else {
            echo "<h1>Error</h1>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
        exit;
    }
}
```

**2. Database Error Handling**
```php
// Wrap all database operations in try-catch
try {
    $result = $supabase->insert('listings', $data);
    if ($result === false) {
        $error = $supabase->getLastError();
        throw new DatabaseException("Database insert failed: " . json_encode($error));
    }
} catch (Exception $e) {
    ErrorHandler::logError([
        'type' => 'database_error',
        'message' => $e->getMessage(),
        'query_context' => 'insert listing'
    ]);
    
    // Return user-friendly error
    return [
        'success' => false,
        'message' => 'Unable to create listing. Please try again later.'
    ];
}
```

**3. API Error Responses**
```php
// Standardized API error response
function apiError(string $message, int $code = 400, array $errors = []): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('c')
    ]);
    exit;
}
```

**4. Graceful Degradation**
- Database connection failures: Display cached content or maintenance page
- Payment gateway failures: Queue for retry, notify user
- Image upload failures: Allow listing creation without images
- Search failures: Fall back to simple query without full-text search

**5. Log Rotation**
```bash
# cron/rotate-logs.sh
# Run daily at midnight
0 0 * * * /path/to/rotate-logs.sh

#!/bin/bash
LOG_DIR="/path/to/logs"
ARCHIVE_DIR="$LOG_DIR/archive"
DATE=$(date +%Y%m%d)

# Rotate error log
if [ -f "$LOG_DIR/error.log" ]; then
    gzip -c "$LOG_DIR/error.log" > "$ARCHIVE_DIR/error-$DATE.log.gz"
    > "$LOG_DIR/error.log"
fi

# Delete logs older than 30 days
find "$ARCHIVE_DIR" -name "*.log.gz" -mtime +30 -delete
```


## Testing Strategy

### Dual Testing Approach

The MineTeh platform will implement both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests**: Verify specific examples, edge cases, and error conditions
**Property Tests**: Verify universal properties across all inputs

Together, these approaches provide comprehensive coverage where unit tests catch concrete bugs and property tests verify general correctness.

### Property-Based Testing Configuration

**Testing Framework**: PHPUnit with Eris (PHP property-based testing library)

**Installation**:
```bash
composer require --dev phpunit/phpunit
composer require --dev giorgiosironi/eris
```

**Configuration** (phpunit.xml):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         verbose="true">
    <testsuites>
        <testsuite name="Unit Tests">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Property Tests">
            <directory>tests/Property</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="ERIS_ITERATIONS" value="100"/>
    </php>
</phpunit>
```

### Property Test Examples

Each correctness property must be implemented as a property-based test with minimum 100 iterations:

**Example 1: Session Token Uniqueness (Property 1)**
```php
// tests/Property/AuthenticationTest.php
use Eris\Generator;

class AuthenticationPropertyTest extends \PHPUnit\Framework\TestCase {
    use \Eris\TestTrait;
    
    /**
     * Feature: mineteh-comprehensive-improvements
     * Property 1: For any two concurrent user sessions, the generated 
     * session tokens must be unique and not collide.
     */
    public function testSessionTokenUniqueness() {
        $this->forAll(
            Generator\int(1, 1000), // user_id 1
            Generator\int(1, 1000)  // user_id 2
        )->then(function ($userId1, $userId2) {
            $auth = new AuthenticationModule();
            
            $token1 = $auth->createSession($userId1, '127.0.0.1', 'TestAgent');
            $token2 = $auth->createSession($userId2, '127.0.0.1', 'TestAgent');
            
            $this->assertNotEquals($token1, $token2, 
                "Session tokens must be unique");
            
            // Cleanup
            $auth->deleteSession($token1);
            $auth->deleteSession($token2);
        });
    }
}
```

**Example 2: Price Range Filtering (Property 68)**
```php
/**
 * Feature: mineteh-comprehensive-improvements
 * Property 68: For any search with min_price and max_price filters,
 * all returned listings must have price >= min_price AND price <= max_price.
 */
public function testPriceRangeFiltering() {
    $this->forAll(
        Generator\float(0, 100000),  // min_price
        Generator\float(0, 100000)   // max_price
    )->when(function ($minPrice, $maxPrice) {
        return $minPrice <= $maxPrice;
    })->then(function ($minPrice, $maxPrice) {
        $search = new SearchEngine();
        $results = $search->filterByPriceRange($minPrice, $maxPrice)->execute();
        
        foreach ($results as $listing) {
            $this->assertGreaterThanOrEqual($minPrice, $listing['price'],
                "Listing price must be >= min_price");
            $this->assertLessThanOrEqual($maxPrice, $listing['price'],
                "Listing price must be <= max_price");
        }
    });
}
```

**Example 3: Rate Limit Enforcement (Property 21)**
```php
/**
 * Feature: mineteh-comprehensive-improvements
 * Property 21: For any IP address, after 5 failed login attempts within
 * 15 minutes, the 6th attempt must be blocked with HTTP 429 status.
 */
public function testLoginRateLimitEnforcement() {
    $this->forAll(
        Generator\string()->withMaxSize(15) // IP address
    )->then(function ($ipAddress) {
        $rateLimiter = new RateLimiter($GLOBALS['supabase']);
        
        // Make 5 attempts - should all succeed
        for ($i = 0; $i < 5; $i++) {
            $allowed = $rateLimiter->checkLimit($ipAddress, 'login', 5, 900);
            $this->assertTrue($allowed, "First 5 attempts should be allowed");
            $rateLimiter->recordAttempt($ipAddress, 'login');
        }
        
        // 6th attempt should be blocked
        $allowed = $rateLimiter->checkLimit($ipAddress, 'login', 5, 900);
        $this->assertFalse($allowed, "6th attempt should be blocked");
        $this->assertEquals(429, http_response_code());
        
        // Cleanup
        $rateLimiter->cleanup();
    });
}
```

### Unit Test Examples

Unit tests focus on specific scenarios and edge cases:

**Example 1: Password Hashing**
```php
// tests/Unit/AuthenticationTest.php
class AuthenticationUnitTest extends \PHPUnit\Framework\TestCase {
    public function testPasswordHashingUsesBcrypt() {
        $password = 'SecurePass123!';
        $hash = hashPassword($password);
        
        // Bcrypt hashes start with $2y$
        $this->assertStringStartsWith('$2y$', $hash);
        
        // Verify password
        $this->assertTrue(verifyPassword($password, $hash));
        
        // Wrong password should fail
        $this->assertFalse(verifyPassword('WrongPassword', $hash));
    }
    
    public function testEmptyPasswordRejected() {
        $this->expectException(InvalidArgumentException::class);
        hashPassword('');
    }
}
```

**Example 2: CSRF Token Validation**
```php
class CSRFProtectionTest extends \PHPUnit\Framework\TestCase {
    public function testValidTokenAccepted() {
        $_SESSION = [];
        $token = CSRFProtection::generateToken();
        
        $this->assertTrue(CSRFProtection::validateToken($token));
    }
    
    public function testInvalidTokenRejected() {
        $_SESSION = [];
        CSRFProtection::generateToken();
        
        $this->assertFalse(CSRFProtection::validateToken('invalid_token'));
    }
    
    public function testMissingTokenRejected() {
        $_SESSION = [];
        
        $this->assertFalse(CSRFProtection::validateToken(''));
    }
}
```

### Integration Tests

Integration tests verify end-to-end workflows:

```php
// tests/Integration/CheckoutFlowTest.php
class CheckoutFlowTest extends \PHPUnit\Framework\TestCase {
    public function testCompleteCheckoutFlow() {
        // 1. Create user
        $user = $this->createTestUser();
        
        // 2. Create listing
        $listing = $this->createTestListing($user['seller_id']);
        
        // 3. Add to cart
        $cart = new CartManager();
        $cart->addItem($user['buyer_id'], $listing['listing_id']);
        
        // 4. Checkout
        $order = new OrderManager();
        $orderId = $order->createOrder($user['buyer_id'], 
            [$listing], 
            ['address' => '123 Test St']);
        
        $this->assertNotNull($orderId);
        
        // 5. Process payment
        $payment = new PaymentManager(new MockPaymentGateway());
        $result = $payment->processPayment($orderId, 45000.00);
        
        $this->assertTrue($result['success']);
        
        // 6. Verify order status
        $orderData = $order->getOrder($orderId);
        $this->assertEquals('paid', $orderData['payment_status']);
        
        // 7. Verify cart cleared
        $cartItems = $cart->getItems($user['buyer_id']);
        $this->assertEmpty($cartItems);
    }
}
```

### Test Coverage Goals

- **Unit Tests**: 70% code coverage minimum
- **Property Tests**: All 112 correctness properties implemented
- **Integration Tests**: All critical user flows covered
- **API Tests**: All endpoints tested with valid and invalid inputs

### Continuous Testing

All tests run automatically:
- On every commit (via Git hooks)
- On pull requests (via CI/CD pipeline)
- Before deployment (blocking deployment if tests fail)
- Nightly (full test suite including slow tests)

### Test Database

Tests use a separate test database that is reset between test runs:

```php
// tests/bootstrap.php
require_once __DIR__ . '/../vendor/autoload.php';

// Use test database
$_ENV['SUPABASE_URL'] = 'https://test.supabase.co';
$_ENV['SUPABASE_KEY'] = 'test_key';

// Seed test data
function seedTestDatabase() {
    global $supabase;
    
    // Create test users
    $supabase->insert('users', [
        'email' => 'test@example.com',
        'password_hash' => hashPassword('TestPass123!'),
        'username' => 'testuser',
        'first_name' => 'Test',
        'last_name' => 'User',
        'email_verified' => true
    ]);
    
    // Create test categories
    // Create test listings
    // etc.
}

// Reset database before each test suite
seedTestDatabase();
```


## Implementation Guidance

### Phase 1: Security Foundation (Weeks 1-2)

**Priority: CRITICAL**

1. **Password Hashing Migration**
   - Audit existing password storage
   - Implement bcrypt hashing with cost factor 12
   - Create migration script to rehash existing passwords on next login
   - File: `includes/auth.php`

2. **Session Management Overhaul**
   - Create `user_sessions` table
   - Implement secure session token generation
   - Add session validation middleware
   - Implement concurrent session limits
   - Files: `database/migrations/001_create_user_sessions.sql`, `includes/auth.php`

3. **CSRF Protection**
   - Implement CSRF token generation and validation
   - Add CSRF tokens to all forms
   - Add CSRF validation middleware
   - Files: `includes/csrf.php`, update all form files

4. **Rate Limiting**
   - Create `rate_limits` table
   - Implement rate limiter class
   - Apply to login, registration, password reset, API endpoints
   - Files: `database/migrations/007_create_rate_limits.sql`, `includes/rate-limit.php`

5. **Input Validation Enhancement**
   - Create comprehensive validation class
   - Add validation to all user inputs
   - Implement XSS filtering
   - Files: `includes/validation.php`, update all action files

### Phase 2: Database Integrity (Week 3)

**Priority: HIGH**

1. **Foreign Key Constraints**
   - Add foreign keys to all tables
   - Test cascade and restrict behaviors
   - File: `database/migrations/010_add_foreign_keys.sql`

2. **Check Constraints**
   - Add check constraints for prices, ratings, dates
   - Test constraint enforcement
   - File: `database/migrations/011_add_check_constraints.sql`

3. **Indexes**
   - Add performance indexes
   - Test query performance improvements
   - File: `database/migrations/012_add_indexes.sql`

4. **New Tables**
   - Create password_resets, transactions, reviews, disputes, audit_logs, user_addresses
   - Files: `database/migrations/002-009_*.sql`

### Phase 3: Core Features (Weeks 4-5)

**Priority: HIGH**

1. **Email Verification**
   - Implement verification token generation
   - Create verification email template
   - Add verification check to listing/bidding
   - Files: `includes/auth.php`, `actions/verify-email.php`

2. **Password Reset Flow**
   - Implement reset token generation
   - Create reset email template
   - Add reset form and processing
   - Files: `forgot-password.php`, `reset-password.php`, `actions/password-reset.php`

3. **Payment Gateway Integration**
   - Choose provider (GCash/PayPal/Stripe)
   - Implement payment interface
   - Add webhook handling
   - Create transaction records
   - Files: `includes/payment.php`, `actions/payment-webhook.php`

4. **Review System**
   - Create reviews table
   - Implement review submission
   - Add seller response capability
   - Calculate and display ratings
   - Files: `database/migrations/005_create_reviews.sql`, `includes/review.php`, `home/submit-review.php`

5. **Dispute Resolution**
   - Create disputes tables
   - Implement dispute opening
   - Add admin dispute management
   - Files: `database/migrations/006_create_disputes.sql`, `includes/dispute.php`, `admin/disputes.php`

### Phase 4: API Completion (Week 6)

**Priority: MEDIUM**

1. **API Endpoints**
   - Implement all 20 required endpoints
   - Add proper authentication
   - Implement rate limiting
   - Add CORS headers
   - Directory: `actions/v1/`

2. **API Documentation**
   - Create OpenAPI/Swagger documentation
   - Add example requests/responses
   - Document authentication
   - File: `API_DOCUMENTATION.md`

### Phase 5: Performance Optimization (Week 7)

**Priority: MEDIUM**

1. **Image Optimization**
   - Implement WebP conversion
   - Generate responsive sizes
   - Add lazy loading
   - Files: `includes/image.php`, update listing display pages

2. **Database Query Optimization**
   - Implement query caching
   - Optimize N+1 queries
   - Add query logging
   - Files: `includes/cache.php`, update database queries

3. **Frontend Optimization**
   - Minify CSS/JS
   - Combine assets
   - Add CDN for libraries
   - Implement service worker
   - Files: `build/minify.php`, `sw.js`

### Phase 6: User Experience (Week 8)

**Priority: MEDIUM**

1. **Responsive Design**
   - Test on all screen sizes
   - Fix mobile issues
   - Optimize touch targets
   - Files: `css/responsive.css`, update all pages

2. **Accessibility**
   - Add alt text to images
   - Implement keyboard navigation
   - Add ARIA labels
   - Test with screen readers
   - Files: update all pages

3. **UX Improvements**
   - Add breadcrumbs
   - Add loading indicators
   - Add confirmation dialogs
   - Implement debouncing/throttling
   - Files: `js/ux-improvements.js`, update all pages

### Phase 7: Admin Tools (Week 9)

**Priority: LOW**

1. **Content Moderation**
   - Add flagging system
   - Create moderation queue
   - Implement bulk actions
   - Files: `admin/moderation.php`, `actions/flag-content.php`

2. **Analytics Dashboard**
   - Implement seller dashboard
   - Add admin dashboard
   - Create analytics queries
   - Files: `home/seller-dashboard.php`, `admin/analytics.php`

3. **Audit Logging**
   - Implement comprehensive logging
   - Create log viewer
   - Add log search
   - Files: `includes/audit.php`, `admin/audit-logs.php`

### Phase 8: Operations (Week 10)

**Priority: LOW**

1. **Database Backups**
   - Implement automated backups
   - Test restoration
   - Document procedures
   - Files: `scripts/backup.sh`, `BACKUP_PROCEDURES.md`

2. **Environment Configuration**
   - Create .env.example
   - Document all variables
   - Implement validation
   - Files: `.env.example`, `config.php`

3. **Testing Infrastructure**
   - Set up PHPUnit
   - Write property tests
   - Write unit tests
   - Write integration tests
   - Directory: `tests/`

4. **CI/CD Pipeline**
   - Set up GitHub Actions
   - Configure automated testing
   - Implement deployment automation
   - Files: `.github/workflows/`, `deploy.sh`

5. **Documentation**
   - Update README
   - Create API documentation
   - Write deployment guide
   - Create troubleshooting guide
   - Files: `README.md`, `DEPLOYMENT.md`, `TROUBLESHOOTING.md`

### Testing Checkpoints

After each phase:
1. Run all unit tests
2. Run all property tests
3. Run integration tests
4. Manual testing of new features
5. Security audit of changes
6. Performance testing
7. Code review

### Deployment Strategy

1. **Development**: Test all changes locally
2. **Staging**: Deploy to staging environment for QA
3. **Production**: Deploy during low-traffic hours
4. **Monitoring**: Watch error logs and metrics for 24 hours
5. **Rollback**: Have rollback plan ready if issues arise

### Risk Mitigation

**High-Risk Changes:**
- Database schema changes (use migrations with rollback)
- Authentication changes (test thoroughly, have backup admin access)
- Payment integration (use sandbox mode first)

**Mitigation Strategies:**
- Feature flags for gradual rollout
- Database backups before migrations
- Canary deployments for critical changes
- Comprehensive testing before production
- 24/7 monitoring during rollout


## Summary

This design document provides a comprehensive blueprint for transforming the MineTeh marketplace platform from 58% complete to a production-ready, enterprise-grade e-commerce system. The design addresses all 42 requirements across six major categories:

### Key Deliverables

1. **Security Enhancements**: Enterprise-grade security with bcrypt password hashing, secure session management, CSRF protection, SQL injection prevention, XSS filtering, rate limiting, secure file uploads, email verification, and password reset flows.

2. **Database Integrity**: Complete referential integrity with foreign key constraints, check constraints, validation rules, and performance indexes across all 20+ tables.

3. **Core Features**: Payment gateway integration, review/rating system, seller analytics dashboard, buyer protection with dispute resolution, and advanced search with filtering.

4. **API Completion**: Full RESTful API with 20+ endpoints, proper authentication, rate limiting, versioning, and comprehensive documentation for mobile app development.

5. **Performance Optimization**: Image optimization with WebP conversion, database query caching, frontend asset minification, lazy loading, and CDN integration achieving sub-2-second page loads.

6. **User Experience**: Responsive design, WCAG AA accessibility compliance, enhanced notifications, delivery tracking, location-based features, improved UX patterns, and mobile optimization.

7. **Operations**: Comprehensive error handling and logging, automated database backups, environment configuration management, testing infrastructure with 112 property-based tests, CI/CD pipeline, and complete documentation.

### Architecture Highlights

- **Three-tier architecture**: Clean separation between presentation, business logic, and data access layers
- **Security-first design**: Multiple security layers from transport to data encryption
- **Modular components**: Well-defined interfaces for authentication, payment, search, delivery, and other core modules
- **Scalable database**: Optimized schema with proper indexing, constraints, and RLS policies
- **RESTful API**: Standard JSON responses with proper status codes and error handling
- **Comprehensive testing**: Dual approach with unit tests and property-based tests for 70%+ coverage

### Implementation Timeline

- **Phase 1-2 (Weeks 1-3)**: Security foundation and database integrity - CRITICAL
- **Phase 3-4 (Weeks 4-6)**: Core features and API completion - HIGH priority
- **Phase 5-6 (Weeks 7-8)**: Performance and UX improvements - MEDIUM priority
- **Phase 7-8 (Weeks 9-10)**: Admin tools and operations - LOW priority

Total estimated timeline: 10 weeks with a team of 2-3 developers.

### Success Criteria

The implementation will be considered successful when:

1. All 112 correctness properties pass with 100+ iterations each
2. Unit test coverage exceeds 70%
3. All critical user flows pass integration tests
4. Security audit shows no high or critical vulnerabilities
5. Page load times are under 2 seconds for 95% of requests
6. API response times are under 500ms for 95% of requests
7. System handles 1000+ concurrent users without degradation
8. Zero data loss or corruption incidents
9. All 42 requirements are fully implemented and verified
10. Documentation is complete and up-to-date

### Next Steps

1. **Review and Approval**: Stakeholders review this design document
2. **Resource Allocation**: Assign development team and timeline
3. **Environment Setup**: Prepare development, staging, and production environments
4. **Sprint Planning**: Break down phases into 2-week sprints
5. **Implementation**: Begin Phase 1 (Security Foundation)
6. **Continuous Testing**: Run tests after each feature completion
7. **Deployment**: Gradual rollout with monitoring
8. **Maintenance**: Ongoing monitoring, bug fixes, and improvements

This design provides a solid foundation for building a secure, scalable, and user-friendly marketplace platform that can compete with established e-commerce solutions while maintaining the unique features that make MineTeh special.

