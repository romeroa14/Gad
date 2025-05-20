<div class="flex space-x-2">
    <!-- Selector de Cuenta Publicitaria (ya implementado) -->
    
    <!-- Selector de Cliente -->
    @if($this->selectedAccount)
        <div class="relative">
            <button wire:click="toggleClientDropdown" type="button" class="inline-flex items-center...">
                <span>Cliente: {{ $selectedClient ? $selectedClient->name : 'Todos' }}</span>
                <svg...></svg>
            </button>
            
            @if($showClientDropdown)
                <div class="absolute...">
                    <button wire:click="selectClient(null)" class="w-full text-left...">
                        Todos los clientes
                    </button>
                    
                    @foreach($clients as $client)
                        <button wire:click="selectClient({{ $client->id }})" class="w-full text-left...">
                            {{ $client->name }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div> 