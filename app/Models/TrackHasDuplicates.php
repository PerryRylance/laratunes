<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TrackHasDuplicates extends Pivot
{
    protected $table = 'track_has_duplicates';

    public function original(): BelongsTo
    {
        return $this->belongsTo(Track::class, ownerKey: 'original_id');
    }

    public function duplicate(): BelongsTo
    {
        return $this->belongsTo(Track::class, ownerKey: 'duplicate_id');
    }
}
