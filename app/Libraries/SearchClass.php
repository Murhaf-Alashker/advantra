<?php

namespace App\Libraries;

use App\Models\City;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;
use function Laravel\Prompts\search;

class SearchClass
{
    protected string $contains = '';

    protected ?string $minPrice = null;
    protected ?string $maxPrice = null;
    protected string $orderBy = 'created_at ';

    protected string $status = 'active';
    protected string $order_type = 'DESC';
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
    }

    public function setContains(string $contains = null): void
    {
        if($contains !== null) {
            $key = strip_tags($contains);
            $key = preg_replace('/\s+/', ' ', $key);
            $key = trim($key);
            $this->contains = $key;
        }
    }

    public function setPrice(string $minPrice = null,string $maxPrice = null): void
    {
        if($minPrice !== null) {
            $this->minPrice = $minPrice;
        }
        if($maxPrice !== null) {
            $this->maxPrice = $maxPrice;
        }
    }
    public function setOrderBy(string $orderBy = null): void
    {
        if($orderBy !== null) {
            $this->orderBy = $orderBy;
        }
    }

    public function setOrderType(string $orderType = null): void
    {
        if($orderType !== null) {
            $this->order_type = $orderType;
        }
    }

    protected function prepare(Builder $model):Builder
    {
        $model = $this->minPrice ? $model->where('price', '>=', $this->minPrice) : $model ;
        $model = $this->maxPrice ? $model->where('price', '<=', $this->maxPrice) : $model ;
        if (strlen($this->contains ?? '') > 0) {
            $model->where(function ($q) {
                $q->where('name', 'like', '%'.$this->contains.'%')
                    ->orWhere('description', 'like', '%'.$this->contains.'%');
            });
        }
        return $model;


    }
}
