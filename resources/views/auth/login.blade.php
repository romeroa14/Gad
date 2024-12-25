<x-filament-panels::page>
    <x-filament-panels::form>
        <x-filament-panels::section>
            <h2 class="text-2xl font-bold tracking-tight">
                {{ __('Login') }}
            </h2>

            <form method="POST" action="{{ route('login') }}" class="space-y-8">
                @csrf

                <x-filament::input
                    type="email"
                    name="email"
                    label="Email"
                    required
                    autofocus
                />

                <x-filament::input
                    type="password"
                    name="password"
                    label="Password"
                    required
                />

                <div class="flex items-center justify-between">
                    <x-filament::button type="submit">
                        {{ __('Login') }}
                    </x-filament::button>

                    <a href="{{ route('facebook.login') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-medium text-white hover:bg-blue-500">
                        {{ __('Login with Facebook') }}
                    </a>
                </div>
            </form>
        </x-filament-panels::section>
    </x-filament-panels::form>
</x-filament-panels::page> 