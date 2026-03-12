# MineTeh API Endpoints Documentation

## Base URL
```
https://mineteh.infinityfreeapp.com/actions/v1/
```

## Authentication

All authenticated endpoints require either:
- **Session Cookie** (for web)
- **Authorization Header** (for mobile): `Authorization: Bearer {token}`

---

## Auth Endpoints

### 1. Login
**Endpoint:** `POST /auth/login.php`

**Request Body:**
```json
{
  "identifier": "username or email",
  "password": "user_password"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "account_id": 1,
      "username": "user123",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "is_admin": false,
      "is_rider": false,
      "user_status": "active"
    },
    "token": "abc123...",
    "expires_at": "2024-02-15 10:30:00"
  }
}
```

**Error Responses:**
- `400`: Missing credentials
- `401`: Incorrect password
- `403`: Account banned
- `404`: Account not found
- `500`: Server error

---

### 2. Register
**Endpoint:** `POST /auth/register.php`

**Request Body:**
```json
{
  "username": "user123",
  "email": "user@example.com",
  "password": "SecurePass1!",
  "first_name": "John",
  "last_name": "Doe"
}
```

**Validation Rules:**
- **Username**: 
  - Minimum 6 characters
  - Must contain at least one number
- **First Name**: 
  - Minimum 2 characters
  - Cannot contain numbers
- **Last Name**: 
  - Minimum 2 characters
  - Cannot contain numbers
- **Email**: Valid email format
- **Password**: 
  - 6-20 characters
  - At least 1 uppercase letter
  - At least 1 number
  - At least 1 special character (!@#$%^&*(),.?":{}|<>)

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "account_id": 1,
      "username": "user123",
      "email": "user@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "is_admin": false,
      "is_rider": false
    },
    "token": "abc123...",
    "expires_at": "2024-02-15 10:30:00"
  }
}
```

**Error Responses:**
- `400`: Validation errors
- `409`: Username or email already exists
- `500`: Server error

---

### 3. Logout
**Endpoint:** `POST /auth/logout.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Logout successful"
}
```

---

## Listings Endpoints

### 1. Get All Listings
**Endpoint:** `GET /listings/index.php`

**Query Parameters:**
- `category` (optional): Filter by category
- `search` (optional): Search term
- `limit` (optional): Number of results (default: 20)
- `offset` (optional): Pagination offset

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "listings": [
      {
        "listing_id": 1,
        "title": "Item Title",
        "description": "Item description",
        "price": 100.00,
        "category": "Items",
        "images": ["image1.jpg", "image2.jpg"],
        "seller": {
          "username": "seller123",
          "first_name": "Jane"
        },
        "created_at": "2024-01-15 10:30:00"
      }
    ],
    "total": 50
  }
}
```

---

### 2. Get Single Listing
**Endpoint:** `GET /listings/show.php?id={listing_id}`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "listing": {
      "listing_id": 1,
      "title": "Item Title",
      "description": "Full description",
      "price": 100.00,
      "category": "Items",
      "images": ["image1.jpg", "image2.jpg"],
      "seller": {
        "account_id": 5,
        "username": "seller123",
        "first_name": "Jane",
        "last_name": "Smith"
      },
      "created_at": "2024-01-15 10:30:00",
      "is_favorited": false
    }
  }
}
```

---

### 3. Create Listing (Authenticated)
**Endpoint:** `POST /listings/create.php`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "title": "Item Title",
  "description": "Item description",
  "price": 100.00,
  "category": "Items",
  "images": ["base64_image_data"]
}
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Listing created successfully",
  "data": {
    "listing_id": 123
  }
}
```

---

## Favorites Endpoints

### 1. Get User Favorites (Authenticated)
**Endpoint:** `GET /favorites/index.php`

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "favorites": [
      {
        "listing_id": 1,
        "title": "Item Title",
        "price": 100.00,
        "image": "image1.jpg"
      }
    ]
  }
}
```

---

### 2. Toggle Favorite (Authenticated)
**Endpoint:** `POST /favorites/toggle.php`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "listing_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Favorite added",
  "data": {
    "is_favorited": true
  }
}
```

---

## Bids Endpoints

### 1. Place Bid (Authenticated)
**Endpoint:** `POST /bids/place.php`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "listing_id": 123,
  "bid_amount": 150.00
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Bid placed successfully",
  "data": {
    "bid_id": 456,
    "bid_amount": 150.00
  }
}
```

---

## Error Response Format

All error responses follow this format:

```json
{
  "success": false,
  "message": "Error description"
}
```

**Common HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request (validation error)
- `401`: Unauthorized (not logged in)
- `403`: Forbidden (banned account)
- `404`: Not Found
- `409`: Conflict (duplicate data)
- `500`: Internal Server Error

---

## Android Integration Example

### Kotlin/Retrofit Setup

```kotlin
// ApiService.kt
interface ApiService {
    @POST("auth/login.php")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<LoginData>>
    
    @POST("auth/register.php")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<LoginData>>
    
    @GET("listings/index.php")
    suspend fun getListings(): Response<ApiResponse<ListingsData>>
    
