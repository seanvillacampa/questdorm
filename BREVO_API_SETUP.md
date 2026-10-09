# Brevo API Setup Guide

## What Changed?

We've switched from **Brevo SMTP** (port 587) to **Brevo API** to fix the connection timeout issue on Render. The API doesn't require port 587, so it bypasses Render's SMTP blocking.

---

## Step 1: Get Your Brevo API Key

1. Go to https://app.brevo.com and log in
2. Click **Settings** (top right corner)
3. Navigate to **SMTP & API** → **API Keys**
4. Click **Generate a new API key**
5. Give it a name like "Quest Dormitory Production"
6. Click **Generate**
7. **Copy the API key** - you won't see it again!

---

## Step 2: Update Local .env File

Open your local `.env` file and replace this line:

```env
BREVO_API_KEY=your-brevo-api-key-here
```

With your actual API key:

```env
BREVO_API_KEY=xkeysib-abc123def456...
```

Save the file.

---

## Step 3: Test Locally

Run this command to test email sending:

```bash
php artisan tinker
```

Then run:

```php
Mail::raw('Test email from Brevo API', function($msg) {
    $msg->to('villacampasean@gmail.com')
        ->subject('Test Email');
});
exit
```

Check your email inbox. If you receive the test email, it works!

---

## Step 4: Update Render Environment Variables

1. Go to https://dashboard.render.com
2. Select your **questdorm** service
3. Click **Environment** in the left sidebar
4. **Remove or update these old SMTP variables** (if they exist):
   - Delete `MAIL_HOST`
   - Delete `MAIL_PORT`
   - Delete `MAIL_USERNAME`
   - Delete `MAIL_PASSWORD`
   - Delete `MAIL_ENCRYPTION`

5. **Add or update these variables**:

| Key | Value |
|-----|-------|
| `MAIL_MAILER` | `brevo` |
| `MAIL_FROM_ADDRESS` | `villacampasean@gmail.com` |
| `MAIL_FROM_NAME` | `Quest Dormitory` |
| `BREVO_API_KEY` | `<paste your Brevo API key>` |

6. Click **Save Changes**
7. Render will automatically redeploy (takes 3-5 minutes)

---

## Step 5: Commit and Push Changes

```bash
git add .
git commit -m "Switch from Brevo SMTP to Brevo API for email delivery"
git push origin main
```

This will trigger another Render deployment with the new code.

---

## Step 6: Test on Render

Once deployment completes (check the Render dashboard):

1. Go to your Render URL
2. Try registering a new tenant or owner
3. Check the email inbox - you should receive the welcome email!

---

## Troubleshooting

### "Class 'App\Mail\BrevoTransport' not found"
Run: `composer dump-autoload` then `php artisan config:clear`

### "Brevo API returned 401 Unauthorized"
Your API key is incorrect. Double-check you copied it correctly from Brevo.

### "Sender email not verified"
Go to Brevo → Settings → Senders & IP → Senders, and verify `villacampasean@gmail.com`

### Still getting SMTP errors
Make sure `MAIL_MAILER=brevo` (not `smtp`) in Render environment variables.

---

## Why This Works

- **SMTP** uses port 587, which Render blocks
- **API** uses HTTPS (port 443), which Render allows
- Both deliver emails the same way to recipients
- API is actually faster and more reliable

---

## Next Steps

Once everything works with `villacampasean@gmail.com`, you can:

1. Buy a custom domain (e.g., `questdormitory.com`)
2. Verify the domain in Brevo
3. Update `MAIL_FROM_ADDRESS` to `noreply@questdormitory.com`
4. Update Render environment variables
5. Redeploy

This makes emails look more professional!
