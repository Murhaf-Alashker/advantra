<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Models\Guide;
use App\Services\TaskService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    protected TaskService $TaskService;
    public function __construct(TaskService $TaskService){
        $this->TaskService = $TaskService;
    }
    public function store(TaskRequest $request,Guide $guide){
       $validated = $request->validated();
       return $this->TaskService->store($validated,$guide);
    }

    public function getMonthlyTasks(){
        return $this->TaskService->getMonthlyTasks();
    }

    public function getReservedDays(Guide $guide)
    {
     return $this->TaskService->getReservedDays($guide);
    }
}
