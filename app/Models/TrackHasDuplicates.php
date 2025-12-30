<?php

namespace App\Models;

use App\Traits\CanGetTableNameStatically;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class TrackHasDuplicates extends Pivot
{
    use CanGetTableNameStatically;

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
