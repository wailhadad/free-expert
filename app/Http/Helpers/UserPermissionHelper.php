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
        $currentPackage = UserMembership::query()->where([
            ['user_id', '=', $user_id],
            ['status', '=', '1'],
            ['start_date', '<=', Carbon::now()],
            ['expire_date', '>=', Carbon::now()]
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
        $currentPackage = UserMembership::query()->where([
            ['user_id', '=', $userId],
            ['status', '=', '1'],
            ['start_date', '<=', Carbon::now()],
            ['expire_date', '>=', Carbon::now()]
        ])->first();
        return isset($currentPackage) ? UserPackage::query()->findOrFail($currentPackage->package_id) : null;
    }

    public static function userPackage(int $userId)
    {
        return UserMembership::query()->where([
            ['user_id', '=', $userId],
            ['status', '=', '1'],
            ['start_date', '<=', Carbon::now()],
            ['expire_date', '>=', Carbon::now()]
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
     * Get the total max subusers allowed for all active packages of a user (including grace period)
     */
    public static function totalMaxSubusers($userId)
    {
        // Get active memberships (not expired)
        $activeMemberships = \App\Models\UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('start_date', '<=', Carbon::now())
            ->where('expire_date', '>=', Carbon::now())
            ->get();
        
        // Get grace period memberships
        $gracePeriodMemberships = \App\Models\UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('in_grace_period', 1)
            ->where('grace_period_until', '>', Carbon::now())
            ->get();
        
        $total = 0;
        
        // Add subusers from active memberships
        foreach ($activeMemberships as $membership) {
            if ($membership->package) {
                $total += (int) $membership->package->max_subusers;
            }
        }
        
        // Add subusers from grace period memberships
        foreach ($gracePeriodMemberships as $membership) {
            if ($membership->package) {
                $total += (int) $membership->package->max_subusers;
            }
        }
        
        return $total;
    }

    /**
     * Check if user has active membership (including grace period)
     */
    public static function hasActiveMembership($userId)
    {
        // First check for active membership (not expired)
        $membership = UserMembership::where('user_id', $userId)
            ->where('status', '1')
            ->where('start_date', '<=', Carbon::now())
            ->where('expire_date', '>=', Carbon::now())
            ->first();
        
        if ($membership) {
            return true;
        }
        
        // If no active membership, check if user is in grace period
        return \App\Http\Helpers\GracePeriodHelper::isUserInGracePeriod($userId);
    }

    /**
     * Check if user can send messages in existing chats (read-only mode for expired memberships)
     */
    public static function canSendMessages($userId, $chatId = null)
    {
        // If user has active membership (including grace period), they can send messages
        if (self::hasActiveMembership($userId)) {
            return true;
        }

        // If no active membership, check if this is an existing chat
        if ($chatId) {
            $chat = \App\Models\DirectChat::find($chatId);
            if ($chat && $chat->user_id == $userId) {
                // User can only read in existing chats, not send new messages
                return false;
            }
        }

        // No active membership and no existing chat - cannot send messages
        return false;
    }

    /**
     * Get prioritized subusers list based on current package limits
     * Priority: Active orders > Active customer offers > Active customer briefs > Completed orders > Chats > Others
     * Only applies prioritization if total subuser limit is less than actual subusers
     */
    public static function getPrioritizedSubusers($userId, $limit = null)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return collect([]);
        }

        $totalMaxSubusers = self::totalMaxSubusers($userId);
        $actualSubusersCount = $user->subusers()->count();

        if ($totalMaxSubusers >= $actualSubusersCount) {
            // All subusers can be shown, activate all
            $user->subusers()->update(['status' => true]);
            return $user->subusers()->orderBy('created_at', 'DESC')->get();
        }

        if ($limit === null) {
            $limit = $totalMaxSubusers;
        }

        $subusers = $user->subusers()
            ->with(['serviceOrders' => function($query) {
                $query->whereIn('order_status', ['pending', 'processing', 'completed']);
            }])
            ->get()
            ->map(function($subuser) {
                $activeOrders = $subuser->serviceOrders->whereIn('order_status', ['pending', 'processing'])->count();
                $completedOrders = $subuser->serviceOrders->where('order_status', 'completed')->count();
                
                // Check if subuser has active customer offers
                $activeOffers = \App\Models\CustomerOffer::where('subuser_id', $subuser->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->count();
                
                // Check if subuser has active customer briefs
                $activeBriefs = \App\Models\CustomerBrief::where('subuser_id', $subuser->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();
                
                // Check if subuser has chats
                $hasChats = \App\Models\DirectChat::where('subuser_id', $subuser->id)->exists();
                
                // Priority: Active orders (10000) > Active customer offers (1000) > Active customer briefs (500) > Completed orders (100) > Chats (10) > Others (1)
                $priority = ($activeOrders * 10000) + ($activeOffers * 1000) + ($activeBriefs * 500) + ($completedOrders * 100) + ($hasChats ? 10 : 0) + 1;
                
                return [
                    'subuser' => $subuser,
                    'priority' => $priority,
                    'active_orders' => $activeOrders,
                    'active_offers' => $activeOffers,
                    'active_briefs' => $activeBriefs,
                    'completed_orders' => $completedOrders,
                    'has_chats' => $hasChats
                ];
            })
            ->sortByDesc('priority')
            ->take($limit)
            ->pluck('subuser');

        // Update subuser statuses based on prioritization
        $shownSubuserIds = $subusers->pluck('id')->toArray();
        
        // Activate shown subusers
        $user->subusers()->whereIn('id', $shownSubuserIds)->update(['status' => true]);
        
        // Deactivate non-shown subusers
        $user->subusers()->whereNotIn('id', $shownSubuserIds)->update(['status' => false]);

        return $subusers;
    }

    /**
     * Manually update subuser statuses for a user based on current package limits
     */
    public static function updateSubuserStatuses($userId)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return false;
        }

        $totalMaxSubusers = self::totalMaxSubusers($userId);
        $actualSubusersCount = $user->subusers()->count();

        if ($totalMaxSubusers >= $actualSubusersCount) {
            // All subusers can be active
            $user->subusers()->update(['status' => true]);
            return true;
        }

        // Get prioritized subusers and update statuses
        self::getPrioritizedSubusers($userId, $totalMaxSubusers);
        return true;
    }

    /**
     * Get seller services that are within the package limit (without updating database status)
     */
    public static function getSellerServicesWithinLimit($sellerId, $limit = null, $languageId = null)
    {
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            return collect([]);
        }

        $totalServices = $seller->services()->count();
        
        if ($limit === null || $limit == 0) {
            // Unlimited or no services allowed
            return collect([]);
        }

        if ($totalServices <= $limit) {
            // All services are within limit
            return $seller->services()->orderBy('created_at', 'DESC')->get();
        }

        // Get all services for this seller with prioritization logic
        $allServices = \App\Models\ClientService\Service::with(['order', 'content', 'review', 'wishlist'])
        ->where('seller_id', $sellerId)
        ->where('service_status', 1)
        ->get()
        ->map(function($service) use ($languageId) {
            $activeOrders = $service->order->whereIn('order_status', ['pending', 'processing'])->count();
            $completedOrders = $service->order->where('order_status', 'completed')->count();
            
            // Priority: Active orders (10000) > Completed orders (100) > Others (1)
            $priority = ($activeOrders * 10000) + ($completedOrders * 100) + 1;
            
            // Get content for the specific language
            $content = $service->content->where('language_id', $languageId)->first();
            if ($content) {
                $service->slug = $content->slug;
                $service->title = $content->title;
                $service->service_category_id = $content->service_category_id;
            }
            
            return [
                'service' => $service,
                'priority' => $priority,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'has_content' => $content ? true : false
            ];
        })
        ->sortByDesc('priority')
        ->filter(function($item) {
            return $item['has_content']; // Only keep services with content
        })
        ->take($limit)
        ->pluck('service')
        ->values(); // Ensure we get a proper collection

        return $allServices;
    }

    /**
     * Get seller services that are within the package limit for seller dashboard display
     * This method returns the IDs of services that should be considered "within limit"
     * based on prioritization logic, but shows all services in the dashboard
     */
    public static function getSellerServicesWithinLimitForDashboard($sellerId, $limit = null, $languageId = null)
    {
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            return collect([]);
        }

        $totalServices = $seller->services()->count();
        
        if ($limit === null || $limit == 0) {
            // Unlimited or no services allowed
            return collect([]);
        }

        if ($totalServices <= $limit) {
            // All services are within limit
            return $seller->services()->pluck('id');
        }

        // Get all services for this seller with prioritization logic
        $allServices = \App\Models\ClientService\Service::with(['order', 'content', 'review', 'wishlist'])
        ->where('seller_id', $sellerId)
        ->where('service_status', 1)
        ->get()
        ->map(function($service) use ($languageId) {
            $activeOrders = $service->order->whereIn('order_status', ['pending', 'processing'])->count();
            $completedOrders = $service->order->where('order_status', 'completed')->count();
            
            // Priority: Active orders (10000) > Completed orders (100) > Others (1)
            $priority = ($activeOrders * 10000) + ($completedOrders * 100) + 1;
            
            // Get content for the specific language
            $content = $service->content->where('language_id', $languageId)->first();
            
            return [
                'service' => $service,
                'priority' => $priority,
                'active_orders' => $activeOrders,
                'completed_orders' => $completedOrders,
                'has_content' => $content ? true : false
            ];
        })
        ->sortByDesc('priority')
        ->filter(function($item) {
            return $item['has_content']; // Only keep services with content
        })
        ->take($limit)
        ->pluck('service.id')
        ->values(); // Ensure we get a proper collection

        return $allServices;
    }

    /**
     * Get seller forms that are within the package limit (without updating database status)
     */
    public static function getSellerFormsWithinLimit($sellerId, $limit = null)
    {
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            return collect([]);
        }

        $totalForms = $seller->forms()->count();
        
        if ($limit === null || $limit == 0) {
            // Unlimited or no forms allowed
            return collect([]);
        }

        if ($totalForms <= $limit) {
            // All forms are within limit
            return $seller->forms()->orderBy('created_at', 'DESC')->get();
        }

        // Get forms with prioritization logic based on service usage
        $forms = $seller->forms()
            ->with(['serviceContents' => function($query) {
                $query->with(['service' => function($serviceQuery) {
                    $serviceQuery->select('id', 'service_status');
                }]);
            }])
            ->get()
            ->map(function($form) {
                // Count forms used in active services (service_status = 1)
                $activeServicesCount = $form->serviceContents->where('service.service_status', 1)->count();
                
                // Count forms used in inactive services (service_status = 0)
                $inactiveServicesCount = $form->serviceContents->where('service.service_status', 0)->count();
                
                // Priority: Active services (10000) > Inactive services (100) > Others (1)
                $priority = ($activeServicesCount * 10000) + ($inactiveServicesCount * 100) + 1;
                
                return [
                    'form' => $form,
                    'priority' => $priority,
                    'active_services' => $activeServicesCount,
                    'inactive_services' => $inactiveServicesCount
                ];
            })
            ->sortByDesc('priority')
            ->take($limit)
            ->pluck('form');

        return $forms;
    }

    /**
     * Check if user can create new chats
     */
    public static function canCreateNewChats($userId)
    {
        return self::hasActiveMembership($userId);
    }

    /**
     * Get subusers that are within the package limit (without updating database status)
     */
    public static function getSubusersWithinLimit($userId, $limit = null)
    {
        $user = \App\Models\User::find($userId);
        if (!$user) {
            return collect([]);
        }

        $totalMaxSubusers = self::totalMaxSubusers($userId);
        $actualSubusersCount = $user->subusers()->count();

        if ($totalMaxSubusers >= $actualSubusersCount) {
            // All subusers are within limit
            return $user->subusers()->orderBy('created_at', 'DESC')->get();
        }

        if ($limit === null) {
            $limit = $totalMaxSubusers;
        }

        $subusers = $user->subusers()
            ->with(['serviceOrders' => function($query) {
                $query->whereIn('order_status', ['pending', 'processing', 'completed']);
            }])
            ->get()
            ->map(function($subuser) {
                $activeOrders = $subuser->serviceOrders->whereIn('order_status', ['pending', 'processing'])->count();
                $completedOrders = $subuser->serviceOrders->where('order_status', 'completed')->count();
                
                // Check if subuser has active customer offers
                $activeOffers = \App\Models\CustomerOffer::where('subuser_id', $subuser->id)
                    ->whereIn('status', ['pending', 'accepted'])
                    ->count();
                
                // Check if subuser has active customer briefs
                $activeBriefs = \App\Models\CustomerBrief::where('subuser_id', $subuser->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->count();
                
                // Check if subuser has chats
                $hasChats = \App\Models\DirectChat::where('subuser_id', $subuser->id)->exists();
                
                // Priority: Active orders (10000) > Active customer offers (1000) > Active customer briefs (500) > Completed orders (100) > Chats (10) > Others (1)
                $priority = ($activeOrders * 10000) + ($activeOffers * 1000) + ($activeBriefs * 500) + ($completedOrders * 100) + ($hasChats ? 10 : 0) + 1;
                
                return [
                    'subuser' => $subuser,
                    'priority' => $priority,
                    'active_orders' => $activeOrders,
                    'active_offers' => $activeOffers,
                    'active_briefs' => $activeBriefs,
                    'completed_orders' => $completedOrders,
                    'has_chats' => $hasChats
                ];
            })
            ->sortByDesc('priority')
            ->take($limit)
            ->pluck('subuser');

        return $subusers;
    }

    /**
     * Get prioritized subusers and update their status in database
     */
} 