<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;

trait ProfileValidationRules
{
    /**
     * Get the validation rules used to validate user profiles.
     *
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
        ];
    }

    /**
     * Get the validation rules used to validate user names.
     *
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'];
    }

    /**
     * Custom message for nameRules()'s regex failure, keyed to match
     * whatever field name each caller validates it under.
     */
    protected function nameMessages(string $field = 'name'): array
    {
        return [$field.'.regex' => 'Nama hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.'];
    }
}
