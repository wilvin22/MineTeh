<?php
session_start();

// Set timezone to match your local timezone
date_default_timezone_set('Asia/Manila'); // Change this to your timezone

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Check if user is restricted and if restriction has expired
$user_status = isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'active';
$restriction_notice = null;
$restriction_error = isset($_GET['error']) && $_GET['error'] === 'restricted';

// Always fetch fresh user status from database to catch admin changes
$user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);

if ($user && is_array($user)) {
    $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
    $_SESSION['user_status'] = $user_status; // Update session with fresh status
    
    if ($user_status === 'restricted' || $restriction_error) {
        $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
        
        if ($restriction_until && strtotime($restriction_until) <= time()) {
            // Restriction expired, reactivate user
            $supabase->update('accounts', [
                'user_status' => 'active',
                'restriction_until' => null,
                'status_reason' => null
            ], ['account_id' => $_SESSION['user_id']]);
            $_SESSION['user_status'] = 'active';
            $user_status = 'active';
        } else {
            $reason = isset($user['status_reason']) ? $user['status_reason'] : 'No reason provided';
            $until_text = $restriction_until ? ' until ' . date('F j, Y g:i A', strtotime($restriction_until)) : ' permanently';
            $restriction_notice = [
                'reason' => $reason,
                'until' => $until_text,
                'from_action' => $restriction_error // Flag to show it came from a blocked action
            ];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    background: #ffffff;
}

#main-content {
    flex-grow: 1;
    padding: 30px;
}

/* Search Section Styles */
.search-section {
    margin-bottom: 25px;
}

.search-container {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
}

.search-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #999;
    pointer-events: none;
    z-index: 2;
}

.search-input {
    width: 100%;
    padding: 18px 60px 18px 55px;
    border: 2px solid #e9ecef;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 500;
    color: #333;
    background: white;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.search-input:focus {
    outline: none;
    border-color: #945a9b;
    box-shadow: 0 4px 16px rgba(148, 90, 155, 0.15);
}

.search-input::placeholder {
    color: #999;
}

.clear-search-btn {
    position: absolute;
    right: 20px;
    top: 50%;
    transform: translateY(-50%);
    background: #e9ecef;
    border: none;
    border-radius: 50%;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    color: #666;
    transition: all 0.2s ease;
    z-index: 2;
}

.clear-search-btn:hover {
    background: #945a9b;
    color: white;
    transform: translateY(-50%) scale(1.1);
}

.search-highlight {
    background: linear-gradient(135deg, #fff3cd, #ffe69c);
    border: 2px solid #945a9b !important;
    box-shadow: 0 6px 20px rgba(148, 90, 155, 0.25) !important;
}

.filter-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
    flex-wrap: wrap;
}

.filter-tabs {
    display: flex;
    gap: 12px;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 15px;
    font-weight: 600;
    color: #666;
    cursor: pointer;
    transition: all 0.3s ease;
}

.filter-tab:hover {
    border-color: #945a9b;
    color: #945a9b;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(148, 90, 155, 0.2);
}

.filter-tab.active {
    background: linear-gradient(135deg, #945a9b, #6a406e);
    border-color: #945a9b;
    color: white;
    box-shadow: 0 4px 12px rgba(148, 90, 155, 0.3);
}

.filter-tab .tab-icon {
    font-size: 18px;
}

.category-filter {
    display: flex;
    align-items: center;
    gap: 12px;
}

.category-filter label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 15px;
    font-weight: 600;
    color: #666;
}

.filter-icon {
    font-size: 18px;
}

.category-dropdown {
    padding: 10px 16px;
    border: 2px solid #e9ecef;
    border-radius: 20px;
    font-size: 14px;
    font-weight: 500;
    color: #333;
    background: white;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 200px;
}

.category-dropdown:hover {
    border-color: #945a9b;
}

.category-dropdown:focus {
    outline: none;
    border-color: #945a9b;
    box-shadow: 0 0 0 3px rgba(148, 90, 155, 0.1);
}

#listings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.listing-card {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    cursor: pointer;
    position: relative;
}

.listing-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.listing-image {
    position: relative;
}

.listing-image img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    background: #e9ecef;
}

