<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class GroupTrip extends Model
{
    /** @use HasFactory<\Database\Factories\GroupTripFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'starting_date',
        'ending_date',
        'status',
        'price',
        'tickets_count',
        'stars_count',
        'reviews_count',
        'guide_id'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function feedbacks(): MorphMany
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

    public function reservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function guideReservations(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_group_trip', 'group_trip_id', 'event_id')
            ->withTimestamps();
    }

    public function report(): HasOne
    {
        return $this->hasOne(Report::class);
    }

    public function cities(): HasManyThrough
    {
        return $this->hasManyThrough(
            City::class,
            Event::class,
            'id',
            'id',
            'id',
            'city_id'
        );
    }
}
