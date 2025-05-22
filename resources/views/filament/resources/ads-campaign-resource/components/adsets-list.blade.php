<div>
    <x-filament::section>
        <x-slot name="heading">
            Conjuntos de Anuncios
        </x-slot>
        
        @if(empty($adSets))
            <div class="text-center py-4">
                <p>No se encontraron conjuntos de anuncios para esta campaña.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 border border-gray-200 rounded-lg shadow-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Presupuesto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Anuncios</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($adSets as $adSet)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ $adSet['name'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 text-xs rounded-full font-medium
                                        @if($adSet['status'] == 'ACTIVE') bg-green-100 text-green-800
                                        @elseif($adSet['status'] == 'PAUSED') bg-yellow-100 text-yellow-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $adSet['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(!empty($adSet['daily_budget']))
                                        <span class="font-medium">{{ number_format($adSet['daily_budget']/100, 2) }}</span> USD/día
                                    @elseif(!empty($adSet['lifetime_budget']))
                                        <span class="font-medium">{{ number_format($adSet['lifetime_budget']/100, 2) }}</span> USD total
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded-full font-medium">
                                        {{ $adSet['ads_count'] ?? 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button 
                                        type="button"
                                        x-data
                                        x-on:click="$dispatch('open-modal', { id: 'view-ads-{{ $adSet['id'] }}' })"
                                        class="inline-flex items-center px-3 py-2 border border-blue-300 text-sm leading-4 font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
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
                                    console.log('Cargando anuncios para AdSet ID: {{ $adSet['id'] }}');
                                    const response = await fetch(`/api/facebook/adset/{{ $adSet['id'] }}/ads`);
                                    
                                    if (!response.ok) {
                                        const errorData = await response.json();
                                        throw new Error(errorData.message || 'Error desconocido');
                                    }
                                    
                                    const data = await response.json();
                                    console.log('Anuncios recibidos:', data);
                                    this.ads = data;
                                    this.loading = false;
                                } catch (error) {
                                    console.error('Error:', error);
                                    this.error = error.message;
                                    this.loading = false;
                                }
                            }
                        }"
                        x-init="loadAds()"
                    >
                        <div x-show="loading" class="flex justify-center items-center py-12">
                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-solid border-current border-r-transparent text-primary-500 align-[-0.125em]" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                            <span class="ml-3 text-gray-700">Cargando anuncios...</span>
                        </div>
                        
                        <div x-show="error" class="bg-red-50 border-l-4 border-red-500 p-4 my-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm text-red-700" x-text="'Error: ' + error"></p>
                                    <button @click="loadAds()" class="mt-2 text-sm font-medium text-red-700 hover:text-red-600 underline">Reintentar</button>
                                </div>
                            </div>
                        </div>
                        
                        <div x-show="!loading && !error && ads.length === 0" class="grid grid-cols-1 gap-4 mt-4">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay anuncios</h3>
                            <p class="mt-1 text-sm text-gray-500">No se encontraron anuncios para este conjunto.</p>
                        </div>
                        
                        {{-- Lista de anuncios --}}
                        <div x-show="!loading && !error && ads.length > 0" class="grid grid-cols-1 gap-4 mt-4">
                            <template x-for="ad in ads" :key="ad.id">
                                <div class="bg-white overflow-hidden border border-gray-200 rounded-lg shadow hover:shadow-md transition-shadow">
                                    <div class="flex flex-row items-start">
                                        <!-- Imagen del anuncio (más grande) -->
                                        <div class="flex-shrink-0">
                                            <template x-if="ad.image_url || ad.thumbnail_url">
                                                <img 
                                                    :src="ad.image_url || ad.thumbnail_url" 
                                                    :alt="ad.name" 
                                                    class="w-40 h-40 object-cover"
                                                />
                                            </template>
                                            <template x-if="!ad.image_url && !ad.thumbnail_url">
                                                <div class="w-40 h-40 bg-gray-100 flex items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Contenido del anuncio (información a la derecha) -->
                                        <div class="flex-1 p-4">
                                            <h3 x-text="ad.name" class="text-lg font-medium text-gray-900 truncate mb-1"></h3>
                                            <p class="text-xs text-gray-500 mb-3">ID: <span x-text="ad.id"></span></p>
                                            
                                            <!-- Estado -->
                                            <div class="mb-3">
                                                <span class="px-2 py-1 text-xs rounded-full font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-800': ad.status === 'ACTIVE',
                                                        'bg-yellow-100 text-yellow-800': ad.status === 'PAUSED',
                                                        'bg-gray-100 text-gray-800': ad.status !== 'ACTIVE' && ad.status !== 'PAUSED'
                                                    }"
                                                    x-text="ad.status">
                                                </span>
                                            </div>
                                            
                                            <!-- Botón Ver en Facebook (ahora debajo del estado) -->
                                            <template x-if="ad.preview_url">
                                                <a :href="ad.preview_url" target="_blank" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                                                    <span>Ver en Facebook</span>
                                                    <svg class="h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                    </svg>
                                                </a>
                                            </template>
                                            
                                            <!-- Texto del anuncio (si está disponible) -->
                                            <template x-if="ad.creative && ad.creative.body">
                                                <div class="mt-3 text-sm text-gray-600">
                                                    <p class="line-clamp-3" x-text="ad.creative.body"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </x-filament::modal>
            @endforeach
        @endif
    </x-filament::section>
</div>