<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'ticket_price',
        'ticket_count',
        'status',
        'stars_count',
        'ticket_limit',
        'city_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function feedbacks(): MorphMany
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

    public function reservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function soloTrips(): BelongsToMany
    {
        return $this->belongsToMany(SoloTrip::class, 'solo_trips_events', 'event_id', 'solo_trip_id')
            ->withPivot('price')
            ->withTimestamps();
    }

    public function groupTrips(): BelongsToMany
    {
        return $this->belongsToMany(GroupTrip::class,'group_trip_event','event_id','group_trip_id')
            ->withTimestamps();
    }
}
