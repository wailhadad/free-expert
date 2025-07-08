<?php

namespace App\Models\ClientService;

use App\Models\Admin;
use App\Models\ClientService\ServiceOrder;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrderMessage extends Model
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
    return $this->belongsTo(User::class, 'person_id', 'id');
  }

  public function admin()
  {
    return $this->belongsTo(Admin::class, 'person_id', 'id');
  }
  public function seller()
  {
    return $this->belongsTo(Seller::class, 'person_id', 'id');
  }

  public function order()
  {
    return $this->belongsTo(ServiceOrder::class, 'order_id', 'id');
  }

  public function subuser()
  {
    return $this->belongsTo(\App\Models\Subuser::class, 'subuser_id', 'id');
  }

  public function getMessageSenderNameAttribute()
  {
    if ($this->subuser) {
      return $this->subuser->full_name;
    } elseif ($this->user) {
      return $this->user->first_name . ' ' . $this->user->last_name;
    } elseif ($this->admin) {
      return $this->admin->name;
    } elseif ($this->seller) {
      return $this->seller->username;
    }
    return 'Unknown';
  }

  public function getMessageSenderImageAttribute()
  {
    if ($this->subuser) {
      return $this->subuser->image;
    } elseif ($this->user) {
      return $this->user->image;
    } elseif ($this->admin) {
      return $this->admin->image;
    } elseif ($this->seller) {
      return $this->seller->photo;
    }
    return null;
  }

  public function getMessageSenderUsernameAttribute()
  {
    if ($this->subuser) {
      return $this->subuser->username;
    } elseif ($this->user) {
      return $this->user->username;
    } elseif ($this->admin) {
      return $this->admin->username;
    } elseif ($this->seller) {
      return $this->seller->username;
    }
    return 'Unknown';
  }
}
