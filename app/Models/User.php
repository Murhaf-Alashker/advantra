<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Scopes\ActiveScope;
use App\Models\Scopes\WithMediaScope;
use App\Traits\MediaHandler;
use Carbon\Carbon;
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



    public function groupTrips($date = null)
    {
        return GroupTrip::whereHas('reservations', function ($query) use ($date) {
            if(!$date){
                $query->where('user_id',$this->id);
            }
            else {
                $query->where('user_id', $this->id)
                      ->whereMonth('created_at', '=', Carbon::parse($date)->month)
                      ->whereYear('created_at', '=', Carbon::parse($date)->year);
            }
        })->get();
    }

    public function allEvents($date = null)
    {
        $dateFilter = function ($query, $table) use ($date) {
            $query->where('user_id', $this->id);

            if ($date) {
                $month = Carbon::parse($date)->month;
                $year  = Carbon::parse($date)->year;

                $query->whereMonth("$table.created_at", $month)
                    ->whereYear("$table.created_at", $year);
            }
        };

        return Event::withTrashed()
            ->distinct()
            ->where(function ($query) use ($dateFilter) {
                $query->whereHas('reservations', fn($q) => $dateFilter($q, 'reservations'))
                    ->orWhereHas('groupTrips.reservations', fn($q) => $dateFilter($q, 'reservations'));
            })
            ->get();
    }

    public function directEvents($date = null)
    {
        return Event::withTrashed()
                    ->whereHas('reservations', fn ($q) =>
                        $q->where('user_id', $this->id)
                    );

    }

    protected function groupTripEvents($date = null)
    {
        return Event::withTrashed()
                    ->whereHas('groupTrips', fn($query) =>
                                            $query->whereHas('reservations', fn($q) =>
                                                $q->where('user_id', $this->id)
                                                    ->where('reservable_type', GroupTrip::class)
                                            )
                            );
    }


    protected function soloTripEvents($date = null)
    {
        return Event::withTrashed()
                    ->whereHas('soloTrips', fn ($q) =>
                        $q->where('user_id',$this->id)
                    );
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

}
