<?php

namespace App\Http\Helpers;

use App\Models\BasicSettings\Basic;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;

class UserPermissionHelper
{
    public static function packagePermission(int $user_id)
    {
        $currentDate = self::getCurrentDate();

        $currentPackage = UserMembership::query()->where([
            ['user_id', '=', $user_id],
            ['status', '=', '1'],
            ['start_date', '<=', $currentDate],
            ['expire_date', '>=', $currentDate]
        ])->first();
        
        $package = isset($currentPackage) ? UserPackage::query()->find($currentPackage->package_id) : null;
        return $package ? $package : collect([]);
    }

    public static function uniqidReal($lenght = 13)
    {
        $bs = Basic::first();
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }

    public static function currentPackagePermission(int $userId)
    {
        $currentDate = self::getCurrentDate();
        $currentPackage = UserMembership::query()->where([
            ['user_id', '=', $userId],
            ['status', '=', '1'],
            ['start_date', '<=', $currentDate],
            ['expire_date', '>=', $currentDate]
        ])->first();
        return isset($currentPackage) ? UserPackage::query()->findOrFail($currentPackage->package_id) : null;
    }

    public static function userPackage(int $userId)
    {
        $currentDate = self::getCurrentDate();
        return UserMembership::query()->where([
            ['user_id', '=', $userId],
            ['status', '=', '1'],
            ['start_date', '<=', $currentDate],
            ['expire_date', '>=', $currentDate]
        ])->first();
    }

    public static function currPackageOrPending($userId)
    {
        $currentPackage = Self::currentPackagePermission($userId);
        if (!$currentPackage) {
            $currentPackage = UserMembership::query()->where([
                ['user_id', '=', $userId],
                ['status', '0']
            ])->whereYear('start_date', '<>', '9999')->orderBy('id', 'DESC')->first();
            $currentPackage = isset($currentPackage) ? UserPackage::query()->findOrFail($currentPackage->package_id) : null;
        }
        return isset($currentPackage) ? $currentPackage : null;
    }

    public static function currMembOrPending($userId)
    {
        $currMemb = Self::userPackage($userId);
        if (!$currMemb) {
            $currMemb = UserMembership::query()->where([
                ['user_id', '=', $userId],
                ['status', '0'],
            ])->whereYear('start_date', '<>', '9999')->orderBy('id', 'DESC')->first();
        }
        return isset($currMemb) ? $currMemb : null;
    }

    public static function hasPendingMembership($userId)
    {
        $count = UserMembership::query()->where([
            ['user_id', '=', $userId],
            ['status', '0']
        ])->whereYear('start_date', '<>', '9999')->count();
        return $count > 0 ? true : false;
    }

    public static function hasAgencyPrivileges($userId)
    {
        $package = self::currentPackagePermission($userId);
        return $package && $package->max_subusers > 0;
    }

    public static function getMaxSubusers($userId)
    {
        $package = self::currentPackagePermission($userId);
        return $package ? $package->max_subusers : 0;
    }

    public static function canCreateSubuser($userId)
    {
        if (!self::hasAgencyPrivileges($userId)) {
            return false;
        }

        $maxSubusers = self::getMaxSubusers($userId);
        $currentSubusers = \App\Models\User::find($userId)->subusers()->count();
        
        return $currentSubusers < $maxSubusers;
    }

    /**
     * Get current date in consistent format with proper timezone
     * This ensures all date comparisons use the same format and timezone
     */
    public static function getCurrentDate()
    {
        $bs = Basic::first();
        Config::set('app.timezone', $bs->timezone);
        return Carbon::now()->format('Y-m-d');
    }

    /**
     * Get the total max subusers allowed for all active packages of a user
     */
    public static function totalMaxSubusers($userId)
    {
        $currentDate = self::getCurrentDate();
        $activeMemberships = \App\Models\UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('start_date', '<=', $currentDate)
            ->where('expire_date', '>=', $currentDate)
            ->get();
        $total = 0;
        foreach ($activeMemberships as $membership) {
            if ($membership->package) {
                $total += (int) $membership->package->max_subusers;
            }
        }
        return $total;
    }
} 