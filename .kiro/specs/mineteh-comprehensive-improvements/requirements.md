# Requirements Document: MineTeh Marketplace Comprehensive Improvements

## Introduction

This document specifies requirements for comprehensive improvements to the MineTeh marketplace platform, a PHP-based auction and marketplace system using Supabase (PostgreSQL). The improvements address critical security vulnerabilities, implement missing core features, enhance database integrity, improve API functionality, and optimize performance. The goal is to transform the platform from 58% complete to a production-ready, secure, and fully functional marketplace.

## Glossary

- **MineTeh_System**: The complete marketplace platform including frontend, backend, database, and APIs
- **Authentication_Module**: The system component handling user login, registration, and session management
- **Payment_Gateway**: Third-party service integration for processing financial transactions (GCash, PayPal, or Stripe)
- **Listing**: An item posted for sale or auction on the marketplace
- **Bid**: An offer to purchase a listing at a specified price
- **Order**: A confirmed purchase transaction between buyer and seller
- **Review**: User feedback and rating for a completed transaction
- **Admin_Panel**: Administrative interface for platform management
- **CSRF_Token**: Cross-Site Request Forgery protection token
- **Rate_Limiter**: System component that restricts request frequency per user/IP
- **Supabase**: PostgreSQL database backend service
- **Session**: Server-side user authentication state stored in $_SESSION
- **RLS_Policy**: Row Level Security policy in Supabase
- **Seller_Dashboard**: Interface showing seller analytics and listing management
- **Buyer_Protection**: Features ensuring safe transactions for purchasers
- **Audit_Log**: System record of security-relevant actions
- **Transaction_Record**: Database record of payment and order details
- **Search_Index**: Database optimization for fast search queries
- **API_Endpoint**: RESTful interface for programmatic access
- **Validation_Rule**: Input checking logic to prevent invalid data
- **Error_Handler**: System component for logging and displaying errors
- **Cache_Layer**: Performance optimization storing frequently accessed data
- **Image_Optimizer**: Component for compressing and resizing uploaded images
- **Email_Verifier**: System for confirming user email addresses
- **Password_Reset_Flow**: Multi-step process for secure password recovery
- **Dispute_Resolution**: Admin tools for handling transaction conflicts
- **Content_Moderator**: Admin tools for reviewing and managing user content
- **Tracking_Number**: Shipment identifier for order delivery status
- **Notification_System**: Real-time alerts for user actions and events
- **Responsive_Design**: UI that adapts to different screen sizes
- **Accessibility_Feature**: UI elements supporting users with disabilities
- **Lazy_Loading**: Performance technique loading content as needed
- **Breadcrumb**: Navigation element showing current page hierarchy
- **Loading_Indicator**: Visual feedback during asynchronous operations
- **Confirmation_Dialog**: UI prompt requiring user verification before action
- **Graceful_Degradation**: System behavior maintaining functionality during errors
- **Foreign_Key_Constraint**: Database rule ensuring referential integrity
- **Check_Constraint**: Database rule validating column values
- **Index**: Database structure for optimizing query performance
- **Prepared_Statement**: SQL query with parameterized inputs preventing injection
- **XSS_Filter**: Input sanitization preventing cross-site scripting attacks
- **SQL_Injection**: Security vulnerability from unsanitized database queries
- **Rider**: Delivery personnel assigned to transport orders
- **Delivery_Assignment**: Process of matching orders to available riders
- **Proof_Of_Delivery**: Photo or signature confirming order receipt
- **Geolocation**: Geographic coordinates for location-based features
- **Autocomplete**: UI feature suggesting options as user types
- **Carousel**: UI component displaying multiple images in sequence
- **Thumbnail**: Small preview image for quick browsing
- **Fullscreen_Mode**: UI state showing image at maximum size
- **JSON_Response**: Structured data format for API communication
- **HTTP_Status_Code**: Numeric code indicating request outcome
- **CORS_Header**: HTTP header enabling cross-origin API requests
- **Pagination**: Dividing large result sets into manageable pages
- **Sorting_Option**: User control for ordering search results
- **Filter_Criteria**: User-specified conditions for narrowing results
- **Debounce**: Technique delaying function execution until input pauses
- **Throttle**: Technique limiting function execution frequency
- **Minification**: Process reducing file size by removing whitespace
- **CDN**: Content Delivery Network for faster asset loading
- **Database_Migration**: Structured process for schema changes
- **Rollback_Script**: SQL commands to reverse database changes
- **Health_Check**: Endpoint verifying system operational status
- **Monitoring_Dashboard**: Interface displaying system metrics and alerts
- **Error_Boundary**: Component catching and handling UI errors
- **Fallback_UI**: Alternative interface shown during errors or loading


## Requirements

### Requirement 1: Secure Authentication and Session Management

**User Story:** As a user, I want secure authentication with proper session handling, so that my account cannot be compromised through common attack vectors.

#### Acceptance Criteria

1. THE Authentication_Module SHALL hash all passwords using bcrypt with a minimum cost factor of 10
2. WHEN a user logs in, THE Authentication_Module SHALL generate a cryptographically secure session token
3. THE Authentication_Module SHALL store session data in the user_sessions table with user_id, session_token, ip_address, user_agent, and expiry timestamp
4. WHEN a session expires, THE Authentication_Module SHALL delete the session record and redirect to login
5. THE Authentication_Module SHALL implement session fixation protection by regenerating session IDs after login
6. THE Authentication_Module SHALL limit concurrent sessions to 5 per user
7. WHEN a user logs out, THE Authentication_Module SHALL delete the session record from the database
8. THE Authentication_Module SHALL validate session tokens on every authenticated request
9. IF a session token is invalid or expired, THEN THE Authentication_Module SHALL return HTTP 401 status
10. THE Authentication_Module SHALL log all authentication events (login, logout, failed attempts) to the Audit_Log

### Requirement 2: Cross-Site Request Forgery Protection

**User Story:** As a user, I want protection against CSRF attacks, so that malicious sites cannot perform actions on my behalf.

#### Acceptance Criteria

