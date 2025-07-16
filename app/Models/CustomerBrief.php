<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerBrief extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subuser_id',
        'title',
        'description',
        'delivery_time',
        'tags',
        'attachments',
        'attachment_names',
        'price',
        'request_quote',
        'status',
    ];

    protected $casts = [
        'tags' => 'array', // If you want to store as JSON, otherwise handle as string
        'request_quote' => 'boolean',
        'attachments' => 'array',
        'attachment_names' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function subuser()
    {
        return $this->belongsTo(\App\Models\Subuser::class, 'subuser_id');
    }

    /**
     * Get attachments as array
     */
    public function getAttachmentsArray()
    {
        if (is_string($this->attachments)) {
            return json_decode($this->attachments, true) ?: [];
        }
        return $this->attachments ?: [];
    }

    /**
     * Get attachment names as array
     */
    public function getAttachmentNamesArray()
    {
        if (is_string($this->attachment_names)) {
            return json_decode($this->attachment_names, true) ?: [];
        }
        return $this->attachment_names ?: [];
    }

    /**
     * Check if brief has attachments
     */
    public function hasAttachments()
    {
        $attachments = $this->getAttachmentsArray();
        return !empty($attachments);
    }

    /**
     * Get attachment count
     */
    public function getAttachmentCount()
    {
        return count($this->getAttachmentsArray());
    }
} 