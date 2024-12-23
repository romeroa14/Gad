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
                config('EAAIkrOlnGSABO5606o6Hnh6ajZC5HZCVAPpPLoaFeee3MMkTxi5P050BCPrNy0LjgvUd1cHQvNpNhf3VPVnmaWLsSGmyNZC6JLZCIACZCL2ofnrQgY5PKFlvo95Aax78rFteNeV1a1R1OVPpmjPc0mrgP5SeAVUTqUKuGqXfMuZCrnxYEv6vLM0iEfkGKukoJ9FcvXsqUE0pARMtXuB5a0VTI9TlBqB3iVyBtoZCTID')
            );
            return Api::instance();
        });
    }
}
