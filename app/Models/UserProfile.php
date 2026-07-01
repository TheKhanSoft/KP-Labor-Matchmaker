<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'company_name',
        'company_email',
        'address',
        'sector',
        'alternate_phone',
        'district',
        'city',
        'province',
        'additional_details',
    ];

    protected $casts = [
        'additional_details' => 'array',
    ];

    /**
     * Get the user that owns this profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
