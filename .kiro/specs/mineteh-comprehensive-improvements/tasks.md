# Implementation Plan: MineTeh Marketplace Comprehensive Improvements

## Overview

This implementation plan transforms the MineTeh marketplace platform from 58% complete to production-ready by addressing 42 requirements across security, core features, database integrity, API completion, performance optimization, and user experience. The plan is organized into 8 phases over 10 weeks, with each task building incrementally toward a secure, scalable, and fully functional e-commerce system.

**Technology Stack:** PHP 7.4+, Supabase (PostgreSQL), HTML5/CSS3/JavaScript, Bootstrap 5

**Implementation Approach:**
- Incremental development with frequent checkpoints
- Property-based testing for 112 correctness properties
- Security-first implementation starting with critical vulnerabilities
- Database migrations with rollback capability
- Continuous integration and testing throughout

## Tasks

### Phase 1: Security Foundation (Weeks 1-2) - CRITICAL PRIORITY

- [ ] 1. Implement password hashing with bcrypt
  - [ ] 1.1 Create password hashing functions in includes/auth.php
    - Implement hashPassword() using PASSWORD_BCRYPT with cost factor 12
    - Implement verifyPassword() for authentication
    - Implement needsRehash() to check if passwords need upgrading
    - _Requirements: 1.1_
  
  - [ ]* 1.2 Write property test for password hashing
    - **Property 1: Password hash uniqueness** - Same password hashed twice produces different hashes
    - **Validates: Requirements 1.1**
  
  - [ ] 1.3 Create migration script for existing passwords
    - Write migration to add password_migration_status column
    - Implement rehash-on-login logic in login action
    - Test migration with sample user accounts
    - _Requirements: 1.1_

- [ ] 2. Implement secure session management
  - [ ] 2.1 Create user_sessions table
    - Write migration 001_create_user_sessions.sql
    - Add columns: session_id, user_id, session_token, ip_address, user_agent, expires_at, created_at
    - Add foreign key to users table with CASCADE delete
    - Add indexes on session_token and user_id
    - _Requirements: 1.2, 1.3_

  - [ ] 2.2 Implement session token generation and validation
    - Create generateSessionToken() using random_bytes(32)
    - Create createSession() to store session in database
    - Create validateSession() to check token validity and expiration
    - Implement session cleanup for expired sessions
    - _Requirements: 1.2, 1.3, 1.4_
  
  - [ ]* 2.3 Write property tests for session management
    - **Property 1: Session token uniqueness** - No two sessions have same token
    - **Property 2: Session data completeness** - All required fields present
    - **Property 3: Expired session cleanup** - Expired sessions return null and are deleted
    - **Validates: Requirements 1.2, 1.3, 1.4**
  
  - [ ] 2.4 Implement session fixation protection
    - Add session ID regeneration after successful login
    - Update all login endpoints to regenerate session
    - Test session ID changes on authentication
    - _Requirements: 1.5_
  
  - [ ] 2.5 Implement concurrent session limits
    - Add logic to limit 5 sessions per user
    - Delete oldest session when limit exceeded
    - Test with multiple concurrent logins
    - _Requirements: 1.6_
  
  - [ ]* 2.6 Write property tests for session limits
    - **Property 5: Concurrent session limit** - Never exceed 5 sessions per user
    - **Property 6: Logout session deletion** - Logout removes session record
    - **Validates: Requirements 1.6, 1.7**
  
  - [ ] 2.7 Update all authentication endpoints
    - Modify login.php to use new session system
    - Modify logout.php to delete session records
    - Update check_login.php to validate sessions
    - Add session validation middleware to protected pages
    - _Requirements: 1.7, 1.8, 1.9_

- [ ] 3. Implement CSRF protection
  - [ ] 3.1 Create CSRF protection class in includes/csrf.php
    - Implement generateToken() using random_bytes(32)
    - Implement validateToken() with hash_equals()
    - Implement regenerateToken() for sensitive operations
    - Implement getTokenField() for form injection
    - _Requirements: 2.1, 2.2_
  
  - [ ]* 3.2 Write property tests for CSRF protection
    - **Property 9: CSRF token uniqueness per session**
    - **Property 10: CSRF token validation** - Invalid tokens rejected with 403
    - **Property 11: CSRF token regeneration** - Token changes after sensitive ops
    - **Validates: Requirements 2.1, 2.3, 2.4, 2.5**
  
  - [ ] 3.3 Add CSRF tokens to all forms
    - Update all POST forms to include CSRF token field
    - Add CSRF validation to all action files
    - Update AJAX requests to include X-CSRF-TOKEN header
    - Test form submissions with valid and invalid tokens
    - _Requirements: 2.2, 2.3_
  
  - [ ] 3.4 Implement CSRF validation middleware
    - Create validateRequest() to check all state-changing requests
    - Add Referer header validation
    - Set SameSite=Strict on session cookies
    - _Requirements: 2.4, 2.6, 2.7_

- [ ] 4. Implement rate limiting
  - [ ] 4.1 Create rate_limits table
    - Write migration 007_create_rate_limits.sql
    - Add columns: limit_id, identifier, endpoint, attempt_count, window_start, blocked_until
    - Add unique constraint on (identifier, endpoint, window_start)
    - Add indexes on identifier and window_start
    - _Requirements: 5.8_
  
  - [ ] 4.2 Create RateLimiter class in includes/rate-limit.php
    - Implement checkLimit() to validate request against limits
    - Implement recordAttempt() to track requests
    - Implement cleanup() to remove old records
    - Implement logViolation() for audit logging
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6_
  
  - [ ]* 4.3 Write property tests for rate limiting
    - **Property 21: Login rate limit enforcement** - 6th attempt blocked with 429
    - **Property 22: Registration rate limit** - 4th attempt blocked
    - **Property 24: API rate limit** - 101st request blocked with Retry-After
    - **Validates: Requirements 5.1, 5.2, 5.3, 5.5, 5.7**
  
  - [ ] 4.4 Apply rate limiting to authentication endpoints
    - Add rate limiting to login.php (5 attempts per 15 min)
    - Add rate limiting to actions/v1/auth/register.php (3 per hour)
    - Add rate limiting to password reset (3 per hour)
    - Return HTTP 429 with Retry-After header when exceeded
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.7_
  
  - [ ] 4.5 Apply rate limiting to API endpoints
    - Add rate limiting middleware to all /api/v1/ endpoints
    - Set limit to 100 requests per minute per user
    - Set search endpoint limit to 20 per minute
    - Add X-RateLimit headers to all responses
    - _Requirements: 5.5, 5.6_
  
  - [ ] 4.6 Create rate limit cleanup cron job
    - Create cron/cleanup-rate-limits.php
    - Delete records older than 24 hours
    - Schedule to run hourly
    - _Requirements: 5.9_

