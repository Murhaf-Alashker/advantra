<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(Auth::guard('api-admin')->check())
        {
            return true;
        }
        throw new AuthorizationException(__('message.unauthorized'),403);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'string|max:50|unique:events,name,'.$this->event->id,
            'description' => 'string|max:1000',
            'name_ar'=>'string|max:50',
            'description_ar'=>'string|max:10000',
            'price' => 'numeric',
            'status' => 'in:active,inactive',
            'category_id' => 'exists:categories,id',
            'city_id' => 'exists:cities,id',
            'old_media' => 'array',
            'old_media.*' =>  ['nullable','exists:media,id'],
            'media' => ['array'],
            'media.*' => ['file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
        ];
    }
}
