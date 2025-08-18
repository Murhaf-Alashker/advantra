<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('api-user')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'info' => ['required', 'array'],
            'info.*.type' => ['required', 'string', 'in:guide,group_trip,event'],
            'info.*.id' => ['required', 'numeric'],
            'info.*.date' => ['required_if:info.*.type,guide', 'date'],
            'info.*.tickets_count' => ['required_unless:info.*.type,guide', 'numeric'],
            'payment_type' => ['required', 'string','in:paypal,points'],
        ];
    }


}
