<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated, active user may submit a listing (defaults to
        // pending). Ownership is enforced on update/delete via the policy.
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'title'            => ['required', 'string', 'max:160'],
            'description'      => ['nullable', 'string', 'max:8000'],
            'category_id'      => ['required', 'integer', 'exists:categories,id'],
            'city_id'          => ['required', 'integer', 'exists:cities,id'],
            'area_id'          => ['nullable', 'integer', 'exists:areas,id'],

            'transaction_type' => ['required', Rule::in(['buy', 'rent'])],
            'price'            => ['required', 'numeric', 'min:0', 'max:99999999999.99'],
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
            'open_house_date'  => ['nullable', 'date', 'after_or_equal:today'],

            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'address'          => ['nullable', 'string', 'max:255'],

            'amenities'        => ['nullable', 'array'],
            'amenities.*'      => ['integer', 'exists:amenities,id'],

            // Optional inline image upload on create.
            'images'           => ['nullable', 'array', 'max:' . (int) env('MAX_IMAGES_PER_LISTING', 20)],
            'images.*'         => ['image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.max'   => 'Each image must be 8 MB or smaller.',
            'images.max'     => 'You can upload at most :max images per listing.',
            'price.max'      => 'That price looks too large — please double-check.',
        ];
    }
}
