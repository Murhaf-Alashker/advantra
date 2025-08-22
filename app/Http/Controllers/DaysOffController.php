<?php

namespace App\Http\Controllers;

use App\Models\DaysOff;
use App\Models\Guide;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DaysOffController extends Controller
{
    public function store(Request $request)
    {
        $id = Auth::guard('api-guide')->id();
        $guide = Guide::findOrFail($id);
        $validated = $request->validate([
            'date' => 'required|array'
        ]);

        $currentDaysOffCount = DaysOff::where('guide_id', $guide->id)->count();
        $newDaysCount = count($validated['date']);
        $total = $currentDaysOffCount + $newDaysCount;

        if($total > 6){
            return response()->json([
                'message' => 'You cannot take more than 6 days off',
            ], 422);
        }
         $conflict = [];
       foreach ($validated['date'] as $date) {

           if(
               Task::where('guide_id', $guide->id)
               ->whereDate('start_date', '<=', $date)
               ->whereDate('end_date', '>=', $date)
               ->exists()
               ||
               DaysOff::where('guide_id',$guide->id)
                      ->where('date', $date)
                      ->exists()
           )
           {
               $conflict[] =  $date;

           }
       }
       if(!empty($conflict)){
           return response()->json(['message' => 'already has tasks on some days',
               'conflict_days' => $conflict]);
    }else
    {
         $dayOff =  $guide->daysOff()->createMany(array_map(fn($d) => ['date' => $d], $validated['date']));
         return response()->json(['days_off' => $dayOff]);

    }



    }
}
