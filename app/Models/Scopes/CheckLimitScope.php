<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Carbon;

class CheckLimitScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('start_date', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                ->where('end_date', '>', Carbon::now()->format('Y-m-d H:i:s'));
    }
}
