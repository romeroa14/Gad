<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-bold">
                    Cuentas Publicitarias de Facebook
                </h2>
                
                @if($this->getAdvertisingAccounts()->count() > 0)
                    <select wire:model.live="selectedAccount" 
                            wire:change="selectAccount($event.target.value)"
                            class="w-64 text-sm bg-gray-400 border-gray-800 rounded-lg shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500 text-gray-200">
                        <option value="">Seleccionar cuenta</option>
                        @foreach($this->getAdvertisingAccounts() as $account)
                            <option value="{{ $account->account_id }}">
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            @if($this->getAdvertisingAccounts()->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->getAdvertisingAccounts() as $account)
                        <div class="p-4 bg-gray-800 rounded-xl shadow-sm border 
                                  {{ $selectedAccount === $account->account_id ? 'border-primary-500 ring-1 ring-primary-500' : 'border-gray-700' }}">
                            <div class="font-medium text-lg text-gray-200">{{ $account->name }}</div>
                            <div class="mt-2 space-y-1 text-gray-400">
                                <div>ID: {{ $account->account_id }}</div>
                                <div>Moneda: {{ $account->currency }}</div>
                                <div>Estado: 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $account->status === 1 ? 'bg-green-900 text-green-300' : 
                                           ($account->status === 2 ? 'bg-yellow-900 text-yellow-300' : 'bg-red-900 text-red-300') }}">
                                        {{ $account->status === 1 ? 'Activo' : 
                                           ($account->status === 2 ? 'Deshabilitado' : 'Inactivo') }}
                                    </span>
                                </div>
                                <div>Zona horaria: {{ $account->timezone }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-gray-400 text-center py-4">
                    No hay cuentas publicitarias conectadas.
                    <a href="{{ route('facebook.login') }}" class="text-primary-500 hover:text-primary-400">
                        Conectar con Facebook
                    </a>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 