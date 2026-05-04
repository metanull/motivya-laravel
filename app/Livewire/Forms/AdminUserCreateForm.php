<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;
use Livewire\Form;

final class AdminUserCreateForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $role = '';

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in([UserRole::Accountant->value, UserRole::Admin->value])],
        ];
    }
}
