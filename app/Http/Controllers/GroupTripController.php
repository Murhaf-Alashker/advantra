<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\CreateGroupTripRequest;
use App\Http\Requests\OfferRequest;
use App\Http\Resources\GroupTripResource;
use App\Models\GroupTrip;
use App\Services\GroupTripService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GroupTripController extends Controller
{
    protected GroupTripService $groupTripService;

    public function __construct(GroupTripService $groupTripService)
    {
        $this->groupTripService = $groupTripService;
    }

    public function index()
    {
        return $this->groupTripService->index();
    }

    public function show(GroupTrip $groupTrip)
    {
        return $this->groupTripService->show($groupTrip);
    }

    public function store(CreateGroupTripRequest $request)
    {
        $groupTrip = DB::transaction(function () use ($request) {

            return $this->groupTripService->store($request->validated());

        });
        return response()->json(new GroupTripResource($groupTrip),201);
    }

    public function destroy(GroupTrip $groupTrip)
    {
        if($groupTrip->status !== Status::FINISHED->value){
            return response()->json(['message' => __('message.cannot_delete_unfinished_group_trip')], 400);
        }
        $this->groupTripService->destroy($groupTrip);
        return response()->json(['message' => __('message.deleted_successfully',['attribute' => 'message.attributes.group_trip'])], 204);
    }

    public function makeOffer(OfferRequest $request,GroupTrip $groupTrip)
    {
        if(Carbon::parse($groupTrip->starting_date)->lessThan($request->end_date)){
            return response()->json(['message' => __('message.invalid_offer_date')],400);
        }
        if($groupTrip->hasOffer()){
            return response()->json(['message' => __('message.has_already_offer',['attribute' => 'message.attributes.group_trip'])],400);
        }
        $offer = $this->groupTripService->makeOffer($request->validated(),$groupTrip);
        if(!$offer){
            return response()->json(['message' => __('message.something_wrong')], 400);
        }
        return response()->json(['message' => __('message.created_successfully',['attribute' => 'message.attributes.offer'])],201);
    }
}
