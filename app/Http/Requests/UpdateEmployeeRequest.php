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

    protected function getShortStateName(?string $state): ?string
    {
        if ($state === null) {
            return null;
        }

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
        $data = [];

        if ($this->has('cpf') && $this->cpf !== null) {
            $data['cpf'] = preg_replace('/\D/', '', $this->cpf);
        }

        if ($this->has('state') && $this->state !== null) {
            $data['state'] = $this->getShortStateName($this->state);
        }

        if (! empty($data)) {
            $this->merge($data);
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
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:employees,email,'.$this->route('employee')->id,
            'cpf' => 'sometimes|string|cpf|size:11|unique:employees,cpf,'.$this->route('employee')->id,
            'city' => 'sometimes|string|max:255',
            'state' => ['sometimes', Rule::enum(BrazilianState::class)],
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