- [ ] 5. Enhance input validation and sanitization
  - [ ] 5.1 Create InputValidator class in includes/validation.php
    - Implement validateEmail() with filter_var
    - Implement validatePhone() for Philippine numbers
    - Implement validatePrice() for decimal validation
    - Implement validateListingTitle() with length checks
    - Implement validateListingDescription() with length checks
    - Implement validatePassword() with complexity requirements
    - Implement sanitizeFilename() to prevent path traversal
    - Implement sanitizeHTML() with allowlist
    - _Requirements: 3.5, 4.1, 4.4, 4.7, 32.2, 32.3, 32.4, 32.5, 32.6_
  
  - [ ]* 5.2 Write property tests for input validation
    - **Property 14: Dangerous input sanitization** - SQL patterns removed
    - **Property 17: HTML entity encoding** - Special chars encoded
    - **Property 20: Path traversal prevention** - ../ patterns removed
    - **Validates: Requirements 3.5, 4.1, 4.7**
  
  - [ ] 5.3 Update all action files with validation
    - Add validation to listing creation/update actions
    - Add validation to user registration/profile actions
    - Add validation to bid placement actions
    - Add validation to message sending actions
    - Return clear error messages for validation failures
    - _Requirements: 32.1, 32.2, 32.3, 32.4, 32.5, 32.6_
  
  - [ ] 5.4 Implement XSS prevention in output
    - Update all display pages to use htmlspecialchars()
    - Use ENT_QUOTES flag for encoding
    - Preserve original content in database (no encoding on storage)
    - Apply encoding only on display
    - _Requirements: 4.1, 4.2, 4.5, 4.6_
  
  - [ ] 5.5 Set security headers
    - Add Content-Security-Policy header
    - Add X-Content-Type-Options: nosniff
    - Add X-Frame-Options: DENY
    - Add Strict-Transport-Security header
    - _Requirements: 4.3, 4.8, 4.9_

- [ ] 6. Implement secure file upload handling
  - [ ] 6.1 Create ImageOptimizer class in includes/image.php
    - Implement validateImage() with finfo_file() MIME check
    - Implement validateMimeType() restricting to JPEG, PNG, GIF, WebP
    - Implement scanForMalware() checking for PHP code patterns
    - Implement optimize() for image compression
    - Implement generateThumbnail() at 150x150 pixels
    - Implement convertToWebP() for format conversion
    - _Requirements: 6.1, 6.2, 6.8, 6.11, 6.12_
  
  - [ ]* 6.2 Write property tests for file uploads
    - **Property 28: MIME type validation** - Non-image types rejected
    - **Property 29: File size limit** - Files over 5MB rejected
    - **Property 31: Filename uniqueness** - No duplicate filenames
    - **Property 32: Executable code detection** - PHP code rejected
    - **Property 33: Double extension rejection** - .php.jpg rejected
    - **Validates: Requirements 6.1, 6.2, 6.3, 6.5, 6.8, 6.9**
  
  - [ ] 6.2 Update file upload handling in listing actions
    - Add MIME type validation before upload
    - Add file size check (5MB limit)
    - Add image count limit (5 per listing)
    - Generate random filenames using uniqid() and hash
    - Store files outside web root in uploads/ directory
    - Reject double extensions and executable patterns
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.8, 6.9_
  
  - [ ] 6.3 Implement image optimization pipeline
    - Compress uploaded images to reduce size by 30%+
    - Generate thumbnails for all listing images
    - Convert images to WebP format
    - Store original, compressed, and thumbnail versions
    - Update database to track image paths
    - _Requirements: 6.11, 6.12, 20.1, 20.2_

- [ ] 7. Implement audit logging
  - [ ] 7.1 Create audit_logs table
    - Write migration 008_create_audit_logs.sql
    - Add columns: log_id, user_id, event_type, event_description, ip_address, user_agent, metadata (JSONB), created_at
    - Add foreign key to users with SET NULL
    - Add indexes on user_id, event_type, and created_at
    - _Requirements: 37.1, 37.2, 37.3, 37.4, 37.5, 37.6, 37.7, 37.8_
  
  - [ ] 7.2 Create audit logging functions in includes/audit.php
    - Implement logSecurityEvent() for security events
    - Implement logAuthEvent() for login/logout
    - Implement logAdminAction() for admin operations
    - Implement logPaymentEvent() for transactions
    - Implement logFileUpload() for uploads
    - _Requirements: 1.10, 5.10, 8.10, 9.8, 37.1-37.8_
  
  - [ ] 7.3 Add audit logging to all critical operations
    - Log all authentication events (login, logout, failed attempts)
    - Log password changes and resets
    - Log admin actions (user suspension, content removal)
    - Log payment transactions
    - Log rate limit violations
    - Log file uploads
    - _Requirements: 1.10, 5.10, 8.10, 9.8, 37.1-37.8_

- [ ] 8. Checkpoint - Security foundation complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify all security headers are set correctly
  - Test authentication flow end-to-end
  - Test CSRF protection on all forms
  - Test rate limiting on all endpoints
  - Review audit logs for completeness

### Phase 2: Database Integrity (Week 3) - HIGH PRIORITY

- [ ] 9. Create new database tables
  - [ ] 9.1 Create password_resets table
    - Write migration 002_create_password_resets.sql
    - Add columns: reset_id, email, token, expires_at, used, created_at
    - Add unique constraint on token
    - Add indexes on token and email
    - _Requirements: 8.1, 8.2, 8.3_
  
  - [ ] 9.2 Create transactions table
    - Write migration 004_create_transactions.sql
    - Add columns: transaction_id, order_id, transaction_ref, payment_gateway, amount, currency, status, gateway_response, created_at, completed_at
    - Add foreign key to orders with CASCADE
    - Add unique constraint on transaction_ref
    - Add indexes on order_id, transaction_ref, and status
    - _Requirements: 9.5_
  
  - [ ] 9.3 Create reviews table
    - Write migration 005_create_reviews.sql
    - Add columns: review_id, order_id, buyer_id, seller_id, rating, comment, seller_response, is_flagged, flag_reason, created_at, updated_at, responded_at
    - Add foreign keys to orders and users
    - Add unique constraint on order_id (one review per order)
    - Add check constraint: rating BETWEEN 1 AND 5
    - Add indexes on seller_id and rating
    - _Requirements: 10.1, 10.2, 10.3, 10.5_
  
  - [ ] 9.4 Create disputes and dispute_evidence tables
    - Write migration 006_create_disputes.sql
    - Create disputes table with columns: dispute_id, order_id, opened_by, reason, status, resolution, resolved_by, created_at, resolved_at
    - Create dispute_evidence table with columns: evidence_id, dispute_id, user_id, evidence_type, content, created_at
    - Add foreign keys with appropriate CASCADE/RESTRICT
    - Add indexes on order_id and status
    - _Requirements: 12.1, 12.2, 12.3, 12.4_
  
  - [ ] 9.5 Create user_addresses table
    - Write migration 009_create_user_addresses.sql
    - Add columns: address_id, user_id, label, full_address, city, postal_code, latitude, longitude, is_default, created_at
    - Add foreign key to users with CASCADE
    - Add index on user_id and is_default
    - _Requirements: 29.1, 29.2_

