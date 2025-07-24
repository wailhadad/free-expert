<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipModificationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'package_id',
        'status',
        'requested_at',
        'applied_at',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
} 