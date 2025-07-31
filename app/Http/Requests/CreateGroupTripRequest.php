<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use App\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateGroupTripRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('api-admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required','string','max:50'],
            'name_ar' => ['required','string','max:50'],
            'description' => ['string','max:1000'],
            'description_ar' => ['string','max:1000'],
            'starting_date' => ['required','date'],
            'ending_date' => ['required','date','after_or_equal:starting_date'],
            'basic_cost' => ['required','numeric'],
            'extra_cost' => ['numeric'],
            'status' => ['in:'. implode(',',Status::values())],
            'price' => ['required','numeric'],
            'tickets_count'=>['required','numeric','min:1'],
            'tickets_limit' => ['numeric'],
            'guide_id' => ['required','exists:guides,id'],
            'events' => ['required','array'],
            'events.*' => ['required','exists:events,id'],
            'media' => ['required','array'],
            'media.*' => ['file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
        ];
    }
}