- [ ] 10. Enhance existing tables with constraints
  - [ ] 10.1 Add email verification columns to users table
    - Write migration 003_add_email_verification.sql
    - Add columns: email_verified (BOOLEAN DEFAULT FALSE), verification_token, verification_expires
    - Add index on verification_token
    - _Requirements: 7.1, 7.2, 7.3, 7.4_
  
  - [ ] 10.2 Enhance listings table
    - Write migration 003_enhance_listings_table.sql
    - Add check constraints: price >= 0, starting_price >= 0, reserve_price >= 0
    - Add check constraint: auction_end_time > created_at
    - Add check constraint: title length BETWEEN 5 AND 100
    - Add check constraint: description length BETWEEN 20 AND 5000
    - Add check constraint: listing_type IN ('fixed', 'auction')
    - Add check constraint: status IN ('active', 'sold', 'closed', 'flagged', 'removed')
    - Add check constraint: condition IN ('new', 'like_new', 'good', 'fair', 'poor')
    - _Requirements: 15.1, 15.12, 32.5, 32.6_
  
  - [ ] 10.3 Enhance users table with constraints
    - Add check constraint: email format validation using regex
    - Add check constraint: status IN ('active', 'restricted', 'banned')
    - Update password_hash column to NOT NULL
    - _Requirements: 15.4, 15.8_
  
  - [ ] 10.4 Enhance bids table with constraints
    - Add check constraint: amount > 0
    - Add check constraint: no self-bidding (user_id != listing owner)
    - _Requirements: 15.2, 33.5_
  
  - [ ] 10.5 Enhance orders table with constraints
    - Add check constraint: total_amount > 0
    - Add check constraint: shipping_cost >= 0
    - Add check constraint: status IN ('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded')
    - Add check constraint: payment_status IN ('pending', 'paid', 'failed', 'refunded')
    - _Requirements: 15.1_

- [ ] 11. Add foreign key constraints
  - [ ] 11.1 Add foreign keys to listings table
    - Write migration 010_add_foreign_keys.sql
    - Add FK: user_id REFERENCES users(user_id) ON DELETE CASCADE
    - Add FK: category_id REFERENCES categories(category_id) ON DELETE SET NULL
    - _Requirements: 14.1, 14.10_
  
  - [ ] 11.2 Add foreign keys to bids table
    - Add FK: listing_id REFERENCES listings(listing_id) ON DELETE CASCADE
    - Add FK: user_id REFERENCES users(user_id) ON DELETE CASCADE
    - _Requirements: 14.2, 14.3_
  
  - [ ] 11.3 Add foreign keys to orders table
    - Add FK: buyer_id REFERENCES users(user_id) ON DELETE RESTRICT
    - Add FK: seller_id REFERENCES users(user_id) ON DELETE RESTRICT
    - Add FK: listing_id REFERENCES listings(listing_id) ON DELETE RESTRICT
    - _Requirements: 14.4, 14.5_
  
  - [ ] 11.4 Add foreign keys to favorites and cart_items
    - Add FK to favorites: user_id and listing_id with CASCADE
    - Add FK to cart_items: user_id and listing_id with CASCADE
    - _Requirements: 14.10, 14.11_
  
  - [ ]* 11.5 Write property tests for foreign key constraints
    - **Property 73: Foreign key cascade deletion** - User deletion cascades to listings, bids, favorites
    - **Property 74: Foreign key restrict deletion** - User with orders cannot be deleted
    - **Validates: Requirements 14.1-14.11**

- [ ] 12. Add performance indexes
  - [ ] 12.1 Add indexes to listings table
    - Write migration 012_add_indexes.sql
    - Add index on (user_id)
    - Add index on (category_id)
    - Add index on (status, created_at DESC)
    - Add index on (latitude, longitude) for location queries
    - Add index on (price) for price sorting
    - Add index on (auction_end_time) for auctions
    - Add full-text index on (title, description) using GIN
    - _Requirements: 16.1, 16.2, 16.3, 16.4, 16.5_
  
  - [ ] 12.2 Add indexes to other tables
    - Add index on bids(listing_id, amount DESC)
    - Add index on orders(buyer_id, created_at DESC)
    - Add index on orders(seller_id, status)
    - Add index on messages(conversation_id, created_at DESC)
    - Add index on notifications(user_id, is_read, created_at DESC)
    - _Requirements: 16.6, 16.7, 16.8_
  
  - [ ] 12.3 Test query performance improvements
    - Run EXPLAIN ANALYZE on common queries
    - Verify index usage in query plans
    - Measure query time improvements
    - _Requirements: 16.1-16.8_

- [ ] 13. Implement Row Level Security policies
  - [ ] 13.1 Create RLS policies for users table
    - Enable RLS on users table
    - Create policy: users can update their own profile
    - Create policy: admins can view all users
    - _Requirements: 14.12_
  
  - [ ] 13.2 Create RLS policies for listings table
    - Create policy: everyone can view active listings
    - Create policy: owners can update their listings
    - Create policy: admins can update any listing
    - _Requirements: 14.12_
  
  - [ ] 13.3 Create RLS policies for orders table
    - Create policy: buyers and sellers can view their orders
    - Create policy: admins can view all orders
    - _Requirements: 14.12_
  
  - [ ] 13.4 Create RLS policies for messages table
    - Create policy: conversation participants can view messages
    - Create policy: participants can insert messages
    - _Requirements: 14.12_
  
  - [ ] 13.5 Create RLS policies for audit_logs table
    - Create policy: only admins can view audit logs
    - _Requirements: 14.12, 37.9_

- [ ] 14. Create database migration and rollback scripts
  - [ ] 14.1 Organize migration files
    - Create database/migrations/ directory structure
    - Number migrations sequentially (001-012)
    - Create rollback/ subdirectory
    - _Requirements: 38.1_
  
  - [ ] 14.2 Write rollback scripts for all migrations
    - Create rollback SQL for each migration
    - Test rollback on development database
    - Document rollback procedures
    - _Requirements: 38.2_
  
  - [ ] 14.3 Create migration runner script
    - Write PHP script to apply migrations in order
    - Track applied migrations in migrations table
    - Support up and down migrations
    - _Requirements: 38.1, 38.2_

- [ ] 15. Checkpoint - Database integrity complete
  - Ensure all tests pass, ask the user if questions arise.
  - Verify all foreign keys are working correctly
  - Verify all check constraints reject invalid data
  - Test cascade and restrict behaviors
  - Verify all indexes improve query performance
  - Test RLS policies with different user roles

### Phase 3: Core Features (Weeks 4-5) - HIGH PRIORITY

- [ ] 16. Implement email verification system
  - [ ] 16.1 Create email verification functions in includes/auth.php
    - Implement generateVerificationToken() using random_bytes(32)
    - Implement sendVerificationEmail() with token link
    - Implement verifyEmail() to validate token and update user
    - Implement resendVerification() with 5-minute cooldown
    - _Requirements: 7.1, 7.2, 7.3, 7.8_
  
  - [ ]* 16.2 Write property tests for email verification
    - **Property 36: Verification token generation** - Token stored and emailed
    - **Property 37: Verification token expiry** - Tokens expire after 24 hours
    - **Property 38: Email verification success** - Valid token sets email_verified true
    - **Property 42: Verification token cleanup** - Token nulled after verification
    - **Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.10**
  
  - [ ] 16.2 Create verification email template
    - Design HTML email template
    - Include verification link with token
    - Add resend verification link
    - Test email delivery
    - _Requirements: 7.1, 7.7_
  
  - [ ] 16.3 Create verification pages
    - Create verify-email.php page
    - Handle token validation and success/error messages
    - Add verification reminder banner to dashboard
    - _Requirements: 7.5, 7.7_
  
  - [ ] 16.4 Add verification checks to protected actions
    - Restrict listing creation to verified users
    - Restrict bid placement to verified users
    - Show clear error messages for unverified users
    - _Requirements: 7.6_

