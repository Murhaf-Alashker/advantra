<?php
namespace App\Services;


use App\Enums\Status;
use App\Http\Requests\UpdateFeedbackRequest;
use App\Http\Resources\FeedbackResource;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackService
{

    public function store(array $data)
    {
        $models = [
            'event' => \App\Models\Event::class,
            'guide' => \App\Models\Guide::class,
            'group_trip' => \App\Models\GroupTrip::class,
        ];

        $type = $data['type'];
        $modelClass = $models[$type];
        $model = $modelClass::findOrFail($data['id']);

        if ($type === 'group_trip' && $model->status !== Status::FINISHED->value) {
            return response()->json([
                'message' => 'You cannot submit feedback until the trip is finished.'
            ], 422);
        }

        $existingFeedback = $model->feedbacks()
            ->where('user_id', Auth::id())
            ->first();

        if ($existingFeedback) {
            return response()->json([
                'message' => 'You have already submitted feedback for this item.'
            ], 422);
        }
        $feedback = $model->feedbacks()->create([
            'rating' => $data['rating'],
            'comment' => $data['comment'],
            'user_id' => Auth::id()
        ]);

        return new FeedbackResource($feedback);


    }

    public function update(array $data, Feedback $feedback)
    {
        $feedback->update($data);
        return new FeedbackResource($feedback);
    }
    public function destroy(Feedback $feedback)
    {
        $feedback->delete();
        return response()->json(['message' => 'Feedback deleted']);
    }

    public function deleteComment(Feedback $feedback)
    {
        $feedback->update(['comment' => null]);
        return response()->json(['message' => 'Comment deleted']);
    }
}
