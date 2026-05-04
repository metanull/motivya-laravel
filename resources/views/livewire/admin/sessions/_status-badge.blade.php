@php
    $badgeClass = match ($session->status) {
        \App\Enums\SessionStatus::Draft     => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
        \App\Enums\SessionStatus::Published => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        \App\Enums\SessionStatus::Confirmed => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        \App\Enums\SessionStatus::Completed => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        \App\Enums\SessionStatus::Cancelled => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        default                             => 'bg-gray-100 text-gray-700',
    };
@endphp

<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeClass }}">
    {{ $session->status->label() }}
</span>