1. THE MineTeh_System SHALL generate a unique CSRF_Token for each user session
2. THE MineTeh_System SHALL include the CSRF_Token in all forms as a hidden input field
3. WHEN a state-changing request is received, THE MineTeh_System SHALL validate the CSRF_Token matches the session token
4. IF the CSRF_Token is missing or invalid, THEN THE MineTeh_System SHALL reject the request with HTTP 403 status
5. THE MineTeh_System SHALL regenerate CSRF_Token after sensitive operations (password change, email change)
6. THE MineTeh_System SHALL set SameSite=Strict attribute on session cookies
7. THE MineTeh_System SHALL validate the HTTP Referer header for state-changing requests


### Requirement 3: SQL Injection Prevention

**User Story:** As a developer, I want all database queries to use prepared statements, so that SQL injection attacks are prevented.

#### Acceptance Criteria

1. THE MineTeh_System SHALL use Prepared_Statement for all database queries with user input
2. THE MineTeh_System SHALL parameterize all SQL query variables using bound parameters
3. THE MineTeh_System SHALL reject any direct string concatenation in SQL queries during code review
4. WHEN building dynamic queries, THE MineTeh_System SHALL use whitelisted column names and table names
5. THE MineTeh_System SHALL validate and sanitize all user input before database operations
6. THE MineTeh_System SHALL escape special characters in LIKE clause patterns
7. THE MineTeh_System SHALL use type casting for numeric parameters (intval, floatval)

### Requirement 4: Cross-Site Scripting Prevention

**User Story:** As a user, I want all displayed content to be sanitized, so that malicious scripts cannot execute in my browser.

#### Acceptance Criteria

1. THE MineTeh_System SHALL apply htmlspecialchars() to all user-generated content before display
2. THE MineTeh_System SHALL use ENT_QUOTES flag when encoding HTML entities
3. THE MineTeh_System SHALL set Content-Security-Policy header restricting script sources
4. THE MineTeh_System SHALL sanitize rich text content using an allowlist of safe HTML tags
5. WHEN storing user input, THE MineTeh_System SHALL preserve original content without encoding
6. WHEN displaying user input, THE MineTeh_System SHALL apply context-appropriate encoding
7. THE MineTeh_System SHALL validate and sanitize file upload names to prevent path traversal
8. THE MineTeh_System SHALL set X-Content-Type-Options: nosniff header
9. THE MineTeh_System SHALL set X-Frame-Options: DENY header to prevent clickjacking


### Requirement 5: Rate Limiting and Brute Force Protection

**User Story:** As a platform administrator, I want rate limiting on sensitive endpoints, so that brute force attacks and abuse are prevented.

#### Acceptance Criteria

1. THE Rate_Limiter SHALL limit login attempts to 5 per IP address per 15 minutes
2. WHEN login attempts exceed the limit, THE Rate_Limiter SHALL block further attempts for 30 minutes
3. THE Rate_Limiter SHALL limit registration attempts to 3 per IP address per hour
4. THE Rate_Limiter SHALL limit password reset requests to 3 per email address per hour
5. THE Rate_Limiter SHALL limit API requests to 100 per user per minute
6. THE Rate_Limiter SHALL limit search queries to 20 per user per minute
7. WHEN rate limits are exceeded, THE Rate_Limiter SHALL return HTTP 429 status with Retry-After header
8. THE Rate_Limiter SHALL store attempt counts in a rate_limits table with ip_address, endpoint, attempt_count, and window_start
9. THE Rate_Limiter SHALL clean up expired rate limit records older than 24 hours
10. THE Rate_Limiter SHALL log rate limit violations to the Audit_Log

### Requirement 6: Secure File Upload Handling

**User Story:** As a user, I want to upload images safely, so that malicious files cannot compromise the system.

#### Acceptance Criteria

1. THE MineTeh_System SHALL validate uploaded file MIME types using finfo_file()
2. THE MineTeh_System SHALL restrict uploads to image types: JPEG, PNG, GIF, WebP
3. THE MineTeh_System SHALL limit individual file size to 5MB
4. THE MineTeh_System SHALL limit total uploads to 5 images per listing
5. THE MineTeh_System SHALL generate random filenames using uniqid() and hash functions
6. THE MineTeh_System SHALL store uploaded files outside the web root directory
7. THE MineTeh_System SHALL serve uploaded files through a PHP script with proper headers
8. THE MineTeh_System SHALL scan uploaded files for executable code patterns
9. THE MineTeh_System SHALL reject files with double extensions (e.g., image.php.jpg)
10. WHEN a video file is uploaded, THE MineTeh_System SHALL reject it with a clear error message
11. THE Image_Optimizer SHALL compress uploaded images to reduce file size by at least 30%
12. THE Image_Optimizer SHALL generate thumbnails at 150x150 pixels for listing previews


### Requirement 7: Email Verification System

**User Story:** As a platform administrator, I want users to verify their email addresses, so that fake accounts and spam are reduced.

#### Acceptance Criteria

1. WHEN a user registers, THE Authentication_Module SHALL send a verification email with a unique token
2. THE Authentication_Module SHALL generate verification tokens using random_bytes(32) and hash them
3. THE Authentication_Module SHALL store verification tokens in the users table with email_verified and verification_token columns
4. THE Authentication_Module SHALL set verification token expiry to 24 hours
5. WHEN a user clicks the verification link, THE Authentication_Module SHALL validate the token and mark email_verified as true
6. THE MineTeh_System SHALL restrict unverified users from creating listings or placing bids
7. THE MineTeh_System SHALL display a verification reminder banner for unverified users
8. THE Authentication_Module SHALL allow users to resend verification emails with 5-minute cooldown
9. IF a verification token is expired, THEN THE Authentication_Module SHALL generate a new token when resend is requested
10. THE Authentication_Module SHALL delete verification tokens after successful verification

### Requirement 8: Password Reset Flow

**User Story:** As a user, I want to securely reset my password if I forget it, so that I can regain access to my account.

#### Acceptance Criteria

1. WHEN a user requests password reset, THE Password_Reset_Flow SHALL send an email with a unique reset token
2. THE Password_Reset_Flow SHALL generate reset tokens using random_bytes(32) and hash them
3. THE Password_Reset_Flow SHALL store reset tokens in a password_resets table with email, token, and expiry timestamp
4. THE Password_Reset_Flow SHALL set reset token expiry to 1 hour
5. WHEN a user clicks the reset link, THE Password_Reset_Flow SHALL validate the token and display password change form
6. THE Password_Reset_Flow SHALL require password confirmation matching the new password
7. THE Password_Reset_Flow SHALL enforce minimum password length of 8 characters
8. WHEN password is changed, THE Password_Reset_Flow SHALL invalidate all existing sessions for that user
9. THE Password_Reset_Flow SHALL delete the reset token after successful password change
10. THE Password_Reset_Flow SHALL log password reset events to the Audit_Log