- [ ] 17. Implement password reset flow
  - [ ] 17.1 Create password reset functions in includes/auth.php
    - Implement requestPasswordReset() to generate token
    - Implement validateResetToken() to check token validity
    - Implement resetPassword() to update password
    - Implement sendResetEmail() with token link
    - _Requirements: 8.1, 8.2, 8.3, 8.4_
  
  - [ ]* 17.2 Write property tests for password reset
    - **Property 43: Reset token generation** - Unique token stored in database
    - **Property 44: Reset token expiry** - Tokens expire after 1 hour
    - **Property 46: Password minimum length** - Passwords under 8 chars rejected
    - **Property 47: Session invalidation** - All sessions deleted on password change
    - **Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.7, 8.8**
  
  - [ ] 17.3 Create password reset pages
    - Create forgot-password.php page
    - Create reset-password.php page with token validation
    - Add password strength indicator
    - Add password confirmation field
    - _Requirements: 8.5, 8.6, 8.7_
  
  - [ ] 17.4 Implement password reset email
    - Design HTML email template
    - Include reset link with token
    - Add expiry notice (1 hour)
    - Test email delivery
    - _Requirements: 8.1_
  
  - [ ] 17.5 Add session invalidation on password change
    - Delete all user sessions on successful password reset
    - Force re-login after password change
    - Log password change event
    - _Requirements: 8.8, 8.9, 8.10_

- [ ] 18. Integrate payment gateway
  - [ ] 18.1 Choose and configure payment provider
    - Select payment provider (GCash/PayPal/Stripe)
    - Create merchant account
    - Obtain API keys and credentials
    - Configure sandbox environment for testing
    - _Requirements: 9.1, 9.2_
  
  - [ ] 18.2 Create payment gateway interface in includes/payment.php
    - Define PaymentGateway interface with createPayment(), verifyWebhook(), processRefund(), getTransactionStatus()
    - Implement GCashGateway class (or chosen provider)
    - Implement PaymentManager class
    - _Requirements: 9.1, 9.2, 9.3_
  
  - [ ] 18.3 Implement payment processing
    - Create processPayment() to initiate payment
    - Store transaction record in transactions table
    - Redirect user to payment gateway
    - Handle payment success/failure callbacks
    - _Requirements: 9.4, 9.5_
  
  - [ ]* 18.4 Write property tests for payment processing
    - **Property 50: Webhook signature validation** - Invalid signatures rejected
    - **Property 51: Order status update** - Paid orders update to "paid" status
    - **Property 52: Transaction record creation** - All fields populated correctly
    - **Property 55: Sensitive data exclusion** - No credit card data stored
    - **Validates: Requirements 9.3, 9.4, 9.5, 9.9**
  
  - [ ] 18.5 Create payment webhook handler
    - Create actions/payment-webhook.php
    - Validate webhook signature
    - Update order status on successful payment
    - Send payment confirmation email
    - Log payment events
    - _Requirements: 9.3, 9.4, 9.8, 9.11_
  
  - [ ] 18.6 Implement refund processing
    - Create processRefund() function
    - Update order status to "refunded"
    - Create refund transaction record
    - Send refund confirmation email
    - _Requirements: 9.7, 9.10_
  
  - [ ] 18.7 Update checkout flow with payment integration
    - Modify home/checkout.php to use payment gateway
    - Add payment method selection
    - Show payment status and confirmation
    - Handle payment errors gracefully
    - _Requirements: 9.1, 9.2, 9.4_

- [ ] 19. Implement review and rating system
  - [ ] 19.1 Create review management functions in includes/review.php
    - Implement createReview() with validation
    - Implement updateReview() with 7-day time limit
    - Implement deleteReview() (admin only)
    - Implement getSellerReviews() with pagination
    - Implement getSellerRating() with average calculation
    - Implement canUserReview() to check eligibility
    - Implement addSellerResponse() for seller replies
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.6, 10.9_
  
  - [ ]* 19.2 Write property tests for review system
    - **Property 57: Review enablement** - Only delivered orders can be reviewed
    - **Property 58: Review submission** - Rating 1-5, comment under 1000 chars
    - **Property 59: Average rating calculation** - Correct average with 2 decimals
    - **Property 60: One review per order** - Second review rejected
    - **Property 61: Seller response limit** - Only one response allowed
    - **Validates: Requirements 10.1, 10.2, 10.3, 10.4, 10.5, 10.6, 10.7**
  
  - [ ] 19.3 Create review submission page
    - Create home/submit-review.php
    - Add star rating input (1-5)
    - Add comment textarea with character counter
    - Validate order is delivered
    - Prevent duplicate reviews
    - _Requirements: 10.1, 10.2, 10.3, 10.5_
  
  - [ ] 19.4 Display reviews on seller profile
    - Show average rating and total review count
    - Display individual reviews with ratings
    - Show seller responses
    - Add pagination for reviews
    - _Requirements: 10.4, 10.6, 10.7_
  
  - [ ] 19.5 Implement review moderation
    - Create flagReview() function for profanity detection
    - Create moderateReview() for admin actions
    - Add flag button to reviews
    - Create admin moderation queue
    - _Requirements: 10.10, 25.1, 25.2_

- [ ] 20. Implement dispute resolution system
  - [ ] 20.1 Create dispute management functions in includes/dispute.php
    - Implement openDispute() with 7-day time window
    - Implement addEvidence() for text and images
    - Implement resolveDispute() (admin only)
    - Implement getDisputeDetails() with evidence
    - _Requirements: 12.2, 12.3, 12.4, 12.5_
  
  - [ ]* 20.2 Write property tests for dispute system
    - **Property 64: Payment escrow** - Payment not released until delivered or resolved
    - **Property 65: Dispute time window** - Cannot open after 7 days
    - **Property 66: Automatic payment release** - Released after 7 days without dispute
    - **Validates: Requirements 12.1, 12.2, 12.7**
  
  - [ ] 20.3 Create dispute pages
    - Create home/open-dispute.php
    - Create home/dispute-details.php
    - Add evidence upload functionality
    - Show dispute status and resolution
    - _Requirements: 12.2, 12.3, 12.4_
  
  - [ ] 20.4 Create admin dispute management
    - Create admin/disputes.php
    - Show all open disputes
    - Add resolution actions (refund buyer, release to seller)
    - Add admin notes and communication
    - _Requirements: 12.5, 12.6_
  
  - [ ] 20.5 Implement payment escrow logic
    - Hold payment in escrow until order delivered
    - Automatically release after 7 days without dispute
    - Release on dispute resolution
    - _Requirements: 12.1, 12.7_

