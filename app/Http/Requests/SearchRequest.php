<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if(Auth::guard('api-user')->check() || Auth::guard('api-admin')->check()){
            return true;
        }
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:event,city,guide,group_trip,all'],
            'search' => ['required', 'string', 'min:1', 'max:255', 'regex:/\S/'],
            'min' => ['sometimes','nullable','required_with:max', 'numeric', 'between:0,1000'],
            'max' => ['sometimes','nullable','required_with:min', 'numeric', 'between:0,1000'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = [];
        $validated['type'] = $this->type;
        $validated['search'] = $this->search;
        if(!$this->has('min') || !$this->has('max') || !$this->max || !$this->min) {
            $validated['min'] = null;
            $validated['max'] = null;
        }
        return $validated;
    }
}
