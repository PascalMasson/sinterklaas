<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Fopper extends Model implements HasMedia
{
    use SoftDeletes, InteractsWithMedia;

    protected $fillable =[
        "inhoud", "created_by_user_id", "created_for_user_id", "registerMediaConversionsUsingModelInstance"
    ];

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdForUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_for_user_id');
    }
}
