<?php

namespace App\Http\Requests;

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
            'ticket_price' => 'numeric',
            'status' => 'in:active,inactive',
            'tickets_limit' => 'numeric',
            'category_id' => 'exists:categories,id',
            'city_id' => 'exists:cities,id',
        ];
    }
}
