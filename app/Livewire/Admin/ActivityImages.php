<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Enums\ActivityType;
use App\Models\ActivityImage;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

final class ActivityImages extends Component
{
    use WithFileUploads, WithPagination;

    #[Validate('required|string')]
    public string $activityType = '';

    #[Validate('required|image|mimes:jpg,jpeg,png,webp|max:2048')]
    public $image;

    #[Validate('nullable|string|max:255')]
    public string $altText = '';

    public function save(): void
    {
        Gate::authorize('access-admin-panel');

        $this->validate();

        $path = $this->image->store('activity-images', 'public');

        ActivityImage::create([
            'activity_type' => $this->activityType,
            'path' => $path,
            'alt_text' => $this->altText,
            'uploaded_by' => auth()->id(),
        ]);

        $this->reset(['image', 'altText', 'activityType']);

        $this->dispatch('notify', type: 'success', message: __('admin.image_uploaded'));
    }

    public function deleteImage(int $imageId): void
    {
        Gate::authorize('access-admin-panel');

        $image = ActivityImage::findOrFail($imageId);

        Storage::disk('public')->delete($image->path);

        $image->delete();

        $this->dispatch('notify', type: 'success', message: __('admin.image_deleted'));
    }

    public function render(): View
    {
        $images = ActivityImage::with('uploader')
            ->latest()
            ->paginate(12);

        return view('livewire.admin.activity-images', [
            'images' => $images,
            'activityTypes' => ActivityType::cases(),
        ])->title(__('admin.activity_images_title'));
    }
}
