# Plan: OTP-Based Registration System

## Overview

Implementasi sistem registrasi dengan verifikasi email menggunakan OTP (One-Time Password) yang dikirim via Gmail SMTP.

## Architecture

### Flow Diagram

```
User Request OTP
       ↓
POST /auth/request-otp
       ↓
Validate Email (unique)
       ↓
Check Rate Limit (3 per 10 min)
       ↓
Generate 6-digit OTP
       ↓
Store in DB (expires 5 min)
       ↓
Dispatch Job → Send Email
       ↓
Return Success Response
       
User Checks Email
       ↓
Copy OTP Code
       ↓
POST /auth/register
       ↓
Verify OTP (email + code)
       ↓
Create User (email_verified_at set)
       ↓
Generate Sanctum Token
       ↓
Delete Used OTP
       ↓
Return User + Token
```

## Components

### Database
- **Table:** `email_verifications`
  - Stores OTP codes with expiry
  - Tracks verification attempts
  - Auto-cleanup via scheduled command

### Services
- **OtpService:** Core OTP logic (generate, verify, rate limit)
- **AuthService:** User registration with OTP verification flag

### Jobs
- **SendOtpEmailJob:** Queue-based email sending for non-blocking UX

### Validation
- **RequestOtpRequest:** Email must be unique
- **RegisterWithOtpRequest:** Email, OTP, name, password validation

### Email
- **SendOtpMail:** Mailable with HTML template
- **otp.blade.php:** Beautiful email design with OTP display

## Configuration

### ENV Variables

```env
# Gmail SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls

# OTP Settings
OTP_EXPIRY_MINUTES=5
OTP_MAX_ATTEMPTS=5
OTP_RATE_LIMIT=3
```

### Gmail Setup Steps

1. Enable 2-Step Verification in Google Account
2. Generate App Password: https://myaccount.google.com/apppasswords
3. Use App Password (not regular password) in MAIL_PASSWORD
4. Update .env with credentials
5. Clear config: `php artisan config:clear`

## Security Considerations

### Rate Limiting
- **Email-based:** Max 3 OTP requests per 10 minutes
- **Implementation:** Cache-based tracking with TTL
- **Bypass protection:** Kombinasi IP + email limiting (future)

### OTP Security
- **Format:** 6-digit random number (1 million combinations)
- **Expiry:** 5 minutes from generation
- **Max attempts:** 5 verification tries per OTP
- **Auto-invalidate:** After successful verification

### Data Protection
- **Cleanup:** Expired OTPs deleted via daily scheduler
- **One-time use:** OTP deleted immediately after successful use
- **Email enumeration:** Consistent error messages

## API Endpoints

### 1. Request OTP
```
POST /api/auth/request-otp
Body: { "email": "user@example.com" }
Response: 200 OK - { message, email, expires_in }
Rate Limit: 3 requests per 10 minutes per email
```

### 2. Register with OTP
```
POST /api/auth/register
Body: { email, otp, name, password, password_confirmation }
Response: 201 Created - { user, token }
Errors: 422 - Invalid/expired OTP
```

## Testing

### Prerequisites
1. Gmail App Password configured in .env
2. Queue worker running: `php artisan queue:work`
3. Database migrated with email_verifications table

### Test Scenarios

**Happy Path:**
1. Request OTP for new email → 200 OK
2. Check email inbox → OTP received
3. Register with valid OTP → 201 Created
4. User created with email_verified_at set
5. Can login with credentials

**Error Cases:**
1. Request OTP for existing email → 422 (email taken)
2. Request OTP 4 times → 429 (rate limited)
3. Register with wrong OTP → 422 (invalid OTP)
4. Register after 5 minutes → 422 (expired OTP)
5. Register with 6 wrong attempts → 422 (max attempts)

### Postman Collection

See [POSTMAN_TEST_GUIDE.md](../POSTMAN_TEST_GUIDE.md) for detailed API documentation and test workflow.

## Maintenance

### Cleanup Command
```bash
php artisan otp:cleanup
```
Deletes all expired OTP records from database.

### Scheduler (Optional)
Add to `app/Console/Kernel.php`:
```php
$schedule->command('otp:cleanup')->daily();
```

## Future Improvements

1. **Resend OTP Endpoint**
   - Allow user to request new OTP if not received
   - With stricter rate limiting

2. **SMS OTP Alternative**
   - Support sending OTP via SMS (Twilio, Vonage)
   - Fallback if email fails

3. **Multi-language Email**
   - Support Indonesian language for email template
   - Locale-based email content

4. **Admin Dashboard**
   - Monitor OTP requests and success rates
   - View failed verification attempts

5. **Remember Device**
   - Skip OTP for trusted devices
   - Device fingerprinting

## Troubleshooting

### Email Not Received
- Check queue worker is running: `php artisan queue:work`
- Verify Gmail SMTP credentials in .env
- Check spam folder
- Review Laravel logs: `storage/logs/laravel.log`

### Rate Limit Issues
- Clear cache: `php artisan cache:clear`
- Check OTP_RATE_LIMIT in .env
- Wait 10 minutes before retry

### OTP Always Invalid
- Verify OTP_EXPIRY_MINUTES in .env
- Check system time is correct
- Review email_verifications table for stored OTP

---

**Implementation Status:** ✅ Complete

**Last Updated:** December 18, 2025
