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
            <div class="overflow-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Presupuesto</th>
                            <th>Anuncios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($adSets as $adSet)
                            <tr>
                                <td>{{ $adSet['name'] }}</td>
                                <td>{{ $adSet['status'] }}</td>
                                <td>
                                    @if(!empty($adSet['daily_budget']))
                                        {{ number_format($adSet['daily_budget']/100, 2) }} USD/día
                                    @elseif(!empty($adSet['lifetime_budget']))
                                        {{ number_format($adSet['lifetime_budget']/100, 2) }} USD total
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $adSet['ads_count'] ?? 0 }}</td>
                                <td>
                                    <button 
                                        type="button"
                                        x-data
                                        x-on:click="$dispatch('open-modal', { id: 'view-ads-{{ $adSet['id'] }}' })"
                                        class="bg-blue-500 text-black px-4 py-2 rounded hover:bg-blue-600"
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
                        <div x-show="loading">Cargando...</div>
                        <div x-show="error" class="text-red-600">
                            <p x-text="'Error: ' + error"></p>
                            <button @click="loadAds()" class="text-primary-600">Reintentar</button>
                        </div>
                        <div x-show="!loading && !error && ads.length === 0">No hay anuncios</div>
                        <div x-show="!loading && !error && ads.length > 0">
                            <template x-for="ad in ads" :key="ad.id">
                                <div>
                                    <span x-text="ad.name"></span>
                                    <div>
                                        <a :href="ad.preview_url" target="_blank" class="flex items-center gap-2">
                                            Ver en Facebook
                                            <img :src="ad.thumbnail_url" alt="Thumbnail" class="w-16 h-16">
                                        </a>
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