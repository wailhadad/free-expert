<?php

namespace App\Http\Helpers;

use App\Models\UserMembership;
use App\Models\Membership;
use App\Models\BasicSettings\Basic;
use Carbon\Carbon;

class GracePeriodHelper
{
    /**
     * Get grace period countdown data for user
     */
    public static function getUserGracePeriodCountdown($userId)
    {
        $membership = UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '>', Carbon::now())
            ->with('package')
            ->first();

        if (!$membership) {
            return null;
        }

        $timeRemaining = $membership->getGracePeriodTimeRemaining();
        if (!$timeRemaining) {
            return null;
        }

        return [
            'membership_id' => $membership->id,
            'package_title' => $membership->package->title,
            'grace_period_until' => $membership->grace_period_until,
            'time_remaining' => $timeRemaining,
            'formatted_time' => self::formatTimeRemaining($timeRemaining),
            'total_seconds' => $timeRemaining['total_seconds']
        ];
    }

    /**
     * Get grace period countdown data for seller
     * Only shows when balance will be insufficient for auto-renewal
     */
    public static function getSellerGracePeriodCountdown($sellerId)
    {
        $membership = Membership::where('seller_id', $sellerId)
            ->where('status', 1)
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '>', Carbon::now())
            ->with('package')
            ->first();

        if (!$membership) {
            return null;
        }

        $timeRemaining = $membership->getGracePeriodTimeRemaining();
        if (!$timeRemaining) {
            return null;
        }

        // Get seller's current balance
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            return null;
        }

        // Check if balance will be insufficient for auto-renewal
        $packagePrice = $membership->package->price;
        if ($seller->amount >= $packagePrice) {
            // Sufficient balance - no need to show grace period warning
            return null;
        }

        return [
            'membership_id' => $membership->id,
            'package_title' => $membership->package->title,
            'grace_period_until' => $membership->grace_period_until,
            'time_remaining' => $timeRemaining,
            'formatted_time' => self::formatTimeRemaining($timeRemaining),
            'total_seconds' => $timeRemaining['total_seconds'],
            'current_balance' => $seller->amount,
            'package_price' => $packagePrice,
            'balance_shortfall' => $packagePrice - $seller->amount
        ];
    }

    /**
     * Format time remaining for display
     */
    public static function formatTimeRemaining($timeRemaining)
    {
        // Always show all units with leading zeros
        $days = str_pad($timeRemaining['days'], 2, '0', STR_PAD_LEFT);
        $hours = str_pad($timeRemaining['hours'], 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($timeRemaining['minutes'], 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($timeRemaining['seconds'], 2, '0', STR_PAD_LEFT);
        
        return "{$days}d {$hours}h {$minutes}m {$seconds}s";
    }

    /**
     * Check if user is in grace period
     */
    public static function isUserInGracePeriod($userId)
    {
        return UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '>', Carbon::now())
            ->exists();
    }

    /**
     * Check if seller is in grace period
     */
    public static function isSellerInGracePeriod($sellerId)
    {
        return Membership::where('seller_id', $sellerId)
            ->where('status', 1)
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '>', Carbon::now())
            ->exists();
    }

    /**
     * Get grace period settings
     */
    public static function getGracePeriodSettings()
    {
        $bs = Basic::first();
        return [
            'grace_period_minutes' => $bs->grace_period_minutes ?? 2,
            'is_enabled' => true
        ];
    }
} 