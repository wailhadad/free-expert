<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'seller_id',
        'subuser_id',
        'brief_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function messages()
    {
        return $this->hasMany(DirectChatMessage::class, 'chat_id');
    }

    public function subuser()
    {
        return $this->belongsTo(\App\Models\Subuser::class, 'subuser_id');
    }
}
