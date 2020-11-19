<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Validator;
use View;

class AppServiceProvider extends ServiceProvider
{
	
    public function boot()
    {	
	
		app('validator')->replacer('without_spaces', function ($message, $attribute, $rule, $parameters) {
            return $attribute.' has wrong format.';
        });
		app('validator')->extend('without_spaces', function ($attribute, $value) {
            return preg_match('/^\S*$/u', $value);
        });
		
		date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));
	}
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    { 
    }
}
