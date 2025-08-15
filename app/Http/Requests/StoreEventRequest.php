<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreEventRequest extends FormRequest
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
            'name' => 'required|string|max:50|unique:events,name',
            'name_ar' => 'required|string|max:50|unique:events,name',
            'description' => 'string|max:1000',
            'description_ar' => 'string|max:1000',
            'basic_cost' => 'required|numeric',
            'price' => 'required|numeric|gt:basic_cost',
            'status' => 'in:active,inactive',
            'city_id' => 'required|exists:cities,id',
            'category_id' => 'required|exists:categories,id',
            'is_limited' =>'in:true,false',
            'tickets_count' => ['required_with:is_limited','numeric','min:1'],
            'tickets_limit' => ['required_with:is_limited','numeric','min:0','lt:tickets_count'],
            'start_date' =>['required_with:is_limited','date','date_format:Y-m-d'],
            'end_date' =>['required_with:is_limited','date','date_format:Y-m-d','after:start_date'],
            'media' => ['required','array'],
            'media.*' => ['required','file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
        ];
    }
}