    @Headers("Authorization: Bearer {token}")
    @POST("favorites/toggle.php")
    suspend fun toggleFavorite(@Body request: FavoriteRequest): Response<ApiResponse<FavoriteData>>
}

// Data Classes
data class LoginRequest(
    val identifier: String,
    val password: String
)

data class ApiResponse<T>(
    val success: Boolean,
    val message: String?,
    val data: T?
)

data class LoginData(
    val user: User,
    val token: String,
    val expires_at: String
)
```

---

## Testing the API

### Using cURL

**Login:**
```bash
curl -X POST https://mineteh.infinityfreeapp.com/actions/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"testuser","password":"TestPass1!"}'
```

**Get Listings:**
```bash
curl https://mineteh.infinityfreeapp.com/actions/v1/listings/index.php
```

**Authenticated Request:**
```bash
curl -X GET https://mineteh.infinityfreeapp.com/actions/v1/favorites/index.php \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Notes

1. **Token Expiration**: Tokens expire after 30 days
2. **CORS**: API allows cross-origin requests from your Android app
3. **Rate Limiting**: Not currently implemented
4. **File Uploads**: Use base64 encoding for images in JSON requests
5. **Pagination**: Use `limit` and `offset` parameters for large datasets


---

## Troubleshooting

### "Expected BEGIN_OBJECT but was STRING" Error in Android

This error occurs when the API returns plain text or HTML instead of JSON. Common causes:

1. **PHP Errors/Warnings**: The API is outputting PHP errors before the JSON response
2. **HTML Output**: Some PHP file is outputting HTML
3. **Whitespace**: Extra whitespace before `<?php` tags
4. **BOM Characters**: Byte Order Mark at the start of PHP files

**Solution:**
- Use the diagnostic tool: `test_api_response.php` to see exactly what the API is returning
- Check that all API files have error display disabled
- Ensure no output before JSON response
- Verify Content-Type header is `application/json`

### Testing Tools

**1. Web-Based Diagnostic Tool:**
```
https://mineteh.infinityfreeapp.com/test_api_response.php
```
This tool shows:
- Raw response body
- Response headers
- Hex dump of response
- JSON validation
- Android integration check

**2. cURL Command:**
```bash
curl -v -X POST https://mineteh.infinityfreeapp.com/actions/v1/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"identifier":"testuser","password":"TestPass1!"}'
```

**3. Postman/Insomnia:**
Import the endpoints and test with your credentials.

### Common Issues

**Issue: "Account not found"**
- Verify the username/email exists in the database
- Check that you're using the correct identifier

**Issue: "Incorrect password"**
- Passwords are case-sensitive
- Verify the password meets validation requirements

**Issue: "Account banned"**
- Check the `user_status` column in the accounts table
- Contact admin to unban the account

**Issue: Empty response**
- Check PHP error logs on the server
- Verify database connection is working
- Use the diagnostic tool to see raw response

### API Configuration

The API configuration is in `actions/v1/config.php`:

```php
// CORS headers
header('Access-Control-Allow-Origin: *'); // Change * to your domain in production
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');
```

**Security Note:** Change `Access-Control-Allow-Origin: *` to your specific domain in production.



---

## Troubleshooting

### JSON Parsing Errors in Android

If you're getting `JsonSyntaxException: Expected BEGIN_OBJECT but was STRING`, the API is returning HTML or plain text instead of JSON. This has been fixed with the following changes:

**What was fixed:**
1. ✅ Output buffering starts immediately at the top of each endpoint
2. ✅ All PHP errors/warnings are suppressed (`error_reporting(0)`)
3. ✅ Removed closing `?>` tags to prevent trailing whitespace
4. ✅ CORS headers set correctly for mobile access
5. ✅ Content-Type header always set to `application/json`

**Testing your API:**
Visit these test pages to verify JSON responses:
- `https://mineteh.infinityfreeapp.com/test_api_login.php` - Test login endpoint
- `https://mineteh.infinityfreeapp.com/test_api_comprehensive.php` - Test all endpoints

**Common causes of non-JSON responses:**
- ❌ PHP warnings/errors being output before JSON
- ❌ Whitespace before `<?php` tags (BOM characters)
- ❌ Closing `?>` tags with trailing whitespace
- ❌ Missing output buffering
- ❌ Database connection errors being echoed

**Retrofit Configuration:**
Make sure your Retrofit instance is configured correctly:

```kotlin
val retrofit = Retrofit.Builder()
    .baseUrl("https://mineteh.infinityfreeapp.com/actions/v1/")
    .addConverterFactory(GsonConverterFactory.create())
    .client(
        OkHttpClient.Builder()
            .addInterceptor { chain ->
                val request = chain.request().newBuilder()
                    .addHeader("Accept", "application/json")
                    .addHeader("Content-Type", "application/json")
                    .build()
                chain.proceed(request)
            }
            .build()
    )
    .build()
```

**Debugging API responses:**
Add logging to see the raw response:

```kotlin
val loggingInterceptor = HttpLoggingInterceptor().apply {
    level = HttpLoggingInterceptor.Level.BODY
}

val client = OkHttpClient.Builder()
    .addInterceptor(loggingInterceptor)
    .build()
```

This will show you exactly what the API is returning in your Logcat.
