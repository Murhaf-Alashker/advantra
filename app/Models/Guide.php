<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use SebastianBergmann\CodeCoverage\Report\Xml\Report;

class Guide extends Model
{
    /** @use HasFactory<\Database\Factories\GuideFactory> */
    use HasFactory,Notifiable,SoftDeletes,HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'description',
        'status',
        'price',
        'const_salary',
        'extra_salary',
        'stars_count',
        'reviews_count',
        'fcm_token',
        'city_id'
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function feedbacks():MorphMany
    {
        return $this->morphMany(Feedback::class,'feedbackable');
    }

    public function groupTrips(): HasMany
    {
        return $this->hasMany(GroupTrip::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class,'mediable');
    }

    public function chats(): HasMany
    {
        return $this->hasMany(Chat::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function translations(): MorphMany
    {
        return $this->morphMany(Translation::class,'translatable');
    }

    public function translate($column)
    {
        return $this->translations()->where('key', '=', 'guide.' . $column)
            ->value('translation');
    }

    public function languages(): belongsToMany
    {
        return $this->belongsToMany(Language::class)
            ->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)
            ->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function scopeActiveGuides($query)
    {
        return $query->where('status', '=', 'active');
    }

    public function scopeGuideWithRate($query)
    {
        return $query->selectRaw('guides.*, ROUND(stars_count / NULLIF(reviews_count, 0), 1) as rating');
    }
}
