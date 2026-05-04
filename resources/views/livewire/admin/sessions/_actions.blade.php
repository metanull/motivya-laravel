@php
    use App\Enums\SessionStatus;

    $canCancel = in_array($session->status, [SessionStatus::Published, SessionStatus::Confirmed], true);

    $canComplete = $session->status === SessionStatus::Confirmed && $session->hasEnded();

    $showCancelForm = $cancellingSessionId === $session->id;
@endphp

<div class="flex flex-wrap items-center gap-2">

    {{-- View link — always visible --}}
    <a
        href="{{ route('sessions.show', $session) }}"
        class="rounded-md bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600"
    >
        {{ __('admin.sessions_action_view') }}
    </a>

    {{-- Cancel button — only when not already showing the cancel form --}}
    @if ($canCancel && ! $showCancelForm)
        <button
            type="button"
            wire:click="confirmCancel({{ $session->id }})"
            class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
        >
            {{ __('admin.sessions_action_cancel') }}
        </button>
    @endif

    {{-- Complete button — only for past confirmed sessions --}}
    @if ($canComplete)
        <button
            type="button"
            wire:click="completeSession({{ $session->id }})"
            wire:confirm="{{ __('admin.sessions_action_complete_confirm') }}"
            class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500"
        >
            {{ __('admin.sessions_action_complete') }}
        </button>
    @endif

</div>
