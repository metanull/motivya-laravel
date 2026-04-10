<?php

declare(strict_types=1);

use App\Enums\ActivityType;
use App\Livewire\Admin\ActivityImages;
use App\Models\ActivityImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

describe('access control', function () {
    it('allows admin to access the activity images page', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        $this->actingAs($admin)
            ->get(route('admin.activity-images'))
            ->assertOk();
    });

    it('denies coach access to the activity images page', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)
            ->get(route('admin.activity-images'))
            ->assertForbidden();
    });

    it('denies athlete access to the activity images page', function () {
        $athlete = User::factory()->athlete()->create();

        $this->actingAs($athlete)
            ->get(route('admin.activity-images'))
            ->assertForbidden();
    });

    it('denies unauthenticated access', function () {
        $this->get(route('admin.activity-images'))
            ->assertRedirect(route('login'));
    });
});

describe('upload', function () {
    it('allows admin to upload an image', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $file = UploadedFile::fake()->image('yoga.jpg', 800, 600);

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->set('activityType', ActivityType::Yoga->value)
            ->set('altText', 'A yoga session in the park')
            ->set('image', $file)
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('activity_images', [
            'activity_type' => ActivityType::Yoga->value,
            'alt_text' => 'A yoga session in the park',
            'uploaded_by' => $admin->id,
        ]);

        $image = ActivityImage::first();
        Storage::disk('public')->assertExists($image->path);
    });

    it('validates required fields', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->call('save')
            ->assertHasErrors(['activityType', 'image']);
    });

    it('rejects files larger than 2MB', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $file = UploadedFile::fake()->image('large.jpg')->size(3000);

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->set('activityType', ActivityType::Running->value)
            ->set('image', $file)
            ->call('save')
            ->assertHasErrors(['image']);
    });

    it('rejects non-image files', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->set('activityType', ActivityType::Running->value)
            ->set('image', $file)
            ->call('save')
            ->assertHasErrors(['image']);
    });
});

describe('delete', function () {
    it('allows admin to delete an image', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $file->store('activity-images', 'public');

        $image = ActivityImage::create([
            'activity_type' => ActivityType::Yoga->value,
            'path' => $path,
            'alt_text' => 'Test image',
            'uploaded_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->call('deleteImage', $image->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('activity_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($path);
    });
});

describe('display', function () {
    it('shows existing images in the gallery', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();
        ActivityImage::factory()->count(3)->create();

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->assertViewHas('images', fn ($images) => $images->count() === 3);
    });

    it('shows activity type options in the dropdown', function () {
        $admin = User::factory()->admin()->withTwoFactor()->create();

        Livewire::actingAs($admin)
            ->test(ActivityImages::class)
            ->assertViewHas('activityTypes', fn ($types) => count($types) === count(ActivityType::cases()));
    });
});
