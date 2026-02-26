# MineTeh REST API Documentation

Base URL: `http://your-domain.com/api/v1/`

## Authentication

All authenticated endpoints require either:
- **Web**: Active PHP session
- **Mobile**: `Authorization: Bearer {token}` header

## Endpoints

### Authentication

#### POST `/auth/login.php`
Login user and get access token.

**Request Body:**
```json
{
  "identifier": "username or email",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "account_id": 1,
      "username": "john_doe",
      "email": "john@example.com",
      "first_name": "John",
      "last_name": "Doe"
    },
    "token": "abc123...",
    "expires_at": "2026-03-28 10:00:00"
  }
}
```

#### POST `/auth/register.php`
Register new user account.

**Request Body:**
```json
{
  "username": "john_doe",
  "email": "john@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Response:** Same as login

#### POST `/auth/logout.php`
Logout and invalidate token (requires auth).

**Response:**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### Listings

#### GET `/listings/index.php`
Get all active listings with optional filters.

**Query Parameters:**
- `category` (optional): Filter by category
- `type` (optional): Filter by type (BID or FIXED)
- `search` (optional): Search in title
- `limit` (optional): Number of results (default: 50)
- `offset` (optional): Pagination offset (default: 0)

**Example:** `/listings/index.php?category=electronics&limit=20`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "iPhone 13",
      "description": "Brand new",
      "price": 45000,
      "location": "Manila",
      "category": "electronics",
      "listing_type": "FIXED",
      "status": "active",
      "image": "/uploads/img_123.jpg",
      "seller": {
        "username": "seller1",
        "first_name": "John",
        "last_name": "Doe"
      },
      "created_at": "2026-02-26 10:00:00"
    }
  ]
}
```

#### GET `/listings/show.php?id={id}`
Get single listing details.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "iPhone 13",
    "description": "Brand new",
    "price": 45000,
    "images": [
      {"image_path": "/uploads/img_123.jpg"},
      {"image_path": "/uploads/img_124.jpg"}
    ],
    "seller": {
      "account_id": 5,
      "username": "seller1",
      "first_name": "John",
      "last_name": "Doe"
    },
    "is_favorited": false,
    "bids": [],
    "highest_bid": null
  }
}
```

#### POST `/listings/create.php` (requires auth)
Create new listing.

**Request Body:**
```json
{
  "title": "iPhone 13",
  "description": "Brand new",
  "price": 45000,
  "location": "Manila",
  "category": "electronics",
  "listing_type": "FIXED"
}
```

For auction listings, add:
```json
{
  "listing_type": "BID",
  "end_time": "2026-03-01 18:00:00",
  "min_bid_increment": 100
}
```

**Response:**
```json
{
  "success": true,
  "message": "Listing created successfully",
  "data": { /* listing object */ }
}
```

---

### Bids

#### POST `/bids/place.php` (requires auth)
Place bid on auction listing.

**Request Body:**
```json
{
  "listing_id": 5,
  "bid_amount": 1500
}
```

**Response:**
```json
{
  "success": true,
  "message": "Bid placed successfully",
  "data": {
    "bid_amount": 1500,
    "listing_id": 5
  }
}
```

---

### Favorites

#### POST `/favorites/toggle.php` (requires auth)
Add or remove listing from favorites.

**Request Body:**
```json
{
  "listing_id": 5
}
```

**Response:**
```json
{
  "success": true,
  "message": "Added to favorites",
  "data": {
    "is_favorited": true
  }
}
```

#### GET `/favorites/index.php` (requires auth)
Get user's favorite listings.

**Response:**
```json
{
  "success": true,
  "data": [ /* array of listing objects */ ]
}
```

---

## Error Responses

All errors follow this format:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `201` - Created
- `400` - Bad Request (validation error)
- `401` - Unauthorized (not logged in)
- `404` - Not Found
- `409` - Conflict (duplicate data)
- `500` - Server Error

---

## Android Integration Example

### Setup Retrofit

```kotlin
// build.gradle
implementation("com.squareup.retrofit2:retrofit:2.9.0")
implementation("com.squareup.retrofit2:converter-gson:2.9.0")
implementation("com.squareup.okhttp3:logging-interceptor:4.11.0")

// ApiClient.kt
object ApiClient {
    private const val BASE_URL = "http://your-domain.com/api/v1/"
    
    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor(HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        })
        .addInterceptor { chain ->
            val token = TokenManager.getToken()
            val request = if (token != null) {
                chain.request().newBuilder()
                    .addHeader("Authorization", "Bearer $token")
                    .build()
            } else {
                chain.request()
            }
            chain.proceed(request)
        }
        .build()
    
    val retrofit: Retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
}

// ApiService.kt
interface ApiService {
    @POST("auth/login.php")
    suspend fun login(@Body credentials: LoginRequest): Response<ApiResponse<LoginData>>
    
    @GET("listings/index.php")
    suspend fun getListings(
        @Query("category") category: String? = null,
        @Query("limit") limit: Int = 50
    ): Response<ApiResponse<List<Listing>>>
    
    @GET("listings/show.php")
    suspend fun getListing(@Query("id") id: Int): Response<ApiResponse<Listing>>
    
    @POST("bids/place.php")
    suspend fun placeBid(@Body bid: BidRequest): Response<ApiResponse<BidData>>
}

// Usage in ViewModel
class ListingsViewModel : ViewModel() {
    private val api = ApiClient.retrofit.create(ApiService::class.java)
    
    fun getListings() = viewModelScope.launch {
        val response = api.getListings(category = "electronics")
        if (response.isSuccessful) {
            val listings = response.body()?.data
            // Update UI
        }
    }
}
```

---

## Database Setup

Create the `user_sessions` table in Supabase:

```sql
CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES accounts(account_id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_user_sessions_token ON user_sessions(token);
CREATE INDEX idx_user_sessions_user_id ON user_sessions(user_id);
```

---

## Testing

Test endpoints using curl:

```bash
# Login
curl -X POST http://localhost/MineTeh/api/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"dummy1","password":"password123"}'

# Get listings
curl http://localhost/MineTeh/api/v1/listings/index.php

# Get listing with auth
curl http://localhost/MineTeh/api/v1/listings/show.php?id=3 \
  -H "Authorization: Bearer YOUR_TOKEN"
```
