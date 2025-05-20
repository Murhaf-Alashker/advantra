<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessInfo extends Model
{
    /** @use HasFactory<\Database\Factories\BusinessInfoFactory> */
    use HasFactory;

    protected $fillable = [
        'total_profit',
        'total_income',
        'reserved_tickets',
        'total_group_trips'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s',
            'updated_at' => 'datetime:Y-m-d H:i:s',
        ];
    }
}
