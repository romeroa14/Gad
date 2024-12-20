<?php

namespace App\Providers;

use FacebookAds\Api;
use Illuminate\Support\ServiceProvider;

class FacebookServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('facebook-ads-api', function ($app) {
            Api::init(
                config('603275022244128'),
                config('f54aa934d5b30c295299bb76390e3806'),
                config('EAAIkrOlnGSABO384PYv8tlXP8XW1InM1dHAxwHOcj9mQiww1gqxI8Xza3kyZBF571k0FYs7LRS1GlFy4ZCXh1zdi1q3Cscn83eDwX9HSDCO2EiBybijgSVTKCeAtV4xgcWMuWFNlsNLLZCWDH361O4DEXCfIeleTngD9pz5y2ATLIEALKxNEFyD9DEFIMf99RzLzsbUSYPmdMuQBRpZAp4i6pPQSsmDQhZBYBBM8TZCgZDZD')
            );
            return Api::instance();
        });
    }
}
