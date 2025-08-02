<?php

namespace App\Models;

use App\Enums\Status;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\WithMediaScope;
use App\Traits\MediaHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class GroupTrip extends Model
{
    /** @use HasFactory<\Database\Factories\GroupTripFactory> */
    use HasFactory,MediaHandler;

    protected $fillable = [
        'name',
        'description',
        'starting_date',
        'ending_date',
        'basic_cost',
        'extra_cost',
        'status',
        'price',
        'tickets_count',
        'tickets_limit',
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

    public function translate($column)
    {
        return $this->translations()->where('key', '=', 'groupTrip.' . $column)
            ->value('translation');
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

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function cities()
    {
        $ids = $this->events()->pluck('city_id')->toArray() ?? [];
        return City::withoutGlobalScope(ActiveScope::class)->whereIn('id',$ids)->get();
    }

    public function offers(): morphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function scopeGroupTripWithRate($query)
    {
        return $query->selectRaw('group_trips.*, ROUND(stars_count / NULLIF(reviews_count, 0), 1) as rating');
    }

    public function scopeNotFinished($query)
    {
        return $query->where('status', '=', Status::PENDING)->orWhere('status', '=', Status::IN_PROGRESS);
    }

    public function scopeHasOffer($query)
    {
        return $query->whereHas('offers');
    }

    public function scopeWithoutOffer($query)
    {
        return $query->whereDoesntHave('offers');
    }

    public function hasOffer(): bool
    {
        return $this->offers()->exists();
    }
}
