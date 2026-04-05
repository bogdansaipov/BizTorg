<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'     => ['required', 'exists:products,id'],
            'name'           => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string', 'max:900'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'image1'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'image2'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'image3'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'image4'         => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
            'latitude'       => ['required', 'numeric', 'between:-90,90'],
            'longitude'      => ['required', 'numeric', 'between:-180,180'],
            'attributes'     => ['nullable', 'array'],
            'attributes.*'   => ['integer', 'exists:attribute_values,id'],
            'price'          => ['required', 'numeric', 'min:0'],
            'currency'       => ['required', 'string', 'in:сум,доллар'],
            'type'           => ['required', 'string', 'in:sale,purchase'],
            'child_region_id'=> ['required', 'exists:regions,id'],
        ];
    }
}
