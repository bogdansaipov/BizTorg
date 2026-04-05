<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid'           => ['required', 'numeric', 'exists:users,id'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:900'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'images'         => ['nullable', 'array'],
            'images.*'       => ['image', 'mimes:jpeg,png,jpg,gif', 'max:5120'],
            'latitude'       => ['required', 'numeric', 'between:-90,90'],
            'longitude'      => ['required', 'numeric', 'between:-180,180'],
            'attributes'     => ['nullable', 'array'],
            'attributes.*'   => ['integer', 'exists:attribute_values,id'],
            'price'          => ['required', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'in:сум,доллар'],
            'type'           => ['required', 'string', 'in:sale,purchase'],
            'child_region_id'=> ['required', 'exists:regions,id'],
            'showNumber'     => ['required', 'boolean'],
            'number'         => ['nullable', 'string', 'max:15'],
        ];
    }
}
