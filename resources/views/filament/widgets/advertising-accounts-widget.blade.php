<x-filament-widgets::widget>
    <x-filament::section>
        <div x-data="{ 
            selectedAccount: null,
            accounts: {{ json_encode($this->getAdvertisingAccounts()) }},
            formatStatus(status) {
                if (status === 1) return 'Activo';
                if (status === 2) return 'Deshabilitado';
                return 'Inactivo';
            },
            getStatusClasses(status) {
                if (status === 1) return 'bg-green-100 text-green-800';
                if (status === 2) return 'bg-yellow-100 text-yellow-800';
                return 'bg-red-100 text-red-800';
            }
        }" class="space-y-4">
            <div class="text-xl font-bold flex justify-between items-center">
                <span>Cuentas Publicitarias de Facebook</span>
                <span class="text-sm text-gray-500" x-text="accounts.length + ' cuentas encontradas'"></span>
            </div>

            @if($this->getAdvertisingAccounts()->count() > 0)
                <div class="space-y-6">
                    <div>
                        <label for="account-select" class="block text-sm font-medium text-gray-700 mb-1">Seleccionar cuenta:</label>
                        <select 
                            id="account-select" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-1 focus:ring-primary-500" 
                            x-model="selectedAccount"
                        >
                            <option value="" disabled selected>Selecciona una cuenta publicitaria</option>
                            <template x-for="account in accounts" :key="account.id">
                                <option :value="account.id" x-text="account.name + ' (' + account.account_id + ')'"></option>
                            </template>
                        </select>
                    </div>
                    
                    <!-- Detalles de la cuenta seleccionada -->
                    <div 
                        x-show="selectedAccount !== null" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="bg-white rounded-xl shadow p-4"
                    >
                        <template x-for="account in accounts" :key="account.id">
                            <div x-show="selectedAccount == account.id">
                                <div class="font-medium text-lg" x-text="account.name"></div>
                                <div class="mt-2 space-y-1 text-sm text-gray-600">
                                    <div x-text="'ID: ' + account.account_id"></div>
                                    <div x-text="'Moneda: ' + account.currency"></div>
                                    <div class="flex items-center space-x-1">
                                        <span>Estado:</span> 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getStatusClasses(account.status)"
                                            x-text="formatStatus(account.status)">
                                        </span>
                                    </div>
                                    <div x-text="'Zona horaria: ' + account.timezone"></div>
                                    <div x-text="'Actualizado: ' + new Date(account.updated_at).toLocaleString()"></div>
                                </div>
                                
                                <!-- Acciones de la cuenta -->
                                <div class="mt-4 flex space-x-2">
                                    <button 
                                        class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600"
                                        @click="window.open('https://www.facebook.com/adsmanager/manage/campaigns?act=' + account.account_id, '_blank')"
                                    >
                                        Ver en Ads Manager
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @else
                <div class="text-gray-500 bg-gray-50 rounded-lg p-4 text-center">
                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 22a10 10 0 1 1 0-20 10 10 0 0 1 0 20z"></path>
                    </svg>
                    <p>No hay cuentas publicitarias conectadas.</p>
                    <p class="text-sm mt-2">Conecta tu cuenta de Facebook para ver tus cuentas publicitarias.</p>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 