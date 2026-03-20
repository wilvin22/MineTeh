<?php
session_start();

// Block admin access to user pages
require_once __DIR__ . '/../includes/block_admin_access.php';

require_once '../database/supabase.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get search parameters
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$listing_type = isset($_GET['type']) ? trim($_GET['type']) : '';

// Get categories for filter
$categories = $supabase->customQuery('categories', '*', 'order=name.asc');
if (!$categories) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
        }

        .search-header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-input {
            flex: 1;
            padding: 15px 20px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-size: 16px;
        }

        .search-input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .search-btn {
            padding: 15px 40px;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8C42 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
        }

        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .filter-select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-select:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .results-count {
            font-size: 18px;
            color: #333;
            font-weight: 600;
        }

        .sort-select {
            padding: 10px 15px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }

        .listing-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
        }

        .listing-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .listing-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }

        .listing-content {
            padding: 15px;
        }

        .listing-title {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .listing-price {
            font-size: 20px;
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 8px;
        }

        .listing-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            color: #999;
        }

        .listing-type-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-fixed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .badge-bid {
            background: #fff3cd;
            color: #856404;
        }

        .no-results {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
        }

        .no-results-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-results-text {
            font-size: 20px;
            color: #666;
            margin-bottom: 10px;
        }

        .no-results-hint {
            font-size: 14px;
            color: #999;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
            color: #666;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .search-box {
                flex-direction: column;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .listings-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="main-content">
        <div class="search-header">
            <form id="search-form" method="GET" action="">
                <div class="search-box">
                    <input type="text" 
                           name="q" 
                           class="search-input" 
                           placeholder="Search for items..." 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           autofocus>
                    <button type="submit" class="search-btn">🔍 Search</button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select name="category" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['name']); ?>" 
                                        <?php echo $category == $cat['name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Listing Type</label>
                        <select name="type" class="filter-select" onchange="this.form.submit()">
                            <option value="">All Types</option>
                            <option value="FIXED" <?php echo $listing_type == 'FIXED' ? 'selected' : ''; ?>>Fixed Price</option>
                            <option value="BID" <?php echo $listing_type == 'BID' ? 'selected' : ''; ?>>Auction</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Min Price</label>
                        <input type="number" name="min_price" class="filter-select" placeholder="₱0" min="0" step="0.01">
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Max Price</label>
                        <input type="number" name="max_price" class="filter-select" placeholder="No limit" min="0" step="0.01">
                    </div>
                </div>
            </form>
        </div>

        <div class="results-header">
            <div class="results-count" id="results-count">
                <span class="loading">Searching...</span>
            </div>
            <select id="sort-select" class="sort-select">
                <option value="created_at.desc">Newest First</option>
                <option value="created_at.asc">Oldest First</option>
                <option value="price.asc">Price: Low to High</option>
                <option value="price.desc">Price: High to Low</option>
                <option value="title.asc">Title: A to Z</option>
                <option value="title.desc">Title: Z to A</option>
            </select>
        </div>

        <div class="listings-grid" id="listings-container">
            <!-- Listings will be loaded here via JavaScript -->
        </div>
    </div>

    <script>
        const searchForm = document.getElementById('search-form');
        const listingsContainer = document.getElementById('listings-container');
        const resultsCount = document.getElementById('results-count');
        const sortSelect = document.getElementById('sort-select');

        function searchListings() {
            const formData = new FormData(searchForm);
            const params = new URLSearchParams(formData);
            params.append('sort', sortSelect.value);

            fetch('../api/search-listings.php?' + params.toString())
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayListings(data.listings);
                        resultsCount.innerHTML = `Found ${data.count} result${data.count !== 1 ? 's' : ''}${data.query ? ' for "' + data.query + '"' : ''}`;
                    } else {
                        listingsContainer.innerHTML = '<div class="no-results"><div class="no-results-icon">❌</div><div class="no-results-text">Error loading results</div></div>';
                        resultsCount.innerHTML = 'Error';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    listingsContainer.innerHTML = '<div class="no-results"><div class="no-results-icon">❌</div><div class="no-results-text">Error loading results</div></div>';
                });
        }

        function displayListings(listings) {
            if (listings.length === 0) {
                listingsContainer.innerHTML = `
                    <div class="no-results">
                        <div class="no-results-icon">🔍</div>
                        <div class="no-results-text">No results found</div>
                        <div class="no-results-hint">Try adjusting your search or filters</div>
                    </div>
                `;
                return;
            }

            listingsContainer.innerHTML = listings.map(listing => {
                const imagePath = listing.image ? listing.image.replace('../', '') : 'uploads/placeholder.jpg';
                const price = parseFloat(listing.price).toLocaleString('en-PH', {minimumFractionDigits: 2});
                const typeClass = listing.listing_type === 'FIXED' ? 'badge-fixed' : 'badge-bid';
                const typeText = listing.listing_type === 'FIXED' ? 'Fixed Price' : 'Auction';

                return `
                    <div class="listing-card" onclick="window.location.href='listing-details.php?id=${listing.listing_id}'">
                        <img src="../${imagePath}" alt="${listing.title}" class="listing-image" onerror="this.src='../uploads/placeholder.jpg'">
                        <div class="listing-content">
                            <div class="listing-title">${listing.title}</div>
                            <div class="listing-price">₱${price}</div>
                            <div class="listing-meta">
                                <span>${listing.location || 'No location'}</span>
                                <span class="listing-type-badge ${typeClass}">${typeText}</span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Search on page load
        searchListings();

        // Search on sort change
        sortSelect.addEventListener('change', searchListings);

        console.log('Search page loaded');
    </script>
</body>
</html>
