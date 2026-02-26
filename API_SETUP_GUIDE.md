# MineTeh REST API Setup Guide

## 🎯 What We Built

A complete REST API that allows your Android app and website to share the same backend. Both platforms can now:
- Login/Register users
- Browse listings
- Place bids
- Manage favorites
- And more!

## 📁 File Structure

```
MineTeh/
├── api/
│   └── v1/
│       ├── config.php              # Core API configuration
│       ├── README.md               # API documentation
│       ├── auth/
│       │   ├── login.php          # POST - User login
│       │   ├── register.php       # POST - User registration
│       │   └── logout.php         # POST - User logout
│       ├── listings/
│       │   ├── index.php          # GET - All listings
│       │   ├── show.php           # GET - Single listing
│       │   └── create.php         # POST - Create listing
│       ├── bids/
│       │   └── place.php          # POST - Place bid
│       └── favorites/
│           ├── toggle.php         # POST - Add/remove favorite
│           └── index.php          # GET - User's favorites
├── add_user_sessions_table.sql    # Database migration
└── test_api.html                  # API testing tool
```

## 🚀 Setup Steps

### Step 1: Create Sessions Table

1. Go to your Supabase dashboard
2. Navigate to SQL Editor
3. Copy and paste the contents of `add_user_sessions_table.sql`
4. Click "Run"

This creates the `user_sessions` table for storing API tokens.

### Step 2: Test the API

1. Open `test_api.html` in your browser:
   ```
   http://localhost/MineTeh/test_api.html
   ```

2. Test each endpoint:
   - **Login** with your existing account (e.g., dummy1/password123)
   - **Get Listings** to see all active listings
   - **Get Single Listing** to see details
   - **Place Bid** (requires login)
   - **Toggle Favorite** (requires login)

3. Check the responses - they should all return JSON

### Step 3: Verify CORS Headers

The API includes CORS headers to allow cross-origin requests from your Android app. If you need to restrict access, edit `api/v1/config.php`:

```php
// Change this line to restrict to specific domains
header('Access-Control-Allow-Origin: *');  // Allow all
// To:
header('Access-Control-Allow-Origin: https://your-app-domain.com');
```

## 📱 Android Integration

### Add Dependencies

In your `build.gradle` (app level):

```gradle
dependencies {
    // Retrofit for API calls
    implementation 'com.squareup.retrofit2:retrofit:2.9.0'
    implementation 'com.squareup.retrofit2:converter-gson:2.9.0'
    
    // OkHttp for logging
    implementation 'com.squareup.okhttp3:logging-interceptor:4.11.0'
    
    // Coroutines
    implementation 'org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.1'
    
    // ViewModel
    implementation 'androidx.lifecycle:lifecycle-viewmodel-ktx:2.6.2'
}
```

### Create Data Models

```kotlin
// models/ApiResponse.kt
data class ApiResponse<T>(
    val success: Boolean,
    val message: String?,
    val data: T?
)

// models/User.kt
data class User(
    val account_id: Int,
    val username: String,
    val email: String,
    val first_name: String,
    val last_name: String
)

// models/LoginData.kt
data class LoginData(
    val user: User,
    val token: String,
    val expires_at: String
)

// models/Listing.kt
data class Listing(
    val id: Int,
    val title: String,
    val description: String,
    val price: Double,
    val location: String,
    val category: String,
    val listing_type: String,
    val status: String,
    val image: String?,
    val seller: Seller?,
    val created_at: String
)

data class Seller(
    val username: String,
    val first_name: String,
    val last_name: String
)
```

### Create API Service

```kotlin
// network/ApiService.kt
interface ApiService {
    @POST("auth/login.php")
    suspend fun login(@Body request: LoginRequest): Response<ApiResponse<LoginData>>
    
    @POST("auth/register.php")
    suspend fun register(@Body request: RegisterRequest): Response<ApiResponse<LoginData>>
    
    @GET("listings/index.php")
    suspend fun getListings(
        @Query("category") category: String? = null,
        @Query("type") type: String? = null,
        @Query("search") search: String? = null,
        @Query("limit") limit: Int = 50,
        @Query("offset") offset: Int = 0
    ): Response<ApiResponse<List<Listing>>>
    
    @GET("listings/show.php")
    suspend fun getListing(@Query("id") id: Int): Response<ApiResponse<Listing>>
    
    @POST("bids/place.php")
    suspend fun placeBid(@Body request: BidRequest): Response<ApiResponse<BidData>>
    
    @POST("favorites/toggle.php")
    suspend fun toggleFavorite(@Body request: FavoriteRequest): Response<ApiResponse<FavoriteData>>
    
    @GET("favorites/index.php")
    suspend fun getFavorites(): Response<ApiResponse<List<Listing>>>
}

// Request models
data class LoginRequest(val identifier: String, val password: String)
data class RegisterRequest(
    val username: String,
    val email: String,
    val password: String,
    val first_name: String,
    val last_name: String
)
data class BidRequest(val listing_id: Int, val bid_amount: Double)
data class FavoriteRequest(val listing_id: Int)
```

