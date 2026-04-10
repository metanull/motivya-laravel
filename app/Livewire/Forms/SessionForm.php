<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ActivityType;
use App\Enums\SessionLevel;
use App\Models\SportSession;
use Illuminate\Validation\Rules\Enum;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class SessionForm extends Form
{
    #[Validate]
    public string $activityType = '';

    #[Validate]
    public string $level = '';

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public string $description = '';

    #[Validate('required|string|max:255')]
    public string $location = '';

    #[Validate('required|string|regex:/^[1-9]\d{3}$/')]
    public string $postalCode = '';

    #[Validate('required|date|after:today')]
    public string $date = '';

    #[Validate('required|date_format:H:i')]
    public string $startTime = '';

    #[Validate('required|date_format:H:i|after:startTime')]
    public string $endTime = '';

    #[Validate('required|numeric|min:0.01')]
    public string $priceEuros = '';

    #[Validate('required|integer|min:1')]
    public int $minParticipants = 1;

    #[Validate('required|integer|min:1|gte:minParticipants')]
    public int $maxParticipants = 10;

    #[Validate('nullable|integer|exists:activity_images,id')]
    public ?int $coverImageId = null;

    #[Validate('boolean')]
    public bool $isRecurring = false;

    #[Validate('required_if:isRecurring,true|integer|min:2|max:12')]
    public int $numberOfWeeks = 2;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activityType' => ['required', 'string', new Enum(ActivityType::class)],
            'level' => ['required', 'string', new Enum(SessionLevel::class)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toServiceArray(): array
    {
        return [
            'activity_type' => $this->activityType,
            'level' => $this->level,
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'postal_code' => $this->postalCode,
            'date' => $this->date,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'price_per_person' => (int) round((float) $this->priceEuros * 100),
            'min_participants' => $this->minParticipants,
            'max_participants' => $this->maxParticipants,
            'cover_image_id' => $this->coverImageId,
        ];
    }

    public function setFromModel(SportSession $session): void
    {
        $this->activityType = $session->activity_type->value;
        $this->level = $session->level->value;
        $this->title = $session->title;
        $this->description = $session->description ?? '';
        $this->location = $session->location;
        $this->postalCode = $session->postal_code;
        $this->date = $session->date->format('Y-m-d');
        $this->startTime = substr((string) $session->start_time, 0, 5);
        $this->endTime = substr((string) $session->end_time, 0, 5);
        $this->priceEuros = number_format($session->price_per_person / 100, 2, '.', '');
        $this->minParticipants = $session->min_participants;
        $this->maxParticipants = $session->max_participants;
        $this->coverImageId = $session->cover_image_id;
    }
}
