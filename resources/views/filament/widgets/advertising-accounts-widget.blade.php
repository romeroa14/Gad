<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium tracking-tight">Cuentas Publicitarias de Facebook</h2>
            </div>

            @if($this->getAdvertisingAccounts()->count() > 0)
                <div x-data="{ open: false, selectedAccount: null }">
                    <!-- Botón principal para abrir el desplegable -->
                    <div class="relative">
                        <button
                            @click="open = !open"
                            type="button"
                            class="inline-flex items-center justify-between w-full rounded-lg border border-gray-300 px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <span x-text="selectedAccount ? selectedAccount.name : 'Seleccionar cuenta publicitaria'">
                                Seleccionar cuenta publicitaria
                            </span>
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>

                        <!-- Lista desplegable -->
                        <div 
                            x-show="open" 
                            @click.outside="open = false"
                            class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto max-h-60"
                            style="display: none;"
                        >
                            @foreach($this->getAdvertisingAccounts() as $account)
                                <div 
                                    @click="selectedAccount = { id: '{{ $account->account_id }}', name: '{{ $account->name }}' }; open = false; $dispatch('account-selected', { accountId: '{{ $account->account_id }}' })"
                                    class="cursor-pointer hover:bg-gray-100 py-2 px-4"
                                >
                                    <div class="font-medium">{{ $account->name }}</div>
                                    <div class="mt-1 text-xs text-gray-600">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $account->status === 1 ? 'bg-green-100 text-green-800' : 
                                               ($account->status === 2 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $account->status === 1 ? 'Activo' : 
                                               ($account->status === 2 ? 'Deshabilitado' : 'Inactivo') }}
                                        </span>
                                        <span class="ml-2">{{ $account->currency }}</span>
                                        <span class="ml-2 text-gray-400">{{ $account->timezone }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Detalles de la cuenta seleccionada -->
                <div x-data="{ accountId: null }" @account-selected.window="accountId = $event.detail.accountId" x-show="accountId" style="display: none;">
                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h3 class="font-medium text-gray-900">Cuenta seleccionada</h3>
                        <p class="text-sm text-gray-500">ID: <span x-text="accountId"></span></p>
                        
                        <!-- Botones de acción -->
                        <div class="mt-3 flex space-x-2">
                            <button 
                                type="button"
                                @click="window.location.href = '/admin/advertising-accounts/' + accountId"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700"
                            >
                                Ver detalles
                            </button>
                            <button 
                                type="button"
                                @click="window.location.href = '/admin/campaigns/create?account_id=' + accountId"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-primary-700 bg-primary-50 hover:bg-primary-100"
                            >
                                Crear campaña
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-gray-500 py-2">
                    No hay cuentas publicitarias conectadas.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 