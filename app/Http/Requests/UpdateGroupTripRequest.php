<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateGroupTripRequest extends FormRequest
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
            'description' => ['required','string','max:1000'],
            'description_ar' => ['required','string','max:1000'],
            'basic_cost' => ['required','required','numeric'],
            'extra_cost' => ['required','numeric','min:0'],
            'price' => ['required','numeric','gt:basic_cost'],
            'adding_tickets_count'=>['required','numeric','min:0'],
            'tickets_limit' => ['required','numeric','min:0'],
            'guide_id' => ['required','exists:guides,id'],
            'media' => ['required','array'],
            'media.*' => ['file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
            'old_media' => 'array',
            'old_media.*' =>  ['nullable','exists:media,id'],
        ];
    }
}
