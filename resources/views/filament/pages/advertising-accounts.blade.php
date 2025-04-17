<x-filament-panels::page>
    <x-filament::section>
        <h2 class="text-xl font-bold tracking-tight">Seleccionar Cuenta Publicitaria</h2>
        
        @if(count($accounts) > 0)
            <form wire:submit="save" class="space-y-6 mt-4">
                {{ $this->form }}
                
                <div class="flex items-center justify-end gap-x-3 py-3">
                    <x-filament::button type="submit">
                        Guardar selección
                    </x-filament::button>
                </div>
            </form>
        @else
            <div class="rounded-lg bg-gray-50 px-4 py-5 mt-4 text-center">
                <p class="text-sm text-gray-500">
                    No se encontraron cuentas publicitarias disponibles.
                </p>
                <p class="text-sm text-gray-500 mt-2">
                    Necesitas conectar tu cuenta de Facebook primero.
                </p>
                
                <div class="mt-4">
                    <x-filament::button 
                        tag="a"
                        href="{{ route('facebook.login') }}"
                        color="primary"
                    >
                        Conectar con Facebook
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page> 