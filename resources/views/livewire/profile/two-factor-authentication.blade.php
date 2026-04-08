<div>
    <section class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('profile.twofa_heading') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('profile.twofa_description') }}
        </p>

        @if ($enabled)
            {{-- 2FA is enabled --}}
            <div class="mt-4 rounded-md bg-green-50 p-4 dark:bg-green-900/20">
                <p class="text-sm font-medium text-green-700 dark:text-green-400">
                    {{ __('profile.twofa_enabled_status') }}
                </p>
            </div>

            {{-- Recovery Codes --}}
            <div class="mt-4">
                @if ($showingRecoveryCodes)
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                        {{ __('profile.twofa_recovery_description') }}
                    </p>
                    <div class="mt-4 grid max-w-xl gap-1 rounded-lg bg-gray-100 p-4 font-mono text-sm dark:bg-gray-900">
                        @foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code)
                            <div>{{ $code }}</div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center gap-4">
                        <form method="POST" action="{{ url('/user/two-factor-recovery-codes') }}">
                            @csrf
                            <button
                                type="submit"
                                class="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                            >
                                {{ __('profile.twofa_regenerate_codes') }}
                            </button>
                        </form>

                        <button
                            type="button"
                            wire:click="hideRecoveryCodes"
                            class="text-sm font-medium text-gray-600 hover:text-gray-500 dark:text-gray-400"
                        >
                            {{ __('profile.twofa_hide_codes') }}
                        </button>
                    </div>
                @else
                    <button
                        type="button"
                        wire:click="showRecoveryCodes"
                        class="mt-2 rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                    >
                        {{ __('profile.twofa_show_codes') }}
                    </button>
                @endif
            </div>

            {{-- Disable 2FA --}}
            <div class="mt-6">
                <form method="DELETE" action="{{ url('/user/two-factor-authentication') }}"
                    onsubmit="event.preventDefault(); fetch(this.action, {method: 'DELETE', headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}}).then(() => window.location.reload());">
                    <button
                        type="submit"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                    >
                        {{ __('profile.twofa_disable') }}
                    </button>
                </form>
            </div>
        @else
            {{-- 2FA is not enabled --}}
            <div class="mt-4" x-data="{ enabling: false, confirming: false, qrCode: '', confirmationCode: '', recoveryCodes: [] }">
                <template x-if="!enabling && !confirming">
                    <button
                        type="button"
                        x-on:click="
                            enabling = true;
                            fetch('{{ url('/user/two-factor-authentication') }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json'
                                }
                            }).then(() => {
                                return fetch('{{ url('/user/two-factor-qr-code') }}', {
                                    headers: { 'Accept': 'application/json' }
                                });
                            }).then(r => r.json()).then(data => {
                                qrCode = data.svg;
                                confirming = true;
                                enabling = false;
                            }).catch(() => { enabling = false; })
                        "
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <span x-show="!enabling">{{ __('profile.twofa_enable') }}</span>
                        <span x-show="enabling" x-cloak>{{ __('profile.twofa_enabling') }}</span>
                    </button>
                </template>

                <template x-if="confirming">
                    <div class="space-y-4">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('profile.twofa_scan_instructions') }}
                        </p>

                        <div class="inline-block rounded-lg bg-white p-4" x-html="qrCode"></div>

                        <form method="POST" action="{{ url('/user/confirmed-two-factor-authentication') }}"
                            x-on:submit.prevent="
                                fetch($el.action, {
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({ code: confirmationCode })
                                }).then(r => {
                                    if (r.ok) { window.location.reload(); }
                                    else { $refs.confirmError.classList.remove('hidden'); }
                                })
                            ">
                            <div>
                                <label for="twofa_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('profile.twofa_confirm_code') }}
                                </label>
                                <input
                                    type="text"
                                    id="twofa_code"
                                    x-model="confirmationCode"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    required
                                    class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                />
                                <p x-ref="confirmError" class="mt-1 hidden text-sm text-red-600 dark:text-red-400">
                                    {{ __('profile.twofa_invalid_code') }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <button
                                    type="submit"
                                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                >
                                    {{ __('profile.twofa_confirm') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </template>
            </div>
        @endif
    </section>
</div>
