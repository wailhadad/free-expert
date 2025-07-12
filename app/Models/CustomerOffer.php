<?php

namespace App\Models;

use App\Models\ClientService\Form;
use App\Models\ClientService\ServiceOrder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'seller_id',
        'user_id',
        'subuser_id',
        'form_id',
        'title',
        'description',
        'price',
        'currency_symbol',
        'status',
        'expires_at',
        'form_data',
        'accepted_order_id',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'form_data' => 'array',
    ];

    public function chat()
    {
        return $this->belongsTo(DirectChat::class, 'chat_id');
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function subuser()
    {
        return $this->belongsTo(Subuser::class, 'subuser_id');
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function acceptedOrder()
    {
        return $this->belongsTo(ServiceOrder::class, 'accepted_order_id');
    }

    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function canBeAccepted()
    {
        return in_array($this->status, ['pending', 'checkout_pending']) && !$this->isExpired();
    }

    public function getFormattedPriceAttribute()
    {
        return $this->currency_symbol . number_format($this->price, 2);
    }
} 