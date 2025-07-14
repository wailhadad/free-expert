<?php

namespace App\Models\ClientService;

use App\Models\ClientService\Service;
use App\Models\ClientService\ServiceOrderMessage;
use App\Models\ClientService\ServicePackage;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
  use HasFactory;

  /**
   * The attributes that aren't mass assignable.
   *
   * @var array
   */
  protected $guarded = [];

  public function user()
  {
    return $this->belongsTo(User::class, 'user_id', 'id');
  }
  public function seller()
  {
    return $this->belongsTo(Seller::class, 'seller_id', 'id');
  }

  public function service()
  {
    return $this->belongsTo(Service::class, 'service_id', 'id');
  }

  public function package()
  {
    return $this->belongsTo(ServicePackage::class, 'package_id', 'id');
  }

  public function message()
  {
    return $this->hasMany(ServiceOrderMessage::class, 'order_id', 'id');
  }

  public function subuser()
  {
    return $this->belongsTo(\App\Models\Subuser::class, 'subuser_id', 'id');
  }

  public function customerOffer()
  {
    return $this->hasOne(\App\Models\CustomerOffer::class, 'accepted_order_id');
  }

  public function getOrderCustomerNameAttribute()
  {
    return $this->subuser ? $this->subuser->full_name : $this->user->first_name . ' ' . $this->user->last_name;
  }

  public function getOrderCustomerImageAttribute()
  {
    return $this->subuser ? $this->subuser->image : $this->user->image;
  }

  public function getOrderCustomerUsernameAttribute()
  {
    return $this->subuser ? $this->subuser->username : $this->user->username;
  }
}
