<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreIPProxyRequest
 *
 * Form Request for validating IP address creation proxy requests.
 * Validates IP address, label, and comment before forwarding to ip-management service.
 */
class StoreIPProxyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'ip_address' => ['required', 'string', 'max:45'],
            'label' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ip_address.required' => 'The IP address is required.',
            'ip_address.string' => 'The IP address must be a string.',
            'ip_address.max' => 'The IP address may not be greater than 45 characters.',
            'label.required' => 'The label is required.',
            'label.string' => 'The label must be a string.',
            'label.max' => 'The label may not be greater than 255 characters.',
            'comment.string' => 'The comment must be a string.',
            'comment.max' => 'The comment may not be greater than 1000 characters.',
        ];
    }
}
