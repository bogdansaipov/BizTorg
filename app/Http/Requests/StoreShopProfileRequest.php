<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_name'      => ['required', 'max:255'],
            'description'    => ['required', 'string'],
            'tax_id_number'  => ['nullable', 'string', 'max:25'],
            'contact_name'   => ['string', 'max:255'],
            'address'        => ['string', 'max:255'],
            'phone'          => ['required', 'string', 'max:20'],
            'facebook_link'  => ['nullable', 'string', 'max:300'],
            'telegram_link'  => ['nullable', 'string', 'max:300'],
            'instagram_link' => ['nullable', 'string', 'max:300'],
            'website'        => ['nullable', 'string'],
            'latitude'       => ['nullable', 'sometimes', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'sometimes', 'numeric', 'between:-180,180'],
        ];
    }
}