- [ ] 21. Implement advanced search and filtering
  - [ ] 21.1 Create SearchEngine class in includes/search.php
    - Implement search() with full-text search
    - Implement autocomplete() for search suggestions
    - Implement filterByCategory(), filterByPriceRange(), filterByLocation(), filterByCondition()
    - Implement sortBy() for price, date, relevance
    - Implement pagination with execute() and count()
    - _Requirements: 13.1, 13.2, 13.3, 13.4_
  
  - [ ]* 21.2 Write property tests for search
    - **Property 67: Full-text search** - Results contain search term
    - **Property 68: Price range filtering** - All results within min/max price
    - **Property 69: Category filtering** - All results in specified category
    - **Property 70: Location radius filtering** - All results within radius
    - **Property 71: Search result sorting** - Results ordered correctly
    - **Property 72: Pagination consistency** - No duplicates or missing items
    - **Validates: Requirements 13.1, 13.2, 13.3, 13.4**
  
  - [ ] 21.3 Update search page with advanced filters
    - Update home/search.php with filter UI
    - Add category dropdown filter
    - Add price range sliders
    - Add location radius filter
    - Add condition checkboxes
    - Add sort dropdown
    - _Requirements: 13.2, 13.3_
  
  - [ ] 21.4 Implement search autocomplete
    - Add autocomplete to search input
    - Show suggestions as user types
    - Implement debouncing for performance
    - _Requirements: 13.5_
  
  - [ ] 21.5 Optimize search performance
    - Use full-text indexes for search queries
    - Implement query result caching
    - Add pagination to limit results
    - _Requirements: 13.4, 16.5_

- [ ] 22. Checkpoint - Core features complete
  - Ensure all tests pass, ask the user if questions arise.
  - Test email verification flow end-to-end
  - Test password reset flow end-to-end
  - Test payment processing with sandbox
  - Test review submission and display
  - Test dispute opening and resolution
  - Test advanced search with all filters

### Phase 4: API Completion (Week 6) - MEDIUM PRIORITY

- [ ] 23. Implement RESTful API endpoints
  - [ ] 23.1 Create API authentication middleware
    - Create actions/v1/middleware/auth.php
    - Validate Bearer token from Authorization header
    - Return 401 for missing/invalid tokens
    - Set user context for authenticated requests
    - _Requirements: 17.4_
  
  - [ ] 23.2 Implement authentication API endpoints
    - Create POST /api/v1/auth/register
    - Create POST /api/v1/auth/login
    - Create POST /api/v1/auth/logout
    - Create POST /api/v1/auth/verify-email
    - Create POST /api/v1/auth/forgot-password
    - Create POST /api/v1/auth/reset-password
    - Return standard JSON response format
    - _Requirements: 17.1, 17.2, 17.3_
  
  - [ ] 23.3 Implement listing API endpoints
    - Create GET /api/v1/listings (with pagination and filters)
    - Create GET /api/v1/listings/:id
    - Create POST /api/v1/listings (authenticated)
    - Create PUT /api/v1/listings/:id (authenticated, owner only)
    - Create DELETE /api/v1/listings/:id (authenticated, owner only)
    - _Requirements: 17.1, 17.2, 17.5, 17.6_
  
  - [ ] 23.4 Implement bidding API endpoints
    - Create POST /api/v1/bids (authenticated)
    - Create GET /api/v1/listings/:id/bids
    - Validate bid amounts and auction status
    - _Requirements: 17.1, 17.2_
  
  - [ ] 23.5 Implement order API endpoints
    - Create GET /api/v1/orders (authenticated)
    - Create GET /api/v1/orders/:id (authenticated)
    - Filter by buyer/seller role
    - _Requirements: 17.1, 17.2_
  
  - [ ] 23.6 Implement messaging API endpoints
    - Create GET /api/v1/messages (authenticated)
    - Create POST /api/v1/messages (authenticated)
    - Create GET /api/v1/conversations/:id/messages (authenticated)
    - _Requirements: 17.1, 17.2_
  
  - [ ] 23.7 Implement notification API endpoints
    - Create GET /api/v1/notifications (authenticated)
    - Create PUT /api/v1/notifications/:id/read (authenticated)
    - Create PUT /api/v1/notifications/read-all (authenticated)
    - _Requirements: 17.1, 17.2_
  
  - [ ] 23.8 Implement category and review API endpoints
    - Create GET /api/v1/categories
    - Create POST /api/v1/reviews (authenticated)
    - Create GET /api/v1/users/:id/reviews
    - _Requirements: 17.1, 17.2_
  
  - [ ] 23.9 Implement seller analytics API endpoint
    - Create GET /api/v1/analytics/dashboard (authenticated)
    - Return revenue, listings, orders, rating stats
    - Include sales chart data
    - _Requirements: 11.1, 11.2, 11.3, 17.1_

- [ ] 24. Add API response formatting and error handling
  - [ ] 24.1 Create standard response helper
    - Create includes/api-response.php
    - Implement jsonSuccess() and jsonError() functions
    - Include success, data, message, errors, meta fields
    - Set appropriate HTTP status codes
    - _Requirements: 17.1, 17.2_
  
  - [ ]* 24.2 Write property tests for API responses
    - **Property 81: API JSON response format** - All responses have required fields
    - **Property 82: API status code correctness** - Correct codes for operations
    - **Property 83: API authentication requirement** - Protected endpoints return 401
    - **Property 84: API pagination metadata** - Meta object has required fields
    - **Property 85: API input validation** - Invalid params return 400 with errors
    - **Validates: Requirements 17.1, 17.2, 17.4, 17.8, 17.20**
  
  - [ ] 24.3 Add pagination to list endpoints
    - Implement pagination helper function
    - Add page, per_page, total, total_pages to meta
    - Default to 20 items per page, max 100
    - _Requirements: 17.8_
  
  - [ ] 24.4 Add CORS headers for mobile apps
    - Set Access-Control-Allow-Origin header
    - Set Access-Control-Allow-Methods header
    - Set Access-Control-Allow-Headers header
    - Handle OPTIONS preflight requests
    - _Requirements: 17.9_

- [ ] 25. Create API documentation
  - [ ] 25.1 Write API documentation in API_DOCUMENTATION.md
    - Document all endpoints with request/response examples
    - Document authentication requirements
    - Document rate limiting
    - Document error codes and messages
    - _Requirements: 18.1, 18.2_
  
  - [ ] 25.2 Create OpenAPI/Swagger specification
    - Write openapi.yaml with all endpoints
    - Include request/response schemas
    - Include authentication schemes
    - Host Swagger UI for interactive docs
    - _Requirements: 18.1, 18.2_
  
  - [ ] 25.3 Add API versioning
    - Implement version prefix /api/v1/
    - Document versioning strategy
    - Plan for future v2 compatibility
    - _Requirements: 18.3_

- [ ] 26. Checkpoint - API completion
  - Ensure all tests pass, ask the user if questions arise.
  - Test all API endpoints with Postman/curl
  - Verify authentication works correctly
  - Verify rate limiting on API endpoints
  - Verify CORS headers for mobile apps
  - Review API documentation completeness

### Phase 5: Performance Optimization (Week 7) - MEDIUM PRIORITY

