<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'exists:users,id'],
            'message'     => ['nullable', 'string'],
            'image_url'   => ['nullable', 'string'],
        ];
    }

    /**
     * Business-level check: message or image must be present.
     * Called after validation in the controller.
     */
    public function hasContent(): bool
    {
        return !empty($this->input('message')) || !empty($this->input('image_url'));
    }
}
