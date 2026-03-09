package com.example.mineteh

import android.content.Intent
import android.os.Bundle
import android.os.Handler
import android.os.Looper
import androidx.appcompat.app.AppCompatActivity
import com.example.mineteh.utils.TokenManager

class SplashActivity : AppCompatActivity() {
    
    private val splashTimeOut: Long = 2000 // 2 seconds
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_splash)
        
        // Hide action bar
        supportActionBar?.hide()
        
        Handler(Looper.getMainLooper()).postDelayed({
            checkLoginStatus()
        }, splashTimeOut)
    }
    
    private fun checkLoginStatus() {
        val tokenManager = TokenManager(this)
        
        if (tokenManager.isLoggedIn()) {
            // User is logged in, go to main activity
            startActivity(Intent(this, MainActivity::class.java))
        } else {
            // User is not logged in, go to login activity
            startActivity(Intent(this, LoginActivity::class.java))
        }
        
        finish()
    }
}
