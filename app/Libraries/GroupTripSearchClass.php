<?php

namespace App\Libraries;

use App\Enums\Status;
use App\Http\Resources\GroupTripResource;
use App\Models\GroupTrip;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class GroupTripSearchClass extends SearchClass
{
    /**
     * Create a new class instance.
     */
    protected bool $hasOffer = false;
    protected array $ignore_order = [];

    public function __construct()
    {
        parent::__construct();
        $this->status = Status::PENDING->value;
    }

    public function setStatus(string $status = 'pending'): void
    {
        if(Auth::guard('api-admin')->check()){
            if(in_array($status,Status::values())){
                $this->status = $status;
            }
        }
        else{
            if(in_array($status,Status::userValues())){
                $this->status = $status;
            }
        }
    }

    public function withOffer(bool $hasOffer = false): void
    {
            $this->hasOffer = $hasOffer;

    }

    public function search()
    {
        $groups = $this->prepare(GroupTrip::query());
        $groups = $this->checkOrderType($groups);
        $groups =$this->hasOffer ? $groups->hasOffer() : $groups;
        return GroupTripResource::collection($groups->where('status' ,'=' ,$this->status)->get());
    }

    public function checkOrderType(Builder $query) :Builder
    {
        $groups = $query->groupTripWithRate();
        if(!in_array($this->orderBy, $this->ignore_order)){
            $groups = $groups->orderBy($this->orderBy,$this->order_type);
        }
        return $groups;
    }
}
