package com.example.mineteh

import android.content.Intent
import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.EditText
import android.widget.ProgressBar
import android.widget.Toast
import androidx.appcompat.app.AppCompatActivity
import com.example.mineteh.network.ApiClient
import com.example.mineteh.utils.TokenManager
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.json.JSONObject

class LoginActivity : AppCompatActivity() {
    
    private lateinit var emailInput: EditText
    private lateinit var passwordInput: EditText
    private lateinit var loginButton: Button
    private lateinit var progressBar: ProgressBar
    private lateinit var tokenManager: TokenManager
    
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_login)
        
        // Hide action bar
        supportActionBar?.hide()
        
        // Initialize views
        emailInput = findViewById(R.id.emailInput)
        passwordInput = findViewById(R.id.passwordInput)
        loginButton = findViewById(R.id.loginButton)
        progressBar = findViewById(R.id.progressBar)
        
        tokenManager = TokenManager(this)
        
        loginButton.setOnClickListener {
            val email = emailInput.text.toString().trim()
            val password = passwordInput.text.toString().trim()
            
            if (validateInput(email, password)) {
                performLogin(email, password)
            }
        }
    }
    
    private fun validateInput(email: String, password: String): Boolean {
        if (email.isEmpty()) {
            emailInput.error = "Email is required"
            emailInput.requestFocus()
            return false
        }
        
        if (!android.util.Patterns.EMAIL_ADDRESS.matcher(email).matches()) {
            emailInput.error = "Please enter a valid email"
            emailInput.requestFocus()
            return false
        }
        
        if (password.isEmpty()) {
            passwordInput.error = "Password is required"
            passwordInput.requestFocus()
            return false
        }
        
        if (password.length < 6) {
            passwordInput.error = "Password must be at least 6 characters"
            passwordInput.requestFocus()
            return false
        }
        
        return true
    }
    
    private fun performLogin(email: String, password: String) {
        // Show loading
        progressBar.visibility = View.VISIBLE
        loginButton.isEnabled = false
        
        CoroutineScope(Dispatchers.IO).launch {
            try {
                val response = ApiClient.login(email, password)
                
                withContext(Dispatchers.Main) {
                    handleLoginResponse(response)
                }
            } catch (e: Exception) {
                withContext(Dispatchers.Main) {
                    progressBar.visibility = View.GONE
                    loginButton.isEnabled = true
                    Toast.makeText(
                        this@LoginActivity,
                        "Login failed: ${e.message}",
                        Toast.LENGTH_LONG
                    ).show()
                }
            }
        }
    }
    
    private fun handleLoginResponse(response: String) {
        progressBar.visibility = View.GONE
        loginButton.isEnabled = true
        
        try {
            val jsonResponse = JSONObject(response)
            val success = jsonResponse.optBoolean("success", false)
            
            if (success) {
                val data = jsonResponse.optJSONObject("data")
                val token = data?.optString("token") ?: ""
                val userId = data?.optInt("user_id") ?: 0
                
                if (token.isNotEmpty() && userId > 0) {
                    // Save token and user info
                    tokenManager.saveToken(token)
                    tokenManager.saveUserId(userId)
                    
                    // Navigate to main activity
                    val intent = Intent(this, MainActivity::class.java)
                    intent.flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
                    startActivity(intent)
                    finish()
                } else {
                    Toast.makeText(this, "Invalid response from server", Toast.LENGTH_SHORT).show()
                }
            } else {
                val message = jsonResponse.optString("message", "Login failed")
                Toast.makeText(this, message, Toast.LENGTH_SHORT).show()
            }
        } catch (e: Exception) {
            Toast.makeText(this, "Error parsing response: ${e.message}", Toast.LENGTH_SHORT).show()
        }
    }
}
