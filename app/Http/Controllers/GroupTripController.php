<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\CreateGroupTripRequest;
use App\Http\Resources\GroupTripResource;
use App\Libraries\FileManager;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Scopes\ActiveScope;
use App\Services\GroupTripService;
use Illuminate\Http\Request;
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

    public function show($groupTripId)
    {
        $groupTrip = GroupTrip::findOrFail($groupTripId);

        return $this->groupTripService->show($groupTrip);
    }

    public function store(CreateGroupTripRequest $request)
    {
        $groupTrip = DB::transaction(function () use ($request) {

            return $this->groupTripService->store($request->validated());

        });
        $groupTrip->load('media');
        return response()->json(new GroupTripResource($groupTrip),201);
    }

    public function destroy($groupTripId)
    {
        $groupTrip = GroupTrip::withoutGlobalScope(ActiveScope::class)->findOrFail($groupTripId);

        if($groupTrip->status !== Status::FINISHED->value){
            return response()->json(['message' => __('message.cannot_delete_unfinished_group_trip')], 400);
        }
        $this->groupTripService->destroy($groupTrip);
        return response()->json(['message' => __('message.deleted_successfully',['attribute' => 'message.attributes.group_trip'])], 204);
    }
}