### Requirement 9: Payment Gateway Integration

**User Story:** As a buyer, I want to pay securely through trusted payment providers, so that my financial information is protected.

#### Acceptance Criteria

1. THE Payment_Gateway SHALL integrate with at least one provider: GCash, PayPal, or Stripe
2. THE Payment_Gateway SHALL redirect users to the provider's secure checkout page
3. THE Payment_Gateway SHALL validate payment webhooks using signature verification
4. WHEN a payment is completed, THE Payment_Gateway SHALL update the order status to "paid"
5. THE Payment_Gateway SHALL store Transaction_Record with transaction_id, amount, currency, status, and timestamp
6. THE Payment_Gateway SHALL handle payment failures gracefully with user-friendly error messages
7. THE Payment_Gateway SHALL support payment refunds through the provider's API
8. THE Payment_Gateway SHALL log all payment events to the Audit_Log
9. THE Payment_Gateway SHALL never store credit card numbers or CVV codes
10. THE Payment_Gateway SHALL display payment confirmation with transaction details
11. THE Payment_Gateway SHALL send email receipts for successful payments

### Requirement 10: Order Review and Rating System

**User Story:** As a buyer, I want to review sellers after purchase, so that other users can make informed decisions.

#### Acceptance Criteria

1. WHEN an order is marked as delivered, THE MineTeh_System SHALL enable the review option for the buyer
2. THE MineTeh_System SHALL allow buyers to submit a Review with rating (1-5 stars) and comment
3. THE MineTeh_System SHALL store reviews in a reviews table with order_id, buyer_id, seller_id, rating, comment, and created_at
4. THE MineTeh_System SHALL display reviews on seller profiles with average rating
5. THE MineTeh_System SHALL limit reviews to one per order
6. THE MineTeh_System SHALL allow sellers to respond to reviews with a single reply
7. THE MineTeh_System SHALL calculate seller average rating from all reviews
8. THE MineTeh_System SHALL display review count and average rating on listings
9. THE MineTeh_System SHALL allow users to edit their reviews within 7 days
10. THE MineTeh_System SHALL flag reviews containing profanity or inappropriate content for moderation


### Requirement 11: Seller Dashboard and Analytics

**User Story:** As a seller, I want to view analytics about my listings and sales, so that I can optimize my business performance.

#### Acceptance Criteria

1. THE Seller_Dashboard SHALL display total revenue for the current month and all time
2. THE Seller_Dashboard SHALL display count of active listings, sold items, and pending orders
3. THE Seller_Dashboard SHALL display a chart of sales over the last 30 days
4. THE Seller_Dashboard SHALL display top-performing listings by views and sales
5. THE Seller_Dashboard SHALL display average rating and total review count
6. THE Seller_Dashboard SHALL display recent orders with status and buyer information
7. THE Seller_Dashboard SHALL display listing view counts and favorite counts
8. THE Seller_Dashboard SHALL allow filtering analytics by date range
9. THE Seller_Dashboard SHALL display conversion rate (views to sales) for each listing
10. THE Seller_Dashboard SHALL refresh data in real-time or with manual refresh button

### Requirement 12: Buyer Protection and Dispute Resolution

**User Story:** As a buyer, I want protection against fraudulent sellers, so that I can shop with confidence.

#### Acceptance Criteria

1. THE MineTeh_System SHALL hold payment in escrow until buyer confirms delivery
2. THE MineTeh_System SHALL allow buyers to open disputes within 7 days of delivery
3. WHEN a dispute is opened, THE Dispute_Resolution SHALL notify the seller and admin
4. THE Dispute_Resolution SHALL allow both parties to submit evidence (text and images)
5. THE Dispute_Resolution SHALL allow admins to review disputes and make decisions
6. WHEN admin resolves a dispute, THE Dispute_Resolution SHALL process refund or release payment accordingly
7. THE MineTeh_System SHALL automatically release payment to seller 7 days after delivery if no dispute is opened
8. THE MineTeh_System SHALL track seller dispute rate and flag accounts with high dispute rates
9. THE MineTeh_System SHALL suspend sellers with dispute rate above 20%
10. THE MineTeh_System SHALL send email notifications at each dispute resolution stage


### Requirement 13: Advanced Search and Filtering

**User Story:** As a buyer, I want to search and filter listings effectively, so that I can find exactly what I'm looking for.

#### Acceptance Criteria

1. THE MineTeh_System SHALL support full-text search across listing titles and descriptions
2. THE MineTeh_System SHALL allow filtering by category, price range, condition, and location
3. THE MineTeh_System SHALL allow sorting by relevance, price (low to high, high to low), date posted, and popularity
4. THE MineTeh_System SHALL display search results with Pagination showing 20 items per page
5. THE MineTeh_System SHALL highlight search terms in results using bold text
6. THE MineTeh_System SHALL implement autocomplete suggestions as user types in search box
7. THE MineTeh_System SHALL debounce search input to reduce server requests
8. THE MineTeh_System SHALL display filter counts showing number of results per filter option
9. THE MineTeh_System SHALL preserve search filters and sorting when navigating between pages
10. THE MineTeh_System SHALL display "no results" message with suggestions when search returns empty
11. THE Search_Index SHALL optimize queries to return results within 500ms for 95% of searches

### Requirement 14: Database Referential Integrity

**User Story:** As a developer, I want proper foreign key constraints, so that data consistency is maintained automatically.

#### Acceptance Criteria

