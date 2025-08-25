<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuideResetPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(){
        $guide = auth()->guard('api-guide')->user();
        if(!$this->email && $guide){
            $this->merge([
                'email' => $guide['email']
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required','string','max:30','min:15','email','exists:guides,email','exists:password_reset_tokens,email'],
            'code' => ['required', 'string','size:6','regex:/^\d+$/'],
            'password'=>['required','string','min:8','confirmed','max:30','regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ];
    }
}
