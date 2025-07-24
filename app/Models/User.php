<?php

namespace App\Models;

use App\Models\ClientService\ServiceOrder;
use App\Models\ClientService\ServiceOrderMessage;
use App\Models\ClientService\ServiceReview;
use App\Models\ClientService\WishlistService;
use App\Models\Shop\ProductOrder;
use App\Models\Shop\ProductReview;
use App\Models\Shop\WishlistProduct;
use App\Models\SupportTicket;
use App\Models\TicketConversation;;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  /**
   * The attributes that should be hidden for arrays.
   *
   * @var array
   */
  protected $hidden = [
    'password',
    'remember_token',
  ];

  /**
   * The attributes that should be cast to native types.
   *
   * @var array
   */
  protected $casts = [
    'email_verified_at' => 'datetime',
  ];

  public function productReview()
  {
    return $this->hasMany(ProductReview::class, 'user_id', 'id');
  }

  public function serviceOrder()
  {
    return $this->hasMany(ServiceOrder::class, 'user_id', 'id');
  }

  public function serviceReview()
  {
    return $this->hasMany(ServiceReview::class, 'user_id', 'id');
  }

  public function wishlistedService()
  {
    return $this->hasMany(WishlistService::class, 'user_id', 'id');
  }

  public function wishlistedProduct()
  {
    return $this->hasMany(WishlistProduct::class, 'user_id', 'id');
  }

  public function message()
  {
    return $this->hasMany(ServiceOrderMessage::class, 'person_id', 'id');
  }

  public function ticket()
  {
    return $this->hasMany(SupportTicket::class, 'user_id', 'id');
  }

  public function ticketConversation()
  {
    return $this->hasMany(TicketConversation::class, 'person_id', 'id');
  }

  public function wishlistedProducts()
  {
    return $this->hasMany(WishlistProduct::class, 'user_id', 'id');
  }

  public function supportTickets()
  {
    return $this->hasMany(SupportTicket::class, 'user_id', 'id');
  }

  public function subusers()
  {
    return $this->hasMany(Subuser::class, 'user_id');
  }

  public function userMemberships()
  {
    return $this->hasMany(UserMembership::class, 'user_id');
  }

  public function currentUserMembership()
  {
    // First check for active membership (not expired)
    $activeMembership = $this->hasOne(UserMembership::class, 'user_id')
      ->where('status', '1')
      ->where('start_date', '<=', \Carbon\Carbon::now())
      ->where('expire_date', '>=', \Carbon\Carbon::now());
    
    if ($activeMembership->exists()) {
      return $activeMembership;
    }
    
    // If no active membership, check for grace period membership
    return $this->hasOne(UserMembership::class, 'user_id')
      ->where('status', '1')
      ->where('in_grace_period', 1)
      ->where('grace_period_until', '>', \Carbon\Carbon::now());
  }

  public function hasAgencyPrivileges()
  {
    // Check for active membership (including grace period)
    $membership = $this->currentUserMembership;
    if (!$membership) {
      return false;
    }
    
    $package = $membership->package;
    return $package && $package->max_subusers > 0;
  }

  public function getMaxSubusersAttribute()
  {
    // Get total max subusers from all active memberships (including grace period)
    return \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($this->id);
  }

  public function getCurrentSubusersCountAttribute()
  {
    return $this->subusers()->count();
  }

  public function canCreateSubuser()
  {
    return $this->hasAgencyPrivileges() && 
           $this->current_subusers_count < $this->max_subusers;
  }
}
