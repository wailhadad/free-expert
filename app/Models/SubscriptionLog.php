<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionLog extends Model
{
    protected $fillable = [
        'seller_id',
        'membership_id',
        'package_id',
        'action',
        'description',
    ];
} 