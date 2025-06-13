<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'description'=>'string|max:10000',
            'status' => 'in:active,inactive',
            'language_id'=>'required|exists:languages,id',
            'images'=>'required',
            'images.*'=>'image|mimes:jpg,jpeg,png|max:2048',


        ];
    }
}
