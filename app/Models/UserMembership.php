<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class UserMembership extends Model
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
        'is_trial',
        'trial_days',
        'receipt',
        'transaction_details',
        'settings',
        'package_id',
        'user_id',
        'start_date',
        'expire_date',
        'grace_period_until',
        'in_grace_period',
        'conversation_id',
        'processed_for_expiration',
        'reminder_sent'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function package()
    {
        return $this->belongsTo(UserPackage::class, 'package_id');
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
} 