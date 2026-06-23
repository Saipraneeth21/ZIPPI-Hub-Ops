<?php

namespace App\Models\Rental;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HubStaff extends Model
{
    protected $table = 'hub_staff';

    protected $guarded = [];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function hub(): BelongsTo
    {
        return $this->belongsTo(Hub::class, 'hub_id');
    }
}
