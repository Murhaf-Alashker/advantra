<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportsLog extends Model
{
    /** @use HasFactory<\Database\Factories\ReportsLogFactory> */
    use HasFactory;

    protected $fillable = [
        'file_path',
        'guide_id',
        'group_trip_id'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function groupTrip(): BelongsTo
    {
        return $this->belongsTo(GroupTrip::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
