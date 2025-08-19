<?php

namespace App\Http\Requests;

use App\Enums\MediaType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UpdateGuideProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(Auth::guard('api-guide')->check())
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
            'description' => 'nullable|string',
            'languages' => 'nullable|array',
            'languages.*' => 'exists:languages,id',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'media' => ['nullable','image','mimes:'.implode(',',MediaType::images()),'max:2048'],
            'price' => [
                'nullable',
                'numeric',
                'min:0',
                function($attribute, $value, $fail) {
                    $today = Carbon::now()->day;
                    if ($today !== 1) {
                        $fail('The ' . $attribute . ' can only be updated on the first day of the month.');
                    }
                },
            ],
        ];
    }
}
