<?php
/*
|----------------------------------------------------------------------------
| TopWindow [ Internet Ecological traffic aggregation and sharing platform ]
|----------------------------------------------------------------------------
| Copyright (c) 2006-2019 http://yangrong1.cn All rights reserved.
|----------------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
|----------------------------------------------------------------------------
| Author: yangrong <yangrong2@gmail.com>
|----------------------------------------------------------------------------
*/
namespace Learn\Input;

use Illuminate\Support\ServiceProvider;
class InputServiceProvider extends ServiceProvider
{
    /**
     * 启动服务
     *
     * @return void
     */
    public function boot()
    {
        $source = realpath($raw = dirname(__DIR__, 2) . '/config/input.php') ?: $raw;
        if ($this->app->runningInConsole()) {
            $this->publishes([$source => config_path('laravel-input.php')], 'laravel-input');
        }
        if (!$this->app->configurationIsCached()) {
            $this->mergeConfigFrom($source, 'laravel-input');
        }
    }
    /**
     * 注册服务
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('laravel-input', function ($app) {
            $request = $app['request'];
            $evil = $app['config']->get('laravel-input.evil', null);
            $replace = $app['config']->get('laravel-input.replace', null);
            $input = new Input($request, $evil, $replace);
            $app->refresh('request', $input, 'withRequest');
            return $input;
        });
        $this->app->alias('laravel-input', Input::class);
    }
    /**
     * 获取服务
     *
     * @return string[]
     */
    public function provides()
    {
        return ['laravel-input'];
    }
}