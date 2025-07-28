<?php

namespace App\Models;

use App\Models\Scopes\CheckLimitScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LimitedEvents extends Model
{
    protected $fillable = [
        'remaining_tickets',
        'tickets_count',
        'tickets_limit',
        'start_date',
        'end_date',
        'event_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'start_date' => 'date:Y-m-d H:i:s',
            'end_date' => 'date:Y-m-d H:i:s',
        ];
    }

    protected static function booted()
    {
        static::addGlobalScope(CheckLimitScope::class);
    }

    public function event(): belongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
