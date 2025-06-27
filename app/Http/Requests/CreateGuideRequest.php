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
            'phone' => ['required','string','min:5','max:17','unique:guides,phone','regex:/^\+[0-9]+$/'],
            'description' => ['nullable','string','min:10','max:255'],
            'const_salary' => ['required', 'string', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'city' => ['required', 'string', 'exists:cities,name'],
            'languages' => ['required', 'array'],
            'languages.*' => ['required', 'string', 'exists:languages,name'],
            'categories' => ['required', 'array'],
            'categories.*' => ['required', 'string', 'exists:categories,name'],
        ];
    }
}
