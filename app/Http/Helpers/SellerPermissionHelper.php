<?php

namespace App\Http\Helpers;

use App\Models\BasicSettings\Basic;
use App\Models\Membership;
use App\Models\Package;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Config;

class SellerPermissionHelper
{

  public static function packagePermission(int $seller_id)
  {
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);

    $currentPackage = Membership::query()->where([
      ['seller_id', '=', $seller_id],
      ['status', '=', '1'],
      ['start_date', '<=', Carbon::now()],
      ['expire_date', '>=', Carbon::now()]
    ])->first();
    $package = isset($currentPackage) ? Package::query()->find($currentPackage->package_id) : null;
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
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);
    
    // First try to find an active membership (not expired)
    $currentPackage = Membership::query()->where([
      ['seller_id', '=', $userId],
      ['status', '=', '1'],
      ['start_date', '<=', Carbon::now()],
      ['expire_date', '>=', Carbon::now()]
    ])->first();
    
    // If no active membership, check for grace period membership
    if (!$currentPackage) {
      $currentPackage = Membership::query()->where([
        ['seller_id', '=', $userId],
        ['status', '=', '1'],
        ['start_date', '<=', Carbon::now()],
        ['in_grace_period', '=', '1'],
        ['grace_period_until', '>', Carbon::now()]
      ])->first();
    }
    
    return isset($currentPackage) ? Package::query()->findOrFail($currentPackage->package_id) : null;
  }
  public static function userPackage(int $userId)
  {
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);

    // First try to find an active membership (not expired)
    $currentPackage = Membership::query()->where([
      ['seller_id', '=', $userId],
      ['status', '=', '1'],
      ['start_date', '<=', Carbon::now()],
      ['expire_date', '>=', Carbon::now()]
    ])->first();
    
    // If no active membership, check for grace period membership
    if (!$currentPackage) {
      $currentPackage = Membership::query()->where([
        ['seller_id', '=', $userId],
        ['status', '=', '1'],
        ['start_date', '<=', Carbon::now()],
        ['in_grace_period', '=', '1'],
        ['grace_period_until', '>', Carbon::now()]
      ])->first();
    }
    
    return $currentPackage;
  }

  public static function currPackageOrPending($userId)
  {

    $currentPackage = Self::currentPackagePermission($userId);
    if (!$currentPackage) {
      $currentPackage = Membership::query()->where([
        ['seller_id', '=', $userId],
        ['status', '0']
      ])->whereYear('start_date', '<>', '9999')->orderBy('id', 'DESC')->first();
      $currentPackage = isset($currentPackage) ? Package::query()->findOrFail($currentPackage->package_id) : null;
    }
    return isset($currentPackage) ? $currentPackage : null;
  }

  public static function currMembOrPending($userId)
  {
    $currMemb = Self::userPackage($userId);
    if (!$currMemb) {
      $currMemb = Membership::query()->where([
        ['seller_id', '=', $userId],
        ['status', '0'],
      ])->whereYear('start_date', '<>', '9999')->orderBy('id', 'DESC')->first();
    }
    return isset($currMemb) ? $currMemb : null;
  }


  public static function hasPendingMembership($userId)
  {
    $count = Membership::query()->where([
      ['seller_id', '=', $userId],
      ['status', '0']
    ])->whereYear('start_date', '<>', '9999')->count();
    return $count > 0 ? true : false;
  }

  public static function nextPackage(int $userId)
  {
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);
    $currMemb = Membership::query()->where([
      ['seller_id', $userId],
      ['start_date', '<=', Carbon::now()],
      ['expire_date', '>=', Carbon::now()]
    ])->where('status', '<>', '2')->whereYear('start_date', '<>', '9999');
    $nextPackage = null;
    if ($currMemb->first()) {
      $countCurrMem = $currMemb->count();
      if ($countCurrMem > 1) {
        $nextMemb = $currMemb->orderBy('id', 'DESC')->first();
      } else {
        $nextMemb = Membership::query()->where([
          ['seller_id', $userId],
          ['start_date', '>', $currMemb->first()->expire_date]
        ])->whereYear('start_date', '<>', '9999')->where('status', '<>', '2')->first();
      }
      $nextPackage = $nextMemb ? Package::query()->where('id', $nextMemb->package_id)->first() : null;
    }
    return $nextPackage;
  }

  public static function nextMembership(int $userId)
  {
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);
    $currMemb = Membership::query()->where([
      ['seller_id', $userId],
      ['start_date', '<=', Carbon::now()],
      ['expire_date', '>=', Carbon::now()]
    ])->where('status', '<>', '2')->whereYear('start_date', '<>', '9999');
    $nextMemb = null;
    if ($currMemb->first()) {
      $countCurrMem = $currMemb->count();
      if ($countCurrMem > 1) {
        $nextMemb = $currMemb->orderBy('id', 'DESC')->first();
      } else {
        $nextMemb = Membership::query()->where([
          ['seller_id', $userId],
          ['start_date', '>', $currMemb->first()->expire_date]
        ])->whereYear('start_date', '<>', '9999')->where('status', '<>', '2')->first();
      }
    }
    return $nextMemb;
  }
  public static function getPackageInfoByMembership($membership_id)
  {
    $bs = Basic::first();
    Config::set('app.timezone', $bs->timezone);
    $membership = Membership::query()->where('id', $membership_id)->select('package_id')->first();
    if ($membership) {
      $pacakge = Package::where([['id', $membership->package_id], ['status', 1]])->first();
      if ($pacakge) {
        if ($pacakge->live_chat_status == 1) {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  public static function getPackageInfo($seller_id, $membership_id)
  {
    $membership = Membership::where([['seller_id', $seller_id], ['id', $membership_id]])->first();
    if (!empty($membership)) {
      $package = Package::where('id', $membership->package_id)->first();
      if (!empty($package)) {
        if ($package->live_chat_status == 1) {
          return true;
        } else {
          return false;
        }
      } else {
        return false;
      }
    } else {
      return false;
    }
  }
}
