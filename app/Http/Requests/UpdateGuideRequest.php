<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
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
          //  'phone' => ['string','min:11','max:17','unique:guides,phone','regex:/^\+[1-9][0-9]{4,15}$/'],
            'languages' => ['array'],
            'languages.*' => ['exists:languages,id'],
            'categories' => ['array'],
            'categories.*' => ['exists:categories,id'],
            'city_id' =>'exists:cities,id' ,
            'card' => ['nullable', 'string', 'email'],
        ];
    }
}
