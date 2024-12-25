<?php

return [
    'default_navigation_group' => null,
    'navigation' => [
        'groups' => [],
        'items' => [
            'dashboard' => [
                'label' => 'Inicio',
                'icon' => 'heroicon-o-home',
                'sort' => -2,
                'isActiveWhen' => fn (): bool => request()->routeIs('filament.pages.dashboard'),
            ],
        ],
    ],
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'pages' => [
            'login' => \Filament\Pages\Auth\Login::class,
        ],
    ],
    
    'middleware' => [
        'auth' => [
            \Filament\Http\Middleware\Authenticate::class,
        ],
        'base' => [
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ],
]; 