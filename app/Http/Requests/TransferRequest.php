<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class TransferRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email_beneficiary' => 'required|email|exists:users,email',
            'amount' => 'required|integer|min:1'
        ];
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'email_beneficiary.required' => 'O campo email_beneficiary é obrigatório',
            'email_beneficiary.email' => 'O campo email_beneficiary deve ser um email válido',
            'email_beneficiary.exists' => 'O usuário não existe',
            'amount.required' => 'O campo amount é obrigatório',
            'amount.integer' => 'O campo amount deve ser um número inteiro',
            'amount.min' => 'O campo amount deve ser maior que 0',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Validação falhou',
            'errors' => $validator->errors(),
        ], 422));
    }
}