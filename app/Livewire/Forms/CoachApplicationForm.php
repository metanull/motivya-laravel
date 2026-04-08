<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

final class CoachApplicationForm extends Form
{
    /** @var list<string> */
    #[Validate('required|array|min:1')]
    public array $specialties = [];

    #[Validate('nullable|string|max:2000')]
    public string $bio = '';

    #[Validate('nullable|string|in:beginner,intermediate,advanced,expert')]
    public string $experience_level = '';

    #[Validate('required|string|regex:/^[1-9]\d{3}$/')]
    public string $postal_code = '';

    #[Validate('required|string|size:2')]
    public string $country = 'BE';

    #[Validate('required|string|regex:/^\d{4}\.\d{3}\.\d{3}$/')]
    public string $enterprise_number = '';

    #[Validate('accepted')]
    public bool $terms_accepted = false;

    /**
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return $this->only([
            'specialties',
            'bio',
            'experience_level',
            'postal_code',
            'country',
            'enterprise_number',
        ]);
    }
}
