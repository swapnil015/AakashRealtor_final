<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Fine-grained ownership/role check is done in the controller via the
        // PropertyPolicy; here we just require an authenticated user.
        return (bool) $this->user();
    }

    public function rules(): array
    {
        // All fields optional on update (PATCH-like semantics) via "sometimes".
        return [
            'title'            => ['sometimes', 'string', 'max:160'],
            'description'      => ['nullable', 'string', 'max:8000'],
            'category_id'      => ['sometimes', 'integer', 'exists:categories,id'],
            'city_id'          => ['sometimes', 'integer', 'exists:cities,id'],
            'area_id'          => ['nullable', 'integer', 'exists:areas,id'],

            'transaction_type' => ['sometimes', Rule::in(['buy', 'rent'])],
            'price'            => ['sometimes', 'numeric', 'min:0', 'max:99999999999.99'],
            'price_unit'       => ['nullable', 'string', 'max:30'],
            'price_negotiable' => ['boolean'],

            'area_size'        => ['nullable', 'numeric', 'min:0'],
            'area_unit'        => ['nullable', Rule::in(['aana', 'ropani', 'paisa', 'daam', 'sqft', 'sqm'])],

            'bedrooms'         => ['nullable', 'integer', 'min:0', 'max:100'],
            'bathrooms'        => ['nullable', 'integer', 'min:0', 'max:100'],
            'floors'           => ['nullable', 'integer', 'min:0', 'max:200'],
            'parking'          => ['nullable', 'integer', 'min:0', 'max:100'],
            'road_width'       => ['nullable', 'numeric', 'min:0'],
            'facing'           => ['nullable', 'string', 'max:30'],

            'is_by_owner'      => ['boolean'],
            'open_house_date'  => ['nullable', 'date'],

            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'address'          => ['nullable', 'string', 'max:255'],

            'amenities'        => ['nullable', 'array'],
            'amenities.*'      => ['integer', 'exists:amenities,id'],
        ];
    }
}
