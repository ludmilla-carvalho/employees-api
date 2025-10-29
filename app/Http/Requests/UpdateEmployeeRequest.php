<?php

namespace App\Http\Requests;

use App\Enums\BrazilianState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('cpf')) {
            $this->merge([
                'cpf' => preg_replace('/\D/', '', $this->cpf),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:employees,email,'.$this->route('employee')->id,
            'cpf' => 'sometimes|required|string|cpf|size:11|unique:employees,cpf,'.$this->route('employee')->id,
            'city' => 'sometimes|required|string|max:255',
            'state' => ['sometimes', 'required', Rule::enum(BrazilianState::class)],
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
            'cpf.required' => 'The CPF field is required.',
            'cpf.cpf' => 'The CPF provided is invalid.',
            'cpf.size' => 'The CPF must be between 11 and 14 characters.',
            'city.required' => 'The city field is required.',
            'state.required' => 'The state field is required.',
            'state.enum' => 'The selected state is invalid. Please select a valid Brazilian state. EX: SP, RJ, MG.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
