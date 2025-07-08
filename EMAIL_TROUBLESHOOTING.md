# Email and PDF Troubleshooting Guide

## Current Status
The order validation system has been updated to work immediately without queues. Both PDF generation and email sending now happen synchronously to ensure they work.

## Issues Fixed

### 1. PDF Generation Issues
- **Problem**: PDFs were not being generated when seller validated orders
- **Solution**: PDF generation now happens immediately in the controller
- **Location**: `app/Http/Controllers/Seller/OrderController.php` and `app/Http/Controllers/BackEnd/ClientService/OrderController.php`

### 2. Email Sending Issues
- **Problem**: Emails were not being sent when admin validated orders
- **Solution**: Email sending now happens immediately with proper SMTP checks
- **Location**: Both controllers now check SMTP configuration before sending

## How to Test

### 1. Test PDF Generation
1. Go to seller dashboard → Orders
2. Find an order with pending payment
3. Change payment status to "completed"
4. Check if PDF is generated in `public/assets/file/invoices/order-invoices/`
5. Check logs in `storage/logs/laravel.log`

### 2. Test Email Sending
1. Go to admin dashboard → Orders
2. Find an order with pending payment
3. Change payment status to "completed"
4. Check if email is sent to customer
5. Check logs for email status

## Troubleshooting

### PDF Not Generated
1. **Check logs**: Look for "PDF generation failed" in `storage/logs/laravel.log`
2. **Check permissions**: Ensure `public/assets/file/invoices/order-invoices/` directory is writable
3. **Check memory**: PDF generation requires sufficient memory (512M recommended)

### Email Not Sent
1. **Check SMTP configuration**:
   ```bash
   # Check if SMTP is enabled
   php artisan tinker
   >>> App\Models\BasicSettings\Basic::select('smtp_status', 'smtp_host', 'from_mail')->first();
   ```

2. **Test email manually**:
   ```bash
   # Create a test email command
   php artisan make:command TestEmailCommand
   ```

3. **Check SMTP settings in admin panel**:
   - Go to Admin → Basic Settings → Email Settings
   - Ensure SMTP is enabled
   - Verify SMTP credentials are correct

### Common Issues

#### 1. SMTP Not Configured
- **Symptom**: No emails sent, logs show "SMTP not configured"
- **Solution**: Configure SMTP in admin panel

#### 2. PDF Generation Timeout
- **Symptom**: "Maximum execution time exceeded" error
- **Solution**: Increased timeout to 120 seconds, optimized PDF generation

#### 3. Memory Issues
- **Symptom**: PDF generation fails with memory errors
- **Solution**: Increased memory limit to 512M for PDF generation

## Log Messages to Look For

### Successful Operations
```
OrderInvoice: PDF generated successfully
OrderPayment: Email sent via SMTP
AdminOrderPayment: Notifications and email sent successfully
```

### Failed Operations
```
OrderInvoice: PDF generation failed
OrderPayment: SMTP not configured, email not sent
AdminOrderPayment: Notifications/Email sending failed
```

## Configuration Requirements

### For PDF Generation
- PHP memory limit: 512M or higher
- Execution time limit: 120 seconds or higher
- Writable directory: `public/assets/file/invoices/order-invoices/`

### For Email Sending
- SMTP server configured and enabled
- Valid SMTP credentials
- From email and name configured

## Testing Commands

### Test Email System
```bash
# Create a test email command
php artisan make:command TestEmailCommand

# Test with a specific email
php artisan test:email your-email@example.com
```

### Check Logs
```bash
# View recent logs
tail -f storage/logs/laravel.log

# Search for specific errors
grep "PDF generation failed" storage/logs/laravel.log
grep "SMTP not configured" storage/logs/laravel.log
```

## Next Steps

If issues persist:
1. Check the logs for specific error messages
2. Verify SMTP configuration in admin panel
3. Test email functionality manually
4. Ensure proper file permissions
5. Check PHP configuration (memory, execution time)

## Queue System (Optional)

The queue system is still available for better performance:
```bash
# Start queue worker
php artisan queue:work

# Check failed jobs
php artisan queue:failed
```

But the current implementation works without queues for immediate functionality. 