### Create API Client

```kotlin
// network/ApiClient.kt
object ApiClient {
    private const val BASE_URL = "http://your-domain.com/api/v1/"
    
    private val loggingInterceptor = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }
    
    private val authInterceptor = Interceptor { chain ->
        val token = TokenManager.getToken() // Get saved token
        val request = if (token != null) {
            chain.request().newBuilder()
                .addHeader("Authorization", "Bearer $token")
                .build()
        } else {
            chain.request()
        }
        chain.proceed(request)
    }
    
    private val okHttpClient = OkHttpClient.Builder()
        .addInterceptor(loggingInterceptor)
        .addInterceptor(authInterceptor)
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .build()
    
    val retrofit: Retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .client(okHttpClient)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
    
    val apiService: ApiService = retrofit.create(ApiService::class.java)
}
```

### Create Token Manager

```kotlin
// utils/TokenManager.kt
object TokenManager {
    private const val PREFS_NAME = "mineteh_prefs"
    private const val KEY_TOKEN = "auth_token"
    
    fun saveToken(context: Context, token: String) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .edit()
            .putString(KEY_TOKEN, token)
            .apply()
    }
    
    fun getToken(context: Context): String? {
        return context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .getString(KEY_TOKEN, null)
    }
    
    fun clearToken(context: Context) {
        context.getSharedPreferences(PREFS_NAME, Context.MODE_PRIVATE)
            .edit()
            .remove(KEY_TOKEN)
            .apply()
    }
}
```

### Usage Example in ViewModel

```kotlin
// viewmodels/ListingsViewModel.kt
class ListingsViewModel : ViewModel() {
    private val api = ApiClient.apiService
    
    private val _listings = MutableLiveData<List<Listing>>()
    val listings: LiveData<List<Listing>> = _listings
    
    private val _loading = MutableLiveData<Boolean>()
    val loading: LiveData<Boolean> = _loading
    
    private val _error = MutableLiveData<String?>()
    val error: LiveData<String?> = _error
    
    fun loadListings(category: String? = null) {
        viewModelScope.launch {
            _loading.value = true
            try {
                val response = api.getListings(category = category)
                if (response.isSuccessful && response.body()?.success == true) {
                    _listings.value = response.body()?.data ?: emptyList()
                    _error.value = null
                } else {
                    _error.value = response.body()?.message ?: "Failed to load listings"
                }
            } catch (e: Exception) {
                _error.value = e.message ?: "Network error"
            } finally {
                _loading.value = false
            }
        }
    }
}

// In your Activity/Fragment
class ListingsActivity : AppCompatActivity() {
    private val viewModel: ListingsViewModel by viewModels()
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        viewModel.listings.observe(this) { listings ->
            // Update RecyclerView
        }
        
        viewModel.loading.observe(this) { isLoading ->
            // Show/hide progress bar
        }
        
        viewModel.error.observe(this) { error ->
            error?.let {
                Toast.makeText(this, it, Toast.LENGTH_SHORT).show()
            }
        }
        
        // Load listings
        viewModel.loadListings()
    }
}
```

## 🔒 Security Notes

### For Development
- RLS is disabled on all tables
- CORS allows all origins (`*`)
- Tokens expire in 30 days

### For Production
1. **Enable RLS** on all Supabase tables
2. **Restrict CORS** to your app's domain
3. **Use HTTPS** for all API calls
4. **Shorten token expiry** to 7 days
5. **Add rate limiting** to prevent abuse
6. **Validate all inputs** server-side
7. **Use environment variables** for sensitive data

## 🧪 Testing Checklist

- [ ] Login with existing account works
- [ ] Registration creates new account
- [ ] Get listings returns data
- [ ] Get single listing shows details
- [ ] Place bid works (for auction items)
- [ ] Toggle favorite adds/removes
- [ ] Get favorites returns user's favorites
- [ ] Logout invalidates token
- [ ] Unauthorized requests return 401

## 📞 Support

If you encounter issues:
1. Check browser console for errors
2. Verify Supabase connection in `database/supabase.php`
3. Ensure `user_sessions` table exists
4. Test with `test_api.html` first
5. Check API responses match expected format

## 🎉 You're Ready!

Your REST API is now ready for Android integration. Both your website and mobile app can use the same backend, ensuring consistent behavior across platforms.
