<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subuser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'first_name',
        'last_name',
        'image',
        'phone_number',
        'address',
        'city',
        'state',
        'country',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function serviceOrders()
    {
        return $this->hasMany(\App\Models\ClientService\ServiceOrder::class, 'subuser_id');
    }

    public function messages()
    {
        return $this->hasMany(\App\Models\ClientService\ServiceOrderMessage::class, 'subuser_id');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
} 