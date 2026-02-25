# Supabase Migration Guide

This guide will help you migrate from MySQL to Supabase.

## Step 1: Create a Supabase Project

1. Go to [https://supabase.com](https://supabase.com)
2. Sign up or log in
3. Click "New Project"
4. Fill in your project details:
   - Name: mineteh (or your preferred name)
   - Database Password: (create a strong password)
   - Region: Choose closest to your users
5. Wait for the project to be created (takes ~2 minutes)

## Step 2: Get Your Supabase Credentials

1. In your Supabase dashboard, go to **Settings** > **API**
2. Copy the following:
   - **Project URL** (looks like: `https://xxxxx.supabase.co`)
   - **anon/public key** (the `anon` key under "Project API keys")

3. Open `database/supabase.php` and replace:
   ```php
   $this->supabaseUrl = 'YOUR_SUPABASE_URL';
   $this->supabaseKey = 'YOUR_SUPABASE_ANON_KEY';
   ```

## Step 3: Create Database Tables

1. In Supabase dashboard, go to **SQL Editor**
2. Copy the SQL from `supabase_schema.sql` (see below)
3. Paste and run it in the SQL Editor

## Step 4: Export Your MySQL Data (Optional)

If you have existing data in MySQL:

1. Export your MySQL data:
   ```bash
   mysqldump -u root mineteh > backup.sql
   ```

2. You'll need to convert and import this data to Supabase manually or use a migration tool

## Step 5: Update Your Code

All API files have been updated to use Supabase:
- ✅ `api/place-bid.php`
- ✅ `api/close-bid.php`
- ✅ `api/favorite-action.php`
- ✅ `api/upload-images.php`
- ✅ `login.php`

You'll need to update other PHP files that use `database.php`:
- `home/bids.php`
- `home/create-listing.php`
- `home/homepage.php`
- `home/messages.php`
- `home/profile.php`
- Any files in the `admin` folder

## Step 6: Update Remaining Files

For each file that uses MySQL:

### Old MySQL code:
```php
include 'database/database.php';
$result = mysqli_query($conn, "SELECT * FROM listings WHERE id='$id'");
$listing = mysqli_fetch_assoc($result);
```

### New Supabase code:
```php
include 'database/supabase.php';
$listing = $supabase->select('listings', '*', ['id' => $id], true);
```

## Supabase Client Methods

### SELECT
```php
// Get all rows
$listings = $supabase->select('listings', '*');

// Get with filters
$listings = $supabase->select('listings', '*', ['status' => 'active']);

// Get single row
$listing = $supabase->select('listings', '*', ['id' => 1], true);

// Custom query
$listings = $supabase->customQuery('listings', '*', 'status=eq.active&order=created_at.desc');
```

### INSERT
```php
$result = $supabase->insert('listings', [
    'title' => 'My Listing',
    'price' => 100
]);
```

### UPDATE
```php
$supabase->update('listings', 
    ['status' => 'closed'],
    ['id' => 1]
);
```

### DELETE
```php
$supabase->delete('listings', ['id' => 1]);
```

### COUNT
```php
$count = $supabase->count('listings', ['status' => 'active']);
```

## Step 7: Enable Row Level Security (RLS)

For production, enable RLS in Supabase:

1. Go to **Authentication** > **Policies**
2. Create policies for each table
3. Example policy for listings:
   ```sql
   -- Allow anyone to read listings
   CREATE POLICY "Public listings are viewable by everyone"
   ON listings FOR SELECT
   USING (true);
   
   -- Allow users to insert their own listings
   CREATE POLICY "Users can insert their own listings"
   ON listings FOR INSERT
   WITH CHECK (auth.uid() = seller_id);
   ```

## Step 8: Test Your Application

1. Test user registration and login
2. Test creating listings
3. Test placing bids
4. Test favorites
5. Test image uploads

## Troubleshooting

### CORS Errors
If you get CORS errors, check your Supabase project settings under **Authentication** > **URL Configuration**

### 401 Unauthorized
Make sure you're using the correct `anon` key, not the `service_role` key

### Data Not Showing
Check the Supabase Table Editor to verify data was inserted correctly

## Benefits of Supabase

- ✅ No need to manage MySQL server
- ✅ Built-in authentication (can replace your custom auth)
- ✅ Real-time subscriptions
- ✅ Automatic API generation
- ✅ Built-in storage for file uploads
- ✅ Free tier available
- ✅ Better security with Row Level Security
