<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DaysOff extends Model
{
    protected $fillable = ['date','guide_id'];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
