<?php

namespace App\Http\Controllers;

use App\Enums\Status;
use App\Models\GroupTrip;
use App\Models\ReportsLog;
use App\Services\GroupTripService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportsLogController extends Controller
{
    public function store(Request $request,GroupTrip $groupTrip){
        if(Auth::guard('api-guide')->id() === $groupTrip->guide_id && !$groupTrip->status === Status::FINISHED->value) {
            $validated = $request->validate([
                'media' => 'required|mimes:pdf|max:2048',
            ]);
//            $filename = Str::uuid() . '.pdf';
//            $filename = Str::snake($filename);
//            $groupTrip->report()->create([
//                'media' => $filename,
//                'guide_id' => Auth::guard('api-guide')->id()
//            ]);
            if($request->hasFile('media') && !$groupTrip->media()->where('type','=','pdf')->exists()) {
                $groupTrip->storeMedia(GroupTripService::FILE_PATH);
                $groupTrip->status = Status::FINISHED->value;
                $groupTrip->save();
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
