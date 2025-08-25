<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class MakeEventLimitedRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('api-admin')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tickets_count' => ['required','numeric','min:1'],
            'tickets_limit' => ['required','numeric','min:0','lt:tickets_count'],
            'start_date' =>['required','date','date_format:Y-m-d H:i:s'],
            'end_date' =>['required','date','date_format:Y-m-d H:i:s','after:start_date'],
        ];
    }
}
