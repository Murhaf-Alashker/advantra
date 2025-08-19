<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\CheckLimitScope;
use App\Models\Scopes\CheckOfferScope;
use App\Models\Scopes\WithMediaScope;
use App\Traits\MediaHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Event extends Model
{
    /** @use HasFactory<\Database\Factories\EventFactory> */
    use HasFactory,SoftDeletes,MediaHandler;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'basic_cost',
        'price',
        'status',
        'stars_count',
        'reviewer_count',
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

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }
    public function translate($column)
    {
        return $this->translations()->where('key', '=', 'event.' . $column)
            ->value('translation');
    }

    public function soloTrips(): BelongsToMany
    {
        return $this->belongsToMany(SoloTrip::class, 'event_solo_trip', 'event_id', 'solo_trip_id')
            ->withPivot('price','tickets_count')
            ->withTimestamps();
    }

    public function groupTrips(): BelongsToMany
    {
        return $this->belongsToMany(GroupTrip::class,'event_group_trip','event_id','group_trip_id')
            ->withTimestamps();
    }

    public function offers(): morphMany
    {
        return $this->morphMany(Offer::class, 'offerable');
    }

    public function limitedEvents(): HasMany
    {
        return $this->hasMany(LimitedEvents::class, 'event_id', 'id');
    }

    public function guides()
    {
        return Guide::where('city_id', $this->city_id);
    }

    public function scopeActiveEvents($query)
    {
        return $query->where('status', '=', 'active');
    }

    public function scopeEventWithRate($query)
    {
        return $query->selectRaw('events.*, ROUND(stars_count / NULLIF(reviewer_count, 0), 1) as rating');
    }

    public function scopeHasOffer($query)
    {
        return $query->whereHas('offers');
    }

    public function scopeWithoutOffer($query)
    {
        return $query->whereDoesntHave('offers');
    }

    public function scopeWhereIsLimited($query)
    {
        return $query->whereHas('limitedEvents');
    }

    public function scopeWhereIsNotLimited($query)
    {
        return $query->whereDoesntHave('limitedEvents');
    }

    public function hasOffer(): bool
    {
        return $this->offers()->exists();
    }

    public function hasAnyOffer(): bool
    {
        return $this->offers()->withoutGlobalScope(CheckOfferScope::class)->where('end_date', '>', Carbon::now())->exists();
    }

    public function isLimited(): bool
    {
        return $this->limitedEvents()->exists();
    }

    public function isEnded():bool
    {
        return $this->limitedEvents()->withoutGlobalScope(CheckLimitScope::class)->where('end_date', '<', Carbon::now())->exists();
    }

}
