<x-filament::page>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Gráfica Principal -->
        <div class="col-span-2 bg-white rounded-lg shadow p-4">
            <div class="h-96">
                <canvas id="mainChart"></canvas>
            </div>
        </div>

        <!-- Métricas Secundarias -->
        <div class="bg-white rounded-lg shadow p-4">
            <div class="h-64">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <div class="h-64">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Aquí va la lógica de las gráficas
        const insights = @json($this->getInsights());
        
        // Configuración de gráficas...
    </script>
    @endpush
</x-filament::page> 