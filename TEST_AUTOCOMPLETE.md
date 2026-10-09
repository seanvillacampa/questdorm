# Testing Tenant Autocomplete

## Step 1: Test the API Endpoint Directly

Open your browser and go to:
```
http://localhost:8000/laundry/search-tenants?q=ju
```

You should see JSON response like:
```json
[
  {
    "id": 1,
    "name": "Juan Santos",
    "room_no": "101",
    "contact_no": "0917 000 1234"
  },
  ...
]
```

If you see a login page, the middleware is blocking it.
If you see an error, there's a problem with the controller.
If you see empty array `[]`, there are no tenants matching "ju".

## Step 2: Check Browser Console

1. Login as staff or owner
2. Go to: http://localhost:8000/laundry/create
3. Open Browser DevTools (F12)
4. Go to Console tab
5. Make sure Customer Type is "Tenant"
6. Type "ju" in Customer Name field
7. Look for console messages:
   - "Input event fired"
   - "Showing loading state"
   - "Fetching from: ..."
   - "Response status: 200"
   - "Tenants received: [...]"

## Step 3: Check Network Tab

1. In DevTools, go to Network tab
2. Type in Customer Name field
3. Look for request to `search-tenants?q=...`
4. Click on it
5. Check:
   - Status Code: should be 200
   - Response: should show JSON array of tenants

## Common Issues

### Issue: No console logs appear
**Problem**: JavaScript not loading or event listener not attached
**Solution**: Check browser console for JavaScript errors

### Issue: "403 Forbidden" or redirects to login
**Problem**: Middleware blocking the request
**Solution**: Make sure you're logged in as staff/owner

### Issue: "404 Not Found"
**Problem**: Route not registered
**Solution**: Run `php artisan route:clear`

### Issue: Empty array []
**Problem**: No tenants in database or search not working
**Solution**: Check if tenants exist with `php artisan tinker` then `Tenant::count()`

### Issue: Autocomplete div not showing
**Problem**: CSS or HTML issue
**Solution**: Check if `tenant-autocomplete` div exists in HTML

## Manual Test with Tinker

```bash
php artisan tinker
```

```php
// Check if tenants exist
\App\Models\Tenant::count();

// Test the search logic
$query = 'ju';
$tenants = \App\Models\Tenant::with(['user', 'contracts' => function ($q) {
    $q->where('is_active', true)->where('status', 'active')->with('room');
}])
->whereHas('user', function ($q) use ($query) {
    $q->where('name', 'like', "%{$query}%")
      ->orWhere('first_name', 'like', "%{$query}%")
      ->orWhere('last_name', 'like', "%{$query}%");
})
->limit(10)
->get();

// Show results
$tenants->map(function ($tenant) {
    $activeContract = $tenant->contracts->first();
    $room = $activeContract?->room;
    return [
        'id' => $tenant->id,
        'name' => $tenant->user->name,
        'room_no' => $room?->room_number ?? '',
        'contact_no' => $tenant->user->phone ?? '',
    ];
});
```

This will show you what the API should return.
