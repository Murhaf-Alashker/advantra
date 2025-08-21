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
            'types' => ['required', 'array'],
            'types.*' => ['required', 'string', 'in:event,city,guide,group_trip'],
            'categories' => ['array','min:1'],
            'categories.*' => ['required','integer', 'exists:categories,id'],
            'languages' => ['array','min:1'],
            'languages.*' => ['required', 'integer', 'exists:languages,id'],
            'countries' => ['array','min:1'],
            'countries.*' => ['required', 'integer', 'exists:countries,id'],
            'cities' => ['array','min:1'],
            'cities.*' => ['required', 'integer', 'exists:cities,id'],
            'with_offer' =>['boolean'],
            'contains' => ['string', 'min:1', 'max:255', 'regex:/\S/'],
            'minPrice' => ['required_with:max', 'numeric','lt:maxPrice', 'between:0,1000'],
            'maxPrice' => ['required_with:min', 'numeric', 'between:0,1000'],
            'orderBy' => ['string', 'min:2', 'max:255','in:id,name,price,created_at'],
        ];
    }

    public function validated($key = null, $default = null): array
    {
        $validated = [];
        if ($this->filled('types')) {
            $types = $this->input('types');
            $validated['types'] = array_map(fn($t) => $t === 'group_trip' ? 'groupTrip' : $t, $types);
        }

        if ($this->filled('categories')) {
            $validated['categories'] = $this->input('categories');
        }
        if ($this->filled('languages')) {
            $validated['languages'] = $this->input('languages');
        }
        if ($this->filled('countries')) {
            $validated['countries'] = $this->input('countries');
        }
        if ($this->filled('cities')) {
            $validated['cities'] = $this->input('cities');
        }
        if ($this->filled('only_offer')) {
            $validated['with_offer'] = $this->input('only_offer');
        }
        if ($this->filled('contains')) {
            $validated['contains'] = $this->input('contains');
        }
        if ($this->filled('minPrice')) {
            $validated['minPrice'] = $this->input('minPrice');
        }
        if ($this->filled('maxPrice')) {
            $validated['maxPrice'] = $this->input('maxPrice');
        }
        if ($this->filled('orderBy')) {
            $validated['orderBy'] = $this->input('orderBy');
        }
        return $validated;
    }
}
