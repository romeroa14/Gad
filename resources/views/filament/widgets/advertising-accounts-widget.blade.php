<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="text-xl font-bold">
                Cuentas Publicitarias de Facebook
            </div>

            @if($this->getAdvertisingAccounts()->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->getAdvertisingAccounts() as $account)
                        <div class="p-4 bg-white rounded-xl shadow">
                            <div class="font-medium text-lg">{{ $account->name }}</div>
                            <div class="mt-2 space-y-1 text-sm text-gray-600">
                                <div>ID: {{ $account->account_id }}</div>
                                <div>Moneda: {{ $account->currency }}</div>
                                <div>Estado: 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $account->status === 1 ? 'bg-green-100 text-green-800' : 
                                           ($account->status === 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
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
                <div class="text-gray-500">
                    No hay cuentas publicitarias conectadas.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 