<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Models\CoachProfile;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class CoachProfileForm extends Form
{
    /** @var array<int, string> */
    #[Validate('required|array|min:1')]
    public array $specialties = [];

    #[Validate('required|string|max:2000')]
    public string $bio = '';

    #[Validate('required|string|in:beginner,intermediate,advanced,expert')]
    public string $experienceLevel = '';

    #[Validate('required|string|regex:/^[1-9]\d{3}$/')]
    public string $postalCode = '';

    #[Validate('nullable|string|max:50')]
    public string $enterpriseNumber = '';

    /**
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'specialties' => $this->specialties,
            'bio' => $this->bio,
            'experience_level' => $this->experienceLevel,
            'postal_code' => $this->postalCode,
            'enterprise_number' => $this->enterpriseNumber,
        ];
    }

    public function setFromModel(CoachProfile $profile): void
    {
        $this->specialties = $profile->specialties ?? [];
        $this->bio = $profile->bio ?? '';
        $this->experienceLevel = $profile->experience_level ?? '';
        $this->postalCode = $profile->postal_code ?? '';
        $this->enterpriseNumber = $profile->enterprise_number ?? '';
    }
}
