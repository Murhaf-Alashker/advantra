<?php
namespace App\Services;

use App\Http\Resources\TaskResource;
use App\Models\Guide;
use App\Models\Task;
use App\Models\User;
use App\Notifications\PersonalNotification;
use App\Notifications\PublicNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TaskService{

    public function store(array $data,Guide $guide){
       $startDateTime = $data['date'] . ' ' . $data['start_time'] ;
       $endDateTime = Carbon::parse($startDateTime)->endOfDay();
        $startDate = Carbon::parse($startDateTime)->toDateString();
        $conflict = Task::where('guide_id', $guide->id)
                         ->whereDate('start_date', '<=', $startDate)
                         ->whereDate('end_date', '>=', $startDate)
                         ->exists();

        if($conflict){
           return response()->json(['message' => 'already reserved '],409);
       }

       $task = $guide->tasks()->create([
           'taskable_id' => Auth::id(),
           'taskable_type' => User::class,
           'start_date' => $startDateTime,
           'end_date' => $endDateTime,
       ]);

       return response()->json(['message' => 'task created',
       'task' => new TaskResource($task)],201);
    }

    public function getMonthlyTasks(){
        $id = Auth::guard('api-guide')->id();
        $guide = Guide::findOrFail($id);
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        $tasks = $guide->tasks()
                       ->whereMonth('start_date', $currentMonth)
                       ->whereYear('start_date', $currentYear)
                       ->get();

        $daysOff = $guide->daysOff()
                         ->whereMonth('date', $currentMonth)
                         ->whereYear('date', $currentYear)
                         ->get();

        return response()->json([
             'const_salary' =>$guide->const_salary,
             'extra_salary' => $guide->extra_salary,
             'Tasks' => TaskResource::collection($tasks),
             'daysOff' => $daysOff,200]);
    }

    public function getReservedDays(Guide $guide)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $tasks = $guide->tasks()
            ->whereMonth('start_date', $currentMonth)
            ->whereYear('start_date', $currentYear)
            ->get();

        $daysOff = $guide->daysOff()
            ->whereMonth('date', $currentMonth)
            ->whereYear('date', $currentYear)
            ->get();

        $reservedTasks = $tasks->map(function($task){
            return [
               'start_date' => $task->start_date,
               'end_date' => $task->end_date,
            ];
        });

        $reservedDaysOff = $daysOff->map(function($dayOff){
            return  ['date' => $dayOff->date];
        });

        return response()->json([
            'reservedTasks' => $reservedTasks,
            'reservedDaysOff' => $reservedDaysOff
        ]);
    }
}
