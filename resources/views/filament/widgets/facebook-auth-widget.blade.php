<div class="text-center">
    @if(auth()->user()->facebook_access_token)
        <x-filament::button
            color="danger"
            icon="heroicon-m-x-circle"
            wire:click="disconnect"
            wire:confirm="¿Estás seguro que deseas desconectar tu cuenta de Facebook?"
        >
            Desconectar cuenta de Facebook
        </x-filament::button>
    @else
        <x-filament::button
            color="primary"
            icon="heroicon-m-user"
            tag="a"
            href="{{ route('facebook.login') }}"
            class="px-6 py-3 text-lg font-semibold"
        >
            Conectar con Facebook
        </x-filament::button>
    @endif
</div> 