1. THE Supabase SHALL define Foreign_Key_Constraint from listings.user_id to users.id with ON DELETE CASCADE
2. THE Supabase SHALL define Foreign_Key_Constraint from bids.listing_id to listings.id with ON DELETE CASCADE
3. THE Supabase SHALL define Foreign_Key_Constraint from bids.user_id to users.id with ON DELETE CASCADE
4. THE Supabase SHALL define Foreign_Key_Constraint from orders.buyer_id to users.id with ON DELETE RESTRICT
5. THE Supabase SHALL define Foreign_Key_Constraint from orders.seller_id to users.id with ON DELETE RESTRICT
6. THE Supabase SHALL define Foreign_Key_Constraint from orders.listing_id to listings.id with ON DELETE RESTRICT
7. THE Supabase SHALL define Foreign_Key_Constraint from messages.sender_id to users.id with ON DELETE CASCADE
8. THE Supabase SHALL define Foreign_Key_Constraint from messages.recipient_id to users.id with ON DELETE CASCADE
9. THE Supabase SHALL define Foreign_Key_Constraint from reviews.order_id to orders.id with ON DELETE CASCADE
10. THE Supabase SHALL define Foreign_Key_Constraint from cart_items.user_id to users.id with ON DELETE CASCADE
11. THE Supabase SHALL define Foreign_Key_Constraint from favorites.user_id to users.id with ON DELETE CASCADE
12. THE Supabase SHALL define Foreign_Key_Constraint from deliveries.order_id to orders.id with ON DELETE CASCADE
13. THE Supabase SHALL define Foreign_Key_Constraint from deliveries.rider_id to riders.id with ON DELETE SET NULL


### Requirement 15: Database Constraints and Validation

**User Story:** As a developer, I want database-level validation rules, so that invalid data cannot be inserted.

#### Acceptance Criteria

1. THE Supabase SHALL define Check_Constraint on listings.price ensuring value >= 0
2. THE Supabase SHALL define Check_Constraint on bids.amount ensuring value > 0
3. THE Supabase SHALL define Check_Constraint on reviews.rating ensuring value BETWEEN 1 AND 5
4. THE Supabase SHALL define Check_Constraint on users.email ensuring valid email format using regex
5. THE Supabase SHALL define NOT NULL constraint on users.email, users.password_hash, users.created_at
6. THE Supabase SHALL define NOT NULL constraint on listings.title, listings.price, listings.user_id
7. THE Supabase SHALL define NOT NULL constraint on orders.buyer_id, orders.seller_id, orders.total_amount
8. THE Supabase SHALL define UNIQUE constraint on users.email
9. THE Supabase SHALL define UNIQUE constraint on user_sessions.session_token
10. THE Supabase SHALL define DEFAULT value for listings.status as 'active'
11. THE Supabase SHALL define DEFAULT value for users.created_at as CURRENT_TIMESTAMP
12. THE Supabase SHALL define Check_Constraint on listings.auction_end_time ensuring it is after created_at

### Requirement 16: Database Indexing for Performance

**User Story:** As a user, I want fast page loads and search results, so that I can browse efficiently.

#### Acceptance Criteria

1. THE Supabase SHALL create Index on listings(user_id) for seller listing queries
2. THE Supabase SHALL create Index on listings(category_id) for category browsing
3. THE Supabase SHALL create Index on listings(status, created_at DESC) for homepage queries
4. THE Supabase SHALL create Index on bids(listing_id, amount DESC) for bid history queries
5. THE Supabase SHALL create Index on orders(buyer_id, created_at DESC) for order history queries
6. THE Supabase SHALL create Index on orders(seller_id, status) for seller order management
7. THE Supabase SHALL create Index on messages(recipient_id, created_at DESC) for inbox queries
8. THE Supabase SHALL create Index on notifications(user_id, is_read, created_at DESC) for notification queries
9. THE Supabase SHALL create full-text Index on listings(title, description) for search queries
10. THE Supabase SHALL create Index on user_sessions(session_token) for authentication queries
11. THE Supabase SHALL create Index on favorites(user_id, listing_id) for saved items queries
12. THE Supabase SHALL create Index on cart_items(user_id) for cart queries


### Requirement 17: RESTful API for Mobile App

**User Story:** As a mobile app developer, I want a complete REST API, so that I can build native Android and iOS apps.

#### Acceptance Criteria

1. THE API_Endpoint SHALL return responses in JSON_Response format with consistent structure
2. THE API_Endpoint SHALL use appropriate HTTP_Status_Code (200, 201, 400, 401, 403, 404, 500)
3. THE API_Endpoint SHALL include CORS_Header allowing requests from mobile apps
4. THE API_Endpoint SHALL require authentication token in Authorization header for protected endpoints
5. THE API_Endpoint SHALL implement POST /api/v1/auth/login returning user data and session token
6. THE API_Endpoint SHALL implement POST /api/v1/auth/register creating new user account
7. THE API_Endpoint SHALL implement POST /api/v1/auth/logout invalidating session token
8. THE API_Endpoint SHALL implement GET /api/v1/listings returning paginated listing array
9. THE API_Endpoint SHALL implement GET /api/v1/listings/:id returning single listing with images
10. THE API_Endpoint SHALL implement POST /api/v1/listings creating new listing with image upload
11. THE API_Endpoint SHALL implement PUT /api/v1/listings/:id updating existing listing
12. THE API_Endpoint SHALL implement DELETE /api/v1/listings/:id soft-deleting listing
13. THE API_Endpoint SHALL implement GET /api/v1/categories returning category array
14. THE API_Endpoint SHALL implement POST /api/v1/bids creating new bid on listing
15. THE API_Endpoint SHALL implement GET /api/v1/orders returning user's order history
16. THE API_Endpoint SHALL implement GET /api/v1/messages returning conversation list
17. THE API_Endpoint SHALL implement POST /api/v1/messages sending new message
18. THE API_Endpoint SHALL implement GET /api/v1/notifications returning notification array
19. THE API_Endpoint SHALL implement PUT /api/v1/notifications/:id/read marking notification as read
20. THE API_Endpoint SHALL validate all input parameters and return 400 with error details for invalid requests


### Requirement 18: API Documentation and Versioning

**User Story:** As an API consumer, I want clear documentation and stable versioning, so that I can integrate reliably.

#### Acceptance Criteria

1. THE MineTeh_System SHALL version all API endpoints with /api/v1/ prefix
2. THE MineTeh_System SHALL maintain API documentation listing all endpoints, parameters, and responses
3. THE MineTeh_System SHALL provide example requests and responses for each endpoint
4. THE MineTeh_System SHALL document authentication requirements for each endpoint
5. THE MineTeh_System SHALL document rate limits for each endpoint
6. THE MineTeh_System SHALL maintain backward compatibility within major versions
7. WHEN breaking changes are needed, THE MineTeh_System SHALL create new major version (v2)
8. THE MineTeh_System SHALL support previous major version for at least 6 months after new version release
9. THE MineTeh_System SHALL include API version in response headers
10. THE MineTeh_System SHALL provide API changelog documenting all changes

