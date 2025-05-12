@if($account)
<div class="bg-white rounded-lg shadow p-4 mb-6 border-l-4 border-primary-500">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-lg font-semibold flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-primary-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V8a2 2 0 00-2-2h-5L9 4H4zm7 5a1 1 0 10-2 0v1H8a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V9z" clip-rule="evenodd" />
                </svg>
                Cuenta Publicitaria Activa
            </h2>
            <div class="mt-1 text-gray-700">
                <span class="font-medium">{{ $account->name }}</span> 
                <span class="text-gray-500">({{ $account->account_id }})</span>
            </div>
        </div>
        <div class="flex items-center space-x-3">
            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                {{ $account->status == 1 ? 'Activa' : ($account->status == 2 ? 'Deshabilitada' : 'Inactiva') }}
            </span>
            <a href="https://www.facebook.com/adsmanager/manage/campaigns?act={{ str_replace('act_', '', $account->account_id) }}" 
               target="_blank"
               class="text-sm text-primary-600 hover:text-primary-800 inline-flex items-center bg-primary-50 px-3 py-1 rounded-full">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Ver en Ads Manager
            </a>
        </div>
    </div>
</div>
@else
<div class="bg-amber-50 rounded-lg shadow p-4 mb-6 border-l-4 border-amber-500">
    <div class="flex items-center text-amber-700">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
        </svg>
        <span class="font-medium">No hay cuenta publicitaria seleccionada</span>
    </div>
    <p class="mt-1 ml-7 text-sm text-amber-600">
        Selecciona una cuenta para administrar tus campañas publicitarias.
    </p>
</div>
@endif 