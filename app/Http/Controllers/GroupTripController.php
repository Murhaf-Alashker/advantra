<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Http\Requests\CreateGroupTripRequest;
use App\Http\Requests\OfferRequest;
use App\Http\Requests\UpdateGroupTripRequest;
use App\Http\Resources\GroupTripResource;
use App\Models\DaysOff;
use App\Models\Event;
use App\Models\GroupTrip;
use App\Models\Guide;
use App\Models\Task;
use App\Services\GroupTripService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public function store(CreateGroupTripRequest $request):JsonResponse
    {
        $tasksCount = Task::where('start_date', '<=',$request->input('ending_date'))->where('end_date', '>=',$request->input('starting_date'))->where('guide_id', $request->input('guide_id'))->count();

        $daysOff = DaysOff::whereBetween('date',[$request->input('starting_date'), $request->input('ending_date')])
            ->where('guide_id', $request->input('guide_id'))
            ->count();
        if($tasksCount > 0 || $daysOff > 0){
            return response()->json(['the guide is busy in this date'],400);
        }
        $groupTrip = DB::transaction(function () use ($request) {

            return $this->groupTripService->store($request->validated());

        });
        return response()->json(new GroupTripResource($groupTrip),201);
    }

    public function update(UpdateGroupTripRequest $request,GroupTrip $groupTrip)
    {
        $validated = $request->validated();
        if($request->filled('guide_id') && $validated['guide_id'] != $groupTrip->guide_id)
        {
            $tasksCount = Task::where('start_date', '<=',$request->input('ending_date'))
                ->where('end_date', '>=',$request->input('starting_date'))
                ->where('guide_id', $request->input('guide_id'))
                ->count();

            $daysOff = DaysOff::whereBetween('date',[$request->input('starting_date'), $request->input('ending_date')])
                               ->where('guide_id', $validated['guide_id'])
                               ->count();

            if($tasksCount > 0 || $daysOff > 0){
                return response()->json(['the guide is busy in this date'],400);
            }
        }

        if((-1 *$validated['adding_tickets_count'] ?? 0)  > $groupTrip->remaining_tickets)
        {
            return response()->json(['maximum tickets to decrease is '.$groupTrip->remaining_tickets],400);
        }
        return $this->groupTripService->update($validated,$groupTrip);
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

    public function updateOffer(OfferRequest $request,GroupTrip $groupTrip):JsonResponse
    {
        if(!$groupTrip->hasOffer()){
            return response()->json('no offer to update',400);
        }

        $validated = $request->validated();

        $groupTrip->offers()->update($validated);
        return response()->json('the offer is updated successfully');
    }

    public function deleteOffer(GroupTrip $groupTrip):JsonResponse
    {
        if(!$groupTrip->hasOffer()){
            return response()->json('no offer to delete',400);
        }
        $groupTrip->offers()->delete();
        return response()->json('the offer is updated successfully');
    }

    public function storeReport(Request $request,GroupTrip $groupTrip){

        $guide = Guide::findOrFail(Auth::guard('api-guide')->id());
        if($guide->id=== $groupTrip->guide_id && $groupTrip->status !== Status::FINISHED->value) {
            $validated = $request->validate([
                'media' => 'required|mimes:pdf|max:2048',
            ]);

            if($request->hasFile('media') && !$groupTrip->media()->where('type','=','pdf')->exists()) {
                $groupTrip->storeMedia(GroupTripService::FILE_PATH);
                $groupTrip->status = Status::FINISHED->value;
                $groupTrip->save();
              $task = $guide->tasks()->where('taskable_id' ,'=',$groupTrip->id);
              $task->status = Status::FINISHED->value;
                $task->save();
                return response()->json([
                    'message' => 'your file is uploaded!',
                ]);
            }else{
                return response()->json([
                    'message' => 'already uploaded a report for this trip']);
            }
        }else{
            return response()->json(['message' => 'this guide is not responsible for this group trip or the trip is already finished'], 401);
        }
    }
}
