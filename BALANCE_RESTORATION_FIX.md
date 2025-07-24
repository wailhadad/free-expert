# Balance Restoration After Package Purchase

## Problem
When a seller's membership expires and their balance goes negative due to auto-renewal deduction, purchasing a new package should restore their balance to what it was before the deduction.

**Example Scenario:**
- Seller has $5 balance
- Membership expires, auto-renewal deducts $20
- Balance becomes -$15
- Seller purchases new package
- Balance should be restored to $5 (original amount)

## Root Cause
The system was deducting the package price from the seller's balance during auto-renewal (cron job), but when the seller purchased a new package, there was no mechanism to restore the balance to its original state before the deduction.

## Solution

### 1. Database Changes
- **Migration**: `2025_07_24_135038_add_original_balance_to_memberships_table.php`
- **New Field**: `original_balance` (decimal, nullable) - stores the seller's balance before auto-renewal deduction

### 2. Model Updates
- **File**: `app/Models/Membership.php`
- **Change**: Added `'original_balance'` to the `$fillable` array

### 3. Cron Job Updates
- **File**: `app/Http/Controllers/CronJobController.php`
- **Change**: When processing truly expired memberships (after grace period), store the original balance before deduction:
  ```php
  $expired_member->update([
      'processed_for_renewal' => 1,
      'pending_payment' => $seller->amount < 0,
      'original_balance' => $originalBalance  // Added this line
  ]);
  ```

### 4. Checkout Controller Updates
- **File**: `app/Http/Controllers/Seller/SellerCheckoutController.php`
- **Change**: Modified the `store()` method to restore balance to original amount when a pending payment membership exists:
  ```php
  if ($pendingMembership && $pendingMembership->original_balance !== null) {
      // Restore the balance to what it was before the deduction
      $seller->amount = $pendingMembership->original_balance;
      $seller->save();
  }
  ```

## How It Works

### Step 1: Auto-Renewal Deduction (Cron Job)
1. Membership expires and goes into grace period
2. After grace period ends, cron job deducts package price from seller's balance
3. **NEW**: Original balance is stored in `original_balance` field
4. If balance goes negative, `pending_payment` flag is set to `true`

### Step 2: Package Purchase (Checkout)
1. Seller purchases a new package
2. System checks for pending payment memberships
3. **NEW**: If found, restores balance to `original_balance` value
4. Clears the `pending_payment` flag
5. Creates new membership

## Testing

### Test Scripts Created
1. `test_balance_restoration.php` - Basic test
2. `test_balance_restoration_realistic.php` - Realistic scenario with actual package prices

### Test Results
```
=== Testing Balance Restoration After Package Purchase (Realistic Scenario) ===

Using existing package: Premium (Price: $20)

=== Step 1: Setting up initial balance ===
Set seller balance to: $5

=== Step 2: Creating expired membership with pending payment ===
Created expired membership (ID: 66)
Original balance stored: $5
Pending payment: Yes

=== Step 3: Simulating balance deduction (cron job) ===
Deducted $20 from balance
New balance: $-15
Balance went negative: Yes

=== Step 4: Simulating package purchase (should restore balance) ===
Package purchase completed
Final balance: $5.00
Expected balance: $5
Balance restored correctly: Yes

=== Step 5: Verifying pending payment status ===
Pending payment membership still exists: No

=== Cleaning up test data ===
Test completed successfully!
```

## Benefits
1. **Fair Treatment**: Sellers get their original balance back after purchasing a new package
2. **Clear Logic**: The system now properly handles the balance restoration flow
3. **Audit Trail**: Original balance is stored for transparency and debugging
4. **No Double Charging**: Sellers are not charged twice for the same service period

## Files Modified
- `database/migrations/2025_07_24_135038_add_original_balance_to_memberships_table.php`
- `app/Models/Membership.php`
- `app/Http/Controllers/CronJobController.php`
- `app/Http/Controllers/Seller/SellerCheckoutController.php`
- `test_balance_restoration.php`
- `test_balance_restoration_realistic.php`
- `BALANCE_RESTORATION_FIX.md` (this file)

## Migration Required
Run the migration to add the `original_balance` field:
```bash
php artisan migrate
``` 