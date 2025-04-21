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
            <div class="text-xl font-bold">
                Cuentas Publicitarias de Facebook
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
                                    <div>
                                        Estado: 
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                            :class="getStatusClasses(account.status)"
                                            x-text="formatStatus(account.status)">
                                        </span>
                                    </div>
                                    <div x-text="'Zona horaria: ' + account.timezone"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            @else
                <div class="text-gray-500">
                    No hay cuentas publicitarias conectadas.
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 