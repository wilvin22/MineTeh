package com.example.mineteh.network

import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.Request
import okhttp3.RequestBody.Companion.toRequestBody
import org.json.JSONObject
import java.util.concurrent.TimeUnit

object ApiClient {
    
    private const val BASE_URL = "https://mineteh.infinityfreeapp.com/"
    private const val LOGIN_ENDPOINT = "api/v1/auth/login.php"
    
    private val client = OkHttpClient.Builder()
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()
    
    fun login(email: String, password: String): String {
        val json = JSONObject().apply {
            put("email", email)
            put("password", password)
        }
        
        val mediaType = "application/json; charset=utf-8".toMediaType()
        val body = json.toString().toRequestBody(mediaType)
        
        val request = Request.Builder()
            .url(BASE_URL + LOGIN_ENDPOINT)
            .post(body)
            .addHeader("Content-Type", "application/json")
            .build()
        
        val response = client.newCall(request).execute()
        
        if (!response.isSuccessful) {
            throw Exception("HTTP ${response.code}: ${response.message}")
        }
        
        return response.body?.string() ?: throw Exception("Empty response body")
    }
}