.listing-content {
    padding: 12px;
}

.listing-price {
    font-size: 18px;
    font-weight: bold;
    color: #945a9b;
    margin-bottom: 4px;
}

.listing-title {
    font-size: 14px;
    font-weight: bold;
    color: #333;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.listing-meta {
    font-size: 12px;
    color: #666;
    margin-top: 6px;
    display: flex;
    justify-content: space-between;
}

.listing-badge {
    font-size: 11px;
    padding: 3px 8px;
    border-radius: 12px;
    background: #e9ecef;
    font-weight: bold;
}

.listing-badge.bid {
    background: #fff3cd;
    color: #856404;
}

.listing-badge.fixed {
    background: #d1e7dd;
    color: #0f5132;
}

.listing-badge.your-listing {
    background: #945a9b;
    color: white;
}

/* Auction Timer Styles */
.auction-timer {
    margin-top: 8px;
    padding: 6px 8px;
    background: linear-gradient(135deg, #945a9b, #6a406e);
    color: white;
    border-radius: 6px;
    font-size: 11px;
    font-weight: bold;
    text-align: center;
    box-shadow: 0 2px 4px rgba(148, 90, 155, 0.3);
}

.auction-timer.ending-soon {
    background: linear-gradient(135deg, #ff4757, #c44569);
    animation: pulse-timer 1s infinite;
}

.auction-timer.ended {
    background: #6c757d;
    color: #fff;
}

@keyframes pulse-timer {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 4px 8px rgba(255, 107, 107, 0.5);
    }
}

.listing-owner-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: linear-gradient(135deg, #945a9b, #6a406e);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    box-shadow: 0 2px 8px rgba(148, 90, 155, 0.3);
    z-index: 5;
}

.edit-listing-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255,255,255,0.9);
    border: none;
    cursor: pointer;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 11px;
    font-weight: bold;
    color: #945a9b;
    transition: all 0.2s ease;
    z-index: 5;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.edit-listing-btn:hover {
    background: #945a9b;
    color: white;
    transform: translateY(-2px);
}

.favorite-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(255,255,255,0.9);
    border: none;
    font-size: 20px;
    cursor: pointer;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 5;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.favorite-btn:hover {
    transform: scale(1.1);
    background: rgba(255,255,255,1);
}

.favorite-btn.favorited::before {
    content: '❤️';
}

.favorite-btn:not(.favorited)::before {
    content: '🤍';
}

/* Mobile Responsive Styles */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }

    #main-content {
        padding: 15px;
    }

    .search-section {
        margin-bottom: 20px;
    }

    .search-input {
        padding: 15px 50px 15px 45px;
        font-size: 15px;
    }

    .search-icon {
        left: 15px;
        font-size: 18px;
    }

    .clear-search-btn {
        right: 15px;
        width: 26px;
        height: 26px;
        font-size: 13px;
    }

    .filter-section {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }

    .filter-tabs {
        flex-wrap: wrap;
        justify-content: center;
    }

    .filter-tab {
        padding: 10px 16px;
        font-size: 14px;
        flex: 1;
        min-width: 100px;
        justify-content: center;
    }

    .category-filter {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }

    .category-filter label {
        justify-content: center;
    }

    .category-dropdown {
        width: 100%;
        min-width: auto;
    }

    #listings-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 12px;
    }

    .listing-card {
        border-radius: 8px;
    }

    .listing-image img {
        height: 140px;
    }

    .listing-content {
        padding: 10px;
    }

    .listing-price {
        font-size: 16px;
    }

    .listing-title {
        font-size: 13px;
    }

    .listing-meta {
        font-size: 11px;
    }

    .listing-owner-badge,
    .edit-listing-btn {
        font-size: 10px;
        padding: 4px 8px;
    }

    .favorite-btn {
        width: 28px;
        height: 28px;
        font-size: 18px;
    }

    .auction-timer {
        font-size: 10px;
        padding: 4px 6px;
    }
}

