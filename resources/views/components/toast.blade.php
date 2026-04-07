{{-- Toast notification container --}}
<div
    x-data="{ toasts: [] }"
    x-on:notify.window="
        toasts.push({ type: $event.detail.type, message: $event.detail.message, id: Date.now() });
        setTimeout(() => toasts.shift(), $event.detail.type === 'error' ? 8000 : 5000)
    "
    class="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2"
    aria-live="polite"
    aria-atomic="false"
>
    {{-- Flash from server-side redirect --}}
    @if (session('flash'))
        <div x-init="$dispatch('notify', @js(session('flash')))"></div>
    @endif

    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
            :class="{
                'bg-green-500 text-white': toast.type === 'success',
                'bg-red-500 text-white': toast.type === 'error',
                'bg-yellow-400 text-gray-900': toast.type === 'warning',
                'bg-blue-500 text-white': toast.type === 'info',
            }"
            class="pointer-events-auto flex max-w-sm items-start gap-3 rounded-lg px-4 py-3 text-sm font-medium shadow-lg"
            role="alert"
        >
            <span x-text="toast.message" class="flex-1"></span>
            <button
                x-on:click="toasts = toasts.filter(t => t.id !== toast.id)"
                type="button"
                class="shrink-0 opacity-80 hover:opacity-100"
                aria-label="{{ __('common.close') }}"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
