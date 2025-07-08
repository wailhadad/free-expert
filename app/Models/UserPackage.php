<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'price',
        'term',
        'is_trial',
        'trial_days',
        'status',
        'max_subusers',
        'custom_features',
        'recommended'
    ];

    public function memberships()
    {
        return $this->hasMany(UserMembership::class, 'package_id');
    }
} 