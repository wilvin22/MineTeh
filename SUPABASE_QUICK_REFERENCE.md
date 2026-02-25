# Supabase Quick Reference

## Common MySQL to Supabase Conversions

### 1. Simple SELECT

**MySQL:**
```php
$sql = "SELECT * FROM listings WHERE id='$id'";
$result = mysqli_query($conn, $sql);
$listing = mysqli_fetch_assoc($result);
```

**Supabase:**
```php
$listing = $supabase->select('listings', '*', ['id' => $id], true);
```

---

### 2. SELECT Multiple Rows

**MySQL:**
```php
$sql = "SELECT * FROM listings WHERE status='active'";
$result = mysqli_query($conn, $sql);
$listings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $listings[] = $row;
}
```

**Supabase:**
```php
$listings = $supabase->select('listings', '*', ['status' => 'active']);
```

---

### 3. INSERT

**MySQL:**
```php
$sql = "INSERT INTO listings (title, price, seller_id) 
        VALUES ('$title', '$price', '$seller_id')";
mysqli_query($conn, $sql);
$listing_id = mysqli_insert_id($conn);
```

**Supabase:**
```php
$result = $supabase->insert('listings', [
    'title' => $title,
    'price' => $price,
    'seller_id' => $seller_id
]);
$listing_id = $result[0]['id'];
```

---

### 4. UPDATE

**MySQL:**
```php
$sql = "UPDATE listings SET status='closed' WHERE id='$id'";
mysqli_query($conn, $sql);
```

**Supabase:**
```php
$supabase->update('listings', 
    ['status' => 'closed'],
    ['id' => $id]
);
```

---

### 5. DELETE

**MySQL:**
```php
$sql = "DELETE FROM favorites WHERE user_id='$user_id' AND listing_id='$listing_id'";
mysqli_query($conn, $sql);
```

**Supabase:**
```php
$supabase->delete('favorites', [
    'user_id' => $user_id,
    'listing_id' => $listing_id
]);
```

---

### 6. COUNT

**MySQL:**
```php
$sql = "SELECT COUNT(*) as total FROM listings WHERE status='active'";
$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result);
$count = $row['total'];
```

**Supabase:**
```php
$count = $supabase->count('listings', ['status' => 'active']);
```

---

### 7. OR Conditions

**MySQL:**
```php
$sql = "SELECT * FROM accounts WHERE email='$input' OR username='$input'";
$result = mysqli_query($conn, $sql);
```

**Supabase:**
```php
$user = $supabase->customQuery('accounts', '*', 
    'or=(email.eq.' . urlencode($input) . ',username.eq.' . urlencode($input) . ')'
);
```

---

### 8. ORDER BY and LIMIT

**MySQL:**
```php
$sql = "SELECT * FROM listings ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $sql);
```

**Supabase:**
```php
$listings = $supabase->customQuery('listings', '*', 
    'order=created_at.desc&limit=10'
);
```

---

### 9. JOIN (Using Supabase's Foreign Key Expansion)

**MySQL:**
```php
$sql = "SELECT l.*, a.username 
        FROM listings l 
        JOIN accounts a ON l.seller_id = a.account_id 
        WHERE l.id='$id'";
$result = mysqli_query($conn, $sql);
```

**Supabase:**
```php
$listing = $supabase->select('listings', 
    '*, accounts(username)', 
    ['id' => $id], 
    true
);
// Access: $listing['accounts']['username']
```

---

### 10. LIKE Search

**MySQL:**
```php
$sql = "SELECT * FROM listings WHERE title LIKE '%$search%'";
$result = mysqli_query($conn, $sql);
```

**Supabase:**
```php
$listings = $supabase->customQuery('listings', '*', 
    'title=ilike.*' . urlencode($search) . '*'
);
```

---

### 11. Greater Than / Less Than

**MySQL:**
```php
$sql = "SELECT * FROM bids WHERE bid_amount > 100";
$result = mysqli_query($conn, $sql);
```

**Supabase:**
```php
$bids = $supabase->customQuery('bids', '*', 'bid_amount=gt.100');
```

**Operators:**
- `gt` = greater than
- `gte` = greater than or equal
- `lt` = less than
- `lte` = less than or equal
- `neq` = not equal

---

### 12. Check if Row Exists

**MySQL:**
```php
$sql = "SELECT * FROM favorites WHERE user_id='$user_id' AND listing_id='$listing_id'";
$result = mysqli_query($conn, $sql);
$exists = mysqli_num_rows($result) > 0;
```

**Supabase:**
```php
$result = $supabase->select('favorites', '*', [
    'user_id' => $user_id,
    'listing_id' => $listing_id
]);
$exists = !empty($result);
```

---

## File Includes

**Old:**
```php
include 'database/database.php';
include '../database/database.php';
include "database.php";
```

**New:**
```php
include 'database/supabase.php';
include '../database/supabase.php';
include "../database/supabase.php";
```

---

## Important Notes

1. **No SQL Injection Protection Needed**: Supabase handles this automatically
2. **No mysqli_real_escape_string**: Not needed with Supabase
3. **Arrays by Default**: Supabase returns arrays directly, no need for `mysqli_fetch_assoc()`
4. **Error Handling**: Check if result is `false` for errors
5. **Timestamps**: Use ISO 8601 format: `date('Y-m-d H:i:s')`

---

## Testing Your Migration

Run this command to check which files still use MySQL:
```bash
php check_mysql_usage.php
```