### Requirement 19: Error Handling and Logging

**User Story:** As a developer, I want comprehensive error logging, so that I can debug issues quickly.

#### Acceptance Criteria

1. THE Error_Handler SHALL log all PHP errors to a dedicated error log file
2. THE Error_Handler SHALL log all database errors with query context
3. THE Error_Handler SHALL log all authentication failures with IP address and timestamp
4. THE Error_Handler SHALL log all payment processing errors with transaction details
5. THE Error_Handler SHALL display user-friendly error messages without exposing system details
6. THE Error_Handler SHALL send email alerts to admins for critical errors (database connection, payment failures)
7. THE Error_Handler SHALL implement Graceful_Degradation showing fallback UI during errors
8. THE Error_Handler SHALL log API errors with request details and response codes
9. THE Error_Handler SHALL rotate log files daily and archive logs older than 30 days
10. THE Error_Handler SHALL provide admin interface for viewing recent errors
11. THE Error_Handler SHALL categorize errors by severity (info, warning, error, critical)


### Requirement 20: Performance Optimization - Image Loading

**User Story:** As a user, I want images to load quickly, so that I can browse listings without delays.

#### Acceptance Criteria

1. THE Image_Optimizer SHALL compress uploaded images to WebP format with 80% quality
2. THE Image_Optimizer SHALL generate responsive image sizes (thumbnail, medium, large)
3. THE MineTeh_System SHALL implement Lazy_Loading for images below the fold
4. THE MineTeh_System SHALL use loading="lazy" attribute on img tags
5. THE MineTeh_System SHALL serve images with proper cache headers (max-age=31536000)
6. THE MineTeh_System SHALL use CDN for serving static images
7. THE MineTeh_System SHALL implement progressive JPEG loading for large images
8. THE MineTeh_System SHALL display Loading_Indicator while images are loading
9. THE MineTeh_System SHALL provide low-quality image placeholder (LQIP) during load
10. THE Carousel SHALL preload next and previous images for smooth navigation

### Requirement 21: Performance Optimization - Database Queries

**User Story:** As a user, I want pages to load in under 2 seconds, so that browsing is smooth and responsive.

#### Acceptance Criteria

1. THE MineTeh_System SHALL limit database queries to maximum 10 per page load
2. THE MineTeh_System SHALL use SELECT with specific columns instead of SELECT *
3. THE MineTeh_System SHALL implement query result caching for frequently accessed data
4. THE Cache_Layer SHALL cache homepage listings for 5 minutes
5. THE Cache_Layer SHALL cache category lists for 1 hour
6. THE Cache_Layer SHALL cache user profile data for 10 minutes
7. THE Cache_Layer SHALL invalidate cache when related data is updated
8. THE MineTeh_System SHALL use LIMIT clause on all list queries
9. THE MineTeh_System SHALL implement cursor-based pagination for large result sets
10. THE MineTeh_System SHALL use database connection pooling to reduce connection overhead
11. THE MineTeh_System SHALL log slow queries (>1 second) for optimization review


### Requirement 22: Performance Optimization - Frontend Assets

**User Story:** As a user, I want the website to load quickly on slow connections, so that I can access it from anywhere.

#### Acceptance Criteria

1. THE MineTeh_System SHALL minify all CSS files using Minification tools
2. THE MineTeh_System SHALL minify all JavaScript files using Minification tools
3. THE MineTeh_System SHALL combine multiple CSS files into single bundle per page
4. THE MineTeh_System SHALL combine multiple JavaScript files into single bundle per page
5. THE MineTeh_System SHALL load non-critical JavaScript asynchronously using async or defer attributes
6. THE MineTeh_System SHALL inline critical CSS for above-the-fold content
7. THE MineTeh_System SHALL serve assets with gzip or brotli compression
8. THE MineTeh_System SHALL use CDN for serving jQuery, Bootstrap, and other libraries
9. THE MineTeh_System SHALL implement service worker for offline functionality
10. THE MineTeh_System SHALL achieve Lighthouse performance score above 80
11. THE MineTeh_System SHALL achieve First Contentful Paint under 1.5 seconds
12. THE MineTeh_System SHALL achieve Time to Interactive under 3 seconds

### Requirement 23: Responsive Design and Mobile Optimization

**User Story:** As a mobile user, I want the website to work perfectly on my phone, so that I can shop on the go.

#### Acceptance Criteria

1. THE MineTeh_System SHALL implement Responsive_Design using mobile-first approach
2. THE MineTeh_System SHALL use viewport meta tag with width=device-width
3. THE MineTeh_System SHALL ensure all interactive elements are at least 44x44 pixels for touch targets
4. THE MineTeh_System SHALL use responsive images with srcset for different screen sizes
5. THE MineTeh_System SHALL ensure text is readable without zooming (minimum 16px font size)
6. THE MineTeh_System SHALL test on devices with screen widths: 320px, 375px, 768px, 1024px, 1920px
7. THE MineTeh_System SHALL ensure horizontal scrolling is never required
8. THE MineTeh_System SHALL optimize forms for mobile with appropriate input types
9. THE MineTeh_System SHALL implement mobile-friendly navigation with hamburger menu
10. THE MineTeh_System SHALL ensure Carousel works with touch swipe gestures
11. THE MineTeh_System SHALL display mobile-optimized image gallery with pinch-to-zoom


### Requirement 24: Accessibility Compliance

**User Story:** As a user with disabilities, I want the website to be accessible with assistive technologies, so that I can use all features independently.

#### Acceptance Criteria

1. THE MineTeh_System SHALL provide alt text for all images describing their content
2. THE MineTeh_System SHALL use semantic HTML elements (header, nav, main, article, footer)
3. THE MineTeh_System SHALL ensure all interactive elements are keyboard accessible
4. THE MineTeh_System SHALL provide visible focus indicators for keyboard navigation
5. THE MineTeh_System SHALL use ARIA labels for icon buttons without text
6. THE MineTeh_System SHALL ensure color contrast ratio meets WCAG AA standards (4.5:1 for text)
7. THE MineTeh_System SHALL provide skip navigation link for keyboard users
8. THE MineTeh_System SHALL ensure form inputs have associated labels
9. THE MineTeh_System SHALL provide error messages that are announced to screen readers
10. THE MineTeh_System SHALL ensure modal dialogs trap focus and can be closed with Escape key
11. THE MineTeh_System SHALL test with screen readers (NVDA, JAWS, or VoiceOver)

