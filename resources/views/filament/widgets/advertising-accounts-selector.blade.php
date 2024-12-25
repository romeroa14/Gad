<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-2">
            <h2 class="text-lg font-medium tracking-tight">Cuentas Publicitarias</h2>
            
            @if(count($this->accounts) > 0)
                <form wire:submit="save">
                    {{ $this->form }}
                </form>
            @else
                <p class="text-sm text-gray-500">
                    No se encontraron cuentas publicitarias disponibles.
                </p>
            @endif
        </div>
    </x-filament::card>
</x-filament-widgets::widget> 