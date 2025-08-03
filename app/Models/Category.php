<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;



class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function events():HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function translations():MorphMany
    {
        return $this->morphMany(Translation::class, 'translatable');
    }

    public function translate($column)
    {
        return $this->translations()->where('key', '=', 'category.' . $column)
            ->value('translation');
    }

    public function guides(): BelongsToMany
    {
        return $this->belongsToMany(Guide::class)
            ->withTimestamps();
    }
}
