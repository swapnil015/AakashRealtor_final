<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public form (spam guard via honeypot middleware + throttle)
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:120'],
            'phone'            => ['required', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:160'],
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'city_id'          => ['required', 'integer', 'exists:cities,id'],
            'transaction_type' => ['required', Rule::in(['buy', 'rent'])],
            'min_budget'       => ['nullable', 'numeric', 'min:0'],
            'max_budget'       => ['nullable', 'numeric', 'gte:min_budget'],
            'message'          => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_budget.gte' => 'The maximum budget must be greater than the minimum budget.',
        ];
    }
}
