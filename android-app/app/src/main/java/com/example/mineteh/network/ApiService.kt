package com.example.mineteh.network

import retrofit2.Response
import retrofit2.http.*

/**
 * API Service interface for Retrofit
 * Currently using OkHttp directly in ApiClient
 * This interface is here for future expansion
 */
interface ApiService {
    
    @POST("api/v1/auth/login.php")
    suspend fun login(
        @Body credentials: Map<String, String>
    ): Response<LoginResponse>
    
    @POST("api/v1/auth/register.php")
    suspend fun register(
        @Body userData: Map<String, String>
    ): Response<RegisterResponse>
    
    @GET("api/v1/listings/index.php")
    suspend fun getListings(
        @Header("Authorization") token: String
    ): Response<ListingsResponse>
}

// Response data classes
data class LoginResponse(
    val success: Boolean,
    val message: String,
    val data: LoginData?
)

data class LoginData(
    val token: String,
    val user_id: Int,
    val email: String
)

data class RegisterResponse(
    val success: Boolean,
    val message: String
)

data class ListingsResponse(
    val success: Boolean,
    val data: List<Listing>?
)

data class Listing(
    val id: Int,
    val title: String,
    val price: Double,
    val image: String?,
    val location: String?
)
