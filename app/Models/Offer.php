<?php

namespace App\Models;

use App\Models\Scopes\CheckOfferScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Offer extends Model
{
    /** @use HasFactory<\Database\Factories\OfferFactory> */
    use HasFactory;

    protected $fillable=[
        'offerable',
        'discount',
        'start_date',
        'end_date',
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
        static::addGlobalScope(CheckOfferScope::class);
    }

    public function offerable(): morphTo
    {
        return $this->morphTo();
    }
}
