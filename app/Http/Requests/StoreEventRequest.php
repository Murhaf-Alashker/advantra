<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50|unique:events,name',
            'name_ar' => 'required|string|max:50|unique:events,name',
            'description' => 'string|max:1000',
            'description_ar' => 'string|max:1000',
            'price' => 'required|numeric',
            'status' => 'in:active,inactive',
            'tickets_limit' => 'numeric',
            'category_id' => 'required|exists:categories,id',
            'media' => ['required','array'],
            'media.*' => ['file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
            'city_id' => 'required|exists:cities,id',
        ];
    }
}
