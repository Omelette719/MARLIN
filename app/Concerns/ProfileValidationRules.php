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
            'nama_panggilan' => $this->namaPanggilanRules(),
            'no_telepon' => $this->noTeleponRules(),
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
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function namaPanggilanRules(): array
    {
        return ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z\s]+$/'];
    }

    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function noTeleponRules(): array
    {
        return ['nullable', 'string', 'max:30', 'regex:/^[0-9]+$/'];
    }

    /**
     * Custom message for nameRules()'s regex failure, keyed to match
     * whatever field name each caller validates it under.
     */
    protected function nameMessages(string $field = 'name'): array
    {
        return [$field.'.regex' => 'Nama hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.'];
    }

    protected function profileMessages(): array
    {
        return [
            ...$this->nameMessages(),
            'nama_panggilan.regex' => 'Nama panggilan hanya boleh berisi huruf dan spasi, tanpa angka atau simbol.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka.',
        ];
    }
}
