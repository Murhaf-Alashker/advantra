<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\WithMediaScope;
use App\Traits\MediaHandler;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , SoftDeletes,HasApiTokens,MediaHandler;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'points',
        'status',
        'email_verified_at',
        'google_id',
        'fcm_token'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
            'email_verified_at' => 'datetime:Y-m-d H:i:s',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
       // static::addGlobalScope(new ActiveScope());
        static::addGlobalScope(new WithMediaScope());
    }

    public function contacts():HasMany
    {
        return $this->hasMany(ContactUs::class);
    }

    public function feedBacks():HasMany
    {
        return $this->hasMany(FeedBack::class);
    }

    public function soloTrips():HasMany
    {
        return $this->hasMany(SoloTrip::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function guideReservations(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }



    public function groupTrips()
    {
        return GroupTrip::whereHas('reservations', function ($query) {
            $query->where('user_id',$this->id)
                ->where('reservable_type',GroupTrip::class);
        })->get();
    }

    public function allEvents()
    {
        return Event::withoutGlobalScope(ActiveScope::class)
                    ->withTrashed()
                    ->distinct()->where(function ($query) {
                        $query->whereHas('reservations', fn($q) => $q->where('user_id', $this->id))
                            ->orWhereHas('soloTrips', fn($q) => $q->where('user_id', $this->id))
                            ->orWhereHas('groupTrips.reservations', fn($q) =>
                            $q->where('user_id', $this->id)
                                ->where('reservable_type', GroupTrip::class)
                );
        });
    }

    public function directEvents()
    {
        return Event::withoutGlobalScope(ActiveScope::class)
                    ->withTrashed()
                    ->whereHas('reservations', fn ($q) =>
                        $q->where('user_id', $this->id)
                    );

    }

    protected function groupTripEvents()
    {
        return Event::withoutGlobalScope(ActiveScope::class)
                    ->withTrashed()
                    ->whereHas('groupTrips', fn($query) =>
                                            $query->whereHas('reservations', fn($q) =>
                                                $q->where('user_id', $this->id)
                                                    ->where('reservable_type', GroupTrip::class)
                                            )
                            );
    }


    protected function soloTripEvents()
    {
        return Event::withoutGlobalScope(ActiveScope::class)
                    ->withTrashed()
                    ->whereHas('soloTrips', fn ($q) =>
                        $q->where('user_id',$this->id)
                    );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}
