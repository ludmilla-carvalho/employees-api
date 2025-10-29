<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:employees,email',
            'cpf' => 'required|string|size:11|unique:employees,cpf',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Get the custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already taken.',
            'cpf.required' => 'The CPF field is required.',
            'cpf.size' => 'The CPF must be exactly 11 characters.',
            'cpf.unique' => 'This CPF is already registered.',
            'city.required' => 'The city field is required.',
            'state.required' => 'The state field is required.',
            'state.size' => 'The state must be exactly 2 characters.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
