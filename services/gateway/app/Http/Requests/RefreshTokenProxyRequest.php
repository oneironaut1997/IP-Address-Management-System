<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class RefreshTokenProxyRequest
 *
 * Form Request for validating token refresh proxy requests.
 * Validates that the Authorization header is present.
 */
class RefreshTokenProxyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
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
            // Authorization header is validated in withValidator
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $authHeader = $this->header('Authorization');

            if (! $authHeader || ! str_starts_with($authHeader, 'Bearer ')) {
                $validator->errors()->add(
                    'authorization',
                    'A valid Bearer token is required for token refresh.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'authorization' => 'A valid Bearer token is required for token refresh.',
        ];
    }
}
