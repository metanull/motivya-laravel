<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
        {{ __('admin.coach_approval_heading') }}
    </h1>

    @if ($pendingApplications->isEmpty())
        <div class="mt-6 rounded-lg bg-white p-6 text-center shadow dark:bg-gray-800">
            <p class="text-gray-500 dark:text-gray-400">{{ __('admin.no_pending_applications') }}</p>
        </div>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($pendingApplications as $profile)
                <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800" wire:key="profile-{{ $profile->id }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $profile->user->name }}
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $profile->user->email }}
                            </p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            {{ __('common.status.pending') }}
                        </span>
                    </div>

                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.specialties_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ implode(', ', $profile->specialties ?? []) }}
                            </dd>
                        </div>
                        @if ($profile->bio)
                            <div class="sm:col-span-2">
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.bio_label') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $profile->bio }}</dd>
                            </div>
                        @endif
                        @if ($profile->experience_level)
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.experience_level_label') }}</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ __('coach.experience_' . $profile->experience_level) }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.postal_code_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $profile->postal_code }} ({{ $profile->country }})</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('coach.enterprise_number_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $profile->enterprise_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.applied_at') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $profile->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>

                    {{-- Rejection form --}}
                    @if ($rejectingProfileId === $profile->id)
                        <div class="mt-4 rounded-md border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                            <label for="rejectionReason-{{ $profile->id }}" class="block text-sm font-medium text-red-800 dark:text-red-200">
                                {{ __('admin.rejection_reason_label') }}
                            </label>
                            <textarea
                                wire:model="rejectionReason"
                                id="rejectionReason-{{ $profile->id }}"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-red-300 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-red-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                placeholder="{{ __('admin.rejection_reason_placeholder') }}"
                            ></textarea>
                            @error('rejectionReason')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex gap-2">
                                <button
                                    type="button"
                                    wire:click="reject"
                                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                                >
                                    {{ __('admin.confirm_reject') }}
                                </button>
                                <button
                                    type="button"
                                    wire:click="cancelReject"
                                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    {{ __('common.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div class="mt-4 flex gap-2">
                            <button
                                type="button"
                                wire:click="approve({{ $profile->id }})"
                                wire:confirm="{{ __('admin.approve_confirm') }}"
                                class="rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                            >
                                {{ __('admin.approve') }}
                            </button>
                            <button
                                type="button"
                                wire:click="confirmReject({{ $profile->id }})"
                                class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500"
                            >
                                {{ __('admin.reject') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $pendingApplications->links() }}
        </div>
    @endif
</div>