@media (max-width: 480px) {
    #main-content {
        padding: 10px;
    }

    .filter-tab {
        padding: 8px 12px;
        font-size: 13px;
    }

    .filter-tab .tab-icon {
        font-size: 16px;
    }

    #listings-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .listing-image img {
        height: 120px;
    }

    .listing-content {
        padding: 8px;
    }

    .listing-price {
        font-size: 14px;
    }

    .listing-title {
        font-size: 12px;
    }

    .listing-meta {
        font-size: 10px;
        flex-direction: column;
        gap: 4px;
    }

    .listing-badge {
        font-size: 10px;
        padding: 2px 6px;
    }
}

</style>

</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div id="main-content" class="main-wrapper">
        <?php if ($restriction_notice): ?>
        <div style="background: linear-gradient(135deg, #ff6b6b, #ee5a6f); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="font-size: 40px;">⚠️</div>
                <div style="flex: 1;">
                    <h3 style="margin: 0 0 10px 0; font-size: 20px;">
                        <?php echo $restriction_notice['from_action'] ? 'Action Blocked - Account Restricted' : 'Your Account is Restricted'; ?>
                    </h3>
                    <p style="margin: 0 0 8px 0; opacity: 0.95;">
                        <?php if ($restriction_notice['from_action']): ?>
                            You cannot place bids or create listings<?php echo $restriction_notice['until']; ?>.
                        <?php else: ?>
                            You cannot create listings or place bids<?php echo $restriction_notice['until']; ?>.
                        <?php endif; ?>
                    </p>
                    <div style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 6px; font-size: 14px;">
                        <strong>Reason:</strong> <?php echo htmlspecialchars($restriction_notice['reason']); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Search Bar -->
        <div class="search-section">
            <div class="search-container">
                <div class="search-icon">🔍</div>
                <input type="text" id="search-input" class="search-input" placeholder="Search for items, categories, or locations...">
                <button id="clear-search" class="clear-search-btn" style="display: none;">✕</button>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <!-- Listing Type Tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">
                    <span class="tab-icon">🏷️</span>
                    All Items
                </button>
                <button class="filter-tab" data-filter="BID">
                    <span class="tab-icon">💰</span>
                    Auctions
                </button>
                <button class="filter-tab" data-filter="FIXED">
                    <span class="tab-icon">🛒</span>
                    Buy Now
                </button>
            </div>

            <!-- Category Filter -->
            <div class="category-filter">
                <label for="category-select">
                    <span class="filter-icon">📂</span>
                    Category:
                </label>
                <select id="category-select" class="category-dropdown">
                    <option value="all">All Categories</option>
                    <?php
                    // Get all categories
                    $categories = $supabase->select('categories', '*', []);
                    if (!empty($categories)) {
                        foreach ($categories as $cat) {
                            echo '<option value="' . $cat['category_id'] . '">' . 
                                 htmlspecialchars($cat['category_icon'] . ' ' . $cat['category_name']) . 
                                 '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>

        <div id="listings-grid">
            <?php
                // Get only active listings from Supabase (exclude disabled/inactive)
                $listings = $supabase->customQuery('listings', '*', 'status=eq.active');

                if (empty($listings)) {
                    echo "<div style='grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;'><div style='font-size: 48px; margin-bottom: 16px;'>📦</div><h2>No listings found</h2></div>";
                } else {
                    // Get all listing IDs
                    $listingIds = array_column($listings, 'id');
                    
                    // Batch fetch all images for these listings
                    $allImages = [];
                    if (!empty($listingIds)) {
                        $imageQuery = 'listing_id=in.(' . implode(',', $listingIds) . ')&order=image_id.asc';
                        $images = $supabase->customQuery('listing_images', 'listing_id,image_path', $imageQuery);
                        
                        if ($images !== false && !empty($images)) {
                            foreach ($images as $image) {
                                $lid = $image['listing_id'];
                                if (!isset($allImages[$lid])) {
                                    $allImages[$lid] = $image['image_path'];
                                }
                            }
                        }
                    }
                    
                    // Batch fetch favorites for current user
                    $userFavorites = [];
                    if (isset($_SESSION['user_id'])) {
                        $favQuery = 'user_id=eq.' . $_SESSION['user_id'] . '&listing_id=in.(' . implode(',', $listingIds) . ')';
                        $favorites = $supabase->customQuery('favorites', 'listing_id', $favQuery);
                        if ($favorites !== false && !empty($favorites)) {
                            $userFavorites = array_column($favorites, 'listing_id');
                        }
                    }
                    
                    foreach ($listings as $row) {
                        $listing_id   = (int)$row['id'];
                        $seller_id    = (int)$row['seller_id'];
                        $title        = htmlspecialchars($row['title']);
                        $price        = $row['price'];
                        $location     = htmlspecialchars($row['location']);
                        $listing_type = $row['listing_type'];
                        
                        // Check if this is the user's listing
                        $is_own_listing = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $seller_id;

                        // Get image from batch results
                        $listing_image = isset($allImages[$listing_id]) ? getImageUrl($allImages[$listing_id]) : getImageUrl('');

                        // Check if user has favorited this listing from batch results
                        $is_favorited = in_array($listing_id, $userFavorites);

                        $badgeClass = $listing_type === 'BID' ? 'bid' : 'fixed';
                        
                        // Add countdown timer for auction items
                        $timerHtml = '';
                        if ($listing_type === 'BID' && !empty($row['end_time'])) {
                            $timerHtml = "<div class='auction-timer' data-end-time='" . $row['end_time'] . "'></div>";
                        }

                        echo "
                        <div class='listing-card' data-id='$listing_id' data-type='$listing_type' data-category='" . ($row['category_id'] ?? '') . "'>
                            <div class='listing-image'>
                                " . ($is_own_listing ? "<div class='listing-owner-badge'>👤 Your Listing</div>" : "") . "
                                " . ($is_own_listing ? "<button class='edit-listing-btn' onclick='editListing(event, $listing_id)'>✏️ Edit</button>" : "") . "
                                " . (!$is_own_listing ? "<button class='favorite-btn " . ($is_favorited ? 'favorited' : '') . "' data-listing-id='$listing_id' onclick='toggleFavorite(event, $listing_id)'></button>" : "") . "
                                <img src='$listing_image' alt='Listing Image'>
                            </div>
                            <div class='listing-content'>
                                <div class='listing-price'>₱" . number_format($price, 2) . "</div>
                                <div class='listing-title'>$title</div>
                                <div class='listing-meta'>
                                    <span>$location</span>
                                    <span class='listing-badge $badgeClass'>$listing_type</span>
                                </div>
                                $timerHtml
                            </div>
                        </div>
                        ";
                    }
                }
                ?>
        </div>
    </div>

    <script>
        // Filter functionality
        const filterTabs = document.querySelectorAll('.filter-tab');
        const categorySelect = document.getElementById('category-select');
        const listingCards = document.querySelectorAll('.listing-card');
        const searchInput = document.getElementById('search-input');
        const clearSearchBtn = document.getElementById('clear-search');

        let currentTypeFilter = 'all';
        let currentCategoryFilter = 'all';
        let currentSearchQuery = '';

        function applyFilters() {
            let visibleCount = 0;
            const searchLower = currentSearchQuery.toLowerCase().trim();

            listingCards.forEach(card => {
                const cardType = card.dataset.type;
                const cardCategory = card.dataset.category;
                
                // Get card text content for search
                const title = card.querySelector('.listing-title')?.textContent.toLowerCase() || '';
                const location = card.querySelector('.listing-meta span:first-child')?.textContent.toLowerCase() || '';
                const price = card.querySelector('.listing-price')?.textContent.toLowerCase() || '';

                // Check type filter
                const typeMatch = currentTypeFilter === 'all' || cardType === currentTypeFilter;
                
                // Check category filter
                const categoryMatch = currentCategoryFilter === 'all' || cardCategory === currentCategoryFilter;
                
                // Check search query
                const searchMatch = searchLower === '' || 
                                  title.includes(searchLower) || 
                                  location.includes(searchLower) ||
                                  price.includes(searchLower);

                // Show card only if all filters match
                if (typeMatch && categoryMatch && searchMatch) {
                    card.style.display = 'block';
                    
                    // Highlight matching cards when searching
                    if (searchLower !== '') {
                        card.classList.add('search-highlight');
                    } else {
                        card.classList.remove('search-highlight');
                    }
                    
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                    card.classList.remove('search-highlight');
                }
            });

            // Handle empty state
            const grid = document.getElementById('listings-grid');
            const existingEmpty = grid.querySelector('.empty-state');
            if (existingEmpty) {
                existingEmpty.remove();
            }

            if (visibleCount === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.style.cssText = 'grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #999;';
                
                let emptyMessage = '<div style="font-size: 48px; margin-bottom: 16px;">🔍</div>';
                if (searchLower !== '') {
                    emptyMessage += `<h2>No results found for "${currentSearchQuery}"</h2><p>Try different keywords or clear your search</p>`;
                } else {
                    emptyMessage += '<h2>No listings found</h2><p>Try selecting different filters</p>';
                }
                
                emptyState.innerHTML = emptyMessage;
                grid.appendChild(emptyState);
            }
        }

        // Search functionality
        searchInput.addEventListener('input', (e) => {
            currentSearchQuery = e.target.value;
            
            // Show/hide clear button
            if (currentSearchQuery.trim() !== '') {
                clearSearchBtn.style.display = 'flex';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            applyFilters();
        });

        // Clear search button
        clearSearchBtn.addEventListener('click', () => {
            searchInput.value = '';
            currentSearchQuery = '';
            clearSearchBtn.style.display = 'none';
            searchInput.focus();
            applyFilters();
        });

        // Allow Enter key to trigger search
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyFilters();
            }
        });

        // Type filter tabs
        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                currentTypeFilter = tab.dataset.filter;
                applyFilters();
            });
        });

        // Category filter dropdown
        categorySelect.addEventListener('change', () => {
            currentCategoryFilter = categorySelect.value;
            applyFilters();
        });

        // Click handler for listing cards
        listingCards.forEach(card => {
            card.addEventListener('click', (e) => {
                // Don't navigate if clicking favorite or edit button
                if (e.target.classList.contains('favorite-btn') || 
                    e.target.classList.contains('edit-listing-btn') ||
                    e.target.closest('.edit-listing-btn')) {
                    return;
                }
                
                const listingId = card.dataset.id;
                console.log('Card clicked, ID:', listingId);
                
                if (!listingId) {
                    console.error('No listing ID found on card');
                    return;
                }
                
                window.location.href = 'listing-details.php?id=' + listingId;
            });
        });

        // Edit listing function
        function editListing(event, listingId) {
            event.stopPropagation(); // Prevent card click
            window.location.href = 'create-listing.php?edit=' + listingId;
        }

        // Favorite toggle function
        function toggleFavorite(event, listingId) {
            event.stopPropagation(); // Prevent card click
            
            const btn = event.currentTarget;
            const isFavorited = btn.classList.contains('favorited');
            
            fetch('../actions/favorite-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: isFavorited ? 'remove' : 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle the favorited class
                    btn.classList.toggle('favorited');
                } else {
                    alert('Error: ' + (data.message || 'Failed to update favorite'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update favorite');
            });
        }

        // Countdown Timer Functionality
        function updateCountdownTimers() {
            const timers = document.querySelectorAll('.auction-timer');
            
            timers.forEach(timer => {
                const endTime = new Date(timer.dataset.endTime).getTime();
                const now = new Date().getTime();
                const timeLeft = endTime - now;
                
                if (timeLeft <= 0) {
                    timer.textContent = '⏰ Auction Ended';
                    timer.classList.add('ended');
                    return;
                }
                
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                let timeString = '';
                if (days > 0) {
                    timeString = `⏰ ${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    timeString = `⏰ ${hours}h ${minutes}m ${seconds}s`;
                } else {
                    timeString = `⏰ ${minutes}m ${seconds}s`;
                }
                
                timer.textContent = timeString;
                
                // Add ending soon class if less than 1 hour left
                if (timeLeft < 3600000) { // 1 hour in milliseconds
                    timer.classList.add('ending-soon');
                } else {
                    timer.classList.remove('ending-soon');
                }
            });
        }
        
        // Update timers immediately and then every second
        updateCountdownTimers();
        setInterval(updateCountdownTimers, 1000);
    </script>
</body>

</html>