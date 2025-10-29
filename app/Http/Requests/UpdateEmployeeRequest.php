<?php

namespace App\Http\Requests;

use App\Enums\BrazilianState;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
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

    protected function getShortStateName(string $state): ?string
    {
        if (strlen($state) > 2) {
            $slugState = strtoupper(Str::slug($state, '_'));
            if (in_array($slugState, BrazilianState::getNames())) {
                return BrazilianState::{$slugState}->value;
            }
        }

        return $state;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D/', '', $this->cpf),
            'state' => $this->getShortStateName($this->state),
        ]);
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
            'cpf.size' => 'The CPF must be between 11 and 14 characters.',
            'cpf.cpf' => 'The CPF provided is invalid.',
            'state.enum' => 'The selected state is invalid. Please select a valid Brazilian state. EX: SP or São Paulo',
        ];
    }
}
