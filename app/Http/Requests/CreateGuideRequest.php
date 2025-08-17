<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class CreateGuideRequest extends FormRequest
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
        throw new AuthorizationException(__('message.unauthorized_admin'),403);

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string','min:3', 'max:25'],
            'email' => ['required','string','email','unique:guides,email'],
            'phone' => ['required','string','min:5','max:17','unique:guides,phone','regex:/^\+[1-9][0-9]{4,15}$/'],
            'const_salary' => ['required', 'string', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'city_id' => ['required', 'exists:cities,id'],
            'languages' => ['required', 'array'],
            'languages.*' => ['required', 'string', 'exists:languages,id'],
            'categories' => ['required', 'array'],
            'categories.*' => ['required', 'string', 'exists:categories,id'],
        ];
    }
}
