<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCityRequest extends FormRequest
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
            'name'=>'required|string|max:50|unique:cities,name',
            'name_ar'=>'required|string|max:50|unique:cities,name',
            'description'=>'string|max:10000',
            'description_ar'=>'string|max:10000',
            'status' => 'in:active,inactive',
            'language_id'=>'required|exists:languages,id',
            'country_id'=>'required|exists:countries,id',
            'media' => ['required','array'],
            'media.*' => ['file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],


        ];
    }
}
