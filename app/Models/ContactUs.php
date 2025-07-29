<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactUs extends Model
{
    /** @use HasFactory<\Database\Factories\ContactUsFactory> */
    use HasFactory;

    protected $fillable = [
        'body',
        'is_read',
        'user_id'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }

    public function user():BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