- [ ] 27. Implement comprehensive error handling
  - [ ] 27.1 Create ErrorHandler class in includes/error-handler.php
    - Implement error logging to file
    - Implement user-friendly error display
    - Implement error notification for critical errors
    - Set up custom error and exception handlers
    - _Requirements: 19.1, 19.2, 19.3_
  
  - [ ] 27.2 Add try-catch blocks to all critical operations
    - Wrap database operations in try-catch
    - Wrap payment operations in try-catch
    - Wrap file operations in try-catch
    - Log errors with context information
    - _Requirements: 19.4_
  
  - [ ] 27.3 Implement graceful degradation
    - Show fallback UI when errors occur
    - Preserve user data on form errors
    - Provide retry mechanisms
    - _Requirements: 19.5_
  
  - [ ] 27.4 Create error log viewer for admins
    - Create admin/error-logs.php
    - Display recent errors with filtering
    - Add search and pagination
    - _Requirements: 19.6_

- [ ] 28. Optimize image handling
  - [ ] 28.1 Implement WebP conversion
    - Update ImageOptimizer to convert to WebP
    - Generate both WebP and original formats
    - Serve WebP to supporting browsers
    - _Requirements: 20.1, 20.2_
  
  - [ ] 28.2 Generate responsive image sizes
    - Create multiple sizes: thumbnail (150x150), small (400x400), medium (800x800), large (1200x1200)
    - Store all sizes in database
    - Use srcset in HTML for responsive images
    - _Requirements: 20.3_
  
  - [ ] 28.3 Implement lazy loading
    - Add loading="lazy" to all images
    - Implement intersection observer for older browsers
    - Show placeholder while loading
    - _Requirements: 20.4_
  
  - [ ] 28.4 Optimize existing images
    - Create script to batch process existing images
    - Generate missing thumbnails and WebP versions
    - Update database records
    - _Requirements: 20.1, 20.2_

- [ ] 29. Optimize database queries
  - [ ] 29.1 Implement query caching
    - Create CacheManager class in includes/cache.php
    - Implement file-based caching with TTL
    - Cache frequently accessed queries (categories, user profiles)
    - Implement cache invalidation on updates
    - _Requirements: 21.1, 21.2_
  
  - [ ] 29.2 Optimize N+1 query problems
    - Identify N+1 queries in listing displays
    - Use JOIN queries to fetch related data
    - Batch load user data for multiple listings
    - _Requirements: 21.3_
  
  - [ ] 29.3 Add query logging and monitoring
    - Log slow queries (>1 second)
    - Create admin dashboard for query performance
    - Identify optimization opportunities
    - _Requirements: 21.4_
  
  - [ ] 29.4 Optimize common queries
    - Optimize homepage listing query
    - Optimize search query with filters
    - Optimize user dashboard queries
    - Verify index usage with EXPLAIN
    - _Requirements: 21.3_

- [ ] 30. Optimize frontend assets
  - [ ] 30.1 Minify CSS and JavaScript
    - Create build/minify.php script
    - Minify all CSS files
    - Minify all JavaScript files
    - Combine multiple files where appropriate
    - _Requirements: 22.1_
  
  - [ ] 30.2 Implement asset versioning
    - Add version query strings to assets
    - Update on file changes for cache busting
    - _Requirements: 22.2_
  
  - [ ] 30.3 Use CDN for libraries
    - Move Bootstrap, jQuery to CDN
    - Add fallback for CDN failures
    - _Requirements: 22.3_
  
  - [ ] 30.4 Implement service worker for caching
    - Create sw.js service worker
    - Cache static assets
    - Implement offline fallback page
    - _Requirements: 22.4_

- [ ] 31. Checkpoint - Performance optimization complete
  - Ensure all tests pass, ask the user if questions arise.
  - Measure page load times before and after
  - Verify images load quickly with lazy loading
  - Test query performance improvements
  - Verify minified assets load correctly
  - Test offline functionality with service worker

### Phase 6: User Experience (Week 8) - MEDIUM PRIORITY

- [ ] 32. Implement responsive design
  - [ ] 32.1 Create responsive CSS
    - Create css/responsive.css
    - Add media queries for mobile, tablet, desktop
    - Test on various screen sizes
    - _Requirements: 23.1, 23.2_
  
  - [ ] 32.2 Fix mobile layout issues
    - Fix navigation menu for mobile
    - Fix listing cards for small screens
    - Fix forms for touch input
    - Optimize touch targets (min 44x44px)
    - _Requirements: 23.2, 23.3_
  
  - [ ] 32.3 Test on real devices
    - Test on iOS devices
    - Test on Android devices
    - Test on tablets
    - Fix device-specific issues
    - _Requirements: 23.1_

- [ ] 33. Implement accessibility features
  - [ ] 33.1 Add alt text to all images
    - Audit all images for missing alt text
    - Add descriptive alt text
    - Use empty alt for decorative images
    - _Requirements: 24.1_
  
  - [ ] 33.2 Implement keyboard navigation
    - Ensure all interactive elements are keyboard accessible
    - Add visible focus indicators
    - Test tab order
    - _Requirements: 24.2_
  
  - [ ] 33.3 Add ARIA labels and roles
    - Add ARIA labels to form inputs
    - Add ARIA roles to navigation
    - Add ARIA live regions for notifications
    - _Requirements: 24.3_
  
  - [ ] 33.4 Test with screen readers
    - Test with NVDA/JAWS on Windows
    - Test with VoiceOver on Mac/iOS
    - Fix screen reader issues
    - _Requirements: 24.4_
  
  - [ ] 33.5 Ensure color contrast compliance
    - Audit color contrast ratios
    - Fix low contrast text
    - Ensure WCAG AA compliance
    - _Requirements: 24.5_

- [ ] 34. Enhance notification system
  - [ ] 34.1 Add real-time notification updates
    - Implement polling for new notifications
    - Update notification badge without page reload
    - Show notification count in real-time
    - _Requirements: 26.7_
  
  - [ ] 34.2 Add notification preferences
    - Create notification settings page
    - Allow users to enable/disable notification types
    - Store preferences in database
    - _Requirements: 26.8_
  
  - [ ] 34.3 Implement email notifications
    - Send email for important notifications
    - Add email templates for each notification type
    - Respect user preferences
    - _Requirements: 26.9_
  
  - [ ] 34.4 Add notification types
    - Implement notifications for new bids
    - Implement notifications for outbid events
    - Implement notifications for new messages
    - Implement notifications for order status changes
    - Implement notifications for auction endings
    - _Requirements: 26.1, 26.2, 26.3, 26.4, 26.5_
  
  - [ ]* 34.5 Write property tests for notifications
    - **Property 110: Bid notification creation** - Notification created for listing owner
    - **Property 111: Outbid notification** - Notification created for outbid user
    - **Property 112: Order status notification** - Notification created for buyer
    - **Validates: Requirements 26.1, 26.2, 26.4**

- [ ] 35. Enhance delivery tracking
  - [ ] 35.1 Add real-time delivery status updates
    - Show current delivery status on order page
    - Display rider information
    - Show estimated delivery time
    - _Requirements: 27.1, 27.2_
  
  - [ ] 35.2 Implement delivery map tracking
    - Add map showing delivery route
    - Show rider current location
    - Update location in real-time
    - _Requirements: 27.3_
  
  - [ ] 35.3 Add proof of delivery display
    - Show uploaded proof of delivery image
    - Add delivery notes
    - Show delivery timestamp
    - _Requirements: 27.4_
  
  - [ ]* 35.4 Write property tests for delivery
    - **Property 107: Rider assignment radius** - Rider within 10km
    - **Property 108: Rider workload limit** - Max 5 concurrent deliveries
    - **Property 109: Delivery status progression** - Valid status transitions
    - **Validates: Requirements 28.1, 28.8, 27.2**

