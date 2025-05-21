<div class="space-y-4">
    <x-filament::section>
        <x-slot name="heading">
            Conjuntos de Anuncios
        </x-slot>
        
        @if(empty($adSets))
            <div class="text-center py-4">
                <p>No se encontraron conjuntos de anuncios para esta campaña.</p>
            </div>
        @else
            <div class="overflow-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs uppercase bg-gray-100">
                        <tr>
                            <th class="px-4 py-2">Nombre</th>
                            <th class="px-4 py-2">Estado</th>
                            <th class="px-4 py-2">Presupuesto</th>
                            <th class="px-4 py-2"># Anuncios</th>
                            <th class="px-4 py-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adSets as $adSet)
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-4 py-2 font-medium">{{ $adSet['name'] }}</td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 text-xs rounded-full 
                                        @if($adSet['status'] == 'ACTIVE') bg-green-100 text-green-800
                                        @elseif($adSet['status'] == 'PAUSED') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $adSet['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    @if(!empty($adSet['daily_budget']))
                                        {{ number_format($adSet['daily_budget']/100, 2) }} USD/día
                                    @elseif(!empty($adSet['lifetime_budget']))
                                        {{ number_format($adSet['lifetime_budget']/100, 2) }} USD total
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    {{ $adSet['ads_count'] ?? 0 }}
                                </td>
                                <td class="px-4 py-2">
                                    <button 
                                        type="button"
                                        class="text-primary-600 hover:text-primary-900"
                                        x-data
                                        x-on:click="$dispatch('open-modal', { id: 'view-ads-{{ $adSet['id'] }}' })"
                                    >
                                        Ver anuncios
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            {{-- Modales para cada AdSet --}}
            @foreach($adSets as $adSet)
                <x-filament::modal
                    id="view-ads-{{ $adSet['id'] }}"
                    :heading="'Anuncios: ' . $adSet['name']"
                    width="4xl"
                >
                    <div 
                        x-data="{ 
                            ads: [], 
                            loading: true,
                            error: null,
                            async loadAds() {
                                this.loading = true;
                                this.error = null;
                                
                                try {
                                    console.log('Solicitando anuncios para AdSet: {{ $adSet['id'] }}');
                                    const response = await fetch(`/api/facebook/adset/${@js($adSet['id'])}/ads`);
                                    
                                    if (!response.ok) {
                                        const errorData = await response.json();
                                        throw new Error(errorData.message || 'Error desconocido al cargar anuncios');
                                    }
                                    
                                    const data = await response.json();
                                    console.log('Anuncios recibidos:', data);
                                    this.ads = data;
                                    this.loading = false;
                                } catch (error) {
                                    console.error('Error al cargar anuncios:', error);
                                    this.error = error.message || 'Error al cargar anuncios';
                                    this.loading = false;
                                }
                            }
                        }"
                        x-init="loadAds()"
                    >
                        <div x-show="loading" class="text-center py-8">
                            <span class="inline-block animate-spin h-6 w-6 border-2 border-primary-500 rounded-full border-t-transparent"></span>
                            <p class="mt-2">Cargando anuncios...</p>
                        </div>
                        
                        <div x-show="error" class="text-center py-8 text-red-600">
                            <p x-text="'Error: ' + error"></p>
                            <button 
                                @click="loadAds()" 
                                class="mt-2 px-4 py-2 bg-primary-100 text-primary-700 rounded hover:bg-primary-200"
                            >
                                Intentar nuevamente
                            </button>
                        </div>
                        
                        <div x-show="!loading && !error && ads.length === 0" class="text-center py-8">
                            <p>No hay anuncios disponibles para este conjunto.</p>
                        </div>
                        
                        <div x-show="!loading && !error && ads.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="ad in ads" :key="ad.id">
                                <div class="border rounded p-4">
                                    <h3 x-text="ad.name" class="font-medium mb-2"></h3>
                                    <p class="text-xs text-gray-500 mb-2">ID: <span x-text="ad.id"></span></p>
                                    
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="px-2 py-1 text-xs rounded-full"
                                            :class="{
                                                'bg-green-100 text-green-800': ad.status === 'ACTIVE',
                                                'bg-yellow-100 text-yellow-800': ad.status === 'PAUSED',
                                                'bg-gray-100 text-gray-800': ad.status !== 'ACTIVE' && ad.status !== 'PAUSED'
                                            }"
                                            x-text="ad.status">
                                        </span>
                                    </div>
                                    
                                    <template x-if="ad.preview_url">
                                        <a :href="ad.preview_url" target="_blank" class="text-primary-600 hover:text-primary-900 text-sm flex items-center">
                                            <span>Ver en Facebook</span>
                                            <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                            </svg>
                                        </a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-filament::modal>
            @endforeach
        @endif
    </x-filament::section>
</div> 