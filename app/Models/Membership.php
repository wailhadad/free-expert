<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_price',
        'discount',
        'coupon_code',
        'price',
        'currency',
        'currency_symbol',
        'payment_method',
        'transaction_id',
        'status',
        'processed_for_renewal',
        'is_trial',
        'trial_days',
        'receipt',
        'transaction_details',
        'settings',
        'package_id',
        'seller_id',
        'start_date',
        'expire_date',
        'grace_period_until',
        'in_grace_period',
        'conversation_id',
        'invoice',
        'reminder_sent',
        'pending_payment',
        'original_balance'
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate invoice when membership is created
        static::created(function ($membership) {
            if (!$membership->invoice && $membership->status == 1) {
                try {
                    $invoiceName = $membership->generateInvoice();
                    $membership->update(['invoice' => $invoiceName]);
                } catch (\Exception $e) {
                    \Log::error('Failed to auto-generate invoice for membership ' . $membership->id . ': ' . $e->getMessage());
                }
            }
        });
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /**
     * Check if membership is in grace period
     */
    public function isInGracePeriod()
    {
        return $this->in_grace_period && $this->grace_period_until && Carbon::parse($this->grace_period_until) > Carbon::now();
    }

    /**
     * Check if membership is truly expired (after grace period)
     */
    public function isTrulyExpired()
    {
        if ($this->grace_period_until) {
            return Carbon::parse($this->grace_period_until) < Carbon::now();
        }
        return Carbon::parse($this->expire_date) < Carbon::now();
    }

    /**
     * Get time remaining in grace period
     */
    public function getGracePeriodTimeRemaining()
    {
        if (!$this->grace_period_until) {
            return null;
        }

        $now = Carbon::now();
        $graceEnd = Carbon::parse($this->grace_period_until);
        
        if ($graceEnd <= $now) {
            return null;
        }

        $diff = $graceEnd->diff($now);
        
        return [
            'days' => $diff->days,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
            'total_seconds' => $graceEnd->diffInSeconds($now)
        ];
    }

    /**
     * Start grace period
     */
    public function startGracePeriod($gracePeriodMinutes = 2)
    {
        $this->grace_period_until = Carbon::parse($this->expire_date)->addMinutes($gracePeriodMinutes);
        $this->in_grace_period = true;
        $this->save();
    }

    /**
     * Generate invoice for this membership
     */
    public function generateInvoice()
    {
        $seller = $this->seller;
        $package = $this->package;
        $bs = \App\Models\BasicSettings\Basic::first();
        
        if (!$seller || !$package) {
            throw new \Exception('Seller or package not found for membership ' . $this->id);
        }

        $invoiceName = $this->id . '_' . $seller->id . '_' . $this->package_id . '.pdf';
        $invoicePath = public_path('assets/file/invoices/seller-memberships/' . $invoiceName);
        
        // Ensure directory exists
        $directory = dirname($invoicePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // Generate PDF with correct variable names for the template
        $pdf = \PDF::loadView('pdf.membership', [
            'websiteInfo' => $bs,
            'member' => [
                'first_name' => $seller->first_name ?? '',
                'last_name' => $seller->last_name ?? '',
                'username' => $seller->username ?? '',
                'email' => $seller->email ?? ''
            ],
            'phone' => $seller->phone ?? '',
            'order_id' => $this->transaction_id ?? '',
            'amount' => $this->price,
            'request' => [
                'payment_method' => $this->payment_method ?? '',
                'start_date' => $this->start_date,
                'expire_date' => $this->expire_date
            ],
            'package_title' => $package->title ?? '',
            'base_currency_text' => $bs->base_currency_text ?? ''
        ]);
        
        $pdf->save($invoicePath);
        
        return $invoiceName;
    }

    /**
     * Generate extension invoice for this membership
     */
    public function generateExtensionInvoice()
    {
        $seller = $this->seller;
        $package = $this->package;
        $bs = \App\Models\BasicSettings\Basic::first();
        
        if (!$seller || !$package) {
            throw new \Exception('Seller or package not found for membership ' . $this->id);
        }

        $invoiceName = 'membership' . uniqid() . '.pdf';
        $invoicePath = public_path('assets/file/invoices/seller-memberships/' . $invoiceName);
        
        // Ensure directory exists
        $directory = dirname($invoicePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // Generate PDF with correct variable names for the template
        $pdf = \PDF::loadView('pdf.membership', [
            'websiteInfo' => $bs,
            'member' => [
                'first_name' => $seller->first_name ?? '',
                'last_name' => $seller->last_name ?? '',
                'username' => $seller->username ?? '',
                'email' => $seller->email ?? ''
            ],
            'phone' => $seller->phone ?? '',
            'order_id' => $this->transaction_id ?? '',
            'amount' => $this->price,
            'request' => [
                'payment_method' => $this->payment_method ?? '',
                'start_date' => $this->start_date,
                'expire_date' => $this->expire_date
            ],
            'package_title' => $package->title ?? '',
            'base_currency_text' => $bs->base_currency_text ?? ''
        ]);
        
        $pdf->save($invoicePath);
        
        return $invoiceName;
    }
}