### Requirement 25: Admin Content Moderation Tools

**User Story:** As an administrator, I want tools to moderate user content, so that I can maintain platform quality and safety.

#### Acceptance Criteria

1. THE Content_Moderator SHALL display flagged listings in admin panel with flag reason
2. THE Content_Moderator SHALL allow admins to approve, reject, or remove flagged listings
3. THE Content_Moderator SHALL allow admins to suspend user accounts with reason
4. THE Content_Moderator SHALL allow admins to view user report history
5. THE Content_Moderator SHALL send email notifications to users when content is removed
6. THE Content_Moderator SHALL allow admins to add notes to user accounts
7. THE Content_Moderator SHALL display recent user activity (listings, bids, messages)
8. THE Content_Moderator SHALL allow admins to search users by email, name, or ID
9. THE Content_Moderator SHALL implement bulk actions for managing multiple items
10. THE Content_Moderator SHALL log all moderation actions to Audit_Log


### Requirement 26: Notification System Enhancements

**User Story:** As a user, I want timely notifications for important events, so that I don't miss opportunities or updates.

#### Acceptance Criteria

1. WHEN a user receives a bid on their listing, THE Notification_System SHALL create a notification
2. WHEN a user is outbid, THE Notification_System SHALL create a notification
3. WHEN an auction ends, THE Notification_System SHALL notify the winner and seller
4. WHEN an order status changes, THE Notification_System SHALL notify the buyer
5. WHEN a message is received, THE Notification_System SHALL create a notification
6. WHEN a review is posted, THE Notification_System SHALL notify the seller
7. THE Notification_System SHALL display unread notification count in header badge
8. THE Notification_System SHALL mark notifications as read when clicked
9. THE Notification_System SHALL allow users to mark all notifications as read
10. THE Notification_System SHALL allow users to configure notification preferences
11. THE Notification_System SHALL send email notifications for critical events (order placed, payment received)
12. THE Notification_System SHALL implement real-time notifications using polling or WebSockets

### Requirement 27: Delivery Tracking and Proof of Delivery

**User Story:** As a buyer, I want to track my order delivery, so that I know when to expect my purchase.

#### Acceptance Criteria

1. WHEN an order is shipped, THE MineTeh_System SHALL assign a Tracking_Number
2. THE MineTeh_System SHALL display delivery status (pending, assigned, in_transit, delivered)
3. THE MineTeh_System SHALL allow buyers to view Rider contact information
4. THE MineTeh_System SHALL display estimated delivery time based on distance
5. WHEN delivery is completed, THE Rider SHALL upload Proof_Of_Delivery (photo or signature)
6. THE MineTeh_System SHALL display Proof_Of_Delivery to buyer and seller
7. THE MineTeh_System SHALL update order status to "delivered" when proof is uploaded
8. THE MineTeh_System SHALL send notification to buyer when order is out for delivery
9. THE MineTeh_System SHALL allow buyers to contact rider through in-app messaging
10. THE MineTeh_System SHALL track delivery time and calculate rider performance metrics


### Requirement 28: Automated Delivery Assignment

**User Story:** As an administrator, I want automatic rider assignment, so that orders are fulfilled efficiently without manual intervention.

#### Acceptance Criteria

1. WHEN an order is placed, THE Delivery_Assignment SHALL find available riders within 10km radius
2. THE Delivery_Assignment SHALL prioritize riders with highest rating and lowest current workload
3. THE Delivery_Assignment SHALL assign order to selected rider automatically
4. THE Delivery_Assignment SHALL notify assigned rider via notification and email
5. THE Delivery_Assignment SHALL allow rider to accept or reject assignment within 10 minutes
6. IF rider rejects or doesn't respond, THEN THE Delivery_Assignment SHALL assign to next available rider
7. THE Delivery_Assignment SHALL consider rider working hours when assigning orders
8. THE Delivery_Assignment SHALL limit maximum concurrent deliveries per rider to 5
9. THE Delivery_Assignment SHALL calculate estimated delivery time based on distance and traffic
10. THE Delivery_Assignment SHALL update order status to "assigned" when rider accepts

### Requirement 29: Location-Based Features

**User Story:** As a buyer, I want to search for items near my location, so that I can reduce shipping costs and time.

#### Acceptance Criteria

1. THE MineTeh_System SHALL allow users to set their location using Autocomplete with Philippine cities
2. THE MineTeh_System SHALL store user location as city name and Geolocation coordinates
3. THE MineTeh_System SHALL allow filtering search results by distance (5km, 10km, 25km, 50km)
4. THE MineTeh_System SHALL display distance from user location on each listing
5. THE MineTeh_System SHALL calculate shipping cost based on distance between buyer and seller
6. THE MineTeh_System SHALL sort search results by distance when location filter is active
7. THE MineTeh_System SHALL use Nominatim API as fallback when city is not in local database
8. THE MineTeh_System SHALL cache Geolocation results to reduce API calls
9. THE MineTeh_System SHALL display map showing seller location on listing details page
10. THE MineTeh_System SHALL allow sellers to hide exact address and show only city/region


### Requirement 30: User Experience Improvements

**User Story:** As a user, I want intuitive navigation and clear feedback, so that I can complete tasks efficiently.

#### Acceptance Criteria

1. THE MineTeh_System SHALL display Breadcrumb navigation on all pages showing current location
2. THE MineTeh_System SHALL display Loading_Indicator for all asynchronous operations
3. THE MineTeh_System SHALL display Confirmation_Dialog before destructive actions (delete listing, cancel order)
4. THE MineTeh_System SHALL display success messages after completing actions (listing created, bid placed)
5. THE MineTeh_System SHALL display inline validation errors on form fields as user types
6. THE MineTeh_System SHALL disable submit buttons during form submission to prevent double-submission
7. THE MineTeh_System SHALL implement Debounce on search input with 300ms delay
8. THE MineTeh_System SHALL implement Throttle on scroll events with 100ms delay
9. THE MineTeh_System SHALL display empty state messages with helpful actions when lists are empty
10. THE MineTeh_System SHALL implement smooth scrolling for anchor links
11. THE MineTeh_System SHALL display tooltips on hover for icon buttons
12. THE MineTeh_System SHALL implement keyboard shortcuts for common actions (Ctrl+S to save)

