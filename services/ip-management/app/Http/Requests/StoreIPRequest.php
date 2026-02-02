<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreIPRequest
 *
 * Validates IP address creation requests.
 */
class StoreIPRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * All authenticated users can create IP addresses.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'string', 'max:45'],
            'label' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ip_address.required' => 'The IP address is required.',
            'ip_address.max' => 'The IP address must not exceed 45 characters.',
            'label.required' => 'The label is required.',
            'label.max' => 'The label must not exceed 255 characters.',
        ];
    }
}