- [ ] 36. Implement location-based features
  - [ ] 36.1 Add location autocomplete
    - Implement Philippine city autocomplete
    - Use existing location data
    - Add to listing creation form
    - _Requirements: 29.3_
  
  - [ ] 36.2 Implement location-based search
    - Add "Near me" search option
    - Calculate distance from user location
    - Sort results by distance
    - _Requirements: 29.4_
  
  - [ ] 36.3 Add location to user addresses
    - Store multiple addresses per user
    - Set default address
    - Use for checkout
    - _Requirements: 29.1, 29.2_

- [ ] 37. Implement UX improvements
  - [ ] 37.1 Add breadcrumb navigation
    - Create breadcrumb component
    - Add to all pages
    - Show current page hierarchy
    - _Requirements: 30.1_
  
  - [ ] 37.2 Add loading indicators
    - Show spinner during AJAX requests
    - Show progress bar for file uploads
    - Add skeleton screens for content loading
    - _Requirements: 30.2_
  
  - [ ] 37.3 Add confirmation dialogs
    - Add confirmation for delete actions
    - Add confirmation for bid placement
    - Add confirmation for order cancellation
    - _Requirements: 30.3_
  
  - [ ] 37.4 Implement debouncing and throttling
    - Debounce search input (300ms)
    - Throttle scroll events
    - Throttle resize events
    - _Requirements: 30.4_

- [ ] 38. Checkpoint - User experience complete
  - Ensure all tests pass, ask the user if questions arise.
  - Test responsive design on all devices
  - Test accessibility with screen readers
  - Verify all notifications work correctly
  - Test delivery tracking features
  - Verify location-based features
  - Test all UX improvements

### Phase 7: Admin Tools (Week 9) - LOW PRIORITY

- [ ] 39. Implement content moderation
  - [ ] 39.1 Create moderation queue
    - Create admin/moderation.php
    - Show flagged listings and reviews
    - Add filtering and sorting
    - _Requirements: 25.1, 25.2_
  
  - [ ] 39.2 Add moderation actions
    - Implement approve/reject actions
    - Implement content removal
    - Implement user warnings
    - Send notifications to users
    - _Requirements: 25.3, 25.4_
  
  - [ ] 39.3 Implement user suspension
    - Add suspend/ban user functionality
    - Set restriction duration
    - Prevent suspended users from actions
    - _Requirements: 25.5, 25.6_
  
  - [ ] 39.4 Add bulk moderation actions
    - Select multiple items
    - Apply actions to multiple items
    - _Requirements: 25.7_

- [ ] 40. Enhance seller dashboard
  - [ ] 40.1 Create seller analytics page
    - Create home/seller-dashboard.php
    - Show revenue statistics (current month, all-time)
    - Show listing statistics (active, sold, views)
    - Show order statistics (pending, processing, completed)
    - _Requirements: 11.1, 11.2, 11.3_
  
  - [ ] 40.2 Add sales chart
    - Implement daily/weekly/monthly sales chart
    - Show revenue and order count over time
    - Use Chart.js for visualization
    - _Requirements: 11.4_
  
  - [ ] 40.3 Add top listings report
    - Show most viewed listings
    - Show most favorited listings
    - Show best selling listings
    - _Requirements: 11.5_
  
  - [ ] 40.4 Add performance metrics
    - Show average response time
    - Show average shipping time
    - Show customer satisfaction rating
    - _Requirements: 11.6_

- [ ] 41. Enhance admin dashboard
  - [ ] 41.1 Create admin analytics
    - Update admin/dashboard.php
    - Show platform-wide statistics
    - Show user growth metrics
    - Show revenue metrics
    - _Requirements: 36.1, 36.2_
  
  - [ ] 41.2 Add admin reports
    - Create sales report
    - Create user activity report
    - Create listing report
    - Add export to CSV functionality
    - _Requirements: 36.3_
  
  - [ ] 41.3 Add system health monitoring
    - Show database status
    - Show error rate
    - Show API response times
    - Add alerts for issues
    - _Requirements: 36.4_

- [ ] 42. Create audit log viewer
  - [ ] 42.1 Create audit log page
    - Create admin/audit-logs.php
    - Display all audit log entries
    - Add filtering by event type, user, date
    - Add search functionality
    - _Requirements: 37.9_
  
  - [ ] 42.2 Add audit log export
    - Export logs to CSV
    - Filter before export
    - _Requirements: 37.10_
  
  - [ ]* 42.3 Write property tests for audit logging
    - **Property 102: Login attempt logging** - All attempts logged
    - **Property 103: Password change logging** - Changes logged
    - **Property 104: Admin action logging** - Admin actions logged
    - **Property 105: Payment transaction logging** - Transactions logged
    - **Property 106: File upload logging** - Uploads logged
    - **Validates: Requirements 37.1, 37.2, 37.3, 37.5, 37.6, 37.8**

- [ ] 43. Checkpoint - Admin tools complete
  - Ensure all tests pass, ask the user if questions arise.
  - Test content moderation workflow
  - Test seller dashboard analytics
  - Test admin dashboard features
  - Verify audit log completeness

### Phase 8: Operations (Week 10) - LOW PRIORITY

- [ ] 44. Implement database backup system
  - [ ] 44.1 Create backup script
    - Create scripts/backup.sh
    - Use pg_dump for PostgreSQL backup
    - Compress backup files
    - Store with timestamp
    - _Requirements: 38.3_
  
  - [ ] 44.2 Schedule automated backups
    - Set up daily backup cron job
    - Set up weekly full backup
    - Retain backups for 30 days
    - _Requirements: 38.4_
  
  - [ ] 44.3 Test backup restoration
    - Document restoration procedure
    - Test restore on development database
    - Verify data integrity after restore
    - _Requirements: 38.5_
  
  - [ ] 44.4 Create backup documentation
    - Write BACKUP_PROCEDURES.md
    - Document backup schedule
    - Document restoration steps
    - Document troubleshooting
    - _Requirements: 38.3, 38.5_

- [ ] 45. Configure environment management
  - [ ] 45.1 Create .env.example file
    - Document all environment variables
    - Add descriptions for each variable
    - Include example values
    - _Requirements: 39.1_
  
  - [ ] 45.2 Update config.php
    - Load variables from .env file
    - Validate required variables
    - Set sensible defaults
    - _Requirements: 39.2_
  
  - [ ] 45.3 Document environment setup
    - Update SETUP_INSTRUCTIONS.md
    - Document all configuration options
    - Add troubleshooting section
    - _Requirements: 39.3_

