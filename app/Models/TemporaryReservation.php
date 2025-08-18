<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemporaryReservation extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'model',
        'model_id',
        'tickets_count',
        'task_date',
    ];


}
