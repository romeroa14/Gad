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
    @endif
</div> 