### Requirement 31: Image Carousel Enhancement

**User Story:** As a buyer, I want to view listing images in a beautiful carousel, so that I can examine products thoroughly.

#### Acceptance Criteria

1. THE Carousel SHALL display all listing images with smooth transitions
2. THE Carousel SHALL provide previous and next navigation buttons
3. THE Carousel SHALL display Thumbnail strip below main image for quick navigation
4. THE Carousel SHALL highlight active thumbnail with border or opacity change
5. THE Carousel SHALL support keyboard navigation (arrow keys, Escape)
6. THE Carousel SHALL support touch swipe gestures on mobile devices
7. THE Carousel SHALL provide Fullscreen_Mode button for enlarged view
8. WHEN in fullscreen mode, THE Carousel SHALL display close button and navigation controls
9. THE Carousel SHALL display image counter showing current position (e.g., "3 / 5")
10. THE Carousel SHALL preload adjacent images for instant navigation
11. THE Carousel SHALL display zoom functionality on hover or pinch gesture


### Requirement 32: Input Validation and Sanitization

**User Story:** As a developer, I want comprehensive input validation, so that invalid data is rejected before processing.

#### Acceptance Criteria

1. THE MineTeh_System SHALL validate all form inputs on both client-side and server-side
2. THE MineTeh_System SHALL validate email format using filter_var with FILTER_VALIDATE_EMAIL
3. THE MineTeh_System SHALL validate phone numbers using regex for Philippine format
4. THE MineTeh_System SHALL validate price inputs as positive numbers with maximum 2 decimal places
5. THE MineTeh_System SHALL validate listing titles between 5 and 100 characters
6. THE MineTeh_System SHALL validate listing descriptions between 20 and 5000 characters
7. THE MineTeh_System SHALL validate URLs using filter_var with FILTER_VALIDATE_URL
8. THE MineTeh_System SHALL sanitize text inputs using trim() and strip_tags()
9. THE MineTeh_System SHALL validate date inputs are in valid format and not in the past
10. THE MineTeh_System SHALL validate auction end time is at least 1 hour in the future
11. THE MineTeh_System SHALL return specific error messages for each validation failure
12. THE MineTeh_System SHALL preserve user input when validation fails for correction

### Requirement 33: Auction System Improvements

**User Story:** As a seller, I want reliable auction functionality, so that my items sell at fair market value.

#### Acceptance Criteria

1. THE MineTeh_System SHALL allow sellers to set starting price and reserve price for auctions
2. THE MineTeh_System SHALL allow sellers to set auction duration (1, 3, 5, 7, or 10 days)
3. THE MineTeh_System SHALL validate bids are higher than current highest bid
4. THE MineTeh_System SHALL implement minimum bid increment (5% of current price or ₱10, whichever is higher)
5. THE MineTeh_System SHALL prevent sellers from bidding on their own auctions
6. THE MineTeh_System SHALL extend auction by 5 minutes if bid is placed in last 5 minutes
7. WHEN auction ends, THE MineTeh_System SHALL automatically create order for highest bidder
8. THE MineTeh_System SHALL notify all bidders when auction ends
9. THE MineTeh_System SHALL display bid history with bidder usernames (partially masked)
10. THE MineTeh_System SHALL display time remaining with countdown timer
11. THE MineTeh_System SHALL allow sellers to cancel auction only if no bids have been placed


### Requirement 34: Messaging System Enhancements

**User Story:** As a user, I want to communicate with buyers and sellers easily, so that I can negotiate and clarify details.

#### Acceptance Criteria

1. THE MineTeh_System SHALL display conversation list sorted by most recent message
2. THE MineTeh_System SHALL display unread message count per conversation
3. THE MineTeh_System SHALL mark messages as read when conversation is opened
4. THE MineTeh_System SHALL display message timestamps in relative format (e.g., "2 hours ago")
5. THE MineTeh_System SHALL allow users to send messages up to 1000 characters
6. THE MineTeh_System SHALL allow users to attach images to messages (max 3 images)
7. THE MineTeh_System SHALL display typing indicator when other user is typing
8. THE MineTeh_System SHALL implement real-time message delivery using polling every 5 seconds
9. THE MineTeh_System SHALL allow users to block other users from messaging them
10. THE MineTeh_System SHALL display listing context in conversation header
11. THE MineTeh_System SHALL allow users to report inappropriate messages to admins

### Requirement 35: Cart and Checkout Improvements

**User Story:** As a buyer, I want a smooth checkout process, so that I can complete purchases quickly.

#### Acceptance Criteria

1. THE MineTeh_System SHALL display cart item count in header badge
2. THE MineTeh_System SHALL allow users to update quantities in cart
3. THE MineTeh_System SHALL display subtotal, shipping cost, and total in cart
4. THE MineTeh_System SHALL validate item availability before checkout
5. THE MineTeh_System SHALL remove out-of-stock items from cart with notification
6. THE MineTeh_System SHALL allow users to save multiple shipping addresses
7. THE MineTeh_System SHALL allow users to select shipping address during checkout
8. THE MineTeh_System SHALL calculate shipping cost based on distance and item weight
9. THE MineTeh_System SHALL display order summary before payment
10. THE MineTeh_System SHALL create order records for each seller separately (multi-vendor support)
11. THE MineTeh_System SHALL send order confirmation email with order details
12. THE MineTeh_System SHALL clear cart after successful checkout


### Requirement 36: Admin Dashboard and Monitoring

**User Story:** As an administrator, I want a comprehensive dashboard, so that I can monitor platform health and activity.

#### Acceptance Criteria

1. THE Admin_Panel SHALL display total users, active listings, and orders in the last 30 days
2. THE Admin_Panel SHALL display revenue chart showing daily sales for the last 30 days
3. THE Admin_Panel SHALL display top sellers by revenue and transaction count
4. THE Admin_Panel SHALL display recent user registrations with verification status
5. THE Admin_Panel SHALL display flagged content requiring moderation
6. THE Admin_Panel SHALL display system health metrics (database connections, error rate, response time)
7. THE Admin_Panel SHALL display active user count and concurrent sessions
8. THE Admin_Panel SHALL allow filtering dashboard data by date range
9. THE Admin_Panel SHALL export reports as CSV or PDF
10. THE Admin_Panel SHALL display pending disputes requiring resolution
11. THE Monitoring_Dashboard SHALL alert admins when error rate exceeds 5% of requests
12. THE Monitoring_Dashboard SHALL alert admins when database response time exceeds 2 seconds