- [ ] 46. Set up testing infrastructure
  - [ ] 46.1 Install and configure PHPUnit
    - Install PHPUnit via Composer
    - Create phpunit.xml configuration
    - Set up test database
    - Create tests/bootstrap.php
    - _Requirements: 40.1_
  
  - [ ] 46.2 Write unit tests
    - Write tests for AuthenticationModule
    - Write tests for SecurityModule
    - Write tests for InputValidator
    - Write tests for PaymentManager
    - Write tests for ReviewSystem
    - Target 70% code coverage
    - _Requirements: 40.2, 40.3_
  
  - [ ] 46.3 Write property-based tests
    - Install property testing library
    - Implement all 112 correctness properties
    - Run with 100+ iterations each
    - _Requirements: 40.4_
  
  - [ ] 46.4 Write integration tests
    - Test complete checkout flow
    - Test auction bidding flow
    - Test dispute resolution flow
    - Test email verification flow
    - Test password reset flow
    - _Requirements: 40.5_
  
  - [ ] 46.5 Create test data seeding
    - Create database/seeds/ directory
    - Write seed scripts for test data
    - Document seeding process
    - _Requirements: 40.1_

- [ ] 47. Set up CI/CD pipeline
  - [ ] 47.1 Create GitHub Actions workflow
    - Create .github/workflows/ci.yml
    - Run tests on every push
    - Run tests on pull requests
    - Check code style with PHP_CodeSniffer
    - _Requirements: 41.1, 41.2_
  
  - [ ] 47.2 Add deployment automation
    - Create .github/workflows/deploy.yml
    - Deploy to staging on merge to develop
    - Deploy to production on merge to main
    - Run database migrations automatically
    - _Requirements: 41.3_
  
  - [ ] 47.3 Create deployment script
    - Create deploy.sh script
    - Pull latest code
    - Run migrations
    - Clear cache
    - Restart services
    - _Requirements: 41.3_
  
  - [ ] 47.4 Set up staging environment
    - Configure staging server
    - Set up staging database
    - Configure environment variables
    - Test deployment process
    - _Requirements: 41.4_

- [ ] 48. Create comprehensive documentation
  - [ ] 48.1 Update README.md
    - Add project overview
    - Add installation instructions
    - Add configuration guide
    - Add usage examples
    - Add contributing guidelines
    - _Requirements: 42.1_
  
  - [ ] 48.2 Create API documentation
    - Complete API_DOCUMENTATION.md
    - Add all endpoint details
    - Add authentication guide
    - Add error handling guide
    - Add rate limiting details
    - _Requirements: 18.1, 18.2, 42.2_
  
  - [ ] 48.3 Create deployment guide
    - Write DEPLOYMENT.md
    - Document server requirements
    - Document deployment steps
    - Document rollback procedures
    - Document monitoring setup
    - _Requirements: 42.3_
  
  - [ ] 48.4 Create troubleshooting guide
    - Write TROUBLESHOOTING.md
    - Document common issues
    - Add solutions for each issue
    - Add debugging tips
    - _Requirements: 42.4_
  
  - [ ] 48.5 Create developer guide
    - Write DEVELOPER_GUIDE.md
    - Document code structure
    - Document coding standards
    - Document testing procedures
    - Document contribution workflow
    - _Requirements: 42.5_

- [ ] 49. Enhance auction system
  - [ ] 49.1 Implement bid validation
    - Validate bid amount > current price + increment
    - Prevent self-bidding
    - Check auction is still active
    - _Requirements: 33.3, 33.4, 33.5_
  
  - [ ]* 49.2 Write property tests for auction system
    - **Property 92: Bid amount validation** - Bid must exceed current + increment
    - **Property 93: Self-bidding prevention** - Seller cannot bid on own listing
    - **Property 94: Auction extension** - Bid in last 5 min extends by 5 min
    - **Property 95: Automatic order creation** - Order created for winner
    - **Property 96: Auction cancellation restriction** - Cannot cancel with bids
    - **Validates: Requirements 33.3, 33.4, 33.5, 33.6, 33.7, 33.11**
  
  - [ ] 49.3 Implement auction extension
    - Extend auction by 5 minutes if bid in last 5 minutes
    - Notify all bidders of extension
    - _Requirements: 33.6_
  
  - [ ] 49.4 Implement automatic order creation
    - Create order for highest bidder when auction ends
    - Send notification to winner and seller
    - Update listing status to sold
    - _Requirements: 33.7_
  
  - [ ] 49.5 Add auction cancellation restrictions
    - Prevent cancellation if bids exist
    - Allow cancellation before first bid
    - _Requirements: 33.11_

- [ ] 50. Enhance messaging system
  - [ ] 50.1 Add message length validation
    - Validate message length 1-1000 characters
    - Show character counter
    - _Requirements: 34.5_
  
  - [ ]* 50.2 Write property tests for messaging
    - **Property 97: Message length validation** - Messages outside 1-1000 rejected
    - **Property 98: Message read status** - Opening conversation marks messages read
    - **Validates: Requirements 34.5, 34.3**
  
  - [ ] 50.3 Implement read status updates
    - Mark messages as read when conversation opened
    - Update unread count in real-time
    - _Requirements: 34.3_

- [ ] 51. Enhance cart and checkout
  - [ ] 51.1 Add cart item availability validation
    - Check listing status before checkout
    - Remove unavailable items from cart
    - Notify user of removed items
    - _Requirements: 35.4, 35.5_
  
  - [ ]* 51.2 Write property tests for cart and checkout
    - **Property 99: Cart item availability** - Unavailable items removed with notification
    - **Property 100: Multi-vendor order separation** - N sellers create N orders
    - **Property 101: Cart cleanup** - Cart cleared after successful checkout
    - **Validates: Requirements 35.4, 35.5, 35.10, 35.12**
  
  - [ ] 51.3 Implement multi-vendor order separation
    - Create separate orders for each seller
    - Calculate shipping per seller
    - Process payments separately
    - _Requirements: 35.10_
  
  - [ ] 51.4 Clear cart after checkout
    - Remove all items after successful checkout
    - Keep items if checkout fails
    - _Requirements: 35.12_

- [ ] 52. Final checkpoint - All phases complete
  - Ensure all tests pass, ask the user if questions arise.
  - Run complete test suite (unit, property, integration)
  - Verify all 42 requirements are implemented
  - Verify all 112 properties pass
  - Test all features end-to-end
  - Review all documentation
  - Perform security audit
  - Measure performance metrics
  - Verify backup and restore procedures
  - Test CI/CD pipeline
  - Prepare for production deployment

## Notes

- Tasks marked with `*` are optional property-based tests that can be skipped for faster MVP delivery
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation and allow for user feedback
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
- Integration tests validate complete user workflows
- All database changes use migrations with rollback capability
- Security is prioritized in Phase 1 as CRITICAL
- Core features in Phases 2-3 are HIGH priority
- Performance and UX in Phases 4-6 are MEDIUM priority
- Admin tools and operations in Phases 7-8 are LOW priority but important for production readiness

## Success Criteria

Implementation is complete when:
1. All 112 correctness properties pass with 100+ iterations each
2. Unit test coverage exceeds 70%
3. All critical user flows pass integration tests
4. Security audit shows no high or critical vulnerabilities
5. Page load times are under 2 seconds for 95% of requests
6. API response times are under 500ms for 95% of requests
7. All 42 requirements are fully implemented and verified
8. Documentation is complete and up-to-date
9. CI/CD pipeline is operational
10. System is ready for production deployment
