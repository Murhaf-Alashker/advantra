<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateGuideRequest extends FormRequest
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
            'description' => ['nullable','string','min:10','max:255'],
            'phone' => ['nullable','string','min:11','max:17','unique:guides,phone','regex:/^\+[0-9]+$/'],
            'languages' => ['nullable', 'array'],
            'languages.*' => ['nullable', 'string', 'exists:languages,name'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['nullable', 'string', 'exists:categories,name'],
            'image' => ['nullable','image','mimes:jpg,jpeg,png','max:2048'],
            'card' => ['nullable', 'string', 'email'],
        ];
    }
}