### Requirement 37: Security Audit Logging

**User Story:** As a security administrator, I want comprehensive audit logs, so that I can investigate security incidents.

#### Acceptance Criteria

1. THE Audit_Log SHALL record all login attempts with username, IP address, user agent, and timestamp
2. THE Audit_Log SHALL record all failed authentication attempts with reason
3. THE Audit_Log SHALL record all password changes with IP address
4. THE Audit_Log SHALL record all email changes with old and new email
5. THE Audit_Log SHALL record all admin actions (user suspension, content removal)
6. THE Audit_Log SHALL record all payment transactions with amount and status
7. THE Audit_Log SHALL record all API requests with endpoint, parameters, and response code
8. THE Audit_Log SHALL record all file uploads with filename, size, and MIME type
9. THE Audit_Log SHALL allow admins to search logs by user, IP address, action type, or date range
10. THE Audit_Log SHALL retain logs for minimum 90 days
11. THE Audit_Log SHALL export logs in JSON format for external analysis


### Requirement 38: Database Backup and Recovery

**User Story:** As a platform administrator, I want automated database backups, so that data can be recovered in case of failure.

#### Acceptance Criteria

1. THE MineTeh_System SHALL create full database backup daily at 2:00 AM
2. THE MineTeh_System SHALL create incremental backups every 6 hours
3. THE MineTeh_System SHALL store backups in secure off-site location
4. THE MineTeh_System SHALL encrypt backups using AES-256 encryption
5. THE MineTeh_System SHALL retain daily backups for 30 days
6. THE MineTeh_System SHALL retain weekly backups for 90 days
7. THE MineTeh_System SHALL test backup restoration monthly
8. THE MineTeh_System SHALL provide Rollback_Script for reverting to previous backup
9. THE MineTeh_System SHALL alert admins if backup fails
10. THE MineTeh_System SHALL document backup restoration procedure

### Requirement 39: Environment Configuration Management

**User Story:** As a developer, I want separate configurations for development and production, so that testing doesn't affect live data.

#### Acceptance Criteria

1. THE MineTeh_System SHALL use environment variables for sensitive configuration (database credentials, API keys)
2. THE MineTeh_System SHALL never commit credentials to version control
3. THE MineTeh_System SHALL provide .env.example file with all required variables
4. THE MineTeh_System SHALL validate all required environment variables are set on startup
5. THE MineTeh_System SHALL use different database instances for development, staging, and production
6. THE MineTeh_System SHALL disable debug mode in production environment
7. THE MineTeh_System SHALL use different payment gateway credentials for testing and production
8. THE MineTeh_System SHALL display environment indicator in admin panel (dev, staging, prod)
9. THE MineTeh_System SHALL implement feature flags for gradual rollout of new features
10. THE MineTeh_System SHALL document all configuration options in README


### Requirement 40: Testing and Quality Assurance

**User Story:** As a developer, I want automated tests, so that regressions are caught before deployment.

#### Acceptance Criteria

1. THE MineTeh_System SHALL implement unit tests for all critical business logic functions
2. THE MineTeh_System SHALL implement integration tests for API endpoints
3. THE MineTeh_System SHALL implement end-to-end tests for critical user flows (registration, checkout, bidding)
4. THE MineTeh_System SHALL achieve minimum 70% code coverage for PHP backend
5. THE MineTeh_System SHALL run all tests automatically before deployment
6. THE MineTeh_System SHALL prevent deployment if any tests fail
7. THE MineTeh_System SHALL implement database seeding for test data
8. THE MineTeh_System SHALL use separate test database that is reset between test runs
9. THE MineTeh_System SHALL document how to run tests locally
10. THE MineTeh_System SHALL implement performance tests ensuring page load under 2 seconds

### Requirement 41: Deployment and CI/CD Pipeline

**User Story:** As a developer, I want automated deployment, so that updates can be released quickly and safely.

#### Acceptance Criteria

1. THE MineTeh_System SHALL use version control (Git) for all code
2. THE MineTeh_System SHALL implement automated deployment pipeline
3. THE MineTeh_System SHALL run tests automatically on every commit
4. THE MineTeh_System SHALL deploy to staging environment automatically on merge to develop branch
5. THE MineTeh_System SHALL require manual approval before production deployment
6. THE MineTeh_System SHALL implement zero-downtime deployment strategy
7. THE MineTeh_System SHALL provide rollback capability to previous version
8. THE MineTeh_System SHALL run Database_Migration scripts automatically during deployment
9. THE MineTeh_System SHALL send deployment notifications to team
10. THE MineTeh_System SHALL implement Health_Check endpoint for monitoring deployment success


### Requirement 42: Documentation and Knowledge Base

**User Story:** As a new developer, I want comprehensive documentation, so that I can understand and contribute to the codebase.

#### Acceptance Criteria

1. THE MineTeh_System SHALL provide README with project overview, setup instructions, and architecture
2. THE MineTeh_System SHALL document all API endpoints with request/response examples
3. THE MineTeh_System SHALL document database schema with entity relationship diagram
4. THE MineTeh_System SHALL document all environment variables and configuration options
5. THE MineTeh_System SHALL provide code comments for complex business logic
6. THE MineTeh_System SHALL document deployment process step-by-step
7. THE MineTeh_System SHALL document troubleshooting guide for common issues
8. THE MineTeh_System SHALL provide user guide for end users
9. THE MineTeh_System SHALL provide admin guide for platform administrators
10. THE MineTeh_System SHALL keep documentation up-to-date with code changes

## Summary

This requirements document specifies 42 comprehensive requirements covering security enhancements, missing features, database integrity, API improvements, performance optimization, user experience, and operational excellence. Each requirement includes detailed acceptance criteria following EARS patterns and INCOSE quality rules to ensure testability and clarity.

The requirements address the transformation of MineTeh from 58% complete to a production-ready marketplace platform with enterprise-grade security, reliability, and user experience.
