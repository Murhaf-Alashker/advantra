<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable , SoftDeletes;

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

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
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
        return $this->directEvents()
            ->merge($this->soloTripEvents())
            ->merge($this->groupTripEvents());
    }

    public function directEvents(): HasManyThrough
    {
        return $this->hasManyThrough(
            Event::class,
            Reservation::class,
            'user_id',
            'id',
            'id',
            'reservable_id')
            ->where('reservable_type', Event::class);
    }

    protected function groupTripEvents()
    {
        return Event::whereHas('groupTrips', function ($query) {
            $query->whereHas('reservations', function ($reservationQuery) {
                $reservationQuery->where('user_id', $this->id)
                    ->where('reservable_type', GroupTrip::class);
            });
        })->get();
    }

    protected function soloTripEvents(): HasManyThrough
    {
        return $this->HasManyThrough(
            Event::class,
            SoloTrip::class,
            'user_id',
            'id',
            'id',
            'id'
        );
    }

}
