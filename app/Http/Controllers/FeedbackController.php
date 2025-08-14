<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Models\Feedback;
use App\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    protected FeedbackService $feedbackService;
    public function __construct(FeedbackService $feedbackService){
        $this->feedbackService = $feedbackService;
    }
    public function store(StoreFeedbackRequest $request)
    {
       if(auth()->user()->status === 'active'){
           $validated  = $request->validated();
           return $this->feedbackService->store($validated);
       }
       else{
           return response()->json(['message' => 'Your account is blocked.']);
       }
    }

    public function update(UpdateFeedbackRequest $request,Feedback $feedback)
    {
        $validated  = $request->validated();
        return $this->feedbackService->update($validated,$feedback);
    }
    public function destroy(Feedback $feedback){
        return $this->feedbackService->destroy($feedback);
    }

    public function deleteComment(Feedback $feedback)
    {
        return $this->feedbackService->deleteComment($feedback);
    }
}
