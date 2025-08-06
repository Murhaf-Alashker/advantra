<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class OfferRequest extends FormRequest
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
            'discount' => ['required', 'numeric', 'min:0', 'max:90'],
            'start_date' => ['required', 'date', 'before_or_equal:end_date','after_or_equal:'.Carbon::now()],
            'end_date' => ['required', 'date', 'after_or_equal:start_date','after_or_equal:'.Carbon::now()],
        ];
    }
}
